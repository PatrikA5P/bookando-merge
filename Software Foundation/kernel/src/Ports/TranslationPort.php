<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Ports;

interface TranslationPort
{
    /**
     * Translate a key with optional interpolation parameters.
     *
     * @param string      $key    Translation key (e.g. 'booking.confirmed.title')
     * @param array       $params Interpolation parameters
     * @param string|null $locale BCP-47 locale tag, null for current locale
     */
    public function translate(string $key, array $params = [], ?string $locale = null): string;

    /**
     * Check if a translation exists for the given key.
     */
    public function hasTranslation(string $key, ?string $locale = null): bool;

    /**
     * Set the current locale.
     *
     * @param string $locale BCP-47 locale tag (e.g. 'de-CH')
     */
    public function setLocale(string $locale): void;

    /**
     * Get the current locale.
     *
     * @return string BCP-47 locale tag
     */
    public function getLocale(): string;

    /**
     * Get all available locales.
     *
     * @return string[] List of BCP-47 locale tags
     */
    public function availableLocales(): array;
}
