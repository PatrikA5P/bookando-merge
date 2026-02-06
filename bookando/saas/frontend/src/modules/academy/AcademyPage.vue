<script setup lang="ts">
/**
 * Academy-Modul — Kurse, Training & Zertifizierungen
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import CoursesTab from './components/CoursesTab.vue';
import LessonsTab from './components/LessonsTab.vue';
import { useI18n } from '@/composables/useI18n';
import { useAcademyStore } from '@/stores/academy';
import { BUTTON_STYLES } from '@/design';

const { t } = useI18n();
const store = useAcademyStore();

const activeTab = ref('courses');

const tabs = computed<Tab[]>(() => [
  { id: 'courses', label: t('academy.courses'), badge: store.courses?.length },
  { id: 'lessons', label: t('academy.lessons') },
  { id: 'badges', label: t('academy.badges') },
  { id: 'quizzes', label: t('academy.quizzes') },
]);
</script>

<template>
  <ModuleLayout
    module-name="academy"
    :title="t('academy.title')"
    :subtitle="t('academy.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="activeTab === 'courses'"
    :fab-label="t('academy.newCourse')"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
        @click="activeTab = 'courses'"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('academy.newCourse') }}
      </button>
    </template>

    <!-- Kurse -->
    <CoursesTab v-if="activeTab === 'courses'" />

    <!-- Lektionen -->
    <LessonsTab v-else-if="activeTab === 'lessons'" />

    <!-- Badges (Platzhalter) -->
    <div v-else-if="activeTab === 'badges'" class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto bg-rose-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">{{ t('academy.badges') }}</h3>
        <p class="text-sm text-slate-500 mt-1">Coming soon</p>
      </div>
    </div>

    <!-- Quizze (Platzhalter) -->
    <div v-else class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto bg-rose-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">{{ t('academy.quizzes') }}</h3>
        <p class="text-sm text-slate-500 mt-1">Coming soon</p>
      </div>
    </div>
  </ModuleLayout>
</template>
