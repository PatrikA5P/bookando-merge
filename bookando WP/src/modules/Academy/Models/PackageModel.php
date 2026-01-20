<?php

declare(strict_types=1);

namespace Bookando\Modules\Academy\Models;

class PackageModel
{
    protected \wpdb $db;
    protected string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'bookando_academy_packages';
    }

    /**
     * Alle aktiven Pakete laden.
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY created_at DESC";
        $packages = $this->db->get_results($sql, ARRAY_A) ?: [];

        foreach ($packages as &$package) {
            $package['items'] = !empty($package['items']) ? json_decode($package['items'], true) : [];
        }

        return $packages;
    }

    /**
     * Paket per ID laden.
     */
    public function find(int $id): ?array
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
        $package = $this->db->get_row($sql, ARRAY_A);

        if (!$package) {
            return null;
        }

        $package['items'] = !empty($package['items']) ? json_decode($package['items'], true) : [];
        return $package;
    }

    /**
     * Paket erstellen oder aktualisieren.
     */
    public function save(array $data): int
    {
        $packageData = $this->sanitizePackage($data);

        if (!empty($packageData['id'])) {
            // Update
            $id = (int)$packageData['id'];
            unset($packageData['id']);
            $packageData['updated_at'] = current_time('mysql');

            $this->db->update($this->table, $packageData, ['id' => $id]);
            error_log('[Bookando Academy] Updated package ID: ' . $id);
        } else {
            // Insert
            unset($packageData['id']);
            $packageData['created_at'] = current_time('mysql');
            $packageData['updated_at'] = $packageData['created_at'];

            $this->db->insert($this->table, $packageData);
            $id = (int)$this->db->insert_id;
            error_log('[Bookando Academy] Created package ID: ' . $id);
        }

        return $id;
    }

    /**
     * Paket löschen.
     */
    public function delete(int $id): bool
    {
        $result = $this->db->delete($this->table, ['id' => $id]);
        error_log('[Bookando Academy] Deleted package ID: ' . $id);
        return $result !== false;
    }

    /**
     * Package-Daten bereinigen.
     */
    protected function sanitizePackage(array $data): array
    {
        // Berechne Rabatt, falls nicht vorhanden
        $price = isset($data['price']) ? (float)$data['price'] : 0;
        $originalPrice = isset($data['original_price']) ? (float)$data['original_price'] : null;
        $discountPercent = 0;

        if ($originalPrice && $originalPrice > 0 && $price < $originalPrice) {
            $discountPercent = round((($originalPrice - $price) / $originalPrice) * 100, 2);
        }

        return [
            'id' => !empty($data['id']) ? (int)$data['id'] : null,
            'title' => sanitize_text_field($data['title'] ?? ''),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'items' => !empty($data['items']) ? wp_json_encode($data['items']) : '[]',
            'price' => $price,
            'original_price' => $originalPrice,
            'discount_percent' => $discountPercent,
            'currency' => sanitize_text_field($data['currency'] ?? 'CHF'),
            'validity_days' => isset($data['validity_days']) ? (int)$data['validity_days'] : null,
            'category' => sanitize_text_field($data['category'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
        ];
    }

    /**
     * Pakete nach Kategorie filtern.
     */
    public function findByCategory(string $category): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE category = %s AND status = 'active' ORDER BY price ASC",
            $category
        );
        $packages = $this->db->get_results($sql, ARRAY_A) ?: [];

        foreach ($packages as &$package) {
            $package['items'] = !empty($package['items']) ? json_decode($package['items'], true) : [];
        }

        return $packages;
    }
}
