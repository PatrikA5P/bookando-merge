<script setup lang="ts">
/**
 * Einstellungen-Modul — Plugin-Konfiguration
 *
 * Tabs: Allgemein, Buchung, Firma, Arbeitszeiten, Benachrichtigungen,
 *       Zahlungen, Integrationen, Events
 * TODO: Einstellungen pro Tenant, Cloud-Webhooks, OAuth-Konfiguration
 */
import { ref } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';

const tabs: Tab[] = [
  { id: 'general', label: 'Allgemein' },
  { id: 'booking', label: 'Buchung' },
  { id: 'company', label: 'Firma' },
  { id: 'hours', label: 'Arbeitszeiten' },
  { id: 'notifications', label: 'Benachrichtigungen' },
  { id: 'payments', label: 'Zahlungen' },
  { id: 'integrations', label: 'Integrationen' },
  { id: 'events', label: 'Events' },
];

const activeTab = ref('general');
</script>

<template>
  <ModuleLayout
    module-name="settings"
    title="Einstellungen"
    subtitle="Plugin-Konfiguration und Verwaltung"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <!-- Allgemein -->
    <div v-if="activeTab === 'general'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-slate-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">Allgemeine Einstellungen</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
          <p class="text-xs text-slate-400 mt-2">Sprache, Zeitzone, Datumsformat und allgemeine Konfiguration</p>
        </div>
      </div>
    </div>

    <!-- Platzhalter für andere Tabs -->
    <div v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-slate-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ tabs.find(t => t.id === activeTab)?.label }}</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
