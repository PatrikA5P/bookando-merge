<?php

namespace Bookando\Core\Dispatcher;

use function __;
use function _x;

class WebhookDispatcher
{
    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'init']);
    }

    public static function init()
    {
        register_rest_route('bookando/v1', '/webhook/(?P<type>[a-zA-Z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true', // Auth/Token prüfen wir manuell!
        ]);
    }

    public static function handle($request)
    {
        $type = sanitize_key($request['type']);
        $headers = $request->get_headers();

        // Custom: Token/Signatur prüfen
        $token = $headers['x-bookando-token'][0] ?? '';
        if ($token !== getenv('BOOKANDO_WEBHOOK_TOKEN')) {
            return new \WP_Error(
                'forbidden',
                _x('Invalid token', 'REST API error message', 'bookando'),
                ['status' => 403]
            );
        }

        $handlerClass = "Bookando\\Core\\Webhook\\{$type}Webhook";
        if (class_exists($handlerClass)) {
            return call_user_func([$handlerClass, 'handle'], $request);
        }
        return new \WP_Error(
            'not_found',
            sprintf(__('Handler für %s nicht gefunden', 'bookando'), $type),
            ['status' => 404]
        );
    }
}
