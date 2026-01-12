# 🏢 Tenant-Provisionierung - Automatische Tenant-Vergabe für SaaS/Cloud/App

## 📋 Übersicht

Das Bookando Tenant-Provisionierungs-System ermöglicht die **automatische Erstellung und Verwaltung von Tenants** bei Lizenz-Käufen über verschiedene Plattformen (SaaS, Cloud, Mobile App).

### Hauptfunktionen

- ✅ **Automatische Tenant-Erstellung** bei Lizenz-Kauf
- ✅ **Cross-Platform Synchronisation** (SaaS ↔ Cloud ↔ Mobile App)
- ✅ **Webhook-Integration** für externe Lizenz-Plattformen (Stripe, Paddle, etc.)
- ✅ **Tenant-Deaktivierung** bei Lizenz-Ablauf oder Kündigung
- ✅ **Strikte Tenant-Isolation** für Datensicherheit
- ✅ **API-Key basierte Authentifizierung** für externe Zugriffe

---

## 🔐 Sicherheit

### Strikte Tenant-Isolation

**WICHTIG:** Das System erzwingt IMMER strikte Tenant-Isolation:

- ✅ **Kein DEV-Modus Bypass** - Auch im Development-Modus werden Tenant-Filter angewendet
- ✅ **Explizites Tenant-Switching** - Entwickler können via `X-BOOKANDO-TENANT` Header Tenants wechseln (erfordert Capability)
- ✅ **Audit-Logging** - Alle Tenant-Zugriffe und -Switches werden geloggt

### API-Key Authentifizierung

Alle Provisionierungs-Requests müssen via API-Key authentifiziert sein:

```php
// wp-config.php
define('BOOKANDO_PROVISIONING_API_KEY', 'your-secure-api-key-here');
```

**Best Practice:** Generieren Sie einen sicheren API-Key:

```bash
openssl rand -hex 32
```

---

## 🚀 Setup & Installation

### 1. Datenbank-Tabelle erstellen

Beim Plugin-Aktivierung wird automatisch die `wp_bookando_tenants` Tabelle erstellt:

```php
// Im Plugin Aktivierungs-Hook
register_activation_hook(__FILE__, function() {
    \Bookando\Core\Tenant\TenantInstaller::install();
    \Bookando\Core\Tenant\TenantInstaller::seedDefaultTenant();
});
```

### 2. REST-API Routen registrieren

In Ihrer Plugin-Hauptdatei:

```php
add_action('rest_api_init', function() {
    \Bookando\Core\Tenant\ProvisioningApi::registerRoutes();
});
```

### 3. API-Key konfigurieren

In `wp-config.php`:

```php
define('BOOKANDO_PROVISIONING_API_KEY', 'bookando_abc123...'); // Ihr sicherer API-Key
```

### 4. Subdomain Multi-Tenant aktivieren (optional)

Für SaaS mit Subdomain-Routing:

```php
// wp-config.php
define('BOOKANDO_SUBDOMAIN_MULTI_TENANT', true);
```

---

## 📡 API-Endpoints

### 1. Tenant erstellen

**POST** `/wp-json/bookando/v1/provisioning/create-tenant`

**Header:**
```
X-Bookando-Provisioning-Key: your-api-key
Content-Type: application/json
```

**Body:**
```json
{
  "company_name": "Firma GmbH",
  "email": "admin@firma.de",
  "license_key": "LICENSE-KEY-12345",
  "platform": "saas",
  "plan": "pro",
  "external_id": "cus_stripe_abc123",
  "subdomain": "firma",
  "metadata": {
    "stripe_customer_id": "cus_abc123",
    "subscription_id": "sub_xyz789"
  }
}
```

**Response:**
```json
{
  "tenant_id": 42,
  "api_key": "bookando_def456...",
  "subdomain": "firma",
  "status": "active"
}
```

**WICHTIG:** Der `api_key` wird NUR EINMAL zurückgegeben! Speichern Sie ihn sicher.

---

### 2. Tenant plattformübergreifend synchronisieren

**POST** `/wp-json/bookando/v1/provisioning/sync-tenant`

**Use Case:** Benutzer kauft SaaS-Lizenz und möchte auch die Mobile App nutzen.

**Body:**
```json
{
  "license_key": "LICENSE-KEY-12345",
  "platform": "app"
}
```

**Response:**
```json
{
  "tenant_id": 42,
  "synced": true
}
```

---

### 3. Tenant deaktivieren

**POST** `/wp-json/bookando/v1/provisioning/deactivate-tenant`

**Body:**
```json
{
  "license_key": "LICENSE-KEY-12345",
  "reason": "expired"
}
```

**Response:**
```json
{
  "deactivated": true
}
```

---

### 4. Webhook-Handler (Generisch)

**POST** `/wp-json/bookando/v1/provisioning/webhook`

Unterstützt folgende Events:
- `license.created` - Neue Lizenz gekauft → Tenant erstellen
- `license.renewed` - Lizenz verlängert → Tenant reaktivieren + Ablaufdatum aktualisieren
- `license.expired` - Lizenz abgelaufen → Tenant deaktivieren
- `license.cancelled` - Lizenz gekündigt → Tenant deaktivieren

**Body:**
```json
{
  "event": "license.created",
  "license_key": "LICENSE-KEY-12345",
  "company_name": "Firma GmbH",
  "email": "admin@firma.de",
  "platform": "saas",
  "plan": "pro",
  "external_id": "cus_stripe_abc123"
}
```

---

## 🔗 Webhook-Integration mit externen Plattformen

### Stripe Integration

Konfigurieren Sie einen Webhook in Stripe:

**URL:** `https://ihr-server.de/wp-json/bookando/v1/provisioning/webhook`

**Events:**
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`

**Webhook-Handler (Beispiel):**

```php
// In Ihrem Webhook-Handler
add_action('stripe_webhook_received', function($event) {
    $provisioning = new \Bookando\Core\Tenant\TenantProvisioner();

    switch ($event['type']) {
        case 'customer.subscription.created':
            $data = [
                'company_name' => $event['data']['object']['metadata']['company_name'],
                'email'        => $event['data']['object']['metadata']['email'],
                'license_key'  => $event['data']['object']['metadata']['license_key'],
                'platform'     => 'saas',
                'plan'         => $event['data']['object']['plan']['nickname'],
                'external_id'  => $event['data']['object']['customer'],
            ];

            $result = $provisioning->createTenant($data);
            // API-Key an Kunden senden (Email, Kundenportal, etc.)
            break;

        case 'customer.subscription.deleted':
            $licenseKey = $event['data']['object']['metadata']['license_key'];
            $tenant = $provisioning->getTenantByLicense($licenseKey);
            $provisioning->deactivateTenant($tenant['id'], 'cancelled');
            break;
    }
});
```

---

## 🌍 Cross-Platform Zugriff

### Szenario: SaaS + Mobile App

1. **Benutzer kauft SaaS-Lizenz**
   ```bash
   POST /provisioning/create-tenant
   → tenant_id: 42
   → api_key: bookando_abc123...
   ```

2. **Benutzer lädt Mobile App herunter**
   - App fordert Login mit License-Key an
   - App ruft auf:
   ```bash
   POST /provisioning/sync-tenant
   {
     "license_key": "LICENSE-KEY-12345",
     "platform": "app"
   }
   → tenant_id: 42 (gleiche wie SaaS!)
   ```

3. **Daten sind plattformübergreifend synchron**
   - Beide Plattformen greifen auf `tenant_id: 42` zu
   - Strikte Isolation zu anderen Tenants

---

## 🔑 Tenant-Switching für Entwickler/Support

### Für Entwickler (im DEV-Modus)

Entwickler können NICHT mehr einfach `BOOKANDO_DEV=true` setzen, um alle Tenants zu sehen.

**Stattdessen: Explizites Tenant-Switching via Header**

```bash
curl -H "X-BOOKANDO-TENANT: 42" \
     -H "X-WP-Nonce: your-nonce" \
     https://ihr-server.de/wp-json/bookando/v1/customers/customers
```

**Erfordert:**
- Eingeloggt als Admin
- Capability: `manage_options` ODER `bookando_switch_tenant`

**Wird geloggt:**
- Jeder Tenant-Switch wird in `ActivityLogger` auditiert
- IP, User-ID, Ziel-Tenant werden protokolliert

---

## 📊 Datenbank-Struktur

### Tabelle: `wp_bookando_tenants`

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT | Tenant-ID (Primary Key) |
| `company_name` | VARCHAR(255) | Firmenname |
| `email` | VARCHAR(255) | Kontakt-Email |
| `license_key` | VARCHAR(255) | Lizenzschlüssel (UNIQUE) |
| `platform` | ENUM | 'saas', 'cloud', 'app' |
| `plan` | ENUM | 'basic', 'pro', 'enterprise', 'lifetime' |
| `external_id` | VARCHAR(255) | Externe ID (z.B. Stripe Customer ID) |
| `subdomain` | VARCHAR(100) | Subdomain für SaaS (UNIQUE) |
| `api_key_hash` | VARCHAR(255) | BCrypt-Hash des API-Keys |
| `status` | ENUM | 'active', 'inactive', 'suspended' |
| `created_at` | DATETIME | Erstellungsdatum |
| `expires_at` | DATETIME | Ablaufdatum (NULL = lifetime) |
| `metadata` | TEXT | JSON-Metadaten |

---

## 🧪 Testing

### Manueller Test: Tenant erstellen

```bash
curl -X POST https://ihr-server.de/wp-json/bookando/v1/provisioning/create-tenant \
  -H "X-Bookando-Provisioning-Key: your-api-key" \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Test GmbH",
    "email": "test@example.com",
    "license_key": "TEST-LICENSE-001",
    "platform": "saas",
    "plan": "basic"
  }'
```

### Subdomain-Test (wenn aktiviert)

```bash
# Zugriff via Subdomain
https://test.ihr-server.de/wp-json/bookando/v1/customers/customers
# → Automatisch tenant_id: 42 (basierend auf Subdomain "test")
```

---

## 🛡️ Best Practices

### 1. API-Key Rotation

Rotieren Sie regelmäßig Ihren Provisioning API-Key:

```php
// Alte wp-config.php
define('BOOKANDO_PROVISIONING_API_KEY', 'old-key');

// Neue wp-config.php
define('BOOKANDO_PROVISIONING_API_KEY', 'new-key');
```

### 2. Rate Limiting

Implementieren Sie Rate Limiting für Provisioning-Endpoints (bereits in `Gate::checkRateLimit()` vorhanden).

### 3. Monitoring

Überwachen Sie `ActivityLogger` für:
- Fehlgeschlagene Authentifizierungen (`provisioning.auth_failed`)
- Tenant-Erstellungen (`tenant.provisioned`)
- Tenant-Deaktivierungen (`tenant.deactivated`)

### 4. Backup

Sichern Sie regelmäßig die `wp_bookando_tenants` Tabelle.

---

## 🔍 Troubleshooting

### Problem: "Tenant not found" beim Sync

**Lösung:** Prüfen Sie, ob `license_key` korrekt ist:

```sql
SELECT * FROM wp_bookando_tenants WHERE license_key = 'LICENSE-KEY-12345';
```

### Problem: API-Key wird abgelehnt

**Lösung:** Prüfen Sie:
1. Ist `BOOKANDO_PROVISIONING_API_KEY` in wp-config.php definiert?
2. Wird der Header `X-Bookando-Provisioning-Key` korrekt gesendet?
3. Stimmt der Key überein? (Case-sensitive!)

### Problem: Subdomain-Routing funktioniert nicht

**Lösung:**
1. Ist `BOOKANDO_SUBDOMAIN_MULTI_TENANT` aktiviert?
2. Ist die Subdomain in `wp_bookando_tenants` eingetragen?
3. Prüfen Sie Option: `get_option('bookando_subdomain_map')`

---

## 📚 Weitere Ressourcen

- **Tenant-Isolation:** Siehe `TenantManager.php`
- **Audit-Logging:** Siehe `ActivityLogger.php`
- **Capabilities:** Siehe `CapabilityService.php`
- **Gate-System:** Siehe `Gate.php`

---

## 💡 Beispiel-Workflows

### Workflow 1: SaaS-Kunde kauft Lizenz

1. Kunde kauft in Ihrem Shop → Stripe Webhook
2. Webhook ruft `POST /provisioning/webhook` auf
3. Tenant wird automatisch erstellt
4. API-Key wird per Email an Kunden gesendet
5. Kunde loggt sich via Subdomain ein: `https://firma.ihr-saas.de`

### Workflow 2: SaaS-Kunde lädt Mobile App

1. Kunde gibt License-Key in App ein
2. App ruft `POST /provisioning/sync-tenant` auf
3. Tenant wird mit Platform "app" verknüpft
4. Kunde hat plattformübergreifenden Zugriff

### Workflow 3: Lizenz läuft ab

1. Stripe sendet `customer.subscription.deleted` Webhook
2. Webhook ruft `POST /provisioning/deactivate-tenant` auf
3. Tenant wird auf Status "inactive" gesetzt
4. Kunde verliert Zugriff (via `Gate::evaluate()`)

---

**Viel Erfolg mit Ihrem Multi-Tenant SaaS! 🚀**
