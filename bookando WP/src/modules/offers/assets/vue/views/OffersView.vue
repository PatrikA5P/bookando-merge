<template>
  <!-- Offers Module - Services Catalog - Pure Tailwind, ModuleLayout Pattern -->
  <div class="flex flex-col min-h-full bg-slate-50/50">

    <!-- ==== MOBILE & TABLET LAYOUT (Sticky Scroll Away - Up to LG breakpoint) ==== -->
    <div class="lg:hidden flex flex-col min-h-screen">
      <!-- Sticky Header Container -->
      <div
        :class="[
          'sticky top-0 left-0 right-0 z-20 transition-transform duration-300 ease-in-out shadow-lg',
          'bg-gradient-to-r from-rose-700 to-pink-900 text-white',
          isHeaderVisible ? 'translate-y-0' : '-translate-y-full'
        ]"
      >
        <!-- Part 1: Title -->
        <div class="px-4 pt-4 pb-2">
          <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h2 class="font-bold text-lg">{{ $t('mod.services.title') }}</h2>
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
              :placeholder="$t('mod.services.list.search_placeholder')"
              class="w-full pl-9 pr-4 py-2 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
            >
          </div>
          <button
            @click="isFilterOpen = !isFilterOpen"
            :class="[
              'p-2 rounded-lg border border-white/20 transition-colors',
              isFilterOpen ? 'bg-white text-rose-700' : 'bg-white/10 text-white hover:bg-white/20'
            ]"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
          </button>
        </div>

        <!-- Filter Content Panel (if open) -->
        <div v-if="isFilterOpen" class="bg-white border-b border-slate-200 p-4 shadow-inner max-h-[50vh] overflow-y-auto text-slate-800">
          <div class="space-y-4">
            <!-- Category Filter -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('mod.services.category.label') }}</label>
              <select
                v-model="activeFilters.category"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm appearance-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none"
              >
                <option value="">{{ $t('core.common.all') }}</option>
                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('fields.status') }}</label>
              <div class="space-y-2">
                <label v-for="status in statusOptions" :key="status" class="flex items-center gap-2 cursor-pointer group">
                  <div :class="[
                    'w-4 h-4 rounded border flex items-center justify-center transition-colors',
                    activeFilters.status.includes(status) ? 'bg-rose-600 border-rose-600' : 'border-slate-300 bg-white'
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
              <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('ui.table.sort_by') }}</label>
              <select
                v-model="activeFilters.sortBy"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm appearance-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none"
              >
                <option value="name_asc">{{ $t('ui.table.sort_name') }} A-Z</option>
                <option value="name_desc">{{ $t('ui.table.sort_name') }} Z-A</option>
                <option value="price_asc">{{ $t('ui.table.sort_price') }} ↑</option>
                <option value="price_desc">{{ $t('ui.table.sort_price') }} ↓</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Wrapper (mobile cards would go here) -->
      <div class="flex-1 p-4">
        <div class="text-center py-12 text-slate-400">
          <p>{{ $t('mod.services.list.empty') }}</p>
        </div>
      </div>

      <!-- Mobile Floating Action Button -->
      <button
        @click="openCreateDialog"
        class="fixed bottom-6 right-6 w-14 h-14 bg-rose-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 hover:bg-rose-700 active:scale-95 transition-all"
      >
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <!-- ==== DESKTOP LAYOUT (Large Screens) ==== -->
    <div class="hidden lg:flex min-h-full p-6 gap-6 items-start">
      <!-- Sticky Header Wrapper -->
      <div class="flex-1 flex flex-col min-w-0">
        <div class="sticky top-0 z-30 bg-slate-50 pt-6 pb-6 -mt-6">
          <div class="flex gap-6 items-stretch">
            <!-- Left: Module Identity (Hero Section) -->
            <div class="w-72 shrink-0 z-20 relative">
              <div class="relative overflow-hidden bg-gradient-to-br from-rose-700 to-pink-900 text-white p-6 rounded-xl shadow-lg h-full flex flex-col justify-center">
                <div class="relative z-10">
                  <div class="flex items-center gap-3 mb-2">
                    <svg class="w-6 h-6 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <h2 class="font-bold text-xl">{{ $t('mod.services.title') }}</h2>
                  </div>
                  <p class="text-xs max-w-2xl text-white/70">{{ $t('mod.services.description') }}</p>
                </div>
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
                  <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
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
                      :placeholder="$t('mod.services.list.search_placeholder')"
                      class="w-full pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all border border-slate-200 rounded-xl bg-slate-50 focus:bg-white"
                    >
                  </div>

                  <!-- Filter Toggle -->
                  <button
                    @click="isFilterOpen = !isFilterOpen"
                    :class="[
                      'transition-colors flex items-center justify-center shrink-0 border rounded-xl p-2.5',
                      isFilterOpen ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
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
                    <span class="hidden md:inline">{{ $t('core.actions.export.label') }}</span>
                  </button>
                </div>

                <div class="pl-3 border-l border-slate-100">
                  <button
                    @click="openCreateDialog"
                    class="flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0 bg-rose-600 hover:bg-rose-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
                  >
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>{{ $t('mod.services.add_service') }}</span>
                  </button>
                </div>
              </div>

              <!-- Filter Expansion inside the action box -->
              <div v-if="isFilterOpen" class="mt-4 pt-4 border-t border-slate-100 animate-slideDown">
                <div class="grid grid-cols-3 gap-4">
                  <!-- Category Filter -->
                  <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('mod.services.category.label') }}</label>
                    <select
                      v-model="activeFilters.category"
                      class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm appearance-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none"
                    >
                      <option value="">{{ $t('core.common.all') }}</option>
                      <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                    </select>
                  </div>

                  <!-- Status Filter -->
                  <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('fields.status') }}</label>
                    <div class="space-y-2">
                      <label v-for="status in statusOptions" :key="status" class="flex items-center gap-2 cursor-pointer group">
                        <div :class="[
                          'w-4 h-4 rounded border flex items-center justify-center transition-colors',
                          activeFilters.status.includes(status) ? 'bg-rose-600 border-rose-600' : 'border-slate-300 bg-white'
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
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">{{ $t('ui.table.sort_by') }}</label>
                    <select
                      v-model="activeFilters.sortBy"
                      class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm appearance-none bg-slate-50 focus:bg-white focus:ring-2 focus:ring-rose-500 outline-none"
                    >
                      <option value="name_asc">{{ $t('ui.table.sort_name') }} A-Z</option>
                      <option value="name_desc">{{ $t('ui.table.sort_name') }} Z-A</option>
                      <option value="price_asc">{{ $t('ui.table.sort_price') }} ↑</option>
                      <option value="price_desc">{{ $t('ui.table.sort_price') }} ↓</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Content Body -->
        <main class="bg-white border border-slate-200 shadow-sm flex-1 flex flex-col z-0 relative rounded-xl overflow-hidden">
          <!-- Table -->
          <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left border-collapse">
              <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
                <tr>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.services.list.columns.name') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.services.list.columns.category') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.services.list.columns.duration') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.services.list.columns.price') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.services.list.columns.status') }}</th>
                  <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">{{ $t('ui.table.actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                <tr
                  v-for="service in paginatedServices"
                  :key="service.id"
                  :class="[
                    'hover:bg-slate-50 transition-colors group',
                    service.status === 'hidden' ? 'opacity-60 bg-slate-50' : ''
                  ]"
                >
                  <td class="p-4">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center font-semibold text-sm shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                      </div>
                      <div>
                        <div class="font-medium text-slate-900">{{ service.title || service.name }}</div>
                        <div class="text-xs text-slate-500">#{{ service.id }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="p-4">
                    <span class="text-sm text-slate-600">{{ service.category || $t('mod.services.uncategorized') }}</span>
                  </td>
                  <td class="p-4">
                    <span class="text-sm text-slate-600">{{ service.duration || '-' }}</span>
                  </td>
                  <td class="p-4">
                    <span class="text-sm font-medium text-slate-900">{{ service.price ? formatPrice(service.price) : '-' }}</span>
                  </td>
                  <td class="p-4">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border',
                      service.status === 'active' || service.status === 'aktiv' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '',
                      service.status === 'inactive' || service.status === 'inaktiv' ? 'bg-slate-100 text-slate-600 border-slate-200' : '',
                      service.status === 'hidden' ? 'bg-amber-50 text-amber-700 border-amber-100' : ''
                    ]">
                      {{ service.status }}
                    </span>
                  </td>
                  <td class="p-4 text-right">
                    <div class="flex justify-end gap-2">
                      <button
                        @click="handleEdit(service)"
                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-full transition-colors"
                      >
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                      </button>
                      <button
                        v-if="service.status !== 'deleted'"
                        @click="handleDelete(service)"
                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-full transition-colors"
                      >
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="paginatedServices.length === 0">
                  <td colspan="6" class="p-12 text-center">
                    <div class="flex flex-col items-center text-slate-400">
                      <svg class="w-12 h-12 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                      <p class="text-lg font-medium text-slate-600">{{ $t('mod.services.list.empty') }}</p>
                      <p class="text-sm">{{ $t('mod.services.adjust_filters') }}</p>
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
                {{ $t('ui.pagination.range', { start: processedServices.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0, end: Math.min(currentPage * itemsPerPage, totalItems), total: totalItems, label: '' }) }}
              </span>
              <select
                v-model="itemsPerPage"
                class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 cursor-pointer"
              >
                <option :value="10">10 {{ $t('ui.pagination.per_page') }}</option>
                <option :value="25">25 {{ $t('ui.pagination.per_page') }}</option>
                <option :value="50">50 {{ $t('ui.pagination.per_page') }}</option>
                <option :value="100">100 {{ $t('ui.pagination.per_page') }}</option>
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
                    ? 'bg-rose-600 text-white shadow-sm'
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

    <!-- Services Form Modal -->
    <component
      v-if="showDialog"
      :is="ServicesForm"
      :service="editingService"
      @close="showDialog = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import { useOffersStore } from '../store/store'

// Lazy load form component
const ServicesForm = defineAsyncComponent(() => import('../components/ServicesForm.vue'))

// i18n
const { t: $t } = useI18n()

// Store
const store = useOffersStore()

// Filter & Pagination State
const searchQuery = ref('')
const isFilterOpen = ref(false)
const activeFilters = ref({
  status: [] as string[],
  category: '',
  sortBy: 'name_asc' as 'name_asc' | 'name_desc' | 'price_asc' | 'price_desc'
})

const currentPage = ref(1)
const itemsPerPage = ref(25)

// Modal State
const showDialog = ref(false)
const editingService = ref<any>(null)

// Status Options
const statusOptions = ['active', 'aktiv', 'inactive', 'inaktiv', 'hidden']

// Categories (would be loaded from store)
const categories = computed(() => {
  const cats = new Set<string>()
  store.items?.forEach((item: any) => {
    if (item.category) cats.add(item.category)
  })
  return Array.from(cats)
})

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
const processedServices = computed(() => {
  let result = [...(store.items || [])]

  // 1. Search
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter((s: any) =>
      s.title?.toLowerCase().includes(q) ||
      s.name?.toLowerCase().includes(q) ||
      s.description?.toLowerCase().includes(q) ||
      s.category?.toLowerCase().includes(q)
    )
  }

  // 2. Category Filter
  if (activeFilters.value.category) {
    result = result.filter((s: any) => s.category === activeFilters.value.category)
  }

  // 3. Status Filter
  if (activeFilters.value.status.length > 0) {
    result = result.filter((s: any) => activeFilters.value.status.includes(s.status))
  }

  // 4. Sorting
  result.sort((a: any, b: any) => {
    switch (activeFilters.value.sortBy) {
      case 'name_asc':
        return (a.title || a.name || '').localeCompare(b.title || b.name || '')
      case 'name_desc':
        return (b.title || b.name || '').localeCompare(a.title || a.name || '')
      case 'price_asc':
        return (a.price || 0) - (b.price || 0)
      case 'price_desc':
        return (b.price || 0) - (a.price || 0)
      default: return 0
    }
  })

  return result
})

// Pagination Logic
const totalItems = computed(() => processedServices.value.length)
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))
const paginatedServices = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = currentPage.value * itemsPerPage.value
  return processedServices.value.slice(start, end)
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
  editingService.value = null
  showDialog.value = true
}

const handleEdit = (service: any) => {
  editingService.value = service
  showDialog.value = true
}

const handleDelete = async (service: any) => {
  if (confirm($t('mod.services.confirm_delete'))) {
    await store.save({ ...service, status: 'deleted' })
  }
}

const handleSaved = () => {
  showDialog.value = false
  editingService.value = null
  store.load()
}

const handleExportCSV = () => {
  const headers = ['ID', 'Name', 'Category', 'Duration', 'Price', 'Status']
  const rows = processedServices.value.map((s: any) => [
    s.id, s.title || s.name, s.category || '', s.duration || '', s.price || '', s.status
  ])

  const csvContent = [headers, ...rows]
    .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `services_export_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

const formatPrice = (price: number | string) => {
  const num = typeof price === 'string' ? parseFloat(price) : price
  return `CHF ${num.toFixed(2)}`
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
