<?php

declare(strict_types=1);

namespace Bookando\Modules\Settings\Api;

use Bookando\Core\Api\Response;
use Bookando\Core\Base\BaseApi;
use Bookando\Modules\Settings\RestHandler;
use WP_REST_Request;
use function _x;

class Api extends BaseApi
{
    protected static function getNamespace(): string     { return 'bookando/v1'; }
    protected static function getModuleSlug(): string    { return 'settings'; }
    protected static function getBaseRoute(): string     { return '/settings'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }

    public static function registerRoutes(): void
    {
        static::registerRoute('', [
            'methods'  => ['GET'],
            'callback' => [static::class, 'overview'],
        ]);

        static::registerRoute('company', [
            'methods'  => ['GET', 'POST'],
            'callback' => [RestHandler::class, 'company'],
        ]);

        static::registerRoute('general', [
            'methods'  => ['GET', 'POST'],
            'callback' => [RestHandler::class, 'general'],
        ]);

        static::registerRoute('roles/(?P<role_slug>[a-z0-9_-]+)', [
            'methods'  => ['GET', 'POST'],
            'callback' => [RestHandler::class, 'roles'],
            'args'     => [
                'role_slug' => [
                    'description'       => _x('Role slug', 'REST parameter description', 'bookando'),
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static fn($value) => is_string($value) && trim((string) $value) !== '',
                ],
            ],
        ]);

        static::registerRoute('feature/(?P<feature_key>[a-z0-9_-]+)', [
            'methods'  => ['GET', 'POST'],
            'callback' => [RestHandler::class, 'feature'],
            'args'     => [
                'feature_key' => [
                    'description'       => _x('Feature key', 'REST parameter description', 'bookando'),
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static fn($value) => is_string($value) && trim((string) $value) !== '',
                ],
            ],
        ]);
    }

    /**
     * Beispiel: Gibt die wichtigsten Settings als Übersicht aus
     */
    public static function overview(WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $p = $wpdb->prefix . 'bookando_';
        // Hier z.B. kombinierte Abfrage über verschiedene Settings
        $general = $wpdb->get_row("SELECT value FROM {$p}settings WHERE settings_key = 'general'");
        $company = $wpdb->get_row("SELECT * FROM {$p}company_settings WHERE tenant_id IS NULL LIMIT 1");
        $result = [
            'general' => $general ? json_decode($general->value, true) : [],
            'company' => $company ? (array)$company : [],
        ];
        return Response::ok($result);
    }
}
