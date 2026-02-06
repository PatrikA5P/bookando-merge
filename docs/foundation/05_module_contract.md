# Phase 4C — Canonical Module Contract

> Verbindliche Regeln für die Integration von Modulen in den Platform Kernel.

---

## 1. Module Manifest Schema

Jedes Modul MUSS eine `module.json` im Root-Verzeichnis haben:

```json
{
  "slug": "appointments",
  "version": "1.0.0",
  "name": "Appointments",
  "description": "Terminbuchung und Zeitachsen-Management",
  "plan": "starter",
  "features_required": ["appointments_basic"],
  "capabilities": [
    "manage_bookando_appointments",
    "view_bookando_appointments"
  ],
  "routes": {
    "prefix": "/appointments",
    "handlers": ["AppointmentRestHandler"]
  },
  "migrations": [
    "001_create_appointments_table",
    "002_add_status_index"
  ],
  "ui": {
    "admin_entry": "assets/vue/main.ts",
    "public_entry": null
  },
  "events": {
    "publishes": [
      "appointment.created",
      "appointment.cancelled",
      "appointment.rescheduled"
    ],
    "subscribes": [
      "payment.succeeded",
      "employee.schedule_changed"
    ]
  },
  "dependencies": {
    "kernel": ">=1.0.0",
    "modules": []
  },
  "permissions": {
    "default_roles": {
      "admin": ["manage_bookando_appointments", "view_bookando_appointments"],
      "manager": ["manage_bookando_appointments", "view_bookando_appointments"],
      "employee": ["view_bookando_appointments"]
    }
  },
  "settings": {
    "namespace": "appointments",
    "schema": "settings.schema.json"
  },
  "audit": {
    "tracked_actions": ["create", "update", "cancel", "reschedule"]
  }
}
```

---

## 2. Erlaubte Abhängigkeiten

### Regel: Module dürfen NUR vom Kernel abhängen, NICHT von anderen Modulen.

```
ERLAUBT:
  Module → Kernel Ports (PersistencePort, QueuePort, etc.)
  Module → Kernel Domain (Value Objects: Money, TenantId, TimeRange)
  Module → Kernel Contracts (Event Schemas, Validation)
  Module → Kernel Application (Command/Query Bus)

VERBOTEN:
  Module A → Module B (direkte Klassenreferenz)
  Module → WordPress Functions
  Module → Framework-spezifische Klassen
  Module → globale Variablen ($wpdb, $_SERVER, $_POST)
```

### Inter-Modul-Kommunikation:
- Über **Domain Events** (EventBusPort)
- Über **Kernel-definierte Contracts** (Shared Interfaces)
- NIEMALS über direkte Klassen-Imports

**Aktueller Verstoß** (zu beheben):
- `src/modules/Academy/FinanceIntegration.php` → importiert Finance-Modul direkt
- `src/modules/Offers/AcademyEnrollmentHandler.php` → importiert Academy-Modul direkt

---

## 3. Service Registration / DI

### Registrierung über ModuleServiceProvider:

Jedes Modul MUSS einen `ServiceProvider` bereitstellen:

```php
namespace Bookando\Modules\Appointments;

use Bookando\Kernel\Contracts\ModuleServiceProviderInterface;
use Bookando\Kernel\Container\ContainerInterface;

class ServiceProvider implements ModuleServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(
            AppointmentRepository::class,
            fn($c) => new AppointmentRepository($c->get(PersistencePort::class))
        );

        $container->singleton(
            CreateAppointmentHandler::class,
            fn($c) => new CreateAppointmentHandler(
                $c->get(AppointmentRepository::class),
                $c->get(DoubleBookingPolicy::class),
                $c->get(EventBusPort::class)
            )
        );
    }

    public function boot(ContainerInterface $container): void
    {
        // Register event listeners
        $eventBus = $container->get(EventBusPort::class);
        $eventBus->subscribe('payment.succeeded', PaymentReceivedListener::class);
    }
}
```

### Regeln:
- Keine `new` in Modul-Code außerhalb des ServiceProviders
- Keine statischen Aufrufe auf Kernel-Services (immer via DI)
- Keine Singleton-Pattern innerhalb von Modulen (Container managed Lifecycle)

---

## 4. Event Bus / Domain Events

### Erlaubte Event-Patterns:

```php
// Event Definition (im Contracts Layer)
final class AppointmentCreated implements DomainEvent
{
    public function __construct(
        public readonly string $eventId,      // UUID
        public readonly int $tenantId,
        public readonly int $appointmentId,
        public readonly int $customerId,
        public readonly int $employeeId,
        public readonly string $startsAtUtc,
        public readonly string $endsAtUtc,
        public readonly string $occurredAt,   // ISO 8601
        public readonly int $version = 1      // Schema-Version
    ) {}
}
```

### Regeln:
1. Events sind **immutable** (readonly properties)
2. Events MÜSSEN `tenantId`, `eventId`, `occurredAt`, `version` enthalten
3. Events DÜRFEN KEINE Referenzen auf lebende Objekte enthalten
4. Events MÜSSEN JSON-serialisierbar sein
5. Event-Handler MÜSSEN idempotent sein (gleicher Event 2x → gleicher Zustand)
6. Event-Versioning: Neue Felder dürfen hinzugefügt werden (backward-compatible). Bestehende Felder dürfen nie entfernt werden.

---

## 5. Settings/Config Injection

### Modul-Settings:

```php
// Modul liest Settings über Kernel-Port
$settings = $container->get(SettingsPort::class);
$bookingAdvance = $settings->get('appointments', 'advance_booking_max', 90); // Default: 90 Tage
```

### Regeln:
- Settings MÜSSEN tenant-scoped sein: `SettingsPort::get(string $module, string $key, mixed $default, ?int $tenantId)`
- Settings Schema wird in `module.json` definiert (validiert beim Speichern)
- Settings Änderungen MÜSSEN geloggt werden (Audit-Trail)
- Sensible Settings (API Keys, Secrets) MÜSSEN verschlüsselt gespeichert werden

---

## 6. RBAC Integration Contract

### Modul definiert Capabilities in `module.json`:

```json
"capabilities": [
  "manage_bookando_appointments",
  "view_bookando_appointments",
  "cancel_bookando_appointments"
]
```

### Enforcement im Modul:

```php
class CreateAppointmentHandler
{
    public function handle(CreateAppointmentCommand $cmd, SecurityContext $ctx): void
    {
        // MUSS: Kernel-AuthorizationPort nutzen
        $ctx->authorize('manage_bookando_appointments');

        // MUSS: Tenant-Kontext verifizieren
        $ctx->assertTenant($cmd->tenantId);

        // ... Business Logic
    }
}
```

### Regeln:
- Jeder Command/Query Handler MUSS `SecurityContext` prüfen
- `SecurityContext` wird vom Kernel bereitgestellt (enthält User, Tenant, Permissions)
- Module dürfen KEINE eigenen Auth-Checks implementieren (kein `current_user_can()`)
- Admin-Rolle hat NICHT automatisch alle Modul-Permissions (explizite Zuweisung)

---

## 7. Tenant Context Contract

### Jede Modul-Operation MUSS einen expliziten Tenant-Kontext haben:

```php
// Command MUSS tenantId enthalten
class CreateAppointmentCommand
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $idempotencyKey,
        public readonly int $customerId,
        // ...
    ) {}
}

// Repository MUSS tenantId in jeder Query verwenden
class AppointmentRepository
{
    public function findById(int $tenantId, int $id): ?Appointment
    {
        return $this->persistence->queryRow(
            'SELECT * FROM appointments WHERE tenant_id = ? AND id = ?',
            [$tenantId, $id]
        );
    }
}
```

### Regeln:
- KEIN impliziter Tenant aus globalem State (kein `TenantManager::currentTenantId()` in Modulen)
- Tenant-ID wird als Parameter durch alle Schichten gereicht
- Cross-Tenant-Zugriff NUR über explizite Kernel-API (ShareService/ACL)

---

## 8. Auditing/Logging Contract

### Module MÜSSEN auditrelevante Aktionen loggen:

```php
class CreateAppointmentHandler
{
    public function handle(CreateAppointmentCommand $cmd, SecurityContext $ctx): Appointment
    {
        // ... create appointment

        $this->logger->audit('appointment.created', [
            'tenant_id' => $cmd->tenantId,
            'appointment_id' => $appointment->id,
            'customer_id' => $cmd->customerId,
            'employee_id' => $cmd->employeeId,
            'actor' => $ctx->userId(),
            'ip' => $ctx->ip(),
        ]);

        return $appointment;
    }
}
```

### Regeln:
- Audit-Events MÜSSEN: `tenant_id`, `actor`, `action`, `entity_type`, `entity_id` enthalten
- Audit-Log ist **append-only** (kein Update/Delete)
- Audit-Log Retention: mindestens 1 Jahr (konfigurierbar)
- Sensible Daten (Passwörter, Karten-Nummern) dürfen NICHT im Audit-Log erscheinen

---

## 9. Feature Gating Contract

### Module MÜSSEN Feature-Gates respektieren:

```php
class CreateAppointmentHandler
{
    public function handle(CreateAppointmentCommand $cmd, SecurityContext $ctx): Appointment
    {
        // Check plan limits
        $this->featureGate->requireFeature('appointments_basic', $cmd->tenantId);

        // Check usage limits
        $this->featureGate->checkLimit('appointments_monthly', $cmd->tenantId, 1);

        // ... Business Logic
    }
}
```

### Regeln:
- FeatureGate wird vom Kernel bereitgestellt (nicht vom Modul implementiert)
- Feature-Checks passieren im Application Layer (vor Domain-Logik)
- Überschreitung von Limits → klarer Fehler mit Upgrade-Hinweis
- Feature-Gate MUSS für Background-Jobs gelten (nicht nur für API-Calls)

---

## 10. Modul-Struktur (Canonical Layout)

```
modules/
  appointments/
  ├── module.json                # Manifest
  ├── ServiceProvider.php        # DI Registration
  ├── Domain/
  │   ├── Appointment.php        # Entity
  │   ├── AppointmentStatus.php  # Enum/Value Object
  │   └── Policies/
  │       └── DoubleBookingPolicy.php
  ├── Application/
  │   ├── Commands/
  │   │   ├── CreateAppointmentCommand.php
  │   │   ├── CreateAppointmentHandler.php
  │   │   ├── CancelAppointmentCommand.php
  │   │   └── CancelAppointmentHandler.php
  │   ├── Queries/
  │   │   ├── GetTimelineQuery.php
  │   │   └── GetTimelineHandler.php
  │   └── Listeners/
  │       └── PaymentReceivedListener.php
  ├── Infrastructure/
  │   ├── AppointmentRepository.php
  │   └── Migrations/
  │       ├── 001_create_appointments_table.php
  │       └── 002_add_status_index.php
  ├── Api/
  │   ├── AppointmentController.php   # REST/HTTP Mapping
  │   └── AppointmentResource.php     # Response DTO
  ├── UI/
  │   └── vue/
  │       ├── main.ts
  │       ├── stores/
  │       └── components/
  └── Tests/
      ├── Unit/
      │   ├── DoubleBookingPolicyTest.php
      │   └── AppointmentTest.php
      └── Integration/
          └── CreateAppointmentFlowTest.php
```
