<?php

declare(strict_types=1);

namespace Bookando\Modules\Resources\Api;

use WP_REST_Request;
use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Core\Dispatcher\RestModuleGuard;
use Bookando\Modules\resources\Capabilities;
use Bookando\Modules\resources\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'resources'; }
    protected static function getBaseRoute(): string     { return '/resources'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    protected static function guardCallback(): ?callable
    {
        return static fn(\WP_REST_Request $request) => RestHandler::guardCapabilities($request);
    }

    public static function registerRoutes(): void
    {
        static::registerRoute('state', [
            'methods'  => WP_REST_Server::READABLE,
            'callback' => [RestHandler::class, 'getState'],
        ]);

        foreach (['locations', 'rooms', 'materials'] as $type) {
            static::registerRoute($type, [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => static function (WP_REST_Request $request) use ($type) {
                    return RestHandler::listResources($type, $request);
                },
                'permission_callback' => self::statePermission(),
            ]);

            static::registerRoute($type, [
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => static function (WP_REST_Request $request) use ($type) {
                    return RestHandler::saveResource($type, $request);
                },
            ]);

            static::registerRoute($type . '/(?P<id>[a-zA-Z0-9-]+)', [
                'methods'  => WP_REST_Server::READABLE,
                'callback' => static function (WP_REST_Request $request) use ($type) {
                    return RestHandler::getResource($type, $request);
                },
                'permission_callback' => self::statePermission(),
            ]);

            static::registerRoute($type . '/(?P<id>[a-zA-Z0-9-]+)', [
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => static function (WP_REST_Request $request) use ($type) {
                    return RestHandler::deleteResource($type, $request);
                },
            ]);
        }
    }

    private static function statePermission(): callable
    {
        return RestModuleGuard::for(Capabilities::CAPABILITY_MANAGE);
    }
}
