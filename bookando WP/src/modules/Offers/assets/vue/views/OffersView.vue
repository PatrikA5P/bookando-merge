<template>
  <ModuleLayout
    :title="$t('mod.offers.title')"
    :description="$t('mod.offers.description')"
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
    value: 'dienstleistungen',
    label: $t('mod.offers.tabs.dienstleistungen'),
    icon: 'briefcase'
  },
  {
    value: 'kurse',
    label: $t('mod.offers.tabs.kurse'),
    icon: 'calendar'
  },
  {
    value: 'online',
    label: $t('mod.offers.tabs.online'),
    icon: 'monitor'
  },
  {
    value: 'categories',
    label: $t('mod.offers.tabs.categories'),
    icon: 'folder'
  },
  {
    value: 'tags',
    label: $t('mod.offers.tabs.tags'),
    icon: 'tag'
  },
  {
    value: 'packages',
    label: $t('mod.offers.tabs.packages'),
    icon: 'package'
  },
  {
    value: 'extras',
    label: $t('mod.offers.tabs.extras'),
    icon: 'plus-circle'
  },
  {
    value: 'coupons',
    label: $t('mod.offers.tabs.coupons'),
    icon: 'gift'
  },
  {
    value: 'pricing',
    label: $t('mod.offers.tabs.pricing'),
    icon: 'trending-up'
  },
  {
    value: 'forms',
    label: $t('mod.offers.tabs.forms'),
    icon: 'file-text'
  }
]

// Tab Change Handler
const handleTabChange = (newTab: string) => {
  activeTab.value = newTab
}
</script>
