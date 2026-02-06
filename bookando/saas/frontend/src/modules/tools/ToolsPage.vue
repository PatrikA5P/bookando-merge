<script setup lang="ts">
/**
 * Tools-Modul — Werkzeuge & Hilfsmittel
 *
 * Tabs: Dienstplanung, Urlaub, Zeiterfassung, Kursplanung,
 *       Benachrichtigungen, Barrierefreiheit
 * TODO: SchedulerService, VacationService, TimeTrackingService,
 *       CoursePlanningService, NotificationService, AccessibilityService
 */
import { ref } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';

const tabs: Tab[] = [
  { id: 'scheduler', label: 'Dienstplanung' },
  { id: 'vacation', label: 'Urlaub' },
  { id: 'timetracking', label: 'Zeiterfassung' },
  { id: 'course-planning', label: 'Kursplanung' },
  { id: 'notifications', label: 'Benachrichtigungen' },
  { id: 'accessibility', label: 'Barrierefreiheit' },
];

const activeTab = ref('scheduler');
</script>

<template>
  <ModuleLayout
    module-name="tools"
    title="Tools"
    subtitle="Werkzeuge und Hilfsmittel"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <!-- Dienstplanung -->
    <div v-if="activeTab === 'scheduler'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-fuchsia-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">Dienstplanung</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
          <p class="text-xs text-slate-400 mt-2">Automatische Dienstplanung mit Regel-Engine und Optimierung</p>
        </div>
      </div>
    </div>

    <!-- Platzhalter für andere Tabs -->
    <div v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-fuchsia-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ tabs.find(t => t.id === activeTab)?.label }}</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
