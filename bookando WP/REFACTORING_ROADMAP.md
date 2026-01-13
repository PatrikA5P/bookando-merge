# 🔧 BOOKANDO REFACTORING ROADMAP

**Status:** Phase A+B+C1+C2 ✅ completed
**Next:** Phase C3-C6 (documented for incremental implementation)

---

## ✅ COMPLETED PHASES

### Phase A: Quick Wins (30min)
- ✅ Deleted unused *ViewModern.vue files (1.418 lines)
- ✅ Removed debug file main-debug.ts
- ✅ Removed old documentation (docs/old/, 84KB)
- ✅ Removed test-results/ artifacts
- ✅ Cleaned up 45+ console.log statements

**Impact:** -3.500 lines, -400KB

### Phase B: Code Consolidation (1h)
- ✅ Removed duplicate UI components (DutySchedulerTab, TimeTrackingTab)
- ✅ Marked duplicate Services as @deprecated:
  - WorkforceTimeTrackingService
  - DutySchedulerService
  - VacationRequestService

**Impact:** -1.882 lines duplicate code

### Phase C1: Design System Extraction (30min)
- ✅ Created `designTokens.ts` with module-specific color schemes
- ✅ Added module colors to `_tokens.scss` (11 modules)
- ✅ Ported design tokens from bookando Design repo

### Phase C2: Countries.ts Optimization (30min)
- ✅ Replaced 735KB countries.ts with NPM package
- ✅ Used i18n-iso-countries (only 5 locales)
- ✅ Bundle reduction: **87%** (196KB → 24.51KB gzip)

**Total completed:** ~6.500 lines removed, 1MB+ bundle reduction

---

## 📋 PENDING PHASES (Implementation Strategy)

### Phase C3: Refactor Large RestHandlers

**Problem:**
- `employees/RestHandler.php`: 2.732 lines (GOD class)
- `tools/RestHandler.php`: 1.306 lines
- `finance/RestHandler.php`: ~800 lines
- Repeated patterns across 11 modules

**Strategy:**
```
Split employees/RestHandler.php into:
├─ EmployeesCrudHandler.php (~400 lines)
│  └─ GET, POST, PUT, DELETE endpoints
├─ EmployeesWorkdayHandler.php (~600 lines)
│  └─ workday_sets, intervals, days_off
├─ EmployeesCalendarHandler.php (~500 lines)
│  └─ Calendar sync, availability
└─ EmployeesAvailabilityHandler.php (~400 lines)
   └─ Availability queries, month cache
```

**Implementation Plan:**
1. Create BaseRestHandler with common patterns
2. Extract CRUD operations to trait
3. Split employees first (reference implementation)
4. Apply pattern to other modules
5. Update Api.php route registration
6. Add deprecation warnings to old handlers
7. Gradual migration over 2-3 sprints

**Priority:** P1 (next sprint)
**Effort:** 5-7 days
**Risk:** MEDIUM (requires careful testing)

---

### Phase C4: Split Large Vue Components

**Problem:**
- `tools/.../DesignTab.vue`: 1.458 lines
- `tools/.../ServiceDesignForm.vue`: 1.418 lines
- `offers/.../CoursesForm.vue`: 1.332 lines
- `finance/.../FinanceView.vue`: 1.139 lines
- `employees/.../EmployeesForm.vue`: 1.084 lines

**Strategy:**
```
DesignTab.vue (1.458 lines) → Split into:
├─ DesignTabLayout.vue (~200 lines)
│  └─ Main container, tab switching
├─ DesignPreview.vue (~400 lines)
│  └─ Live preview rendering
├─ DesignForms.vue (~500 lines)
│  └─ Form configuration
└─ DesignControls.vue (~358 lines)
   └─ Color pickers, settings

ServiceDesignForm.vue (1.418 lines) → Split into:
├─ ServiceFormLayout.vue (~150 lines)
├─ ServiceGeneralTab.vue (~350 lines)
├─ ServicePricingTab.vue (~350 lines)
├─ ServiceSchedulingTab.vue (~350 lines)
└─ ServiceAdvancedTab.vue (~218 lines)
```

**Implementation Plan:**
1. Create component splits with `defineExpose` for parent access
2. Extract shared state to composables
3. Use provide/inject for deep component trees
4. Maintain i18n compliance (no hardcoded strings)
5. Test each component independently
6. Gradual rollout per module

**Priority:** P1 (next sprint)
**Effort:** 3-4 days
**Risk:** LOW (Vue supports well)

---

### Phase C5: Optimize CSS Bundle

**Problem:**
- `admin-ui.css`: 317KB (11.967 lines) compiled
- Monolithic structure, no tree-shaking
- Contains unused Tailwind classes

**Strategy:**
```
Current:
admin-ui.scss → admin-ui.css (317KB)

Proposed:
├─ _tokens.scss (variables only)
├─ _reset.scss (normalize)
├─ components/
│  ├─ _buttons.scss
│  ├─ _inputs.scss
│  ├─ _cards.scss
│  ├─ _tables.scss
│  └─ _modals.scss
└─ modules/
   ├─ _employees.scss
   ├─ _customers.scss
   └─ ... (module-specific)

Build process:
1. Component-scoped CSS (Vue <style scoped>)
2. PurgeCSS in production
3. Critical CSS inline
4. Rest lazy-loaded per module
```

**Implementation Plan:**
1. Audit current CSS usage (which classes are actually used)
2. Extract component styles to Vue files
3. Keep only global utilities in admin-ui.scss
4. Configure PurgeCSS with safelist
5. Measure bundle size improvement

**Expected Impact:** 317KB → ~80KB (75% reduction)

**Priority:** P2 (sprint after next)
**Effort:** 2-3 days
**Risk:** LOW (non-breaking)

---

### Phase C6: SaaS-Ready Architecture

**Goal:** Decouple from WordPress to enable standalone SaaS deployment

**Current State:**
- WordPress-coupled: REST API requires WP core
- Database: Direct $wpdb usage
- Auth: WordPress users only
- Deployment: WordPress plugin only

**Target State:**
- Platform-agnostic REST API
- Adapter pattern for database (WP/PostgreSQL/MySQL)
- JWT auth with WordPress fallback
- Deployable as: WP Plugin | Standalone Express | Docker

**Strategy:**

#### 1. Create Abstraction Layers

```php
// Database Adapter Pattern
interface DatabaseAdapter {
    public function query(string $sql, array $params): array;
    public function insert(string $table, array $data): int;
    public function update(string $table, array $data, array $where): bool;
    public function delete(string $table, array $where): bool;
}

class WordPressDatabaseAdapter implements DatabaseAdapter {
    // Uses $wpdb
}

class PDODatabaseAdapter implements DatabaseAdapter {
    // Uses PDO for standalone
}

// Usage:
$db = DatabaseAdapterFactory::create(); // Auto-detects environment
```

#### 2. Environment Configuration

```php
// .env support
BOOKANDO_MODE=wordpress|standalone
DB_TYPE=wordpress|mysql|postgresql
DB_HOST=localhost
DB_NAME=bookando
DB_USER=root
DB_PASS=secret

// Config loader
class EnvironmentConfig {
    public static function isWordPress(): bool;
    public static function isSaaS(): bool;
    public static function getDbAdapter(): DatabaseAdapter;
}
```

#### 3. Auth Abstraction

```php
interface AuthProvider {
    public function getCurrentUserId(): ?int;
    public function getUserByEmail(string $email): ?User;
    public function validateToken(string $token): bool;
}

class WordPressAuthProvider implements AuthProvider {
    // Uses wp_get_current_user()
}

class JWTAuthProvider implements AuthProvider {
    // Uses Firebase JWT
}
```

#### 4. REST API Decoupling

```php
// Remove WP_REST_Request dependency
abstract class BaseRestController {
    protected function handleRequest(Request $request): Response {
        // Platform-agnostic
    }
}

// Adapter for WordPress
class WordPressRestAdapter extends BaseRestController {
    public function wpHandle(WP_REST_Request $wpRequest): WP_REST_Response {
        $request = $this->convertFromWP($wpRequest);
        $response = $this->handleRequest($request);
        return $this->convertToWP($response);
    }
}
```

#### 5. Feature Flags

```typescript
// Frontend
const features = {
  multitenancy: import.meta.env.VITE_FEATURE_MULTITENANCY === 'true',
  licensing: import.meta.env.VITE_FEATURE_LICENSING === 'true',
  wordpress: import.meta.env.VITE_PLATFORM === 'wordpress',
};

if (features.wordpress) {
  // WordPress-specific code
} else {
  // Standalone SaaS code
}
```

**Implementation Plan:**

**Sprint 1:** Database Abstraction
- Create DatabaseAdapter interface
- Implement WordPressDatabaseAdapter
- Refactor 1-2 modules to use adapter
- Test in WordPress mode

**Sprint 2:** Auth Abstraction
- Create AuthProvider interface
- Implement WordPressAuthProvider
- Prepare JWT provider structure
- Update middleware to use provider

**Sprint 3:** REST Decoupling
- Create platform-agnostic Request/Response
- Extract business logic from WordPress handlers
- Create adapter layer
- Test with existing endpoints

**Sprint 4:** Environment Config
- Add .env support
- Create feature flags system
- Document deployment modes
- Create Docker setup for standalone

**Sprint 5:** Frontend Decoupling
- Remove WordPress-specific frontend code
- Use environment variables for platform detection
- Create standalone build target
- Test in both modes

**Priority:** P0 (critical for SaaS goal)
**Effort:** 15-20 days (5 sprints)
**Risk:** HIGH (architectural change)

**Success Metrics:**
- Same codebase runs as WP Plugin AND standalone
- No WordPress dependencies in business logic
- Database queries platform-agnostic
- Auth works with WordPress OR JWT
- Frontend works without WordPress admin

---

## 🎯 IMPLEMENTATION TIMELINE

### Sprint 1 (Current - Week 1-2)
- ✅ Phase A: Quick Wins
- ✅ Phase B: Consolidation
- ✅ Phase C1: Design System
- ✅ Phase C2: Countries Optimization
- 🔄 Phase C6: Database Adapter (start)

### Sprint 2 (Week 3-4)
- Phase C3: RestHandler Refactoring (employees module)
- Phase C4: Split 2-3 large Vue components
- Phase C6: Auth Abstraction

### Sprint 3 (Week 5-6)
- Phase C3: RestHandler Refactoring (remaining modules)
- Phase C4: Split remaining Vue components
- Phase C6: REST Decoupling

### Sprint 4 (Week 7-8)
- Phase C5: CSS Optimization
- Phase C6: Environment Config + Feature Flags
- Testing & Documentation

### Sprint 5 (Week 9-10)
- Phase C6: Frontend Decoupling
- Standalone deployment setup
- End-to-end testing
- Performance optimization

---

## 📊 PROGRESS TRACKING

| Phase | Status | Lines Removed | Bundle Saved | Risk | Priority |
|-------|--------|---------------|--------------|------|----------|
| A: Quick Wins | ✅ Done | 3.500 | 400KB | LOW | P0 |
| B: Consolidation | ✅ Done | 1.882 | - | LOW | P0 |
| C1: Design System | ✅ Done | - | - | LOW | P0 |
| C2: Countries | ✅ Done | 25.394 | 685KB | LOW | P0 |
| C3: RestHandlers | 📋 Planned | ~2.000 | - | MED | P1 |
| C4: Vue Components | 📋 Planned | ~1.500 | - | LOW | P1 |
| C5: CSS Optimization | 📋 Planned | - | 237KB | LOW | P2 |
| C6: SaaS-Ready | 🔄 In Progress | - | - | HIGH | P0 |

**Total Completed:** ~30.776 lines, ~1.085MB
**Total Planned:** ~3.500 lines, ~237KB

---

## 🚀 QUICK START FOR NEXT DEVELOPER

### To continue Phase C3 (RestHandler Refactoring):
```bash
# 1. Create base handler
cp src/Core/Base/BaseRestHandler.php src/Core/Base/BaseRestHandler.new.php

# 2. Split employees/RestHandler.php
# See detailed plan in Phase C3 section above

# 3. Test
npm run test
```

### To continue Phase C4 (Vue Component Splitting):
```bash
# 1. Pick largest component
# DesignTab.vue (1.458 lines)

# 2. Create sub-components
mkdir -p src/modules/tools/assets/vue/components/design/tabs/
touch src/modules/tools/assets/vue/components/design/tabs/DesignPreview.vue
# ... etc

# 3. Extract shared state to composable
touch src/modules/tools/assets/vue/composables/useDesignState.ts
```

### To continue Phase C6 (SaaS-Ready):
```bash
# 1. Create adapter interfaces
touch src/Core/Adapter/DatabaseAdapter.php
touch src/Core/Adapter/WordPressDatabaseAdapter.php

# 2. Add .env support
composer require vlucas/phpdotenv

# 3. Refactor one module to use adapter
# Start with smallest: resources or partnerhub
```

---

## 📝 NOTES

- **i18n Compliance:** All refactored code MUST use `$t()` or `__()` - no hardcoded strings!
- **Multitenancy:** All database queries MUST include `tenant_id` scoping
- **Testing:** Run `npm run test` after each phase
- **Git Strategy:** One commit per completed phase
- **Documentation:** Update this file as phases complete

---

**Last Updated:** 2026-01-13
**Author:** Claude (AI Assistant)
**Review:** Required before Phase C3 implementation
