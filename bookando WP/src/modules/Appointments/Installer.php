<?php
namespace Bookando\Modules\Appointments;

class Installer
{
    public static function install(): void
    {
        // Die Kern-Tabellen für Termine und Events werden im Core-Installer angelegt.
        // Dieses Modul benötigt derzeit keine zusätzlichen Migrationen.
    }
}
