<?php

declare(strict_types=1);

namespace Bookando\Modules\Resources;

use Bookando\Core\Api\Response;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Dispatcher\RestModuleGuard;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function __;
use function apply_filters;
use function sprintf;

class RestHandler
{
    private const CAPABILITY_FILTER = 'bookando_resources_capability_map';

    /**
     * @var array<string, array<string, string>>
     */
    private const CAPABILITY_MAP = [
        'POST'   => [
            'locations' => Capabilities::CAPABILITY_MANAGE,
            'rooms'     => Capabilities::CAPABILITY_MANAGE,
            'materials' => Capabilities::CAPABILITY_MANAGE,
        ],
        'DELETE' => [
            'locations' => Capabilities::CAPABILITY_MANAGE,
            'rooms'     => Capabilities::CAPABILITY_MANAGE,
            'materials' => Capabilities::CAPABILITY_MANAGE,
        ],
    ];

    public static function register(): void
    {
        $guard = RestModuleGuard::for('resources', static fn(\WP_REST_Request $request) => self::guardCapabilities($request));
        $stateGuard = RestModuleGuard::for(Capabilities::CAPABILITY_MANAGE);

        register_rest_route('bookando/v1', '/resources/state', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'getState'],
            'permission_callback' => $stateGuard,
        ]);

        foreach (['locations', 'rooms', 'materials'] as $type) {
            register_rest_route('bookando/v1', "/resources/{$type}", [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => fn(WP_REST_Request $request) => self::listResources($type, $request),
                'permission_callback' => $stateGuard,
            ]);

            register_rest_route('bookando/v1', "/resources/{$type}", [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => fn(WP_REST_Request $request) => self::saveResource($type, $request),
                'permission_callback' => $guard,
            ]);

            register_rest_route('bookando/v1', "/resources/{$type}/(?P<id>[a-zA-Z0-9-]+)", [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => fn(WP_REST_Request $request) => self::getResource($type, $request),
                'permission_callback' => $stateGuard,
            ]);

            register_rest_route('bookando/v1', "/resources/{$type}/(?P<id>[a-zA-Z0-9-]+)", [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => fn(WP_REST_Request $request) => self::deleteResource($type, $request),
                'permission_callback' => $guard,
            ]);
        }
    }

    public static function state($params, WP_REST_Request $request)
    {
        if (strtoupper($request->get_method()) !== 'GET') {
            return Response::error([
                'code'    => 'method_not_allowed',
                'message' => __('Methode nicht unterstützt.', 'bookando'),
            ], 405);
        }

        return self::getState();
    }

    public static function locations(array $params, WP_REST_Request $request): WP_REST_Response
    {
        return self::handleResource('locations', $params, $request);
    }

    public static function rooms(array $params, WP_REST_Request $request): WP_REST_Response
    {
        return self::handleResource('rooms', $params, $request);
    }

    public static function materials(array $params, WP_REST_Request $request): WP_REST_Response
    {
        return self::handleResource('materials', $params, $request);
    }

    public static function getState(): WP_REST_Response
    {
        return Response::ok(StateRepository::getState());
    }

    public static function listResources(string $type, WP_REST_Request $request): WP_REST_Response
    {
        unset($request);

        return Response::ok(StateRepository::listResources($type));
    }

    public static function getResource(string $type, WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        if ($id === '') {
            return Response::error([
                'code'    => 'missing_id',
                'message' => __('Ressource fehlt.', 'bookando'),
            ], 400);
        }

        $resource = StateRepository::findResource($type, $id);
        if ($resource === null) {
            return Response::error([
                'code'    => 'not_found',
                'message' => __('Ressource nicht gefunden.', 'bookando'),
            ], 404);
        }

        return Response::ok($resource);
    }

    private static function handleResource(string $type, array $params, WP_REST_Request $request): WP_REST_Response
    {
        $method = strtoupper($request->get_method());
        if ($method === 'POST') {
            return self::saveResource($type, $request);
        }

        if ($method === 'DELETE') {
            if (!empty($params['subkey'])) {
                $request->set_param('id', $params['subkey']);
            }
            return self::deleteResource($type, $request);
        }

        if ($method === 'GET') {
            $subkey = (string) ($params['subkey'] ?? '');
            if ($subkey !== '') {
                $request->set_param('id', $subkey);
                return self::getResource($type, $request);
            }

            return self::listResources($type, $request);
        }

        return Response::error([
            'code'    => 'method_not_allowed',
            'message' => __('Methode nicht unterstützt.', 'bookando'),
        ], 405);
    }

    public static function saveResource(string $type, WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            return Response::error([
                'code'    => 'invalid_payload',
                'message' => __('Ungültige Ressourcendaten.', 'bookando'),
            ], 400);
        }

        $validated = ResourcesService::validateResource($type, $payload);
        if ($validated instanceof WP_Error) {
            return Response::error($validated);
        }

        $resource = StateRepository::upsertResource($type, $validated);
        return Response::created($resource);
    }

    public static function deleteResource(string $type, WP_REST_Request $request): WP_REST_Response
    {
        $id = (string) $request->get_param('id');
        if ($id === '') {
            return Response::error([
                'code'    => 'missing_id',
                'message' => __('Ressource fehlt.', 'bookando'),
            ], 400);
        }

        $deleted = StateRepository::deleteResource($type, $id);
        return Response::ok([
            'deleted' => $deleted,
            'id'      => $id,
        ]);
    }

    public static function canManage(): bool
    {
        return Gate::canManage('resources');
    }

    public static function guardCapabilities(WP_REST_Request $request): bool|WP_Error
    {
        if (!Gate::isWrite($request)) {
            return true;
        }

        $method = strtoupper($request->get_method() ?? 'GET');
        $action = self::resolveActionKey($request);

        $map = self::capabilityMap();
        $capability = $map[$method][$action] ?? $map[$method]['*'] ?? null;

        if ($capability === null) {
            return true;
        }

        if (Gate::canManage('resources')) {
            return true;
        }

        if (Gate::hasCapability($capability)) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            sprintf(__('Zusätzliche Berechtigung %s erforderlich.', 'bookando'), $capability),
            ['status' => 403]
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function capabilityMap(): array
    {
        $map = self::CAPABILITY_MAP;

        if (function_exists('apply_filters')) {
            /** @var array<string, array<string, string>> $map */
            $map = (array) apply_filters(self::CAPABILITY_FILTER, $map);
        }

        return $map;
    }

    private static function resolveActionKey(WP_REST_Request $request): string
    {
        $route = method_exists($request, 'get_route') ? (string) $request->get_route() : '';
        if ($route === '') {
            return '';
        }

        $route = explode('?', $route, 2)[0];
        $parts = explode('/', trim($route, '/'));

        $index = array_search('resources', $parts, true);
        $segment = '';
        if ($index !== false) {
            $segment = $parts[$index + 1] ?? '';
        } elseif (isset($parts[3])) {
            $segment = $parts[3];
        }

        if ($segment === '') {
            return '';
        }

        if (strpos($segment, '(?P<') === 0) {
            foreach (['type', 'resource', 'resource_type'] as $key) {
                $value = (string) $request->get_param($key);
                if ($value !== '') {
                    return $value;
                }
            }

            $urlParams = $request->get_url_params();
            foreach (['type', 'resource', 'resource_type'] as $key) {
                if (isset($urlParams[$key]) && is_string($urlParams[$key]) && $urlParams[$key] !== '') {
                    return $urlParams[$key];
                }
            }

            return '';
        }

        return $segment;
    }
}
