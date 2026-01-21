# BOOKANDO MODULE DISCREPANCY ANALYSIS
## Comparison: bookando Design (React) vs bookando WP (Vue)

Generated: 2026-01-21

---

## ✅ MODULE TAB STRUCTURE (Design Reference)

### **Academy Module**
**Design Tabs:** `courses | lessons | badges | cards`
- courses: Course management
- lessons: Individual lesson content
- badges: Achievement badges
- cards: Education cards

### **Finance Module**
**Design Tabs:** `overview | invoicing | accounting | payroll | commissions | vouchers`
- overview: Financial dashboard
- invoicing: Invoice management
- accounting: Bookkeeping
- payroll: Salary management
- commissions: Commission tracking
- vouchers: Voucher management

### **Offers Module**
**Design Tabs:** `catalog | categories | bundles | vouchers | forms | tags | extras | pricing`
- catalog: Main service catalog
- categories: Service categories
- bundles: Service packages
- vouchers: Gift vouchers
- forms: Booking forms
- tags: Tag management
- extras: Additional services
- pricing: Dynamic pricing rules

### **Resources Module**
**Design Tabs:** `locations | rooms | equipment`
- locations: Business locations
- rooms: Room management
- equipment: Equipment/vehicles

### **Workday Module (Arbeitsalltag)**
**Design Tabs:** `appointments | timetracking | shifts | absences | planner`
- appointments: Daily appointment view
- timetracking: Time tracking
- shifts: Shift management
- absences: Absence management
- planner: Schedule planner

### **Employees Module**
**Design Tabs:** `profile | address | hr | services` (inside employee detail form)
- profile: Basic info
- address: Contact details
- hr: HR data
- services: Service assignments

### **Customers Module**
**Design Tabs:** None (single view with filters)

---

## 🔴 CRITICAL ISSUES IN BOOKANDO WP

### 1. **Offers Module - COMPLETELY WRONG**

**Current State (WP):**
- Separate menu items: "Dienstleistungen", "Kurse", "Online"
- Wrong tabs based on offer_type field
- Doesn't match Design at all!

**Expected State (Design):**
- Single menu "Angebote"
- Tabs: Catalog, Categories, Bundles, Vouchers, Forms, Tags, Extras, Pricing

**Action Required:**
- ❌ Remove offer_type based navigation
- ❌ Remove Dienstleistungen/Kurse/Online structure
- ✅ Implement 8 tabs matching Design
- ✅ Create tab components: CatalogTab, CategoriesTab, BundlesTab, etc.

### 2. **Finance Module - Tabs not working**

**Current State (WP):**
- Tabs exist but don't work (clicking doesn't switch)
- "Add" button does nothing

**Expected State (Design):**
- 6 working tabs: overview, invoicing, accounting, payroll, commissions, vouchers
- Each tab has functional add button

**Action Required:**
- ✅ Fix tab switching logic in FinanceView.vue
- ✅ Add proper @update:active-tab handler
- ✅ Implement action buttons per tab

### 3. **Resources Module - Tabs not working**

**Current State (WP):**
- Same tab issue as Finance

**Expected State (Design):**
- 3 tabs: locations, rooms, equipment

**Action Required:**
- ✅ Fix tab navigation
- ✅ Implement ResourcesView with proper tabs

### 4. **Workday Module - Tabs not working**

**Current State (WP):**
- Same tab issue

**Expected State (Design):**
- 5 tabs: appointments, timetracking, shifts, absences, planner

**Action Required:**
- ✅ Fix tab navigation
- ✅ Implement WorkdayView with proper tabs

### 5. **Academy Module - Buttons not working**

**Current State (WP):**
- "Lektion erstellen" shows placeholder message
- "Hinzufügen" (in Kurse tab) does nothing

**Expected State (Design):**
- 4 functional tabs: courses, lessons, badges, cards
- All add buttons open proper modals

**Action Required:**
- ✅ Implement CourseEditor modal
- ✅ Implement LessonEditor modal
- ✅ Connect add buttons to modals

### 6. **Employees Module - Empty modal**

**Current State (WP):**
- Add button opens empty modal

**Expected State (Design):**
- Modal with 4 tabs: profile, address, hr, services

**Action Required:**
- ✅ Fix EmployeesForm.vue to render content
- ✅ Add dummy employees in Installer.php

### 7. **DesignFrontend Module - Not visible**

**Current State (WP):**
- Module exists but not in menu

**Expected State (Design):**
- Visible module with portal management features

**Action Required:**
- ✅ Fix module registration in Module.php
- ✅ Use BaseModule register() pattern
- ✅ Register via Core Menu system

---

## 🎯 ROOT CAUSES

### Issue Pattern #1: Tab Navigation Broken
**Affected:** Finance, Resources, Workday, Tools

**Cause:** Tabs are rendered but activeTab state not properly connected to content rendering

**Solution Template:**
```vue
<ModuleLayout
  :tabs="tabs"
  :active-tab="activeTab"
  @update:active-tab="handleTabChange"
>
  <OverviewTab v-if="activeTab === 'overview'" />
  <InvoicingTab v-if="activeTab === 'invoicing'" />
  <!-- etc -->
</ModuleLayout>

<script setup>
const activeTab = ref('overview')
const handleTabChange = (newTab) => {
  activeTab.value = newTab
}
</script>
```

### Issue Pattern #2: Modals not opening
**Affected:** Academy, Employees

**Cause:** Buttons have @click handlers but modals not registered or empty

**Solution:** Connect showDialog state to modal component

### Issue Pattern #3: Wrong architecture
**Affected:** Offers module

**Cause:** Implemented custom structure instead of copying Design 1:1

**Solution:** Completely rebuild Offers to match Design structure

---

## 📋 IMPLEMENTATION PRIORITY

### **Phase 1: Quick Fixes (1-2 hours)**
1. ✅ Fix Customers Form (DONE)
2. ✅ Add dummy customers (DONE)
3. ✅ Consolidate Offers menu (DONE - PHP only)
4. ⏳ Fix Finance tab navigation
5. ⏳ Fix Resources tab navigation
6. ⏳ Fix Workday tab navigation
7. ⏳ Fix Tools tab navigation

### **Phase 2: Modal & Form Fixes (2-3 hours)**
8. ⏳ Fix Employees modal
9. ⏳ Add dummy employees
10. ⏳ Fix Academy course creation
11. ⏳ Fix Academy lesson creation

### **Phase 3: Major Refactor (4-6 hours)**
12. ⏳ Rebuild Offers module with correct tabs:
    - CatalogTab component
    - CategoriesTab component
    - BundlesTab component
    - VouchersTab component
    - FormsTab component
    - TagsTab component
    - ExtrasTab component
    - PricingTab component

### **Phase 4: Module Visibility (1 hour)**
13. ⏳ Fix DesignFrontend module registration

---

## 🔧 TECHNICAL DEBT

### Database Schema Issues
- `offer_type` field in offers table was added for wrong architecture
- Should be removed or repurposed when rebuilding Offers module

### Code Quality
- Many modules have tab logic but content components missing
- ModuleLayout component exists and works (see Academy) but not used everywhere
- Inconsistent patterns across modules

---

## ✅ VERIFICATION CHECKLIST

After implementing fixes, verify:
- [ ] All module tabs switch correctly
- [ ] All "Add" buttons open proper modals/forms
- [ ] All modals have content (not empty)
- [ ] Offers module matches Design 1:1
- [ ] Dummy data exists for Customers and Employees
- [ ] DesignFrontend is visible in menu
- [ ] No console errors in browser
- [ ] All i18n keys exist (no "missing translation" warnings)
