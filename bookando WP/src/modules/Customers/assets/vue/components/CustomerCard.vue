<!-- CustomerCard.vue -->
<template>
  <teleport to="body">
    <transition name="customer-card">
      <div
        v-if="show"
        class="customer-card-overlay"
        @click.self="close"
      >
        <div class="customer-card">
          <!-- Top Toolbar -->
          <div class="customer-card-toolbar">
            <div class="toolbar-left">
              <AppButton
                icon="x"
                variant="ghost"
                size="square"
                btn-type="icononly"
                :tooltip="t('core.common.close')"
                @click="close"
              />
              <div class="toolbar-customer-info">
                <AppAvatar
                  :src="customer?.avatar_url"
                  :initials="initials(customer)"
                  size="sm"
                />
                <div>
                  <h2 class="toolbar-title">
                    {{ fullName }}
                  </h2>
                  <p class="toolbar-subtitle">
                    ID {{ customer?.id }}
                  </p>
                </div>
              </div>
            </div>

            <div class="toolbar-actions">
              <AppButton
                v-if="!editMode"
                icon="edit"
                variant="standard"
                @click="editMode = true"
              >
                {{ t('core.common.edit_mode') }}
              </AppButton>
              <AppButton
                v-else
                icon="check"
                variant="primary"
                @click="saveChanges"
              >
                {{ t('core.common.save') }}
              </AppButton>

              <AppButton
                icon="mail"
                variant="standard"
                :tooltip="t('core.actions.send_email')"
                @click="sendEmail"
              />

              <AppButton
                icon="phone"
                variant="standard"
                :tooltip="t('core.actions.call')"
                @click="makeCall"
              />

              <AppButton
                icon="calendar"
                variant="standard"
                :tooltip="t('core.actions.new_appointment')"
                @click="newAppointment"
              />

              <AppButton
                icon="tag"
                variant="standard"
                :tooltip="t('core.actions.assign_coupon')"
                @click="assignCoupon"
              />

              <AppButton
                icon="more-horizontal"
                variant="standard"
                :tooltip="t('core.common.more_actions')"
              />
            </div>
          </div>

          <!-- Content Area -->
          <div class="customer-card-content">
            <!-- Tabs -->
            <AppTabs
              v-model="activeTab"
              :tabs="tabs"
            />

            <!-- Tab Panels -->
            <div class="customer-card-panels">
              <!-- Overview Tab -->
              <div
                v-show="activeTab === 'overview'"
                class="customer-card-panel"
              >
                <div class="bookando-grid bookando-grid--2 bookando-gap-lg">
                  <!-- Stats Cards -->
                  <div class="bookando-card">
                    <div class="bookando-card__header">
                      <h3 class="bookando-card__title">
                        {{ t('mod.customers.sections.stats') }}
                      </h3>
                    </div>
                    <div class="bookando-card__body">
                      <div class="stats-grid">
                        <div class="stat-item">
                          <AppIcon
                            name="calendar"
                            class="stat-icon bookando-text-primary"
                          />
                          <div class="stat-content">
                            <div class="stat-value">
                              {{ customer?.total_appointments || 0 }}
                            </div>
                            <div class="stat-label">
                              {{ t('mod.customers.stats.appointments') }}
                            </div>
                          </div>
                        </div>
                        <div class="stat-item">
                          <AppIcon
                            name="book"
                            class="stat-icon bookando-text-accent"
                          />
                          <div class="stat-content">
                            <div class="stat-value">
                              {{ customer?.total_courses || 0 }}
                            </div>
                            <div class="stat-label">
                              {{ t('mod.customers.stats.courses') }}
                            </div>
                          </div>
                        </div>
                        <div class="stat-item">
                          <AppIcon
                            name="credit-card"
                            class="stat-icon bookando-text-success"
                          />
                          <div class="stat-content">
                            <div class="stat-value">
                              {{ formatCurrency(customer?.total_revenue || 0) }}
                            </div>
                            <div class="stat-label">
                              {{ t('mod.customers.stats.revenue') }}
                            </div>
                          </div>
                        </div>
                        <div class="stat-item">
                          <AppIcon
                            name="award"
                            class="stat-icon bookando-text-warning"
                          />
                          <div class="stat-content">
                            <div class="stat-value">
                              {{ customer?.total_badges || 0 }}
                            </div>
                            <div class="stat-label">
                              {{ t('mod.customers.stats.badges') }}
                            </div>
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
                      <div class="activity-timeline">
                        <div
                          v-if="!activities.length"
                          class="bookando-text-center bookando-text-muted bookando-p-lg"
                        >
                          {{ t('mod.customers.no_recent_activity') }}
                        </div>
                        <div
                          v-for="activity in activities"
                          :key="activity.id"
                          class="activity-item"
                        >
                          <div class="activity-icon">
                            <AppIcon :name="activity.icon" />
                          </div>
                          <div class="activity-content">
                            <div class="activity-title">
                              {{ activity.title }}
                            </div>
                            <div class="activity-meta bookando-text-muted">
                              {{ formatDatetime(activity.date, locale) }}
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Details Tab -->
              <div
                v-show="activeTab === 'details'"
                class="customer-card-panel"
              >
                <div class="bookando-card">
                  <div class="bookando-card__header">
                    <h3 class="bookando-card__title">
                      {{ t('mod.customers.sections.personal_info') }}
                    </h3>
                  </div>
                  <div class="bookando-card__body">
                    <div class="details-grid">
                      <div
                        v-for="field in personalFields"
                        :key="field.key"
                        class="detail-row"
                      >
                        <div class="detail-label">
                          {{ field.label }}:
                        </div>
                        <div class="detail-value">
                          {{ getFieldValue(field.key) }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Appointments Tab -->
              <div
                v-show="activeTab === 'appointments'"
                class="customer-card-panel"
              >
                <div class="bookando-card">
                  <div class="bookando-card__header">
                    <h3 class="bookando-card__title">
                      {{ t('mod.customers.sections.appointments') }}
                    </h3>
                  </div>
                  <div class="bookando-card__body">
                    <p class="bookando-text-muted">
                      {{ t('mod.customers.appointments_coming_soon') }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Courses Tab -->
              <div
                v-show="activeTab === 'courses'"
                class="customer-card-panel"
              >
                <div class="bookando-card">
                  <div class="bookando-card__header">
                    <h3 class="bookando-card__title">
                      {{ t('mod.customers.sections.courses') }}
                    </h3>
                  </div>
                  <div class="bookando-card__body">
                    <p class="bookando-text-muted">
                      {{ t('mod.customers.courses_coming_soon') }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Invoices Tab -->
              <div
                v-show="activeTab === 'invoices'"
                class="customer-card-panel"
              >
                <div class="bookando-card">
                  <div class="bookando-card__header">
                    <h3 class="bookando-card__title">
                      {{ t('mod.customers.sections.invoices') }}
                    </h3>
                  </div>
                  <div class="bookando-card__body">
                    <p class="bookando-text-muted">
                      {{ t('mod.customers.invoices_coming_soon') }}
                    </p>
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
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import {
  genderLabel,
  countryLabel,
  languageLabel,
  statusLabel,
  formatDatetime
} from '@core/Util/formatters'

const { t, locale } = useI18n()

const props = defineProps<{
  show: boolean
  customer: any
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'save', customer: any): void
}>()

const editMode = ref(false)
const activeTab = ref('overview')

const tabs = computed(() => [
  { label: t('mod.customers.tabs.overview'), value: 'overview' },
  { label: t('mod.customers.tabs.details'), value: 'details' },
  { label: t('mod.customers.tabs.appointments'), value: 'appointments' },
  { label: t('mod.customers.tabs.courses'), value: 'courses' },
  { label: t('mod.customers.tabs.invoices'), value: 'invoices' }
])

const fullName = computed(() => {
  if (!props.customer) return ''
  return `${props.customer.first_name || ''} ${props.customer.last_name || ''}`.trim()
})

// Placeholder data
const activities = computed(() => [])

const personalFields = computed(() => [
  { key: 'email', label: t('mod.customers.fields.email') },
  { key: 'phone', label: t('mod.customers.fields.phone') },
  { key: 'address', label: t('mod.customers.fields.address') },
  { key: 'city', label: t('mod.customers.fields.city') },
  { key: 'zip', label: t('mod.customers.fields.zip') },
  { key: 'country', label: t('mod.customers.fields.country') },
  { key: 'language', label: t('mod.customers.fields.language') },
  { key: 'gender', label: t('mod.customers.fields.gender') },
  { key: 'status', label: t('mod.customers.fields.status') }
])

function initials(item: any): string {
  return ((item?.first_name?.[0] || '') + (item?.last_name?.[0] || '')).toUpperCase()
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat(locale.value, {
    style: 'currency',
    currency: 'EUR'
  }).format(value)
}

function getFieldValue(key: string): string {
  const val = props.customer?.[key]
  if (!val) return '–'

  switch (key) {
    case 'country':
      return countryLabel(val, locale.value) || val
    case 'language':
      return languageLabel(val, locale.value) || val
    case 'gender':
      return genderLabel(val, locale.value) || val
    case 'status':
      return statusLabel(val, locale.value) || val
    default:
      return String(val)
  }
}

function close() {
  editMode.value = false
  emit('close')
}

function saveChanges() {
  // TODO: Implement save logic
  emit('save', props.customer)
  editMode.value = false
}

function sendEmail() {
  if (props.customer?.email) {
    window.location.href = `mailto:${props.customer.email}`
  }
}

function makeCall() {
  if (props.customer?.phone) {
    window.location.href = `tel:${props.customer.phone}`
  }
}

function newAppointment() {
  // TODO: Implement new appointment
}

function assignCoupon() {
  // TODO: Implement assign coupon
}

// Close on ESC key
watch(() => props.show, (isShowing) => {
  if (isShowing) {
    const handleEsc = (e: KeyboardEvent) => {
      if (e.key === 'Escape') close()
    }
    document.addEventListener('keydown', handleEsc)
    return () => document.removeEventListener('keydown', handleEsc)
  }
})
</script>

<style scoped>
.customer-card-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.customer-card {
  width: 100%;
  height: 100%;
  background: var(--bookando-background);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.customer-card-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--bookando-space-md) var(--bookando-space-lg);
  background: var(--bookando-surface);
  border-bottom: 1px solid var(--bookando-border);
  position: sticky;
  top: 0;
  z-index: 100;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-md);
}

.toolbar-customer-info {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
}

.toolbar-title {
  font-size: var(--bookando-font-lg);
  font-weight: 600;
  margin: 0;
}

.toolbar-subtitle {
  font-size: var(--bookando-font-sm);
  color: var(--bookando-text-muted);
  margin: 0;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
}

.customer-card-content {
  flex: 1;
  overflow-y: auto;
  padding: var(--bookando-space-lg);
}

.customer-card-panels {
  margin-top: var(--bookando-space-lg);
}

.customer-card-panel {
  animation: fadeIn 0.2s ease-in-out;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--bookando-space-lg);
}

.stat-item {
  display: flex;
  align-items: flex-start;
  gap: var(--bookando-space-md);
}

.stat-icon {
  font-size: 1.5rem;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: var(--bookando-font-2xl);
  font-weight: 700;
  line-height: 1.2;
}

.stat-label {
  font-size: var(--bookando-font-sm);
  color: var(--bookando-text-muted);
  margin-top: var(--bookando-space-xxs);
}

.activity-timeline {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-md);
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: var(--bookando-space-sm);
  padding: var(--bookando-space-sm);
  border-radius: var(--bookando-radius-md);
  background: var(--bookando-background);
}

.activity-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bookando-primary-light);
  color: var(--bookando-primary);
  border-radius: var(--bookando-radius-full);
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 500;
  font-size: var(--bookando-font-sm);
}

.activity-meta {
  font-size: var(--bookando-font-xs);
  margin-top: 2px;
}

.details-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--bookando-space-md);
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--bookando-space-sm) 0;
  border-bottom: 1px solid var(--bookando-border);
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: var(--bookando-text-muted);
}

.detail-value {
  text-align: right;
}

/* Transitions */
.customer-card-enter-active,
.customer-card-leave-active {
  transition: opacity 0.3s ease;
}

.customer-card-enter-active .customer-card,
.customer-card-leave-active .customer-card {
  transition: transform 0.3s ease;
}

.customer-card-enter-from,
.customer-card-leave-to {
  opacity: 0;
}

.customer-card-enter-from .customer-card {
  transform: scale(0.95);
}

.customer-card-leave-to .customer-card {
  transform: scale(0.95);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
