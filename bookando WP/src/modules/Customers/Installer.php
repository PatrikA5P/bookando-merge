<?php
/**
 * Installer für Modul "customers"
 */
namespace Bookando\Modules\Customers;

class Installer
{
    public static function install(): void
    {
        // Legacy-Tabellenanlage entfernt – das Modul verwendet die Core-Tabelle
        // `bookando_users` für die Kundendatenhaltung.
    }
}
