<?php

declare(strict_types=1);

namespace Bookando\Core\Helper;

final class HelperPathResolver
{
    /**
     * @return list<string>
     */
    public static function candidates(): array
    {
        $candidates = [
            dirname(__DIR__) . '/Helpers.php',
        ];

        if (defined('BOOKANDO_PLUGIN_DIR')) {
            $candidates[] = rtrim(BOOKANDO_PLUGIN_DIR, '/\\') . '/src/Core/Helpers.php';
        }

        if (defined('BOOKANDO_PLUGIN_FILE')) {
            $candidates[] = rtrim(dirname(BOOKANDO_PLUGIN_FILE), '/\\') . '/src/Core/Helpers.php';
        }

        $unique = [];
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            $normalized = rtrim($candidate, '/\\');
            if (!in_array($normalized, $unique, true)) {
                $unique[] = $normalized;
            }
        }

        return $unique;
    }
}
