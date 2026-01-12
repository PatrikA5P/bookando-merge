<!-- CRMListPanel.vue - List Panel with Search, Filters & Pagination -->
<template>
  <div class="crm-list-panel">
    <!-- Header with Search & Filters -->
    <div class="crm-list-panel__header">
      <!-- Search -->
      <div v-if="searchable" class="crm-list-panel__search">
        <AppIcon name="search" class="search-icon" />
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="searchPlaceholder"
          @input="handleSearch"
        />
        <AppIcon
          v-if="searchQuery"
          name="x"
          class="clear-icon is-visible"
          @click="clearSearch"
        />
      </div>

      <!-- Filters -->
      <div v-if="$slots.filters || filters.length" class="crm-list-panel__filters">
        <slot name="filters">
          <AppButton
            v-for="filter in filters"
            :key="filter.value"
            :variant="activeFilter === filter.value ? 'primary' : 'ghost'"
            size="sm"
            @click="handleFilterChange(filter.value)"
          >
            {{ filter.label }}
            <AppBadge
              v-if="filter.count !== undefined"
              variant="default"
              size="sm"
              :label="String(filter.count)"
            />
          </AppButton>
        </slot>
      </div>
    </div>

    <!-- List Content -->
    <div ref="listContentRef" class="crm-list-panel__content">
      <!-- Bulk Select Header -->
      <div v-if="multiSelect && items.length" class="crm-list-bulk-header">
        <label class="crm-checkbox-wrapper">
          <input
            type="checkbox"
            :checked="isAllSelected"
            :indeterminate.prop="isSomeSelected"
            @change="handleSelectAll"
          />
          <span class="crm-checkbox-label">
            {{ selectedCount }} {{ selectedCount === 1 ? 'selected' : 'selected' }}
          </span>
        </label>
      </div>

      <!-- Empty State -->
      <div v-if="!filteredItems.length" class="crm-empty-list">
        <div class="crm-empty-list__icon">
          <AppIcon :name="emptyIcon" />
        </div>
        <div class="crm-empty-list__title">{{ emptyTitle }}</div>
        <div class="crm-empty-list__description">{{ emptyDescription }}</div>
        <AppButton
          v-if="showAddButton"
          variant="primary"
          :icon="addButtonIcon"
          @click="$emit('add')"
        >
          {{ addButtonLabel }}
        </AppButton>
      </div>

      <!-- List Items -->
      <slot
        v-else
        name="default"
        :items="paginatedItems"
        :selectedId="selectedId"
        :selectedIds="selectedIds"
        :onSelect="handleSelect"
        :onToggleSelect="handleToggleSelect"
      />
    </div>

    <!-- Footer with Pagination -->
    <div v-if="paginated && filteredItems.length" class="crm-list-panel__footer">
      <AppPagination
        :current-page="currentPage"
        :total-pages="totalPages"
        :page-size="itemsPerPage"
        :total-items="filteredItems.length"
        entity-label-singular="item"
        entity-label-plural="items"
        layout="compact"
        @page-change="goToPage"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import AppButton from './AppButton.vue'
import AppIcon from './AppIcon.vue'
import AppBadge from './AppBadge.vue'
import AppPagination from './AppPagination.vue'

export interface Filter {
  label: string
  value: string
  count?: number
}

export interface CRMListPanelProps {
  items: any[]
  selectedId?: string | null
  selectedIds?: Set<string>
  searchable?: boolean
  searchPlaceholder?: string
  searchFields?: string[]
  filters?: Filter[]
  activeFilter?: string
  multiSelect?: boolean
  paginated?: boolean
  itemsPerPage?: number
  showAddButton?: boolean
  addButtonLabel?: string
  addButtonIcon?: string
  emptyIcon?: string
  emptyTitle?: string
  emptyDescription?: string
}

const props = withDefaults(defineProps<CRMListPanelProps>(), {
  selectedId: null,
  selectedIds: () => new Set(),
  searchable: true,
  searchPlaceholder: 'Search...',
  searchFields: () => ['name', 'email', 'phone'],
  filters: () => [],
  activeFilter: 'all',
  multiSelect: true,
  paginated: true,
  itemsPerPage: 20,
  showAddButton: true,
  addButtonLabel: 'Add New',
  addButtonIcon: 'plus',
  emptyIcon: 'inbox',
  emptyTitle: 'No items found',
  emptyDescription: 'Get started by adding your first item.'
})

const emit = defineEmits<{
  (e: 'select', id: string): void
  (e: 'toggleSelect', id: string): void
  (e: 'selectAll', checked: boolean): void
  (e: 'search', query: string): void
  (e: 'filterChange', value: string): void
  (e: 'add'): void
}>()

// State
const searchQuery = ref('')
const currentPage = ref(1)
const listContentRef = ref<HTMLElement>()

// Computed
const filteredItems = computed(() => {
  let items = props.items

  // Apply search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item => {
      return props.searchFields.some(field => {
        const value = item[field]
        return value && String(value).toLowerCase().includes(query)
      })
    })
  }

  return items
})

const totalPages = computed(() => {
  if (!props.paginated) return 1
  return Math.ceil(filteredItems.value.length / props.itemsPerPage)
})

const paginatedItems = computed(() => {
  if (!props.paginated) return filteredItems.value

  const start = (currentPage.value - 1) * props.itemsPerPage
  const end = start + props.itemsPerPage
  return filteredItems.value.slice(start, end)
})

const selectedCount = computed(() => props.selectedIds.size)

const isAllSelected = computed(() => {
  return props.items.length > 0 && selectedCount.value === props.items.length
})

const isSomeSelected = computed(() => {
  return selectedCount.value > 0 && selectedCount.value < props.items.length
})

// Methods
function handleSearch() {
  currentPage.value = 1
  emit('search', searchQuery.value)
}

function clearSearch() {
  searchQuery.value = ''
  handleSearch()
}

function handleFilterChange(value: string) {
  currentPage.value = 1
  emit('filterChange', value)
}

function handleSelect(id: string) {
  emit('select', id)
}

function handleToggleSelect(id: string) {
  emit('toggleSelect', id)
}

function handleSelectAll(event: Event) {
  const checked = (event.target as HTMLInputElement).checked
  emit('selectAll', checked)
}

function goToPage(page: number) {
  currentPage.value = page
  scrollToTop()
}

function scrollToTop() {
  if (listContentRef.value) {
    listContentRef.value.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

// Watch for items change to reset page if needed
watch(
  () => props.items.length,
  () => {
    if (currentPage.value > totalPages.value) {
      currentPage.value = Math.max(1, totalPages.value)
    }
  }
)
</script>

<style lang="scss" scoped>
// Custom styles for list panel enhancements
.crm-list-bulk-header {
  padding: var(--bookando-spacing-sm) var(--bookando-spacing-lg);
  border-bottom: 1px solid var(--bookando-border-light);
  background: var(--bookando-bg-soft);
  position: sticky;
  top: 0;
  z-index: 5;
}

.crm-checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: var(--bookando-spacing-sm);
  cursor: pointer;
  user-select: none;

  input[type="checkbox"] {
    cursor: pointer;
  }

  .crm-checkbox-label {
    font-size: var(--bookando-font-size-sm);
    color: var(--bookando-text-muted);
    font-weight: 500;
  }
}

.pagination-controls {
  display: flex;
  gap: var(--bookando-spacing-xs);
}
</style>
