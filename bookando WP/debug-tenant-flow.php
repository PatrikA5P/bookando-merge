<?php
/**
 * Debug Script: Tenant-Filterung vollständig durchleuchten
 *
 * Führe dieses Script aus, um GENAU zu sehen, was schief läuft.
 */

// WordPress Bootstrap - flexibel für verschiedene Umgebungen
$wpLoadPaths = [
    __DIR__ . '/../../wp-load.php',
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
];

$wpLoad = null;
foreach ($wpLoadPaths as $path) {
    if (file_exists($path)) {
        $wpLoad = $path;
        break;
    }
}

if (!$wpLoad) {
    // Alternativ: Direkt von Plugin aus
    if (file_exists(__DIR__ . '/bookando.php')) {
        echo "❌ Bitte führe das Script von der WordPress-Installation aus:\n";
        echo "   cd /path/to/wordpress\n";
        echo "   php wp-content/plugins/bookando/debug-tenant-flow.php\n\n";
    } else {
        echo "❌ wp-load.php nicht gefunden.\n";
    }
    exit(1);
}

define('WP_USE_THEMES', false);
require_once $wpLoad;

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 Tenant-Filterung DEBUG                                    ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================================
// 1. Umgebungsprüfung
// ============================================================================

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ 1️⃣  Umgebung & Konfiguration                                │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

$envType = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : '(nicht gesetzt = production)';
echo "WP_ENVIRONMENT_TYPE     : $envType\n";

$bookandoDev = defined('BOOKANDO_DEV') ? (BOOKANDO_DEV ? 'true' : 'false') : '(nicht gesetzt)';
echo "BOOKANDO_DEV            : $bookandoDev\n";

$wpDebug = defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false';
echo "WP_DEBUG                : $wpDebug\n";

// Eingeloggter User
$user = wp_get_current_user();
if ($user->ID) {
    echo "Eingeloggter User       : {$user->user_login} (ID: {$user->ID})\n";
    echo "Rollen                  : " . implode(', ', $user->roles) . "\n";
} else {
    echo "Eingeloggter User       : ❌ NICHT eingeloggt\n";
    echo "⚠️  WICHTIG: Du musst eingeloggt sein!\n";
    echo "   Führe das Script über WordPress-CLI aus:\n";
    echo "   wp eval-file debug-tenant-flow.php --user=admin\n\n";
    exit(1);
}

echo "\n";

// ============================================================================
// 2. Gate & Permissions
// ============================================================================

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ 2️⃣  Gate & Permissions                                       │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

$gateExists = class_exists('Bookando\Core\Auth\Gate');
echo "Gate Class existiert    : " . ($gateExists ? '✓ ja' : '❌ nein') . "\n";

if ($gateExists) {
    $devBypass = \Bookando\Core\Auth\Gate::devBypass();
    echo "Gate::devBypass()       : " . ($devBypass ? '✅ TRUE (alle Tenants)' : '❌ FALSE (strikte Filterung)') . "\n";

    $canManage = \Bookando\Core\Auth\Gate::canManage('customers');
    echo "Gate::canManage('customers'): " . ($canManage ? '✅ TRUE' : '❌ FALSE') . "\n";

    if (!$canManage) {
        echo "\n⚠️  PROBLEM: User hat keine Berechtigung 'customers:manage'!\n";
        echo "   Der API-Request wird bereits auf Permission-Ebene blockiert.\n\n";
    }
} else {
    echo "❌ Gate-Class nicht gefunden - Plugin nicht geladen?\n";
}

echo "\n";

// ============================================================================
// 3. TenantManager
// ============================================================================

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ 3️⃣  TenantManager                                            │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

$tenantManagerExists = class_exists('Bookando\Core\Tenant\TenantManager');
echo "TenantManager existiert : " . ($tenantManagerExists ? '✓ ja' : '❌ nein') . "\n";

if ($tenantManagerExists) {
    $currentTenantId = \Bookando\Core\Tenant\TenantManager::currentTenantId();
    echo "currentTenantId()       : " . ($currentTenantId !== null ? $currentTenantId : 'NULL') . "\n";
} else {
    echo "❌ TenantManager nicht gefunden\n";
    $currentTenantId = null;
}

// Was würde RestHandler verwenden?
$effectiveTenantId = ($gateExists && \Bookando\Core\Auth\Gate::devBypass())
    ? null
    : $currentTenantId;

echo "\n";
echo "🎯 Effektive tenant_id für API: " . ($effectiveTenantId !== null ? $effectiveTenantId : '✅ NULL (zeigt ALLE)') . "\n";

if ($effectiveTenantId !== null) {
    echo "   ⚠️  PROBLEM GEFUNDEN: tenant_id ist NICHT null!\n";
    echo "   Das bedeutet, es wird gefiltert auf tenant_id = $effectiveTenantId\n";
    if ($gateExists && !\Bookando\Core\Auth\Gate::devBypass()) {
        echo "   Ursache: Gate::devBypass() gibt FALSE zurück\n";
        echo "   Prüfe die Implementierung von Gate::devBypass()\n";
    }
}

echo "\n";

// ============================================================================
// 4. Datenbankprüfung
// ============================================================================

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ 4️⃣  Datenbank-Abfragen                                       │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

global $wpdb;
$table = $wpdb->prefix . 'bookando_users';

// Test 1: ALLE Kunden (ohne Filter)
$sqlAll = "SELECT COUNT(*) FROM $table
           WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
           AND status <> 'deleted'";
$countAll = (int) $wpdb->get_var($sqlAll);
echo "Alle aktiven Kunden (kein Filter)        : $countAll\n";

// Test 2: Mit tenant_id Filter (wie Repository)
if ($effectiveTenantId !== null) {
    $sqlFiltered = $wpdb->prepare(
        "SELECT COUNT(*) FROM $table
         WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
         AND status <> 'deleted'
         AND (tenant_id = %d OR tenant_id IS NULL)",
        $effectiveTenantId
    );
    $countFiltered = (int) $wpdb->get_var($sqlFiltered);
    echo "Mit Tenant-Filter (tenant_id=$effectiveTenantId)  : $countFiltered\n";
} else {
    echo "Mit Tenant-Filter (tenant_id=NULL)       : $countAll (kein Filter)\n";
    $countFiltered = $countAll;
}

// Test 3: Nur NULL tenant_ids
$sqlNull = "SELECT COUNT(*) FROM $table
            WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
            AND status <> 'deleted'
            AND tenant_id IS NULL";
$countNull = (int) $wpdb->get_var($sqlNull);
echo "Nur NULL tenant_id                        : $countNull\n";

// Beispiele anzeigen
echo "\n📋 Beispiele (erste 3 Kunden):\n";
$examples = $wpdb->get_results(
    "SELECT id, tenant_id, first_name, last_name, email, status
     FROM $table
     WHERE (JSON_CONTAINS(roles, '\"customer\"') OR JSON_CONTAINS(roles, '\"bookando_customer\"'))
     AND status <> 'deleted'
     LIMIT 3",
    ARRAY_A
);

foreach ($examples as $row) {
    $tid = $row['tenant_id'] !== null ? $row['tenant_id'] : 'NULL';
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    echo "   • ID {$row['id']}: $name (tenant_id: $tid)\n";
}

echo "\n";

// ============================================================================
// 5. Repository-Test
// ============================================================================

echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ 5️⃣  Repository-Test (CustomerService)                        │\n";
echo "└─────────────────────────────────────────────────────────────┘\n";

$serviceExists = class_exists('Bookando\Modules\customers\CustomerService');
echo "CustomerService existiert: " . ($serviceExists ? '✓ ja' : '❌ nein') . "\n";

if ($serviceExists) {
    $service = new \Bookando\Modules\customers\CustomerService();

    echo "\nRufe CustomerService::listCustomers() auf...\n";
    echo "   mit tenant_id = " . ($effectiveTenantId !== null ? $effectiveTenantId : 'NULL') . "\n";

    $result = $service->listCustomers([
        'include_deleted' => 'no',
        'limit' => 50
    ], $effectiveTenantId);

    if (is_wp_error($result)) {
        echo "   ❌ FEHLER: " . $result->get_error_message() . "\n";
    } else {
        $returnedCount = isset($result['data']) ? count($result['data']) : 0;
        echo "   ✅ Erfolgreich\n";
        echo "   → API gibt zurück: $returnedCount Kunden\n";
        echo "   → Total in DB     : {$result['total']}\n";

        if ($returnedCount === 0 && $countAll > 0) {
            echo "\n   ⚠️  PROBLEM: DB hat $countAll Kunden, aber API gibt 0 zurück!\n";
        }

        if ($returnedCount > 0) {
            echo "\n   📋 Erste 3 zurückgegebene Kunden:\n";
            foreach (array_slice($result['data'], 0, 3) as $customer) {
                $tid = $customer['tenant_id'] ?? 'NULL';
                $name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                echo "      • ID {$customer['id']}: $name (tenant_id: $tid)\n";
            }
        }
    }
} else {
    echo "❌ CustomerService nicht gefunden\n";
}

echo "\n";

// ============================================================================
// 6. Diagnose & Lösungsvorschläge
// ============================================================================

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  🎯 Diagnose & Lösungsvorschläge                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$hasProblems = false;

// Problem 1: Keine Berechtigung
if ($gateExists && !$canManage) {
    echo "❌ PROBLEM 1: Fehlende Berechtigung\n";
    echo "   → User hat kein 'customers:manage' Recht\n";
    echo "   → API-Requests werden auf Permission-Ebene blockiert\n";
    echo "\n   LÖSUNG:\n";
    echo "   - Als Administrator einloggen, oder\n";
    echo "   - User die entsprechende Capability zuweisen\n";
    echo "\n";
    $hasProblems = true;
}

// Problem 2: Gate::devBypass() gibt FALSE
if ($gateExists && !$devBypass && $envType === 'local') {
    echo "❌ PROBLEM 2: Gate::devBypass() gibt FALSE zurück\n";
    echo "   → WP_ENVIRONMENT_TYPE ist 'local', aber devBypass() = false\n";
    echo "   → Prüfe die Implementierung von Gate::devBypass()\n";
    echo "\n   LÖSUNG: Schaue dir Gate.php an:\n";
    echo "   src/Core/Auth/Gate.php::devBypass()\n";
    echo "\n";
    $hasProblems = true;
}

// Problem 3: TenantId ist gesetzt obwohl DEV-Modus
if ($effectiveTenantId !== null && $envType === 'local') {
    echo "❌ PROBLEM 3: tenant_id wird gesetzt obwohl DEV-Modus\n";
    echo "   → Effektive tenant_id = $effectiveTenantId\n";
    echo "   → Sollte NULL sein im DEV-Modus\n";
    echo "\n   LÖSUNG: Prüfe RestHandler.php Zeile 30:\n";
    echo "   \$tenantId = Gate::devBypass() ? null : TenantManager::currentTenantId();\n";
    echo "\n";
    $hasProblems = true;
}

// Problem 4: Repository gibt trotzdem 0 zurück
if ($serviceExists && isset($returnedCount) && $returnedCount === 0 && $countAll > 0) {
    echo "❌ PROBLEM 4: Repository gibt 0 Kunden zurück\n";
    echo "   → Datenbank hat $countAll Kunden\n";
    echo "   → API gibt 0 zurück\n";
    echo "\n   LÖSUNG: Aktiviere SQL-Debugging:\n";
    echo "   global \$wpdb;\n";
    echo "   \$wpdb->show_errors();\n";
    echo "   echo \$wpdb->last_query;\n";
    echo "\n";
    $hasProblems = true;
}

if (!$hasProblems) {
    echo "✅ Alles sieht gut aus!\n";
    echo "\n";
    if (isset($returnedCount) && $returnedCount > 0) {
        echo "   Die API sollte funktionieren. Öffne das Kundenmodul im Browser:\n";
        echo "   " . admin_url('admin.php?page=bookando-customers') . "\n";
    } else {
        echo "   Aber: Keine Kunden in der Datenbank gefunden.\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

exit($hasProblems ? 1 : 0);
