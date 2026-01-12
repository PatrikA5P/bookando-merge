<?php
namespace Bookando\Core\Licensing;

use Bookando\Core\Manager\ModuleStateRepository;
use Bookando\Core\Service\ActivityLogger;
use JsonException;
use WP_Error;
use function __;
use function home_url;
use function wp_json_encode;

class LicenseManager
{
    protected static ?array $licenseData = null;

    // 🕒 zentrale Dauer der Testphase in Tagen (global für alle Module)
    public const GRACE_PERIOD_DAYS = 30;

    /**
     * DEV-MODUS: Im Entwicklermodus (BOOKANDO_DEV) werden ALLE Module/Features/Pläne als erlaubt behandelt!
     * Dies gilt für alle Methoden: isModuleAllowed, isFeatureEnabled, getLicensePlan etc.
     * Deaktiviere für echten SaaS/Produktivbetrieb einfach das Define in wp-config.php.
     */
    protected static function isDevMode(): bool
    {
        return defined('BOOKANDO_DEV') && BOOKANDO_DEV === true;
    }

    /**
     * Lese das Lizenz-Mapping (Plan → Module/Features) aus license-features.php
     */
    protected static function getLicenseFeatureMap(): array
    {
        static $map = null;
        if ($map === null) {
            $map = include __DIR__ . '/license-features.php';
        }
        return $map;
    }

    /**
     * Auflösung: Alle Module für einen Plan (inkl. Vererbung, z. B. @starter)
     */
    public static function resolvePlanModules(string $plan): array
    {
        $map = self::getLicenseFeatureMap();
        if (empty($map['plans'][$plan])) {
            return [];
        }
        $modules = [];
        $stack = [$plan];
        while ($stack) {
            $current = array_pop($stack);
            foreach ($map['plans'][$current]['modules'] ?? [] as $entry) {
                if (str_starts_with($entry, '@')) {
                    $stack[] = substr($entry, 1);
                } elseif (!in_array($entry, $modules, true)) {
                    $modules[] = $entry;
                }
            }
        }
        return $modules;
    }

    /**
     * Auflösung: Alle Features für einen Plan (inkl. Vererbung)
     */
    public static function resolvePlanFeatures(string $plan): array
    {
        $map = self::getLicenseFeatureMap();
        if (empty($map['plans'][$plan])) {
            return [];
        }
        $features = [];
        $stack = [$plan];
        while ($stack) {
            $current = array_pop($stack);
            foreach ($map['plans'][$current]['features'] ?? [] as $entry) {
                if (str_starts_with($entry, '@')) {
                    $stack[] = substr($entry, 1);
                } elseif (!in_array($entry, $features, true)) {
                    $features[] = $entry;
                }
            }
        }
        return $features;
    }

    /**
     * Prüft, ob ein Modul vollständig aktiviert ist (DEV: immer true)
     */
    public static function isModuleAllowed(string $moduleSlug): bool
    {
        if (self::isDevMode()) {
            ActivityLogger::log(
                'license.dev',
                'Module allowed in dev mode',
                ['module' => $moduleSlug],
                ActivityLogger::LEVEL_INFO,
                null,
                $moduleSlug
            );
            return true;
        }
        $meta = self::getModuleMeta($moduleSlug);

        if (empty($meta['license_required'])) {
            return true;
        }

        if (self::hasValidLicenseFor($moduleSlug)) {
            return true;
        }

        // Prüfe globale Gnadenfrist
        $installedAt = ModuleStateRepository::instance()->getInstalledAt($moduleSlug) ?? time();

        return (time() - $installedAt) < self::getGracePeriod();
    }

    /**
     * Prüft, ob ein einzelnes Feature in der Lizenz aktiv ist (DEV: immer true)
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        if (self::shouldLogDiagnostics()) {
            ActivityLogger::log(
                'license.debug',
                'isFeatureEnabled called',
                [
                    'feature' => $feature,
                    'devMode' => self::isDevMode(),
                ],
                ActivityLogger::LEVEL_INFO
            );
        }
        if (self::isDevMode()) {
            ActivityLogger::log(
                'license.dev',
                'Feature enabled in dev mode',
                ['feature' => $feature],
                ActivityLogger::LEVEL_INFO
            );
            return true;
        }
        $license = self::getLicenseData();

        if (empty($license['features'])) {
            return false;
        }

        return in_array($feature, $license['features'], true);
    }

    /**
     * Aktiviert Diagnose-Logs nur im DEV- oder Debug-Modus.
     */
    protected static function shouldLogDiagnostics(): bool
    {
        if (self::isDevMode()) {
            return true;
        }

        return defined('WP_DEBUG') && WP_DEBUG === true;
    }

    /**
     * Prüft, ob ein Modul laut Lizenz aktiv ist (DEV: immer true)
     */
    public static function hasValidLicenseFor(string $moduleSlug): bool
    {
        if (self::isDevMode()) {
            ActivityLogger::log(
                'license.dev',
                'Module license treated as valid in dev mode',
                ['module' => $moduleSlug],
                ActivityLogger::LEVEL_INFO,
                null,
                $moduleSlug
            );
            return true;
        }
        $license = self::getLicenseData();

        // Prüfe, ob das Modul laut Plan-Mapping (und gebuchtem Plan) enthalten ist
        $plan = $license['plan'] ?? null;
        if ($plan) {
            $allowedModules = self::resolvePlanModules($plan);
            if (in_array(strtolower($moduleSlug), $allowedModules, true)) {
                return true;
            }
        }

        // Zusätzlich: falls explizit als Modul gelistet
        return !empty($license['modules']) &&
            in_array(strtolower($moduleSlug), $license['modules'], true);
    }

    /**
     * Prüft, ob alle in module.json definierten "features_required" freigeschaltet sind (DEV: immer true)
     */
    public static function hasAllRequiredFeatures(string $moduleSlug): bool
    {
        if (self::isDevMode()) {
            ActivityLogger::log(
                'license.dev',
                'All features allowed in dev mode',
                ['module' => $moduleSlug],
                ActivityLogger::LEVEL_INFO,
                null,
                $moduleSlug
            );
            return true;
        }
        $meta = self::getModuleMeta($moduleSlug);
        if (empty($meta['features_required'])) {
            return true;
        }

        foreach ((array) $meta['features_required'] as $feature) {
            if (!self::isFeatureEnabled($feature)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Prüft, ob die Lizenzdaten lokal gültig sind (DEV: immer true)
     */
    public static function hasValidLicense(): bool
    {
        if (self::isDevMode()) {
            ActivityLogger::log(
                'license.dev',
                'License treated as valid in dev mode',
                [],
                ActivityLogger::LEVEL_INFO
            );
            return true;
        }
        $license = self::getLicenseData();
        return !empty($license['key']) && !empty($license['modules']);
    }

    /**
     * Lizenzschlüssel ausgeben (DEV: Dummy-Key)
     */
    public static function getLicenseKey(): ?string
    {
        if (self::isDevMode()) {
            return 'dev-local-key';
        }
        $license = self::getLicenseData();
        return $license['key'] ?? null;
    }

    /**
     * Gibt z. B. "starter", "pro", "education" zurück (DEV: immer "pro")
     */
    public static function getLicensePlan(): ?string
    {
        if (self::isDevMode()) {
            return 'pro';
        }
        $license = self::getLicenseData();
        return $license['plan'] ?? null;
    }

    /**
     * Zentrale Testzeit in Sekunden
     */
    protected static function getGracePeriod(): int
    {
        return self::GRACE_PERIOD_DAYS * DAY_IN_SECONDS;
    }

    /**
     * Lade Metadaten aus module.json
     */
    protected static function getModuleMeta(string $slug): array
    {
        // Korrekt: Kleinbuchstaben "modules", nicht "Modules"
        $file = BOOKANDO_PLUGIN_DIR . 'src/modules/' . $slug . '/module.json';
        if (!file_exists($file)) {
            ActivityLogger::log(
                'license.meta',
                'Module manifest missing',
                ['path' => $file, 'module' => $slug],
                ActivityLogger::LEVEL_WARNING,
                null,
                $slug
            );
            return [];
        }
        $json = json_decode(file_get_contents($file), true);
        if (!$json) {
            ActivityLogger::log(
                'license.meta',
                'Module manifest could not be parsed',
                ['path' => $file, 'module' => $slug],
                ActivityLogger::LEVEL_WARNING,
                null,
                $slug
            );
            return [];
        }
        return $json;
    }

    /**
     * Lizenzdaten (cache + fallback)
     */
    public static function getLicenseData(): array
    {
        if (self::isDevMode()) {
            // DEV: Dummy-License mit allen Modulen/Features erlaubt!
            $allModules = [];
            $allFeatures = [];
            $map = self::getLicenseFeatureMap();
            foreach ($map['plans'] ?? [] as $plan => $config) {
                $allModules = array_merge($allModules, $config['modules'] ?? []);
                $allFeatures = array_merge($allFeatures, $config['features'] ?? []);
            }
            $allModules = array_unique(array_map('strtolower', $allModules));
            $allFeatures = array_unique($allFeatures);

            return [
                'key'      => 'dev-local-key',
                'modules'  => $allModules,
                'features' => $allFeatures,
                'plan'     => 'pro',
            ];
        }

        if (self::$licenseData !== null) {
            return self::$licenseData;
        }

        $stored = get_option('bookando_license_data');

        self::$licenseData = is_array($stored) ? $stored : [
            'key'      => null,
            'modules'  => [],
            'features' => [],
            'plan'     => null,
        ];

        return self::$licenseData;
    }

    /**
     * Lizenzdaten setzen (z. B. nach Validierung)
     */
    public static function setLicenseData(array $data): void
    {
        update_option('bookando_license_data', $data);
        self::$licenseData = $data;
    }

    /**
     * Lizenzdaten zurücksetzen
     */
    public static function clear(): void
    {
        delete_option('bookando_license_data');
        self::$licenseData = null;
    }

    /**
     * Lizenz-API anfragen (POST)
     */
    public static function verifyRemote(string $licenseKey): ?array
    {
        $endpoint = apply_filters('bookando_license_endpoint', 'https://license.bookando.ch/api/check');

        $payload = wp_json_encode([
            'license_key' => $licenseKey,
            'site_url'    => home_url(),
        ]);

        if ($payload === false) {
            ActivityLogger::error('license.http', 'Failed to encode payload for license verification');
            return null;
        }

        $response = wp_remote_post($endpoint, [
            'timeout' => 10,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $payload,
        ]);

        if (is_wp_error($response)) {
            ActivityLogger::error('license.http', 'License verification request failed', [
                'error' => $response->get_error_message(),
            ]);
            return null;
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status < 200 || $status >= 300) {
            ActivityLogger::warning('license.http', 'License verification returned unexpected status', [
                'status' => $status,
            ]);
            return null;
        }

        $body = wp_remote_retrieve_body($response);

        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            ActivityLogger::error('license.http', 'License verification response is not valid JSON', [
                'message' => $exception->getMessage(),
            ]);
            return null;
        }

        if (!is_array($data) || empty($data['valid'])) {
            ActivityLogger::warning('license.http', 'License verification response marked as invalid', [
                'response' => $data,
            ]);
            return null;
        }

        return $data;
    }

    // Optional: Features für die UI/Module anzeigen (z. B. für Info "in deinem Plan enthalten")
    public static function getAvailableModulesForPlan(?string $plan = null): array
    {
        $plan = $plan ?? self::getLicensePlan();
        return $plan ? self::resolvePlanModules($plan) : [];
    }

    public static function getAvailableFeaturesForPlan(?string $plan = null): array
    {
        $plan = $plan ?? self::getLicensePlan();
        return $plan ? self::resolvePlanFeatures($plan) : [];
    }

    public static function requiresRestWrite(): bool
    {
        return self::isFeatureEnabled('rest_api_write');
    }
    public static function requiresRestRead(): bool
    {
        return self::isFeatureEnabled('rest_api_read');
    }

    /**
     * Ensures that a specific feature is available for the current license.
     *
     * Returns {@see WP_Error} with HTTP status 402 when the feature is missing.
     *
     * @return true|WP_Error
     */
    public static function ensureFeature(string $moduleSlug, string $featureKey)
    {
        if (self::isFeatureEnabled($featureKey)) {
            return true;
        }

        return new WP_Error(
            'feature_not_available',
            __('Dieses Feature ist in deinem aktuellen Tarif nicht enthalten.', 'bookando'),
            [
                'status'  => 402,
                'module'  => strtolower($moduleSlug),
                'feature' => $featureKey,
            ]
        );
    }
}
