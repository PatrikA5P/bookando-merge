<?php
namespace Bookando\Core\Tenant;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Bookando\Core\Api\Response;
use Bookando\Core\Service\ActivityLogger;

/**
 * REST-API für Tenant-Provisionierung (für externe Lizenz-Plattformen).
 *
 * Endpoints:
 * - POST /bookando/v1/provisioning/create-tenant
 * - POST /bookando/v1/provisioning/sync-tenant
 * - POST /bookando/v1/provisioning/webhook
 *
 * SICHERHEIT:
 * - Alle Requests müssen via API-Key authentifiziert sein
 * - API-Key wird in Header: X-Bookando-Provisioning-Key übergeben
 * - Definiert in: BOOKANDO_PROVISIONING_API_KEY (wp-config.php)
 */
class ProvisioningApi
{
    /** @var TenantProvisioner */
    private TenantProvisioner $provisioner;

    public function __construct()
    {
        $this->provisioner = new TenantProvisioner();
    }

    /**
     * Registriert alle REST-Routes.
     */
    public static function registerRoutes(): void
    {
        $namespace = 'bookando/v1';
        $controller = new self();

        register_rest_route($namespace, '/provisioning/create-tenant', [
            'methods'             => 'POST',
            'callback'            => [$controller, 'createTenant'],
            'permission_callback' => [$controller, 'checkApiKey'],
        ]);

        register_rest_route($namespace, '/provisioning/sync-tenant', [
            'methods'             => 'POST',
            'callback'            => [$controller, 'syncTenant'],
            'permission_callback' => [$controller, 'checkApiKey'],
        ]);

        register_rest_route($namespace, '/provisioning/webhook', [
            'methods'             => 'POST',
            'callback'            => [$controller, 'handleWebhook'],
            'permission_callback' => [$controller, 'checkApiKey'],
        ]);

        register_rest_route($namespace, '/provisioning/deactivate-tenant', [
            'methods'             => 'POST',
            'callback'            => [$controller, 'deactivateTenant'],
            'permission_callback' => [$controller, 'checkApiKey'],
        ]);
    }

    /**
     * API-Key Authentifizierung.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function checkApiKey(WP_REST_Request $request): bool
    {
        if (!defined('BOOKANDO_PROVISIONING_API_KEY')) {
            return false;
        }

        $providedKey = $request->get_header('X-Bookando-Provisioning-Key');

        if (!$providedKey) {
            return false;
        }

        $isValid = hash_equals(BOOKANDO_PROVISIONING_API_KEY, $providedKey);

        if (!$isValid) {
            ActivityLogger::warning('provisioning.auth_failed', 'Provisioning API-Key ungültig', [
                'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'endpoint' => $request->get_route(),
            ]);
        }

        return $isValid;
    }

    /**
     * POST /bookando/v1/provisioning/create-tenant
     *
     * Erstellt neuen Tenant bei Lizenz-Kauf.
     *
     * Body:
     * {
     *   "company_name": "Firma GmbH",
     *   "email": "admin@firma.de",
     *   "license_key": "LICENSE-KEY-12345",
     *   "platform": "saas",
     *   "plan": "pro",
     *   "external_id": "cus_stripe_12345",
     *   "subdomain": "firma" (optional),
     *   "metadata": {} (optional)
     * }
     *
     * Response:
     * {
     *   "tenant_id": 42,
     *   "api_key": "bookando_abc123...",
     *   "subdomain": "firma",
     *   "status": "active"
     * }
     */
    public function createTenant(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        $result = $this->provisioner->createTenant($data);

        if (is_wp_error($result)) {
            return Response::error($result);
        }

        return Response::created($result);
    }

    /**
     * POST /bookando/v1/provisioning/sync-tenant
     *
     * Synchronisiert Tenant zwischen Plattformen (SaaS ↔ Cloud ↔ App).
     *
     * Body:
     * {
     *   "license_key": "LICENSE-KEY-12345",
     *   "platform": "app"
     * }
     *
     * Response:
     * {
     *   "tenant_id": 42,
     *   "synced": true
     * }
     */
    public function syncTenant(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['license_key']) || empty($data['platform'])) {
            return Response::error([
                'code'    => 'missing_params',
                'message' => 'license_key und platform sind erforderlich',
            ], 400);
        }

        $result = $this->provisioner->syncTenantAcrossPlatforms(
            $data['license_key'],
            $data['platform']
        );

        if (is_wp_error($result)) {
            return Response::error($result);
        }

        return Response::ok($result);
    }

    /**
     * POST /bookando/v1/provisioning/deactivate-tenant
     *
     * Deaktiviert Tenant bei Lizenz-Ablauf oder Kündigung.
     *
     * Body:
     * {
     *   "license_key": "LICENSE-KEY-12345",
     *   "reason": "expired"
     * }
     */
    public function deactivateTenant(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['license_key'])) {
            return Response::error([
                'code'    => 'missing_license_key',
                'message' => 'license_key ist erforderlich',
            ], 400);
        }

        $tenant = $this->provisioner->getTenantByLicense($data['license_key']);

        if (!$tenant) {
            return Response::error([
                'code'    => 'tenant_not_found',
                'message' => 'Kein Tenant für diesen Lizenzschlüssel gefunden',
            ], 404);
        }

        $result = $this->provisioner->deactivateTenant(
            (int) $tenant['id'],
            $data['reason'] ?? 'expired'
        );

        if (is_wp_error($result)) {
            return Response::error($result);
        }

        return Response::ok(['deactivated' => true]);
    }

    /**
     * POST /bookando/v1/provisioning/webhook
     *
     * Generischer Webhook-Handler für externe Plattformen (Stripe, Paddle, etc.).
     *
     * Body:
     * {
     *   "event": "license.created|license.renewed|license.expired|license.cancelled",
     *   "license_key": "LICENSE-KEY-12345",
     *   "company_name": "Firma GmbH",
     *   "email": "admin@firma.de",
     *   "platform": "saas",
     *   "plan": "pro",
     *   "external_id": "cus_stripe_12345"
     * }
     */
    public function handleWebhook(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_json_params();

        if (empty($data['event']) || empty($data['license_key'])) {
            return Response::error([
                'code'    => 'invalid_webhook',
                'message' => 'event und license_key sind erforderlich',
            ], 400);
        }

        $event = $data['event'];
        $licenseKey = $data['license_key'];

        switch ($event) {
            case 'license.created':
                // Neuen Tenant anlegen
                $result = $this->provisioner->createTenant($data);
                if (is_wp_error($result)) {
                    return Response::error($result);
                }
                return Response::ok(['action' => 'created', 'tenant_id' => $result['tenant_id']]);

            case 'license.renewed':
                // Tenant reaktivieren + Ablaufdatum verlängern
                $tenant = $this->provisioner->getTenantByLicense($licenseKey);
                if (!$tenant) {
                    return Response::error(['code' => 'tenant_not_found'], 404);
                }

                $plan = $data['plan'] ?? $tenant['plan'];
                $newExpiry = $this->calculateExpiryDate($plan);

                $result = $this->provisioner->reactivateTenant((int) $tenant['id'], $newExpiry);
                if (is_wp_error($result)) {
                    return Response::error($result);
                }
                return Response::ok(['action' => 'renewed', 'tenant_id' => $tenant['id']]);

            case 'license.expired':
            case 'license.cancelled':
                // Tenant deaktivieren
                $tenant = $this->provisioner->getTenantByLicense($licenseKey);
                if (!$tenant) {
                    return Response::error(['code' => 'tenant_not_found'], 404);
                }

                $reason = $event === 'license.expired' ? 'expired' : 'cancelled';
                $result = $this->provisioner->deactivateTenant((int) $tenant['id'], $reason);

                if (is_wp_error($result)) {
                    return Response::error($result);
                }
                return Response::ok(['action' => 'deactivated', 'tenant_id' => $tenant['id']]);

            default:
                return Response::error([
                    'code'    => 'unknown_event',
                    'message' => 'Unbekannter Event-Typ: ' . $event,
                ], 400);
        }
    }

    /**
     * Hilfsmethode: Berechnet Ablaufdatum.
     */
    private function calculateExpiryDate(string $plan): string
    {
        $durations = [
            'basic'      => '+1 year',
            'pro'        => '+1 year',
            'enterprise' => '+2 years',
        ];

        $duration = $durations[$plan] ?? '+1 year';

        return date('Y-m-d H:i:s', strtotime($duration));
    }
}
