<template>
  <div
    v-if="state"
    class="time-tracking"
  >
    <div class="tt-header">
      <div>
        <h2>{{ t('mod.tools.timeTracking.title') }}</h2>
        <p>{{ t('mod.tools.timeTracking.subtitle') }}</p>
      </div>
      <div class="tt-actions">
        <label>
          {{ t('mod.tools.timeTracking.employeeId') }}
          <input
            v-model="timerForm.employeeId"
            type="text"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.employeeName') }}
          <input
            v-model="timerForm.employeeName"
            type="text"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.role') }}
          <input
            v-model="timerForm.role"
            type="text"
          >
        </label>
        <button
          class="btn primary"
          @click="startTimer"
        >
          {{ t('mod.tools.timeTracking.startTimer') }}
        </button>
      </div>
    </div>

    <div class="tt-stats">
      <div class="stat">
        <span>{{ t('mod.tools.timeTracking.hoursWeek') }}</span>
        <strong>{{ state.summary?.hours_week || 0 }}</strong>
      </div>
      <div class="stat">
        <span>{{ t('mod.tools.timeTracking.overtime') }}</span>
        <strong>{{ state.summary?.overtime_hours || 0 }}</strong>
      </div>
      <div class="stat">
        <span>{{ t('mod.tools.timeTracking.activeTimers') }}</span>
        <strong>{{ state.summary?.active_timers || 0 }}</strong>
      </div>
    </div>

    <section class="tt-card">
      <h3>{{ t('mod.tools.timeTracking.activeTimersTitle') }}</h3>
      <div
        v-if="state.running?.length"
        class="timer-list"
      >
        <div
          v-for="timer in state.running"
          :key="timer.employee_id"
          class="timer-row"
        >
          <div>
            <strong>{{ timer.employee_name || timer.employee_id }}</strong>
            <small>{{ formatDate(timer.started_at) }}</small>
          </div>
          <button
            class="btn"
            @click="stopTimer(timer.employee_id)"
          >
            {{ t('mod.tools.timeTracking.stopTimer') }}
          </button>
        </div>
      </div>
      <p
        v-else
        class="muted"
      >
        {{ t('mod.tools.timeTracking.noTimers') }}
      </p>
    </section>

    <section class="tt-card">
      <div class="card-header">
        <h3>{{ t('mod.tools.timeTracking.manualEntry') }}</h3>
        <button
          class="btn ghost"
          @click="saveManualEntry"
        >
          {{ t('mod.tools.save') }}
        </button>
      </div>
      <div class="form-grid">
        <label>
          {{ t('mod.tools.timeTracking.employeeId') }}
          <input
            v-model="entryForm.employeeId"
            type="text"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.employeeName') }}
          <input
            v-model="entryForm.employeeName"
            type="text"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.role') }}
          <input
            v-model="entryForm.role"
            type="text"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.date') }}
          <input
            v-model="entryForm.date"
            type="date"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.start') }}
          <input
            v-model="entryForm.startTime"
            type="time"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.end') }}
          <input
            v-model="entryForm.endTime"
            type="time"
          >
        </label>
        <label class="full">
          {{ t('mod.tools.notes') }}
          <textarea
            v-model="entryForm.notes"
            rows="2"
          />
        </label>
      </div>
    </section>

    <section class="tt-card">
      <div class="card-header">
        <h3>{{ t('mod.tools.timeTracking.rulesTitle') }}</h3>
        <button
          class="btn ghost"
          @click="saveRules"
        >
          {{ t('mod.tools.save') }}
        </button>
      </div>
      <div class="rules-grid">
        <label>
          {{ t('mod.tools.timeTracking.rounding') }}
          <input
            v-model.number="rulesForm.rounding"
            type="number"
            min="1"
          >
        </label>
        <label>
          {{ t('mod.tools.timeTracking.overtimeThreshold') }}
          <input
            v-model.number="rulesForm.overtime_threshold"
            type="number"
            min="1"
          >
        </label>
        <label class="checkbox">
          <input
            v-model="rulesForm.allow_manual"
            type="checkbox"
          >
          {{ t('mod.tools.timeTracking.allowManual') }}
        </label>
      </div>
    </section>

    <section class="tt-card">
      <h3>{{ t('mod.tools.timeTracking.entriesTitle') }}</h3>
      <table class="tt-table">
        <thead>
          <tr>
            <th>{{ t('mod.tools.timeTracking.employeeName') }}</th>
            <th>{{ t('mod.tools.timeTracking.role') }}</th>
            <th>{{ t('mod.tools.timeTracking.start') }}</th>
            <th>{{ t('mod.tools.timeTracking.end') }}</th>
            <th>{{ t('mod.tools.timeTracking.duration') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="entry in state.entries || []"
            :key="entry.id"
          >
            <td>{{ entry.employee_name || entry.employee_id }}</td>
            <td>{{ entry.role }}</td>
            <td>{{ formatDate(entry.clock_in) }}</td>
            <td>{{ formatDate(entry.clock_out) }}</td>
            <td>{{ entry.hours }} h</td>
          </tr>
        </tbody>
      </table>
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
import { reactive, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { extractRestData, isRestSuccess } from '../../utils/api'

interface TimeTrackingState {
  entries: any[]
  running: any[]
  summary: Record<string, any>
  rules: Record<string, any>
}

const { t } = useI18n()
const state = ref<TimeTrackingState | null>(null)

const timerForm = reactive({
  employeeId: '',
  employeeName: '',
  role: 'trainer'
})

const entryForm = reactive({
  employeeId: '',
  employeeName: '',
  role: 'trainer',
  date: new Date().toISOString().split('T')[0],
  startTime: '08:00',
  endTime: '10:00',
  notes: ''
})

const rulesForm = reactive({
  rounding: 5,
  overtime_threshold: 8,
  allow_manual: true
})

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
    const response = await fetch(`${rest.baseUrl}/time-tracking`, {
      headers: { 'X-WP-Nonce': rest.nonce }
    })
    const result = await response.json()
    const payload = extractRestData<any>(result)
    if (payload) {
      applyState(payload)
    }
  } catch (error) {
    console.error('Failed to load time tracking', error)
  }
}

function applyState(data: any) {
  if (!data) {
    return
  }
  state.value = data
  rulesForm.rounding = data.rules?.rounding || 5
  rulesForm.overtime_threshold = data.rules?.overtime_threshold || 8
  rulesForm.allow_manual = data.rules?.allow_manual ?? true
}

async function startTimer() {
  if (!timerForm.employeeId) {
    return
  }
  await submit(`${rest.baseUrl}/time-tracking/clock-in`, {
    employee_id: timerForm.employeeId,
    employee_name: timerForm.employeeName,
    role: timerForm.role
  })
}

async function stopTimer(employeeId: string) {
  await submit(`${rest.baseUrl}/time-tracking/clock-out`, { employee_id: employeeId })
}

async function saveManualEntry() {
  if (!entryForm.employeeId) {
    return
  }
  await submit(`${rest.baseUrl}/time-tracking/entries`, {
    employee_id: entryForm.employeeId,
    employee_name: entryForm.employeeName,
    role: entryForm.role,
    date: entryForm.date,
    start_time: entryForm.startTime,
    end_time: entryForm.endTime,
    notes: entryForm.notes
  })
}

async function saveRules() {
  await submit(`${rest.baseUrl}/time-tracking/rules`, rulesForm)
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
    if (!isRestSuccess(result)) {
      return
    }
    const payload = extractRestData<any>(result)
    if (payload?.state) {
      applyState(payload.state)
    } else if (payload) {
      applyState(payload)
    }
  } catch (error) {
    console.error('Time tracking request failed', error)
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
.time-tracking {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-lg);
}

.tt-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: var(--bookando-space-lg);
}

.tt-actions {
  display: flex;
  gap: var(--bookando-space-md);
  flex-wrap: wrap;
}

.tt-actions label {
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

.tt-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: var(--bookando-space-md);
}

.stat {
  background: var(--bookando-surface);
  padding: var(--bookando-space-md);
  border-radius: var(--bookando-radius);
}

.tt-card {
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

.tt-table {
  width: 100%;
  border-collapse: collapse;
}

.tt-table th,
.tt-table td {
  padding: 0.5rem;
  border-bottom: 1px solid var(--bookando-border);
  text-align: left;
}

.timer-list {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-sm);
}

.timer-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--bookando-space-sm);
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius);
}

.muted {
  color: var(--bookando-text-muted);
  font-size: 0.9rem;
}
</style>
