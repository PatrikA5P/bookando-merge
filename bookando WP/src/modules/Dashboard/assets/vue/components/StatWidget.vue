<template>
  <div class="h-full flex flex-col justify-between">
    <div class="flex justify-between items-start">
      <div>
        <p class="text-sm font-medium text-slate-500 mb-1">{{ title }}</p>
        <h3 class="text-2xl font-bold text-slate-900">{{ value }}</h3>
      </div>
      <div :class="['p-2 rounded-lg bg-slate-50', colorClass]">
        <component :is="iconComponent" :size="20" />
      </div>
    </div>
    <div class="mt-4 flex items-center gap-2">
      <span :class="['flex items-center text-xs font-semibold', isPositive ? 'text-emerald-600' : 'text-rose-600']">
        <ArrowUpRightIcon v-if="isPositive" :size="14" class="mr-1" />
        <ArrowDownRightIcon v-else :size="14" class="mr-1" />
        {{ change }}
      </span>
      <span class="text-xs text-slate-400">{{ $t('dashboard.vs_last_month') }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowUpRight as ArrowUpRightIcon,
  ArrowDownRight as ArrowDownRightIcon,
  Banknote as BanknoteIcon,
  Users as UsersIcon,
  Calendar as CalendarIcon,
  Clock as ClockIcon
} from 'lucide-vue-next'

interface Props {
  title: string
  value: string
  change: string
  isPositive: boolean
  iconName: string
  colorClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  colorClass: 'text-slate-600'
})

const { t: $t } = useI18n()

const iconComponent = computed(() => {
  const iconMap: Record<string, any> = {
    banknote: BanknoteIcon,
    users: UsersIcon,
    calendar: CalendarIcon,
    clock: ClockIcon
  }
  return iconMap[props.iconName] || BanknoteIcon
})
</script>
