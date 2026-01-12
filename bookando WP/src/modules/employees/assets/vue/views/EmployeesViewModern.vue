<!-- EmployeesViewModern.vue - Modernized HRM-Style Employee View -->
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <!-- Header -->
        <template #header>
          <AppPageHeader
            :title="t('mod.employees.title')"
            hide-brand-below="md"
          >
            <template #right>
              <AppButton
                icon="user-plus"
                variant="primary"
                icon-size="xl"
                :tooltip="t('mod.employees.actions.add')"
                :icon-only-on-mobile="true"
                :is-mobile-view="belowSm"
                @click="openCreateDialog"
              >
                {{ t('mod.employees.actions.add') }}
              </AppButton>
            </template>
          </AppPageHeader>
        </template>

        <!-- Toolbar (FilterBar) -->
        <template #toolbar>
          <AppFilterBar
            :ratio="[6,3,2]"
            :ratio-mobile="[2,1]"
            stack-below="md"
            :layout-vars="{
              '--fb-left-pct': '54%',
              '--fb-center-pct': '28%',
              '--fb-right-pct': '18%',
              '--fb-inner-gap': 'var(--bookando-space-sm)'
            }"
          >
            <!-- LEFT: Search -->
            <template #left>
              <div class="bookando-flex-fill">
                <BookandoField
                  v-model="searchQuery"
                  type="search"
                  :placeholder="t('mod.employees.search_placeholder')"
                  @input="handleSearch"
                />
              </div>
            </template>

            <!-- CENTER: Status Filter Buttons -->
            <template #center>
              <AppButton
                v-for="filter in statusFilters"
                :key="filter.value"
                :variant="activeStatusFilter === filter.value ? 'primary' : 'standard'"
                size="sm"
                @click="handleFilterChange(filter.value)"
              >
                {{ filter.label }}
                <AppBadge
                  v-if="filter.count !== undefined"
                  variant="default"
                  size="sm"
                  :label="String(filter.count)"
                />
              </AppButton>
            </template>

            <!-- RIGHT: Export -->
            <template #right>
              <AppButton
                icon="download"
                variant="standard"
                size="square"
                btn-type="icononly"
                icon-size="md"
                :tooltip="t('ui.csv.export')"
                @click="exportCSV"
              />
            </template>
          </AppFilterBar>
        </template>

        <!-- CRM Split View -->
        <CRMSplitView
          :items="filteredItems"
          :auto-select-first="true"
          :multi-select="true"
          :selected-ids="selectedIds"
          :list-width="store.sidebarWidth"
          @select="handleSelect"
          @selectAll="handleSelectAll"
          @toggleSelect="handleToggleSelect"
          @resize="handleResize"
        >
          <!-- List Panel Slot -->
          <template #list="{ selectedId, onSelect }">
            <CRMListPanel
              :items="filteredItems"
              :selected-id="selectedId"
              :selected-ids="selectedIds"
              :searchable="true"
              :search-placeholder="t('mod.employees.search_placeholder')"
              :search-fields="['first_name', 'last_name', 'email', 'phone']"
              :filters="statusFilters"
              :active-filter="activeStatusFilter"
              :multi-select="true"
              :paginated="true"
              :items-per-page="30"
              :show-add-button="true"
              :add-button-label="t('mod.employees.actions.add')"
              @select="onSelect"
              @toggleSelect="handleToggleSelect"
              @selectAll="handleSelectAll"
              @search="handleSearch"
              @filterChange="handleFilterChange"
              @add="openCreateDialog"
            >
              <!-- List Items -->
              <template #default="{ items, selectedId: currentSelectedId, onSelect: handleItemSelect }">
                <CRMListItem
                  v-for="employee in items"
                  :key="employee.id"
                  :item="employee"
                  :is-active="currentSelectedId === employee.id"
                  :is-selected="selectedIds.has(employee.id)"
                  :multi-select="true"
                  :show-status="true"
                  :show-status-badge="true"
                  :show-meta="true"
                  :show-quick-actions="!isMobileView"
                  :enable-swipe="isMobileView"
                  :left-actions="getLeftSwipeActions(employee)"
                  :right-actions="getRightSwipeActions(employee)"
                  @click="handleItemSelect"
                  @toggleSelect="() => handleToggleSelect(employee.id)"
                  @edit="openEditDialog"
                  @moreActions="(item) => handleMoreActions(item)"
                >
                  <!-- Meta Info -->
                  <template #meta="{ item }">
                    <span v-if="item.position" class="crm-list-item__meta-item">
                      <AppIcon name="briefcase" />
                      {{ item.position }}
                    </span>
                    <span v-if="item.department" class="crm-list-item__meta-item">
                      <AppIcon name="users" />
                      {{ item.department }}
                    </span>
                  </template>
                </CRMListItem>
              </template>
            </CRMListPanel>
          </template>

          <!-- Detail Panel Slot -->
          <template #detail="{ item }">
            <CRMDetailPanel
              v-if="item"
              :item="item"
              :tabs="detailTabs"
              :stats="getEmployeeStats(item)"
              :editable="true"
              :edit-mode="editMode"
              @close="handleCloseDetail"
              @edit="handleEditEmployee"
              @save="handleSaveEmployee"
              @tabChange="handleTabChange"
            >
              <!-- Overview Tab -->
              <template #overview="{ item: employee, editMode: isEditMode }">
                <!-- Employment Info Section -->
                <div class="crm-detail-section">
                  <h3 class="crm-detail-section__title">
                    <AppIcon name="briefcase" />
                    {{ t('mod.employees.sections.employment') }}
                  </h3>
                  <div class="crm-detail-list">
                    <div v-if="employee.position" class="crm-detail-item">
                      <span class="crm-detail-item__label">{{ t('mod.employees.fields.position') }}:</span>
                      <span class="crm-detail-item__value">{{ employee.position }}</span>
                    </div>
                    <div v-if="employee.department" class="crm-detail-item">
                      <span class="crm-detail-item__label">{{ t('mod.employees.fields.department') }}:</span>
                      <span class="crm-detail-item__value">{{ employee.department }}</span>
                    </div>
                    <div v-if="employee.hire_date" class="crm-detail-item">
                      <span class="crm-detail-item__label">{{ t('mod.employees.fields.hire_date') }}:</span>
                      <span class="crm-detail-item__value">{{ formatDate(employee.hire_date) }}</span>
                    </div>
                  </div>
                </div>
              </template>

              <!-- Schedule Tab -->
              <template #schedule="{ item: employee }">
                <div class="crm-tab-content-section">
                  <p class="bookando-text-muted">{{ t('mod.employees.schedule_placeholder') }}</p>
                </div>
              </template>

              <!-- Availability Tab -->
              <template #availability="{ item: employee }">
                <div class="crm-tab-content-section">
                  <p class="bookando-text-muted">{{ t('mod.employees.availability_placeholder') }}</p>
                </div>
              </template>

              <!-- Performance Tab -->
              <template #performance="{ item: employee }">
                <div class="crm-tab-content-section">
                  <CRMQuickStats
                    :stats="getPerformanceStats(employee)"
                    :columns="2"
                    :show-header="false"
                  />
                </div>
              </template>

              <!-- Activity Tab -->
              <template #activity="{ item: employee }">
                <CRMActivityTimeline
                  :activities="getEmployeeActivity(employee)"
                  :title="t('mod.employees.sections.activity')"
                  :show-header="true"
                  :filterable="true"
                />
              </template>

              <!-- Notes Tab -->
              <template #notes="{ item: employee, editMode: isEditMode }">
                <div class="crm-notes-section">
                  <p class="bookando-text-muted">{{ t('mod.employees.notes_placeholder') }}</p>
                </div>
              </template>

              <!-- Files Tab -->
              <template #files="{ item: employee }">
                <div class="crm-files-section">
                  <p class="bookando-text-muted">{{ t('mod.employees.files_placeholder') }}</p>
                </div>
              </template>
            </CRMDetailPanel>
          </template>
        </CRMSplitView>

        <!-- Bulk Action Bar -->
        <transition name="slideup-bulkaction">
          <AppBulkAction
            v-if="selectedIds.size > 0"
            v-model="localBulkAction"
            class="bookando-bulk-slidein"
            :selected="Array.from(selectedIds)"
            :bulk-options="bulkActions"
            :confirm-before-apply="true"
            :loading="bulkBusy"
            @apply="onBulkApply"
            @cancel="onBulkCancel"
          >
            <template #cancel-button>
              <AppButton
                variant="secondary"
                :disabled="bulkBusy"
                @click="onBulkCancel"
              >
                {{ t('core.common.cancel') }}
              </AppButton>
            </template>
            <template #apply-button>
              <AppButton
                variant="primary"
                :disabled="!localBulkAction || bulkBusy"
                :loading="bulkBusy"
                @click="onBulkApply(localBulkAction)"
              >
                {{ bulkBusy ? t('core.bulk.applying') : t('core.bulk.apply') }}
              </AppButton>
            </template>
          </AppBulkAction>
        </transition>

        <!-- Modals -->
        <EmployeesForm
          v-if="showDialog"
          :model-value="editingEmployee"
          @save="onSaveEmployee"
          @cancel="closeDialog"
        />

        <!-- Loader -->
        <div v-if="loading" class="bookando-backdrop">
          <div class="bookando-loader" />
        </div>
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { defineAsyncComponent, ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

// Core Components
import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppBulkAction from '@core/Design/components/AppBulkAction.vue'
import AppFilterBar from '@core/Design/components/AppFilterBar.vue'
import AppBadge from '@core/Design/components/AppBadge.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

// CRM Components
import CRMSplitView from '@core/Design/components/CRMSplitView.vue'
import CRMListPanel from '@core/Design/components/CRMListPanel.vue'
import CRMListItem from '@core/Design/components/CRMListItem.vue'
import CRMDetailPanel from '@core/Design/components/CRMDetailPanel.vue'
import CRMActivityTimeline from '@core/Design/components/CRMActivityTimeline.vue'
import CRMQuickStats from '@core/Design/components/CRMQuickStats.vue'

// Store & Composables
import { useEmployeesStore } from '../store/store'
import { useEmployeeActions, employeesBulkOptions } from '../../../actions'
import { useResponsive } from '@core/Composables/useResponsive'
import { notify } from '@core/Composables/useNotifier'

const EmployeesForm = defineAsyncComponent(() => import('../components/EmployeesForm.vue'))

const MODULE = 'employees'

// Environment
const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

// Responsive
const { isBelow } = useResponsive()
const isMobileView = computed(() => isBelow('md').value)
const belowSm = isBelow('sm')

// Store & i18n
const store = useEmployeesStore()
const { loading: actionsLoading, run } = useEmployeeActions()
const { t, locale } = useI18n()
const loading = computed(() => store.loading || actionsLoading.value)

// UI State
const showDialog = ref(false)
const editingEmployee = ref<any>(null)
const editMode = ref(false)
const selectedIds = ref<Set<string>>(new Set())
const searchQuery = ref('')
const activeStatusFilter = ref('all')
const localBulkAction = ref<string>('')
const bulkBusy = ref(false)
const activeTab = ref('overview')

// Computed
const filteredItems = computed(() => {
  let items = store.items

  // Apply search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item => {
      const searchFields = ['first_name', 'last_name', 'email', 'phone', 'position', 'department']
      return searchFields.some(field => {
        const value = (item as any)[field]
        return value && String(value).toLowerCase().includes(query)
      })
    })
  }

  // Apply status filter
  if (activeStatusFilter.value && activeStatusFilter.value !== 'all') {
    items = items.filter(item => item.status === activeStatusFilter.value)
  }

  return items
})

const statusFilters = computed(() => [
  { label: t('mod.employees.filters.all'), value: 'all', count: store.items.length },
  { label: t('mod.employees.filters.active'), value: 'active', count: store.items.filter(i => i.status === 'active').length },
  { label: t('mod.employees.filters.inactive'), value: 'inactive', count: store.items.filter(i => i.status === 'inactive').length },
  { label: t('mod.employees.filters.on_leave'), value: 'on_leave', count: store.items.filter(i => i.status === 'on_leave').length }
])

const detailTabs = computed(() => [
  { id: 'overview', label: t('mod.employees.tabs.overview'), icon: 'home' },
  { id: 'contact', label: t('mod.employees.tabs.contact'), icon: 'mail' },
  { id: 'schedule', label: t('mod.employees.tabs.schedule'), icon: 'calendar' },
  { id: 'availability', label: t('mod.employees.tabs.availability'), icon: 'clock' },
  { id: 'performance', label: t('mod.employees.tabs.performance'), icon: 'trending-up' },
  { id: 'activity', label: t('mod.employees.tabs.activity'), icon: 'activity' },
  { id: 'notes', label: t('mod.employees.tabs.notes'), icon: 'file-text', count: 0 },
  { id: 'files', label: t('mod.employees.tabs.files'), icon: 'folder', count: 0 }
])

const bulkActions = computed(() => employeesBulkOptions(t))

// Methods
function handleSelect(id: string) {
  // Single select in split view
}

function handleToggleSelect(id: string) {
  const newSet = new Set(selectedIds.value)
  if (newSet.has(id)) {
    newSet.delete(id)
  } else {
    newSet.add(id)
  }
  selectedIds.value = newSet
}

function handleSelectAll(checked: boolean) {
  if (checked) {
    selectedIds.value = new Set(filteredItems.value.map(item => item.id))
  } else {
    selectedIds.value = new Set()
  }
}

function handleSearch(query: string) {
  searchQuery.value = query
}

function handleFilterChange(value: string) {
  activeStatusFilter.value = value
}

function handleResize(width: number) {
  store.setSidebarWidth(width)
}

function handleCloseDetail() {
  // Close detail panel on mobile
}

function handleEditEmployee(employee: any) {
  // Open edit dialog instead of inline editing
  openEditDialog(employee)
}

function handleSaveEmployee(employee: any) {
  editMode.value = false
  onSaveEmployee(employee)
}

function handleTabChange(tabId: string) {
  activeTab.value = tabId
}

function handleMoreActions(item: any) {
  // Show context menu for more actions
}

function openCreateDialog() {
  editingEmployee.value = null
  showDialog.value = true
}

async function openEditDialog(employee: any) {
  const fresh = employee?.id ? (await store.fetchById(employee.id)) : null
  editingEmployee.value = fresh ? { ...fresh } : { ...employee }
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  editingEmployee.value = null
}

async function onSaveEmployee(employee: any) {
  const ok = await store.save(employee)
  if (ok) {
    showDialog.value = false
    editingEmployee.value = null
    editMode.value = false
    notify('success', t('mod.employees.messages.save_success'))
  } else {
    notify('danger', (store as any).error || t('mod.employees.messages.save_error'))
  }
}

async function onBulkApply(action: string) {
  if (!action || selectedIds.value.size === 0) return
  try {
    bulkBusy.value = true
    const ids = Array.from(selectedIds.value)
    const res = await run(action as any, ids)
    if (res.ok) {
      await store.load()
      notify('success', t('core.actions.generic.success'))
      selectedIds.value = new Set()
    } else {
      notify('danger', res.message || t('core.actions.generic.error'))
    }
  } finally {
    bulkBusy.value = false
    localBulkAction.value = ''
  }
}

function onBulkCancel() {
  selectedIds.value = new Set()
  localBulkAction.value = ''
}

// Swipe Actions
function getLeftSwipeActions(employee: any) {
  return [
    {
      id: 'edit',
      label: t('core.common.edit'),
      icon: 'edit',
      variant: 'primary' as const,
      handler: (item: any) => openEditDialog(item)
    }
  ]
}

function getRightSwipeActions(employee: any) {
  return [
    {
      id: 'delete',
      label: t('core.common.delete'),
      icon: 'trash',
      variant: 'danger' as const,
      handler: (item: any) => handleDelete(item)
    }
  ]
}

async function handleDelete(employee: any) {
  try {
    const res = await run('soft_delete' as any, [employee.id])
    if (res.ok) {
      notify('success', t('core.actions.generic.success'))
      await store.load()
    }
  } catch (error) {
    notify('danger', t('core.actions.generic.error'))
  }
}

// Helper Functions
function getEmployeeStats(employee: any) {
  return [
    {
      id: 'appointments',
      label: t('mod.employees.stats.total_appointments'),
      value: 0,
      icon: 'calendar',
      variant: 'primary' as const
    },
    {
      id: 'hours',
      label: t('mod.employees.stats.hours_this_month'),
      value: 0,
      suffix: 'h',
      icon: 'clock',
      variant: 'info' as const
    },
    {
      id: 'services',
      label: t('mod.employees.stats.services'),
      value: 0,
      icon: 'briefcase',
      variant: 'success' as const
    },
    {
      id: 'tenure',
      label: t('mod.employees.stats.tenure'),
      value: calculateTenure(employee.hire_date),
      icon: 'award',
      variant: 'default' as const
    }
  ]
}

function getPerformanceStats(employee: any) {
  return [
    {
      id: 'rating',
      label: t('mod.employees.stats.average_rating'),
      value: 0,
      suffix: '/5',
      icon: 'star',
      variant: 'success' as const,
      progress: 0
    },
    {
      id: 'completed',
      label: t('mod.employees.stats.completed_services'),
      value: 0,
      icon: 'check-circle',
      variant: 'primary' as const
    },
    {
      id: 'revenue',
      label: t('mod.employees.stats.revenue_generated'),
      value: 0,
      prefix: '€',
      icon: 'dollar-sign',
      variant: 'success' as const,
      trend: 0
    },
    {
      id: 'utilization',
      label: t('mod.employees.stats.utilization_rate'),
      value: 0,
      suffix: '%',
      icon: 'activity',
      variant: 'info' as const,
      progress: 0
    }
  ]
}

function getEmployeeActivity(employee: any) {
  // TODO: Load from API
  return []
}

function formatDate(date: string | Date): string {
  if (!date) return '-'
  try {
    return new Date(date).toLocaleDateString()
  } catch {
    return String(date)
  }
}

function calculateTenure(hireDate: string | Date): string {
  if (!hireDate) return '-'
  try {
    const start = new Date(hireDate)
    const now = new Date()
    const years = now.getFullYear() - start.getFullYear()
    const months = now.getMonth() - start.getMonth()

    if (years > 0) {
      return `${years}y ${months}m`
    }
    return `${months}m`
  } catch {
    return '-'
  }
}

// CSV Export
function exportCSV() {
  const header = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Position', 'Department', 'Status']
  const rows = filteredItems.value.map((item: any) => [
    item.id ?? '',
    item.first_name ?? '',
    item.last_name ?? '',
    item.email ?? '',
    item.phone ?? '',
    item.position ?? '',
    item.department ?? '',
    item.status ?? ''
  ])

  const csvContent = [header, ...rows]
    .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `employees_export_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

// Lifecycle
onMounted(async () => {
  await store.load()
})
</script>

<style scoped>
.bookando-backdrop {
  position: fixed;
  z-index: 2200;
  inset: 0;
  background: rgba(255, 255, 255, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}

.bookando-loader {
  border: 4px solid #f3f3f3;
  border-radius: 50%;
  border-top: 4px solid #4F46E5;
  width: 48px;
  height: 48px;
  animation: spin 0.9s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.crm-tab-content-section {
  padding: var(--bookando-spacing-lg);
}

.crm-notes-section,
.crm-files-section {
  padding: var(--bookando-spacing-2xl);
  text-align: center;
}

/* Bulk Action Slide-in Animation */
.slideup-bulkaction-enter-active,
.slideup-bulkaction-leave-active {
  transition: transform 0.3s ease;
}

.slideup-bulkaction-enter-from,
.slideup-bulkaction-leave-to {
  transform: translateY(100%);
}
</style>
