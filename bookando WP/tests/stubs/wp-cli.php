<?php

use RuntimeException;

if (!class_exists('Bookando_Test_WpCliException')) {
    class Bookando_Test_WpCliException extends RuntimeException
    {
    }
}

if (!class_exists('WP_CLI_Command')) {
    class WP_CLI_Command
    {
    }
}

if (!class_exists('WP_CLI')) {
    class WP_CLI
    {
        /** @var array<string, mixed> */
        public static array $commands = [];

        /** @var list<array{type:string,message:string}> */
        public static array $messages = [];

        public static function reset(): void
        {
            self::$commands = [];
            self::$messages = [];
        }

        public static function add_command(string $name, $callable): void
        {
            self::$commands[$name] = $callable;
        }

        public static function success(string $message): void
        {
            self::$messages[] = ['type' => 'success', 'message' => $message];
        }

        public static function warning(string $message): void
        {
            self::$messages[] = ['type' => 'warning', 'message' => $message];
        }

        public static function error(string $message): void
        {
            throw new Bookando_Test_WpCliException($message);
        }
    }
}
