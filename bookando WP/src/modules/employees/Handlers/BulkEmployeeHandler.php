<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use WP_REST_Request;
use WP_Error;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Settings\FormRules;
use function rest_ensure_response;
use function sanitize_key;
use function is_email;
use function current_time;
use function wp_json_encode;
use function array_values;
use function array_filter;
use function array_map;
use function array_fill;
use function array_unshift;
use function array_key_exists;
use function implode;
use function count;
use function __;

/**
 * Handler für Bulk-Operationen auf Employees.
 *
 * Unterstützt:
 * - block: Status auf 'blocked' setzen (Batch)
 * - activate: Status auf 'active' setzen (Batch)
 * - soft_delete: Status auf 'deleted' setzen (Batch)
 * - hard_delete: PII anonymisieren + Cascade Delete (Batch)
 * - save: Single Create/Update Operation (wie bei Customers)
 */
class BulkEmployeeHandler
{
    /**
     * Bulk-Operationen Endpoint Handler.
     *
     * POST /wp-json/bookando/v1/employees/bulk
     * Body: { action: 'block'|'activate'|'soft_delete'|'hard_delete'|'save', ids?: number[], payload?: any }
     *
     * @param array $params URL-Parameter
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function bulk(array $params, WP_REST_Request $request)
    {
        global $wpdb;
        $tables = EmployeeRepository::employeeTables();
        $usersTab = $tables['usersTab'];
        $tenantId = Gate::devBypass() ? null : TenantManager::currentTenantId();

        $body = (array) $request->get_json_params();
        $action = sanitize_key($body['action'] ?? '');
        $ids = array_values(array_filter(array_map('intval', (array) ($body['ids'] ?? [])), fn($v) => $v > 0));
        $payload = $body['payload'] ?? null;

        if (!$action) {
            return new WP_Error('bad_request', __('Aktion fehlt.', 'bookando'), ['status' => 400]);
        }

        switch ($action) {
            case 'hard_delete':
                return self::handleBulkHardDelete($ids, $usersTab, $tenantId);

            case 'soft_delete':
                return self::handleBulkSoftDelete($ids, $usersTab, $tenantId);

            case 'block':
            case 'activate':
                return self::handleBulkActivate($action, $ids, $usersTab, $tenantId);

            case 'save':
                return self::handleBulkSave($payload, $usersTab, $tenantId);

            default:
                return new WP_Error('bad_request', __('Unbekannte Aktion.', 'bookando'), ['status' => 400]);
        }
    }

    /**
     * Bulk Hard Delete: Anonymisiert PII für mehrere Employees.
     *
     * @param array $ids Employee IDs
     * @param string $usersTab Users-Tabelle
     * @param int|null $tenantId Tenant ID
     * @return \WP_REST_Response|WP_Error Response
     */
    private static function handleBulkHardDelete(array $ids, string $usersTab, ?int $tenantId)
    {
        global $wpdb;

        if (empty($ids)) {
            return new WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
        }

        foreach ($ids as $employeeId) {
            EmployeeDeleteHandler::hardDeleteRecord($usersTab, (int) $employeeId, $tenantId);
        }

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['ok' => true, 'affected' => (int) $wpdb->rows_affected]);
    }

    /**
     * Bulk Soft Delete: Setzt Status auf 'deleted' für mehrere Employees.
     *
     * @param array $ids Employee IDs
     * @param string $usersTab Users-Tabelle
     * @param int|null $tenantId Tenant ID
     * @return \WP_REST_Response|WP_Error Response
     */
    private static function handleBulkSoftDelete(array $ids, string $usersTab, ?int $tenantId)
    {
        global $wpdb;

        if (empty($ids)) {
            return new WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
        }

        foreach ($ids as $employeeId) {
            EmployeeDeleteHandler::softDeleteRecord($usersTab, (int) $employeeId, $tenantId);
        }

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['ok' => true, 'affected' => (int) $wpdb->rows_affected]);
    }

    /**
     * Bulk Activate/Block: Setzt Status für mehrere Employees.
     *
     * @param string $action 'activate' oder 'block'
     * @param array $ids Employee IDs
     * @param string $usersTab Users-Tabelle
     * @param int|null $tenantId Tenant ID
     * @return \WP_REST_Response|WP_Error Response
     */
    private static function handleBulkActivate(string $action, array $ids, string $usersTab, ?int $tenantId)
    {
        global $wpdb;

        if (empty($ids)) {
            return new WP_Error('bad_request', __('IDs fehlen.', 'bookando'), ['status' => 400]);
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $whereTenant = '';
        $args = $ids;

        if ($tenantId) {
            $whereTenant = " AND tenant_id = %d";
            $args[] = $tenantId;
        }

        $status = ($action === 'block') ? 'blocked' : 'active';
        $sql = "UPDATE {$usersTab}
                SET status = %s, deleted_at = NULL, updated_at = %s
                WHERE id IN ($placeholders) {$whereTenant}";

        array_unshift($args, $status, current_time('mysql'));
        $wpdb->query($wpdb->prepare($sql, ...$args));

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['ok' => true, 'affected' => (int) $wpdb->rows_affected]);
    }

    /**
     * Bulk Save: Single Create oder Update Operation.
     *
     * Wie bei Customers: Ermöglicht einzelne Create/Update via Bulk-Endpoint.
     *
     * @param mixed $payload Employee-Daten
     * @param string $usersTab Users-Tabelle
     * @param int|null $tenantId Tenant ID
     * @return \WP_REST_Response|WP_Error Response
     */
    private static function handleBulkSave($payload, string $usersTab, ?int $tenantId)
    {
        global $wpdb;

        $isCreate = empty($payload['id']);
        $data = EmployeeInputValidator::sanitizeEmployeeInput((array) $payload, $isCreate);

        // Email-Validierung
        if (!empty($data['email']) && !is_email($data['email'])) {
            return new WP_Error('invalid_email', __('Ungültige E-Mail-Adresse.', 'bookando'), ['status' => 400]);
        }

        // FormRules-Validierung
        $targetStatus = EmployeeInputValidator::normalizeStatus($data['status'] ?? 'active');
        $rules = FormRules::get('employees', 'admin');

        if ($targetStatus !== 'deleted') {
            $missing = EmployeeFormValidator::validateByRules(
                $data + ['status' => $targetStatus],
                $rules,
                $targetStatus
            );
            if (!empty($missing)) {
                return new WP_Error(
                    'validation_error',
                    __('Pflichtfelder fehlen.', 'bookando'),
                    ['status' => 422, 'fields' => $missing]
                );
            }
        }

        if ($isCreate) {
            return self::handleBulkCreate($data, $usersTab, $tenantId, $targetStatus);
        } else {
            return self::handleBulkUpdate($payload['id'], $data, $usersTab, $tenantId);
        }
    }

    /**
     * Bulk Create: Erstellt neuen Employee.
     *
     * @param array $data Sanitierte Employee-Daten
     * @param string $usersTab Users-Tabelle
     * @param int|null $tenantId Tenant ID
     * @param string $targetStatus Ziel-Status
     * @return \WP_REST_Response|WP_Error Response
     */
    private static function handleBulkCreate(
        array $data,
        string $usersTab,
        ?int $tenantId,
        string $targetStatus
    ) {
        global $wpdb;

        $roles = wp_json_encode(['bookando_employee']);
        $insert = [
            'tenant_id'     => $tenantId ?: (int) ($data['tenant_id'] ?? 1),
            'email'         => $data['email'] ?? null,
            'first_name'    => $data['first_name'] ?? null,
            'last_name'     => $data['last_name'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'address'       => $data['address'] ?? null,
            'address_2'     => $data['address_2'] ?? null,
            'zip'           => $data['zip'] ?? null,
            'city'          => $data['city'] ?? null,
            'country'       => $data['country'] ?? null,
            'birthdate'     => $data['birthdate'] ?? null,
            'gender'        => $data['gender'] ?? null,
            'language'      => $data['language'] ?? 'de',
            'note'          => $data['note'] ?? null,
            'description'   => $data['description'] ?? null,
            'badge_id'      => $data['badge_id'] ?? null,
            'avatar_url'    => $data['avatar_url'] ?? null,
            'timezone'      => $data['timezone'] ?? null,
            'password_hash' => $data['password_hash'] ?? null,
            'roles'         => $roles,
            'status'        => $targetStatus,
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
            'deleted_at'    => null,
        ];

        $wpdb->insert($usersTab, $insert);

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['ok' => true, 'id' => (int) $wpdb->insert_id]);
    }

    /**
     * Bulk Update: Aktualisiert existierenden Employee.
     *
     * @param int $id Employee ID
     * @param array $data Sanitierte Employee-Daten
     * @param string $usersTab Users-Tabelle
     * @param int|null $tenantId Tenant ID
     * @return \WP_REST_Response|WP_Error Response
     */
    private static function handleBulkUpdate(int $id, array $data, string $usersTab, ?int $tenantId)
    {
        global $wpdb;

        // Nur übergebene Keys updaten (analog PUT)
        $update = [];
        $updateFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'address',
            'address_2',
            'zip',
            'city',
            'country',
            'birthdate',
            'gender',
            'language',
            'note',
            'description',
            'avatar_url',
            'timezone',
            'status',
            'badge_id',
            'password_hash',
        ];

        foreach ($updateFields as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        $update['updated_at'] = current_time('mysql');

        $where = ['id' => $id];
        $whereFormats = ['%d'];

        if ($tenantId) {
            $where['tenant_id'] = $tenantId;
            $whereFormats[] = '%d';
        }

        $wpdb->update($usersTab, $update, $where, null, $whereFormats);

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['ok' => true, 'updated' => true, 'id' => $id]);
    }
}
