<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Tests\TestAdapters;

use SoftwareFoundation\Kernel\Ports\TranslationPort;

/**
 * In-memory translation store for testing. Supports parameter interpolation.
 */
final class InMemoryTranslation implements TranslationPort
{
    /** @var array<string, array<string, string>> locale => [key => translated string] */
    private array $translations = [];

    private string $currentLocale = 'en';

    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->currentLocale;

        $value = $this->translations[$locale][$key] ?? $key;

        foreach ($params as $param => $replacement) {
            $value = str_replace('{' . $param . '}', (string) $replacement, $value);
        }

        return $value;
    }

    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        $locale ??= $this->currentLocale;

        return isset($this->translations[$locale][$key]);
    }

    public function setLocale(string $locale): void
    {
        $this->currentLocale = $locale;
    }

    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    /**
     * @return string[]
     */
    public function availableLocales(): array
    {
        return array_keys($this->translations);
    }

    // --- Test helpers ---

    /** Add a single translation entry. */
    public function addTranslation(string $locale, string $key, string $value): void
    {
        $this->translations[$locale][$key] = $value;
    }
}
