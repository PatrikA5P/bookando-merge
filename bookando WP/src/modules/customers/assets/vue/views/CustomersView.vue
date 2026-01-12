<!-- CustomersView.vue -->
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
                @mouseenter="preloadCustomersForm"
                @click="openCreateDialog"
              >
                {{ t('mod.customers.actions.add') }}
              </AppButton>
            </template>
          </AppPageHeader>
        </template>

        <!-- Toolbar (Filterbar) -->
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
            <!-- LEFT: Suche + mobiler Filterbutton -->
            <template #left="{ stack }">
              <div class="bookando-flex-fill">
                <BookandoField
                  v-model="search"
                  type="search"
                  :placeholder="t('mod.customers.search_placeholder')"
                />
              </div>
              <div
                v-if="stack"
                style="margin-left:auto"
              >
                <AppButton
                  icon="filter"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  icon-size="md"
                  :tooltip="t('ui.filter.toggle')"
                  @click.stop="showFilters = !showFilters"
                />
              </div>
            </template>

            <!-- CENTER: Filter -> Columns -> Sort -->
            <template #center="{ stack }">
              <AppButton
                v-if="!stack"
                icon="filter"
                variant="standard"
                size="square"
                btn-type="icononly"
                icon-size="md"
                :tooltip="t('ui.filter.toggle')"
                @click.stop="showFilters = !showFilters"
              />
              <AppButton
                v-if="!stack && !isMobileView"
                icon="columns"
                variant="standard"
                size="square"
                btn-type="icononly"
                icon-size="md"
                :tooltip="t('ui.table.choose_columns')"
                @click.stop="showColumns = !showColumns"
              />
              <AppSort
                v-model:value="sortMobile"
                class="bookando-sort-inline"
                :options="sortOptionsMobile"
                :threshold="50"
                @update:value="onMobileSortChange"
              />
            </template>

            <!-- RIGHT: Import / Export -->
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
              <AppButton
                icon="upload"
                variant="standard"
                size="square"
                btn-type="icononly"
                icon-size="md"
                :tooltip="t('ui.csv.import')"
                @click="openImportDialog"
              />
              <input
                ref="importInput"
                type="file"
                accept=".csv"
                style="display:none"
                @change="importCSV"
              >
            </template>

            <!-- BELOW: Filterpanel & Column-Chooser -->
            <template #below>
              <transition name="accordion">
                <AppFilter
                  v-if="showFilters"
                  v-model="activeFilters"
                  v-model:active-filter-fields="activeFilterFields"
                  :show="showFilters"
                  :filters="filterOptions"
                  :labels="filterLabels"
                  :filter-field-options="allFilterFields"
                  @clear="clearAllFilters"
                  @close="showFilters = false"
                />
              </transition>

              <transition name="accordion">
                <AppColumnChooserDraggable
                  v-if="!isMobileView && showColumns"
                  v-model="visibleColumns"
                  :columns="allColumns"
                  @close="showColumns = false"
                  @autosize="onChooserAutosize"
                  @reset="onChooserReset"
                />
              </transition>
            </template>
          </AppFilterBar>
        </template>

        <!-- Main Content -->
        <!-- Tabelle / Pagination -->
        <div
          v-if="!isMobileView && useStickyTest"
          class="bookando-container bookando-p0"
        >
          <AppTableStickyTest />
        </div>

        <!-- Desktop: Grid Layout (Table + Sidebar) -->
        <div
          v-else-if="!isMobileView"
          class="bookando-page-with-sidebar"
        >
          <!-- Main Content Area (scrollable) -->
          <div class="bookando-page-with-sidebar__main">
            <CustomersTable
              :items="pagedItemsDesktop"
              :columns="allColumns"
              :visible-columns="visibleColumns"
              :col-widths="store.colWidths"
              :sort-key="sortKey"
              :sort-direction="sortDirection"
              :selected-items="selected"
              :reset-widths-trigger="resetWidthsTrigger"
              @update:col-widths="store.setColWidths"
              @edit="openEditDialog"
              @quick="onRowQuick"
              @select="onSelect"
              @sort="onSortDesktop"
              @row-click="openQuickPreview"
            />

            <div class="bookando-container bookando-p-md">
              <AppPagination
                :current-page="currentPageDesktop"
                :total-pages="totalPagesDesktop"
                :page-size="pageSizeDesktop"
                :page-size-options="pageSizeOptions"
                :total-items="filteredItems.length"
                :entity-label-singular="t('mod.customers.entity_singular')"
                :entity-label-plural="t('mod.customers.entity_plural')"
                @page-change="goToPageDesktop"
                @page-size-change="setPageSizeDesktop"
              />
            </div>
          </div>

          <!-- Sidebar (resizable width, full height) -->
          <CustomerQuickPreview
            v-if="showQuickPreview && selectedCustomer"
            :customer="selectedCustomer"
            :width="store.sidebarWidth"
            @close="closeQuickPreview"
            @edit="openFullCard"
            @resize="store.setSidebarWidth"
          />
        </div>

        <div v-else>
          <CustomersTableMobile
            :items="pagedItemsMobile"
            :selected-items="selected"
            @select="onSelect"
            @edit="openEditDialog"
          />
          <AppPagination
            :current-page="currentPageMobile"
            :total-pages="totalPagesMobile"
            :page-size="pageSizeMobile"
            :page-size-options="pageSizeOptions"
            :total-items="filteredItems.length"
            :entity-label-singular="t('mod.customers.entity_singular')"
            :entity-label-plural="t('mod.customers.entity_plural')"
            @page-change="goToPageMobile"
            @page-size-change="setPageSizeMobile"
          />
        </div>

        <!-- BULKACTION -->
        <transition name="slideup-bulkaction">
          <AppBulkAction
            v-if="filteredItems.length && selected.length"
            v-model="localBulkAction"
            class="bookando-bulk-slidein"
            :selected="selected"
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

        <!-- MODALS -->
        <CustomersForm
          v-if="showDialog"
          :model-value="editingCustomer"
          @save="onSaveCustomer"
          @cancel="closeDialog"
        />

        <!-- Zentrales Confirm für „endgültig löschen“ -->
        <AppModal
          :show="confirm.show"
          module="customers"
          action="hard_delete"
          type="danger"
          confirm-variant="danger"
          @confirm="confirmHardDelete"
          @cancel="confirm.show = false"
        />

        <!-- Loader -->
        <div
          v-if="loading"
          class="bookando-backdrop"
        >
          <div class="bookando-loader" />
        </div>

        <!-- Full-Screen Customer Card -->
        <CustomerCard
          v-if="showFullCard && selectedCustomer"
          :customer="selectedCustomer"
          @close="closeFullCard"
        />

        <div
          v-if="filteredItems.length"
          style="height:90px"
        />
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { defineAsyncComponent, ref, unref, computed, onMounted, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

// Shell & Rahmen
import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'

// Daten & Logik
import { useCustomersStore } from '../store/store'
import { useTable, type TableColumn } from '@core/Composables/useTable'
import { getGenders } from '@core/Design/data/genders'
import { getSupportedLangs } from '@core/Design/data/language-mapping'
import { languageLabel, statusLabel } from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'
import type { ActionKey } from '@core/Composables/useModuleActions'
import { useCustomerActions, customersBulkOptions } from '../../../actions'
import { useResponsive } from '@core/Composables/useResponsive'
import { notify } from '@core/Composables/useNotifier'

// UI-Komponenten
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppTableStickyTest from '@core/Design/components/AppTableStickyTest.vue'
import AppFilterBar from '@core/Design/components/AppFilterBar.vue'
import AppFilter from '@core/Design/components/AppFilter.vue'
import AppSort from '@core/Design/components/AppSort.vue'
import AppColumnChooserDraggable from '@core/Design/components/AppColumnChooserDraggable.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import CustomersTable from '../components/CustomersTable.vue'
import CustomersTableSimple from '../components/CustomersTableSimple.vue'
import CustomersTableMobile from '../components/CustomersTableMobile.vue'
import CustomerQuickPreview from '../components/CustomerQuickPreview.vue'
import CustomerCard from '../components/CustomerCard.vue'
import AppPagination from '@core/Design/components/AppPagination.vue'
import AppBulkAction from '@core/Design/components/AppBulkAction.vue'
import AppModal from '@core/Design/components/AppModal.vue'

const CustomersForm = defineAsyncComponent(() => import('../components/CustomersForm.vue'))

type BP = 'sm'|'md'|'lg'|'xl'
const MODULE = 'customers'

/* === Flags / Umgebung === */
const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

/* === Responsive === */
// xl breakpoint: Table for desktop (≥1200px), Cards for mobile/tablet (<1200px)
const breakpoint: BP = 'xl'
const { isBelow } = useResponsive()
const isMobileView = computed(() => unref(isBelow(breakpoint)))
const belowMd = isBelow('md')
const belowSm = isBelow('sm')

/* === Stores / i18n === */
const store = useCustomersStore()
const { loading: actionsLoading, run } = useCustomerActions()
const { t, locale } = useI18n()
const loading = computed(() => store.loading || actionsLoading)

/* === UI-States === */
const showDialog = ref(false)
const editingCustomer = ref<any>(null)
const confirm = ref<{ show: boolean; item: any | null }>({ show: false, item: null })
const showColumns = ref(false)
const showFilters = ref(false)
const resetWidthsTrigger = ref(0)
const showQuickPreview = ref(false)
const showFullCard = ref(false)
const selectedCustomer = ref<any>(null)

/* ===== Spalten-Definition ===== */
function makeColumns(): TableColumn[] {
  return [
    { key: 'customer',  label: fieldLabel(t, 'customer', MODULE), sortable: true,  visible: true,  filter: false, sortable_mobile: true,  visible_mobile: true },
    { key: 'id',        label: fieldLabel(t, 'id', MODULE),       sortable: true,  visible: false, filter: false, sortable_mobile: true,  visible_mobile: true },
    { key: 'first_name',label: fieldLabel(t, 'first_name', MODULE), sortable: true, visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'last_name', label: fieldLabel(t, 'last_name', MODULE),  sortable: true, visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'email',     label: fieldLabel(t, 'email', MODULE),    sortable: true,  visible: false, filter: false, sortable_mobile: true,  visible_mobile: true },
    { key: 'phone',     label: fieldLabel(t, 'phone', MODULE),    sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: true },
    { key: 'status',    label: fieldLabel(t, 'status', MODULE),   sortable: true,  visible: true,  filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'address',   label: fieldLabel(t, 'address', MODULE),  sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'address_2', label: fieldLabel(t, 'address_2', MODULE),sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'zip',       label: fieldLabel(t, 'zip', MODULE),      sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'city',      label: fieldLabel(t, 'city', MODULE),     sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'country',   label: fieldLabel(t, 'country', MODULE),  sortable: true,  visible: false, filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'birthdate', label: fieldLabel(t, 'birthdate', MODULE),sortable: true,  visible: true,  filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'gender',    label: fieldLabel(t, 'gender', MODULE),   sortable: true,  visible: true,  filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'language',  label: fieldLabel(t, 'language', MODULE), sortable: true,  visible: true,  filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'note',      label: fieldLabel(t, 'note', MODULE),     sortable: false, visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'description',label:fieldLabel(t, 'description', MODULE),sortable:false,visible:false,filter:false,sortable_mobile:false,visible_mobile:false },
    { key: 'avatar_url',label: fieldLabel(t, 'avatar_url', MODULE),sortable:false, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'timezone',  label: fieldLabel(t, 'timezone', MODULE), sortable:false, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'external_id',label:fieldLabel(t, 'external_id', MODULE),sortable:false,visible:false,filter:false,sortable_mobile:false,visible_mobile:false },
    { key: 'tenant_id', label: fieldLabel(t, 'tenant_id', MODULE),sortable:false, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'roles',     label: fieldLabel(t, 'roles', MODULE),    sortable:false, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'badge_id',  label: fieldLabel(t, 'badge_id', MODULE), sortable:false, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'password_hash', label: fieldLabel(t, 'password_hash', MODULE), sortable:false, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'password_reset_token', label: fieldLabel(t, 'password_reset_token', MODULE), sortable:false, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'created_at',label: fieldLabel(t, 'created_at', MODULE),sortable:true, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'updated_at',label: fieldLabel(t, 'updated_at', MODULE),sortable:true, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
    { key: 'deleted_at',label: fieldLabel(t, 'deleted_at', MODULE),sortable:true, visible:false, filter:false, sortable_mobile:false, visible_mobile:false },
  ]
}

const LABEL_MAP = computed<Record<string, Record<string, string>>>(() => {
  const langs = getSupportedLangs()
  return {
    status: {
      active:  statusLabel('active',  locale.value),
      blocked: statusLabel('blocked', locale.value),
      deleted: statusLabel('deleted', locale.value),
    },
    gender: Object.fromEntries(getGenders(locale.value).map(o => [o.value, o.label])),
    language: Object.fromEntries(langs.map(code => [code, languageLabel(code, locale.value)])),
  }
})

const FALLBACK_OPTIONS = computed<Record<string, string[]>>(() => {
  const langs = getSupportedLangs()
  return {
    status: ['active','blocked','deleted'],
    gender: getGenders(locale.value).map(o => o.value),
    language: langs,
  }
})
const EXCLUDED_FILTER_KEYS = ['customer', 'password_hash', 'avatar_url', 'badge_id', 'password_reset_token']

/* === Bridge für persistente Table/Filter-Settings === */
const storeBridge = {
  get visibleColumns() { return store.visibleColumns },
  setVisibleColumns: (keys: string[]) => store.setVisibleColumns(keys),
  get colWidths() { return store.colWidths },
  setColWidths: (w: Record<string, number>) => store.setColWidths(w),
  get activeFilterFields() { return store.activeFilterFields },
  setActiveFilterFields: (keys: string[]) => store.setActiveFilterFields(keys),
  get activeFilters() { return store.activeFilters },
  setActiveFilters: (val: Record<string, string[]>) => store.setActiveFilters(val)
}

/* === useTable === */
const {
  rawColumns, refreshColumns, allColumns, visibleColumns,
  search, sortKey, sortDirection, sortMobile, sortOptionsMobile, onMobileSortChange,
  allFilterFields, filterLabels, activeFilterFields, activeFilters, filterOptions, clearAllFilters,
  filteredItems, sortedItems,
  pageSizeOptions,
  pageSizeDesktop, currentPageDesktop, totalPagesDesktop, pagedItemsDesktop,
  pageSizeMobile, currentPageMobile, totalPagesMobile, pagedItemsMobile,
  goToPageDesktop, setPageSizeDesktop,
  goToPageMobile, setPageSizeMobile
} = useTable(
  {
    defaultSortKey: 'customer',
    defaultSortDir: 'asc',
    columns: makeColumns,
    itemsSource: () => store.items,
    excludedFilterKeys: EXCLUDED_FILTER_KEYS,
    labelMap: LABEL_MAP,
    fallbackOptions: FALLBACK_OPTIONS,
    pageSizeDesktopDefault: 30,
    pageSizeMobileDefault: 10
  },
  storeBridge
)

/* === Sticky-Test === */
const useStickyTest = ref(
  typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('tabletest')
)

/* === Preload CustomersForm === */
function preloadCustomersForm() {
  // Preload ohne Mounten, Fehler stillschweigend ignorieren
  import('../components/CustomersForm.vue').catch(() => {})
}

/* === Reaktivität === */
// Watch filter changes without deep: true (Pinia store already tracks reactivity)
watch([activeFilters, activeFilterFields], () => {
  currentPageDesktop.value = 1
  currentPageMobile.value = 1
}, { flush: 'post' })

watch(isMobileView, (isMobile) => {
  if (isMobile && showColumns.value) showColumns.value = false
})

function onChooserAutosize() { resetWidthsTrigger.value++ }
async function onChooserReset() {
  store.resetColumnSettings()
  const defaults = rawColumns.value.filter(c => c.visible).map(c => c.key)
  store.setVisibleColumns(defaults)
  await nextTick()
  resetWidthsTrigger.value++
}

watch(locale, async () => {
  refreshColumns()
  await nextTick()
  if (store.activeFilterFields?.length) store.setActiveFilterFields([...store.activeFilterFields])
  if (store.activeFilters && Object.keys(store.activeFilters).length) store.setActiveFilters({ ...store.activeFilters })
})

onMounted(async () => {
  await store.load()
  if (!store.activeFilterFields?.length) {
    store.setActiveFilterFields(rawColumns.value.filter(c => c.filter === true).map(c => c.key))
  }
  if (!store.activeFilters || !Object.keys(store.activeFilters).length) {
    store.setActiveFilters(Object.fromEntries(
      rawColumns.value.filter(c => c.filter === true).map(c => [c.key, [] as string[]])
    ))
  }
})

/* === Auswahl & Row-Interaktionen === */
const selected = ref<any[]>([])

function clearSelection() {
  selected.value = []
  localBulkAction.value = ''
}

function onSelect(selectedIds: any[]) {
  selected.value = selectedIds
}

function onSortDesktop({ key, direction }: { key: string; direction: 'asc'|'desc' }) {
  sortKey.value = key
  sortDirection.value = direction
}

function openCreateDialog() {
  preloadCustomersForm()
  clearSelection()
  editingCustomer.value = null
  showDialog.value = true
}

async function openEditDialog(customer: any) {
  preloadCustomersForm()
  clearSelection()
  const fresh = customer?.id ? (await store.fetchById(customer.id)) : null
  editingCustomer.value = fresh ? { ...fresh } : { ...customer }
  showDialog.value = true
}

function closeDialog() {
  showDialog.value = false
  editingCustomer.value = null
}

/* === Quick Preview & Full Card === */
function openQuickPreview(customer: any) {
  selectedCustomer.value = customer
  showQuickPreview.value = true
  showFullCard.value = false
}

function closeQuickPreview() {
  showQuickPreview.value = false
  // Delay clearing selected customer to allow animation to complete
  setTimeout(() => {
    if (!showFullCard.value) {
      selectedCustomer.value = null
    }
  }, 300)
}

function openFullCard(customer?: any) {
  if (customer) {
    selectedCustomer.value = customer
  }
  showFullCard.value = true
  showQuickPreview.value = false
}

function closeFullCard() {
  showFullCard.value = false
  // Delay clearing selected customer to allow animation to complete
  setTimeout(() => {
    if (!showQuickPreview.value) {
      selectedCustomer.value = null
    }
  }, 300)
}

async function onSaveCustomer(customer: any) {
  const ok = await store.save(customer)
  if (ok) {
    showDialog.value = false
    editingCustomer.value = null
    notify('success', t('mod.customers.messages.save_success'))
  } else {
    notify('danger', (store as any).error || t('mod.customers.messages.save_error'))
  }
}

async function onRowQuick(payload: { action: ActionKey | string; item: any } | null) {
  const { action, item } = payload || {}
  if (!item?.id) return
  try {
    if (action === 'export') {
      exportCSVForIds([item.id])
      notify('success', t('core.actions.export.success'))
      return
    }
    if (action === 'hard_delete') {
      confirm.value = { show: true, item }
      return
    }
    const res = await run(action as any, [item.id])
    res.ok
      ? notify('success', t('core.actions.generic.success'))
      : notify('danger',  res.message || t('core.actions.generic.error'))
  } catch {
    notify('danger', t('core.actions.generic.error'))
  }
}

async function confirmHardDelete() {
  const item = confirm.value.item
  confirm.value = { show: false, item: null }
  if (!item?.id) return
  try {
    const res = await run('hard_delete' as any, [item.id])
    res.ok
      ? notify('success', t('mod.customers.messages.delete_success'))
      : notify('danger',  res.message || t('mod.customers.messages.delete_error'))
  } catch {
    notify('danger', t('mod.customers.messages.delete_error'))
  }
}

/* === Bulk-Actions === */
const bulkBusy = ref(false)
const bulkActions = computed(() => customersBulkOptions(t))
const localBulkAction = ref<string>('')

async function onBulkApply(action: string) {
  if (!action || !selected.value.length) return
  try {
    bulkBusy.value = true
    if (action === 'export') {
      exportCSVForIds(selected.value)
      notify('success', t('core.actions.export.success'))
      return
    }
    const res = await run(action as any, selected.value)
    if (res.ok) {
      await store.load()
      notify('success', t('core.actions.generic.success'))
    } else {
      notify('danger', res.message || t('core.actions.generic.error'))
    }
  } finally {
    bulkBusy.value = false
    clearSelection()
  }
}

function onBulkCancel() {
  clearSelection()
}

/* === CSV Export/Import === */
function exportCSV() {
  const header = visibleColumns.value.map(
    key => allColumns.value.find(col => col.key === key)?.label || key
  )
  const rows = filteredItems.value.map((item: any) =>
    visibleColumns.value.map(key => (item as any)[key] ?? '')
  )
  const csvContent = [header, ...rows]
    .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `kunden_export_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

function exportCSVForIds(ids: (string|number)[]) {
  const header = visibleColumns.value.map(
    key => allColumns.value.find(col => col.key === key)?.label || key
  )
  const rows = filteredItems.value
    .filter((item: any) => ids.includes(item.id))
    .map((item: any) => visibleColumns.value.map(key => item[key] ?? ''))

  const csv = [header, ...rows]
    .map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `kunden_export_${new Date().toISOString().slice(0,10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

const importInput = ref<HTMLInputElement | null>(null)
function openImportDialog() { importInput.value?.click() }
function importCSV(event: Event) {
  const input = event.target as HTMLInputElement | null
  const files = input?.files
  if (!files?.length) return
  const file = files.item(0)
  if (!file) return
  const reader = new FileReader()
  reader.onload = (evt: ProgressEvent<FileReader>) => {
    if (typeof evt.target?.result === 'string') {
      alert(t('ui.csv.import_demo'))
    }
  }
  reader.readAsText(file)
}
</script>

<style scoped>
.bookando-backdrop {
  position: fixed;
  z-index: 2200;
  inset: 0;
  background: rgba(255,255,255,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}
.bookando-loader {
  border: 4px solid #f3f3f3;
  border-radius: 50%;
  border-top: 4px solid #4F46E5;
  width: 48px; height: 48px;
  animation: spin 0.9s linear infinite;
}
@keyframes spin { 0% { transform: rotate(0deg);} 100% { transform: rotate(360deg);} }

/* Grid Layout: Table + Sidebar */
.bookando-page-with-sidebar {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0;
  min-height: 400px;
}

.bookando-page-with-sidebar:has(.customer-quick-preview) {
  grid-template-columns: 1fr auto;
}

.bookando-page-with-sidebar__main {
  min-width: 0;
  overflow-y: auto;
  max-height: calc(100vh - 280px);
}
</style>

