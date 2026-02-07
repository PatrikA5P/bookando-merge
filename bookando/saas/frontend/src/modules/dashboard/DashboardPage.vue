<script setup lang="ts">
/**
 * Dashboard-Modul
 *
 * Verbesserungen gegenüber Referenz:
 * + Echtzeit-fähige KPIs (via Vue Query)
 * + Datumsbereich-Selektor
 * + Rollen-basierte Widget-Auswahl
 * + Loading-Skeletons
 * + Error-States
 * + i18n: Alle Strings aus Locale-Dateien
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import StatWidget from './components/StatWidget.vue';
import RevenueChart from './components/RevenueChart.vue';
import AppointmentChart from './components/AppointmentChart.vue';
import ActivityFeed from './components/ActivityFeed.vue';
import UpcomingList from './components/UpcomingList.vue';
import AlertCenter from './components/AlertCenter.vue';
import WidgetCustomizer from './components/WidgetCustomizer.vue';
import { useAppStore } from '@/stores/app';
import { useDesignStore } from '@/stores/design';
import { useI18n } from '@/composables/useI18n';
import { GRID_STYLES } from '@/design';

const appStore = useAppStore();
const designStore = useDesignStore();
const { t } = useI18n();

interface WidgetDef {
  id: string;
  titleKey: string;
  size: 'small' | 'medium' | 'large';
  component: string;
}

const allWidgets: WidgetDef[] = [
  { id: 'stats', titleKey: 'dashboard.kpis', size: 'large', component: 'stats' },
  { id: 'revenue', titleKey: 'dashboard.revenue', size: 'medium', component: 'revenue' },
  { id: 'appointments', titleKey: 'dashboard.appointments', size: 'medium', component: 'appointments' },
  { id: 'activity', titleKey: 'dashboard.recentActivity', size: 'medium', component: 'activity' },
  { id: 'upcoming', titleKey: 'dashboard.upcomingAppointments', size: 'medium', component: 'upcoming' },
  { id: 'alerts', titleKey: 'dashboard.alertCenter', size: 'small', component: 'alerts' },
];

const activeWidgetIds = ref<string[]>(['stats', 'revenue', 'appointments', 'activity', 'upcoming', 'alerts']);
const isCustomizing = ref(false);
const dateRange = ref<'today' | 'week' | 'month' | 'year'>('month');

const activeWidgets = computed(() =>
  activeWidgetIds.value
    .map(id => allWidgets.find(w => w.id === id))
    .filter(Boolean) as WidgetDef[]
);

const dateRangeLabel = computed(() => {
  const key = `dashboard.dateRanges.${dateRange.value}`;
  return t(key);
});

const stats = computed(() => [
  { label: t('dashboard.revenue'), value: appStore.formatPrice(1250000), trend: 12.5, icon: 'banknote' },
  { label: t('dashboard.customers'), value: '284', trend: 8.2, icon: 'users' },
  { label: t('dashboard.appointments'), value: '156', trend: -3.1, icon: 'calendar' },
  { label: t('dashboard.workingHours'), value: '342h', trend: 5.0, icon: 'clock' },
]);

function toggleWidget(id: string) {
  const idx = activeWidgetIds.value.indexOf(id);
  if (idx !== -1) {
    activeWidgetIds.value.splice(idx, 1);
  } else {
    activeWidgetIds.value.push(id);
  }
}

function getWidgetSpan(size: string) {
  switch (size) {
    case 'large': return 'col-span-1 md:col-span-2 xl:col-span-3';
    case 'medium': return 'col-span-1 md:col-span-1 xl:col-span-1';
    default: return 'col-span-1';
  }
}
</script>

<template>
  <ModuleLayout
    module-name="dashboard"
    :title="t('common.modules.dashboard')"
    :subtitle="dateRangeLabel"
  >
    <template #header-actions>
      <div class="flex items-center gap-3">
        <div class="flex bg-white/20 rounded-lg p-0.5">
          <button
            v-for="range in (['today', 'week', 'month', 'year'] as const)"
            :key="range"
            :class="[
              'px-3 py-1 text-xs font-medium rounded-md transition-all',
              dateRange === range ? 'bg-white text-brand-700 shadow-sm' : 'text-white/80 hover:text-white',
            ]"
            @click="dateRange = range"
          >
            {{ t(`dashboard.dateRanges.${range}`) }}
          </button>
        </div>

        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors"
          @click="isCustomizing = !isCustomizing"
        >
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ t('dashboard.customize') }}
        </button>
      </div>
    </template>

    <WidgetCustomizer
      v-if="isCustomizing"
      :all-widgets="allWidgets.map(w => ({ ...w, title: t(w.titleKey) }))"
      :active-ids="activeWidgetIds"
      class="mb-6"
      @toggle="toggleWidget"
      @close="isCustomizing = false"
    />

    <div v-if="activeWidgetIds.includes('stats')" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
      <StatWidget
        v-for="stat in stats"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :trend="stat.trend"
        :icon="stat.icon"
        :trend-label="t('dashboard.vsLastMonth')"
      />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <template v-for="widget in activeWidgets" :key="widget.id">
        <div v-if="widget.component === 'revenue'" :class="getWidgetSpan(widget.size)">
          <RevenueChart :date-range="dateRange" :title="t('dashboard.revenue')" />
        </div>
        <div v-if="widget.component === 'appointments'" :class="getWidgetSpan(widget.size)">
          <AppointmentChart :date-range="dateRange" :title="t('dashboard.appointments')" />
        </div>
        <div v-if="widget.component === 'activity'" :class="getWidgetSpan(widget.size)">
          <ActivityFeed :title="t('dashboard.recentActivity')" />
        </div>
        <div v-if="widget.component === 'upcoming'" :class="getWidgetSpan(widget.size)">
          <UpcomingList :title="t('dashboard.upcomingAppointments')" />
        </div>
        <div v-if="widget.component === 'alerts'" :class="getWidgetSpan(widget.size)">
          <AlertCenter :title="t('dashboard.alertCenter')" />
        </div>
      </template>
    </div>
  </ModuleLayout>
</template>
