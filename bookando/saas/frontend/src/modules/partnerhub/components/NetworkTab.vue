<script setup lang="ts">
import { ref } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import { useAppStore } from '@/stores/app';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const appStore = useAppStore();

interface Partner {
  id: string;
  name: string;
  type: string;
  status: 'ACTIVE' | 'INACTIVE' | 'PENDING';
  revenueShare: number;
  syncedAt: string;
}

const searchQuery = ref('');

const partners = ref<Partner[]>([
  { id: 'p1', name: 'Wellness Oase Zürich', type: 'Spa & Wellness', status: 'ACTIVE', revenueShare: 15, syncedAt: '2026-02-05' },
  { id: 'p2', name: 'FitStudio Basel', type: 'Fitness', status: 'ACTIVE', revenueShare: 10, syncedAt: '2026-02-04' },
  { id: 'p3', name: 'Beauty Corner Bern', type: 'Kosmetik', status: 'PENDING', revenueShare: 12, syncedAt: '' },
]);
</script>

<template>
  <div class="space-y-4">
    <BSearchBar v-model="searchQuery" :placeholder="t('common.search') + '...'" />

    <div v-if="partners.length === 0">
      <BEmptyState
        :title="t('partnerhub.noPartners')"
        :description="t('partnerhub.noPartnersDesc')"
        icon="users"
        :action-label="t('partnerhub.newPartner')"
      />
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="partner in partners"
        :key="partner.id"
        :class="CARD_STYLES.base"
        class="hover:shadow-md transition-shadow cursor-pointer"
      >
        <div class="p-4">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ partner.name }}</h3>
              <p class="text-xs text-slate-500">{{ partner.type }}</p>
            </div>
            <BBadge :status="partner.status" dot>
              {{ partner.status === 'ACTIVE' ? t('common.active') : partner.status === 'PENDING' ? t('common.pending') : t('common.inactive') }}
            </BBadge>
          </div>
          <div class="flex items-center justify-between text-xs text-slate-500">
            <span>{{ t('partnerhub.revenueShare') }}: {{ partner.revenueShare }}%</span>
            <span v-if="partner.syncedAt">{{ appStore.formatDate(partner.syncedAt) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
