<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use function trim;
use function preg_match;
use function strtolower;
use function in_array;

/**
 * Datentransformations-Utilities für Employee-Modul.
 *
 * Bietet Hilfsmethoden für:
 * - Zeit- und Datumsformatierung
 * - Enum-Normalisierung (repeat, calendar)
 * - Status-Prüfungen (hard deleted)
 */
class EmployeeDataTransformer
{
    /**
     * Konvertiert Zeit-String in Datenbank-Format (HH:MM:SS).
     *
     * Akzeptiert:
     * - HH:MM:SS (passthrough)
     * - HH:MM (wird zu HH:MM:00)
     * - Leerer String oder ungültig (wird zu '')
     *
     * @param string $value Zeit-String
     * @return string Formatierte Zeit oder leer
     */
    public static function toDbTime(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        return '';
    }

    /**
     * Konvertiert Datums-String in Datenbank-Format (YYYY-MM-DD).
     *
     * Akzeptiert:
     * - YYYY-MM-DD (passthrough)
     * - DD.MM.YYYY (wird zu YYYY-MM-DD)
     * - Leerer String oder ungültig (wird zu '')
     *
     * @param string $value Datums-String
     * @return string Formatiertes Datum oder leer
     */
    public static function toDbDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        return '';
    }

    /**
     * Normalisiert Wiederholungs-Pattern für Days Off.
     *
     * Erlaubte Werte: none, daily, weekly, monthly, yearly
     * Ungültige Werte werden zu 'none'.
     *
     * @param string $repeat Wiederholungs-Pattern
     * @return string Normalisierter Wert
     */
    public static function sanitizeRepeat(string $repeat): string
    {
        $repeat = strtolower(trim($repeat));
        $allowed = ['none', 'daily', 'weekly', 'monthly', 'yearly'];

        return in_array($repeat, $allowed, true) ? $repeat : 'none';
    }

    /**
     * Normalisiert Calendar-Provider.
     *
     * Erlaubte Werte: google, microsoft, exchange, icloud, ics
     * Ungültige Werte werden zu leerem String.
     *
     * @param string $calendar Calendar-Provider
     * @return string Normalisierter Wert oder leer
     */
    public static function sanitizeCalendar(string $calendar): string
    {
        $calendar = strtolower(trim($calendar));
        $allowed = ['google', 'microsoft', 'exchange', 'icloud', 'ics'];

        return in_array($calendar, $allowed, true) ? $calendar : '';
    }

    /**
     * Prüft, ob ein Employee-Record hard deleted wurde.
     *
     * Hard deleted bedeutet:
     * - status === 'deleted'
     * - deleted_at ist gesetzt
     * - PII wurde anonymisiert
     *
     * @param array $row Employee-Datenbank-Row
     * @return bool True wenn hard deleted
     */
    public static function isHardDeleted(array $row): bool
    {
        return isset($row['status'], $row['deleted_at'])
            && $row['status'] === 'deleted'
            && !empty($row['deleted_at']);
    }

    /**
     * Konvertiert legacy flat working_hours Array in workday_sets Struktur.
     *
     * @deprecated Legacy-Migration, sollte nicht mehr verwendet werden
     * @param array $flat Altes Format
     * @return array Neues Format mit workday_sets
     */
    public static function convertFlatWorkingHoursToSets(array $flat): array
    {
        // Legacy-Konvertierung für alte APIs
        // TODO: Entfernen wenn alle Clients auf neues Format migriert sind
        $sets = [];
        foreach ($flat as $item) {
            if (!isset($item['day'])) {
                continue;
            }
            $dayId = (int) $item['day'];
            $sets[] = [
                'week_day_id' => $dayId,
                'label' => '',
                'intervals' => [
                    [
                        'time_from' => $item['start'] ?? '09:00:00',
                        'time_to' => $item['end'] ?? '17:00:00',
                    ],
                ],
                'services' => [],
                'locations' => [],
            ];
        }

        return $sets;
    }
}
