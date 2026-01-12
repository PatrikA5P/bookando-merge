<?php
/**
 * Migration Script: NULL tenant_id → Default Tenant
 *
 * Zweck:
 * - Migriert alle Kunden mit tenant_id = NULL auf einen Standard-Tenant
 * - Ermöglicht saubere Multi-Tenancy ohne Legacy-Workarounds
 * - Verbessert Performance durch Entfernen der OR-Bedingung
 *
 * Verwendung:
 *   php scripts/migrate-null-tenant-ids.php [--dry-run] [--tenant-id=1]
 *
 * Optionen:
 *   --dry-run      : Simulation ohne tatsächliche Änderungen
 *   --tenant-id=N  : Ziel-Tenant-ID (Standard: 1)
 *   --help         : Diese Hilfe anzeigen
 */

declare(strict_types=1);

// ============================================================================
// Argument Parsing
// ============================================================================

$options = getopt('', ['dry-run', 'tenant-id:', 'help']);

if (isset($options['help'])) {
    echo file_get_contents(__FILE__);
    exit(0);
}

$dryRun = isset($options['dry-run']);
$targetTenantId = isset($options['tenant-id']) ? (int) $options['tenant-id'] : 1;

if ($targetTenantId < 1) {
    echo "❌ Fehler: tenant-id muss >= 1 sein\n";
    exit(1);
}

// ============================================================================
// WordPress Bootstrap
// ============================================================================

// Versuche wp-load.php zu finden
$wpLoad = null;
foreach ([
    __DIR__ . '/../../../wp-load.php',  // Standard-Plugin-Struktur
    __DIR__ . '/../../../../wp-load.php',
    __DIR__ . '/../wp-load.php',
] as $path) {
    if (file_exists($path)) {
        $wpLoad = $path;
        break;
    }
}

if (!$wpLoad) {
    echo "❌ Fehler: wp-load.php nicht gefunden.\n";
    echo "   Führe das Script aus dem Plugin-Verzeichnis aus.\n";
    exit(1);
}

// WordPress laden
define('WP_USE_THEMES', false);
require_once $wpLoad;

global $wpdb;

// ============================================================================
// Vorbereitung
// ============================================================================

$table = $wpdb->prefix . 'bookando_users';

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Migration: NULL tenant_id → Tenant $targetTenantId                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

if ($dryRun) {
    echo "🔍 DRY-RUN Modus: Keine Änderungen werden vorgenommen\n\n";
}

// ============================================================================
// Schritt 1: Analysieren
// ============================================================================

echo "📊 Schritt 1: Analysiere betroffene Datensätze...\n";

// Zähle Kunden mit NULL tenant_id
$nullCustomersCount = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM $table
     WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
     AND tenant_id IS NULL"
);

if ($wpdb->last_error) {
    echo "❌ Datenbankfehler: {$wpdb->last_error}\n";
    exit(1);
}

echo "   → Kunden mit NULL tenant_id: $nullCustomersCount\n";

if ($nullCustomersCount === 0) {
    echo "\n✅ Keine Migration notwendig. Alle Kunden haben bereits eine tenant_id.\n\n";
    exit(0);
}

// Zeige Beispiele
echo "\n   Beispiele (erste 5):\n";
$examples = $wpdb->get_results(
    "SELECT id, first_name, last_name, email, status, created_at
     FROM $table
     WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
     AND tenant_id IS NULL
     LIMIT 5",
    ARRAY_A
);

foreach ($examples as $row) {
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    echo "     • ID {$row['id']}: {$name} ({$row['email']}) - Status: {$row['status']}\n";
}

if ($nullCustomersCount > 5) {
    echo "     • ... und " . ($nullCustomersCount - 5) . " weitere\n";
}

// ============================================================================
// Schritt 2: Prüfe Ziel-Tenant
// ============================================================================

echo "\n📋 Schritt 2: Prüfe Ziel-Tenant...\n";

// Optional: Prüfe ob der Tenant existiert
// (Anpassen je nach deiner Tenant-Tabellen-Struktur)
$tenantExists = true; // Placeholder - anpassen wenn du eine tenant-Tabelle hast

if (!$tenantExists) {
    echo "   ⚠️  WARNUNG: Tenant $targetTenantId existiert möglicherweise nicht\n";
} else {
    echo "   ✓ Ziel-Tenant: $targetTenantId\n";
}

// Prüfe ob es bereits Kunden mit dieser tenant_id gibt
$existingCount = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table
     WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
     AND tenant_id = %d",
    $targetTenantId
));

echo "   → Existierende Kunden in Tenant $targetTenantId: $existingCount\n";

// ============================================================================
// Schritt 3: Migration durchführen
// ============================================================================

if (!$dryRun) {
    echo "\n🔄 Schritt 3: Führe Migration durch...\n";

    $sql = $wpdb->prepare(
        "UPDATE $table
         SET tenant_id = %d, updated_at = %s
         WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
         AND tenant_id IS NULL",
        $targetTenantId,
        current_time('mysql')
    );

    $result = $wpdb->query($sql);

    if ($wpdb->last_error) {
        echo "   ❌ Fehler bei Migration: {$wpdb->last_error}\n";
        exit(1);
    }

    echo "   ✓ Aktualisiert: $result Datensätze\n";

    // Verifizierung
    $remainingNull = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $table
         WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
         AND tenant_id IS NULL"
    );

    if ($remainingNull > 0) {
        echo "   ⚠️  WARNUNG: Es verbleiben noch $remainingNull Kunden mit NULL tenant_id\n";
    } else {
        echo "   ✓ Verifizierung: Keine NULL tenant_ids mehr vorhanden\n";
    }
} else {
    echo "\n🔍 Schritt 3: DRY-RUN - Keine Änderungen\n";
    echo "   → Würde $nullCustomersCount Datensätze aktualisieren\n";
    echo "   → Führe das Script ohne --dry-run aus, um die Migration durchzuführen\n";
}

// ============================================================================
// Zusammenfassung
// ============================================================================

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Migration " . ($dryRun ? 'Simulation' : 'abgeschlossen') . "                                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (!$dryRun) {
    echo "✅ Nächste Schritte:\n";
    echo "\n";
    echo "1. Teste die Kundenansicht im Backend\n";
    echo "2. Prüfe ob alle Kunden korrekt angezeigt werden\n";
    echo "3. Optional: Entferne den OR-Workaround aus CustomerRepository.php\n";
    echo "   (Zeilen mit '|| tenant_id IS NULL' können nach erfolgreicher Migration entfernt werden)\n";
    echo "\n";
    echo "4. Optional: Füge DB-Constraint hinzu:\n";
    echo "   ALTER TABLE $table MODIFY tenant_id INT NOT NULL DEFAULT 1;\n";
    echo "\n";
} else {
    echo "ℹ️  Dies war eine Simulation. Führe das Script ohne --dry-run aus:\n";
    echo "   php scripts/migrate-null-tenant-ids.php --tenant-id=$targetTenantId\n";
    echo "\n";
}

// Rollback-Information
if (!$dryRun) {
    echo "⚠️  Rollback (falls nötig):\n";
    echo "   UPDATE $table\n";
    echo "   SET tenant_id = NULL\n";
    echo "   WHERE tenant_id = $targetTenantId\n";
    echo "   AND updated_at >= '" . current_time('mysql') . "';\n";
    echo "\n";
}

exit(0);
