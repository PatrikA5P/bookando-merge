<?php
declare(strict_types=1);
namespace SoftwareFoundation\Kernel\Contracts;

/**
 * Defines what a module declares about itself.
 * Every module MUST provide a manifest (typically loaded from module.json).
 */
final class ModuleManifest
{
    public function __construct(
        public readonly string $slug,
        public readonly string $version,
        public readonly string $name,
        public readonly string $description,
        public readonly array $licensing,      // min_plan, features, quotas, integrations
        public readonly array $capabilities,   // permission strings
        public readonly array $events,         // publishes, subscribes
        public readonly array $migrations,     // migration file list
        public readonly array $dependencies,   // kernel version, required modules (should be empty ideally)
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            slug: $data['slug'],
            version: $data['version'] ?? '1.0.0',
            name: $data['name'] ?? $data['slug'],
            description: $data['description'] ?? '',
            licensing: $data['licensing'] ?? [],
            capabilities: $data['capabilities'] ?? [],
            events: $data['events'] ?? ['publishes' => [], 'subscribes' => []],
            migrations: $data['migrations'] ?? [],
            dependencies: $data['dependencies'] ?? [],
        );
    }

    public static function fromJsonFile(string $path): self
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Cannot read manifest file: {$path}");
        }
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        return self::fromArray($data);
    }

    public function minPlan(): string
    {
        return $this->licensing['min_plan'] ?? 'starter';
    }

    /** @return string[] */
    public function publishedEvents(): array
    {
        return $this->events['publishes'] ?? [];
    }

    /** @return string[] */
    public function subscribedEvents(): array
    {
        return $this->events['subscribes'] ?? [];
    }
}
