<script setup lang="ts">
/**
 * PortalAcademyTab — Kundenseitiger Academy-Zugang
 *
 * Zeigt dem Kunden seine eingeschriebenen Kurse,
 * Lernfortschritt und verfuegbare Badges.
 */
import { computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import {
  useAcademyStore,
  COURSE_DIFFICULTY_LABELS,
  COURSE_TYPE_LABELS,
} from '@/stores/academy';
import type { AcademyCourse } from '@/stores/academy';
import { CARD_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const store = useAcademyStore();

// Customer-visible published courses
const availableCourses = computed(() =>
  store.courses.filter(c => c.status === 'PUBLISHED')
);

// Enrolled courses (from enrollments)
const enrolledCourseIds = computed(() =>
  new Set(store.enrollments.map(e => e.courseId))
);

const enrolledCourses = computed(() =>
  availableCourses.value.filter(c => enrolledCourseIds.value.has(c.id))
);

const otherCourses = computed(() =>
  availableCourses.value.filter(c => !enrolledCourseIds.value.has(c.id))
);

function getEnrollmentProgress(courseId: string): number {
  const enrollment = store.enrollments.find(e => e.courseId === courseId);
  return enrollment?.progress ?? 0;
}

function getDifficultyDots(d: string): number {
  return d === 'BEGINNER' ? 1 : d === 'INTERMEDIATE' ? 2 : 3;
}

function getLessonCount(course: AcademyCourse): number {
  return course.modules.reduce((sum, m) => sum + m.lessons.length, 0);
}
</script>

<template>
  <div>
    <!-- Enrolled Courses -->
    <div v-if="enrolledCourses.length > 0" class="mb-8">
      <h3 class="text-sm font-semibold text-slate-900 mb-4">Meine Kurse</h3>
      <div :class="GRID_STYLES.cols3">
        <div
          v-for="course in enrolledCourses"
          :key="course.id"
          :class="CARD_STYLES.hover"
          class="overflow-hidden cursor-pointer"
        >
          <!-- Progress Header -->
          <div class="h-2 bg-slate-100">
            <div
              class="h-full bg-gradient-to-r from-brand-500 to-cyan-500 transition-all duration-500"
              :style="{ width: `${getEnrollmentProgress(course.id)}%` }"
            />
          </div>

          <div class="p-4">
            <div class="flex items-start justify-between mb-2">
              <h4 class="text-sm font-semibold text-slate-900 line-clamp-2 flex-1">{{ course.title }}</h4>
              <BBadge variant="info" size="sm" class="ml-2 shrink-0">
                {{ COURSE_TYPE_LABELS[course.type] }}
              </BBadge>
            </div>

            <p class="text-xs text-slate-500 line-clamp-2 mb-3">{{ course.description }}</p>

            <!-- Progress -->
            <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
              <span>Fortschritt</span>
              <span class="font-medium text-brand-600">{{ getEnrollmentProgress(course.id) }}%</span>
            </div>

            <!-- Stats -->
            <div class="flex items-center gap-4 text-xs text-slate-400 pt-3 border-t border-slate-100">
              <div class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                {{ getLessonCount(course) }} Lektionen
              </div>
              <div class="flex items-center gap-1">
                <div class="flex gap-0.5">
                  <span v-for="dot in 3" :key="dot" :class="['w-1.5 h-1.5 rounded-full', dot <= getDifficultyDots(course.difficulty) ? 'bg-amber-500' : 'bg-slate-200']" />
                </div>
                {{ COURSE_DIFFICULTY_LABELS[course.difficulty] }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Available Courses -->
    <div v-if="otherCourses.length > 0">
      <h3 class="text-sm font-semibold text-slate-900 mb-4">Verfuegbare Kurse</h3>
      <div :class="GRID_STYLES.cols3">
        <div
          v-for="course in otherCourses"
          :key="course.id"
          :class="CARD_STYLES.base"
          class="p-4"
        >
          <h4 class="text-sm font-semibold text-slate-900 mb-1 line-clamp-2">{{ course.title }}</h4>
          <p class="text-xs text-slate-500 line-clamp-2 mb-3">{{ course.description }}</p>

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-1 text-xs text-slate-400">
              <div class="flex gap-0.5">
                <span v-for="dot in 3" :key="dot" :class="['w-1.5 h-1.5 rounded-full', dot <= getDifficultyDots(course.difficulty) ? 'bg-amber-500' : 'bg-slate-200']" />
              </div>
              {{ COURSE_DIFFICULTY_LABELS[course.difficulty] }}
            </div>
            <BBadge variant="info" size="sm">{{ COURSE_TYPE_LABELS[course.type] }}</BBadge>
          </div>
        </div>
      </div>
    </div>

    <!-- Badges Section -->
    <div v-if="store.badges.length > 0" class="mt-8">
      <h3 class="text-sm font-semibold text-slate-900 mb-4">Badges</h3>
      <div class="flex flex-wrap gap-4">
        <div
          v-for="badge in store.badges"
          :key="badge.id"
          class="flex flex-col items-center gap-2 p-3 rounded-xl bg-slate-50 border border-slate-100 w-24"
        >
          <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
            </svg>
          </div>
          <span class="text-xs font-medium text-slate-700 text-center leading-tight">{{ badge.name }}</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BEmptyState
      v-if="availableCourses.length === 0 && store.badges.length === 0"
      title="Keine Kurse verfuegbar"
      description="Es sind aktuell keine Kurse fuer Sie freigeschaltet."
      icon="folder"
    />
  </div>
</template>
