# Activity-Logging Guide für Bookando

Dieses Dokument beschreibt, welche Aktionen geloggt werden sollten und wie.

## Bereits implementiertes Logging

### 1. DevBypass-Nutzung (Gate.php)
✅ **Implementiert**

```php
// Gate.php:29-40
if ($bypass) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $backtrace[1]['function'] ?? 'unknown';
    
    ActivityLogger::warning('security', 'DevBypass verwendet', [
        'user_id' => get_current_user_id(),
        'caller' => "{$class}::{$caller}",
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}
```

### 2. Rate Limit Exceeded (Gate.php)
✅ **Implementiert**

```php
// Gate.php:65-70
if ($attempts >= $maxAttempts) {
    ActivityLogger::warning('security', 'Rate limit exceeded', [
        'identifier' => $identifier,
        'attempts' => $attempts,
        'max' => $maxAttempts,
        'window' => $windowSeconds
    ]);
    return false;
}
```

---

## Empfohlenes zusätzliches Logging

### 1. Login-Ereignisse

**Wo**: WordPress-Hook `wp_login` und `wp_login_failed`

**Beispiel**:
```php
// In Plugin.php oder einem eigenen AuthListener.php
add_action('wp_login', function($user_login, $user) {
    ActivityLogger::info('auth', 'User login successful', [
        'user_id' => $user->ID,
        'username' => $user_login,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}, 10, 2);

add_action('wp_login_failed', function($username, $error) {
    ActivityLogger::warning('auth', 'Login failed', [
        'username' => $username,
        'error' => $error->get_error_message(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}, 10, 2);
```

---

### 2. Permission-Änderungen

**Wo**: Wenn User-Rollen oder Capabilities geändert werden

**Beispiel**:
```php
// In UserService.php oder ähnlich
public function updateUserRole(int $userId, string $newRole): void {
    $user = get_userdata($userId);
    $oldRole = $user->roles[0] ?? 'none';
    
    // Rolle ändern
    $user->set_role($newRole);
    
    // Loggen
    ActivityLogger::warning('permissions', 'User role changed', [
        'user_id' => $userId,
        'old_role' => $oldRole,
        'new_role' => $newRole,
        'changed_by' => get_current_user_id()
    ]);
}
```

---

### 3. Daten-Export

**Wo**: Bei CSV/PDF-Export von Kundendaten, Buchungen, etc.

**Beispiel**:
```php
// In Customers/RestHandler.php oder Export-Service
public function exportCustomers(WP_REST_Request $request): WP_REST_Response {
    $filters = $request->get_params();
    $customers = $this->fetchCustomers($filters);
    
    // Export durchführen
    $csv = $this->generateCSV($customers);
    
    // Loggen (wichtig für DSGVO-Compliance!)
    ActivityLogger::info('export', 'Customer data exported', [
        'user_id' => get_current_user_id(),
        'count' => count($customers),
        'filters' => $filters,
        'format' => 'csv'
    ]);
    
    return rest_ensure_response(['data' => $csv]);
}
```

---

### 4. Kritische Daten-Änderungen

**Wo**: Bei DELETE, wichtigen UPDATEs

**Beispiel**:
```php
// In Customers/RestHandler.php
public function deleteCustomer(WP_REST_Request $request): WP_REST_Response {
    $customerId = $request->get_param('id');
    
    // Hole Customer-Daten vor Löschung
    $customer = $this->getCustomer($customerId);
    
    // Lösche
    $this->db->delete('customers', ['id' => $customerId]);
    
    // Loggen
    ActivityLogger::warning('customers', 'Customer deleted', [
        'customer_id' => $customerId,
        'email' => $customer->email,
        'deleted_by' => get_current_user_id()
    ]);
    
    return rest_ensure_response(['success' => true]);
}
```

---

### 5. License-Änderungen

**Wo**: Wenn Lizenz aktiviert/deaktiviert wird

**Beispiel**:
```php
// In LicenseManager.php
public function activateLicense(string $licenseKey): bool {
    $result = $this->api->activate($licenseKey);
    
    if ($result['success']) {
        ActivityLogger::info('licensing', 'License activated', [
            'license_key' => substr($licenseKey, 0, 10) . '***',
            'plan' => $result['plan'],
            'activated_by' => get_current_user_id()
        ]);
    }
    
    return $result['success'];
}
```

---

### 6. Tenant-Switching (Multi-Tenancy)

**Wo**: Wenn Admin zwischen Mandanten wechselt

**Beispiel**:
```php
// In TenantManager.php
public static function switchTenant(int $newTenantId): void {
    $oldTenantId = self::currentTenantId();
    
    // Switch durchführen
    self::$currentTenantId = $newTenantId;
    
    // Loggen
    ActivityLogger::info('tenancy', 'Tenant switched', [
        'user_id' => get_current_user_id(),
        'old_tenant' => $oldTenantId,
        'new_tenant' => $newTenantId
    ]);
}
```

---

## Implementierungs-Prioritäten

| Prio | Aktion | Grund |
|------|--------|-------|
| 🔴 **HOCH** | Login-Ereignisse | Sicherheit, Intrusion-Detection |
| 🔴 **HOCH** | Daten-Export | DSGVO-Compliance |
| 🟡 **MITTEL** | Permission-Änderungen | Audit-Trail |
| 🟡 **MITTEL** | Kritische DELETE-Operationen | Fehlersuche |
| 🟢 **NIEDRIG** | Tenant-Switching | Debugging |
| 🟢 **NIEDRIG** | License-Aktivierung | Support |

---

## Log-Konventionen

### Kontext-Namen

- `auth` - Authentifizierung (Login, Logout)
- `security` - Sicherheit (Rate Limits, DevBypass)
- `permissions` - Berechtigungen (Rollen, Capabilities)
- `export` - Datenexporte (CSV, PDF)
- `tenancy` - Multi-Tenancy (Tenant-Switch)
- `licensing` - Lizenzierung
- `{module_name}` - Modul-spezifisch (z.B. `customers`, `employees`)

### Payload-Struktur

Immer folgende Felder inkludieren (wenn verfügbar):
```php
[
    'user_id' => get_current_user_id(),      // Wer hat es gemacht?
    'tenant_id' => TenantManager::currentTenantId(), // In welchem Mandanten?
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',    // Von welcher IP?
    // ... weitere kontextabhängige Felder
]
```

---

## Datenschutz-Hinweise (DSGVO)

⚠️ **WICHTIG**: Sensible Daten NICHT in Logs speichern!

```php
// ❌ FALSCH
ActivityLogger::info('customers', 'Customer created', [
    'email' => $customer->email,              // ⚠️ Personenbezogen!
    'phone' => $customer->phone,              // ⚠️ Personenbezogen!
    'credit_card' => $customer->credit_card   // ⚠️ HOCHSENSIBEL!
]);

// ✅ RICHTIG
ActivityLogger::info('customers', 'Customer created', [
    'customer_id' => $customer->id,           // ✅ ID statt Email
    'has_phone' => !empty($customer->phone),  // ✅ Boolean statt Wert
    // credit_card NIEMALS loggen!
]);
```

---

## Log-Aufbewahrung

Empfohlene Retention-Policies:

| Log-Typ | Aufbewahrung | Begründung |
|---------|--------------|------------|
| Sicherheits-Events | 1 Jahr | Forensik, Compliance |
| Daten-Exports | 6 Monate | DSGVO-Nachweis |
| Permission-Changes | 6 Monate | Audit-Trail |
| Normal-Events | 30 Tage | Debugging |

**Implementierung**: Cron-Job in `CronDispatcher.php`:
```php
add_action('bookando_daily_cleanup', function() {
    global $wpdb;
    $table = $wpdb->prefix . 'bookando_activity_log';
    
    // Security-Logs: 1 Jahr
    $wpdb->query("
        DELETE FROM $table 
        WHERE severity = 'warning' 
        AND context IN ('security', 'auth') 
        AND logged_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    
    // Normal-Logs: 30 Tage
    $wpdb->query("
        DELETE FROM $table 
        WHERE severity = 'info' 
        AND logged_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
});
```

---

**Letzte Aktualisierung**: 2025-11-10  
**Autor**: Bookando Development Team
