<template>
  <div class="calendar-tab">
    <div class="calendar-header">
      <div>
        <h2>{{ t('mod.workday.calendar.title') }}</h2>
        <p>{{ t('mod.workday.calendar.subtitle') }}</p>
      </div>
      <div class="calendar-controls">
        <button class="btn" @click="previousMonth">‹</button>
        <span class="current-month">{{ currentMonthName }} {{ currentYear }}</span>
        <button class="btn" @click="nextMonth">›</button>
        <button class="btn primary" @click="goToToday">{{ t('mod.workday.calendar.today') }}</button>
      </div>
    </div>

    <!-- Legend -->
    <div class="calendar-legend">
      <div class="legend-item">
        <span class="legend-color" style="background: #3b82f6"></span>
        <span>{{ t('mod.workday.calendar.courses') }}</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #10b981"></span>
        <span>{{ t('mod.workday.calendar.appointments') }}</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #f59e0b"></span>
        <span>{{ t('mod.workday.calendar.workingHours') }}</span>
      </div>
      <div class="legend-item">
        <span class="legend-color" style="background: #ef4444"></span>
        <span>{{ t('mod.workday.calendar.vacations') }}</span>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div v-if="!loading" class="calendar-grid">
      <div class="calendar-weekdays">
        <div v-for="day in weekdays" :key="day" class="calendar-weekday">
          {{ day }}
        </div>
      </div>
      <div class="calendar-days">
        <div
          v-for="day in calendarDays"
          :key="`${day.date}`"
          :class="['calendar-day', {
            'other-month': day.isOtherMonth,
            'today': day.isToday,
            'has-events': day.events.length > 0
          }]"
          @click="selectDay(day)"
        >
          <div class="day-number">{{ day.dayNumber }}</div>
          <div class="day-events">
            <div
              v-for="(event, idx) in day.events.slice(0, 3)"
              :key="idx"
              :class="['event-marker', `event-${event.type}`]"
              :title="event.title"
            >
              {{ event.title }}
            </div>
            <div v-if="day.events.length > 3" class="more-events">
              +{{ day.events.length - 3 }} {{ t('mod.workday.calendar.more') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="loading-state">
      <p>{{ t('mod.tools.loading') }}</p>
    </div>

    <!-- Day Details Modal -->
    <div v-if="selectedDay" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ formatSelectedDate(selectedDay.date) }}</h3>
          <button class="btn-close" @click="closeModal">×</button>
        </div>
        <div class="modal-body">
          <div v-if="selectedDay.events.length > 0">
            <div
              v-for="(event, idx) in selectedDay.events"
              :key="idx"
              :class="['event-detail', `event-${event.type}`]"
            >
              <div class="event-time">{{ event.time }}</div>
              <div class="event-info">
                <div class="event-title">{{ event.title }}</div>
                <div v-if="event.description" class="event-description">
                  {{ event.description }}
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <p>{{ t('mod.workday.calendar.noEvents') }}</p>
          </div>
        </div>
      </div>
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

interface CalendarEvent {
  type: 'course' | 'appointment' | 'work' | 'vacation'
  title: string
  time: string
  description?: string
}

interface CalendarDay {
  date: string
  dayNumber: number
  isOtherMonth: boolean
  isToday: boolean
  events: CalendarEvent[]
}

const loading = ref(true)
const currentDate = ref(new Date())
const currentYear = computed(() => currentDate.value.getFullYear())
const currentMonth = computed(() => currentDate.value.getMonth())
const calendarDays = ref<CalendarDay[]>([])
const selectedDay = ref<CalendarDay | null>(null)

const weekdays = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']

const currentMonthName = computed(() => {
  return new Date(currentYear.value, currentMonth.value, 1).toLocaleDateString('de-DE', {
    month: 'long'
  })
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

function getRestConfig() {
  const vars = (window as any).BOOKANDO_VARS || {}
  const restBase = vars.rest_url || '/wp-json/bookando/v1/workday'
  return {
    baseUrl: restBase,
    nonce: vars.nonce || ''
  }
}

const rest = getRestConfig()

async function loadCalendarData() {
  loading.value = true
  try {
    const month = currentMonth.value + 1 // JavaScript months are 0-indexed
    const year = currentYear.value

    const response = await fetch(
      `${rest.baseUrl}/calendar?month=${month}&year=${year}`,
      {
        headers: { 'X-WP-Nonce': rest.nonce }
      }
    )
    const result = await response.json()
    const data = extractRestData<any>(result)

    if (data) {
      buildCalendar(data)
    } else {
      buildCalendar({})
    }
  } catch (error) {
    console.error('Failed to load calendar', error)
    showToast(t('mod.tools.error'), 'error')
    buildCalendar({})
  } finally {
    loading.value = false
  }
}

function buildCalendar(data: any) {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)

  // Get the day of week (0 = Sunday, 1 = Monday, etc.)
  let startDay = firstDay.getDay()
  // Convert Sunday (0) to 7 for our Monday-first week
  startDay = startDay === 0 ? 7 : startDay

  const days: CalendarDay[] = []
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  // Previous month days
  const prevMonthLastDay = new Date(year, month, 0).getDate()
  for (let i = startDay - 2; i >= 0; i--) {
    const dayNumber = prevMonthLastDay - i
    const date = new Date(year, month - 1, dayNumber)
    days.push({
      date: date.toISOString().split('T')[0],
      dayNumber,
      isOtherMonth: true,
      isToday: false,
      events: []
    })
  }

  // Current month days
  for (let day = 1; day <= lastDay.getDate(); day++) {
    const date = new Date(year, month, day)
    const dateStr = date.toISOString().split('T')[0]
    const isToday = date.getTime() === today.getTime()

    days.push({
      date: dateStr,
      dayNumber: day,
      isOtherMonth: false,
      isToday,
      events: getEventsForDate(dateStr, data)
    })
  }

  // Next month days
  const remainingDays = 42 - days.length // 6 weeks * 7 days
  for (let day = 1; day <= remainingDays; day++) {
    const date = new Date(year, month + 1, day)
    days.push({
      date: date.toISOString().split('T')[0],
      dayNumber: day,
      isOtherMonth: true,
      isToday: false,
      events: []
    })
  }

  calendarDays.value = days
}

function getEventsForDate(dateStr: string, data: any): CalendarEvent[] {
  const events: CalendarEvent[] = []

  // Add events from API data (if available)
  if (data.events && Array.isArray(data.events)) {
    const dayEvents = data.events.filter((e: any) => e.date === dateStr)
    events.push(...dayEvents.map((e: any) => ({
      type: e.type || 'appointment',
      title: e.title || 'Event',
      time: e.time || '00:00',
      description: e.description
    })))
  }

  return events
}

function previousMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1)
  loadCalendarData()
}

function nextMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1)
  loadCalendarData()
}

function goToToday() {
  currentDate.value = new Date()
  loadCalendarData()
}

function selectDay(day: CalendarDay) {
  if (!day.isOtherMonth) {
    selectedDay.value = day
  }
}

function closeModal() {
  selectedDay.value = null
}

function formatSelectedDate(dateStr: string) {
  const date = new Date(dateStr)
  return date.toLocaleDateString('de-DE', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(() => {
  loadCalendarData()
})
</script>

<style>
.calendar-tab {
  padding: var(--bookando-space-lg);
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--bookando-space-lg);
  flex-wrap: wrap;
  gap: var(--bookando-space-md);
}

.calendar-header h2 {
  margin: 0 0 var(--bookando-space-xs);
}

.calendar-header p {
  margin: 0;
  color: var(--bookando-text-muted);
}

.calendar-controls {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
}

.current-month {
  font-weight: 600;
  min-width: 150px;
  text-align: center;
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

.calendar-legend {
  display: flex;
  gap: var(--bookando-space-lg);
  margin-bottom: var(--bookando-space-lg);
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-xs);
  font-size: 0.875rem;
}

.legend-color {
  width: 12px;
  height: 12px;
  border-radius: 2px;
}

.calendar-grid {
  background: white;
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius-lg);
  overflow: hidden;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: var(--bookando-surface);
  border-bottom: 1px solid var(--bookando-border);
}

.calendar-weekday {
  padding: var(--bookando-space-sm);
  text-align: center;
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--bookando-text-muted);
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
  min-height: 100px;
  padding: var(--bookando-space-sm);
  border-right: 1px solid var(--bookando-border);
  border-bottom: 1px solid var(--bookando-border);
  cursor: pointer;
  transition: background-color 0.2s;
}

.calendar-day:nth-child(7n) {
  border-right: none;
}

.calendar-day:hover:not(.other-month) {
  background: var(--bookando-surface);
}

.calendar-day.other-month {
  background: #fafafa;
  color: var(--bookando-text-muted);
  cursor: default;
}

.calendar-day.today {
  background: #eff6ff;
}

.calendar-day.today .day-number {
  background: var(--bookando-primary);
  color: white;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.day-number {
  font-weight: 600;
  margin-bottom: var(--bookando-space-xs);
}

.day-events {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.event-marker {
  font-size: 0.75rem;
  padding: 2px 6px;
  border-radius: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.event-course {
  background: #dbeafe;
  color: #1e40af;
}

.event-appointment {
  background: #d1fae5;
  color: #065f46;
}

.event-work {
  background: #fef3c7;
  color: #92400e;
}

.event-vacation {
  background: #fee2e2;
  color: #991b1b;
}

.more-events {
  font-size: 0.75rem;
  color: var(--bookando-text-muted);
  margin-top: 2px;
}

.loading-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--bookando-text-muted);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: white;
  border-radius: var(--bookando-radius-lg);
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--bookando-space-lg);
  border-bottom: 1px solid var(--bookando-border);
}

.modal-header h3 {
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 2rem;
  line-height: 1;
  cursor: pointer;
  padding: 0;
  color: var(--bookando-text-muted);
}

.btn-close:hover {
  color: var(--bookando-text);
}

.modal-body {
  padding: var(--bookando-space-lg);
}

.event-detail {
  display: flex;
  gap: var(--bookando-space-md);
  padding: var(--bookando-space-md);
  border-radius: var(--bookando-radius);
  margin-bottom: var(--bookando-space-sm);
}

.event-time {
  font-weight: 600;
  min-width: 60px;
}

.event-info {
  flex: 1;
}

.event-title {
  font-weight: 600;
  margin-bottom: var(--bookando-space-xs);
}

.event-description {
  font-size: 0.875rem;
  color: var(--bookando-text-muted);
}

.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: var(--bookando-text-muted);
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
