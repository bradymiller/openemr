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

    // =========================================================================
    // open() / gc() / validateId() — no lock interaction
    // =========================================================================

    public function testOpenDelegatesToInner(): void
    {
        $this->inner->expects($this->once())->method('open')->with('/path', 'sess')->willReturn(true);
        $this->redis->expects($this->never())->method('set');

        $this->assertTrue($this->handler->open('/path', 'sess'));
    }

    public function testGcDelegatesToInner(): void
    {
        $this->inner->expects($this->once())->method('gc')->with(3600)->willReturn(5);
        $this->redis->expects($this->never())->method('set');

        $this->assertSame(5, $this->handler->gc(3600));
    }

    public function testValidateIdDelegatesToInner(): void
    {
        $this->inner->expects($this->once())->method('validateId')->with('abc123')->willReturn(true);
        $this->redis->expects($this->never())->method('set');

        $this->assertTrue($this->handler->validateId('abc123'));
    }

    // =========================================================================
    // read() — acquires lock, then delegates
    // =========================================================================

    public function testReadAcquiresLockThenDelegates(): void
    {
        // SET returns non-null (OK) on first attempt — lock acquired immediately
        $this->redis->expects($this->once())
            ->method('set')
            ->with(
                $this->stringStartsWith('lock_'),
                $this->isType('string'), // token
                'PX',
                30000,
                'NX'
            )
            ->willReturn('OK');

        $this->inner->expects($this->once())
            ->method('read')
            ->with('sess123')
            ->willReturn('session-data');

        $result = $this->handler->read('sess123');

        $this->assertSame('session-data', $result);
    }

    public function testReadUsesCorrectLockKey(): void
    {
        $this->redis->expects($this->once())
            ->method('set')
            ->with('lock_my-session-id', $this->anything(), 'PX', $this->anything(), 'NX')
            ->willReturn('OK');

        $this->inner->method('read')->willReturn('');

        $this->handler->read('my-session-id');
    }

    // =========================================================================
    // write() — writes then releases lock via Lua
    // =========================================================================

    public function testWriteReleasesLockAfterWriting(): void
    {
        $this->redis->method('set')->willReturn('OK');

        $this->inner->expects($this->once())
            ->method('write')
            ->with('sess123', 'data')
            ->willReturn(true);

        // Lua release script must be called exactly once
        $this->redis->expects($this->once())
            ->method('eval')
            ->with(
                $this->stringContains('redis.call("GET"'),
                1,
                'lock_sess123',
                $this->isType('string') // token
            );

        $this->handler->read('sess123');
        $result = $this->handler->write('sess123', 'data');

        $this->assertTrue($result);
    }

    public function testWriteReleasesLockEvenWhenInnerThrows(): void
    {
        $this->redis->method('set')->willReturn('OK');
        $this->inner->method('write')->willThrowException(new \RuntimeException('Redis write failed'));

        $this->redis->expects($this->once())->method('eval');

        $this->handler->read('sess123');

        $this->expectException(\RuntimeException::class);
        $this->handler->write('sess123', 'data');
    }

    // =========================================================================
    // updateTimestamp() — extends TTL then releases lock
    // =========================================================================

    public function testUpdateTimestampReleasesLock(): void
    {
        $this->redis->method('set')->willReturn('OK');

        $this->inner->expects($this->once())
            ->method('updateTimestamp')
            ->with('sess123', 'data')
            ->willReturn(true);

        $this->redis->expects($this->once())->method('eval');

        $this->handler->read('sess123');
        $result = $this->handler->updateTimestamp('sess123', 'data');

        $this->assertTrue($result);
    }

    // =========================================================================
    // destroy() — destroys session then releases lock
    // =========================================================================

    public function testDestroyReleasesLock(): void
    {
        $this->redis->method('set')->willReturn('OK');

        $this->inner->expects($this->once())
            ->method('destroy')
            ->with('sess123')
            ->willReturn(true);

        $this->redis->expects($this->once())->method('eval');

        $this->handler->read('sess123');
        $result = $this->handler->destroy('sess123');

        $this->assertTrue($result);
    }

    // =========================================================================
    // close() — the read_and_close path
    // =========================================================================

    public function testCloseReleasesLockWhenLockIsStillHeld(): void
    {
        // Simulate read_and_close: read acquires lock, close releases it (no write)
        $this->redis->method('set')->willReturn('OK');
        $this->inner->method('read')->willReturn('data');
        $this->inner->method('close')->willReturn(true);

        // eval (lock release) must be called once, from close()
        $this->redis->expects($this->once())->method('eval');

        $this->handler->read('sess123');
        $this->handler->close();
    }

    public function testCloseIsNoOpForLockWhenWriteAlreadyReleasedIt(): void
    {
        // Normal flow: read → write (releases lock) → close (no-op for lock)
        $this->redis->method('set')->willReturn('OK');
        $this->inner->method('read')->willReturn('data');
        $this->inner->method('write')->willReturn(true);
        $this->inner->method('close')->willReturn(true);

        // eval called once by write(), NOT again by close()
        $this->redis->expects($this->once())->method('eval');

        $this->handler->read('sess123');
        $this->handler->write('sess123', 'data');
        $this->handler->close();
    }

    public function testCloseWithoutPriorReadDoesNotCallEval(): void
    {
        $this->inner->method('close')->willReturn(true);
        $this->redis->expects($this->never())->method('eval');

        $this->handler->close();
    }

    // =========================================================================
    // Lock timeout — throws when spin-wait expires
    // =========================================================================

    public function testThrowsWhenLockCannotBeAcquiredWithinTimeout(): void
    {
        // SET always returns null — lock is permanently held by another request
        $this->redis->method('set')->willReturn(null);

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
        $this->redis->method('set')->willReturn(null);
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
        $capturedArgs = [];

        $this->redis->method('set')->willReturn('OK');
        $this->inner->method('read')->willReturn('');
        $this->inner->method('write')->willReturn(true);

        $this->redis->expects($this->once())
            ->method('eval')
            ->willReturnCallback(function () use (&$capturedArgs): int {
                $capturedArgs = func_get_args();
                return 1;
            });

        $this->handler->read('sess-xyz');
        $this->handler->write('sess-xyz', '');

        // KEYS[1] should be the lock key
        $this->assertSame('lock_sess-xyz', $capturedArgs[2]);
        // ARGV[1] should be a non-empty hex token
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $capturedArgs[3]);
    }
}
