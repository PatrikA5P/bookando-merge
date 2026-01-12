<?php

namespace Bookando\Core\Helper;

use Bookando\Core\Service\ActivityLogger;

final class Locales
{
    private const LOCALES = [
        'de_CH' => ['label' => 'Deutsch (Schweiz)', 'language' => 'de'],
        'de_DE' => ['label' => 'Deutsch (Deutschland)', 'language' => 'de'],
        'en_GB' => ['label' => 'English (UK)', 'language' => 'en'],
        'en_US' => ['label' => 'English (US)', 'language' => 'en'],
        'fr_CH' => ['label' => 'Français (Suisse)', 'language' => 'fr'],
        'fr_FR' => ['label' => 'Français (France)', 'language' => 'fr'],
        'it_IT' => ['label' => 'Italiano (Italia)', 'language' => 'it'],
        'es_ES' => ['label' => 'Español (España)', 'language' => 'es'],
    ];

    public static function listAvailable(?string $path = null, array $favorites = []): array
    {
        $locales = [];
        $files = $path && is_dir($path) ? glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.*') : [];

        if ($files) {
            foreach ($files as $file) {
                $code = str_replace(['-', '.min'], ['_', ''], pathinfo($file, PATHINFO_FILENAME));
                $code = strtoupper($code);
                $meta = self::LOCALES[$code] ?? ['label' => $code, 'language' => substr(strtolower($code), 0, 2)];
                $locales[$code] = [
                    'locale'    => $code,
                    'label'     => $meta['label'],
                    'language'  => $meta['language'],
                    'path'      => $file,
                    'favorite'  => in_array($code, $favorites, true),
                    'available' => true,
                ];
            }
        }

        foreach (self::LOCALES as $code => $meta) {
            if (!isset($locales[$code])) {
                $locales[$code] = [
                    'locale'    => $code,
                    'label'     => $meta['label'],
                    'language'  => $meta['language'],
                    'path'      => null,
                    'favorite'  => in_array($code, $favorites, true),
                    'available' => false,
                ];
            }
        }

        ksort($locales);
        return array_values($locales);
    }

    public static function label(string $locale): string
    {
        $locale = strtoupper($locale);
        if (!isset(self::LOCALES[$locale])) {
            ActivityLogger::warning('helper.locales', 'Unknown locale requested', ['locale' => $locale]);
        }
        return self::LOCALES[$locale]['label'] ?? $locale;
    }
}
