<?php
/**
 * Registriert die Workday-Capability für Administrator und bookando_manager Rollen.
 *
 * Dieses Script muss nur EINMAL ausgeführt werden, um die fehlende Capability zu registrieren.
 *
 * Nutzung:
 * 1. Dieses Skript in wp-content/mu-plugins/ als PHP-Datei speichern
 * 2. WordPress Admin öffnen (Capability wird automatisch registriert)
 * 3. Skript wieder entfernen
 */

add_action('init', function() {
    // Capability, die registriert werden soll
    $capability = 'manage_bookando_workday';

    // Rollen, die diese Capability erhalten sollen
    $roles = ['administrator', 'bookando_manager'];

    foreach ($roles as $roleName) {
        $role = get_role($roleName);

        if ($role === null) {
            error_log("[Workday Cap] Rolle '{$roleName}' nicht gefunden - überspringe");
            continue;
        }

        if ($role->has_cap($capability)) {
            error_log("[Workday Cap] Rolle '{$roleName}' hat bereits '{$capability}'");
            continue;
        }

        $role->add_cap($capability);
        error_log("[Workday Cap] ✅ Capability '{$capability}' zu Rolle '{$roleName}' hinzugefügt");
    }

    error_log("[Workday Cap] Capability-Registrierung abgeschlossen");
}, 1);
