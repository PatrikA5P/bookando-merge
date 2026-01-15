<?php
namespace Bookando\Core\Tenant;

use WP_Error;
use Bookando\Core\Service\ActivityLogger;
use function wp_generate_password;
use function wp_json_encode;
use function sanitize_text_field;
use function sanitize_email;

/**
 * TenantProvisioner - Automatische Tenant-Erstellung für SaaS/Cloud/App-Lizenzen
 *
 * Verantwortlich für:
 * - Automatische Tenant-Provisionierung bei Lizenz-Kauf
 * - Cross-Platform Tenant-Synchronisation (SaaS ↔ Cloud ↔ Mobile App)
 * - Webhook-Integration für externe Lizenz-Plattformen
 * - Tenant-Deaktivierung bei Lizenz-Ablauf
 *
 * SICHERHEIT:
 * - Alle Provisionierungs-Requests müssen authentifiziert sein
 * - API-Keys werden über BOOKANDO_PROVISIONING_API_KEY definiert
 * - Jede Provisionierung wird auditiert
 */
class TenantProvisioner
{
    /** @var string Datenbanktabelle für Tenants */
    private string $table;

    /** @var \wpdb WordPress Datenbank-Objekt */
    private \wpdb $db;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'bookando_tenants';
    }

    /**
     * Erstellt einen neuen Tenant automatisch bei Lizenz-Kauf.
     *
     * @param array<string, mixed> $data {
     *     @type string $company_name    Firmenname (Pflicht)
     *     @type string $email           Kontakt-Email (Pflicht)
     *     @type string $license_key     Lizenzschlüssel (Pflicht)
     *     @type string $platform        Plattform: 'saas'|'cloud'|'app' (Pflicht)
     *     @type string $plan            Lizenz-Plan: 'basic'|'pro'|'enterprise'
     *     @type string $external_id     Externe ID (z.B. Stripe Customer ID)
     *     @type string $subdomain       Gewünschte Subdomain (optional)
     *     @type array  $metadata        Zusätzliche Metadaten (optional)
     * }
     * @return array{tenant_id: int, api_key: string, status: string}|WP_Error
     */
    public function createTenant(array $data)
    {
        // Validierung
        $validation = $this->validateTenantData($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Prüfe, ob Lizenz bereits verwendet wird
        if ($this->licenseKeyExists($data['license_key'])) {
            return new WP_Error(
                'license_already_used',
                'Lizenzschlüssel wird bereits verwendet.',
                ['status' => 409]
            );
        }

        // Subdomain prüfen/generieren
        $subdomain = $this->resolveSubdomain($data);

        // API-Key generieren für Cross-Platform Zugriff
        $apiKey = $this->generateApiKey();

        // Tenant-Daten vorbereiten
        $tenantData = [
            'company_name'  => sanitize_text_field($data['company_name']),
            'email'         => sanitize_email($data['email']),
            'license_key'   => sanitize_text_field($data['license_key']),
            'platform'      => sanitize_text_field($data['platform']),
            'plan'          => sanitize_text_field($data['plan'] ?? 'basic'),
            'external_id'   => sanitize_text_field($data['external_id'] ?? ''),
            'subdomain'     => $subdomain,
            'api_key_hash'  => password_hash($apiKey, PASSWORD_BCRYPT),
            'status'        => 'active',
            'created_at'    => current_time('mysql'),
            'expires_at'    => $this->calculateExpiryDate($data['plan'] ?? 'basic'),
            'metadata'      => wp_json_encode($data['metadata'] ?? []),
        ];

        // Transaktion starten
        $this->db->query('START TRANSACTION');

        try {
            // Tenant in Datenbank anlegen
            $inserted = $this->db->insert($this->table, $tenantData);
            if (!$inserted) {
                throw new \RuntimeException($this->db->last_error ?: 'Failed to insert tenant');
            }

            $tenantId = (int) $this->db->insert_id;

            // Standard-Einstellungen initialisieren
            $this->initializeTenantDefaults($tenantId);

            // Subdomain-Mapping registrieren (wenn aktiviert)
            if (defined('BOOKANDO_SUBDOMAIN_MULTI_TENANT') && BOOKANDO_SUBDOMAIN_MULTI_TENANT) {
                $this->registerSubdomainMapping($subdomain, $tenantId);
            }

            // Commit
            $this->db->query('COMMIT');

            // Audit-Log
            ActivityLogger::info('tenant.provisioned', 'Neuer Tenant provisioniert', [
                'tenant_id'    => $tenantId,
                'company_name' => $tenantData['company_name'],
                'platform'     => $tenantData['platform'],
                'plan'         => $tenantData['plan'],
                'subdomain'    => $subdomain,
            ]);

            return [
                'tenant_id' => $tenantId,
                'api_key'   => $apiKey, // NUR EINMAL zurückgeben!
                'subdomain' => $subdomain,
                'status'    => 'active',
            ];

        } catch (\Exception $e) {
            $this->db->query('ROLLBACK');

            ActivityLogger::error('tenant.provisioning_failed', 'Tenant-Provisionierung fehlgeschlagen', [
                'error'        => $e->getMessage(),
                'company_name' => $data['company_name'] ?? 'unknown',
            ]);

            return new WP_Error(
                'provisioning_failed',
                'Tenant-Provisionierung fehlgeschlagen: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Synchronisiert Tenant-Daten zwischen Plattformen (SaaS ↔ Cloud ↔ App).
     *
     * @param string $licenseKey Lizenzschlüssel
     * @param string $platform   Ziel-Plattform
     * @return array{tenant_id: int, synced: bool}|WP_Error
     */
    public function syncTenantAcrossPlatforms(string $licenseKey, string $platform)
    {
        $tenant = $this->getTenantByLicense($licenseKey);

        if (!$tenant) {
            return new WP_Error(
                'tenant_not_found',
                'Kein Tenant für diesen Lizenzschlüssel gefunden.',
                ['status' => 404]
            );
        }

        // Plattform-Zugriff registrieren
        $platforms = json_decode($tenant['metadata'], true) ?? [];
        $platforms['platforms'] = $platforms['platforms'] ?? [];

        if (!in_array($platform, $platforms['platforms'], true)) {
            $platforms['platforms'][] = $platform;

            $this->db->update(
                $this->table,
                ['metadata' => wp_json_encode($platforms)],
                ['id' => $tenant['id']]
            );

            ActivityLogger::info('tenant.platform_synced', 'Tenant-Plattform synchronisiert', [
                'tenant_id' => $tenant['id'],
                'platform'  => $platform,
            ]);
        }

        return [
            'tenant_id' => (int) $tenant['id'],
            'synced'    => true,
        ];
    }

    /**
     * Deaktiviert Tenant bei Lizenz-Ablauf oder Kündigung.
     *
     * @param int $tenantId Tenant-ID
     * @param string $reason Grund: 'expired'|'cancelled'|'suspended'
     * @return bool|WP_Error
     */
    public function deactivateTenant(int $tenantId, string $reason = 'expired')
    {
        $updated = $this->db->update(
            $this->table,
            [
                'status'     => 'inactive',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $tenantId]
        );

        if (!$updated) {
            return new WP_Error('update_failed', 'Tenant-Deaktivierung fehlgeschlagen');
        }

        ActivityLogger::warning('tenant.deactivated', 'Tenant deaktiviert', [
            'tenant_id' => $tenantId,
            'reason'    => $reason,
        ]);

        return true;
    }

    /**
     * Reaktiviert Tenant nach Lizenz-Verlängerung.
     *
     * @param int $tenantId Tenant-ID
     * @param string $newExpiryDate Neues Ablaufdatum (Y-m-d H:i:s)
     * @return bool|WP_Error
     */
    public function reactivateTenant(int $tenantId, string $newExpiryDate)
    {
        $updated = $this->db->update(
            $this->table,
            [
                'status'     => 'active',
                'expires_at' => $newExpiryDate,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $tenantId]
        );

        if (!$updated) {
            return new WP_Error('update_failed', 'Tenant-Reaktivierung fehlgeschlagen');
        }

        ActivityLogger::info('tenant.reactivated', 'Tenant reaktiviert', [
            'tenant_id'      => $tenantId,
            'new_expiry_at'  => $newExpiryDate,
        ]);

        return true;
    }

    /**
     * Holt Tenant-Daten anhand des Lizenzschlüssels.
     *
     * @param string $licenseKey Lizenzschlüssel
     * @return array<string, mixed>|null
     */
    public function getTenantByLicense(string $licenseKey): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE license_key = %s LIMIT 1";
        $row = $this->db->get_row($this->db->prepare($sql, $licenseKey), ARRAY_A);

        return $row ?: null;
    }

    /**
     * Validiert Tenant-Eingabedaten.
     *
     * @param array<string, mixed> $data
     * @return true|WP_Error
     */
    private function validateTenantData(array $data)
    {
        $required = ['company_name', 'email', 'license_key', 'platform'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf('Pflichtfeld fehlt: %s', $field),
                    ['status' => 400]
                );
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return new WP_Error('invalid_email', 'Ungültige Email-Adresse', ['status' => 400]);
        }

        $validPlatforms = ['saas', 'cloud', 'app'];
        if (!in_array($data['platform'], $validPlatforms, true)) {
            return new WP_Error(
                'invalid_platform',
                'Plattform muss sein: saas, cloud oder app',
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * Prüft, ob Lizenzschlüssel bereits existiert.
     *
     * @param string $licenseKey
     * @return bool
     */
    private function licenseKeyExists(string $licenseKey): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE license_key = %s";
        $count = (int) $this->db->get_var($this->db->prepare($sql, $licenseKey));

        return $count > 0;
    }

    /**
     * Generiert sicheren API-Key für Cross-Platform Zugriff.
     *
     * @return string
     */
    private function generateApiKey(): string
    {
        return 'bookando_' . bin2hex(random_bytes(32));
    }

    /**
     * Berechnet Ablaufdatum basierend auf Plan.
     *
     * @param string $plan
     * @return string|null
     */
    private function calculateExpiryDate(string $plan): ?string
    {
        $durations = [
            'basic'      => '+1 year',
            'pro'        => '+1 year',
            'enterprise' => '+2 years',
            'lifetime'   => null, // Kein Ablauf
        ];

        $duration = $durations[$plan] ?? '+1 year';

        return $duration ? date('Y-m-d H:i:s', strtotime($duration)) : null;
    }

    /**
     * Löst Subdomain auf (aus Eingabe oder generiert).
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function resolveSubdomain(array $data): string
    {
        if (!empty($data['subdomain'])) {
            $subdomain = sanitize_title($data['subdomain']);
            // Prüfe Verfügbarkeit
            if (!$this->subdomainExists($subdomain)) {
                return $subdomain;
            }
        }

        // Fallback: Generiere aus Firmennamen
        $base = sanitize_title($data['company_name']);
        $subdomain = $base;
        $counter = 1;

        while ($this->subdomainExists($subdomain)) {
            $subdomain = $base . '-' . $counter;
            $counter++;
        }

        return $subdomain;
    }

    /**
     * Prüft, ob Subdomain bereits existiert.
     *
     * @param string $subdomain
     * @return bool
     */
    private function subdomainExists(string $subdomain): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE subdomain = %s";
        $count = (int) $this->db->get_var($this->db->prepare($sql, $subdomain));

        return $count > 0;
    }

    /**
     * Initialisiert Standard-Einstellungen für neuen Tenant.
     *
     * @param int $tenantId
     * @return void
     */
    private function initializeTenantDefaults(int $tenantId): void
    {
        // Standard-Ressourcen (z.B. via Resources-Modul)
        if (class_exists('Bookando\\Modules\\resources\\StateRepository')) {
            \Bookando\Modules\Resources\StateRepository::seedDefaultsForTenant($tenantId, true);
        }

        // Weitere Modul-Defaults können hier initialisiert werden
        do_action('bookando_tenant_provisioned', $tenantId);
    }

    /**
     * Registriert Subdomain-Mapping für Multi-Tenant SaaS.
     *
     * @param string $subdomain
     * @param int $tenantId
     * @return void
     */
    private function registerSubdomainMapping(string $subdomain, int $tenantId): void
    {
        $map = get_option('bookando_subdomain_map', []);
        $map[$subdomain] = $tenantId;
        update_option('bookando_subdomain_map', $map, false);
    }
}
