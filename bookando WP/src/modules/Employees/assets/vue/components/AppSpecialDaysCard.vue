<!-- AppSpecialDaysCard.vue -->
<template>
  <AppCard
    :hide-header="true"
    padding="0"
    body-padding="0"
    rounded="sm"
    shadow="1"
    class="bookando-card--t-220"
    :class="{ 'bookando-card--open': isOpenLike }"
  >
    <!-- Header -->
    <div
      class="bookando-card__header-bar bookando-bg-soft bookando-px-sm bookando-py-xs bookando-grid bookando-items-center"
      style="--bookando-grid-cols: 1fr auto; gap:.5rem; min-width:0;"
    >
      <!-- Title / date -->
      <div class="bookando-w-full">
        <template v-if="!editHeaderDate">
          <h4 class="bookando-ellipsis bookando-text-md bookando-font-semibold bookando-m-0">
            {{ headerLabel || emptyTitle }}
          </h4>
          <div
            v-if="!hasDay && !hasNewDraft"
            class="bookando-text-sm bookando-text-muted bookando-mt-xxs"
          >
            {{ t('mod.employees.form.special_days.header_hint') || 'Wähle zuerst einen Datumsbereich.' }}
          </div>
        </template>

        <!-- Header date editor -->
        <div
          v-else
          class="bookando-w-full"
        >
          <BookandoField
            :id="`${idPrefix}header_edit_range`"
            type="daterange"
            input-icon="calendar"
            :editable="true"
            :text-input="true"
            :clearable="true"
            commit-on="apply"
            :auto-apply="true"
            format="dd.MM.yyyy"
            model-type="yyyy-MM-dd"
            :range-separator="' – '"
            :placeholder="t('ui.date.range_select') || 'Zeitraum wählen'"
            width="full"
            :model-value="toRangeModel(headerDraftStart, headerDraftEnd)"
            @update:model-value="onUpdateHeaderRange"
          />
        </div>
      </div>

      <!-- Header actions -->
      <div
        v-if="!hasNewDraft && !editHeaderDate"
        class="bookando-inline-flex bookando-gap-xs"
        style="justify-self:end;"
      >
        <AppPopover
          trigger-mode="icon"
          trigger-icon="more-horizontal"
          trigger-variant="standard"
          :offset="2"
          width="content"
          :panel-min-width="260"
          panel-class="qa-menu"
          :close-on-item-click="false"
        >
          <template #content="{ close }">
            <div
              class="popover-menu"
              role="menu"
            >
              <div
                class="dropdown-option"
                role="menuitem"
                @mousedown.prevent
                @click="safePopoverAction(() => startEditHeader(), close)"
              >
                <AppIcon
                  name="edit"
                  class="dropdown-icon"
                />
                <span class="option-label">{{ t('mod.employees.form.special_days.change_date') || 'Datum ändern' }}</span>
              </div>

              <div
                class="dropdown-option"
                role="menuitem"
                @mousedown.prevent
                @click="safePopoverAction(() => duplicateDay(), close)"
              >
                <AppIcon
                  name="copy"
                  class="dropdown-icon"
                />
                <span class="option-label">{{ t('mod.employees.form.special_days.copy') || 'Karte duplizieren' }}</span>
              </div>

              <div
                class="dropdown-option"
                role="menuitem"
                @mousedown.prevent
                @click="safePopoverAction(() => startNewCombo(), close)"
              >
                <AppIcon
                  name="plus"
                  class="dropdown-icon"
                />
                <span class="option-label">{{ t('mod.employees.form.special_days.add_combo') || 'Kombination hinzufügen' }}</span>
              </div>

              <div
                class="dropdown-separator"
                aria-hidden="true"
              />

              <div
                class="dropdown-option bookando-text-danger"
                role="menuitem"
                @mousedown.prevent
                @click="safePopoverAction(() => (confirmDeleteDay = true), close)"
              >
                <AppIcon
                  name="trash-2"
                  class="dropdown-icon"
                />
                <span class="option-label">{{ t('mod.employees.form.special_days.delete') || 'Karte löschen' }}</span>
              </div>
            </div>
          </template>
        </AppPopover>

        <AppButton
          icon="plus"
          btn-type="icononly"
          size="square"
          variant="standard"
          type="button"
          :disabled="disabled"
          :aria-label="t('core.common.add')"
          :tooltip="t('core.common.add')"
          @click="startNewCombo()"
        />
      </div>
    </div>

    <!-- Header save/cancel -->
    <div
      v-if="editHeaderDate"
      class="bookando-flex bookando-justify-end bookando-gap-sm bookando-px-sm bookando-pb-xs"
    >
      <AppButton
        btn-type="textonly"
        size="dynamic"
        variant="secondary"
        type="button"
        @click="cancelHeaderEdit"
      >
        {{ t('core.common.cancel') || 'Abbrechen' }}
      </AppButton>
      <AppButton
        btn-type="full"
        size="dynamic"
        variant="primary"
        type="button"
        @click="saveHeaderEdit"
      >
        {{ t('core.common.save') || 'Speichern' }}
      </AppButton>
    </div>

    <!-- Body -->
    <div class="bookando-card__body bookando-py-xs">
      <!-- Empty state -->
      <div
        v-if="!hasDay && localItems.length === 0 && !openEditors.size"
        class="bookando-px-sm bookando-py-sm bookando-text-sm bookando-text-muted"
      >
        <div class="bookando-mb-xs">
          {{ t('mod.employees.form.special_days.empty') || 'Noch kein Zeitraum und keine Kombination erfasst.' }}
        </div>
        <div class="bookando-inline-flex bookando-gap-xs">
          <AppButton
            btn-type="full"
            variant="primary"
            size="dynamic"
            type="button"
            @click="startEditHeader()"
          >
            {{ t('ui.date.select') || 'Datum wählen' }}
          </AppButton>
          <AppButton
            btn-type="textonly"
            variant="secondary"
            size="dynamic"
            type="button"
            :disabled="!hasDay"
            @click="startNewCombo()"
          >
            {{ t('mod.employees.form.special_days.add_combo') || 'Kombination hinzufügen' }}
          </AppButton>
        </div>
      </div>

      <!-- Combos -->
      <div
        v-for="(c, i) in localItems"
        :key="c._key"
        class="bookando-py-xs"
        :class="{ 'bookando-border-t-sm bookando-border-t-solid bookando-border-t-light': i > 0 }"
      >
        <!-- Row header -->
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
              {{ countLabel(c.serviceIds?.length || 0) }} {{ serviceWord(c.serviceIds?.length || 0) }}
              {{ andWord }}
              {{ countLabel(c.locationIds?.length || 0) }} {{ locationWord(c.locationIds?.length || 0) }}
            </strong>
            <template #content>
              <div class="bookando-tooltip-p-sm bookando-tooltip-grid-2">
                <div>
                  <div class="bookando-tooltip-head">
                    {{ t('mod.employees.form.special_days.services') }}
                  </div>
                  <ul style="margin:0; padding-left:1rem;">
                    <li
                      v-for="s in mapIdsToNames(c.serviceIds || [], serviceOptions, serviceOptionLabel!, serviceOptionValue!)"
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
                    {{ t('mod.employees.form.special_days.locations') }}
                  </div>
                  <ul style="margin:0; padding-left:1rem;">
                    <li
                      v-for="l in mapIdsToNames(c.locationIds || [], locationOptions, locationOptionLabel!, locationOptionValue!)"
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
              :aria-label="t('core.common.edit') || 'Bearbeiten'"
              @click="toggleEdit(i)"
            />
            <AppButton
              icon="trash-2"
              btn-type="icononly"
              size="square"
              variant="standard"
              type="button"
              :aria-label="t('core.common.delete') || 'Löschen'"
              @click="onDeleteCombo(i)"
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
              <strong>{{ t('mod.employees.form.special_days.working_hours') }}</strong>
            </div>
            <div class="bookando-text-sm">
              <div
                v-for="(w, wi) in c.work"
                :key="`cw_${c._key}_${wi}`"
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
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr 1fr; gap:.75rem;"
          >
            <!-- Services -->
            <BookandoField
              :id="`sd_services_${i}`"
              :model-value="drafts[i].serviceIds"
              type="dropdown"
              :options="serviceOptions"
              multiple
              searchable
              clearable
              :label="t('mod.employees.form.special_days.services')"
              :option-label="serviceOptionLabel"
              :option-value="serviceOptionValue"
              width="full"
              @update:model-value="val => (drafts[i].serviceIds = asIds(val))"
            />
            <!-- Locations -->
            <BookandoField
              :id="`sd_locations_${i}`"
              :model-value="drafts[i].locationIds"
              type="dropdown"
              :options="locationOptions"
              :source="locationOptions?.length ? undefined : 'locations'"
              multiple
              searchable
              clearable
              :label="t('mod.employees.form.special_days.locations')"
              :option-label="locationOptionLabel"
              :option-value="locationOptionValue"
              width="full"
              @update:model-value="val => (drafts[i].locationIds = asIds(val))"
            />
          </div>

          <!-- Working hours -->
          <div>
            <strong class="bookando-text-sm">{{ t('mod.employees.form.special_days.working_hours') }}</strong>
            <div class="bookando-flex bookando-flex-col bookando-gap-xs bookando-mt-xs">
              <div
                v-for="(w, wi) in drafts[i].work"
                :key="`sdw_${wi}`"
                class="bookando-grid"
                style="--bookando-grid-cols: 1fr 1fr auto; gap:.5rem; align-items:center;"
              >
                <BookandoField
                  :id="`sd_work_start_${i}_${wi}`"
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
                  :id="`sd_work_end_${i}_${wi}`"
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
                  @click="removeWorkRow(i, wi)"
                />
              </div>
              <div class="bookando-flex bookando-justify-start bookando-mt-xxs">
                <AppButton
                  type="button"
                  icon="plus"
                  variant="clear"
                  icon-size="md"
                  size="dynamic"
                  :tooltip="t('mod.employees.form.special_days.add_working_hours')"
                  @click="addWorkRow(i)"
                >
                  {{ t('mod.employees.form.special_days.add_working_hours') }}
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
              {{ t('core.common.cancel') || 'Abbrechen' }}
            </AppButton>
            <AppButton
              btn-type="full"
              variant="primary"
              size="dynamic"
              type="button"
              @click="saveInline(i)"
            >
              {{ t('core.common.save') || 'Speichern' }}
            </AppButton>
          </div>
        </div>
      </div>

      <!-- New combo (also allows picking date if header unset) -->
      <div
        v-if="hasNewDraft"
        class="bookando-border-t-sm bookando-border-t-solid bookando-border-t-light bookando-pt-sm"
      >
        <div
          class="bookando-grid"
          style="--bookando-grid-cols: 1fr auto; align-items:center;"
        >
          <strong class="bookando-text-sm">{{ t('mod.employees.form.special_days.new_combo') || 'Neue Kombination für diesen Tag definieren' }}</strong>
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
          <!-- Date pick only if card has no date yet -->
          <BookandoField
            v-if="!hasDay"
            :id="`${idPrefix}new_range`"
            type="daterange"
            input-icon="calendar"
            :editable="true"
            :text-input="true"
            :clearable="true"
            commit-on="apply"
            :auto-apply="true"
            format="dd.MM.yyyy"
            model-type="yyyy-MM-dd"
            :range-separator="' – '"
            :placeholder="t('ui.date.range_select') || 'Zeitraum wählen'"
            width="full"
            :model-value="toRangeModel(newDraftStart, newDraftEnd)"
            @update:model-value="onUpdateNewRange"
          />

          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr 1fr; gap:.75rem;"
          >
            <BookandoField
              id="sd_services_new"
              :model-value="drafts['__new__'].serviceIds"
              type="dropdown"
              :options="serviceOptions"
              multiple
              searchable
              clearable
              :label="t('mod.employees.form.special_days.services')"
              :option-label="serviceOptionLabel"
              :option-value="serviceOptionValue"
              width="full"
              @update:model-value="val => (drafts['__new__'].serviceIds = asIds(val))"
            />
            <BookandoField
              id="sd_locations_new"
              :model-value="drafts['__new__'].locationIds"
              type="dropdown"
              :options="locationOptions"
              :source="locationOptions?.length ? undefined : 'locations'"
              multiple
              searchable
              clearable
              :label="t('mod.employees.form.special_days.locations')"
              :option-label="locationOptionLabel"
              :option-value="locationOptionValue"
              width="full"
              @update:model-value="val => (drafts['__new__'].locationIds = asIds(val))"
            />
          </div>

          <div>
            <strong class="bookando-text-sm">{{ t('mod.employees.form.special_days.working_hours') }}</strong>
            <div class="bookando-flex bookando-flex-col bookando-gap-xs bookando-mt-xs">
              <div
                v-for="(w, wi) in drafts['__new__'].work"
                :key="`sdnw_${wi}`"
                class="bookando-grid"
                style="--bookando-grid-cols: 1fr 1fr auto; gap:.5rem; align-items:center;"
              >
                <BookandoField
                  :id="`sd_work_start_new_${wi}`"
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
                  :id="`sd_work_end_new_${wi}`"
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
                  @click="removeWorkRow('__new__', wi)"
                />
              </div>
              <div class="bookando-flex bookando-justify-start bookando-mt-xxs">
                <AppButton
                  type="button"
                  icon="plus"
                  variant="clear"
                  icon-size="md"
                  size="dynamic"
                  :tooltip="t('mod.employees.form.special_days.add_working_hours')"
                  @click="addWorkRow('__new__')"
                >
                  {{ t('mod.employees.form.special_days.add_working_hours') }}
                </AppButton>
              </div>
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
              {{ t('core.common.cancel') || 'Abbrechen' }}
            </AppButton>
            <AppButton
              btn-type="full"
              variant="primary"
              size="dynamic"
              type="button"
              @click="saveInline('__new__')"
            >
              {{ t('core.common.save') || 'Speichern' }}
            </AppButton>
          </div>
        </div>
      </div>
    </div>
  </AppCard>

  <!-- Delete card confirm -->
  <AppModal
    :show="confirmDeleteDay"
    module="employees"
    action="hard_delete"
    type="danger"
    :close-on-backdrop="false"
    :close-on-esc="true"
    @confirm="doConfirmDeleteDay"
    @cancel="confirmDeleteDay = false"
  />
</template>

<script setup lang="ts">
import { ref, reactive, computed, nextTick, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCard from '@core/Design/components/AppCard.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import AppModal from '@core/Design/components/AppModal.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppTooltip from '@core/Design/components/AppTooltip.vue'
import { useTimeRanges, type TimeRange } from '@core/Composables/useTimeRanges'

type TR = TimeRange & { id?: number }
type SpecialCombo = { id?: number; serviceIds: number[]; locationIds: number[]; work: TR[] }
type SpecialComboUI = SpecialCombo & { _key: string }
type DayRange = { dateStart: string, dateEnd?: string | null }
const { t } = useI18n()

const emit = defineEmits<{
  (event: 'updateDay',      payload: { dateStart: string; dateEnd: string | null }): void
  (event: 'copyDay',        payload: { dateStart: string; dateEnd: string; items: SpecialCombo[]; requireDate?: boolean }): void
  (event: 'saveCombo',      payload: { index: number | null; value: SpecialCombo }): void
  (event: 'deleteCombo',    index: number): void
  (event: 'deleteDay'): void
  (event: 'consumedOpen'): void
  (event: 'open-form-tab', payload:{ tab:'working_days'|'special_days'|'days_off', anchor?:string }): void
}>()

/* Props */
const props = withDefaults(defineProps<{
  title?: string
  idPrefix?: string
  disabled?: boolean
  dateStart?: string | null
  dateEnd?: string | null
  items: SpecialCombo[]
  otherDays?: DayRange[]
  serviceOptions?: any[]
  serviceOptionLabel?: string
  serviceOptionValue?: string
  locationOptions?: any[]
  locationOptionLabel?: string
  locationOptionValue?: string
  openMode?: 'none' | 'new' | 'editDate'
}>(), {
  title: '',
  idPrefix: '',
  disabled: false,
  dateStart: null,
  dateEnd: null,
  items: () => [],
  otherDays: () => [],
  serviceOptions: () => [],
  serviceOptionLabel: 'name',
  serviceOptionValue: 'id',
  locationOptions: () => [],
  locationOptionLabel: 'label',
  locationOptionValue: 'id',
  openMode: 'none'
})

/* Local state */
const localDayStart = ref<string|null>(null)
const localDayEnd   = ref<string|null>(null)
const localItems    = ref<SpecialComboUI[]>([])
const editHeaderDate = ref(false)
const headerDraftStart = ref(''); const headerDraftEnd = ref('')

// For first creation in "__new__"
const newDraftStart = ref('')    // yyyy-MM-dd
const newDraftEnd   = ref('')    // yyyy-MM-dd

/* Derived */
const hasDay = computed(() => !!localDayStart.value)
const headerLabel = computed(() => {
  if(!localDayStart.value) return ''
  const ds=localDayStart.value, de=localDayEnd.value||ds
  return ds===de ? formatYMD(ds) : `${formatYMD(ds)} – ${formatYMD(de)}`
})
const emptyTitle = computed(() => t('mod.employees.form.special_days.empty_title') || 'Besondere Tage (neu)')
const isOpenLike = computed(() => editHeaderDate.value || openEditors.size > 0 || hasNewDraft.value)

/* Mount & hydration */
onMounted(async () => {
  localDayStart.value = props.dateStart || null
  localDayEnd.value   = (props.dateEnd ?? props.dateStart) || null
  localItems.value = mapItemsWithStableKeys(props.items)

  if (props.openMode === 'new') {
    await nextTick()
    editHeaderDate.value = false
    newDraftStart.value = ''
    newDraftEnd.value   = ''
    startNewCombo()
    await nextTick()
    const root = document.getElementById(`${props.idPrefix}new_range`) as HTMLElement | null
    const target = (root?.querySelector('input,button,[tabindex]') as HTMLElement) || root
    target?.focus?.()
    emit('consumedOpen')
  }
})
watch(() => props.openMode, async (mode) => {
  if (mode === 'new') {
    editHeaderDate.value = false
    startNewCombo()
    await nextTick()
    const root = document.getElementById(`${props.idPrefix}new_range`) as HTMLElement | null
    const target = (root?.querySelector('input,button,[tabindex]') as HTMLElement) || root
    target?.focus?.()
    emit('consumedOpen')
  } else if (mode === 'editDate') {
    cancelEdit('__new__')
    startEditHeader()
    emit('consumedOpen')
  }
})
watch(() => [props.dateStart, props.dateEnd], ([ds,de]) => {
  localDayStart.value = ds || null
  localDayEnd.value   = (de ?? ds) || null
})
watch(() => JSON.stringify(props.items), () => {
  localItems.value = mapItemsWithStableKeys(props.items)
})

/* Helpers: stable keys + sorting */
function asIds(val:any): number[] {
  const arr = Array.isArray(val) ? val : (val == null ? [] : [val])
  return Array.from(new Set(arr.map((n:any) => Number(n)).filter(Number.isFinite))).sort((a,b)=>a-b)
}
function uid(){ return 'k_'+Math.random().toString(36).slice(2,9)+Date.now().toString(36).slice(-4) }
function toMin(t?:string){ if(!t) return Infinity; const [h,m]=String(t).split(':').map(Number); return (isFinite(h)&&isFinite(m))? h*60+m : Infinity }
function firstWorkStart(c: SpecialCombo){ const starts=(c.work||[]).map(w=>w.start).filter(Boolean); return starts.length? Math.min(...starts.map(toMin)) : Infinity }
function sortCombosInPlace<T extends SpecialComboUI|SpecialCombo>(arr:T[]){ arr.sort((a,b)=>firstWorkStart(a)-firstWorkStart(b)) }
function mapItemsWithStableKeys(src: SpecialCombo[] | undefined): SpecialComboUI[] {
  const prevBySig = new Map(localItems.value.map(i => [
    JSON.stringify([i.serviceIds, i.locationIds, i.work]),
    i._key
  ]))
  const mapped = Array.isArray(src)
    ? src.map(x => {
        const clone: SpecialCombo = {
          id: x.id,
          serviceIds:  [...(x.serviceIds  || [])],
          locationIds: [...(x.locationIds || [])],
          work:        (x.work || []).map(w => ({ ...w }))
        }
        const sig = JSON.stringify([clone.serviceIds, clone.locationIds, clone.work])
        const key = (x as any)._key || prevBySig.get(sig) || uid()
        return { ...clone, _key: key }
      })
    : []
  sortCombosInPlace(mapped)
  return mapped
}

/* Header edit */
function initHeaderDraft(){ headerDraftStart.value = localDayStart.value || ''; headerDraftEnd.value = localDayEnd.value || localDayStart.value || '' }
function startEditHeader(){
  cancelEdit('__new__')
  editHeaderDate.value = true
  initHeaderDraft()
  nextTick(() => {
    const root = document.getElementById(`${props.idPrefix}header_edit_range`) as HTMLElement | null
    const target = (root?.querySelector('input,button,[tabindex]') as HTMLElement) || root
    target?.focus?.()
  })
}
function cancelHeaderEdit(){ editHeaderDate.value=false }
function onUpdateNewRange(val: [unknown, unknown] | null) {
  if (val == null) { newDraftStart.value = ''; newDraftEnd.value = ''; return }
  const [a, b] = val
  newDraftStart.value = (a === null) ? '' : (toYMDStrict(a) || newDraftStart.value)
  newDraftEnd.value   = (b === null) ? '' : (toYMDStrict(b) || newDraftEnd.value)
}
function onUpdateHeaderRange(val: [unknown, unknown] | null) {
  if (val == null) { headerDraftStart.value = ''; headerDraftEnd.value = ''; return }
  const [a, b] = val
  headerDraftStart.value = (a === null) ? '' : (toYMDStrict(a) || headerDraftStart.value)
  headerDraftEnd.value   = (b === null) ? '' : (toYMDStrict(b) || headerDraftEnd.value)
}
function saveHeaderEdit(){
  forceCommitActiveInput()
  const ds=headerDraftStart.value, de=headerDraftEnd.value || ds
  if(!ds || (de && ds>de)){ notify('danger', (t('ui.date.invalid_range') as string) || 'Datumsbereich ist ungültig.'); return }
  if(overlapsAny(ds,de,props.otherDays||[])){ notify('danger', (t('ui.date.overlap') as string) || 'Zeitraum überschneidet sich mit einem bestehenden Special Day.'); return }
  localDayStart.value=ds; localDayEnd.value=de; emit('updateDay',{dateStart:ds,dateEnd:de}); editHeaderDate.value=false
}

/* Popover helper */
function safePopoverAction(fn: () => void, close: () => void){ fn(); setTimeout(() => { try { close() } catch {} }, 0) }

/* Copy card */
function duplicateDay(){
  const cloned: SpecialCombo[] = localItems.value.map(({_key, id, ...rest}) => ({
    serviceIds:  [...(rest.serviceIds || [])],
    locationIds: [...(rest.locationIds || [])],
    work:        (rest.work   || []).map(w => ({ ...w })),
  }))
  emit('copyDay', { dateStart: '', dateEnd: '', items: cloned, requireDate: true })
}

/* Delete card */
const confirmDeleteDay = ref(false)
function doConfirmDeleteDay(){
  confirmDeleteDay.value = false
  editHeaderDate.value = false
  openEditors.clear()
  drafts.value = {}
  localDayStart.value = null
  localDayEnd.value = null
  localItems.value = []
  emit('deleteDay')
}

/* Combo editing */
type EditorKey = number | '__new__'
const openEditors = reactive(new Set<EditorKey>())
const drafts = ref<Record<string, SpecialCombo>>({})
const hasNewDraft = computed(() => openEditors.has('__new__'))
const { normalizeRange, sanitizeTimes, rangesOverlap, toPairs } = useTimeRanges()

function baseDraft(): SpecialCombo { return { serviceIds:[], locationIds:[], work:[{start:'',end:''}] } }
function ensureRows(k:EditorKey){ const d=drafts.value[k as any]; if(!d.work?.length) d.work=[{start:'',end:''}] }
function toggleEdit(i:number){
  if(openEditors.has(i)) return cancelEdit(i)
  const s=localItems.value[i]
  drafts.value[i]={ id:s.id, serviceIds:[...(s.serviceIds||[])], locationIds:[...(s.locationIds||[])], work:(s.work||[]).map(x=>({...x})) }
  ensureRows(i); openEditors.add(i)
  emit('open-form-tab', { tab: 'special_days', anchor: `sd_services_${i}` })
}
function startNewCombo(){
  editHeaderDate.value = false
  if (hasNewDraft.value) return
  if (!hasDay.value) { newDraftStart.value = ''; newDraftEnd.value = '' }
  drafts.value['__new__'] = baseDraft()
  openEditors.add('__new__')
  emit('open-form-tab', { tab: 'special_days', anchor: `sd_services_new` })
}
function cancelEdit(k:EditorKey){ openEditors.delete(k); delete drafts.value[k as any] }
function addWorkRow(k:EditorKey){ drafts.value[k as any].work.push({start:'',end:''}) }
function removeWorkRow(k:EditorKey,i:number){ const a=drafts.value[k as any].work; a.length<=1 ? (a[0]={start:'',end:''}) : a.splice(i,1) }

/* Validation (no overlaps, valid pairs, >=10m) */
function validateCombo(combo: SpecialCombo): string[] {
  const issues:string[]=[]
  const wRaw = combo.work || []

  const hasIncomplete = wRaw.some(r => (!!r.start && !r.end) || (!r.start && !!r.end))
  const hasInvalid    = wRaw.some(r => r.start && r.end && !normalizeRange(r.start, r.end))
  if (hasIncomplete || hasInvalid) {
    issues.push((t('mod.employees.form.validation.worktime_invalid') as string) || 'Die erfassten Arbeitszeiten sind nicht gültig.')
  }

  const workClean = sanitizeTimes(wRaw)
  if (!workClean.length) {
    issues.push((t('mod.employees.form.validation.work_required') as string) || 'Mindestens eine Arbeitszeit ist erforderlich.')
  }

  const pairs = toPairs(workClean)
  const tooShort  = pairs.some(p => p[1] - p[0] < 10)
  if (tooShort) {
    issues.push((t('mod.employees.form.validation.worktime_invalid') as string) || 'Die erfassten Arbeitszeiten sind nicht gültig.')
  }

  for (let i = 0; i < pairs.length; i++) {
    for (let j = i + 1; j < pairs.length; j++) {
      if (rangesOverlap(pairs[i], pairs[j])) {
        issues.push((t('mod.employees.form.validation.worktime_invalid') as string) || 'Die erfassten Arbeitszeiten sind nicht gültig.')
        i = pairs.length; break
      }
    }
  }
  return Array.from(new Set(issues))
}

/* Notifications & commit */
function notify(level:'success'|'info'|'warning'|'danger', message:string, timeoutMs?:number){
  window.dispatchEvent(new CustomEvent('bookando:notify',{ detail:{level,message,timeoutMs} }))
}
function forceCommitActiveInput(){ (document.activeElement as HTMLElement|undefined)?.dispatchEvent(new Event('blur',{bubbles:true})) }

/* Save / delete combo */
function onDeleteCombo(i:number){
  if ((props.items?.length || 0) <= 1) { confirmDeleteDay.value = true; return }
  emit('deleteCombo', i)
}
async function saveInline(k:EditorKey){
  forceCommitActiveInput(); await nextTick()

  const v = { ...drafts.value[k as any] }
  v.serviceIds  = asIds(v.serviceIds)
  v.locationIds = asIds(v.locationIds)

  const problems = validateCombo(v)
  if (problems.length) {
    notify('danger', `${(t('mod.employees.form.validation.title') as string)||'Bitte Eingaben prüfen'}:\n• ${problems.join('\n• ')}`)
    return
  }

  const cleaned: SpecialCombo = { ...v, work: sanitizeTimes(v.work) }

  if (k === '__new__') {
    if (!hasDay.value) {
      const ds = newDraftStart.value
      const de = newDraftEnd.value || newDraftStart.value
      if (!ds || (de && ds > de)) { notify('danger', (t('ui.date.invalid_range') as string) || 'Datumsbereich ist ungültig.'); return }
      if (overlapsAny(ds, de, props.otherDays || [])) { notify('danger', (t('ui.date.overlap') as string) || 'Zeitraum überschneidet sich mit einem bestehenden Special Day.'); return }
      localDayStart.value = ds
      localDayEnd.value   = de
      emit('updateDay', { dateStart: ds, dateEnd: de })
    }
    emit('saveCombo', { index: null, value: cleaned })
  } else {
    emit('saveCombo', { index: k as number, value: cleaned })
  }

  cancelEdit(k)
}

/* i18n helpers */
function countLabel(n:number){ return n>0 ? String(n) : allWord.value }
const andWord = computed(() => (t('core.common.and') as string) || 'und')
const allWord = computed(() => (t('core.common.all') as string) || 'Alle')
function serviceWord(n:number){ return n===1 ? (t('mod.employees.form.special_days.service') as string || 'Dienstleistung') : (t('mod.employees.form.special_days.services') as string || 'Dienstleistungen') }
function locationWord(n:number){ return n===1 ? (t('mod.employees.form.special_days.location') as string || 'Ort') : (t('mod.employees.form.special_days.locations') as string || 'Orte') }
function mapIdsToNames(ids:number[], options:any[], labelKey:string, valueKey:string){
  const set = new Set(ids.map(Number))
  return options.filter(o => set.has(Number(o?.[valueKey]))).map(o => String(o?.[labelKey] ?? o?.[valueKey]))
}
function formatYMD(d?:string|null){ if(!d) return ''; const [y,m,dd]=String(d).split('-'); return `${dd}.${m}.${y}` }
function toYMDStrict(input:any):string|null{
  if(!input) return null
  if(input instanceof Date){ const y=input.getFullYear(), m=String(input.getMonth()+1).padStart(2,'0'), d=String(input.getDate()).padStart(2,'0'); return `${y}-${m}-${d}` }
  if(typeof input==='string'){
    if(/^\d{4}-\d{2}-\d{2}$/.test(input)) return input
    if(/^\d{2}\.\d{2}\.\d{4}$/.test(input)){ const [dd,mm,yyyy]=input.split('.'); return `${yyyy}-${mm}-${dd}` }
  }
  return null
}
function toRangeModel(a?: string | null, b?: string | null) {
  const A = a || null, B = b || null
  return (!A && !B) ? null : [A, B] as [string | null, string | null]
}

/* Overlap helpers */
function normEnd(end?:string|null,start?:string){ return (end && end.length) ? end : (start || '') }
function overlaps(aStart:string,aEnd:string,bStart:string,bEnd:string){ return (aStart <= bEnd) && (bStart <= aEnd) }
function overlapsAny(start:string,end:string,others:DayRange[]){ const aS=start, aE=normEnd(end,start); return (others||[]).some(o=>{ const bS=o.dateStart, bE=normEnd(o.dateEnd||null,o.dateStart); return overlaps(aS,aE,bS,bE) }) }
</script>
