<!-- AppServiceAvailabilityCard.vue -->
<template>
  <AppCard
    :hide-header="true"
    padding="0"
    body-padding="0"
    rounded="sm"
    shadow="1"
    :class="['bookando-card--t-220', (hasCombos || openEditors.size || hasNewDraft) ? 'bookando-card--open' : '']"
  >
    <!-- Header -->
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
        <!-- Auf andere Tage anwenden -->
        <AppPopover
          :offset="2"
          width="auto"
          :panel-min-width="260"
          :close-on-item-click="false"
        >
          <template #trigger="{ toggle }">
            <AppButton
              btn-type="icononly"
              size="square"
              variant="standard"
              icon="copy"
              :tooltip="t('mod.employees.form.working_days.apply_to_other_days')"
              :aria-label="t('mod.employees.form.working_days.apply_to_other_days')"
              type="button"
              @click="toggle"
            />
          </template>
          <template #content="{ close }">
            <div
              class="bookando-flex bookando-flex-col bookando-gap-xxs"
              style="padding:.5rem; min-width:240px;"
            >
              <strong class="bookando-text-sm">
                {{ t('mod.employees.form.working_days.choose_days') }}
              </strong>

              <label
                v-for="opt in otherDayOptions"
                :key="opt.key"
                class="bookando-flex bookando-items-center bookando-gap-xs"
              >
                <input
                  v-model="selectedOtherDays"
                  type="checkbox"
                  :value="opt.key"
                >
                <span>{{ opt.label }}</span>
              </label>

              <div class="bookando-flex bookando-justify-end bookando-gap-sm bookando-mt-sm">
                <AppButton
                  btn-type="textonly"
                  variant="secondary"
                  size="dynamic"
                  type="button"
                  @click="close()"
                >
                  {{ t('core.common.cancel') }}
                </AppButton>
                <AppButton
                  btn-type="full"
                  variant="primary"
                  size="dynamic"
                  type="button"
                  @click="applyToSelected(close)"
                >
                  {{ t('core.bulk.apply') }}
                </AppButton>
              </div>
            </div>
          </template>
        </AppPopover>

        <!-- Neu -->
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

    <!-- Body -->
    <div
      v-if="hasCombos || openEditors.size || hasNewDraft"
      class="bookando-card__body bookando-py-xs"
    >
      <!-- Bestehende Kombis -->
      <div
        v-for="(c, i) in combos"
        :key="`${dayKey}-${c.id ?? i}`"
        class="bookando-py-xs"
        :class="{ 'bookando-border-t-sm bookando-border-t-solid bookando-border-t-light': i > 0 }"
      >
        <div
          class="bookando-grid"
          style="--bookando-grid-cols: 1fr auto; align-items:center;"
        >
          <!-- Linke Seite: Orte -->
          <AppTooltip
            :delay="300"
            position="top"
          >
            <strong
              class="bookando-text-sm bookando-underline"
              style="cursor:help;"
            >
              {{ countLabel(c.locationIds.length) }} {{ locationWord(c.locationIds.length) }}
            </strong>
            <template #content>
              <div class="bookando-tooltip-p-sm">
                <div class="bookando-tooltip-head">
                  {{ t('mod.employees.form.working_days.locations') }}
                </div>
                <ul style="margin:0; padding-left:1rem;">
                  <li
                    v-for="l in mapIdsToNames(c.locationIds, locationOptions, locationOptionLabel!, locationOptionValue!)"
                    :key="l"
                  >
                    {{ l }}
                  </li>
                  <li v-if="!c.locationIds?.length">
                    {{ allWord }}
                  </li>
                </ul>
              </div>
            </template>
          </AppTooltip>

          <div
            class="bookando-inline-flex bookando-gap-xs"
            style="justify-self:end;"
          >
            <AppButton
              :icon="openEditors.has(i) ? 'x' : 'edit'"
              btn-type="icononly"
              size="square"
              variant="standard"
              type="button"
              :aria-label="t('core.common.edit')"
              @click="toggleEdit(i)"
            />
            <AppButton
              icon="trash-2"
              btn-type="icononly"
              size="square"
              variant="standard"
              type="button"
              :aria-label="t('core.common.delete')"
              @click="$emit('deleteCombo', i)"
            />
          </div>
        </div>

        <!-- Readonly: NUR ZEITEN – links, etwas größer -->
        <div
          v-if="!openEditors.has(i)"
          class="bookando-mt-xxs"
        >
          <div class="bookando-flex bookando-flex-col bookando-gap-xxs">
            <div
              v-for="(w, wi) in c.work"
              :key="`cw_${wi}`"
              class="bookando-text-md"
              style="line-height:1.4;"
            >
              {{ w.start }} – {{ w.end }}
            </div>
          </div>
        </div>

        <!-- Inline-Editor -->
        <div
          v-else
          class="bookando-flex bookando-flex-col bookando-gap-sm bookando-mt-sm"
        >
          <!-- Nur Orte -->
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr; gap:.75rem;"
          >
            <BookandoField
              :id="`${dayKey}_locations_edit_${i}`"
              :model-value="drafts[i].locationIds"
              type="dropdown"
              :options="locationOptions"
              :source="locationOptions?.length ? undefined : 'locations'"
              multiple
              searchable
              clearable
              :label="t('mod.employees.form.working_days.locations')"
              :option-label="locationOptionLabel"
              :option-value="locationOptionValue"
              width="full"
              @update:model-value="val => (drafts[i].locationIds = asIds(val))"
            />
          </div>

          <!-- Zeiten (ohne Überschrift) -->
          <div class="bookando-flex bookando-flex-col bookando-gap-xs">
            <div
              v-for="(w, wi) in drafts[i].work"
              :key="`ew_${i}_${wi}`"
              class="bookando-grid"
              style="--bookando-grid-cols: 1fr 1fr auto; gap:.5rem; align-items:center;"
            >
              <BookandoField
                :id="`${dayKey}_work_start_${i}_${wi}`"
                v-model="w.start"
                type="time"
                input-icon="clock"
                :grouped="false"
                format="HH:mm"
                model-type="HH:mm"
                :text-input="true"
                :clearable="true"
                commit-on="blur"
                :auto-apply="true"
                :placeholder="t('ui.time.select')"
              />
              <BookandoField
                :id="`${dayKey}_work_end_${i}_${wi}`"
                v-model="w.end"
                type="time"
                input-icon="clock"
                :grouped="false"
                format="HH:mm"
                model-type="HH:mm"
                :text-input="true"
                :clearable="true"
                commit-on="blur"
                :auto-apply="true"
                :placeholder="t('ui.time.select')"
              />
              <AppButton
                icon="minus"
                btn-type="icononly"
                size="square"
                variant="standard"
                type="button"
                :aria-label="t('core.common.remove')"
                @click="removeWorkRowIdx(i, wi)"
              />
            </div>

            <div class="bookando-flex bookando-justify-start bookando-mt-xxs">
              <AppButton
                type="button"
                icon="plus"
                variant="clear"
                icon-size="md"
                size="dynamic"
                :tooltip="t('mod.employees.form.working_days.add_working_hours')"
                @click="addWorkRowIdx(i)"
              >
                {{ t('mod.employees.form.working_days.add_working_hours') }}
              </AppButton>
            </div>
          </div>

          <!-- Footer -->
          <div class="bookando-flex bookando-justify-end bookando-gap-sm">
            <AppButton
              btn-type="textonly"
              variant="secondary"
              size="dynamic"
              type="button"
              @click="cancelEdit(i)"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
            <AppButton
              btn-type="full"
              variant="primary"
              size="dynamic"
              type="button"
              @click="saveComboInline(i)"
            >
              {{ t('core.common.save') || 'Speichern' }}
            </AppButton>
          </div>
        </div>
      </div>

      <!-- Neu-Entwurf -->
      <div
        v-if="hasNewDraft"
        class="bookando-border-t-sm bookando-border-t-solid bookando-border-t-light bookando-pt-sm"
      >
        <div
          class="bookando-grid"
          style="--bookando-grid-cols: 1fr auto; align-items:center;"
        >
          <strong class="bookando-text-sm">{{ t('mod.employees.form.working_days.new_combo') || 'Neue Kombination' }}</strong>
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

        <div class="bookando-flex bookando-flex-col bookando-gap-sm bookando-mt-sm">
          <BookandoField
            :id="`${dayKey}_locations_new`"
            :model-value="drafts['__new__'].locationIds"
            type="dropdown"
            :options="locationOptions"
            :source="locationOptions?.length ? undefined : 'locations'"
            multiple
            searchable
            clearable
            :label="t('mod.employees.form.working_days.locations')"
            :option-label="locationOptionLabel"
            :option-value="locationOptionValue"
            width="full"
            @update:model-value="val => (drafts['__new__'].locationIds = asIds(val))"
          />

          <!-- Zeiten (ohne Überschrift) -->
          <div class="bookando-flex bookando-flex-col bookando-gap-xs">
            <div
              v-for="(w, wi) in drafts['__new__'].work"
              :key="`nw_${wi}`"
              class="bookando-grid"
              style="--bookando-grid-cols: 1fr 1fr auto; gap:.5rem; align-items:center;"
            >
              <BookandoField
                :id="`${dayKey}_work_start_new_${wi}`"
                v-model="w.start"
                type="time"
                input-icon="clock"
                :grouped="false"
                format="HH:mm"
                model-type="HH:mm"
                :text-input="true"
                :clearable="true"
                commit-on="blur"
                :auto-apply="true"
                :placeholder="t('ui.time.select')"
              />
              <BookandoField
                :id="`${dayKey}_work_end_new_${wi}`"
                v-model="w.end"
                type="time"
                input-icon="clock"
                :grouped="false"
                format="HH:mm"
                model-type="HH:mm"
                :text-input="true"
                :clearable="true"
                commit-on="blur"
                :auto-apply="true"
                :placeholder="t('ui.time.select')"
              />
              <AppButton
                icon="minus"
                btn-type="icononly"
                size="square"
                variant="standard"
                type="button"
                :aria-label="t('core.common.remove')"
                @click="removeWorkRowIdx('__new__', wi)"
              />
            </div>

            <div class="bookando-flex bookando-justify-start bookando-mt-xxs">
              <AppButton
                type="button"
                icon="plus"
                variant="clear"
                icon-size="md"
                size="dynamic"
                :tooltip="t('mod.employees.form.working_days.add_working_hours')"
                @click="addWorkRowIdx('__new__')"
              >
                {{ t('mod.employees.form.working_days.add_working_hours') }}
              </AppButton>
            </div>
          </div>

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
              @click="saveComboInline('__new__')"
            >
              {{ t('core.common.save') || 'Speichern' }}
            </AppButton>
          </div>
        </div>
      </div>
    </div>

    <div
      v-else
      class="bookando-card__body bookando-py-xs bookando-text-sm bookando-text-muted"
    >
      {{ t('mod.employees.form.working_days.empty') || 'Noch keine Kombination erfasst.' }}
    </div>
  </AppCard>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCard from '@core/Design/components/AppCard.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppTooltip from '@core/Design/components/AppTooltip.vue'

type TR = { start:string; end:string }
type Combo = { id?: number; locationIds: number[]; work: TR[] }

const { t, tm } = useI18n()

/* Props / Emits */
const props = withDefaults(defineProps<{
  dayKey: string
  title: string
  disabled?: boolean
  combos: Combo[]
  locationOptions?: any[]
  locationOptionLabel?: string
  locationOptionValue?: string
}>(), {
  disabled: false,
  combos: () => [],
  locationOptions: () => [],
  locationOptionLabel: 'label',
  locationOptionValue: 'id'
})

const emit = defineEmits<{
  (event:'applyToDays', payload:{ toDayKeys:string[] }): void
  (event:'saveCombo',  payload:{ index:number|null, value:Combo }): void
  (event:'deleteCombo', index:number): void
}>()

/* State / display */
const hasCombos = computed(() => (props.combos?.length || 0) > 0)

/* Apply-to-days */
const weekdays = computed<string[]>(() => {
  const arr = (tm && (tm('ui.date.weekdays') as unknown)) as string[] | undefined
  return Array.isArray(arr) && arr.length === 7 ? arr
       : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']
})
const dayIndex: Record<string, number> = { sun:0, mon:1, tue:2, wed:3, thu:4, fri:5, sat:6 }
const dayOrder = computed<{ key:string; label:string }[]>(() =>
  ['mon','tue','wed','thu','fri','sat','sun'].map(key => ({ key, label: weekdays.value[dayIndex[key]] }))
)
const otherDayOptions = computed(() => dayOrder.value.filter(d => d.key !== props.dayKey))
const selectedOtherDays = ref<string[]>([])
function applyToSelected(close: () => void) {
  if (selectedOtherDays.value.length) emit('applyToDays', { toDayKeys: selectedOtherDays.value })
  selectedOtherDays.value = []; close()
}

/* Inline editing */
type EditorKey = number | '__new__'
const openEditors = ref<Set<EditorKey>>(new Set())
const drafts = ref<Record<string, Combo>>({})
const hasNewDraft = computed(() => openEditors.value.has('__new__'))

function asIds(val:any): number[] {
  const arr = Array.isArray(val) ? val : (val == null ? [] : [val])
  return Array.from(new Set(arr.map((n:any) => Number(n)).filter(Number.isFinite))).sort((a,b)=>a-b)
}
function baseDraft(): Combo { return { locationIds: [], work:[{start:'',end:''}] } }
function ensureAtLeastOneRowIdx(key:EditorKey){
  const d = drafts.value[key as any]
  if (!d.work?.length) d.work = [{start:'',end:''}]
}
function toggleEdit(i:number) {
  if (openEditors.value.has(i)) { cancelEdit(i) }
  else {
    const src = props.combos[i]
    drafts.value[i] = { id: src.id, locationIds: [...(src.locationIds || [])], work: (src.work || []).map(w => ({ ...w })) }
    ensureAtLeastOneRowIdx(i); openEditors.value.add(i)
  }
}
function onCreate() {
  if (hasNewDraft.value) return
  drafts.value['__new__'] = baseDraft()
  openEditors.value.add('__new__')
}
function cancelEdit(key:EditorKey){ openEditors.value.delete(key); delete drafts.value[key as any] }
function addWorkRowIdx(key:EditorKey){ drafts.value[key as any].work.push({ start:'', end:'' }) }
function removeWorkRowIdx(key:EditorKey, idx:number){
  const arr = drafts.value[key as any].work
  arr.length<=1 ? (arr[0]={start:'',end:''}) : arr.splice(idx,1)
}

/* Validation */
function validateDraft(combo: Combo): string[] {
  const issues: string[] = []
  const MIN_BLOCK_MIN = 10
  const wRaw = combo.work || []
  const hasIncomplete  = wRaw.some(r => (!!r.start && !r.end) || (!r.start && !!r.end))
  const hasInvalid     = wRaw.some(r => r.start && r.end && (r.start >= r.end))
  if (hasIncomplete || hasInvalid) { issues.push(t('mod.employees.form.validation.worktime_invalid') as string) }
  const toMin = (t:string)=>{ const [H,M]=t.split(':').map(Number); return H*60+M }
  const pairs = wRaw.filter(r => r.start && r.end).map(r => [toMin(r.start), toMin(r.end)] as [number,number])
  if (!pairs.length) issues.push(t('mod.employees.form.validation.work_required') as string)
  if (pairs.some(p => p[1]-p[0] < MIN_BLOCK_MIN)) issues.push(t('mod.employees.form.validation.worktime_invalid') as string)
  for (let i=0;i<pairs.length;i++){
    for (let j=i+1;j<pairs.length;j++){
      if (Math.max(pairs[i][0], pairs[j][0]) < Math.min(pairs[i][1], pairs[j][1])) {
        issues.push(t('mod.employees.form.validation.worktime_invalid') as string); i=pairs.length; break
      }
    }
  }
  return Array.from(new Set(issues))
}
function saveComboInline(key:EditorKey){
  const value: Combo = { ...drafts.value[key as any] }
  value.locationIds = asIds(value.locationIds)
  const problems = validateDraft(value)
  if (problems.length) {
    window.dispatchEvent(new CustomEvent('bookando:notify', {
      detail: { level:'danger', message: `Bitte Eingaben prüfen:\n• ${problems.join('\n• ')}` }
    })); return
  }
  const cleaned: Combo = { ...value, work: (value.work || []).filter(w => w.start && w.end) }
  if (key === '__new__') emit('saveCombo', { index: null, value: cleaned })
  else                   emit('saveCombo', { index: key as number, value: cleaned })
  cancelEdit(key)
}

/* Display helpers */
function mapIdsToNames(ids:number[], options:any[], labelKey:string, valueKey:string): string[] {
  const set = new Set(ids)
  return options.filter(o => set.has(o?.[valueKey])).map(o => String(o?.[labelKey] ?? o?.[valueKey]))
}
const allWord = computed(() => (t('core.common.all') as string) || 'Alle')
function countLabel(n:number) { return n > 0 ? String(n) : allWord.value }
function locationWord(n:number) {
  return n === 1 ? ((t('mod.employees.form.working_days.location')  as string) || 'Ort')
                 : ((t('mod.employees.form.working_days.locations') as string) || 'Orte')
}
</script>
