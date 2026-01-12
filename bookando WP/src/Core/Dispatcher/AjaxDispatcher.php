<?php

namespace Bookando\Core\Dispatcher;

class AjaxDispatcher
{
    public static function register(): void
    {
        add_action('wp_ajax_bookando', [self::class, 'handle']);
        add_action('wp_ajax_nopriv_bookando', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        // Nonce check
        check_ajax_referer('bookando_ajax', 'nonce');
        $module = sanitize_key($_POST['module'] ?? '');
        $action = sanitize_key($_POST['action'] ?? '');

        if (empty($module) || empty($action)) {
            wp_send_json_error(['error' => 'Missing module or action.']);
        }

        $handlerClass = "Bookando\\Modules\\$module\\AjaxHandler";
        if (class_exists($handlerClass) && method_exists($handlerClass, $action)) {
            if (!current_user_can('manage_bookando_' . $module)) {
                wp_send_json_error(['error' => 'Unauthorized.']);
            }
            $result = call_user_func([$handlerClass, $action], $_POST);
            wp_send_json_success($result);
        } else {
            wp_send_json_error(['error' => "Handler $handlerClass::$action not found"]);
        }
    }
}
