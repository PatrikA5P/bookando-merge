<script setup lang="ts">
import { ref } from 'vue';
import BToggle from '@/components/ui/BToggle.vue';
import BButton from '@/components/ui/BButton.vue';
import { useI18n } from '@/composables/useI18n';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();

const dataMinimization = ref(true);
const auditTrailEnabled = ref(true);
const autoDeleteInactive = ref(false);
const retentionDays = ref(365);

const auditLog = ref([
  { id: '1', action: 'Partner data exported', user: 'Admin', date: '2026-02-05 14:30', partner: 'Wellness Oase' },
  { id: '2', action: 'DPA signed', user: 'Admin', date: '2026-02-04 10:15', partner: 'FitStudio Basel' },
  { id: '3', action: 'Data access request', user: 'System', date: '2026-02-03 09:00', partner: 'Beauty Corner' },
]);
</script>

<template>
  <div class="space-y-6">
    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-base font-semibold text-slate-900">{{ t('partnerhub.gdpr') }} — DSG/DSGVO</h3>
      </div>
      <div :class="CARD_STYLES.body" class="space-y-4">
        <BToggle v-model="dataMinimization" :label="t('partnerhub.dataMinimization')" description="Nur notwendige Daten mit Partnern teilen" />
        <BToggle v-model="auditTrailEnabled" :label="t('partnerhub.auditTrail')" description="Alle Datenzugriffe protokollieren" />
        <BToggle v-model="autoDeleteInactive" label="Auto-Löschung" description="Inaktive Partner-Daten automatisch löschen" />
      </div>
    </div>

    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-base font-semibold text-slate-900">{{ t('partnerhub.auditTrail') }}</h3>
      </div>
      <div class="divide-y divide-slate-100">
        <div v-for="entry in auditLog" :key="entry.id" class="px-4 py-3 flex items-center justify-between">
          <div>
            <p class="text-sm text-slate-700">{{ entry.action }}</p>
            <p class="text-xs text-slate-500">{{ entry.partner }} · {{ entry.user }}</p>
          </div>
          <span class="text-xs text-slate-400">{{ entry.date }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
