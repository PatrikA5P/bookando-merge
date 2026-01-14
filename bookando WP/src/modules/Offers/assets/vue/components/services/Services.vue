<!-- Services.vue -->
<template>
  <div class="services-overview">
    <!-- Filterbar -->
    <div class="bookando-pb-md">
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
        <!-- LEFT: Suche + mobiler Filterbutton -->
        <template #left="{ stack }">
          <div class="bookando-flex-fill">
            <BookandoField
              v-model="search"
              type="search"
              :placeholder="t('mod.services.search_placeholder') || t('ui.search.placeholder') || 'Suchen...'"
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
              :tooltip="t('ui.filter.toggle') || 'Filter'"
              @click.stop="showFilters = !showFilters"
            />
          </div>
        </template>

        <!-- CENTER: Filter-Button (Desktop) + Inline-Sort -->
        <template #center="{ stack }">
          <AppButton
            v-if="!stack"
            icon="filter"
            variant="standard"
            size="square"
            btn-type="icononly"
            icon-size="md"
            :tooltip="t('ui.filter.toggle') || 'Filter'"
            @click.stop="showFilters = !showFilters"
          />
          <AppSort
            v-model:value="sortCombined"
            class="bookando-sort-inline"
            :options="sortOptions"
            :threshold="50"
            @update:value="onSortCombinedChange"
          />
        </template>

        <!-- RIGHT: Export / Import -->
        <template #right>
          <div class="bookando-inline-flex bookando-gap-xxs bookando-ml-auto">
            <AppButton
              icon="download"
              variant="standard"
              size="square"
              btn-type="icononly"
              icon-size="md"
              :tooltip="t('ui.csv.export') || 'CSV exportieren'"
              @click="exportCSV"
            />
            <AppButton
              icon="upload"
              variant="standard"
              size="square"
              btn-type="icononly"
              icon-size="md"
              :tooltip="t('ui.csv.import') || 'CSV importieren'"
              @click="openImportDialog"
            />
          </div>
          <input
            ref="importInput"
            type="file"
            accept=".csv"
            style="display:none"
            @change="importCSV"
          >
        </template>

        <!-- BELOW: nur Filterpanel -->
        <template #below>
          <transition name="accordion">
            <div
              v-if="showFilters"
              class="bookando-card"
            >
              <div class="bookando-card-body">
                <div
                  class="bookando-grid"
                  style="--bookando-grid-cols: 1fr 1fr 1fr auto; gap: var(--bookando-space-sm);"
                >
                  <BookandoField
                    id="srv_cats"
                    v-model="selectedCategoryIds"
                    type="dropdown"
                    multiple
                    clearable
                    searchable
                    teleport
                    :z-index="10020"
                    :options="categories"
                    option-label="name"
                    option-value="id"
                    mode="basic"
                    :placeholder="t('mod.services.category.placeholder') || 'Kategorien...'"
                  />
                  <BookandoField
                    id="srv_tags"
                    v-model="selectedTagIds"
                    type="dropdown"
                    multiple
                    clearable
                    searchable
                    teleport
                    :z-index="10020"
                    :options="tags"
                    option-label="name"
                    option-value="id"
                    mode="basic"
                    :placeholder="t('mod.services.tags.placeholder') || 'Schlagwörter...'"
                  />
                  <BookandoField
                    id="srv_evt"
                    v-model="selectedEventTypes"
                    type="dropdown"
                    multiple
                    clearable
                    searchable
                    teleport
                    :z-index="10020"
                    :options="eventTypeOptions"
                    option-label="label"
                    option-value="value"
                    mode="basic"
                    :placeholder="t('mod.services.event_type') || 'Ereignistypen...'"
                  />
                  <div class="bookando-inline-flex bookando-justify-end">
                    <AppButton
                      variant="secondary"
                      @click="resetFilters"
                    >
                      {{ t('core.common.reset') || 'Zurücksetzen' }}
                    </AppButton>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </template>
      </AppFilterBar>
    </div>

    <!-- Loader -->
    <div
      v-if="loading"
      class="bookando-card"
    >
      <div class="bookando-card-body">
        {{ t('ui.common.loading') || 'Laden…' }}
      </div>
    </div>

    <!-- Sortierbare Liste -->
    <!-- Grid-Gap funktioniert, weil das Grid direkt auf dem <draggable> liegt -->
    <draggable
      v-else
      v-model="visibleServices"
      class="services-list"
      tag="div"
      item-key="id"
      :handle="'.services-row'"
      animation="160"
      ghost-class="svc-row--ghost"
      drag-class="svc-row--dragging"
      @start="onDragStart"
      @end="onDragFinish"
    >
      <template #item="{ element: srv }">
        <article
          class="services-row"
          :class="{ 'is-hidden': srv.status === 'hidden' }"
          @click="onCardClick(srv)"
        >
          <!-- rein optisches Drag-Icon (per AppIcon) -->
          <span
            class="services-drag-handle"
            aria-hidden="true"
          >
            <AppIcon name="menu" />
          </span>

          <!-- farbige Seitenleiste, clippt an der Card -->
          <div
            class="services-side-cap"
            :style="{ backgroundColor: sideCapColor(srv) }"
            aria-hidden="true"
          />

          <!-- Inhalt -->
          <div
            class="services-body"
            @click.stop
          >
            <div class="services-left">
              <!-- Checkbox -->
              <div
                class="services-select"
                @click.stop
              >
                <AppCheckbox
                  :id="`select_${srv.id}`"
                  v-model="selectionById[srv.id]"
                  align="left"
                />
              </div>

              <!-- Avatar -->
              <img
                v-if="srv.avatar_url"
                class="services-avatar"
                :src="srv.avatar_url"
                :alt="srv.name"
                loading="lazy"
              >

              <!-- Textblock -->
              <div class="services-text">
                <h3 class="services-title">
                  {{ srv.name }} <small>ID {{ srv.id }}</small>
                </h3>

                <p class="services-meta">
                  {{ durationLabel(srv) }} • {{ meetingTypeLabel() }} • {{ eventTypeLabel(srv.event_type) }}
                </p>

                <p
                  v-if="availabilityLabel(srv)"
                  class="services-availability"
                >
                  {{ availabilityLabel(srv) }}
                </p>

                <p
                  v-if="srv.description"
                  class="services-desc"
                >
                  {{ srv.description }}
                </p>
                <em
                  v-if="srv.external_product_id"
                  class="services-code"
                >{{ srv.external_product_id }}</em>
              </div>
            </div>

            <!-- Actions -->
            <div class="services-right">
              <!-- Desktop-Buttons explizit via isMobileView steuern -->
              <div
                v-if="!isMobileView"
                class="services-secondary"
              >
                <AppButton
                  icon="edit"
                  btn-type="icononly"
                  size="square"
                  variant="standard"
                  :tooltip="t('core.common.edit')"
                  @click="$emit('edit-service', srv)"
                />
                <AppButton
                  icon="trash-2"
                  btn-type="icononly"
                  size="square"
                  variant="standard"
                  :tooltip="t('core.common.delete')"
                  @click="$emit('delete-service', srv)"
                />
              </div>

              <!-- Popover: Desktop + Mobile -->
              <AppPopover
                trigger-mode="icon"
                trigger-icon="more-horizontal"
                trigger-variant="standard"
                :offset="2"
                width="content"
                :panel-min-width="260"
                panel-class="qa-menu"
                :close-on-item-click="true"
                scroll="none"
              >
                <template #content="{ close }">
                  <div
                    class="popover-menu"
                    role="none"
                  >
                    <!-- Edit & Delete auch im Popover -->
                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('edit-service', srv); close()"
                    >
                      <AppIcon
                        name="edit-3"
                        class="dropdown-icon"
                      />
                      <span class="option-label">{{ t('core.common.edit') || 'Bearbeiten' }}</span>
                    </div>
                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('delete-service', srv); close()"
                    >
                      <AppIcon
                        name="trash-2"
                        class="dropdown-icon"
                      />
                      <span class="option-label">{{ t('core.common.delete') || 'Löschen' }}</span>
                    </div>

                    <div
                      class="dropdown-separator"
                      aria-hidden="true"
                    />

                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('book', srv); close()"
                    >
                      <AppIcon
                        name="calendar-plus"
                        class="dropdown-icon"
                      />
                      <span class="option-label">Meeting buchen</span>
                    </div>
                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('offer-time', srv); close()"
                    >
                      <AppIcon
                        name="mail"
                        class="dropdown-icon"
                      />
                      <span class="option-label">Zeitfenster anbieten</span>
                    </div>
                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('share', srv); close()"
                    >
                      <AppIcon
                        name="share-2"
                        class="dropdown-icon"
                      />
                      <span class="option-label">Verfügbarkeit teilen</span>
                    </div>
                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('one-time', srv); close()"
                    >
                      <AppIcon
                        name="repeat"
                        class="dropdown-icon"
                      />
                      <span class="option-label">Einmal-Link erstellen</span>
                    </div>

                    <div
                      class="dropdown-separator"
                      aria-hidden="true"
                    />

                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('make-secret', srv); close()"
                    >
                      <AppIcon
                        name="eye-off"
                        class="dropdown-icon"
                      />
                      <span class="option-label">Geheim machen</span>
                    </div>
                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('duplicate-service', srv); close()"
                    >
                      <AppIcon
                        name="copy"
                        class="dropdown-icon"
                      />
                      <span class="option-label">Duplizieren</span>
                    </div>
                    <div
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="$emit('toggle-active', srv); close()"
                    >
                      <AppIcon
                        :name="srv.status === 'hidden' ? 'toggle-left' : 'toggle-right'"
                        class="dropdown-icon"
                      />
                      <span class="option-label">
                        {{ srv.status === 'hidden' ? 'Aktivieren' : 'Deaktivieren' }}
                      </span>
                    </div>

                    <!-- Mobile: eigener Schließen-Button nur bei kleinem View -->
                    <div
                      v-if="isMobileView"
                      class="dropdown-separator"
                      aria-hidden="true"
                    />
                    <div
                      v-if="isMobileView"
                      class="dropdown-option"
                      role="menuitem"
                      @click.stop="close()"
                    >
                      <AppIcon
                        name="x"
                        class="dropdown-icon"
                      />
                      <span class="option-label">{{ t('core.common.close') || 'Schließen' }}</span>
                    </div>
                  </div>
                </template>
              </AppPopover>
            </div>
          </div>
        </article>
      </template>
    </draggable>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import draggable from 'vuedraggable'

import AppFilterBar from '@core/Design/components/AppFilterBar.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppSort from '@core/Design/components/AppSort.vue'
import AppCheckbox from '@core/Design/components/AppCheckbox.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'

/* ------------------------------------------------------------------
   Typen der Datenobjekte (als leichte DTOs)
------------------------------------------------------------------- */
type Category = { id:number; name:string; color?:string|null }
type Tag = { id:number; name:string }
type PriceAction = {
  id:number; label?:string; start?:string; end?:string; price:number;
  banner?:string; highlight?:'accent'|'warning'|'danger'
}
type DynamicRule = {
  id:number; mode:'fixed'|'percent'; amount:number; label?:string;
  days?: Array<'mon'|'tue'|'wed'|'thu'|'fri'|'sat'|'sun'>;
  dateStart?:string|null; dateEnd?:string|null; timeStart?:string|null; timeEnd?:string|null;
}
type EventType = 'one_on_one'|'group'|'round_robin'|'collective'|'one_off'|'meeting_poll'
type Service = {
  id:number;
  category_id:number|null;
  tag_ids:number[];
  name:string;
  description?:string;
  duration_min:number;
  price:number;
  currency?:string;
  external_product_id?:string;
  status?:'active'|'hidden';
  event_type?: EventType;
  avatar_url?: string;
  price_actions?:PriceAction[];
  dynamic_rules?:DynamicRule[];
}

/* Props:
   - isMobileView: wird in OffersView.vue via useResponsive() bestimmt
     und hier zur Steuerung mobiler UI verwendet (kein lokales MQ-Handling) */
const props = defineProps<{
  categories: Category[],
  tags: Tag[],
  services: Service[],
  loading?: boolean,
  sortState?: { key:'name'|'price'|'category'|'tag'; direction:'asc'|'desc' },
  isMobileView?: boolean
}>()
const isMobileView = computed(() => !!props.isMobileView)

/* Emits für Aktionen an die Elternkomponente */
const emit = defineEmits([
  'edit-service','delete-service','duplicate-service',
  'book','offer-time','share','one-time','make-secret','toggle-active',
  'reorder-services'
])

/* i18n */
const { t } = useI18n()

/* -------------------- UI: Filter -------------------- */
const showFilters = ref(false)
const search = ref('')
const selectedCategoryIds = ref<number[]>([])
const selectedTagIds = ref<number[]>([])
const selectedEventTypes = ref<EventType[]>([])

/* -------------------- Sortierung -------------------- */
const internalSortKey = ref<'name'|'price'|'category'|'tag'>(props.sortState?.key || 'name')
const sortDirection   = ref<'asc'|'desc'>(props.sortState?.direction || 'asc')
const sortCombined    = ref(`${internalSortKey.value}:${sortDirection.value}`)

const sortOptions = computed(() => {
  const L = {
    name: t('ui.table.sort_name') || 'Name',
    price: t('ui.table.sort_price') || 'Preis',
    category: t('ui.table.sort_category') || 'Kategorie',
    tag: t('mod.services.sort_tag') || 'Schlagwort',
    asc: t('ui.sort.asc') || 'aufsteigend',
    desc: t('ui.sort.desc') || 'absteigend'
  }
  return [
    { label: `${L.name} ${L.asc}`,      value: 'name:asc' },
    { label: `${L.name} ${L.desc}`,     value: 'name:desc' },
    { label: `${L.price} ${L.asc}`,     value: 'price:asc' },
    { label: `${L.price} ${L.desc}`,    value: 'price:desc' },
    { label: `${L.category} ${L.asc}`,  value: 'category:asc' },
    { label: `${L.category} ${L.desc}`, value: 'category:desc' },
    { label: `${L.tag} ${L.asc}`,       value: 'tag:asc' },
    { label: `${L.tag} ${L.desc}`,      value: 'tag:desc' },
  ]
})
watch([internalSortKey, sortDirection], () => {
  sortCombined.value = `${internalSortKey.value}:${sortDirection.value}`
})
function onSortCombinedChange(val: string) {
  const [k, d] = String(val || '').split(':') as any
  if (k) internalSortKey.value = k
  if (d === 'asc' || d === 'desc') sortDirection.value = d
}

/* -------------------- Optionen/Labels -------------------- */
const eventTypeOptions = [
  { label: 'One-on-One', value: 'one_on_one' },
  { label: 'Gruppe (1 → n)', value: 'group' },
  { label: 'Round Robin', value: 'round_robin' },
  { label: 'Kollektiv (n → 1)', value: 'collective' },
  { label: 'Einmaliges Meeting', value: 'one_off' },
  { label: 'Meeting-Umfrage', value: 'meeting_poll' },
] as const
const eventTypeLabel = (et?: EventType) =>
  eventTypeOptions.find(o => o.value === et)?.label || (t('mod.services.event_type') || 'Event')

/* -------------------- Label-Helfer -------------------- */
function categoryLabel(id:number|null|undefined){
  if (id == null) return t('mod.services.uncategorized') || 'Ohne Kategorie'
  const c = props.categories?.find(c => c.id === id)
  return c?.name || t('mod.services.uncategorized') || 'Ohne Kategorie'
}
function tagLabels(ids:number[]){ return ids.map(id => props.tags.find(t=>t.id===id)?.name).filter(Boolean) as string[] }

/* -------------------- Filter + Sort Pipeline -------------------- */
const filteredAndSorted = computed(() => {
  const term = search.value.trim().toLowerCase()
  const catSet = new Set(selectedCategoryIds.value)
  const tagSet = new Set(selectedTagIds.value)
  const evtSet = new Set(selectedEventTypes.value)
  const hasCat = catSet.size > 0
  const hasTag = tagSet.size > 0
  const hasEvt = evtSet.size > 0

  let list = (props.services || []).filter(s => {
    const byTerm = !term
      || s.name.toLowerCase().includes(term)
      || (s.description||'').toLowerCase().includes(term)

    const matchCat = hasCat && s.category_id != null && catSet.has(s.category_id)
    const matchTag = hasTag && (s.tag_ids || []).some(id => tagSet.has(id))
    const byCatOrTag = (hasCat || hasTag) ? (matchCat || matchTag) : true

    const byEvt = hasEvt ? (s.event_type ? evtSet.has(s.event_type) : false) : true

    return byTerm && byCatOrTag && byEvt
  })

  const key = internalSortKey.value
  const dir = sortDirection.value

  const tagsKey = (s: Service) => tagLabels(s.tag_ids).join(', ') || ''
  const catKey  = (s: Service) => categoryLabel(s.category_id) || ''

  list.sort((a:Service, b:Service) => {
    const v =
      key === 'name'     ? (a.name||'').localeCompare(b.name||'', 'de')
    : key === 'price'    ? (a.price||0) - (b.price||0)
    : key === 'category' ? catKey(a).localeCompare(catKey(b), 'de')
    :                       tagsKey(a).localeCompare(tagsKey(b), 'de')
    return dir === 'asc' ? v : -v
  })

  return list
})

/* -------------------- Draggable -------------------- */
const visibleServices = ref<Service[]>([])
watch(filteredAndSorted, (list) => {
  visibleServices.value = [...list]
}, { immediate: true })

const isDragging = ref(false)
function onDragStart(){ isDragging.value = true }
function onDragFinish(){
  isDragging.value = false
  emit('reorder-services', visibleServices.value.map(s => s.id))
}

/* Klick auf Karte → Edit, aber nicht während Drag */
function onCardClick(srv: Service){
  if (isDragging.value) return
  emit('edit-service', srv)
}

/* -------------------- Auswahl (Bulk) -------------------- */
const selectionById = ref<Record<number, boolean>>({})
watch(visibleServices, (list) => {
  for (const s of list) if (!(s.id in selectionById.value)) selectionById.value[s.id] = false
}, { immediate: true })

/* -------------------- UI-Helfer -------------------- */
const durationLabel = (s: Service) => {
  const min = s.duration_min ?? 0
  const h = Math.floor(min / 60)
  const m = min % 60
  if (!h) return `${m} ${t('ui.time.min') || 'Min'}`
  return `${h} h ${m ? `${m} ${t('ui.time.min') || 'Min'}` : ''}`.trim()
}
const meetingTypeLabel = () => t('mod.services.meeting_type_personal') || 'Persönliches Meeting'
const sideCapColor = (s: Service) =>
  props.categories.find(c => c.id === s.category_id)?.color || 'var(--bookando-accent, #6D28D9)'

/* -------------------- Verfügbarkeit-Label -------------------- */
const dayOrder = ['mon','tue','wed','thu','fri','sat','sun'] as const
const dayShort: Record<string,string> = { mon:'Mo', tue:'Di', wed:'Mi', thu:'Do', fri:'Fr', sat:'Sa', sun:'So' }

function fmtTime(t?: string|null){
  if (!t) return ''
  const m = String(t).match(/^(\d{1,2}):(\d{2})/)
  return m ? `${m[1].padStart(2,'0')}:${m[2]}` : (t || '')
}
function compressDayRuns(idxs: number[]){
  const runs: Array<[number,number]> = []
  let start = idxs[0], prev = idxs[0]
  for (let i=1;i<idxs.length;i++){
    const cur = idxs[i]
    if (cur === prev + 1) { prev = cur; continue }
    runs.push([start, prev]); start = prev = cur
  }
  runs.push([start, prev])
  return runs
}
function labelForRun([a,b]: [number,number]){
  return a === b ? dayShort[dayOrder[a]] : `${dayShort[dayOrder[a]]}–${dayShort[dayOrder[b]]}`
}
function availabilityLabel(s: Service){
  const rules = (s.dynamic_rules || []).filter(r => (r.days && r.days.length))
  if (!rules.length) return 'Mo–So, Zeiten variieren'
  const groups = new Map<string, string[]>() // timeKey -> days[]
  for (const r of rules){
    const d = (r.days || []).slice().filter(Boolean) as string[]
    if (!d.length) continue
    const key = r.timeStart && r.timeEnd ? `${fmtTime(r.timeStart)}–${fmtTime(r.timeEnd)}` : 'Zeiten variieren'
    const arr = groups.get(key) || []
    arr.push(...d)
    groups.set(key, arr)
  }
  const parts: string[] = []
  for (const [timeKey, days] of groups){
    const idxs = Array.from(new Set(days.map(d => dayOrder.indexOf(d as any)).filter(i => i >= 0))).sort((a,b)=>a-b)
    if (!idxs.length) continue
    const runs = compressDayRuns(idxs).map(labelForRun).join(', ')
    parts.push(`${runs} ${timeKey}`)
  }
  return parts.join('; ')
}

/* -------------------- CSV Export / Import (basic) -------------------- */
const importInput = ref<HTMLInputElement | null>(null)

function exportCSV() {
  const header = ['Name','Kategorie','Tags','Ereignistyp','Dauer (min)','Preis']
  const rows = visibleServices.value.map((s) => [
    s.name || '',
    categoryLabel(s.category_id),
    tagLabels(s.tag_ids).join(' | '),
    s.event_type || '',
    String(s.duration_min ?? ''),
    String(s.price ?? '')
  ])
  const csv = [header, ...rows]
    .map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `dienstleistungen_${new Date().toISOString().slice(0,10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

function openImportDialog(){ importInput.value?.click() }
function importCSV(event: Event){
  const files = (event.target as HTMLInputElement).files
  if (!files?.length) return
  const reader = new FileReader()
  reader.onload = () => {
    alert(t('ui.csv.import_demo') || 'Import-Demo – Parsing noch nicht implementiert.')
  }
  reader.readAsText(files[0])
}

/* Reset Filter */
function resetFilters(){
  selectedCategoryIds.value = []
  selectedTagIds.value = []
  selectedEventTypes.value = []
}
</script>
