<template>
  <div
    v-if="state"
    class="course-planner"
  >
    <div class="planner-header">
      <div>
        <h2>{{ t('mod.tools.coursePlanner.title') }}</h2>
        <p>{{ t('mod.tools.coursePlanner.subtitle') }}</p>
      </div>
      <div class="planner-period">
        <label>
          {{ t('mod.tools.coursePlanner.periodStart') }}
          <input
            v-model="planPeriod.start"
            type="date"
          >
        </label>
        <label>
          {{ t('mod.tools.coursePlanner.periodEnd') }}
          <input
            v-model="planPeriod.end"
            type="date"
          >
        </label>
        <button
          class="btn primary"
          @click="generatePlan"
        >
          {{ t('mod.tools.coursePlanner.generatePlan') }}
        </button>
      </div>
    </div>

    <div class="planner-grid">
      <section class="planner-card">
        <h3>{{ t('mod.tools.coursePlanner.analytics') }}</h3>
        <div class="stats-row">
          <div class="stat">
            <span>{{ t('mod.tools.coursePlanner.totalSessions') }}</span>
            <strong>{{ state.analytics?.total_sessions || 0 }}</strong>
          </div>
          <div class="stat">
            <span>{{ t('mod.tools.coursePlanner.avgAttendance') }}</span>
            <strong>{{ state.analytics?.avg_attendance || 0 }}</strong>
          </div>
          <div class="stat">
            <span>{{ t('mod.tools.coursePlanner.cancellationRate') }}</span>
            <strong>{{ (state.analytics?.cancellation_rate || 0).toFixed?.(1) ?? state.analytics?.cancellation_rate ?? 0 }}%</strong>
          </div>
        </div>
        <div
          v-if="state.analytics?.popular_slots?.length"
          class="popular-slots"
        >
          <h4>{{ t('mod.tools.coursePlanner.popularSlots') }}</h4>
          <ul>
            <li
              v-for="slot in state.analytics.popular_slots"
              :key="slot.day + slot.time"
            >
              <span>{{ slot.label }}</span>
              <small>{{ t('mod.tools.coursePlanner.successScore') }}: {{ slot.score }}</small>
            </li>
          </ul>
        </div>
      </section>

      <section class="planner-card">
        <h3>{{ t('mod.tools.coursePlanner.importTitle') }}</h3>
        <div class="form-grid">
          <label>
            {{ t('mod.tools.coursePlanner.offer') }}
            <select
              v-model="importForm.offerId"
              @change="prefillFromOffer"
            >
              <option value="">{{ t('mod.tools.choose') }}</option>
              <option
                v-for="offer in offers"
                :key="offer.id"
                :value="offer.id"
              >
                {{ offer.title }}
              </option>
            </select>
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.titleLabel') }}
            <input
              v-model="importForm.title"
              type="text"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.type') }}
            <input
              v-model="importForm.type"
              type="text"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.location') }}
            <input
              v-model="importForm.location"
              type="text"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.date') }}
            <input
              v-model="importForm.date"
              type="date"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.startTime') }}
            <input
              v-model="importForm.startTime"
              type="time"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.endTime') }}
            <input
              v-model="importForm.endTime"
              type="time"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.status') }}
            <select v-model="importForm.status">
              <option value="held">{{ t('mod.tools.coursePlanner.statuses.held') }}</option>
              <option value="waitlist">{{ t('mod.tools.coursePlanner.statuses.waitlist') }}</option>
              <option value="cancelled">{{ t('mod.tools.coursePlanner.statuses.cancelled') }}</option>
            </select>
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.capacity') }}
            <input
              v-model.number="importForm.capacity"
              type="number"
              min="1"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.attendance') }}
            <input
              v-model.number="importForm.attendance"
              type="number"
              min="0"
            >
          </label>
        </div>
        <button
          class="btn"
          @click="submitImport"
        >
          {{ t('mod.tools.coursePlanner.importAction') }}
        </button>
      </section>
    </div>

    <section class="planner-card">
      <div class="card-header">
        <h3>{{ t('mod.tools.coursePlanner.preferences') }}</h3>
        <button
          class="btn ghost"
          @click="savePreferences"
        >
          {{ t('mod.tools.coursePlanner.savePreferences') }}
        </button>
      </div>
      <div class="preferences-grid">
        <div>
          <h4>{{ t('mod.tools.coursePlanner.allowedDays') }}</h4>
          <div class="checkbox-list">
            <label
              v-for="day in daysOfWeek"
              :key="day.value"
            >
              <input
                v-model="preferenceForm.allowedDays"
                type="checkbox"
                :value="day.value"
              >
              {{ day.label }}
            </label>
          </div>
        </div>
        <div class="time-window">
          <h4>{{ t('mod.tools.coursePlanner.timeWindow') }}</h4>
          <label>
            {{ t('mod.tools.coursePlanner.startTime') }}
            <input
              v-model="preferenceForm.preferredTime.start"
              type="time"
            >
          </label>
          <label>
            {{ t('mod.tools.coursePlanner.endTime') }}
            <input
              v-model="preferenceForm.preferredTime.end"
              type="time"
            >
          </label>
          <label class="checkbox">
            <input
              v-model="preferenceForm.requireDaylight"
              type="checkbox"
            >
            {{ t('mod.tools.coursePlanner.requireDaylight') }}
          </label>
        </div>
        <div class="targets">
          <h4>{{ t('mod.tools.coursePlanner.typeTargets') }}</h4>
          <div
            v-for="target in courseTypeTargets"
            :key="target.id"
            class="target-row"
          >
            <input
              v-model="target.type"
              type="text"
              placeholder="Type"
            >
            <input
              v-model.number="target.count"
              type="number"
              min="1"
            >
            <button
              class="icon"
              @click="removeTarget(target.id)"
            >
              ×
            </button>
          </div>
          <button
            class="btn ghost"
            @click="addTarget"
          >
            {{ t('mod.tools.add') }}
          </button>
        </div>
        <div class="targets">
          <h4>{{ t('mod.tools.coursePlanner.simultaneous') }}</h4>
          <div
            v-for="group in linkedGroups"
            :key="group.id"
            class="target-row"
          >
            <input
              v-model="group.value"
              type="text"
              :placeholder="t('mod.tools.coursePlanner.simultaneousPlaceholder')"
            >
            <button
              class="icon"
              @click="removeLinkedGroup(group.id)"
            >
              ×
            </button>
          </div>
          <button
            class="btn ghost"
            @click="addLinkedGroup"
          >
            {{ t('mod.tools.add') }}
          </button>
        </div>
      </div>
    </section>

    <section class="planner-card">
      <div class="card-header">
        <h3>{{ t('mod.tools.coursePlanner.planPreview') }}</h3>
        <span
          v-if="state.plan?.generated_at"
          class="muted"
        >
          {{ t('mod.tools.coursePlanner.generatedAt') }}
          {{ formatDate(state.plan.generated_at) }}
        </span>
      </div>
      <table
        v-if="state.plan?.entries?.length"
        class="planner-table"
      >
        <thead>
          <tr>
            <th>{{ t('mod.tools.coursePlanner.date') }}</th>
            <th>{{ t('mod.tools.coursePlanner.type') }}</th>
            <th>{{ t('mod.tools.coursePlanner.titleLabel') }}</th>
            <th>{{ t('mod.tools.coursePlanner.timeWindow') }}</th>
            <th>{{ t('mod.tools.coursePlanner.score') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="entry in state.plan.entries"
            :key="entry.start_iso + entry.type"
          >
            <td>{{ formatDate(entry.start_iso) }}</td>
            <td>{{ entry.type }}</td>
            <td>{{ entry.title }}</td>
            <td>{{ entry.start }} - {{ entry.end }}</td>
            <td>{{ entry.score }}</td>
          </tr>
        </tbody>
      </table>
      <p
        v-else
        class="muted"
      >
        {{ t('mod.tools.coursePlanner.noPlan') }}
      </p>
    </section>

    <section class="planner-card">
      <div class="card-header">
        <h3>{{ t('mod.tools.coursePlanner.history') }}</h3>
        <span class="muted">{{ t('mod.tools.coursePlanner.entries', { count: state.history?.total || 0 }) }}</span>
      </div>
      <table class="planner-table">
        <thead>
          <tr>
            <th>{{ t('mod.tools.coursePlanner.titleLabel') }}</th>
            <th>{{ t('mod.tools.coursePlanner.type') }}</th>
            <th>{{ t('mod.tools.coursePlanner.date') }}</th>
            <th>{{ t('mod.tools.coursePlanner.location') }}</th>
            <th>{{ t('mod.tools.coursePlanner.attendance') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="course in state.history?.items || []"
            :key="course.id"
          >
            <td>{{ course.title }}</td>
            <td>{{ course.type }}</td>
            <td>{{ formatDate(course.start) }}</td>
            <td>{{ course.location }}</td>
            <td>{{ course.attendance }}/{{ course.capacity }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
  <div
    v-else
    class="muted"
  >
    {{ t('mod.tools.loading') }}
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { extractRestData } from '../../utils/api'

interface CoursePlannerState {
  history: { items: any[]; total: number }
  preferences: Record<string, any>
  analytics: Record<string, any>
  plan?: Record<string, any>
  offers?: any[]
}

const { t } = useI18n()
const state = ref<CoursePlannerState | null>(null)
const loading = ref(false)

const daysOfWeek = [
  { value: 'monday', label: t('mod.tools.weekdays.mon') },
  { value: 'tuesday', label: t('mod.tools.weekdays.tue') },
  { value: 'wednesday', label: t('mod.tools.weekdays.wed') },
  { value: 'thursday', label: t('mod.tools.weekdays.thu') },
  { value: 'friday', label: t('mod.tools.weekdays.fri') },
  { value: 'saturday', label: t('mod.tools.weekdays.sat') },
  { value: 'sunday', label: t('mod.tools.weekdays.sun') }
]

const preferenceForm = reactive({
  allowedDays: ['monday', 'wednesday', 'friday'],
  preferredTime: { start: '08:00', end: '21:00' },
  requireDaylight: false
})

const planPeriod = reactive({
  start: new Date().toISOString().split('T')[0],
  end: new Date(Date.now() + 6 * 86400000).toISOString().split('T')[0]
})

const courseTypeTargets = ref<{ id: number; type: string; count: number }[]>([
  { id: 1, type: 'course', count: 2 }
])
const linkedGroups = ref<{ id: number; value: string }[]>([])
let targetCounter = 2

const importForm = reactive({
  offerId: '',
  title: '',
  type: 'course',
  location: '',
  date: new Date().toISOString().split('T')[0],
  startTime: '18:00',
  endTime: '19:30',
  status: 'held',
  capacity: 12,
  attendance: 10
})

const offers = computed(() => state.value?.offers || [])

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
  loading.value = true
  try {
    const response = await fetch(`${rest.baseUrl}/course-planner`, {
      headers: { 'X-WP-Nonce': rest.nonce }
    })
    const result = await response.json()
    const responseData = extractRestData<any>(result)
    if (responseData) {
      state.value = responseData
      syncForms()
    }
  } catch (error) {
    console.error('Failed to load planner', error)
  } finally {
    loading.value = false
  }
}

function syncForms() {
  if (!state.value) return
  const prefs = state.value.preferences || {}
  preferenceForm.allowedDays = [...(prefs.allowed_days || [])]
  preferenceForm.preferredTime.start = prefs.preferred_time_window?.start || '08:00'
  preferenceForm.preferredTime.end = prefs.preferred_time_window?.end || '21:00'
  preferenceForm.requireDaylight = !!prefs.require_daylight
  courseTypeTargets.value = (prefs.course_type_targets || []).map((row: any, index: number) => ({
    id: index + 1,
    type: row.type,
    count: row.count
  }))
  if (!courseTypeTargets.value.length) {
    courseTypeTargets.value = [{ id: 1, type: 'course', count: 2 }]
  }
  linkedGroups.value = (prefs.linked_course_groups || []).map((group: string[], index: number) => ({
    id: index + 1,
    value: group.join(', ')
  }))
  planPeriod.start = state.value.plan?.period_start || planPeriod.start
  planPeriod.end = state.value.plan?.period_end || planPeriod.end
}

function formatDate(value?: string) {
  if (!value) return ''
  try {
    return new Date(value).toLocaleString()
  } catch (_error) {
    return value
  }
}

async function savePreferences() {
  const payload = {
    allowed_days: preferenceForm.allowedDays,
    preferred_time_window: preferenceForm.preferredTime,
    require_daylight: preferenceForm.requireDaylight,
    course_type_targets: courseTypeTargets.value.map(target => ({
      type: target.type,
      count: target.count
    })),
    linked_course_groups: linkedGroups.value
      .map(group => group.value.split(',').map(v => v.trim()).filter(Boolean))
      .filter(group => group.length > 1)
  }

  try {
    const response = await fetch(`${rest.baseUrl}/course-planner/preferences`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': rest.nonce
      },
      body: JSON.stringify(payload)
    })
    const result = await response.json()
    const responseData = extractRestData<any>(result)
    if (responseData) {
      state.value = responseData
      syncForms()
    }
  } catch (error) {
    console.error('Failed to save preferences', error)
  }
}

async function submitImport() {
  try {
    const response = await fetch(`${rest.baseUrl}/course-planner/import`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': rest.nonce
      },
      body: JSON.stringify({
        offer_id: importForm.offerId || undefined,
        title: importForm.title,
        type: importForm.type,
        location: importForm.location,
        date: importForm.date,
        start_time: importForm.startTime,
        end_time: importForm.endTime,
        status: importForm.status,
        capacity: importForm.capacity,
        attendance: importForm.attendance
      })
    })
    const result = await response.json()
    const responseData = extractRestData<any>(result)
    if (responseData) {
      state.value = responseData
      resetImportForm()
      syncForms()
    }
  } catch (error) {
    console.error('Failed to import history', error)
  }
}

function resetImportForm() {
  importForm.offerId = ''
  importForm.title = ''
  importForm.location = ''
  importForm.attendance = 10
}

async function generatePlan() {
  const payload = {
    period_start: planPeriod.start,
    period_end: planPeriod.end,
    preferences: {
      allowed_days: preferenceForm.allowedDays,
      preferred_time_window: preferenceForm.preferredTime,
      require_daylight: preferenceForm.requireDaylight,
      course_type_targets: courseTypeTargets.value.map(target => ({
        type: target.type,
        count: target.count
      })),
      linked_course_groups: linkedGroups.value
        .map(group => group.value.split(',').map(v => v.trim()).filter(Boolean))
        .filter(group => group.length > 1)
    }
  }

  try {
    const response = await fetch(`${rest.baseUrl}/course-planner/generate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': rest.nonce
      },
      body: JSON.stringify(payload)
    })
    const result = await response.json()
    const responseData = extractRestData<any>(result)
    if (responseData) {
      state.value = {
        ...(state.value || {}),
        plan: responseData
      }
    }
  } catch (error) {
    console.error('Failed to generate plan', error)
  }
}

function addTarget() {
  courseTypeTargets.value.push({ id: targetCounter++, type: 'course', count: 1 })
}

function removeTarget(id: number) {
  courseTypeTargets.value = courseTypeTargets.value.filter(target => target.id !== id)
  if (!courseTypeTargets.value.length) {
    addTarget()
  }
}

function addLinkedGroup() {
  linkedGroups.value.push({ id: Date.now(), value: '' })
}

function removeLinkedGroup(id: number) {
  linkedGroups.value = linkedGroups.value.filter(group => group.id !== id)
}

function prefillFromOffer() {
  const offer = offers.value.find(item => `${item.id}` === `${importForm.offerId}`)
  if (offer && !importForm.title) {
    importForm.title = offer.title
  }
  if (offer && !importForm.type) {
    importForm.type = 'course'
  }
}

onMounted(() => {
  loadState()
})
</script>

<style scoped>
.course-planner {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-lg);
}

.planner-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: var(--bookando-space-lg);
  flex-wrap: wrap;
}

.planner-header h2 {
  margin: 0;
}

.planner-period {
  display: flex;
  gap: var(--bookando-space-md);
  align-items: flex-end;
}

.planner-period label {
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

.planner-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: var(--bookando-space-lg);
}

.planner-card {
  background: #fff;
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius-lg);
  padding: var(--bookando-space-lg);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: var(--bookando-space-md);
  margin: var(--bookando-space-md) 0;
}

.stat {
  background: var(--bookando-surface-muted);
  border-radius: var(--bookando-radius);
  padding: var(--bookando-space-md);
}

.popular-slots ul {
  margin: 0;
  padding-left: 1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--bookando-space-md);
  margin-bottom: var(--bookando-space-md);
}

.form-grid label,
.time-window label {
  display: flex;
  flex-direction: column;
  font-size: 0.875rem;
  gap: 0.25rem;
}

.preferences-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--bookando-space-lg);
}

.checkbox-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
}

.target-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 0.5rem;
}

.target-row input[type='text'],
.target-row input[type='number'] {
  flex: 1;
}

.target-row .icon {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
}

.planner-table {
  width: 100%;
  border-collapse: collapse;
}

.planner-table th,
.planner-table td {
  padding: 0.5rem;
  border-bottom: 1px solid var(--bookando-border);
  text-align: left;
}

.muted {
  color: var(--bookando-text-muted);
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .planner-period {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
