<!-- AppTableLite.vue -->
<template>
  <div
    ref="shellRef"
    class="tbl-shell"
  >
    <table
      ref="tableRef"
      class="tbl"
      :style="tableStyle"
    >
      <colgroup>
        <!-- sticky checkbox -->
        <col style="width:50px;min-width:50px;max-width:50px">

        <!-- data columns + handles -->
        <template
          v-for="(c, i) in dataCols"
          :key="c.key"
        >
          <col :style="colStyle(c.key)">
          <!-- Griff zwischen Datenspalten -->
          <col
            v-if="i < dataCols.length - 1"
            class="col-handle"
            :style="{ width: HANDLE_W + 'px' }"
          >
        </template>

        <!-- Griff vor Actions (für letzte Datenspalte) -->
        <col
          v-if="useActions && dataCols.length"
          class="col-handle"
          :style="{ width: HANDLE_W + 'px' }"
        >

        <!-- sticky actions -->
        <col
          v-if="useActions"
          style="width:150px;min-width:150px;max-width:150px"
        >
      </colgroup>

      <thead>
        <tr>
          <!-- checkbox -->
          <th class="th checkbox sticky-left">
            <input
              type="checkbox"
              :checked="allSelected"
              @change="toggleAll(($event.target as HTMLInputElement).checked)"
            >
          </th>

          <!-- data headers -->
          <template
            v-for="(c, i) in dataCols"
            :key="c.key"
          >
            <th
              class="th"
              :data-col="c.key"
              @dblclick.stop="autoSize(c.key)"
              @click="c.sortable && onSort(c.key)"
            >
              <span class="head">
                <span class="label">{{ c.label }}</span>
                <span
                  v-if="sort.key === c.key"
                  aria-hidden="true"
                >
                  {{ sort.dir === 'asc' ? '▲' : '▼' }}
                </span>
              </span>
            </th>

            <!-- sichtbarer Griff zwischen Datenspalten -->
            <th
              v-if="i < dataCols.length - 1"
              class="th handle"
              aria-label="Spalte anpassen"
              @mousedown.stop="beginResize(i, $event)"
              @dblclick.stop="autoSize(c.key)"
            >
              <span class="bar" />
            </th>
          </template>

          <!-- Griff vor Actions (wirkt auf letzte Datenspalte) -->
          <th
            v-if="useActions && dataCols.length"
            class="th handle sticky-right-before-actions"
            aria-label="Spalte anpassen"
            @mousedown.stop="beginResize(dataCols.length - 1, $event, true)"
            @dblclick.stop="autoSize(dataCols[dataCols.length - 1].key)"
          >
            <span class="bar" />
          </th>

          <!-- actions -->
          <th
            v-if="useActions"
            class="th actions sticky-right"
          >
            Aktionen
          </th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="row in displayItems"
          :key="rowKey(row)"
        >
          <!-- checkbox -->
          <td class="td checkbox sticky-left">
            <input
              type="checkbox"
              :checked="selectedSet.has(rowKey(row))"
              @change="toggleOne(rowKey(row), ($event.target as HTMLInputElement).checked)"
            >
          </td>

          <!-- data cells -->
          <template
            v-for="(c, i) in dataCols"
            :key="c.key"
          >
            <td
              class="td"
              :data-col="c.key"
            >
              <slot
                :name="`cell-${c.key}`"
                :item="row"
                :col="c"
              >
                {{ row[c.key] }}
              </slot>
            </td>
            <td
              v-if="i < dataCols.length - 1"
              class="td handle-dummy"
              aria-hidden="true"
            />
          </template>

          <!-- dummy vor actions (Ausrichtung) -->
          <td
            v-if="useActions && dataCols.length"
            class="td handle-dummy sticky-right-before-actions"
            aria-hidden="true"
          />

          <!-- actions -->
          <td
            v-if="useActions"
            class="td actions sticky-right"
          >
            <slot
              name="actions"
              :item="row"
            >
              <button @click="$emit('edit', row)">
                ✎
              </button>
              <button @click="$emit('delete', row)">
                🗑
              </button>
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from 'vue'

type Dir = 'asc' | 'desc'
type RowKey = string | number
interface Col {
  key: string
  label: string
  sortable?: boolean
  minWidth?: number
  maxWidth?: number
  width?: number
}

const props = defineProps<{
  items: any[]
  columns: Col[]            // nur Daten-Spalten (ohne checkbox/actions)
  selected?: RowKey[]
  rowKeyField?: string
  useActions?: boolean
}>()

const emit = defineEmits(['edit', 'delete', 'update:selected', 'sort', 'update:widths'])

const HANDLE_W = 18

/* shell + table refs */
const shellRef  = ref<HTMLElement | null>(null)
const tableRef  = ref<HTMLTableElement | null>(null)

/* columns + widths */
const dataCols = computed(() => props.columns)
const widths = ref<Record<string, number>>({})

function colStyle(key: string) {
  const c = props.columns.find(x => x.key === key) || {}
  const w = widths.value[key] ?? c.width ?? c.minWidth ?? 120
  const min = c.minWidth ?? 60
  const max = c.maxWidth ?? 2000
  const clamped = Math.min(Math.max(w, min), max)
  return { width: clamped + 'px', minWidth: min + 'px', maxWidth: max + 'px' }
}

const tableStyle = computed(() => ({
  /* Tabelle nimmt exakt die Summe aller colgroup-Breiten an,
     füllt aber mind. den Container */
  width: 'max-content',
  minWidth: '100%',
}))

/* selection */
const selectedSet = computed(() => new Set(props.selected || []))
function rowKey(r: any): RowKey {
  return props.rowKeyField ? r[props.rowKeyField] : (r.id ?? r.key ?? r._id)
}
function toggleOne(id: RowKey, on: boolean) {
  const s = new Set(selectedSet.value)
  on ? s.add(id) : s.delete(id)
  emit('update:selected', Array.from(s))
}
function toggleAll(on: boolean) {
  const ids = on ? props.items.map(rowKey) : []
  emit('update:selected', ids)
}
const allSelected = computed(
  () => props.items.length > 0 && props.items.every(r => selectedSet.value.has(rowKey(r)))
)

/* sort (einfach) */
const sort = ref<{ key: string | null; dir: Dir }>({ key: null, dir: 'asc' })
function onSort(key: string) {
  sort.value = {
    key,
    dir: sort.value.key === key && sort.value.dir === 'asc' ? 'desc' : 'asc',
  }
  emit('sort', sort.value)
}
const displayItems = computed(() => {
  if (!sort.value.key) return props.items
  const k = sort.value.key
  const d = sort.value.dir === 'asc' ? 1 : -1
  return [...props.items].sort(
    (a, b) =>
      (a?.[k] ?? '').toString().localeCompare((b?.[k] ?? '').toString()) * d
  )
})

/* resizing */
const state = ref<{ i: number; startX: number; startW: number; last: boolean } | null>(null)

function getScrollEl(): HTMLElement | null {
  // benutze den nächsten .bookando-container als echten Scrollcontainer (Fallback: die Shell)
  const shell = shellRef.value
  return (shell?.closest('.bookando-container .bookando-container--hscroll') as HTMLElement) || shell || null
}

function beginResize(i: number, event: MouseEvent, last = false) {
  const key = dataCols.value[i].key
  const startW = parseFloat((colStyle(key).width as string)) || 120
  state.value = { i, startX: event.clientX, startW, last }
  window.addEventListener('mousemove', onDrag)
  window.addEventListener('mouseup', stopDrag)
  document.body.style.cursor = 'col-resize'
}

function onDrag(event: MouseEvent) {
  if (!state.value) return
  const { i, startX, startW, last } = state.value
  const delta = event.clientX - startX
  const key = dataCols.value[i].key
  widths.value = { ...widths.value, [key]: startW + delta }

  // Excel-Feeling: ziehst du die letzte nach rechts, scrolle den Scrollcontainer mit
  if (last && delta > 0) {
    const scroller = getScrollEl()
    if (scroller) scroller.scrollLeft += delta
  }
}

function stopDrag() {
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', stopDrag)
  document.body.style.cursor = ''
  state.value = null
  emit('update:widths', widths.value)
}

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', stopDrag)
  document.body.style.cursor = ''
})

/* autosize per double click */
function autoSize(key: string) {
  const t = tableRef.value
  if (!t) return
  const th  = t.querySelector(`th[data-col="${key}"]`) as HTMLElement | null
  const tds = Array.from(
    t.querySelectorAll(`td[data-col="${key}"]`)
  ) as HTMLElement[]

  let max = 60
  const measure = (el: HTMLElement) => {
    const r = document.createElement('span')
    r.style.visibility = 'hidden'
    r.style.whiteSpace = 'nowrap'
    r.style.position = 'absolute'
    r.textContent = el.innerText || ''
    document.body.appendChild(r)
    const w = r.offsetWidth + 16 // kleine Luft
    document.body.removeChild(r)
    return w
  }
  if (th) max = Math.max(max, measure(th))
  for (const td of tds.slice(0, 50)) max = Math.max(max, measure(td))

  widths.value = { ...widths.value, [key]: max }
  emit('update:widths', widths.value)
}
</script>

<style scoped>
/* =========================================
   Hülle (innerhalb der Komponente)
   → NICHT scrollen lassen; der Elterncontainer scrollt.
   ========================================= */
.tbl-shell{
  position: relative;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow: visible;                 /* ← wichtig: kein eigener Scrollbalken */
  -webkit-overflow-scrolling: touch;
  scrollbar-gutter: stable both-edges;
  contain: paint;                    /* hilft Sticky/Layering in Safari/Chromium */
  overscroll-behavior-x: contain;
}

/* =========================================
   Tabelle
   ========================================= */
.tbl{
  width: max-content;               /* Inhalt bestimmt Breite */
  min-width: 100%;                  /* aber nie kleiner als Container */
  border-collapse: separate;
  border-spacing: 0;
  table-layout: fixed;
}
.th,.td{
  padding: 10px 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  border-bottom: 1px solid #eee;
  background:#fff;
}

/* Sticky-Spalten */
.sticky-left  { position: sticky; inset-inline-start: 0; z-index: 5; background:#fff; }
.sticky-right { position: sticky; inset-inline-end:   0; z-index: 6; background:#fff; }

/* Actions & Checkbox fixbreit */
.th.actions,.td.actions{
  position: sticky;
  right: 0;
  z-index: 40;
  background:#fff;
  width:150px; min-width:150px; max-width:150px;
  text-align:center;
}
.th.checkbox,.td.checkbox{
  width:50px; min-width:50px; max-width:50px;
  text-align:center;
}

/* Handles */
.handle, .handle-dummy{
  width:18px; min-width:18px; max-width:18px; padding:0;
  user-select: none;
}
.handle{
  cursor: col-resize; position: relative;
}
.handle .bar{
  position:absolute; left:50%; top:0; bottom:0;
  width:3px; transform:translateX(-50%);
  background:#c9c9c9;
  pointer-events:none;
}
.handle:hover .bar{ background:#4caf50; }

/* Griff vor Actions über den Zellen halten */
.sticky-right-before-actions{
  position: sticky;
  inset-inline-end: 150px;
  z-index: 7;
  background:#fff;
}

/* Kopf */
.head{ display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
.label{ overflow:hidden; text-overflow:ellipsis; }
</style>
