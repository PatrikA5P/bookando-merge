<?php

declare(strict_types=1);

namespace Bookando\Modules\Dashboard\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'dashboard'; }
    protected static function getBaseRoute(): string     { return '/dashboard'; }
    protected static function getRestHandlerClass(): string { return \stdClass::class; }

    public static function registerRoutes(): void
    {
        // Get widget configuration
        static::registerRoute('widgets', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [self::class, 'getWidgets'],
            'permission_callback' => static::defaultPermission(),
        ]);

        // Save widget configuration
        static::registerRoute('widgets', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [self::class, 'saveWidgets'],
            'permission_callback' => static::defaultPermission(),
        ]);

        // Get dashboard statistics
        static::registerRoute('stats', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [self::class, 'getStats'],
            'permission_callback' => static::defaultPermission(),
        ]);
    }

    public static function getWidgets(\WP_REST_Request $request): \WP_REST_Response
    {
        $userId = get_current_user_id();
        $widgets = get_user_meta($userId, 'bookando_dashboard_widgets', true);

        if (!is_array($widgets) || empty($widgets)) {
            // Default widget configuration
            $widgets = [
                'stat-revenue',
                'stat-customers',
                'stat-appointments',
                'stat-time',
                'chart-revenue',
                'list-activity'
            ];
        }

        return \Bookando\Core\Api\Response::ok(['widgets' => $widgets]);
    }

    public static function saveWidgets(\WP_REST_Request $request): \WP_REST_Response
    {
        $userId = get_current_user_id();
        $widgets = $request->get_param('widgets');

        if (!is_array($widgets)) {
            return \Bookando\Core\Api\Response::error([
                'code' => 'invalid_data',
                'message' => __('Invalid widget configuration', 'bookando')
            ], 400);
        }

        update_user_meta($userId, 'bookando_dashboard_widgets', $widgets);

        return \Bookando\Core\Api\Response::ok(['success' => true]);
    }

    public static function getStats(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $tenantId = \Bookando\Core\Tenant\TenantManager::currentTenantId();

        // Mock statistics (replace with real queries later)
        $stats = [
            'revenue' => ['total' => 45231.89, 'change' => 20.1],
            'customers' => ['total' => 2345, 'change' => 15.3],
            'appointments' => ['total' => 452, 'change' => -4.5],
            'avgSessionTime' => ['value' => 58, 'change' => 1.2],
        ];

        return \Bookando\Core\Api\Response::ok($stats);
    }
}
