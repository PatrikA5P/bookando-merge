<template>
  <ModuleLayout
    :hero-title="$t('mod.partnerhub.title')"
    :hero-description="$t('mod.partnerhub.description')"
    :hero-icon="NetworkIcon"
    hero-gradient="bg-gradient-to-br from-indigo-700 to-indigo-950"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:active-tab="handleTabChange"
    :show-search="false"
  >
    <!-- Network Tab -->
    <div v-if="activeTab === 'network'" class="flex flex-col h-full animate-fadeIn">
      <div class="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-bold text-lg text-slate-800">{{ $t('mod.partnerhub.connected_partners') }}</h3>
        <button class="text-sm text-slate-500 hover:text-indigo-600 flex items-center gap-1">
          <RefreshCwIcon :size="14" /> {{ $t('mod.partnerhub.sync_all') }}
        </button>
      </div>
      <div class="flex-1 overflow-auto">
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="p-4 text-xs text-slate-500 uppercase font-semibold">{{ $t('mod.partnerhub.company') }}</th>
              <th class="p-4 text-xs text-slate-500 uppercase font-semibold">{{ $t('mod.partnerhub.type') }}</th>
              <th class="p-4 text-xs text-slate-500 uppercase font-semibold">{{ $t('mod.partnerhub.gdpr_status') }}</th>
              <th class="p-4 text-xs text-slate-500 uppercase font-semibold">{{ $t('mod.partnerhub.rev_share') }}</th>
              <th class="p-4 text-xs text-slate-500 uppercase font-semibold text-right">{{ $t('common.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="partner in mockPartners" :key="partner.id" class="hover:bg-slate-50">
              <td class="p-4 font-medium text-slate-900">{{ partner.companyName }}</td>
              <td class="p-4 text-slate-600 text-sm">{{ partner.type }}</td>
              <td class="p-4">
                <span
                  v-if="partner.gdprSigned"
                  class="flex items-center gap-1.5 text-emerald-600 text-xs font-medium bg-emerald-50 px-2 py-1 rounded-full w-fit"
                >
                  <CheckCircleIcon :size="12" /> {{ $t('mod.partnerhub.signed_avv') }}
                </span>
                <span
                  v-else
                  class="flex items-center gap-1.5 text-amber-600 text-xs font-medium bg-amber-50 px-2 py-1 rounded-full w-fit"
                >
                  <AlertTriangleIcon :size="12" /> {{ $t('mod.partnerhub.pending') }}
                </span>
              </td>
              <td class="p-4 text-slate-600 text-sm">{{ partner.revenueShare }}%</td>
              <td class="p-4 text-right">
                <span
                  :class="[
                    'inline-block w-2 h-2 rounded-full mr-2',
                    partner.status === 'Active' ? 'bg-emerald-500' : 'bg-slate-300'
                  ]"
                ></span>
                <span class="text-sm text-slate-600">{{ partner.status }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- GDPR Tab -->
    <div v-if="activeTab === 'gdpr'" class="p-6 animate-fadeIn space-y-6">
      <div class="border-b border-slate-100 pb-4">
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <ShieldIcon class="text-emerald-500" :size="20" /> {{ $t('mod.partnerhub.compliance_article_28') }}
        </h3>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
            <h4 class="font-bold text-slate-800 text-sm mb-2">{{ $t('mod.partnerhub.avv_title') }}</h4>
            <p class="text-xs text-slate-500 mb-3">{{ $t('mod.partnerhub.avv_description') }}</p>
            <button class="text-indigo-600 text-sm font-medium hover:underline flex items-center gap-1">
              <FileTextIcon :size="14" /> {{ $t('mod.partnerhub.view_template') }}
            </button>
          </div>

          <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
            <div class="flex justify-between items-center mb-2">
              <h4 class="font-bold text-slate-800 text-sm">{{ $t('mod.partnerhub.data_minimization') }}</h4>
              <div class="w-8 h-4 bg-emerald-500 rounded-full relative cursor-pointer">
                <div class="absolute right-0.5 top-0.5 w-3 h-3 bg-white rounded-full"></div>
              </div>
            </div>
            <p class="text-xs text-slate-500">{{ $t('mod.partnerhub.data_minimization_desc') }}</p>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200">
          <div class="p-3 bg-slate-50 border-b border-slate-200 text-sm font-bold text-slate-700">
            {{ $t('mod.partnerhub.audit_trail') }}
          </div>
          <div class="divide-y divide-slate-100">
            <div
              v-for="(log, i) in mockAuditTrail"
              :key="i"
              class="p-3 text-sm flex justify-between"
            >
              <div>
                <span class="font-medium text-slate-900">{{ log.action }}</span>
                <span class="text-slate-400 mx-2">-</span>
                <span class="text-slate-600">{{ log.partner }}</span>
              </div>
              <span class="text-slate-400 text-xs">{{ log.time }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- API Tab -->
    <div v-if="activeTab === 'api'" class="flex flex-col h-full animate-fadeIn">
      <div class="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-bold text-lg text-slate-800">{{ $t('mod.partnerhub.api_developers') }}</h3>
        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">
          + {{ $t('mod.partnerhub.new_key') }}
        </button>
      </div>
      <div class="p-6 space-y-8">
        <div>
          <h4 class="font-bold text-slate-700 text-sm mb-3">{{ $t('mod.partnerhub.active_api_keys') }}</h4>
          <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                <tr>
                  <th class="p-3">{{ $t('common.name') }}</th>
                  <th class="p-3">{{ $t('mod.partnerhub.prefix') }}</th>
                  <th class="p-3">{{ $t('mod.partnerhub.last_used') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-sm">
                <tr v-for="key in mockApiKeys" :key="key.id">
                  <td class="p-3 font-medium">{{ key.name }}</td>
                  <td class="p-3 font-mono text-slate-500 bg-slate-50 w-fit px-2 rounded">{{ key.prefix }}</td>
                  <td class="p-3 text-slate-500">{{ key.lastUsed }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <h4 class="font-bold text-slate-700 text-sm mb-3">{{ $t('mod.partnerhub.webhooks') }}</h4>
          <div class="p-4 border border-slate-200 rounded-lg bg-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <WebhookIcon class="text-indigo-500" :size="20" />
              <span class="text-sm font-mono text-slate-700">https://api.yourservice.com/webhooks/bookando</span>
            </div>
            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full font-bold">
              {{ $t('mod.partnerhub.active') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Settings Tab -->
    <div v-if="activeTab === 'settings'" class="p-6 animate-fadeIn">
      <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
        <SettingsIcon :size="48" class="mx-auto mb-4 text-slate-300" />
        <h3 class="text-lg font-bold text-slate-800 mb-2">{{ $t('mod.partnerhub.settings_title') }}</h3>
        <p class="text-slate-600">{{ $t('mod.partnerhub.settings_description') }}</p>
      </div>
    </div>
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import {
  Network as NetworkIcon,
  Shield as ShieldIcon,
  Code as CodeIcon,
  Settings as SettingsIcon,
  Share2 as Share2Icon,
  RefreshCw as RefreshCwIcon,
  CheckCircle as CheckCircleIcon,
  AlertTriangle as AlertTriangleIcon,
  FileText as FileTextIcon,
  Webhook as WebhookIcon
} from 'lucide-vue-next'

const { t: $t } = useI18n()

// State
const activeTab = ref('network')

// Tabs definition
const tabs = ref([
  { id: 'network', icon: Share2Icon, label: $t('mod.partnerhub.tabs.network') },
  { id: 'gdpr', icon: ShieldIcon, label: $t('mod.partnerhub.tabs.gdpr') },
  { id: 'api', icon: CodeIcon, label: $t('mod.partnerhub.tabs.api') },
  { id: 'settings', icon: SettingsIcon, label: $t('mod.partnerhub.tabs.settings') }
])

// Mock Data
const mockPartners = ref([
  { id: 'P-001', companyName: 'Yoga Studio Downtown', type: 'Service Provider', status: 'Active', gdprSigned: true, revenueShare: 15 },
  { id: 'P-002', companyName: 'Fitness Equipment Co', type: 'Reseller', status: 'Active', gdprSigned: true, revenueShare: 10 },
  { id: 'P-003', companyName: 'Wellness Apps Inc', type: 'Service Provider', status: 'Pending', gdprSigned: false, revenueShare: 20 }
])

const mockApiKeys = ref([
  { id: 'key_live_1', name: 'Production App', prefix: 'pk_live_...', created: '2023-09-15', lastUsed: '2 mins ago', status: 'Active' },
  { id: 'key_test_1', name: 'Development', prefix: 'pk_test_...', created: '2023-10-01', lastUsed: 'Never', status: 'Active' }
])

const mockAuditTrail = ref([
  { action: 'Data Shared', partner: 'Yoga Studio', time: '10:32 AM' },
  { action: 'AVV Signed', partner: 'Fitness Co', time: 'Yesterday' },
  { action: 'Revoked', partner: 'Wellness Apps', time: '2 days ago' }
])

// Methods
const handleTabChange = (tabId: string) => {
  activeTab.value = tabId
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>
