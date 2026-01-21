<template>
  <div class="space-y-6">
    <!-- View Controls -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4 flex-wrap">
      <!-- View Mode Toggle -->
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-slate-700">{{ $t('mod.offers.overview.kurse.view_mode') }}:</span>
        <div class="flex bg-slate-100 rounded-lg p-1">
          <button
            @click="viewMode = 'list'"
            :class="[
              'px-4 py-1.5 text-sm font-medium rounded-md transition-all',
              viewMode === 'list'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            {{ $t('mod.offers.overview.kurse.view_list') }}
          </button>
          <button
            @click="viewMode = 'grouped'"
            :class="[
              'px-4 py-1.5 text-sm font-medium rounded-md transition-all',
              viewMode === 'grouped'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            {{ $t('mod.offers.overview.kurse.view_grouped') }}
          </button>
        </div>
      </div>

      <!-- Date Range Filter -->
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-slate-700">{{ $t('mod.offers.overview.kurse.date_range') }}:</span>
        <input
          v-model="dateFrom"
          type="date"
          class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
        <span class="text-slate-500">–</span>
        <input
          v-model="dateTo"
          type="date"
          class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
        <button
          @click="applyDateFilter"
          class="px-4 py-1.5 text-sm font-medium bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors"
        >
          {{ $t('core.actions.filter.apply') }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600"></div>
    </div>

    <!-- Content -->
    <div v-else-if="courses.length > 0" class="space-y-4">
      <!-- List View -->
      <div v-if="viewMode === 'list'" class="space-y-3">
        <div
          v-for="course in courses"
          :key="course.id"
          class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg font-bold text-slate-900">{{ course.title }}</h3>
                <span
                  v-if="course.current_participants && course.max_participants"
                  class="px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-full"
                >
                  👥 {{ course.current_participants }}/{{ course.max_participants }}
                </span>
              </div>

              <p v-if="course.description" class="text-sm text-slate-600 mb-3">{{ course.description }}</p>

              <div class="flex items-center gap-4 text-sm text-slate-500">
                <span v-if="course.start_date" class="flex items-center gap-1">
                  📅 {{ formatDate(course.start_date) }}
                  <span v-if="course.start_time">{{ course.start_time }}</span>
                </span>
                <span v-if="course.price" class="font-semibold text-slate-700">
                  {{ course.price }} {{ course.currency || 'CHF' }}
                </span>
              </div>
            </div>

            <div class="flex gap-2">
              <button
                @click="viewDetails(course)"
                class="px-3 py-1.5 text-sm font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
              >
                {{ $t('core.actions.view.details') }}
              </button>
              <button
                @click="editCourse(course)"
                class="px-3 py-1.5 text-sm font-medium text-rose-700 border border-rose-300 rounded-lg hover:bg-rose-50 transition-colors"
              >
                {{ $t('core.actions.edit') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Grouped View -->
      <div v-else class="space-y-6">
        <div
          v-for="group in groupedCourses"
          :key="group.title"
          class="bg-white rounded-xl border-2 border-slate-200 overflow-hidden"
        >
          <!-- Group Header -->
          <div class="bg-gradient-to-r from-rose-700 to-pink-900 text-white p-5">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-xl font-bold mb-1">{{ group.title }}</h3>
                <p v-if="group.description" class="text-rose-100 text-sm">{{ group.description }}</p>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold">{{ group.sessions.length }}</div>
                <div class="text-xs text-rose-100">{{ $t('mod.offers.overview.kurse.sessions') }}</div>
              </div>
            </div>
          </div>

          <!-- Group Sessions -->
          <div class="divide-y divide-slate-100">
            <div
              v-for="(session, idx) in group.sessions"
              :key="idx"
              class="p-4 hover:bg-slate-50 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="text-center">
                    <div class="text-2xl font-bold text-rose-700">{{ formatDay(session.start_date) }}</div>
                    <div class="text-xs text-slate-500 uppercase">{{ formatMonth(session.start_date) }}</div>
                  </div>
                  <div>
                    <div class="font-semibold text-slate-900">
                      {{ formatWeekday(session.start_date) }}
                    </div>
                    <div class="text-sm text-slate-600">
                      {{ session.start_time }} - {{ session.end_time }}
                    </div>
                    <div v-if="session.location" class="text-xs text-slate-500 mt-1">
                      📍 {{ session.location }}
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <span
                    v-if="session.current_participants !== undefined"
                    class="px-3 py-1.5 bg-blue-50 text-blue-700 text-sm font-bold rounded-lg"
                  >
                    👥 {{ session.current_participants }}/{{ session.max_participants }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Group Actions -->
          <div class="bg-slate-50 p-4 flex gap-2 justify-end border-t border-slate-200">
            <button
              @click="viewGroupDetails(group)"
              class="px-4 py-2 text-sm font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-white transition-colors"
            >
              {{ $t('core.actions.view.details') }}
            </button>
            <button
              @click="viewParticipants(group)"
              class="px-4 py-2 text-sm font-medium text-blue-700 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors"
            >
              {{ $t('mod.offers.overview.kurse.view_participants') }}
            </button>
            <button
              @click="editGroup(group)"
              class="px-4 py-2 text-sm font-medium text-rose-700 border border-rose-300 rounded-lg hover:bg-rose-50 transition-colors"
            >
              {{ $t('core.actions.edit') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.overview.kurse.no_courses') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import httpBase from '@assets/http'

const { t: $t } = useI18n()
const http = httpBase.module('offers')

// State
const viewMode = ref<'list' | 'grouped'>('grouped')
const dateFrom = ref('')
const dateTo = ref('')
const loading = ref(false)
const courses = ref<any[]>([])

// Set default date range (current month)
const today = new Date()
const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0)
dateFrom.value = firstDay.toISOString().split('T')[0]
dateTo.value = lastDay.toISOString().split('T')[0]

// Grouped courses computation
const groupedCourses = computed(() => {
  // Group courses by title (assuming multiple sessions of same course have same title)
  const groups: Record<string, any> = {}

  courses.value.forEach(course => {
    const key = course.title
    if (!groups[key]) {
      groups[key] = {
        title: course.title,
        description: course.description,
        price: course.price,
        currency: course.currency,
        sessions: []
      }
    }
    groups[key].sessions.push(course)
  })

  // Sort sessions by date within each group
  Object.values(groups).forEach(group => {
    group.sessions.sort((a: any, b: any) => {
      return new Date(a.start_date).getTime() - new Date(b.start_date).getTime()
    })
  })

  return Object.values(groups)
})

// Load courses
const loadCourses = async () => {
  loading.value = true
  try {
    const response = await http.get('by-type/kurse')
    courses.value = response.data || []
  } catch (error) {
    console.error('Failed to load courses:', error)
    courses.value = []
  } finally {
    loading.value = false
  }
}

// Apply date filter
const applyDateFilter = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      start_date: dateFrom.value,
      end_date: dateTo.value,
      group_by: 'none'
    })
    const response = await http.get(`calendar/range?${params}`)
    courses.value = response.data?.courses || []
  } catch (error) {
    console.error('Failed to apply date filter:', error)
  } finally {
    loading.value = false
  }
}

// Actions
const viewDetails = (course: any) => {
  console.log('View course details:', course)
  // TODO: Open details modal
}

const editCourse = (course: any) => {
  console.log('Edit course:', course)
  // TODO: Open edit modal
}

const viewGroupDetails = (group: any) => {
  console.log('View group details:', group)
  // TODO: Open group details modal
}

const viewParticipants = (group: any) => {
  console.log('View participants:', group)
  // TODO: Open participants modal
}

const editGroup = (group: any) => {
  console.log('Edit group:', group)
  // TODO: Open edit modal for entire course series
}

// Date formatters
const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatDay = (dateStr: string) => {
  if (!dateStr) return ''
  return new Date(dateStr).getDate().toString()
}

const formatMonth = (dateStr: string) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('de-CH', { month: 'short' })
}

const formatWeekday = (dateStr: string) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('de-CH', { weekday: 'long' })
}

// Load on mount
onMounted(() => {
  loadCourses()
})
</script>
