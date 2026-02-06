<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Application;

use SoftwareFoundation\Kernel\Ports\CachePort;

/**
 * Prevents duplicate command execution using idempotency keys.
 * Stores results keyed by idempotency key with a TTL of 24 hours.
 */
final class IdempotencyGuard
{
    private const TTL_SECONDS = 86400; // 24 hours

    public function __construct(
        private readonly CachePort $cache,
    ) {}

    /** Check if this command was already processed. Returns cached result or null. */
    public function check(Command $command): mixed
    {
        $key = $this->cacheKey($command);
        return $this->cache->get($key);
    }

    /** Record the result of a command execution. */
    public function record(Command $command, mixed $result): void
    {
        $key = $this->cacheKey($command);
        $this->cache->set($key, $result, self::TTL_SECONDS);
    }

    private function cacheKey(Command $command): string
    {
        return 'idempotency:' . $command->idempotencyKey();
    }
}
