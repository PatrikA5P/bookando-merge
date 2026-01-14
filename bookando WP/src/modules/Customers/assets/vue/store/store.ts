// src/modules/customers/assets/vue/store/store.ts

import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Customer } from '../models/CustomersModel'
import {
  getCustomers,
  getCustomer,
  createCustomer,
  updateCustomer,
  deleteCustomer as apiDeleteCustomer
} from '../api/CustomersApi'

export const useCustomersStore = defineStore('customers', () => {
  const items = ref<Customer[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Kundenliste laden
  async function load() {
    loading.value = true
    error.value = null
    try {
      const response = await getCustomers()
      items.value = response
    } catch (err: any) {
      error.value = err?.message || 'Fehler beim Laden der Kunden.'
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: number): Promise<Customer | null> {
    try {
      return await getCustomer(id)
    } catch {
      return null
    }
  }

  async function save(customer: Customer) {
    loading.value = true
    error.value = null
    try {
      if (customer.id) {
        await updateCustomer(customer.id, customer)
      } else {
        await createCustomer(customer)
      }
      await load()
      return true
    } catch (err: any) {
      error.value = err?.message || 'Fehler beim Speichern.'
      return false
    } finally {
      loading.value = false
    }
  }

  async function remove(id: number) {
    loading.value = true
    error.value = null
    try {
      await apiDeleteCustomer(id)
      await load()
      return true
    } catch (err: any) {
      error.value = err?.message || 'Fehler beim Löschen.'
      return false
    } finally {
      loading.value = false
    }
  }

  // Filter wählen und speichern (Key-Array)
const ACTIVE_FILTER_FIELDS_KEY = 'bookando_customers_active_filter_fields'
const ACTIVE_FILTERS_KEY = 'bookando_customers_active_filters'

const activeFilterFields = ref<string[]>(loadActiveFilterFields())
const activeFilters = ref<any>(loadActiveFilters())

function loadActiveFilterFields(): string[] {
  try {
    const raw = localStorage.getItem(ACTIVE_FILTER_FIELDS_KEY)
    const parsed = JSON.parse(raw || '[]')
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}
function setActiveFilterFields(fields: string[]) {
  // Immer neues Array zuweisen!
  activeFilterFields.value = [...fields]
  try { 
    localStorage.setItem(ACTIVE_FILTER_FIELDS_KEY, JSON.stringify(activeFilterFields.value))
  } catch {}
}

function loadActiveFilters(): any {
  try {
    const raw = localStorage.getItem(ACTIVE_FILTERS_KEY)
    const parsed = JSON.parse(raw || '{}')
    return typeof parsed === 'object' && parsed !== null ? parsed : {}
  } catch {
    return {}
  }
}
function setActiveFilters(val: any) {
  activeFilters.value = val
  try { localStorage.setItem(ACTIVE_FILTERS_KEY, JSON.stringify(val)) } catch {}
}

  // Spalten wählen und speichern (Key-Array)
  const VISIBLE_COLUMNS_KEY = 'bookando_customers_visible_columns'
  const visibleColumns = ref<string[]>(loadVisibleColumns())

  function loadVisibleColumns(): string[] {
    try {
      const raw = localStorage.getItem(VISIBLE_COLUMNS_KEY)
      const parsed = JSON.parse(raw || '[]')
      return Array.isArray(parsed) ? parsed.filter(k => typeof k === 'string') : []
    } catch {
      return []
    }
  }

  function setVisibleColumns(cols: string[]) {
    // **kein rawColumns prüfen** – die Validierung machen wir im Panel, nicht im Store!
    visibleColumns.value = cols
    try {
      localStorage.setItem(VISIBLE_COLUMNS_KEY, JSON.stringify(cols))
    } catch (_e) {
      console.warn('Konnte sichtbare Spalten nicht speichern:', _e)
    }
  }


const COL_WIDTHS_KEY = 'bookando_customers_col_widths'
const colWidths = ref<{ [key: string]: number }>(loadColWidths())

function loadColWidths(): { [key: string]: number } {
  try {
    const raw = localStorage.getItem(COL_WIDTHS_KEY)
    const parsed = JSON.parse(raw || '{}')
    return typeof parsed === 'object' && parsed !== null ? parsed : {}
  } catch {
    return {}
  }
}

function setColWidths(widths: { [key: string]: number }) {
  colWidths.value = { ...widths }
  try {
    localStorage.setItem(COL_WIDTHS_KEY, JSON.stringify(colWidths.value))
  } catch (_e) {
    console.warn('Konnte Spaltenbreiten nicht speichern:', _e)
  }
}

function resetColumnSettings() {
  try {
    localStorage.removeItem(VISIBLE_COLUMNS_KEY)
    localStorage.removeItem(COL_WIDTHS_KEY)
    visibleColumns.value = []      // optional: auf Default zurücksetzen
    colWidths.value = {}
  } catch (_e) {
    console.warn('Konnte Spalteneinstellungen nicht zurücksetzen:', _e)
  }
}

// Sidebar-Breite speichern
const SIDEBAR_WIDTH_KEY = 'bookando_customers_sidebar_width'
const sidebarWidth = ref<number>(loadSidebarWidth())

function loadSidebarWidth(): number {
  try {
    const raw = localStorage.getItem(SIDEBAR_WIDTH_KEY)
    const parsed = parseInt(raw || '360', 10)
    return parsed >= 280 && parsed <= 600 ? parsed : 360
  } catch {
    return 360
  }
}

function setSidebarWidth(width: number) {
  const clamped = Math.max(280, Math.min(600, width))
  sidebarWidth.value = clamped
  try {
    localStorage.setItem(SIDEBAR_WIDTH_KEY, String(clamped))
  } catch (_e) {
    console.warn('Konnte Sidebar-Breite nicht speichern:', _e)
  }
}

  return {
    items,
    loading,
    error,
    load,
    fetchById,
    save,
    remove,
    activeFilterFields,
    setActiveFilterFields,
    activeFilters,
    setActiveFilters,
    visibleColumns,
    setVisibleColumns,
    colWidths,
    setColWidths,
    resetColumnSettings,
    sidebarWidth,
    setSidebarWidth
  }
})
