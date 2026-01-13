<!-- AppWorkingDayCard.vue -->
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
        <!-- Apply to other days -->
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

        <!-- Add combo -->
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
      <!-- Existing combos -->
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
          <AppTooltip
            :delay="300"
            position="top"
          >
            <strong
              class="bookando-text-sm bookando-underline"
              style="cursor:help;"
            >
              {{ countLabel(c.serviceIds.length) }} {{ serviceWord(c.serviceIds.length) }}
              {{ andWord }}
              {{ countLabel(c.locationIds.length) }} {{ locationWord(c.locationIds.length) }}
            </strong>
            <template #content>
              <div class="bookando-tooltip-p-sm bookando-tooltip-grid-2">
                <div>
                  <div class="bookando-tooltip-head">
                    {{ t('mod.employees.form.working_days.services') }}
                  </div>
                  <ul style="margin:0; padding-left:1rem;">
                    <li
                      v-for="s in mapIdsToNames(c.serviceIds, serviceOptions, serviceOptionLabel!, serviceOptionValue!)"
                      :key="s"
                    >
                      {{ s }}
                    </li>
                    <li v-if="!c.serviceIds?.length">
                      {{ allWord }}
                    </li>
                  </ul>
                </div>

                <div>
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

        <!-- Readonly -->
        <div
          v-if="!openEditors.has(i)"
          class="bookando-grid bookando-mt-xxs"
          style="--bookando-grid-cols: 1fr 1fr; gap:.5rem; align-items:start; word-break: break-word;"
        >
          <template v-if="c.work?.length">
            <div class="bookando-text-sm">
              <strong>{{ t('mod.employees.form.working_days.working_hours') }}</strong>
            </div>
            <div class="bookando-text-sm">
              <div
                v-for="(w, wi) in c.work"
                :key="`cw_${wi}`"
                class="bookando-ellipsis"
                style="line-height:1.4;"
              >
                {{ w.start }} – {{ w.end }}
              </div>
            </div>
          </template>
        </div>

        <!-- Inline editor -->
        <div
          v-else
          class="bookando-flex bookando-flex-col bookando-gap-sm bookando-mt-sm"
        >
          <!-- Services & locations -->
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr 1fr; gap:.75rem;"
          >
            <BookandoField
              :id="`${dayKey}_services_edit_${i}`"
              :model-value="drafts[i].serviceIds"
              type="dropdown"
              :options="serviceOptions"
              multiple
              searchable
              clearable
              :label="t('mod.employees.form.working_days.services')"
              :option-label="serviceOptionLabel"
              :option-value="serviceOptionValue"
              width="full"
              @update:model-value="val => (drafts[i].serviceIds = asIds(val))"
            />
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

          <!-- Working hours -->
          <div>
            <strong class="bookando-text-sm">{{ t('mod.employees.form.working_days.working_hours') }}</strong>
            <div class="bookando-flex bookando-flex-col bookando-gap-xs bookando-mt-xs">
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

      <!-- New combo -->
      <div
        v-if="hasNewDraft"
        class="bookando-border-t-sm bookando-border-t-solid bookando-border-t-light bookando-pt-sm"
      >
        <div
          class="bookando-grid"
          style="--bookando-grid-cols: 1fr auto; align-items:center;"
        >
          <strong class="bookando-text-sm">
            {{ t('mod.employees.form.working_days.new_combo') || 'Neue Kombination' }}
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

        <div class="bookando-flex bookando-flex-col bookando-gap-sm bookando-mt-sm">
          <!-- Services & locations -->
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr 1fr; gap:.75rem;"
          >
            <BookandoField
              :id="`${dayKey}_services_new`"
              :model-value="drafts['__new__'].serviceIds"
              type="dropdown"
              :options="serviceOptions"
              multiple
              searchable
              clearable
              :label="t('mod.employees.form.working_days.services')"
              :option-label="serviceOptionLabel"
              :option-value="serviceOptionValue"
              width="full"
              @update:model-value="val => (drafts['__new__'].serviceIds = asIds(val))"
            />
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
          </div>

          <!-- Working hours -->
          <div>
            <strong class="bookando-text-sm">{{ t('mod.employees.form.working_days.working_hours') }}</strong>
            <div class="bookando-flex bookando-flex-col bookando-gap-xs bookando-mt-xs">
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
          </div>

          <!-- Footer -->
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
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCard from '@core/Design/components/AppCard.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppTooltip from '@core/Design/components/AppTooltip.vue'
import { useTimeRanges, type TimeRange } from '@core/Composables/useTimeRanges'

const { normalizeRange, sanitizeTimes, rangesOverlap, toPairs } = useTimeRanges()

type TR = TimeRange & { id?: number }
type Combo = { id?: number; serviceIds: number[]; locationIds: number[]; work: TR[] }

const { t, tm } = useI18n()

/* Props / Emits */
const props = withDefaults(defineProps<{
  dayKey: string
  title: string
  disabled?: boolean
  combos: Combo[]
  serviceOptions?: any[]
  serviceOptionLabel?: string
  serviceOptionValue?: string
  locationOptions?: any[]
  locationOptionLabel?: string
  locationOptionValue?: string
}>(), {
  disabled: false,
  combos: () => [],
  serviceOptions: () => [],
  serviceOptionLabel: 'name',
  serviceOptionValue: 'id',
  locationOptions: () => [],
  locationOptionLabel: 'label',
  locationOptionValue: 'id'
})

const emit = defineEmits<{
  (event:'applyToDays', payload:{ toDayKeys:string[] }): void
  (event:'saveCombo',  payload:{ index:number|null, value:Combo }): void
  (event:'deleteCombo', index:number): void
  (event:'open-form-tab', payload:{ tab:'working_days'|'special_days'|'days_off', anchor?:string }): void
}>()

/* State / display */
const hasCombos = computed(() => (props.combos?.length || 0) > 0)

/* Apply-to-days */
const weekdays = computed<string[]>(() => {
  const arr = (tm && (tm('ui.date.weekdays') as unknown)) as string[] | undefined
  return Array.isArray(arr) && arr.length === 7
    ? arr
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
function baseDraft(): Combo { return { serviceIds: [], locationIds: [], work:[{start:'',end:''}] } }
function ensureAtLeastOneRowIdx(key:EditorKey){
  const d = drafts.value[key as any]
  if (!d.work?.length) d.work = [{start:'',end:''}]
}

function toggleEdit(i:number) {
  if (openEditors.value.has(i)) {
    cancelEdit(i)
  } else {
    const src = props.combos[i]
    drafts.value[i] = {
      id: src.id,
      serviceIds:  [...(src.serviceIds || [])],
      locationIds: [...(src.locationIds || [])],
      work:        (src.work || []).map(w => ({ ...w })),
    }
    ensureAtLeastOneRowIdx(i)
    openEditors.value.add(i)
    emit('open-form-tab', { tab: 'working_days', anchor: `${props.dayKey}_services_edit_${i}` })
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

/* Notify & input commit */
function notify(level:'success'|'info'|'warning'|'danger', message:string, timeoutMs?:number) {
  window.dispatchEvent(new CustomEvent('bookando:notify', { detail: { level, message, timeoutMs } }))
}
function forceCommitActiveInput() {
  (document.activeElement as HTMLElement | null)?.dispatchEvent(new Event('blur', { bubbles: true }))
}

/* Validation (no overlaps, min 10m, valid pairs) */
function validateDraft(combo: Combo): string[] {
  const issues: string[] = []
  const MIN_BLOCK_MIN = 10

  const wRaw = combo.work || []
  const hasIncomplete  = wRaw.some(r => (!!r.start && !r.end) || (!r.start && !!r.end))
  const hasInvalid     = wRaw.some(r => r.start && r.end && !normalizeRange(r.start, r.end))
  if (hasIncomplete || hasInvalid) {
    issues.push(t('mod.employees.form.validation.worktime_invalid') as string || 'Die erfassten Arbeitszeiten sind nicht gültig.')
  }

  const workClean = sanitizeTimes(wRaw)
  if (!workClean.length) {
    issues.push(t('mod.employees.form.validation.work_required') as string || 'Mindestens eine Arbeitszeit ist erforderlich.')
  }

  const workPairs = toPairs(workClean)
  const tooShortWork = workPairs.some(p => p[1] - p[0] < MIN_BLOCK_MIN)
  if (tooShortWork) issues.push(t('mod.employees.form.validation.worktime_invalid') as string || 'Die erfassten Arbeitszeiten sind nicht gültig.')

  // Overlap check
  for (let i = 0; i < workPairs.length; i++) {
    for (let j = i + 1; j < workPairs.length; j++) {
      if (rangesOverlap(workPairs[i], workPairs[j])) {
        issues.push(t('mod.employees.form.validation.worktime_invalid') as string || 'Die erfassten Arbeitszeiten sind nicht gültig.')
        i = workPairs.length; break
      }
    }
  }
  return Array.from(new Set(issues))
}

async function saveComboInline(key:EditorKey){
  forceCommitActiveInput()
  await nextTick()

  const value: Combo = { ...drafts.value[key as any] }
  value.serviceIds  = asIds(value.serviceIds)
  value.locationIds = asIds(value.locationIds)

  const problems = validateDraft(value)
  if (problems.length) {
    const title = (t('mod.employees.form.validation.title') as string) || 'Bitte Eingaben prüfen'
    notify('danger', `${title}:\n• ${problems.join('\n• ')}`)
    return
  }

  const cleaned: Combo = { ...value, work: sanitizeTimes(value.work) }

  if (key === '__new__') {
    emit('saveCombo', { index: null, value: cleaned })
  } else {
    emit('saveCombo', { index: key as number, value: cleaned })
  }
  cancelEdit(key)
}

/* Display helpers */
function mapIdsToNames(ids:number[], options:any[], labelKey:string, valueKey:string): string[] {
  const set = new Set(ids)
  return options.filter(o => set.has(o?.[valueKey])).map(o => String(o?.[labelKey] ?? o?.[valueKey]))
}
const andWord = computed(() => (t('core.common.and') as string) || 'und')
const allWord = computed(() => (t('core.common.all') as string) || 'Alle')
function countLabel(n:number) { return n > 0 ? String(n) : allWord.value }
function serviceWord(n:number) {
  return n === 1 ? ((t('mod.employees.form.working_days.service')   as string) || 'Dienstleistung')
                 : ((t('mod.employees.form.working_days.services')  as string) || 'Dienstleistungen')
}
function locationWord(n:number) {
  return n === 1 ? ((t('mod.employees.form.working_days.location')  as string) || 'Ort')
                 : ((t('mod.employees.form.working_days.locations') as string) || 'Orte')
}

/* Dev traces */
onMounted(() => {
})
watch(() => props.combos, (nv) => {
}, { deep: true })
</script>
