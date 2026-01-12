<!-- EmployeesForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dialog-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <!-- Calendar overlay -->
    <AppAddCalendar
      v-if="showAddCal"
      :open="true"
      :employee-id="Number(form?.id) || 0"
      mode="ro"
      @close="showAddCal = false"
      @connected="onCalConnected"
    />

    <!-- Hard-Delete Confirm -->
    <AppModal
      :show="confirmHardDelete"
      module="employees"
      action="hard_delete"
      type="danger"
      :close-on-backdrop="false"
      :close-on-esc="true"
      @confirm="doHardDelete"
      @cancel="confirmHardDelete = false"
    />

    <AppForm>
      <!-- HEADER -->
      <template #header>
        <h2 id="dialog-title">
          {{ form.id ? t('mod.employees.actions.edit') || 'Mitarbeiter bearbeiten' : t('mod.employees.actions.add') || 'Mitarbeiter hinzufügen' }}
        </h2>
        <AppButton
          icon="x"
          btn-type="icononly"
          variant="standard"
          size="square"
          icon-size="md"
          @click="onCancel"
        />
      </template>

      <!-- TABS -->
      <template #tabs>
        <AppTabs
          v-model="tab"
          :tabs="tabsDef"
          nav-only
        />
      </template>

      <!-- BODY -->
      <template #default>
        <div v-if="form">
          <div
            v-if="error"
            class="bookando-alert bookando-alert--danger"
            role="alert"
            aria-live="assertive"
          >
            {{ error }}
          </div>

          <!-- DETAILS -->
          <section
            v-show="tab === 'details'"
            id="bookando-tabpanel-details"
            class="tab-content"
            role="tabpanel"
            aria-labelledby="bookando-tab-details"
            tabindex="0"
          >
            <div class="bookando-flex bookando-flex-col bookando-items-center bookando-justify-center bookando-gap-sm bookando-width-full bookando-my-md">
              <AppAvatar
                :src="form.avatar_url"
                :initials="initials(form)"
                size="xl"
                :can-upload="true"
                :can-remove="!!form.avatar_url"
                @upload="openAvatarDialog"
                @remove="removeAvatar"
              />
              <input
                ref="avatarInput"
                type="file"
                accept="image/*"
                class="bookando-hide"
                @change="onAvatarFileSelect"
              >

              <div class="bookando-text-lg bookando-font-semibold bookando-mt-xxs">
                {{ fullName || t('core.common.unnamed') }}
              </div>

              <div class="bookando-inline-flex bookando-items-center bookando-gap-xs bookando-mt-xxs">
                <AppPopover
                  trigger-mode="icon"
                  trigger-icon="more-horizontal"
                  trigger-variant="standard"
                  :offset="2"
                  width="content"
                  :panel-min-width="240"
                  :close-on-item-click="true"
                >
                  <template #content="{ close }">
                    <div
                      class="popover-menu"
                      role="menu"
                    >
                      <div
                        v-for="opt in quickOptions"
                        :key="opt.value"
                        class="dropdown-option"
                        role="menuitem"
                        :aria-disabled="opt.disabled ? 'true' : undefined"
                        :class="{ 'bookando-text-muted': opt.disabled }"
                        @click.stop="!opt.disabled && onQuickAction(opt.value, close)"
                      >
                        <AppIcon
                          :name="opt.icon"
                          class="dropdown-icon"
                          :class="opt.className"
                        />
                        <span class="option-label">{{ opt.label }}</span>
                      </div>
                    </div>
                  </template>
                </AppPopover>

                <span :class="['bookando-status-label', statusClass(form.status)]">
                  <span class="status-label-text">
                    {{ statusLabelForForm(form.status, form.deleted_at) }}
                  </span>
                </span>
              </div>

              <span
                v-if="avatarError"
                class="bookando-alert bookando-alert--danger bookando-text-center"
              >
                {{ avatarError }}
              </span>
            </div>

            <form
              :id="formId"
              class="bookando-form"
              novalidate
              autocomplete="off"
              aria-describedby="form-error"
              @submit.prevent="onSubmit"
            >
              <div class="bookando-grid two-columns">
                <BookandoField
                  id="first_name"
                  v-model="form.first_name"
                  type="text"
                  :label="fieldLabel(t, 'first_name', MODULE)"
                  required
                />
                <BookandoField
                  id="last_name"
                  v-model="form.last_name"
                  type="text"
                  :label="fieldLabel(t, 'last_name', MODULE)"
                  required
                />
              </div>

              <BookandoField
                id="email"
                v-model="form.email"
                type="email"
                :label="fieldLabel(t, 'email', MODULE)"
              />
              <BookandoField
                id="phone"
                v-model="form.phone"
                type="phone"
                :label="fieldLabel(t, 'phone', MODULE)"
                source="countries"
              />
              <BookandoField
                id="address"
                v-model="form.address"
                type="text"
                :label="fieldLabel(t, 'address', MODULE)"
              />
              <BookandoField
                id="address_2"
                v-model="form.address_2"
                type="text"
                :label="fieldLabel(t, 'address_2', MODULE)"
              />

              <div
                class="bookando-grid"
                style="--bookando-grid-cols: 1fr 2fr"
              >
                <BookandoField
                  id="zip"
                  v-model="form.zip"
                  type="text"
                  :label="fieldLabel(t, 'zip', MODULE)"
                />
                <BookandoField
                  id="city"
                  v-model="form.city"
                  type="text"
                  :label="fieldLabel(t, 'city', MODULE)"
                />
              </div>

              <BookandoField
                id="country"
                v-model="form.country"
                type="dropdown"
                source="countries"
                :searchable="true"
                :clearable="true"
                show-flag
                :label="fieldLabel(t, 'country', MODULE)"
                option-label="label"
                option-value="code"
                mode="flag-label"
              />

              <div class="bookando-grid two-columns">
                <BookandoField
                  id="gender"
                  v-model="form.gender"
                  type="dropdown"
                  searchable
                  source="genders"
                  :label="fieldLabel(t, 'gender', MODULE)"
                  option-label="label"
                  option-value="value"
                  mode="basic"
                  clearable
                />
                <BookandoField
                  id="birthdate"
                  v-model="form.birthdate"
                  type="date"
                  :label="fieldLabel(t, 'birthdate', MODULE)"
                  :placeholder="t('ui.date.select')"
                  format="dd.MM.yyyy"
                  :clearable="true"
                  :auto-apply="true"
                  :text-input="true"
                  commit-on="blur"
                  model-type="yyyy-MM-dd"
                  min-date="1920-01-01"
                  max-date="2099-12-31"
                  input-icon="calendar"
                />
              </div>

              <BookandoField
                id="language"
                v-model="form.language"
                type="dropdown"
                source="languages"
                :searchable="true"
                :clearable="true"
                show-flag
                :label="fieldLabel(t, 'language', MODULE)"
                mode="flag-label"
              />

              <div class="bookando-grid two-columns">
                <BookandoField
                  id="timezone"
                  v-model="form.timezone"
                  type="dropdown"
                  source="timezones"
                  searchable
                  clearable
                  :label="fieldLabel(t, 'timezone', MODULE)"
                />
                <BookandoField
                  id="work_locations"
                  v-model="form.work_locations"
                  type="dropdown"
                  source="locations"
                  :searchable="true"
                  :clearable="true"
                  multiple
                  :label="fieldLabel(t, 'work_locations', MODULE)"
                  option-label="label"
                  option-value="id"
                />
              </div>

              <BookandoField
                id="badge"
                v-model="form.badge"
                type="dropdown"
                source="badges"
                :searchable="true"
                :clearable="true"
                :label="fieldLabel(t, 'badge', MODULE)"
                option-label="label"
                option-value="id"
              />

              <BookandoField
                id="employee_area_password"
                v-model="form.employee_area_password"
                type="password"
                :label="fieldLabel(t, 'employee_area_password', MODULE)"
                :placeholder="t('mod.employees.employee_area_password_hint')"
              />

              <BookandoField
                id="description"
                v-model="form.description"
                type="textarea"
                :label="fieldLabel(t, 'description', MODULE)"
              />
              <BookandoField
                id="note"
                v-model="form.note"
                type="textarea"
                :label="fieldLabel(t, 'note', MODULE)"
              />
            </form>
          </section>

          <!-- CALENDAR -->
          <section v-show="tab === 'calendar'">
            <AppCalendarOverview
              :calendars="calendars"
              :employee-id="Number(form?.id) || 0"
              @open-add="showAddCal = true"
              @change-calendars="handleChangeCalendars"
            />
          </section>

          <!-- ASSIGNED SERVICES (stub) -->
          <section
            v-show="tab === 'services'"
            id="bookando-tabpanel-services"
            class="tab-content"
            role="tabpanel"
            aria-labelledby="bookando-tab-services"
            tabindex="0"
          >
            <div
              v-for="group in serviceGroups"
              :key="group.id"
              class="bookando-card bookando-mb-sm"
            >
              <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-xs">
                <label class="bookando-flex bookando-items-center bookando-gap-xs">
                  <input
                    v-model="group.enabled"
                    type="checkbox"
                  >
                  <strong>{{ group.name }}</strong>
                </label>
                <div
                  class="bookando-grid"
                  style="--bookando-grid-cols: 1fr 1fr; gap: .75rem;"
                >
                  <span class="bookando-text-sm">{{ t('fields.capacity') }}</span>
                  <span class="bookando-text-sm">{{ t('fields.price') }}</span>
                </div>
              </div>

              <div
                v-for="srv in group.services"
                :key="srv.id"
                class="bookando-grid"
                style="--bookando-grid-cols: 1fr 100px 100px 140px; gap: .5rem; align-items: center;"
              >
                <label class="bookando-flex bookando-items-center bookando-gap-xs">
                  <input
                    v-model="srv.enabled"
                    type="checkbox"
                  >
                  <span :title="srv.name">{{ srv.name }}</span>
                </label>
                <input
                  v-model.number="srv.minCapacity"
                  type="number"
                  min="1"
                  class="bookando-input"
                  :disabled="!srv.enabled"
                  :aria-label="t('fields.min_capacity')"
                  :placeholder="t('fields.min_abbrev') || 'Min.'"
                >
                <input
                  v-model.number="srv.maxCapacity"
                  type="number"
                  min="1"
                  class="bookando-input"
                  :disabled="!srv.enabled"
                  :aria-label="t('fields.max_capacity')"
                  :placeholder="t('fields.max_abbrev') || 'Max.'"
                >
                <input
                  v-model="srv.price"
                  type="text"
                  class="bookando-input"
                  :disabled="!srv.enabled"
                  :aria-label="t('fields.price')"
                  :placeholder="t('fields.price_placeholder') || 'CHF 0.00'"
                >
              </div>
            </div>
          </section>

          <!-- WORKING DAYS -->
          <section
            v-show="tab === 'workingdays'"
            id="bookando-tabpanel-workingdays"
            class="tab-content"
            role="tabpanel"
            aria-labelledby="bookando-tab-workingdays"
            tabindex="0"
          >
            <div class="bookando-grid one-column align-top bookando-gap-md">
              <AppSectionIntro
                :title="t('mod.employees.form.working_days.header') || 'Arbeitszeiten erfassen'"
                :hint="t('mod.employees.form.working_days.header_hint') || 'Hier kann die Standard-Arbeitswoche für Dienstleistungen und Orte geplant werden.'"
                as="h3"
              />

              <AppWorkingDayCard
                v-for="(day, idx) in workingDays"
                :key="day.key"
                :day-key="day.key"
                :title="day.label"
                :combos="day.combos"
                :service-options="flattenedServices"
                service-option-label="name"
                service-option-value="id"
                @apply-to-days="({ toDayKeys }) => applyToDays(idx, toDayKeys)"
                @save-combo="({ index, value }) => saveCombo(idx, index, value)"
                @delete-combo="(i) => removeCombo(idx, i)"
              />
            </div>
          </section>

          <!-- DAYS OFF -->
          <section
            v-show="tab === 'days_off'"
            id="bookando-tabpanel-off"
            class="tab-content"
            role="tabpanel"
            aria-labelledby="bookando-tab-off"
            tabindex="0"
          >
            <div class="bookando-grid one-column align-top bookando-gap-md">
              <AppSectionIntro
                :title="t('mod.employees.form.days_off.header') || 'Freie Tage erfassen'"
                :hint="t('mod.employees.form.days_off.header_hint') || 'Plane Urlaube/Abwesenheiten. Wiederholbare Einträge werden jährlich berücksichtigt.'"
                as="h3"
              />

              <AppDaysOffCard
                :title="t('mod.employees.tabs.days_off') || 'Freie Tage'"
                :items="daysOff"
                @save-combo="({ index, value }) => {
                  if (index == null) {
                    const id = Math.max(0, ...daysOff.map(d => d.id)) + 1
                    daysOff.push({ ...value, id })
                  } else {
                    daysOff.splice(index, 1, { ...daysOff[index], ...value })
                  }
                  daysOffDirty = true
                }"
                @delete-combo="(i) => { daysOff.splice(i, 1); daysOffDirty = true }"
              />
            </div>
          </section>

          <!-- SPECIAL DAYS -->
          <section
            v-show="tab === 'specialdays'"
            id="bookando-tabpanel-special"
            class="tab-content"
            role="tabpanel"
            aria-labelledby="bookando-tab-special"
            tabindex="0"
          >
            <div class="bookando-grid one-column align-top bookando-gap-md">
              <AppSectionIntro
                :title="t('mod.employees.form.special_days.header') || 'Besondere Tage definieren'"
                :hint="t('mod.employees.form.special_days.header_hint') || ''"
                as="h3"
              />

              <!-- Global toolbar -->
              <div class="bookando-mb-sm">
                <div
                  class="bookando-grid"
                  style="--bookando-grid-cols: 1fr auto auto; gap:.75rem; align-items:center;"
                >
                  <BookandoField
                    id="sd_global_filter_range"
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
                    :placeholder="t('ui.date.range_select') || 'Zeitraum wählen'"
                    :model-value="toRangeModel(sdFilterStart, sdFilterEnd)"
                    @update:model-value="onUpdateSdFilterRange"
                  />
                  <AppButton
                    icon="rotate-ccw"
                    btn-type="icononly"
                    size="square"
                    variant="standard"
                    :tooltip="t('core.common.reset') || 'Zurücksetzen'"
                    :aria-label="t('ui.filter.reset_all') || 'Reset Filter'"
                    @click="resetSdFilter"
                  />
                  <AppButton
                    icon="plus"
                    btn-type="full"
                    size="dynamic"
                    variant="primary"
                    @click="addSpecialDayCard()"
                  >
                    {{ t('core.common.add') }}
                  </AppButton>
                </div>
              </div>

              <!-- Cards -->
              <div
                v-for="card in filteredSpecialDayCards"
                :key="card.id"
                :ref="el => setCardRef(card.id, el)"
              >
                <AppSpecialDaysCard
                  :id-prefix="`sd_${card.id}_`"
                  :open-mode="openModeById[card.id] || 'none'"
                  :title="t('mod.employees.special_days') || 'Besondere Tage'"
                  :date-start="card.dateStart"
                  :date-end="card.dateEnd"
                  :items="card.items"
                  :other-days="otherDaysForId(card.id)"

                  :service-options="flattenedServices"
                  service-option-label="name"
                  service-option-value="id"
                  :location-options="[]"
                  location-option-label="label"
                  location-option-value="id"

                  @consumed-open="openModeById[card.id] = 'none'"
                  @copy-day="({ items }) => {
                    const newId = nextCardId()
                    specialDayCards.unshift({
                      id: newId,
                      dateStart: null,
                      dateEnd: null,
                      items: JSON.parse(JSON.stringify(items))
                    })
                    nextTick(() => {
                      openModeById[newId] = 'editDate'
                      scrollToCard(newId)
                    })
                  }"
                  @save-combo="({ index, value }) => { upsertSpecialCombo(card, index, value); specialDaysDirty = true }"
                  @delete-combo="(i) => { card.items.splice(i, 1); card.items = card.items.slice(); specialDaysDirty = true }"
                  @delete-day="() => { specialDayCards.splice(specialDayCards.indexOf(card), 1); specialDaysDirty = true }"
                  @update-day="({ dateStart, dateEnd }) => {
                    card.dateStart = dateStart
                    card.dateEnd = dateEnd
                    ensureFilterCovers(dateStart, dateEnd)
                    sortCardsAndScroll(card.id)
                    specialDaysDirty = true
                  }"
                />
              </div>
            </div>
          </section>
        </div>
      </template>

      <!-- FOOTER -->
      <template #footer>
        <div class="bookando-form-buttons bookando-form-buttons--split">
          <div class="bookando-inline-flex bookando-items-center bookando-gap-sm">
            <AppButton
              btn-type="textonly"
              variant="secondary"
              size="dynamic"
              type="button"
              @click="onCancel"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
          </div>
          <AppButton
            btn-type="full"
            variant="primary"
            size="dynamic"
            type="submit"
            :form="formId"
          >
            {{ t('core.common.save') }}
          </AppButton>
        </div>
      </template>
    </AppForm>
  </div>
</template>

<script setup lang="ts">
/* =======================================================================================
 * IMPORTS
 * ======================================================================================= */
import { onMounted, onUnmounted, ref, reactive, watch, computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { getEmployee as apiGetEmployee, deleteEmployee as apiDeleteEmployee } from '../api/EmployeesApi'

import AppForm from '@core/Design/components/AppForm.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import AppModal from '@core/Design/components/AppModal.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppSectionIntro from '@core/Design/components/AppSectionIntro.vue'
import AppWorkingDayCard from './AppWorkingDayCard.vue'
import AppSpecialDaysCard  from './AppSpecialDaysCard.vue'
import AppDaysOffCard      from './AppDaysOffCard.vue'
import AppCalendarOverview from './AppCalendarOverview.vue'
import AppAddCalendar from './AppAddCalendar.vue'

import httpBase from '@assets/http'
import { notify } from '@core/Composables/useNotifier'
import { statusLabel } from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'

/* Central composable */
import {
  normalizeEmployeeFromApi,
  serializeSpecialDaysFromCards,
  type EmployeeVM,
  type EmployeeFormBase,
  type WorkDay,
  type UIDayOff,
  type SDSpecialDayCard,
  type ServiceGroup,
} from '../../../composables/useEmployeeData'

/* HTTP module client */
const http = httpBase.module('employees')

/* I18n + module constant */
const MODULE = 'employees'
const { t, locale } = useI18n()

/* =======================================================================================
 * TYPES
 * ======================================================================================= */
type SDTime = { id?: number; start: string; end: string }

type Employee = EmployeeFormBase & {
  workday_sets?: any[]
  working_hours?: any[]
  days_off?: any[]
  special_days?: any[]
  special_day_sets?: any[]
  assigned_services?: any
  services?: any
  calendars?: any[]
  created_at?: string
  updated_at?: string
}

type EmployeeCalendar = {
  id?: number
  calendar: 'google' | 'outlook' | 'exchange' | 'apple'
  calendar_id: string
  token?: string | null
  mode?: 'ro' | 'wb'
  label?: string
  subLabel?: string
}

type SpecialDayVM = {
  id?: number
  start: string
  end: string
  serviceIds: number[]
  locationIds: number[]
  work: SDTime[]
}
type EmployeeFormVM = {
  form: Employee
  workingDays: WorkDay[]
  workingDaysDirty: boolean
  daysOff: Array<{ id:number; title?:string; note?:string; start:string; end:string; repeatYearly?:boolean }>
  daysOffDirty: boolean
  specialDays: SpecialDayVM[]
  specialDaysDirty: boolean
  serviceGroups?: ServiceGroup[]
  servicesDirty?: boolean
  calendars: any[]
  formDirty: boolean
}

/* =======================================================================================
 * PROPS / EMITS
 * ======================================================================================= */
const props = defineProps<{ modelValue: Employee | null }>()
const emit = defineEmits<{ (event: 'save', vm: EmployeeFormVM): void; (event: 'cancel'): void }>()

/* =======================================================================================
 * UI / Tabs
 * ======================================================================================= */
function L(key: string, fallback: string) { const val = t(key) as string; return val === key ? fallback : val }
const tabsDef = computed(() => ([
  { label: L('mod.employees.form.tabs.details', 'Details'), value: 'details' },
  { label: L('mod.employees.tabs.calendar', 'Kalender'), value: 'calendar' },
  { label: L('mod.employees.tabs.assigned_services', 'Zugeordnete Dienstleistungen'), value: 'services' },
  { label: L('mod.employees.tabs.working_hours', 'Arbeitszeiten'), value: 'workingdays' },
  { label: L('mod.employees.tabs.days_off', 'Freie Tage'), value: 'days_off' },
  { label: L('mod.employees.tabs.special_days', 'Besondere Tage'), value: 'specialdays' }
]))
type TabKey = 'details' | 'calendar' | 'services' | 'workingdays' | 'days_off' | 'specialdays'
const tab = ref<TabKey>('details')

/* =======================================================================================
 * CENTRAL VM STATE + local refs
 * ======================================================================================= */
const emptyEmployee: Employee = {
  id: undefined,
  first_name: '', last_name: '',
  email: '', phone: '',
  address: '', address_2: '', zip: '', city: '',
  country: null as any, gender: '', birthdate: '',
  language: 'de', timezone: 'Europe/Zurich',
  work_locations: [], badge: null as any,
  employee_area_password: '', description: '', note: '',
  avatar_url: '', deleted_at: null, status: 'active',
}
const vm = ref<EmployeeVM | null>(null)

const form = ref<Employee>({ ...emptyEmployee })
const workingDays = ref<WorkDay[]>([])
const daysOff = ref<UIDayOff[]>([])
const specialDayCards = ref<SDSpecialDayCard[]>([])
const serviceGroups = ref<ServiceGroup[]>([])
const calendars = ref<EmployeeCalendar[]>([])

/* Dirty flags */
const formDirty = ref(false)
let workingDaysDirty = false
let daysOffDirty = false
let specialDaysDirty = false
let servicesDirty = false

/* Baseline JSON for pristine compare */
const _formBaselineJson = ref(JSON.stringify(form.value))

/* =======================================================================================
 * AVATAR
 * ======================================================================================= */
const error = ref('')
const avatarError = ref('')
const formId = `bookando-form-${Math.random().toString(36).slice(2, 8)}`
const avatarInput = ref<HTMLInputElement | null>(null)
function initials(e: Employee) { return ((e.first_name?.[0] || '') + (e.last_name?.[0] || '')).toUpperCase() }
const fullName = computed(() => `${form.value.first_name || ''} ${form.value.last_name || ''}`.trim())

function openAvatarDialog(){ avatarError.value = ''; avatarInput.value?.click() }
function onAvatarFileSelect(event: Event) {
  avatarError.value = ''
  const input = event.target as HTMLInputElement
  if (!input.files || !input.files[0]) return
  if (!form.value.id || !Number.isInteger(Number(form.value.id))) {
    const msg = t('ui.upload.saveFirst'); avatarError.value = msg; notify('danger', msg); input.value = ''; return
  }
  const file = input.files[0]
  if (!file.type.startsWith('image/')) { const msg = t('ui.upload.only_images'); avatarError.value = msg; notify('danger', msg); return }
  if (file.size > 5 * 1024 * 1024)      { const msg = t('ui.upload.too_large_5mb'); avatarError.value = msg; notify('danger', msg); return }
  uploadAvatar(file); input.value = ''
}
async function uploadAvatar(file: File) {
  if (!form.value.id) { const msg = t('ui.upload.saveFirst'); avatarError.value = msg; notify('danger', msg); return }
  try {
    const fd = new FormData()
    fd.append('avatar', file)
    const { data } = await http.post<any>(`bookando/v1/users/${Number(form.value.id)}/avatar`, fd, { absolute: true })
    form.value.avatar_url = data?.avatar_url || ''
    avatarError.value = ''
  } catch (_e: any) { const msg = _e?.message || (t('ui.upload.avatar_upload_error') as string); avatarError.value = msg; notify('danger', msg) }
}
async function removeAvatar(){
  if (!form.value.id) return
  try { await http.del<any>(`bookando/v1/users/${form.value.id}/avatar`, undefined, { absolute: true }); form.value.avatar_url = ''; avatarError.value = '' }
  catch (_e:any) { const msg = _e?.message || (t('ui.upload.avatar_remove_error') as string); avatarError.value = msg; notify('danger', msg) }
}

/* Status / quick actions */
function statusClass(val: string) { return val === 'active' ? 'active' : val === 'blocked' ? 'inactive' : 'deleted' }
function statusLabelForForm(status: string, deletedAt: string | null) {
  if (status === 'deleted' && !deletedAt) return t('core.status.marked_for_deletion')
  return statusLabel(status, locale.value)
}
type QuickKey = 'soft_delete' | 'hard_delete' | 'block' | 'activate'
type QuickOption = { value: QuickKey; label: string; icon: string; className?: string; disabled?: boolean }
const pendingHardDelete = ref(false)
const confirmHardDelete = ref(false)
const quickOptions = computed<QuickOption[]>(() => {
  const s = form.value.status
  const isMarked = s === 'deleted' && !form.value.deleted_at
  return [
    { value: 'block',       label: t('core.actions.block.label'),       icon: 'user-x',     className: 'bookando-text-warning', disabled: s === 'blocked' },
    { value: 'activate',    label: t('core.actions.activate.label'),    icon: 'user-check', className: 'bookando-text-success', disabled: s === 'active'  },
    { value: 'soft_delete', label: t('core.actions.soft_delete.label'), icon: 'user-minus', className: 'bookando-text-danger',  disabled: isMarked },
    { value: 'hard_delete', label: t('core.actions.hard_delete.label'), icon: 'trash-2',    className: 'bookando-text-danger' }
  ]
})
function onQuickAction(action: QuickKey, close?: () => void) {
  close?.()
  switch (action) {
    case 'block':       form.value.status = 'blocked'; pendingHardDelete.value = false; break
    case 'activate':    form.value.status = 'active';  form.value.deleted_at = null; pendingHardDelete.value = false; break
    case 'soft_delete': form.value.status = 'deleted'; form.value.deleted_at = null; pendingHardDelete.value = false; break
    case 'hard_delete': form.value.status = 'deleted'; form.value.deleted_at = null; pendingHardDelete.value = true;  break
  }
}

/* =======================================================================================
 * CALENDAR
 * ======================================================================================= */
function handleChangeCalendars(list: EmployeeCalendar[]) { calendars.value = [...list]; calendarsDirty = true }
let calendarsDirty = false
const showAddCal = ref(false)
function onCalConnected(p:{calendar:'google'|'outlook'|'exchange'|'apple'; calendar_id:string; token?:string|null; mode:'ro'|'wb'; id?:number}) {
  const sub =
    p.calendar === 'google'  ? (t('ui.calendar.sub.google')   || 'Gmail, G Suite') :
    p.calendar === 'outlook' ? (t('ui.calendar.sub.outlook')  || 'Office-365/Outlook/Hotmail') :
    p.calendar === 'exchange'? (t('ui.calendar.sub.exchange') || 'EWS/AutoDiscover') :
                               (t('ui.calendar.sub.apple')    || 'iCloud / ICS')
  const label =
    p.calendar === 'google'   ? (t('mod.employees.form.calendar.labels.google_calendar')   || 'Google-Kalender') :
    p.calendar === 'outlook'  ? (t('mod.employees.form.calendar.labels.outlook_calendar')  || 'Outlook-Kalender') :
    p.calendar === 'exchange' ? (t('mod.employees.form.calendar.labels.exchange_calendar') || 'Exchange-Kalender') :
                                (t('mod.employees.form.calendar.labels.apple_calendar')    || 'Apple / iCloud')
  calendars.value = [...calendars.value, { calendar: p.calendar, calendar_id: p.calendar_id, label, subLabel: sub, mode: p.mode, id: p.id }]
  calendarsDirty = true
  showAddCal.value = false
}

/* =======================================================================================
 * SERVICES
 * ======================================================================================= */
const flattenedServices = computed(() =>
  (serviceGroups.value || []).flatMap(g => g.services.map(s => ({ id: s.id, name: s.name })))
)
watch(serviceGroups, () => { servicesDirty = true }, { deep: true })

/* =======================================================================================
 * WORKING DAYS / DAYS OFF / SPECIAL DAYS – CRUD
 * ======================================================================================= */
type DayCombo = { id:number; serviceIds:number[]; locationIds:number[]; work:SDTime[] }
const keyToIndex:Record<string,number> = { mon:0, tue:1, wed:2, thu:3, fri:4, sat:5, sun:6 }
function saveCombo(dayIdx:number, index:number|null, value:Omit<DayCombo,'id'>|DayCombo) {
  const day = workingDays.value[dayIdx]
  const copy = {
    serviceIds:  [...(value as any).serviceIds],
    locationIds: [...(value as any).locationIds],
    work:        [...((value as any).work || [])],
  }
  if (index == null) {
    const id = Math.max(0, ...day.combos.map(c => c.id || 0)) + 1
    day.combos.push({ id, ...copy })
  } else {
    const old = day.combos[index]
    day.combos.splice(index, 1, { id: old.id, ...copy })
  }
  workingDaysDirty = true
}
function removeCombo(dayIdx:number, index:number) { workingDays.value[dayIdx].combos.splice(index, 1); workingDaysDirty = true }
function applyToDays(fromIdx:number, toKeys:string[]) {
  const src = workingDays.value[fromIdx]
  const clone = (input:any)=>JSON.parse(JSON.stringify(input))
  for (const k of toKeys) {
    const i = keyToIndex[k]
    if (i === undefined || i === fromIdx) continue
    workingDays.value[i].combos = clone(src.combos)
  }
  workingDaysDirty = true
}

/* Days off */
function toRangeModel(a?:string|null,b?:string|null){ const A=a||null, B=b||null; return (!A&&!B) ? null : [A,B] as [string|null,string|null] }
const sdFilterStart = ref<string>(''); const sdFilterEnd = ref<string>('')
function onUpdateSdFilterRange(range:[unknown,unknown] | null){
  const toYMDStrict=(x:any)=> typeof x==='string' ? (x.match(/^\d{4}-\d{2}-\d{2}$/)?x:x) : (x && x instanceof Date ? `${x.getFullYear()}-${String(x.getMonth()+1).padStart(2,'0')}-${String(x.getDate()).padStart(2,'0')}` : null)
  if (range == null) { sdFilterStart.value = ''; sdFilterEnd.value = ''; return }
  const [a,b]=range; sdFilterStart.value = (a===null)?'':(toYMDStrict(a) || sdFilterStart.value); sdFilterEnd.value   = (b===null)?'':(toYMDStrict(b) || sdFilterEnd.value)
}
function ensureFilterCovers(ds: string, de?: string | null) { const end = de || ds; if (!sdFilterStart.value || ds < sdFilterStart.value) sdFilterStart.value = ds; if (!sdFilterEnd.value || end > sdFilterEnd.value) sdFilterEnd.value = end }
function resetSdFilter(){
  const today=new Date(); const y=today.getFullYear(); const m=today.getMonth(); const d=today.getDate()
  const s=`${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`
  const e=new Date(y+1,m,d); e.setDate(e.getDate()-1)
  const ee=`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,'0')}-${String(e.getDate()).padStart(2,'0')}`
  sdFilterStart.value = s; sdFilterEnd.value = ee
}

/* Special-Day cards helpers */
const openModeById = reactive<Record<number, 'none' | 'new' | 'editDate'>>({})
function otherDaysForId(cardId:number){
  return specialDayCards.value
    .filter(c => c.id !== cardId)
    .filter(c => !!c.dateStart)
    .map(c => ({ dateStart: c.dateStart as string, dateEnd: c.dateEnd || c.dateStart }))
}
const cardRefs = ref<Record<number, HTMLElement|null>>({})
function setCardRef(id:number, el: any){ cardRefs.value[id] = el as HTMLElement | null }
function sortCardsInPlace(){
  const normEnd=(end?:string|null,start?:string|null)=> end || start || ''
  const cmp=(a?:string|null,b?:string|null)=> (a||'')<(b||'')?-1:(a||'')>(b||'')?1:0
  specialDayCards.value.sort((a,b) => {
    if(!a.dateStart && !b.dateStart) return 0
    if(!a.dateStart) return -1
    if(!b.dateStart) return 1
    const s=cmp(a.dateStart,b.dateStart)
    return s!==0?s:cmp(normEnd(a.dateEnd,a.dateStart), normEnd(b.dateEnd,b.dateStart))
  })
}
function scrollToCard(id:number){ const el = cardRefs.value[id]; el?.scrollIntoView?.({ block:'start', inline:'nearest', behavior:'smooth' }) }
function nextCardId(){ return Math.max(0, ...specialDayCards.value.map(c=>c.id)) + 1 }
async function sortCardsAndScroll(id:number){ sortCardsInPlace(); await nextTick(); scrollToCard(id) }
function upsertSpecialCombo(card: SDSpecialDayCard, index: number | null | undefined, value: any) {
  if (index == null) { card.items.push(value) } else { card.items.splice(index, 1, value) }
  const first=(c:any)=> (c.work||[]).map((w:any)=>w.start).sort()[0] || '99:99'
  card.items.sort((a:any,b:any)=> first(a).localeCompare(first(b)))
  card.items = card.items.slice()
  specialDaysDirty = true
}
const filteredSpecialDayCards = computed(() => {
  const fs = sdFilterStart.value, fe = sdFilterEnd.value || sdFilterStart.value
  if (!fs && !fe) return specialDayCards.value
  const inter=(aS:string,aE:string,bS:string,bE:string)=> (aS<=bE)&&(bS<=aE)
  return specialDayCards.value.filter(c => { const ds=c.dateStart||''; const de=c.dateEnd||ds; if(!ds) return true; return inter(fs,fe,ds,de) })
})
function addSpecialDayCard(){ const id=nextCardId(); openModeById[id]='new'; specialDayCards.value.unshift({ id, dateStart:null, dateEnd:null, items:[] }); nextTick(()=>scrollToCard(id)) }

/* =======================================================================================
 * WATCHERS – Hydration
 * ======================================================================================= */
function assignFromVM() {
  if (!vm.value) { form.value = { ...emptyEmployee }; workingDays.value=[]; daysOff.value=[]; specialDayCards.value=[]; serviceGroups.value=[]; calendars.value=[]; return }
  form.value = vm.value.form as Employee
  workingDays.value = vm.value.workingDays
  daysOff.value = vm.value.daysOff
  specialDayCards.value = vm.value.specialDayCards
  serviceGroups.value = vm.value.serviceGroups || []
  calendars.value = (vm.value.calendars || []) as any
  // reset dirty
  workingDaysDirty = false; daysOffDirty = false; specialDaysDirty = false; servicesDirty = false
  _formBaselineJson.value = JSON.stringify(form.value); formDirty.value = false
}
watch(
  () => props.modelValue,
  async (val) => {
    error.value = ''
    if (!val) { vm.value = null; assignFromVM(); return }
    // 1) normalize list entry
    vm.value = normalizeEmployeeFromApi(val)
    assignFromVM()

    // 2) load details if needed
    const hasSets   = Array.isArray((val as any)?.workday_sets) || Array.isArray((val as any)?.working_hours)
    const hasOff    = Array.isArray((val as any)?.days_off)
    const hasSpec   = Array.isArray((val as any)?.special_day_sets) || Array.isArray((val as any)?.special_days)
    if (val.id && !(hasSets && hasOff && hasSpec)) {
      try {
        const full = await apiGetEmployee(Number(val.id))
        vm.value = normalizeEmployeeFromApi({ ...val, ...full })
        assignFromVM()
      } catch (_e:any) { error.value = _e?.message || (t('mod.employees.errors.load_failed') as string) }
    }
  },
  { immediate: true, deep: true }
)

/* Stammdaten-Dirty */
watch(form, (nv) => { try { formDirty.value = JSON.stringify(nv) !== _formBaselineJson.value } catch {} }, { deep: true })

/* =======================================================================================
 * LIFECYCLE (scroll lock)
 * ======================================================================================= */
onMounted(() => {
  resetSdFilter()
  const scrollY = window.scrollY
  document.body.style.top = `-${scrollY}px`
  document.body.style.position = 'fixed'
  document.body.style.width = '100%'
  document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
  const scrollY = Math.abs(parseInt(document.body.style.top || '0', 10))
  document.body.style.position = ''
  document.body.style.top = ''
  document.body.style.overflow = ''
  document.body.style.width = ''
  window.scrollTo(0, scrollY)
})

/* =======================================================================================
 * VALIDATION + SUBMIT/CANCEL
 * ======================================================================================= */
function validate(): boolean {
  error.value = ''
  if (!form.value.first_name || !form.value.last_name) { error.value = t('mod.employees.validation.name_required'); return false }
  if (form.value.email && !/.+@.+\..+/.test(form.value.email)) { error.value = t('mod.employees.validation.email_invalid'); return false }
  return true
}

async function onSubmit() {
  if (!validate()) return
  if (pendingHardDelete.value) { confirmHardDelete.value = true; return }

  // DaysOff → store shape
  const daysOffVm = daysOff.value.map(d => ({
    id: d.id, title: d.title, note: d.note,
    start: d.dateStart, end: d.dateEnd || d.dateStart, repeatYearly: d.repeatYearly,
  }))

  const vmOut: EmployeeFormVM = {
    form: { ...(form.value as Employee) },
    workingDays: JSON.parse(JSON.stringify(workingDays.value)),
    workingDaysDirty,
    daysOff: daysOffVm,
    daysOffDirty,
    specialDays: serializeSpecialDaysFromCards(specialDayCards.value) as any,
    specialDaysDirty,
    calendars: JSON.parse(JSON.stringify(calendars.value)),
    formDirty: formDirty.value,
    serviceGroups: JSON.parse(JSON.stringify(serviceGroups.value)),
    servicesDirty,
  }

  emit('save', vmOut)
}
function onCancel() { emit('cancel') }

/* =======================================================================================
 * HARD DELETE
 * ======================================================================================= */
async function doHardDelete() {
  confirmHardDelete.value = false
  try {
    if (!form.value.id) throw new Error('No ID')
    await apiDeleteEmployee(Number(form.value.id), { hard: true })
    notify('success', t('mod.employees.messages.delete_success'))
    emit('cancel')
  } catch (_e:any) {
    const msg = _e?.message || (t('mod.employees.messages.delete_error') as string)
    notify('danger', msg)
  }
}
</script>
