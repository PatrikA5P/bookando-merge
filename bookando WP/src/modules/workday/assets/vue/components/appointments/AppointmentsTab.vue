<template>
  <div class="appointments-tab">
    <div class="appointments-header">
      <div>
        <h2>{{ t('mod.workday.appointments.title') }}</h2>
        <p>{{ t('mod.workday.appointments.subtitle') }}</p>
      </div>
      <button class="btn primary" @click="showCreateModal = true">
        + {{ t('mod.workday.appointments.createAppointment') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-card">
      <h3>{{ t('mod.workday.appointments.filters') }}</h3>
      <div class="filters-grid">
        <label>
          {{ t('mod.workday.appointments.filterByEmployee') }}
          <select v-model="filters.employeeId">
            <option value="">{{ t('mod.workday.appointments.allEmployees') }}</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.name }}
            </option>
          </select>
        </label>
        <label>
          {{ t('mod.workday.appointments.dateRange') }}
          <div class="date-range">
            <input v-model="filters.dateFrom" type="date">
            <span>-</span>
            <input v-model="filters.dateTo" type="date">
          </div>
        </label>
        <button class="btn" @click="applyFilters">{{ t('mod.tools.apply') }}</button>
      </div>
    </div>

    <!-- Appointments List -->
    <div class="appointments-list">
      <div class="list-tabs">
        <button
          :class="['tab-btn', { active: activeTab === 'upcoming' }]"
          @click="activeTab = 'upcoming'"
        >
          {{ t('mod.workday.appointments.upcoming') }}
        </button>
        <button
          :class="['tab-btn', { active: activeTab === 'past' }]"
          @click="activeTab = 'past'"
        >
          {{ t('mod.workday.appointments.past') }}
        </button>
      </div>

      <div v-if="!loading" class="appointments-content">
        <div v-if="filteredAppointments.length > 0" class="appointments-table">
          <table>
            <thead>
              <tr>
                <th>{{ t('mod.workday.appointments.time') }}</th>
                <th>{{ t('mod.workday.appointments.customer') }}</th>
                <th>{{ t('mod.workday.appointments.service') }}</th>
                <th>{{ t('mod.workday.appointments.employee') }}</th>
                <th>{{ t('mod.workday.appointments.duration') }}</th>
                <th>{{ t('mod.workday.appointments.status') }}</th>
                <th>{{ t('mod.workday.appointments.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="appointment in filteredAppointments" :key="appointment.id">
                <td>
                  <div class="appointment-date">
                    {{ formatDate(appointment.date) }}
                  </div>
                  <div class="appointment-time">
                    {{ appointment.time }}
                  </div>
                </td>
                <td>{{ appointment.customer_name }}</td>
                <td>{{ appointment.service_name }}</td>
                <td>{{ appointment.employee_name }}</td>
                <td>{{ appointment.duration }} min</td>
                <td>
                  <span :class="['status-badge', appointment.status]">
                    {{ appointment.status }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button class="btn-sm" @click="viewAppointment(appointment)">
                      👁️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state">
          <p>{{ t('mod.workday.appointments.noAppointments') }}</p>
        </div>
      </div>
      <div v-else class="loading-state">
        <p>{{ t('mod.tools.loading') }}</p>
      </div>
    </div>

    <!-- Info Box -->
    <div class="info-box">
      <strong>ℹ️ {{ t('mod.workday.appointments.migrationNote') }}</strong>
    </div>

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
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { extractRestData } from '../../utils/api'

const { t } = useI18n()

interface Appointment {
  id: number
  date: string
  time: string
  customer_name: string
  service_name: string
  employee_name: string
  duration: number
  status: string
}

const loading = ref(true)
const appointments = ref<Appointment[]>([])
const employees = ref<any[]>([])
const activeTab = ref<'upcoming' | 'past'>('upcoming')
const showCreateModal = ref(false)

const filters = ref({
  employeeId: '',
  dateFrom: '',
  dateTo: ''
})

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

  setTimeout(() => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }, 4000)
}

const filteredAppointments = computed(() => {
  const now = new Date()
  now.setHours(0, 0, 0, 0)

  return appointments.value.filter(appointment => {
    const appointmentDate = new Date(appointment.date)
    appointmentDate.setHours(0, 0, 0, 0)

    // Filter by tab (upcoming/past)
    if (activeTab.value === 'upcoming') {
      if (appointmentDate < now) return false
    } else {
      if (appointmentDate >= now) return false
    }

    // Filter by employee
    if (filters.value.employeeId && appointment.employee_name !== filters.value.employeeId) {
      return false
    }

    // Filter by date range
    if (filters.value.dateFrom) {
      const from = new Date(filters.value.dateFrom)
      if (appointmentDate < from) return false
    }
    if (filters.value.dateTo) {
      const to = new Date(filters.value.dateTo)
      if (appointmentDate > to) return false
    }

    return true
  })
})

function getRestConfig() {
  const vars = (window as any).BOOKANDO_VARS || {}
  const restBase = vars.rest_url || '/wp-json/bookando/v1/workday'
  return {
    baseUrl: restBase,
    nonce: vars.nonce || ''
  }
}

const rest = getRestConfig()

async function loadAppointments() {
  loading.value = true
  try {
    const response = await fetch(`${rest.baseUrl}/appointments`, {
      headers: { 'X-WP-Nonce': rest.nonce }
    })
    const result = await response.json()
    const data = extractRestData<any>(result)

    if (data && data.appointments) {
      appointments.value = data.appointments
    }
  } catch (error) {
    console.error('Failed to load appointments', error)
    showToast(t('mod.tools.error'), 'error')
  } finally {
    loading.value = false
  }
}

async function loadEmployees() {
  try {
    const response = await fetch(`${rest.baseUrl}/time-tracking/employees`, {
      headers: { 'X-WP-Nonce': rest.nonce }
    })
    const result = await response.json()
    const data = extractRestData<any>(result)

    if (data && data.employees) {
      employees.value = data.employees
    }
  } catch (error) {
    console.error('Failed to load employees', error)
  }
}

function applyFilters() {
  // Filters are applied via computed property
  showToast('Filter angewendet', 'success')
}

function viewAppointment(appointment: Appointment) {
  showToast(`Termin Details: ${appointment.customer_name}`, 'info')
}

function formatDate(dateStr: string): string {
  const date = new Date(dateStr)
  return date.toLocaleDateString('de-DE', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

onMounted(() => {
  loadAppointments()
  loadEmployees()
})
</script>

<style>
.appointments-tab {
  padding: var(--bookando-space-lg);
}

.appointments-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--bookando-space-lg);
  flex-wrap: wrap;
  gap: var(--bookando-space-md);
}

.appointments-header h2 {
  margin: 0 0 var(--bookando-space-xs);
}

.appointments-header p {
  margin: 0;
  color: var(--bookando-text-muted);
}

.btn {
  border: 1px solid var(--bookando-border);
  background: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
}

.btn:hover:not(:disabled) {
  background: var(--bookando-surface);
}

.btn.primary {
  background: var(--bookando-primary);
  color: white;
  border-color: var(--bookando-primary);
}

.btn.primary:hover:not(:disabled) {
  opacity: 0.9;
}

.filters-card {
  background: white;
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius-lg);
  padding: var(--bookando-space-lg);
  margin-bottom: var(--bookando-space-lg);
}

.filters-card h3 {
  margin: 0 0 var(--bookando-space-md);
}

.filters-grid {
  display: grid;
  grid-template-columns: 1fr 2fr auto;
  gap: var(--bookando-space-md);
  align-items: end;
}

.filters-grid label {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-xs);
  font-size: 0.875rem;
}

.date-range {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
}

.date-range input {
  flex: 1;
}

.appointments-list {
  background: white;
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius-lg);
  overflow: hidden;
}

.list-tabs {
  display: flex;
  border-bottom: 1px solid var(--bookando-border);
}

.tab-btn {
  flex: 1;
  padding: var(--bookando-space-md) var(--bookando-space-lg);
  border: none;
  background: none;
  cursor: pointer;
  font-weight: 500;
  color: var(--bookando-text-muted);
  transition: all 0.2s;
}

.tab-btn:hover {
  background: var(--bookando-surface);
}

.tab-btn.active {
  color: var(--bookando-primary);
  border-bottom: 2px solid var(--bookando-primary);
}

.appointments-content {
  padding: var(--bookando-space-lg);
}

.appointments-table table {
  width: 100%;
  border-collapse: collapse;
}

.appointments-table th,
.appointments-table td {
  padding: var(--bookando-space-md);
  text-align: left;
  border-bottom: 1px solid var(--bookando-border);
}

.appointments-table th {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--bookando-text-muted);
}

.appointment-date {
  font-weight: 600;
}

.appointment-time {
  font-size: 0.875rem;
  color: var(--bookando-text-muted);
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.action-buttons {
  display: flex;
  gap: var(--bookando-space-xs);
}

.btn-sm {
  background: none;
  border: 1px solid var(--bookando-border);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

.btn-sm:hover {
  background: var(--bookando-surface);
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--bookando-text-muted);
}

.loading-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--bookando-text-muted);
}

.info-box {
  background: #e3f2fd;
  border: 1px solid #90caf9;
  border-radius: var(--bookando-radius-lg);
  padding: var(--bookando-space-lg);
  margin-top: var(--bookando-space-lg);
  color: #1565c0;
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
</style>
