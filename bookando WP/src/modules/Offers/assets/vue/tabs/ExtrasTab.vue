<template>
  <div class="p-6 space-y-6">
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <div class="flex-1 max-w-md">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('mod.offers.extras.search_placeholder')"
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
        {{ $t('mod.offers.extras.create') }}
      </button>
    </div>

    <!-- Extras List -->
    <div v-if="!loading && filteredExtras.length > 0" class="space-y-3">
      <div
        v-for="extra in filteredExtras"
        :key="extra.id"
        class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-all"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h3 class="text-lg font-bold text-slate-900">{{ extra.name }}</h3>
              <span
                :class="[
                  'px-2.5 py-0.5 text-xs font-bold rounded-full',
                  extra.type === 'optional' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'
                ]"
              >
                {{ extra.type === 'optional' ? $t('mod.offers.extras.optional') : $t('mod.offers.extras.mandatory') }}
              </span>
            </div>

            <p v-if="extra.description" class="text-sm text-slate-600 mb-3">{{ extra.description }}</p>

            <div class="flex items-center gap-4 text-sm">
              <span class="font-semibold text-slate-700">
                {{ extra.price }} {{ extra.currency }}
              </span>
              <span v-if="extra.duration" class="text-slate-500">
                ⏱ {{ extra.duration }} Min
              </span>
              <span v-if="extra.applicable_to" class="text-slate-500">
                {{ $t('mod.offers.extras.applicable_to') }}: {{ extra.applicable_to.join(', ') }}
              </span>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              @click="editExtra(extra)"
              class="p-2 text-slate-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <button
              @click="deleteExtra(extra)"
              class="p-2 text-slate-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.extras.no_extras') }}</p>
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
const extras = ref<any[]>([
  { id: 1, name: 'Zusätzliche Theorie-Lektion', description: 'Extra Vorbereitung für die Theorieprüfung', type: 'optional', price: '50', currency: 'CHF', duration: 60, applicable_to: ['Theoriekurse'] },
  { id: 2, name: 'Prüfungsgebühr', description: 'Offizielle Gebühr für die praktische Prüfung', type: 'mandatory', price: '120', currency: 'CHF', applicable_to: ['Alle Pakete'] },
  { id: 3, name: 'Fahrzeugmiete für Prüfung', description: 'Auto-Miete am Prüfungstag', type: 'optional', price: '80', currency: 'CHF', applicable_to: ['Fahrausbildung'] }
])

// Filtered extras
const filteredExtras = computed(() => {
  if (!searchQuery.value) return extras.value
  const query = searchQuery.value.toLowerCase()
  return extras.value.filter(extra =>
    extra.name.toLowerCase().includes(query) ||
    extra.description?.toLowerCase().includes(query)
  )
})

// Actions
const openCreateDialog = () => {
  console.log('Create extra')
}

const editExtra = (extra: any) => {
  console.log('Edit extra:', extra)
}

const deleteExtra = (extra: any) => {
  if (confirm($t('mod.offers.extras.confirm_delete'))) {
    console.log('Delete extra:', extra)
  }
}

onMounted(() => {
  // TODO: Load extras from API
})
</script>
