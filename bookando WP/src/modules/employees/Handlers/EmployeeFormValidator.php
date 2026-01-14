<?php

declare(strict_types=1);

namespace Bookando\Modules\employees\Handlers;

use function in_array;
use function is_array;
use function implode;

/**
 * FormRules-Validierung für Employee-Modul.
 *
 * Validiert Pflichtfelder basierend auf:
 * - FormRules-Konfiguration
 * - Ziel-Status (active, blocked, deleted)
 * - Conditional Requirements (when.status_is, when.status_not)
 * - Gruppen-Regeln (at_least_one_of)
 */
class EmployeeFormValidator
{
    /**
     * Validiert Daten gegen FormRules für einen Ziel-Status.
     *
     * Prüft:
     * - Einzelne Pflichtfelder (required + when-Conditions)
     * - Gruppen-Regeln (at_least_one_of)
     *
     * @param array $data Zu validierende Daten
     * @param array $rules FormRules-Konfiguration
     * @param string $targetStatus Ziel-Status (active, blocked, deleted)
     * @return array Liste der fehlenden Felder (leer = valide)
     */
    public static function validateByRules(array $data, array $rules, string $targetStatus): array
    {
        $missing = [];

        // Einzelne Pflichtfelder prüfen
        foreach (($rules['fields'] ?? []) as $field => $config) {
            if (self::fieldRequiredByWhen($config, $targetStatus)) {
                $value = $data[$field] ?? null;
                if ($value === null || $value === '') {
                    $missing[] = $field;
                }
            }
        }

        // Gruppen-Regeln: at_least_one_of
        foreach (($rules['groups']['at_least_one_of'] ?? []) as $group) {
            $anyFilled = false;
            foreach ((array) $group as $field) {
                $value = $data[$field] ?? null;
                if ($value !== null && $value !== '') {
                    $anyFilled = true;
                    break;
                }
            }
            if (!$anyFilled) {
                $missing[] = 'at_least_one_of:' . implode('|', (array) $group);
            }
        }

        return $missing;
    }

    /**
     * Prüft, ob ein Feld für einen bestimmten Status erforderlich ist.
     *
     * Berücksichtigt conditional requirements:
     * - when.status_is: Feld nur bei bestimmten Status required
     * - when.status_not: Feld nicht required bei bestimmten Status
     *
     * @param array $config Feld-Konfiguration aus FormRules
     * @param string $status Aktueller/Ziel-Status
     * @return bool True wenn Feld required
     */
    public static function fieldRequiredByWhen(array $config, string $status): bool
    {
        if (empty($config['required'])) {
            return false;
        }

        $when = $config['when'] ?? [];
        $isRequired = true;

        // status_is: Feld nur bei diesen Status required
        if (!empty($when['status_is'])) {
            $isRequired = in_array($status, (array) $when['status_is'], true);
        }

        // status_not: Feld nicht required bei diesen Status
        if (!empty($when['status_not'])) {
            if (in_array($status, (array) $when['status_not'], true)) {
                $isRequired = false;
            }
        }

        return $isRequired;
    }
}
