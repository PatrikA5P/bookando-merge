<?php

namespace Bookando\Core\Dispatcher;

use WP_Error;
use WP_REST_Request;
use Bookando\Core\Auth\Gate;
use Bookando\Core\Licensing\LicenseManager;
use Bookando\Core\Tenant\TenantManager;
use function _x;
use function sprintf;

/**
 * Factory for module specific REST permission callbacks.
 *
 * The goal is to provide a single, well tested entry point that takes care of
 * tenant resolution, nonce validation, licensing and capability checks. Modules
 * can optionally provide an "after" callback for fine grained checks (for
 * instance to allow self-service reads for non managers).
 */
final class RestModuleGuard
{
    /**
     * Builds a permission callback for a module.
     *
     * @param string               $module Module slug (e.g. "customers").
     * @param callable|Closure|null $after Optional extra validation hook. The
     *                                     callback receives the WP_REST_Request
     *                                     and must return bool|WP_Error.
     */
    public static function for(string $module, callable $after = null): callable
    {
        $module = trim($module);
        $capability = self::extractCapability($module);
        $moduleSlug = $capability !== null
            ? (self::inferModuleFromCapability($capability) ?? self::normalizeSlug($module))
            : self::normalizeSlug($module);

        return static function (WP_REST_Request $request) use ($moduleSlug, $capability, $after) {
            $resolvedTenant = TenantManager::resolveFromRequest($request);
            if ($resolvedTenant > 0) {
                TenantManager::setCurrentTenantId($resolvedTenant);
            }

            if ($moduleSlug !== '') {
                if (!LicenseManager::isModuleAllowed($moduleSlug)) {
                    return new WP_Error(
                        'module_not_allowed',
                        _x('Module not allowed for current plan', 'REST API error message', 'bookando'),
                        ['status' => 403]
                    );
                }

                $result = Gate::evaluate($request, $moduleSlug);
                if ($result instanceof WP_Error) {
                    return $result;
                }
            }

            if ($capability !== null) {
                $hasManage = $moduleSlug !== '' ? Gate::canManage($moduleSlug) : false;
                if (!$hasManage && !Gate::hasCapability($capability)) {
                    return new WP_Error(
                        'rest_forbidden',
                        sprintf(_x('Additional capability %s required.', 'REST API error message', 'bookando'), $capability),
                        ['status' => 403]
                    );
                }
            }

            if ($after !== null) {
                $extra = $after($request);
                if ($extra instanceof WP_Error) {
                    return $extra;
                }

                if ($extra === false) {
                    return new WP_Error(
                        'rest_forbidden',
                        _x('Additional permission guard failed.', 'REST API error message', 'bookando'),
                        ['status' => 403]
                    );
                }
            }

            return true;
        };
    }
    private static function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
        return is_string($slug) ? $slug : '';
    }

    private static function extractCapability(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        return str_contains($value, 'bookando_') ? $value : null;
    }

    private static function inferModuleFromCapability(string $capability): ?string
    {
        if (preg_match('/_bookando_([a-z0-9_-]+)$/', $capability, $matches) === 1) {
            return self::normalizeSlug($matches[1]);
        }

        return null;
    }
}

