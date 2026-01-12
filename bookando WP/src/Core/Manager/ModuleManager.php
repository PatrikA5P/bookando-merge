<?php

namespace Bookando\Core\Manager;

use Bookando\Core\Base\BaseModule;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Service\ActivityLogger;

class ModuleManager
{
    private static ?self $instance = null;
    /**
     * @var array<string, BaseModule>
     */
    private array $modules = [];
    private ModuleStateRepository $stateRepository;

    private function __construct()
    {
        $this->stateRepository = ModuleStateRepository::instance();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Liefert alle aktivierten Modul-Slugs aus der Option
     */
    private function getActiveSlugs(): array
    {
        return $this->stateRepository->getActiveSlugs();
    }

    /**
     * Scannt alle vorhandenen Module im Dateisystem (src/modules/<slug>/module.json)
     *
     * @return list<string>
     */
    public function scanModules(): array
    {
        $modulesDir = BOOKANDO_PLUGIN_DIR . '/src/modules/';
        $found = [];
        foreach (glob($modulesDir . '*/module.json') as $file) {
            $slug = basename(dirname($file));
            if (self::isLegacySlug($slug)) {
                continue;
            }
            $found[] = $slug;
        }
        return $found;
    }

    /**
     * Lädt alle aktivierten Module, prüft Lizenz und Feature-Flags, initialisiert sie
     */
    public function loadModules(): void
    {
        // Wenn schon geladen, zweiten Lauf überspringen
        if (!empty($this->modules)) {
            ActivityLogger::info('modules.manager', 'loadModules() skipped – already loaded');
            return;
        }

        $resolved = [];

        foreach ($this->getActiveSlugs() as $slug) {
            if (self::isLegacySlug($slug)) {
                ActivityLogger::info('modules.manager', 'Legacy module skipped', ['slug' => $slug]);
                continue;
            }
            $manifestFile = BOOKANDO_PLUGIN_DIR . "/src/modules/{$slug}/module.json";

            if (!file_exists($manifestFile)) {
                ActivityLogger::warning('modules.manager', 'Module skipped: manifest missing', ['slug' => $slug]);
                continue;
            }

            try {
                $manifest = new ModuleManifest($slug);
                $resolved[$slug] = $manifest;

                // Installzeit pro Modul speichern
                $key = 'bookando_module_installed_at_' . strtolower($slug);
                $installedLegacy = (int) get_option($key);
                if (!$installedLegacy) {
                    update_option($key, time());
                }
                $this->stateRepository->recordInstallation($slug, $installedLegacy ?: null);

            } catch (\Throwable $e) {
                ActivityLogger::error('modules.manager', 'Module failed to load', [
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Abhängigkeiten prüfen
        $disabledDueToDependencies = [];
        $needsValidation = true;

        while ($needsValidation) {
            $needsValidation = false;

            foreach ($resolved as $slug => $manifest) {
                if (isset($disabledDueToDependencies[$slug])) {
                    continue;
                }

                foreach ($manifest->getDependencies() as $dep) {
                    if (isset($resolved[$dep])) {
                        continue;
                    }

                    $message = "Modul '$slug' erfordert fehlendes Modul '$dep'.";
                    ActivityLogger::error('modules.manager', $message, [
                        'slug' => $slug,
                        'dependency' => $dep,
                    ]);

                    $disabledDueToDependencies[$slug] = true;
                    unset($resolved[$slug]);
                    $this->stateRepository->deactivate($slug);

                    ActivityLogger::warning('modules.manager', 'Module disabled due to missing dependency', [
                        'slug' => $slug,
                        'dependency' => $dep,
                    ]);

                    $needsValidation = true;
                    // Stop validating the current module as it has already been disabled.
                    break;
                }
            }
        }

        // Initialisieren (nur erlaubte Module mit erfüllten Feature-Abhängigkeiten)
        foreach ($resolved as $slug => $manifest) {
            if (!LicenseManager::isModuleAllowed($slug)) {
                ActivityLogger::warning('modules.manager', 'Module disabled due to license', ['slug' => $slug]);
                continue;
            }
            // Features required prüfen
            foreach ($manifest->getFeaturesRequired() as $feature) {
                if (!LicenseManager::isFeatureEnabled($feature)) {
                    ActivityLogger::warning('modules.manager', 'Module disabled: missing feature', [
                        'slug' => $slug,
                        'feature' => $feature,
                    ]);
                    continue 2;
                }
            }
            $class = $manifest->getFqcn();
            if (!class_exists($class)) {
                ActivityLogger::error('modules.manager', 'Module class missing', ['class' => $class]);
                continue;
            }
            if (!is_subclass_of($class, \Bookando\Core\Base\BaseModule::class)) {
                ActivityLogger::warning('modules.manager', 'Module class does not extend BaseModule', ['class' => $class]);
                continue;
            }

            $this->modules[$slug] = new $class();
            $this->modules[$slug]->boot();
            $this->stateRepository->activate($slug);
        }
    }

    public function getModule(string $slug): ?BaseModule
    {
        return $this->modules[$slug] ?? null;
    }

    /**
     * @return array<string, BaseModule>
     */
    public function getAllModules(): array
    {
        return $this->modules;
    }

    public function activate(string $slug): bool
    {
        return $this->stateRepository->activate($slug);
    }

    public function deactivate(string $slug): bool
    {
        return $this->stateRepository->deactivate($slug);
    }

    public function isActive(string $slug): bool
    {
        return $this->stateRepository->isActive($slug);
    }

    public function getManifest(string $slug): ModuleManifest
    {
        return new ModuleManifest($slug);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAvailableModules(): array
    {
        return array_map(
            fn(ModuleManifest $m) => $m->toArray(),
            ModuleManifest::all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getVisibleModules(): array
    {
        return array_map(
            fn(ModuleManifest $m) => $m->toArray(),
            ModuleManifest::visible()
        );
    }

    public function requiresLicense(string $slug): bool
    {
        return (new ModuleManifest($slug))->requiresLicense() !== false;
    }

    public function getRemainingTrialDays(string $slug): ?int
    {
        $installedAt = $this->stateRepository->getInstalledAt($slug);
        if (!$installedAt) {
            return null;
        }

        $graceSeconds = LicenseManager::GRACE_PERIOD_DAYS * DAY_IN_SECONDS;
        $remaining = ($installedAt + $graceSeconds) - time();

        return $remaining > 0 ? (int) ceil($remaining / DAY_IN_SECONDS) : 0;
    }

    public static function isLegacySlug(string $slug): bool
    {
        return str_ends_with(strtolower($slug), '_old');
    }

    /**
     * Gibt alle Meta-Infos (module.json) als Array zurück.
     *
     * @return array<string, mixed>
     */
    public function getModuleMeta(string $slug): array
    {
        try {
            return $this->getManifest($slug)->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Gibt alle Modul-Abhängigkeiten zurück.
     *
     * @return list<string>
     */
    public function getModuleDependencies(string $slug): array
    {
        try {
            return $this->getManifest($slug)->getDependencies();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Gibt die Feature-Abhängigkeiten (Lizenz-Flags) zurück.
     *
     * @return list<string>
     */
    public function getModuleFeaturesRequired(string $slug): array
    {
        try {
            return $this->getManifest($slug)->getFeaturesRequired();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Gibt die Modulgruppe (core, offers, crm, ...) zurück.
     */
    public function getModuleGroup(string $slug): ?string
    {
        try {
            return $this->getManifest($slug)->getGroup();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Gibt für alle gefundenen Module das Meta-Array zurück.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllModuleMetas(): array
    {
        $metas = [];
        foreach ($this->scanModules() as $slug) {
            $metas[$slug] = $this->getModuleMeta($slug);
        }
        return $metas;
    }

}
