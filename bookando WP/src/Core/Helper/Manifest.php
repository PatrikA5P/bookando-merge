<?php

declare(strict_types=1);

namespace Bookando\Core\Helper;

class Manifest
{
    /**
     * Static Cache pro Request - verhindert mehrfaches Einlesen innerhalb desselben Requests.
     *
     * @var array<string, array<string, mixed>|null>
     */
    private static array $cache = [];

    /**
     * @return list<string>
     */
    private static function manifestPaths(?string $distDir = null): array
    {
        if ($distDir === null) {
            if (!defined('BOOKANDO_PLUGIN_FILE')) {
                return [];
            }

            if (!function_exists('plugin_dir_path')) {
                return [];
            }

            $distDir = plugin_dir_path(BOOKANDO_PLUGIN_FILE) . 'dist/';
        }

        $distDir = rtrim(str_replace('\\', '/', $distDir), '/') . '/';

        return [
            $distDir . '.vite/manifest.json',
            $distDir . 'manifest.json',
        ];
    }

    public static function loadRaw(?string $distDir = null): ?string
    {
        foreach (self::manifestPaths($distDir) as $path) {
            if (!is_readable($path)) {
                continue;
            }

            $contents = file_get_contents($path);
            if ($contents !== false) {
                return $contents;
            }
        }

        return null;
    }

    /**
     * Lädt das Vite-Manifest mit Multi-Level-Caching:
     * 1. Static Cache (pro Request)
     * 2. Transient Cache (1 Stunde, persistent)
     * 3. Dateisystem (wenn Cache leer)
     *
     * @return array<string, mixed>|null
     */
    public static function load(?string $distDir = null): ?array
    {
        $cacheKey = self::getCacheKey($distDir);

        // 1. Static Cache (schnellster)
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        // 2. Transient Cache (persistent, 1 Stunde)
        if (function_exists('get_transient')) {
            $cached = get_transient($cacheKey);
            if (is_array($cached)) {
                self::$cache[$cacheKey] = $cached;
                return $cached;
            }
        }

        // 3. Dateisystem (langsam)
        $raw = self::loadRaw($distDir);
        if ($raw === null) {
            self::$cache[$cacheKey] = null;
            return null;
        }

        $data = json_decode($raw, true);
        $result = is_array($data) ? $data : null;

        // Speichere in beiden Caches
        self::$cache[$cacheKey] = $result;

        if ($result !== null && function_exists('set_transient')) {
            // Cache für 1 Stunde (in Production länger möglich)
            $ttl = defined('BOOKANDO_DEV') && BOOKANDO_DEV ? 300 : HOUR_IN_SECONDS;
            set_transient($cacheKey, $result, $ttl);
        }

        return $result;
    }

    /**
     * Erstellt einen eindeutigen Cache-Key basierend auf dem distDir.
     *
     * @param string|null $distDir
     * @return string
     */
    private static function getCacheKey(?string $distDir): string
    {
        $dir = $distDir ?? 'default';
        return 'bookando_manifest_' . md5($dir);
    }

    /**
     * Löscht den Manifest-Cache (nützlich nach Vite-Build).
     *
     * @param string|null $distDir
     * @return void
     */
    public static function clearCache(?string $distDir = null): void
    {
        $cacheKey = self::getCacheKey($distDir);

        // Static Cache löschen
        unset(self::$cache[$cacheKey]);

        // Transient Cache löschen
        if (function_exists('delete_transient')) {
            delete_transient($cacheKey);
        }
    }

    /**
     * Löscht ALLE Manifest-Caches.
     *
     * @return void
     */
    public static function clearAllCaches(): void
    {
        self::$cache = [];

        if (function_exists('delete_transient')) {
            delete_transient('bookando_manifest_default');
            delete_transient(self::getCacheKey(null));
        }
    }
}
