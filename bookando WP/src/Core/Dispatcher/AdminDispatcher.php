<?php

namespace Bookando\Core\Dispatcher;

/**
 * Dispatcher für spezielle Admin-Requests, z.B. Bulk-Aktionen, Config-APIs
 */
class AdminDispatcher
{
    public static function register(): void
    {
        // Beispiel: Eigene Admin-Ajax-Route (nur eingeloggte Admins)
        add_action('wp_ajax_bookando_admin', [self::class, 'handle']);
    }

    public static function handle()
    {
        // Permission prüfen!
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'No permission'], 403);
        }

        error_log('[Bookando] AdminDispatcher: Anfrage erhalten');
        // Logik: z.B. Bulk-Operationen, Einstellungen, ...
        wp_send_json_success(['message' => 'OK']);
    }
}
