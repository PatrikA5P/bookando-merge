<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <!-- Header -->
        <template #header>
          <AppPageHeader :title="t('mod.workday.title')" />
        </template>

        <!-- Tabs Navigation -->
        <template #nav>
          <AppTabs
            v-model="activeTab"
            :tabs="tabs"
            nav-only
          />
        </template>

        <!-- Tab Content -->
        <CalendarTab v-if="activeTab === 'calendar'" />
        <AppointmentsTab v-else-if="activeTab === 'appointments'" />
        <TimeTrackingTab v-else-if="activeTab === 'timeTracking'" />
        <DutySchedulingTab v-else-if="activeTab === 'dutyScheduling'" />
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import CalendarTab from '../components/calendar/CalendarTab.vue'
import AppointmentsTab from '../components/appointments/AppointmentsTab.vue'
import TimeTrackingTab from '../components/time-tracking/TimeTrackingTab.vue'
import DutySchedulingTab from '../components/duty-scheduler/DutySchedulingTab.vue'

const { t } = useI18n()
const activeTab = ref('calendar')

// License & Module Access
const vars = typeof window !== 'undefined' && (window as any).BOOKANDO_VARS || {}
const moduleAllowed = vars.module_allowed ?? true
const requiredPlan = typeof vars.required_plan === 'string' && vars.required_plan !== ''
  ? vars.required_plan
  : null

const tabs = computed(() => [
  {
    value: 'calendar',
    label: t('mod.workday.tabs.calendar'),
    icon: 'calendar'
  },
  {
    value: 'appointments',
    label: t('mod.workday.tabs.appointments'),
    icon: 'clock'
  },
  {
    value: 'timeTracking',
    label: t('mod.workday.tabs.timeTracking'),
    icon: 'briefcase'
  },
  {
    value: 'dutyScheduling',
    label: t('mod.workday.tabs.dutyScheduling'),
    icon: 'users'
  }
])
</script>
