<?php
namespace Bookando\Core\Role;

class CapabilityService
{
    public static function getAll(): array
    {
        return [
            'manage_bookando_settings',
            'manage_bookando_customers',
            'manage_bookando_events',
            'manage_bookando_bookings',
            'manage_bookando_services',
        ];
    }

    public static function moduleCap(string $module): string
    {
        // Einheitliche Ableitung + Filter-Hook für spätere Anpassungen
        $default = "manage_bookando_{$module}";
        return apply_filters("bookando_cap_{$module}", $default);
    }

    public static function assignAllTo(string $roleName): void
    {
        $role = get_role($roleName);
        if (!$role) return;
        foreach (self::getAll() as $cap) {
            if (!$role->has_cap($cap)) $role->add_cap($cap);
        }
    }

    public static function removeAllFrom(string $roleName): void
    {
        $role = get_role($roleName);
        if (!$role) return;
        foreach (self::getAll() as $cap) {
            if ($role->has_cap($cap)) $role->remove_cap($cap);
        }
    }

    public static function createRoles(): void
    {
        // einmalig anlegen (idempotent)
        add_role('bookando_manager', 'Bookando Manager', []);
        add_role('bookando_employee', 'Bookando Mitarbeiter', []);
        // Standard-Zuweisungen
        self::assignAllTo('bookando_manager'); // Manager kann alles
        // Employee bekommt nur ausgewählte Rechte (Beispiel)
        $emp = get_role('bookando_employee');
        if ($emp && !$emp->has_cap('manage_bookando_customers')) {
            $emp->add_cap('manage_bookando_customers');
            // ...weitere gezielte Caps bei Bedarf
        }
    }

    public static function seedOnActivation(): void
    {
        // Rollen sicher anlegen
        if (!get_role('bookando_manager')) {
            add_role('bookando_manager', __('Bookando Manager', 'bookando'), ['read' => true]);
        }
        if (!get_role('bookando_employee')) {
            add_role('bookando_employee', __('Bookando Mitarbeiter', 'bookando'), ['read' => true]);
        }

        // Caps verteilen
        self::assignAllTo('administrator');
        self::assignAllTo('bookando_manager');

        // Employee: gezielte Basisrechte
        $emp = get_role('bookando_employee');
        if ($emp && !$emp->has_cap('manage_bookando_customers')) {
            $emp->add_cap('manage_bookando_customers');
            // hier ggf. weitere Caps ergänzen
        }
    }

}
