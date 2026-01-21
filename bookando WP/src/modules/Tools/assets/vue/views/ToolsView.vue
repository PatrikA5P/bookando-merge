<template>
  <ModuleLayout
    :hero-title="$t('mod.tools.title')"
    :hero-description="$t('mod.tools.description')"
    :hero-icon="SettingsIcon"
    hero-gradient="bg-gradient-to-br from-purple-700 to-indigo-900"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:active-tab="handleTabChange"
  >
    <ReportsTab v-if="activeTab === 'reports'" />
    <NotificationsTab v-else-if="activeTab === 'notifications'" />
    <SystemTab v-else-if="activeTab === 'system'" />
    <DesignTab v-else-if="activeTab === 'design'" />
    <ImportTab v-else-if="activeTab === 'import'" />
    <ApiTab v-else-if="activeTab === 'api'" />
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Settings as SettingsIcon, BarChart2 as BarChart2Icon, Bell as BellIcon, Server as ServerIcon, Palette as PaletteIcon, Upload as UploadIcon, Code as CodeIcon } from 'lucide-vue-next'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import ReportsTab from '../components/ReportsTab.vue'
import NotificationsTab from '../components/NotificationsTab.vue'
import SystemTab from '../components/SystemTab.vue'
import DesignTab from '../components/DesignTab.vue'
import ImportTab from '../components/ImportTab.vue'
import ApiTab from '../components/ApiTab.vue'

const { t: $t } = useI18n()

const activeTab = ref('reports')

const tabs = [
  { id: 'reports', icon: BarChart2Icon, label: $t('mod.tools.reports') },
  { id: 'notifications', icon: BellIcon, label: $t('mod.tools.notifications') },
  { id: 'system', icon: ServerIcon, label: $t('mod.tools.system') },
  { id: 'design', icon: PaletteIcon, label: $t('mod.tools.design') },
  { id: 'import', icon: UploadIcon, label: $t('mod.tools.import') },
  { id: 'api', icon: CodeIcon, label: $t('mod.tools.api') }
]

const handleTabChange = (tabId: string) => {
  activeTab.value = tabId
}
</script>
