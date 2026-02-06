<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Domain\Sequence\SequenceGap;
use SoftwareFoundation\Kernel\Ports\SequencePort;

/**
 * In-memory sequence generator for testing. Gap-free by construction.
 */
final class InMemorySequence implements SequencePort
{
    private const DEFAULT_PATTERN = '{PREFIX}-{YYYY}-{000000}';

    /** @var array<string, int> "tenantId:prefix:year" => counter */
    private array $counters = [];

    /** @var array<string, string> prefix => format pattern */
    private array $formats = [];

    public function next(int $tenantId, string $prefix, int $year): string
    {
        $key = "{$tenantId}:{$prefix}:{$year}";
        $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;

        return $this->format($prefix, $year, $this->counters[$key]);
    }

    public function current(int $tenantId, string $prefix, int $year): int
    {
        return $this->counters["{$tenantId}:{$prefix}:{$year}"] ?? 0;
    }

    /**
     * @return SequenceGap[]
     */
    public function detectGaps(int $tenantId, string $prefix, int $year): array
    {
        // In-memory sequences are gap-free by construction.
        return [];
    }

    public function setFormat(string $prefix, string $pattern): void
    {
        $this->formats[$prefix] = $pattern;
    }

    // --- Test helpers ---

    /** Reset a specific sequence counter to zero. */
    public function reset(string $prefix, int $year, int $tenantId): void
    {
        unset($this->counters["{$tenantId}:{$prefix}:{$year}"]);
    }

    private function format(string $prefix, int $year, int $counter): string
    {
        $pattern = $this->formats[$prefix] ?? self::DEFAULT_PATTERN;

        // Determine zero-padding width from the placeholder (e.g. {000000} => 6).
        $padWidth = 1;
        if (preg_match('/\{(0+)\}/', $pattern, $matches)) {
            $padWidth = strlen($matches[1]);
        }

        $formatted = str_replace('{PREFIX}', strtoupper($prefix), $pattern);
        $formatted = str_replace('{YYYY}', (string) $year, $formatted);
        $formatted = str_replace(
            '{' . str_repeat('0', $padWidth) . '}',
            str_pad((string) $counter, $padWidth, '0', STR_PAD_LEFT),
            $formatted,
        );

        return $formatted;
    }
}
