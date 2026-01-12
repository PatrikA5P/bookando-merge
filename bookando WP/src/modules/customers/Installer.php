<?php
/**
 * Installer für Modul "customers"
 */
namespace Bookando\Modules\customers;

class Installer
{
    public static function install(): void
    {
        // Legacy-Tabellenanlage entfernt – das Modul verwendet die Core-Tabelle
        // `bookando_users` für die Kundendatenhaltung.
    }
}
