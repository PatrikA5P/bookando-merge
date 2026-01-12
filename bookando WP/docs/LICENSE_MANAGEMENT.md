# 🔐 Lizenz-Management - Best Practice für SaaS/Cloud/App

## 📋 Übersicht

Bookando hat **zwei Lizenz-Systeme**, die parallel (oder separat) betrieben werden können:

| System | Verwendung | Speicherort | Best für |
|--------|------------|-------------|----------|
| **LicenseGuard** (NEU) | Tenant-basiert, Multi-Tenant SaaS | `wp_bookando_tenants` | SaaS, Cloud, Mobile Apps |
| **LicenseManager** (ALT) | Modul-basiert, Single-Tenant | `wp_options` | Single-Site WordPress |

---

## 🚀 Empfehlung: Welches System für Sie?

### Szenario 1: **Multi-Tenant SaaS** (z.B. bookando.app)
✅ **Nutzen Sie: LicenseGuard**
- Jeder Tenant hat eigene Lizenz
- Zentrale Verwaltung via `wp_bookando_tenants`
- Cross-Platform Synchronisation (SaaS ↔ Cloud ↔ App)
- Automatische Provisionierung via API

### Szenario 2: **Single-Tenant WordPress-Plugin** (klassisch)
✅ **Nutzen Sie: LicenseManager**
- Eine Lizenz pro WordPress-Installation
- Modulbasierte Freischaltung
- Grace Period basierend auf Installation
- Kompatibel mit bestehenden Setups

### Szenario 3: **Hybrid** (Migration von ALT → NEU)
✅ **Nutzen Sie: LicenseIntegration**
- Beide Systeme parallel aktiv
- Automatische Migrations-Unterstützung
- Fallback für alte Instanzen

---

## 🔐 LicenseGuard (NEU) - Tenant-basiert

### Setup & Aktivierung

#### 1. Datenbank-Tabelle erstellen

```php
// Plugin-Aktivierung
\Bookando\Core\Tenant\TenantInstaller::install();
\Bookando\Core\Tenant\TenantInstaller::seedDefaultTenant();
```

#### 2. Middleware registrieren (automatische Lizenz-Prüfung)

```php
// In Ihrer Plugin-Hauptdatei oder init Hook
add_action('init', function() {
    \Bookando\Core\Licensing\LicenseMiddleware::register();
});
```

#### 3. DEV-Bypass konfigurieren (nur für Entwicklung!)

**Option A: wp-config.php (empfohlen für lokale Dev)**
```php
// wp-config.php
define('BOOKANDO_DEV_BYPASS', true);  // NUR in local/development/staging!
```

**Option B: Capability-basiert (empfohlen für Support/Admin)**
```php
// Vergeben Sie Capability an Admins/Support
$user = get_user_by('email', 'admin@bookando.app');
$user->add_cap('bookando_dev_bypass');
```

**Sicherheit:**
- `BOOKANDO_DEV_BYPASS` wird in Production **automatisch blockiert**
- Alle Bypasses werden in `ActivityLogger` auditiert

---

### Verwendung im Code

#### Lizenz-Status prüfen

```php
use Bookando\Core\Licensing\LicenseGuard;

// Einfache Prüfung: Hat Tenant gültige Lizenz?
if (!LicenseGuard::hasValidLicense()) {
    wp_die('Ihre Lizenz ist abgelaufen.');
}

// Feature-Prüfung: Hat Tenant bestimmtes Feature?
if (!LicenseGuard::hasFeature('advanced_reports')) {
    return new WP_Error('feature_locked', 'Dieses Feature ist in Ihrem Plan nicht enthalten.');
}

// Plan abrufen
$plan = LicenseGuard::getCurrentPlan(); // 'basic', 'pro', 'enterprise', 'lifetime'

// Verbleibende Tage bis Ablauf
$daysLeft = LicenseGuard::getDaysUntilExpiry();
if ($daysLeft !== null && $daysLeft < 30) {
    echo "Ihre Lizenz läuft in {$daysLeft} Tagen ab!";
}

// Grace Period Check
if (LicenseGuard::isInGracePeriod()) {
    echo "Sie befinden sich in der Grace Period (7 Tage nach Ablauf).";
}

// Vollständige Lizenz-Info (für Dashboard)
$info = LicenseGuard::getLicenseInfo();
/*
Array (
    'is_valid' => true,
    'plan' => 'pro',
    'expires_at' => '2025-12-31 23:59:59',
    'days_remaining' => 180,
    'is_grace_period' => false,
    'features' => ['api_access', 'advanced_reports', ...]
)
*/
```

---

### Features pro Plan (Standard)

```php
'basic' => [
    'api_access',
    'basic_reports',
    'single_user',
    'email_support',
]

'pro' => [
    'api_access',
    'basic_reports',
    'advanced_reports',
    'multi_user',
    'priority_support',
    'webhooks',
    'custom_branding',
]

'enterprise' => [
    ... (alle Pro Features) ...
    'sso',
    'advanced_security',
    'dedicated_support',
    'sla_guarantee',
    'white_label',
]

'lifetime' => [
    ... (wie Pro, aber kein Ablaufdatum) ...
]
```

**Custom Features hinzufügen:**
```php
add_filter('bookando_plan_features', function($features, $plan) {
    $features['custom_plan'] = [
        'api_access',
        'my_custom_feature',
    ];
    return $features;
}, 10, 2);
```

---

### REST-API Middleware (automatisch)

Die `LicenseMiddleware` prüft **automatisch ALLE Bookando REST-Requests** (`/bookando/v1/*`):

```php
// Wird automatisch geprüft:
GET /bookando/v1/customers/customers  → Lizenz muss gültig sein

// Öffentliche Endpoints (keine Prüfung):
POST /bookando/v1/auth/login         → OK
POST /bookando/v1/provisioning/webhook  → OK
```

**Eigenen öffentlichen Endpoint hinzufügen:**
```php
\Bookando\Core\Licensing\LicenseMiddleware::addPublicEndpoint('/bookando/v1/my-public-endpoint');
```

**Bei ungültiger Lizenz:**
```json
HTTP/1.1 402 Payment Required
{
  "code": "license_expired",
  "message": "Ihre Lizenz ist seit 10 Tagen abgelaufen. Bitte erneuern Sie Ihre Lizenz.",
  "data": {
    "license_status": {
      "is_valid": false,
      "plan": "pro",
      "expires_at": "2025-01-01 00:00:00",
      "days_remaining": -10
    },
    "renewal_url": "https://bookando.app/pricing"
  }
}
```

---

### Grace Period (7 Tage)

**Nach Lizenz-Ablauf haben Tenants noch 7 Tage Zugriff:**

```php
// Beispiel: Lizenz läuft am 1. Januar ab
// → Zugriff bis 8. Januar (7 Tage Grace Period)

// Während Grace Period werden Warn-Header gesetzt:
X-Bookando-License-Grace-Period: true
X-Bookando-License-Grace-Days: 5  // Verbleibende Grace-Tage
```

**Im Frontend anzeigen:**
```javascript
fetch('/wp-json/bookando/v1/customers/customers')
  .then(response => {
    if (response.headers.get('X-Bookando-License-Grace-Period') === 'true') {
      const daysLeft = response.headers.get('X-Bookando-License-Grace-Days');
      alert(`Warnung: Ihre Lizenz läuft in ${daysLeft} Tagen ab!`);
    }
  });
```

---

## 🔧 LicenseManager (ALT) - Modul-basiert

### Verwendung (Backward-Compatibility)

```php
use Bookando\Core\Licensing\LicenseManager;

// Modul-Prüfung
if (!LicenseManager::isModuleAllowed('customers')) {
    return new WP_Error('module_locked', 'Modul nicht in Lizenz enthalten.');
}

// Feature-Prüfung
if (!LicenseManager::isFeatureEnabled('rest_api_write')) {
    return new WP_Error('feature_locked', 'REST-API Schreibzugriff nicht freigeschaltet.');
}

// Plan abrufen
$plan = LicenseManager::getLicensePlan(); // 'starter', 'pro', 'education'

// Grace Period (30 Tage ab Installation)
$hasValidLicense = LicenseManager::hasValidLicense();
// → true innerhalb 30 Tage, auch ohne Lizenz
```

**DEV-Modus (ALT):**
```php
// wp-config.php
define('BOOKANDO_DEV', true);  // ALLE Module/Features erlaubt!
```

---

## 🔄 Integration: ALT ↔ NEU

### LicenseIntegration - Beide Systeme parallel

```php
use Bookando\Core\Licensing\LicenseIntegration;

// Prüft automatisch: Neues System ODER Altes System
if (!LicenseIntegration::hasValidLicense()) {
    wp_die('Keine gültige Lizenz');
}

if (!LicenseIntegration::hasFeature('advanced_reports')) {
    return new WP_Error('feature_locked', 'Feature nicht verfügbar');
}

$plan = LicenseIntegration::getCurrentPlan();
```

**Logik:**
1. Prüft zuerst `wp_bookando_tenants` (neues System)
2. Falls nicht vorhanden → Fallback zu `wp_options` (altes System)
3. Transparent für Entwickler

---

### Migration: ALT → NEU

**Einmalig nach Plugin-Update aufrufen:**

```php
$result = \Bookando\Core\Licensing\LicenseIntegration::migrateOldLicenseToTenant();

if ($result['success']) {
    echo $result['message']; // "Lizenz erfolgreich migriert zu Tenant-ID 1"
} else {
    echo 'Fehler: ' . $result['message'];
}
```

**Was passiert:**
- Liest `bookando_license_data` aus `wp_options`
- Erstellt Eintrag in `wp_bookando_tenants`
- Speichert Backup in `bookando_license_data_migrated`
- Markiert Migrations-Datum

---

## 💡 Best Practices

### 1. Lizenz-Prüfung in REST-Endpoints

**❌ NICHT SO:**
```php
public static function myEndpoint(WP_REST_Request $request) {
    // Keine Lizenz-Prüfung!
    return Response::ok(['data' => 'sensitive']);
}
```

**✅ SO:**
```php
public static function myEndpoint(WP_REST_Request $request) {
    // Automatisch via Middleware geprüft (empfohlen)
    // ODER manuell:
    if (!LicenseGuard::hasValidLicense()) {
        return Response::error(['code' => 'license_invalid'], 402);
    }

    // Feature-spezifisch:
    if (!LicenseGuard::hasFeature('advanced_reports')) {
        return Response::error(['code' => 'feature_locked'], 402);
    }

    return Response::ok(['data' => 'sensitive']);
}
```

---

### 2. DEV-Bypass für Entwicklung

**❌ NICHT SO (unsicher!):**
```php
// wp-config.php (Production!)
define('BOOKANDO_DEV_BYPASS', true);  // ⚠️ GEFÄHRLICH!
```

**✅ SO (sicher):**
```php
// wp-config.php (nur für local/staging!)
if (in_array($_SERVER['HTTP_HOST'], ['localhost', 'dev.bookando.local'])) {
    define('BOOKANDO_DEV_BYPASS', true);
}
```

**✅ ODER (Capability-basiert):**
```php
// Nur für Support/Admin
add_action('init', function() {
    if (current_user_can('manage_options')) {
        $user = wp_get_current_user();
        $user->add_cap('bookando_dev_bypass');
    }
});
```

---

### 3. Frontend: Lizenz-Status anzeigen

```vue
<template>
  <div v-if="licenseInfo.is_valid">
    <p>Plan: {{ licenseInfo.plan }}</p>
    <p v-if="licenseInfo.days_remaining">
      Verbleibend: {{ licenseInfo.days_remaining }} Tage
    </p>
    <p v-if="licenseInfo.is_grace_period" class="warning">
      ⚠️ Grace Period aktiv! Bitte erneuern Sie Ihre Lizenz.
    </p>
  </div>
  <div v-else class="error">
    ❌ Lizenz ungültig. <a :href="renewalUrl">Jetzt erneuern</a>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const licenseInfo = ref({});
const renewalUrl = ref('');

onMounted(async () => {
  const response = await fetch('/wp-json/bookando/v1/license/status');
  const data = await response.json();
  licenseInfo.value = data.license_status;
  renewalUrl.value = data.renewal_url;
});
</script>
```

**Endpoint für License-Status erstellen:**
```php
// In ProvisioningApi oder separate LicenseApi
register_rest_route('bookando/v1', '/license/status', [
    'methods' => 'GET',
    'callback' => function() {
        return [
            'license_status' => LicenseGuard::getLicenseInfo(),
            'renewal_url' => 'https://bookando.app/pricing',
        ];
    },
    'permission_callback' => '__return_true', // Öffentlich
]);
```

---

### 4. Webhook-Integration für Lizenz-Updates

```php
// Stripe Webhook: Lizenz-Ablauf
add_action('stripe_subscription_cancelled', function($subscription) {
    $licenseKey = $subscription['metadata']['license_key'];
    $tenant = (new \Bookando\Core\Tenant\TenantProvisioner())->getTenantByLicense($licenseKey);

    if ($tenant) {
        // Deaktiviere Tenant
        $provisioner->deactivateTenant((int) $tenant['id'], 'cancelled');

        // Sende Email-Benachrichtigung
        wp_mail(
            $tenant['email'],
            'Lizenz gekündigt',
            'Ihre Bookando-Lizenz wurde gekündigt. Sie haben noch 7 Tage Zugriff (Grace Period).'
        );
    }
});
```

---

## 🛡️ Sicherheit

### Zugriffskontrolle

**Strikte Regeln:**
1. ✅ **Alle REST-Endpoints** müssen Lizenz prüfen (via Middleware)
2. ✅ **Keine Umgehung via Umgebungsvariablen** (außer non-production)
3. ✅ **Audit-Logging** für alle Lizenz-Checks
4. ✅ **Grace Period** nur 7 Tage (nicht unbegrenzt)

### Audit-Logging

Alle Lizenz-Ereignisse werden geloggt:

```php
// Automatisch geloggt:
- Lizenz-Prüfung fehlgeschlagen → ActivityLogger::warning('license.check_failed')
- DEV-Bypass verwendet → ActivityLogger::warning('license.dev_bypass_used')
- Grace Period aktiv → ActivityLogger::warning('license.grace_period')
- DEV-Bypass in Production blockiert → ActivityLogger::critical('license.dev_bypass_blocked')
```

**Logs abrufen:**
```php
$logs = \Bookando\Core\Service\ActivityLogger::getRecentByContext('license');
```

---

## 🧪 Testing

### Unit-Tests

```php
use Bookando\Core\Licensing\LicenseGuard;

class LicenseGuardTest extends WP_UnitTestCase
{
    public function test_valid_license()
    {
        // Tenant mit gültiger Lizenz erstellen
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'bookando_tenants', [
            'id' => 1,
            'license_key' => 'TEST-KEY',
            'status' => 'active',
            'plan' => 'pro',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            // ... weitere Felder
        ]);

        $this->assertTrue(LicenseGuard::hasValidLicense(1));
        $this->assertTrue(LicenseGuard::hasFeature('advanced_reports', 1));
        $this->assertEquals('pro', LicenseGuard::getCurrentPlan(1));
    }

    public function test_expired_license()
    {
        // Tenant mit abgelaufener Lizenz
        $wpdb->insert($wpdb->prefix . 'bookando_tenants', [
            'id' => 2,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            // ...
        ]);

        $this->assertFalse(LicenseGuard::hasValidLicense(2));
    }

    public function test_grace_period()
    {
        // Lizenz abgelaufen, aber in Grace Period (< 7 Tage)
        $wpdb->insert($wpdb->prefix . 'bookando_tenants', [
            'id' => 3,
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            // ...
        ]);

        $this->assertTrue(LicenseGuard::hasValidLicense(3)); // Noch gültig!
        $this->assertTrue(LicenseGuard::isInGracePeriod(3));
    }
}
```

---

## 📊 Monitoring & Analytics

### Empfohlene Metriken

```php
// Täglicher Cronjob: Lizenz-Status prüfen
add_action('bookando_daily_license_check', function() {
    global $wpdb;
    $table = $wpdb->prefix . 'bookando_tenants';

    // Ablaufende Lizenzen (< 30 Tage)
    $expiring = $wpdb->get_results("
        SELECT id, email, expires_at, plan
        FROM {$table}
        WHERE status = 'active'
          AND expires_at IS NOT NULL
          AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
    ");

    foreach ($expiring as $tenant) {
        // Email-Benachrichtigung senden
        wp_mail(
            $tenant->email,
            'Lizenz läuft bald ab',
            "Ihre {$tenant->plan}-Lizenz läuft am {$tenant->expires_at} ab."
        );
    }

    // Abgelaufene Lizenzen in Grace Period
    $gracePeriod = $wpdb->get_results("
        SELECT id, email
        FROM {$table}
        WHERE status = 'active'
          AND expires_at < NOW()
          AND expires_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");

    // Statistiken loggen
    ActivityLogger::info('license.daily_check', 'Täglicher Lizenz-Check', [
        'expiring_soon' => count($expiring),
        'grace_period' => count($gracePeriod),
    ]);
});
```

---

## 🔗 Siehe auch

- [TENANT_PROVISIONING.md](./TENANT_PROVISIONING.md) - Automatische Tenant-Erstellung
- [Gate.php](../src/Core/Auth/Gate.php) - Authentifizierung & Autorisierung
- [TenantManager.php](../src/Core/Tenant/TenantManager.php) - Tenant-Verwaltung

---

**Viel Erfolg mit Ihrem Lizenz-Management-System! 🚀**
