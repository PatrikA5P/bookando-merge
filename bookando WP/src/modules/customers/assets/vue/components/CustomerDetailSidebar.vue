<!-- CustomerDetailSidebar.vue -->
<template>
  <teleport to="body">
    <transition name="sidebar">
      <div
        v-if="show"
        class="bookando-sidebar-overlay"
        @click="close"
      >
        <div
          class="bookando-sidebar bookando-sidebar--right"
          @click.stop
        >
          <!-- Header -->
          <div class="bookando-sidebar__header">
            <div class="sidebar-header-content">
              <AppAvatar
                :src="customer?.avatar_url"
                :initials="initials(customer)"
                size="md"
                fit="cover"
                :alt="fullName"
              />
              <div class="sidebar-header-info">
                <h2 class="sidebar-title">
                  {{ fullName }}
                </h2>
                <p class="sidebar-subtitle bookando-text-muted">
                  ID {{ customer?.id }} • {{ statusLabel(customer?.status, locale) }}
                </p>
              </div>
            </div>
            <AppButton
              icon="x"
              variant="ghost"
              size="square"
              btn-type="icononly"
              icon-size="md"
              :tooltip="t('core.common.close')"
              @click="close"
            />
          </div>

          <!-- Tabs -->
          <div class="bookando-sidebar__tabs">
            <AppTabs
              v-model="activeTab"
              :tabs="tabs"
              size="sm"
            />
          </div>

          <!-- Content -->
          <div class="bookando-sidebar__content">
            <!-- Overview Tab -->
            <div
              v-if="activeTab === 'overview'"
              class="sidebar-tab-content"
            >
              <!-- Stats Dashboard -->
              <div class="bookando-grid bookando-grid--2 bookando-gap-md bookando-mb-lg">
                <div class="bookando-card bookando-card--compact">
                  <div class="stat-card">
                    <AppIcon
                      name="calendar"
                      class="stat-icon bookando-text-primary"
                    />
                    <div class="stat-content">
                      <div class="stat-value">
                        {{ stats.appointments }}
                      </div>
                      <div class="stat-label bookando-text-muted">
                        {{ t('mod.customers.stats.appointments') }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="bookando-card bookando-card--compact">
                  <div class="stat-card">
                    <AppIcon
                      name="book"
                      class="stat-icon bookando-text-accent"
                    />
                    <div class="stat-content">
                      <div class="stat-value">
                        {{ stats.courses }}
                      </div>
                      <div class="stat-label bookando-text-muted">
                        {{ t('mod.customers.stats.courses') }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="bookando-card bookando-card--compact">
                  <div class="stat-card">
                    <AppIcon
                      name="credit-card"
                      class="stat-icon bookando-text-success"
                    />
                    <div class="stat-content">
                      <div class="stat-value">
                        {{ formatCurrency(stats.revenue) }}
                      </div>
                      <div class="stat-label bookando-text-muted">
                        {{ t('mod.customers.stats.revenue') }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="bookando-card bookando-card--compact">
                  <div class="stat-card">
                    <AppIcon
                      name="award"
                      class="stat-icon bookando-text-warning"
                    />
                    <div class="stat-content">
                      <div class="stat-value">
                        {{ stats.badges }}
                      </div>
                      <div class="stat-label bookando-text-muted">
                        {{ t('mod.customers.stats.badges') }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Recent Activity -->
              <div class="bookando-card">
                <div class="bookando-card__header">
                  <h3 class="bookando-card__title">
                    {{ t('mod.customers.sections.recent_activity') }}
                  </h3>
                </div>
                <div class="bookando-card__body">
                  <div
                    v-if="loading.activity"
                    class="bookando-text-center bookando-p-lg"
                  >
                    <AppIcon
                      name="loader"
                      class="bookando-icon-spin"
                    />
                  </div>
                  <div
                    v-else-if="!recentActivity.length"
                    class="bookando-text-center bookando-text-muted bookando-p-lg"
                  >
                    {{ t('mod.customers.no_recent_activity') }}
                  </div>
                  <div
                    v-else
                    class="activity-timeline"
                  >
                    <div
                      v-for="(item, idx) in recentActivity"
                      :key="idx"
                      class="activity-item"
                    >
                      <div class="activity-icon">
                        <AppIcon
                          :name="item.icon"
                          :class="`bookando-text-${item.color}`"
                        />
                      </div>
                      <div class="activity-content">
                        <div class="activity-title">
                          {{ item.title }}
                        </div>
                        <div class="activity-meta bookando-text-muted bookando-text-sm">
                          {{ formatDate(item.date, locale) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Details Tab -->
            <div
              v-if="activeTab === 'details'"
              class="sidebar-tab-content"
            >
              <div class="bookando-card">
                <div class="bookando-card__header">
                  <h3 class="bookando-card__title">
                    {{ t('mod.customers.sections.personal_info') }}
                  </h3>
                  <AppButton
                    icon="edit"
                    variant="ghost"
                    size="sm"
                    :tooltip="t('core.common.edit')"
                    @click="$emit('edit', customer)"
                  />
                </div>
                <div class="bookando-card__body">
                  <div class="detail-grid">
                    <div
                      v-for="field in personalFields"
                      :key="field.key"
                      class="detail-row"
                    >
                      <div class="detail-label">
                        {{ field.label }}
                      </div>
                      <div class="detail-value">
                        <template v-if="field.key === 'email' && customer.email">
                          <a
                            :href="`mailto:${customer.email}`"
                            class="bookando-link"
                          >{{ customer.email }}</a>
                        </template>
                        <template v-else-if="field.key === 'phone' && customer.phone">
                          <a
                            :href="`tel:${customer.phone}`"
                            class="bookando-link"
                          >{{ customer.phone }}</a>
                        </template>
                        <template v-else-if="field.key === 'gender'">
                          {{ genderLabel(customer.gender, locale) || '–' }}
                        </template>
                        <template v-else-if="field.key === 'birthdate'">
                          {{ formatDate(customer.birthdate, locale) }}
                        </template>
                        <template v-else-if="field.key === 'country'">
                          <span
                            v-if="customer.country"
                            class="flag"
                          >{{ countryFlag(customer.country) }}</span>
                          {{ countryLabel(customer.country, locale) || customer.country || '–' }}
                        </template>
                        <template v-else-if="field.key === 'language'">
                          <span
                            v-if="customer.language"
                            class="flag"
                          >{{ languageFlag(customer.language) }}</span>
                          {{ languageLabel(customer.language, locale) || customer.language || '–' }}
                        </template>
                        <template v-else>
                          {{ customer[field.key] || '–' }}
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Address -->
              <div
                v-if="hasAddress"
                class="bookando-card bookando-mt-md"
              >
                <div class="bookando-card__header">
                  <h3 class="bookando-card__title">
                    {{ t('mod.customers.sections.address') }}
                  </h3>
                </div>
                <div class="bookando-card__body">
                  <div class="address-block">
                    <div v-if="customer.street">
                      {{ customer.street }}
                    </div>
                    <div v-if="customer.postal_code || customer.city">
                      {{ customer.postal_code }} {{ customer.city }}
                    </div>
                    <div v-if="customer.country">
                      <span class="flag">{{ countryFlag(customer.country) }}</span>
                      {{ countryLabel(customer.country, locale) }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Appointments Tab -->
            <div
              v-if="activeTab === 'appointments'"
              class="sidebar-tab-content"
            >
              <div class="bookando-card">
                <div class="bookando-card__header">
                  <h3 class="bookando-card__title">
                    {{ t('mod.customers.sections.appointments') }}
                  </h3>
                  <span class="bookando-badge bookando-badge--primary">
                    {{ appointments.length }}
                  </span>
                </div>
                <div class="bookando-card__body">
                  <div
                    v-if="loading.appointments"
                    class="bookando-text-center bookando-p-lg"
                  >
                    <AppIcon
                      name="loader"
                      class="bookando-icon-spin"
                    />
                  </div>
                  <div
                    v-else-if="!appointments.length"
                    class="bookando-text-center bookando-text-muted bookando-p-lg"
                  >
                    {{ t('mod.customers.no_appointments') }}
                  </div>
                  <div
                    v-else
                    class="list-items"
                  >
                    <div
                      v-for="apt in appointments"
                      :key="apt.id"
                      class="list-item"
                    >
                      <div class="list-item-icon">
                        <AppIcon
                          name="calendar"
                          class="bookando-text-primary"
                        />
                      </div>
                      <div class="list-item-content">
                        <div class="list-item-title">
                          {{ apt.service_name }}
                        </div>
                        <div class="list-item-meta bookando-text-muted bookando-text-sm">
                          {{ formatDatetime(apt.start_time, locale) }}
                        </div>
                      </div>
                      <div class="list-item-actions">
                        <span
                          class="bookando-badge"
                          :class="`bookando-badge--${getAppointmentStatusColor(apt.status)}`"
                        >
                          {{ apt.status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Courses Tab -->
            <div
              v-if="activeTab === 'courses'"
              class="sidebar-tab-content"
            >
              <div class="bookando-card">
                <div class="bookando-card__header">
                  <h3 class="bookando-card__title">
                    {{ t('mod.customers.sections.courses') }}
                  </h3>
                  <span class="bookando-badge bookando-badge--accent">
                    {{ courses.length }}
                  </span>
                </div>
                <div class="bookando-card__body">
                  <div
                    v-if="loading.courses"
                    class="bookando-text-center bookando-p-lg"
                  >
                    <AppIcon
                      name="loader"
                      class="bookando-icon-spin"
                    />
                  </div>
                  <div
                    v-else-if="!courses.length"
                    class="bookando-text-center bookando-text-muted bookando-p-lg"
                  >
                    {{ t('mod.customers.no_courses') }}
                  </div>
                  <div
                    v-else
                    class="list-items"
                  >
                    <div
                      v-for="course in courses"
                      :key="course.id"
                      class="list-item"
                    >
                      <div class="list-item-icon">
                        <AppIcon
                          name="book"
                          class="bookando-text-accent"
                        />
                      </div>
                      <div class="list-item-content">
                        <div class="list-item-title">
                          {{ course.name }}
                        </div>
                        <div class="list-item-meta bookando-text-muted bookando-text-sm">
                          {{ t('mod.customers.course_progress', { progress: course.progress || 0 }) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Invoices Tab -->
            <div
              v-if="activeTab === 'invoices'"
              class="sidebar-tab-content"
            >
              <div class="bookando-card">
                <div class="bookando-card__header">
                  <h3 class="bookando-card__title">
                    {{ t('mod.customers.sections.invoices') }}
                  </h3>
                  <span class="bookando-badge bookando-badge--success">
                    {{ invoices.length }}
                  </span>
                </div>
                <div class="bookando-card__body">
                  <div
                    v-if="loading.invoices"
                    class="bookando-text-center bookando-p-lg"
                  >
                    <AppIcon
                      name="loader"
                      class="bookando-icon-spin"
                    />
                  </div>
                  <div
                    v-else-if="!invoices.length"
                    class="bookando-text-center bookando-text-muted bookando-p-lg"
                  >
                    {{ t('mod.customers.no_invoices') }}
                  </div>
                  <div
                    v-else
                    class="list-items"
                  >
                    <div
                      v-for="invoice in invoices"
                      :key="invoice.id"
                      class="list-item"
                    >
                      <div class="list-item-icon">
                        <AppIcon
                          name="credit-card"
                          class="bookando-text-success"
                        />
                      </div>
                      <div class="list-item-content">
                        <div class="list-item-title">
                          {{ invoice.number }}
                        </div>
                        <div class="list-item-meta bookando-text-muted bookando-text-sm">
                          {{ formatDate(invoice.date, locale) }}
                        </div>
                      </div>
                      <div class="list-item-actions">
                        <div class="bookando-font-semibold">
                          {{ formatCurrency(invoice.amount) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import {
  genderLabel,
  countryFlag,
  countryLabel,
  languageFlag,
  languageLabel,
  statusLabel,
  formatDate,
  formatDatetime
} from '@core/Util/formatters'
import { initials } from '../../../composables/useCustomerData'

const { t, locale } = useI18n()

const props = defineProps<{
  show: boolean
  customer: any | null
}>()

const emit = defineEmits(['close', 'edit'])

// State
const activeTab = ref<'overview' | 'details' | 'appointments' | 'courses' | 'invoices'>('overview')

const loading = ref({
  activity: false,
  appointments: false,
  courses: false,
  invoices: false
})

// Placeholder data - will be loaded from API
const stats = ref({
  appointments: 0,
  courses: 0,
  revenue: 0,
  badges: 0
})

const recentActivity = ref<any[]>([])
const appointments = ref<any[]>([])
const courses = ref<any[]>([])
const invoices = ref<any[]>([])

// Computed
const fullName = computed(() => {
  if (!props.customer) return ''
  return `${props.customer.first_name || ''} ${props.customer.last_name || ''}`.trim()
})

const hasAddress = computed(() => {
  if (!props.customer) return false
  return !!(props.customer.street || props.customer.city || props.customer.postal_code || props.customer.country)
})

const tabs = computed(() => [
  { label: t('mod.customers.tabs.overview'), value: 'overview' },
  { label: t('mod.customers.tabs.details'), value: 'details' },
  { label: t('mod.customers.tabs.appointments'), value: 'appointments' },
  { label: t('mod.customers.tabs.courses'), value: 'courses' },
  { label: t('mod.customers.tabs.invoices'), value: 'invoices' }
])

const personalFields = computed(() => [
  { key: 'email', label: t('core.fields.email') },
  { key: 'phone', label: t('core.fields.phone') },
  { key: 'gender', label: t('core.fields.gender') },
  { key: 'birthdate', label: t('core.fields.birthdate') },
  { key: 'language', label: t('core.fields.language') },
  { key: 'country', label: t('core.fields.country') }
])

// Methods
const close = () => {
  emit('close')
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat(locale.value, {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
}

const getAppointmentStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    confirmed: 'success',
    pending: 'warning',
    cancelled: 'danger',
    completed: 'primary'
  }
  return colors[status] || 'muted'
}

// Load related data when customer changes
watch(() => props.customer?.id, async (customerId) => {
  if (!customerId) return

  activeTab.value = 'overview'

  // TODO: Load data from API
  // For now using placeholder values
  stats.value = {
    appointments: props.customer?.total_appointments || 0,
    courses: 0,
    revenue: 0,
    badges: 0
  }

  recentActivity.value = []
  appointments.value = []
  courses.value = []
  invoices.value = []
}, { immediate: true })

// Close on ESC key
const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

if (typeof window !== 'undefined') {
  window.addEventListener('keydown', handleKeydown)
}
</script>

<style scoped>
/* Sidebar Overlay */
.bookando-sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  display: flex;
  justify-content: flex-end;
}

/* Sidebar Container */
.bookando-sidebar {
  background: var(--bookando-color-bg-primary, #fff);
  width: 100%;
  max-width: 500px;
  height: 100vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
}

/* Header */
.bookando-sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--bookando-space-lg, 1.5rem);
  border-bottom: 1px solid var(--bookando-color-border, #e5e7eb);
  flex-shrink: 0;
}

.sidebar-header-content {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-md, 1rem);
  flex: 1;
  min-width: 0;
}

.sidebar-header-info {
  flex: 1;
  min-width: 0;
}

.sidebar-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sidebar-subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.875rem;
}

/* Tabs */
.bookando-sidebar__tabs {
  padding: 0 var(--bookando-space-lg, 1.5rem);
  border-bottom: 1px solid var(--bookando-color-border, #e5e7eb);
  flex-shrink: 0;
}

/* Content */
.bookando-sidebar__content {
  flex: 1;
  overflow-y: auto;
  padding: var(--bookando-space-lg, 1.5rem);
}

.sidebar-tab-content {
  animation: fadeIn 0.2s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Stats Card */
.stat-card {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-md, 1rem);
}

.stat-icon {
  width: 2.5rem;
  height: 2.5rem;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

/* Activity Timeline */
.activity-timeline {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-md, 1rem);
}

.activity-item {
  display: flex;
  align-items: start;
  gap: var(--bookando-space-md, 1rem);
}

.activity-icon {
  width: 2rem;
  height: 2rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-title {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

/* Detail Grid */
.detail-grid {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-md, 1rem);
}

.detail-row {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: var(--bookando-space-md, 1rem);
  align-items: start;
}

.detail-label {
  font-weight: 500;
  color: var(--bookando-color-text-muted, #6b7280);
}

.detail-value {
  word-break: break-word;
}

/* Address Block */
.address-block {
  line-height: 1.6;
}

.address-block .flag {
  margin-right: 0.5rem;
}

/* List Items */
.list-items {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-sm, 0.5rem);
}

.list-item {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-md, 1rem);
  padding: var(--bookando-space-sm, 0.5rem);
  border-radius: var(--bookando-radius-md, 0.5rem);
  transition: background-color 0.2s;
}

.list-item:hover {
  background: var(--bookando-color-bg-secondary, #f9fafb);
}

.list-item-icon {
  width: 2rem;
  height: 2rem;
  flex-shrink: 0;
}

.list-item-content {
  flex: 1;
  min-width: 0;
}

.list-item-title {
  font-weight: 500;
  margin-bottom: 0.25rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.list-item-actions {
  flex-shrink: 0;
}

/* Transitions */
.sidebar-enter-active,
.sidebar-leave-active {
  transition: opacity 0.3s ease;
}

.sidebar-enter-active .bookando-sidebar,
.sidebar-leave-active .bookando-sidebar {
  transition: transform 0.3s ease;
}

.sidebar-enter-from,
.sidebar-leave-to {
  opacity: 0;
}

.sidebar-enter-from .bookando-sidebar,
.sidebar-leave-to .bookando-sidebar {
  transform: translateX(100%);
}

/* Responsive */
@media (max-width: 767px) {
  .bookando-sidebar {
    max-width: 100%;
  }

  .detail-row {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }

  .detail-label {
    font-size: 0.875rem;
  }
}
</style>
