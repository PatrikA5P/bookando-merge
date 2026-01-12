<?php
declare(strict_types=1);

namespace Bookando\Core\Base;

use Bookando\Core\Helper\Manifest;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Manager\ModuleManifest;

/**
 * Abstrakte Basisklasse für alle Bookando-Module.
 * - Zentrale Asset-Logik (JS/CSS für DEV/PROD)
 * - Erkennt automatisch den Modul-Slug aus dem Namespace
 * - Vite Dev-Server Erkennung
 * - Lädt ALLE zu einem Entry gehörenden CSS-Dateien (inkl. Import-Chunks)
 * - Übergibt BOOKANDO_VARS non-destruktiv
 */
abstract class BaseModule
{
    /**
     * Bootstraps the module. By default we simply forward to {@see register()}.
     *
     * Individual modules can still override this method when they require
     * additional boot logic, but the vast majority of modules share the same
     * behaviour. Centralising this logic keeps the concrete Module classes
     * tidy and prevents every module from duplicating a one-line method.
     */
    public function boot(): void
    {
        $this->register();
    }

    abstract public function register(): void;

    /**
     * Helper to register a capability class via the {@code init} hook.
     *
     * Capabilities are idempotent and inexpensive, therefore we keep the
     * registration lazy and guard against missing classes. Using a dedicated
     * helper avoids anonymous functions sprinkled throughout all modules and
     * makes it obvious where capabilities should be wired up.
     *
     * @param class-string $capabilityClass
     */
    protected function registerCapabilities(string $capabilityClass): void
    {
        add_action('init', static function () use ($capabilityClass): void {
            if (is_callable([$capabilityClass, 'register'])) {
                $capabilityClass::register();
            }
        });
    }

    /**
     * Helper to register admin specific hooks when a module exposes a custom
     * menu and Vue application. The callback is wrapped in an {@code is_admin}
     * check to ensure the code is only executed for backend requests.
     *
     * @param callable():void $callback
     */
    protected function registerAdminHooks(callable $callback): void
    {
        if (!is_admin()) {
            return;
        }

        $callback();
    }

    /**
     * Helper to register REST routes. The callback is hooked into
     * {@code rest_api_init} to follow WordPress best practices.
     *
     * @param callable():void $callback
     */
    protected function registerRestRoutes(callable $callback): void
    {
        add_action('rest_api_init', $callback);
    }

    /**
     * Lädt die Assets für das aktuelle Modul (DEV & PROD, mit Vite-Fallback).
     *
     * @hook admin_enqueue_scripts
     *
     * @param string|null $slug
     */
    protected function enqueue_module_assets(?string $slug = null): void
    {
        $slug = $slug ?? $this->getSlug();
        $targetPage = "bookando_{$slug}";

        if (!$this->isCurrentModuleScreen($targetPage)) {
            return;
        }

        if (!$this->userCanAccessModule($slug)) {
            return;
        }

        // Nonce check removed: Asset loading doesn't require nonce validation.
        // Security is already ensured by:
        // 1. isCurrentModuleScreen() - validates we're on the correct admin page
        // 2. userCanAccessModule() - validates user has correct capability
        // Nonces are meant for state-changing operations (forms, AJAX), not static asset loading.

        $manifest = $this->loadModuleManifest($slug);

        // DEV-Flag aus wp-config.php
        $is_dev_flag = (defined('BOOKANDO_DEV') && BOOKANDO_DEV === true);

        // --- Vite DEV-Server Quick-Check ---
        $vite_alive = $is_dev_flag ? $this->isViteDevServerAvailable() : false;
        $is_dev = $is_dev_flag && $vite_alive;

        // === CSS ===
        // DEV: kein separates CSS → Vite injiziert CSS via JS-Imports automatisch.
        if (!$is_dev) {
            $cssFiles = $this->collect_module_css_from_manifest($slug);

            // Fallbacks falls Manifest fehlt / kein Treffer (legacy Layouts)
            if (empty($cssFiles)) {
                $cssFiles = $this->collect_module_css_legacy($slug);
            }

            // Enqueue (nach Admin-UI, damit Admin-Basis zuerst kommt)
            foreach ($cssFiles as $i => $css) {
                wp_enqueue_style(
                    "bookando-{$slug}-style-" . ($i + 1),
                    $css['url'],
                    ['bookando-admin-ui'],
                    $css['ver']
                );
            }
        }

        // === JS ===
        // Wichtig: DIESELBE URL für alle Loader – OHNE ?ver=…
        $script_url = $is_dev
            ? "http://localhost:5173/{$slug}/main.js"
            : plugins_url("dist/{$slug}/main.js", BOOKANDO_PLUGIN_FILE);

        $script_handle = "bookando-{$slug}-app";

        // Bridge & Vue als Dependencies, damit die Bridge garantiert vorher da ist
        // $ver => FALSE: WordPress hängt KEIN ?ver=… an
        wp_register_script(
            $script_handle,
            $script_url,
            ['bookando-admin-bridge'],
            false,
            true
        );

        // Sicherheitsnetz: falls irgendwo global doch ein ver= angehängt wird,
        // entfernen wir es NUR für diesen Handle.
        add_filter('script_loader_src', static function (string $src, string $handle) use ($script_handle): string {
            if ($handle === $script_handle) {
                $src = remove_query_arg('ver', $src);
            }
            return $src;
        }, 10, 2);

        // Non-destruktives Merge von Modulwerten in BOOKANDO_VARS
        $merge = $this->buildModuleVars($slug, $manifest);

        wp_add_inline_script(
            $script_handle,
            '(function(add){' .
                'var w = window; w.BOOKANDO_VARS = w.BOOKANDO_VARS || {};' .
                // Sprache NIE überschreiben (Bridge ist führend)
                'delete add.lang; delete add.wp_locale;' .
                // gezieltes Mergen nur der Modulwerte
                'for (var k in add){ if (Object.prototype.hasOwnProperty.call(add, k)) { w.BOOKANDO_VARS[k] = add[k]; } }' .
            '})( ' . wp_json_encode($merge) . ' );',
            'before'
        );

        wp_enqueue_script($script_handle);

        if ($is_dev_flag && !$vite_alive) {
            error_log("[Bookando:{$slug}] WARNUNG: BOOKANDO_DEV ist true, aber Vite läuft nicht. Fallback auf PROD-Assets aktiviert!");
        }
    }

    /**
     * Prüft, ob der lokale Vite Dev-Server erreichbar ist.
     *
     * Nutzt die WordPress HTTP-API, damit Deployments ohne ext/curl funktionieren.
     * Das Ergebnis wird zweifach gecached:
     * 1. Static Cache (pro Request) - verhindert mehrfache HTTP-Calls im selben Request
     * 2. Transient Cache (5 Minuten) - persistent über mehrere Requests
     *
     * Optimierung: Cache von 30s → 5min erhöht, da Vite-Server selten crasht.
     */
    private function isViteDevServerAvailable(): bool
    {
        // 1. Static Cache (schnellster) - gilt für alle Modul-Instanzen im Request
        static $globalRequestCache = null;

        if ($globalRequestCache !== null) {
            return $globalRequestCache;
        }

        // 2. Transient Cache (5 Minuten statt 30 Sekunden)
        $transientKey = 'bookando_vite_alive';
        $cached       = get_transient($transientKey);
        if ($cached !== false) {
            return $globalRequestCache = (bool) $cached;
        }

        // 3. HTTP-Check (nur wenn beide Caches leer)
        $url     = 'http://localhost:5173/';
        $args    = ['timeout' => 0.5];
        $result  = false;
        $response = wp_remote_head($url, $args);

        // Fallback auf GET, wenn HEAD nicht funktioniert
        if (is_wp_error($response) || !$this->isSuccessfulHttpResponse($response)) {
            $response = wp_remote_get($url, $args);
        }

        if (!is_wp_error($response)) {
            $result = $this->isSuccessfulHttpResponse($response);
        }

        // Cache für 5 Minuten (300 Sekunden)
        set_transient($transientKey, $result ? '1' : '0', 300);

        return $globalRequestCache = $result;
    }

    private function isSuccessfulHttpResponse($response): bool
    {
        $code = (int) wp_remote_retrieve_response_code($response);

        return $code >= 200 && $code < 500;
    }

    /**
     * Lädt das Modul-Manifest für den übergebenen Slug.
     */
    protected function loadModuleManifest(string $slug): ?ModuleManifest
    {
        try {
            return new ModuleManifest($slug);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Baut die Inline-Variablen für das Modul auf Basis des Manifests auf.
     *
     * @return array{
     *     module_allowed: bool,
     *     required_plan: string,
     *     features_required: list<string>,
     *     tabs: array<array-key, mixed>,
     *     ajax_url: string,
     *     rest_url: string,
     *     rest_url_base: string,
     *     rest_root: string,
     *     iconBase: string,
     *     slug: string
     * }
     */
    protected function buildModuleVars(string $slug, ?ModuleManifest $manifest): array
    {
        $requiredPlan = 'starter';
        $featuresRequired = ['export_csv'];
        $tabs = [];
        $moduleActions = null;

        if ($manifest instanceof ModuleManifest) {
            $plan = $manifest->getPlan();
            if (is_string($plan) && $plan !== '') {
                $requiredPlan = $plan;
            }

            $manifestFeatures = array_values(array_filter(
                (array) $manifest->getFeaturesRequired(),
                static fn($feature) => is_string($feature) && $feature !== ''
            ));
            if (!empty($manifestFeatures)) {
                $featuresRequired = $manifestFeatures;
            }

            $manifestTabs = $manifest->getTabs();
            if (is_array($manifestTabs) && !empty($manifestTabs)) {
                $tabs = $manifestTabs;
            }

            $manifestActions = $manifest->getActions();
            if (is_array($manifestActions) && !empty($manifestActions)) {
                $moduleActions = $manifestActions;
            }
        }

        $licenseData = LicenseManager::getLicenseData();
        $rawFeatures = is_array($licenseData['features'] ?? null)
            ? $licenseData['features']
            : [];

        $licenseFeatures = array_values(array_filter(
            array_map(
                static fn($feature) => is_string($feature) ? trim($feature) : '',
                $rawFeatures
            ),
            static fn($feature) => $feature !== ''
        ));

        return [
            'module_allowed'    => $this->isModuleAllowed($slug),
            'required_plan'     => $requiredPlan,
            'features_required' => $featuresRequired,
            'module_actions'    => $moduleActions,
            'license_features'  => $licenseFeatures,
            'tabs'              => $tabs,
            'ajax_url'          => admin_url('admin-ajax.php'),
            'rest_url'          => rest_url("bookando/v1/{$slug}"),
            'rest_url_base'     => rest_url('bookando/v1'),
            'rest_root'         => rest_url(),
            'iconBase'          => trailingslashit(plugins_url('src/Core/Design/assets/icons', BOOKANDO_PLUGIN_FILE)),
            'slug'              => $slug,
        ];
    }

    /**
     * Prüft, ob das Modul laut Lizenz aktiviert werden darf.
     */
    protected function isModuleAllowed(string $slug): bool
    {
        return LicenseManager::isModuleAllowed($slug);
    }

    /**
     * Sucht alle CSS-Dateien eines Modul-Entrys über das Vite-Manifest
     * (inkl. CSS aus importierten Chunks).
     *
     * @return list<array{url: string, ver: int}>
     */
    protected function collect_module_css_from_manifest(string $slug): array
    {
        $dist_dir = plugin_dir_path(BOOKANDO_PLUGIN_FILE) . 'dist/';
        $dist_url = plugins_url('dist/', BOOKANDO_PLUGIN_FILE);

        $manifest = Manifest::load($dist_dir);
        if (!is_array($manifest)) {
            return [];
        }

        // Den richtigen Key finden (Entry für dieses Modul)
        // Typische src: src/modules/{slug}/assets/vue/main.ts
        $entryKey = null;
        foreach ($manifest as $key => $val) {
            if (empty($val['isEntry'])) continue;
            $src = $val['src'] ?? '';
            if ($src && (str_contains($src, "/modules/{$slug}/assets/vue/main.")
                      || str_ends_with($src, "/{$slug}/main.ts")
                      || str_ends_with($src, "/{$slug}/main.js"))) {
                $entryKey = $key;
                break;
            }
        }
        if ($entryKey === null) {
            // kein Treffer → leer
            return [];
        }

        // Rekursiv CSS aus Entry + Imports einsammeln
        $seen = [];     // CSS-Dateien
        $css  = [];
        $visited = [];  // Manifest-Knoten

        $pushCss = static function (string $rel) use ($dist_dir, $dist_url, &$css, &$seen): void {
            $rel = ltrim($rel, '/');
            if (isset($seen[$rel])) return;
            $file = $dist_dir . $rel;
            if (file_exists($file)) {
                $css[] = ['url' => $dist_url . $rel, 'ver' => filemtime($file)];
                $seen[$rel] = true;
            }
        };

        $walk = static function (string $key) use (&$walk, $manifest, $pushCss, &$visited): void {
            if (empty($manifest[$key])) return;

            // ✅ Verhindert Wiederholung/Endlosschleifen
            if (isset($visited[$key])) return;
            $visited[$key] = true;

            $node = $manifest[$key];

            // zuerst Imports (Basis vor Entry)
            foreach (array_merge($node['imports'] ?? [], $node['dynamicImports'] ?? []) as $imp) {
                if (is_string($imp) && $imp !== '') {
                    $walk($imp);
                }
            }

            // CSS des aktuellen Nodes
            if (!empty($node['css'])) {
                $list = is_array($node['css']) ? $node['css'] : [$node['css']];
                foreach ($list as $rel) {
                    if (is_string($rel) && $rel !== '') {
                        $pushCss($rel);
                    }
                }
            }
        };

        $walk($entryKey);

        return $css;
    }

    /**
     * Fallback-Suche für alte Layouts (ohne Manifest-Kette)
     *
     * @return list<array{url: string, ver: int}>
     */
    protected function collect_module_css_legacy(string $slug): array
    {
        $dist_dir = plugin_dir_path(BOOKANDO_PLUGIN_FILE) . 'dist/';
        $dist_url = plugins_url('dist/', BOOKANDO_PLUGIN_FILE);
        $out      = [];

        // dist/{slug}/main.css
        $legacy = $dist_dir . "{$slug}/main.css";
        if (file_exists($legacy)) {
            $out[] = [
                'url' => $dist_url . "{$slug}/main.css",
                'ver' => filemtime($legacy),
            ];
        }

        // dist/assets/{slug}-*.css
        if (empty($out)) {
            foreach (glob($dist_dir . "assets/{$slug}-*.css") ?: [] as $file) {
                $rel = 'assets/' . basename($file);
                $out[] = [
                    'url' => $dist_url . $rel,
                    'ver' => filemtime($file),
                ];
            }
        }

        return $out;
    }

    /**
     * Slug automatisch aus dem Namespace.
     * Annahme: ...\Modules\<slug>\Module
     */
    protected function getSlug(): string
    {
        $parts = explode('\\', static::class);
        return isset($parts[2]) ? strtolower($parts[2]) : 'unknown';
    }

    private function isCurrentModuleScreen(string $expectedPage): bool
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (is_object($screen)) {
            $candidates = [];
            foreach (['id', 'base', 'post_type'] as $property) {
                if (isset($screen->{$property}) && is_string($screen->{$property}) && $screen->{$property} !== '') {
                    $candidates[] = $screen->{$property};
                }
            }

            foreach ($candidates as $candidate) {
                if ($candidate === $expectedPage) {
                    return true;
                }

                if (str_ends_with($candidate, "_{$expectedPage}")) {
                    return true;
                }
            }
        }

        $page = $this->readRequestString('page');

        return $page !== '' && $page === $expectedPage;
    }

    private function userCanAccessModule(string $slug): bool
    {
        if (!function_exists('current_user_can')) {
            return true;
        }

        $capability = "manage_bookando_{$slug}";

        return current_user_can($capability);
    }

    private function hasValidModuleNonce(string $slug): bool
    {
        // Debug-Logging initialisieren
        if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
            \Bookando\Core\Service\DebugLogger::init();
            \Bookando\Core\Service\DebugLogger::startTimer('asset_nonce_check');
        }

        // Wenn Nonce-Validierung deaktiviert ist (für Debugging), immer true zurückgeben
        if (defined('BOOKANDO_DISABLE_MODULE_NONCE') && BOOKANDO_DISABLE_MODULE_NONCE === true) {
            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::logAsset('nonce_disabled', "module-{$slug}", [
                    'slug' => $slug,
                    'status' => 'BOOKANDO_DISABLE_MODULE_NONCE is active - bypassing nonce check',
                ]);
            }
            return true;
        }

        if (!function_exists('wp_verify_nonce')) {
            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::logAsset('wp_verify_nonce_missing', "module-{$slug}", [
                    'slug' => $slug,
                    'status' => 'wp_verify_nonce not available - allowing by default',
                ]);
            }
            return true;
        }

        // WICHTIG: $isNonce = true verhindert sanitize_text_field() auf dem Nonce
        $nonce = $this->readRequestString('_wpnonce', true);

        $action = "bookando_module_assets_{$slug}";

        if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
            \Bookando\Core\Service\DebugLogger::logAsset('nonce_read_for_validation', "module-{$slug}", [
                'slug' => $slug,
                'action' => $action,
                'nonce_empty' => $nonce === '',
                'nonce_length' => strlen($nonce),
                'nonce_preview' => $nonce !== '' ? substr($nonce, 0, 10) . '...' : 'EMPTY',
            ]);
        }

        if ($nonce === '') {
            if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
                \Bookando\Core\Service\DebugLogger::stopTimer('asset_nonce_check');
                \Bookando\Core\Service\DebugLogger::logAsset('nonce_empty_assets_blocked', "module-{$slug}", [
                    'slug' => $slug,
                    'action' => $action,
                    'status' => 'BLOCKED - No nonce in request',
                    'result' => false,
                ]);
            }
            return false;
        }

        $verifyResult = wp_verify_nonce($nonce, $action);

        if (class_exists(\Bookando\Core\Service\DebugLogger::class)) {
            \Bookando\Core\Service\DebugLogger::stopTimer('asset_nonce_check');
            \Bookando\Core\Service\DebugLogger::logAsset('nonce_verification_result', "module-{$slug}", [
                'slug' => $slug,
                'action' => $action,
                'nonce_preview' => substr($nonce, 0, 10) . '...',
                'verify_result' => $verifyResult ? 'VALID' : 'INVALID',
                'nonce_age' => $verifyResult,
                'status' => $verifyResult ? 'ALLOWED - Assets will load' : 'BLOCKED - Assets will not load',
            ]);
        }

        return (bool) $verifyResult;
    }

    /**
     * Liest einen Request-Parameter (GET/POST) und sanitiert ihn.
     *
     * Nutzt die zentrale Helper-Funktion bookando_read_sanitized_request()
     * um Code-Duplikation zu vermeiden.
     *
     * @param string $key
     * @param bool $isNonce Wenn true, wird der Wert nicht mit sanitize_text_field() behandelt
     * @return string
     */
    private function readRequestString(string $key, bool $isNonce = false): string
    {
        return bookando_read_sanitized_request($key, $isNonce);
    }
}
