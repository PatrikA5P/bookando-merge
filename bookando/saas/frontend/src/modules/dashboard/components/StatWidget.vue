<script setup lang="ts">
/**
 * StatWidget -- KPI stat card
 *
 * Displays a single KPI with value, trend indicator, and icon.
 * Matches reference: title, value, change %, trend arrow, "vs last month" label.
 */
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';

const props = defineProps<{
  title: string;
  value: string;
  change: string;
  isPositive: boolean;
  icon: 'banknote' | 'users' | 'calendar' | 'clock';
  colorClass?: string;
}>();

const { t } = useI18n();

const iconColor = computed(() => props.colorClass || 'text-slate-600');
</script>

<template>
  <div class="h-full flex flex-col justify-between">
    <div class="flex justify-between items-start">
      <div>
        <p class="text-sm font-medium text-slate-500 mb-1">{{ title }}</p>
        <h3 class="text-2xl font-bold text-slate-900">{{ value }}</h3>
      </div>
      <div :class="['p-2 rounded-lg bg-slate-50', iconColor]">
        <!-- Banknote icon -->
        <svg v-if="icon === 'banknote'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <!-- Users icon -->
        <svg v-else-if="icon === 'users'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <!-- Calendar icon -->
        <svg v-else-if="icon === 'calendar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <!-- Clock icon -->
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
    </div>

    <!-- Trend indicator -->
    <div class="mt-4 flex items-center gap-2">
      <span
        :class="[
          'flex items-center text-xs font-semibold',
          isPositive ? 'text-emerald-600' : 'text-rose-600',
        ]"
      >
        <!-- Arrow up-right -->
        <svg v-if="isPositive" class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7" />
        </svg>
        <!-- Arrow down-right -->
        <svg v-else class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10" />
        </svg>
        {{ change }}
      </span>
      <span class="text-xs text-slate-400">{{ t('dashboard.vsLastMonth') }}</span>
    </div>
  </div>
</template>
