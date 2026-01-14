<!-- EmployeeQuickPreview.vue -->
<template>
  <div
    class="employee-quick-preview"
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
        :src="employee?.avatar_url"
        :initials="initials(employee)"
        size="lg"
      />
      <div class="preview-header-info">
        <h3 class="preview-title">
          {{ fullName }}
        </h3>
        <p class="preview-subtitle bookando-text-muted">
          ID {{ employee?.id }} • {{ statusLabel(employee?.status, locale) }}
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
        {{ t('mod.employees.sections.contact') }}
      </h4>
      <div class="preview-info-list">
        <div
          v-if="employee?.email"
          class="preview-info-item"
        >
          <AppIcon
            name="mail"
            class="preview-info-icon"
          />
          <a
            :href="`mailto:${employee.email}`"
            class="bookando-link"
          >
            {{ employee.email }}
          </a>
        </div>
        <div
          v-if="employee?.phone"
          class="preview-info-item"
        >
          <AppIcon
            name="phone"
            class="preview-info-icon"
          />
          <a
            :href="`tel:${normalizePhone(employee.phone)}`"
            class="bookando-link"
          >
            {{ employee.phone }}
          </a>
        </div>
      </div>
    </div>

    <!-- Role & Department -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.employees.sections.role') }}
      </h4>
      <div class="preview-info-grid">
        <div
          v-if="employee?.role"
          class="preview-info-item"
        >
          <span class="preview-info-label">{{ t('mod.employees.fields.role') }}:</span>
          <span>{{ employee.role }}</span>
        </div>
        <div
          v-if="employee?.department"
          class="preview-info-item"
        >
          <span class="preview-info-label">{{ t('mod.employees.fields.department') }}:</span>
          <span>{{ employee.department }}</span>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.employees.sections.stats') }}
      </h4>
      <div class="preview-stats-grid">
        <div class="preview-stat-card">
          <AppIcon
            name="calendar"
            class="preview-stat-icon bookando-text-primary"
          />
          <div class="preview-stat-content">
            <div class="preview-stat-value">
              {{ stats.appointments }}
            </div>
            <div class="preview-stat-label bookando-text-muted">
              {{ t('mod.employees.stats.appointments') }}
            </div>
          </div>
        </div>

        <div class="preview-stat-card">
          <AppIcon
            name="users"
            class="preview-stat-icon bookando-text-accent"
          />
          <div class="preview-stat-content">
            <div class="preview-stat-value">
              {{ stats.customers }}
            </div>
            <div class="preview-stat-label bookando-text-muted">
              {{ t('mod.employees.stats.customers') }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Services -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.employees.sections.services') }}
      </h4>
      <div class="preview-list">
        <div
          v-if="!services.length"
          class="preview-empty"
        >
          {{ t('mod.employees.no_services') }}
        </div>
        <div
          v-for="service in services"
          :key="service.id"
          class="preview-list-item"
        >
          <AppIcon
            name="briefcase"
            class="preview-list-icon"
          />
          <div class="preview-list-content">
            <div class="preview-list-title">
              {{ service.name }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Next Appointments -->
    <div class="preview-section">
      <h4 class="preview-section-title">
        {{ t('mod.employees.sections.next_appointments') }}
      </h4>
      <div class="preview-list">
        <div
          v-if="!nextAppointments.length"
          class="preview-empty"
        >
          {{ t('mod.employees.no_upcoming_appointments') }}
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

    <!-- Action Button -->
    <div class="preview-footer">
      <AppButton
        variant="primary"
        icon="edit"
        class="bookando-width-full"
        @click="$emit('edit', employee)"
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
  statusLabel,
  formatDatetime
} from '@core/Util/formatters'

const { t, locale } = useI18n()

const props = defineProps<{
  employee: any
  width: number
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'edit', employee: any): void
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
  if (!props.employee) return ''
  return `${props.employee.first_name || ''} ${props.employee.last_name || ''}`.trim()
})

// Placeholder data - replace with real data from API
const stats = computed(() => ({
  appointments: 0,
  customers: 0
}))

const services = computed(() => {
  // TODO: Load from API
  return []
})

const nextAppointments = computed(() => {
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
.employee-quick-preview {
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

.preview-stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--bookando-space-md);
}

.preview-stat-card {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
  padding: var(--bookando-space-md);
  background: var(--bookando-background);
  border-radius: var(--bookando-radius-md);
}

.preview-stat-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.preview-stat-content {
  flex: 1;
  min-width: 0;
}

.preview-stat-value {
  font-size: var(--bookando-font-xl);
  font-weight: 700;
  line-height: 1;
}

.preview-stat-label {
  font-size: var(--bookando-font-xs);
  margin-top: 4px;
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
</style>
