<script setup lang="ts">
import { ref } from 'vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BButton from '@/components/ui/BButton.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();

const company = ref({
  name: 'Bookando GmbH',
  email: 'info@bookando.ch',
  phone: '+41 44 123 45 67',
  street: 'Bahnhofstrasse 1',
  zip: '8001',
  city: 'Z\u00fcrich',
  country: 'CH',
  iban: 'CH93 0076 2011 6238 5295 7',
  qrIban: 'CH44 3199 9123 0008 8901 2',
  bankName: 'Z\u00fcrcher Kantonalbank',
  vatId: 'CHE-123.456.789 MWST',
});

function save() {
  toast.success(t('common.savedSuccessfully'));
}
</script>

<template>
  <div class="space-y-6">
    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-base font-semibold text-slate-900">{{ t('settings.company') }}</h3>
      </div>
      <div :class="CARD_STYLES.body" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BInput v-model="company.name" :label="t('settings.companyName')" required />
          <BInput v-model="company.email" :label="t('settings.companyEmail')" type="email" />
          <BInput v-model="company.phone" :label="t('settings.companyPhone')" type="tel" />
          <BInput v-model="company.vatId" :label="t('settings.vatId')" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="sm:col-span-2">
            <BInput v-model="company.street" :label="t('customers.street')" />
          </div>
          <BInput v-model="company.zip" :label="t('customers.zip')" />
          <BInput v-model="company.city" :label="t('customers.city')" />
          <BInput v-model="company.country" :label="t('customers.country')" />
        </div>
      </div>
    </div>

    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-base font-semibold text-slate-900">{{ t('settings.iban') }} & {{ t('settings.bankName') }}</h3>
      </div>
      <div :class="CARD_STYLES.body" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <BInput v-model="company.iban" :label="t('settings.iban')" placeholder="CH93 0076 2011 6238 5295 7" />
          <BInput v-model="company.qrIban" :label="t('settings.qrIban')" placeholder="CH44 3199 9123 0008 8901 2" />
          <BInput v-model="company.bankName" :label="t('settings.bankName')" />
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <BButton variant="primary" @click="save">{{ t('common.save') }}</BButton>
    </div>
  </div>
</template>
