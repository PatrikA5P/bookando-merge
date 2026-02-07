<script setup lang="ts">
/**
 * Partner Hub-Modul — Partner-Management & DSGVO
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import NetworkTab from './components/NetworkTab.vue';
import GdprTab from './components/GdprTab.vue';
import PartnerApiTab from './components/PartnerApiTab.vue';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';

const { t } = useI18n();
const designStore = useDesignStore();
const activeTab = ref('network');

const tabs = computed<Tab[]>(() => [
  { id: 'network', label: t('partnerhub.network') },
  { id: 'gdpr', label: t('partnerhub.gdpr') },
  { id: 'api', label: t('partnerhub.apiAccess') },
  { id: 'settings', label: t('partnerhub.hubSettings') },
]);
</script>

<template>
  <ModuleLayout
    module-name="partnerhub"
    :title="t('partnerhub.title')"
    :subtitle="t('partnerhub.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="activeTab === 'network'"
    :fab-label="t('partnerhub.newPartner')"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        {{ t('partnerhub.syncAll') }}
      </button>
    </template>

    <NetworkTab v-if="activeTab === 'network'" />
    <GdprTab v-else-if="activeTab === 'gdpr'" />
    <PartnerApiTab v-else-if="activeTab === 'api'" />

    <!-- Settings placeholder -->
    <div v-else class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <h3 class="text-sm font-semibold text-slate-900">{{ t('partnerhub.hubSettings') }}</h3>
        <p class="text-sm text-slate-500 mt-1">Coming soon</p>
      </div>
    </div>
  </ModuleLayout>
</template>
