# 📚 API Best Practices für Bookando

## 🎯 Übersicht

Dieses Dokument definiert Best Practices für API-Implementierungen in Bookando.

---

## 🏗️ Backend: REST Handler

### ✅ **RICHTIG: Response::ok() verwenden**

```php
// RestHandler.php
public static function customers($params, WP_REST_Request $request): WP_REST_Response
{
    $service = new CustomerService();
    $result = $service->listCustomers($query, $tenantId);

    // ✅ Standardisiertes Format
    return Response::ok($result);
}
```

**Result:**
```json
{
  "data": {
    "data": [...],
    "total": 142,
    "limit": 50,
    "offset": 0
  },
  "meta": {
    "success": true
  }
}
```

---

### ❌ **FALSCH: Direktes rest_ensure_response()**

```php
// ❌ Legacy-Approach (inkonsistent)
$response = rest_ensure_response([
    'data' => $rows,
    'total' => $total
]);
return $response;
```

**Problem:** Keine standardisierte Fehlerbehandlung, keine Meta-Informationen.

---

## 🎨 Frontend: API Layer

### ✅ **RICHTIG: Vertraue dem Response Interceptor**

Der HTTP-Client (`src/assets/http/client.ts`) unwrappt automatisch das Response::ok() Format.

```typescript
// CustomersApi.ts
export async function getCustomers(params: CustomersQuery = {}) {
  const res = await http.get<any[]>('customers', params)

  // ✅ Interceptor hat bereits unwrapped
  // res.data = { data: [...], total: 142, limit: 50, offset: 0 }
  return Array.isArray(res.data) ? res.data : (res.data?.data ?? [])
}
```

**Was der Interceptor macht:**
```typescript
// VORHER (Backend):  { data: { data: [...], total: 142 }, meta: {...} }
// NACHHER (Frontend): { data: [...], total: 142 }
```

---

### ❌ **FALSCH: Manuelles Unwrapping**

```typescript
// ❌ Nicht mehr nötig!
if (res.data && typeof res.data === 'object' && 'data' in res.data) {
  return res.data.data  // Interceptor macht das schon
}
```

---

## 🗄️ Datenbank: Optionale Felder

### Prinzip: **Sparse Data**

Nicht alle Felder müssen in der DB existieren. Viele Felder werden durch Systemeinstellungen definiert.

**In DB gespeichert:**
- Core-Felder: `id`, `first_name`, `last_name`, `email`, `status`, `created_at`
- Wichtige Business-Felder: `phone`, `address`, `birthdate`

**Nur in Settings/Formular:**
- Custom Fields (benutzerdefiniert)
- Temporäre Formular-Felder
- UI-spezifische Felder

### Beispiel: Customer

```typescript
// CustomerRepository.php gibt nur DB-Felder zurück
const dbCustomer = {
  id: 1,
  first_name: 'Max',
  last_name: 'Mustermann',
  email: 'max@example.com',
  // Keine custom fields hier!
}

// Frontend merged mit Settings
const fullCustomer = {
  ...dbCustomer,
  ...customFields,  // aus FormRules/Settings geladen
  _computed: {      // UI-spezifisch
    displayName: `${dbCustomer.first_name} ${dbCustomer.last_name}`
  }
}
```

---

## 📝 Validierung & FormRules

### Backend: Whitelist-Approach

```php
// CustomerService.php
public function createCustomer(array $payload, int $tenantId)
{
    // ✅ Nur definierte Felder durchlassen
    $result = $this->validator->validateCreate($payload);
    $data = $result->data();  // nur whitelisted fields

    // Custom Fields separat behandeln
    $customFields = $this->extractCustomFields($payload);

    return $this->repository->insert($data);
}
```

### Frontend: FormRules aus Settings

```typescript
// CustomersForm.vue
const formRules = await loadFormRules('customers')

// Dynamische Felder basierend auf Rules
const fields = computed(() => {
  return Object.keys(formRules).map(key => ({
    name: key,
    type: formRules[key].type,
    required: formRules[key].required,
    // ...weitere Rules
  }))
})
```

---

## 🔄 CRUD-Operationen: Standardmuster

### 1. **List** (GET /customers)

```php
// Backend: RestHandler.php
return Response::ok([
    'data'   => $rows,
    'total'  => $total,
    'limit'  => $limit,
    'offset' => $offset
]);
```

```typescript
// Frontend: CustomersApi.ts
export async function getCustomers(params: CustomersQuery = {}) {
  const res = await http.get<any[]>('customers', params)
  return Array.isArray(res.data) ? res.data : (res.data?.data ?? [])
}
```

---

### 2. **Get** (GET /customers/{id})

```php
// Backend
return Response::ok($customer);
```

```typescript
// Frontend
export async function getCustomer(id: number) {
  const res = await http.get<any>(`customers/${id}`)
  return res.data
}
```

---

### 3. **Create** (POST /customers)

```php
// Backend
$id = $service->createCustomer($payload, $tenantId);
return Response::created(['id' => $id]);
```

```typescript
// Frontend
export async function createCustomer(data: Customer) {
  const res = await http.post<any>('customers', toPayload(data))
  return res.data  // { id: 123 }
}
```

---

### 4. **Update** (PUT /customers/{id})

```php
// Backend
$service->updateCustomer($id, $payload, $tenantId);
return Response::updated();
```

```typescript
// Frontend
export async function updateCustomer(id: number, data: Customer) {
  const res = await http.put<any>(`customers/${id}`, toPayload(data))
  return res.data  // { updated: true }
}
```

---

### 5. **Delete** (DELETE /customers/{id})

```php
// Backend
$hard = (bool) $request->get_param('hard');
$service->deleteCustomer($id, $hard, $tenantId);
return Response::deleted($hard);
```

```typescript
// Frontend
export async function deleteCustomer(id: number, opts: { hard?: boolean } = {}) {
  const query = opts.hard ? { hard: 1 } : undefined
  const res = await http.del<any>(`customers/${id}`, query)
  return res.data  // { deleted: true, hard: false }
}
```

---

## 🛡️ Fehlerbehandlung

### Backend

```php
// Service-Layer
if (!$valid) {
    return new WP_Error('validation_error', 'Ungültige Daten', ['status' => 400]);
}

// RestHandler
if (is_wp_error($result)) {
    return Response::error($result);
}
```

**Error Response:**
```json
{
  "data": null,
  "error": {
    "code": "validation_error",
    "message": "Ungültige Daten",
    "details": { ... }
  },
  "meta": {
    "success": false,
    "status": 400
  }
}
```

### Frontend

```typescript
try {
  const customer = await createCustomer(data)
  notify('success', t('messages.save_success'))
} catch (error) {
  // Axios Error Interceptor normalisiert Errors
  notify('danger', error.message || t('messages.save_error'))
}
```

---

## ✅ Checkliste: Neues Modul implementieren

### Backend

- [ ] RestHandler nutzt `Response::ok()` / `Response::error()`
- [ ] Service-Layer gibt strukturierte Daten zurück (Arrays/Objects)
- [ ] Repository nutzt Whitelist für INSERT/UPDATE
- [ ] Validierung mit FormRules-Integration
- [ ] Tenant-Isolation implementiert
- [ ] Soft-Delete/Hard-Delete unterschieden

### Frontend

- [ ] API-File nutzt http.get/post/put/del aus `@assets/http`
- [ ] Keine manuelle Response-Unwrapping (Interceptor macht das)
- [ ] Fallback für Legacy-Format: `res.data?.data ?? []`
- [ ] TypeScript-Typen definiert
- [ ] Store nutzt strukturierte API-Calls
- [ ] Custom Fields aus Settings geladen, nicht aus DB

---

## 📖 Weiterführende Dokumentation

- `/src/Core/Api/Response.php` - Response Helper
- `/src/assets/http/client.ts` - HTTP-Client mit Interceptors
- `/src/types/api.d.ts` - TypeScript Response-Typen
- `/src/Core/Settings/FormRules.php` - Formular-Validierung
