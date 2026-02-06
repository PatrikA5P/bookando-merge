<script setup lang="ts">
/**
 * Academy-Modul — Kurse, Training & Zertifizierungen
 *
 * Tabs: Kurse, Pakete, Trainingskarten, Lektionen, Quizze
 * TODO: Kurseditor, Fortschrittsverfolgung, Meilensteine,
 *       Finance-Integration (Rechnungsstellung), Zertifikate
 */
import { ref } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';

const tabs: Tab[] = [
  { id: 'courses', label: 'Kurse' },
  { id: 'packages', label: 'Pakete' },
  { id: 'training-cards', label: 'Trainingskarten' },
  { id: 'lessons', label: 'Lektionen' },
  { id: 'quizzes', label: 'Quizze' },
];

const activeTab = ref('courses');
</script>

<template>
  <ModuleLayout
    module-name="academy"
    title="Academy"
    subtitle="Kurse, Training und Zertifizierungen"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    fab-label="Neuen Kurs erstellen"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Neuer Kurs
      </button>
    </template>

    <!-- Kurse -->
    <div v-if="activeTab === 'courses'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-rose-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">Kurse</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
          <p class="text-xs text-slate-400 mt-2">Kursverwaltung mit Lektionen, Themen und Fortschrittsverfolgung</p>
        </div>
      </div>
    </div>

    <!-- Platzhalter für andere Tabs -->
    <div v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-rose-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ tabs.find(t => t.id === activeTab)?.label }}</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
