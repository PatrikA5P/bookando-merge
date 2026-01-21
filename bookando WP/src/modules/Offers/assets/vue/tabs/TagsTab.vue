<template>
  <div class="p-6 space-y-6">
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <div class="flex-1 max-w-md">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('mod.offers.tags.search_placeholder')"
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
        {{ $t('mod.offers.tags.create') }}
      </button>
    </div>

    <!-- Tags Cloud -->
    <div v-if="!loading && filteredTags.length > 0" class="bg-white rounded-xl border border-slate-200 p-6">
      <div class="flex flex-wrap gap-3">
        <div
          v-for="tag in filteredTags"
          :key="tag.id"
          class="group relative inline-flex items-center gap-2 px-4 py-2 rounded-full border-2 transition-all hover:shadow-md"
          :style="{
            borderColor: tag.color || '#cbd5e1',
            backgroundColor: tag.color ? `${tag.color}15` : '#f8fafc'
          }"
        >
          <span class="font-semibold text-slate-900">{{ tag.name }}</span>
          <span class="text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">
            {{ tag.offer_count || 0 }}
          </span>

          <!-- Hover Actions -->
          <div class="absolute -top-2 -right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click="editTag(tag)"
              class="p-1.5 bg-white border border-slate-300 text-slate-600 hover:text-rose-700 hover:border-rose-300 rounded-full shadow-sm transition-colors"
              :title="$t('core.actions.edit')"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="deleteTag(tag)"
              class="p-1.5 bg-white border border-slate-300 text-slate-600 hover:text-red-700 hover:border-red-300 rounded-full shadow-sm transition-colors"
              :title="$t('core.actions.delete')"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
      <p class="text-lg font-medium">{{ $t('mod.offers.tags.no_tags') }}</p>
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
const tags = ref<any[]>([
  { id: 1, name: 'Anfänger', color: '#22c55e', offer_count: 8 },
  { id: 2, name: 'Fortgeschritten', color: '#f59e0b', offer_count: 5 },
  { id: 3, name: 'VIP', color: '#8b5cf6', offer_count: 3 },
  { id: 4, name: 'Prüfungsvorbereitung', color: '#ef4444', offer_count: 6 },
  { id: 5, name: 'Intensivkurs', color: '#3b82f6', offer_count: 4 }
])

// Filtered tags
const filteredTags = computed(() => {
  if (!searchQuery.value) return tags.value
  const query = searchQuery.value.toLowerCase()
  return tags.value.filter(tag => tag.name.toLowerCase().includes(query))
})

// Actions
const openCreateDialog = () => {
  console.log('Create tag')
}

const editTag = (tag: any) => {
  console.log('Edit tag:', tag)
}

const deleteTag = (tag: any) => {
  if (confirm($t('mod.offers.tags.confirm_delete'))) {
    console.log('Delete tag:', tag)
  }
}

onMounted(() => {
  // TODO: Load tags from API
})
</script>
