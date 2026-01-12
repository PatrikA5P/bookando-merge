<?php
declare(strict_types=1);

namespace Bookando\Core\Partnership;

use WP_Error;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Service\ActivityLogger;

/**
 * Service für Partner-Beziehungen mit Business-Logic.
 *
 * Verantwortlichkeiten:
 * - Erstellen und Verwalten von Partnerschaften
 * - Validierung von Partner-Anfragen
 * - Berechtigungsprüfungen
 * - Provisionsvereinbarungen
 * - Partner-Einladungen
 */
final class PartnershipService
{
    private PartnershipRepository $repository;

    public function __construct(?PartnershipRepository $repository = null)
    {
        $this->repository = $repository ?? new PartnershipRepository();
    }

    /**
     * Erstellt eine neue Partnership mit Validierung.
     *
     * @param int   $primaryTenant   Haupt-Tenant-ID
     * @param int   $partnerTenant   Partner-Tenant-ID
     * @param array $options         Partnership-Konfiguration
     * @return array|WP_Error
     */
    public function createPartnership(int $primaryTenant, int $partnerTenant, array $options = [])
    {
        // Validierung
        if ($primaryTenant === $partnerTenant) {
            return new WP_Error(
                'partnership_self_reference',
                __('Cannot create partnership with yourself.', 'bookando')
            );
        }

        // Prüfe, ob Tenants existieren
        if (!$this->tenantExists($primaryTenant)) {
            return new WP_Error(
                'partnership_invalid_primary',
                __('Primary tenant does not exist.', 'bookando')
            );
        }

        if (!$this->tenantExists($partnerTenant)) {
            return new WP_Error(
                'partnership_invalid_partner',
                __('Partner tenant does not exist.', 'bookando')
            );
        }

        // Prüfe, ob Partnership bereits existiert
        $existing = $this->repository->findRelationship($primaryTenant, $partnerTenant);
        if ($existing) {
            return new WP_Error(
                'partnership_exists',
                __('Partnership already exists.', 'bookando'),
                ['existing_id' => $existing['id']]
            );
        }

        // Standard-Optionen
        $defaults = [
            'relationship_type' => 'trusted_partner',
            'status' => 'active',
            'sharing_permissions' => [
                'customers' => ['view'],
                'events' => ['view'],
            ],
            'commission_type' => 'percentage',
            'commission_value' => 10.00, // 10% Standard-Provision
            'metadata' => [],
            'expires_at' => null,
        ];

        $data = array_merge($defaults, $options, [
            'primary_tenant' => $primaryTenant,
            'partner_tenant' => $partnerTenant,
        ]);

        // Partnership erstellen
        $id = $this->repository->create($data);

        if (!$id) {
            return new WP_Error(
                'partnership_create_failed',
                __('Failed to create partnership.', 'bookando')
            );
        }

        // Log
        ActivityLogger::info('partnership.created', 'Partnership created', [
            'partnership_id' => $id,
            'primary_tenant' => $primaryTenant,
            'partner_tenant' => $partnerTenant,
            'commission_value' => $data['commission_value'],
        ]);

        return $this->repository->findById($id);
    }

    /**
     * Aktualisiert eine Partnership.
     *
     * @param int   $partnershipId  Partnership-ID
     * @param int   $currentTenant  ID des aktuellen Tenants (Security-Check)
     * @param array $updates        Zu aktualisierende Felder
     * @return bool|WP_Error
     */
    public function updatePartnership(int $partnershipId, int $currentTenant, array $updates)
    {
        $partnership = $this->repository->findById($partnershipId);

        if (!$partnership) {
            return new WP_Error(
                'partnership_not_found',
                __('Partnership not found.', 'bookando')
            );
        }

        // Security: Nur Primary-Tenant darf updaten
        if ($partnership['primary_tenant'] !== $currentTenant) {
            return new WP_Error(
                'partnership_unauthorized',
                __('You are not authorized to update this partnership.', 'bookando')
            );
        }

        // Bestimmte Felder dürfen nicht geändert werden
        unset($updates['id'], $updates['primary_tenant'], $updates['partner_tenant'], $updates['created_at']);

        $success = $this->repository->update($partnershipId, $updates);

        if ($success) {
            ActivityLogger::info('partnership.updated', 'Partnership updated', [
                'partnership_id' => $partnershipId,
                'updated_by_tenant' => $currentTenant,
            ]);
        }

        return $success;
    }

    /**
     * Beendet eine Partnership.
     *
     * @param int $partnershipId
     * @param int $currentTenant Security-Check
     * @return bool|WP_Error
     */
    public function terminatePartnership(int $partnershipId, int $currentTenant)
    {
        $partnership = $this->repository->findById($partnershipId);

        if (!$partnership) {
            return new WP_Error(
                'partnership_not_found',
                __('Partnership not found.', 'bookando')
            );
        }

        // Beide Partner dürfen beenden
        if ($partnership['primary_tenant'] !== $currentTenant && $partnership['partner_tenant'] !== $currentTenant) {
            return new WP_Error(
                'partnership_unauthorized',
                __('You are not authorized to terminate this partnership.', 'bookando')
            );
        }

        $success = $this->repository->terminate($partnershipId);

        if ($success) {
            ActivityLogger::warning('partnership.terminated', 'Partnership terminated', [
                'partnership_id' => $partnershipId,
                'terminated_by_tenant' => $currentTenant,
                'primary_tenant' => $partnership['primary_tenant'],
                'partner_tenant' => $partnership['partner_tenant'],
            ]);
        }

        return $success;
    }

    /**
     * Liefert alle Partners des aktuellen Tenants.
     *
     * @param int    $tenantId
     * @param string $direction 'outgoing' (als Primary) oder 'incoming' (als Partner)
     * @return array
     */
    public function getPartnerships(int $tenantId, string $direction = 'outgoing'): array
    {
        if ($direction === 'incoming') {
            return $this->repository->findAllPrimaries($tenantId, 'active');
        }

        return $this->repository->findAllPartners($tenantId, 'active');
    }

    /**
     * Prüft, ob zwei Tenants eine aktive Partnership haben.
     *
     * @param int $tenantA
     * @param int $tenantB
     * @return bool
     */
    public function hasActivePartnership(int $tenantA, int $tenantB): bool
    {
        // Prüfe beide Richtungen
        return $this->repository->isActive($tenantA, $tenantB)
            || $this->repository->isActive($tenantB, $tenantA);
    }

    /**
     * Prüft, ob ein Tenant Berechtigung für eine Ressource hat.
     *
     * @param int    $accessorTenant  Tenant, der zugreifen möchte
     * @param int    $ownerTenant     Tenant, dem die Ressource gehört
     * @param string $resource        Ressourcentyp (z.B. 'customers', 'events')
     * @param string $permission      Berechtigung (z.B. 'view', 'edit')
     * @return bool
     */
    public function hasPermission(int $accessorTenant, int $ownerTenant, string $resource, string $permission): bool
    {
        // Eigene Ressourcen: immer erlaubt
        if ($accessorTenant === $ownerTenant) {
            return true;
        }

        // Prüfe Partnership
        return $this->repository->hasPermission($ownerTenant, $accessorTenant, $resource, $permission);
    }

    /**
     * Gewährt zusätzliche Berechtigung für eine Partnership.
     *
     * @param int    $partnershipId
     * @param string $resource      z.B. 'customers'
     * @param string $permission    z.B. 'edit'
     * @return bool|WP_Error
     */
    public function grantPermission(int $partnershipId, string $resource, string $permission)
    {
        $partnership = $this->repository->findById($partnershipId);

        if (!$partnership) {
            return new WP_Error('partnership_not_found', __('Partnership not found.', 'bookando'));
        }

        $permissions = $partnership['sharing_permissions'] ?? [];

        if (!isset($permissions[$resource])) {
            $permissions[$resource] = [];
        }

        if (!in_array($permission, $permissions[$resource], true)) {
            $permissions[$resource][] = $permission;
        }

        return $this->repository->update($partnershipId, [
            'sharing_permissions' => $permissions
        ]);
    }

    /**
     * Entzieht Berechtigung für eine Partnership.
     *
     * @param int    $partnershipId
     * @param string $resource
     * @param string $permission
     * @return bool|WP_Error
     */
    public function revokePermission(int $partnershipId, string $resource, string $permission)
    {
        $partnership = $this->repository->findById($partnershipId);

        if (!$partnership) {
            return new WP_Error('partnership_not_found', __('Partnership not found.', 'bookando'));
        }

        $permissions = $partnership['sharing_permissions'] ?? [];

        if (isset($permissions[$resource])) {
            $permissions[$resource] = array_values(array_diff($permissions[$resource], [$permission]));
        }

        return $this->repository->update($partnershipId, [
            'sharing_permissions' => $permissions
        ]);
    }

    /**
     * Berechnet Provision für eine Buchung.
     *
     * @param int   $partnershipId
     * @param float $bookingAmount Buchungsbetrag
     * @return float Provisions-Betrag
     */
    public function calculateCommission(int $partnershipId, float $bookingAmount): float
    {
        $partnership = $this->repository->findById($partnershipId);

        if (!$partnership) {
            return 0.00;
        }

        $type = $partnership['commission_type'] ?? 'percentage';
        $value = (float) ($partnership['commission_value'] ?? 0.00);

        if ($type === 'percentage') {
            return $bookingAmount * ($value / 100);
        }

        // Fixed amount
        return $value;
    }

    /**
     * Prüft, ob ein Tenant existiert.
     *
     * @param int $tenantId
     * @return bool
     */
    private function tenantExists(int $tenantId): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bookando_tenants';

        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE id = %d",
            $tenantId
        ));

        return $count > 0;
    }
}
