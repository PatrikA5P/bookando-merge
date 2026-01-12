<!-- SettingsView.vue -->
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <!-- Lizenzprüfung -->
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <template #header>
          <AppPageHeader
            :title="t('mod.settings.title')"
            icon="settings"
            no-cta
          />
        </template>

        <div class="bookando-card-grid">
          <!-- Karte: Allgemein -->
          <SettingsCard
            icon="settings"
            :title="t('mod.settings.cards.general.title')"
            :desc="t('mod.settings.cards.general.desc')"
            :link="t('mod.settings.cards.general.link')"
            @mouseenter="preloadGeneral"
            @click="openGeneral"
          />

          <!-- Karte: Unternehmen -->
          <SettingsCard
            icon="home"
            :title="t('mod.settings.cards.company.title')"
            :desc="t('mod.settings.cards.company.desc')"
            :link="t('mod.settings.cards.company.link')"
            @mouseenter="preloadCompany"
            @click="openCompany"
          />

          <SettingsCard
            icon="mail"
            :title="t('mod.settings.cards.notifications.title')"
            :desc="t('mod.settings.cards.notifications.desc')"
            :link="t('mod.settings.cards.notifications.link')"
            disabled
          />
          <SettingsCard
            icon="calendar"
            :title="t('mod.settings.cards.working_hours.title')"
            :desc="t('mod.settings.cards.working_hours.desc')"
            :link="t('mod.settings.cards.working_hours.link')"
            disabled
          />
          <SettingsCard
            icon="credit-card"
            :title="t('mod.settings.cards.payments.title')"
            :desc="t('mod.settings.cards.payments.desc')"
            :link="t('mod.settings.cards.payments.link')"
            disabled
          />
          <SettingsCard
            icon="integration"
            :title="t('mod.settings.cards.integrations.title')"
            :desc="t('mod.settings.cards.integrations.desc')"
            :link="t('mod.settings.cards.integrations.link')"
            disabled
          />
          <SettingsCard
            icon="calendar-2"
            :title="t('mod.settings.cards.appointments_events.title')"
            :desc="t('mod.settings.cards.appointments_events.desc')"
            :link="t('mod.settings.cards.appointments_events.link')"
            disabled
          />
          <SettingsCard
            icon="tag"
            :title="t('mod.settings.cards.labels.title')"
            :desc="t('mod.settings.cards.labels.desc')"
            :link="t('mod.settings.cards.labels.link')"
            disabled
          />
          <SettingsCard
            icon="users"
            :title="t('mod.settings.cards.roles.title')"
            :desc="t('mod.settings.cards.roles.desc')"
            :link="t('mod.settings.cards.roles.link')"
            @mouseenter="preloadRoles"
            @click="openRoles"
          />
          <SettingsCard
            icon="shield"
            :title="t('mod.settings.cards.activation.title')"
            :desc="t('mod.settings.cards.activation.desc')"
            :link="t('mod.settings.cards.activation.link')"
            disabled
          />
          <SettingsCard
            icon="key"
            :title="t('mod.settings.cards.api_keys.title')"
            :desc="t('mod.settings.cards.api_keys.desc')"
            :link="t('mod.settings.cards.api_keys.link')"
            disabled
          />
        </div>

        <!-- Modale (jede bringt ihre eigene Overlay mit) -->
        <SettingsGeneralForm
          v-if="showGeneralForm"
          @close="closeGeneral"
        />
        <SettingsRolesForm
          v-if="showRolesForm"
          @close="closeRoles"
        />
        <SettingsCompanyForm
          v-if="showCompanyForm"
          @close="closeCompany"
        />
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { ref, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'

import AppShell from '@core/Design/components/AppShell.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue' 
import SettingsCard from '../components/SettingsCard.vue'

// 🔽 lazy:
const SettingsGeneralForm = defineAsyncComponent(() => import('../components/SettingsGeneralForm.vue'))
const SettingsRolesForm   = defineAsyncComponent(() => import('../components/SettingsRolesForm.vue'))
const SettingsCompanyForm = defineAsyncComponent(() => import('../components/SettingsCompanyForm.vue'))

const { t } = useI18n()
const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

const showGeneralForm = ref(false)
const showRolesForm   = ref(false)
const showCompanyForm = ref(false)

function openGeneral()  { showGeneralForm.value = true }
function closeGeneral() { showGeneralForm.value = false }

function openRoles()    { showRolesForm.value = true }
function closeRoles()   { showRolesForm.value = false }

function openCompany()  { showCompanyForm.value = true }
function closeCompany() { showCompanyForm.value = false }

// (optional) Preload beim Hover auf Card:
function preloadGeneral() { import('../components/SettingsGeneralForm.vue') }
function preloadRoles()   { import('../components/SettingsRolesForm.vue') }
function preloadCompany() { import('../components/SettingsCompanyForm.vue') }
</script>

