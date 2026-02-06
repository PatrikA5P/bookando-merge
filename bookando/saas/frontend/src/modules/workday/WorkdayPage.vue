<script setup lang="ts">
/**
 * Arbeitstag-Modul — Dienstpläne, Schichten & Zeiterfassung
 *
 * Tabs: Dienstplan, Schichten, Zeiterfassung, Urlaub, Überstunden
 * TODO: Drag & Drop Dienstplanung, Schichtvorlagen,
 *       Live-Timer, Urlaubsanträge, Überstundensaldi
 */
import { ref } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';

const tabs: Tab[] = [
  { id: 'schedule', label: 'Dienstplan' },
  { id: 'shifts', label: 'Schichten' },
  { id: 'timetracking', label: 'Zeiterfassung' },
  { id: 'vacation', label: 'Urlaub' },
  { id: 'overtime', label: 'Überstunden' },
];

const activeTab = ref('schedule');
</script>

<template>
  <ModuleLayout
    module-name="workday"
    title="Arbeitstag"
    subtitle="Dienstpläne, Schichten und Zeiterfassung"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <!-- Dienstplan -->
    <div v-if="activeTab === 'schedule'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">Dienstplan</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
          <p class="text-xs text-slate-400 mt-2">Drag &amp; Drop Dienstplanung mit Wochen- und Monatsansicht</p>
        </div>
      </div>
    </div>

    <!-- Platzhalter für andere Tabs -->
    <div v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-amber-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ tabs.find(t => t.id === activeTab)?.label }}</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
