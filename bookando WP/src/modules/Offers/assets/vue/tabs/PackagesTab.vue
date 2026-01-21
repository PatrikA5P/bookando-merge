<template>
  <div class="p-6 space-y-6">
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <div class="flex-1 max-w-md">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('mod.offers.packages.search_placeholder')"
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
        {{ $t('mod.offers.packages.create') }}
      </button>
    </div>

    <!-- Packages Grid -->
    <div v-if="!loading && filteredPackages.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div
        v-for="pkg in filteredPackages"
        :key="pkg.id"
        class="bg-white rounded-xl border-2 border-slate-200 overflow-hidden hover:border-rose-300 hover:shadow-lg transition-all"
      >
        <!-- Package Header -->
        <div class="bg-gradient-to-r from-rose-600 to-pink-700 text-white p-5">
          <div class="flex items-start justify-between mb-2">
            <h3 class="text-xl font-bold">{{ pkg.name }}</h3>
            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
              {{ pkg.discount }}% {{ $t('mod.offers.packages.discount') }}
            </span>
          </div>
          <p class="text-rose-100 text-sm">{{ pkg.description }}</p>
        </div>

        <!-- Package Content -->
        <div class="p-5 space-y-4">
          <!-- Included Items -->
          <div>
            <h4 class="text-sm font-bold text-slate-700 mb-2">{{ $t('mod.offers.packages.included') }}:</h4>
            <div class="space-y-2">
              <div
                v-for="item in pkg.items"
                :key="item.id"
                class="flex items-center gap-2 text-sm text-slate-600"
              >
                <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ item.quantity }}x {{ item.name }}</span>
              </div>
            </div>
          </div>

          <!-- Pricing -->
          <div class="pt-4 border-t border-slate-200">
            <div class="flex items-baseline gap-2 mb-1">
              <span class="text-3xl font-bold text-slate-900">{{ pkg.price }}</span>
              <span class="text-slate-500">{{ pkg.currency }}</span>
              <span v-if="pkg.original_price" class="ml-auto text-slate-400 line-through text-sm">
                {{ pkg.original_price }} {{ pkg.currency }}
              </span>
            </div>
            <p class="text-xs text-green-600 font-medium">
              {{ $t('mod.offers.packages.savings') }}: {{ pkg.savings }} {{ pkg.currency }}
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="bg-slate-50 p-4 flex gap-2 border-t border-slate-200">
          <button
            @click="editPackage(pkg)"
            class="flex-1 px-4 py-2 text-sm font-medium text-rose-700 border border-rose-300 rounded-lg hover:bg-rose-50 transition-colors"
          >
            {{ $t('core.actions.edit') }}
          </button>
          <button
            @click="duplicatePackage(pkg)"
            class="px-4 py-2 text-sm font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            {{ $t('core.actions.duplicate') }}
          </button>
          <button
            @click="deletePackage(pkg)"
            class="px-4 py-2 text-sm font-medium text-red-700 border border-red-300 rounded-lg hover:bg-red-50 transition-colors"
          >
            {{ $t('core.actions.delete') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.packages.no_packages') }}</p>
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
const packages = ref<any[]>([
  {
    id: 1,
    name: 'Führerschein Komplett',
    description: 'Alles was du für den Führerschein brauchst',
    discount: 15,
    price: '2550',
    original_price: '3000',
    savings: '450',
    currency: 'CHF',
    items: [
      { id: 1, name: 'Theorieprüfung Vorbereitung', quantity: 1 },
      { id: 2, name: 'VKU Kurs', quantity: 1 },
      { id: 3, name: 'Fahrstunden à 45 Min', quantity: 25 },
      { id: 4, name: 'Prüfungsvorbereitung', quantity: 2 }
    ]
  },
  {
    id: 2,
    name: 'Schnellkurs Paket',
    description: 'Intensiv-Training für schnelle Prüfungsvorbereitung',
    discount: 10,
    price: '1890',
    original_price: '2100',
    savings: '210',
    currency: 'CHF',
    items: [
      { id: 1, name: 'Fahrstunden à 45 Min', quantity: 20 },
      { id: 2, name: 'Prüfungsvorbereitung Intensiv', quantity: 3 }
    ]
  }
])

// Filtered packages
const filteredPackages = computed(() => {
  if (!searchQuery.value) return packages.value
  const query = searchQuery.value.toLowerCase()
  return packages.value.filter(pkg =>
    pkg.name.toLowerCase().includes(query) ||
    pkg.description.toLowerCase().includes(query)
  )
})

// Actions
const openCreateDialog = () => {
  console.log('Create package')
}

const editPackage = (pkg: any) => {
  console.log('Edit package:', pkg)
}

const duplicatePackage = (pkg: any) => {
  console.log('Duplicate package:', pkg)
}

const deletePackage = (pkg: any) => {
  if (confirm($t('mod.offers.packages.confirm_delete'))) {
    console.log('Delete package:', pkg)
  }
}

onMounted(() => {
  // TODO: Load packages from API
})
</script>
