<template>
  <div class="flex-1 flex flex-col h-full">
    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.workday.course_planner') }}</h3>
      <div class="flex gap-2">
        <div class="flex bg-white rounded-lg border border-slate-200 p-1">
          <button class="px-3 py-1 text-xs font-medium bg-slate-100 rounded text-slate-800">
            {{ $t('mod.workday.week') }}
          </button>
          <button class="px-3 py-1 text-xs font-medium text-slate-500 hover:bg-slate-50">
            {{ $t('mod.workday.month') }}
          </button>
        </div>
        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
          {{ $t('mod.workday.save_schedule') }}
        </button>
      </div>
    </div>

    <!-- Main Grid Area -->
    <div class="flex-1 flex overflow-hidden border border-slate-200 rounded-xl">
      <!-- Sidebar with Draggable Resources -->
      <div class="w-48 border-r border-slate-200 p-4 bg-slate-50 overflow-y-auto flex-shrink-0">
        <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">{{ $t('mod.workday.draggable_courses') }}</h4>
        <div class="space-y-2">
          <div
            v-for="course in courses"
            :key="course"
            class="p-3 bg-white border border-slate-200 rounded-lg shadow-sm cursor-move hover:border-brand-400 text-sm font-medium text-slate-700"
          >
            {{ course }}
          </div>
        </div>

        <h4 class="text-xs font-bold text-slate-500 uppercase mt-6 mb-3">{{ $t('mod.workday.instructors') }}</h4>
        <div class="space-y-2">
          <div
            v-for="instructor in instructors"
            :key="instructor"
            class="flex items-center gap-2 p-2 hover:bg-slate-100 rounded cursor-pointer"
          >
            <div class="w-6 h-6 bg-brand-100 rounded-full text-brand-600 flex items-center justify-center text-xs font-bold">
              {{ instructor[0] }}
            </div>
            <span class="text-sm text-slate-600">{{ instructor }}</span>
          </div>
        </div>
      </div>

      <!-- Schedule Grid -->
      <div class="flex-1 overflow-auto bg-white relative">
        <div class="min-w-[800px]">
          <!-- Header Days -->
          <div class="grid grid-cols-8 border-b border-slate-200 sticky top-0 bg-white z-10">
            <div class="p-3 text-xs font-bold text-slate-400 border-r">{{ $t('mod.workday.time_room') }}</div>
            <div
              v-for="day in weekDays"
              :key="day"
              class="p-3 text-center text-sm font-bold text-slate-700 border-r bg-slate-50"
            >
              {{ day }}
            </div>
          </div>

          <!-- Room Rows -->
          <div v-for="room in rooms" :key="room" class="grid grid-cols-8 border-b border-slate-100 min-h-[100px]">
            <div class="p-3 text-xs font-medium text-slate-500 border-r bg-slate-50/50">{{ room }}</div>

            <!-- Day Cells -->
            <div
              v-for="(day, dayIndex) in weekDays"
              :key="day"
              class="border-r border-slate-50 relative p-1 hover:bg-slate-50 transition-colors"
            >
              <!-- Sample Scheduled Classes -->
              <div
                v-if="dayIndex === 1 && room.includes('Room A')"
                class="absolute top-2 left-2 right-2 bg-blue-100 border border-blue-200 text-blue-700 p-2 rounded text-xs cursor-pointer"
              >
                <div class="font-bold">Yoga Basics</div>
                <div class="opacity-75">09:00 - 10:30</div>
              </div>
              <div
                v-if="dayIndex === 3 && room.includes('Spin')"
                class="absolute top-10 left-2 right-2 bg-purple-100 border border-purple-200 text-purple-700 p-2 rounded text-xs cursor-pointer"
              >
                <div class="font-bold">Spin Class</div>
                <div class="opacity-75">18:00 - 19:00</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

const courses = ref(['Yoga Basics', 'HIIT Advanced', 'Meditation', 'Spin Class'])
const instructors = ref(['Sarah J.', 'Mike R.', 'Emma W.'])
const weekDays = ref(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])
const rooms = ref(['Room A (Large)', 'Room B (Quiet)', 'Spin Studio'])
</script>
