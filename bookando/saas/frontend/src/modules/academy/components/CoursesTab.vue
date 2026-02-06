<script setup lang="ts">
/**
 * CoursesTab — Kurs-Grid mit Filterfunktionen
 *
 * Features:
 * - Responsive Grid (1/2/3 Spalten)
 * - Kurskarten mit Gradient, Badges, Schwierigkeitsindikator
 * - Such- und Filterfunktionen (Typ, Sichtbarkeit, Schwierigkeit)
 * - Erstellen/Bearbeiten via CourseModal
 * - Leerer Zustand mit BEmptyState
 */
import { ref, computed, watch } from 'vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import CourseModal from './CourseModal.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAcademyStore } from '@/stores/academy';
import type { Course, CourseType, CourseVisibility, CourseDifficulty } from '@/stores/academy';
import { CARD_STYLES, BADGE_STYLES, BUTTON_STYLES } from '@/design';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const academyStore = useAcademyStore();

// ---- Props / Emits ----
const showModal = defineModel<boolean>('showModal', { default: false });

// ---- State ----
const searchQuery = ref('');
const filterType = ref('');
const filterVisibility = ref('');
const filterDifficulty = ref('');
const editingCourse = ref<Course | null>(null);

// ---- Filter Options ----
const typeOptions = [
  { value: '', label: 'Alle Typen' },
  { value: 'ONLINE', label: 'Online' },
  { value: 'IN_PERSON', label: 'Vor Ort' },
  { value: 'BLENDED', label: 'Blended' },
];

const visibilityOptions = [
  { value: '', label: 'Alle' },
  { value: 'PRIVATE', label: 'Privat' },
  { value: 'INTERNAL', label: 'Intern' },
  { value: 'PUBLIC', label: 'Oeffentlich' },
];

const difficultyOptions = [
  { value: '', label: 'Alle Stufen' },
  { value: 'BEGINNER', label: 'Einsteiger' },
  { value: 'INTERMEDIATE', label: 'Fortgeschritten' },
  { value: 'ADVANCED', label: 'Experte' },
];

// ---- Computed ----
const filteredCourses = computed(() => {
  let result = [...academyStore.courses];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(c =>
      c.title.toLowerCase().includes(q) ||
      c.description.toLowerCase().includes(q)
    );
  }

  if (filterType.value) {
    result = result.filter(c => c.type === filterType.value);
  }

  if (filterVisibility.value) {
    result = result.filter(c => c.visibility === filterVisibility.value);
  }

  if (filterDifficulty.value) {
    result = result.filter(c => c.difficulty === filterDifficulty.value);
  }

  return result;
});

// ---- Helpers ----
function getDifficultyGradient(difficulty: CourseDifficulty): string {
  switch (difficulty) {
    case 'BEGINNER': return 'from-emerald-400 to-teal-500';
    case 'INTERMEDIATE': return 'from-amber-400 to-orange-500';
    case 'ADVANCED': return 'from-rose-400 to-pink-600';
    default: return 'from-slate-400 to-slate-500';
  }
}

function getDifficultyLabel(difficulty: CourseDifficulty): string {
  switch (difficulty) {
    case 'BEGINNER': return 'Einsteiger';
    case 'INTERMEDIATE': return 'Fortgeschritten';
    case 'ADVANCED': return 'Experte';
    default: return difficulty;
  }
}

function getDifficultyDots(difficulty: CourseDifficulty): number {
  switch (difficulty) {
    case 'BEGINNER': return 1;
    case 'INTERMEDIATE': return 2;
    case 'ADVANCED': return 3;
    default: return 0;
  }
}

function getTypeLabel(type: CourseType): string {
  switch (type) {
    case 'ONLINE': return 'Online';
    case 'IN_PERSON': return 'Vor Ort';
    case 'BLENDED': return 'Blended';
    default: return type;
  }
}

function getTypeBadgeVariant(type: CourseType): 'info' | 'purple' | 'warning' {
  switch (type) {
    case 'ONLINE': return 'info';
    case 'IN_PERSON': return 'purple';
    case 'BLENDED': return 'warning';
    default: return 'info';
  }
}

function getVisibilityLabel(visibility: CourseVisibility): string {
  switch (visibility) {
    case 'PRIVATE': return 'Privat';
    case 'INTERNAL': return 'Intern';
    case 'PUBLIC': return 'Oeffentlich';
    default: return visibility;
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'DRAFT': return 'Entwurf';
    case 'PUBLISHED': return 'Veroeffentlicht';
    case 'ARCHIVED': return 'Archiviert';
    default: return status;
  }
}

function getStatusBadgeVariant(status: string): 'default' | 'success' | 'warning' {
  switch (status) {
    case 'DRAFT': return 'default';
    case 'PUBLISHED': return 'success';
    case 'ARCHIVED': return 'warning';
    default: return 'default';
  }
}

function getCurriculumDuration(course: Course): string {
  const totalMinutes = course.curriculum.reduce((sum, item) => sum + item.duration, 0);
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;
  if (hours > 0 && minutes > 0) return `${hours}h ${minutes}min`;
  if (hours > 0) return `${hours}h`;
  return `${minutes}min`;
}

// ---- Actions ----
function openCreate() {
  editingCourse.value = null;
  showModal.value = true;
}

function openEdit(course: Course) {
  editingCourse.value = course;
  showModal.value = true;
}

function handleModalClose() {
  showModal.value = false;
  editingCourse.value = null;
}

function handleSave(data: Partial<Course>, publish: boolean) {
  if (editingCourse.value) {
    const status = publish ? 'PUBLISHED' : (data.status || editingCourse.value.status);
    academyStore.updateCourse(editingCourse.value.id, { ...data, status } as Partial<Course>);
    toast.success('Kurs aktualisiert');
  } else {
    const status = publish ? 'PUBLISHED' : 'DRAFT';
    academyStore.addCourse({
      title: data.title || '',
      description: data.description || '',
      type: data.type || 'ONLINE',
      visibility: data.visibility || 'INTERNAL',
      difficulty: data.difficulty || 'BEGINNER',
      categoryId: data.categoryId || '',
      image: data.image,
      certificateEnabled: data.certificateEnabled || false,
      badgeId: data.badgeId,
      curriculum: data.curriculum || [],
      status,
    });
    toast.success(publish ? 'Kurs veroeffentlicht' : 'Entwurf gespeichert');
  }
  handleModalClose();
}
</script>

<template>
  <!-- Search & Filters -->
  <div class="flex flex-col gap-4 mb-6">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <div class="flex-1">
        <BSearchBar
          v-model="searchQuery"
          placeholder="Kurse suchen..."
        />
      </div>
      <BButton
        variant="primary"
        class="hidden md:flex"
        @click="openCreate"
      >
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Neuer Kurs
      </BButton>
    </div>

    <!-- Filter Row -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="sm:w-40">
        <BSelect
          v-model="filterType"
          :options="typeOptions"
          placeholder="Typ"
        />
      </div>
      <div class="sm:w-40">
        <BSelect
          v-model="filterVisibility"
          :options="visibilityOptions"
          placeholder="Sichtbarkeit"
        />
      </div>
      <div class="sm:w-40">
        <BSelect
          v-model="filterDifficulty"
          :options="difficultyOptions"
          placeholder="Schwierigkeit"
        />
      </div>
    </div>
  </div>

  <!-- Empty State -->
  <BEmptyState
    v-if="filteredCourses.length === 0 && !searchQuery && !filterType && !filterVisibility && !filterDifficulty"
    title="Noch keine Kurse vorhanden"
    description="Erstellen Sie Ihren ersten Kurs, um mit dem Academy-Modul zu starten."
    icon="folder"
    action-label="Ersten Kurs erstellen"
    @action="openCreate"
  />

  <BEmptyState
    v-else-if="filteredCourses.length === 0"
    title="Keine Kurse gefunden"
    description="Passen Sie Ihre Suchkriterien oder Filter an."
    icon="search"
  />

  <!-- Course Grid -->
  <div
    v-else
    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"
  >
    <div
      v-for="course in filteredCourses"
      :key="course.id"
      :class="CARD_STYLES.gridItem"
      @click="openEdit(course)"
    >
      <!-- Image / Gradient Header -->
      <div
        :class="[
          'h-32 bg-gradient-to-br relative overflow-hidden',
          getDifficultyGradient(course.difficulty),
        ]"
      >
        <!-- Course Icon -->
        <div class="absolute inset-0 flex items-center justify-center opacity-20">
          <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>

        <!-- Status Badge (top-right) -->
        <div class="absolute top-3 right-3">
          <BBadge :variant="getStatusBadgeVariant(course.status)">
            {{ getStatusLabel(course.status) }}
          </BBadge>
        </div>

        <!-- Type Badge (top-left) -->
        <div class="absolute top-3 left-3">
          <BBadge :variant="getTypeBadgeVariant(course.type)">
            {{ getTypeLabel(course.type) }}
          </BBadge>
        </div>
      </div>

      <!-- Card Body -->
      <div class="p-4 flex-1 flex flex-col">
        <h3 class="text-sm font-semibold text-slate-900 mb-1 line-clamp-2">
          {{ course.title }}
        </h3>
        <p class="text-xs text-slate-500 mb-3 line-clamp-2 flex-1">
          {{ course.description }}
        </p>

        <!-- Meta Row -->
        <div class="flex items-center justify-between text-xs text-slate-500 mt-auto pt-3 border-t border-slate-100">
          <!-- Difficulty Indicator -->
          <div class="flex items-center gap-1.5">
            <div class="flex gap-0.5">
              <span
                v-for="dot in 3"
                :key="dot"
                :class="[
                  'w-1.5 h-1.5 rounded-full',
                  dot <= getDifficultyDots(course.difficulty) ? 'bg-rose-500' : 'bg-slate-200',
                ]"
              />
            </div>
            <span>{{ getDifficultyLabel(course.difficulty) }}</span>
          </div>

          <!-- Visibility -->
          <span class="text-slate-400">
            {{ getVisibilityLabel(course.visibility) }}
          </span>
        </div>

        <!-- Bottom Row -->
        <div class="flex items-center justify-between text-xs text-slate-500 mt-2">
          <!-- Participants -->
          <div class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ course.participantCount }} Teilnehmer</span>
          </div>

          <!-- Duration -->
          <div class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ getCurriculumDuration(course) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Course Modal -->
  <CourseModal
    :show="showModal"
    :course="editingCourse"
    @close="handleModalClose"
    @save="handleSave"
  />
</template>
