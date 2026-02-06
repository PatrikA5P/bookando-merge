<script setup lang="ts">
/**
 * WidgetCustomizer — Dashboard-Anpassung
 */
import { CARD_STYLES, BUTTON_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

defineProps<{
  allWidgets: { id: string; title: string; size: string }[];
  activeIds: string[];
}>();

const emit = defineEmits<{
  (e: 'toggle', id: string): void;
  (e: 'close'): void;
}>();
</script>

<template>
  <div :class="[CARD_STYLES.base, 'animate-slide-down']">
    <div :class="CARD_STYLES.headerCompact">
      <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold text-slate-900">{{ t('dashboard.widgets') }}</h3>
        <button
          class="p-1 rounded-lg hover:bg-slate-100 text-slate-400"
          @click="emit('close')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    <div :class="CARD_STYLES.bodyCompact">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <button
          v-for="widget in allWidgets"
          :key="widget.id"
          :class="[
            'p-3 rounded-lg border text-left transition-all duration-200',
            activeIds.includes(widget.id)
              ? 'border-brand-300 bg-brand-50 text-brand-700'
              : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300',
          ]"
          @click="emit('toggle', widget.id)"
        >
          <div class="flex items-center gap-2">
            <div
              :class="[
                'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                activeIds.includes(widget.id)
                  ? 'border-brand-600 bg-brand-600'
                  : 'border-slate-300',
              ]"
            >
              <svg
                v-if="activeIds.includes(widget.id)"
                class="w-3 h-3 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-sm font-medium">{{ widget.title }}</span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
