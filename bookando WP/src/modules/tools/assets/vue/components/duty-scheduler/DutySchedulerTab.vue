<template>
  <div
    v-if="state"
    class="duty-scheduler"
  >
    <div class="ds-header">
      <div>
        <h2>{{ t('mod.tools.dutyScheduling.title') }}</h2>
        <p>{{ t('mod.tools.dutyScheduling.subtitle') }}</p>
      </div>
      <div class="ds-period">
        <label>
          {{ t('mod.tools.dutyScheduling.periodStart') }}
          <input
            v-model="rosterPeriod.start"
            type="date"
          >
        </label>
        <label>
          {{ t('mod.tools.dutyScheduling.periodEnd') }}
          <input
            v-model="rosterPeriod.end"
            type="date"
          >
        </label>
        <button
          class="btn primary"
          @click="generateRoster"
        >
          {{ t('mod.tools.dutyScheduling.generate') }}
        </button>
      </div>
    </div>

    <div class="ds-grid">
      <section class="ds-card">
        <div class="card-header">
          <h3>{{ t('mod.tools.dutyScheduling.templates') }}</h3>
          <button
            class="btn ghost"
            @click="saveTemplate"
          >
            {{ t('mod.tools.save') }}
          </button>
        </div>
        <div class="form-grid">
          <label>
            {{ t('mod.tools.dutyScheduling.templateName') }}
            <input
              v-model="templateForm.label"
              type="text"
            >
          </label>
          <label>
            {{ t('mod.tools.dutyScheduling.start') }}
            <input
              v-model="templateForm.start"
              type="time"
            >
          </label>
          <label>
            {{ t('mod.tools.dutyScheduling.end') }}
            <input
              v-model="templateForm.end"
              type="time"
            >
          </label>
          <label class="full">
            {{ t('mod.tools.dutyScheduling.days') }}
            <select
              v-model="templateForm.days"
              multiple
            >
              <option
                v-for="day in daysOfWeek"
                :key="day.value"
                :value="day.value"
              >
                {{ day.label }}
              </option>
            </select>
          </label>
        </div>
        <div class="roles-list">
          <div
            v-for="role in templateForm.roles"
            :key="role.id"
            class="role-row"
          >
            <input
              v-model="role.role"
              type="text"
              placeholder="role"
            >
            <input
              v-model.number="role.required"
              type="number"
              min="1"
            >
            <button
              class="icon"
              @click="removeRole(role.id)"
            >
              ×
            </button>
          </div>
          <button
            class="btn ghost"
            @click="addRole"
          >
            {{ t('mod.tools.dutyScheduling.addRole') }}
          </button>
        </div>
        <div class="list">
          <h4>{{ t('mod.tools.dutyScheduling.existingTemplates') }}</h4>
          <ul>
            <li
              v-for="template in templates"
              :key="template.id"
              @click="loadTemplate(template)"
            >
              <strong>{{ template.label }}</strong>
              <small>{{ template.days.join(', ') }} · {{ template.start }} - {{ template.end }}</small>
            </li>
          </ul>
        </div>
      </section>

      <section class="ds-card">
        <div class="card-header">
          <h3>{{ t('mod.tools.dutyScheduling.availability') }}</h3>
          <button
            class="btn ghost"
            @click="saveAvailability"
          >
            {{ t('mod.tools.save') }}
          </button>
        </div>
        <div class="form-grid">
          <label>
            {{ t('mod.tools.dutyScheduling.employeeId') }}
            <input
              v-model="availabilityForm.employeeId"
              type="text"
            >
          </label>
          <label>
            {{ t('mod.tools.dutyScheduling.employeeName') }}
            <input
              v-model="availabilityForm.name"
              type="text"
            >
          </label>
          <label>
            {{ t('mod.tools.dutyScheduling.roles') }}
            <input
              v-model="availabilityForm.rolesText"
              type="text"
              placeholder="trainer,host"
            >
          </label>
          <label>
            {{ t('mod.tools.dutyScheduling.weeklyCapacity') }}
            <input
              v-model.number="availabilityForm.weeklyCapacity"
              type="number"
              min="8"
            >
          </label>
          <label>
            {{ t('mod.tools.dutyScheduling.unavailableDays') }}
            <select
              v-model="availabilityForm.unavailableDays"
              multiple
            >
              <option
                v-for="day in daysOfWeek"
                :key="day.value"
                :value="day.value"
              >
                {{ day.label }}
              </option>
            </select>
          </label>
          <label>
            {{ t('mod.tools.dutyScheduling.preferredShifts') }}
            <input
              v-model="availabilityForm.preferredText"
              type="text"
              placeholder="shift_morning"
            >
          </label>
        </div>
        <div class="list">
          <h4>{{ t('mod.tools.dutyScheduling.currentAvailability') }}</h4>
          <ul>
            <li
              v-for="employee in availabilityList"
              :key="employee.employee_id"
            >
              <strong>{{ employee.name || employee.employee_id }}</strong>
              <small>{{ employee.roles.join(', ') }}</small>
            </li>
          </ul>
        </div>
      </section>
    </div>

    <section class="ds-card">
      <div class="card-header">
        <h3>{{ t('mod.tools.dutyScheduling.constraints') }}</h3>
        <button
          class="btn ghost"
          @click="saveConstraints"
        >
          {{ t('mod.tools.save') }}
        </button>
      </div>
      <div class="rules-grid">
        <label>
          {{ t('mod.tools.dutyScheduling.maxHours') }}
          <input
            v-model.number="constraintForm.max_hours_per_week"
            type="number"
            min="8"
          >
        </label>
        <label>
          {{ t('mod.tools.dutyScheduling.minRest') }}
          <input
            v-model.number="constraintForm.min_rest_hours"
            type="number"
            min="1"
          >
        </label>
        <label class="checkbox">
          <input
            v-model="constraintForm.allow_overtime"
            type="checkbox"
          >
          {{ t('mod.tools.dutyScheduling.allowOvertime') }}
        </label>
      </div>
    </section>

    <section class="ds-card">
      <div class="card-header">
        <h3>{{ t('mod.tools.dutyScheduling.rosterTitle') }}</h3>
        <span
          v-if="state.roster?.generated_at"
          class="muted"
        >
          {{ t('mod.tools.dutyScheduling.generatedAt') }}
          {{ formatDate(state.roster.generated_at) }}
        </span>
      </div>
      <table
        v-if="state.roster?.assignments?.length"
        class="ds-table"
      >
        <thead>
          <tr>
            <th>{{ t('mod.tools.dutyScheduling.date') }}</th>
            <th>{{ t('mod.tools.dutyScheduling.shift') }}</th>
            <th>{{ t('mod.tools.dutyScheduling.role') }}</th>
            <th>{{ t('mod.tools.dutyScheduling.employee') }}</th>
            <th>{{ t('mod.tools.dutyScheduling.status') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="assignment in state.roster.assignments"
            :key="assignment.date + assignment.shift_id + assignment.role"
          >
            <td>{{ formatDate(assignment.start_iso) }}</td>
            <td>{{ assignment.shift_label }}</td>
            <td>{{ assignment.role }}</td>
            <td>{{ assignment.employee_name || t('mod.tools.dutyScheduling.openSlot') }}</td>
            <td>
              <span :class="['badge', assignment.status]">
                {{ assignment.status === 'assigned' ? t('mod.tools.dutyScheduling.assigned') : t('mod.tools.dutyScheduling.open') }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
      <p
        v-else
        class="muted"
      >
        {{ t('mod.tools.dutyScheduling.noRoster') }}
      </p>
    </section>
  </div>
  <p
    v-else
    class="muted"
  >
    {{ t('mod.tools.loading') }}
  </p>
</template>

<script setup lang="ts">
import { computed, reactive, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { extractRestData } from '../../utils/api'

interface DutySchedulerState {
  templates: any[]
  availability: Record<string, any>
  constraints: Record<string, any>
  roster: Record<string, any>
}

const { t } = useI18n()
const state = ref<DutySchedulerState | null>(null)

const templateForm = reactive({
  id: '',
  label: '',
  start: '08:00',
  end: '16:00',
  days: ['monday'],
  roles: [{ id: 1, role: 'trainer', required: 1 }]
})
let roleCounter = 2

const availabilityForm = reactive({
  employeeId: '',
  name: '',
  rolesText: 'trainer',
  weeklyCapacity: 32,
  unavailableDays: [] as string[],
  preferredText: ''
})

const constraintForm = reactive({
  max_hours_per_week: 40,
  min_rest_hours: 11,
  allow_overtime: false
})

const rosterPeriod = reactive({
  start: new Date().toISOString().split('T')[0],
  end: new Date(Date.now() + 6 * 86400000).toISOString().split('T')[0]
})

const daysOfWeek = [
  { value: 'monday', label: t('mod.tools.weekdays.mon') },
  { value: 'tuesday', label: t('mod.tools.weekdays.tue') },
  { value: 'wednesday', label: t('mod.tools.weekdays.wed') },
  { value: 'thursday', label: t('mod.tools.weekdays.thu') },
  { value: 'friday', label: t('mod.tools.weekdays.fri') },
  { value: 'saturday', label: t('mod.tools.weekdays.sat') },
  { value: 'sunday', label: t('mod.tools.weekdays.sun') }
]

const templates = computed(() => state.value?.templates || [])
const availabilityList = computed(() => Object.values(state.value?.availability || {}))

function getRestConfig() {
  const vars = (window as any).BOOKANDO_VARS || {}
  const restBase = vars.rest_url || '/wp-json/bookando/v1'
  return {
    baseUrl: `${restBase}/tools`,
    nonce: vars.nonce || ''
  }
}

const rest = getRestConfig()

async function loadState() {
  try {
    const response = await fetch(`${rest.baseUrl}/duty-scheduling`, {
      headers: { 'X-WP-Nonce': rest.nonce }
    })
    const result = await response.json()
    const responsePayload = extractRestData(result)
    if (responsePayload) {
      applyState(responsePayload)
    }
  } catch (error) {
    console.error('Failed to load duty scheduler', error)
  }
}

function applyState(data: any) {
  if (!data) {
    return
  }
  state.value = data
  constraintForm.max_hours_per_week = data.constraints?.max_hours_per_week || 40
  constraintForm.min_rest_hours = data.constraints?.min_rest_hours || 11
  constraintForm.allow_overtime = data.constraints?.allow_overtime ?? false
}

async function saveTemplate() {
  await submit(`${rest.baseUrl}/duty-scheduling/templates`, {
    id: templateForm.id || undefined,
    label: templateForm.label,
    start: templateForm.start,
    end: templateForm.end,
    days: templateForm.days,
    roles: templateForm.roles.map(role => ({ role: role.role, required: role.required }))
  })
  templateForm.id = ''
  templateForm.label = ''
}

function loadTemplate(template: any) {
  templateForm.id = template.id
  templateForm.label = template.label
  templateForm.start = template.start
  templateForm.end = template.end
  templateForm.days = [...template.days]
  templateForm.roles = template.roles.map((role: any, index: number) => ({
    id: index + 1,
    role: role.role,
    required: role.required
  }))
}

function addRole() {
  templateForm.roles.push({ id: roleCounter++, role: 'trainer', required: 1 })
}

function removeRole(id: number) {
  templateForm.roles = templateForm.roles.filter(role => role.id !== id)
  if (!templateForm.roles.length) {
    addRole()
  }
}

async function saveAvailability() {
  await submit(`${rest.baseUrl}/duty-scheduling/availability`, {
    employee_id: availabilityForm.employeeId,
    name: availabilityForm.name,
    roles: availabilityForm.rolesText.split(',').map(value => value.trim()).filter(Boolean),
    weekly_capacity: availabilityForm.weeklyCapacity,
    unavailable_days: availabilityForm.unavailableDays,
    preferred_shifts: availabilityForm.preferredText.split(',').map(value => value.trim()).filter(Boolean)
  })
}

async function saveConstraints() {
  await submit(`${rest.baseUrl}/duty-scheduling/constraints`, constraintForm)
}

async function generateRoster() {
  await submit(`${rest.baseUrl}/duty-scheduling/generate`, {
    period_start: rosterPeriod.start,
    period_end: rosterPeriod.end
  })
}

async function submit(url: string, payload: Record<string, any>) {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': rest.nonce
      },
      body: JSON.stringify(payload)
    })
    const result = await response.json()
    const payload = extractRestData(result)
    if (payload) {
      applyState(payload)
    }
  } catch (error) {
    console.error('Duty scheduling request failed', error)
  }
}

function formatDate(value?: string) {
  if (!value) {
    return ''
  }
  try {
    return new Date(value).toLocaleString()
  } catch (_error) {
    return value
  }
}

onMounted(loadState)
</script>

<style scoped>
.duty-scheduler {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-lg);
}

.ds-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: var(--bookando-space-lg);
}

.ds-period {
  display: flex;
  gap: var(--bookando-space-md);
  align-items: flex-end;
}

.ds-period label {
  display: flex;
  flex-direction: column;
  font-size: 0.875rem;
}

.btn {
  border: 1px solid var(--bookando-border);
  background: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
}

.btn.primary {
  background: var(--bookando-primary);
  color: #fff;
  border-color: var(--bookando-primary);
}

.btn.ghost {
  background: transparent;
}

.ds-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: var(--bookando-space-lg);
}

.ds-card {
  background: #fff;
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius-lg);
  padding: var(--bookando-space-lg);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--bookando-space-md);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--bookando-space-md);
}

.form-grid label {
  display: flex;
  flex-direction: column;
  font-size: 0.875rem;
}

.form-grid .full {
  grid-column: 1 / -1;
}

.roles-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: var(--bookando-space-md);
}

.role-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.role-row input {
  flex: 1;
}

.role-row .icon {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
}

.list ul {
  margin: 0;
  padding-left: 1rem;
  max-height: 200px;
  overflow: auto;
}

.rules-grid {
  display: flex;
  gap: var(--bookando-space-md);
  flex-wrap: wrap;
}

.rules-grid label {
  display: flex;
  flex-direction: column;
}

.rules-grid .checkbox {
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
}

.ds-table {
  width: 100%;
  border-collapse: collapse;
}

.ds-table th,
.ds-table td {
  padding: 0.5rem;
  border-bottom: 1px solid var(--bookando-border);
  text-align: left;
}

.badge {
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
  font-size: 0.75rem;
}

.badge.assigned {
  background: var(--bookando-success-soft);
  color: var(--bookando-success);
}

.badge.open {
  background: var(--bookando-warning-soft);
  color: var(--bookando-warning);
}

.muted {
  color: var(--bookando-text-muted);
  font-size: 0.875rem;
}
</style>
