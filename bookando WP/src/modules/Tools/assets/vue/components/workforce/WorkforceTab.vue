<template>
  <div
    v-if="state"
    class="workforce-tab"
  >
    <div class="workforce-header">
      <div>
        <h2>{{ t('mod.tools.workforce.title') }}</h2>
        <p>{{ t('mod.tools.workforce.subtitle') }}</p>
      </div>
    </div>

    <!-- Employee Selection -->
    <section class="workforce-card">
      <h3>{{ t('mod.tools.workforce.selectEmployee') }}</h3>
      <div class="employee-selector">
        <label>
          {{ t('mod.tools.workforce.employee') }}
          <select v-model="selectedEmployeeId" :disabled="employees.length === 0">
            <option value="">{{ employees.length === 0 ? 'Keine Mitarbeiter verfügbar' : t('mod.tools.choose') }}</option>
            <option
              v-for="emp in employees"
              :key="emp.id"
              :value="emp.id"
            >
              {{ emp.name }} ({{ emp.status }})
            </option>
          </select>
        </label>
        <label>
          {{ t('mod.tools.workforce.statusFilter') }}
          <select v-model="employeeStatusFilter" @change="loadEmployees">
            <option value="active">{{ t('mod.tools.workforce.statusActive') }}</option>
            <option value="all">{{ t('mod.tools.workforce.statusAll') }}</option>
          </select>
        </label>
      </div>
    </section>

    <!-- Time Tracking Section -->
    <section class="workforce-card">
      <h3>⏰ {{ t('mod.tools.workforce.timeTracking') }}</h3>

      <!-- Active Timer Display -->
      <div v-if="currentTimer" class="active-timer">
        <div class="timer-info">
          <strong>{{ getEmployeeName(currentTimer.user_id) }}</strong>
          <span class="timer-duration">{{ formatLiveDuration(currentTimer.started_at) }}</span>
        </div>
        <button class="btn danger" :disabled="isClockingOut" @click="clockOut">
          {{ isClockingOut ? '⏳ Wird ausgestempelt...' : t('mod.tools.workforce.clockOut') }}
        </button>
      </div>

      <!-- Clock In/Out Buttons -->
      <div class="clock-actions">
        <button
          class="btn primary btn-large"
          :disabled="!selectedEmployeeId || !!currentTimer || isClockingIn"
          @click="clockIn"
        >
          {{ isClockingIn ? '⏳' : '🕐' }} {{ isClockingIn ? 'Wird eingestempelt...' : t('mod.tools.workforce.clockIn') }}
        </button>
      </div>

      <!-- Week Summary -->
      <div class="summary-stats">
        <div class="stat">
          <span>{{ t('mod.tools.workforce.thisWeek') }}</span>
          <strong>{{ state.summary?.this_week?.total_hours || 0 }} h</strong>
        </div>
        <div class="stat">
          <span>{{ t('mod.tools.workforce.thisMonth') }}</span>
          <strong>{{ state.summary?.this_month?.total_hours || 0 }} h</strong>
        </div>
        <div class="stat">
          <span>{{ t('mod.tools.workforce.activeTimers') }}</span>
          <strong>{{ state.active_timers?.length || 0 }}</strong>
        </div>
      </div>

      <!-- Recent Entries -->
      <h4>{{ t('mod.tools.workforce.recentEntries') }}</h4>
      <div v-if="state.recent_entries && state.recent_entries.length > 0">
        <table class="entries-table">
          <thead>
            <tr>
              <th>{{ t('mod.tools.workforce.employee') }}</th>
              <th>{{ t('mod.tools.workforce.clockInTime') }}</th>
              <th>{{ t('mod.tools.workforce.clockOutTime') }}</th>
              <th>{{ t('mod.tools.workforce.totalHours') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="entry in state.recent_entries" :key="entry.id">
              <td>{{ entry.employee_name }}</td>
              <td>{{ formatDateTime(entry.clock_in_at) }}</td>
              <td>{{ formatDateTime(entry.clock_out_at) }}</td>
              <td>{{ entry.total_hours }} h</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="empty-state">
        <p>📋 Noch keine Zeiteinträge vorhanden</p>
      </div>
    </section>

    <!-- Vacation Requests Section -->
    <section class="workforce-card">
      <h3>🏖️ {{ t('mod.tools.workforce.vacationRequests') }}</h3>

      <!-- New Request Form -->
      <div class="vacation-form">
        <h4>{{ t('mod.tools.workforce.newRequest') }}</h4>
        <div class="form-grid">
          <label>
            {{ t('mod.tools.workforce.startDate') }}
            <input v-model="vacationForm.start_date" type="date">
          </label>
          <label>
            {{ t('mod.tools.workforce.endDate') }}
            <input v-model="vacationForm.end_date" type="date">
          </label>
          <label class="full">
            {{ t('mod.tools.workforce.reason') }}
            <input v-model="vacationForm.name" type="text">
          </label>
          <label class="full">
            {{ t('mod.tools.notes') }}
            <textarea v-model="vacationForm.note" rows="2"></textarea>
          </label>
        </div>
        <button
          class="btn primary"
          :disabled="!selectedEmployeeId || isSubmittingVacation"
          @click="submitVacationRequest"
        >
          {{ isSubmittingVacation ? '⏳ Wird eingereicht...' : t('mod.tools.workforce.submitRequest') }}
        </button>
      </div>

      <!-- Pending Requests -->
      <div class="requests-list">
        <h4>{{ t('mod.tools.workforce.pendingRequests') }}</h4>
        <div v-if="pendingRequests.length > 0">
          <div v-for="req in pendingRequests" :key="req.id" class="request-card">
            <div class="request-info">
              <strong>{{ req.employee_name }}</strong>
              <span>{{ req.start_date }} - {{ req.end_date }} ({{ req.days_count }} {{ t('mod.tools.workforce.days') }})</span>
              <small>{{ req.name }}</small>
            </div>
            <div class="request-actions">
              <button
                class="btn success btn-sm"
                :disabled="approvingRequestId === req.id || rejectingRequestId === req.id"
                @click="approveRequest(req.id)"
              >
                {{ approvingRequestId === req.id ? '⏳' : '✓' }} {{ approvingRequestId === req.id ? 'Genehmigen...' : t('mod.tools.workforce.approve') }}
              </button>
              <button
                class="btn danger btn-sm"
                :disabled="approvingRequestId === req.id || rejectingRequestId === req.id"
                @click="rejectRequest(req.id)"
              >
                {{ rejectingRequestId === req.id ? '⏳' : '✗' }} {{ rejectingRequestId === req.id ? 'Ablehnen...' : t('mod.tools.workforce.reject') }}
              </button>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>✅ Keine offenen Urlaubsanträge</p>
        </div>
      </div>
    </section>

    <!-- Toast Notifications -->
    <div class="toast-container">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="['toast', `toast-${toast.type}`]"
      >
        {{ toast.message }}
      </div>
    </div>
  </div>
  <p v-else class="muted">
    {{ t('mod.tools.loading') }}
  </p>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { extractRestData, isRestSuccess } from '../../utils/api'

const { t } = useI18n()

const state = ref<any>(null)
const employees = ref<any[]>([])
const vacationRequests = ref<any[]>([])
const selectedEmployeeId = ref<string>('')
const employeeStatusFilter = ref<string>('active')
const currentTime = ref<Date>(new Date())
let timerInterval: number | null = null
const vacationForm = ref({
  start_date: '',
  end_date: '',
  name: '',
  note: ''
})

// Loading states
const isClockingIn = ref(false)
const isClockingOut = ref(false)
const isSubmittingVacation = ref(false)
const approvingRequestId = ref<number | null>(null)
const rejectingRequestId = ref<number | null>(null)

// Toast notification system
interface Toast {
  id: number
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
}
const toasts = ref<Toast[]>([])
let toastIdCounter = 0

function showToast(message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info') {
  const id = toastIdCounter++
  toasts.value.push({ id, message, type })

  // Auto-remove after 4 seconds
  setTimeout(() => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }, 4000)
}

const currentTimer = computed(() => {
  if (!selectedEmployeeId.value || !state.value?.active_timers) return null
  return state.value.active_timers.find((t: any) => t.user_id === parseInt(selectedEmployeeId.value))
})

const pendingRequests = computed(() => {
  return vacationRequests.value.filter((r: any) => r.request_status === 'pending')
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
    const response = await fetch(`${rest.baseUrl}/workforce/time-tracking`, {
      headers: { 'X-WP-Nonce': rest.nonce }
    })
    const result = await response.json()
    const payload = extractRestData<any>(result)
    if (payload) {
      state.value = payload
      employees.value = payload.employees || []
    }
  } catch (error) {
    console.error('Failed to load workforce state', error)
  }
}

async function reloadAll() {
  await Promise.all([
    loadState(),
    loadVacationRequests()
  ])
}

async function loadEmployees() {
  try {
    const response = await fetch(
      `${rest.baseUrl}/workforce/time-tracking/employees?status=${employeeStatusFilter.value}`,
      { headers: { 'X-WP-Nonce': rest.nonce } }
    )
    const result = await response.json()
    const payload = extractRestData<any>(result)
    if (payload) {
      employees.value = payload.employees || []
    }
  } catch (error) {
    console.error('Failed to load employees', error)
  }
}

async function loadVacationRequests() {
  try {
    const response = await fetch(`${rest.baseUrl}/workforce/vacation-requests`, {
      headers: { 'X-WP-Nonce': rest.nonce }
    })
    const result = await response.json()
    const payload = extractRestData<any>(result)
    if (payload) {
      vacationRequests.value = payload.requests || []
    }
  } catch (error) {
    console.error('Failed to load vacation requests', error)
  }
}

async function clockIn() {
  if (!selectedEmployeeId.value || isClockingIn.value) return

  isClockingIn.value = true
  try {
    const response = await fetch(`${rest.baseUrl}/workforce/time-tracking/clock-in`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': rest.nonce
      },
      body: JSON.stringify({
        user_id: parseInt(selectedEmployeeId.value)
      })
    })
    const result = await response.json()
    if (result.success) {
      await reloadAll()
      showToast(t('mod.tools.workforce.clockInSuccess'), 'success')
    }
  } catch (error) {
    console.error('Clock in failed', error)
    showToast(t('mod.tools.error'), 'error')
  } finally {
    isClockingIn.value = false
  }
}

async function clockOut() {
  if (!selectedEmployeeId.value || isClockingOut.value) return

  isClockingOut.value = true
  try {
    const response = await fetch(`${rest.baseUrl}/workforce/time-tracking/clock-out`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': rest.nonce
      },
      body: JSON.stringify({
        user_id: parseInt(selectedEmployeeId.value)
      })
    })
    const result = await response.json()
    if (result.success) {
      await reloadAll()
      showToast(t('mod.tools.workforce.clockOutSuccess'), 'success')
    }
  } catch (error) {
    console.error('Clock out failed', error)
    showToast(t('mod.tools.error'), 'error')
  } finally {
    isClockingOut.value = false
  }
}

async function submitVacationRequest() {
  if (!selectedEmployeeId.value) {
    showToast(t('mod.tools.workforce.selectEmployee'), 'warning')
    return
  }

  // Validate form
  if (!vacationForm.value.start_date) {
    showToast('Bitte Startdatum auswählen', 'warning')
    return
  }

  if (!vacationForm.value.end_date) {
    showToast('Bitte Enddatum auswählen', 'warning')
    return
  }

  // Check if end date is after start date
  const startDate = new Date(vacationForm.value.start_date)
  const endDate = new Date(vacationForm.value.end_date)
  if (endDate < startDate) {
    showToast('Enddatum muss nach dem Startdatum liegen', 'warning')
    return
  }

  isSubmittingVacation.value = true
  try {
    const response = await fetch(`${rest.baseUrl}/workforce/vacation-requests`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': rest.nonce
      },
      body: JSON.stringify({
        user_id: parseInt(selectedEmployeeId.value),
        ...vacationForm.value
      })
    })
    const result = await response.json()
    if (isRestSuccess(result)) {
      await loadVacationRequests()
      vacationForm.value = { start_date: '', end_date: '', name: '', note: '' }
      showToast(t('mod.tools.workforce.requestSubmitted'), 'success')
    } else {
      showToast(result.message || t('mod.tools.error'), 'error')
    }
  } catch (error) {
    console.error('Vacation request failed', error)
    showToast(t('mod.tools.error'), 'error')
  } finally {
    isSubmittingVacation.value = false
  }
}

async function approveRequest(requestId: number) {
  approvingRequestId.value = requestId
  try {
    const response = await fetch(
      `${rest.baseUrl}/workforce/vacation-requests/${requestId}/approve`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': rest.nonce
        },
        body: JSON.stringify({})
      }
    )
    const result = await response.json()
    if (isRestSuccess(result)) {
      await loadVacationRequests()
      showToast(t('mod.tools.workforce.requestApproved'), 'success')
    }
  } catch (error) {
    console.error('Approve failed', error)
    showToast(t('mod.tools.error'), 'error')
  } finally {
    approvingRequestId.value = null
  }
}

async function rejectRequest(requestId: number) {
  const reason = prompt(t('mod.tools.workforce.rejectReason'))
  if (!reason) return

  rejectingRequestId.value = requestId
  try {
    const response = await fetch(
      `${rest.baseUrl}/workforce/vacation-requests/${requestId}/reject`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': rest.nonce
        },
        body: JSON.stringify({ reason })
      }
    )
    const result = await response.json()
    if (isRestSuccess(result)) {
      await loadVacationRequests()
      showToast(t('mod.tools.workforce.requestRejected'), 'success')
    }
  } catch (error) {
    console.error('Reject failed', error)
    showToast(t('mod.tools.error'), 'error')
  } finally {
    rejectingRequestId.value = null
  }
}

function getEmployeeName(userId: number): string {
  const emp = employees.value.find((e: any) => e.id === userId)
  return emp?.name || `Employee #${userId}`
}

function formatDateTime(datetime: string | null): string {
  if (!datetime) return '-'
  return new Date(datetime).toLocaleString('de-DE')
}

function formatDuration(minutes: number): string {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${hours}:${mins.toString().padStart(2, '0')}`
}

function formatLiveDuration(startedAt: string): string {
  if (!startedAt) return '0:00'
  const start = new Date(startedAt)
  const totalMinutes = Math.floor((currentTime.value.getTime() - start.getTime()) / 60000)
  return formatDuration(totalMinutes)
}

onMounted(() => {
  loadState()
  loadEmployees()
  loadVacationRequests()

  // Update timer display every second
  timerInterval = window.setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timerInterval !== null) {
    clearInterval(timerInterval)
  }
})
</script>

<style>
.workforce-tab {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-lg);
}

.workforce-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: var(--bookando-space-md);
}

.workforce-card {
  background: #fff;
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius-lg);
  padding: var(--bookando-space-lg);
}

.employee-selector {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: var(--bookando-space-md);
}

.employee-selector label {
  display: flex;
  flex-direction: column;
  font-size: 0.875rem;
}

.active-timer {
  background: var(--bookando-success-soft, #e6f7f0);
  border: 1px solid var(--bookando-success, #10b981);
  border-radius: var(--bookando-radius);
  padding: var(--bookando-space-md);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--bookando-space-md);
}

.timer-info {
  display: flex;
  flex-direction: column;
}

.timer-duration {
  font-size: 1.5rem;
  font-weight: bold;
  color: var(--bookando-success, #10b981);
}

.clock-actions {
  margin: var(--bookando-space-lg) 0;
}

.btn-large {
  padding: 1.5rem 3rem;
  font-size: 1.25rem;
  width: 100%;
}

.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: var(--bookando-space-md);
  margin: var(--bookando-space-lg) 0;
}

.stat {
  background: var(--bookando-surface, #f9fafb);
  padding: var(--bookando-space-md);
  border-radius: var(--bookando-radius);
  display: flex;
  flex-direction: column;
}

.stat strong {
  font-size: 1.5rem;
  color: var(--bookando-primary);
}

.entries-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: var(--bookando-space-md);
}

.entries-table th,
.entries-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--bookando-border);
}

.vacation-form {
  margin-top: var(--bookando-space-md);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--bookando-space-md);
  margin-bottom: var(--bookando-space-md);
}

.form-grid label {
  display: flex;
  flex-direction: column;
  font-size: 0.875rem;
}

.form-grid .full {
  grid-column: 1 / -1;
}

.requests-list {
  margin-top: var(--bookando-space-lg);
}

.request-card {
  background: var(--bookando-surface, #f9fafb);
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius);
  padding: var(--bookando-space-md);
  margin-bottom: var(--bookando-space-sm);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.request-info {
  display: flex;
  flex-direction: column;
}

.request-actions {
  display: flex;
  gap: var(--bookando-space-sm);
}

.btn {
  border: 1px solid var(--bookando-border);
  background: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn.primary {
  background: var(--bookando-primary);
  color: #fff;
  border-color: var(--bookando-primary);
}

.btn.success {
  background: var(--bookando-success, #10b981);
  color: #fff;
  border-color: var(--bookando-success, #10b981);
}

.btn.danger {
  background: var(--bookando-danger, #ef4444);
  color: #fff;
  border-color: var(--bookando-danger, #ef4444);
}

.btn.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}

.muted {
  color: var(--bookando-text-muted);
  font-size: 0.9rem;
}

.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.toast {
  min-width: 300px;
  padding: 1rem 1.5rem;
  border-radius: var(--bookando-radius);
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-left: 4px solid;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-success {
  border-left-color: var(--bookando-success, #10b981);
  background: #f0fdf4;
  color: #166534;
}

.toast-error {
  border-left-color: var(--bookando-danger, #ef4444);
  background: #fef2f2;
  color: #991b1b;
}

.toast-warning {
  border-left-color: #f59e0b;
  background: #fffbeb;
  color: #92400e;
}

.toast-info {
  border-left-color: var(--bookando-primary, #3b82f6);
  background: #eff6ff;
  color: #1e40af;
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--bookando-text-muted);
  background: var(--bookando-surface, #f9fafb);
  border-radius: var(--bookando-radius);
  margin: 1rem 0;
}

.empty-state p {
  font-size: 1rem;
  margin: 0;
}
</style>
