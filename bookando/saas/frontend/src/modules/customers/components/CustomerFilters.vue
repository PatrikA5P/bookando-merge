<script setup lang="ts">
/**
 * CustomerFilters — Erweiterte Filter
 */
import { CARD_STYLES, BADGE_STYLES, BUTTON_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const model = defineModel<{
  status: string[];
  gender: string;
  country: string;
  city: string;
  tags: string[];
}>({ required: true });

function toggleStatus(status: string) {
  const idx = model.value.status.indexOf(status);
  if (idx !== -1) {
    model.value.status.splice(idx, 1);
  } else {
    model.value.status.push(status);
  }
}

function resetFilters() {
  model.value.status = [];
  model.value.gender = '';
  model.value.country = '';
  model.value.city = '';
  model.value.tags = [];
}

const hasActiveFilters = () => {
  return model.value.status.length > 0 || model.value.gender || model.value.country || model.value.city || model.value.tags.length > 0;
};
</script>

<template>
  <div :class="[CARD_STYLES.base, 'animate-slide-down']">
    <div :class="CARD_STYLES.bodyCompact">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Status -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ t('common.sortBy') }}</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="status in ['ACTIVE', 'BLOCKED', 'DELETED']"
              :key="status"
              :class="[
                'px-2.5 py-1 rounded-full text-xs font-medium transition-colors border',
                model.status.includes(status)
                  ? 'bg-brand-50 text-brand-700 border-brand-200'
                  : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50',
              ]"
              @click="toggleStatus(status)"
            >
              {{ status === 'ACTIVE' ? t('customers.status.active') : status === 'BLOCKED' ? t('customers.status.blocked') : t('customers.status.deleted') }}
            </button>
          </div>
        </div>

        <!-- Geschlecht -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ t('customers.gender') }}</label>
          <select
            v-model="model.gender"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
          >
            <option value="">{{ t('common.selectAll') }}</option>
            <option value="male">{{ t('customers.male') }}</option>
            <option value="female">{{ t('customers.female') }}</option>
            <option value="other">{{ t('customers.other') }}</option>
          </select>
        </div>

        <!-- Land -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ t('customers.country') }}</label>
          <select
            v-model="model.country"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
          >
            <option value="">{{ t('common.selectAll') }}</option>
            <option value="CH">Schweiz</option>
            <option value="DE">Deutschland</option>
            <option value="AT">Österreich</option>
            <option value="LI">Liechtenstein</option>
          </select>
        </div>

        <!-- Stadt -->
        <div>
          <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ t('customers.city') }}</label>
          <input
            v-model="model.city"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500"
            :placeholder="t('customers.city') + '...'"
          />
        </div>
      </div>

      <!-- Reset -->
      <div v-if="hasActiveFilters()" class="mt-3 flex justify-end">
        <button
          class="text-xs text-brand-600 hover:text-brand-700 font-medium"
          @click="resetFilters"
        >
          {{ t('common.reset') }}
        </button>
      </div>
    </div>
  </div>
</template>
