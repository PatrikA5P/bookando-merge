<?php

foreach ([
    'bookando_test_options'           => [],
    'bookando_test_transients'        => [],
    'bookando_test_uuid_counter'      => 0,
    'bookando_test_current_time'      => '2025-01-01 12:00:00',
    'bookando_test_user_caps'         => [],
    'bookando_test_current_screen'    => null,
    'bookando_test_enqueued_scripts'  => [],
    'bookando_test_enqueued_styles'   => [],
    'bookando_test_registered_scripts'=> [],
    'bookando_test_inline_scripts'    => [],
    'bookando_test_filters'           => [],
    'bookando_test_filter_callbacks'  => [],
    'bookando_test_actions'           => [],
    'bookando_test_roles'             => [],
    'bookando_test_http_post'         => ['callback' => null, 'last' => null],
    'bookando_test_environment_type'  => 'production',
] as $key => $value) {
    if (!isset($GLOBALS[$key])) {
        $GLOBALS[$key] = $value;
    }
}

if (!isset($GLOBALS['bookando_test_rest_state'])) {
    $GLOBALS['bookando_test_rest_state'] = [
        'is_logged_in'    => false,
        'current_user_id' => 0,
        'capabilities'    => [],
        'nonces'          => [],
    ];
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!isset($GLOBALS['bookando_test_scripts'])) {
    $GLOBALS['bookando_test_scripts'] = [];
}

if (!isset($GLOBALS['bookando_test_styles'])) {
    $GLOBALS['bookando_test_styles'] = [];
}

if (!isset($GLOBALS['bookando_test_script_modules'])) {
    $GLOBALS['bookando_test_script_modules'] = [];
}

if (!function_exists('bookando_test_reset_stubs')) {
    function bookando_test_reset_stubs(): void
    {
        $GLOBALS['bookando_test_options']       = [];
        $GLOBALS['bookando_test_transients']    = [];
        $GLOBALS['bookando_test_uuid_counter']  = 0;
        $GLOBALS['bookando_test_current_time']  = '2025-01-01 12:00:00';
        $GLOBALS['bookando_test_rest_state'] = [
            'is_logged_in'    => false,
            'current_user_id' => 0,
            'capabilities'    => [],
            'nonces'          => [],
        ];
        $GLOBALS['bookando_test_filter_callbacks'] = [];
        $GLOBALS['bookando_test_actions'] = [];
        $GLOBALS['bookando_test_roles'] = [];
        $GLOBALS['bookando_test_http_post'] = ['callback' => null, 'last' => null];
        $GLOBALS['bookando_test_user_caps'] = [];
        $GLOBALS['bookando_test_environment_type'] = 'production';
        unset($_SERVER['HTTP_X_WP_NONCE']);
    }
}

if (!function_exists('wp_get_environment_type')) {
    function wp_get_environment_type(): string
    {
        return $GLOBALS['bookando_test_environment_type'] ?? 'production';
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        private string $method;

        private string $route;

        /** @var array<string, mixed> */
        private array $params = [];

        /** @var array<string, string> */
        private array $headers = [];

        public function __construct(string $method = 'GET', string $route = '')
        {
            $this->method = strtoupper($method);
            $this->route  = $route;
        }

        public function get_method(): string
        {
            return $this->method;
        }

        public function get_route(): string
        {
            return $this->route;
        }

        public function set_param(string $key, $value): void
        {
            $this->params[$key] = $value;
        }

        public function get_param(string $key)
        {
            return $this->params[$key] ?? null;
        }

        /**
         * @return array<string, mixed>
         */
        public function get_params(): array
        {
            return $this->params;
        }

        public function set_header(string $name, string $value): void
        {
            $this->headers[strtolower($name)] = $value;
        }

        public function get_header(string $name): ?string
        {
            $key = strtolower($name);
            return $this->headers[$key] ?? null;
        }
    }
}

if (!function_exists('bookando_test_set_rest_permissions')) {
    function bookando_test_set_rest_permissions(array $overrides): void
    {
        $GLOBALS['bookando_test_rest_state'] = array_merge(
            $GLOBALS['bookando_test_rest_state'],
            $overrides
        );
    }
}

if (!function_exists('get_option')) {
    function get_option(string $name, $default = false)
    {
        return $GLOBALS['bookando_test_options'][$name] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $name, $value, $autoload = null): bool
    {
        $GLOBALS['bookando_test_options'][$name] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $name): bool
    {
        unset($GLOBALS['bookando_test_options'][$name]);
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool
    {
        return in_array($capability, $GLOBALS['bookando_test_user_caps'] ?? [], true);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = [])
    {
        return array_merge((array) $defaults, (array) $args);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value): string
    {
        return trim((string) $value);
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($value): string
    {
        $value = (string) $value;
        $value = preg_replace('/[^A-Za-z0-9._-]/', '', $value) ?? '';
        $value = preg_replace('/\.+/', '.', $value) ?? '';

        return trim($value, '.');
if (!function_exists('absint')) {
    function absint($maybeint): int
    {
        $int = (int) (is_scalar($maybeint) ? $maybeint : 0);

        return $int < 0 ? -$int : $int;
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value): string
    {
        return trim((string) $value);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($value): string
    {
        $value = strtolower((string) $value);
        return preg_replace('/[^a-z0-9_\-]/', '', $value) ?? '';
    }
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return (string) $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return (string) $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return (string) $text;
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type)
    {
        $current = $GLOBALS['bookando_test_current_time'] ?? gmdate('Y-m-d H:i:s');

        return $type === 'Y-m-d'
            ? substr($current, 0, 10)
            : $current;
    }
}

if (!function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4(): string
    {
        $GLOBALS['bookando_test_uuid_counter'] = ($GLOBALS['bookando_test_uuid_counter'] ?? 0) + 1;

        return sprintf('uuid-%d', $GLOBALS['bookando_test_uuid_counter']);
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return (bool) ($GLOBALS['bookando_test_rest_state']['is_logged_in'] ?? false);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability): bool
    {
        $caps = $GLOBALS['bookando_test_rest_state']['capabilities'] ?? [];
        return in_array($capability, $caps, true);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        return (int) ($GLOBALS['bookando_test_rest_state']['current_user_id'] ?? 0);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action): bool
    {
        $nonces = $GLOBALS['bookando_test_rest_state']['nonces'] ?? [];
        if (!array_key_exists($action, $nonces)) {
            return false;
        }

        return hash_equals((string) $nonces[$action], (string) $nonce);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, int $options = 0, int $depth = 512)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | $options, $depth);
    }
}

if (!function_exists('bookando_test_mock_http_post')) {
    function bookando_test_mock_http_post(?callable $callback): void
    {
        $GLOBALS['bookando_test_http_post']['callback'] = $callback;
    }
}

if (!function_exists('bookando_test_get_last_http_post')) {
    function bookando_test_get_last_http_post(): ?array
    {
        return $GLOBALS['bookando_test_http_post']['last'] ?? null;
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post(string $url, array $args = [])
    {
        $GLOBALS['bookando_test_http_post']['last'] = ['url' => $url, 'args' => $args];

        $callback = $GLOBALS['bookando_test_http_post']['callback'] ?? null;
        if (is_callable($callback)) {
            return $callback($url, $args);
        }

        return [
            'body'     => '',
            'response' => ['code' => 200],
        ];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response): string
    {
        if (is_array($response) && isset($response['body'])) {
            return (string) $response['body'];
        }

        return '';
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $name, $value, int $expiration = 0): bool
    {
        $GLOBALS['bookando_test_transients'][$name] = [
            'value'      => $value,
            'expires_at' => $expiration > 0 ? time() + $expiration : null,
        ];

        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $name)
    {
        if (!isset($GLOBALS['bookando_test_transients'][$name])) {
            return false;
        }

        $entry = $GLOBALS['bookando_test_transients'][$name];
        if (isset($entry['expires_at']) && $entry['expires_at'] !== null && $entry['expires_at'] < time()) {
            unset($GLOBALS['bookando_test_transients'][$name]);
            return false;
        }

        return $entry['value'];
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $name): bool
    {
        unset($GLOBALS['bookando_test_transients'][$name]);
        return true;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('get_date_from_gmt')) {
    function get_date_from_gmt(string $string, string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = strtotime($string);
        return $timestamp !== false ? gmdate($format, $timestamp) : $string;
    }
}

if (!function_exists('wp_remote_head')) {
    function wp_remote_head(string $url, array $args = []): array
    {
        return ['response' => ['code' => 200]];
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get(string $url, array $args = []): array
    {
        return ['response' => ['code' => 200]];
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action = '-1'): string
    {
        return 'nonce-' . $action;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string $action = '-1'): bool
    {
        return $nonce === 'nonce-' . $action;
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response): int
    {
        if (is_array($response) && isset($response['response']['code'])) {
            return (int) $response['response']['code'];
        }

        return 0;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user(): object
    {
        return (object) ['ID' => 0];
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta(int $userId, string $key, bool $single = false)
    {
        return 0;
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        $path = ltrim($path, '/');
        return 'https://example.test/wp-content/plugins/bookando/' . $path;
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string
    {
        return dirname($file) . '/';
    }
}

if (!function_exists('wp_enqueue_style')) {
    /**
     * @param list<string> $deps
     * @param string|false $ver
     */
    function wp_enqueue_style(string $handle, string $src, array $deps = [], $ver = false, string $media = 'all'): void
    {
        $GLOBALS['bookando_test_enqueued_styles'][] = $handle;
    }
}

if (!function_exists('wp_register_script')) {
    /**
     * @param list<string> $deps
     * @param string|false $ver
     */
    function wp_register_script(string $handle, string $src, array $deps = [], $ver = false, bool $in_footer = false): void
    {
        $GLOBALS['bookando_test_registered_scripts'][$handle] = [
            'src'      => $src,
            'deps'     => $deps,
            'ver'      => $ver,
            'in_footer'=> $in_footer,
        ];
    }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script(string $handle, string $data, string $position = 'after'): void
    {
        $GLOBALS['bookando_test_inline_scripts'][$handle][] = [
            'data'     => $data,
            'position' => $position,
        ];
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle): void
    {
        $GLOBALS['bookando_test_enqueued_scripts'][] = $handle;
    }
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
        $path = ltrim($path, '/');
        return 'https://example.test/wp-admin/' . $path;
    }
}

if (!function_exists('rest_url')) {
    function rest_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return 'https://example.test/wp-json/' . $path;
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $string): string
    {
        return rtrim($string, "\\/") . '/';
    }
}

if (!function_exists('wp_add_dashboard_widget')) {
    function wp_add_dashboard_widget(...$args): void {}
}

if (!function_exists('add_filter')) {
    function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        $GLOBALS['bookando_test_filters'][] = [$hook_name, $priority];
        $GLOBALS['bookando_test_filter_callbacks'][$hook_name][$priority][] = [$callback, $accepted_args];
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook_name, $value, ...$args)
    {
        if (!isset($GLOBALS['bookando_test_filter_callbacks'][$hook_name])) {
            return $value;
        }

        ksort($GLOBALS['bookando_test_filter_callbacks'][$hook_name]);

        foreach ($GLOBALS['bookando_test_filter_callbacks'][$hook_name] as $callbacks) {
            foreach ($callbacks as [$callback, $accepted]) {
                $invokeArgs = array_slice([$value, ...$args], 0, $accepted);
                $value = $callback(...$invokeArgs);
            }
        }

        return $value;
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        $GLOBALS['bookando_test_actions'][] = [$hook_name, $priority];
        add_filter($hook_name, $callback, $priority, $accepted_args);
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return true;
    }
}

if (!class_exists('Bookando_Test_Role')) {
    class Bookando_Test_Role
    {
        /** @var array<string, bool> */
        private array $caps = [];

        public function has_cap(string $capability): bool
        {
            return isset($this->caps[$capability]);
        }

        public function add_cap(string $capability): void
        {
            $this->caps[$capability] = true;
        }
    }
}

if (!function_exists('get_role')) {
    function get_role(string $role)
    {
        if (!isset($GLOBALS['bookando_test_roles'][$role])) {
            $GLOBALS['bookando_test_roles'][$role] = new Bookando_Test_Role();
        }

        return $GLOBALS['bookando_test_roles'][$role];
    }
}

if (!function_exists('get_current_screen')) {
    function get_current_screen()
    {
        return $GLOBALS['bookando_test_current_screen'];
    }
}
