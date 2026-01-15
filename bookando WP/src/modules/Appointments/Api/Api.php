<?php

declare(strict_types=1);

namespace Bookando\Modules\Appointments\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\Appointments\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'appointments'; }
    protected static function getBaseRoute(): string     { return '/appointments'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        foreach (self::routeMatrix() as $route) {
            $args = [
                'methods' => $route['methods'],
                'callback' => $route['callback'],
            ];

            if (isset($route['show_in_index'])) {
                $args['show_in_index'] = $route['show_in_index'];
            }

            static::registerRoute($route['path'], $args);
        }
    }

    protected static function guardCallback(): ?callable
    {
        return null;
    }

    /**
     * Definiert alle REST-Routen des Moduls als Matrix (Pfad, Methoden, Handler).
     *
     * @return array<int, array{
     *     path: string,
     *     methods: array<int, string>,
     *     callback: callable,
     *     show_in_index?: bool
     * }>
     */
    private static function routeMatrix(): array
    {
        return [
            [
                'path'     => 'timeline',
                'methods'  => static::methods(WP_REST_Server::READABLE),
                'callback' => static::restCallback('timeline'),
            ],
            [
                'path'     => 'appointments',
                'methods'  => static::methods(
                    WP_REST_Server::READABLE,
                    WP_REST_Server::CREATABLE,
                    WP_REST_Server::EDITABLE,
                    WP_REST_Server::DELETABLE
                ),
                'callback' => static::restCallback('appointments'),
            ],
            [
                'path'     => 'appointments/(?P<subkey>[a-zA-Z0-9_-]+)',
                'methods'  => static::methods(
                    WP_REST_Server::READABLE,
                    WP_REST_Server::CREATABLE,
                    WP_REST_Server::EDITABLE,
                    WP_REST_Server::DELETABLE
                ),
                'callback' => static::restCallback('appointments', ['subkey' => 'subkey']),
            ],
            [
                'path'     => 'assign',
                'methods'  => static::methods(WP_REST_Server::CREATABLE),
                'callback' => static::restCallback('assign'),
            ],
            [
                'path'     => 'lookups',
                'methods'  => static::methods(WP_REST_Server::READABLE),
                'callback' => static::restCallback('lookups'),
            ],
        ];
    }
}
