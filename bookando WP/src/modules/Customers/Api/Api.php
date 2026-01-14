<?php

declare(strict_types=1);

namespace Bookando\Modules\Customers\Api;

use WP_REST_Request;
use WP_REST_Server;
use Bookando\Core\Api\Response;
use Bookando\Core\Base\BaseApi;
use Bookando\Core\Dispatcher\RestPermissions;
use Bookando\Modules\customers\RestHandler;
use function __;
use function _x;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'customers'; }
    protected static function getBaseRoute(): string     { return '/customers'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    protected static function defaultPermission(): callable
    {
        return [RestPermissions::class, 'customers'];
    }

    public static function registerRoutes(): void
    {
        static::registerRoute('', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
            'callback' => static::restCallback('customers'),
            'args'     => [
                'include_deleted' => [
                    'validate_callback' => static fn($value) => in_array((string) $value, ['no', 'soft', 'all'], true),
                ],
            ],
        ]);

        static::registerRoute('(?P<subkey>\d+)', [
            'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::EDITABLE, WP_REST_Server::DELETABLE),
            'callback' => static::restCallback('customers', ['subkey' => 'subkey']),
            'args'     => [
                'subkey' => [
                    'description'       => _x('Customer ID', 'REST parameter description', 'bookando'),
                    'validate_callback' => static fn($value) => is_numeric($value) && (int) $value > 0,
                ],
                'hard'   => [
                    'description'       => _x('Hard delete when set to 1', 'REST parameter description', 'bookando'),
                    'validate_callback' => static function ($value) {
                        if ($value === null) {
                            return true;
                        }
                        if (is_bool($value)) {
                            return true;
                        }
                        return in_array((string) $value, ['0', '1'], true);
                    },
                ],
            ],
        ]);

        static::registerRoute('bulk', [
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => static::restCallback('bulk'),
        ]);

        static::registerRoute('export', [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => static::defaultPermission(),
            'callback'            => [self::class, 'exportCustomers'],
        ]);
    }

    public static function exportCustomers(WP_REST_Request $request): \WP_REST_Response
    {
        return Response::ok([
            'success' => true,
            'message' => __('Export noch nicht implementiert', 'bookando'),
        ]);
    }
}
