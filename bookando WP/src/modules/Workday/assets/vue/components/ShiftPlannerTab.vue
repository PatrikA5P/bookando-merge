<template>
  <div class="flex flex-col h-full">
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-4">
      <div class="flex items-center gap-4 bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
        <button class="p-1 hover:bg-slate-100 rounded text-slate-600">
          <ChevronLeftIcon :size="20" />
        </button>
        <div class="text-sm font-bold text-slate-800 w-32 text-center">
          {{ $t('mod.workday.oct_23_29') }}
        </div>
        <button class="p-1 hover:bg-slate-100 rounded text-slate-600">
          <ChevronRightIcon :size="20" />
        </button>
      </div>
      <div class="flex gap-2">
        <button class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50 text-slate-600">
          {{ $t('mod.workday.load_template') }}
        </button>
        <button class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium hover:bg-slate-50 text-slate-600">
          {{ $t('mod.workday.copy_last_week') }}
        </button>
        <button class="px-3 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 shadow-sm">
          {{ $t('mod.workday.publish') }}
        </button>
      </div>
    </div>

    <!-- Grid -->
    <div class="flex-1 bg-white rounded-xl border border-slate-200 shadow-sm overflow-auto">
      <table class="w-full border-collapse min-w-[800px]">
        <thead class="bg-slate-50 sticky top-0 z-10">
          <tr>
            <th class="p-4 text-left text-xs font-bold text-slate-500 uppercase border-b border-r border-slate-200 w-48 bg-slate-50 z-20 sticky left-0">
              {{ $t('mod.workday.employee') }}
            </th>
            <th v-for="day in weekDays" :key="day.dateStr" class="p-3 text-center border-b border-slate-200 min-w-[100px]">
              <div class="text-xs font-medium text-slate-500">{{ day.dayName }}</div>
              <div class="text-sm font-bold text-slate-800">{{ day.dayNum }}</div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="emp in mockEmployees" :key="emp.id">
            <td class="p-4 border-b border-r border-slate-200 bg-white sticky left-0 z-10">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                  {{ emp.firstName[0] }}{{ emp.lastName[0] }}
                </div>
                <div>
                  <div class="font-medium text-sm text-slate-900">{{ emp.firstName }} {{ emp.lastName }}</div>
                  <div class="text-xs text-slate-500">{{ emp.position }}</div>
                </div>
              </div>
            </td>
            <td v-for="day in weekDays" :key="day.dateStr" class="p-1 border-b border-slate-100 text-center h-16 hover:bg-slate-50 transition-colors">
              <ShiftBadge
                v-if="getShiftFor(emp.id, day.dateStr)"
                :type="getShiftFor(emp.id, day.dateStr)!.type"
                :start="getShiftFor(emp.id, day.dateStr)!.startTime"
                :end="getShiftFor(emp.id, day.dateStr)!.endTime"
              />
              <div v-else class="w-full h-full rounded border-2 border-dashed border-transparent hover:border-slate-200 flex items-center justify-center opacity-0 hover:opacity-100 cursor-pointer">
                <PlusIcon :size="14" class="text-slate-400" />
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Legend -->
    <div class="mt-4 flex gap-4 text-xs text-slate-500">
      <div class="flex items-center gap-1">
        <div class="w-3 h-3 bg-sky-50 border border-sky-200 rounded"></div> {{ $t('mod.workday.early_shift') }}
      </div>
      <div class="flex items-center gap-1">
        <div class="w-3 h-3 bg-amber-50 border border-amber-200 rounded"></div> {{ $t('mod.workday.late_shift') }}
      </div>
      <div class="flex items-center gap-1">
        <div class="w-3 h-3 bg-indigo-50 border border-indigo-200 rounded"></div> {{ $t('mod.workday.night_shift') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft as ChevronLeftIcon, ChevronRight as ChevronRightIcon, Plus as PlusIcon } from 'lucide-vue-next'
import ShiftBadge from './ShiftBadge.vue'

interface WorkShift {
  id: string
  employeeId: string
  date: string
  type: 'Early' | 'Late' | 'Night' | 'Off'
  startTime: string
  endTime: string
}

interface Employee {
  id: string
  firstName: string
  lastName: string
  position: string
}

const { t: $t } = useI18n()

const mockEmployees = ref<Employee[]>([
  { id: 'e1', firstName: 'Sarah', lastName: 'Jenkins', position: 'Therapist' },
  { id: 'e2', firstName: 'Mike', lastName: 'Ross', position: 'Instructor' },
  { id: 'e3', firstName: 'Jessica', lastName: 'Pearson', position: 'Manager' }
])

const shifts = ref<WorkShift[]>([
  { id: 's1', employeeId: 'e1', date: '2023-10-24', type: 'Early', startTime: '08:00', endTime: '16:00' },
  { id: 's2', employeeId: 'e2', date: '2023-10-24', type: 'Late', startTime: '12:00', endTime: '20:00' }
])

const currentDate = ref(new Date())

const weekDays = computed(() => {
  const days = []
  for (let i = 0; i < 7; i++) {
    const d = new Date(currentDate.value)
    d.setDate(d.getDate() - d.getDay() + 1 + i) // Start Monday
    days.push({
      dateStr: d.toISOString().split('T')[0],
      dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
      dayNum: d.getDate()
    })
  }
  return days
})

const getShiftFor = (empId: string, dateStr: string): WorkShift | undefined => {
  return shifts.value.find(s => s.employeeId === empId && s.date === dateStr)
}
</script>
