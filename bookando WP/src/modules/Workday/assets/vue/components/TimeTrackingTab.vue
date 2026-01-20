<template>
  <div class="space-y-6 overflow-y-auto h-full p-1">
    <!-- Navigation Sub-Tabs -->
    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
      <div class="flex gap-4">
        <button
          @click="viewMode = 'personal'; selectedEmployeeId = null"
          :class="['pb-2 text-sm font-medium transition-colors relative', viewMode === 'personal' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-700']"
        >
          {{ $t('mod.workday.my_time') }}
          <div v-if="viewMode === 'personal'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-brand-600 rounded-t-full"></div>
        </button>
        <button
          v-if="hasTeamPermission"
          @click="viewMode = 'team'"
          :class="['pb-2 text-sm font-medium transition-colors relative', viewMode === 'team' ? 'text-brand-600' : 'text-slate-500 hover:text-slate-700']"
        >
          {{ $t('mod.workday.team_overview') }}
          <div v-if="viewMode === 'team'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-brand-600 rounded-t-full"></div>
        </button>
      </div>
      <button class="text-xs font-medium text-brand-600 flex items-center gap-1 hover:underline">
        <RefreshCwIcon :size="14" /> {{ $t('mod.workday.sync_calendars') }}
      </button>
    </div>

    <!-- Personal View -->
    <div v-if="viewMode === 'personal'" class="space-y-6 animate-fadeIn">
      <!-- Status Header -->
      <div class="bg-slate-900 text-white rounded-xl p-6 shadow-lg flex flex-col md:flex-row items-center justify-between">
        <div class="flex items-center gap-4">
          <div :class="['p-3 rounded-full', isClockedIn ? 'bg-brand-600 animate-pulse' : 'bg-slate-700']">
            <ClockIcon :size="24" />
          </div>
          <div>
            <p class="text-slate-400 text-sm">{{ $t('mod.workday.current_status') }}</p>
            <h3 class="text-xl font-bold">{{ isClockedIn ? $t('mod.workday.clocked_in') : $t('mod.workday.clocked_out') }}</h3>
            <p v-if="isClockedIn" class="text-xs text-brand-300">{{ $t('mod.workday.started_today_at', { time: '08:55 AM' }) }}</p>
          </div>
        </div>
        <div class="text-center md:text-right mt-4 md:mt-0">
          <div class="text-4xl font-mono font-bold tracking-widest mb-3">{{ isClockedIn ? elapsedTime : '--:--:--' }}</div>
          <button
            @click="handleClockAction"
            :class="['px-6 py-2 rounded-lg text-sm font-medium transition-colors shadow-lg', isClockedIn ? 'bg-rose-500 hover:bg-rose-600 text-white' : 'bg-emerald-500 hover:bg-emerald-600 text-white']"
          >
            {{ isClockedIn ? $t('mod.workday.clock_out') : $t('mod.workday.clock_in') }}
          </button>
        </div>
      </div>

      <!-- Stats Row -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div class="text-sm text-slate-500 font-medium mb-1">{{ $t('mod.workday.hours_today') }}</div>
          <div class="text-2xl font-bold text-slate-800">4h 12m</div>
          <div class="text-xs text-emerald-600 font-medium">{{ $t('mod.workday.on_track') }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div class="text-sm text-slate-500 font-medium mb-1">{{ $t('mod.workday.weekly_overtime') }}</div>
          <div class="text-2xl font-bold text-amber-600">+1h 30m</div>
          <div class="text-xs text-slate-400">{{ $t('mod.workday.vs_40h_week') }}</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <div class="text-sm text-slate-500 font-medium mb-1">{{ $t('mod.workday.absence_balance') }}</div>
          <div class="text-2xl font-bold text-slate-800">21.5 {{ $t('mod.workday.days') }}</div>
          <div class="text-xs text-slate-400">{{ $t('mod.workday.vacation_remaining') }}</div>
        </div>
      </div>

      <!-- Timesheet -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center">
          <h3 class="font-bold text-slate-800">{{ $t('mod.workday.timesheet_week') }}</h3>
          <button @click="handleAddManualEntry" class="text-brand-600 text-sm font-medium flex items-center gap-1 hover:bg-brand-50 px-2 py-1 rounded">
            <PlusIcon :size="16" /> {{ $t('mod.workday.manual_entry') }}
          </button>
        </div>
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
            <tr>
              <th class="p-4">{{ $t('mod.workday.date') }}</th>
              <th class="p-4">{{ $t('mod.workday.time_range') }}</th>
              <th class="p-4">{{ $t('mod.workday.type') }}</th>
              <th class="p-4">{{ $t('mod.workday.duration') }}</th>
              <th class="p-4">{{ $t('mod.workday.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <tr v-for="entry in personalEntries" :key="entry.id" class="hover:bg-slate-50">
              <td class="p-4 font-medium text-slate-800">{{ entry.date }}</td>
              <td class="p-4 text-slate-600">{{ entry.startTime }} - {{ entry.endTime || '...' }}</td>
              <td class="p-4">
                <span :class="['px-2 py-1 rounded text-xs font-medium', entry.type === 'Break' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700']">
                  {{ entry.type }}
                </span>
              </td>
              <td class="p-4 font-mono text-slate-600">{{ entry.endTime ? '4h 00m' : $t('mod.workday.in_progress') }}</td>
              <td class="p-4">
                <CheckCircleIcon v-if="entry.status === 'Approved'" :size="16" class="text-emerald-500" />
                <ClockIcon v-else-if="entry.status === 'Pending'" :size="16" class="text-amber-500" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Team View -->
    <div v-if="viewMode === 'team' && !selectedEmployeeId" class="space-y-6 animate-fadeIn">
      <!-- Team Filters -->
      <div class="flex flex-col md:flex-row gap-4 justify-between items-center bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="relative w-full md:w-96">
          <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
          <input
            v-model="teamQuery"
            type="text"
            :placeholder="$t('mod.workday.search_employee')"
            class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
          />
        </div>
        <div class="flex gap-2 w-full md:w-auto">
          <button class="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-medium">
            <FilterIcon :size="16" /> {{ $t('mod.workday.department') }}
          </button>
          <button class="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 text-sm font-medium">
            <DownloadIcon :size="16" /> {{ $t('mod.workday.report') }}
          </button>
        </div>
      </div>

      <!-- Team Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="p-3 rounded-lg bg-emerald-50 text-emerald-600">
            <UsersIcon :size="20" />
          </div>
          <div>
            <p class="text-xs text-slate-500 font-bold uppercase">{{ $t('mod.workday.active_now') }}</p>
            <h4 class="text-xl font-bold text-slate-800">12 <span class="text-xs font-normal text-slate-400">/ 18</span></h4>
          </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="p-3 rounded-lg bg-amber-50 text-amber-600">
            <ClockIcon :size="20" />
          </div>
          <div>
            <p class="text-xs text-slate-500 font-bold uppercase">{{ $t('mod.workday.on_break') }}</p>
            <h4 class="text-xl font-bold text-slate-800">2</h4>
          </div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
          <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
            <BarChart3Icon :size="20" />
          </div>
          <div>
            <p class="text-xs text-slate-500 font-bold uppercase">{{ $t('mod.workday.total_hrs_today') }}</p>
            <h4 class="text-xl font-bold text-slate-800">48h 20m</h4>
          </div>
        </div>
      </div>

      <!-- Team Grid -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
            <tr>
              <th class="p-4">{{ $t('mod.workday.employee') }}</th>
              <th class="p-4">{{ $t('mod.workday.department') }}</th>
              <th class="p-4">{{ $t('mod.workday.live_status') }}</th>
              <th class="p-4">{{ $t('mod.workday.started_at') }}</th>
              <th class="p-4">{{ $t('mod.workday.duration') }}</th>
              <th class="p-4 text-right">{{ $t('mod.workday.details') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <tr
              v-for="emp in filteredTeamStatus"
              :key="emp.id"
              @click="selectedEmployeeId = emp.id"
              class="hover:bg-slate-50 cursor-pointer group"
            >
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                    {{ emp.name.split(' ').map(n => n[0]).join('') }}
                  </div>
                  <span class="font-medium text-slate-800 group-hover:text-brand-600 transition-colors">{{ emp.name }}</span>
                </div>
              </td>
              <td class="p-4 text-slate-600">{{ emp.department }}</td>
              <td class="p-4">
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold', getStatusClass(emp.status)]">
                  <span :class="['w-2 h-2 rounded-full', emp.status === 'Working' ? 'bg-emerald-500 animate-pulse' : emp.status === 'Break' ? 'bg-amber-500' : 'bg-slate-400']"></span>
                  {{ emp.status }}
                </span>
              </td>
              <td class="p-4 font-mono text-slate-600">{{ emp.startTime }}</td>
              <td class="p-4 font-mono text-slate-800 font-medium">{{ emp.duration }}</td>
              <td class="p-4 text-right">
                <ChevronRightIcon :size="16" class="text-slate-400" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Team Employee Detail -->
    <div v-if="viewMode === 'team' && selectedEmployeeId" class="space-y-6 animate-fadeIn">
      <div class="flex items-center gap-4">
        <button @click="selectedEmployeeId = null" class="p-2 hover:bg-slate-100 rounded-full transition-colors">
          <ArrowLeftIcon :size="20" class="text-slate-600" />
        </button>
        <div>
          <h3 class="text-lg font-bold text-slate-800">
            {{ getEmployeeName(selectedEmployeeId) }}'s {{ $t('mod.workday.timesheet') }}
          </h3>
          <p class="text-xs text-slate-500">{{ $t('mod.workday.managing_entries') }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
          <div class="text-sm font-bold text-slate-700">{{ $t('mod.workday.october_2023') }}</div>
          <button
            @click="handleAddManualEntry"
            class="px-3 py-1.5 bg-white border border-slate-300 rounded text-xs font-medium shadow-sm hover:bg-slate-50 flex items-center gap-1"
          >
            <PlusIcon :size="14" /> {{ $t('mod.workday.add_manual_entry') }}
          </button>
        </div>
        <table class="w-full text-left">
          <thead class="bg-white border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
            <tr>
              <th class="p-4">{{ $t('mod.workday.date') }}</th>
              <th class="p-4">{{ $t('mod.workday.time_range') }}</th>
              <th class="p-4">{{ $t('mod.workday.type') }}</th>
              <th class="p-4">{{ $t('mod.workday.total') }}</th>
              <th class="p-4">{{ $t('mod.workday.status') }}</th>
              <th class="p-4 text-right">{{ $t('mod.workday.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-sm">
            <tr v-for="entry in selectedEmployeeEntries" :key="entry.id" class="hover:bg-slate-50 group">
              <td class="p-4 font-medium text-slate-800">{{ entry.date }}</td>
              <td class="p-4 text-slate-600">{{ entry.startTime }} - {{ entry.endTime }}</td>
              <td class="p-4">
                <span :class="['px-2 py-1 rounded text-xs font-medium', entry.type === 'Break' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700']">
                  {{ entry.type }}
                </span>
              </td>
              <td class="p-4 font-mono text-slate-600">4h 00m</td>
              <td class="p-4">
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ entry.status }}</span>
              </td>
              <td class="p-4 text-right">
                <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button class="p-1.5 bg-white border border-slate-200 text-slate-500 rounded hover:text-brand-600 hover:border-brand-300">
                    <Edit2Icon :size="14" />
                  </button>
                  <button @click="handleDeleteEntry(entry.id)" class="p-1.5 bg-white border border-slate-200 text-slate-500 rounded hover:text-rose-600 hover:border-rose-300">
                    <Trash2Icon :size="14" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="selectedEmployeeEntries.length === 0">
              <td colspan="6" class="p-8 text-center text-slate-400">
                {{ $t('mod.workday.no_entries_found') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  Plus as PlusIcon,
  RefreshCw as RefreshCwIcon,
  Search as SearchIcon,
  Filter as FilterIcon,
  Download as DownloadIcon,
  Users as UsersIcon,
  BarChart3 as BarChart3Icon,
  ChevronRight as ChevronRightIcon,
  ArrowLeft as ArrowLeftIcon,
  Edit2 as Edit2Icon,
  Trash2 as Trash2Icon
} from 'lucide-vue-next'

interface TimeEntry {
  id: string
  employeeId: string
  date: string
  startTime: string
  endTime?: string
  type: 'Work' | 'Break'
  status: 'Pending' | 'Approved'
}

interface TeamStatus {
  id: string
  name: string
  department: string
  status: 'Working' | 'Break' | 'Out'
  startTime: string
  duration: string
}

const { t: $t } = useI18n()

const viewMode = ref<'personal' | 'team'>('personal')
const hasTeamPermission = ref(true)
const isClockedIn = ref(true)
const elapsedTime = ref('00:00:00')
const teamQuery = ref('')
const selectedEmployeeId = ref<string | null>(null)

let clockInterval: any = null

const mockTeamStatus = ref<TeamStatus[]>([
  { id: 'e1', name: 'Sarah Jenkins', department: 'Wellness', status: 'Working', startTime: '08:00', duration: '4h 30m' },
  { id: 'e2', name: 'Mike Ross', department: 'Fitness', status: 'Break', startTime: '09:00', duration: '3h 15m' },
  { id: 'e3', name: 'Jessica Pearson', department: 'Admin', status: 'Out', startTime: '-', duration: '0h 00m' }
])

const entries = ref<TimeEntry[]>([
  { id: 't1', employeeId: 'current_user', date: '2023-10-24', startTime: '08:00', endTime: '12:00', type: 'Work', status: 'Approved' },
  { id: 't2', employeeId: 'current_user', date: '2023-10-24', startTime: '12:00', endTime: '13:00', type: 'Break', status: 'Approved' },
  { id: 't3', employeeId: 'current_user', date: '2023-10-24', startTime: '13:00', endTime: '17:00', type: 'Work', status: 'Approved' },
  { id: 't4', employeeId: 'current_user', date: new Date().toISOString().split('T')[0], startTime: '08:55', type: 'Work', status: 'Pending' },
  { id: 't5', employeeId: 'e1', date: '2023-10-24', startTime: '09:00', endTime: '12:15', type: 'Work', status: 'Approved' },
  { id: 't6', employeeId: 'e1', date: '2023-10-24', startTime: '12:15', endTime: '13:00', type: 'Break', status: 'Approved' }
])

const personalEntries = computed(() => entries.value.filter(e => e.employeeId === 'current_user'))

const filteredTeamStatus = computed(() =>
  mockTeamStatus.value.filter(e => e.name.toLowerCase().includes(teamQuery.value.toLowerCase()))
)

const selectedEmployeeEntries = computed(() =>
  selectedEmployeeId.value ? entries.value.filter(e => e.employeeId === selectedEmployeeId.value) : []
)

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Working': return 'bg-emerald-100 text-emerald-700'
    case 'Break': return 'bg-amber-100 text-amber-700'
    case 'Out': return 'bg-slate-100 text-slate-500'
    default: return 'bg-slate-100 text-slate-500'
  }
}

const getEmployeeName = (id: string) => {
  return mockTeamStatus.value.find(e => e.id === id)?.name || ''
}

onMounted(() => {
  if (isClockedIn.value) {
    const start = new Date()
    start.setHours(8, 55, 0)
    clockInterval = setInterval(() => {
      const now = new Date()
      const diff = now.getTime() - start.getTime()
      const hours = Math.floor(diff / (1000 * 60 * 60))
      const mins = Math.floor((diff / (1000 * 60)) % 60)
      const secs = Math.floor((diff / 1000) % 60)
      elapsedTime.value = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
    }, 1000)
  }
})

onUnmounted(() => {
  if (clockInterval) clearInterval(clockInterval)
})

const handleClockAction = () => {
  if (isClockedIn.value) {
    const now = new Date()
    const timeStr = `${now.getHours()}:${now.getMinutes()}`
    entries.value = entries.value.map(e => e.id === 't4' ? { ...e, endTime: timeStr, status: 'Approved' } : e)
    isClockedIn.value = false
    if (clockInterval) clearInterval(clockInterval)
  } else {
    const now = new Date()
    const timeStr = `${now.getHours()}:${now.getMinutes()}`
    entries.value.push({
      id: `t_${Date.now()}`,
      employeeId: 'current_user',
      date: now.toISOString().split('T')[0],
      startTime: timeStr,
      type: 'Work',
      status: 'Pending'
    })
    isClockedIn.value = true
  }
}

const handleAddManualEntry = () => {
  const now = new Date()
  entries.value.unshift({
    id: `t_new_${Date.now()}`,
    employeeId: selectedEmployeeId.value || 'current_user',
    date: now.toISOString().split('T')[0],
    startTime: '09:00',
    endTime: '17:00',
    type: 'Work',
    status: 'Approved'
  })
}

const handleDeleteEntry = (id: string) => {
  if (confirm($t('mod.workday.delete_entry_confirm'))) {
    entries.value = entries.value.filter(e => e.id !== id)
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>
