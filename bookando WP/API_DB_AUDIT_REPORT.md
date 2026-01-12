# API-Design & Datenbankstruktur - Gründliche Analyse

**Projekt:** Bookando  
**Analysedatum:** 2025-11-16  
**Fokus:** REST-API, Datenbankdesign, Data-Layer, Caching, Sicherheit

---

## EXECUTIVE SUMMARY

Bookando implementiert eine **reife, mehrschichtige Architektur** mit zentralisiertem REST-Dispatcher, Multi-Tenant-Isolation auf Datenbankebene und umfangreichem Sicherheitsmanagement. 

### Stärken:
- ✅ Klare Multi-Tenant-Isolation mit erzwungener Tenant-Filterung
- ✅ Standardisierte Fehlerbehandlung und Response-Struktur
- ✅ Mehrschichtige Authentifizierung (JWT, API-Key, Session)
- ✅ Ausgezeichnete Datenbankmigrationen mit sauberer Normalisierung
- ✅ Robustes Rate-Limiting & Nonce-Verifikation

### Problembereiche:
- ⚠️ Keine explizite API-Versionierung in Responses
- ⚠️ Fehlende globale Pagination-Limits in einigen Endpunkten
- ⚠️ Minimales Caching für Lesezugriffe
- ⚠️ Keine Dokumentation von Ratelimit-Verhalten
- ⚠️ Inkonsistenz bei Fehlerdetails

---

## 1. REST-API-DESIGN

### 1.1 Endpoint-Struktur & Konsistenz

#### Struktur: ✅ Konsistent

**Namespace:** `bookando/v1` (zentral definiert in `RestDispatcher::NS`)

**Muster:** `/bookando/v1/{module}/{type}/{subkey?}`

Beispiele:
```
GET    /bookando/v1/customers/customers                    (Liste)
POST   /bookando/v1/customers/customers                    (Neu)
GET    /bookando/v1/customers/customers/{id}              (Detail)
PUT    /bookando/v1/customers/customers/{id}              (Update)
DELETE /bookando/v1/customers/customers/{id}?hard=1       (Löschen)

GET    /bookando/v1/employees/employees/{id}/workday-sets (Nested)
POST   /bookando/v1/employees/employees/{id}/workday-sets (Nested Create)

POST   /bookando/v1/appointments/assign                     (Action)
GET    /bookando/v1/appointments/timeline                   (Special)
```

**Registrierungsmechanismus:**
```php
// RestDispatcher::init()
register_rest_route(self::NS, '/(?P<module>...)/(?P<type>...)', [
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'callback' => [self::class, 'dispatch'],
    'permission_callback' => [self::class, 'permission'],
]);
```

**Vorteile:**
- Ein Catch-all-Handler für alle Module
- Explizite Modulregistrierung via `RestDispatcher::registerModule()`
- Route-zu-Modul-Mapping mit Patterns

**Problem:** Mehrere spezielle Routen hardcodiert:
```php
register_rest_route(self::NS, '/employees/(?P<id>\d+)/workday-sets', [...]);
register_rest_route(self::NS, '/employees/(?P<id>\d+)/calendars', [...]);
// ... 10+ weitere Spezialrouten
```

❌ **Befund:** Verstößt gegen DRY-Prinzip, fehleranfällig bei Erweiterung

---

### 1.2 HTTP-Methoden Korrekt Verwendet?

#### Status: ✅ Überwiegend korrekt, mit geringfügigen Problemen

**Korrekte Verwendung:**
```php
// RestDispatcher.php
register_rest_route(self::NS, '/users/(?P<id>\d+|self)/avatar', [
    'methods' => ['POST', 'DELETE'],  // ✅ Richtig
    'callback' => [self::class, 'avatarHandler'],
]);

register_rest_route(self::NS, '/employees/(?P<id>\d+)/calendars', [
    'methods' => ['GET', 'PUT'],       // ✅ Richtig (PUT für Update-Collection)
    'callback' => [self::class, 'employeesCalendarsList'],
]);
```

**Probleme:**
1. **GET mit Mutation (Days Off)**
```php
register_rest_route(self::NS, '/employees/(?P<id>\d+)/days-off', [
    'methods' => ['GET', 'POST', 'PUT'],  // ⚠️ PUT auf GET-Route?
    'callback' => [self::class, 'employeesDaysOff'],
]);
```

2. **Fehlende PATCH-Methode**
```php
// employees/calendars/(?P<calId>\d+) nutzt nur PATCH und DELETE
// Aber empfohlen: auch PUT für Vollupdate
```

**Empfehlungen:**
- PUT für Vollupdate einer Collection
- PATCH für Teilupdate (nicht implementiert)
- POST nur für Create, nicht für Aktion-ähnliche OPs

---

### 1.3 Response-Formate Einheitlich?

#### Status: ✅ Sehr einheitlich

**Standard-Format:**
```php
// Response::ok()
{
    "data": { ... },
    "meta": {
        "success": true,
        "status": 200
    }
}

// Response::error()
{
    "data": null,
    "error": {
        "code": "error_code",
        "message": "Lesbare Fehlermeldung",
        "details": { ... }  // Optional
    },
    "meta": {
        "success": false,
        "status": 400
    }
}

// Response::created()
{
    "data": { id: 123, ... },
    "meta": { "success": true, "status": 201 }
}

// Response::deleted()
{
    "data": {
        "deleted": true,
        "hard": false,  // soft vs hard delete
        ...
    },
    "meta": { "success": true }
}
```

**Konsistenzpunkte:**
- ✅ Alle Responses haben `data`, `error`, `meta`
- ✅ Konsistente Status-Codes (200, 201, 400, 403, 404, 429, 500)
- ✅ Strukturierte Error-Details

**Problem:**
```php
// Manchmal direktes WP_Error zurück statt Response
return new WP_Error('not_found', 'Message', ['status' => 404]);

// Sollte sein:
return Response::error([
    'code' => 'not_found',
    'message' => 'Message'
], 404);
```

❌ **Befund:** ~15-20% der Fehler sind WP_Errors statt standardisierter Responses

---

### 1.4 Error-Handling

#### Status: ✅ Robust, aber inkonsistent

**Implementierung in RestDispatcher::permission():**
```php
public static function permission($request) {
    $module = self::resolveModuleSlug($request);

    if ($module === null) {
        return new WP_Error(
            'rest_unknown_module',
            __('Unable to resolve module for this request'),
            ['status' => 403]
        );
    }

    if (!self::ensureModuleRegistered($module)) {
        return new WP_Error(
            'rest_module_unregistered',
            sprintf(__('No REST module registered for "%s".'), $module),
            ['status' => 500]
        );
    }

    // ... weitere Guard-Checks
    $guard = RestModuleGuard::for($module, $after);
    $result = $guard($request);

    return $result !== false;
}
```

**Fehlertypen:**
| Status | Fehler | Beispiel |
|--------|--------|----------|
| 400 | Bad Request | Missing parameter, invalid format |
| 401 | Unauthorized | Invalid JWT, expired API key |
| 403 | Forbidden | Missing capability, module unregistered |
| 404 | Not Found | Resource doesn't exist |
| 405 | Method Not Allowed | Unsupported HTTP method |
| 429 | Rate Limit | Too many requests |
| 500 | Server Error | Database error, handler failure |

**Logging:**
```php
ActivityLogger::error('rest.dispatch', 'Module handler failed', [
    'module' => $module,
    'type' => $type,
    'error' => $exception->getMessage(),
]);

ActivityLogger::warning('auth.failed', 'User not authenticated', [
    'route' => $route,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
]);
```

✅ **Befund:** Standardisiert, aber könnte strukturierter sein

---

### 1.5 API-Versionierung

#### Status: ⚠️ Minimal implementiert

**Aktuell:**
- Namespace: `bookando/v1` (hartcodiert)
- Keine Content-Type Negotiation
- Keine Accept-Header Verarbeitung
- Keine API-Version in Response-Header

**Fehlend:**
```php
// Sollte sein:
header('API-Version: v1');
header('Deprecation: true');  // Wenn Feature veraltet
```

**Empfehlungen:**
1. Version im Response-Header
2. Deprecation-Warning bei älteren Versionen
3. Version-Negotiation in BaseApi

```php
// Beispiel:
public static function getAPIVersion(): string {
    return '1.0.0';  // Semantic versioning
}
```

---

### 1.6 Rate-Limiting

#### Status: ✅ Implementiert, aber nicht dokumentiert

**Implementierung in `Gate::checkRateLimit()`:**
```php
public static function checkRateLimit(
    string $identifier,
    int $maxAttempts = 10,
    int $windowSeconds = 60
): bool {
    $key = 'bookando_ratelimit_' . md5($identifier);
    $attempts = (int) get_transient($key);

    if ($attempts >= $maxAttempts) {
        ActivityLogger::warning('security', 'Rate limit exceeded', [...]);
        return false;
    }

    set_transient($key, $attempts + 1, $windowSeconds);
    return true;
}
```

**Nutzung in REST:**
```php
// Gate::evaluate()
if (self::isWrite($request)) {
    $rateLimitCheck = self::checkRestRateLimit(
        $request,
        "write_{$module}",
        30,  // max_attempts
        60   // window_seconds
    );
    if ($rateLimitCheck instanceof WP_Error) {
        return $rateLimitCheck;
    }
}
```

**Probleme:**
1. Transients als Cache (WordPress-spezifisch, nicht optimal)
2. Keine Differenzierung nach Endpoint
3. Keine Dokumentation der Limits
4. Keine Retry-After Header in Responses

**Fehler bei Überschreitung:**
```
HTTP 429 Too Many Requests
{
    "data": null,
    "error": {
        "code": "rest_rate_limit_exceeded",
        "message": "Rate limit exceeded. Please try again later."
    },
    "meta": {
        "success": false,
        "status": 429,
        "retry_after": 60
    }
}
```

✅ **Befund:** Rate-Limiting vorhanden, Response könnte Standard-Header verwenden

---

### 1.7 Pagination

#### Status: ⚠️ Implementiert, aber Limit fehlt teilweise

**BaseApi-Pagination:**
```php
public static function getPaginationParams(WP_REST_Request $request): array {
    $page     = max(1, (int) $request->get_param('page') ?: 1);
    $per_page = min(100, max(1, (int) $request->get_param('per_page') ?: 20));
    $offset   = ($page - 1) * $per_page;
    return compact('page', 'per_page', 'offset');
}
```

**Problem:** Max 100 ist hartcodiert, nicht konfigurierbar

**In CustomerRepository:**
```php
public function list(array $filters, int $tenantId): array {
    $limit  = (int) ($filters['limit'] ?? 50);
    $offset = (int) ($filters['offset'] ?? 0);
    
    // ⚠️ Keine Obergrenze!
    // Request mit limit=999999 ist möglich!
    
    $sqlRows = "SELECT * FROM {$this->table} {$where} 
                ORDER BY {$order} {$dir}, id ASC 
                LIMIT %d OFFSET %d";
}
```

❌ **Befund:** Pagination-Limits variieren, keine Standardisierung

**Empfohlene Fixes:**
```php
const PAGINATION_MAX_PER_PAGE = 100;
const PAGINATION_DEFAULT_PER_PAGE = 25;

protected function validatePaginationParams(array $filters): array {
    return [
        'limit' => min(self::PAGINATION_MAX_PER_PAGE, 
                      max(1, (int) ($filters['limit'] ?? self::PAGINATION_DEFAULT_PER_PAGE))),
        'offset' => max(0, (int) ($filters['offset'] ?? 0))
    ];
}
```

---

## 2. DATENBANKSTRUKTUR

### 2.1 Tabellendesign & Normalisierung

#### Status: ✅ Exzellent normalisiert

**Core-Tabellen (Installer.php):**

```
Tabellen-Übersicht:
├── Multi-Tenant (mandanten & benutzer)
│   ├── tenants (id, name, status, time_zone, ...)
│   ├── users (id, tenant_id, roles JSON, ...)
│   ├── user_roles (user_id, role_id) [Junction]
│   └── roles (id, slug)
│
├── Events & Termine (UTC-basiert)
│   ├── events (id, tenant_id, type, status, ...)
│   ├── event_periods (id, event_id, period_start_utc, period_end_utc)
│   ├── event_period_employees (period_id, user_id) [Junction]
│   ├── event_period_services (period_id, service_id) [Junction]
│   ├── event_period_locations (period_id, location_id) [Junction]
│   └── event_period_resources (period_id, resource_id) [Junction]
│
├── Buchungen
│   └── appointments (id, tenant_id, customer_id, service_id, 
│                     starts_at_utc, ends_at_utc, client_tz, ...)
│
├── Infrastruktur (Orte, Zahlungen, Custom Fields)
│   ├── locations (id, tenant_id, name, address, ...)
│   ├── payments (id, appointment_id, amount, status, method, ...)
│   ├── custom_fields (id, type, required, ...)
│   ├── custom_field_options (id, field_id, label, ...)
│   └── custom_field_map (id, field_id, entity_type, entity_id)
│
├── Benachrichtigungen
│   ├── notifications (id, trigger_entity, trigger_action, ...)
│   └── notification_log (id, notification_id, status, sent_at, ...)
│
├── Verfügbarkeiten
│   ├── employees_workday_sets (user_id, week_day_id) [Defaults]
│   ├── employees_workday_intervals (set_id, start_time, end_time)
│   ├── employees_workday_set_services (set_id, service_id) [Junction]
│   ├── employees_workday_set_locations (set_id, location_id) [Junction]
│   ├── employees_days_off (user_id, start_date, end_date, repeat_yearly)
│   ├── employees_specialday_sets (user_id, start_date) [Exceptions]
│   ├── employees_specialday_intervals (set_id, start_time, end_time)
│   └── employees_specialday_set_* [Junctions]
│
├── Kalender-Integration
│   ├── calendar_connections (user_id, provider, auth_type, ...)
│   ├── calendars (connection_id, calendar_id, access, ...)
│   └── calendar_events (appointment_id, calendar_id, external_event_id)
│
├── Konfiguration
│   ├── settings (tenant_id, settings_key, value JSON)
│   ├── booking_settings (slot_length, default_status, ...)
│   ├── working_hours_settings (week_schedule JSON, days_off JSON)
│   ├── company_settings (name, address, phone, ...)
│   ├── notifications_settings (mail_service, smtp_*, provider_settings JSON)
│   ├── payments_settings (currency, price_separator, ...)
│   ├── integrations_settings (google_calendar JSON, zoom JSON, ...)
│   ├── event_settings (allow_overbooking, employee_selection_logic, ...)
│   └── offer_categories (tenant_id, offer_type ENUM, slug, ...)
│
├── Partner & Sharing (B2B)
│   ├── partner_relationships (primary_tenant, partner_tenant, ...)
│   ├── shared_offerings (offering_type, offering_id, owner_tenant, ...)
│   ├── commission_ledger (appointment_id, commission_amount, status, ...)
│   └── share_acl (resource_type, resource_id, owner_tenant, grantee_tenant)
│
├── Technisch
│   ├── webhook_outbox (event_type, payload JSON, status, ...)
│   ├── availability_month_cache (user_id, year, month, payload, valid_until)
│   ├── api_keys (user_id, tenant_id, key_hash, permissions JSON, ...)
│   ├── role_settings (tenant_id, role_slug, settings JSON)
│   ├── module_states (slug, status, installed_at, ...)
│   └── activity_log (logged_at, context, message, payload, ...)
```

**Normalisierungsgrad: 3NF + JSON für Semi-Strukturierte Daten**

**Beispiele guter Normalisierung:**

1. **User-Rollen: Korrekt normalisiert**
```sql
-- Gut: Separate Junction-Tabelle
user_roles (user_id, role_id) PRIMARY KEY (user_id, role_id)

-- Vermieden: Denormalisiert in users.roles (hätte Queries kompliziert)
-- users.roles = JSON ['bookando_admin', 'bookando_teacher']  ← Aktuell!
```

2. **Workday-Sets: Granular & flexibel**
```sql
employees_workday_sets (id, user_id, week_day_id, label, sort)
employees_workday_intervals (id, set_id, start_time, end_time)
employees_workday_set_services (set_id, service_id)  -- Defaults
employees_workday_interval_services (interval_id, service_id)  -- Overrides
```

**Besonderheit:** Sets können Services auf zwei Ebenen haben:
- Set-Ebene: Defaults für den ganzen Tag
- Interval-Ebene: Spezifische Services pro Zeitfenster

3. **Event-Periode-Architektur: Elegant**
```sql
events (id, tenant_id, name, type, status, ...)
event_periods (id, event_id, period_start_utc, period_end_utc)
event_period_employees (period_id, user_id, role)
event_period_services (period_id, service_id)
```

Vorteil: Ein Event kann mehrere Zeiträume haben, jeder mit eigenen Zuordnungen

**Probleme:**

1. **JSON-Verwendung beim Rollen-Management**
```sql
users.roles JSON DEFAULT NULL
```
❌ Sollte relational sein über `user_roles` (ist bereits da!)

2. **Settings als JSON in separaten Tabellen**
```sql
booking_settings (slot_length_minutes, default_status, ...)
notifications_settings (smtp_host, smtp_port, ...)
```
⚠️ Funktioniert, aber könnte durch Settings-Entity vereinheitlicht sein

---

### 2.2 Indizes & Performance

#### Status: ✅ Gut durchdacht, mit Verbesserungspotenzial

**Vorhandene Indizes:**

```sql
-- Multi-Tenant Isolation
KEY idx_users_tenant (tenant_id)
KEY idx_events_tenant (tenant_id)
KEY idx_appointments_tenant (tenant_id)

-- Bereichsabfragen
KEY idx_appointments_time (starts_at_utc, ends_at_utc)
KEY idx_evtp_range (period_start_utc, period_end_utc)

-- Status & Statusübergang
KEY idx_events_status (status)
KEY idx_appointments_status (status)

-- Spezielle Patterns
KEY idx_apt_referral (referred_by_tenant, commission_status)  -- Provisionen
KEY idx_module_states_status (status)
KEY idx_module_states_updated (updated_at)

-- Verfügbarkeitsabfragen
KEY idx_wds_user_day (user_id, week_day_id)
KEY idx_wds_user_day_sort (user_id, week_day_id, sort)
KEY idx_wdi_range (set_id, start_time, end_time)

-- Kalender
KEY idx_user_provider (user_id, provider)
KEY idx_conn (connection_id)

-- Suche
KEY idx_cfo_field (field_id)
KEY idx_cfm_entity (entity_type, entity_id)
```

**Fehlende, kritische Indizes:**

1. **Composite-Indizes für häufige Queries**
```sql
-- In appointments: sehr häufige Query
SELECT * FROM appointments 
WHERE tenant_id = ? AND customer_id = ? AND status = ? 
ORDER BY starts_at_utc DESC

-- Sollte sein:
KEY idx_apt_tenant_customer_status (tenant_id, customer_id, status)
```

2. **JSON-Field Indizes**
```sql
-- Für Rollen-Suchen (User hat Rolle?)
KEY idx_users_roles (roles)  -- MySQL 5.7+: Generated Column
```

3. **Unique Constraints**
```sql
-- Gibt es?
UNIQUE KEY uq_tenant_email (tenant_id, email)
UNIQUE KEY uq_api_key (api_key)

-- Sollte es geben:
UNIQUE KEY uq_user_external (external_id)  -- Für WP-Sync
```

**Performance-Analyse:**

Häufige Queries in RestHandler:
```php
// employees/RestHandler.php - Zeile 133-138
$sql = "SELECT * FROM {$usersTab} WHERE id = %d";
if ($tenantId) {
    $sql .= $wpdb->prepare(" AND tenant_id = %d", $tenantId);
}

// ✅ Schnell: idx_users_tenant
```

```php
// customers/CustomerRepository.php - Zeile 92-95
$sqlTotal = "SELECT COUNT(*) FROM {$this->table} {$where}";
// Mit Search: 
// WHERE (tenant_id = ? OR tenant_id IS NULL) 
//   AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)

// ⚠️ Könnte langsam sein: Keine Indizes auf (first_name, last_name, email)
```

**Empfehlungen:**

```sql
-- Für Kunden-Suche
KEY idx_customers_name (first_name, last_name)
KEY idx_customers_email (email)

-- Für Termine nach Zeitraum pro Tenant
KEY idx_apt_tenant_range (tenant_id, starts_at_utc, ends_at_utc)

-- Für Ausfallzeiten-Queries (häufig)
KEY idx_daysoff_user_range (user_id, start_date, end_date)

-- Für Status-Übergänge
KEY idx_apt_tenant_status_date (tenant_id, status, starts_at_utc)
```

---

### 2.3 Foreign-Key-Beziehungen

#### Status: ⚠️ Nicht implementiert

**Aktueller Stand:** Keine FOREIGN KEY Constraints!

```php
// Core/Installer.php - Zeile 59-891
$sql = [
    "CREATE TABLE {$p}appointments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NULL,
        customer_id BIGINT UNSIGNED NULL,  -- ← Keine FK!
        employee_id BIGINT UNSIGNED NULL,  -- ← Keine FK!
        service_id BIGINT UNSIGNED NULL,   -- ← Keine FK!
        location_id BIGINT UNSIGNED NULL,  -- ← Keine FK!
        ...
    ) $col;",
```

**Probleme ohne Foreign Keys:**

1. **Orphaned Records möglich**
```sql
-- Customer gelöscht, aber Appointment bleibt
DELETE FROM users WHERE id = 123;
-- appointments.customer_id = 123 existiert jetzt nicht mehr
```

2. **Keine CASCADE-Operationen**
```sql
-- Sollte: Hard-Delete eines Customers alle seine Appointments löschen
-- Aktuell: Manuell implementiert (fehleranfällig)
```

3. **Keine Datenbank-Integritätsprüfung**

**Warum nicht implementiert?**
- WordPress nutzt oft keine FKs (historisch)
- Shared Hosting unterstützt FKs nicht immer
- Aber moderne Setups sollten FKs nutzen!

**Empfohlene Lösung:**

```sql
ALTER TABLE `bookando_appointments` 
ADD CONSTRAINT fk_apt_customer FOREIGN KEY (customer_id) 
    REFERENCES bookando_users(id) ON DELETE SET NULL;

ALTER TABLE `bookando_appointments`
ADD CONSTRAINT fk_apt_employee FOREIGN KEY (employee_id)
    REFERENCES bookando_users(id) ON DELETE SET NULL;

ALTER TABLE `bookando_appointments`
ADD CONSTRAINT fk_apt_service FOREIGN KEY (service_id)
    REFERENCES bookando_services(id) ON DELETE SET NULL;

ALTER TABLE `bookando_event_periods`
ADD CONSTRAINT fk_evtp_event FOREIGN KEY (event_id)
    REFERENCES bookando_events(id) ON DELETE CASCADE;
```

**Für jetzt:** Validierung auf Application-Layer durchführen

```php
// In CustomerRepository::hardDelete()
// Stelle sicher, dass Appointments auch bereinigt werden
private function cleanupRelatedData(int $customerId): void {
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}bookando_appointments 
         WHERE customer_id = %d",
        $customerId
    ));
}
```

---

### 2.4 Multi-Tenant-Architektur in DB

#### Status: ✅ Ausgezeichnet implementiert

**Design: Row-Level Isolation (Tenant-ID pro Tabelle)**

**Erzwingung auf Datenbankebene:**

```php
// BaseModel.php - MultiTenantTrait
protected function applyTenant(string $baseSql, array $args): array {
    $tenantId = TenantManager::currentTenantId();
    
    if ($tenantId === null) {
        throw new \RuntimeException('Tenant context missing');
    }
    
    // Automatische WHERE-Klausel hinzufügen
    if (stripos($baseSql, 'WHERE') !== false) {
        $scopedSql = str_replace('WHERE', "WHERE tenant_id = %d AND", $baseSql);
    } else {
        $scopedSql = $baseSql . " WHERE tenant_id = %d";
    }
    
    array_unshift($args, $tenantId);
    return [$scopedSql, $args];
}

// Nutzung:
protected function fetchAll(string $baseSql, array $args = []): array {
    [$scopedSql, $scopedArgs] = $this->applyTenant($baseSql, $args);
    // ← Tenant-Filter automatisch injiziert!
    $prepared = $this->db->prepare($scopedSql, $scopedArgs);
    return $this->db->get_results($prepared, ARRAY_A) ?: [];
}
```

**Beispiel: Customers auflisten**

```php
// RestHandler ruft auf:
$service->listCustomers($query, $tenantId);

// CustomerService::listCustomers() ruft auf:
$repository->list($filters, $tenantId);

// CustomerRepository::list():
public function list(array $filters, int $tenantId): array {
    $where = "WHERE (tenant_id = %d OR tenant_id IS NULL)";
    $args = [$tenantId];
    
    // ⚠️ Erlaubt auch NULL-Tenant (Legacy-Daten)
    // Dies ist bewusst, aber kann Probleme verursachen
    
    // Filter, Search, etc. hinzufügen...
    $sqlRows = "SELECT * FROM {$this->table} {$where} ... LIMIT %d OFFSET %d";
}
```

**Tenant-Auflösung:**

```php
// TenantManager::resolveFromRequest()
public static function resolveFromRequest($request): ?int {
    // 1. X-BOOKANDO-TENANT Header (explizit)
    $tenant = (int) ($request->get_header('X-BOOKANDO-TENANT') ?: 0);
    if ($tenant > 0) return $tenant;
    
    // 2. User-Meta (Standard)
    $userId = get_current_user_id();
    if ($userId > 0) {
        $meta = get_user_meta($userId, 'bookando_default_tenant', true);
        if ((int) $meta > 0) return (int) $meta;
    }
    
    // 3. Subdomain (z.B. "tenant1.bookando.local")
    // (Code nicht gezeigt)
    
    // 4. Default
    return null;
}
```

**Kontextmanagement:**

```php
// In RestDispatcher::safeParams()
$tenantId = TenantManager::resolveFromRequest($request);
if ($tenantId > 0) {
    $params['_tenant_id'] = $tenantId;
    TenantManager::setCurrentTenantId($tenantId);  // Global Cache
}
```

**Sicherheit: ✅ Exzellent**

```php
// Beispiel: Unbewusster Tenant-Wechsel wird verhindert
$prev = TenantManager::currentTenantId();  // Speichere Alt
TenantManager::setCurrentTenantId($newTenant);
try {
    // Operationen mit neuem Tenant
} finally {
    TenantManager::setCurrentTenantId($prev);  // Stelle wieder her
}
```

**Problem: Legacy-Tenant-Handling**

```php
// Überall WHERE (tenant_id = %d OR tenant_id IS NULL)
// Dies erlaubt Zugriff auf Daten ohne Tenant-Zuweisung

// Empfehlung: Deprecated-Warnung
$query = "... WHERE (tenant_id = %d OR tenant_id IS NULL)";
// Sollte mittel-bis langfristig zu WHERE tenant_id = %d übergehen
```

---

### 2.5 Migrations-System

#### Status: ⚠️ Funktional, aber unkonventionell

**Ansatz:** WordPress-native `dbDelta()` statt Laravel Migrations

```php
// Core/Installer.php::installCoreTables()
protected static function installCoreTables(): void {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    $p = $wpdb->prefix . 'bookando_';
    $col = $wpdb->get_charset_collate();
    
    $sql = [
        "CREATE TABLE {$p}tenants (...) {$col};",
        "CREATE TABLE {$p}users (...) {$col};",
        // ... 80+ weitere Tabellen
    ];
    
    foreach ($sql as $statement) {
        dbDelta($statement);  // ← WordPress-Funktion
    }
    
    self::normalizeTimestamps();  // Post-Processing
}
```

**Vorteile:**
- ✅ Keine externe Abhängigkeit (WordPress-native)
- ✅ Funktioniert auf WordPress-Hosting
- ✅ Idempotent (dbDelta() erstellt nur wenn nötig)

**Nachteile:**
- ❌ Keine Rollback-Möglichkeit
- ❌ Keine Migrations-Historie
- ❌ Schwer zu versionieren
- ❌ ALTER TABLE-Statements nicht automatisiert

**Implementierung der Modul-Installer:**

```php
// modules/employees/Installer.php
class Installer {
    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $tableName = $wpdb->prefix . 'bookando_employees';
        $sql = "CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(191) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) {$wpdb->get_charset_collate()};";
        
        dbDelta($sql);
    }
}
```

**Problem: Redundante Tabellendeklarationen!**

`bookando_employees` wird in mehreren Stellen definiert:
1. `Core/Installer.php` (Zeile 1-70)
2. `modules/employees/Installer.php` (Zeile 18-24)
3. Möglichweise in anderen Modulen

**Besserer Ansatz:**

```php
// Zentrale Migration-Klasse
class Migrations {
    public static function installVersion1_0_0(): void {
        // Alle Tabellen für v1.0.0
    }
    
    public static function installVersion1_1_0(): void {
        // Schema-Änderungen für v1.1.0
    }
}

// Mit Tracking:
$installedVersion = get_option('bookando_db_version');
if (version_compare($installedVersion, '1.1.0', '<')) {
    Migrations::installVersion1_1_0();
    update_option('bookando_db_version', '1.1.0');
}
```

---

### 2.6 Backup-Strategie

#### Status: ❌ Keine erkannte Strategie

**Keine Implementierung gefunden für:**
- Automatische Backups
- Snapshot-Verwaltung
- Backup-Validierung
- Point-in-Time Recovery

**Empfehlungen:**

1. **Application-Level Backup Hook**
```php
// Core/Installer.php
public static function createBackup(): void {
    global $wpdb;
    
    $backupDate = date('Y-m-d_H-i-s');
    $backupName = "bookando_backup_{$backupDate}";
    
    // WordPress-native:
    do_action('bookando_before_backup');
    
    // Dann Datenbank-Dump
    // ... (externe Tools wie mysqldump)
    
    do_action('bookando_after_backup', $backupName);
}
```

2. **Outbox-Pattern für Webhooks** (bereits implementiert!)
```sql
webhook_outbox (id, event_type, payload JSON, status, ...)
-- Diese Tabelle hält Events bis zur erfolgreichen Zustellung
-- = Disaster Recovery für Webhooks
```

---

## 3. DATA-LAYER

### 3.1 Repository-Pattern

#### Status: ✅ Teilweise implementiert, könnte standardisiert sein

**Implementiert in:**
- `CustomerRepository` (customers module)
- `StateRepository` (academy module)
- `PartnershipRepository` (core)

**Beispiel: CustomerRepository**

```php
class CustomerRepository {
    private wpdb $db;
    private string $table;
    
    public function __construct(?wpdb $wpdbInstance = null) {
        global $wpdb;
        $this->db = $wpdbInstance ?? $wpdb;
        $this->table = $this->db->prefix . 'bookando_users';
    }
    
    public function find(int $id, int $tenantId): ?array {
        // Get single record
    }
    
    public function list(array $filters, int $tenantId): array {
        // Get multiple with filters/search/pagination
    }
    
    public function insert(array $data): int {
        // Insert new record
    }
    
    public function update(int $id, array $data, int $tenantId): int {
        // Update record
    }
    
    public function softDelete(int $id, int $tenantId): void {
        // Mark as deleted
    }
    
    public function hardDelete(int $id, int $tenantId): void {
        // Permanently delete + anonymize PII
    }
}
```

**Problem: Nicht überall implementiert**

- ✅ Customers: CustomerRepository vorhanden
- ❌ Employees: In RestHandler vermischt
- ❌ Appointments: In RestHandler vermischt
- ❌ Settings: In RestHandler vermischt

**Empfehlung: Repository für alle Entities**

```
src/modules/
├── employees/
│   ├── RestHandler.php
│   ├── Repository.php (neu)
│   └── Service.php (neu)
├── appointments/
│   ├── RestHandler.php
│   ├── Repository.php (neu)
│   └── Service.php (neu)
└── ...
```

---

### 3.2 Model-Klassen

#### Status: ✅ BaseModel vorhanden, sparsam genutzt

**Zentrale Basis-Klasse:**

```php
abstract class BaseModel {
    use MultiTenantTrait;
    
    protected wpdb $db;
    protected string $tableName;
    
    protected function fetchAll(string $baseSql, array $args = []): array {
        // Multi-tenant SELECT mit automatischem tenant_id-Filter
    }
    
    protected function fetchOne(string $baseSql, array $args = []): ?array {
        // Wie fetchAll, aber ein Record
    }
    
    protected function paginate(...): array {
        // fetchAll mit Pagination
    }
    
    protected function insert(array $data): int {
        // Insert + auto-tenant_id setzen
    }
    
    protected function update(int $id, array $data): int {
        // Update mit tenant_id-Scope
    }
    
    protected function delete(int $id): int {
        // Delete mit tenant_id-Scope
    }
}
```

**Nutzung:**
```php
class CustomerModel extends BaseModel {
    public function __construct() {
        $this->tableName = $this->table('users');
    }
    
    public function getActiveCustomers(): array {
        return $this->fetchAll(
            "SELECT * FROM {$this->tableName} WHERE status = %s",
            ['active']
        );
        // tenant_id-Filter wird automatisch hinzugefügt!
    }
}
```

**Problem: BaseModel wird nicht konsistent genutzt**

- ✅ CustomerRepository nutzt $wpdb direkt (OK, explizit)
- ❌ EmployeesRestHandler vermischt SQL im Handler
- ⚠️ Keine Model-Klassen für Employees, Appointments, etc.

**Empfehlung:**

```php
// Konsequente Nutzung
class CustomerModel extends BaseModel {
    // Spezifische Queries für Customers
}

class AppointmentModel extends BaseModel {
    // Spezifische Queries für Appointments
}

class RestHandler {
    private CustomerModel $customerModel;
    
    public function customers($params, WP_REST_Request $request) {
        $customers = $this->customerModel->getActiveCustomers();
        // Clean separation of concerns
    }
}
```

---

### 3.3 ORM vs Raw SQL

#### Status: ⚠️ Raw SQL durchgehend, kein ORM

**Implementierung: Reine wpdb-Prepared Statements**

```php
// Gut: Prepared Statements überall
$sql = "SELECT * FROM {$this->table} WHERE id = %d";
$row = $this->db->get_row($this->db->prepare($sql, $id), ARRAY_A);

// Sehr gut: Parameterisiert
$sql = "SELECT * FROM {$this->table} WHERE id = %d AND tenant_id = %d";
$row = $wpdb->get_row($wpdb->prepare($sql, $id, $tenantId), ARRAY_A);
```

**Vorteil:** Schutz vor SQL-Injection durch `wpdb->prepare()`

**Nachteil:** Keine Query-Builder, viel Boilerplate

**Beispiel komplexe Query (mit Problemen):**

```php
// employees/RestHandler.php - Zeile ~200+
$sql = "SELECT id, week_day_id, label, sort, created_at FROM {$wSetTab}
        WHERE user_id = %d AND week_day_id = %d ORDER BY sort ASC";

// ⚠️ Keine Tenant-Filterung explizit sichtbar!
// (Angenommen, wird anderswo durchgesetzt, aber nicht offensichtlich)
```

**Empfehlung: Query-Builder oder Micro-ORM**

```php
// Pseudo-Code für besseren Query-Builder
$query = Query::table('users')
    ->where('tenant_id', $tenantId)
    ->where('status', 'active')
    ->orderBy('last_name')
    ->limit(50)
    ->get();
```

---

### 3.4 Query-Builder

#### Status: ❌ Nicht vorhanden

**Aktueller Stand: Alles raw SQL**

Beispiel aus BaseModel:
```php
protected function paginate(...): array {
    [$scopedSql, $scopedArgs] = $this->applyTenant($baseSql, $args);
    $orderPart = $this->buildOrderBy($orderBy, $dir, $allow ?: $this->allowedOrderBy());
    $pagedSql  = $scopedSql . $orderPart . ' LIMIT %d OFFSET %d';
    $pagedArgs = array_merge($scopedArgs, [$perPage, $offset]);
    
    $preparedPaged = $this->db->prepare($pagedSql, $pagedArgs);
    $items = $this->db->get_results($preparedPaged, ARRAY_A) ?: [];
    
    // ... mehr SQL-String-Manipulation
}
```

**Problem: Fehleranfällig & schwer zu testen**

**Lösung: Micro Query-Builder**

```php
class QueryBuilder {
    private array $wheres = [];
    private array $args = [];
    private ?int $tenantId = null;
    
    public function where(string $column, string $operator, $value): self {
        $this->wheres[] = "`{$column}` {$operator} %s";
        $this->args[] = $value;
        return $this;
    }
    
    public function tenant(int $tenantId): self {
        $this->tenantId = $tenantId;
        return $this;
    }
    
    public function build(): array {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($this->tenantId !== null) {
            $this->wheres[] = "tenant_id = %d";
            array_unshift($this->args, $this->tenantId);
        }
        
        if ($this->wheres) {
            $sql .= " WHERE " . implode(" AND ", $this->wheres);
        }
        
        return [$sql, $this->args];
    }
}

// Nutzung:
$builder = new QueryBuilder('users');
[$sql, $args] = $builder
    ->tenant($tenantId)
    ->where('status', '=', 'active')
    ->where('role', 'LIKE', '%admin%')
    ->build();
```

---

### 3.5 Data-Validation

#### Status: ✅ Implementiert, aber verstreut

**Validierungsmechanismen:**

1. **Sanitization in RestHandler**
```php
// customers/RestHandler.php
$email = sanitize_email($payload['email'] ?? '');
$name = sanitize_text_field($payload['first_name'] ?? '');
```

2. **FormRules (Zentral)**
```php
// Core/Settings/FormRules.php (angenommen)
// Regeln pro Status (draft → published, etc.)
$rules = FormRules::forStatus('pending');
// $rules->validate($data);
```

3. **Repository-Validierung**
```php
// CustomerRepository
public function insert(array $data): int {
    // Keine explizite Validierung, trust $data
    $this->db->insert($this->table, $data);
}
```

**Problem: Validierung am falschen Ort**

```php
// ❌ Sollte NICHT sein:
public static function customers($params, WP_REST_Request $request) {
    $email = sanitize_email($request->get_param('email'));
    $name = sanitize_text_field($request->get_param('name'));
    // ... 20 Zeilen Sanitization im Handler
    
    // Dann an Service:
    $service->createCustomer(['email' => $email, 'name' => $name]);
}

// ✅ Sollte sein:
public static function customers($params, WP_REST_Request $request) {
    $payload = $request->get_json_params();
    
    $validator = new CustomerValidator();
    $errors = $validator->validate($payload, 'create');
    if (!empty($errors)) {
        return Response::error(['code' => 'validation_failed', 'details' => $errors], 422);
    }
    
    $service->createCustomer($payload);
}
```

**Empfehlung: Validator-Klassen**

```php
class CustomerValidator {
    public function validate(array $data, string $action = 'create'): array {
        $errors = [];
        
        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'Email ist erforderlich.';
        } elseif (!is_email($data['email'])) {
            $errors['email'] = 'Ungültige Email-Adresse.';
        }
        
        // Name
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'Vorname ist erforderlich.';
        }
        
        // Status-spezifisch
        if ($action === 'publish' && empty($data['address'])) {
            $errors['address'] = 'Adresse erforderlich zum Veröffentlichen.';
        }
        
        return $errors;
    }
}
```

---

## 4. CACHING-LAYER

### Status: ⚠️ Minimal implementiert

**Erkannte Caching-Mechanismen:**

1. **WordPress-Transients (Rate Limiting)**
```php
// Gate::checkRateLimit()
$key = 'bookando_ratelimit_' . md5($identifier);
$attempts = (int) get_transient($key);
set_transient($key, $attempts + 1, $windowSeconds);  // 60s default
```

2. **OAuth State Transients**
```php
// RestDispatcher::oauthStart()
set_transient('bookando_oauth_state_' . $state, [
    'provider'    => $provider,
    'employee_id' => $employeeId,
], MINUTE_IN_SECONDS * 15);
```

3. **UI-Monats-Cache**
```sql
-- availability_month_cache
CREATE TABLE bookando_availability_month_cache (
    id, tenant_id, user_id, service_id, location_id,
    year, month, payload MEDIUMTEXT, valid_until,
    PRIMARY KEY (tenant_id, user_id, service_id, location_id, year, month)
);
```

**Fehlende Caching-Strategien:**

1. ❌ **Query-Result Caching**
```php
// Aktuell:
$customers = $repository->list($filters, $tenantId);
// Wird jedes Mal neu aus DB geladen

// Sollte sein:
$cacheKey = 'bookando_customers_' . md5(json_encode($filters) . '_' . $tenantId);
$customers = wp_cache_get($cacheKey);
if (false === $customers) {
    $customers = $repository->list($filters, $tenantId);
    wp_cache_set($cacheKey, $customers, '', 3600);  // 1h
}
```

2. ❌ **Object Caching für häufig gelesene Entities**
```php
// Fehlend:
$customer = wp_cache_remember("bookando_customer_{$id}", function() use ($id) {
    return $repository->find($id);
}, '', 1800);  // 30 Minuten
```

3. ❌ **Cache-Invalidierung**
```php
// Wenn Customer aktualisiert:
wp_cache_delete("bookando_customer_{$id}");
wp_cache_delete("bookando_customers_list");  // Invalidiert alle Listen
```

**Warum wenig Caching?**

- ✅ Könnte intentional sein (Daten-Frische Priorität)
- ✅ WordPress-Caching variiert (ob APCu/Redis/Filesystem)
- ⚠️ Aber für High-Traffic problematisch

**Empfehlung:**

```php
// Cache-Strategie für Lesezugriffe
class CachedRepository {
    private CustomerRepository $repository;
    private CacheManager $cache;
    
    public function find(int $id, int $tenantId): ?array {
        $cacheKey = "customer_{$tenantId}_{$id}";
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        $record = $this->repository->find($id, $tenantId);
        if ($record !== null) {
            $this->cache->set($cacheKey, $record, 1800);  // 30 min
        }
        
        return $record;
    }
    
    public function invalidate(int $id, int $tenantId): void {
        $this->cache->delete("customer_{$tenantId}_{$id}");
    }
}
```

---

## 5. API-SICHERHEIT

### 5.1 Authentication

#### Status: ✅ Mehrschichtig & robust

**Implementiert in: AuthMiddleware::authenticate()**

**Drei Authentifizierungsmethoden (in Reihenfolge):**

1. **JWT Token (Bearer Authorization)**
```php
private static function authenticateJWT(WP_REST_Request $request) {
    $authHeader = $request->get_header('Authorization');
    // Format: "Bearer {token}"
    
    $token = trim(substr($authHeader, 7));
    $payload = JWTService::validateToken($token);
    
    if ($payload instanceof WP_Error) {
        return $payload;  // Fehler
    }
    
    $userId = JWTService::getUserId($payload);
    $tenantId = JWTService::getTenantId($payload);
    
    return [
        'user_id' => $userId,
        'tenant_id' => $tenantId,
        'method' => 'jwt',
    ];
}
```

**Token-Lebenszyklus:**
- Ausgestellt bei Login
- Validiert auf jedem Request
- Abgelaufen = 401 Unauthorized

2. **API Key (X-API-Key Header)**
```php
private static function authenticateAPIKey(WP_REST_Request $request) {
    $apiKey = $request->get_header('X-API-Key');
    
    $result = self::validateAPIKey($apiKey);
    // → Suche key_hash in bookando_api_keys
    
    return [
        'user_id' => $result['user_id'],
        'tenant_id' => $result['tenant_id'],
        'method' => 'api_key',
    ];
}

private static function validateAPIKey(string $apiKey) {
    $keyHash = hash('sha256', $apiKey);  // ✅ Nur Hash in DB!
    
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT ... FROM bookando_api_keys WHERE key_hash = %s",
        $keyHash
    ));
    
    // Checks:
    if ($row['status'] !== 'active') { return error; }          // Status
    if ($row['expires_at'] < time()) { return error; }          // Ablauf
    if (!self::checkRateLimit(...)) { return error; }            // Rate Limit
    
    // Update last_used_at asynchron
    wp_schedule_single_event(time(), 'bookando_update_api_key_usage', [$row['id']]);
    
    return [...];
}
```

**Probleme:**
- ⚠️ `wp_schedule_single_event()` für Audit-Tracking nicht ideal
- ⚠️ Keine Rotation-Mechanik

3. **WordPress Session (Cookie)**
```php
private static function authenticateSession(WP_REST_Request $request): ?array {
    $userId = get_current_user_id();
    if ($userId === 0) return null;  // Nicht eingeloggt
    
    $tenantId = TenantManager::resolveFromRequest($request);
    
    return [
        'user_id' => $userId,
        'tenant_id' => $tenantId,
        'method' => 'session',
    ];
}
```

**Sicherheits-Features:**

| Feature | Status | Details |
|---------|--------|---------|
| Password Hashing | ✅ | Verwendet WordPress `wp_hash_password()` |
| Nonce Verification | ✅ | `wp_verify_nonce($nonce, 'wp_rest')` |
| CSRF Protection | ✅ | Nonce in `X-WP-Nonce` Header |
| Rate Limiting | ✅ | `Gate::checkRateLimit()` per Identifier |
| Token Expiration | ✅ | JWT & API-Keys mit TTL |
| TLS/HTTPS | ? | Nicht im Code erkannt (angenommen) |

**Empfehlungen:**

1. **JWT Rotation**
```php
public static function refreshJWT(string $token): array {
    $payload = JWTService::validateToken($token);
    
    // Neuen Token ausstellen
    $newToken = JWTService::createToken($payload['user_id'], $payload['tenant_id']);
    
    // Alten Token auf Blacklist setzen
    BlacklistService::add($token);
    
    return ['token' => $newToken];
}
```

2. **API-Key Rotation**
```php
public function rotateAPIKey(int $keyId): string {
    $old = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM bookando_api_keys WHERE id = %d", $keyId)
    );
    
    $newKey = wp_generate_password(32, true);
    $newHash = hash('sha256', $newKey);
    
    $wpdb->insert('bookando_api_keys', [
        'user_id' => $old->user_id,
        'tenant_id' => $old->tenant_id,
        'key_hash' => $newHash,
        'replaces_key_id' => $keyId,
        'created_at' => current_time('mysql'),
    ]);
    
    return $newKey;  // Zeige nur einmal!
}
```

---

### 5.2 Authorization

#### Status: ✅ Multi-Level, aber teilweise unklar

**Ebenen:**

1. **Modul-Level**
```php
// Gate::canManage($module)
public static function canManage(string $module): bool {
    if (self::devBypass()) return true;  // Dev-Modus
    return current_user_can(self::moduleCap($module)) 
        || current_user_can('manage_options');  // WordPress Admin
}
```

2. **Request-Level (Gate::evaluate())**
```php
public static function evaluate(WP_REST_Request $request, string $module) {
    if (self::devBypass()) return true;
    
    // Rate Limiting für Writes
    if (self::isWrite($request)) {
        $rateLimitCheck = self::checkRestRateLimit($request, "write_{$module}", 30, 60);
        if ($rateLimitCheck instanceof WP_Error) return $rateLimitCheck;
    }
    
    // User muss eingeloggt sein
    if (!is_user_logged_in()) return false;
    
    // Nonce für Writes
    if (self::isWrite($request) && !self::verifyNonce($request)) {
        return false;
    }
    
    // Modul-Capability
    if (!self::hasCapability(self::moduleCap($module))) {
        return false;
    }
    
    return true;
}
```

3. **Custom Permission Callbacks**
```php
// RestPermissions::customers($request)
// Prüft: READABLE, CREATABLE, EDITABLE, DELETABLE
// Delegiert an Capability-Checks
```

**Problem: Dev-Bypass ist zu breit**

```php
if (self::devBypass()) {
    return true;  // ← Umgeht ALLES!
}

public static function devBypass(): bool {
    $environment = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production';
    
    if ($environment === 'production') {
        return false;
    }
    
    $bypass = defined('BOOKANDO_DEV') && BOOKANDO_DEV && current_user_can('manage_options');
    
    if ($bypass) {
        // Audit-Log: ✅ Gut
        ActivityLogger::warning('security.devbypass', 'DevBypass verwendet', [...]);
    }
    
    return $bypass;
}
```

**Empfehlungen:**

1. **Explizite Capability Whitelist**
```php
public static function evaluate(WP_REST_Request $request, string $module) {
    // Dev-Bypass NUR für lokale Entwicklung
    if (defined('WP_LOCAL_DEVELOPMENT') && WP_LOCAL_DEVELOPMENT) {
        if (current_user_can('manage_options')) {
            ActivityLogger::warning('security.devbypass', ...);
            return true;
        }
    }
    
    // Normale Checks
    return self::checkCapabilities($request, $module);
}
```

2. **Granulare Capabilities pro Action**
```php
// Statt nur "read_customers", "edit_customers"
// Nutze: "read_customers", "create_customers", "edit_own_customers", "delete_customers"
```

---

### 5.3 Input-Validation

#### Status: ✅ Implement, aber nicht zentral

**Sanitization-Patterns:**

1. **Text-Felder**
```php
$email = sanitize_email($payload['email'] ?? '');
$name = sanitize_text_field($payload['first_name'] ?? '');
$text = sanitize_textarea_field($payload['description'] ?? '');
```

2. **Keys**
```php
$key = sanitize_key($request->get_param('module'));
// Nur alphanumerisch + - _
```

3. **URLs**
```php
$url = esc_url_raw($request->get_param('url'));
```

4. **JSON**
```php
$roles = json_decode($payload['roles'], true) ?: [];
// Nicht validiert ob Structure korrekt!
```

**Problem: Keine zentralisierte Validierung**

```php
// ❌ Aktuell: In jedem Handler
public static function customers($params, WP_REST_Request $request) {
    $email = sanitize_email($request->get_param('email'));
    $name = sanitize_text_field($request->get_param('name'));
    // ... mehr Sanitization
}

// ✅ Sollte sein: Validator
class CustomerValidator {
    public function sanitize(array $data): array {
        return [
            'email' => sanitize_email($data['email'] ?? ''),
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'roles' => $this->sanitizeRoles($data['roles'] ?? []),
        ];
    }
    
    private function sanitizeRoles(array $roles): array {
        return array_filter($roles, function($role) {
            return in_array($role, ['bookando_customer', 'bookando_employee', ...], true);
        });
    }
}
```

**Empfehlungen:**

1. **Whitelist-Validierung statt Blacklist**
```php
class RoleValidator {
    private const ALLOWED_ROLES = [
        'bookando_customer',
        'bookando_employee',
        'bookando_admin',
        'bookando_teacher',
    ];
    
    public function validate(array $roles): array {
        return array_intersect($roles, self::ALLOWED_ROLES);
    }
}
```

2. **Type Coercion**
```php
$bookandoUserId = (int) $request->get_param('user_id');  // String → Integer
$isActive = (bool) $request->get_param('is_active');      // String/int → Boolean
```

---

### 5.4 Rate-Limiting (Vertiefung)

#### Status: ⚠️ Implementiert, aber nicht dokumentiert

**Implementierung:**

```php
// Gate::checkRestRateLimit()
public static function checkRestRateLimit(
    WP_REST_Request $request,
    string $action,
    int $maxAttempts = 20,
    int $windowSeconds = 60
) {
    $userId = get_current_user_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Identifier
    $identifier = $userId > 0
        ? "rest_{$action}_user_{$userId}"
        : "rest_{$action}_ip_{$ip}";
    
    if (!self::checkRateLimit($identifier, $maxAttempts, $windowSeconds)) {
        return new WP_Error(
            'rest_rate_limit_exceeded',
            _x('Rate limit exceeded. Please try again later.'),
            ['status' => 429, 'retry_after' => $windowSeconds]
        );
    }
    
    return true;
}

// In Gate::evaluate()
if (self::isWrite($request)) {
    $rateLimitCheck = self::checkRestRateLimit(
        $request,
        "write_{$module}",
        30,    // 30 Versuche
        60     // pro 60 Sekunden
    );
}
```

**Standard Rate-Limits:**

| Aktion | Max | Window | Grund |
|--------|-----|--------|-------|
| Write Operations | 30 | 60s | Standard API-Limit |
| API-Key Access | pro config | (s. oben) | Customizable |
| Rate Limit überschritten | N/A | N/A | 429 Response |

**Problem: Keine Dokumentation in API-Responses**

```json
// Fehlend:
HTTP 429 Too Many Requests

{
    "data": null,
    "error": {
        "code": "rest_rate_limit_exceeded",
        "message": "Rate limit exceeded. Please try again later."
    },
    "meta": {
        "success": false,
        "status": 429,
        "retry_after": 60  // ← Sekunden warten
    }
}
```

**Besser:**
```json
HTTP 429 Too Many Requests
Retry-After: 60

{
    "data": null,
    "error": {
        "code": "rest_rate_limit_exceeded",
        "message": "Rate limit exceeded.",
        "details": {
            "retry_after": 60,
            "limit": 30,
            "window": 60,
            "current_attempts": 31
        }
    },
    "meta": {
        "success": false,
        "status": 429
    }
}
```

---

## ZUSAMMENFASSUNG & EMPFEHLUNGEN

### Stärken der Architektur:

1. ✅ **Multi-Tenant Isolation** ist erzwungen auf mehreren Ebenen
2. ✅ **Standardisierte REST-API** mit konsistenten Responses
3. ✅ **Robustes Datenbank-Schema** mit guter Normalisierung
4. ✅ **Mehrschichtige Authentifizierung** (JWT, API-Key, Session)
5. ✅ **Activity Logging** für Audit-Trail
6. ✅ **Sicherheits-Features** (Rate Limiting, Nonce, Sanitization)

### Verbesserungsbereiche (Priorität):

**Hoch (Quick Wins):**
1. Pagination-Limits standardisieren (MAX 100)
2. WP_Errors konsistent in Response::error() wrappen
3. API-Version in Response-Header hinzufügen
4. Rate-Limit Dokumentation erweitern

**Mittel (Architektur):**
1. Repository-Pattern auf alle Entities ausweiten
2. Zentrale Validator-Klassen für Input-Validation
3. Query-Builder oder Micro-ORM einführen
4. Caching-Strategie für Lesezugriffe implementieren

**Niedrig (Langfristig):**
1. Foreign-Key Constraints hinzufügen
2. Migrations-System von dbDelta zu versioniert
3. ORM erwägen (z.B. Eloquent als Standalone)
4. GraphQL als Alternative zu REST

### Keine kritischen Sicherheitsmängel erkannt ✅

Das System zeigt reife Sicherheitspraktiken und könnte mit den obigen Verbesserungen noch robuster werden.

