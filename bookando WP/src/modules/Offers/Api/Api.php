<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Modules\Offers\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'offers'; }
    protected static function getBaseRoute(): string     { return '/offers'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        static::registerRoute('offers', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'list'],
            'permission_callback' => static::permissionWithFeature(),
        ]);

        static::registerRoute('offers', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'create'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);

        static::registerRoute('offers/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [RestHandler::class, 'get'],
            'permission_callback' => static::permissionWithFeature(),
            'args'                => [
                'id' => [
                    'validate_callback' => static fn($value) => is_numeric($value) && (int) $value > 0,
                ],
            ],
        ]);

        static::registerRoute('offers/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => [RestHandler::class, 'update'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);

        static::registerRoute('offers/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [RestHandler::class, 'delete'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);

        static::registerRoute('bulk', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [RestHandler::class, 'bulk'],
            'permission_callback' => static::permissionWithFeature('rest_api_write'),
        ]);
    }

    private static function permissionWithFeature(?string $feature = null): callable
    {
        $basePermission = static::defaultPermission();

        return static function (WP_REST_Request $request) use ($basePermission, $feature) {
            $result = $basePermission($request);
            if ($result instanceof WP_Error || $result === false) {
                return $result;
            }

            if ($feature === null) {
                return true;
            }

            $featureResult = LicenseManager::ensureFeature(static::getModuleSlug(), $feature);
            if ($featureResult instanceof WP_Error) {
                return $featureResult;
            }

            return true;
        };
    }
}
