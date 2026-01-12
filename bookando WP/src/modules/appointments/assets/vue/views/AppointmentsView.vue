<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <template #header>
          <AppPageHeader
            :title="t('mod.appointments.title')"
            hide-brand-below="md"
          >
            <template #actions>
              <div class="bookando-appointments-actions">
                <AppButton
                  variant="primary"
                  @click="openCreate"
                >
                  {{ t('mod.appointments.actions.new') }}
                </AppButton>
                <AppButton
                  variant="secondary"
                  @click="openAssign"
                >
                  {{ t('mod.appointments.actions.assign') }}
                </AppButton>
                <AppButton
                  variant="ghost"
                  :loading="loading"
                  @click="loadTimeline"
                >
                  {{ t('mod.appointments.actions.refresh') }}
                </AppButton>
              </div>
            </template>
          </AppPageHeader>
        </template>

        <div class="bookando-card">
          <div class="bookando-card-body">
            <div
              v-if="timelineError"
              class="bookando-alert bookando-alert--danger bookando-mb-md"
              role="alert"
            >
              {{ timelineError }}
            </div>

            <div
              v-if="loading"
              class="bookando-appointments-empty"
            >
              <span class="bookando-loader" />
            </div>
            <div
              v-else-if="!timeline.length"
              class="bookando-appointments-empty"
            >
              {{ t('mod.appointments.timeline.empty') }}
            </div>
            <div
              v-else
              class="bookando-appointments-timeline"
            >
              <div
                v-for="group in timeline"
                :key="group.date"
                class="bookando-appointments-group"
              >
                <div class="bookando-appointments-group__header">
                  <span>{{ group.label }}</span>
                  <span class="bookando-text-muted">{{ summarizeGroup(group) }}</span>
                </div>
                <div class="bookando-appointments-group__list">
                  <div
                    v-for="item in group.items"
                    :key="itemKey(item)"
                    class="bookando-appointments-item"
                  >
                    <div class="bookando-appointments-item__time">
                      {{ formatTimeRange(item) }}
                    </div>
                    <div class="bookando-appointments-item__meta">
                      <template v-if="item.type === 'appointment'">
                        <strong>{{ item.service?.name || t('mod.appointments.labels.unknown_service') }}</strong>
                        <span>{{ item.customer?.name || t('mod.appointments.labels.unknown_customer') }}</span>
                        <span class="bookando-text-muted">
                          {{ statusLabel(item.status) }}
                          <template v-if="item.event?.name">
                            · {{ item.event?.name }}
                          </template>
                        </span>
                      </template>
                      <template v-else>
                        <strong>{{ item.name }}</strong>
                        <span class="bookando-text-muted">
                          {{ t('mod.appointments.labels.event_type', { type: item.event_type || '–' }) }}
                        </span>
                        <span class="bookando-text-muted">
                          {{ participantsLabel(item.participants) }}
                          <template v-if="item.capacity">
                            · {{ capacityLabel(item.participants, item.capacity) }}
                          </template>
                        </span>
                      </template>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </AppPageLayout>
    </div>

    <transition name="fade">
      <div
        v-if="showCreateModal"
        class="bookando-dialog-wrapper active"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="bookando-form-overlay active"
          tabindex="-1"
          @click="closeCreate"
        />

        <AppForm>
          <template #header>
            <h2>{{ t('mod.appointments.forms.appointment.title') }}</h2>
            <AppButton
              icon="x"
              btn-type="icononly"
              variant="standard"
              size="square"
              icon-size="md"
              @click="closeCreate"
            />
          </template>

          <template #default>
            <form
              class="bookando-form"
              novalidate
              autocomplete="off"
              @submit.prevent="submitCreate"
            >
              <div class="bookando-appointments-modal-grid">
                <BookandoField
                  id="appointment_customer"
                  v-model.number="createForm.customer_id"
                  type="dropdown"
                  :label="t('mod.appointments.forms.appointment.customer')"
                  :options="customerOptions"
                  option-label="label"
                  option-value="value"
                  searchable
                  required
                />
                <BookandoField
                  id="appointment_service"
                  v-model.number="createForm.service_id"
                  type="dropdown"
                  :label="t('mod.appointments.forms.appointment.service')"
                  :options="serviceOptions"
                  option-label="label"
                  option-value="value"
                  searchable
                  required
                />
                <BookandoField
                  id="appointment_status"
                  v-model="createForm.status"
                  type="dropdown"
                  :label="t('mod.appointments.forms.appointment.status')"
                  :options="statusOptions"
                  option-label="label"
                  option-value="value"
                />
                <BookandoField
                  id="appointment_persons"
                  v-model.number="createForm.persons"
                  type="number"
                  min="1"
                  :label="t('mod.appointments.forms.appointment.persons')"
                  :description="t('mod.appointments.forms.appointment.persons_hint')"
                />
              </div>

              <div class="bookando-appointments-modal-grid">
                <BookandoField
                  id="appointment_start"
                  :label="t('mod.appointments.forms.appointment.starts_at')"
                >
                  <AppDatepicker
                    v-model="createForm.starts_at"
                    type="datetime"
                    model-type="iso"
                    :clearable="false"
                  />
                </BookandoField>
                <BookandoField
                  id="appointment_end"
                  :label="t('mod.appointments.forms.appointment.ends_at')"
                >
                  <AppDatepicker
                    v-model="createForm.ends_at"
                    type="datetime"
                    model-type="iso"
                  />
                </BookandoField>
              </div>

              <BookandoField
                id="appointment_note"
                v-model="createForm.note"
                type="textarea"
                :label="t('mod.appointments.forms.appointment.note')"
                class="bookando-appointments-form-note"
                :rows="3"
              />

              <div
                v-if="createError"
                class="bookando-alert bookando-alert--danger"
                role="alert"
              >
                {{ createError }}
              </div>
            </form>
          </template>

          <template #footer>
            <AppButton
              variant="secondary"
              type="button"
              @click="closeCreate"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
            <AppButton
              variant="primary"
              type="button"
              :loading="savingCreate"
              @click="submitCreate"
            >
              {{ t('mod.appointments.forms.appointment.submit') }}
            </AppButton>
          </template>
        </AppForm>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="showAssignModal"
        class="bookando-dialog-wrapper active"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="bookando-form-overlay active"
          tabindex="-1"
          @click="closeAssign"
        />

        <AppForm>
          <template #header>
            <h2>{{ t('mod.appointments.forms.assign.title') }}</h2>
            <AppButton
              icon="x"
              btn-type="icononly"
              variant="standard"
              size="square"
              icon-size="md"
              @click="closeAssign"
            />
          </template>

          <template #default>
            <form
              class="bookando-form"
              novalidate
              autocomplete="off"
              @submit.prevent="submitAssign"
            >
              <div class="bookando-appointments-modal-grid">
                <BookandoField
                  id="assign_event"
                  v-model.number="assignForm.event_id"
                  type="dropdown"
                  :label="t('mod.appointments.forms.assign.event')"
                  :options="eventOptions"
                  option-label="label"
                  option-value="value"
                  searchable
                  required
                />
                <BookandoField
                  v-if="periodOptions.length"
                  id="assign_period"
                  v-model.number="assignForm.period_id"
                  type="dropdown"
                  :label="t('mod.appointments.forms.assign.period')"
                  :options="periodOptions"
                  option-label="label"
                  option-value="value"
                  clearable
                />
                <BookandoField
                  id="assign_customer"
                  v-model.number="assignForm.customer_id"
                  type="dropdown"
                  :label="t('mod.appointments.forms.assign.customer')"
                  :options="customerOptions"
                  option-label="label"
                  option-value="value"
                  searchable
                  required
                />
                <BookandoField
                  id="assign_service"
                  v-model.number="assignForm.service_id"
                  type="dropdown"
                  :label="t('mod.appointments.forms.assign.service')"
                  :options="serviceOptions"
                  option-label="label"
                  option-value="value"
                  searchable
                  clearable
                />
                <BookandoField
                  id="assign_status"
                  v-model="assignForm.status"
                  type="dropdown"
                  :label="t('mod.appointments.forms.assign.status')"
                  :options="statusOptions"
                  option-label="label"
                  option-value="value"
                />
              </div>

              <div
                v-if="!periodOptions.length"
                class="bookando-appointments-modal-grid"
              >
                <BookandoField
                  id="assign_start"
                  :label="t('mod.appointments.forms.assign.starts_at')"
                >
                  <AppDatepicker
                    v-model="assignForm.starts_at"
                    type="datetime"
                    model-type="iso"
                    :clearable="false"
                  />
                </BookandoField>
                <BookandoField
                  id="assign_end"
                  :label="t('mod.appointments.forms.assign.ends_at')"
                >
                  <AppDatepicker
                    v-model="assignForm.ends_at"
                    type="datetime"
                    model-type="iso"
                  />
                </BookandoField>
              </div>

              <div
                v-if="assignError"
                class="bookando-alert bookando-alert--danger"
                role="alert"
              >
                {{ assignError }}
              </div>
            </form>
          </template>

          <template #footer>
            <AppButton
              variant="secondary"
              type="button"
              @click="closeAssign"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
            <AppButton
              variant="primary"
              type="button"
              :loading="savingAssign"
              @click="submitAssign"
            >
              {{ t('mod.appointments.forms.assign.submit') }}
            </AppButton>
          </template>
        </AppForm>
      </div>
    </transition>
  </AppShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import dayjs from 'dayjs'

import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppForm from '@core/Design/components/AppForm.vue'
import AppDatepicker from '@core/Design/components/AppDatepicker.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

import { notify } from '@core/Composables/useNotifier'

import {
  fetchTimeline,
  fetchLookups,
  createAppointment,
  assignCustomerToEvent
} from '../api/AppointmentsApi'
import type {
  TimelineGroup,
  TimelineItem,
  AppointmentStatus,
  LookupResponse,
  LookupCustomer,
  LookupService,
  LookupEvent
} from '../models/timeline'

const { t, tc } = useI18n()

const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

const loading = ref(false)
const timeline = ref<TimelineGroup[]>([])
const timelineError = ref<string | null>(null)

const lookups = ref<LookupResponse>({ customers: [], services: [], events: [] })

const showCreateModal = ref(false)
const savingCreate = ref(false)
const createError = ref<string | null>(null)
const createForm = reactive({
  customer_id: null as number | null,
  service_id: null as number | null,
  status: 'confirmed' as AppointmentStatus,
  persons: 1,
  starts_at: dayjs().second(0).millisecond(0).toISOString(),
  ends_at: dayjs().second(0).millisecond(0).add(1, 'hour').toISOString(),
  note: ''
})

const showAssignModal = ref(false)
const savingAssign = ref(false)
const assignError = ref<string | null>(null)
const assignForm = reactive({
  event_id: null as number | null,
  period_id: null as number | null,
  customer_id: null as number | null,
  service_id: null as number | null,
  status: 'confirmed' as AppointmentStatus,
  starts_at: dayjs().second(0).millisecond(0).toISOString(),
  ends_at: dayjs().second(0).millisecond(0).add(1, 'hour').toISOString()
})

onMounted(async () => {
  await Promise.all([loadLookups(), loadTimeline()])
})

async function loadTimeline() {
  loading.value = true
  timelineError.value = null
  try {
    const data = await fetchTimeline()
    timeline.value = data
  } catch (error: any) {
    console.error('[Bookando] Failed to load timeline', error)
    timelineError.value = error?.message || 'Timeline konnte nicht geladen werden.'
  } finally {
    loading.value = false
  }
}

async function loadLookups() {
  try {
    const data = await fetchLookups({ limit: 100 })
    lookups.value = data
  } catch (error: any) {
    console.error('[Bookando] Failed to load lookups', error)
  }
}

function openCreate() {
  loadLookups()
  resetCreateForm()
  showCreateModal.value = true
}

function closeCreate() {
  showCreateModal.value = false
}

function resetCreateForm() {
  createForm.customer_id = null
  createForm.service_id = null
  createForm.status = 'confirmed'
  createForm.persons = 1
  createForm.starts_at = dayjs().second(0).millisecond(0).toISOString()
  createForm.ends_at = dayjs(createForm.starts_at).add(1, 'hour').toISOString()
  createForm.note = ''
  createError.value = null
}

function openAssign() {
  loadLookups()
  resetAssignForm()
  showAssignModal.value = true
}

function closeAssign() {
  showAssignModal.value = false
}

function resetAssignForm() {
  assignForm.event_id = null
  assignForm.period_id = null
  assignForm.customer_id = null
  assignForm.service_id = null
  assignForm.status = 'confirmed'
  assignForm.starts_at = dayjs().second(0).millisecond(0).toISOString()
  assignForm.ends_at = dayjs(assignForm.starts_at).add(1, 'hour').toISOString()
  assignError.value = null
}

const customerOptions = computed(() =>
  lookups.value.customers.map((customer: LookupCustomer) => ({
    label: customer.name,
    value: customer.id
  }))
)

const serviceOptions = computed(() =>
  lookups.value.services.map((service: LookupService) => ({
    label: service.name,
    value: service.id
  }))
)

const statusValues: AppointmentStatus[] = ['pending', 'approved', 'confirmed', 'cancelled', 'noshow']
const statusOptions = computed(() =>
  statusValues.map((value) => ({
    label: statusLabel(value),
    value
  }))
)

const eventOptions = computed(() => {
  const seen = new Map<number, string>()
  lookups.value.events.forEach((event: LookupEvent) => {
    if (!seen.has(event.event_id)) {
      seen.set(event.event_id, event.name)
    }
  })
  return Array.from(seen.entries()).map(([value, label]) => ({ label, value }))
})

const periodOptions = computed(() => {
  if (!assignForm.event_id) return []
  return lookups.value.events
    .filter((event) => event.event_id === assignForm.event_id && event.period_id)
    .map((event) => ({
      label: formatDateRange(event.start_local, event.end_local),
      value: event.period_id as number
    }))
})

watch(
  () => assignForm.event_id,
  () => {
    assignForm.period_id = null
    const periods = periodOptions.value
    if (periods.length === 1) {
      assignForm.period_id = periods[0].value
    }
    if (assignForm.period_id) {
      const period = lookups.value.events.find((event) => event.event_id === assignForm.event_id && event.period_id === assignForm.period_id)
      if (period?.start_local) {
        assignForm.starts_at = dayjs(period.start_local).toISOString()
      }
      if (period?.end_local) {
        assignForm.ends_at = dayjs(period.end_local).toISOString()
      }
    } else {
      assignForm.starts_at = dayjs().second(0).millisecond(0).toISOString()
      assignForm.ends_at = dayjs(assignForm.starts_at).add(1, 'hour').toISOString()
    }
  }
)

function statusLabel(status: AppointmentStatus): string {
  return t(`mod.appointments.status.${status}`) || status
}

function participantsLabel(count: number): string {
  return tc('mod.appointments.timeline.participants', count, { count })
}

function capacityLabel(used: number, max: number): string {
  return t('mod.appointments.timeline.capacity', { used, max })
}

function summarizeGroup(group: TimelineGroup): string {
  const appointmentCount = group.items.filter((item) => item.type === 'appointment').length
  const eventCount = group.items.length - appointmentCount
  const parts: string[] = []
  if (appointmentCount) {
    parts.push(`${appointmentCount} ${t('mod.appointments.timeline.appointment_label')}`)
  }
  if (eventCount) {
    parts.push(`${eventCount} ${t('mod.appointments.timeline.event_label')}`)
  }
  return parts.join(' · ')
}

function itemKey(item: TimelineItem): string {
  return item.type === 'appointment'
    ? `appt-${item.id}`
    : `evt-${item.event_id}-${item.period_id ?? 'na'}`
}

function formatTimeRange(item: TimelineItem): string {
  const start = item.start_local ? dayjs(item.start_local) : null
  const end = item.end_local ? dayjs(item.end_local) : null
  if (start && end) {
    return `${start.format('HH:mm')} – ${end.format('HH:mm')}`
  }
  if (start) {
    return start.format('HH:mm')
  }
  return '–'
}

function formatDateRange(start?: string | null, end?: string | null): string {
  const startDate = start ? dayjs(start) : null
  const endDate = end ? dayjs(end) : null
  if (startDate && endDate) {
    if (startDate.isSame(endDate, 'day')) {
      return `${startDate.format('DD.MM.YYYY HH:mm')} – ${endDate.format('HH:mm')}`
    }
    return `${startDate.format('DD.MM.YYYY HH:mm')} – ${endDate.format('DD.MM.YYYY HH:mm')}`
  }
  if (startDate) {
    return startDate.format('DD.MM.YYYY HH:mm')
  }
  return t('core.common.not_available') || '–'
}

async function submitCreate() {
  if (!createForm.customer_id || !createForm.service_id || !createForm.starts_at) {
    createError.value = t('mod.appointments.forms.appointment.error')
    return
  }

  savingCreate.value = true
  createError.value = null
  try {
    await createAppointment({
      customer_id: createForm.customer_id,
      service_id: createForm.service_id,
      status: createForm.status,
      persons: createForm.persons,
      starts_at: createForm.starts_at,
      ends_at: createForm.ends_at || undefined,
      meta: createForm.note ? { note: createForm.note } : undefined
    })
    notify({ type: 'success', message: t('mod.appointments.forms.appointment.success') })
    closeCreate()
    await loadTimeline()
  } catch (error: any) {
    console.error('[Bookando] Failed to create appointment', error)
    createError.value = error?.message || t('mod.appointments.forms.appointment.error')
  } finally {
    savingCreate.value = false
  }
}

async function submitAssign() {
  if (!assignForm.event_id || !assignForm.customer_id) {
    assignError.value = t('mod.appointments.forms.assign.error')
    return
  }

  savingAssign.value = true
  assignError.value = null
  try {
    await assignCustomerToEvent({
      event_id: assignForm.event_id,
      period_id: assignForm.period_id ?? undefined,
      customer_id: assignForm.customer_id,
      service_id: assignForm.service_id ?? undefined,
      status: assignForm.status,
      starts_at: assignForm.starts_at,
      ends_at: assignForm.ends_at || undefined
    })
    notify({ type: 'success', message: t('mod.appointments.forms.assign.success') })
    closeAssign()
    await loadTimeline()
  } catch (error: any) {
    console.error('[Bookando] Failed to assign customer', error)
    assignError.value = error?.message || t('mod.appointments.forms.assign.error')
  } finally {
    savingAssign.value = false
  }
}
</script>
