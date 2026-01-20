<template>
  <div class="p-6 space-y-6 bg-slate-50/50 min-h-screen">
    <!-- Header with Widget Customization -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $t('dashboard.title') }}</h1>
        <p class="text-sm text-slate-600 mt-1">{{ $t('dashboard.welcome_back') }}</p>
      </div>
      <button
        @click="isCustomizing = !isCustomizing"
        class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
      >
        <SettingsIcon :size="18" />
        {{ isCustomizing ? $t('dashboard.done') : $t('dashboard.customize') }}
      </button>
    </div>

    <!-- Widget Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <template v-for="(widgetId, index) in visibleWidgets" :key="widgetId">
        <div
          :class="[
            'bg-white rounded-xl border border-slate-200 shadow-sm p-6 relative',
            widgets[widgetId].gridSpan
          ]"
        >
          <!-- Widget Controls (when customizing) -->
          <div
            v-if="isCustomizing"
            class="absolute top-2 right-2 flex gap-2 z-10"
          >
            <button
              @click="moveWidget(index, 'up')"
              :disabled="index === 0"
              class="p-1 bg-white border border-slate-200 rounded hover:bg-slate-50 disabled:opacity-50"
            >
              <ChevronUpIcon :size="14" />
            </button>
            <button
              @click="moveWidget(index, 'down')"
              :disabled="index === visibleWidgets.length - 1"
              class="p-1 bg-white border border-slate-200 rounded hover:bg-slate-50 disabled:opacity-50"
            >
              <ChevronDownIcon :size="14" />
            </button>
            <button
              @click="toggleWidget(widgetId)"
              class="p-1 bg-white border border-slate-200 rounded hover:bg-rose-50 hover:border-rose-200"
            >
              <XIcon :size="14" />
            </button>
          </div>

          <!-- Widget Content -->
          <component :is="widgets[widgetId].component" v-bind="widgets[widgetId].props" />
        </div>
      </template>
    </div>

    <!-- Hidden Widgets Panel -->
    <div v-if="isCustomizing && hiddenWidgets.length > 0" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h3 class="font-bold text-slate-800 mb-4">{{ $t('dashboard.hidden_widgets') }}</h3>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="widgetId in hiddenWidgets"
          :key="widgetId"
          @click="toggleWidget(widgetId)"
          class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 text-sm"
        >
          <PlusIcon :size="14" />
          {{ widgets[widgetId].title }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Settings as SettingsIcon, Plus as PlusIcon, X as XIcon, ChevronUp as ChevronUpIcon, ChevronDown as ChevronDownIcon } from 'lucide-vue-next'
import StatWidget from '../components/StatWidget.vue'
import RevenueChartWidget from '../components/RevenueChartWidget.vue'
import AppointmentChartWidget from '../components/AppointmentChartWidget.vue'
import RecentActivityWidget from '../components/RecentActivityWidget.vue'
import InfocenterWidget from '../components/InfocenterWidget.vue'
import UpcomingAppointmentsWidget from '../components/UpcomingAppointmentsWidget.vue'

const { t: $t } = useI18n()

const isCustomizing = ref(false)

// Widget visibility state
const widgetVisibility = ref<Record<string, boolean>>({
  revenue: true,
  customers: true,
  appointments: true,
  avgRevenue: true,
  revenueChart: true,
  appointmentChart: true,
  recentActivity: true,
  infocenter: true,
  upcomingAppointments: true
})

// Widget order
const widgetOrder = ref<string[]>([
  'revenue',
  'customers',
  'appointments',
  'avgRevenue',
  'revenueChart',
  'appointmentChart',
  'upcomingAppointments',
  'recentActivity',
  'infocenter'
])

// Widget definitions
const widgets: Record<string, any> = {
  revenue: {
    title: $t('dashboard.total_revenue'),
    component: StatWidget,
    gridSpan: 'col-span-1',
    props: {
      title: $t('dashboard.total_revenue'),
      value: 'CHF 45,230',
      change: '+12.5%',
      isPositive: true,
      iconName: 'banknote',
      colorClass: 'text-emerald-600'
    }
  },
  customers: {
    title: $t('dashboard.total_customers'),
    component: StatWidget,
    gridSpan: 'col-span-1',
    props: {
      title: $t('dashboard.total_customers'),
      value: '1,245',
      change: '+8.2%',
      isPositive: true,
      iconName: 'users',
      colorClass: 'text-blue-600'
    }
  },
  appointments: {
    title: $t('dashboard.appointments_today'),
    component: StatWidget,
    gridSpan: 'col-span-1',
    props: {
      title: $t('dashboard.appointments_today'),
      value: '24',
      change: '-2.1%',
      isPositive: false,
      iconName: 'calendar',
      colorClass: 'text-purple-600'
    }
  },
  avgRevenue: {
    title: $t('dashboard.avg_revenue'),
    component: StatWidget,
    gridSpan: 'col-span-1',
    props: {
      title: $t('dashboard.avg_revenue'),
      value: 'CHF 187',
      change: '+5.3%',
      isPositive: true,
      iconName: 'clock',
      colorClass: 'text-amber-600'
    }
  },
  revenueChart: {
    title: $t('dashboard.revenue_analytics'),
    component: RevenueChartWidget,
    gridSpan: 'col-span-1 md:col-span-2'
  },
  appointmentChart: {
    title: $t('dashboard.weekly_appointments'),
    component: AppointmentChartWidget,
    gridSpan: 'col-span-1 md:col-span-2'
  },
  upcomingAppointments: {
    title: $t('dashboard.upcoming_appointments'),
    component: UpcomingAppointmentsWidget,
    gridSpan: 'col-span-1 md:col-span-2'
  },
  recentActivity: {
    title: $t('dashboard.recent_activity'),
    component: RecentActivityWidget,
    gridSpan: 'col-span-1 md:col-span-2'
  },
  infocenter: {
    title: $t('dashboard.infocenter'),
    component: InfocenterWidget,
    gridSpan: 'col-span-1 md:col-span-2 lg:col-span-4'
  }
}

const visibleWidgets = computed(() =>
  widgetOrder.value.filter(id => widgetVisibility.value[id])
)

const hiddenWidgets = computed(() =>
  widgetOrder.value.filter(id => !widgetVisibility.value[id])
)

const toggleWidget = (widgetId: string) => {
  widgetVisibility.value[widgetId] = !widgetVisibility.value[widgetId]
}

const moveWidget = (index: number, direction: 'up' | 'down') => {
  const newOrder = [...widgetOrder.value]
  const targetIndex = direction === 'up' ? index - 1 : index + 1

  if (targetIndex >= 0 && targetIndex < newOrder.length) {
    [newOrder[index], newOrder[targetIndex]] = [newOrder[targetIndex], newOrder[index]]
    widgetOrder.value = newOrder
  }
}
</script>
