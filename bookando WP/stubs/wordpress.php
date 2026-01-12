<?php
declare(strict_types=1);

if (!defined('BOOKANDO_PLUGIN_FILE')) {
    define('BOOKANDO_PLUGIN_FILE', __FILE__);
}

if (!function_exists('add_action')) {
    function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void {}
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void {}
}

if (!function_exists('apply_filters')) {
    /**
     * @template T
     * @param T $value
     * @return T
     */
    function apply_filters(string $hook_name, $value)
    {
        return $value;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return false;
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        return '/plugins/' . ltrim($path, '/');
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        $directory = dirname($file);

        if ($directory === '.' || $directory === '') {
            return __DIR__ . '/';
        }

        return rtrim($directory, '/\\') . '/';
    }
}

if (!function_exists('wp_enqueue_style')) {
    /**
     * @param list<string> $deps
     * @param string|false $ver
     */
    function wp_enqueue_style(string $handle, string $src, array $deps = [], $ver = false, string $media = 'all'): void {}
}

if (!function_exists('wp_register_script')) {
    /**
     * @param list<string> $deps
     * @param string|false $ver
     */
    function wp_register_script(string $handle, string $src, array $deps = [], $ver = false, bool $in_footer = false): void {}
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script(string $handle, string $data, string $position = 'after'): void {}
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle): void {}
}

if (!function_exists('remove_query_arg')) {
    function remove_query_arg(string $key, string $query): string
    {
        return $query;
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return '/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('rest_url')) {
    function rest_url(string $path = ''): string
    {
        return '/wp-json/' . ltrim($path, '/');
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $string): string
    {
        return rtrim($string, '/') . '/';
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, int $options = 0, int $depth = 512): string
    {
        return json_encode($data, $options, $depth) ?: '';
    }
}

if (!class_exists('WP_Role')) {
    class WP_Role
    {
        public function has_cap(string $cap): bool
        {
            return false;
        }

        public function add_cap(string $cap): void {}
    }
}

if (!function_exists('get_role')) {
    function get_role(string $role): ?WP_Role
    {
        return new WP_Role();
    }
}
