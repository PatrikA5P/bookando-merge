<?php

if (!defined('BOOKANDO_PLUGIN_DIR')) {
    define('BOOKANDO_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!defined('BOOKANDO_PLUGIN_FILE')) {
    define('BOOKANDO_PLUGIN_FILE', BOOKANDO_PLUGIN_DIR . 'bookando.php');
}

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Bookando\\Core\\'    => __DIR__ . '/../src/Core/',
        'Bookando\\Helper\\'  => __DIR__ . '/../src/Helper/',
        'Bookando\\Modules\\' => __DIR__ . '/../src/modules/',
        'Bookando\\CLI\\'     => __DIR__ . '/../src/CLI/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $path     = $baseDir . str_replace('\\', '/', $relative) . '.php';

            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
});

if (!class_exists('wpdb')) {
    class wpdb
    {
        public string $prefix = 'wp_';

        public string $last_error = '';

        public function prepare(string $query, ...$args): array
        {
            return ['query' => $query, 'args' => $args];
        }

        public function get_var($query)
        {
            return null;
        }

        public function get_col($query): array
        {
            return [];
        }

        public function insert($table, $data, $format = null)
        {
            return true;
        }

        public function update($table, $data, $where, $format = null, $whereFormat = null)
        {
            return true;
        }

        public function query($query)
        {
            return 1;
        }
    }
}

if (!class_exists('Bookando_Test_SpyWpdb')) {
    class Bookando_Test_SpyWpdb extends wpdb
    {
        public array $lookups = [];

        public array $inserted = [];

        public array $updated = [];

        public array $queries = [];

        public ?bool $renameResult = true;

        public function registerLookup(string $key, mixed $value): void
        {
            $this->lookups[$key] = $value;
        }

        public function prepare(string $query, ...$args): array
        {
            return ['query' => $query, 'args' => $args];
        }

        private function resolveKey($query): string
        {
            if (is_array($query) && isset($query['args'][0])) {
                return (string) $query['args'][0];
            }

            return (string) $query;
        }

        public function get_var($query)
        {
            $key = $this->resolveKey($query);
            $this->queries[] = $key;
            return $this->lookups[$key] ?? null;
        }

        public function insert($table, $data, $format = null)
        {
            $this->inserted[] = ['table' => $table, 'data' => $data, 'format' => $format];
            return true;
        }

        public function update($table, $data, $where, $format = null, $whereFormat = null)
        {
            $this->updated[] = [
                'table'        => $table,
                'data'         => $data,
                'where'        => $where,
                'format'       => $format,
                'where_format' => $whereFormat,
            ];

            return true;
        }

        public function query($query)
        {
            $trimmed = trim((string) $query);
            $this->queries[] = $trimmed;

            if (str_starts_with($trimmed, 'RENAME TABLE')) {
                return $this->renameResult;
            }

            return 1;
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $code;

        private string $message;

        private mixed $data;

        public function __construct(string $code = '', string $message = '', mixed $data = null)
        {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_data(): mixed
        {
            return $this->data;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        private mixed $data;

        private int $status;

        public function __construct(mixed $data = null, int $status = 200)
        {
            $this->data   = $data;
            $this->status = $status;
        }

        public function set_status(int $status): void
        {
            $this->status = $status;
        }

        public function get_status(): int
        {
            return $this->status;
        }

        public function get_data(): mixed
        {
            return $this->data;
        }
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response(mixed $data): WP_REST_Response
    {
        return $data instanceof WP_REST_Response ? $data : new WP_REST_Response($data);
    }
}

require_once __DIR__ . '/stubs/wp-functions.php';
require_once __DIR__ . '/stubs/wp-cli.php';
