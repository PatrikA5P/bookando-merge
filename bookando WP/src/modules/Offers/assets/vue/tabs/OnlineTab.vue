<template>
  <div class="space-y-6">
    <!-- Info Banner -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl p-5">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <h3 class="font-bold text-purple-900 mb-1">{{ $t('mod.offers.overview.online.info_title') }}</h3>
          <p class="text-sm text-purple-700">
            {{ $t('mod.offers.overview.online.info_text') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
    </div>

    <!-- Online Courses -->
    <div v-else-if="courses.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div
        v-for="course in courses"
        :key="course.id"
        class="bg-white rounded-xl border-2 border-slate-200 overflow-hidden hover:border-purple-300 hover:shadow-lg transition-all"
      >
        <!-- Course Header -->
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white p-5">
          <h3 class="text-xl font-bold mb-2">{{ course.title }}</h3>
          <p v-if="course.description" class="text-purple-100 text-sm">{{ course.description }}</p>
        </div>

        <!-- Course Details -->
        <div class="p-5 space-y-4">
          <!-- Academy Integration Status -->
          <div v-if="course.academy_course_ids?.length > 0" class="bg-green-50 border border-green-200 rounded-lg p-3">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-semibold text-green-900">{{ $t('mod.offers.overview.online.academy_integrated') }}</span>
            </div>
            <div class="text-sm text-green-700">
              {{ course.academy_course_ids.length }} {{ $t('mod.offers.overview.online.academy_courses_linked') }}
            </div>
            <div v-if="course.auto_enroll_academy" class="text-xs text-green-600 mt-1">
              ✓ {{ $t('mod.offers.overview.online.auto_enroll_enabled') }}
            </div>
          </div>

          <!-- Not Integrated -->
          <div v-else class="bg-amber-50 border border-amber-200 rounded-lg p-3">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <span class="text-sm font-medium text-amber-900">
                {{ $t('mod.offers.overview.online.not_integrated') }}
              </span>
            </div>
          </div>

          <!-- Access Duration -->
          <div class="flex items-center justify-between py-2 border-b border-slate-100">
            <span class="text-sm text-slate-600">{{ $t('mod.offers.overview.online.access_duration') }}:</span>
            <span class="font-semibold text-slate-900">
              {{ course.academy_access_duration_days
                ? `${course.academy_access_duration_days} ${$t('core.time.days')}`
                : $t('mod.offers.overview.online.lifetime_access')
              }}
            </span>
          </div>

          <!-- Price -->
          <div v-if="course.price" class="flex items-center justify-between py-2 border-b border-slate-100">
            <span class="text-sm text-slate-600">{{ $t('fields.price') }}:</span>
            <span class="text-2xl font-bold text-purple-700">
              {{ course.price }} {{ course.currency || 'CHF' }}
            </span>
          </div>

          <!-- Enrollment Stats -->
          <div v-if="course.stats" class="grid grid-cols-3 gap-3 pt-2">
            <div class="text-center">
              <div class="text-2xl font-bold text-slate-900">{{ course.stats.total_enrollments || 0 }}</div>
              <div class="text-xs text-slate-500">{{ $t('mod.offers.overview.online.total_enrollments') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">{{ course.stats.active_enrollments || 0 }}</div>
              <div class="text-xs text-slate-500">{{ $t('mod.offers.overview.online.active') }}</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">{{ course.stats.completed || 0 }}</div>
              <div class="text-xs text-slate-500">{{ $t('mod.offers.overview.online.completed') }}</div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="bg-slate-50 p-4 flex gap-2 border-t border-slate-200">
          <button
            @click="editCourse(course)"
            class="flex-1 px-4 py-2 text-sm font-medium text-purple-700 border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors"
          >
            {{ $t('core.actions.edit') }}
          </button>
          <button
            v-if="course.academy_course_ids?.length > 0"
            @click="viewAcademyCourses(course)"
            class="px-4 py-2 text-sm font-medium text-indigo-700 border border-indigo-300 rounded-lg hover:bg-indigo-50 transition-colors"
          >
            {{ $t('mod.offers.overview.online.view_academy_courses') }}
          </button>
          <button
            @click="viewEnrollments(course)"
            class="px-4 py-2 text-sm font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            {{ $t('mod.offers.overview.online.view_enrollments') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.overview.online.no_courses') }}</p>
      <button
        @click="createOnlineCourse"
        class="mt-4 px-6 py-2.5 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition-colors"
      >
        {{ $t('mod.offers.overview.online.create_first') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import httpBase from '@assets/http'

const { t: $t } = useI18n()
const http = httpBase.module('offers')

// State
const loading = ref(false)
const courses = ref<any[]>([])

// Load online courses with stats
const loadCourses = async () => {
  loading.value = true
  try {
    const response = await http.get('by-type/online')
    courses.value = response.data || []

    // Load stats for each course
    await Promise.all(
      courses.value.map(async (course) => {
        try {
          const statsResponse = await http.get(`${course.id}/stats`)
          course.stats = statsResponse.data
        } catch (error) {
          console.error(`Failed to load stats for course ${course.id}:`, error)
          course.stats = { total_enrollments: 0, active_enrollments: 0, completed: 0 }
        }
      })
    )
  } catch (error) {
    console.error('Failed to load online courses:', error)
    courses.value = []
  } finally {
    loading.value = false
  }
}

// Actions
const editCourse = (course: any) => {
  console.log('Edit online course:', course)
  // TODO: Open edit modal
}

const viewAcademyCourses = (course: any) => {
  console.log('View linked Academy courses:', course)
  // TODO: Navigate to Academy module filtered by course IDs
}

const viewEnrollments = (course: any) => {
  console.log('View enrollments:', course)
  // TODO: Open enrollments modal/page
}

const createOnlineCourse = () => {
  console.log('Create new online course')
  // TODO: Open create modal
}

// Load on mount
onMounted(() => {
  loadCourses()
})
</script>
