<script setup lang="ts">
/**
 * Academy-Modul — Kurse, Quizze, Ausbildungskarten, Badges
 *
 * Refactored: Domain-Typen, neue Tabs (Quizze, Ausbildungskarten),
 * CourseFormPanel + TrainingCardFormPanel (SlideIn statt BModal)
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import CoursesTab from './components/CoursesTab.vue';
import LessonsTab from './components/LessonsTab.vue';
import BadgesTab from './components/BadgesTab.vue';
import QuizzesTab from './components/QuizzesTab.vue';
import TrainingCardsTab from './components/TrainingCardsTab.vue';
import CourseFormPanel from './components/CourseFormPanel.vue';
import TrainingCardFormPanel from './components/TrainingCardFormPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { useAcademyStore } from '@/stores/academy';
import { useTrainingCardsStore } from '@/stores/training-cards';
import type { AcademyCourse } from '@/stores/academy';
import type { TrainingCardTemplate } from '@/stores/training-cards';

const { t } = useI18n();
const academyStore = useAcademyStore();
const trainingCardsStore = useTrainingCardsStore();

const activeTab = ref('courses');

const tabs = computed<Tab[]>(() => [
  { id: 'courses', label: t('academy.courses'), badge: academyStore.courseCount },
  { id: 'quizzes', label: t('academy.quizzes'), badge: academyStore.totalQuizCount },
  { id: 'training-cards', label: 'Ausbildungskarten', badge: trainingCardsStore.templateCount },
  { id: 'badges', label: t('academy.badges'), badge: academyStore.badgeCount },
]);

// ── CourseFormPanel State ─────────────────────────────────────────────────
const showCoursePanel = ref(false);
const editingCourse = ref<AcademyCourse | null>(null);

function handleCreateCourse() {
  editingCourse.value = null;
  showCoursePanel.value = true;
}

function handleEditCourse(course: AcademyCourse) {
  editingCourse.value = course;
  showCoursePanel.value = true;
}

function handleCourseSaved() {
  showCoursePanel.value = false;
  editingCourse.value = null;
}

// ── TrainingCardFormPanel State ───────────────────────────────────────────
const showTrainingCardPanel = ref(false);
const editingTemplate = ref<TrainingCardTemplate | null>(null);

function handleCreateTemplate() {
  editingTemplate.value = null;
  showTrainingCardPanel.value = true;
}

function handleEditTemplate(template: TrainingCardTemplate) {
  editingTemplate.value = template;
  showTrainingCardPanel.value = true;
}

function handleTemplateSaved() {
  showTrainingCardPanel.value = false;
  editingTemplate.value = null;
}

// ── FAB ──────────────────────────────────────────────────────────────────
const fabLabel = computed(() => {
  switch (activeTab.value) {
    case 'courses': return t('academy.newCourse');
    case 'training-cards': return 'Neue Vorlage';
    default: return '';
  }
});

const showFab = computed(() => ['courses', 'training-cards'].includes(activeTab.value));

function handleFabClick() {
  if (activeTab.value === 'courses') handleCreateCourse();
  else if (activeTab.value === 'training-cards') handleCreateTemplate();
}
</script>

<template>
  <ModuleLayout
    module-name="academy"
    :title="t('academy.title')"
    :subtitle="t('academy.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="showFab"
    :fab-label="fabLabel"
    @tab-change="(id: string) => activeTab = id"
    @fab-click="handleFabClick"
  >
    <template #header-actions>
      <button
        v-if="showFab"
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
        @click="handleFabClick"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ fabLabel }}
      </button>
    </template>

    <!-- Kurse -->
    <CoursesTab
      v-if="activeTab === 'courses'"
      @edit="handleEditCourse"
      @create="handleCreateCourse"
    />

    <!-- Quizze -->
    <QuizzesTab v-else-if="activeTab === 'quizzes'" />

    <!-- Ausbildungskarten -->
    <TrainingCardsTab
      v-else-if="activeTab === 'training-cards'"
      @edit="handleEditTemplate"
      @create="handleCreateTemplate"
    />

    <!-- Badges -->
    <BadgesTab v-else-if="activeTab === 'badges'" />
  </ModuleLayout>

  <!-- Course Form Panel (SlideIn) -->
  <CourseFormPanel
    v-model="showCoursePanel"
    :course="editingCourse"
    @saved="handleCourseSaved"
    @deleted="handleCourseSaved"
  />

  <!-- Training Card Form Panel (SlideIn) -->
  <TrainingCardFormPanel
    v-model="showTrainingCardPanel"
    :template="editingTemplate"
    @saved="handleTemplateSaved"
    @deleted="handleTemplateSaved"
  />
</template>
