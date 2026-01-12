<?php

declare(strict_types=1);

namespace Bookando\Modules\partnerhub\Api;

use WP_REST_Request;
use WP_REST_Server;
use Bookando\Core\Api\Response;
use Bookando\Core\Base\BaseApi;
use Bookando\Core\Dispatcher\RestPermissions;
use Bookando\Modules\partnerhub\Capabilities;
use Bookando\Modules\partnerhub\RestHandler;
use function __;
use function _x;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'partnerhub'; }
    protected static function getBaseRoute(): string     { return '/partnerhub'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    protected static function defaultPermission(): callable
    {
        return static fn() => static::canViewModule();
    }

    protected static function managePermission(): callable
    {
        return static fn() => static::canManageModule();
    }

    private static function canViewModule(): bool
    {
        return current_user_can(Capabilities::CAPABILITY_VIEW)
            || current_user_can(Capabilities::CAPABILITY_VIEW_LEGACY);
    }

    private static function canManageModule(): bool
    {
        return current_user_can(Capabilities::CAPABILITY_MANAGE)
            || current_user_can(Capabilities::CAPABILITY_MANAGE_LEGACY);
    }

    public static function registerRoutes(): void
    {
        // Partners
        static::registerRoute('/partners', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
            'callback' => static::restCallback('partners'),
            'permission_callback' => static::defaultPermission(),
        ]);

        static::registerRoute('/partners/(?P<id>\d+)', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::EDITABLE, WP_REST_Server::DELETABLE),
            'callback' => static::restCallback('partners', ['subkey' => 'id']),
            'permission_callback' => static::managePermission(),
            'args'     => [
                'id' => [
                    'validate_callback' => static fn($value) => is_numeric($value) && (int) $value > 0,
                ],
            ],
        ]);

        // Mappings
        static::registerRoute('/mappings', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
            'callback' => static::restCallback('mappings'),
            'permission_callback' => static::defaultPermission(),
        ]);

        static::registerRoute('/mappings/(?P<id>\d+)', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::EDITABLE, WP_REST_Server::DELETABLE),
            'callback' => static::restCallback('mappings', ['subkey' => 'id']),
            'permission_callback' => static::managePermission(),
        ]);

        // Rules
        static::registerRoute('/rules', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
            'callback' => static::restCallback('rules'),
            'permission_callback' => static::defaultPermission(),
        ]);

        static::registerRoute('/rules/(?P<id>\d+)', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::EDITABLE, WP_REST_Server::DELETABLE),
            'callback' => static::restCallback('rules', ['subkey' => 'id']),
            'permission_callback' => static::managePermission(),
        ]);

        // Feeds
        static::registerRoute('/feeds', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
            'callback' => static::restCallback('feeds'),
            'permission_callback' => static::defaultPermission(),
        ]);

        // Consents
        static::registerRoute('/consents', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
            'callback' => static::restCallback('consents'),
            'permission_callback' => function () {
                return current_user_can('view_bookando_partner_consents');
            },
        ]);

        // Dashboard
        static::registerRoute('/dashboard', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('dashboard'),
            'permission_callback' => static::defaultPermission(),
        ]);

        // Audit Logs
        static::registerRoute('/audit-logs', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => static::restCallback('audit_logs'),
            'permission_callback' => function () {
                return current_user_can('view_bookando_partner_audit_logs');
            },
        ]);
    }
}
