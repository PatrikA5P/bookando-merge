<?php

declare(strict_types=1);

namespace Bookando\Core\Service;

/**
 * Umfassendes Debug-Logging-System für Bookando
 *
 * Verwendung:
 * - In wp-config.php: define('BOOKANDO_DEBUG', true);
 * - Logs in: wp-content/debug.log
 *
 * Features:
 * - Nonce-Flow-Tracking
 * - Asset-Loading-Tracking
 * - Request-Tracking
 * - Performance-Tracking
 */
final class DebugLogger
{
    private static bool $enabled = false;
    private static array $timers = [];
    private static array $buffer = [];
    private static string $sessionId = '';

    /**
     * Initialisiert den Debug-Logger
     */
    public static function init(): void
    {
        self::$enabled = defined('BOOKANDO_DEBUG') && BOOKANDO_DEBUG === true;

        if (!self::$enabled) {
            return;
        }

        self::$sessionId = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);

        // Log Session-Start
        self::log('🚀 DEBUG SESSION START', [
            'session_id' => self::$sessionId,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'time' => date('Y-m-d H:i:s'),
        ]);

        // Shutdown-Hook für Zusammenfassung
        register_shutdown_function([self::class, 'shutdown']);
    }

    /**
     * Hauptmethode für Logging
     */
    public static function log(string $message, array $context = [], string $level = 'INFO'): void
    {
        if (!self::$enabled) {
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[1] ?? $trace[0] ?? null;

        $entry = [
            'time' => microtime(true),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'file' => isset($caller['file']) ? basename($caller['file']) : 'unknown',
            'line' => $caller['line'] ?? 0,
            'function' => $caller['function'] ?? 'unknown',
        ];

        self::$buffer[] = $entry;

        // Sofort in error_log schreiben
        self::writeToLog($entry);
    }

    /**
     * Spezialisiertes Nonce-Logging
     */
    public static function logNonce(string $stage, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }

        $nonce = $_REQUEST['_wpnonce'] ?? null;

        $enrichedContext = array_merge([
            'stage' => $stage,
            'nonce_present' => $nonce !== null,
            'nonce_value' => $nonce ? substr($nonce, 0, 8) . '...' : 'N/A',
            'nonce_length' => $nonce ? strlen($nonce) : 0,
            'screen_id' => function_exists('get_current_screen') ? get_current_screen()->id ?? 'N/A' : 'N/A',
            'page' => $_GET['page'] ?? 'N/A',
        ], $context);

        self::log("🔐 NONCE: {$stage}", $enrichedContext, 'NONCE');
    }

    /**
     * Spezialisiertes Asset-Loading-Logging
     */
    public static function logAsset(string $stage, string $handle, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }

        $enrichedContext = array_merge([
            'stage' => $stage,
            'handle' => $handle,
            'screen_id' => function_exists('get_current_screen') ? get_current_screen()->id ?? 'N/A' : 'N/A',
        ], $context);

        self::log("📦 ASSET: {$stage}", $enrichedContext, 'ASSET');
    }

    /**
     * Performance-Timer starten
     */
    public static function startTimer(string $name): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$timers[$name] = microtime(true);
        self::log("⏱️ TIMER START: {$name}", [], 'TIMER');
    }

    /**
     * Performance-Timer stoppen
     */
    public static function stopTimer(string $name): float
    {
        if (!self::$enabled) {
            return 0.0;
        }

        if (!isset(self::$timers[$name])) {
            self::log("⚠️ TIMER NOT FOUND: {$name}", [], 'WARNING');
            return 0.0;
        }

        $duration = microtime(true) - self::$timers[$name];
        self::log("⏱️ TIMER STOP: {$name}", [
            'duration_ms' => round($duration * 1000, 2),
        ], 'TIMER');

        unset(self::$timers[$name]);
        return $duration;
    }

    /**
     * Request-Details loggen
     */
    public static function logRequest(): void
    {
        if (!self::$enabled) {
            return;
        }

        self::log('📥 REQUEST DETAILS', [
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'get_params' => array_keys($_GET),
            'post_params' => array_keys($_POST),
            'cookie_count' => count($_COOKIE),
        ]);
    }

    /**
     * WordPress-Screen-Info loggen
     */
    public static function logScreen(): void
    {
        if (!self::$enabled || !function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        self::log('🖥️ SCREEN INFO', [
            'id' => $screen->id,
            'base' => $screen->base,
            'post_type' => $screen->post_type ?? 'N/A',
            'taxonomy' => $screen->taxonomy ?? 'N/A',
        ]);
    }

    /**
     * Nonce-Verifikation testen
     */
    public static function testNonceVerification(string $nonce, array $actions): void
    {
        if (!self::$enabled) {
            return;
        }

        $results = [];
        foreach ($actions as $action) {
            $result = wp_verify_nonce($nonce, $action);
            $results[$action] = $result ? '✅ VALID' : '❌ INVALID';
        }

        self::log('🔍 NONCE VERIFICATION TEST', [
            'nonce' => substr($nonce, 0, 8) . '...',
            'results' => $results,
        ], 'TEST');
    }

    /**
     * Schreibt Entry in error_log
     */
    private static function writeToLog(array $entry): void
    {
        $levelEmoji = [
            'INFO' => 'ℹ️',
            'WARNING' => '⚠️',
            'ERROR' => '❌',
            'NONCE' => '🔐',
            'ASSET' => '📦',
            'TIMER' => '⏱️',
            'TEST' => '🔍',
        ];

        $emoji = $levelEmoji[$entry['level']] ?? '•';
        $time = date('H:i:s', (int) $entry['time']);
        $ms = sprintf('%03d', ($entry['time'] - floor($entry['time'])) * 1000);

        $location = sprintf('%s:%d', $entry['file'], $entry['line']);

        $contextStr = '';
        if (!empty($entry['context'])) {
            $contextStr = ' | ' . json_encode($entry['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $logLine = sprintf(
            "[BOOKANDO-%s] %s %s.%s | %s | %s%s",
            self::$sessionId,
            $emoji,
            $time,
            $ms,
            $location,
            $entry['message'],
            $contextStr
        );

        error_log($logLine);
    }

    /**
     * Shutdown-Handler für Zusammenfassung
     */
    public static function shutdown(): void
    {
        if (!self::$enabled || empty(self::$buffer)) {
            return;
        }

        $startTime = self::$buffer[0]['time'] ?? microtime(true);
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $stats = [
            'total_logs' => count(self::$buffer),
            'duration_ms' => round($duration * 1000, 2),
            'by_level' => [],
        ];

        foreach (self::$buffer as $entry) {
            $level = $entry['level'];
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
        }

        self::log('🏁 DEBUG SESSION END', $stats);
    }

    /**
     * Gibt alle Logs als Array zurück (für Admin-UI)
     */
    public static function getBuffer(): array
    {
        return self::$buffer;
    }

    /**
     * Leert den Buffer
     */
    public static function clearBuffer(): void
    {
        self::$buffer = [];
    }
}
