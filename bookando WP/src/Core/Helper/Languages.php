<?php

namespace Bookando\Core\Helper;

use Bookando\Core\Service\ActivityLogger;

final class Languages
{
    private const LABELS = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'it' => 'Italiano',
        'es' => 'Español',
    ];

    public static function listAvailable(?string $path = null, array $favorites = []): array
    {
        $found = [];
        $source = $path && is_dir($path) ? glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.json') : [];

        if ($source) {
            foreach ($source as $file) {
                $code = strtolower(pathinfo($file, PATHINFO_FILENAME));
                $found[$code] = [
                    'code'      => $code,
                    'label'     => self::LABELS[$code] ?? strtoupper($code),
                    'path'      => $file,
                    'favorite'  => in_array($code, $favorites, true),
                    'available' => true,
                ];
            }
        }

        foreach (self::LABELS as $code => $label) {
            if (!isset($found[$code])) {
                $found[$code] = [
                    'code'      => $code,
                    'label'     => $label,
                    'path'      => null,
                    'favorite'  => in_array($code, $favorites, true),
                    'available' => false,
                ];
            }
        }

        ksort($found);
        return array_values($found);
    }

    public static function label(string $code): string
    {
        $code = strtolower($code);
        if (!isset(self::LABELS[$code])) {
            ActivityLogger::warning('helper.languages', 'Unknown language code requested', ['code' => $code]);
        }
        return self::LABELS[$code] ?? strtoupper($code);
    }
}
