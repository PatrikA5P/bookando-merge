<!-- CRMSplitView.vue - Master Layout for CRM/HRM Modules -->
<template>
  <div class="crm-split-view" :class="splitViewClasses">
    <!-- List Panel Container (Left) -->
    <div
      class="crm-split-view__list"
      :style="listPanelStyle"
    >
      <slot
        name="list"
        :selectedId="selectedId"
        :selectedIds="selectedIds"
        :isMultiSelect="isMultiSelect"
        :onSelect="handleSelect"
        :onToggleSelect="handleToggleSelect"
        :onSelectAll="handleSelectAll"
      />
    </div>

    <!-- Resize Handle (Desktop only) -->
    <div
      v-if="props.resizable && isDesktop"
      class="crm-split-view__resize-handle"
      @mousedown="handleResizeStart"
      @touchstart="handleResizeStart"
    >
      <div class="crm-split-view__resize-handle-bar" />
    </div>

    <!-- Detail Panel Container (Right) -->
    <div
      v-if="selectedId || $slots.emptyDetail"
      class="crm-split-view__detail"
      :class="detailPanelClasses"
    >
      <slot
        v-if="selectedId"
        name="detail"
        :item="selectedItem"
        :onClose="handleCloseDetail"
      />
      <slot v-else name="emptyDetail" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

export interface CRMSplitViewProps {
  items: any[]
  autoSelectFirst?: boolean
  multiSelect?: boolean
  listWidth?: number
  minListWidth?: number
  maxListWidth?: number
  resizable?: boolean
  detailAlwaysVisible?: boolean
}

const props = withDefaults(defineProps<CRMSplitViewProps>(), {
  autoSelectFirst: true,
  multiSelect: true,
  listWidth: 35,
  minListWidth: 25,
  maxListWidth: 50,
  resizable: true,
  detailAlwaysVisible: false
})

const emit = defineEmits<{
  (e: 'update:selectedId', id: string | null): void
  (e: 'update:selectedIds', ids: string[]): void
  (e: 'select', item: any): void
  (e: 'multiSelect', items: any[]): void
  (e: 'resize', width: number): void
}>()

// State
const selectedId = ref<string | null>(null)
const selectedIds = ref<Set<string>>(new Set())
const isMultiSelect = ref(false)
const isDetailOpen = ref(false)
const currentListWidth = ref(props.listWidth)
const isResizing = ref(false)
const resizeStartX = ref(0)
const resizeStartWidth = ref(0)

// Computed
const selectedItem = computed(() => {
  if (!selectedId.value) return null
  return props.items.find(item => item.id === selectedId.value)
})

const isDesktop = computed(() => {
  if (typeof window === 'undefined') return false
  return window.innerWidth >= 1024
})

const splitViewClasses = computed(() => ({
  'is-detail-open': isDetailOpen.value,
  'is-multi-select': isMultiSelect.value,
  'is-resizing': isResizing.value
}))

const detailPanelClasses = computed(() => ({
  'is-open': isDetailOpen.value || props.detailAlwaysVisible
}))

const listPanelStyle = computed(() => {
  if (typeof window === 'undefined' || window.innerWidth < 1024) {
    return {}
  }
  return {
    width: `${currentListWidth.value}%`
  }
})

// Methods
function handleSelect(id: string) {
  selectedId.value = id
  isDetailOpen.value = true
  const item = props.items.find(i => i.id === id)
  if (item) {
    emit('select', item)
    emit('update:selectedId', id)
  }
}

function handleToggleSelect(id: string) {
  if (selectedIds.value.has(id)) {
    selectedIds.value.delete(id)
  } else {
    selectedIds.value.add(id)
  }
  selectedIds.value = new Set(selectedIds.value)
  const items = Array.from(selectedIds.value).map(id =>
    props.items.find(i => i.id === id)
  ).filter(Boolean)
  emit('update:selectedIds', Array.from(selectedIds.value))
  emit('multiSelect', items)
}

function handleSelectAll(checked: boolean) {
  if (checked) {
    selectedIds.value = new Set(props.items.map(i => i.id))
  } else {
    selectedIds.value.clear()
  }
  selectedIds.value = new Set(selectedIds.value)
  const items = checked ? props.items : []
  emit('update:selectedIds', Array.from(selectedIds.value))
  emit('multiSelect', items)
}

function handleCloseDetail() {
  isDetailOpen.value = false
  selectedId.value = null
  emit('update:selectedId', null)
}

// Resize Functionality
function handleResizeStart(event: MouseEvent | TouchEvent) {
  if (!isDesktop.value) return

  event.preventDefault()
  isResizing.value = true

  const clientX = event instanceof MouseEvent ? event.clientX : event.touches[0].clientX
  resizeStartX.value = clientX
  resizeStartWidth.value = currentListWidth.value

  document.addEventListener('mousemove', handleResizeMove)
  document.addEventListener('mouseup', handleResizeEnd)
  document.addEventListener('touchmove', handleResizeMove)
  document.addEventListener('touchend', handleResizeEnd)
  document.body.style.cursor = 'col-resize'
  document.body.style.userSelect = 'none'
}

function handleResizeMove(event: MouseEvent | TouchEvent) {
  if (!isResizing.value) return

  const clientX = event instanceof MouseEvent ? event.clientX : event.touches[0].clientX
  const containerWidth = (event.target as HTMLElement)?.closest('.crm-split-view')?.clientWidth || window.innerWidth
  const deltaX = clientX - resizeStartX.value
  const deltaPercent = (deltaX / containerWidth) * 100

  let newWidth = resizeStartWidth.value + deltaPercent
  newWidth = Math.max(props.minListWidth, Math.min(props.maxListWidth, newWidth))

  currentListWidth.value = newWidth
}

function handleResizeEnd() {
  if (!isResizing.value) return

  isResizing.value = false
  document.removeEventListener('mousemove', handleResizeMove)
  document.removeEventListener('mouseup', handleResizeEnd)
  document.removeEventListener('touchmove', handleResizeMove)
  document.removeEventListener('touchend', handleResizeEnd)
  document.body.style.cursor = ''
  document.body.style.userSelect = ''

  // Emit resize event with new width
  emit('resize', currentListWidth.value)
}

// Keyboard Navigation
function handleKeyDown(event: KeyboardEvent) {
  if (!props.items.length) return

  const currentIndex = props.items.findIndex(i => i.id === selectedId.value)

  switch (event.key) {
    case 'ArrowUp':
      event.preventDefault()
      if (currentIndex > 0) {
        handleSelect(props.items[currentIndex - 1].id)
      }
      break

    case 'ArrowDown':
      event.preventDefault()
      if (currentIndex < props.items.length - 1) {
        handleSelect(props.items[currentIndex + 1].id)
      }
      break

    case 'Escape':
      if (window.innerWidth < 1024) {
        handleCloseDetail()
      }
      break

    case 'Enter':
      if (selectedId.value && window.innerWidth >= 1024) {
        // Trigger edit mode
        emit('select', selectedItem.value)
      }
      break
  }
}

// Auto-select first item
watch(
  () => props.items,
  (newItems) => {
    if (props.autoSelectFirst && newItems.length > 0 && !selectedId.value) {
      handleSelect(newItems[0].id)
    }
  },
  { immediate: true }
)

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyDown)
})

// Expose methods for parent components
defineExpose({
  selectItem: handleSelect,
  clearSelection: handleCloseDetail,
  toggleMultiSelect: (value: boolean) => {
    isMultiSelect.value = value
  }
})
</script>

<style lang="scss" scoped>
// All styles are in _crm-split-view.scss
// This component just uses the global classes
</style>
