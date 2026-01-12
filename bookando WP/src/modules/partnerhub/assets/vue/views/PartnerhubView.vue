<template>
  <AppShell>
    <AppPageHeader
      :title="t('mod.partnerhub.title')"
      :description="t('mod.partnerhub.description')"
    />

    <AppTabs
      v-model="activeTab"
      :tabs="tabs"
      class="mb-6"
    />

    <!-- Dashboard -->
    <div
      v-if="activeTab === 'dashboard'"
      class="space-y-6"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <AppCard>
          <div class="text-sm text-gray-600">
            {{ t('mod.partnerhub.total_partners') }}
          </div>
          <div class="text-3xl font-bold">
            {{ stats.total_partners || 0 }}
          </div>
        </AppCard>
        <AppCard>
          <div class="text-sm text-gray-600">
            {{ t('mod.partnerhub.active_mappings') }}
          </div>
          <div class="text-3xl font-bold">
            {{ stats.total_mappings || 0 }}
          </div>
        </AppCard>
        <AppCard>
          <div class="text-sm text-gray-600">
            {{ t('mod.partnerhub.active_consents') }}
          </div>
          <div class="text-3xl font-bold">
            {{ stats.total_consents || 0 }}
          </div>
        </AppCard>
        <AppCard>
          <div class="text-sm text-gray-600">
            {{ t('mod.partnerhub.pending_approvals') }}
          </div>
          <div class="text-3xl font-bold text-orange-600">
            {{ stats.pending_approvals || 0 }}
          </div>
        </AppCard>
      </div>

      <AppCard>
        <h3 class="text-lg font-semibold mb-4">
          {{ t('mod.partnerhub.recent_transactions') }}
        </h3>
        <p class="text-gray-500">
          {{ t('mod.partnerhub.coming_soon') }}
        </p>
      </AppCard>
    </div>

    <!-- Partner -->
    <div
      v-if="activeTab === 'partners'"
      class="space-y-6"
    >
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">
          {{ t('mod.partnerhub.partners') }}
        </h2>
        <AppButton
          v-if="capabilities.manage_partners"
          variant="primary"
          @click="showPartnerForm = true"
        >
          {{ t('mod.partnerhub.add_partner') }}
        </AppButton>
      </div>

      <AppCard>
        <p class="text-gray-500">
          {{ t('mod.partnerhub.partner_list_coming_soon') }}
        </p>
        <p class="text-sm text-gray-400 mt-2">
          {{ t('mod.partnerhub.use_rest_api') }}:
          <code class="bg-gray-100 px-2 py-1 rounded">GET {{ restUrl }}/partners</code>
        </p>
      </AppCard>
    </div>

    <!-- Mappings -->
    <div
      v-if="activeTab === 'mappings'"
      class="space-y-6"
    >
      <AppCard>
        <h3 class="text-lg font-semibold mb-4">
          {{ t('mod.partnerhub.listings_mappings') }}
        </h3>
        <p class="text-gray-500">
          {{ t('mod.partnerhub.coming_soon') }}
        </p>
      </AppCard>
    </div>

    <!-- Consents -->
    <div
      v-if="activeTab === 'consents'"
      class="space-y-6"
    >
      <AppCard>
        <h3 class="text-lg font-semibold mb-4">
          {{ t('mod.partnerhub.customer_consents') }}
        </h3>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
          <div class="flex items-start">
            <AppIcon
              name="info-circle"
              class="text-blue-600 mt-1 mr-3"
            />
            <div>
              <h4 class="font-semibold text-blue-900">
                {{ t('mod.partnerhub.gdpr_compliant') }}
              </h4>
              <p class="text-sm text-blue-700 mt-1">
                {{ t('mod.partnerhub.gdpr_notice') }}
              </p>
            </div>
          </div>
        </div>
        <p class="text-gray-500">
          {{ t('mod.partnerhub.coming_soon') }}
        </p>
      </AppCard>
    </div>

    <!-- Feeds -->
    <div
      v-if="activeTab === 'feeds'"
      class="space-y-6"
    >
      <AppCard>
        <h3 class="text-lg font-semibold mb-4">
          {{ t('mod.partnerhub.feed_exports') }}
        </h3>
        <p class="text-gray-500">
          {{ t('mod.partnerhub.coming_soon') }}
        </p>
      </AppCard>
    </div>

    <!-- Audit Logs -->
    <div
      v-if="activeTab === 'audit'"
      class="space-y-6"
    >
      <AppCard v-if="capabilities.view_audit_logs">
        <h3 class="text-lg font-semibold mb-4">
          {{ t('mod.partnerhub.audit_logs') }}
        </h3>
        <p class="text-gray-500">
          {{ t('mod.partnerhub.coming_soon') }}
        </p>
      </AppCard>
      <AppCard v-else>
        <p class="text-red-500">
          {{ t('mod.partnerhub.no_permission') }}
        </p>
      </AppCard>
    </div>
  </AppShell>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppShell from '@core/Design/components/AppShell.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import AppCard from '@core/Design/components/AppCard.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'

const { t } = useI18n()

const activeTab = ref('dashboard')
const stats = ref({})
const showPartnerForm = ref(false)

const capabilities = window.bookandoPartnerhub?.capabilities || {}
const restUrl = window.bookandoPartnerhub?.restUrl || ''

const tabs = computed(() => [
  { id: 'dashboard', label: t('mod.partnerhub.dashboard') },
  { id: 'partners', label: t('mod.partnerhub.partners') },
  { id: 'mappings', label: t('mod.partnerhub.mappings') },
  { id: 'consents', label: t('mod.partnerhub.consents') },
  { id: 'feeds', label: t('mod.partnerhub.feeds') },
  { id: 'audit', label: t('mod.partnerhub.audit_logs') },
])

const loadDashboardStats = async () => {
  try {
    const response = await fetch(`${restUrl}/dashboard`, {
      headers: {
        'X-WP-Nonce': window.bookandoPartnerhub.nonce,
      },
    })
    const data = await response.json()
    stats.value = data.data || {}
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
  }
}

onMounted(() => {
  loadDashboardStats()
})
</script>
