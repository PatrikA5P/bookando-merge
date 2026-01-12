# 🔐 Lizenz-System - Quick Start

## Setup für Ihre Plugin-Hauptdatei

```php
<?php
/**
 * Plugin Name: Bookando
 * Description: Multi-Tenant Buchungssystem
 */

// 1. Plugin-Aktivierung: Datenbank-Tabellen erstellen
register_activation_hook(__FILE__, function() {
    // Tenant-Tabelle erstellen
    \Bookando\Core\Tenant\TenantInstaller::install();
    \Bookando\Core\Tenant\TenantInstaller::seedDefaultTenant();
});

// 2. REST-API: Provisionierung & Lizenz-Status
add_action('rest_api_init', function() {
    // Provisionierungs-API (für externe Lizenz-Plattformen)
    \Bookando\Core\Tenant\ProvisioningApi::registerRoutes();

    // Lizenz-Status Endpoint (öffentlich, für Dashboard)
    register_rest_route('bookando/v1', '/license/status', [
        'methods' => 'GET',
        'callback' => function() {
            return [
                'license_status' => \Bookando\Core\Licensing\LicenseGuard::getLicenseInfo(),
                'renewal_url' => 'https://bookando.app/pricing',
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});

// 3. Lizenz-Middleware: Automatische Prüfung für alle REST-Requests
add_action('init', function() {
    \Bookando\Core\Licensing\LicenseMiddleware::register();
});

// 4. Optional: Migration von altem Lizenz-System
add_action('admin_init', function() {
    // Nur einmal ausführen
    if (get_option('bookando_license_migrated')) {
        return;
    }

    $result = \Bookando\Core\Licensing\LicenseIntegration::migrateOldLicenseToTenant();

    if ($result['success']) {
        update_option('bookando_license_migrated', true);
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Lizenz erfolgreich migriert!</p></div>';
        });
    }
});
```

---

## wp-config.php für Entwicklung

```php
<?php
// Umgebung definieren
define('WP_ENVIRONMENT_TYPE', 'local'); // 'local', 'development', 'staging', 'production'

// DEV-Bypass (NUR in non-production!)
if (WP_ENVIRONMENT_TYPE !== 'production') {
    define('BOOKANDO_DEV_BYPASS', true);  // Lizenz-Prüfung überspringen
}

// API-Key für Provisionierung (Production)
define('BOOKANDO_PROVISIONING_API_KEY', 'your-secure-api-key-here');

// Optional: Subdomain Multi-Tenant
define('BOOKANDO_SUBDOMAIN_MULTI_TENANT', true);

// Optional: Custom Renewal-URL
define('BOOKANDO_RENEWAL_URL', 'https://bookando.app/renew');
```

---

## Verwendung in Ihrem Code

### In REST-Endpoints

```php
namespace Bookando\Modules\customers;

use Bookando\Core\Licensing\LicenseGuard;

class RestHandler
{
    public static function getCustomers(WP_REST_Request $request)
    {
        // Lizenz-Prüfung (optional, Middleware prüft automatisch)
        if (!LicenseGuard::hasValidLicense()) {
            return Response::error(['code' => 'license_invalid'], 402);
        }

        // Feature-Prüfung
        if (!LicenseGuard::hasFeature('api_access')) {
            return Response::error(['code' => 'feature_locked'], 402);
        }

        // Ihre Logik...
        return Response::ok(['customers' => [...]]);
    }
}
```

### In Admin-Pages

```php
add_action('admin_menu', function() {
    add_menu_page(
        'Advanced Reports',
        'Reports',
        'manage_options',
        'bookando-reports',
        function() {
            // Feature-Prüfung
            if (!\Bookando\Core\Licensing\LicenseGuard::hasFeature('advanced_reports')) {
                echo '<div class="notice notice-error">';
                echo '<p>Dieses Feature ist in Ihrem Plan nicht enthalten.</p>';
                echo '<p><a href="https://bookando.app/upgrade">Jetzt upgraden</a></p>';
                echo '</div>';
                return;
            }

            // Report anzeigen...
            echo '<h1>Advanced Reports</h1>';
        }
    );
});
```

### In Vue-Komponenten

```vue
<template>
  <div>
    <button
      v-if="hasFeature('webhooks')"
      @click="configureWebhook"
    >
      Webhook konfigurieren
    </button>
    <div v-else class="upgrade-notice">
      🔒 Webhooks sind in Ihrem Plan nicht verfügbar.
      <a href="/upgrade">Jetzt upgraden</a>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const hasFeature = ref(false);

onMounted(async () => {
  const response = await fetch('/wp-json/bookando/v1/license/status');
  const data = await response.json();
  hasFeature.value = data.license_status.features.includes('webhooks');
});
</script>
```

---

## Testen

### DEV-Bypass aktivieren (temporär)

```php
// wp-config.php
define('BOOKANDO_DEV_BYPASS', true);
```

### Capability-basierter Bypass

```php
// Fügen Sie Capability zu Ihrem User hinzu
$user = wp_get_current_user();
$user->add_cap('bookando_dev_bypass');
```

### Unit-Tests

```php
public function setUp(): void
{
    parent::setUp();

    // Tenant mit Lizenz erstellen
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'bookando_tenants', [
        'id' => 1,
        'company_name' => 'Test Company',
        'email' => 'test@example.com',
        'license_key' => 'TEST-KEY-123',
        'platform' => 'saas',
        'plan' => 'pro',
        'status' => 'active',
        'created_at' => current_time('mysql'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
        'subdomain' => 'test',
        'api_key_hash' => password_hash('test-key', PASSWORD_BCRYPT),
        'metadata' => '{}',
    ]);
}
```

---

## Troubleshooting

### Problem: "Lizenz ungültig" trotz gültiger Lizenz

**Lösung 1: Cache leeren**
```php
\Bookando\Core\Licensing\LicenseGuard::clearCache();
```

**Lösung 2: Tabelle prüfen**
```sql
SELECT * FROM wp_bookando_tenants WHERE id = 1;
```

**Lösung 3: DEV-Bypass aktivieren (temporär)**
```php
// wp-config.php
define('BOOKANDO_DEV_BYPASS', true);
```

---

### Problem: REST-API gibt 402 zurück

**Ursache:** Lizenz ist abgelaufen oder ungültig

**Lösung 1: Grace Period prüfen**
```php
$info = \Bookando\Core\Licensing\LicenseGuard::getLicenseInfo();
var_dump($info);
```

**Lösung 2: Endpoint als öffentlich markieren**
```php
\Bookando\Core\Licensing\LicenseMiddleware::addPublicEndpoint('/bookando/v1/my-endpoint');
```

---

### Problem: Migration schlägt fehl

**Ursache:** Tabelle existiert nicht oder alte Lizenz nicht gefunden

**Lösung:**
```php
// 1. Tabelle erstellen
\Bookando\Core\Tenant\TenantInstaller::install();

// 2. Migration manuell ausführen
$result = \Bookando\Core\Licensing\LicenseIntegration::migrateOldLicenseToTenant();
var_dump($result);
```

---

## Weitere Dokumentation

- [LICENSE_MANAGEMENT.md](../../../docs/LICENSE_MANAGEMENT.md) - Vollständige Dokumentation
- [TENANT_PROVISIONING.md](../../../docs/TENANT_PROVISIONING.md) - Automatische Tenant-Vergabe

---

**Support:** https://bookando.app/support
**Docs:** https://docs.bookando.app
