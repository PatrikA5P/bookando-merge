# Modul: Customers

> Scaffold erstellt mit Bookando CLI – 2025-06-02

## Beschreibung
Manage customers for bookings and CRM.

## Datenhaltung
- Kundendaten werden in der Core-Tabelle `wp_bookando_users` (bzw. `<prefix>bookando_users`, z. B. `wp_bookando_users`) gespeichert.
- Das Modul legt keine eigene Tabelle mehr an. Bestandsinstallationen erhalten beim Update automatisch eine Sicherung der ehemaligen Tabelle als `..._bookando_customers_legacy`.

## Migration
- Beim Ausführen des Core-Installers (z. B. bei Plugin-Aktivierung) wird eine vorhandene Legacy-Tabelle `bookando_customers` entweder in `bookando_customers_legacy` umbenannt oder – falls bereits vorhanden – entfernt.
- Daten können bei Bedarf manuell aus der Backup-Tabelle in die neue Struktur migriert werden.

## REST-Endpunkte
- GET /customers

## Vue-Komponenten
- SPA: Admin.vue
- State: Pinia
- Liste: ✅
- Formular: ✅
- Upload: ✅
- Datepicker: ❌
- Editor: ❌

## Lizenzpflicht: ✅ Ja
