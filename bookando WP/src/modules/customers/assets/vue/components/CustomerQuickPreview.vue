<!-- CustomerQuickPreview.vue -->
<template>
  <div
    class="customer-quick-preview"
    :style="{ width: `${width}px` }"
  >
    <!-- Resize Handle -->
    <div
      class="resize-handle"
      @mousedown="startResize"
    />
    <!-- Header -->
    <div class="preview-header">
      <AppAvatar
        :src="customer?.avatar_url"
        :initials="initials(customer)"
        size="lg"
      />
      <div class="preview-header-info">
        <h3 class="preview-title">
          {{ fullName }}
        </h3>
        <p class="preview-subtitle bookando-text-muted">
          ID {{ customer?.id }} • {{ statusLabel(customer?.status, locale) }}
        </p>
      </div>
      <AppButton
        icon="x"
        variant="ghost"
        size="square"
        btn-type="icononly"
        @click="$emit('close')"
      />
    </div>

    <!-- Contact Info -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.customers.sections.contact') }}
      </h4>
      <div class="preview-info-list">
        <div
          v-if="customer?.email"
          class="preview-info-item"
        >
          <AppIcon
            name="mail"
            class="preview-info-icon"
          />
          <a
            :href="`mailto:${customer.email}`"
            class="bookando-link"
          >
            {{ customer.email }}
          </a>
        </div>
        <div
          v-if="customer?.phone"
          class="preview-info-item"
        >
          <AppIcon
            name="phone"
            class="preview-info-icon"
          />
          <a
            :href="`tel:${normalizePhone(customer.phone)}`"
            class="bookando-link"
          >
            {{ customer.phone }}
          </a>
        </div>
      </div>
    </div>

    <!-- Address -->
    <div
      v-if="hasAddress"
      class="preview-section"
    >
      <h4 class="preview-section-title">
        {{ t('mod.customers.sections.address') }}
      </h4>
      <div class="preview-info-list">
        <div class="preview-info-item">
          <AppIcon
            name="map-pin"
            class="preview-info-icon"
          />
          <div class="preview-address">
            <div v-if="customer?.address">
              {{ customer.address }}
            </div>
            <div v-if="customer?.zip || customer?.city">
              {{ customer.zip }} {{ customer.city }}
            </div>
            <div v-if="customer?.country">
              <span class="flag">{{ countryFlag(customer.country) }}</span>
              {{ countryLabel(customer.country, locale) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Personal Info -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.customers.sections.personal') }}
      </h4>
      <div class="preview-info-grid">
        <div
          v-if="customer?.language"
          class="preview-info-item"
        >
          <span class="preview-info-label">{{ t('mod.customers.fields.language') }}:</span>
          <span>
            <span class="flag">{{ languageFlag(customer.language) }}</span>
            {{ languageLabel(customer.language, locale) }}
          </span>
        </div>
        <div
          v-if="customer?.gender"
          class="preview-info-item"
        >
          <span class="preview-info-label">{{ t('mod.customers.fields.gender') }}:</span>
          <span>{{ genderLabel(customer.gender, locale) }}</span>
        </div>
      </div>
    </div>

    <!-- Next Appointments -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.customers.sections.next_appointments') }}
      </h4>
      <div class="preview-list">
        <div
          v-if="!nextAppointments.length"
          class="preview-empty"
        >
          {{ t('mod.customers.no_upcoming_appointments') }}
        </div>
        <div
          v-for="apt in nextAppointments"
          :key="apt.id"
          class="preview-list-item"
        >
          <AppIcon
            name="calendar"
            class="preview-list-icon"
          />
          <div class="preview-list-content">
            <div class="preview-list-title">
              {{ apt.service_name }}
            </div>
            <div class="preview-list-meta bookando-text-muted">
              {{ formatDatetime(apt.start_time, locale) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Courses -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.customers.sections.active_courses') }}
      </h4>
      <div class="preview-list">
        <div
          v-if="!activeCourses.length"
          class="preview-empty"
        >
          {{ t('mod.customers.no_active_courses') }}
        </div>
        <div
          v-for="course in activeCourses"
          :key="course.id"
          class="preview-list-item"
        >
          <AppIcon
            name="book"
            class="preview-list-icon"
          />
          <div class="preview-list-content">
            <div class="preview-list-title">
              {{ course.name }}
            </div>
            <div class="preview-list-meta bookando-text-muted">
              {{ course.progress }}% {{ t('core.common.completed') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Training Card Progress -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.customers.sections.training_cards') }}
      </h4>
      <div class="preview-list">
        <div
          v-if="!trainingCards.length"
          class="preview-empty"
        >
          {{ t('mod.customers.no_training_cards') }}
        </div>
        <div
          v-for="card in trainingCards"
          :key="card.id"
          class="preview-list-item"
        >
          <AppIcon
            name="award"
            class="preview-list-icon"
          />
          <div class="preview-list-content">
            <div class="preview-list-title">
              {{ card.name }}
            </div>
            <div class="preview-list-meta bookando-text-muted">
              {{ card.completed_sessions }}/{{ card.total_sessions }} {{ t('mod.customers.sessions_completed') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Button -->
    <div class="preview-footer">
      <AppButton
        variant="primary"
        icon="edit"
        class="bookando-width-full"
        @click="$emit('edit', customer)"
      >
        {{ t('core.common.edit') }}
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import {
  genderLabel,
  countryFlag,
  countryLabel,
  languageFlag,
  languageLabel,
  statusLabel,
  formatDatetime
} from '@core/Util/formatters'

const { t, locale } = useI18n()

const props = defineProps<{
  customer: any
  width: number
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'edit', customer: any): void
  (e: 'resize', width: number): void
}>()

// Resize functionality
const isResizing = ref(false)
const startX = ref(0)
const startWidth = ref(0)

function startResize(event: MouseEvent) {
  isResizing.value = true
  startX.value = event.clientX
  startWidth.value = props.width

  document.addEventListener('mousemove', handleResize)
  document.addEventListener('mouseup', stopResize)
  document.body.style.cursor = 'col-resize'
  document.body.style.userSelect = 'none'
}

function handleResize(event: MouseEvent) {
  if (!isResizing.value) return

  const delta = startX.value - event.clientX
  const newWidth = Math.max(280, Math.min(600, startWidth.value + delta))
  emit('resize', newWidth)
}

function stopResize() {
  isResizing.value = false
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
  document.body.style.cursor = ''
  document.body.style.userSelect = ''
}

const fullName = computed(() => {
  if (!props.customer) return ''
  return `${props.customer.first_name || ''} ${props.customer.last_name || ''}`.trim()
})

const hasAddress = computed(() => {
  const c = props.customer
  return c?.address || c?.zip || c?.city || c?.country
})

// Placeholder data - replace with real data from API
const nextAppointments = computed(() => {
  // TODO: Load from API
  return []
})

const activeCourses = computed(() => {
  // TODO: Load from API
  return []
})

const trainingCards = computed(() => {
  // TODO: Load from API
  return []
})

function initials(item: any): string {
  return ((item?.first_name?.[0] || '') + (item?.last_name?.[0] || '')).toUpperCase()
}

function normalizePhone(phone: string | number): string {
  return String(phone ?? '').replace(/\s+/g, '')
}
</script>

<style scoped>
.customer-quick-preview {
  position: relative;
  height: 100%;
  background: var(--bookando-surface);
  border-left: 1px solid var(--bookando-border);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.resize-handle {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  cursor: col-resize;
  z-index: 20;
  background: transparent;
  transition: background-color 0.2s;
}

.resize-handle:hover {
  background: var(--bookando-primary, #4F46E5);
}

.preview-header {
  display: flex;
  align-items: flex-start;
  gap: var(--bookando-space-md);
  padding: var(--bookando-space-lg);
  border-bottom: 1px solid var(--bookando-border);
  position: sticky;
  top: 0;
  background: var(--bookando-surface);
  z-index: 10;
}

.preview-header-info {
  flex: 1;
  min-width: 0;
}

.preview-title {
  font-size: var(--bookando-font-lg);
  font-weight: 600;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.preview-subtitle {
  font-size: var(--bookando-font-sm);
  margin: var(--bookando-space-xxs) 0 0;
}

.preview-section {
  padding: var(--bookando-space-lg);
  border-bottom: 1px solid var(--bookando-border);
}

.preview-section-title {
  font-size: var(--bookando-font-sm);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--bookando-text-muted);
  margin: 0 0 var(--bookando-space-md);
}

.preview-info-list {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-sm);
}

.preview-info-item {
  display: flex;
  align-items: flex-start;
  gap: var(--bookando-space-sm);
  font-size: var(--bookando-font-sm);
}

.preview-info-icon {
  color: var(--bookando-text-muted);
  margin-top: 2px;
  flex-shrink: 0;
}

.preview-address {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.preview-info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--bookando-space-sm);
}

.preview-info-grid .preview-info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-info-label {
  color: var(--bookando-text-muted);
  font-weight: 500;
}

.preview-list {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-sm);
}

.preview-list-item {
  display: flex;
  align-items: flex-start;
  gap: var(--bookando-space-sm);
  padding: var(--bookando-space-sm);
  background: var(--bookando-background);
  border-radius: var(--bookando-radius-md);
}

.preview-list-icon {
  color: var(--bookando-text-muted);
  margin-top: 2px;
  flex-shrink: 0;
}

.preview-list-content {
  flex: 1;
  min-width: 0;
}

.preview-list-title {
  font-weight: 500;
  font-size: var(--bookando-font-sm);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.preview-list-meta {
  font-size: var(--bookando-font-xs);
  margin-top: 2px;
}

.preview-empty {
  text-align: center;
  padding: var(--bookando-space-lg);
  color: var(--bookando-text-muted);
  font-size: var(--bookando-font-sm);
}

.preview-footer {
  padding: var(--bookando-space-lg);
  margin-top: auto;
  border-top: 1px solid var(--bookando-border);
  position: sticky;
  bottom: 0;
  background: var(--bookando-surface);
}

.flag {
  margin-right: var(--bookando-space-xxs);
}
</style>
