<?php
/**
 * Installer für Modul "settings"
 */
namespace Bookando\Modules\settings;

class Installer
{
    public static function install(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'settings';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY -- Primärschlüssel,
  title VARCHAR(191) -- Name oder Bezeichnung,
  status VARCHAR(32) -- Status (aktiv/inaktiv),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP -- Erstellt am
        ) $charset;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}