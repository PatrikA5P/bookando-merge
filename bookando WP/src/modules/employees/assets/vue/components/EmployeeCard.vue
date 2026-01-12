<!-- EmployeeCard.vue -->
<template>
  <teleport to="body">
    <div
      v-if="employee"
      class="employee-card-overlay"
      @click.self="close"
    >
      <div class="employee-card">
        <!-- Top Toolbar -->
        <div class="employee-card-toolbar">
          <div class="toolbar-left">
            <AppButton
              icon="x"
              variant="ghost"
              size="square"
              btn-type="icononly"
              :tooltip="t('core.common.close')"
              @click="close"
            />
            <div class="toolbar-employee-info">
              <AppAvatar
                :src="employee?.avatar_url"
                :initials="initials(employee)"
                size="sm"
              />
              <div>
                <h2 class="toolbar-title">
                  {{ fullName }}
                </h2>
                <p class="toolbar-subtitle bookando-text-muted">
                  ID {{ employee?.id }}
                </p>
              </div>
            </div>
          </div>

          <div class="toolbar-actions">
            <AppButton
              v-if="!editMode"
              icon="edit"
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
              variant="ghost"
              size="square"
              btn-type="icononly"
              :tooltip="t('core.actions.send_email')"
              @click="sendEmail"
            />
            <AppButton
              icon="phone"
              variant="ghost"
              size="square"
              btn-type="icononly"
              :tooltip="t('core.actions.call')"
              @click="makeCall"
            />
            <AppButton
              icon="calendar"
              variant="ghost"
              size="square"
              btn-type="icononly"
              :tooltip="t('core.actions.new_appointment')"
              @click="newAppointment"
            />
            <AppButton
              icon="more-horizontal"
              variant="ghost"
              size="square"
              btn-type="icononly"
              :tooltip="t('core.common.more_actions')"
            />
          </div>
        </div>

        <!-- Tabs -->
        <div class="employee-card-tabs">
          <AppTabs
            v-model="activeTab"
            :tabs="tabs"
          />
        </div>

        <!-- Content -->
        <div class="employee-card-content">
          <!-- Overview Tab -->
          <div
            v-if="activeTab === 'overview'"
            class="tab-content"
          >
            <div class="content-grid">
              <!-- Quick Stats -->
              <div class="stats-section">
                <h3 class="section-title">
                  {{ t('mod.employees.sections.stats') }}
                </h3>
                <div class="stats-grid">
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
                        {{ t('mod.employees.stats.appointments') }}
                      </div>
                    </div>
                  </div>

                  <div class="stat-card">
                    <AppIcon
                      name="users"
                      class="stat-icon bookando-text-accent"
                    />
                    <div class="stat-content">
                      <div class="stat-value">
                        {{ stats.customers }}
                      </div>
                      <div class="stat-label bookando-text-muted">
                        {{ t('mod.employees.stats.customers') }}
                      </div>
                    </div>
                  </div>

                  <div class="stat-card">
                    <AppIcon
                      name="clock"
                      class="stat-icon bookando-text-warning"
                    />
                    <div class="stat-content">
                      <div class="stat-value">
                        {{ stats.hours }}
                      </div>
                      <div class="stat-label bookando-text-muted">
                        {{ t('mod.employees.stats.hours') }}
                      </div>
                    </div>
                  </div>

                  <div class="stat-card">
                    <AppIcon
                      name="dollar-sign"
                      class="stat-icon bookando-text-success"
                    />
                    <div class="stat-content">
                      <div class="stat-value">
                        {{ stats.revenue }}
                      </div>
                      <div class="stat-label bookando-text-muted">
                        {{ t('mod.employees.stats.revenue') }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Contact & Basic Info -->
              <div class="info-section">
                <h3 class="section-title">
                  {{ t('mod.employees.sections.contact') }}
                </h3>
                <div class="info-list">
                  <div
                    v-if="employee?.email"
                    class="info-item"
                  >
                    <AppIcon
                      name="mail"
                      class="info-icon"
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
                    class="info-item"
                  >
                    <AppIcon
                      name="phone"
                      class="info-icon"
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
            </div>
          </div>

          <!-- Details Tab -->
          <div
            v-else-if="activeTab === 'details'"
            class="tab-content"
          >
            <div class="section-placeholder">
              <AppIcon
                name="user"
                class="placeholder-icon"
              />
              <p class="placeholder-text">
                {{ t('mod.employees.tabs.details') }}
              </p>
            </div>
          </div>

          <!-- Schedule Tab -->
          <div
            v-else-if="activeTab === 'schedule'"
            class="tab-content"
          >
            <div class="section-placeholder">
              <AppIcon
                name="calendar"
                class="placeholder-icon"
              />
              <p class="placeholder-text">
                {{ t('mod.employees.tabs.schedule') }}
              </p>
            </div>
          </div>

          <!-- Services Tab -->
          <div
            v-else-if="activeTab === 'services'"
            class="tab-content"
          >
            <div class="section-placeholder">
              <AppIcon
                name="briefcase"
                class="placeholder-icon"
              />
              <p class="placeholder-text">
                {{ t('mod.employees.tabs.services') }}
              </p>
            </div>
          </div>

          <!-- Permissions Tab -->
          <div
            v-else-if="activeTab === 'permissions'"
            class="tab-content"
          >
            <div class="section-placeholder">
              <AppIcon
                name="shield"
                class="placeholder-icon"
              />
              <p class="placeholder-text">
                {{ t('mod.employees.tabs.permissions') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'

const { t } = useI18n()

const props = defineProps<{
  employee: any
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const editMode = ref(false)
const activeTab = ref('overview')

const tabs = computed(() => [
  { id: 'overview', label: t('mod.employees.tabs.overview') },
  { id: 'details', label: t('mod.employees.tabs.details') },
  { id: 'schedule', label: t('mod.employees.tabs.schedule') },
  { id: 'services', label: t('mod.employees.tabs.services') },
  { id: 'permissions', label: t('mod.employees.tabs.permissions') }
])

const fullName = computed(() => {
  if (!props.employee) return ''
  return `${props.employee.first_name || ''} ${props.employee.last_name || ''}`.trim()
})

// Placeholder stats - replace with real data
const stats = computed(() => ({
  appointments: 0,
  customers: 0,
  hours: 0,
  revenue: '€0'
}))

function initials(item: any): string {
  return ((item?.first_name?.[0] || '') + (item?.last_name?.[0] || '')).toUpperCase()
}

function normalizePhone(phone: string | number): string {
  return String(phone ?? '').replace(/\s+/g, '')
}

function close() {
  emit('close')
}

function saveChanges() {
  // TODO: Save changes
  editMode.value = false
}

function sendEmail() {
  if (props.employee?.email) {
    window.location.href = `mailto:${props.employee.email}`
  }
}

function makeCall() {
  if (props.employee?.phone) {
    window.location.href = `tel:${normalizePhone(props.employee.phone)}`
  }
}

function newAppointment() {
  // TODO: Open new appointment dialog
}

// ESC key support
function handleEscape(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    close()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
.employee-card-overlay {
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
}

.employee-card {
  width: 100%;
  height: 100%;
  background: var(--bookando-background);
  display: flex;
  flex-direction: column;
}

.employee-card-toolbar {
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

.toolbar-employee-info {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
}

.toolbar-title {
  font-size: var(--bookando-font-lg);
  font-weight: 600;
  margin: 0;
  line-height: 1.2;
}

.toolbar-subtitle {
  font-size: var(--bookando-font-xs);
  margin: 0;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
}

.employee-card-tabs {
  background: var(--bookando-surface);
  border-bottom: 1px solid var(--bookando-border);
  padding: 0 var(--bookando-space-lg);
}

.employee-card-content {
  flex: 1;
  overflow-y: auto;
  padding: var(--bookando-space-xl);
}

.tab-content {
  max-width: 1400px;
  margin: 0 auto;
}

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: var(--bookando-space-xl);
}

.section-title {
  font-size: var(--bookando-font-md);
  font-weight: 600;
  margin: 0 0 var(--bookando-space-lg);
}

.stats-section {
  grid-column: 1 / -1;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--bookando-space-md);
}

.stat-card {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-md);
  padding: var(--bookando-space-lg);
  background: var(--bookando-surface);
  border-radius: var(--bookando-radius-lg);
  border: 1px solid var(--bookando-border);
}

.stat-icon {
  font-size: 32px;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: var(--bookando-font-xxl);
  font-weight: 700;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: var(--bookando-font-sm);
}

.info-section {
  padding: var(--bookando-space-lg);
  background: var(--bookando-surface);
  border-radius: var(--bookando-radius-lg);
  border: 1px solid var(--bookando-border);
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-space-md);
}

.info-item {
  display: flex;
  align-items: center;
  gap: var(--bookando-space-sm);
  font-size: var(--bookando-font-sm);
}

.info-icon {
  color: var(--bookando-text-muted);
  flex-shrink: 0;
}

.section-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--bookando-space-xxl);
  text-align: center;
}

.placeholder-icon {
  font-size: 64px;
  color: var(--bookando-text-muted);
  opacity: 0.3;
  margin-bottom: var(--bookando-space-lg);
}

.placeholder-text {
  font-size: var(--bookando-font-lg);
  color: var(--bookando-text-muted);
  margin: 0;
}

@media (max-width: 768px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .toolbar-actions {
    flex-wrap: wrap;
  }
}
</style>
