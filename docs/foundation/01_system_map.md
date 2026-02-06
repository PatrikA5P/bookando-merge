# Phase 1 — System Map

## 1. Boot-Sequenz

The plugin boots via `bookando WP/bookando.php` (352 lines):

1. **Constants**: `BOOKANDO_PLUGIN_FILE`, `BOOKANDO_PLUGIN_DIR`, `BOOKANDO_PLUGIN_URL`
2. **EnvLoader**: `src/Core/Config/EnvLoader.php` loads `.env`
3. **Feature Flags**: `BOOKANDO_DEV`, `BOOKANDO_SYNC_USERS`
4. **Composer Autoloader**: `vendor/autoload.php` (PSR-4: `Bookando\Core\` → `src/Core/`, `Bookando\Modules\` → `src/modules/`)
5. **Helpers + Container**: `src/Core/Container/helpers.php` provides `resolve()` function
6. **ServiceProvider::register()** (`src/Core/Providers/ServiceProvider.php`): Registers into PSR-11 Container:
   - WordPress globals (`wpdb`)
   - Core services (`TenantManagerInterface`, `ActivityLogger`, `DebugLogger`, `UserSyncService`)
   - Module services (`CustomerRepository`, `CustomerValidator`, `CustomerService`)
7. **Plugin Activation Hook** (runs on activation):
   - `Installer::install()` creates all core DB tables
   - Migrations 002-004 run
   - `CapabilityService::seedOnActivation()` creates roles
   - Crons scheduled: `bookando_verify_license`, `bookando_log_cleanup`, `bookando_queue_process`
8. **Plugin Init** (`plugins_loaded` hook):
   - `Loader::init()` (`src/Core/Loader.php:23`):
     a. `initAuth()` — hooks `AuthMiddleware::authenticate` onto `rest_pre_dispatch`
     b. `initDispatchers()` — require_once AjaxDispatcher, RestDispatcher, WebhookDispatcher
     c. `initHelpers()` — loads global helper functions
     d. `initModules()` — `ModuleManager::instance()->loadModules()` (reads `config/modules.php`, checks `bookando_active_modules` option, instantiates each Module class, calls `boot()`)
     e. `initApiRoutes()` — hooks `rest_api_init` → registers AuthApi, PartnershipApi, RolesApi
9. **Frontend Portal** shortcodes registered
10. **SecurityHeadersMiddleware::apply()** registered

## 2. Dependency Injection / Container

- **PSR-11 Container**: `src/Core/Container/Container.php` — Singleton pattern, supports `singleton()`, `bind()`, `instance()`, circular dependency detection
- **ServiceProvider**: `src/Core/Providers/ServiceProvider.php` — central registration point
- **Usage**: Most code uses static classes (TenantManager, Gate, LicenseManager) rather than DI. Only Customers module fully uses DI via constructor injection
- **Gap**: Static access pattern (TenantManager::currentTenantId(), Gate::evaluate()) bypasses container — makes testing harder

## 3. REST API Registration & Middleware Flow

### Request Flow:
```
HTTP Request
  → WordPress REST API infrastructure
  → rest_pre_dispatch filter (priority 10):
      1. LicenseMiddleware::checkLicense() — verifies license + grace period
      2. AuthMiddleware::authenticate() — resolves auth: JWT → API Key → Session
  → Route matching via register_rest_route()
  → permission_callback (RestModuleGuard::for($module)):
      a. TenantManager::resolveFromRequest() — sets tenant context
      b. LicenseManager::isModuleAllowed($module) — checks module license
      c. Gate::evaluate($request, $module) — full auth check:
         - devBypass check
         - RateLimitMiddleware::apply()
         - is_user_logged_in()
         - TenantManager::currentTenantId() > 0
         - For writes: verifyNonce() + LicenseManager::isFeatureEnabled('rest_api_write') + canManage($module)
         - For reads: LicenseManager::isFeatureEnabled('rest_api_read') + (canManage || can 'read')
  → callback (RestHandler method)
  → SecurityHeadersMiddleware applies response headers
```

### Route Registration:
- Core routes: registered in `Loader::initApiRoutes()` via `rest_api_init`
- Module routes: each Module's `Api.php` calls `BaseApi::register()` which registers with `RestDispatcher`
- RestDispatcher (`src/Core/Dispatcher/RestDispatcher.php`, 1173 lines): Central route hub, registers catch-all pattern per module, delegates to module RestHandlers

## 4. Tenant Resolution Flow

File: `src/Core/Tenant/TenantManager.php`

Resolution priority (method `resolveFromRequest()`):
1. **Header** `X-BOOKANDO-TENANT` — only if user has `manage_options` or `bookando_switch_tenant`
2. **Request param** `tenant_id` or `_tenant_id`
3. **User meta** `user_tenant_id` (from WP user profile)
4. **Subdomain mapping** — if `BOOKANDO_SUBDOMAIN_MULTI_TENANT` constant is defined (SaaS mode), extracts leftmost host label, maps via `bookando_subdomain_map` option or `config/tenants.php`
5. **Fallback**: config `default_tenant` or option `bookando_default_tenant_id` or hardcoded `1`

Context propagation:
- Static memoization: `$cachedTenantId` (per-request)
- Filter hook `bookando_tenant_id_resolved` for last-chance override
- BaseModel reads `TenantManager::currentTenantId()` on every query via `MultiTenantTrait::applyTenant()`
- Insert/Update/Delete also enforce tenant_id in WHERE clause
- Cross-tenant via `runAsTenant($id, $fn)` (temporarily swaps) or `fetchOneUnsafeNoScope()` (requires prior ACL check)

### Where WordPress acts as host for tenant:
- User meta (`user_tenant_id`), WP options (`bookando_shared_tenants`, `bookando_subdomain_map`), WP capabilities for admin check

## 5. Authn/Authz Flow

### Authentication (AuthMiddleware.php):
Three-layer auth, tried in order:
1. **JWT**: `Authorization: Bearer <token>` → `JWTService::validateToken()` (HMAC-SHA256, uses WP AUTH_KEY+SECURE_AUTH_KEY as secret)
2. **API Key**: `X-API-Key: <key>` → SHA256 hash lookup in `bookando_api_keys` table
3. **WP Session**: `wp_get_current_user()` (cookie-based)

Public routes bypass auth: `/auth/login`, `/auth/register`, `/integrations/webhook/*`, `/health`

### Authorization (Gate.php):
- `Gate::evaluate(WP_REST_Request, string $module)` — central entry
- Dev bypass: non-production environment + `BOOKANDO_DEV` + `manage_options` capability (audit-logged)
- Rate limiting: via `RateLimitMiddleware::apply()` (user-ID or IP-based, transient storage)
- Login check: `is_user_logged_in()`
- Tenant check: `TenantManager::currentTenantId() > 0`
- Write operations: nonce verification (`X-WP-Nonce` header) + license feature check + capability check
- Read operations: license feature check + (module capability OR `read` capability)

### Server-side enforcement points:
- `RestModuleGuard::for()` on every REST route
- `EmployeeAuthorizationGuard` for employee-specific operations
- `RestPermissions::customers()` for customer-specific fine-grained access
- `BaseCapabilities` per module (registered on `init` hook)

### Where WordPress acts as host:
- `current_user_can()` for all capability checks
- `wp_verify_nonce()` for CSRF protection
- WP user system as identity provider for session auth
- WP roles as RBAC storage

## 6. Data Access Layer

### BaseModel (`src/Core/Model/BaseModel.php`):
- Abstract class, all models extend it
- Uses `MultiTenantTrait` for automatic tenant scoping
- `$wpdb` global for all DB operations
- Methods: `fetchAll()`, `fetchOne()`, `paginate()`, `insert()`, `update()`, `delete()`
- `insert()`: throws RuntimeException if tenant context missing, auto-sets tenant_id
- `update()`: scoped to `(id, tenant_id)`, prevents tenant_id mutation
- `delete()`: scoped to `(id, tenant_id)`
- `paginate()`: ORDER BY whitelist to prevent SQL injection via column names
- `formats()`: auto-detects wpdb format strings (%s, %d, %f)

### MultiTenantTrait (`src/Core/Model/Traits/MultiTenantTrait.php`):
- `applyTenant()` wraps any SELECT in a subquery: `SELECT * FROM ($sql) as t WHERE t.tenant_id = %d`
- Always reads from `TenantManager::currentTenantId()`
- Throws RuntimeException if tenant is null

### DatabaseAdapter (interface at `src/Core/Adapter/DatabaseAdapter.php`):
- Methods: `query()`, `queryRow()`, `queryValue()`, `insert()`, `update()`, `delete()`, `beginTransaction()`, `commit()`, `rollback()`, `escape()`
- `WordPressDatabaseAdapter` implements it using `$wpdb`
- Factory: `DatabaseAdapterFactory::create()`

### Repository pattern:
- `CustomerRepository` implements `CustomerRepositoryInterface` (contract in `src/Core/Contracts/`)
- `EmployeeRepository`, `ResourcesRepository`, various `StateRepository` classes
- Not all modules use repository pattern — some use Model directly in RestHandler

### Migrations:
- `Migrator.php` runs numbered migrations
- Core Installer creates all tables via `dbDelta()`
- Each module has optional `Installer.php` for module-specific tables

## 7. Queue/Job Execution

File: `src/Core/Queue/QueueManager.php`

- Table: `wp_bookando_queue_jobs` (created by Migration002)
- Enqueue: `QueueManager::enqueue($jobClass, $payload, $priority, $uniqueKey)`
- Process: triggered by WP-Cron every minute (`bookando_queue_process`)
- Batch processing: fetches N jobs ordered by priority ASC, created_at ASC
- Status flow: `pending → processing → completed|failed|dead`
- Retry: exponential backoff (2^attempt * 60 seconds), max 3 attempts
- Dead letter: jobs exceeding max retries get status `dead`
- Delayed jobs: `enqueueDelayed()` sets `available_at` in future
- Cleanup: daily cron deletes completed jobs older than N days

### Tenant context in jobs:
- **GAP**: No explicit tenant_id propagation in job payload. Jobs run in cron context where `TenantManager::currentTenantId()` defaults to 1 or whatever the cron context provides. This is a RISK for multi-tenant SaaS.

## 8. Frontend Architecture

- **Vue 3** (Composition API) + **Pinia** stores + **TypeScript**
- **Vite 7** as bundler (dev server at localhost:5173)
- Each module has its own Vue app in `assets/vue/` directory
- **Design System**: `src/Core/Design/` — components, composables (7), design tokens, SCSS
- **i18n**: vue-i18n with 7 language files (de, en, es, fr, it + 2)
- **API Client**: `packages/api-client/` — shared endpoints for appointments, customers, employees
- **Types**: `packages/types/` — shared TypeScript models/enums
- **Tailwind CSS** for utility classes + SCSS for component styles
- **Storybook 8.6** for component documentation
- Assets loaded per module via `BaseModule::enqueue_module_assets()` — only loads for current admin screen

## 9. Integration Patterns

### Calendar Sync:
- **Google**: Full OAuth2 (read/write), FreeBusy queries, event CRUD with `bookando_appointment_id` metadata. Tokens encrypted (AES-256-GCM) via `OAuthTokenStorage`
- **Apple/iCal**: Read-only ICS feed parsing. No OAuth, just URL-based
- **Microsoft**: OAuth2 via Graph API, read/write events. Token refresh + caching

### Video Conference:
- Zoom and Google Meet integrations (details in respective files)

### Payments:
- `GatewayInterface` defines contract: `createPayment()`, `capturePayment()`, `refundPayment()`, `handleWebhook()`, `verifyWebhookSignature()`
- `AbstractGateway` provides `formatAmount()`/`parseAmount()` for currency conversion
- `GatewayManager` as registry (singleton per gateway+tenant)
- Webhooks: `PaymentWebhookHandler` dispatches to gateway-specific handler
- WordPress actions fired: `bookando_payment_success`, `bookando_payment_failed`, `bookando_refund_completed`

### Webhooks (Inbound):
- Route: `/wp-json/bookando/v1/webhooks/payments/{gateway}`
- Signature verification: Stripe (SDK), TWINT (HMAC-SHA256), PayPal (TODO: incomplete), Mollie (API callback), Klarna (none)
- Event normalization to: `payment.success`, `payment.failed`, `refund.completed`

## 10. Licensing / Feature Gating

- `LicenseManager::isFeatureEnabled($feature)` — checks against tenant's license features
- `LicenseManager::isModuleAllowed($module)` — checks module is in license
- `LicenseGuard::hasValidLicense($tenantId)` — checks status + expiry + 7-day grace period
- `LicenseMiddleware`: REST pre-dispatch hook, whitelists public endpoints
- Grace period headers: `X-Bookando-License-Grace-Period`, `X-Bookando-License-Grace-Days`
- License stored in `wp_bookando_tenants` table (per-tenant)
- Feature map in `license-features.php`

## 11. Error Handling & Logging

### ActivityLogger (`src/Core/Service/ActivityLogger.php`):
- Persistent log to `wp_bookando_activity_log` table
- Levels: info, warning, error
- Fields: `logged_at`, `severity`, `context`, `message`, `payload` (JSON), `tenant_id`, `module_slug`
- Used for: auth events, security events, rate limits, license events, REST dispatch errors

### DebugLogger (`src/Core/Service/DebugLogger.php`):
- Development-only logging
- Timer support for performance measurement

### Audit events logged:
- `auth.success`, `auth.failed`, `auth.login.success/failed`, `auth.logout`, `auth.register`
- `security.devbypass`, `security.rate_limit_exceeded`, `security.path_traversal`
- `rest.oauth`, `rest.share`, `rest.dispatch`
- `license.grace_period`

### Gaps:
- No correlation IDs across request → job → webhook chains
- No structured logging format (just free-text message + JSON payload)
- No external observability integration (no OpenTelemetry, no Prometheus, no Sentry)
- No metric collection (latency, error rates, queue depth)

## 12. WordPress as Runtime Host — Adapter Candidates

| WP-Funktion | Wo genutzt | Muss in Adapter |
|---|---|---|
| `$wpdb` (DB) | BaseModel, alle Repos, QueueManager, ActivityLogger, … | `DatabasePort` (bereits begonnen: DatabaseAdapter) |
| `current_user_can()` | Gate, CapabilityService, BaseCapabilities, EmployeeAuthorizationGuard | `AuthorizationPort` |
| `wp_get_current_user()` | AuthMiddleware, TenantManager | `IdentityPort` |
| `wp_verify_nonce()` | Gate | `CsrfPort` |
| `get_option()`/`set_option()` | TenantManager, LicenseGuard, ModuleManager, Settings | `KeyValueStorePort` |
| `get_transient()`/`set_transient()` | RateLimitMiddleware, JWTService, Calendar token caching, Gate | `CachePort` |
| `wp_remote_get()`/`wp_remote_post()` | Calendar integrations, License verification, Vite check | `HttpClientPort` |
| `apply_filters()`/`do_action()` | TenantManager, Gate, Installers, Module lifecycle | `EventBusPort` (nicht alle: nur domänen-relevante Events) |
| `wp_schedule_single_event()`/`wp_cron` | QueueManager, Loader, Plugin activation | `SchedulerPort` |
| `error_log()` | Fallback in ActivityLogger, DebugLogger | `LoggerPort` |
| WP User System (roles/meta) | CapabilityService, TenantManager, AuthMiddleware | `UserDirectoryPort` |
| WordPress REST API infrastructure | All REST routes | `HttpRouter` / `RequestPort` |
| `dbDelta()` | Installer, Module Installers | `MigrationRunnerPort` |
| `plugin_dir_path()`/`plugins_url()` | BaseModule, asset loading | `PathResolverPort` |
