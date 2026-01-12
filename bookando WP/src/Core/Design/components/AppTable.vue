<!-- AppTable.vue -->
<template>
  <div
    ref="scrollRef"
    class="bookando-table-scroll-container bookando-table-context"
  >
    <!-- ===== TABLE VIEW ===== -->
    <table
      v-if="viewMode === 'table'"
      ref="tableRef"
      class="bookando-table"
      :style="tableStyle"
      role="table"
    >
      <colgroup>
        <col
          v-if="useCheckboxes"
          :style="getColStyle('checkbox')"
        >

        <!-- Daten-Spalten -->
        <template
          v-for="(col, idx) in visibleColumnsList"
          :key="col.key"
        >
          <col :style="getColStyle(col.key)">
          <!-- Zwischen-Handle-Col nur zwischen Datenspalten -->
          <col
            v-if="idx < visibleColumnsList.length - 1"
            class="resize-handle-col"
            :style="{ width: HANDLE_W + 'px' }"
          >
        </template>

        <!-- Actions (fix/sticky – zählt nicht zur Datenbreite) -->
        <col
          v-if="useActions"
          :style="getColStyle('actions')"
        >
      </colgroup>

      <thead>
        <tr>
          <!-- Checkbox (fix) -->
          <th
            v-if="useCheckboxes"
            class="checkbox-column"
          >
            <slot name="header-checkbox">
              <AppCheckbox
                :model-value="allSelected"
                align="left"
                @update:model-value="toggleAll"
              />
            </slot>
          </th>

          <!-- Daten-Spalten-Header -->
          <template
            v-for="(col, idx) in visibleColumnsList"
            :key="col.key"
          >
            <th
              :data-col="col.key"
              :style="getColStyle(col.key)"
              :class="[
                'resizable',
                { 'is-resizing': !!resizing && resizing.idx === idx },
                { 'sortable': !!col.sortable },
                { 'is-last-data-th': idx === visibleColumnsList.length - 1 } // ← wichtig für den Fallback
              ]"
              tabindex="0"
              role="columnheader"
              :aria-sort="getAriaSort(col.key)"
              @click="col.sortable && sortColumn(col.key)"
              @dblclick.stop="autoSizeColByIndex(idx)"
            >
              <slot
                :name="`header-${col.key}`"
                :col="col"
              >
                <div class="header-label-text">
                  <span>{{ col.label }}</span>
                  <slot :name="`sort-icon-${col.key}`" />
                </div>
              </slot>

              <!-- Edge-Handle: nur in der LETZTEN Datenspalte -->
              <span
                v-if="idx === visibleColumnsList.length - 1"
                class="resize-edge"
                :aria-label="props.resizeAriaLabel || t('core.table.resizeCol')"
                @mousedown.stop="startResizeRightOf(idx, $event)"
                @dblclick.stop="autoSizeColByIndex(idx)"
                @click.stop.prevent
              />
            </th>

            <!-- Zwischen-Handle-TH zwischen Datenspalten -->
            <th
              v-if="idx < visibleColumnsList.length - 1"
              class="resize-handle-th"
              :aria-label="props.resizeAriaLabel || t('core.table.resizeCol')"
              @mousedown.stop="startResizeBetween(idx, $event)"
              @dblclick.stop="autoSizeColByIndex(idx)"
            >
              <span class="resize-handle" />
            </th>
          </template>

          <!-- Actions (fix) -->
          <th
            v-if="useActions"
            class="actions-column"
          >
            <div class="header-label-text">
              <span>{{ actionsLabel }}</span>
            </div>
          </th>
        </tr>
      </thead>

      <tbody>
        <tr v-if="!items.length">
          <td
            :colspan="totalColumns"
            class="bookando-text-muted"
          >
            <slot name="empty">
              {{ emptyText }}
            </slot>
          </td>
        </tr>

        <template v-else>
          <template
            v-for="item in items"
            :key="rowKey(item)"
          >
            <tr
              class="bookando-table-row"
              @click="emit('row-click', item)"
            >
              <!-- Checkbox (fix) -->
              <td
                v-if="useCheckboxes"
                class="checkbox-column"
                @click.stop
              >
                <slot
                  name="cell-checkbox"
                  :item="item"
                >
                  <AppCheckbox
                    :model-value="selectedItems.includes(rowKey(item))"
                    align="left"
                    @update:model-value="val => toggleItem(rowKey(item), val)"
                  />
                </slot>
              </td>

              <!-- Daten-Zellen + Dummy-Handle-Zellen zwischen den Datenspalten -->
              <template
                v-for="(col, idx) in visibleColumnsList"
                :key="col.key"
              >
                <td
                  :data-col="col.key"
                  :style="getColStyle(col.key)"
                >
                  <slot
                    :name="`cell-${col.key}`"
                    :item="item"
                    :col="col"
                  >
                    {{ item[col.key] }}
                  </slot>
                </td>
                <td
                  v-if="idx < visibleColumnsList.length - 1"
                  class="resize-handle-th"
                  aria-hidden="true"
                />
              </template>

              <!-- Actions (fix) -->
              <td
                v-if="useActions"
                class="actions-column"
                @click.stop
              >
                <div class="bookando-inline-flex bookando-items-center bookando-justify-end bookando-gap-sm bookando-width-full">
                  <slot
                    name="actions"
                    :item="item"
                  />
                </div>
              </td>
            </tr>
          </template>
        </template>
      </tbody>
    </table>

    <!-- ===== GRID / CARD VIEW ===== -->
    <div
      v-else
      class="bookando-grid-list"
      :style="gridInlineStyle"
    >
      <template v-if="!items.length">
        <div class="bookando-grid-empty">
          <slot name="empty">
            {{ emptyText }}
          </slot>
        </div>
      </template>

      <template v-else>
        <div
          v-for="item in items"
          :key="rowKey(item)"
          class="bookando-grid-item"
          :class="{ 'is-selected': selectedItems.includes(rowKey(item)) }"
        >
          <div
            v-if="useCheckboxes"
            class="grid-item-select"
            @click.stop
          >
            <slot
              name="grid-select"
              :item="item"
            >
              <AppCheckbox
                :model-value="selectedItems.includes(rowKey(item))"
                @update:model-value="val => toggleItem(rowKey(item), val)"
              />
            </slot>
          </div>

          <div class="grid-item-content">
            <slot
              v-if="viewMode === 'card'"
              name="card"
              :item="item"
            >
              <div class="card-fallback">
                <div class="card-fallback-title">
                  {{ item[visibleColumnsList[0]?.key] }}
                </div>
                <div class="card-fallback-meta">
                  <div
                    v-for="col in visibleColumnsList.slice(1,4)"
                    :key="col.key"
                    class="meta-row"
                  >
                    <span class="meta-label">{{ col.label }}:</span>
                    <span class="meta-value">{{ item[col.key] }}</span>
                  </div>
                </div>
              </div>
            </slot>

            <slot
              v-else
              name="grid-item"
              :item="item"
            >
              <div class="grid-fallback">
                <div class="grid-fallback-title">
                  {{ item[visibleColumnsList[0]?.key] }}
                </div>
                <ul class="grid-fallback-list">
                  <li
                    v-for="col in visibleColumnsList.slice(1,5)"
                    :key="col.key"
                  >
                    <strong>{{ col.label }}: </strong>{{ item[col.key] }}
                  </li>
                </ul>
              </div>
            </slot>
          </div>

          <div
            v-if="useActions"
            class="grid-item-actions"
            @click.stop
          >
            <slot
              name="actions"
              :item="item"
            />
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * AppTable.vue — SCRIPT (Refactored with Composables)
 * ------------------------------------------------------------
 * Verwendet Composables für:
 *  - useTableSelection: Checkbox-Selektion
 *  - useTableSort: Sort-State-Management
 *  - useTableResize: Excel-ähnliches Column-Resizing
 */

import { ref, computed, getCurrentInstance, onMounted, nextTick, watch, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCheckbox from './AppCheckbox.vue'
import { useTableSelection } from '../composables/useTableSelection'
import { useTableSort } from '../composables/useTableSort'
import { useTableResize } from '../composables/useTableResize'


const { t } = useI18n()


/* ────────────────────────────────────────────────────────────
 * Typen & Props
 * ──────────────────────────────────────────────────────────── */
type Direction = 'asc' | 'desc'
interface TableColumn {
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
type RowKey = string | number
type ViewMode = 'table' | 'grid' | 'card'

const props = defineProps<{
  items: any[]
  columns: TableColumn[]
  visibleColumns: string[]
  colWidths?: Record<string, number>
  emptyText?: string
  useCheckboxes?: boolean
  useActions?: boolean
  selectedItems?: RowKey[]
  rowKeyField?: string
  actionsLabel?: string
  sortState?: { key?: string; direction?: Direction }
  viewMode?: ViewMode
  gridMin?: number | string
  gridGap?: number | string
  gridAutoRows?: string
  /** Persistenz */
  storageKey?: string
  userId?: string | number
  /** Button-Reset-Trigger (z. B. von AppFilterbar) */
  resetWidthsTrigger?: number
  resizeAriaLabel?: string
}>()

const emit = defineEmits<{
  (event: 'sort', key: string): void
  (event: 'update:selected', value: RowKey[]): void
  (event: 'update:col-widths', value: Record<string, number>): void
  (event: 'row-click', item: any): void
}>()

/* ────────────────────────────────────────────────────────────
 * Konstanten
 * ──────────────────────────────────────────────────────────── */
const COLUMN_DEFAULTS = {
  minWidth: 100,
  maxWidth: 2000,
  autoMaxWidth: 500,
  checkbox: { width: 70, minWidth: 70, maxWidth: 70 },
  actions:  { width: 150, minWidth: 150, maxWidth: 150 },
}
const HANDLE_W = 18

/* ────────────────────────────────────────────────────────────
 * Ableitungen / Computed
 * ──────────────────────────────────────────────────────────── */
const actionsLabel = computed(() => props.actionsLabel ?? t('ui.table.actions'))
const viewMode = computed<ViewMode>(() => props.viewMode ?? 'table')

/** Sichtbare Datenspalten (ohne Checkbox/Aktionen). */
const visibleColumnsList = computed(() => {
  // Ensure visibleColumns is a plain array
  const visibleKeys = Array.isArray(props.visibleColumns)
    ? props.visibleColumns
    : Array.from(props.visibleColumns as any)

  // If no visibleColumns specified, show all columns
  if (!visibleKeys || visibleKeys.length === 0) {
    return props.columns
  }

  // Filter columns based on visibleColumns
  return props.columns.filter(c => visibleKeys.includes(c.key))
})

/** Letzte Datenspalte (für Füll- und Guard-Logik). */
const lastDataKey = computed(() => visibleColumnsList.value.at(-1)?.key ?? null)

/* ────────────────────────────────────────────────────────────
 * Refs / DOM
 * ──────────────────────────────────────────────────────────── */
const tableRef  = ref<HTMLTableElement|null>(null)
const scrollRef = ref<HTMLDivElement|null>(null)

/* ────────────────────────────────────────────────────────────
 * Selection Logic (via Composable)
 * ──────────────────────────────────────────────────────────── */
const {
  selectedItems,
  allSelected,
  toggleItem,
  toggleAll,
  isSelected,
  rowKey
} = useTableSelection({
  items: toRef(props, 'items'),
  selectedItems: toRef(props, 'selectedItems'),
  rowKeyField: toRef(props, 'rowKeyField'),
  onUpdate: (newSelection) => emit('update:selected', newSelection)
})

/* ────────────────────────────────────────────────────────────
 * Sort Logic (via Composable)
 * ──────────────────────────────────────────────────────────── */
const {
  sortState,
  sortColumn,
  isSorted,
  getSortDirection,
  getAriaSort
} = useTableSort({
  sortState: toRef(props, 'sortState'),
  onSort: (key: string) => emit('sort', key)
})

/* ────────────────────────────────────────────────────────────
 * Resize Logic (via Composable)
 * ──────────────────────────────────────────────────────────── */
const effectiveColWidths = computed<Record<string, number>>(() => props.colWidths ?? {})

const {
  resizing,
  scrollInnerWidth,
  visibleColumnsList: visibleColumnsFromResize,
  lastDataKey: lastDataKeyFromResize,
  startResizeBetween,
  startResizeRightOf,
  autoSizeCol,
  autoSizeColByIndex,
  autosizeAllVisible,
  getColStyle,
  clampedWidth,
  ensureFillToContainer,
  measureCellContentWidth,
  maxColContentWidth
} = useTableResize({
  columns: toRef(props, 'columns'),
  visibleColumns: toRef(props, 'visibleColumns'),
  colWidths: toRef(props, 'colWidths'),
  useCheckboxes: toRef(props, 'useCheckboxes'),
  useActions: toRef(props, 'useActions'),
  storageKey: toRef(props, 'storageKey'),
  userId: toRef(props, 'userId'),
  resetTrigger: toRef(props, 'resetWidthsTrigger'),
  scrollRef,
  tableRef,
  onUpdateWidths: (widths) => emit('update:col-widths', widths)
})

/* ────────────────────────────────────────────────────────────
 * Mount / Initialisierung
 * ──────────────────────────────────────────────────────────── */
// Mount logic is now handled by useTableResize composable

/* ────────────────────────────────────────────────────────────
 * Width-Helpers & Container-Untergrenze
 * ──────────────────────────────────────────────────────────── */
// All width helpers are now provided by useTableResize composable
const handleSum = computed(() =>
  Math.max(0, visibleColumnsList.value.length - 1) * HANDLE_W
)

/* ────────────────────────────────────────────────────────────
 * Styles aus Breiten ableiten (für <col> & Zellen)
 * ──────────────────────────────────────────────────────────── */
// getColStyle is now provided by useTableResize composable

/* ────────────────────────────────────────────────────────────
 * Resizing, Autosize, Measurement
 * ──────────────────────────────────────────────────────────── */
// All resize, autosize, and measurement logic is now handled by useTableResize composable

// Expose composable methods for external usage
defineExpose({
  autoSizeCol,
  autoSizeColByIndex,
  autosizeAllVisible,
  autoSizeAllCols: autosizeAllVisible
})

/* ────────────────────────────────────────────────────────────
 * Tabellenweite (kosmetisch – Logik macht ensureFillToContainer)
 * ──────────────────────────────────────────────────────────── */
const checkboxW = computed(() => props.useCheckboxes ? COLUMN_DEFAULTS.checkbox.width : 0)
const actionsW  = computed(() => props.useActions   ? COLUMN_DEFAULTS.actions.width  : 0)

const tableStyle = computed(() => {
  const dataSum = visibleColumnsList.value
    .reduce((acc, c) => acc + clampedWidth(c.key), 0)

  const minWidthPx = dataSum + handleSum.value + checkboxW.value + actionsW.value
  return { width: '100%', minWidth: `max(100%, ${minWidthPx}px)` }
})

/* ────────────────────────────────────────────────────────────
 * Weitere Ableitungen
 * ──────────────────────────────────────────────────────────── */
const numHandleCols = computed(() => visibleColumnsList.value.length > 1 ? visibleColumnsList.value.length - 1 : 0)
const totalColumns = computed(() =>
  (props.useCheckboxes ? 1 : 0) +
  visibleColumnsList.value.length +
  numHandleCols.value +
  (props.useActions ? 1 : 0)
)

/* ────────────────────────────────────────────────────────────
 * Grid-View Inline-Style
 * ──────────────────────────────────────────────────────────── */
const gridInlineStyle = computed(() => {
  const min = typeof props.gridMin === 'number' ? `${props.gridMin}px` : (props.gridMin || '240px')
  const gap = typeof props.gridGap === 'number' ? `${props.gridGap}px` : (props.gridGap || '12px')
  const rows = props.gridAutoRows || 'auto'
  return {
    display: 'grid',
    gap,
    gridTemplateColumns: `repeat(auto-fill, minmax(${min}, 1fr))`,
    gridAutoRows: rows
  }
})
</script>
