/**
 * useTableResize
 *
 * Umfangreiches Composable für Excel-ähnliches Column-Resizing in AppTable
 * Extrahiert aus AppTable.vue (~500 Zeilen Resize-Logik)
 *
 * Features:
 * - Resize zwischen Spalten (nur linke Spalte ändert sich)
 * - Resize am rechten Edge (letzte Spalte kann über Viewport wachsen)
 * - Auto-Size via Doppelklick mit Content-Messung
 * - Container-Fill (letzte Spalte füllt Container)
 * - Persistenz in localStorage
 * - Guard-Logik verhindert Unterschreiten der Containerbreite
 */

import { ref, computed, watch, nextTick, onMounted, type Ref, type ComputedRef } from 'vue'

export interface TableColumn {
  key: string
  label: string
  sortable?: boolean
  visible?: boolean
  minWidth?: number
  width?: number
  maxWidth?: number
  autoMaxWidth?: number
  [key: string]: any
}

export interface UseTableResizeOptions {
  columns: ComputedRef<TableColumn[]>
  visibleColumns: ComputedRef<string[]>
  colWidths: ComputedRef<Record<string, number> | undefined>
  useCheckboxes: ComputedRef<boolean>
  useActions: ComputedRef<boolean>
  tableRef: Ref<HTMLTableElement | null>
  scrollRef: Ref<HTMLDivElement | null>
  storageKey?: ComputedRef<string | undefined>
  userId?: ComputedRef<string | number | undefined>
  resetTrigger?: ComputedRef<number | undefined>
  onUpdateWidths: (widths: Record<string, number>) => void
}

const COLUMN_DEFAULTS = {
  minWidth: 100,
  maxWidth: 2000,
  autoMaxWidth: 500,
  checkbox: { width: 70, minWidth: 70, maxWidth: 70 },
  actions: { width: 150, minWidth: 150, maxWidth: 150 },
}
const HANDLE_W = 18

export function useTableResize(options: UseTableResizeOptions) {
  const {
    columns,
    visibleColumns,
    colWidths,
    useCheckboxes,
    useActions,
    tableRef,
    scrollRef,
    storageKey,
    userId,
    resetTrigger,
    onUpdateWidths
  } = options

  // ===== State =====
  const scrollInnerWidth = ref(0)
  const autoSizedOnce = ref<Set<string>>(new Set())
  type ResizingState = { idx: number; startX: number; startLeft: number } | null
  const resizing = ref<ResizingState>(null)

  // ===== Computed =====
  const effectiveColWidths = computed(() => colWidths.value ?? {})

  const visibleColumnsList = computed(() =>
    columns.value.filter(c => visibleColumns.value.includes(c.key))
  )

  const lastDataKey = computed(() => visibleColumnsList.value.at(-1)?.key ?? null)

  const handleSum = computed(() =>
    Math.max(0, visibleColumnsList.value.length - 1) * HANDLE_W
  )

  const checkboxW = computed(() => useCheckboxes.value ? COLUMN_DEFAULTS.checkbox.width : 0)
  const actionsW = computed(() => useActions.value ? COLUMN_DEFAULTS.actions.width : 0)

  const requiredDataMin = computed(() =>
    Math.max(0, scrollInnerWidth.value - checkboxW.value - actionsW.value)
  )

  // ===== Helper Functions =====

  function computeScrollInnerWidth() {
    const sc = scrollRef.value
    if (!sc) return
    const cs = getComputedStyle(sc)
    const pl = parseFloat(cs.paddingLeft || '0')
    const pr = parseFloat(cs.paddingRight || '0')
    scrollInnerWidth.value = Math.max(0, sc.clientWidth - pl - pr)
  }

  function clampedWidth(key: string) {
    const col = columns.value.find(c => c.key === key) || {}
    const minW = col.minWidth ?? COLUMN_DEFAULTS.minWidth
    const isLast = key === lastDataKey.value
    const maxW = isLast ? Number.POSITIVE_INFINITY : (col.maxWidth ?? COLUMN_DEFAULTS.maxWidth)
    let w = effectiveColWidths.value?.[key] ?? col.width ?? minW
    return Math.min(Math.max(w, minW), maxW)
  }

  function widthFrom(key: string, override?: Record<string, number>) {
    if (override && key in override) return override[key]
    return clampedWidth(key)
  }

  function sumDataColsFrom(override?: Record<string, number>) {
    return visibleColumnsList.value.reduce((acc, c) => acc + widthFrom(c.key, override), 0)
  }

  function lastColGuardMin() {
    const lk = lastDataKey.value
    if (!lk) return 0
    const col = columns.value.find(c => c.key === lk) || {}
    const colMin = col.minWidth ?? COLUMN_DEFAULTS.minWidth
    const sumExceptLast = visibleColumnsList.value.reduce((acc, c) => {
      if (c.key === lk) return acc
      return acc + clampedWidth(c.key)
    }, 0)
    const need = requiredDataMin.value - (sumExceptLast + handleSum.value)
    return Math.max(colMin, Math.ceil(need))
  }

  function clampToMinMax(widths: Record<string, number>) {
    const clamped: Record<string, number> = {}
    for (const k of Object.keys(widths)) {
      const col = columns.value.find(c => c.key === k) || {}
      const minW = col.minWidth ?? COLUMN_DEFAULTS.minWidth
      const isLast = k === lastDataKey.value
      const maxW = isLast ? Number.POSITIVE_INFINITY : (col.maxWidth ?? COLUMN_DEFAULTS.maxWidth)
      clamped[k] = Math.min(Math.max(widths[k], minW), maxW)
    }
    return clamped
  }

  // ===== Container Fill Logic =====

  function ensureFillToContainer() {
    const lk = lastDataKey.value
    if (!lk) return
    const sumNow = sumDataColsFrom()
    const deficit = requiredDataMin.value - (sumNow + handleSum.value)
    if (deficit > 0) {
      const col = columns.value.find(c => c.key === lk) || {}
      const maxW = col.maxWidth ?? Number.POSITIVE_INFINITY
      const cur = clampedWidth(lk)
      const next = Math.min(cur + deficit, maxW)
      if (next !== cur) {
        onUpdateWidths({ ...effectiveColWidths.value, [lk]: next })
      }
    }
  }

  // ===== Resize Between Columns =====

  function startResizeBetween(idx: number, event: MouseEvent) {
    const leftKey = visibleColumnsList.value[idx].key
    const startLeft = clampedWidth(leftKey)
    resizing.value = { idx, startX: event.clientX, startLeft }
    document.body.style.cursor = 'col-resize'
    document.addEventListener('mousemove', onResizingBetween)
    document.addEventListener('mouseup', stopResizingBetween)
  }

  function onResizingBetween(event: MouseEvent) {
    if (!resizing.value) return
    const { idx, startX, startLeft } = resizing.value
    const delta = event.clientX - startX

    const key = visibleColumnsList.value[idx].key
    const col = columns.value.find(c => c.key === key) || {}
    const minW = col.minWidth ?? COLUMN_DEFAULTS.minWidth
    const maxW = col.maxWidth ?? COLUMN_DEFAULTS.maxWidth

    let newW = Math.min(Math.max(startLeft + delta, minW), maxW)
    let widths = { ...effectiveColWidths.value, [key]: newW }

    // Guard: Auffüllen der letzten Spalte bei Defizit
    const sumProposed = sumDataColsFrom(widths) + handleSum.value
    const deficit = requiredDataMin.value - sumProposed
    if (deficit > 0) {
      const lk = lastDataKey.value
      if (lk) {
        const lcol = columns.value.find(c => c.key === lk) || {}
        const maxLast = lcol.maxWidth ?? Number.POSITIVE_INFINITY
        const cur = widthFrom(lk, widths)
        widths[lk] = Math.min(cur + deficit, maxLast)
      }
    }

    onUpdateWidths(clampToMinMax(widths))
  }

  function stopResizingBetween() {
    document.removeEventListener('mousemove', onResizingBetween)
    document.removeEventListener('mouseup', stopResizingBetween)
    document.body.style.cursor = ''
    resizing.value = null
  }

  // ===== Resize Right Edge =====

  function startResizeRightOf(idx: number, event: MouseEvent) {
    const key = visibleColumnsList.value[idx].key
    const startW = clampedWidth(key)
    resizing.value = { idx, startX: event.clientX, startLeft: startW }
    document.body.style.cursor = 'col-resize'
    document.addEventListener('mousemove', onResizingRightOf)
    document.addEventListener('mouseup', stopResizingRightOf)
  }

  function onResizingRightOf(event: MouseEvent) {
    if (!resizing.value) return
    const delta = event.clientX - resizing.value.startX
    const key = visibleColumnsList.value[resizing.value.idx].key

    const col = columns.value.find(c => c.key === key) || {}
    const hardMin = col.minWidth ?? COLUMN_DEFAULTS.minWidth
    const guardMin = lastColGuardMin()
    const minW = Math.max(hardMin, guardMin)
    const maxW = col.maxWidth ?? Number.POSITIVE_INFINITY

    const newW = Math.min(Math.max(resizing.value.startLeft + delta, minW), maxW)
    const newWidths = { ...effectiveColWidths.value, [key]: newW }
    onUpdateWidths(clampToMinMax(newWidths))
  }

  function stopResizingRightOf() {
    document.removeEventListener('mousemove', onResizingRightOf)
    document.removeEventListener('mouseup', stopResizingRightOf)
    document.body.style.cursor = ''
    resizing.value = null
  }

  // ===== Measurement Helpers =====

  function measureCellContentWidth(content: string, cls = '', styles: Partial<CSSStyleDeclaration> = {}) {
    const dummy = document.createElement('div')
    dummy.style.position = 'absolute'
    dummy.style.visibility = 'hidden'
    dummy.style.whiteSpace = 'nowrap'
    dummy.style.height = 'auto'
    if (styles.fontSize) dummy.style.fontSize = styles.fontSize
    if (styles.fontFamily) dummy.style.fontFamily = styles.fontFamily
    if (styles.fontWeight) dummy.style.fontWeight = styles.fontWeight as string
    if (styles.fontStyle) dummy.style.fontStyle = styles.fontStyle
    if (cls) dummy.className = cls
    dummy.textContent = content
    document.body.appendChild(dummy)
    const width = dummy.offsetWidth
    document.body.removeChild(dummy)
    return width
  }

  // XSS-safe: Measure width by cloning DOM element instead of using innerHTML
  function measureElementContentWidth(element: Element, style: CSSStyleDeclaration, className = '') {
    const dummy = document.createElement('div')
    dummy.style.position = 'absolute'
    dummy.style.visibility = 'hidden'
    dummy.style.whiteSpace = 'nowrap'
    dummy.style.height = 'auto'
    dummy.style.fontSize = style.fontSize || 'inherit'
    dummy.style.fontFamily = style.fontFamily || 'inherit'
    dummy.style.fontWeight = style.fontWeight || 'inherit'
    dummy.style.fontStyle = style.fontStyle || 'inherit'
    if (className) dummy.className = className

    // Safe: Clone the element's children instead of using innerHTML
    const clonedContent = element.cloneNode(true) as Element
    while (clonedContent.firstChild) {
      dummy.appendChild(clonedContent.firstChild)
    }

    document.body.appendChild(dummy)
    const width = dummy.offsetWidth
    document.body.removeChild(dummy)
    return width
  }

  function maxColContentWidth(
    table: HTMLTableElement,
    colKey: string,
    includeBreathing = false
  ): number {
    let max = COLUMN_DEFAULTS.minWidth
    const col = columns.value.find(c => c.key === colKey)
    if (!col) return max

    const headerTh = table.querySelector(`thead th[data-col="${colKey}"]`) as HTMLTableCellElement | null
    if (headerTh) {
      const labelDiv = headerTh.querySelector('.header-label-text')
      if (labelDiv) {
        const computedStyle = getComputedStyle(labelDiv)
        // XSS-safe: Pass element directly instead of innerHTML
        const baseW = measureElementContentWidth(labelDiv, computedStyle, labelDiv.className)
        const cs = getComputedStyle(headerTh)
        const pl = parseFloat(cs.paddingLeft || '0')
        const pr = parseFloat(cs.paddingRight || '0')
        max = Math.max(max, baseW + pl + pr + 20)
      }
    }

    const bodyRows = table.querySelectorAll(`tbody tr:not(.accordion-row) td[data-col="${colKey}"]`)
    bodyRows.forEach(td => {
      const text = (td as HTMLTableCellElement).textContent || ''
      const computedStyle = getComputedStyle(td)
      const textW = measureCellContentWidth(text, td.className, {
        fontSize: computedStyle.fontSize,
        fontFamily: computedStyle.fontFamily,
        fontWeight: computedStyle.fontWeight,
        fontStyle: computedStyle.fontStyle
      })
      const pl = parseFloat(computedStyle.paddingLeft || '0')
      const pr = parseFloat(computedStyle.paddingRight || '0')
      max = Math.max(max, textW + pl + pr)
    })

    if (includeBreathing) {
      const limit = col.autoMaxWidth ?? COLUMN_DEFAULTS.autoMaxWidth
      max = Math.min(max + 20, limit)
    }

    const minW = col.minWidth ?? COLUMN_DEFAULTS.minWidth
    const isLast = colKey === lastDataKey.value
    const maxW = isLast ? Number.POSITIVE_INFINITY : (col.maxWidth ?? COLUMN_DEFAULTS.maxWidth)
    return Math.min(Math.max(max, minW), maxW)
  }

  // ===== Auto-Size Functions =====

  function autosizeAllVisible(save = false) {
    const t = tableRef.value
    if (!t) return
    const newW: Record<string, number> = { ...effectiveColWidths.value }
    visibleColumnsList.value.forEach(col => {
      let w = maxColContentWidth(t, col.key)
      if (col.key === lastDataKey.value) w = Math.max(w, lastColGuardMin())
      newW[col.key] = w
    })
    onUpdateWidths(newW)
    if (save) saveWidths(newW)
    nextTick(() => ensureFillToContainer())
  }

  function autoSizeColByIndex(idx: number) {
    const table = tableRef.value
    if (!table) return
    const col = visibleColumnsList.value[idx]
    if (!col) return
    const firstTime = !autoSizedOnce.value.has(col.key)
    let newWidth = maxColContentWidth(table, col.key, firstTime)
    if (col.key === lastDataKey.value) newWidth = Math.max(newWidth, lastColGuardMin())
    autoSizedOnce.value.add(col.key)
    onUpdateWidths({ ...effectiveColWidths.value, [col.key]: newWidth })
    nextTick(() => ensureFillToContainer())
  }

  function autoSizeCol(key: string, forceBreathing?: boolean) {
    const table = tableRef.value
    if (!table) return
    const includeBreathing = forceBreathing ?? !autoSizedOnce.value.has(key)
    let newWidth = maxColContentWidth(table, key, includeBreathing)
    if (key === lastDataKey.value) newWidth = Math.max(newWidth, lastColGuardMin())
    autoSizedOnce.value.add(key)
    onUpdateWidths({ ...effectiveColWidths.value, [key]: newWidth })
    nextTick(() => ensureFillToContainer())
  }

  // ===== Persistence =====

  function buildStorageKey() {
    let winUser: string | number | undefined
    try {
      winUser = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS?.current_user_id) || undefined
    } catch {}
    const user = userId?.value ?? winUser ?? 'anon'
    const table = storageKey?.value ?? 'table'
    return `bookando:table:${table}:u${user}`
  }

  function loadSavedWidths(): Record<string, number> | null {
    try {
      const raw = localStorage.getItem(buildStorageKey())
      if (!raw) return null
      return JSON.parse(raw)
    } catch {
      return null
    }
  }

  function saveWidths(widths: Record<string, number>) {
    try {
      localStorage.setItem(buildStorageKey(), JSON.stringify(widths))
    } catch {}
  }

  // ===== Style Helpers =====

  function getColStyle(key: string) {
    if (key === 'checkbox') return { width: '70px', minWidth: '70px', maxWidth: '70px' }
    if (key === 'actions') return { width: '150px', minWidth: '150px', maxWidth: '150px' }

    const col = columns.value.find(c => c.key === key) || {}
    const minW = col.minWidth ?? COLUMN_DEFAULTS.minWidth
    const isLast = key === lastDataKey.value
    const maxW = isLast ? Number.POSITIVE_INFINITY : (col.maxWidth ?? COLUMN_DEFAULTS.maxWidth)

    let width = effectiveColWidths.value?.[key] ?? col.width ?? minW
    width = Math.max(width, minW)
    if (!isLast) width = Math.min(width, maxW)

    const style: Record<string, string> = { width: width + 'px', minWidth: minW + 'px' }
    if (!isLast) style.maxWidth = maxW + 'px'
    return style
  }

  // ===== Lifecycle & Watchers =====

  onMounted(async () => {
    computeScrollInnerWidth()

    if ('ResizeObserver' in window) {
      const ro = new ResizeObserver(() => {
        computeScrollInnerWidth()
        ensureFillToContainer()
      })
      if (scrollRef.value) ro.observe(scrollRef.value)
    } else {
      window.addEventListener('resize', () => {
        computeScrollInnerWidth()
        ensureFillToContainer()
      })
    }

    await nextTick()
    const saved = loadSavedWidths()
    if (saved && Object.keys(saved).length) {
      onUpdateWidths(clampToMinMax(saved))
    } else {
      autosizeAllVisible()
    }

    nextTick(() => ensureFillToContainer())
  })

  // Reset Trigger
  if (resetTrigger) {
    watch(resetTrigger, async () => {
      autoSizedOnce.value.clear()
      await nextTick()
      autosizeAllVisible(true)
      nextTick(() => ensureFillToContainer())
    })
  }

  // New Visible Columns
  watch(() => visibleColumns.value.slice(), async (now, prev) => {
    await nextTick()
    const added = now.filter(k => !prev?.includes(k))
    if (!added.length) return
    const t = tableRef.value
    if (!t) return
    const newWidths = { ...effectiveColWidths.value }
    added.forEach(k => { newWidths[k] = maxColContentWidth(t, k) })
    onUpdateWidths(newWidths)
    saveWidths(newWidths)
    nextTick(() => ensureFillToContainer())
  })

  // Persistence
  watch(effectiveColWidths, (w) => { if (w) saveWidths(w) })

  // ===== Return API =====

  return {
    // State
    resizing: computed(() => resizing.value),
    scrollInnerWidth: computed(() => scrollInnerWidth.value),

    // Computed
    visibleColumnsList,
    lastDataKey,
    handleSum,

    // Resize Functions
    startResizeBetween,
    startResizeRightOf,

    // Auto-Size Functions
    autoSizeCol,
    autoSizeColByIndex,
    autosizeAllVisible,
    autoSizeAllCols: autosizeAllVisible,

    // Helper Functions
    getColStyle,
    clampedWidth,
    ensureFillToContainer,

    // Measurement (falls extern benötigt)
    measureCellContentWidth,
    maxColContentWidth
  }
}
