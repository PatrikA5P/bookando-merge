<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use WP_REST_Request;
use WP_Error;
use function rest_ensure_response;
use function current_time;
use function wp_json_encode;
use function implode;
use function array_fill;
use function count;
use function __;

/**
 * Handler für Employee-Löschoperationen.
 *
 * Unterstützt:
 * - Soft Delete: Status auf 'deleted' setzen, Daten bleiben erhalten
 * - Hard Delete: PII anonymisieren + Cascade Delete aller Subtabellen
 */
class EmployeeDeleteHandler
{
    /**
     * Löscht einen Employee (soft oder hard).
     *
     * @param array $tables Tabellennamen-Mapping
     * @param int|null $tenantId Tenant ID
     * @param int $id Employee ID
     * @param WP_REST_Request $request REST Request
     * @return \WP_REST_Response|WP_Error Response oder Fehler
     */
    public static function handleEmployeeDelete(
        array $tables,
        ?int $tenantId,
        int $id,
        WP_REST_Request $request
    ) {
        extract($tables, EXTR_OVERWRITE);

        global $wpdb;

        $hard = (bool) $request->get_param('hard');

        if ($hard) {
            // Hard Delete: PII anonymisieren
            self::hardDeleteRecord($usersTab, $id, $tenantId);

            // Workday Sets + Intervals + Mappings
            $oldSetIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wSetTab} WHERE user_id=%d", $id));
            if ($oldSetIds) {
                $placeholders = implode(',', array_fill(0, count($oldSetIds), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wIntTab} WHERE set_id IN ($placeholders)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetLoc} WHERE set_id IN ($placeholders)", ...$oldSetIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wSetSvc} WHERE set_id IN ($placeholders)", ...$oldSetIds));
            }
            $wpdb->delete($wSetTab, ['user_id' => $id], ['%d']);

            // Days Off
            $wpdb->delete($holTab, ['user_id' => $id], ['%d']);

            // Special Day Sets + Intervals + Mappings
            $sdIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$sdSetTab} WHERE user_id=%d", $id));
            if ($sdIds) {
                $placeholders = implode(',', array_fill(0, count($sdIds), '%d'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdIntTab} WHERE set_id IN ($placeholders)", ...$sdIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdSetLoc} WHERE set_id IN ($placeholders)", ...$sdIds));
                $wpdb->query($wpdb->prepare("DELETE FROM {$sdSetSvc} WHERE set_id IN ($placeholders)", ...$sdIds));
            }
            $wpdb->delete($sdSetTab, ['user_id' => $id], ['%d']);

            // Calendar Connections → Calendars → Events
            $connIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$calConnTab} WHERE user_id=%d", $id)) ?: [];
            if ($connIds) {
                $placeholders1 = implode(',', array_fill(0, count($connIds), '%d'));
                $calIds = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$calsTab} WHERE connection_id IN ($placeholders1)", ...$connIds)) ?: [];
                if ($calIds) {
                    $placeholders2 = implode(',', array_fill(0, count($calIds), '%d'));
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE ce FROM {$eventsTab} ce
                            INNER JOIN {$calsTab} c ON c.id = ce.calendar_id
                            WHERE c.id IN ($placeholders2)",
                            ...$calIds
                        )
                    );
                    $wpdb->query($wpdb->prepare("DELETE FROM {$calsTab} WHERE id IN ($placeholders2)", ...$calIds));
                }
                $wpdb->query($wpdb->prepare("DELETE FROM {$calConnTab} WHERE id IN ($placeholders1)", ...$connIds));
            }

            if ($wpdb->last_error) {
                return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
            }

            return rest_ensure_response(['deleted' => true, 'hard' => true]);
        }

        // Soft Delete
        self::softDeleteRecord($usersTab, $id, $tenantId);

        if ($wpdb->last_error) {
            return new WP_Error('db_error', $wpdb->last_error, ['status' => 500]);
        }

        return rest_ensure_response(['deleted' => true, 'hard' => false]);
    }

    /**
     * Soft Delete: Setzt Status auf 'deleted', behält alle Daten.
     *
     * @param string $table Tabelle (users)
     * @param int $id Employee ID
     * @param int|null $tenantId Tenant ID
     */
    public static function softDeleteRecord(string $table, int $id, ?int $tenantId): void
    {
        global $wpdb;

        $where = ['id' => $id];
        $whereFormats = ['%d'];

        if ($tenantId) {
            $where['tenant_id'] = $tenantId;
            $whereFormats[] = '%d';
        }

        $wpdb->update(
            $table,
            [
                'status' => 'deleted',
                'deleted_at' => null,
                'updated_at' => current_time('mysql'),
            ],
            $where,
            ['%s', '%s', '%s'],
            $whereFormats
        );
    }

    /**
     * Hard Delete: Anonymisiert PII, setzt deleted_at.
     *
     * Anonymisiert:
     * - Namen, E-Mail, Telefon, Adresse
     * - Birthdate, Gender, Note, Description
     * - Avatar, Timezone, External ID, Badge ID
     * - Password Hash, Reset Token
     * - Roles Array
     *
     * @param string $table Tabelle (users)
     * @param int $id Employee ID
     * @param int|null $tenantId Tenant ID
     */
    public static function hardDeleteRecord(string $table, int $id, ?int $tenantId): void
    {
        global $wpdb;

        $anonEmail = 'deleted+' . $id . '@invalid.local';

        $data = [
            'status' => 'deleted',
            'deleted_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),

            // PII Anonymisierung
            'first_name' => null,
            'last_name' => null,
            'email' => $anonEmail,
            'phone' => null,
            'address' => null,
            'address_2' => null,
            'zip' => null,
            'city' => null,
            'country' => null,
            'birthdate' => null,
            'gender' => null,
            'language' => null,
            'note' => null,
            'description' => null,
            'avatar_url' => null,
            'timezone' => null,
            'external_id' => null,
            'badge_id' => null,
            'password_hash' => null,
            'password_reset_token' => null,
            'roles' => wp_json_encode([]),
        ];

        $where = ['id' => $id];
        $whereFormats = ['%d'];

        if ($tenantId) {
            $where['tenant_id'] = $tenantId;
            $whereFormats[] = '%d';
        }

        $dataFormats = array_fill(0, count($data), '%s');

        $wpdb->update($table, $data, $where, $dataFormats, $whereFormats);
    }
}
