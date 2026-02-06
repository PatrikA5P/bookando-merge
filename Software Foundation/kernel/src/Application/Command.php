<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Application;

/**
 * Marker interface for all commands (write operations).
 * Every command MUST carry tenantId and idempotencyKey.
 */
interface Command
{
    public function tenantId(): int;
    public function idempotencyKey(): string;
}
