<script setup lang="ts">
/**
 * Angebote-Modul — Dienstleistungen & Pakete
 *
 * Tabs: Dienstleistungen, Pakete, Preisgestaltung
 * TODO: Kalenderansicht, Academy-Bridge, Cloud-Webhooks,
 *       Preisregeln, Verfügbarkeitskonfiguration
 */
import { ref } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';

const tabs: Tab[] = [
  { id: 'services', label: 'Dienstleistungen' },
  { id: 'packages', label: 'Pakete' },
  { id: 'pricing', label: 'Preisgestaltung' },
];

const activeTab = ref('services');
</script>

<template>
  <ModuleLayout
    module-name="offers"
    title="Angebote"
    subtitle="Dienstleistungen und Pakete verwalten"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    fab-label="Neues Angebot erstellen"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Neues Angebot
      </button>
    </template>

    <!-- Dienstleistungen -->
    <div v-if="activeTab === 'services'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">Dienstleistungen</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
          <p class="text-xs text-slate-400 mt-2">Verwaltung aller buchbaren Dienstleistungen mit Preisen und Dauer</p>
        </div>
      </div>
    </div>

    <!-- Platzhalter für andere Tabs -->
    <div v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-blue-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ tabs.find(t => t.id === activeTab)?.label }}</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
