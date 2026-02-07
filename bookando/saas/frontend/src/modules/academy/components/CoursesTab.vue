<script setup lang="ts">
/**
 * CoursesTab — Kurs-Grid mit Filterfunktionen
 *
 * Refactored: Nutzt AcademyCourse Domain-Typen.
 * CRUD über CourseFormPanel (SlideIn) gesteuert von AcademyPage.
 */
import { ref, computed } from 'vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import { useAcademyStore, COURSE_TYPE_LABELS, COURSE_DIFFICULTY_LABELS, COURSE_VISIBILITY_LABELS, COURSE_STATUS_LABELS, COURSE_STATUS_COLORS } from '@/stores/academy';
import type { AcademyCourse, CourseType, CourseVisibility, CourseDifficulty, CourseStatus } from '@/stores/academy';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const store = useAcademyStore();

const emit = defineEmits<{
  (e: 'edit', course: AcademyCourse): void;
  (e: 'create'): void;
}>();

const searchQuery = ref('');
const filterType = ref('');
const filterVisibility = ref('');
const filterDifficulty = ref('');

const typeOptions = [
  { value: '', label: 'Alle Typen' },
  ...Object.entries(COURSE_TYPE_LABELS).map(([v, l]) => ({ value: v, label: l })),
];
const visibilityOptions = [
  { value: '', label: 'Alle' },
  ...Object.entries(COURSE_VISIBILITY_LABELS).map(([v, l]) => ({ value: v, label: l })),
];
const difficultyOptions = [
  { value: '', label: 'Alle Stufen' },
  ...Object.entries(COURSE_DIFFICULTY_LABELS).map(([v, l]) => ({ value: v, label: l })),
];

const filteredCourses = computed(() => {
  let result = [...store.courses];
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(c => c.title.toLowerCase().includes(q) || c.description.toLowerCase().includes(q));
  }
  if (filterType.value) result = result.filter(c => c.type === filterType.value);
  if (filterVisibility.value) result = result.filter(c => c.visibility === filterVisibility.value);
  if (filterDifficulty.value) result = result.filter(c => c.difficulty === filterDifficulty.value);
  return result;
});

function getDifficultyGradient(d: CourseDifficulty): string {
  switch (d) {
    case 'BEGINNER': return 'from-emerald-400 to-teal-500';
    case 'INTERMEDIATE': return 'from-amber-400 to-orange-500';
    case 'ADVANCED': return 'from-rose-400 to-pink-600';
    default: return 'from-slate-400 to-slate-500';
  }
}

function getDifficultyDots(d: CourseDifficulty): number {
  return d === 'BEGINNER' ? 1 : d === 'INTERMEDIATE' ? 2 : 3;
}

function getStatusBadgeVariant(s: CourseStatus): 'default' | 'success' | 'warning' {
  return s === 'PUBLISHED' ? 'success' : s === 'DRAFT' ? 'default' : 'warning';
}

function getTypeBadgeVariant(type: CourseType): 'info' | 'purple' | 'warning' {
  return type === 'ONLINE' ? 'info' : type === 'IN_PERSON' ? 'purple' : 'warning';
}

function getModuleLessonCount(course: AcademyCourse): number {
  return course.modules.reduce((sum, m) => sum + m.lessons.length, 0);
}

function getCourseDuration(course: AcademyCourse): string {
  const total = course.modules.reduce((sum, m) =>
    sum + m.lessons.reduce((ls, l) => ls + (l.durationMinutes || 0), 0), 0);
  const hours = Math.floor(total / 60);
  const minutes = total % 60;
  if (hours > 0 && minutes > 0) return `${hours}h ${minutes}min`;
  if (hours > 0) return `${hours}h`;
  return `${minutes || 0}min`;
}
</script>

<template>
  <!-- Search & Filters -->
  <div class="flex flex-col gap-4 mb-6">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <div class="flex-1">
        <BSearchBar v-model="searchQuery" placeholder="Kurse suchen..." />
      </div>
      <BButton variant="primary" class="hidden md:flex" @click="emit('create')">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Neuer Kurs
      </BButton>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="sm:w-40"><BSelect v-model="filterType" :options="typeOptions" /></div>
      <div class="sm:w-40"><BSelect v-model="filterVisibility" :options="visibilityOptions" /></div>
      <div class="sm:w-40"><BSelect v-model="filterDifficulty" :options="difficultyOptions" /></div>
    </div>
  </div>

  <!-- Empty States -->
  <BEmptyState
    v-if="filteredCourses.length === 0 && !searchQuery && !filterType && !filterVisibility && !filterDifficulty"
    title="Noch keine Kurse vorhanden"
    description="Erstellen Sie Ihren ersten Kurs."
    icon="folder"
    action-label="Ersten Kurs erstellen"
    @action="emit('create')"
  />

  <BEmptyState
    v-else-if="filteredCourses.length === 0"
    title="Keine Kurse gefunden"
    description="Passen Sie Ihre Filter an."
    icon="search"
  />

  <!-- Course Grid -->
  <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div
      v-for="course in filteredCourses"
      :key="course.id"
      :class="CARD_STYLES.gridItem"
      @click="emit('edit', course)"
    >
      <!-- Gradient Header -->
      <div :class="['h-32 bg-gradient-to-br relative overflow-hidden', getDifficultyGradient(course.difficulty)]">
        <div class="absolute inset-0 flex items-center justify-center opacity-20">
          <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
        </div>
        <div class="absolute top-3 right-3">
          <BBadge :variant="getStatusBadgeVariant(course.status)">{{ COURSE_STATUS_LABELS[course.status] }}</BBadge>
        </div>
        <div class="absolute top-3 left-3">
          <BBadge :variant="getTypeBadgeVariant(course.type)">{{ COURSE_TYPE_LABELS[course.type] }}</BBadge>
        </div>
      </div>

      <!-- Body -->
      <div class="p-4 flex-1 flex flex-col">
        <h3 class="text-sm font-semibold text-slate-900 mb-1 line-clamp-2">{{ course.title }}</h3>
        <p class="text-xs text-slate-500 mb-3 line-clamp-2 flex-1">{{ course.description }}</p>

        <div class="flex items-center justify-between text-xs text-slate-500 mt-auto pt-3 border-t border-slate-100">
          <div class="flex items-center gap-1.5">
            <div class="flex gap-0.5">
              <span v-for="dot in 3" :key="dot" :class="['w-1.5 h-1.5 rounded-full', dot <= getDifficultyDots(course.difficulty) ? 'bg-rose-500' : 'bg-slate-200']" />
            </div>
            <span>{{ COURSE_DIFFICULTY_LABELS[course.difficulty] }}</span>
          </div>
          <span class="text-slate-400">{{ COURSE_VISIBILITY_LABELS[course.visibility] }}</span>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-500 mt-2">
          <div class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            <span>{{ course.participantCount }} Teilnehmer</span>
          </div>
          <div class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ getModuleLessonCount(course) }} Lektionen</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
