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
          <AppPageHeader :title="t('mod.tools.title')" />
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
        <CoursePlannerTab v-if="activeTab === 'coursePlanner'" />
        <ReportsTab v-else-if="activeTab === 'reports'" />
        <BookingFormsTab v-else-if="activeTab === 'bookingForms'" />
        <NotificationsMatrixTab v-else-if="activeTab === 'notifications'" />
        <DesignTab v-else-if="activeTab === 'design'" />
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
import ReportsTab from '../components/reports/ReportsTab.vue'
import BookingFormsTab from '../components/booking-forms/BookingFormsTab.vue'
import NotificationsMatrixTab from '../components/notifications/NotificationsMatrixTab.vue'
import DesignTab from '../components/design/DesignTab.vue'
import CoursePlannerTab from '../components/course-planner/CoursePlannerTab.vue'

const { t } = useI18n()
const activeTab = ref('workforce')

// License & Module Access
const vars = typeof window !== 'undefined' && (window as any).BOOKANDO_VARS || {}
const moduleAllowed = vars.module_allowed ?? true
const requiredPlan = typeof vars.required_plan === 'string' && vars.required_plan !== ''
  ? vars.required_plan
  : null

const tabs = computed(() => [
  {
    value: 'workforce',
    label: t('mod.tools.tabs.workforce'),
    icon: 'briefcase'
  },
  {
    value: 'coursePlanner',
    label: t('mod.tools.tabs.coursePlanner'),
    icon: 'calendar'
  },
  {
    value: 'reports',
    label: t('mod.tools.tabs.reports'),
    icon: 'chart-bar'
  },
  {
    value: 'bookingForms',
    label: t('mod.tools.tabs.bookingForms') || 'Buchungsformulare',
    icon: 'clipboard-list'
  },
  {
    value: 'notifications',
    label: t('mod.tools.tabs.notifications'),
    icon: 'bell'
  },
  {
    value: 'design',
    label: t('mod.tools.tabs.design'),
    icon: 'palette'
  }
])
</script>
