<template>
  <ModuleLayout
    :hero-title="$t('mod.offers.title')"
    :hero-description="$t('mod.offers.description')"
    :hero-icon="BriefcaseIcon"
    hero-gradient="bg-gradient-to-br from-blue-700 to-sky-900"
    module-name="offers"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:active-tab="handleTabChange"
  >
    <!-- Tab 1: Dienstleistungen -->
    <DienstleistungenTab v-if="activeTab === 'dienstleistungen'" />

    <!-- Tab 2: Kurse -->
    <KurseTab v-if="activeTab === 'kurse'" />

    <!-- Tab 3: Online -->
    <OnlineTab v-if="activeTab === 'online'" />

    <!-- Tab 4: Categories -->
    <CategoriesTab v-if="activeTab === 'categories'" />

    <!-- Tab 5: Tags -->
    <TagsTab v-if="activeTab === 'tags'" />

    <!-- Tab 6: Packages & Bundles -->
    <PackagesTab v-if="activeTab === 'packages'" />

    <!-- Tab 7: Add-ons & Extras -->
    <ExtrasTab v-if="activeTab === 'extras'" />

    <!-- Tab 8: Coupons & Gift Cards -->
    <CouponsTab v-if="activeTab === 'coupons'" />

    <!-- Tab 9: Dynamic Pricing -->
    <DynamicPricingTab v-if="activeTab === 'pricing'" />

    <!-- Tab 10: Booking Forms -->
    <BookingFormsTab v-if="activeTab === 'forms'" />
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Briefcase as BriefcaseIcon,
  Calendar as CalendarIcon,
  Monitor as MonitorIcon,
  Folder as FolderIcon,
  Tag as TagIcon,
  Package as PackageIcon,
  PlusCircle as PlusCircleIcon,
  Gift as GiftIcon,
  TrendingUp as TrendingUpIcon,
  FileText as FileTextIcon
} from 'lucide-vue-next'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'

// Tab Components
import DienstleistungenTab from '../tabs/DienstleistungenTab.vue'
import KurseTab from '../tabs/KurseTab.vue'
import OnlineTab from '../tabs/OnlineTab.vue'
import CategoriesTab from '../tabs/CategoriesTab.vue'
import TagsTab from '../tabs/TagsTab.vue'
import PackagesTab from '../tabs/PackagesTab.vue'
import ExtrasTab from '../tabs/ExtrasTab.vue'
import CouponsTab from '../tabs/CouponsTab.vue'
import DynamicPricingTab from '../tabs/DynamicPricingTab.vue'
import BookingFormsTab from '../tabs/BookingFormsTab.vue'

const { t: $t } = useI18n()

// Active Tab State
const activeTab = ref('dienstleistungen')

// Tab Configuration
const tabs = [
  {
    id: 'dienstleistungen',
    label: $t('mod.offers.tabs.dienstleistungen'),
    icon: BriefcaseIcon
  },
  {
    id: 'kurse',
    label: $t('mod.offers.tabs.kurse'),
    icon: CalendarIcon
  },
  {
    id: 'online',
    label: $t('mod.offers.tabs.online'),
    icon: MonitorIcon
  },
  {
    id: 'categories',
    label: $t('mod.offers.tabs.categories'),
    icon: FolderIcon
  },
  {
    id: 'tags',
    label: $t('mod.offers.tabs.tags'),
    icon: TagIcon
  },
  {
    id: 'packages',
    label: $t('mod.offers.tabs.packages'),
    icon: PackageIcon
  },
  {
    id: 'extras',
    label: $t('mod.offers.tabs.extras'),
    icon: PlusCircleIcon
  },
  {
    id: 'coupons',
    label: $t('mod.offers.tabs.coupons'),
    icon: GiftIcon
  },
  {
    id: 'pricing',
    label: $t('mod.offers.tabs.pricing'),
    icon: TrendingUpIcon
  },
  {
    id: 'forms',
    label: $t('mod.offers.tabs.forms'),
    icon: FileTextIcon
  }
]

// Tab Change Handler
const handleTabChange = (newTab: string) => {
  activeTab.value = newTab
}
</script>
