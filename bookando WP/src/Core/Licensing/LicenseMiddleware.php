<?php
namespace Bookando\Core\Licensing;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Bookando\Core\Api\Response;

/**
 * LicenseMiddleware - Automatische Lizenz-Prüfung für REST-API
 *
 * Prüft VOR jedem REST-API Request, ob der Tenant eine gültige Lizenz hat.
 * Blockiert Zugriff bei ungültiger Lizenz.
 *
 * VERWENDUNG:
 * - Wird automatisch via rest_pre_dispatch Filter registriert
 * - Prüft nur Bookando-eigene Endpoints (/bookando/v1/*)
 * - Überspringt öffentliche Endpoints (z.B. /auth/login)
 *
 * DEV-BYPASS:
 * - Entwickler mit 'bookando_dev_bypass' Capability werden nicht blockiert
 * - ODER BOOKANDO_DEV_BYPASS=true in wp-config.php (nur non-production!)
 */
class LicenseMiddleware
{
    /**
     * Endpoints die NICHT lizenzgeprüft werden (öffentlich).
     *
     * @var array<string>
     */
    private static array $publicEndpoints = [
        '/bookando/v1/auth/login',
        '/bookando/v1/auth/register',
        '/bookando/v1/auth/refresh',
        '/bookando/v1/auth/logout',
        '/bookando/v1/provisioning/create-tenant',
        '/bookando/v1/provisioning/webhook',
        '/bookando/v1/health',
    ];

    /**
     * Registriert Middleware-Hook.
     */
    public static function register(): void
    {
        add_filter('rest_pre_dispatch', [self::class, 'checkLicense'], 10, 3);
    }

    /**
     * Prüft Lizenz vor REST-Request.
     *
     * @param mixed $result Response (null = continue, WP_Error = block)
     * @param mixed $server REST-Server Instance
     * @param WP_REST_Request $request
     * @return mixed
     */
    public static function checkLicense($result, $server, WP_REST_Request $request)
    {
        // Nur Bookando-Endpoints prüfen
        $route = $request->get_route();

        if (!self::isBookandoEndpoint($route)) {
            return $result;
        }

        // Öffentliche Endpoints überspringen
        if (self::isPublicEndpoint($route)) {
            return $result;
        }

        // Lizenz prüfen
        if (!LicenseGuard::hasValidLicense()) {
            return self::createLicenseErrorResponse();
        }

        // Grace Period Warning hinzufügen (als Header)
        if (LicenseGuard::isInGracePeriod()) {
            $daysRemaining = LicenseGuard::getDaysUntilExpiry();
            $graceDays = max(0, 7 + $daysRemaining); // Days left in grace period

            // Info-Header für Client
            add_filter('rest_post_dispatch', function($response) use ($graceDays) {
                if ($response instanceof WP_REST_Response) {
                    $response->header('X-Bookando-License-Grace-Period', "true");
                    $response->header('X-Bookando-License-Grace-Days', (string) $graceDays);
                }
                return $response;
            });
        }

        return $result;
    }

    /**
     * Prüft, ob Route ein Bookando-Endpoint ist.
     *
     * @param string $route
     * @return bool
     */
    private static function isBookandoEndpoint(string $route): bool
    {
        return strpos($route, '/bookando/v1/') === 0;
    }

    /**
     * Prüft, ob Endpoint öffentlich ist (keine Lizenz-Prüfung).
     *
     * @param string $route
     * @return bool
     */
    private static function isPublicEndpoint(string $route): bool
    {
        foreach (self::$publicEndpoints as $publicRoute) {
            if (strpos($route, $publicRoute) === 0) {
                return true;
            }
        }

        // Filter-Hook für Custom Public Endpoints
        if (function_exists('apply_filters')) {
            $isPublic = apply_filters('bookando_is_public_endpoint', false, $route);
            if ($isPublic) {
                return true;
            }
        }

        return false;
    }

    /**
     * Erstellt Fehler-Response für ungültige Lizenz.
     *
     * @return WP_Error
     */
    private static function createLicenseErrorResponse(): WP_Error
    {
        $licenseInfo = LicenseGuard::getLicenseInfo();

        $message = 'Ihre Lizenz ist abgelaufen oder ungültig. Bitte erneuern Sie Ihre Lizenz.';
        $code = 'license_expired';

        // Spezifische Fehlermeldungen
        if ($licenseInfo['plan'] === 'none') {
            $message = 'Keine gültige Lizenz gefunden. Bitte aktivieren Sie eine Lizenz.';
            $code = 'license_missing';
        } elseif ($licenseInfo['days_remaining'] === -1) {
            $daysExpired = abs($licenseInfo['days_remaining'] ?? 0);
            $message = "Ihre Lizenz ist seit {$daysExpired} Tagen abgelaufen. Bitte erneuern Sie Ihre Lizenz.";
        }

        return new WP_Error(
            $code,
            $message,
            [
                'status'         => 402, // Payment Required
                'license_status' => $licenseInfo,
                'renewal_url'    => self::getRenewalUrl(),
            ]
        );
    }

    /**
     * Gibt Renewal-URL zurück (konfigurierbar).
     *
     * @return string
     */
    private static function getRenewalUrl(): string
    {
        $defaultUrl = 'https://bookando.app/pricing';

        if (defined('BOOKANDO_RENEWAL_URL')) {
            return BOOKANDO_RENEWAL_URL;
        }

        if (function_exists('apply_filters')) {
            return apply_filters('bookando_license_renewal_url', $defaultUrl);
        }

        return $defaultUrl;
    }

    /**
     * Fügt öffentlichen Endpoint hinzu.
     *
     * @param string $endpoint
     * @return void
     */
    public static function addPublicEndpoint(string $endpoint): void
    {
        if (!in_array($endpoint, self::$publicEndpoints, true)) {
            self::$publicEndpoints[] = $endpoint;
        }
    }
}
