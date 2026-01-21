<?php

declare(strict_types=1);

namespace Bookando\Modules\Offers;

/**
 * Offer Type Constants
 *
 * Three distinct types of offers (Sales Layer for Academy):
 * - DIENSTLEISTUNGEN: Individual bookable services (driving lessons, haircuts, consultations)
 * - KURSE: Planned courses/events with fixed dates (basic courses, workshops, seminars)
 * - ONLINE: Self-paced online courses from Academy (no employee required)
 */
final class OfferType
{
    public const DIENSTLEISTUNGEN = 'dienstleistungen'; // Individual services
    public const KURSE = 'kurse';                       // Planned courses/events
    public const ONLINE = 'online';                     // Self-paced online courses

    /**
     * Get all valid offer types
     *
     * @return string[]
     */
    public static function getAll(): array
    {
        return [
            self::DIENSTLEISTUNGEN,
            self::KURSE,
            self::ONLINE,
        ];
    }

    /**
     * Check if offer type is valid
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::getAll(), true);
    }

    /**
     * Get localized label for offer type
     */
    public static function getLabel(string $type): string
    {
        return match ($type) {
            self::DIENSTLEISTUNGEN => __('Dienstleistungen', 'bookando'),
            self::KURSE => __('Kurse', 'bookando'),
            self::ONLINE => __('Online', 'bookando'),
            default => $type,
        };
    }

    /**
     * Get description for offer type
     */
    public static function getDescription(string $type): string
    {
        return match ($type) {
            self::DIENSTLEISTUNGEN => __('Individuelle Dienstleistungen mit wählbarer Zeit (z.B. Fahrstunden, Beratungen)', 'bookando'),
            self::KURSE => __('Geplante Kurse und Events mit festen Terminen (z.B. Grundkurse, Workshops)', 'bookando'),
            self::ONLINE => __('Online-Kurse ohne Mitarbeiter - Kunden absolvieren Academy-Inhalte selbstständig', 'bookando'),
            default => '',
        };
    }
}
