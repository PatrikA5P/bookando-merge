<script setup lang="ts">
/**
 * Tools-Modul -- Berichte, Import, API, Benachrichtigungen, System
 *
 * Zentrale Werkzeugseite mit 5 Tabs:
 * - Reports: Berichte generieren und herunterladen
 * - Datenimport: CSV/Excel Import mit Validierung
 * - API & Webhooks: API-Schluessel und Webhook-Verwaltung
 * - Benachrichtigungen: Benachrichtigungsvorlagen
 * - System: Systemstatus und Wartung
 */
import { ref } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import ReportsTab from './components/ReportsTab.vue';
import DataImportTab from './components/DataImportTab.vue';
import ApiTab from './components/ApiTab.vue';
import NotificationsTab from './components/NotificationsTab.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const tabs: Tab[] = [
  { id: 'reports', label: t('tools.tabs.reports') || 'Reports' },
  { id: 'import', label: t('tools.tabs.import') || 'Datenimport' },
  { id: 'api', label: t('tools.tabs.api') || 'API & Webhooks' },
  { id: 'notifications', label: t('tools.tabs.notifications') || 'Benachrichtigungen' },
  { id: 'system', label: t('tools.tabs.system') || 'System' },
];

const activeTab = ref('reports');
</script>

<template>
  <ModuleLayout
    module-name="tools"
    title="Tools"
    :subtitle="t('tools.subtitle') || 'Werkzeuge und Hilfsmittel'"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ t('tools.systemSettings') || 'Systemeinstellungen' }}
        </button>
      </div>
    </template>

    <!-- Reports -->
    <ReportsTab v-if="activeTab === 'reports'" />

    <!-- Datenimport -->
    <DataImportTab v-else-if="activeTab === 'import'" />

    <!-- API & Webhooks -->
    <ApiTab v-else-if="activeTab === 'api'" />

    <!-- Benachrichtigungen -->
    <NotificationsTab v-else-if="activeTab === 'notifications'" />

    <!-- System -->
    <div v-else-if="activeTab === 'system'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-fuchsia-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ t('tools.system.title') || 'System' }}</h3>
          <p class="text-sm text-slate-500 mt-1">{{ t('tools.system.comingSoon') || 'Wird implementiert' }}</p>
          <p class="text-xs text-slate-400 mt-2">{{ t('tools.system.description') || 'Systemstatus, Wartung und Diagnose-Werkzeuge' }}</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
