<template>
  <!-- Customers Module - Pure Tailwind, ModuleLayout Pattern -->
  <div class="flex flex-col min-h-full bg-slate-50/50">

    <!-- ==== MOBILE & TABLET LAYOUT (Sticky Scroll Away - Up to LG breakpoint) ==== -->
    <div class="lg:hidden flex flex-col min-h-screen">
      <!-- Sticky Header Container -->
      <div
        :class="[
          'sticky top-0 left-0 right-0 z-20 transition-transform duration-300 ease-in-out shadow-lg',
          'bg-gradient-to-r from-emerald-700 to-teal-900 text-white',
          isHeaderVisible ? 'translate-y-0' : '-translate-y-full'
        ]"
      >
        <!-- Part 1: Title -->
        <div class="px-4 pt-4 pb-2">
          <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h2 class="font-bold text-lg">{{ $t('mod.customers.title') }}</h2>
          </div>
        </div>

        <!-- Part 2: Integrated Search & Filter -->
        <div class="px-4 pb-3 flex gap-2">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="$t('mod.customers.search_placeholder')"
              class="w-full pl-9 pr-4 py-2 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
            >
          </div>
          <button
            @click="isFilterOpen = !isFilterOpen"
            :class="[
              'p-2 rounded-lg border border-white/20 transition-colors',
              isFilterOpen ? 'bg-white text-brand-700' : 'bg-white/10 text-white hover:bg-white/20'
            ]"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Content Wrapper -->
      <div class="flex-1">
        <!-- Filter Content Panel (if open) -->
        <div v-if="isFilterOpen" class="bg-white border-b border-slate-200 p-4 shadow-inner max-h-[50vh] overflow-y-auto text-slate-800 animate-slideDown">
          <div class="space-y-4">
            <!-- Status Filter -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('mod.customers.filter.status') }}</label>
              <div class="space-y-2">
                <label v-for="status in statusOptions" :key="status" class="flex items-center gap-2 cursor-pointer group">
                  <div :class="[
                    'w-4 h-4 rounded border flex items-center justify-center transition-colors',
                    activeFilters.status.includes(status) ? 'bg-brand-600 border-brand-600' : 'border-slate-300 bg-white'
                  ]">
                    <svg v-if="activeFilters.status.includes(status)" class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4 a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <input
                    type="checkbox"
                    class="hidden"
                    :checked="activeFilters.status.includes(status)"
                    @change="toggleStatusFilter(status)"
                  >
                  <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ status }}</span>
                </label>
              </div>
            </div>

            <!-- Sort Order -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('mod.customers.filter.sort') }}</label>
              <select
                v-model="activeFilters.sortBy"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm appearance-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand-500 outline-none"
              >
                <option value="name_asc">{{ $t('mod.customers.sort.name_asc') }}</option>
                <option value="name_desc">{{ $t('mod.customers.sort.name_desc') }}</option>
                <option value="newest">{{ $t('mod.customers.sort.newest') }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="p-4">
          <CustomersList :customers="paginatedCustomers" @edit="handleEdit" @delete="handleDelete" />
        </div>
      </div>

      <!-- Mobile Floating Action Button -->
      <button
        @click="openCreateDialog"
        class="fixed bottom-6 right-6 w-14 h-14 bg-brand-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 hover:bg-brand-700 active:scale-95 transition-all"
      >
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <!-- ==== DESKTOP LAYOUT (Large Screens) ==== -->
    <div class="hidden lg:flex min-h-full p-6 gap-6 items-start">
      <!-- Sticky Header Wrapper - No tabs, full width layout -->
      <div class="flex-1 flex flex-col min-w-0">
        <div class="sticky top-0 z-30 bg-slate-50 pt-6 pb-6 -mt-6">
          <div class="flex gap-6 items-stretch">
            <!-- Left: Module Identity (Hero Section) -->
            <div class="w-72 shrink-0 z-20 relative">
              <div class="relative overflow-hidden bg-gradient-to-br from-emerald-700 to-teal-900 text-white p-6 rounded-xl shadow-lg h-full flex flex-col justify-center">
                <div class="relative z-10">
                  <div class="flex items-center gap-3 mb-2">
                    <svg class="w-6 h-6 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <h2 class="font-bold text-xl text-white">{{ $t('mod.customers.title') }}</h2>
                  </div>
                  <p class="text-xs max-w-2xl text-white/70">{{ $t('mod.customers.description') }}</p>
                </div>
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
                  <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Right: Actions Toolbar -->
            <div class="flex-1 bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-center relative z-20">
              <div class="flex items-center gap-3 w-full justify-between">
                <div class="flex-1 flex gap-3">
                  <!-- Search -->
                  <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                      v-model="searchQuery"
                      type="text"
                      :placeholder="$t('mod.customers.search_placeholder')"
                      class="w-full pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all border border-slate-200 rounded-xl bg-slate-50 focus:bg-white"
                    >
                  </div>

                  <!-- Filter Toggle -->
                  <button
                    @click="isFilterOpen = !isFilterOpen"
                    :class="[
                      'transition-colors flex items-center justify-center shrink-0 border rounded-xl p-2.5',
                      isFilterOpen ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                    ]"
                  >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                  </button>

                  <!-- Export CSV -->
                  <button
                    @click="handleExportCSV"
                    class="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="hidden md:inline">{{ $t('mod.customers.actions.export') }}</span>
                  </button>
                </div>

                <div class="pl-3 border-l border-slate-100">
                  <button
                    @click="openCreateDialog"
                    class="flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0 bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
                  >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ $t('mod.customers.actions.add') }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Content Body -->
        <main class="bg-white border border-slate-200 shadow-sm flex-1 flex flex-col z-0 relative rounded-xl overflow-hidden">
          <!-- Filter Expansion inside the content container -->
          <div v-if="isFilterOpen" class="p-4 border-b border-slate-200 bg-slate-50 animate-slideDown">
            <div class="grid grid-cols-3 gap-4">
              <!-- Status Filter -->
              <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('mod.customers.filter.status') }}</label>
                <div class="space-y-2">
                  <label v-for="status in statusOptions" :key="status" class="flex items-center gap-2 cursor-pointer group">
                    <div :class="[
                      'w-4 h-4 rounded border flex items-center justify-center transition-colors',
                      activeFilters.status.includes(status) ? 'bg-brand-600 border-brand-600' : 'border-slate-300 bg-white'
                    ]">
                      <svg v-if="activeFilters.status.includes(status)" class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <input
                      type="checkbox"
                      class="hidden"
                      :checked="activeFilters.status.includes(status)"
                      @change="toggleStatusFilter(status)"
                    >
                    <span class="text-sm text-slate-700 group-hover:text-slate-900">{{ status }}</span>
                  </label>
                </div>
              </div>

              <!-- Sort Order -->
              <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('mod.customers.filter.sort') }}</label>
                <select
                  v-model="activeFilters.sortBy"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm appearance-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand-500 outline-none"
                >
                  <option value="name_asc">{{ $t('mod.customers.sort.name_asc') }}</option>
                  <option value="name_desc">{{ $t('mod.customers.sort.name_desc') }}</option>
                  <option value="newest">{{ $t('mod.customers.sort.newest') }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Table -->
          <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
                <tr>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.customers.table.customer') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.customers.table.contact') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.customers.table.address') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.customers.table.status') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">{{ $t('mod.customers.table.actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr
                  v-for="customer in paginatedCustomers"
                  :key="customer.id"
                  :class="[
                    'hover:bg-slate-50 transition-colors group',
                    customer.status === 'deleted' ? 'opacity-60 bg-slate-50' : ''
                  ]"
                >
                  <td class="p-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-semibold text-sm uppercase shrink-0">
                        {{ getInitials(customer) }}
                      </div>
                      <div>
                        <div class="font-medium text-slate-900">{{ customer.first_name }} {{ customer.last_name }}</div>
                        <div class="text-xs text-slate-500">#{{ customer.id }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="p-4">
                    <div class="flex flex-col gap-1 text-sm text-slate-600">
                      <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        {{ customer.email }}
                      </div>
                      <div v-if="customer.phone" class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ customer.phone }}
                      </div>
                    </div>
                  </td>
                  <td class="p-4">
                    <div class="flex flex-col gap-0.5 text-sm text-slate-600">
                      <template v-if="customer.city || customer.country">
                        <div v-if="customer.city">{{ customer.city }}</div>
                        <div v-if="customer.country" class="text-xs text-slate-400">{{ customer.country }}</div>
                      </template>
                      <span v-else class="text-slate-400 italic">{{ $t('mod.customers.no_address') }}</span>
                    </div>
                  </td>
                  <td class="p-4">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border',
                      customer.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '',
                      customer.status === 'blocked' ? 'bg-rose-50 text-rose-700 border-rose-100' : '',
                      customer.status === 'deleted' ? 'bg-slate-100 text-slate-600 border-slate-200' : ''
                    ]">
                      {{ customer.status }}
                    </span>
                  </td>
                  <td class="p-4 text-right">
                    <div class="flex justify-end gap-2">
                      <button
                        @click="handleEdit(customer)"
                        class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-full transition-colors"
                      >
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        v-if="customer.status !== 'deleted'"
                        @click="handleDelete(customer)"
                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-full transition-colors"
                      >
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="paginatedCustomers.length === 0">
                  <td colspan="5" class="p-12 text-center">
                    <div class="flex flex-col items-center text-slate-400">
                      <svg class="w-12 h-12 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      <p class="text-lg font-medium text-slate-600">{{ $t('mod.customers.no_results') }}</p>
                      <p class="text-sm">{{ $t('mod.customers.adjust_filters') }}</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer -->
          <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4 text-sm text-slate-600">
              <span>
                {{ $t('mod.customers.pagination.showing') }}
                <span class="font-bold text-slate-900">{{ processedCustomers.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }}</span>
                {{ $t('mod.customers.pagination.to') }}
                <span class="font-bold text-slate-900">{{ Math.min(currentPage * itemsPerPage, totalItems) }}</span>
                {{ $t('mod.customers.pagination.of') }}
                <span class="font-bold text-slate-900">{{ totalItems }}</span>
              </span>
              <select
                v-model="itemsPerPage"
                class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer"
              >
                <option :value="10">10 {{ $t('mod.customers.pagination.per_page') }}</option>
                <option :value="25">25 {{ $t('mod.customers.pagination.per_page') }}</option>
                <option :value="50">50 {{ $t('mod.customers.pagination.per_page') }}</option>
                <option :value="100">100 {{ $t('mod.customers.pagination.per_page') }}</option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                @click="currentPage = Math.max(currentPage - 1, 1)"
                :disabled="currentPage === 1"
                class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <button
                v-for="(page, idx) in getPageNumbers()"
                :key="idx"
                @click="typeof page === 'number' && (currentPage = page)"
                :disabled="typeof page !== 'number'"
                :class="[
                  'min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors',
                  page === currentPage
                    ? 'bg-brand-600 text-white shadow-sm'
                    : typeof page === 'number' ? 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' : 'text-slate-400 cursor-default'
                ]"
              >
                {{ page }}
              </button>

              <button
                @click="currentPage = Math.min(currentPage + 1, totalPages)"
                :disabled="currentPage === totalPages || totalPages === 0"
                class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>
        </main>
      </div>
    </div>

    <!-- Customer Form Modal (reusing existing component for now) -->
    <component
      v-if="showDialog"
      :is="CustomersForm"
      :customer="editingCustomer"
      @close="showDialog = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCustomersStore } from '../store/store'

// Lazy load form component
const CustomersForm = defineAsyncComponent(() => import('../components/CustomersForm.vue'))

// i18n
const { t: $t } = useI18n()

// Store
const store = useCustomersStore()

// Filter & Pagination State
const searchQuery = ref('')
const isFilterOpen = ref(false)
const activeFilters = ref({
  status: [] as string[],
  sortBy: 'name_asc' as 'name_asc' | 'name_desc' | 'newest'
})

const currentPage = ref(1)
const itemsPerPage = ref(25)

// Modal State
const showDialog = ref(false)
const editingCustomer = ref<any>(null)

// Status Options
const statusOptions = ['active', 'blocked', 'deleted']

// Scroll Direction Hook (for mobile sticky header)
const scrollDirection = ref<'up' | 'down' | null>(null)
const scrolledToTop = ref(true)
const isHeaderVisible = computed(() => scrollDirection.value === 'up' || scrolledToTop.value)

// Toggle Status Filter
const toggleStatusFilter = (status: string) => {
  const exists = activeFilters.value.status.includes(status)
  if (exists) {
    activeFilters.value.status = activeFilters.value.status.filter(s => s !== status)
  } else {
    activeFilters.value.status.push(status)
  }
}

// Data Processing
const processedCustomers = computed(() => {
  let result = [...(store.items || [])]

  // 1. Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(c =>
      c.first_name?.toLowerCase().includes(q) ||
      c.last_name?.toLowerCase().includes(q) ||
      c.email?.toLowerCase().includes(q) ||
      c.phone?.includes(q)
    )
  }

  // 2. Status Filter
  if (activeFilters.value.status.length > 0) {
    result = result.filter(c => activeFilters.value.status.includes(c.status))
  }

  // 3. Sorting
  result.sort((a, b) => {
    switch (activeFilters.value.sortBy) {
      case 'name_asc':
        return (a.last_name || '').localeCompare(b.last_name || '')
      case 'name_desc':
        return (b.last_name || '').localeCompare(a.last_name || '')
      case 'newest':
        return (b.id || 0) - (a.id || 0)
      default: return 0
    }
  })

  return result
})

// Pagination Logic
const totalItems = computed(() => processedCustomers.value.length)
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))
const paginatedCustomers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = currentPage.value * itemsPerPage.value
  return processedCustomers.value.slice(start, end)
})

// Smart Pagination
const getPageNumbers = () => {
  const delta = 2
  const range: number[] = []
  const rangeWithDots: (number | string)[] = []
  let l: number | undefined

  range.push(1)
  for (let i = currentPage.value - delta; i <= currentPage.value + delta; i++) {
    if (i < totalPages.value && i > 1) {
      range.push(i)
    }
  }
  if (totalPages.value > 1) range.push(totalPages.value)

  for (const i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  }
  return rangeWithDots
}

// Actions
const openCreateDialog = () => {
  editingCustomer.value = null
  showDialog.value = true
}

const handleEdit = (customer: any) => {
  editingCustomer.value = customer
  showDialog.value = true
}

const handleDelete = async (customer: any) => {
  if (confirm($t('mod.customers.confirm_delete'))) {
    await store.save({ ...customer, status: 'deleted' })
  }
}

const handleSaved = () => {
  showDialog.value = false
  editingCustomer.value = null
  store.load()
}

const handleExportCSV = () => {
  const headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'City', 'Country']
  const rows = processedCustomers.value.map(c => [
    c.id, c.first_name, c.last_name, c.email, c.phone, c.status,
    c.city || '', c.country || ''
  ])

  const csvContent = [headers, ...rows]
    .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `customers_export_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

const getInitials = (customer: any) => {
  return `${customer.first_name?.[0] || ''}${customer.last_name?.[0] || ''}`.toUpperCase()
}

// Load data on mount
onMounted(() => {
  store.load()

  // Setup scroll listener for mobile
  const mainContainer = document.querySelector('main')
  if (mainContainer) {
    let lastScrollY = mainContainer.scrollTop

    const updateScrollDirection = () => {
      const scrollY = mainContainer.scrollTop
      const direction = scrollY > lastScrollY ? 'down' : 'up'

      if (direction !== scrollDirection.value && Math.abs(scrollY - lastScrollY) > 5) {
        scrollDirection.value = direction
      }
      scrolledToTop.value = scrollY < 10
      lastScrollY = scrollY > 0 ? scrollY : 0
    }

    mainContainer.addEventListener('scroll', updateScrollDirection)
  }
})

// Reset pagination when filters change
watch([searchQuery, activeFilters, itemsPerPage], () => {
  currentPage.value = 1
}, { deep: true })

// Simple CustomersList component for mobile
const CustomersList = ({ customers, onEdit, onDelete }: any) => {
  // This would be a separate component in production
  return null
}
</script>

<style scoped>
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slideDown {
  animation: slideDown 0.2s ease-out;
}

/* Hide scrollbar but keep functionality */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
