<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Ports\LoggerPort;

/**
 * Null logger for testing. Optionally records log entries for assertions.
 */
final class NullLogger implements LoggerPort
{
    /** @var array<int, array{level: string, message: string, context: array}> */
    private array $entries = [];

    /** @var array<int, array{action: string, context: array}> */
    private array $audits = [];

    private ?string $correlationId = null;

    public function log(string $level, string $message, array $context = []): void
    {
        $this->entries[] = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function audit(string $action, array $context): void
    {
        $this->audits[] = ['action' => $action, 'context' => $context];
    }

    public function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
    }

    // --- Test helpers ---

    /** @return array[] */
    public function entries(): array
    {
        return $this->entries;
    }

    /** @return array[] */
    public function audits(): array
    {
        return $this->audits;
    }

    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    public function hasEntry(string $level, string $messageContains): bool
    {
        foreach ($this->entries as $entry) {
            if ($entry['level'] === $level && str_contains($entry['message'], $messageContains)) {
                return true;
            }
        }
        return false;
    }

    public function reset(): void
    {
        $this->entries = [];
        $this->audits = [];
        $this->correlationId = null;
    }
}
