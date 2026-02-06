<script setup lang="ts">
import { ref } from 'vue';
import BSelect from '@/components/ui/BSelect.vue';
import BToggle from '@/components/ui/BToggle.vue';
import { useI18n } from '@/composables/useI18n';
import { useAppStore } from '@/stores/app';
import { CARD_STYLES, BUTTON_STYLES } from '@/design';

const { t, locale, setLocale, availableLocales, localeLabels } = useI18n();
const appStore = useAppStore();

const darkMode = ref(false);
const notifications = ref(true);
const compactMode = ref(false);

const timezoneOptions = [
  { label: 'Europe/Zurich', value: 'Europe/Zurich' },
  { label: 'Europe/Berlin', value: 'Europe/Berlin' },
  { label: 'Europe/Vienna', value: 'Europe/Vienna' },
  { label: 'UTC', value: 'UTC' },
];

const dateFormatOptions = [
  { label: 'DD.MM.YYYY', value: 'DD.MM.YYYY' },
  { label: 'MM/DD/YYYY', value: 'MM/DD/YYYY' },
  { label: 'YYYY-MM-DD', value: 'YYYY-MM-DD' },
];

const currencyOptions = [
  { label: 'CHF', value: 'CHF' },
  { label: 'EUR', value: 'EUR' },
  { label: 'USD', value: 'USD' },
];

const selectedTimezone = ref('Europe/Zurich');
const selectedDateFormat = ref('DD.MM.YYYY');
const selectedCurrency = ref('CHF');
</script>

<template>
  <div class="space-y-6">
    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-base font-semibold text-slate-900">{{ t('settings.language') }}</h3>
      </div>
      <div :class="CARD_STYLES.body" class="space-y-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <button
            v-for="loc in availableLocales"
            :key="loc"
            :class="[
              'px-4 py-3 rounded-lg border-2 text-sm font-medium transition-all',
              locale === loc
                ? 'border-brand-500 bg-brand-50 text-brand-700'
                : 'border-slate-200 hover:border-slate-300 text-slate-600',
            ]"
            @click="setLocale(loc)"
          >
            {{ localeLabels[loc] }}
          </button>
        </div>
      </div>
    </div>

    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-base font-semibold text-slate-900">{{ t('settings.timezone') }} & {{ t('settings.dateFormat') }}</h3>
      </div>
      <div :class="CARD_STYLES.body" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <BSelect v-model="selectedTimezone" :label="t('settings.timezone')" :options="timezoneOptions" />
        <BSelect v-model="selectedDateFormat" :label="t('settings.dateFormat')" :options="dateFormatOptions" />
        <BSelect v-model="selectedCurrency" :label="t('settings.currency')" :options="currencyOptions" />
      </div>
    </div>

    <div :class="CARD_STYLES.base">
      <div :class="CARD_STYLES.header">
        <h3 class="text-base font-semibold text-slate-900">{{ t('settings.general') }}</h3>
      </div>
      <div :class="CARD_STYLES.body" class="space-y-4">
        <BToggle v-model="notifications" :label="t('tools.notifications')" description="E-Mail- und Push-Benachrichtigungen" />
        <BToggle v-model="compactMode" label="Compact Mode" description="Kompaktere Darstellung der Oberfl\u00e4che" />
        <BToggle v-model="darkMode" label="Dark Mode" description="Dunkles Farbschema (Coming soon)" :disabled="true" />
      </div>
    </div>
  </div>
</template>
