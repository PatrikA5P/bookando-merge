<?php

namespace Bookando\Core\Admin;

/**
 * Helfer für Admin-spezifische Aufgaben, nicht direkt Teil von BaseAdmin!
 */
class AdminUtils
{
    public static function currentScreenIs(string $screenId): bool
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        return $screen && $screen->id === $screenId;
    }

    public static function userCan(string $cap): bool
    {
        return current_user_can($cap);
    }

    public static function adminUrl(string $page, array $params = []): string
    {
        $params['page'] = $page;
        return add_query_arg($params, admin_url('admin.php'));
    }
}
