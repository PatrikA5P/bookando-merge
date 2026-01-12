<!-- CRMActivityTimeline.vue - Activity Timeline Component -->
<template>
  <div class="crm-activity-timeline" :class="timelineClasses">
    <!-- Header -->
    <div v-if="showHeader" class="crm-activity-timeline__header">
      <h3 class="crm-activity-timeline__title">{{ title }}</h3>
      <slot name="headerActions">
        <AppButton
          v-if="filterable"
          variant="ghost"
          icon="filter"
          size="sm"
          @click="toggleFilter"
        >
          {{ filterLabel }}
        </AppButton>
      </slot>
    </div>

    <!-- Filter Bar -->
    <div v-if="showFilter && filterable" class="crm-activity-filter">
      <AppButton
        v-for="type in activityTypes"
        :key="type.id"
        :variant="activeFilter === type.id ? 'primary' : 'ghost'"
        :icon="type.icon"
        size="sm"
        @click="handleFilterChange(type.id)"
      >
        {{ type.label }}
      </AppButton>
    </div>

    <!-- Timeline Items -->
    <div class="crm-activity-timeline__content">
      <div
        v-if="!filteredActivities.length"
        class="crm-activity-timeline__empty"
      >
        <AppIcon :name="emptyIcon" />
        <p>{{ emptyMessage }}</p>
      </div>

      <div
        v-for="(activity, index) in paginatedActivities"
        :key="activity.id"
        class="crm-activity-item"
        :class="getActivityClasses(activity)"
      >
        <!-- Timeline Dot & Line -->
        <div class="crm-activity-item__timeline">
          <div class="crm-activity-item__dot">
            <AppIcon :name="getActivityIcon(activity)" />
          </div>
          <div
            v-if="index < paginatedActivities.length - 1"
            class="crm-activity-item__line"
          />
        </div>

        <!-- Activity Content -->
        <div class="crm-activity-item__content">
          <!-- Header -->
          <div class="crm-activity-item__header">
            <div class="crm-activity-item__info">
              <h4 class="crm-activity-item__title">
                {{ activity.title }}
              </h4>
              <div class="crm-activity-item__meta">
                <span class="crm-activity-item__time">
                  {{ formatTime(activity.timestamp) }}
                </span>
                <span v-if="activity.user" class="crm-activity-item__user">
                  <AppIcon name="user" />
                  {{ activity.user }}
                </span>
              </div>
            </div>
            <AppBadge
              v-if="activity.type"
              :label="getTypeLabel(activity.type)"
              :variant="getTypeVariant(activity.type)"
              size="sm"
            />
          </div>

          <!-- Description -->
          <div
            v-if="activity.description"
            class="crm-activity-item__description"
          >
            {{ activity.description }}
          </div>

          <!-- Custom Content Slot -->
          <div v-if="$slots[`activity-${activity.type}`]" class="crm-activity-item__custom">
            <slot :name="`activity-${activity.type}`" :activity="activity" />
          </div>

          <!-- Attachments -->
          <div
            v-if="activity.attachments?.length"
            class="crm-activity-item__attachments"
          >
            <AppIcon name="paperclip" />
            <span>{{ activity.attachments.length }} {{ attachmentLabel }}</span>
          </div>

          <!-- Actions -->
          <div
            v-if="showActions"
            class="crm-activity-item__actions"
          >
            <slot name="itemActions" :activity="activity">
              <AppButton
                variant="ghost"
                icon="eye"
                size="sm"
                @click="$emit('view', activity)"
              >
                {{ viewLabel }}
              </AppButton>
              <AppButton
                v-if="activity.editable"
                variant="ghost"
                icon="edit"
                size="sm"
                @click="$emit('edit', activity)"
              >
                {{ editLabel }}
              </AppButton>
            </slot>
          </div>
        </div>
      </div>
    </div>

    <!-- Load More / Pagination -->
    <div v-if="hasMore" class="crm-activity-timeline__footer">
      <AppButton
        variant="ghost"
        :loading="loading"
        @click="loadMore"
      >
        {{ loadMoreLabel }}
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppButton from './AppButton.vue'
import AppIcon from './AppIcon.vue'
import AppBadge from './AppBadge.vue'

export interface Activity {
  id: string | number
  type: string
  title: string
  description?: string
  timestamp: string | Date
  user?: string
  attachments?: any[]
  editable?: boolean
  metadata?: Record<string, any>
}

export interface ActivityType {
  id: string
  label: string
  icon: string
}

export interface CRMActivityTimelineProps {
  activities: Activity[]
  title?: string
  showHeader?: boolean
  filterable?: boolean
  showActions?: boolean
  itemsPerPage?: number
  loading?: boolean
  // Labels
  filterLabel?: string
  emptyMessage?: string
  emptyIcon?: string
  attachmentLabel?: string
  viewLabel?: string
  editLabel?: string
  loadMoreLabel?: string
  // Activity Types
  activityTypes?: ActivityType[]
}

const props = withDefaults(defineProps<CRMActivityTimelineProps>(), {
  title: 'Activity',
  showHeader: true,
  filterable: true,
  showActions: true,
  itemsPerPage: 20,
  loading: false,
  filterLabel: 'Filter',
  emptyMessage: 'No activity to display',
  emptyIcon: 'activity',
  attachmentLabel: 'attachments',
  viewLabel: 'View',
  editLabel: 'Edit',
  loadMoreLabel: 'Load More',
  activityTypes: () => [
    { id: 'all', label: 'All', icon: 'list' },
    { id: 'email', label: 'Emails', icon: 'mail' },
    { id: 'call', label: 'Calls', icon: 'phone' },
    { id: 'meeting', label: 'Meetings', icon: 'video' },
    { id: 'note', label: 'Notes', icon: 'file-text' },
    { id: 'task', label: 'Tasks', icon: 'check-square' },
    { id: 'status', label: 'Status', icon: 'activity' }
  ]
})

const emit = defineEmits<{
  (e: 'view', activity: Activity): void
  (e: 'edit', activity: Activity): void
  (e: 'loadMore'): void
  (e: 'filterChange', type: string): void
}>()

// State
const showFilter = ref(false)
const activeFilter = ref('all')
const currentPage = ref(1)

// Computed
const timelineClasses = computed(() => ({
  'is-loading': props.loading,
  'has-filter': showFilter.value
}))

const filteredActivities = computed(() => {
  if (activeFilter.value === 'all') {
    return props.activities
  }
  return props.activities.filter(activity => activity.type === activeFilter.value)
})

const paginatedActivities = computed(() => {
  const end = currentPage.value * props.itemsPerPage
  return filteredActivities.value.slice(0, end)
})

const hasMore = computed(() => {
  return paginatedActivities.value.length < filteredActivities.value.length
})

// Methods
function toggleFilter() {
  showFilter.value = !showFilter.value
}

function handleFilterChange(type: string) {
  activeFilter.value = type
  currentPage.value = 1
  emit('filterChange', type)
}

function loadMore() {
  if (!props.loading && hasMore.value) {
    currentPage.value++
    emit('loadMore')
  }
}

function getActivityClasses(activity: Activity) {
  return {
    [`crm-activity-item--${activity.type}`]: true,
    'is-editable': activity.editable
  }
}

function getActivityIcon(activity: Activity): string {
  const iconMap: Record<string, string> = {
    email: 'mail',
    call: 'phone',
    meeting: 'video',
    note: 'file-text',
    task: 'check-square',
    status: 'activity',
    appointment: 'calendar',
    payment: 'credit-card',
    document: 'file',
    message: 'message-square'
  }
  return iconMap[activity.type] || 'circle'
}

function getTypeLabel(type: string): string {
  const typeObj = props.activityTypes.find(t => t.id === type)
  return typeObj?.label || type.charAt(0).toUpperCase() + type.slice(1)
}

function getTypeVariant(type: string): string {
  const variantMap: Record<string, string> = {
    email: 'info',
    call: 'primary',
    meeting: 'primary',
    note: 'default',
    task: 'success',
    status: 'warning',
    appointment: 'primary',
    payment: 'success',
    error: 'danger'
  }
  return variantMap[type] || 'default'
}

function formatTime(timestamp: string | Date): string {
  if (!timestamp) return ''

  try {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins}m ago`
    if (diffHours < 24) return `${diffHours}h ago`
    if (diffDays < 7) return `${diffDays}d ago`

    return date.toLocaleDateString(undefined, {
      month: 'short',
      day: 'numeric',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    })
  } catch {
    return String(timestamp)
  }
}
</script>

<style lang="scss" scoped>
// Component uses global styles from _crm-split-view.scss
// Add component-specific refinements here

.crm-activity-timeline {
  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--bookando-spacing-md);
  }

  &__title {
    font-size: var(--bookando-font-size-lg);
    font-weight: 600;
    margin: 0;
  }

  &__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--bookando-spacing-2xl);
    color: var(--bookando-text-muted);
    text-align: center;

    svg {
      width: 48px;
      height: 48px;
      margin-bottom: var(--bookando-spacing-md);
      opacity: 0.3;
    }
  }

  &__footer {
    display: flex;
    justify-content: center;
    padding-top: var(--bookando-spacing-lg);
  }

  &.is-loading {
    opacity: 0.6;
    pointer-events: none;
  }
}

.crm-activity-filter {
  display: flex;
  flex-wrap: wrap;
  gap: var(--bookando-spacing-xs);
  margin-bottom: var(--bookando-spacing-lg);
  padding-bottom: var(--bookando-spacing-md);
  border-bottom: 1px solid var(--bookando-border);
}

.crm-activity-item {
  &:last-child {
    .crm-activity-item__line {
      display: none;
    }
  }

  &.is-editable {
    .crm-activity-item__content {
      cursor: pointer;

      &:hover {
        background: var(--bookando-bg-soft);
      }
    }
  }
}

// Smooth fade-in animation for new items
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.crm-activity-item {
  animation: slideInUp 0.3s ease-out;
}
</style>
