<!-- CRMDetailPanel.vue - Detail Panel with Modern Design -->
<template>
  <div class="crm-detail-panel" :class="panelClasses">
    <!-- Mobile/Tablet Close Button -->
    <button
      v-if="showCloseButton"
      class="crm-detail-panel__close"
      @click="$emit('close')"
    >
      <AppIcon name="x" />
    </button>

    <!-- Compact Header Section -->
    <div class="crm-detail-panel__header">
      <div class="crm-detail-header">
        <div class="crm-detail-header__top">
          <!-- Avatar -->
          <AppAvatar
            :src="item?.avatar_url"
            :initials="getInitials(item)"
            :size="avatarSize"
            class="crm-detail-header__avatar"
          />

          <!-- Info right of Avatar -->
          <div class="crm-detail-header__info">
            <h2 class="crm-detail-header__title">
              {{ fullName }}
            </h2>
            <div class="crm-detail-header__meta">
              <AppBadge
                v-if="item?.status"
                :variant="getStatusVariant(item.status)"
                :label="getStatusLabel(item.status)"
                size="sm"
              />
              <span v-if="item?.id" class="crm-detail-header__id">
                ID {{ item.id }}
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div class="crm-detail-header__actions">
            <slot name="headerActions">
              <AppButton
                v-if="editable"
                variant="primary"
                :icon="editMode ? 'check' : 'edit'"
                :size="actionButtonSize"
                @click="handleEditToggle"
              >
                {{ editMode ? saveLabel : editLabel }}
              </AppButton>
              <AppButton
                v-if="showMoreActions"
                variant="ghost"
                icon="more-vertical"
                :size="actionButtonSize"
                btn-type="icononly"
                @click="toggleActionsMenu"
              />
            </slot>
          </div>
        </div>

        <!-- Contact Info under Header -->
        <div class="crm-detail-header__contacts">
          <div v-if="item?.email" class="crm-detail-contact">
            <AppIcon name="mail" />
            <a :href="`mailto:${item.email}`" class="bookando-link">
              {{ item.email }}
            </a>
          </div>
          <div v-if="item?.phone" class="crm-detail-contact">
            <AppIcon name="phone" />
            <a :href="`tel:${normalizePhone(item.phone)}`" class="bookando-link">
              {{ item.phone }}
            </a>
          </div>
          <div v-if="hasAddress" class="crm-detail-contact">
            <AppIcon name="map-pin" />
            <span>{{ getFullAddress }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Tab Navigation - Pill Buttons -->
    <div class="crm-detail-panel__tabs">
      <div class="crm-tabs-modern">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="['crm-tab-pill', { 'is-active': activeTab === tab.id }]"
          :title="tab.label"
          @click="handleTabChange(tab.id)"
        >
          <AppIcon v-if="tab.icon" :name="tab.icon" class="crm-tab-pill__icon" />
          <span class="crm-tab-pill__label">{{ tab.label }}</span>
          <span
            v-if="tab.count !== undefined"
            class="crm-tab-pill__count"
          >
            {{ tab.count }}
          </span>
        </button>
      </div>
    </div>

    <!-- Tab Content -->
    <div ref="contentRef" class="crm-detail-panel__content">
      <!-- Overview Tab -->
      <div v-if="activeTab === 'overview'" class="crm-tab-content">
        <slot name="overview" :item="item" :editMode="editMode">
          <!-- Quick Stats -->
          <div v-if="stats.length" class="crm-stats-section">
            <CRMQuickStats :stats="stats" :columns="2" :animated="false" />
          </div>

          <!-- Personal Info Section -->
          <div class="crm-detail-section">
            <h3 class="crm-detail-section__title">
              <AppIcon name="user" />
              {{ personalSectionTitle }}
            </h3>
            <div class="crm-detail-list">
              <div v-if="item?.language" class="crm-detail-item">
                <span class="crm-detail-item__label">{{ languageLabel }}:</span>
                <span class="crm-detail-item__value">{{ getLanguageLabel(item.language) }}</span>
              </div>
              <div v-if="item?.gender" class="crm-detail-item">
                <span class="crm-detail-item__label">{{ genderLabel }}:</span>
                <span class="crm-detail-item__value">{{ getGenderLabel(item.gender) }}</span>
              </div>
              <div v-if="item?.date_of_birth" class="crm-detail-item">
                <span class="crm-detail-item__label">{{ dateOfBirthLabel }}:</span>
                <span class="crm-detail-item__value">{{ formatDate(item.date_of_birth) }}</span>
              </div>
            </div>
          </div>
        </slot>
      </div>

      <!-- Contact Tab -->
      <div v-if="activeTab === 'contact'" class="crm-tab-content">
        <slot name="contact" :item="item" :editMode="editMode">
          <div class="crm-detail-section">
            <h3 class="crm-detail-section__title">{{ contactDetailsTitle }}</h3>
            <div class="crm-detail-list">
              <div v-if="item?.email" class="crm-detail-item">
                <span class="crm-detail-item__label">{{ emailLabel }}:</span>
                <span class="crm-detail-item__value">{{ item.email }}</span>
              </div>
              <div v-if="item?.phone" class="crm-detail-item">
                <span class="crm-detail-item__label">{{ phoneLabel }}:</span>
                <span class="crm-detail-item__value">{{ item.phone }}</span>
              </div>
              <div v-if="item?.mobile" class="crm-detail-item">
                <span class="crm-detail-item__label">{{ mobileLabel }}:</span>
                <span class="crm-detail-item__value">{{ item.mobile }}</span>
              </div>
            </div>
          </div>

          <!-- Address Section -->
          <div v-if="hasAddress" class="crm-detail-section">
            <h3 class="crm-detail-section__title">
              <AppIcon name="map-pin" />
              {{ addressSectionTitle }}
            </h3>
            <div class="crm-detail-address">
              <div v-if="item?.address">{{ item.address }}</div>
              <div v-if="item?.zip || item?.city">
                {{ item.zip }} {{ item.city }}
              </div>
              <div v-if="item?.country">
                {{ getCountryLabel(item.country) }}
              </div>
            </div>
          </div>
        </slot>
      </div>

      <!-- Appointments Tab -->
      <div v-if="activeTab === 'appointments'" class="crm-tab-content">
        <slot name="appointments" :item="item">
          <AppEmptyState
            :title="noAppointmentsTitle"
            :description="noAppointmentsDescription"
            icon="calendar"
            :action-label="addAppointmentLabel"
            @action="$emit('addAppointment', item)"
          />
        </slot>
      </div>

      <!-- Activity Tab -->
      <div v-if="activeTab === 'activity'" class="crm-tab-content">
        <slot name="activity" :item="item">
          <AppEmptyState
            :title="noActivityTitle"
            :description="noActivityDescription"
            icon="activity"
          />
        </slot>
      </div>

      <!-- Notes Tab -->
      <div v-if="activeTab === 'notes'" class="crm-tab-content">
        <slot name="notes" :item="item" :editMode="editMode">
          <AppEmptyState
            :title="noNotesTitle"
            :description="noNotesDescription"
            icon="file-text"
            :action-label="addNoteLabel"
            @action="$emit('addNote', item)"
          />
        </slot>
      </div>

      <!-- Files Tab -->
      <div v-if="activeTab === 'files'" class="crm-tab-content">
        <slot name="files" :item="item">
          <AppEmptyState
            :title="noFilesTitle"
            :description="noFilesDescription"
            icon="folder"
            :action-label="uploadFileLabel"
            @action="$emit('uploadFile', item)"
          />
        </slot>
      </div>

      <!-- Custom Tab Content -->
      <div
        v-for="customTab in customTabs"
        v-show="activeTab === customTab.id"
        :key="customTab.id"
        class="crm-tab-content"
      >
        <slot :name="`tab-${customTab.id}`" :item="item" :editMode="editMode" />
      </div>
    </div>

    <!-- Footer Actions (optional) -->
    <div v-if="$slots.footer || showFooter" class="crm-detail-panel__footer">
      <slot name="footer" :item="item" :editMode="editMode" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppAvatar from './AppAvatar.vue'
import AppButton from './AppButton.vue'
import AppIcon from './AppIcon.vue'
import AppBadge from './AppBadge.vue'
import AppEmptyState from './AppEmptyState.vue'
import CRMQuickStats from './CRMQuickStats.vue'

export interface Tab {
  id: string
  label: string
  icon?: string
  count?: number
}

export interface Stat {
  label: string
  value: string | number
  icon: string
  variant?: string
}

export interface CRMDetailPanelProps {
  item: any
  tabs?: Tab[]
  stats?: Stat[]
  defaultTab?: string
  editable?: boolean
  editMode?: boolean
  showCloseButton?: boolean
  showMoreActions?: boolean
  showFooter?: boolean
  // Labels
  editLabel?: string
  saveLabel?: string
  contactSectionTitle?: string
  addressSectionTitle?: string
  personalSectionTitle?: string
  contactDetailsTitle?: string
  emailLabel?: string
  phoneLabel?: string
  mobileLabel?: string
  languageLabel?: string
  genderLabel?: string
  dateOfBirthLabel?: string
  noAppointmentsTitle?: string
  noAppointmentsDescription?: string
  addAppointmentLabel?: string
  noActivityTitle?: string
  noActivityDescription?: string
  noNotesTitle?: string
  noNotesDescription?: string
  addNoteLabel?: string
  noFilesTitle?: string
  noFilesDescription?: string
  uploadFileLabel?: string
}

const props = withDefaults(defineProps<CRMDetailPanelProps>(), {
  tabs: () => [
    { id: 'overview', label: 'Overview', icon: 'home' },
    { id: 'contact', label: 'Contact', icon: 'mail' },
    { id: 'appointments', label: 'Appointments', icon: 'calendar', count: 0 },
    { id: 'activity', label: 'Activity', icon: 'activity' },
    { id: 'notes', label: 'Notes', icon: 'file-text', count: 0 },
    { id: 'files', label: 'Files', icon: 'folder', count: 0 }
  ],
  stats: () => [],
  defaultTab: 'overview',
  editable: true,
  editMode: false,
  showCloseButton: true,
  showMoreActions: true,
  showFooter: false,
  editLabel: 'Edit',
  saveLabel: 'Save',
  contactSectionTitle: 'Contact Information',
  addressSectionTitle: 'Address',
  personalSectionTitle: 'Personal Information',
  contactDetailsTitle: 'Contact Details',
  emailLabel: 'Email',
  phoneLabel: 'Phone',
  mobileLabel: 'Mobile',
  languageLabel: 'Language',
  genderLabel: 'Gender',
  dateOfBirthLabel: 'Date of Birth',
  noAppointmentsTitle: 'No appointments',
  noAppointmentsDescription: 'No appointments scheduled yet.',
  addAppointmentLabel: 'Add Appointment',
  noActivityTitle: 'No activity',
  noActivityDescription: 'No recent activity to display.',
  noNotesTitle: 'No notes',
  noNotesDescription: 'No notes added yet.',
  addNoteLabel: 'Add Note',
  noFilesTitle: 'No files',
  noFilesDescription: 'No files uploaded yet.',
  uploadFileLabel: 'Upload File'
})

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'edit', item: any): void
  (e: 'save', item: any): void
  (e: 'tabChange', tabId: string): void
  (e: 'addAppointment', item: any): void
  (e: 'addNote', item: any): void
  (e: 'uploadFile', item: any): void
  (e: 'moreActions'): void
}>()

// State
const activeTab = ref(props.defaultTab)
const contentRef = ref<HTMLElement>()

// Computed
const panelClasses = computed(() => ({
  'is-edit-mode': props.editMode
}))

const fullName = computed(() => {
  if (!props.item) return ''
  const first = props.item.first_name || ''
  const last = props.item.last_name || ''
  return `${first} ${last}`.trim() || 'Unnamed'
})

const hasAddress = computed(() => {
  const item = props.item
  return item?.address || item?.zip || item?.city || item?.country
})

const getFullAddress = computed(() => {
  const parts = []
  if (props.item?.address) parts.push(props.item.address)
  if (props.item?.zip || props.item?.city) {
    parts.push(`${props.item?.zip || ''} ${props.item?.city || ''}`.trim())
  }
  return parts.join(', ')
})

const customTabs = computed(() => {
  const standardTabIds = ['overview', 'contact', 'appointments', 'activity', 'notes', 'files']
  return props.tabs.filter(tab => !standardTabIds.includes(tab.id))
})

const avatarSize = computed(() => {
  return window.innerWidth < 768 ? 'md' : 'lg'
})

const actionButtonSize = computed(() => {
  return window.innerWidth < 768 ? 'sm' : 'md'
})

// Methods
function handleTabChange(tabId: string) {
  activeTab.value = tabId
  emit('tabChange', tabId)
  scrollToTop()
}

function handleEditToggle() {
  if (props.editMode) {
    emit('save', props.item)
  } else {
    emit('edit', props.item)
  }
}

function toggleActionsMenu() {
  emit('moreActions')
}

function scrollToTop() {
  if (contentRef.value) {
    contentRef.value.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function getInitials(item: any): string {
  if (!item) return '?'
  const first = item.first_name?.[0] || ''
  const last = item.last_name?.[0] || ''
  return (first + last).toUpperCase() || '?'
}

function getStatusVariant(status: string): string {
  const statusMap: Record<string, string> = {
    active: 'success',
    inactive: 'default',
    pending: 'warning',
    blocked: 'danger',
    on_leave: 'warning'
  }
  return statusMap[status] || 'default'
}

function getStatusLabel(status: string): string {
  return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
}

function getCountryLabel(country: string): string {
  // TODO: Implement proper country label resolution
  return country
}

function getLanguageLabel(language: string): string {
  // TODO: Implement proper language label resolution
  return language
}

function getGenderLabel(gender: string): string {
  const genderMap: Record<string, string> = {
    male: 'Male',
    female: 'Female',
    other: 'Other'
  }
  return genderMap[gender] || gender
}

function formatDate(date: string): string {
  if (!date) return ''
  try {
    return new Date(date).toLocaleDateString()
  } catch {
    return date
  }
}

function normalizePhone(phone: string | number): string {
  return String(phone ?? '').replace(/\s+/g, '')
}

// Watch for item changes to reset tab if needed
watch(
  () => props.item,
  (newItem, oldItem) => {
    if (newItem?.id !== oldItem?.id) {
      activeTab.value = props.defaultTab
      scrollToTop()
    }
  }
)

// Watch for default tab changes
watch(
  () => props.defaultTab,
  (newTab) => {
    if (newTab) {
      activeTab.value = newTab
    }
  }
)
</script>

<style lang="scss" scoped>
.crm-detail-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--bookando-surface);

  &__close {
    position: absolute;
    top: var(--bookando-spacing-md);
    right: var(--bookando-spacing-md);
    z-index: 100;
    padding: var(--bookando-spacing-xs);
    border: none;
    background: var(--bookando-bg-soft);
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
      background: var(--bookando-border);
    }

    @media (min-width: 768px) {
      display: none;
    }
  }

  &__header {
    flex-shrink: 0;
    padding: var(--bookando-spacing-lg);
    border-bottom: 1px solid var(--bookando-border);
  }

  &__tabs {
    flex-shrink: 0;
    padding: var(--bookando-spacing-md) var(--bookando-spacing-lg);
    border-bottom: 1px solid var(--bookando-border);
    overflow-x: auto;
    overflow-y: hidden;

    &::-webkit-scrollbar {
      height: 4px;
    }

    &::-webkit-scrollbar-thumb {
      background: var(--bookando-border);
      border-radius: 2px;
    }
  }

  &__content {
    flex: 1;
    overflow-y: auto;
    padding: var(--bookando-spacing-lg);
  }

  &__footer {
    flex-shrink: 0;
    padding: var(--bookando-spacing-lg);
    border-top: 1px solid var(--bookando-border);
  }

  &.is-edit-mode {
    .crm-detail-header__actions {
      opacity: 1;
    }
  }
}

// Compact Header
.crm-detail-header {
  &__top {
    display: flex;
    align-items: flex-start;
    gap: var(--bookando-spacing-md);
    margin-bottom: var(--bookando-spacing-md);
  }

  &__avatar {
    flex-shrink: 0;
  }

  &__info {
    flex: 1;
    min-width: 0;
  }

  &__title {
    font-size: var(--bookando-font-size-xl);
    font-weight: 600;
    margin: 0 0 var(--bookando-spacing-xxs) 0;
    line-height: 1.3;
  }

  &__meta {
    display: flex;
    align-items: center;
    gap: var(--bookando-spacing-sm);
    flex-wrap: wrap;
  }

  &__id {
    font-size: var(--bookando-font-size-sm);
    color: var(--bookando-text-muted);
  }

  &__actions {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: var(--bookando-spacing-xs);
  }

  &__contacts {
    display: flex;
    flex-direction: column;
    gap: var(--bookando-spacing-xs);
    padding-top: var(--bookando-spacing-md);
    border-top: 1px solid var(--bookando-border);
  }
}

.crm-detail-contact {
  display: flex;
  align-items: center;
  gap: var(--bookando-spacing-sm);
  font-size: var(--bookando-font-size-sm);
  color: var(--bookando-text-muted);

  svg {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
  }

  a {
    color: var(--bookando-primary);
    text-decoration: none;

    &:hover {
      text-decoration: underline;
    }
  }
}

// Modern Pill Tabs
.crm-tabs-modern {
  display: flex;
  gap: var(--bookando-spacing-xs);
  flex-wrap: nowrap;
}

.crm-tab-pill {
  display: inline-flex;
  align-items: center;
  gap: var(--bookando-spacing-xs);
  padding: var(--bookando-spacing-sm) var(--bookando-spacing-md);
  border: none;
  background: var(--bookando-bg-soft);
  border-radius: 20px;
  font-size: var(--bookando-font-size-sm);
  font-weight: 500;
  color: var(--bookando-text-muted);
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;

  &__icon {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
    transition: transform 0.2s ease;
  }

  &__label {
    max-width: 0;
    overflow: hidden;
    opacity: 0;
    transition: all 0.2s ease;
  }

  &__count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    background: var(--bookando-border);
    border-radius: 9px;
    font-size: var(--bookando-font-size-xs);
    font-weight: 600;
    line-height: 1;
  }

  &:hover {
    background: var(--bookando-border);
    color: var(--bookando-text);

    .crm-tab-pill__label {
      max-width: 200px;
      opacity: 1;
    }

    .crm-tab-pill__icon {
      transform: scale(1.1);
    }
  }

  &.is-active {
    background: var(--bookando-primary);
    color: var(--bookando-white);

    .crm-tab-pill__label {
      max-width: 200px;
      opacity: 1;
    }

    .crm-tab-pill__count {
      background: rgba(255, 255, 255, 0.2);
      color: var(--bookando-white);
    }
  }
}

// Tab Content
.crm-tab-content {
  animation: fadeIn 0.2s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.crm-stats-section {
  margin-bottom: var(--bookando-spacing-xl);
}

.crm-detail-section {
  margin-bottom: var(--bookando-spacing-xl);

  &:last-child {
    margin-bottom: 0;
  }

  &__title {
    display: flex;
    align-items: center;
    gap: var(--bookando-spacing-sm);
    font-size: var(--bookando-font-size-lg);
    font-weight: 600;
    margin: 0 0 var(--bookando-spacing-md) 0;
    color: var(--bookando-text);

    svg {
      width: 20px;
      height: 20px;
      color: var(--bookando-primary);
    }
  }
}

.crm-detail-list {
  display: flex;
  flex-direction: column;
  gap: var(--bookando-spacing-sm);
}

.crm-detail-item {
  display: flex;
  align-items: flex-start;
  gap: var(--bookando-spacing-md);
  font-size: var(--bookando-font-size-sm);

  &__label {
    flex-shrink: 0;
    min-width: 120px;
    font-weight: 500;
    color: var(--bookando-text-muted);
  }

  &__value {
    flex: 1;
    color: var(--bookando-text);
  }
}

.crm-detail-address {
  font-size: var(--bookando-font-size-sm);
  line-height: 1.6;
  color: var(--bookando-text);

  div {
    margin-bottom: var(--bookando-spacing-xxs);

    &:last-child {
      margin-bottom: 0;
    }
  }
}

// Responsive
@media (max-width: 767px) {
  .crm-detail-header {
    &__title {
      font-size: var(--bookando-font-size-lg);
    }

    &__top {
      flex-wrap: wrap;
    }

    &__actions {
      width: 100%;
      justify-content: flex-end;
    }
  }

  .crm-tab-pill {
    &__label {
      max-width: 0 !important;
      opacity: 0 !important;
    }

    &.is-active .crm-tab-pill__label {
      max-width: 100px;
      opacity: 1 !important;
    }
  }
}
</style>
