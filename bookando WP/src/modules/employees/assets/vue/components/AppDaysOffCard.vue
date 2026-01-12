<!-- AppDaysOffCard.vue -->
<template>
  <!-- Filter -->
  <div class="bookando-mb-sm">
    <div
      class="bookando-grid"
      style="--bookando-grid-cols: 1fr auto; gap:.75rem; align-items:center;"
    >
      <BookandoField
        id="off_filter_range"
        type="daterange"
        input-icon="calendar"
        :editable="true"
        :text-input="true"
        :clearable="true"
        commit-on="blur"
        :auto-apply="true"
        format="dd.MM.yyyy"
        model-type="yyyy-MM-dd"
        :range-separator="' – '"
        :placeholder="t('ui.date.range') || 'Zeitraum filtern'"
        :model-value="toRangeModel(filterStart, filterEnd)"
        @update:model-value="onUpdateFilterRange"
      />
      <AppButton
        icon="rotate-ccw"
        btn-type="icononly"
        size="square"
        variant="standard"
        :tooltip="t('core.common.reset') || 'Zurücksetzen'"
        :aria-label="t('ui.filter.reset_all') || 'Reset Filter'"
        @click="resetFilterToDefault"
      />
    </div>
  </div>

  <!-- Card -->
  <AppCard
    :hide-header="true"
    padding="0"
    body-padding="0"
    rounded="sm"
    shadow="1"
    :class="['bookando-card--t-220', (displayItems.length || openEditors.size || hasNewDraft) ? 'bookando-card--open' : '']"
  >
    <!-- Card-Header (Titel + Add) -->
    <div
      class="bookando-card__header-bar bookando-bg-soft bookando-px-sm bookando-py-xs bookando-grid bookando-items-center"
      style="--bookando-grid-cols: 1fr auto; gap:.5rem; min-width:0;"
    >
      <h4 class="bookando-ellipsis bookando-text-md bookando-font-semibold bookando-m-0">
        {{ title }}
      </h4>
      <div
        class="bookando-inline-flex bookando-gap-xs"
        style="justify-self:end;"
      >
        <AppButton
          icon="plus"
          btn-type="icononly"
          size="square"
          variant="standard"
          type="button"
          :disabled="disabled || hasNewDraft"
          :aria-label="t('core.common.add')"
          :tooltip="t('core.common.add')"
          @click="onCreate"
        />
      </div>
    </div>

    <!-- Tabellen-Header (3 Spalten: Badge | Datum/Titel | Aktionen) -->
    <div
      class="bookando-px-sm bookando-pt-xs bookando-text-sm bookando-text-muted"
      style="display:grid; grid-template-columns: auto 1fr auto; gap:.75rem;"
    >
      <div />
      <div class="bookando-font-semibold">
        {{ (t('core.common.date') as string) || 'Datum' }} / {{ (t('core.common.title') as string) || 'Titel' }}
      </div>
      <div />
    </div>

    <!-- Body -->
    <div
      v-if="displayItems.length || openEditors.size || hasNewDraft"
      class="bookando-card__body bookando-py-xs"
    >
      <div
        v-for="(row, i) in displayItems"
        :key="`off-${row.key}`"
        class="bookando-py-xs"
        :class="{ 'bookando-border-t-sm bookando-border-t-solid bookando-border-t-light': i > 0 }"
      >
        <!-- Readonly-Zeile -->
        <div
          v-if="!openEditors.has(row.sourceIndex)"
          style="display:grid; grid-template-columns: auto 1fr auto; gap:.75rem; align-items:center;"
        >
          <!-- Badge (jährlich/einmalig) -->
          <AppTooltip
            :delay="250"
            position="top"
          >
            <span
              class="bookando-inline-flex bookando-items-center bookando-gap-xxs bookando-text-xs bookando-rounded-full bookando-px-xs bookando-py-xxs"
              :class="row.repeatYearly ? 'bookando-bg-soft' : 'bookando-bg-subtle'"
            >
              <AppIcon
                :name="row.repeatYearly ? 'refresh-ccw' : 'circle'"
                class="bookando-icon"
              />
            </span>
            <template #content>
              <span>
                {{ row.repeatYearly ? (t('mod.employees.form.days_off.repeat_yearly') || 'Jährlich wiederholen') : (t('core.common.once') || 'Einmalig') }}
              </span>
            </template>
          </AppTooltip>

          <!-- Datum · (optional) Titel · (optional) Info -->
          <div class="bookando-text-sm bookando-inline-flex bookando-items-center bookando-gap-xxs bookando-ellipsis">
            <span>{{ formatRangeLong(row.displayStart, row.displayEnd, locale?.value || 'de-CH') }}</span>
            <span>·</span>
            <span class="bookando-font-medium">
              {{ row.title || (t('mod.employees.form.days_off.untitled') as string) || 'Abwesenheit' }}
            </span>
            <AppTooltip
              v-if="row.note"
              :delay="250"
              position="top"
            >
              <AppIcon
                name="info"
                class="bookando-icon"
                style="opacity:.85;"
              />
              <template #content>
                <div style="max-width:32rem; white-space:pre-wrap;">
                  {{ row.note }}
                </div>
              </template>
            </AppTooltip>
          </div>

          <!-- Aktionen -->
          <div
            class="bookando-inline-flex bookando-gap-xs"
            style="justify-self:end;"
          >
            <AppButton
              :icon="'edit'"
              btn-type="icononly"
              size="square"
              variant="standard"
              type="button"
              :aria-label="t('core.common.edit')"
              @click="toggleEdit(row.sourceIndex)"
            />
            <AppButton
              icon="trash-2"
              btn-type="icononly"
              size="square"
              variant="standard"
              type="button"
              :aria-label="t('core.common.delete')"
              @click="$emit('deleteCombo', row.sourceIndex)"
            />
          </div>
        </div>

        <!-- Editor -->
        <div
          v-else
          class="bookando-flex bookando-flex-col bookando-gap-sm bookando-mt-sm bookando-px-sm"
        >
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr 1fr; gap:.75rem;"
          >
            <BookandoField
              :id="`off_title_${row.sourceIndex}`"
              v-model="drafts[row.sourceIndex].title"
              type="text"
              input-icon="edit-2"
              :label="t('core.common.title')"
            />

            <BookandoField
              :id="`off_yearly_${row.sourceIndex}`"
              v-model="drafts[row.sourceIndex].repeatYearly"
              type="checkbox"
              :label="t('mod.employees.form.days_off.repeat_yearly') || 'Jährlich wiederholen'"
            />
          </div>

          <!-- Daterange (tippbar & clearbar) -->
          <BookandoField
            :id="`off_daterange_${row.sourceIndex}`"
            type="daterange"
            input-icon="calendar"
            :editable="true"
            :text-input="true"
            :clearable="true"
            commit-on="blur"
            :auto-apply="true"
            format="dd.MM.yyyy"
            model-type="yyyy-MM-dd"
            :range-separator="' – '"
            :placeholder="t('ui.date.range') || 'Zeitraum wählen'"
            :model-value="toRangeModel(drafts[row.sourceIndex].dateStart, drafts[row.sourceIndex].dateEnd)"
            @update:model-value="onUpdateRange(row.sourceIndex, $event)"
          />

          <BookandoField
            :id="`off_note_${row.sourceIndex}`"
            v-model="drafts[row.sourceIndex].note"
            type="textarea"
            :label="t('core.common.note')"
            :rows="2"
          />

          <div class="bookando-flex bookando-justify-end bookando-gap-sm">
            <AppButton
              btn-type="textonly"
              variant="secondary"
              size="dynamic"
              type="button"
              @click="cancelEdit(row.sourceIndex)"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
            <AppButton
              btn-type="full"
              variant="primary"
              size="dynamic"
              type="button"
              @click="saveInline(row.sourceIndex)"
            >
              {{ t('core.common.save') }}
            </AppButton>
          </div>
        </div>
      </div>

      <!-- Neuer Eintrag -->
      <div
        v-if="hasNewDraft"
        class="bookando-border-t-sm bookando-border-t-solid bookando-border-t-light bookando-pt-sm"
      >
        <div
          class="bookando-grid"
          style="--bookando-grid-cols: 1fr auto; align-items:center;"
        >
          <strong class="bookando-text-sm">
            {{ t('mod.employees.form.days_off.new') || 'Neue Abwesenheit' }}
          </strong>
          <div
            class="bookando-inline-flex bookando-gap-xs"
            style="justify-self:end;"
          >
            <AppButton
              icon="x"
              btn-type="icononly"
              size="square"
              variant="standard"
              type="button"
              :aria-label="t('core.common.cancel')"
              @click="cancelEdit('__new__')"
            />
          </div>
        </div>

        <div class="bookando-flex bookando-flex-col bookando-gap-sm bookando-mt-sm bookando-px-sm">
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr 1fr; gap:.75rem;"
          >
            <BookandoField
              id="off_title_new"
              v-model="drafts['__new__'].title"
              type="text"
              input-icon="edit-2"
              :label="t('core.common.title')"
            />

            <BookandoField
              id="off_yearly_new"
              v-model="drafts['__new__'].repeatYearly"
              type="checkbox"
              :label="t('mod.employees.form.days_off.repeat_yearly') || 'Jährlich wiederholen'"
            />
          </div>

          <BookandoField
            id="off_daterange_new"
            type="daterange"
            input-icon="calendar"
            :editable="true"
            :text-input="true"
            :clearable="true"
            commit-on="blur"
            :auto-apply="true"
            format="dd.MM.yyyy"
            model-type="yyyy-MM-dd"
            :range-separator="' – '"
            :placeholder="t('ui.date.range') || 'Zeitraum wählen'"
            :model-value="toRangeModel(drafts['__new__'].dateStart, drafts['__new__'].dateEnd)"
            @update:model-value="onUpdateRange('__new__', $event)"
          />

          <BookandoField
            id="off_note_new"
            v-model="drafts['__new__'].note"
            type="textarea"
            :label="t('core.common.note')"
            :rows="2"
          />

          <div class="bookando-flex bookando-justify-end bookando-gap-sm">
            <AppButton
              btn-type="textonly"
              variant="secondary"
              size="dynamic"
              type="button"
              @click="cancelEdit('__new__')"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
            <AppButton
              btn-type="full"
              variant="primary"
              size="dynamic"
              type="button"
              @click="saveInline('__new__')"
            >
              {{ t('core.common.save') }}
            </AppButton>
          </div>
        </div>
      </div>
    </div>
  </AppCard>
</template>

<script setup lang="ts">
/**
 * Best Practices:
 * - Keine doppelten Helfer im File (alles aus useDateRanges importieren)
 * - Daterange-Felder explizit editable + textInput + clearable
 * - Filter wird nur in onMounted auf Default gesetzt und per Reset-Button.
 *   Beim Tippen/Leeren KEIN Auto-Reset.
 */
import { ref, computed, nextTick, onMounted } from 'vue'
import {
  toYMDStrict,
  parseYMD,
  ymd,
  addDays,
  dateRangesOverlap,
  materializeYearlyOccurrence,
  extendFilterToFitRange,
  formatRangeLong,
  toRangeModel
} from '@core/Composables/useDateRanges'
import { useI18n } from 'vue-i18n'
import AppCard from '@core/Design/components/AppCard.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppTooltip from '@core/Design/components/AppTooltip.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'

type DayOff = {
  id?: number
  title: string
  note?: string
  dateStart: string // YYYY-MM-DD
  dateEnd: string   // YYYY-MM-DD
  repeatYearly: boolean
}

const { t, locale } = useI18n()

/* Props */
const props = withDefaults(defineProps<{
  title: string
  disabled?: boolean
  items: DayOff[]
}>(), {
  disabled: false,
  items: () => []
})

const emit = defineEmits<{
  (event:'saveCombo', payload:{ index:number|null, value:DayOff }): void
  (event:'deleteCombo', index:number): void
  (event:'open-form-tab', payload:{ tab:'working_days'|'special_days'|'days_off', anchor?:string }): void
}>()

/* ---------- Editor-State ---------- */
type EditorKey = number | '__new__'
const openEditors = ref<Set<EditorKey>>(new Set())
const drafts = ref<Record<string, DayOff>>({})
const hasNewDraft = computed(() => openEditors.value.has('__new__'))

function baseDraft(): DayOff {
  return { title:'', note:'', dateStart:'', dateEnd:'', repeatYearly:false }
}
function toggleEdit(i:number){
  if (openEditors.value.has(i)) return cancelEdit(i)
  const s = props.items[i]
  drafts.value[i] = {
    id: s.id,
    title: s.title || '',
    note: s.note || '',
    dateStart: s.dateStart,
    dateEnd: s.dateEnd,
    repeatYearly: !!s.repeatYearly
  }
  openEditors.value.add(i)
  emit('open-form-tab', { tab: 'days_off', anchor: `off_title_${i}` })
}
function onCreate(){
  if (hasNewDraft.value) return
  drafts.value['__new__'] = baseDraft()
  openEditors.value.add('__new__')
  emit('open-form-tab', { tab: 'days_off', anchor: `off_title_new` })
}
function cancelEdit(k:EditorKey){
  openEditors.value.delete(k)
  delete drafts.value[k as any]
}

/* UX helpers */
function notify(level:'success'|'info'|'warning'|'danger', message:string, timeoutMs?:number){
  window.dispatchEvent(new CustomEvent('bookando:notify',{ detail:{level,message,timeoutMs} }))
}
function forceCommitActiveInput(){
  (document.activeElement as HTMLElement | undefined)?.dispatchEvent(new Event('blur',{bubbles:true}))
}

/* ---------- Validation (nur gültiger DateRange ist Pflicht) ---------- */
function validDateRange(a?:string,b?:string){
  return !!(a && b && a<=b)
}
function validate(v: DayOff){
  const issues: string[] = []
  if (!validDateRange(v.dateStart, v.dateEnd)) {
    issues.push((t('ui.date.invalid_range') as string) || 'Datumsbereich ist ungültig.')
  }
  return issues
}

/* Speichern + ggf. Filter minimal erweitern (damit der Eintrag sichtbar ist) */
async function saveInline(k:EditorKey){
  forceCommitActiveInput()
  await nextTick()

  const v = { ...drafts.value[k as any] }
  const problems = validate(v)
  if (problems.length) {
    return notify('danger', `${(t('mod.employees.form.validation.title') as string) || 'Bitte Eingaben prüfen'}:\n• ${problems.join('\n• ')}`)
  }

  const hasFilter = !!(filterStart.value && filterEnd.value)
  if (hasFilter) {
    const fS = parseYMD(filterStart.value)
    const fE = parseYMD(filterEnd.value)
    const vS = parseYMD(v.dateStart)
    const vE = parseYMD(v.dateEnd)
    if (!dateRangesOverlap(fS, fE, vS, vE)) {
      const ext = extendFilterToFitRange(filterStart.value, filterEnd.value, v.dateStart, v.dateEnd)
      filterStart.value = ext.start
      filterEnd.value   = ext.end
    }
  }

  emit('saveCombo', { index: k==='__new__' ? null : (k as number), value: v })
  cancelEdit(k)
}

/* ---------- Editor: Daterange commit ---------- */
function onUpdateRange(k:EditorKey, range:[unknown,unknown] | null){
  const d = drafts.value[k as any]
  if (!d) return

  if (range == null) {
    d.dateStart = ''
    d.dateEnd   = ''
    return
  }
  const [a,b] = range
  const s = (a === null) ? '' : (toYMDStrict(a) || d.dateStart)
  const e = (b === null) ? '' : (toYMDStrict(b) || d.dateEnd)
  d.dateStart = s
  d.dateEnd   = e
}

/* ---------- Filter (DateRange) ---------- */
const filterStart = ref<string>('')   // yyyy-MM-dd
const filterEnd   = ref<string>('')   // yyyy-MM-dd

function resetFilterToDefault(){
  // heute .. (heute + 1 Jahr - 1 Tag)
  const start = new Date()
  const end   = addDays(new Date(start.getFullYear()+1, start.getMonth(), start.getDate()), -1)
  filterStart.value = ymd(start)
  filterEnd.value   = ymd(end)
}
onMounted(resetFilterToDefault)

function onUpdateFilterRange(range:[unknown,unknown] | null){
  if (range == null) {
    filterStart.value = ''
    filterEnd.value   = ''
    return
  }
  const [a,b] = range
  filterStart.value = (a === null) ? '' : (toYMDStrict(a) || filterStart.value)
  filterEnd.value   = (b === null) ? '' : (toYMDStrict(b) || filterEnd.value)
}

/* ---------- Anzeige (gefiltert + aufwärts sortiert) ---------- */
const displayItems = computed(() => {
  const rows: Array<{
    key: string
    sourceIndex: number
    title: string
    note?: string
    repeatYearly: boolean
    displayStart: string
    displayEnd: string
  }> = []

  const hasFilter = !!(filterStart.value && filterEnd.value)
  const fStart = hasFilter ? parseYMD(filterStart.value) : null
  const fEnd   = hasFilter ? parseYMD(filterEnd.value)   : null

  // Fenster zum Materialisieren wiederholter Einträge:
  // - Mit Filter: Jahre des Filters (inkl. Pufferjahr für übergreifende Ranges)
  // - Ohne Filter: heuristisch aktuelles Jahr .. aktuelles Jahr + 1
  const now = new Date()
let yearFrom: number
let yearTo: number
if (!hasFilter) {
  const years: number[] = []
  props.items.forEach(it => {
    years.push(parseYMD(it.dateStart).getFullYear())
  ; years.push(parseYMD(it.dateEnd).getFullYear())
  })
  const now = new Date()
  const minY = years.length ? Math.min(...years) : now.getFullYear()
  const maxY = years.length ? Math.max(...years) : now.getFullYear() + 1
  yearFrom = minY - 1
  yearTo   = maxY + 1
} else {
  yearFrom = fStart!.getFullYear()
  yearTo   = fEnd!.getFullYear()
}

  props.items.forEach((it, idx) => {
    if (!it.repeatYearly) {
      if (!hasFilter || dateRangesOverlap(parseYMD(it.dateStart), parseYMD(it.dateEnd), fStart!, fEnd!)) {
        rows.push({
          key: `s-${idx}`,
          sourceIndex: idx,
          title: it.title,
          note: it.note,
          repeatYearly: false,
          displayStart: it.dateStart,
          displayEnd: it.dateEnd
        })
      }
    } else {
      for (let y = yearFrom - 1; y <= yearTo; y++) {
        const occ = materializeYearlyOccurrence(it.dateStart, it.dateEnd, y)
        if (!hasFilter || dateRangesOverlap(occ.start, occ.end, fStart!, fEnd!)) {
          rows.push({
            key: `r-${idx}-${y}`,
            sourceIndex: idx,
            title: it.title,
            note: it.note,
            repeatYearly: true,
            displayStart: ymd(occ.start),
            displayEnd: ymd(occ.end)
          })
        }
      }
    }
  })

  // Aufwärts sortieren (nächster zuerst), nach Start dann Ende
  rows.sort((r1, r2) => {
    const a = parseYMD(r1.displayStart).getTime()
    const b = parseYMD(r2.displayStart).getTime()
    if (a !== b) return a - b
    return parseYMD(r1.displayEnd).getTime() - parseYMD(r2.displayEnd).getTime()
  })

  return rows
})
</script>
