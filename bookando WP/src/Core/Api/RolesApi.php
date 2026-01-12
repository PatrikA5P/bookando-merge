<?php

namespace Bookando\Core\Api;

use Bookando\Core\Tenant\TenantManager;
use Bookando\Core\Api\Response;
use WP_REST_Request;
use WP_REST_Server;

class RolesApi
{
    public static function register(): void
    {
        register_rest_route('bookando/v1', '/roles', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [self::class, 'get_roles'],
            'permission_callback' => function () {
                // Lesen genügt – passe das gern strenger an (z. B. manage_options)
                return is_user_logged_in() && current_user_can('read');
            },
        ]);
    }

    public static function get_roles(WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $p = $wpdb->prefix . 'bookando_';

        // DEV: gib einfach alles zurück
        if (defined('BOOKANDO_DEV') && BOOKANDO_DEV) {
            $roles = $wpdb->get_col("SELECT slug FROM {$p}roles");
            return Response::ok(['roles' => array_values(array_unique($roles))]);
        }

        // PROD: auf Tenant einschränken + Defaults sicherstellen
        $tenant_id = (int) TenantManager::currentTenantId();

        // 1) Alle Rollen, die im Tenant über Nutzerzuweisungen vorkommen
        $tenantRoles = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT r.slug
             FROM {$p}user_roles ur
             JOIN {$p}users u ON u.id = ur.user_id
             JOIN {$p}roles r ON r.id = ur.role_id
             WHERE u.tenant_id = %d",
            $tenant_id
        ));

        // 2) System-Default-Rollen (solange du kein is_default-Feld hast)
        $defaults = ['bookando_customer','bookando_employee','bookando_admin'];

        $roles = array_values(array_unique(array_merge($tenantRoles ?: [], $defaults)));
        return Response::ok(['roles' => $roles]);
    }
}
