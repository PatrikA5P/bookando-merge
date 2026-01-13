<!-- EmployeesView.vue -->
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
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
                @mouseenter="preloadEmployeesForm"
                @click="openCreateDialog"
              >
                {{ t('mod.employees.actions.add') }}
              </AppButton>
            </template>
          </AppPageHeader>
        </template>

        <template #toolbar>
          <AppFilterBar
            :ratio="[5,3,2]"
            :ratio-mobile="[2,1]"
            stack-below="md"
            :layout-vars="{
              '--fb-left-pct': '50%',
              '--fb-center-pct': '30%',
              '--fb-right-pct': '20%',
              '--fb-inner-gap': 'var(--bookando-space-sm)'
            }"
          >
            <!-- LEFT -->
            <template #left="{ stack }">
              <div class="bookando-flex-fill">
                <BookandoField
                  v-model="search"
                  type="search"
                  :placeholder="t('mod.employees.search_placeholder')"
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

            <!-- CENTER -->
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

            <!-- RIGHT -->
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

            <!-- BELOW -->
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

        <!-- Desktop: Grid Layout (Table + Sidebar) -->
        <div
          v-if="!isMobileView"
          class="bookando-page-with-sidebar"
        >
          <!-- Main Content Area (scrollable) -->
          <div class="bookando-page-with-sidebar__main">
            <EmployeesTable
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
                :entity-label-singular="t('mod.employees.entity_singular')"
                :entity-label-plural="t('mod.employees.entity_plural')"
                @page-change="goToPageDesktop"
                @page-size-change="setPageSizeDesktop"
              />
            </div>
          </div>

          <!-- Sidebar (resizable width, full height) -->
          <EmployeeQuickPreview
            v-if="showQuickPreview && selectedEmployee"
            :employee="selectedEmployee"
            :width="store.sidebarWidth"
            @close="closeQuickPreview"
            @edit="openFullCard"
            @resize="store.setSidebarWidth"
          />
        </div>

        <div v-else>
          <EmployeesTableMobile
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
            :entity-label-singular="t('mod.employees.entity_singular')"
            :entity-label-plural="t('mod.employees.entity_plural')"
            @page-change="goToPageMobile"
            @page-size-change="setPageSizeMobile"
          />
        </div>

        <!-- Bulk-Actions -->
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

        <!-- Formular-Dialog -->
        <EmployeesForm
          v-if="showDialog"
          :key="editingEmployee?.id || 'new'"
          :model-value="editingEmployee"
          @save="onSaveEmployee"
          @cancel="closeDialog"
        />

        <!-- Hard-Delete Confirm (zentral) -->
        <AppModal
          :show="confirm.show"
          module="employees"
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

        <div
          v-if="filteredItems.length"
          style="height:90px"
        />
      </AppPageLayout>

      <!-- Full-Screen Employee Card -->
      <EmployeeCard
        v-if="showFullCard && selectedEmployee"
        :employee="selectedEmployee"
        @close="closeFullCard"
      />
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { defineAsyncComponent, ref, unref, computed, onMounted, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'

/* Shell */
import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue' 

/* Daten & Logik */
import { useEmployeesStore, EmployeeFormVM } from '../store/store'
import { useTable, type TableColumn } from '@core/Composables/useTable'
import { getGenders } from '@core/Design/data/genders'
import { getSupportedLangs } from '@core/Design/data/language-mapping'
import { languageLabel, statusLabel } from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'
import type { ActionKey } from '@core/Composables/useModuleActions'
import { useEmployeeActions, employeesBulkOptions } from '../../../actions'
import { useResponsive } from '@core/Composables/useResponsive'
import { notify } from '@core/Composables/useNotifier'

/* UI */
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppFilterBar from '@core/Design/components/AppFilterBar.vue'
import AppFilter from '@core/Design/components/AppFilter.vue'
import AppSort from '@core/Design/components/AppSort.vue'
import AppColumnChooserDraggable from '@core/Design/components/AppColumnChooserDraggable.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import EmployeesTable from '../components/EmployeesTable.vue'
import EmployeesTableMobile from '../components/EmployeesTableMobile.vue'
import AppPagination from '@core/Design/components/AppPagination.vue'
import AppBulkAction from '@core/Design/components/AppBulkAction.vue'
import AppModal from '@core/Design/components/AppModal.vue'
import EmployeeQuickPreview from '../components/EmployeeQuickPreview.vue'
import EmployeeCard from '../components/EmployeeCard.vue'

const EmployeesForm = defineAsyncComponent(() => import('../components/EmployeesForm.vue'))

type BP = 'sm'|'md'|'lg'|'xl'
const MODULE = 'employees'

/* Flags / Umgebung */
const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

/* Responsive */
const breakpoint: BP = 'xl'
const { isBelow } = useResponsive()
const isMobileView = computed(() => unref(isBelow(breakpoint)))
const belowSm = isBelow('sm')

/* Stores / i18n */
const store = useEmployeesStore()
const { loading: actionsLoading, run } = useEmployeeActions()
const { t, locale } = useI18n()
const loading = computed(() => store.loading || actionsLoading)

/* UI-States */
const showDialog = ref(false)
const editingEmployee = ref<any>(null)
const confirm = ref<{ show: boolean; item: any | null }>({ show: false, item: null })
const showColumns = ref(false)
const showFilters = ref(false)
const resetWidthsTrigger = ref(0)
const showQuickPreview = ref(false)
const showFullCard = ref(false)
const selectedEmployee = ref<any>(null)

/* Spalten-Definition (Start mit Kernfeldern aus wp_bookando_users) */
function makeColumns(): TableColumn[] {
  return [
    { key: 'employee',   label: fieldLabel(t, 'employee', MODULE),    sortable: true,  visible: true,  filter: false, sortable_mobile: true,  visible_mobile: true },
    { key: 'id',         label: fieldLabel(t, 'id', MODULE),          sortable: true,  visible: false, filter: false, sortable_mobile: true,  visible_mobile: true },
    { key: 'first_name', label: fieldLabel(t, 'first_name', MODULE),  sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'last_name',  label: fieldLabel(t, 'last_name', MODULE),   sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'email',      label: fieldLabel(t, 'email', MODULE),       sortable: true,  visible: true,  filter: false, sortable_mobile: true,  visible_mobile: true },
    { key: 'phone',      label: fieldLabel(t, 'phone', MODULE),       sortable: true,  visible: true,  filter: false, sortable_mobile: false, visible_mobile: true },
    { key: 'status',     label: fieldLabel(t, 'status', MODULE),      sortable: true,  visible: true,  filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'language',   label: fieldLabel(t, 'language', MODULE),    sortable: true,  visible: true,  filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'location',   label: fieldLabel(t, 'location', MODULE),    sortable: true,  visible: false, filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'services',   label: fieldLabel(t, 'services', MODULE),    sortable: false, visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'calendar',   label: fieldLabel(t, 'calendar', MODULE),    sortable: false, visible: false, filter: true,  sortable_mobile: false, visible_mobile: false },
    { key: 'created_at', label: fieldLabel(t, 'created_at', MODULE),  sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
    { key: 'updated_at', label: fieldLabel(t, 'updated_at', MODULE),  sortable: true,  visible: false, filter: false, sortable_mobile: false, visible_mobile: false },
  ]
}

/* Label/Filter-Optionen */
const LABEL_MAP = computed<Record<string, Record<string, string>>>(() => {
  const langs = getSupportedLangs()
  return {
    status: {
      active:  statusLabel('active',  locale.value),
      blocked: statusLabel('blocked', locale.value),
      deleted: statusLabel('deleted', locale.value),
    },
    language: Object.fromEntries(langs.map(code => [code, languageLabel(code, locale.value)])),
    calendar: {
      google:  t('ui.calendar.providers.google')  || 'Google',
      outlook: t('ui.calendar.providers.outlook') || 'Outlook',
      apple:   t('ui.calendar.providers.apple')   || 'Apple'
    },
  }
})

const FALLBACK_OPTIONS = computed<Record<string, string[]>>(() => {
  const langs = getSupportedLangs()
  return {
    status: ['active','blocked','deleted'],
    language: langs,
    calendar: ['google','outlook','apple'],
  }
})
const EXCLUDED_FILTER_KEYS = ['employee', 'services']

/* Persistenz-Bridge */
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

/* useTable */
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
    defaultSortKey: 'employee',
    defaultSortDir: 'asc',
    columns: makeColumns,
    itemsSource: () => store.items,
    excludedFilterKeys: EXCLUDED_FILTER_KEYS,
    labelMap: LABEL_MAP,
    fallbackOptions: FALLBACK_OPTIONS,
    pageSizeDesktopDefault: 30,
    pageSizeMobileDefault: 10,
    /* Such-Mapper: nach Name, Email, Telefon */
    searchMapper: (item: any) => [
      item.first_name, item.last_name, item.email, item.phone
    ].filter(Boolean).join(' ')
  },
  storeBridge
)

/* Preload Form */
function preloadEmployeesForm() {
  import('../components/EmployeesForm.vue').catch(() => {})
}

/* Reaktivitaet */
// Watch filter changes without deep: true (Pinia store already tracks reactivity)
watch([activeFilters, activeFilterFields], () => {
  currentPageDesktop.value = 1
  currentPageMobile.value = 1
}, { flush: 'post' })

watch(isMobileView, (isMobile) => { if (isMobile && showColumns.value) showColumns.value = false })

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

/* Auswahl & Row-Interaktionen */
const selected = ref<any[]>([])
function clearSelection() { selected.value = []; localBulkAction.value = '' }
function onSelect(selectedIds: any[]) { selected.value = selectedIds }
function onSortDesktop({ key, direction }: { key: string; direction: 'asc'|'desc' }) { sortKey.value = key; sortDirection.value = direction }

function openCreateDialog() {
  preloadEmployeesForm()
  clearSelection()
  editingEmployee.value = null
  showDialog.value = true
}

let currentEditReq = 0 // verhindert, dass alte Antworten neue überschreiben

async function openEditDialog(employee: any) {
  preloadEmployeesForm()
  clearSelection()

  if (!employee?.id) {
    editingEmployee.value = null
    showDialog.value = true
    return
  }

  const myReq = ++currentEditReq
  
  try {
    // WICHTIG: Lade VOLLSTÄNDIG VOR dem Öffnen!
    const fullData = await store.fetchById(employee.id)
    
    if (myReq !== currentEditReq) return
    
    if (fullData) {
      editingEmployee.value = {
        ...fullData,
        workday_sets: fullData.workday_sets || [],
        working_hours: fullData.working_hours || [],
        days_off: fullData.days_off || [],
        special_days: fullData.special_days || [],
        special_day_sets: fullData.special_day_sets || [],
        assigned_services: fullData.assigned_services || fullData.services || [],
        calendars: fullData.calendars || []
      }
    } else {
      editingEmployee.value = { ...employee }
    }
    
    showDialog.value = true  // <-- Jetzt erst öffnen!
    
  } catch (error) {
    console.error('[EmployeesView] Error:', error)
    editingEmployee.value = { ...employee }
    showDialog.value = true
  }
}

function closeDialog() {
  showDialog.value = false
  editingEmployee.value = null  // Referenz freigeben
}

/* Quick Preview & Full Card */
function openQuickPreview(employee: any) {
  selectedEmployee.value = employee
  showQuickPreview.value = true
  showFullCard.value = false
}

function closeQuickPreview() {
  showQuickPreview.value = false
  setTimeout(() => {
    if (!showFullCard.value) {
      selectedEmployee.value = null
    }
  }, 300)
}

function openFullCard(employee?: any) {
  if (employee) {
    selectedEmployee.value = employee
  }
  showFullCard.value = true
  showQuickPreview.value = false
}

function closeFullCard() {
  showFullCard.value = false
  setTimeout(() => {
    if (!showQuickPreview.value) {
      selectedEmployee.value = null
    }
  }, 300)
}

async function onSaveEmployee(vm: EmployeeFormVM) {
  const ok = await store.saveFromForm(vm)
  if (ok) {
    showDialog.value = false
    editingEmployee.value = null
    notify('success', t('mod.employees.messages.save_success'))
  } else {
    notify('danger', (store as any).error || t('mod.employees.messages.save_error'))
  }
}

/* Quick- / Row-Actions */
async function onRowQuick(_payload: { action: ActionKey | string; item: any }) {
  const { action, item } = _payload || {}
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
      ? notify('success', t('mod.employees.messages.delete_success'))
      : notify('danger',  res.message || t('mod.employees.messages.delete_error'))
  } catch {
    notify('danger', t('mod.employees.messages.delete_error'))
  }
}

/* Bulk-Actions */
const bulkBusy = ref(false)
const bulkActions = computed(() => employeesBulkOptions(t))
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

function onBulkCancel() { clearSelection() }

/* CSV Export/Import */
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
  link.download = `mitarbeitende_export_${new Date().toISOString().slice(0,10)}.csv`
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
  a.download = `mitarbeitende_export_${new Date().toISOString().slice(0,10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

const importInput = ref<HTMLInputElement | null>(null)
function openImportDialog() { importInput.value?.click() }
function importCSV(event: any) {
  const files = event.target.files
  if (!files?.length) return
  const reader = new FileReader()
  reader.onload = (_e) => {
    if (typeof (_e.target as any)?.result === 'string') {
      alert(t('ui.csv.import_demo'))
    }
  }
  reader.readAsText(files[0])
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

.bookando-page-with-sidebar:has(.employee-quick-preview) {
  grid-template-columns: 1fr auto;
}

.bookando-page-with-sidebar__main {
  min-width: 0;
  overflow-y: auto;
  max-height: calc(100vh - 280px);
}
</style>
