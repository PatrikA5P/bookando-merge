<!-- CRMListItem.vue - List Item with Swipe Actions -->
<template>
  <div
    ref="itemRef"
    class="crm-list-item"
    :class="itemClasses"
  >
    <!-- Swipe Actions Background (Left) -->
    <div
      v-if="leftActions.length && enableSwipe"
      class="crm-list-item-swipe crm-list-item-swipe--left"
    >
      <button
        v-for="action in leftActions"
        :key="action.id"
        :class="['crm-list-item-action', `crm-list-item-action--${action.variant || 'primary'}`]"
        @click.stop="handleAction(action)"
      >
        <AppIcon :name="action.icon" />
        <span class="crm-list-item-action__label">{{ action.label }}</span>
      </button>
    </div>

    <!-- Swipe Actions Background (Right) -->
    <div
      v-if="rightActions.length && enableSwipe"
      class="crm-list-item-swipe crm-list-item-swipe--right"
    >
      <button
        v-for="action in rightActions"
        :key="action.id"
        :class="['crm-list-item-action', `crm-list-item-action--${action.variant || 'danger'}`]"
        @click.stop="handleAction(action)"
      >
        <AppIcon :name="action.icon" />
        <span class="crm-list-item-action__label">{{ action.label }}</span>
      </button>
    </div>

    <!-- Main Content -->
    <div
      ref="contentRef"
      class="crm-list-item__content"
      @click="handleClick"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
      @mousedown="handleMouseDown"
      @mousemove="handleMouseMove"
      @mouseup="handleMouseUp"
      @mouseleave="handleMouseLeave"
    >
      <!-- Checkbox (Multi-Select) -->
      <div
        v-if="multiSelect"
        class="crm-list-item__checkbox"
        @click.stop
      >
        <AppCheckbox
          :model-value="isSelected"
          align="left"
          @update:model-value="handleToggleSelect"
        />
      </div>

      <!-- Avatar -->
      <div class="crm-list-item__avatar">
        <AppAvatar
          :src="item.avatar_url"
          :initials="getInitials(item)"
          :size="avatarSize"
          fit="cover"
          :alt="`${item.first_name ?? ''} ${item.last_name ?? ''}`.trim()"
        />
      </div>

      <!-- Info -->
      <div class="crm-list-item__info">
        <div class="crm-list-item__name">
          {{ fullName }}
          <span v-if="item.id" class="crm-list-item__id">
            (ID {{ item.id }})
          </span>
        </div>
        <div class="crm-list-item__contacts">
          <div v-if="item.email" class="crm-list-item__contact">
            <AppIcon name="mail" class="bookando-icon bookando-mr-xxs" />
            <a
              :href="`mailto:${item.email}`"
              class="bookando-link"
              @click.stop
            >{{ item.email }}</a>
          </div>
          <div v-if="item.phone" class="crm-list-item__contact">
            <AppIcon name="phone" class="bookando-icon bookando-mr-xxs" />
            <a
              :href="`tel:${normalizePhone(item.phone)}`"
              class="bookando-link"
              @click.stop
            >{{ item.phone }}</a>
          </div>
        </div>
        <div v-if="showMeta && $slots.meta" class="crm-list-item__meta">
          <slot name="meta" :item="item" />
        </div>
      </div>

      <!-- Status Badge -->
      <div v-if="item.status && showStatusBadge" class="crm-list-item__status">
        <span :class="['bookando-status-label', getStatusClass(item.status)]">
          <span class="status-label-text">{{ getStatusLabel(item.status) }}</span>
        </span>
      </div>

      <!-- Quick Actions (Desktop) -->
      <div
        v-if="showQuickActions"
        class="crm-list-item__actions"
        @click.stop
      >
        <slot name="quickActions" :item="item">
          <AppButton
            icon="edit"
            variant="standard"
            size="square"
            btn-type="icononly"
            icon-size="md"
            tooltip="Edit"
            @click="$emit('edit', item)"
          />
          <AppPopover
            trigger-mode="icon"
            trigger-icon="more-horizontal"
            trigger-variant="standard"
            :offset="2"
            width="content"
            :panel-min-width="220"
            :close-on-item-click="true"
          >
            <template #content="{ close }">
              <slot name="moreActions" :item="item" :close="close" />
            </template>
          </AppPopover>
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppAvatar from './AppAvatar.vue'
import AppIcon from './AppIcon.vue'
import AppCheckbox from './AppCheckbox.vue'
import AppButton from './AppButton.vue'
import AppPopover from './AppPopover.vue'

export interface SwipeAction {
  id: string
  label: string
  icon: string
  variant?: 'primary' | 'success' | 'warning' | 'danger'
  handler: (item: any) => void
}

export interface CRMListItemProps {
  item: any
  isActive?: boolean
  isSelected?: boolean
  multiSelect?: boolean
  showStatus?: boolean
  showStatusBadge?: boolean
  showMeta?: boolean
  showQuickActions?: boolean
  showChevron?: boolean
  avatarSize?: 'sm' | 'md' | 'lg'
  leftActions?: SwipeAction[]
  rightActions?: SwipeAction[]
  swipeThreshold?: number
  enableSwipe?: boolean
}

const props = withDefaults(defineProps<CRMListItemProps>(), {
  isActive: false,
  isSelected: false,
  multiSelect: true,
  showStatus: true,
  showStatusBadge: true,
  showMeta: true,
  showQuickActions: true,
  showChevron: false,
  avatarSize: 'md',
  leftActions: () => [],
  rightActions: () => [],
  swipeThreshold: 80,
  enableSwipe: true
})

const emit = defineEmits<{
  (e: 'click', item: any): void
  (e: 'select', item: any): void
  (e: 'toggleSelect', item: any): void
  (e: 'edit', item: any): void
  (e: 'moreActions', item: any): void
  (e: 'action', action: SwipeAction, item: any): void
}>()

// Refs
const itemRef = ref<HTMLElement>()
const contentRef = ref<HTMLElement>()

// Swipe state
const startX = ref(0)
const startY = ref(0)
const currentX = ref(0)
const isDragging = ref(false)
const isMouseDragging = ref(false)

// Computed
const itemClasses = computed(() => ({
  'is-active': props.isActive,
  'is-selected': props.isSelected,
  'is-dragging': isDragging.value,
  'has-left-actions': props.leftActions.length > 0,
  'has-right-actions': props.rightActions.length > 0
}))

const fullName = computed(() => {
  if (!props.item) return ''
  const first = props.item.first_name || ''
  const last = props.item.last_name || ''
  return `${first} ${last}`.trim() || 'Unnamed'
})

// Methods
function handleClick() {
  if (!isDragging.value) {
    emit('click', props.item)
    emit('select', props.item)
  }
}

function handleToggleSelect() {
  emit('toggleSelect', props.item)
}

function handleAction(action: SwipeAction) {
  action.handler(props.item)
  emit('action', action, props.item)
  resetSwipe()
}

function getInitials(item: any): string {
  if (!item) return '?'
  const first = item.first_name?.[0] || ''
  const last = item.last_name?.[0] || ''
  return (first + last).toUpperCase() || '?'
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    active: 'Active',
    inactive: 'Inactive',
    pending: 'Pending',
    blocked: 'Blocked',
    on_leave: 'On Leave'
  }
  return labels[status] || status
}

function getStatusClass(status: string): string {
  return `bookando-status-${status || 'default'}`
}

function formatDate(date: string | Date): string {
  if (!date) return ''
  try {
    const d = new Date(date)
    const now = new Date()
    const diffMs = now.getTime() - d.getTime()
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffDays === 0) return 'Today'
    if (diffDays === 1) return 'Yesterday'
    if (diffDays < 7) return `${diffDays} days ago`

    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
  } catch {
    return String(date)
  }
}

// Touch event handlers
function handleTouchStart(e: TouchEvent) {
  if (!props.enableSwipe) return

  startX.value = e.touches[0].clientX
  startY.value = e.touches[0].clientY
  currentX.value = 0
  isDragging.value = false
}

function handleTouchMove(e: TouchEvent) {
  if (!props.enableSwipe || !contentRef.value) return

  const deltaX = e.touches[0].clientX - startX.value
  const deltaY = e.touches[0].clientY - startY.value

  // Check if horizontal swipe (not vertical scroll)
  if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
    isDragging.value = true
    currentX.value = deltaX

    // Limit swipe distance
    const maxSwipe = 120
    const limitedX = Math.max(-maxSwipe, Math.min(maxSwipe, deltaX))

    contentRef.value.style.transform = `translateX(${limitedX}px)`
    contentRef.value.style.transition = 'none'
  }
}

function handleTouchEnd() {
  if (!props.enableSwipe || !contentRef.value) return

  const threshold = props.swipeThreshold

  if (Math.abs(currentX.value) > threshold) {
    // Trigger action
    if (currentX.value > 0 && props.leftActions.length > 0) {
      handleAction(props.leftActions[0])
    } else if (currentX.value < 0 && props.rightActions.length > 0) {
      handleAction(props.rightActions[0])
    }
  }

  resetSwipe()
  isDragging.value = false
}

// Mouse event handlers (for desktop testing)
function handleMouseDown(e: MouseEvent) {
  if (!props.enableSwipe || window.innerWidth > 768) return

  isMouseDragging.value = true
  startX.value = e.clientX
  startY.value = e.clientY
  currentX.value = 0
}

function handleMouseMove(e: MouseEvent) {
  if (!isMouseDragging.value || !contentRef.value) return

  const deltaX = e.clientX - startX.value
  isDragging.value = true
  currentX.value = deltaX

  const maxSwipe = 120
  const limitedX = Math.max(-maxSwipe, Math.min(maxSwipe, deltaX))

  contentRef.value.style.transform = `translateX(${limitedX}px)`
  contentRef.value.style.transition = 'none'
}

function handleMouseUp() {
  if (!isMouseDragging.value) return

  handleTouchEnd()
  isMouseDragging.value = false
}

function handleMouseLeave() {
  if (isMouseDragging.value) {
    resetSwipe()
    isMouseDragging.value = false
    isDragging.value = false
  }
}

function resetSwipe() {
  if (!contentRef.value) return

  contentRef.value.style.transform = 'translateX(0)'
  contentRef.value.style.transition = 'transform 0.3s ease'

  setTimeout(() => {
    if (contentRef.value) {
      contentRef.value.style.transition = ''
    }
  }, 300)
}
</script>

<style lang="scss" scoped>
// Component uses global styles from _crm-split-view.scss
// Add component-specific refinements here

.crm-list-item {
  position: relative;
  overflow: hidden;

  &__content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: var(--bookando-spacing-sm);
    padding: var(--bookando-spacing-md);
    background: var(--bookando-surface);
    border-bottom: 1px solid var(--bookando-border);
    touch-action: pan-y;
    user-select: none;
    cursor: pointer;
    transition: background-color 0.15s ease;

    &:hover {
      background: var(--bookando-bg-soft);
    }
  }

  &__checkbox {
    flex-shrink: 0;
  }

  &__avatar {
    flex-shrink: 0;
  }

  &__info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: var(--bookando-spacing-xxs);
  }

  &__name {
    font-weight: 600;
    font-size: var(--bookando-font-size-base);
    color: var(--bookando-text);
    line-height: 1.4;
  }

  &__id {
    font-weight: 400;
    font-size: var(--bookando-font-size-sm);
    color: var(--bookando-text-muted);
  }

  &__contacts {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: var(--bookando-font-size-sm);
    color: var(--bookando-text-muted);
  }

  &__contact {
    display: flex;
    align-items: center;
    gap: var(--bookando-spacing-xxs);
  }

  &__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--bookando-spacing-sm);
    font-size: var(--bookando-font-size-xs);
    color: var(--bookando-text-muted);
  }

  &__status {
    flex-shrink: 0;
    margin-left: auto;
  }

  &__actions {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: var(--bookando-spacing-xs);
  }

  &.is-active &__content {
    background: var(--bookando-primary-light, rgba(79, 70, 229, 0.05));
  }

  &.is-selected &__content {
    background: var(--bookando-primary-light, rgba(79, 70, 229, 0.08));
  }

  &.is-dragging {
    .crm-list-item__content {
      cursor: grabbing;
    }
  }
}

.crm-list-item-swipe {
  position: absolute;
  top: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  z-index: 1;

  &--left {
    left: 0;
    padding-left: var(--bookando-spacing-md);
  }

  &--right {
    right: 0;
    padding-right: var(--bookando-spacing-md);
  }
}

.crm-list-item-action {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--bookando-spacing-xxs);
  padding: var(--bookando-spacing-sm) var(--bookando-spacing-md);
  border: none;
  background: none;
  color: var(--bookando-white);
  font-size: var(--bookando-font-size-xs);
  cursor: pointer;
  transition: all 0.2s ease;

  &__label {
    font-weight: 500;
    white-space: nowrap;
  }

  &--primary {
    background: var(--bookando-primary);
  }

  &--success {
    background: var(--bookando-success);
  }

  &--warning {
    background: var(--bookando-warning);
  }

  &--danger {
    background: var(--bookando-danger);
  }

  &:active {
    transform: scale(0.95);
  }
}

// Responsive
@media (max-width: 767px) {
  .crm-list-item {
    &__status {
      display: none; // Hide on mobile, shown in detail view
    }
  }
}
</style>
