<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\Application;

use PHPUnit\Framework\TestCase;
use SoftwareFoundation\Kernel\Application\Command;
use SoftwareFoundation\Kernel\Application\IdempotencyGuard;
use SoftwareFoundation\Kernel\Tests\TestAdapters\InMemoryCache;

final class IdempotencyGuardTest extends TestCase
{
    private function stubCommand(string $idempotencyKey, int $tenantId = 1): Command
    {
        return new class($idempotencyKey, $tenantId) implements Command {
            public function __construct(
                private readonly string $key,
                private readonly int $tenant,
            ) {}

            public function tenantId(): int
            {
                return $this->tenant;
            }

            public function idempotencyKey(): string
            {
                return $this->key;
            }
        };
    }

    public function test_returns_null_for_new_command(): void
    {
        $guard = new IdempotencyGuard(new InMemoryCache());
        $command = $this->stubCommand('key-1');

        $this->assertNull($guard->check($command));
    }

    public function test_returns_cached_result_for_duplicate(): void
    {
        $cache = new InMemoryCache();
        $guard = new IdempotencyGuard($cache);
        $command = $this->stubCommand('key-1');

        $guard->record($command, ['id' => 42]);

        $result = $guard->check($command);
        $this->assertSame(['id' => 42], $result);
    }

    public function test_different_keys_are_independent(): void
    {
        $cache = new InMemoryCache();
        $guard = new IdempotencyGuard($cache);

        $cmd1 = $this->stubCommand('key-1');
        $cmd2 = $this->stubCommand('key-2');

        $guard->record($cmd1, 'result-1');

        $this->assertSame('result-1', $guard->check($cmd1));
        $this->assertNull($guard->check($cmd2));
    }
}
