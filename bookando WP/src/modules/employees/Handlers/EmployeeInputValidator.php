<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use Bookando\Core\Util\Sanitizer;
use function sanitize_email;
use function trim;
use function strtolower;
use function strtoupper;
use function in_array;
use function preg_match;
use function is_array;
use function array_key_exists;
use function wp_hash_password;

/**
 * Input-Validierung und -Sanitierung für Employee-Daten.
 *
 * Implementiert "Null-First" Philosophie:
 * - Leere Strings werden zu NULL
 * - Ungültige Werte werden zu NULL
 * - Strikte Validierung für strukturierte Felder (country, gender, status)
 * - Tolerantes Mapping für User-Inputs
 */
class EmployeeInputValidator
{
    /**
     * Sanitiert und normalisiert Employee-Input-Daten.
     *
     * Null-First Sanitizing + tolerantes Mapping (gender, country).
     * Beim CREATE werden alle Felder validiert.
     * Beim UPDATE werden nur übergebene Felder validiert.
     *
     * @param array $input Rohe Input-Daten
     * @param bool $isCreate True beim Erstellen, False beim Update
     * @return array Sanitierte Daten
     */
    public static function sanitizeEmployeeInput(array $input, bool $isCreate): array
    {
        $output = [];

        // Basis-Strings ('' → NULL)
        $copyNullIfEmpty = function (string $key) use (&$output, $input, $isCreate) {
            if ($isCreate || array_key_exists($key, $input)) {
                $output[$key] = Sanitizer::nullIfEmpty($input[$key] ?? null);
            }
        };

        $stringFields = [
            'first_name',
            'last_name',
            'address',
            'address_2',
            'zip',
            'city',
            'note',
            'avatar_url',
            'timezone',
            'description',
        ];

        foreach ($stringFields as $field) {
            $copyNullIfEmpty($field);
        }

        // Email
        if ($isCreate || array_key_exists('email', $input)) {
            $email = sanitize_email($input['email'] ?? '');
            $output['email'] = ($email !== '') ? $email : null;
        }

        // Phone
        if ($isCreate || array_key_exists('phone', $input)) {
            $output['phone'] = Sanitizer::phone($input['phone'] ?? null);
        }

        // Country (ISO-2) + Language
        if ($isCreate || array_key_exists('country', $input)) {
            $output['country'] = self::normalizeCountry($input['country'] ?? null);
        }
        if ($isCreate || array_key_exists('language', $input)) {
            $output['language'] = Sanitizer::language($input['language'] ?? null) ?? 'de';
        }

        // Birthdate
        if ($isCreate || array_key_exists('birthdate', $input)) {
            $birthdate = trim((string) ($input['birthdate'] ?? ''));
            if ($birthdate === '') {
                $output['birthdate'] = null;
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
                $output['birthdate'] = $birthdate;
            } elseif (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $birthdate, $matches)) {
                $output['birthdate'] = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            } else {
                $output['birthdate'] = null;
            }
        }

        // Gender – tolerant mappen
        if ($isCreate || array_key_exists('gender', $input)) {
            $output['gender'] = self::normalizeGender($input['gender'] ?? null);
        }

        // Status
        if ($isCreate || array_key_exists('status', $input)) {
            $output['status'] = self::normalizeStatus($input['status'] ?? 'active');
        }

        // Optional tenant_id
        if ($isCreate || array_key_exists('tenant_id', $input)) {
            $output['tenant_id'] = isset($input['tenant_id']) ? (int) $input['tenant_id'] : null;
        }

        // Badge
        if ($isCreate || array_key_exists('badge_id', $input)) {
            $output['badge_id'] = isset($input['badge_id']) && $input['badge_id'] !== ''
                ? (int) $input['badge_id']
                : null;
        }

        // Employee-Area-Password (write-only) → Hash
        if ($isCreate || array_key_exists('employee_area_password', $input)) {
            $password = (string) ($input['employee_area_password'] ?? '');
            // Leer lassen ⇒ kein Update; beim Create optional
            if ($password !== '') {
                $output['password_hash'] = wp_hash_password($password);
            } elseif ($isCreate) {
                $output['password_hash'] = null;
            }
        }

        // Collections (roh übernehmen, wenn vorhanden; Full-Replace im Controller)
        if (array_key_exists('workday_sets', $input)) {
            $output['workday_sets'] = is_array($input['workday_sets']) ? $input['workday_sets'] : [];
        }

        if (array_key_exists('days_off', $input)) {
            $output['days_off'] = is_array($input['days_off']) ? $input['days_off'] : [];
        }

        if (array_key_exists('special_day_sets', $input)) {
            $output['special_day_sets'] = is_array($input['special_day_sets'])
                ? $input['special_day_sets']
                : [];
        }

        if (array_key_exists('calendars', $input)) {
            $output['calendars'] = is_array($input['calendars']) ? $input['calendars'] : [];
        }

        return $output;
    }

    /**
     * Normalisiert Status-Enum.
     *
     * Akzeptiert:
     * - active, blocked, deleted (passthrough)
     * - inactive, deactivated → blocked
     * - ungültig → active (default)
     *
     * @param string $status Status-String
     * @return string Normalisierter Status
     */
    public static function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if (in_array($status, ['active', 'blocked', 'deleted'], true)) {
            return $status;
        }
        if (in_array($status, ['inactive', 'deactivated'], true)) {
            return 'blocked';
        }

        return 'active';
    }

    /**
     * Gender tolerantes Mapping (wie bei customers).
     *
     * Akzeptiert:
     * - male, female, other, none → m, f, d, n
     * - m, f, d, n (passthrough)
     * - männlich, weiblich, divers, keine angabe → m, f, d, n
     * - Leer oder ungültig → NULL
     *
     * @param mixed $gender Gender-Input (String, Array, etc.)
     * @return string|null Normalisiertes Gender (m|f|d|n) oder NULL
     */
    public static function normalizeGender($gender): ?string
    {
        $gender = strtolower(trim((string) $gender));
        if ($gender === '') {
            return null;
        }

        $map = [
            // Englische UI-Keys
            'male'   => 'm',
            'female' => 'f',
            'other'  => 'd',
            'none'   => 'n',
            // DB-Codes direkt
            'm' => 'm',
            'f' => 'f',
            'd' => 'd',
            'n' => 'n',
            // Deutsche Bezeichnungen
            'männlich' => 'm',
            'weiblich' => 'f',
            'divers'   => 'd',
            'keine angabe' => 'n',
        ];

        return $map[$gender] ?? null;
    }

    /**
     * Country strikt ISO-2 (A–Z).
     *
     * Akzeptiert auch Objekt/Array (z.B. {code:'CH'} oder {value:'CH'}).
     * Nur 2-Buchstaben ISO-Codes werden akzeptiert.
     * Leer oder ungültig → NULL
     *
     * @param mixed $country Country-Input (String, Array, etc.)
     * @return string|null ISO-2 Country-Code oder NULL
     */
    public static function normalizeCountry($country): ?string
    {
        if (is_array($country)) {
            $country = $country['code'] ?? $country['value'] ?? null;
        }
        $country = strtoupper(trim((string) $country));
        if ($country === '') {
            return null;
        }

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
    }
}
