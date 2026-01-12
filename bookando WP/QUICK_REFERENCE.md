# Bookando - Quick Reference Guide

## Key Architecture Facts

### Tech Stack
- **Backend:** PHP 8.1+ with Composer (PSR-4 autoloading)
- **Frontend:** Vue 3 + TypeScript + Vite
- **State Management:** Pinia
- **Database:** MySQL 5.7+ / MariaDB 10.2+
- **CMS:** WordPress 6.0+

### Core Infrastructure

| Component | File | Purpose |
|-----------|------|---------|
| **Plugin Entry** | `/bookando.php` | Main WordPress plugin file |
| **Main Class** | `src/Core/Plugin.php` | Plugin initialization and hooks |
| **Module Manager** | `src/Core/Manager/ModuleManager.php` | Discover, load, activate modules |
| **Module Base** | `src/Core/Base/BaseModule.php` | Abstract class for all modules |
| **API Base** | `src/Core/Base/BaseApi.php` | Abstract class for REST APIs |
| **Model Base** | `src/Core/Model/BaseModel.php` | Abstract database model |
| **Tenant Manager** | `src/Core/Tenant/TenantManager.php` | Multi-tenant isolation |
| **License Manager** | `src/Core/Licensing/LicenseManager.php` | Feature gating & licensing |

### Directory Structure

```
bookando/
├── bookando.php                 # Plugin entry point
├── config/
│   ├── modules.php              # Module registry
│   └── tenants.php              # Tenant config
├── src/
│   ├── Core/                    # Core functionality
│   │   ├── Base/                # Base classes
│   │   ├── Manager/             # Module/state management
│   │   ├── Licensing/           # License system
│   │   ├── Tenant/              # Multi-tenancy
│   │   ├── Admin/               # WordPress admin
│   │   ├── Api/                 # REST API
│   │   ├── Auth/                # Authentication
│   │   ├── Model/               # Database models
│   │   ├── Design/              # UI components (Vue + CSS)
│   │   ├── Service/             # Core services
│   │   └── Dispatcher/          # Request routing
│   ├── modules/                 # Feature modules
│   │   ├── appointments/
│   │   ├── customers/
│   │   ├── employees/
│   │   ├── finance/
│   │   ├── resources/
│   │   ├── academy/
│   │   ├── offers/
│   │   ├── tools/
│   │   ├── settings/
│   │   ├── partnerhub/
│   │   └── workday/
│   ├── Helper/                  # Helper functions
│   └── CLI/                     # WP-CLI commands
├── scripts/
│   ├── vite.config.ts           # Build configuration
│   ├── generate-module.js       # Module generator
│   └── validate-modules.mjs     # Module validator
├── composer.json                # PHP dependencies
└── package.json                 # Node dependencies
```

## Critical Concepts

### Modules (10+)
Self-contained feature bundles:
- **appointments** - Booking management
- **customers** - CRM
- **employees** - Staff management  
- **finance** - Payments & invoices
- **resources** - Rooms & equipment
- **academy** - Training & courses
- **offers** - Service catalog
- **tools** - Custom fields & notifications
- **settings** - System configuration
- **partnerhub** - Collaboration
- **workday** - Shift planning

Each module has:
- PHP: Module.php, Model.php, RestHandler.php, Api.php, Installer.php
- Vue: main.ts, views/, components/, store/, api/
- Config: module.json
- Metadata: Capabilities.php

### Multi-Tenant Architecture
- Every table has `tenant_id` column
- `TenantManager::currentTenantId()` determines active tenant
- `BaseModel::applyTenant()` auto-isolates queries
- Default tenant ID = 1 (single-tenant mode)
- Pro Plan unlocks multiple tenants & white-label

### Licensing System
**Plans:**
- **Starter** - All core modules + basic features
- **Pro** - Starter + multi-tenant, white-label, advanced API
- **Academy** - Pro + education modules

**Files:**
- `LicenseManager.php` - License validation & feature gating
- `license-features.php` - Plan definitions
- `LicenseMiddleware.php` - REST API protection

**Feature Gates:**
```php
LicenseManager::isModuleAllowed('customers');
LicenseManager::isFeatureEnabled('calendar_sync');
```

### REST API Structure
**Base:** `/wp-json/bookando/v1/`

**Pattern per module:**
- `GET /bookando/v1/{module}` - List
- `GET /bookando/v1/{module}/:id` - Get
- `POST /bookando/v1/{module}` - Create
- `PUT /bookando/v1/{module}/:id` - Update
- `DELETE /bookando/v1/{module}/:id` - Delete

**Authentication:**
- WordPress Session (default)
- JWT Tokens
- API Keys

### Build System
**Development:**
```bash
npm run dev          # Vite dev server on localhost:5173
npm run watch:css    # Watch SCSS changes
```

**Production:**
```bash
npm run build:all    # Build CSS + JS bundles
npm run build        # Build JS only
npm run build:css    # Build CSS only
```

**Module Structure (Vite):**
- Each module = one entry point (`src/modules/{slug}/assets/vue/main.ts`)
- Output: `dist/{slug}/` with manifest.json
- CSS loaded from manifest (dev uses HMR)

### Database Queries
All models use BaseModel for safety:

```php
// Automatically tenant-scoped
$model->fetchAll(['limit' => 10]);
$model->fetchOne($id);
$model->create($data);
$model->update($id, $data);
$model->delete($id);

// Raw queries need manual tenant check
WHERE tenant_id = {$tenantId}
```

## Common Tasks

### Enable Dev Mode
```php
// wp-config.php
define('BOOKANDO_DEV', true);
```
- All modules allowed
- All features enabled
- Vite HMR active
- Debug logging enabled

### Add a New Module
1. `npm run module:make` → Interactive generator
2. Create `module.json` with metadata
3. Implement `Module.php` → register hooks/routes
4. Implement `Model.php` → extend BaseModel
5. Implement `RestHandler.php` → API endpoints
6. Create Vue components in `assets/vue/`
7. Add to `config/modules.php`
8. Add to `license-features.php`
9. `npm run build`

### Add a License Feature
1. Add to `license-features.php` → 'features' array
2. Gate in code:
   ```php
   if (LicenseManager::isFeatureEnabled('feature_name')) { /* ... */ }
   ```
3. Test with `BOOKANDO_DEV=true`

### Debug REST API
```
GET /wp-json/bookando/v1/customers
Authorization: Bearer {JWT_TOKEN}
```

Use Browser DevTools Network tab or:
```bash
curl -H "Authorization: Bearer {token}" \
  http://localhost/wp-json/bookando/v1/customers
```

## Important Files to Know

| File | Purpose |
|------|---------|
| `bookando.php` | Plugin bootstrap |
| `src/Core/Plugin.php` | Initialization sequence |
| `src/Core/Manager/ModuleManager.php` | Module lifecycle |
| `config/modules.php` | Module registry |
| `src/Core/Licensing/license-features.php` | Plan definitions |
| `scripts/vite.config.ts` | Build config |
| `src/Core/Base/BaseModule.php` | Module template |
| `src/Core/Base/BaseApi.php` | API template |
| `src/Core/Model/BaseModel.php` | DB model template |

## Quick Checklist: New Module

- [ ] Run `npm run module:make`
- [ ] Fill in module.json (name, plan, dependencies)
- [ ] Implement Model (extends BaseModel)
- [ ] Implement RestHandler (API methods)
- [ ] Implement Api class (route registration)
- [ ] Create Vue main.ts entry point
- [ ] Create at least one Vue component
- [ ] Create Installer (database setup)
- [ ] Register in config/modules.php
- [ ] Add to license-features.php
- [ ] Run `npm run lint && npm run build`
- [ ] Test: Module appears in admin menu

## Performance Tips

1. **Caching:**
   - Licenses (5 min TTL)
   - Module manifests (5 min TTL)
   - Vite availability (5 min TTL)

2. **Asset Loading:**
   - Production: Minified + manifest-based
   - Dev: HMR via Vite on :5173

3. **Queries:**
   - Always use BaseModel methods (auto-tenant)
   - Use prepare() for prepared statements
   - Add indexes on tenant_id + common filters

4. **Frontend:**
   - Lazy-load components
   - Use Pinia for global state
   - Enable code splitting in Vite

## Deployment Checklist

- [ ] Build assets: `npm run build:all`
- [ ] Set `BOOKANDO_DEV=false`
- [ ] Valid license key configured
- [ ] Database migrations run automatically
- [ ] wp-content writable for uploads
- [ ] Object cache configured (Redis/Memcached)
- [ ] HTTPS enabled
- [ ] Activity logs monitored
- [ ] Backups configured

## Testing

```bash
npm run test              # All tests
npm run test:frontend     # Unit tests only
npm run test:e2e          # Playwright e2e
npm run test:coverage     # Coverage report

# Linting
npm run lint              # Check
npm run lint:fix          # Auto-fix

# i18n
npm run i18n:audit        # Check translations
npm run i18n:fix          # Auto-fix
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Module not loading | Check module.json exists & valid JSON |
| Assets 404 | Run `npm run build` or start Vite dev server |
| API 403 Forbidden | Check license, capabilities, or JWT token |
| Tenant isolation broken | Verify BaseModel::applyTenant() called |
| Missing strings | Run `npm run i18n:fix` |
| CSS not applying | Rebuild: `npm run build:css:all` |

## Getting Help

1. Check `/docs` folder for detailed guides
2. See `/BOOKANDO_ARCHITECTURE_GUIDE.md` for full architecture
3. Review existing modules for patterns
4. Check activity logs: `Bookando > Logs` in admin
5. Enable BOOKANDO_DEBUG for detailed logging

---

**Version:** 1.0.0  
**Last Updated:** November 2025  
**Quality Score:** 74/100

For complete details, see `BOOKANDO_ARCHITECTURE_GUIDE.md`
