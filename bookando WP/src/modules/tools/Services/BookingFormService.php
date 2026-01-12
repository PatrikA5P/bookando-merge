<?php

declare(strict_types=1);

namespace Bookando\Modules\tools\Services;

use Bookando\Core\Database\Database;
use function wp_json_encode;
use function json_decode;

class BookingFormService
{
    private const TABLE_NAME = 'bookando_booking_forms';

    /**
     * Get all booking forms
     *
     * @return array
     */
    public static function getAll(): array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $results = $wpdb->get_results(
            "SELECT * FROM `{$table}` ORDER BY is_default DESC, id DESC",
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map([self::class, 'formatForm'], $results);
    }

    /**
     * Get a single booking form by ID
     *
     * @param int $id
     * @return array|null
     */
    public static function get(int $id): ?array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return self::formatForm($result);
    }

    /**
     * Create a new booking form
     *
     * @param array $data
     * @return array|null
     */
    public static function create(array $data): ?array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        // If setting as default, unset other defaults
        if (!empty($data['is_default'])) {
            $wpdb->update(
                $table,
                ['is_default' => 0],
                [],
                ['%d'],
                []
            );
        }

        $insertData = [
            'tenant_id'   => get_current_user_id(), // TODO: Replace with actual tenant_id
            'name'        => $data['name'],
            'description' => $data['description'] ?? '',
            'fields'      => wp_json_encode($data['fields'] ?? []),
            'is_default'  => !empty($data['is_default']) ? 1 : 0,
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ];

        $inserted = $wpdb->insert(
            $table,
            $insertData,
            ['%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        if (!$inserted) {
            return null;
        }

        return self::get($wpdb->insert_id);
    }

    /**
     * Update an existing booking form
     *
     * @param int $id
     * @param array $data
     * @return array|null
     */
    public static function update(int $id, array $data): ?array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        // If setting as default, unset other defaults
        if (!empty($data['is_default'])) {
            $wpdb->update(
                $table,
                ['is_default' => 0],
                ['id != %d' => $id],
                ['%d'],
                ['%d']
            );
        }

        $updateData = [
            'updated_at' => current_time('mysql'),
        ];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['fields'])) {
            $updateData['fields'] = wp_json_encode($data['fields']);
        }
        if (isset($data['is_default'])) {
            $updateData['is_default'] = !empty($data['is_default']) ? 1 : 0;
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = !empty($data['is_active']) ? 1 : 0;
        }

        $updated = $wpdb->update(
            $table,
            $updateData,
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%d', '%d'],
            ['%d']
        );

        if ($updated === false) {
            return null;
        }

        return self::get($id);
    }

    /**
     * Delete a booking form
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        // Check if it's the default form
        $form = self::get($id);
        if ($form && $form['is_default']) {
            return false; // Cannot delete default form
        }

        $deleted = $wpdb->delete(
            $table,
            ['id' => $id],
            ['%d']
        );

        return $deleted !== false;
    }

    /**
     * Get the default booking form
     *
     * @return array|null
     */
    public static function getDefault(): ?array
    {
        global $wpdb;
        $table = Database::table(self::TABLE_NAME);

        $result = $wpdb->get_row(
            "SELECT * FROM `{$table}` WHERE is_default = 1 AND is_active = 1 LIMIT 1",
            ARRAY_A
        );

        if (!$result) {
            return null;
        }

        return self::formatForm($result);
    }

    /**
     * Format a booking form for output
     *
     * @param array $form
     * @return array
     */
    private static function formatForm(array $form): array
    {
        return [
            'id'          => (int) $form['id'],
            'name'        => $form['name'],
            'description' => $form['description'] ?? '',
            'fields'      => json_decode($form['fields'] ?? '[]', true),
            'is_default'  => (bool) $form['is_default'],
            'is_active'   => (bool) $form['is_active'],
            'created_at'  => $form['created_at'],
            'updated_at'  => $form['updated_at'],
        ];
    }
}
