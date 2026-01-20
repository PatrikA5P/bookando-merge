<template>
  <div class="flex flex-col h-full space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h3 class="font-bold text-slate-800 text-lg">{{ $t('mod.workday.absence_management') }}</h3>
      <button
        @click="isModalOpen = true"
        class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 text-sm font-medium shadow-sm flex items-center gap-2"
      >
        <PlusIcon :size="16" /> {{ $t('mod.workday.request_absence') }}
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="p-3 rounded-full bg-emerald-100 text-emerald-600">
          <PlaneIcon :size="20" />
        </div>
        <div>
          <div class="text-xs text-slate-500 font-bold uppercase">{{ $t('mod.workday.vacation_balance') }}</div>
          <div class="text-xl font-bold text-slate-800">21.5 <span class="text-xs font-normal text-slate-400">/ 25 {{ $t('mod.workday.days') }}</span></div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="p-3 rounded-full bg-rose-100 text-rose-600">
          <StethoscopeIcon :size="20" />
        </div>
        <div>
          <div class="text-xs text-slate-500 font-bold uppercase">{{ $t('mod.workday.sick_days_ytd') }}</div>
          <div class="text-xl font-bold text-slate-800">3 <span class="text-xs font-normal text-slate-400">{{ $t('mod.workday.days') }}</span></div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
          <BabyIcon :size="20" />
        </div>
        <div>
          <div class="text-xs text-slate-500 font-bold uppercase">{{ $t('mod.workday.other_leave') }}</div>
          <div class="text-xl font-bold text-slate-800">0 <span class="text-xs font-normal text-slate-400">{{ $t('mod.workday.days') }}</span></div>
        </div>
      </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex-1">
      <table class="w-full text-left">
        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase">
          <tr>
            <th class="p-4">{{ $t('mod.workday.employee') }}</th>
            <th class="p-4">{{ $t('mod.workday.type') }}</th>
            <th class="p-4">{{ $t('mod.workday.dates') }}</th>
            <th class="p-4">{{ $t('mod.workday.duration') }}</th>
            <th class="p-4">{{ $t('mod.workday.reason') }}</th>
            <th class="p-4">{{ $t('mod.workday.status') }}</th>
            <th class="p-4 text-right">{{ $t('mod.workday.action') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr v-for="req in requests" :key="req.id" class="hover:bg-slate-50">
            <td class="p-4 font-bold text-slate-700">{{ req.employeeName }}</td>
            <td class="p-4">
              <span :class="['px-2 py-1 rounded-full text-xs font-medium', getTypeClass(req.type)]">
                {{ req.type }}
              </span>
            </td>
            <td class="p-4 text-slate-600">{{ req.startDate }} <span class="text-slate-400 px-1">to</span> {{ req.endDate }}</td>
            <td class="p-4 text-slate-600">5 {{ $t('mod.workday.days') }}</td>
            <td class="p-4 text-slate-500 italic">{{ req.reason }}</td>
            <td class="p-4">
              <span :class="['flex items-center gap-1 font-bold text-xs', getStatusColorClass(req.status)]">
                <CheckCircleIcon v-if="req.status === 'Approved'" :size="12" />
                <ClockIcon v-else-if="req.status === 'Pending'" :size="12" />
                <XCircleIcon v-else :size="12" />
                {{ req.status }}
              </span>
            </td>
            <td class="p-4 text-right">
              <div v-if="req.status === 'Pending'" class="flex justify-end gap-2">
                <button class="p-1.5 bg-emerald-50 text-emerald-600 rounded hover:bg-emerald-100">
                  <CheckCircleIcon :size="16" />
                </button>
                <button class="p-1.5 bg-rose-50 text-rose-600 rounded hover:bg-rose-100">
                  <XCircleIcon :size="16" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Request Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
          <h3 class="font-bold text-lg">{{ $t('mod.workday.new_absence_request') }}</h3>
          <button @click="isModalOpen = false">
            <XCircleIcon class="text-slate-400" :size="20" />
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.workday.absence_type') }}</label>
            <select class="w-full border border-slate-300 rounded-lg px-3 py-2">
              <option value="Vacation">{{ $t('mod.workday.vacation') }}</option>
              <option value="Sick">{{ $t('mod.workday.sick') }}</option>
              <option value="NBU">{{ $t('mod.workday.nbu') }}</option>
              <option value="BU">{{ $t('mod.workday.bu') }}</option>
              <option value="Maternity">{{ $t('mod.workday.maternity') }}</option>
              <option value="Unpaid">{{ $t('mod.workday.unpaid_leave') }}</option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.workday.start_date') }}</label>
              <input type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.workday.end_date') }}</label>
              <input type="date" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.workday.reason_notes') }}</label>
            <textarea class="w-full border border-slate-300 rounded-lg px-3 py-2" rows="3"></textarea>
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <button @click="isModalOpen = false" class="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-lg">
              {{ $t('common.cancel') }}
            </button>
            <button @click="isModalOpen = false" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
              {{ $t('mod.workday.submit_request') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Plus as PlusIcon,
  Plane as PlaneIcon,
  Stethoscope as StethoscopeIcon,
  Baby as BabyIcon,
  CheckCircle as CheckCircleIcon,
  Clock as ClockIcon,
  XCircle as XCircleIcon
} from 'lucide-vue-next'

interface AbsenceRequest {
  id: string
  employeeName: string
  type: 'Vacation' | 'Sick' | 'NBU' | 'BU' | 'Maternity' | 'Unpaid'
  startDate: string
  endDate: string
  reason: string
  status: 'Pending' | 'Approved' | 'Rejected'
}

const { t: $t } = useI18n()

const isModalOpen = ref(false)

const requests = ref<AbsenceRequest[]>([
  { id: 'a1', employeeName: 'Sarah Jenkins', type: 'Vacation', startDate: '2023-11-10', endDate: '2023-11-15', reason: 'Family trip', status: 'Pending' },
  { id: 'a2', employeeName: 'Mike Ross', type: 'Sick', startDate: '2023-10-01', endDate: '2023-10-03', reason: 'Flu', status: 'Approved' }
])

const getTypeClass = (type: string) => {
  switch (type) {
    case 'Vacation': return 'bg-emerald-50 text-emerald-700'
    case 'Sick': return 'bg-rose-50 text-rose-700'
    default: return 'bg-slate-100 text-slate-700'
  }
}

const getStatusColorClass = (status: string) => {
  switch (status) {
    case 'Approved': return 'text-emerald-600'
    case 'Pending': return 'text-amber-600'
    case 'Rejected': return 'text-rose-600'
    default: return 'text-slate-600'
  }
}
</script>
