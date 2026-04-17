<?php

/**
 * Integration tests for LockingRedisSessionHandler against a real Redis Sentinel cluster.
 *
 * These tests verify behaviors that mocks cannot cover:
 *  - The lock key is actually written to Redis during read() with a positive TTL
 *  - The lock key is atomically removed after write(), updateTimestamp(), destroy(), and close()
 *  - A second read() for the same session ID throws while the first read()'s lock is still held
 *    (mutual exclusion)
 *  - The Lua release script is a no-op when the stored token has been replaced by another owner
 *    (token mismatch prevents accidental release of a lock we no longer own)
 *
 * These tests are registered under the 'redis-sentinel' PHPUnit testsuite and invoked by
 * build_test_redis_failover() in ci/ciLibrary.source before the failover steps, so they only
 * run inside the OpenEMR container when the full sentinel cluster is available.  They are
 * excluded from the 'common' testsuite to prevent double-execution in sentinel CI configs.
 *
 * Redis client construction mirrors SentinelUtil but omits sentinel-level AUTH: the CI sentinel
 * containers have no requirepass, so only the master password is forwarded.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2025 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Common\Session\Predis;

use OpenEMR\Common\Session\Predis\LockingRedisSessionHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Psr\Log\NullLogger;

/**
 * Combined inner-handler interface as required by LockingRedisSessionHandler.
 */
interface InnerHandlerInterface extends \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface {}

class LockingRedisSessionHandlerIntegrationTest extends TestCase
{
    private Client $redis;

    /**
     * Fixed session ID used across all tests.
     * A short, recognisable prefix makes it easy to spot in redis-cli KEYS output.
     */
    private const SESSION_ID = 'oetest-lock-intg';

    private function lockKey(): string
    {
        return 'lock_' . self::SESSION_ID;
    }

    // =========================================================================
    // Test lifecycle
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = $this->buildClient();

        // Remove any stale key left by a previous failed run
        $this->redis->del($this->lockKey());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Always clean up, even when a test fails mid-way
        if (isset($this->redis)) {
            $this->redis->del($this->lockKey());
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a Predis sentinel client from the env vars set by the CI compose stack.
     *
     * Sentinel-level AUTH is intentionally omitted: the CI sentinel containers have no
     * requirepass directive, so sending AUTH would return an error.  Only the master
     * password (which the master does require) is forwarded.
     */
    private function buildClient(): Client
    {
        $rawSentinels = (string) getenv('REDIS_SENTINELS');
        $masterName   = (string) (getenv('REDIS_MASTER') ?: 'mymaster');
        $masterPass   = getenv('REDIS_MASTER_PASSWORD') ?: null;

        $sentinelParameters = array_map(
            static fn(string $host): array => [
                'scheme' => 'tcp',
                'host'   => trim($host),
                'port'   => 26379,
            ],
            explode('|||', $rawSentinels),
        );

        $options = ['replication' => 'sentinel', 'service' => $masterName];
        if ($masterPass !== null) {
            $options['parameters'] = ['password' => $masterPass];
        }

        return new Client($sentinelParameters, $options);
    }

    /**
     * @return InnerHandlerInterface&MockObject
     */
    private function makeInner(): InnerHandlerInterface&MockObject
    {
        $mock = $this->createMock(InnerHandlerInterface::class);
        $mock->method('read')->willReturn('');
        $mock->method('write')->willReturn(true);
        $mock->method('updateTimestamp')->willReturn(true);
        $mock->method('destroy')->willReturn(true);
        $mock->method('close')->willReturn(true);
        return $mock;
    }

    private function makeHandler(int $maxLockWaitSeconds = 10): LockingRedisSessionHandler
    {
        return new LockingRedisSessionHandler(
            $this->redis,
            $this->makeInner(),
            lockTtlSeconds: 30,
            maxLockWaitSeconds: $maxLockWaitSeconds,
            logger: new NullLogger(),
        );
    }

    // =========================================================================
    // Lock key lifecycle — verifies the key actually appears and disappears in Redis
    // =========================================================================

    public function testLockKeyExistsInRedisAfterRead(): void
    {
        $handler = $this->makeHandler();
        $handler->read(self::SESSION_ID);

        $this->assertSame(1, $this->redis->exists($this->lockKey()), 'lock key must be present in Redis after read()');
        $this->assertGreaterThan(0, $this->redis->pttl($this->lockKey()), 'lock key must carry a positive TTL');

        $handler->write(self::SESSION_ID, '');
    }

    public function testLockKeyRemovedAfterWrite(): void
    {
        $handler = $this->makeHandler();
        $handler->read(self::SESSION_ID);
        $handler->write(self::SESSION_ID, '');

        $this->assertSame(0, $this->redis->exists($this->lockKey()), 'lock key must be gone from Redis after write()');
    }

    public function testLockKeyRemovedAfterUpdateTimestamp(): void
    {
        $handler = $this->makeHandler();
        $handler->read(self::SESSION_ID);
        $handler->updateTimestamp(self::SESSION_ID, '');

        $this->assertSame(0, $this->redis->exists($this->lockKey()), 'lock key must be gone from Redis after updateTimestamp()');
    }

    public function testLockKeyRemovedAfterDestroy(): void
    {
        $handler = $this->makeHandler();
        $handler->read(self::SESSION_ID);
        $handler->destroy(self::SESSION_ID);

        $this->assertSame(0, $this->redis->exists($this->lockKey()), 'lock key must be gone from Redis after destroy()');
    }

    public function testLockKeyRemovedAfterClose(): void
    {
        $handler = $this->makeHandler();
        $handler->read(self::SESSION_ID);
        $handler->close();

        $this->assertSame(0, $this->redis->exists($this->lockKey()), 'lock key must be gone from Redis after close()');
    }

    // =========================================================================
    // Mutual exclusion — a second reader cannot acquire the lock while held
    // =========================================================================

    public function testSecondReadThrowsWhileLockIsHeld(): void
    {
        $holder = $this->makeHandler();
        $holder->read(self::SESSION_ID);  // acquires lock

        // A second handler with zero wait budget cannot acquire the same lock
        $waiter = $this->makeHandler(maxLockWaitSeconds: 0);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Failed to acquire Redis session lock');
            $waiter->read(self::SESSION_ID);
        } finally {
            $holder->write(self::SESSION_ID, '');  // release so tearDown can clean up cleanly
        }
    }

    public function testSecondReadSucceedsAfterFirstReleasesLock(): void
    {
        $handlerA = $this->makeHandler();
        $handlerA->read(self::SESSION_ID);
        $handlerA->write(self::SESSION_ID, '');  // releases lock

        // Lock is now free; a second handler should acquire it without throwing
        $handlerB = $this->makeHandler();
        $handlerB->read(self::SESSION_ID);

        $this->assertSame(1, $this->redis->exists($this->lockKey()), 'handler B must hold the lock after handler A released it');

        $handlerB->write(self::SESSION_ID, '');
    }

    // =========================================================================
    // Lua atomicity — release must be a no-op when we no longer own the lock
    // =========================================================================

    public function testLuaReleaseIsNoOpWhenTokenMismatches(): void
    {
        $handler = $this->makeHandler();
        $handler->read(self::SESSION_ID);  // acquires lock, stores token X internally

        // Simulate another process replacing the lock value after our TTL expired
        // and re-acquiring the key with its own token.
        $this->redis->set($this->lockKey(), 'foreign-token');

        // write() triggers releaseLock(): the Lua script calls GET and compares to
        // our original token X.  'foreign-token' != X, so DEL is not executed.
        $handler->write(self::SESSION_ID, '');

        $this->assertSame(1, $this->redis->exists($this->lockKey()), 'lock key must survive when the stored token does not match ours');
        // tearDown will del() the key
    }
}
