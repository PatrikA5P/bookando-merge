<script setup lang="ts">
import { ref } from 'vue';
import BInput from '@/components/ui/BInput.vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();

const apiKeys = ref([
  { id: 'key1', name: 'Production', key: 'bk_live_****...****3f2a', status: 'ACTIVE', created: '2026-01-15', lastUsed: '2026-02-05' },
  { id: 'key2', name: 'Staging', key: 'bk_test_****...****8b1c', status: 'ACTIVE', created: '2026-01-20', lastUsed: '2026-02-03' },
]);

function generateKey() {
  toast.success('API Key generated');
}

function revokeKey(id: string) {
  const key = apiKeys.value.find(k => k.id === id);
  if (key) key.status = 'INACTIVE';
  toast.success('API Key revoked');
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-base font-semibold text-slate-900">{{ t('partnerhub.apiAccess') }}</h3>
      <BButton variant="primary" @click="generateKey">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New API Key
      </BButton>
    </div>

    <div class="space-y-4">
      <div v-for="apiKey in apiKeys" :key="apiKey.id" :class="CARD_STYLES.base">
        <div class="p-4">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ apiKey.name }}</h3>
              <code class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded mt-1 inline-block">{{ apiKey.key }}</code>
            </div>
            <BBadge :status="apiKey.status" dot>
              {{ apiKey.status === 'ACTIVE' ? t('common.active') : t('common.inactive') }}
            </BBadge>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-500">
            <span>Created: {{ apiKey.created }}</span>
            <div class="flex items-center gap-2">
              <span>Last used: {{ apiKey.lastUsed }}</span>
              <button
                v-if="apiKey.status === 'ACTIVE'"
                class="text-red-500 hover:text-red-700 font-medium"
                @click="revokeKey(apiKey.id)"
              >
                Revoke
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
