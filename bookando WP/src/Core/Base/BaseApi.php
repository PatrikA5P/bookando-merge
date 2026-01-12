<?php

namespace Bookando\Core\Base;

use Bookando\Core\Dispatcher\RestDispatcher;
use Bookando\Core\Dispatcher\RestModuleGuard;
use Bookando\Core\Api\Response;
use WP_Error;
use WP_REST_Request;

/**
 * Zentrale Basisklasse für Modul-APIs.
 *
 * - Kapselt die Registrierung beim {@see RestDispatcher}
 * - Stellt Utility-Methoden für Routendefinitionen bereit
 * - Behält die bisherigen Daten-Helfer (Pagination/Search/Sort)
 */
abstract class BaseApi
{
    /** REST-Namespace, z. B. "bookando/v1". */
    abstract protected static function getNamespace(): string;

    /** Modul-Slug (für den {@see RestDispatcher}). */
    abstract protected static function getModuleSlug(): string;

    /** Basispfad der Modulrouten, z. B. "/settings". */
    abstract protected static function getBaseRoute(): string;

    /** Vollqualifizierte Klasse des RestHandlers. */
    abstract protected static function getRestHandlerClass(): string;

    /**
     * Optionaler zusätzlicher Guard, der an {@see RestModuleGuard::for()} übergeben wird.
     *
     * @return callable|null
     */
    protected static function guardCallback(): ?callable
    {
        return null;
    }

    /** Registriert das Modul beim Dispatcher. */
    public static function register(): void
    {
        RestDispatcher::registerModule(static::getModuleSlug(), static::getRestHandlerClass());
    }

    /**
     * Rückgabe der Standard-Permission-Callback für alle Modulrouten.
     */
    protected static function defaultPermission(): callable
    {
        return RestModuleGuard::for(static::getModuleSlug(), static::guardCallback());
    }

    /**
     * Registriert eine Route relativ zum Modul-Basispfad.
     *
     * @param string $path Relativer Pfad ("", "list", "/(?P<id>\d+)")
     * @param array  $args Standardargumente von {@see register_rest_route}
     */
    protected static function registerRoute(string $path, array $args): void
    {
        if (!isset($args['permission_callback'])) {
            $args['permission_callback'] = static::defaultPermission();
        }

        register_rest_route(
            static::getNamespace(),
            static::normalizeRoute($path, true),
            $args
        );
    }

    /**
     * Registriert eine Route mit absolutem Pfad (ohne Modul-Basis).
     */
    protected static function registerAbsoluteRoute(string $route, array $args): void
    {
        if (!isset($args['permission_callback'])) {
            $args['permission_callback'] = static::defaultPermission();
        }

        register_rest_route(
            static::getNamespace(),
            static::normalizeRoute($route, false),
            $args
        );
    }

    /**
     * Erstellt einen Callback, der die Legacy-Signatur des RestHandlers nutzt
     * (array $params, WP_REST_Request $request).
     *
     * @param array<string,string> $paramMap request-param => handler-param
     */
    protected static function restCallback(string $method, array $paramMap = []): callable
    {
        $handler = static::getRestHandlerClass();

        return static function (WP_REST_Request $request) use ($handler, $method, $paramMap) {
            $params = [];
            foreach ($paramMap as $requestKey => $handlerKey) {
                $value = $request->get_param($requestKey);
                if ($value !== null) {
                    $params[$handlerKey] = $value;
                }
            }

            return $handler::$method($params, $request);
        };
    }

    /**
     * Erstellt einen Callback, der zwingend eine ID erwartet.
     */
    protected static function restCallbackWithId(string $method, string $param = 'id'): callable
    {
        $handler = static::getRestHandlerClass();

        return static function (WP_REST_Request $request) use ($handler, $method, $param) {
            $raw = $request->get_param($param);
            $id  = is_numeric($raw) ? (int) $raw : null;
            if ($id === null || $id <= 0) {
                return new WP_Error('missing_id', 'Missing required resource id for this endpoint.', ['status' => 400]);
            }

            return $handler::$method(['id' => $id, 'subkey' => $id], $request);
        };
    }

    /**
     * Normalisiert Routenpfade auf die Form "/foo/bar".
     */
    private static function normalizeRoute(string $path, bool $useBase): string
    {
        $path = trim($path);
        $base = trim(static::getBaseRoute());

        if ($useBase) {
            $base = trim($base, '/');
            $path = trim($path, '/');

            if ($base === '' && $path === '') {
                return '/';
            }

            if ($base === '') {
                return '/' . $path;
            }

            if ($path === '') {
                return '/' . $base;
            }

            return '/' . $base . '/' . $path;
        }

        $path = trim($path, '/');
        return '/' . $path;
    }

    /**
     * Hilfsfunktion zum Normalisieren von HTTP-Methoden.
     *
     * @return string[]
     */
    protected static function methods(string ...$methods): array
    {
        $result = [];
        foreach ($methods as $method) {
            $chunks = preg_split('/\s*,\s*/', $method);
            foreach ($chunks as $chunk) {
                $chunk = strtoupper(trim($chunk));
                if ($chunk === '') {
                    continue;
                }
                $result[$chunk] = true;
            }
        }

        return array_keys($result);
    }

    /**
     * Rückfall-Handler – wird selten genutzt, bleibt aber aus BC-Gründen erhalten.
     */
    public static function handleGet(WP_REST_Request $request): \WP_REST_Response
    {
        return Response::ok([
            'success' => false,
            'message' => 'handleGet() muss in der Kindklasse überschrieben werden.',
        ]);
    }

    // -----------------------------------------------
    // 🔹 UTILITY: Pagination-Parameter
    // -----------------------------------------------
    public static function getPaginationParams(WP_REST_Request $request): array
    {
        $page     = max(1, (int) $request->get_param('page') ?: 1);
        $per_page = min(100, max(1, (int) $request->get_param('per_page') ?: 20));
        $offset   = ($page - 1) * $per_page;
        return compact('page', 'per_page', 'offset');
    }

    // -----------------------------------------------
    // 🔹 UTILITY: Filter/Suche (Beispiel: nach Feld „title“)
    // -----------------------------------------------
    protected static function applySearch(array $data, WP_REST_Request $request, string $field = 'title'): array
    {
        $search = trim((string) ($request->get_param('search') ?? ''));
        if ($search === '') {
            return $data;
        }

        return array_filter($data, static function ($row) use ($search, $field) {
            return isset($row[$field]) && stripos((string) $row[$field], $search) !== false;
        });
    }

    // -----------------------------------------------
    // 🔹 UTILITY: Sortierung (einfach, nach Feld, ASC/DESC)
    // -----------------------------------------------
    protected static function applySort(array $data, WP_REST_Request $request, string $field = 'id', string $defaultOrder = 'DESC'): array
    {
        $sort_by = $request->get_param('sort_by') ?: $field;
        $order   = strtoupper($request->get_param('order') ?: $defaultOrder);

        usort($data, static function ($a, $b) use ($sort_by, $order) {
            if (!isset($a[$sort_by], $b[$sort_by])) {
                return 0;
            }
            if ($a[$sort_by] == $b[$sort_by]) {
                return 0;
            }

            $lessThan = $a[$sort_by] < $b[$sort_by];
            $desc     = $order === 'DESC';

            return ($lessThan xor $desc) ? 1 : -1;
        });

        return $data;
    }

    // -----------------------------------------------
    // 🔹 UTILITY: Datumsfilter (optional)
    // -----------------------------------------------
    protected static function applyDateFilter(array $data, WP_REST_Request $request, string $field = 'created_at'): array
    {
        $from = $request->get_param('from');
        $to   = $request->get_param('to');

        return array_filter($data, static function ($row) use ($from, $to, $field) {
            $val = isset($row[$field]) ? strtotime((string) $row[$field]) : false;
            if (!$val) {
                return false;
            }
            if ($from && $val < strtotime((string) $from)) {
                return false;
            }
            if ($to && $val > strtotime((string) $to)) {
                return false;
            }
            return true;
        });
    }
}
