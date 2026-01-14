<?php

declare(strict_types=1);

namespace Bookando\Modules\Employees\Api;

use WP_REST_Server;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\employees\RestHandler;
use function _x;

class Api extends BaseApi
{
    private const NS   = 'bookando/v1';
    private const BASE = '/employees';

    protected static function getNamespace(): string     { return self::NS; }
    protected static function getModuleSlug(): string    { return 'employees'; }
    protected static function getBaseRoute(): string     { return self::BASE; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    protected static function guardCallback(): ?callable
    {
        return static fn(\WP_REST_Request $request) => RestHandler::guardPermissions($request);
    }

    public static function registerRoutes(): void
    {
        foreach (self::routeMatrix() as $route) {
            $args = [
                'methods' => $route['methods'],
                'callback' => $route['callback'],
            ];

            if (isset($route['args'])) {
                $args['args'] = $route['args'];
            }

            if (isset($route['show_in_index'])) {
                $args['show_in_index'] = $route['show_in_index'];
            }

            static::registerRoute($route['path'], $args);
        }
    }

    /**
     * Routen-Matrix des Moduls (Pfad, Methoden, Handler, optionale Argumente).
     *
     * @return array<int, array{
     *     path: string,
     *     methods: array<int, string>,
     *     callback: callable,
     *     args?: array,
     *     show_in_index?: bool
     * }>
     */
    private static function routeMatrix(): array
    {
        $idArgs = [
            'id' => [
                'required'          => true,
                'validate_callback' => static fn($v) => is_numeric($v) && (int) $v > 0,
                'sanitize_callback' => 'absint',
                'description'       => _x('Numeric employee ID.', 'REST parameter description', 'bookando'),
            ],
        ];

        $subkeyArgs = [
            'subkey' => [
                'required'          => true,
                'validate_callback' => static fn($v) => is_numeric($v) && (int) $v > 0,
                'sanitize_callback' => 'absint',
                'description'       => _x('Legacy numeric employee id.', 'REST parameter description', 'bookando'),
            ],
        ];

        return [
            [
                'path'          => '',
                'methods'       => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
                'callback'      => static::restCallback('employees'),
                'show_in_index' => true,
            ],
            [
                'path'          => 'employees',
                'methods'       => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
                'callback'      => static::restCallback('employees'),
                'show_in_index' => false,
            ],
            [
                'path'          => '(?P<id>\d+)',
                'methods'       => static::methods(
                    WP_REST_Server::READABLE,
                    WP_REST_Server::EDITABLE,
                    WP_REST_Server::DELETABLE
                ),
                'callback'      => static::restCallbackWithId('employees', 'id'),
                'args'          => $idArgs,
                'show_in_index' => true,
            ],
            [
                'path'          => 'employees/(?P<subkey>\d+)',
                'methods'       => static::methods(
                    WP_REST_Server::READABLE,
                    WP_REST_Server::EDITABLE,
                    WP_REST_Server::DELETABLE
                ),
                'callback'      => static::restCallback('employees', ['subkey' => 'subkey']),
                'args'          => $subkeyArgs,
                'show_in_index' => false,
            ],
            [
                'path'          => 'bulk',
                'methods'       => static::methods(WP_REST_Server::CREATABLE),
                'callback'      => static::restCallback('bulk'),
                'show_in_index' => false,
            ],
            [
                'path'     => '(?P<id>\d+)/workday-sets',
                'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
                'callback' => static::restCallbackWithId('workdaySets', 'id'),
                'args'     => $idArgs,
            ],
            [
                'path'     => '(?P<id>\d+)/special-day-sets',
                'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::CREATABLE),
                'callback' => static::restCallbackWithId('specialDaySets', 'id'),
                'args'     => $idArgs,
            ],
            [
                'path'          => '(?P<id>\d+)/special-days',
                'methods'       => static::methods(WP_REST_Server::READABLE),
                'callback'      => static::restCallbackWithId('specialDays', 'id'),
                'args'          => $idArgs,
                'show_in_index' => false,
            ],
            [
                'path'     => '(?P<id>\d+)/calendars',
                'methods'  => static::methods(WP_REST_Server::READABLE, WP_REST_Server::EDITABLE),
                'callback' => static::restCallbackWithId('calendars', 'id'),
                'args'     => $idArgs,
            ],
            [
                'path'     => '(?P<id>\d+)/calendar/invite',
                'methods'  => static::methods(WP_REST_Server::CREATABLE),
                'callback' => static::restCallbackWithId('calendarInvite', 'id'),
                'args'     => $idArgs,
            ],
            [
                'path'          => '(?P<id>\d+)/calendar/connections/ics',
                'methods'       => static::methods(WP_REST_Server::CREATABLE, WP_REST_Server::DELETABLE),
                'callback'      => static::restCallbackWithId('calendarIcs', 'id'),
                'args'          => $idArgs,
                'show_in_index' => false,
            ],
            [
                'path'          => '(?P<id>\d+)/days-off',
                'methods'       => static::methods(
                    WP_REST_Server::READABLE,
                    WP_REST_Server::CREATABLE,
                    WP_REST_Server::EDITABLE
                ),
                'callback'      => static::restCallbackWithId('daysOff', 'id'),
                'args'          => $idArgs,
                'show_in_index' => true,
            ],
        ];
    }
}
