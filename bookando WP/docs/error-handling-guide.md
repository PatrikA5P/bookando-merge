# Error-Handling Guide für Bookando

Dieser Guide definiert konsistente Error-Handling-Patterns für das Bookando WordPress-Plugin.

## Ziel

Einheitliche Fehlerbehandlung über alle Module hinweg, um:
- **Wartbarkeit** zu verbessern (vorhersagbare Fehlerbehandlung)
- **Debugging** zu erleichtern (konsistente Log-Meldungen)
- **User Experience** zu optimieren (klare Fehlermeldungen)

---

## Patterns nach Kontext

### 1. REST API Endpoints

**Kontext**: Alle REST API Controller (z.B. `RestHandler.php`)

**Pattern**: `WP_Error` zurückgeben

```php
// ✅ RICHTIG
public function handle_create_customer(WP_REST_Request $request) {
    if (!$this->validate_input($request)) {
        return new WP_Error(
            'rest_invalid_param',
            _x('Invalid customer data', 'REST API error message', 'bookando'),
            ['status' => 400]
        );
    }
    
    // ... weiterer Code
}

// ❌ FALSCH
public function handle_create_customer(WP_REST_Request $request) {
    if (!$this->validate_input($request)) {
        throw new Exception('Invalid data'); // KEINE Exceptions in REST!
    }
}
```

**Error-Codes** (WordPress-Standard):
- `rest_unauthorized` - Nicht eingeloggt (401)
- `rest_forbidden` - Keine Berechtigung (403)
- `rest_invalid_param` - Ungültige Parameter (400)
- `rest_not_found` - Ressource nicht gefunden (404)
- `rest_server_error` - Interner Fehler (500)

---

### 2. Service-Klassen (Business-Logik)

**Kontext**: Service-Layer, Helper-Klassen (z.B. `CustomerService.php`)

**Pattern**: **Exceptions** werfen

```php
// ✅ RICHTIG
class CustomerService {
    public function createCustomer(array $data): Customer {
        if (empty($data['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }
        
        if ($this->emailExists($data['email'])) {
            throw new \RuntimeException('Email already exists');
        }
        
        // ... erstelle Customer
    }
}

// Nutzung in REST Controller:
public function handle_create(WP_REST_Request $request) {
    try {
        $customer = $this->customerService->createCustomer($request->get_params());
        return rest_ensure_response($customer);
    } catch (\InvalidArgumentException $e) {
        return new WP_Error('rest_invalid_param', $e->getMessage(), ['status' => 400]);
    } catch (\RuntimeException $e) {
        return new WP_Error('rest_server_error', $e->getMessage(), ['status' => 500]);
    }
}
```

**Exception-Typen**:
- `\InvalidArgumentException` - Ungültige Eingabedaten
- `\RuntimeException` - Laufzeitfehler (DB-Fehler, etc.)
- `\LogicException` - Programmlogik-Fehler (sollte nie in Production passieren)

---

### 3. Helper-Funktionen (Optionale Werte)

**Kontext**: Helper-Funktionen, die etwas suchen/finden

**Pattern**: `null` zurückgeben für "nicht gefunden"

```php
// ✅ RICHTIG
/**
 * Findet einen Customer by ID.
 *
 * @return Customer|null
 */
public function findCustomerById(int $id): ?Customer {
    $row = $this->db->get_row("SELECT * FROM customers WHERE id = $id");
    
    if ($row === null) {
        return null; // Nicht gefunden
    }
    
    return Customer::fromRow($row);
}

// Nutzung:
$customer = $customerService->findCustomerById(123);
if ($customer === null) {
    // Handle "nicht gefunden"
}
```

**WICHTIG**: Immer `?Type` in der Methoden-Signatur annotieren!

---

### 4. Boolean Validierungen

**Kontext**: Validierungsmethoden, Permission-Checks

**Pattern**: `true` / `false` zurückgeben

```php
// ✅ RICHTIG
public function canUserManageCustomer(int $userId, int $customerId): bool {
    return $this->hasPermission($userId, 'manage_customers')
        && $this->isSameTenant($userId, $customerId);
}

// ❌ FALSCH - Keine Exceptions/null für Boolean-Checks!
public function canUserManageCustomer(int $userId, int $customerId): bool {
    if (!$this->hasPermission($userId, 'manage_customers')) {
        throw new Exception('No permission'); // FALSCH!
    }
    // ...
}
```

---

### 5. Datenbank-Operationen

**Kontext**: DB-Queries (INSERT, UPDATE, DELETE)

**Pattern**: Exceptions für kritische Fehler, `false` für Fehlschlag

```php
// ✅ RICHTIG
public function insertCustomer(array $data): int {
    global $wpdb;
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'bookando_customers',
        $data
    );
    
    if ($result === false) {
        // Logge Fehler
        ActivityLogger::error('customers', 'Failed to insert customer', [
            'data' => $data,
            'error' => $wpdb->last_error
        ]);
        
        // Werfe Exception
        throw new \RuntimeException('Failed to insert customer: ' . $wpdb->last_error);
    }
    
    return $wpdb->insert_id;
}
```

---

## Logging-Strategie

### Wann loggen?

| Situation | Log-Level | Beispiel |
|-----------|-----------|----------|
| Kritischer Fehler (DB-Fehler, Permissions) | `error` | `ActivityLogger::error('customers', 'DB insert failed')` |
| Sicherheits-relevante Aktionen | `warning` | `ActivityLogger::warning('security', 'Rate limit exceeded')` |
| Wichtige Business-Ereignisse | `info` | `ActivityLogger::info('customers', 'Customer created')` |

### Log-Konventionen

```php
// ✅ RICHTIG
ActivityLogger::error('module_name', 'Clear error message', [
    'user_id' => get_current_user_id(),
    'context' => $additionalData
]);

// ❌ FALSCH
error_log('Something went wrong'); // Zu unspezifisch
```

---

## Zusammenfassung-Tabelle

| Kontext | Pattern | Beispiel |
|---------|---------|----------|
| **REST API** | `WP_Error` | `return new WP_Error('rest_forbidden', '...', ['status' => 403])` |
| **Service-Layer** | `Exception` | `throw new \RuntimeException('...')` |
| **Optional-Werte** | `null` | `return $customer ?? null` |
| **Validierungen** | `bool` | `return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)` |
| **DB-Operationen** | `Exception` (kritisch) | `throw new \RuntimeException('DB error')` |

---

## Anti-Patterns vermeiden

### ❌ ANTI-PATTERN 1: Inconsistent Returns

```php
// ❌ FALSCH
public function getCustomer(int $id) {
    if ($id <= 0) {
        return false; // Manchmal false
    }
    
    $customer = $this->db->get_row("...");
    if (!$customer) {
        return null; // Manchmal null
    }
    
    return $customer; // Manchmal Objekt
}
```

**Lösung**: Konsistente Rückgabe-Typen:
```php
// ✅ RICHTIG
public function getCustomer(int $id): ?Customer {
    if ($id <= 0) {
        return null; // Immer null für "nicht gefunden"
    }
    
    $customer = $this->db->get_row("...");
    return $customer ? Customer::fromRow($customer) : null;
}
```

---

### ❌ ANTI-PATTERN 2: Silent Failures

```php
// ❌ FALSCH
public function updateCustomer(int $id, array $data): void {
    $result = $wpdb->update('customers', $data, ['id' => $id]);
    // Ignoriert Fehler! $result könnte false sein
}
```

**Lösung**: Immer auf Fehler prüfen:
```php
// ✅ RICHTIG
public function updateCustomer(int $id, array $data): void {
    global $wpdb;
    
    $result = $wpdb->update(
        $wpdb->prefix . 'bookando_customers',
        $data,
        ['id' => $id]
    );
    
    if ($result === false) {
        ActivityLogger::error('customers', 'Update failed', ['id' => $id]);
        throw new \RuntimeException('Failed to update customer');
    }
}
```

---

### ❌ ANTI-PATTERN 3: Catch ohne Re-Throw

```php
// ❌ FALSCH
try {
    $this->doSomethingCritical();
} catch (Exception $e) {
    // Fehler wird verschluckt!
}
```

**Lösung**: Immer Fehler behandeln oder weiterwerfen:
```php
// ✅ RICHTIG
try {
    $this->doSomethingCritical();
} catch (Exception $e) {
    ActivityLogger::error('context', 'Critical operation failed', [
        'error' => $e->getMessage()
    ]);
    throw $e; // Oder neuen Fehler werfen mit Kontext
}
```

---

## Migration-Strategie

Für bestehenden Code:

1. **Phase 1**: Neue Module folgen diesem Guide strikt
2. **Phase 2**: Bei Bug-Fixes existierende Module anpassen
3. **Phase 3**: Großes Refactoring (optional)

---

## Checkliste für Code-Reviews

- [ ] REST-Controller nutzen `WP_Error`
- [ ] Service-Layer wirft `Exceptions`
- [ ] Optional-Returns sind mit `?Type` annotiert
- [ ] Kritische Fehler werden geloggt (`ActivityLogger::error`)
- [ ] Keine stillen Fehler (silent failures)
- [ ] Konsistente Rückgabe-Typen innerhalb einer Methode

---

**Letzte Aktualisierung**: 2025-11-10  
**Autor**: Bookando Development Team
