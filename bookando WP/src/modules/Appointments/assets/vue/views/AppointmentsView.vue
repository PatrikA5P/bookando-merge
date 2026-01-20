<template>
  <ModuleLayout
    :hero-title="$t('mod.appointments.title')"
    :hero-description="$t('mod.appointments.description')"
    :hero-icon="CalendarIcon"
    hero-gradient="bg-gradient-to-br from-blue-700 to-indigo-900"
    :show-search="true"
    :search-placeholder="$t('mod.appointments.search_placeholder')"
    :primary-action="primaryAction"
  >
    <div class="p-6 space-y-6">
      <!-- View Toggle & Filters -->
      <div class="flex flex-col md:flex-row justify-between gap-4">
        <div class="flex gap-2">
          <button
            v-for="mode in viewModes"
            :key="mode.id"
            @click="viewMode = mode.id"
            :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2', viewMode === mode.id ? 'bg-brand-600 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50']"
          >
            <component :is="mode.icon" :size="16" />
            {{ mode.label }}
          </button>
        </div>

        <div class="flex gap-2">
          <select v-model="filters.status" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="all">{{ $t('mod.appointments.all_status') }}</option>
            <option value="confirmed">{{ $t('mod.appointments.confirmed') }}</option>
            <option value="pending">{{ $t('mod.appointments.pending') }}</option>
            <option value="completed">{{ $t('mod.appointments.completed') }}</option>
            <option value="cancelled">{{ $t('mod.appointments.cancelled') }}</option>
          </select>

          <select v-model="filters.employeeId" class="px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
            <option value="all">{{ $t('mod.appointments.all_employees') }}</option>
            <option v-for="emp in mockEmployees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
          </select>
        </div>
      </div>

      <!-- Calendar View -->
      <div v-if="viewMode === 'calendar'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.appointments.week_view') }}</h3>
          <div class="flex gap-2">
            <button @click="changeWeek(-1)" class="p-2 hover:bg-slate-100 rounded-lg">
              <ChevronLeftIcon :size="20" />
            </button>
            <button @click="goToToday" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-medium">
              {{ $t('mod.appointments.today') }}
            </button>
            <button @click="changeWeek(1)" class="p-2 hover:bg-slate-100 rounded-lg">
              <ChevronRightIcon :size="20" />
            </button>
          </div>
        </div>

        <!-- Week Grid -->
        <div class="grid grid-cols-7 gap-2">
          <div v-for="(day, index) in weekDays" :key="index" class="text-center">
            <div class="text-xs font-medium text-slate-500 mb-2">{{ day.dayName }}</div>
            <div :class="['text-sm font-bold mb-2 p-2 rounded-lg', isToday(day.date) ? 'bg-brand-600 text-white' : 'text-slate-800']">
              {{ day.dayNumber }}
            </div>
            <!-- Appointments for this day -->
            <div class="space-y-1">
              <div
                v-for="apt in getAppointmentsForDay(day.date)"
                :key="apt.id"
                @click="openEditModal(apt)"
                :class="['p-2 rounded text-xs cursor-pointer hover:shadow-md transition-shadow', getStatusColorClass(apt.status)]"
              >
                <div class="font-medium truncate">{{ apt.customerName }}</div>
                <div class="text-[10px]">{{ apt.time }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-if="viewMode === 'list'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="p-4 text-left text-xs font-semibold text-slate-500 uppercase">{{ $t('mod.appointments.customer') }}</th>
              <th class="p-4 text-left text-xs font-semibold text-slate-500 uppercase">{{ $t('mod.appointments.date_time') }}</th>
              <th class="p-4 text-left text-xs font-semibold text-slate-500 uppercase">{{ $t('mod.appointments.service') }}</th>
              <th class="p-4 text-left text-xs font-semibold text-slate-500 uppercase">{{ $t('mod.appointments.employee') }}</th>
              <th class="p-4 text-left text-xs font-semibold text-slate-500 uppercase">{{ $t('common.status') }}</th>
              <th class="p-4 text-right text-xs font-semibold text-slate-500 uppercase">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="apt in filteredAppointments" :key="apt.id" class="hover:bg-slate-50">
              <td class="p-4">
                <div class="font-medium text-slate-800">{{ apt.customerName }}</div>
                <div class="text-xs text-slate-500">{{ apt.customerEmail }}</div>
              </td>
              <td class="p-4">
                <div class="text-sm text-slate-800">{{ apt.date }}</div>
                <div class="text-xs text-slate-500">{{ apt.time }}</div>
              </td>
              <td class="p-4 text-sm text-slate-700">{{ apt.service }}</td>
              <td class="p-4 text-sm text-slate-700">{{ apt.employeeName }}</td>
              <td class="p-4">
                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColorClass(apt.status)]">
                  {{ apt.status }}
                </span>
              </td>
              <td class="p-4 text-right">
                <button @click="openEditModal(apt)" class="p-2 hover:bg-slate-100 rounded-lg">
                  <MoreVerticalIcon :size="16" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Appointment Modal (simplified) -->
      <div v-if="isModalOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6">
          <h3 class="text-lg font-bold text-slate-800 mb-4">{{ editingAppointment ? $t('mod.appointments.edit_appointment') : $t('mod.appointments.new_appointment') }}</h3>

          <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.appointments.customer') }}</label>
              <input type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.appointments.date') }}</label>
              <input type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.appointments.time') }}</label>
              <input type="time" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
            </div>
            <div class="col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.appointments.service') }}</label>
              <select class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white">
                <option>Massage Therapy</option>
                <option>Yoga Session</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <button @click="closeModal" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">
              {{ $t('common.cancel') }}
            </button>
            <button @click="saveAppointment" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium">
              {{ $t('common.save') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import {
  Calendar as CalendarIcon,
  List as ListIcon,
  Plus as PlusIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
  MoreVertical as MoreVerticalIcon
} from 'lucide-vue-next'

const { t: $t } = useI18n()

// State
const viewMode = ref('calendar')
const currentDate = ref(new Date())
const filters = ref({
  status: 'all',
  employeeId: 'all'
})
const isModalOpen = ref(false)
const editingAppointment = ref<any>(null)

// Mock data
const mockEmployees = ref([
  { id: 'emp1', name: 'Sarah Jenkins' },
  { id: 'emp2', name: 'Mike Ross' },
  { id: 'emp3', name: 'Jessica Pearson' }
])

const mockAppointments = ref([
  { id: '1', customerName: 'John Doe', customerEmail: 'john@example.com', date: '2026-01-20', time: '09:00', service: 'Massage', employeeName: 'Sarah Jenkins', status: 'confirmed' },
  { id: '2', customerName: 'Jane Smith', customerEmail: 'jane@example.com', date: '2026-01-20', time: '11:00', service: 'Yoga', employeeName: 'Mike Ross', status: 'pending' },
  { id: '3', customerName: 'Bob Johnson', customerEmail: 'bob@example.com', date: '2026-01-21', time: '14:00', service: 'Consultation', employeeName: 'Jessica Pearson', status: 'confirmed' }
])

// View modes
const viewModes = ref([
  { id: 'calendar', icon: CalendarIcon, label: $t('mod.appointments.calendar_view') },
  { id: 'list', icon: ListIcon, label: $t('mod.appointments.list_view') }
])

// Primary action
const primaryAction = computed(() => ({
  label: $t('mod.appointments.new_appointment'),
  icon: PlusIcon,
  onClick: openNewModal
}))

// Week days
const weekDays = computed(() => {
  const startOfWeek = getStartOfWeek(currentDate.value)
  const days = []

  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + i)

    days.push({
      date: date.toISOString().split('T')[0],
      dayName: date.toLocaleDateString('de-DE', { weekday: 'short' }),
      dayNumber: date.getDate()
    })
  }

  return days
})

// Computed
const filteredAppointments = computed(() => {
  return mockAppointments.value.filter(apt => {
    if (filters.value.status !== 'all' && apt.status !== filters.value.status) return false
    if (filters.value.employeeId !== 'all' && apt.employeeName !== mockEmployees.value.find(e => e.id === filters.value.employeeId)?.name) return false
    return true
  })
})

// Methods
const getStartOfWeek = (date: Date) => {
  const d = new Date(date)
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  return new Date(d.setDate(diff))
}

const isToday = (dateString: string) => {
  const today = new Date().toISOString().split('T')[0]
  return dateString === today
}

const getAppointmentsForDay = (dateString: string) => {
  return filteredAppointments.value.filter(apt => apt.date === dateString)
}

const getStatusColorClass = (status: string) => {
  const map: Record<string, string> = {
    confirmed: 'bg-emerald-100 text-emerald-700',
    pending: 'bg-amber-100 text-amber-700',
    completed: 'bg-blue-100 text-blue-700',
    cancelled: 'bg-rose-100 text-rose-700'
  }
  return map[status.toLowerCase()] || 'bg-slate-100 text-slate-700'
}

const changeWeek = (direction: number) => {
  const newDate = new Date(currentDate.value)
  newDate.setDate(newDate.getDate() + (direction * 7))
  currentDate.value = newDate
}

const goToToday = () => {
  currentDate.value = new Date()
}

const openNewModal = () => {
  editingAppointment.value = null
  isModalOpen.value = true
}

const openEditModal = (appointment: any) => {
  editingAppointment.value = appointment
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  editingAppointment.value = null
}

const saveAppointment = () => {
  // Save logic here
  closeModal()
}
</script>
