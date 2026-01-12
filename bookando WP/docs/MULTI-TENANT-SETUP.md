# Multi-Tenant Setup & Konfiguration

## 📋 Übersicht

Bookando unterstützt Multi-Tenancy für die Verwaltung mehrerer Mandanten (Kunden/Organisationen) in einer Installation.

## 🔧 Tenant-Filterung

### Wie funktioniert es?

Die Tenant-Filterung wird in zwei Modi betrieben:

#### 1. **Produktiv-Modus** (Standard)

```php
// RestHandler.php
$tenantId = TenantManager::currentTenantId(); // z.B. 1

// Repository filtert:
// WHERE ... AND (tenant_id = 1 OR tenant_id IS NULL)
```

**Verhalten:**
- Jeder User sieht nur Daten seines Tenants
- Legacy-Daten mit `tenant_id = NULL` werden dem aktuellen Tenant zugeordnet
- Strikte Datenisolation zwischen Tenants

#### 2. **DEV-Modus** (Entwicklung/Testing)

```php
// RestHandler.php
$tenantId = Gate::devBypass() ? null : TenantManager::currentTenantId();
//           ↑ Im DEV-Modus: null

// Repository filtert NICHT:
// WHERE ... (kein tenant_id Filter)
```

**Verhalten:**
- **Alle Tenants** werden angezeigt
- Ideal für Entwicklung und Debugging
- Kein Performance-Overhead durch OR-Bedingungen

## ⚙️ DEV-Modus aktivieren

### Option 1: wp-config.php (empfohlen)

```php
// wp-config.php
define('WP_ENVIRONMENT_TYPE', 'local');
// oder
define('WP_ENVIRONMENT_TYPE', 'development');
```

### Option 2: Per Admin-Rolle

Der DEV-Bypass wird automatisch aktiviert wenn:
- `WP_ENVIRONMENT_TYPE` ist `local`, `development` oder `staging`
- Und der User ist Admin

Siehe: `src/Core/Auth/Gate.php::devBypass()`

## 🚀 Migration: NULL tenant_id beheben

### Problem

Historische Daten haben möglicherweise `tenant_id = NULL`. Dies erfordert eine OR-Bedingung in SQL-Abfragen und kann Performance beeinträchtigen.

### Lösung: Migration-Script

```bash
# 1. DRY-RUN: Simulation ohne Änderungen
php scripts/migrate-null-tenant-ids.php --dry-run --tenant-id=1

# 2. Analyse-Ausgabe prüfen
# → Zeigt betroffene Datensätze
# → Verifiziert Ziel-Tenant

# 3. Migration durchführen
php scripts/migrate-null-tenant-ids.php --tenant-id=1

# 4. Verifizieren
# → Prüfe Kundenansicht im Backend
# → Alle Kunden sollten sichtbar sein
```

### Nach der Migration

**Optional:** OR-Workaround entfernen für bessere Performance

```php
// CustomerRepository.php - NACH erfolgreicher Migration
if ($tenantId !== null) {
    // Vorher: $where .= ' AND (tenant_id = %d OR tenant_id IS NULL)';
    $where .= ' AND tenant_id = %d';  // ← Vereinfacht!
    $args[] = $tenantId;
}
```

**Optional:** DB-Constraint hinzufügen

```sql
-- Erzwinge NOT NULL für tenant_id
ALTER TABLE wp_bookando_users
MODIFY tenant_id INT NOT NULL DEFAULT 1;
```

## 🎯 Best Practices

### 1. **Strikte Tenant-Isolation (Produktion)**

```php
// ✅ RICHTIG: Immer mit tenant_id filtern
$tenantId = TenantManager::currentTenantId();
$customers = $repository->list($filters, $tenantId);

// ❌ FALSCH: Niemals null in Produktion
$customers = $repository->list($filters, null); // Zeigt ALLE Tenants!
```

### 2. **DEV-Modus nur in Entwicklung**

```php
// ✅ RICHTIG: Umgebungsabhängig
$tenantId = Gate::devBypass() ? null : TenantManager::currentTenantId();

// ❌ FALSCH: Hardcoded bypass
$tenantId = null; // Sicherheitsrisiko!
```

### 3. **Neue Datensätze immer mit tenant_id**

```php
// ✅ RICHTIG: tenant_id explizit setzen
$data = [
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'tenant_id' => $tenantId ?: 1, // Fallback für Legacy-Support
    // ...
];
$repository->insert($data);

// ❌ FALSCH: tenant_id auslassen
$data = ['first_name' => 'Max', /* keine tenant_id */];
```

### 4. **Superadmin-Rechte**

Für spezielle User, die mehrere Tenants verwalten sollen:

```php
// Erweitere Gate.php
public static function canAccessMultipleTenants(): bool
{
    if (self::devBypass()) return true;

    $user = wp_get_current_user();
    return $user->has_cap('manage_network'); // Netzwerk-Admin
}

// Nutze in RestHandler.php
$tenantId = Gate::canAccessMultipleTenants()
    ? ($request->get_param('tenant_id') ?? TenantManager::currentTenantId())
    : TenantManager::currentTenantId();
```

## 🔍 Debugging

### Console-Logging aktivieren

```bash
# Browser Console
localStorage.setItem('BOOKANDO_DEBUG_HTTP', '1')

# Zeigt alle API-Requests mit tenant_id
```

### SQL-Abfragen prüfen

```php
// Aktiviere WordPress Query Monitor Plugin
// Oder füge temporär hinzu:

global $wpdb;
$wpdb->show_errors();
echo $wpdb->last_query; // Nach Repository-Aufruf
```

### Tenant-ID ermitteln

```javascript
// Browser Console
console.log(window.BOOKANDO_VARS?.current_tenant_id);
```

```php
// PHP
$tenantId = TenantManager::currentTenantId();
echo "Current Tenant: $tenantId\n";
```

## 📊 Performance-Optimierung

### Vor Migration (mit NULL-Werten)

```sql
-- Langsam: OR-Bedingung verhindert Index-Nutzung
WHERE (tenant_id = 1 OR tenant_id IS NULL)
```

### Nach Migration

```sql
-- Schnell: Index kann genutzt werden
WHERE tenant_id = 1

-- Optional: Compound-Index hinzufügen
CREATE INDEX idx_tenant_role ON wp_bookando_users(tenant_id, roles(100));
```

## 🛡️ Sicherheit

### Checklist

- [ ] `WP_ENVIRONMENT_TYPE` in wp-config.php gesetzt
- [ ] DEV-Bypass nur in Entwicklungsumgebungen aktiv
- [ ] Alle neuen Datensätze haben explizite tenant_id
- [ ] Migration für Legacy-NULL-Werte durchgeführt
- [ ] Tests für Tenant-Isolation geschrieben
- [ ] Keine hardcoded tenant_id-Bypasses im Code

### Test: Tenant-Isolation verifizieren

```bash
# 1. Als User von Tenant A einloggen
# 2. Kundenansicht öffnen
# 3. Browser DevTools → Network → API-Request prüfen
# 4. Sollte nur Kunden von Tenant A zeigen

# 5. Als Admin im DEV-Modus
# 6. Sollte ALLE Tenants zeigen
```

## 📚 Weiterführende Dokumentation

- **TenantManager**: `src/Core/Tenant/TenantManager.php`
- **Gate/Permissions**: `src/Core/Auth/Gate.php`
- **Customer Repository**: `src/modules/customers/CustomerRepository.php`
- **REST Handler**: `src/modules/customers/RestHandler.php`

## 🆘 Troubleshooting

### Problem: "Keine Kunden werden angezeigt"

**Ursache:** tenant_id-Filterung blockiert Legacy-Daten

**Lösung:**
```bash
# Option 1: DEV-Modus aktivieren (temporär)
# wp-config.php: define('WP_ENVIRONMENT_TYPE', 'local');

# Option 2: Migration durchführen (dauerhaft)
php scripts/migrate-null-tenant-ids.php --tenant-id=1
```

### Problem: "Ich sehe Daten anderer Tenants"

**Ursache:** DEV-Modus aktiv in Produktion

**Lösung:**
```php
// wp-config.php
define('WP_ENVIRONMENT_TYPE', 'production'); // Explizit setzen
```

### Problem: "Tenant-ID ist immer null"

**Ursache:** TenantManager nicht korrekt initialisiert

**Lösung:**
```php
// Prüfe ob Tenant-Middleware läuft
// Debug-Output in TenantManager::currentTenantId()
```

---

**Letzte Aktualisierung:** 2025-11-10
**Version:** 1.0
