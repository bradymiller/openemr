<?php

/**
 * Isolated tests for LockingRedisSessionHandler
 *
 * Tests that LockingRedisSessionHandler correctly:
 *  - Acquires a Redis lock before reading session data
 *  - Releases the lock atomically via Lua script after writing
 *  - Releases the lock after updateTimestamp (lazy_write path)
 *  - Releases the lock after destroy
 *  - Releases the lock in close() for the read_and_close path
 *  - Is a no-op in close() when the lock was already released
 *  - Throws when spin-wait expires without acquiring the lock
 *  - Delegates open(), gc(), validateId() without any lock interaction
 *
 * All Redis and inner-handler interactions are verified with mocks — no
 * real Redis connection or Docker environment is required.
 *
 * Note: Predis\ClientInterface dispatches all commands (set, eval, …) through
 * __call(), so tests mock __call() and inspect recorded calls rather than
 * mocking individual command methods that don't exist on the interface.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2025 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Common\Session\Predis;

use OpenEMR\Common\Session\Predis\LockingRedisSessionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\ClientInterface;
use Psr\Log\NullLogger;

/**
 * Combined inner-handler interface as used by LockingRedisSessionHandler.
 */
interface InnerHandlerInterface extends \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface {}

class LockingRedisSessionHandlerTest extends TestCase
{
    private ClientInterface&MockObject $redis;
    private InnerHandlerInterface&MockObject $inner;
    private LockingRedisSessionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->createMock(ClientInterface::class);
        $this->inner = $this->createMock(InnerHandlerInterface::class);

        $this->handler = new LockingRedisSessionHandler(
            $this->redis,
            $this->inner,
            lockTtlSeconds: 30,
            maxLockWaitSeconds: 5,
            logger: new NullLogger(),
        );
    }

    /**
     * Configure the Redis mock to record every __call() invocation and return
     * sensible defaults: 'OK' for SET NX (lock acquired), 1 for EVAL (lock released).
     *
     * @param list<array{cmd: string, args: list<mixed>}> $calls Passed by reference; populated on each __call invocation
     */
    private function configureRedisTracking(array &$calls): void
    {
        $this->redis->method('__call')
            ->willReturnCallback(
                static function (string $cmd, array $args) use (&$calls): mixed {
                    $calls[] = ['cmd' => $cmd, 'args' => $args];
                    return $cmd === 'set' ? 'OK' : 1;
                }
            );
    }

    /**
     * Filter recorded __call invocations by command name and re-index.
     *
     * @param list<array{cmd: string, args: list<mixed>}> $calls
     * @return list<array{cmd: string, args: list<mixed>}>
     */
    private function callsFor(array $calls, string $cmd): array
    {
        return array_values(array_filter($calls, static fn(array $c): bool => $c['cmd'] === $cmd));
    }

    // =========================================================================
    // open() / gc() / validateId() — no lock interaction
    // =========================================================================

    public function testOpenDelegatesToInner(): void
    {
        $redisCalled = false;
        $this->redis->method('__call')->willReturnCallback(
            static function () use (&$redisCalled): mixed {
                $redisCalled = true;
                return null;
            }
        );
        $this->inner->expects($this->once())->method('open')->with('/path', 'sess')->willReturn(true);

        $this->assertTrue($this->handler->open('/path', 'sess'));
        $this->assertFalse($redisCalled, 'Redis should not be called for open()');
    }

    public function testGcDelegatesToInner(): void
    {
        $redisCalled = false;
        $this->redis->method('__call')->willReturnCallback(
            static function () use (&$redisCalled): mixed {
                $redisCalled = true;
                return null;
            }
        );
        $this->inner->expects($this->once())->method('gc')->with(3600)->willReturn(5);

        $this->assertSame(5, $this->handler->gc(3600));
        $this->assertFalse($redisCalled, 'Redis should not be called for gc()');
    }

    public function testValidateIdDelegatesToInner(): void
    {
        $redisCalled = false;
        $this->redis->method('__call')->willReturnCallback(
            static function () use (&$redisCalled): mixed {
                $redisCalled = true;
                return null;
            }
        );
        $this->inner->expects($this->once())->method('validateId')->with('abc123')->willReturn(true);

        $this->assertTrue($this->handler->validateId('abc123'));
        $this->assertFalse($redisCalled, 'Redis should not be called for validateId()');
    }

    // =========================================================================
    // read() — acquires lock, then delegates
    // =========================================================================

    public function testReadAcquiresLockThenDelegates(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->expects($this->once())->method('read')->with('sess123')->willReturn('session-data');

        $result = $this->handler->read('sess123');

        $this->assertSame('session-data', $result);

        $setCalls = $this->callsFor($calls, 'set');
        $this->assertCount(1, $setCalls, 'SET NX should be called once for lock acquisition');
        $this->assertSame('lock_sess123', $setCalls[0]['args'][0]);
        $this->assertIsString($setCalls[0]['args'][1]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $setCalls[0]['args'][1]);
        $this->assertSame('PX', $setCalls[0]['args'][2]);
        $this->assertSame('NX', $setCalls[0]['args'][4]);
    }

    public function testReadUsesCorrectLockKey(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->method('read')->willReturn('');

        $this->handler->read('my-session-id');

        $setCalls = $this->callsFor($calls, 'set');
        $this->assertCount(1, $setCalls);
        $this->assertSame('lock_my-session-id', $setCalls[0]['args'][0]);
    }

    // =========================================================================
    // write() — writes then releases lock via Lua
    // =========================================================================

    public function testWriteReleasesLockAfterWriting(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->expects($this->once())->method('write')->with('sess123', 'data')->willReturn(true);

        $this->handler->read('sess123');
        $result = $this->handler->write('sess123', 'data');

        $this->assertTrue($result);

        $evalCalls = $this->callsFor($calls, 'eval');
        $this->assertCount(1, $evalCalls, 'Lua release script should be called once after write');
        $this->assertIsString($evalCalls[0]['args'][0]);
        $this->assertStringContainsString('redis.call("GET"', $evalCalls[0]['args'][0]);
        $this->assertSame('lock_sess123', $evalCalls[0]['args'][2]);
    }

    public function testWriteReleasesLockEvenWhenInnerThrows(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->method('write')->willThrowException(new \RuntimeException('Redis write failed'));

        $this->handler->read('sess123');

        try {
            $this->handler->write('sess123', 'data');
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException) {
            // expected
        }

        $evalCalls = $this->callsFor($calls, 'eval');
        $this->assertCount(1, $evalCalls, 'Lock must be released even when write throws');
    }

    // =========================================================================
    // updateTimestamp() — extends TTL then releases lock
    // =========================================================================

    public function testUpdateTimestampReleasesLock(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->expects($this->once())->method('updateTimestamp')->with('sess123', 'data')->willReturn(true);

        $this->handler->read('sess123');
        $result = $this->handler->updateTimestamp('sess123', 'data');

        $this->assertTrue($result);
        $this->assertCount(1, $this->callsFor($calls, 'eval'), 'Lock must be released after updateTimestamp');
    }

    // =========================================================================
    // destroy() — destroys session then releases lock
    // =========================================================================

    public function testDestroyReleasesLock(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->expects($this->once())->method('destroy')->with('sess123')->willReturn(true);

        $this->handler->read('sess123');
        $result = $this->handler->destroy('sess123');

        $this->assertTrue($result);
        $this->assertCount(1, $this->callsFor($calls, 'eval'), 'Lock must be released after destroy');
    }

    // =========================================================================
    // close() — the read_and_close path
    // =========================================================================

    public function testCloseReleasesLockWhenLockIsStillHeld(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->method('read')->willReturn('data');
        $this->inner->method('close')->willReturn(true);

        $this->handler->read('sess123');
        $this->handler->close();

        $this->assertCount(1, $this->callsFor($calls, 'eval'), 'Lock must be released by close() in read_and_close path');
    }

    public function testCloseIsNoOpForLockWhenWriteAlreadyReleasedIt(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->method('read')->willReturn('data');
        $this->inner->method('write')->willReturn(true);
        $this->inner->method('close')->willReturn(true);

        $this->handler->read('sess123');
        $this->handler->write('sess123', 'data');
        $this->handler->close();

        $this->assertCount(1, $this->callsFor($calls, 'eval'), 'eval called once by write(), not again by close()');
    }

    public function testCloseWithoutPriorReadDoesNotCallRedis(): void
    {
        $redisCalled = false;
        $this->redis->method('__call')->willReturnCallback(
            static function () use (&$redisCalled): mixed {
                $redisCalled = true;
                return null;
            }
        );
        $this->inner->method('close')->willReturn(true);

        $this->handler->close();

        $this->assertFalse($redisCalled, 'Redis should not be called by close() when no lock was acquired');
    }

    // =========================================================================
    // Lock timeout — throws when spin-wait expires
    // =========================================================================

    public function testThrowsWhenLockCannotBeAcquiredWithinTimeout(): void
    {
        // __call returns null for every command — SET NX never succeeds
        $this->redis->method('__call')->willReturn(null);

        $fastHandler = new LockingRedisSessionHandler(
            $this->redis,
            $this->inner,
            lockTtlSeconds: 30,
            maxLockWaitSeconds: 0,
            logger: new NullLogger(),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to acquire Redis session lock');

        $fastHandler->read('sess123');
    }

    public function testInnerReadIsNotCalledWhenLockTimesOut(): void
    {
        $this->redis->method('__call')->willReturn(null);
        $this->inner->expects($this->never())->method('read');

        $fastHandler = new LockingRedisSessionHandler(
            $this->redis,
            $this->inner,
            maxLockWaitSeconds: 0,
            logger: new NullLogger(),
        );

        try {
            $fastHandler->read('sess123');
        } catch (\RuntimeException) {
            // expected
        }
    }

    // =========================================================================
    // Lua release script correctness
    // =========================================================================

    public function testLuaScriptPassesCorrectKeysAndArgs(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->method('read')->willReturn('');
        $this->inner->method('write')->willReturn(true);

        $this->handler->read('sess-xyz');
        $this->handler->write('sess-xyz', '');

        $evalCalls = $this->callsFor($calls, 'eval');
        $this->assertCount(1, $evalCalls);

        // KEYS[1] should be the lock key
        $this->assertSame('lock_sess-xyz', $evalCalls[0]['args'][2]);
        // ARGV[1] should be a 32-char hex token from bin2hex(random_bytes(16))
        $this->assertIsString($evalCalls[0]['args'][3]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $evalCalls[0]['args'][3]);
    }

    public function testLuaTokenMatchesTokenUsedInSetNx(): void
    {
        /** @var list<array{cmd: string, args: list<mixed>}> $calls */
        $calls = [];
        $this->configureRedisTracking($calls);
        $this->inner->method('read')->willReturn('');
        $this->inner->method('write')->willReturn(true);

        $this->handler->read('sess-xyz');
        $this->handler->write('sess-xyz', '');

        $setCalls  = $this->callsFor($calls, 'set');
        $evalCalls = $this->callsFor($calls, 'eval');

        // The token written to the lock key must be the same token checked in the Lua release
        $this->assertSame($setCalls[0]['args'][1], $evalCalls[0]['args'][3]);
    }
}
