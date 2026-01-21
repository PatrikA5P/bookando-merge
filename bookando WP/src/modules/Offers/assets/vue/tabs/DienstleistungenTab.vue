<template>
  <div class="space-y-6">
    <!-- Filter Bar -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4 flex-wrap">
      <div class="flex-1 flex items-center gap-2">
        <label class="text-sm font-semibold text-slate-700">{{ $t('mod.offers.overview.dienstleistungen.category') }}:</label>
        <select
          v-model="selectedCategory"
          @change="applyFilter"
          class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
          <option value="">{{ $t('core.common.all') }}</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>
      </div>

      <div class="flex items-center gap-2">
        <label class="text-sm font-semibold text-slate-700">{{ $t('fields.status') }}:</label>
        <select
          v-model="selectedStatus"
          @change="applyFilter"
          class="px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
          <option value="">{{ $t('core.common.all') }}</option>
          <option value="active">{{ $t('core.common.active') }}</option>
          <option value="inactive">{{ $t('core.common.inactive') }}</option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600"></div>
    </div>

    <!-- Services Grid -->
    <div v-else-if="services.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="service in services"
        :key="service.id"
        class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg transition-all"
      >
        <div class="flex items-start justify-between mb-3">
          <h3 class="text-lg font-bold text-slate-900">{{ service.title }}</h3>
          <button
            @click="toggleStatus(service)"
            :class="[
              'px-2.5 py-1 text-xs font-bold rounded-full transition-colors',
              service.status === 'active'
                ? 'bg-green-100 text-green-700 hover:bg-green-200'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            ]"
          >
            {{ service.status === 'active' ? '✓ Aktiv' : '○ Inaktiv' }}
          </button>
        </div>

        <p v-if="service.description" class="text-sm text-slate-600 mb-4 line-clamp-2">
          {{ service.description }}
        </p>

        <div class="space-y-2 mb-4">
          <div v-if="service.duration_minutes" class="flex items-center gap-2 text-sm text-slate-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ service.duration_minutes }} Min
          </div>

          <div v-if="service.price" class="flex items-center gap-2">
            <span class="text-2xl font-bold text-rose-700">{{ service.price }}</span>
            <span class="text-sm text-slate-500">{{ service.currency || 'CHF' }}</span>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            @click="editService(service)"
            class="flex-1 px-3 py-2 text-sm font-medium text-rose-700 border border-rose-300 rounded-lg hover:bg-rose-50 transition-colors"
          >
            {{ $t('core.actions.edit') }}
          </button>
          <button
            @click="viewBookings(service)"
            class="px-3 py-2 text-sm font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            📅
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.overview.dienstleistungen.no_services') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import httpBase from '@assets/http'

const { t: $t } = useI18n()
const http = httpBase.module('offers')

// State
const loading = ref(false)
const services = ref<any[]>([])
const selectedCategory = ref('')
const selectedStatus = ref('')

// Categories from services
const categories = computed(() => {
  const cats = new Set<string>()
  services.value.forEach(service => {
    if (service.category) cats.add(service.category)
  })
  return Array.from(cats).sort()
})

// Load services
const loadServices = async () => {
  loading.value = true
  try {
    const response = await http.get('by-type/dienstleistungen')
    services.value = response.data || []
  } catch (error) {
    console.error('Failed to load services:', error)
    services.value = []
  } finally {
    loading.value = false
  }
}

// Apply filter
const applyFilter = () => {
  // Re-load with filters (API should support these params)
  loadServices()
}

// Toggle status
const toggleStatus = async (service: any) => {
  const newStatus = service.status === 'active' ? 'inactive' : 'active'
  try {
    await http.put(`${service.id}`, { status: newStatus })
    service.status = newStatus
  } catch (error) {
    console.error('Failed to toggle status:', error)
  }
}

// Actions
const editService = (service: any) => {
  console.log('Edit service:', service)
  // TODO: Open edit modal
}

const viewBookings = (service: any) => {
  console.log('View bookings for:', service)
  // TODO: Navigate to appointments filtered by this service
}

// Load on mount
onMounted(() => {
  loadServices()
})
</script>
