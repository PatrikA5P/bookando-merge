<template>
  <div class="p-6 space-y-6">
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <div class="flex-1 max-w-md">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('mod.offers.categories.search_placeholder')"
          class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
      </div>
      <button
        @click="openCreateDialog"
        class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 text-white rounded-lg font-medium hover:bg-rose-700 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('mod.offers.categories.create') }}
      </button>
    </div>

    <!-- Categories Grid -->
    <div v-if="!loading && filteredCategories.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="category in filteredCategories"
        :key="category.id"
        class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg transition-all group"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1">
            <h3 class="text-lg font-bold text-slate-900 mb-1">{{ category.name }}</h3>
            <p v-if="category.description" class="text-sm text-slate-600 line-clamp-2">
              {{ category.description }}
            </p>
          </div>
          <div
            v-if="category.color"
            class="w-6 h-6 rounded-full border-2 border-white shadow-sm flex-shrink-0"
            :style="{ backgroundColor: category.color }"
          />
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
          <span class="text-sm text-slate-500">
            {{ category.offer_count || 0 }} {{ $t('mod.offers.categories.offers_count') }}
          </span>
          <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click="editCategory(category)"
              class="p-2 text-slate-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
              :title="$t('core.actions.edit')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="deleteCategory(category)"
              class="p-2 text-slate-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
              :title="$t('core.actions.delete')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.categories.no_categories') }}</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

// State
const loading = ref(false)
const searchQuery = ref('')
const categories = ref<any[]>([
  { id: 1, name: 'Fahrausbildung', description: 'Alle Fahrstunden und Prüfungsvorbereitung', color: '#ef4444', offer_count: 12 },
  { id: 2, name: 'Theoriekurse', description: 'VKU und Nothilfe', color: '#3b82f6', offer_count: 5 },
  { id: 3, name: 'Online-Kurse', description: 'Selbststudium und E-Learning', color: '#8b5cf6', offer_count: 8 }
])

// Filtered categories
const filteredCategories = computed(() => {
  if (!searchQuery.value) return categories.value
  const query = searchQuery.value.toLowerCase()
  return categories.value.filter(cat =>
    cat.name.toLowerCase().includes(query) ||
    cat.description?.toLowerCase().includes(query)
  )
})

// Actions
const openCreateDialog = () => {
  console.log('Create category')
  // TODO: Open modal
}

const editCategory = (category: any) => {
  console.log('Edit category:', category)
  // TODO: Open edit modal
}

const deleteCategory = (category: any) => {
  if (confirm($t('mod.offers.categories.confirm_delete'))) {
    console.log('Delete category:', category)
    // TODO: Delete via API
  }
}

onMounted(() => {
  // TODO: Load categories from API
})
</script>
