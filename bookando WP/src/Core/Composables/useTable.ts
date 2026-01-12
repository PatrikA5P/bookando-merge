// src/Core/Composables/useTable.ts
import { ref, computed, watch } from 'vue'
import throttle from 'lodash/throttle'

/**
 * Generische Tabellen-Logik (Filter, Sort, Suche, Pagination, Spaltensichtbarkeit).
 * - KEINE Modul-Defaults hier drin. Alles Modulspezifische kommt als Props rein.
 */

export type TableItem = Record<string, any>

export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  visible?: boolean
  minWidth?: number
  width?: number
  maxWidth?: number
  autoMaxWidth?: number
  filter?: boolean
  sortable_mobile?: boolean
  visible_mobile?: boolean
  [key: string]: any
}

export interface UseTableStoreBridge {
  // Persistenz-Hooks für Sichtbarkeit, Breiten & Filterfelder/-werte
  visibleColumns?: string[]
  setVisibleColumns: (keys: string[]) => void

  colWidths?: Record<string, number>
  setColWidths: (w: Record<string, number>) => void

  activeFilterFields?: string[]
  setActiveFilterFields: (keys: string[]) => void

  activeFilters?: Record<string, string[]>
  setActiveFilters: (val: Record<string, string[]>) => void
}

export interface UseTableConfig {
  /** vom Modul gesetzt */
  defaultSortKey: string
  defaultSortDir: 'asc' | 'desc'
  columns: () => TableColumn[]           // Factory, damit i18n-Labels neu gebaut werden können
  itemsSource: () => TableItem[]         // Datenquelle (Store o.ä.)
  excludedFilterKeys?: string[]          // z.B. ['customer', 'password_hash', ...]
  labelMap?: Record<string, Record<string, string>> // Labeling für Filteroptionen
  fallbackOptions?: Record<string, string[]>        // Fallbackwerte, wenn Daten leer

  /** optionale Paginierung-Defaults */
  pageSizeDesktopDefault?: number
  pageSizeMobileDefault?: number
}

export function useTable(cfg: UseTableConfig, store: UseTableStoreBridge) {
  // ======= Columns =======
  const rawColumns = ref<TableColumn[]>(cfg.columns())

  // Wenn Spaltenfactory extern neu evaluiert wird (z.B. bei Locale-Change), einfach Setter nutzen:
  function refreshColumns() {
    rawColumns.value = cfg.columns()
  }

  const allColumns = computed<TableColumn[]>(() =>
    rawColumns.value.map(col => ({ sortable: true, visible: true, ...col }))
  )

  const visibleColumns = computed<string[]>({
    get() {
      const allKeys = allColumns.value.map(c => c.key)
      const storeKeys = (store.visibleColumns || []).filter(k => allKeys.includes(k))
      return storeKeys.length
        ? storeKeys
        : allColumns.value.filter(c => c.visible !== false).map(c => c.key)
    },
    set(val) {
      store.setVisibleColumns([...(Array.isArray(val) ? val : [])])
    }
  })

  // ======= Suche / Sortierung (Desktop/Mobile) =======
  const search = ref('')

  const sortKey = ref<string>(cfg.defaultSortKey)
  const sortDirection = ref<'asc' | 'desc'>(cfg.defaultSortDir)
  const sortMobile = ref<string>('')

  watch([sortKey, sortDirection], ([k, d]) => {
    sortMobile.value = (k && d) ? `${k}:${d}` : ''
  })

  const sortOptionsMobile = computed(() =>
    rawColumns.value
      .filter(col => col.sortable_mobile)
      .flatMap(col => ([
        { value: `${col.key}:asc`,  label: `${col.label} aufsteigend`,  icon: 'chevron-up' },
        { value: `${col.key}:desc`, label: `${col.label} absteigend`,   icon: 'chevron-down' }
      ]))
  )

  function onMobileSortChange(val: string) {
    if (!val) return
    const [key, dir] = val.split(':')
    sortKey.value = key
    sortDirection.value = (dir === 'desc' ? 'desc' : 'asc')
  }

  // ======= Filter =======
  const EXCLUDED_FILTER_KEYS = cfg.excludedFilterKeys || []

  const DEFAULT_FILTER_KEYS = computed<string[]>(() =>
    rawColumns.value.filter(c => c.filter === true).map(c => c.key)
  )

  const DEFAULT_FILTER_VALUES = computed<Record<string, string[]>>(() =>
    Object.fromEntries(DEFAULT_FILTER_KEYS.value.map(k => [k, [] as string[]]))
  )

  const allowedFilterKeys = computed<string[]>(() =>
    rawColumns.value
      .filter(col => !!col.key && !EXCLUDED_FILTER_KEYS.includes(col.key))
      .map(col => col.key)
  )

  function sanitizeActiveFilterFields(fields: any) {
    const keys = allFilterFields.value.map(f => f.key)
    return (Array.isArray(fields) ? fields : []).filter(f => keys.includes(f))
  }
  function sanitizeActiveFilters(filters: any) {
    const keys = allFilterFields.value.map(f => f.key)
    if (!filters || typeof filters !== 'object') return {}
    return Object.fromEntries(Object.entries(filters).filter(([key]) => keys.includes(key)))
  }

  const allFilterFields = computed(() =>
    rawColumns.value
      .filter(col => !!col.key && !EXCLUDED_FILTER_KEYS.includes(col.key))
      .map(col => ({ key: col.key, label: col.label || col.key }))
  )
  const filterLabels = computed<Record<string, string>>(() =>
    Object.fromEntries(allFilterFields.value.map(f => [f.key, f.label]))
  )

  const activeFilterFields = computed<string[]>({
    get: () => sanitizeActiveFilterFields(store.activeFilterFields),
    set: (value) => store.setActiveFilterFields([...sanitizeActiveFilterFields(value)])
  })
  const activeFilters = computed<Record<string, string[]>>({
    get: () => sanitizeActiveFilters(store.activeFilters),
    set: (value) => store.setActiveFilters(sanitizeActiveFilters(value))
  })

  const filterOptionsRaw = computed(() => {
    const arr = cfg.itemsSource() || []
    const result: Record<string, Array<{ label: string; value: any }>> = {}
    for (const f of allFilterFields.value) {
      const key = f.key
      let filtered = arr
      // Berücksichtige bereits gesetzte Filter (außer den aktuellen Key)
      for (const filterKey of Object.keys(activeFilters.value)) {
        if (filterKey !== key && (activeFilters.value as any)[filterKey]?.length) {
          filtered = filtered.filter((item: any) => {
            let itemVal: any = item[filterKey]
            if (typeof itemVal === 'number') itemVal = String(itemVal)
            return (activeFilters.value as any)[filterKey].includes(itemVal)
          })
        }
      }
      let values: any[] = [
        ...new Set(
          filtered
            .map((i: any) => {
              let val = i[key]
              if (val === undefined || val === null || val === '') return undefined
              if (typeof val === 'number') return String(val)
              return val
            })
            .filter((val: any) => val !== undefined)
        )
      ].sort((a, b) => String(a).localeCompare(String(b), undefined, { numeric: true, sensitivity: 'base' }))

      if (values.length === 0 && cfg.fallbackOptions?.[key]) {
        values = cfg.fallbackOptions[key]
      }

      result[key] = values.map((val: any) => ({
        label: cfg.labelMap?.[key]?.[val] || String(val),
        value: val
      }))
    }
    return result
  })

  const filterOptions = ref(filterOptionsRaw.value)
  const throttledUpdate = throttle(() => {
    filterOptions.value = filterOptionsRaw.value
  }, 200)

  watch(
    [() => cfg.itemsSource(), activeFilters, () => allFilterFields.value.map(f => f.key).join('|')],
    throttledUpdate,
    { immediate: true }
  )

  function clearAllFilters() {
    activeFilters.value = Object.fromEntries(allFilterFields.value.map(f => [f.key, []]))
  }

  // ======= Items: Suche -> Filter -> Sort -> Pagination =======
  const filteredItems = computed<TableItem[]>(() => {
    let arr = cfg.itemsSource() || []
    if (search.value) {
      const q = search.value.trim().toLowerCase()
      arr = arr.filter((item: any) =>
        `${item.first_name || ''} ${item.last_name || ''} ${item.email || ''} ${item.phone || ''}`
          .toLowerCase()
          .includes(q)
      )
    }
    for (const key of Object.keys(activeFilters.value)) {
      if (!allowedFilterKeys.value.includes(key)) continue
      if ((activeFilters.value as any)[key]?.length) {
        arr = arr.filter((i: any) => (activeFilters.value as any)[key].includes(i[key]))
      }
    }
    return arr
  })

  const sortedItems = computed<TableItem[]>(() => {
    if (!sortKey.value) return filteredItems.value
    return [...filteredItems.value].sort((a: any, b: any) => {
      let av = a[sortKey.value]
      let bv = b[sortKey.value]
      if (av === undefined || av === null) av = ''
      if (bv === undefined || bv === null) bv = ''
      if (typeof av === 'number' && typeof bv === 'number') {
        return sortDirection.value === 'asc' ? av - bv : bv - av
      }
      return sortDirection.value === 'asc'
        ? String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' })
        : String(bv).localeCompare(String(av), undefined, { numeric: true, sensitivity: 'base' })
    })
  })

  // ======= Pagination (Desktop/Mobile getrennt, wie bisher) =======
  const pageSizeOptions = [
    { label: '10', value: 10 },
    { label: '20', value: 20 },
    { label: '30', value: 30 },
    { label: '50', value: 50 },
    { label: '100', value: 100 },
    { label: '500', value: 500 }
  ]

  // Desktop
  const pageSizeDesktop = ref<number>(cfg.pageSizeDesktopDefault ?? 30)
  const currentPageDesktop = ref<number>(1)
  const totalPagesDesktop = computed<number>(() => Math.max(1, Math.ceil(sortedItems.value.length / pageSizeDesktop.value)))
  const pagedItemsDesktop = computed<TableItem[]>(() => {
    const start = (currentPageDesktop.value - 1) * pageSizeDesktop.value
    return sortedItems.value.slice(start, start + pageSizeDesktop.value)
  })
  function goToPageDesktop(page: number) {
    currentPageDesktop.value = Math.max(1, Math.min(page, totalPagesDesktop.value))
  }
  function setPageSizeDesktop(size: number) {
    pageSizeDesktop.value = size
    currentPageDesktop.value = 1
  }

  // Mobile
  const pageSizeMobile = ref<number>(cfg.pageSizeMobileDefault ?? 10)
  const currentPageMobile = ref<number>(1)
  const totalPagesMobile = computed<number>(() => Math.max(1, Math.ceil(sortedItems.value.length / pageSizeMobile.value)))
  const pagedItemsMobile = computed<TableItem[]>(() => {
    const start = (currentPageMobile.value - 1) * pageSizeMobile.value
    return sortedItems.value.slice(start, start + pageSizeMobile.value)
  })
  function goToPageMobile(page: number) {
    currentPageMobile.value = Math.max(1, Math.min(page, totalPagesMobile.value))
  }
  function setPageSizeMobile(size: number) {
    pageSizeMobile.value = size
    currentPageMobile.value = 1
  }

  return {
    // Columns
    rawColumns,
    refreshColumns,
    allColumns,
    visibleColumns,

    // Suche/Sort
    search,
    sortKey,
    sortDirection,
    sortMobile,
    sortOptionsMobile,
    onMobileSortChange,

    // Filter
    allFilterFields,
    filterLabels,
    activeFilterFields,
    activeFilters,
    filterOptions,
    clearAllFilters,

    // Datenfluss
    filteredItems,
    sortedItems,

    // Pagination
    pageSizeOptions,
    pageSizeDesktop, currentPageDesktop, totalPagesDesktop, pagedItemsDesktop,
    pageSizeMobile,  currentPageMobile,  totalPagesMobile,  pagedItemsMobile,
    goToPageDesktop, setPageSizeDesktop,
    goToPageMobile,  setPageSizeMobile
  }
}
