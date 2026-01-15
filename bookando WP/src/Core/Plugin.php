<?php

declare(strict_types=1);

namespace Bookando\Core;

use Bookando\Core\Base\BaseAdmin;
use Bookando\Core\Helper\Manifest;
use Bookando\Core\Loader;
use Bookando\Core\Helper\HelperPathResolver;
use Bookando\Core\Admin\Menu;
use Bookando\Core\Admin\LogsPage;
use Bookando\Core\Manager\ModuleManager;
use Bookando\Core\Dispatcher\AjaxDispatcher;
use Bookando\Core\Dispatcher\RestDispatcher;
use Bookando\Core\Dispatcher\WebhookDispatcher;
use Bookando\Core\Dispatcher\PublicDispatcher;
use Bookando\Core\Dispatcher\CronDispatcher;
use Bookando\Core\Dispatcher\AdminDispatcher;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Service\ActivityLogger;
use Bookando\Helper\Template;

class Plugin
{
    protected Loader $loader;

    private Template $templateHelper;

    private const GENERAL_SETTINGS_CACHE_KEY = 'bookando_general_settings';
    private const GENERAL_SETTINGS_CACHE_GROUP = 'bookando';
    private const GENERAL_SETTINGS_CACHE_TTL = 300; // 5 Minuten
    private const GENERAL_SETTINGS_OPTION_FALLBACKS = [
        'bookando_general_settings',
        'bookando_settings_general',
        'bookando_settings',
        'bookando_general',
    ];

    protected static ?array $manifestCache = null;

    protected static bool $manifestCacheInitialized = false;

    protected static bool $portalBridgeInitialized = false;

    protected static ?array $generalSettingsRuntimeCache = null;

    protected static bool $generalSettingsCacheInitialized = false;

    public function __construct()
    {
        $this->templateHelper = new Template(
            BOOKANDO_PLUGIN_DIR,
            static function (): string {
                return function_exists('get_stylesheet_directory') ? (string) get_stylesheet_directory() : '';
            },
            static function (string $option, $default = false) {
                return get_option($option, $default);
            },
            static function (int $type, string $name, int $filter, mixed $options = null) {
                if ($options === null) {
                    return filter_input($type, $name, $filter);
                }

                return filter_input($type, $name, $filter, $options);
            },
            static function ($value) {
                return sanitize_text_field($value);
            },
            static function ($value) {
                return sanitize_file_name($value);
            },
            static function (string $path): bool {
                return file_exists($path);
            },
            static function (string $path): void {
                include $path;
            },
            static function (string $message): void {
                if (function_exists('error_log')) {
                    error_log('[Bookando] ' . $message);
                }
            }
        );

        BaseAdmin::setTemplateHelper($this->templateHelper);

        // Übersetzungen laden
        add_action('plugins_loaded', [$this, 'loadTextdomain']);

        // Initialisierung nach Plugin-Start
        add_action('init', [$this, 'boot']);

        // Zentrales Admin-Menü registrieren
        add_action('admin_menu', [Menu::class, 'registerMenus']);
        add_action('bookando_register_module_menus', [LogsPage::class, 'register']);

        // ➕ Dispatcher global registrieren
        AjaxDispatcher::register();
        RestDispatcher::register();
        WebhookDispatcher::register();
        PublicDispatcher::register();
        CronDispatcher::register();
        AdminDispatcher::register();

        // Bookando-Admin-Assets NUR auf Bookando-Seiten laden
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets_conditionally']);

        // Shortcodes für die Portale registrieren (Frontend-Portale/Buchungsformular)
        add_action('init', [self::class, 'register_frontend_portal_shortcodes']);

        // DB: fehlende Indizes einmalig nachziehen
        add_action('init', [self::class, 'maybe_add_missing_indexes']);
        add_action('init', [self::class, 'maybe_add_performance_indexes']);

        // === Filter: Bookando-Module immer als type="module" ausgeben
        add_action('init', function () {
            add_filter('script_loader_tag', function ($tag, $handle) {
                if (strpos($handle, 'bookando-') === 0 && str_ends_with($handle, '-app')) {
                    return str_replace('<script ', '<script type="module" ', $tag);
                }
                return $tag;
            }, 10, 2);
        });
    }

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'bookando',
            false,
            dirname(plugin_basename(BOOKANDO_PLUGIN_FILE)) . '/languages/'
        );
    }

    public function boot(): void
    {
        // Helpers sicher laden (Case-Sensitive + Fallback auf Plugin-Root)
        foreach (HelperPathResolver::candidates() as $candidate) {
            if (is_string($candidate) && file_exists($candidate)) {
                require_once $candidate;
                break;
            }
        }

        // Optional: Bookando-User-Auto-Sync initialisieren (Default: AUS)
        if (defined('BOOKANDO_SYNC_USERS') && BOOKANDO_SYNC_USERS === true) {
            if (class_exists(\Bookando\Core\Service\UserSyncService::class)) {
                \Bookando\Core\Service\UserSyncService::register_hooks();
            }
        }

        $devMode = function_exists('bookando_is_dev') ? bookando_is_dev() : (defined('BOOKANDO_DEV') && BOOKANDO_DEV);
        $shouldSeedDummy = ($devMode || (defined('WP_DEBUG') && WP_DEBUG));
        $hasLicenseData = (bool) get_option('bookando_license_data');

        if (!$hasLicenseData && $shouldSeedDummy) {
            if (is_admin()) {
                add_action('admin_notices', [self::class, 'renderDevLicenseNotice']);
            }
            ActivityLogger::info('core.license', 'Dev dummy license seeding available via CLI');
        } elseif (!$hasLicenseData) {
            ActivityLogger::warning('core.license', 'No license data present on production install');
        }

        // Loader initialisieren
        $this->loader = new Loader();
        $this->loader->init();
    }

    public static function renderDevLicenseNotice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (get_option('bookando_license_data')) {
            return;
        }

        $message = __('Bookando: Keine Lizenzdaten gefunden. Führe "wp bookando seed-dev-license" aus, um eine Entwicklungs-Lizenz zu setzen.', 'bookando');

        echo '<div class="notice notice-warning"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Lädt zentrale Bookando-Styles (Admin-UI) NUR auf Bookando-Admin-Seiten.
     * Präfixiert alle Selektoren, vermeidet globale Leaks.
     * Ergänzt: liest Pluginsprache aus wp_bookando_settings (general.lang).
     */
    public static function enqueue_admin_assets_conditionally()
    {
        if (!function_exists('get_current_screen')) return;
        $screen = get_current_screen();
        if (!$screen || !isset($screen->id) || strpos($screen->id, 'bookando') === false) return;

        // Vue/Pinia NICHT separat laden – steckt im Vite-Bundle.
        // Nur wenn du EXPLIZIT per CDN externalisierst (siehe Vite USE_CDN), modulare ESM laden:
        if (getenv('VITE_USE_CDN') === 'true' && function_exists('wp_enqueue_script_module')) {
            wp_enqueue_script_module('bookando-cdn-vue',  'https://cdn.jsdelivr.net/npm/vue@3.5.21/dist/vue.esm-browser.prod.js', [], null, true);
            wp_enqueue_script_module('bookando-cdn-pinia','https://cdn.jsdelivr.net/npm/pinia@2.2.6/dist/pinia.esm-browser.prod.js', [], null, true);
            wp_enqueue_script_module('bookando-cdn-i18n', 'https://cdn.jsdelivr.net/npm/vue-i18n@9.14.0/dist/vue-i18n.esm-browser.prod.js', [], null, true);
        }
        // sonst: nichts laden – der Entry importiert alles selbst.

        // 1) Zentrales Admin-UI (aus dist/Manifest, Fallback: src/)
        self::enqueue_admin_ui_css();

        // 2) Polyfills zuerst, dann BRIDGE registrieren + enqueuen
        self::enqueue_polyfills();
        wp_register_script('bookando-admin-bridge', '', ['bookando-polyfills'], null, true);
        wp_enqueue_script('bookando-admin-bridge');

        // 3) Sprache aus Settings lesen (sicher & tenant-aware fallback)
        $settings_arr = self::get_general_settings();

        // Plugin-Sprache: null oder 'system' ⇒ WP-User-Locale
        $plugin_lang = $settings_arr['lang'] ?? null;              // 'de' | 'en' | 'fr' | 'it' | null
        $wp_locale   = function_exists('get_user_locale') ? get_user_locale() : get_locale(); // z. B. 'de_CH'
        $rawLang     = (!$plugin_lang || $plugin_lang === 'system') ? $wp_locale : $plugin_lang;

        // REST/Nonce/Origin
        $rest_nonce = wp_create_nonce('wp_rest');
        $rest_url   = esc_url_raw(rest_url());
        $origin     = esc_url_raw(get_site_url());

        // Logging nur, wenn WP_DEBUG aktiv ist
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Bookando] settings general.lang = ' . var_export($plugin_lang, true));
            error_log('[Bookando] bridge rawLang = ' . var_export($rawLang, true));
        }

        // 4) Bridge-Daten injizieren (VOR allen Modul-Skripten!)
        wp_add_inline_script(
            'bookando-admin-bridge',
            '(function(d){' .
                'var w = window; w.BOOKANDO_VARS = w.BOOKANDO_VARS || {};' .
                // niemals vorhandene Werte zerstören (z. B. wenn bereits via anderer Hook gesetzt)
                'for (var k in d){ if (d.hasOwnProperty(k) && (w.BOOKANDO_VARS[k] === undefined || w.BOOKANDO_VARS[k] === null)) { w.BOOKANDO_VARS[k] = d[k]; } }' .
            '})(' . wp_json_encode([
                'wp_locale'  => $wp_locale,
                'lang'       => $rawLang,
                'rest_nonce' => $rest_nonce,
                'nonce'      => $rest_nonce,
                'rest_url'   => $rest_url,
                'origin'     => $origin,
            ]) . ');',
            'before'
        );

        // Klassische WP-Bridge als Fallback
        wp_localize_script('bookando-admin-bridge', 'wpApiSettings', [
            'root'  => $rest_url,
            'nonce' => $rest_nonce,
        ]);
    }

    protected static function get_general_settings(): array
    {
        if (self::$generalSettingsCacheInitialized) {
            return self::$generalSettingsRuntimeCache ?? [];
        }

        $cacheKey = self::GENERAL_SETTINGS_CACHE_KEY;
        $cacheTtl = self::GENERAL_SETTINGS_CACHE_TTL;
        $settings = null;

        if (function_exists('wp_cache_get')) {
            $cached = wp_cache_get($cacheKey, self::GENERAL_SETTINGS_CACHE_GROUP);
            if ($cached !== false) {
                $settings = is_array($cached) ? $cached : [];
            }
        }

        if ($settings === null && function_exists('get_transient')) {
            $transient = get_transient($cacheKey);
            if ($transient !== false) {
                $settings = is_array($transient) ? $transient : [];
                if (function_exists('wp_cache_set')) {
                    wp_cache_set($cacheKey, $settings, self::GENERAL_SETTINGS_CACHE_GROUP, $cacheTtl);
                }
            }
        }

        if ($settings === null) {
            $settings = self::resolve_general_settings_from_db();
            if ($settings === null) {
                $settings = self::load_general_settings_from_options();
            }

            if (!is_array($settings)) {
                $settings = [];
            }

            if (function_exists('wp_cache_set')) {
                wp_cache_set($cacheKey, $settings, self::GENERAL_SETTINGS_CACHE_GROUP, $cacheTtl);
            }
            if (function_exists('set_transient')) {
                set_transient($cacheKey, $settings, $cacheTtl);
            }
        }

        self::$generalSettingsRuntimeCache = $settings;
        self::$generalSettingsCacheInitialized = true;

        return $settings;
    }

    protected static function resolve_general_settings_from_db(): ?array
    {
        global $wpdb;

        if (!isset($wpdb)) {
            return null;
        }

        $table = $wpdb->prefix . 'bookando_settings';

        $tableLike = method_exists($wpdb, 'esc_like') ? $wpdb->esc_like($table) : addcslashes($table, '_%');
        $tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tableLike));
        if ($tableExists !== $table) {
            return null;
        }

        $value_json = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT value FROM {$table} WHERE settings_key = %s AND tenant_id IS NULL ORDER BY id DESC LIMIT 1",
                'general'
            )
        );
        if (!$value_json) {
            $value_json = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT value FROM {$table} WHERE settings_key = %s ORDER BY id DESC LIMIT 1",
                    'general'
                )
            );
        }

        if (!is_string($value_json) || $value_json === '') {
            return [];
        }

        $decoded = json_decode($value_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected static function load_general_settings_from_options(): array
    {
        if (!function_exists('get_option')) {
            return [];
        }

        foreach (self::GENERAL_SETTINGS_OPTION_FALLBACKS as $optionName) {
            $value = get_option($optionName, null);

            if ($value === null || $value === false || $value === '') {
                continue;
            }

            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    public static function flush_general_settings_cache(): void
    {
        self::$generalSettingsRuntimeCache = null;
        self::$generalSettingsCacheInitialized = false;

        if (function_exists('wp_cache_delete')) {
            wp_cache_delete(self::GENERAL_SETTINGS_CACHE_KEY, self::GENERAL_SETTINGS_CACHE_GROUP);
        }

        if (function_exists('delete_transient')) {
            delete_transient(self::GENERAL_SETTINGS_CACHE_KEY);
        }
    }

    /**
     * Polyfills (startsWith/includes) früh laden – im Admin und in den Frontend-Portalen.
     */
    protected static function enqueue_polyfills(): void
    {
        $handle = 'bookando-polyfills';
        if (wp_script_is($handle, 'registered') || wp_script_is($handle, 'enqueued')) {
            return;
        }

        wp_enqueue_script(
            $handle,
            plugins_url('src/Core/Design/assets/js/polyfills.js', BOOKANDO_PLUGIN_FILE),
            [],
            self::asset_version('src/Core/Design/assets/js/polyfills.js'),
            true
        );
    }

    protected static function get_manifest(): ?array
    {
        if (!self::$manifestCacheInitialized) {
            $dist_dir = BOOKANDO_PLUGIN_DIR . 'dist/';
            $manifest_paths = [
                $dist_dir . '.vite/manifest.json',
                $dist_dir . 'manifest.json',
            ];

            foreach ($manifest_paths as $path) {
                if (!file_exists($path)) {
                    continue;
                }

                $json = file_get_contents($path);
                $data = json_decode($json, true);

                if (is_array($data)) {
                    self::$manifestCache = $data;
                    break;
                }
            }

            self::$manifestCacheInitialized = true;
        }

        return self::$manifestCache;
    }

    protected static function locate_manifest_entry(array $manifest, string $bundleKey): ?array
    {
        foreach ($manifest as $value) {
            if (empty($value['isEntry'])) {
                continue;
            }

            $candidates = [
                $value['src'] ?? null,
                $value['file'] ?? null,
            ];

            foreach ($candidates as $candidate) {
                if (!is_string($candidate)) {
                    continue;
                }

                if (str_contains($candidate, $bundleKey)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Enqueued das zentrale Admin-UI-CSS.
     * Reihenfolge:
     * 1) Bevorzugt dist/.vite/manifest.json (oder dist/manifest.json) lesen und die
     *    aus admin-ui.scss gebauten CSS-Dateien laden (gehashte Dateinamen).
     * 2) Fallback: src/Core/Design/assets/css/admin-ui.css (manuell kompiliert).
     */
    protected static function enqueue_admin_ui_css(): void
    {
        $dist_dir = BOOKANDO_PLUGIN_DIR . 'dist/';
        $dist_url = plugins_url('dist/', BOOKANDO_PLUGIN_FILE);

        $manifest = Manifest::load($dist_dir);

        $enqueued = false;

        if (is_array($manifest)) {
            // Entry finden, dessen src auf unsere admin-ui.scss zeigt
            $entryKey = null;
            foreach ($manifest as $key => $val) {
                if (empty($val['isEntry'])) continue;
                $src = $val['src'] ?? '';
                if ($src && (
                    str_ends_with($src, 'src/Core/Design/assets/scss/admin-ui.scss') ||
                    str_contains($src, '/Core/Design/assets/scss/admin-ui.scss')
                )) {
                    $entryKey = $key;
                    break;
                }
            }

            if ($entryKey !== null) {
                // CSS-only entries haben 'file' direkt, JS-entries haben 'css' array
                $cssList = [];

                if (!empty($manifest[$entryKey]['css'])) {
                    // JS Entry mit CSS-Dateien
                    $cssList = is_array($manifest[$entryKey]['css'])
                        ? $manifest[$entryKey]['css']
                        : [$manifest[$entryKey]['css']];
                } elseif (!empty($manifest[$entryKey]['file']) && str_ends_with($manifest[$entryKey]['file'], '.css')) {
                    // CSS-only Entry
                    $cssList = [$manifest[$entryKey]['file']];
                }

                foreach (array_values($cssList) as $i => $rel) {
                    $rel  = ltrim($rel, '/');
                    $file = $dist_dir . $rel;
                    if (!file_exists($file)) continue;

                    wp_enqueue_style(
                        $i === 0 ? 'bookando-admin-ui' : 'bookando-admin-ui-' . ($i + 1),
                        $dist_url . $rel,
                        [],
                        filemtime($file)
                    );
                    $enqueued = true;
                }
            }
        }

        if (!$enqueued) {
            // Fallback: manuell kompilierte src-CSS
            wp_enqueue_style(
                'bookando-admin-ui',
                plugins_url('src/Core/Design/assets/css/admin-ui.css', BOOKANDO_PLUGIN_FILE),
                [],
                self::asset_version('src/Core/Design/assets/css/admin-ui.css')
            );
        }
    }

    /**
     * Registriert Shortcodes für Buchungsformular und Portale,
     * sorgt für Einbindung der jeweiligen SPA-Bundles + Design-Variablen.
     */
    public static function register_frontend_portal_shortcodes()
    {
        // Buchungsformular
        add_shortcode('bookando_booking_form', [self::class, 'render_booking_form_portal']);
        // Kundenportal
        add_shortcode('bookando_customer_portal', [self::class, 'render_customer_portal']);
        // Mitarbeiterportal
        add_shortcode('bookando_employee_portal', [self::class, 'render_employee_portal']);
    }

    /**
     * Shortcode-Ausgabe für das Buchungsformular-Portal (Frontend)
     */
    public static function render_booking_form_portal($atts)
    {
        return self::render_portal_app('frontend-booking', 'bookando-booking-frontend', 'bookando-booking-app');
    }

    /**
     * Shortcode-Ausgabe für das Kundenportal
     */
    public static function render_customer_portal($atts)
    {
        return self::render_portal_app('customer-portal', 'bookando-customer-frontend', 'bookando-customer-portal-app');
    }

    /**
     * Shortcode-Ausgabe für das Mitarbeiterportal
     */
    public static function render_employee_portal($atts)
    {
        return self::render_portal_app('employee-portal', 'bookando-employee-frontend', 'bookando-employee-portal-app');
    }

    protected static function render_portal_app(string $bundleKey, string $handle, string $mountId): string
    {
        $design = get_option('bookando_design_settings', []);
        $inline_style = '';

        if (!empty($design) && is_array($design)) {
            foreach ($design as $key => $value) {
                $inline_style .= "--bookando-{$key}: {$value};";
            }
        }

        self::enqueue_portal_assets($bundleKey, $handle, is_array($design) ? $design : []);

        return '<div id="' . esc_attr($mountId) . '" style="' . esc_attr($inline_style) . '"></div>';
    }

    protected static function enqueue_portal_assets(string $bundleKey, string $handle, array $design): void
    {
        self::enqueue_polyfills();
        self::ensure_portal_bridge();

        $dist_dir = BOOKANDO_PLUGIN_DIR . 'dist/';
        $dist_url = plugins_url('dist/', BOOKANDO_PLUGIN_FILE);
        $manifest = self::get_manifest();
        $entry    = is_array($manifest) ? self::locate_manifest_entry($manifest, $bundleKey) : null;

        $cssEnqueued = false;

        if ($entry && !empty($entry['css'])) {
            $cssList = is_array($entry['css']) ? $entry['css'] : [$entry['css']];
            foreach (array_values($cssList) as $index => $rel) {
                $rel       = ltrim($rel, '/');
                $cssHandle = $index === 0 ? $handle : $handle . '-' . ($index + 1);

                if (wp_style_is($cssHandle, 'enqueued')) {
                    $cssEnqueued = true;
                    continue;
                }

                $file = $dist_dir . $rel;
                if (!file_exists($file)) {
                    continue;
                }

                wp_enqueue_style(
                    $cssHandle,
                    $dist_url . $rel,
                    [],
                    filemtime($file)
                );

                $cssEnqueued = true;
            }
        }

        if (!$cssEnqueued && !wp_style_is($handle, 'enqueued')) {
            $fallbackCss = "dist/{$bundleKey}/main.css";
            wp_enqueue_style(
                $handle,
                plugins_url($fallbackCss, BOOKANDO_PLUGIN_FILE),
                [],
                self::asset_version($fallbackCss)
            );
        }

        $scriptEnqueuedNow = false;

        if (!wp_script_is($handle, 'enqueued')) {
            $scriptSrc = null;
            $version   = null;

            if ($entry && !empty($entry['file'])) {
                $rel  = ltrim($entry['file'], '/');
                $file = $dist_dir . $rel;
                if (file_exists($file)) {
                    $version = filemtime($file);
                }
                $scriptSrc = $dist_url . $rel;
            }

            if ($scriptSrc === null) {
                $fallbackJs = "dist/{$bundleKey}/main.js";
                $scriptSrc  = plugins_url($fallbackJs, BOOKANDO_PLUGIN_FILE);
                $version    = self::asset_version($fallbackJs);
            }

            wp_enqueue_script(
                $handle,
                $scriptSrc,
                ['bookando-polyfills', 'bookando-portal-bridge'],
                $version,
                true
            );

            $scriptEnqueuedNow = true;
        }

        if ($scriptEnqueuedNow) {
            wp_localize_script($handle, 'BOOKANDO_PORTAL_VARS', [
                'design' => $design,
            ]);
        }
    }

    protected static function ensure_portal_bridge(): void
    {
        $handle = 'bookando-portal-bridge';

        if (!wp_script_is($handle, 'registered')) {
            wp_register_script($handle, '', ['bookando-polyfills'], null, true);
        }

        if (!self::$portalBridgeInitialized) {
            $rest_nonce = wp_create_nonce('wp_rest');
            $rest_url   = esc_url_raw(rest_url());
            $origin     = esc_url_raw(get_site_url());
            $wp_locale  = function_exists('get_user_locale') ? get_user_locale() : get_locale();

            wp_add_inline_script(
                $handle,
                '(function(d){var w=window;w.BOOKANDO_VARS=w.BOOKANDO_VARS||{};for(var k in d){if(d.hasOwnProperty(k)&&(w.BOOKANDO_VARS[k]===undefined||w.BOOKANDO_VARS[k]===null)){w.BOOKANDO_VARS[k]=d[k];}}})( ' .
                wp_json_encode([
                    'wp_locale'  => $wp_locale,
                    'lang'       => $wp_locale,
                    'rest_nonce' => $rest_nonce,
                    'nonce'      => $rest_nonce,
                    'rest_url'   => $rest_url,
                    'origin'     => $origin,
                ]) . ');',
                'before'
            );

            wp_localize_script($handle, 'wpApiSettings', [
                'root'  => $rest_url,
                'nonce' => $rest_nonce,
            ]);

            self::$portalBridgeInitialized = true;
        }

        if (!wp_script_is($handle, 'enqueued')) {
            wp_enqueue_script($handle);
        }
    }

    public static function reset_portal_asset_state(): void
    {
        self::$portalBridgeInitialized = false;
        self::$manifestCache           = null;
        self::$manifestCacheInitialized = false;
    }

    /**
     * Fehlende DB-Indizes einmalig anlegen:
     * - Wenn 'tenant_id' existiert: (tenant_id, external_id)
     * - Sonst: (external_id)
     */
    public static function maybe_add_missing_indexes(): void
    {
        // nur einmal ausführen
        if (get_option('bookando_migr_idx_users_external_done')) {
            return;
        }

        global $wpdb;
        $tbl = $wpdb->prefix . 'bookando_users';

        // Spalte external_id vorhanden?
        $hasExternal = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'external_id'", $tbl
        ));
        if (!$hasExternal) {
            // nichts tun, wenn die Spalte nicht existiert
            update_option('bookando_migr_idx_users_external_done', 1, false);
            return;
        }

        // Gibt es tenant_id?
        $hasTenant = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'tenant_id'", $tbl
        ));

        $indexName = $hasTenant ? 'idx_users_tenant_external' : 'idx_users_external';

        // Index bereits vorhanden?
        $exists = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s", $tbl, $indexName
        ));

        if (!$exists) {
            // SICHERHEIT: Backticks für Table/Index-Namen (verhindert SQL Injection)
            $safe_table = '`' . esc_sql($tbl) . '`';
            $safe_index = '`' . preg_replace('/[^a-z0-9_]/i', '', $indexName) . '`';

            if ($hasTenant) {
                $wpdb->query("ALTER TABLE {$safe_table} ADD KEY {$safe_index} (tenant_id, external_id)");
            } else {
                $wpdb->query("ALTER TABLE {$safe_table} ADD KEY {$safe_index} (external_id)");
            }
        }

        update_option('bookando_migr_idx_users_external_done', 1, false);
    }

    /**
     * Performance-Indizes für ActivityLog und Event-Tabellen nachziehen.
     * Wird nur einmal ausgeführt, wenn noch nicht erledigt.
     *
     * Indizes:
     * - activity_log: tenant_id, severity, context, logged_at
     * - event_periods: event_id + period_start_utc
     */
    public static function maybe_add_performance_indexes(): void
    {
        // Nur einmal ausführen
        if (get_option('bookando_performance_indexes_added')) {
            return;
        }

        global $wpdb;
        $indexes_added = [];

        // 1. ActivityLog-Indizes
        $log_table = $wpdb->prefix . 'bookando_activity_log';
        if (self::tableExists($log_table)) {
            $log_indexes = [
                'idx_log_tenant_severity' => ['tenant_id', 'severity'],
                'idx_log_context' => ['context(50)'],
                'idx_log_logged_at' => ['logged_at'],
            ];

            foreach ($log_indexes as $indexName => $columns) {
                if (!self::indexExists($log_table, $indexName)) {
                    $safe_table = '`' . esc_sql($log_table) . '`';
                    $safe_index = '`' . preg_replace('/[^a-z0-9_]/i', '', $indexName) . '`';
                    $columns_str = implode(', ', $columns);

                    $wpdb->query("ALTER TABLE {$safe_table} ADD INDEX {$safe_index} ({$columns_str})");
                    $indexes_added[] = "{$log_table}.{$indexName}";

                    ActivityLogger::info('core.migration', 'Performance-Index erstellt', [
                        'table' => $log_table,
                        'index' => $indexName,
                        'columns' => $columns
                    ]);
                }
            }
        }

        // 2. Event-Periods-Index
        $periods_table = $wpdb->prefix . 'bookando_event_periods';
        if (self::tableExists($periods_table)) {
            $indexName = 'idx_event_period';
            if (!self::indexExists($periods_table, $indexName)) {
                $safe_table = '`' . esc_sql($periods_table) . '`';
                $safe_index = '`' . preg_replace('/[^a-z0-9_]/i', '', $indexName) . '`';

                $wpdb->query("ALTER TABLE {$safe_table} ADD INDEX {$safe_index} (event_id, period_start_utc)");
                $indexes_added[] = "{$periods_table}.{$indexName}";

                ActivityLogger::info('core.migration', 'Performance-Index erstellt', [
                    'table' => $periods_table,
                    'index' => $indexName,
                    'columns' => ['event_id', 'period_start_utc']
                ]);
            }
        }

        update_option('bookando_performance_indexes_added', time(), false);

        if (!empty($indexes_added)) {
            ActivityLogger::info('core.migration', 'Performance-Indizes-Migration abgeschlossen', [
                'indexes_count' => count($indexes_added),
                'indexes' => $indexes_added
            ]);
        }
    }

    /**
     * Prüft ob eine Tabelle existiert.
     */
    private static function tableExists(string $tableName): bool
    {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
            $tableName
        ));
        return (int) $result > 0;
    }

    /**
     * Prüft ob ein Index auf einer Tabelle existiert.
     */
    private static function indexExists(string $tableName, string $indexName): bool
    {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s",
            $tableName,
            $indexName
        ));
        return (int) $result > 0;
    }

    /**
     * Asset-Versionierung über File-Hash, um Caching-Probleme zu vermeiden.
     */
    protected static function asset_version($relative_path)
    {
        $full = BOOKANDO_PLUGIN_DIR . ltrim($relative_path, '/');
        if (file_exists($full)) {
            return filemtime($full);
        }
        return defined('BOOKANDO_VERSION') ? BOOKANDO_VERSION : '1.0.0';
    }
}
