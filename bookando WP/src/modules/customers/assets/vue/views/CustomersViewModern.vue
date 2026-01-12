<!-- CustomersViewModern.vue - Modernized CRM-Style Customer View -->
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
            :title="t('mod.customers.title')"
            hide-brand-below="md"
          >
            <template #right>
              <AppButton
                icon="user-plus"
                variant="primary"
                icon-size="xl"
                :tooltip="t('mod.customers.actions.add')"
                :icon-only-on-mobile="true"
                :is-mobile-view="belowSm"
                @click="openCreateDialog"
              >
                {{ t('mod.customers.actions.add') }}
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
            <!-- LEFT: Suche -->
            <template #left>
              <div class="bookando-flex-fill">
                <BookandoField
                  v-model="searchQuery"
                  type="search"
                  :placeholder="t('mod.customers.search_placeholder')"
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
              :search-placeholder="t('mod.customers.search_placeholder')"
              :search-fields="['first_name', 'last_name', 'email', 'phone']"
              :filters="statusFilters"
              :active-filter="activeStatusFilter"
              :multi-select="true"
              :paginated="true"
              :items-per-page="30"
              :show-add-button="true"
              :add-button-label="t('mod.customers.actions.add')"
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
                  v-for="customer in items"
                  :key="customer.id"
                  :item="customer"
                  :is-active="currentSelectedId === customer.id"
                  :is-selected="selectedIds.has(customer.id)"
                  :multi-select="true"
                  :show-status="true"
                  :show-status-badge="true"
                  :show-meta="true"
                  :show-quick-actions="!isMobileView"
                  :enable-swipe="isMobileView"
                  :left-actions="getLeftSwipeActions(customer)"
                  :right-actions="getRightSwipeActions(customer)"
                  @click="handleItemSelect"
                  @toggleSelect="() => handleToggleSelect(customer.id)"
                  @edit="openEditDialog"
                  @moreActions="(item) => handleMoreActions(item)"
                >
                  <!-- Meta Info -->
                  <template #meta="{ item }">
                    <span v-if="item.last_contact" class="crm-list-item__meta-item">
                      <AppIcon name="clock" />
                      {{ formatLastContact(item.last_contact) }}
                    </span>
                    <span v-if="getUpcomingAppointments(item)" class="crm-list-item__meta-item">
                      <AppIcon name="calendar" />
                      {{ getUpcomingAppointments(item) }}
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
              :stats="getCustomerStats(item)"
              :editable="true"
              :edit-mode="editMode"
              @close="handleCloseDetail"
              @edit="handleEditCustomer"
              @save="handleSaveCustomer"
              @tabChange="handleTabChange"
            >
              <!-- Overview Tab -->
              <template #overview="{ item: customer, editMode: isEditMode }">
                <!-- Contact & Personal Info are handled by default slot -->
                <!-- Add custom overview content here if needed -->
              </template>

              <!-- Appointments Tab -->
              <template #appointments="{ item: customer }">
                <div class="crm-tab-content-section">
                  <CRMActivityTimeline
                    :activities="getCustomerAppointments(customer)"
                    :title="t('mod.customers.sections.appointments')"
                    :show-header="false"
                    :filterable="true"
                  />
                </div>
              </template>

              <!-- Activity Tab -->
              <template #activity="{ item: customer }">
                <CRMActivityTimeline
                  :activities="getCustomerActivity(customer)"
                  :title="t('mod.customers.sections.activity')"
                  :show-header="true"
                  :filterable="true"
                />
              </template>

              <!-- Notes Tab -->
              <template #notes="{ item: customer, editMode: isEditMode }">
                <div class="crm-notes-section">
                  <!-- Notes content will go here -->
                  <p class="bookando-text-muted">{{ t('mod.customers.notes_placeholder') }}</p>
                </div>
              </template>

              <!-- Files Tab -->
              <template #files="{ item: customer }">
                <div class="crm-files-section">
                  <!-- Files content will go here -->
                  <p class="bookando-text-muted">{{ t('mod.customers.files_placeholder') }}</p>
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
        <CustomersForm
          v-if="showDialog"
          :model-value="editingCustomer"
          @save="onSaveCustomer"
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
import { defineAsyncComponent, ref, computed, onMounted, watch } from 'vue'
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

// Store & Composables
import { useCustomersStore } from '../store/store'
import { useCustomerActions, customersBulkOptions } from '../../../actions'
import { useResponsive } from '@core/Composables/useResponsive'
import { notify } from '@core/Composables/useNotifier'

const CustomersForm = defineAsyncComponent(() => import('../components/CustomersForm.vue'))

const MODULE = 'customers'

// Environment
const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

// Responsive
const { isBelow } = useResponsive()
const isMobileView = computed(() => isBelow('md').value)
const belowSm = isBelow('sm')

// Store & i18n
const store = useCustomersStore()
const { loading: actionsLoading, run } = useCustomerActions()
const { t, locale } = useI18n()
const loading = computed(() => store.loading || actionsLoading.value)

// UI State
const showDialog = ref(false)
const editingCustomer = ref<any>(null)
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
      const searchFields = ['first_name', 'last_name', 'email', 'phone']
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
  { label: t('mod.customers.filters.all'), value: 'all', count: store.items.length },
  { label: t('mod.customers.filters.active'), value: 'active', count: store.items.filter(i => i.status === 'active').length },
  { label: t('mod.customers.filters.inactive'), value: 'inactive', count: store.items.filter(i => i.status === 'inactive').length },
  { label: t('mod.customers.filters.blocked'), value: 'blocked', count: store.items.filter(i => i.status === 'blocked').length }
])

const detailTabs = computed(() => [
  { id: 'overview', label: t('mod.customers.tabs.overview'), icon: 'home' },
  { id: 'contact', label: t('mod.customers.tabs.contact'), icon: 'mail' },
  { id: 'appointments', label: t('mod.customers.tabs.appointments'), icon: 'calendar', count: 0 },
  { id: 'activity', label: t('mod.customers.tabs.activity'), icon: 'activity' },
  { id: 'notes', label: t('mod.customers.tabs.notes'), icon: 'file-text', count: 0 },
  { id: 'files', label: t('mod.customers.tabs.files'), icon: 'folder', count: 0 }
])

const bulkActions = computed(() => customersBulkOptions(t))

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

function handleEditCustomer(customer: any) {
  // Open edit dialog instead of inline editing
  openEditDialog(customer)
}

function handleSaveCustomer(customer: any) {
  editMode.value = false
  onSaveCustomer(customer)
}

function handleTabChange(tabId: string) {
  activeTab.value = tabId
}

function handleMoreActions(item: any) {
  // Show context menu for more actions
}

function openCreateDialog() {
  editingCustomer.value = null
  showDialog.value = true
}

async function openEditDialog(customer: any) {
  const fresh = customer?.id ? (await store.fetchById(customer.id)) : null
  editingCustomer.value = fresh ? { ...fresh } : { ...customer }
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  editingCustomer.value = null
}

async function onSaveCustomer(customer: any) {
  const ok = await store.save(customer)
  if (ok) {
    showDialog.value = false
    editingCustomer.value = null
    editMode.value = false
    notify('success', t('mod.customers.messages.save_success'))
  } else {
    notify('danger', (store as any).error || t('mod.customers.messages.save_error'))
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
function getLeftSwipeActions(customer: any) {
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

function getRightSwipeActions(customer: any) {
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

async function handleDelete(customer: any) {
  // Implement delete logic
  try {
    const res = await run('soft_delete' as any, [customer.id])
    if (res.ok) {
      notify('success', t('core.actions.generic.success'))
      await store.load()
    }
  } catch (error) {
    notify('danger', t('core.actions.generic.error'))
  }
}

// Helper Functions
function getCustomerStats(customer: any) {
  return [
    {
      id: 'appointments',
      label: t('mod.customers.stats.total_appointments'),
      value: 0,
      icon: 'calendar',
      variant: 'primary' as const
    },
    {
      id: 'revenue',
      label: t('mod.customers.stats.total_revenue'),
      value: 0,
      prefix: '€',
      icon: 'dollar-sign',
      variant: 'success' as const
    },
    {
      id: 'last_visit',
      label: t('mod.customers.stats.last_visit'),
      value: '-',
      icon: 'clock',
      variant: 'info' as const
    },
    {
      id: 'member_since',
      label: t('mod.customers.stats.member_since'),
      value: formatMemberSince(customer.created_at),
      icon: 'user-check',
      variant: 'default' as const
    }
  ]
}

function getCustomerAppointments(customer: any) {
  // TODO: Load from API
  return []
}

function getCustomerActivity(customer: any) {
  // TODO: Load from API
  return []
}

function formatLastContact(date: string | Date): string {
  if (!date) return '-'
  try {
    const d = new Date(date)
    const now = new Date()
    const diffDays = Math.floor((now.getTime() - d.getTime()) / 86400000)
    if (diffDays === 0) return t('core.time.today')
    if (diffDays === 1) return t('core.time.yesterday')
    if (diffDays < 7) return t('core.time.days_ago', { count: diffDays })
    return d.toLocaleDateString()
  } catch {
    return String(date)
  }
}

function formatMemberSince(date: string | Date): string {
  if (!date) return '-'
  try {
    const d = new Date(date)
    return d.toLocaleDateString(undefined, { month: 'short', year: 'numeric' })
  } catch {
    return String(date)
  }
}

function getUpcomingAppointments(customer: any): string {
  // TODO: Calculate upcoming appointments
  return ''
}

// CSV Export
function exportCSV() {
  const header = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status']
  const rows = filteredItems.value.map((item: any) => [
    item.id ?? '',
    item.first_name ?? '',
    item.last_name ?? '',
    item.email ?? '',
    item.phone ?? '',
    item.status ?? ''
  ])

  const csvContent = [header, ...rows]
    .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `customers_export_${new Date().toISOString().slice(0, 10)}.csv`
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
