<?php

declare(strict_types=1);

namespace SoftwareFoundation\Kernel\Domain\I18n;

use InvalidArgumentException;

final class Locale
{
    public function __construct(
        public readonly string $language,
        public readonly ?string $region = null,
    ) {
        if (!preg_match('/^[a-z]{2}$/', $this->language)) {
            throw new InvalidArgumentException(
                sprintf('Language must be 2 lowercase letters (ISO 639-1), got "%s".', $this->language)
            );
        }

        if ($this->region !== null && !preg_match('/^[A-Z]{2}$/', $this->region)) {
            throw new InvalidArgumentException(
                sprintf('Region must be 2 uppercase letters (ISO 3166-1) or null, got "%s".', $this->region)
            );
        }
    }

    public static function of(string $tag): self
    {
        $parts = explode('-', $tag, 2);

        $language = strtolower($parts[0]);
        $region = isset($parts[1]) ? strtoupper($parts[1]) : null;

        return new self($language, $region);
    }

    public static function german(): self
    {
        return new self('de');
    }

    public static function germanCH(): self
    {
        return new self('de', 'CH');
    }

    public static function english(): self
    {
        return new self('en');
    }

    public static function french(): self
    {
        return new self('fr');
    }

    public static function italian(): self
    {
        return new self('it');
    }

    public function tag(): string
    {
        if ($this->region !== null) {
            return $this->language . '-' . $this->region;
        }

        return $this->language;
    }

    public function equals(self $other): bool
    {
        return $this->language === $other->language
            && $this->region === $other->region;
    }

    public function __toString(): string
    {
        return $this->tag();
    }
}
