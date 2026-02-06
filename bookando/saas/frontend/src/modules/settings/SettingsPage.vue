<script setup lang="ts">
/**
 * Einstellungen-Modul — Plugin-Konfiguration
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import GeneralTab from './components/GeneralTab.vue';
import CompanyTab from './components/CompanyTab.vue';
import IntegrationsTab from './components/IntegrationsTab.vue';
import RolesTab from './components/RolesTab.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
const activeTab = ref('general');

const tabs = computed<Tab[]>(() => [
  { id: 'general', label: t('settings.general') },
  { id: 'company', label: t('settings.company') },
  { id: 'integrations', label: t('settings.integrations') },
  { id: 'roles', label: t('settings.roles') },
  { id: 'modules', label: t('settings.modulesConfig') },
]);
</script>

<template>
  <ModuleLayout
    module-name="settings"
    :title="t('settings.title')"
    :subtitle="t('settings.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <GeneralTab v-if="activeTab === 'general'" />
    <CompanyTab v-else-if="activeTab === 'company'" />
    <IntegrationsTab v-else-if="activeTab === 'integrations'" />
    <RolesTab v-else-if="activeTab === 'roles'" />
    <div v-else class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('settings.modulesConfig') }}</h3>
        <p class="text-sm text-slate-500 mt-1">Coming soon</p>
      </div>
    </div>
  </ModuleLayout>
</template>
