<template>
  <ModuleLayout
    :hero-title="$t('mod.finance.title')"
    :hero-description="$t('mod.finance.description')"
    :hero-icon="WalletIcon"
    hero-gradient="bg-gradient-to-br from-green-700 to-emerald-900"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:active-tab="handleTabChange"
  >
    <!-- Overview Tab -->
    <OverviewTab v-if="activeTab === 'overview'" />

    <!-- Invoicing Tab -->
    <InvoicingTab v-else-if="activeTab === 'invoicing'" />

    <!-- Accounting Tab -->
    <AccountingTab v-else-if="activeTab === 'accounting'" />

    <!-- Payroll Tab -->
    <PayrollTab v-else-if="activeTab === 'payroll'" />

    <!-- Vouchers Tab -->
    <VouchersTab v-else-if="activeTab === 'vouchers'" />

    <!-- Commissions Tab -->
    <CommissionsTab v-else-if="activeTab === 'commissions'" />
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Wallet as WalletIcon, BarChart3 as BarChart3Icon, FileText as FileTextIcon, Calculator as CalculatorIcon, Users as UsersIcon, Gift as GiftIcon, TrendingUp as TrendingUpIcon } from 'lucide-vue-next'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import OverviewTab from '../components/OverviewTab.vue'
import InvoicingTab from '../components/InvoicingTab.vue'
import AccountingTab from '../components/AccountingTab.vue'
import PayrollTab from '../components/PayrollTab.vue'
import VouchersTab from '../components/VouchersTab.vue'
import CommissionsTab from '../components/CommissionsTab.vue'

const { t: $t } = useI18n()

const activeTab = ref('overview')

const tabs = [
  { id: 'overview', icon: BarChart3Icon, label: $t('mod.finance.overview') },
  { id: 'invoicing', icon: FileTextIcon, label: $t('mod.finance.invoicing') },
  { id: 'accounting', icon: CalculatorIcon, label: $t('mod.finance.accounting') },
  { id: 'payroll', icon: UsersIcon, label: $t('mod.finance.payroll') },
  { id: 'vouchers', icon: GiftIcon, label: $t('mod.finance.vouchers') },
  { id: 'commissions', icon: TrendingUpIcon, label: $t('mod.finance.commissions') }
]

const handleTabChange = (tabId: string) => {
  activeTab.value = tabId
}
</script>
