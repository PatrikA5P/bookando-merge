<?php

namespace Bookando\Core\Manager;

class ModuleManifest
{
    protected string $slug;
    protected array $data = [];

    /** @var array<string, array> */
    private static array $manifestCache = [];

    public function __construct(string $slug)
    {
        $this->slug = $slug;

        $this->data = self::loadManifestData($slug);
    }

    protected static function getDefaultData(string $slug): array
    {
        return [
            'slug'             => $slug,
            'name'             => ucfirst($slug),
            'version'          => '1.0.0',
            'description'      => '',
            'group'            => null,
            'plan'             => null,
            'license_required' => false,
            'features_required'=> [],
            'always_active'    => false,
            'visible'          => true,
            'tenant_required'  => false,
            'dependencies'     => [],
            'tabs'             => [],
            'actions'          => [],
        ];
    }

    protected static function loadManifestData(string $slug): array
    {
        if (isset(self::$manifestCache[$slug])) {
            return self::$manifestCache[$slug];
        }

        $file = BOOKANDO_PLUGIN_DIR . "/src/modules/{$slug}/module.json";
        if (!file_exists($file) || !is_readable($file)) {
            throw new \RuntimeException("Manifest for module '{$slug}' not found or unreadable.");
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read manifest for module '{$slug}'.");
        }

        try {
            $raw = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(
                "Invalid JSON in manifest for module '{$slug}'.",
                0,
                $exception
            );
        }

        return self::$manifestCache[$slug] = array_merge(self::getDefaultData($slug), $raw);
    }

    public function getSlug(): string
    {
        return $this->data['slug'];
    }

    public function getName(string $locale = 'default'): string
    {
        return $this->translate($this->data['name'], $locale);
    }

    public function getDescription(string $locale = 'default'): string
    {
        return $this->translate($this->data['description'], $locale);
    }

    public function getVersion(): string
    {
        return $this->data['version'];
    }

    public function getGroup(): ?string
    {
        return $this->data['group'] ?? null;
    }

    public function getPlan(): ?string
    {
        return $this->data['plan'] ?? null;
    }

    public function isVisible(): bool
    {
        return (bool) $this->data['visible'];
    }

    public function requiresLicense(): bool|string
    {
        return $this->data['license_required'] ?? false;
    }

    public function getFeaturesRequired(): array
    {
        return $this->data['features_required'] ?? [];
    }

    public function isAlwaysActive(): bool
    {
        return (bool) ($this->data['always_active'] ?? false);
    }

    public function isTenantRequired(): bool
    {
        return (bool) ($this->data['tenant_required'] ?? false);
    }

    public function getDependencies(): array
    {
        return $this->data['dependencies'] ?? [];
    }

    public function getTabs(): array
    {
        return $this->data['tabs'] ?? [];
    }

    public function getActions(): array
    {
        $actions = $this->data['actions'] ?? [];

        return is_array($actions) ? $actions : [];
    }

    public function toArray(): array
    {
        return $this->data;
    }

    protected function translate(array|string $value, string $locale): string
    {
        if (is_array($value)) {
            return $value[$locale] ?? $value['default'] ?? reset($value);
        }
        return $value;
    }

    /**
     * Scan all modules and return array of ModuleManifest instances
     */
    public static function all(): array
    {
        $base = BOOKANDO_PLUGIN_DIR . '/src/modules/';
        $list = [];

        foreach (glob($base . '*/module.json') as $file) {
            $slug = basename(dirname($file));
            if (ModuleManager::isLegacySlug($slug)) {
                continue;
            }
            try {
                $manifest = new self($slug);
                $list[$slug] = $manifest;
            } catch (\Throwable $e) {
                // Optional: Log or skip invalid module.json
            }
        }

        return $list;
    }

    /**
     * Return all visible modules
     */
    public static function visible(): array
    {
        return array_filter(self::all(), fn(self $m) => $m->isVisible());
    }

    /**
     * Return all slugs
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }

        /**
     * Gibt den Fully Qualified Class Name (FQCN) des Hauptmoduls zurück.
     * Standard: Bookando\Modules\<slug>\Module
     */
    public function getFqcn(): string
    {
        return "Bookando\\Modules\\{$this->slug}\\Module";
    }

}
