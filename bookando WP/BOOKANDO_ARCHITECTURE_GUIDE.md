# Bookando Platform - Comprehensive Architecture Overview

**Version:** 1.0.0  
**Last Updated:** November 2025  
**Quality Score:** 74/100 (Target: 95+)  
**Tech Stack:** PHP 8.1+ | Vue 3 | TypeScript | Vite | WordPress

---

## Table of Contents
1. [System Architecture](#system-architecture)
2. [Plugin System & Module Structure](#plugin-system--module-structure)
3. [Multi-Tenant Architecture](#multi-tenant-architecture)
4. [Licensing System](#licensing-system)
5. [Cross-Platform Setup](#cross-platform-setup)
6. [Configuration Files & Entry Points](#configuration-files--entry-points)
7. [Database & API Structure](#database--api-structure)
8. [Feature Integration Guide](#feature-integration-guide)

---

## System Architecture

### High-Level Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     BOOKANDO ECOSYSTEM                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  FRONTEND (Vue 3 + TypeScript + Vite)                           │
│  ├── Module SPAs (appointments, customers, employees, etc.)    │
│  ├── Shared Components (Design System - 54+ reusable)          │
│  ├── Pinia State Management                                     │
│  └── vue-i18n (Multi-language support)                         │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │           REST API LAYER (bookando/v1)                     │ │
│  │  ├── Authentication (JWT, API Keys, Sessions)              │ │
│  │  ├── Authorization (Role-based Access Control)             │ │
│  │  └── Request/Response Handling                             │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  BACKEND (PHP 8.1+ with Composer)                              │
│  ├── Core Infrastructure                                        │
│  │   ├── Multi-Tenant System (Data Isolation)                  │
│  │   ├── Licensing Engine (Plan-based Feature Gating)         │
│  │   ├── Role-Based Access Control (RBAC)                     │
│  │   ├── Activity Logging & Audit Trail                       │
│  │   └── Queue Management (Background Jobs)                    │
│  │                                                              │
│  ├── 10+ Modular Plugins                                       │
│  │   ├── Appointments (Booking Management)                    │
│  │   ├── Customers (CRM)                                      │
│  │   ├── Employees (Staff Management)                         │
│  │   ├── Finance (Payments & Invoices)                        │
│  │   ├── Resources (Rooms, Equipment)                         │
│  │   ├── Academy (Training & Courses)                         │
│  │   ├── Offers (Service Catalog)                             │
│  │   ├── Tools (Custom Fields, Notifications)                 │
│  │   ├── Settings (Configuration)                             │
│  │   ├── PartnersHub (Collaboration)                          │
│  │   └── Workday (Shift Management)                           │
│  │                                                              │
│  └── Payment Integrations                                       │
│      ├── Stripe                                                │
│      ├── PayPal                                                │
│      ├── Mollie                                                │
│      └── Klarna                                                │
│                                                                  │
│  WORDPRESS INTEGRATION                                          │
│  ├── Admin Pages & Menus                                       │
│  ├── User Management (Sync with WP Users)                      │
│  ├── Capabilities & Roles                                      │
│  └── Custom Post Types & Taxonomies                            │
│                                                                  │
│  DATABASE (MySQL/MariaDB)                                       │
│  ├── wp_bookando_* tables (Module Data)                        │
│  ├── Tenant Isolation (tenant_id column)                       │
│  └── Activity Logs & Audit Trails                              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Core Components

| Component | Location | Purpose |
|-----------|----------|---------|
| **BaseModule** | `/src/Core/Base/BaseModule.php` | Abstract base for all modules (asset loading, security, capability management) |
| **BaseApi** | `/src/Core/Base/BaseApi.php` | Abstract REST API handler for modules |
| **BaseModel** | `/src/Core/Model/BaseModel.php` | Abstract database model with multi-tenant support |
| **ModuleManager** | `/src/Core/Manager/ModuleManager.php` | Discovers, loads, and manages all modules |
| **TenantManager** | `/src/Core/Tenant/TenantManager.php` | Handles tenant resolution and isolation |
| **LicenseManager** | `/src/Core/Licensing/LicenseManager.php` | License validation and feature gating |
| **Plugin** | `/src/Core/Plugin.php` | Main plugin entry point and initialization |

---

## Plugin System & Module Structure

### Module Architecture

Each module follows a consistent structure:

```
src/modules/{slug}/
├── Module.php                    # Module registration & initialization
├── module.json                   # Module metadata & configuration
├── Capabilities.php              # Role definitions
├── Installer.php                 # Database table creation
├── Model.php                     # Data model (extends BaseModel)
├── RestHandler.php               # REST API handlers
├── Admin/
│   └── Admin.php                # WordPress admin integration
├── Api/
│   └── Api.php                  # REST route registration
├── Templates/
│   └── admin-vue-container.php  # HTML container for Vue app
└── assets/
    ├── vue/
    │   ├── main.ts              # Vue app entry point
    │   ├── views/               # Page components
    │   ├── components/          # Reusable components
    │   ├── api/                 # Frontend API client
    │   ├── store/               # Pinia stores
    │   ├── models/              # Data models
    │   └── i18n.local.ts        # Module translations
    └── css/
        └── admin.scss            # Module styles
```

### Module.json Specification

```json
{
  "slug": "appointments",
  "plan": "starter",              // License plan requirement
  "version": "1.0.0",
  "tenant_required": true,         // Multi-tenant requirement
  "license_required": true,        // License check
  "features_required": [           // Feature gates
    "calendar_sync",
    "rest_api_read"
  ],
  "group": "operations",           // Module category
  "is_saas": true,                // SaaS compatible
  "has_admin": true,              // Has WordPress admin UI
  "supports_webhook": true,        // Webhook support
  "supports_offline": true,        // Works offline
  "supports_calendar": true,       // Calendar integration
  "name": {                        // Multi-language names
    "default": "Appointments",
    "de": "Termine",
    "en": "Appointments"
  },
  "actions": {                     // Bulk actions available
    "allowed": ["soft_delete", "hard_delete", "export"]
  },
  "visible": true,
  "menu_icon": "dashicons-calendar-alt",
  "menu_position": 28,
  "dependencies": [],              // Module dependencies
  "tabs": [],                      // UI tabs
  "is_submodule": false,
  "parent_module": null
}
```

### Module Lifecycle

```
1. DISCOVERY (ModuleManager::scanModules)
   ├── Scan src/modules for module.json files
   └── Load manifest metadata

2. ACTIVATION (Plugin Initialization)
   ├── Check license requirements
   ├── Verify feature gates
   └── Execute Installer::run()

3. REGISTRATION (Module::register)
   ├── Register capabilities
   ├── Register admin hooks
   ├── Register REST routes
   └── Enqueue assets

4. RUNTIME
   ├── Frontend: Load Vue SPA and mount
   ├── Backend: Handle REST API requests
   └── Database: Query with tenant isolation
```

### Available Modules (10+)

| Module | Group | Plan | Description |
|--------|-------|------|-------------|
| **Customers** | CRM | Starter | Customer management, profiles, relationships |
| **Employees** | HR | Starter | Staff, scheduling, availability, calendar sync |
| **Appointments** | Booking | Starter | Appointment booking, calendar view |
| **Offers** | Catalog | Starter | Service catalog, pricing, availability |
| **Resources** | Ops | Starter | Rooms, equipment, asset management |
| **Finance** | Finance | Starter | Invoices, payments, financial reports |
| **Academy** | Training | Academy | Courses, certifications, learning materials |
| **Tools** | Utilities | Starter | Custom fields, notifications, forms |
| **Settings** | Admin | Starter | System configuration, roles, branding |
| **PartnersHub** | Business | Pro | Partner network, collaboration, revenue sharing |
| **Workday** | HR | Starter | Shift planning, time tracking, scheduling |

---

## Multi-Tenant Architecture

### Tenant Concept

**What is a Tenant?**
- An isolated data partition (company, organization, or business unit)
- Each tenant has its own data, configuration, and users
- WordPress installations can host multiple tenants
- Default tenant ID = 1 (single-tenant setup)

### Tenant Resolution

**How Bookando determines the current tenant:**

```
┌─────────────────────────────────────────┐
│  TenantManager::currentTenantId()       │
├─────────────────────────────────────────┤
│ 1. Check static cache (per-request)     │
│ 2. Resolve from request context:        │
│    ├── REST endpoint (tenant param)     │
│    ├── WordPress user meta              │
│    ├── Cookie / Session                 │
│    └── Filter hook (custom logic)       │
│ 3. Apply filter: bookando_tenant_id     │
│ 4. Default to ID 1 (if not found)       │
└─────────────────────────────────────────┘
```

### Data Isolation

**Database Structure:**
- Every Bookando table has a `tenant_id` column (BIGINT UNSIGNED)
- All SELECT queries use `BaseModel::applyTenant()` to add WHERE clause
- All INSERT/UPDATE/DELETE operations validate tenant ownership

**Example Query:**
```php
// Frontend wants customer list
$customers = $model->fetchAll(['limit' => 10, 'offset' => 0]);

// Behind the scenes (BaseModel::applyTenant):
// SELECT * FROM wp_bookando_users 
// WHERE tenant_id = {$currentTenant} 
// LIMIT 10 OFFSET 0
```

### Multi-Tenant Features (Pro Plan+)

From `license-features.php`:
- **multi_tenant**: Enable multiple organizations
- **white_label**: Custom branding per tenant
- **cross_tenant_share**: Share data between tenants with ACL
- **cross_tenant_share**: Token-based sharing

### Implementation Files

| File | Purpose |
|------|---------|
| `/src/Core/Tenant/TenantManager.php` | Tenant resolution & caching |
| `/src/Core/Tenant/TenantProvisioner.php` | Create new tenant & database setup |
| `/src/Core/Tenant/TenantInstaller.php` | Install modules for tenant |
| `/src/Core/Tenant/ProvisioningApi.php` | REST API for tenant creation |
| `/src/Core/Model/Traits/MultiTenantTrait.php` | Tenant isolation trait |

---

## Licensing System

### License Architecture

```
┌────────────────────────────────────────────────────┐
│         BOOKANDO LICENSING SYSTEM                  │
├────────────────────────────────────────────────────┤
│                                                    │
│  LICENSE MANAGER (Core)                           │
│  ├── License Key Validation                       │
│  ├── Remote Verification (API call)               │
│  ├── Feature Gate Control                         │
│  └── Grace Period (30 days)                       │
│                                                    │
│  PLAN TIERS                                       │
│  ├── STARTER                                      │
│  │   └── All core modules + basic features       │
│  ├── PRO                                          │
│  │   └── Starter + multi-tenant, white-label     │
│  └── ACADEMY                                      │
│      └── Pro + education modules                  │
│                                                    │
│  FEATURE FLAGS                                    │
│  ├── Modules (full functionality blocks)         │
│  ├── Features (fine-grained capabilities)        │
│  └── Integrations (payment gateways, etc.)       │
│                                                    │
└────────────────────────────────────────────────────┘
```

### License Manager API

**File:** `/src/Core/Licensing/LicenseManager.php`

```php
// Get current license info
$licenseData = LicenseManager::getLicenseData();
// Returns: ['key', 'plan', 'features', 'expires_at', 'valid', ...]

// Check if module is allowed
$allowed = LicenseManager::isModuleAllowed('customers');

// Check if feature is enabled
$enabled = LicenseManager::isFeatureEnabled('calendar_sync');

// Get plan name
$plan = LicenseManager::getLicensePlan();

// Verify license with remote server
LicenseManager::verifyRemote($licenseKey);

// Check grace period (30 days)
$inGrace = LicenseManager::isWithinGracePeriod();
```

### License Features Map

**File:** `/src/Core/Licensing/license-features.php`

Defines plan → modules → features hierarchy:

```php
'plans' => [
    'starter' => [
        'modules' => [
            'settings', 'customers', 'employees', 'locations',
            'services', 'resources', 'events', 'appointments',
            'packages', 'payments', 'invoices', 'discounts',
            'notifications', 'custom_fields', 'analytics', 'reports'
        ],
        'features' => [
            'waitlist', 'calendar_sync', 'feedback', 'mobile_app',
            'webhooks', 'export_csv', 'rest_api_read',
            // ... more features
        ]
    ],
    'pro' => [
        'modules' => ['@starter'],  // Inherits from starter
        'features' => [
            '@starter',  // Inherits features
            'multi_tenant', 'white_label', 'rest_api_write',
            // ... pro-only features
        ]
    ]
]
```

### DEV Mode

In development:
```php
// wp-config.php
define('BOOKANDO_DEV', true);
```

When enabled:
- ALL modules are allowed
- ALL features are enabled
- NO license validation
- Perfect for testing/development

### License Middleware

**File:** `/src/Core/Licensing/LicenseMiddleware.php`

Applied to REST API routes:
- Validates license before allowing requests
- Checks module/feature availability
- Returns 403 Forbidden if not allowed
- Logs licensing denials to activity log

---

## Cross-Platform Setup

### Web Application

**Frontend Delivery:**
- Vue 3 SPA (Single Page Application) per module
- Vite build system with HMR support
- SCSS/CSS with responsive design system
- Service Worker support for offline

**Browser Support:**
- Modern browsers (ES6+)
- Mobile-responsive UI
- Touch-friendly interface
- Progressive enhancement

### Mobile & PWA

**Features:**
- PWA support (Service Worker, Web App Manifest)
- Offline capability (enabled in module.json)
- Mobile-optimized components
- Touch gestures and mobile-first design

**Example (from module.json):**
```json
{
  "supports_offline": true,
  "mobile_app": true,
  "is_saas": true
}
```

### API-First Architecture

**Why API-First?**
- Frontend calls REST API for all data
- Backend/API can be hosted separately
- Desktop apps can integrate via API
- Mobile apps can use same endpoints

**API Endpoints:**
```
/wp-json/bookando/v1/customers
/wp-json/bookando/v1/appointments
/wp-json/bookando/v1/employees
... (one per module)
```

### Development Server

**Vite Dev Server:**
```bash
npm run dev
# Runs on localhost:5173
# HMR (Hot Module Replacement) enabled
# Asset serving for development
```

**File:** `/scripts/vite.config.ts`

Features:
- Multi-entry build (one per module)
- CSS pre-processing (SCSS)
- TypeScript support
- SVG loader
- CSS purging for production

---

## Configuration Files & Entry Points

### Main Entry Point

**File:** `/bookando.php`

```php
<?php
/**
 * Plugin Name: Bookando
 * Description: Modulares Kurs- & Buchungs-Plugin für WordPress
 * Version: 1.0.0
 */

// 1. Load environment (.env file)
// 2. Feature flags (BOOKANDO_DEV, etc.)
// 3. Composer autoload
// 4. DI Container helpers
// 5. Register services
// 6. Hook into WordPress
// 7. Initialize Plugin class
```

### Configuration Files

| File | Purpose |
|------|---------|
| **composer.json** | PHP dependencies, autoloading, scripts |
| **package.json** | Node.js dependencies, build scripts |
| **tsconfig.json** | TypeScript compiler settings |
| **scripts/vite.config.ts** | Frontend build configuration |
| **config/modules.php** | Module registry (slug, class, manifest) |
| **config/tenants.php** | Tenant configuration (empty for default) |
| **audit.config.json** | Code quality audit settings |

### Key Configuration Locations

```
src/Core/
├── Config/
│   └── EnvLoader.php           # .env file parser
├── Providers/
│   └── ServiceProvider.php     # Service registration
├── Manager/
│   └── ModuleManager.php       # Module loading
└── Plugin.php                  # Main initialization
```

### Environment Variables

**File:** `.env.example`

```bash
# Development mode
BOOKANDO_DEV=false

# User sync with WordPress
BOOKANDO_SYNC_USERS=false

# API settings
BOOKANDO_API_RATE_LIMIT=100

# License (if offline)
BOOKANDO_LICENSE_KEY=

# Logging
BOOKANDO_DEBUG=false
BOOKANDO_LOG_LEVEL=info
```

### WordPress Hooks

**Activation/Deactivation:**
```php
// Runs on plugin activation
register_activation_hook('bookando.php', [Installer::class, 'run']);

// Runs on plugin deactivation
register_deactivation_hook('bookando.php', 'bookando_uninstall_all');
```

**Initialization Sequence:**
```
plugins_loaded
  └─ LoadTextdomain (i18n)
     
init
  └─ Boot (Loader::init)
     ├─ initAuth (JWT, API Keys)
     ├─ initDispatchers (REST, AJAX, Webhook)
     ├─ initHelpers (Global functions)
     └─ initModules (Load all active modules)
     
admin_menu
  └─ Menu::registerMenus (Admin sidebar)
     ├─ Bookando main menu
     └─ Module submenu items

admin_enqueue_scripts
  └─ BaseModule::enqueue_module_assets (per module)
     ├─ Vite check (dev mode)
     ├─ Load CSS from manifest
     ├─ Load JS from dist or dev server
     └─ Inline module configuration
```

---

## Database & API Structure

### Database Tables

**Core Tables:**
```
wp_bookando_users           # Customers, employees, staff
wp_bookando_appointments    # Appointment bookings
wp_bookando_customers       # CRM customer data
wp_bookando_employees       # Employee records
wp_bookando_resources       # Rooms, equipment
wp_bookando_events          # Courses, events
wp_bookando_offers          # Services, packages
wp_bookando_payments        # Payment transactions
wp_bookando_invoices        # Invoices
wp_bookando_activity_logs   # Audit trail
wp_bookando_queue           # Background jobs
... (module-specific tables)
```

**Universal Columns:**
```
id              BIGINT UNSIGNED PRIMARY KEY
tenant_id       BIGINT UNSIGNED (for isolation)
created_at      DATETIME (creation timestamp)
updated_at      DATETIME (last modification)
deleted_at      DATETIME NULL (soft deletes)
```

### REST API Structure

**Base URL:**
```
https://example.com/wp-json/bookando/v1/
```

**Module Endpoints Pattern:**
```
GET    /bookando/v1/{module}                    # List all
GET    /bookando/v1/{module}/{id}               # Get single
POST   /bookando/v1/{module}                    # Create
PUT    /bookando/v1/{module}/{id}               # Update
DELETE /bookando/v1/{module}/{id}               # Delete

Examples:
GET    /bookando/v1/customers
POST   /bookando/v1/customers
GET    /bookando/v1/customers/123
PUT    /bookando/v1/customers/123
DELETE /bookando/v1/customers/123
```

**Core API Endpoints:**
```
POST   /bookando/v1/auth/login                 # Authenticate
POST   /bookando/v1/auth/logout                # Logout
GET    /bookando/v1/health                     # Health check
GET    /bookando/v1/roles                      # Available roles
```

### API Response Format

**Success Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "meta": {
    "total": 100,
    "per_page": 20,
    "current_page": 1
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": {
    "code": "INVALID_TENANT",
    "message": "You do not have permission to access this tenant",
    "details": {
      "tenant_id": 999
    }
  }
}
```

### Authentication

**Supported Methods:**
1. **WordPress Session** (Default)
   - Cookie-based
   - User must be logged into WordPress

2. **JWT Tokens**
   - Stateless authentication
   - For API clients, mobile apps
   - JWTService handles creation/validation

3. **API Keys**
   - Long-lived credentials
   - For service-to-service integration
   - Rate limiting per key

**Implementation:**
```php
// File: src/Core/Auth/AuthMiddleware.php
// Called on every REST request
// Validates JWT or API key
// Sets current user context
```

### Dispatcher Pattern

**Request Routing:**
```
┌─────────────────┐
│   WordPress     │
│   REST API      │
└────────┬────────┘
         │
         v
┌─────────────────────────────────┐
│   RestDispatcher                │
│   - Route matching              │
│   - Module guard checking       │
│   - Handler delegation          │
└────────┬────────────────────────┘
         │
         v
┌─────────────────────────────────┐
│   Module RestHandler            │
│   (e.g., AppointmentsRestHandler)│
│   - Method handlers             │
│   - Data transformation         │
│   - Response formatting         │
└─────────────────────────────────┘
```

---

## Feature Integration Guide

### How to Add a New Module

#### Step 1: Generate Module Scaffolding

```bash
npm run module:make
# Interactive CLI to create:
# - Module directory structure
# - PHP classes (Module, RestHandler, Model)
# - Vue components (main.ts, views, components)
# - Database migration
# - i18n files
```

#### Step 2: Define Module Metadata

**File:** `src/modules/{slug}/module.json`

```json
{
  "slug": "my-feature",
  "plan": "starter",
  "version": "1.0.0",
  "tenant_required": true,
  "license_required": true,
  "features_required": ["export_csv"],
  "group": "operations",
  "name": {
    "default": "My Feature",
    "de": "Mein Feature"
  }
}
```

#### Step 3: Create Module Class

**File:** `src/modules/{slug}/Module.php`

```php
<?php
namespace Bookando\Modules\myfeature;

use Bookando\Core\Base\BaseModule;
use Bookando\Modules\myfeature\Admin\Admin;
use Bookando\Modules\myfeature\Api\Api;

class Module extends BaseModule
{
    public function register(): void
    {
        // Register capabilities
        $this->registerCapabilities(Capabilities::class);
        
        // Register admin interface
        $this->registerAdminHooks(function (): void {
            add_action('bookando_register_module_menus', [Admin::class, 'register_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        });
        
        // Register API
        Api::register();
        $this->registerRestRoutes([Api::class, 'registerRoutes']);
    }
    
    public function enqueue_admin_assets(): void
    {
        $this->enqueue_module_assets();
    }
}
```

#### Step 4: Create Data Model

**File:** `src/modules/{slug}/Model.php`

```php
<?php
namespace Bookando\Modules\myfeature;

use Bookando\Core\Model\BaseModel;

class Model extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->tableName = $this->table('my_feature');
    }
    
    // Implement CRUD methods
    public function create(array $data): int|false { /* ... */ }
    public function fetchAll(array $filters = []): array { /* ... */ }
    public function fetchOne(int $id): array|null { /* ... */ }
    public function update(int $id, array $data): bool { /* ... */ }
    public function delete(int $id): bool { /* ... */ }
}
```

#### Step 5: Create REST API Handler

**File:** `src/modules/{slug}/Api/Api.php`

```php
<?php
namespace Bookando\Modules\myfeature\Api;

use Bookando\Core\Base\BaseApi;
use Bookando\Modules\myfeature\RestHandler;

class Api extends BaseApi
{
    protected static function getNamespace(): string { return 'bookando/v1'; }
    protected static function getModuleSlug(): string { return 'myfeature'; }
    protected static function getBaseRoute(): string { return '/my-feature'; }
    protected static function getRestHandlerClass(): string { return RestHandler::class; }
    
    public static function registerRoutes(): void
    {
        // Define your API routes here
    }
}
```

#### Step 6: Create Frontend Vue Component

**File:** `src/modules/{slug}/assets/vue/main.ts`

```typescript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './views/MyFeatureView.vue'

const app = createApp(App)
app.use(createPinia())

// Mount to #bookando-my-feature-app
app.mount('#bookando-my-feature-app')
```

#### Step 7: Create Database Migration

**File:** `src/modules/{slug}/Installer.php`

```php
<?php
namespace Bookando\Modules\myfeature;

class Installer
{
    public static function run(): void
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'bookando_my_feature';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_tenant (tenant_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
```

#### Step 8: Register in Module Config

**File:** `config/modules.php`

```php
return [
    // ... existing modules
    'myfeature' => [
        'slug' => 'myfeature',
        'class' => \Bookando\Modules\MyFeature\Module::class,
        'manifest' => dirname(__DIR__) . '/src/modules/myfeature/module.json',
    ],
];
```

#### Step 9: Update License Map (if required)

**File:** `src/Core/Licensing/license-features.php`

Add to appropriate plan:
```php
'starter' => [
    'modules' => [
        // ... existing
        'myfeature',  // Add new module
    ]
]
```

#### Step 10: Build & Test

```bash
# Build frontend
npm run build

# Run tests
npm run test

# Lint code
npm run lint

# Check module structure
npm run validate:modules
```

### How to Add a New Feature (Non-Module)

**Features** are fine-grained capabilities that enhance existing modules.

#### Step 1: Add Feature to License Map

**File:** `src/Core/Licensing/license-features.php`

```php
'starter' => [
    'modules' => [...],
    'features' => [
        'my_awesome_feature',  // Add here
        // ... existing
    ]
]
```

#### Step 2: Gate Feature in Code

**Frontend (Vue):**
```vue
<script setup>
import { computed } from 'vue'

// BOOKANDO_VARS is injected by PHP
const licensedFeatures = computed(() => 
  window.BOOKANDO_VARS?.license_features || []
)

const hasFeature = computed(() => 
  licensedFeatures.value.includes('my_awesome_feature')
)
</script>

<template>
  <div v-if="hasFeature" class="feature-ui">
    <!-- Feature content -->
  </div>
</template>
```

**Backend (PHP):**
```php
use Bookando\Core\Licensing\LicenseManager;

if (LicenseManager::isFeatureEnabled('my_awesome_feature')) {
    // Execute feature
}
```

### Integration Points for New App/Feature

**For a new **Module**:**
1. Use `npm run module:make` generator
2. Implement Model (CRUD)
3. Implement RestHandler (API endpoints)
4. Create Vue components
5. Register in config/modules.php
6. Add to license-features.php

**For a new **Feature**:**
1. Add to license-features.php features list
2. Gate behind `LicenseManager::isFeatureEnabled()`
3. Implement in module code
4. Test with/without license

**For a new **Integration** (e.g., payment gateway):**
1. Add package to composer.json
2. Create integration class in src/Core/Integrations/
3. Register in ServiceProvider
4. Add feature flag to license-features.php
5. Use in payment module

**For a new **API Endpoint**:**
1. Add route in Module's Api::registerRoutes()
2. Implement handler in RestHandler
3. Document endpoint format
4. Add authentication/authorization
5. Return standardized Response

---

## Advanced Topics

### Activity Logging & Audit Trail

Every significant action is logged:
```
ActivityLogger::log(
    'module.action',           // Category
    'User created customer',    // Message
    ['customer_id' => 123],     // Context
    ActivityLogger::LEVEL_INFO, // Level
    null,                       // User ID (auto)
    'module_slug'              // Module
);
```

**Queried via:**
```
GET /wp-admin/admin.php?page=bookando_logs
```

### Queue System

Background jobs via queue:
```php
use Bookando\Core\Queue\QueueManager;

QueueManager::push([
    'action' => 'send_email',
    'payload' => ['email' => 'user@example.com'],
    'retry_count' => 3,
    'retry_delay' => 60
]);
```

### Security Headers

Automatically added to all responses:
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- Strict-Transport-Security (HTTPS)

### i18n/Localization

All strings wrapped in translation functions:
```php
__('Customer Name', 'bookando')
_e('Save', 'bookando')
```

Multi-language support via:
- Vue i18n (frontend)
- gettext (backend)
- .pot template generation
- Automated i18n audit tools

---

## Performance Considerations

### Caching Strategy

- **Object Cache:** Licenses, module manifests (5 min TTL)
- **Transient Cache:** Vite server availability check (5 min)
- **Query Cache:** BaseModel result caching
- **Static Cache:** Per-request memoization

### Asset Loading

- **Development:** HMR via Vite dev server
- **Production:** Minified, optimized bundles
- **CSS:** Generated from Vite manifest + legacy fallback
- **JS:** Loaded with Vite manifest or fallback

### Database Optimization

- Indexes on tenant_id, created_at
- Prepared statements for all queries
- Batch operations for bulk inserts
- Soft deletes instead of hard deletes

---

## Deployment

### Production Checklist

1. **Build assets:** `npm run build:all`
2. **Install dependencies:** `composer install --no-dev`
3. **Run migrations:** Automatic on plugin activation
4. **Set environment:** Define BOOKANDO_DEV=false
5. **Test API:** Verify license and endpoints working
6. **Check permissions:** Ensure wp-content is writable
7. **Enable caching:** Configure object/query caching
8. **Monitor logs:** Check wp_bookando_activity_logs regularly

### Scaling Considerations

- **Multi-Server:** Shared database, asset CDN
- **Microservices:** API can be extracted to separate server
- **Tenants:** Scale to 1000+ via tenant partitioning
- **Load Balancing:** Stateless architecture supports LB

---

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| Module not loading | Check module.json exists and is valid JSON |
| Assets not loading | Verify Vite dev server or rebuild with `npm run build` |
| API returns 403 | Check capabilities, JWT token, or license |
| Tenant isolation broken | Ensure BaseModel::applyTenant() called on queries |
| Missing translations | Run `npm run i18n:fix` and rebuild |

### Debug Mode

Enable detailed logging:
```php
// wp-config.php
define('BOOKANDO_DEBUG', true);
define('BOOKANDO_DEV', true);
```

Logs appear in:
- WordPress admin: Bookando > Logs
- Database: wp_bookando_activity_logs
- PHP error log: /wp-content/debug.log (if WP_DEBUG enabled)

---

## Summary

**Bookando** is a highly modular, multi-tenant WordPress plugin built on:

- **Modern Frontend:** Vue 3 + TypeScript + Vite
- **Enterprise Backend:** PHP 8.1+ with clean architecture
- **Flexible Licensing:** Plan-based feature gating
- **Secure:** Multi-tenant isolation, RBAC, audit trails
- **Extensible:** Plugin system for unlimited modules
- **Professional:** 54+ UI components, i18n support, testing

**To build a new feature/app:**
1. Determine if it's a module or feature
2. Use scaffolding generator for modules
3. Follow Base classes (BaseModule, BaseModel, BaseApi)
4. Implement CRUD + REST endpoints
5. Create Vue frontend
6. Register in config and license map
7. Deploy and test

For detailed component documentation, see `/docs` folder.

