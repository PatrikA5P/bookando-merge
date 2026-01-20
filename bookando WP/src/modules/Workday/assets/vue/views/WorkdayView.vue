<template>
  <ModuleLayout
    :hero-title="$t('mod.workday.title')"
    :hero-description="$t('mod.workday.description')"
    :hero-icon="ClockIcon"
    hero-gradient="bg-gradient-to-br from-indigo-700 to-purple-900"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="activeTab = $event"
  >
    <!-- Appointments Tab -->
    <AppointmentsView v-if="activeTab === 'appointments'" />

    <!-- Time Tracking Tab -->
    <TimeTrackingTab v-else-if="activeTab === 'timetracking'" />

    <!-- Shift Planner Tab -->
    <ShiftPlannerTab v-else-if="activeTab === 'shifts'" />

    <!-- Absences Tab -->
    <AbsenceTab v-else-if="activeTab === 'absences'" />

    <!-- Course Planner Tab -->
    <CoursePlannerTab v-else-if="activeTab === 'planner'" />
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Clock as ClockIcon, CalendarDays as CalendarDaysIcon, Timer as TimerIcon, Users as UsersIcon, Briefcase as BriefcaseIcon, Layout as LayoutIcon } from 'lucide-vue-next'
import ModuleLayout from '@/Core/Design/components/ModuleLayout.vue'
import AppointmentsView from '../../../Appointments/assets/vue/views/AppointmentsView.vue'
import TimeTrackingTab from '../components/TimeTrackingTab.vue'
import ShiftPlannerTab from '../components/ShiftPlannerTab.vue'
import AbsenceTab from '../components/AbsenceTab.vue'
import CoursePlannerTab from '../components/CoursePlannerTab.vue'

const { t: $t } = useI18n()

const activeTab = ref('appointments')

const tabs = [
  { id: 'appointments', icon: CalendarDaysIcon, label: $t('mod.workday.appointments') },
  { id: 'timetracking', icon: TimerIcon, label: $t('mod.workday.time_tracking') },
  { id: 'shifts', icon: UsersIcon, label: $t('mod.workday.shift_planner') },
  { id: 'absences', icon: BriefcaseIcon, label: $t('mod.workday.absences') },
  { id: 'planner', icon: LayoutIcon, label: $t('mod.workday.course_planner') }
]
</script>
