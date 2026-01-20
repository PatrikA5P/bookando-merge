<template>
  <div class="bookando-admin bookando-admin-page flex flex-col min-h-full bg-slate-50/50">
    <!-- Mobile & Tablet Layout -->
    <div class="lg:hidden flex flex-col min-h-screen">
      <div
        class="sticky top-0 left-0 right-0 z-20 transition-transform duration-300 ease-in-out shadow-lg bg-gradient-to-r from-blue-900 to-slate-800 text-white"
      >
        <div class="px-4 pt-4 pb-2">
          <div class="flex items-center gap-2 mb-3">
            <Layers :size="20" class="text-white/80" />
            <h2 class="font-bold text-lg">{{ $t('mod.design_system.hero.title') }}</h2>
          </div>
          <div class="flex overflow-x-auto no-scrollbar gap-2 pb-1">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              @click="activeTab = tab.id"
              :class="[
                'flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap',
                activeTab === tab.id
                  ? 'bg-white text-brand-700 shadow-sm'
                  : 'bg-white/10 text-white hover:bg-white/20'
              ]"
            >
              <component :is="tab.icon" :size="16" />
              <span v-if="activeTab === tab.id">{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <div class="px-4 pb-3 flex gap-2">
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70" :size="16" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search..."
              class="w-full pl-9 pr-4 py-2 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
            />
          </div>
          <button
            type="button"
            @click="toggleFilter"
            :class="[
              'p-2 rounded-lg border border-white/20 transition-colors',
              showFilter ? 'bg-white text-brand-700' : 'bg-white/10 text-white hover:bg-white/20'
            ]"
          >
            <Filter :size="20" />
          </button>
        </div>

        <div
          v-if="showFilter"
          class="bg-white border-b border-slate-200 p-4 shadow-inner max-h-[50vh] overflow-y-auto text-slate-800"
        >
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <select class="input-field">
              <option>{{ $t('mod.design_system.filters.all_categories') }}</option>
            </select>
            <select class="input-field">
              <option>{{ $t('mod.design_system.filters.all_types') }}</option>
            </select>
            <select class="input-field">
              <option>{{ $t('mod.design_system.filters.active_only') }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="flex-1">
        <div class="p-2 md:p-6 flex-1">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-2 md:gap-6">
            <div
              v-for="item in paginatedItems"
              :key="item.id"
              class="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden"
            >
              <div class="h-40 bg-slate-100 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                <div class="absolute bottom-3 left-3">
                  <span
                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold shadow-sm border backdrop-blur-md bg-white/95 text-blue-700"
                  >
                    <Clock :size="14" />
                    {{ $t('mod.design_system.cards.service_label') }}
                  </span>
                </div>
              </div>
              <div class="p-5 flex-1 flex flex-col">
                <div class="flex justify-between items-start mb-2">
                  <span
                    class="text-[10px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200 px-1.5 rounded bg-slate-50"
                  >
                    {{ $t(item.typeKey) }}
                  </span>
                  <div class="font-bold text-lg text-slate-900">
                    {{ formatPrice(item.price) }}
                  </div>
                </div>
                <h3 class="font-bold text-slate-800 text-lg mb-2 leading-tight">
                  {{ $t(item.titleKey) }}
                </h3>
                <p class="text-slate-500 text-sm mb-4 line-clamp-2 flex-1">
                  {{ $t(item.descKey) }}
                </p>
                <div class="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center">
                  <div class="flex gap-2">
                    <button
                      type="button"
                      class="p-1.5 text-slate-400 hover:text-brand-600 bg-slate-50 rounded-md"
                      :title="$t('mod.design_system.cards.edit')"
                    >
                      <Edit2 :size="16" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 text-slate-400 hover:text-rose-600 bg-slate-50 rounded-md"
                      :title="$t('mod.design_system.cards.delete')"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="paginatedItems.length === 0" class="col-span-full py-12 text-center text-slate-400">
              <p class="text-lg font-medium">
                {{ $t('mod.design_system.empty_state', { query: searchQuery }) }}
              </p>
            </div>
          </div>
        </div>

        <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4 mt-auto">
          <div class="flex flex-col sm:flex-row sm:items-center gap-3 text-sm text-slate-600">
            <span>
              {{
                $t('mod.design_system.pagination.summary', {
                  start: totalItems > 0 ? startItem : 0,
                  end: endItem,
                  total: totalItems,
                })
              }}
            </span>
            <select
              v-model.number="itemsPerPage"
              class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer"
            >
              <option v-for="option in itemsPerPageOptions" :key="option" :value="option">
                {{ $t('mod.design_system.pagination.per_page', { count: option }) }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-1">
            <button
              type="button"
              @click="goToPreviousPage"
              :disabled="currentPage === 1"
              class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              :title="$t('mod.design_system.pagination.previous')"
            >
              <ChevronLeft :size="16" />
            </button>

            <button
              v-for="page in pageNumbers"
              :key="`page-${page}`"
              type="button"
              @click="goToPage(page)"
              :disabled="typeof page !== 'number'"
              :class="[
                'min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors',
                page === currentPage
                  ? 'bg-brand-600 text-white shadow-sm'
                  : typeof page === 'number'
                    ? 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'
                    : 'text-slate-400 cursor-default'
              ]"
            >
              {{ page }}
            </button>

            <button
              type="button"
              @click="goToNextPage"
              :disabled="currentPage === totalPages || totalPages === 0"
              class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              :title="$t('mod.design_system.pagination.next')"
            >
              <ChevronRight :size="16" />
            </button>
          </div>
        </div>
      </div>

      <button
        type="button"
        @click="triggerPrimaryAction"
        class="fixed bottom-6 right-6 w-14 h-14 bg-brand-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 hover:bg-brand-700 active:scale-95 transition-all"
      >
        <Plus :size="28" />
      </button>
    </div>

    <!-- Desktop Layout -->
    <div class="hidden lg:flex min-h-full p-6 gap-6 items-start">
      <aside class="w-72 flex-shrink-0 flex flex-col gap-6 sticky top-6 self-start z-40">
        <div
          class="relative overflow-hidden shrink-0 transition-all bg-gradient-to-br from-blue-900 to-slate-800 text-white p-6 shadow-lg rounded-xl flex flex-col justify-center"
        >
          <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
              <div class="text-white/80">
                <Layers :size="24" />
              </div>
              <h2 class="font-bold text-xl">{{ $t('mod.design_system.hero.title') }}</h2>
            </div>
            <p class="text-xs max-w-2xl text-white/70">
              {{ $t('mod.design_system.hero.description') }}
            </p>
          </div>
          <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
            <Layers :size="100" />
          </div>
        </div>

        <nav class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 space-y-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-3 transition-all whitespace-nowrap w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium',
              activeTab === tab.id
                ? 'bg-slate-100 text-slate-900 font-bold shadow-sm'
                : 'text-slate-600 hover:bg-slate-50'
            ]"
          >
            <component :is="tab.icon" :size="18" />
            <span>{{ tab.label }}</span>
          </button>
        </nav>
      </aside>

      <div class="flex-1 flex flex-col min-w-0">
        <div class="sticky top-0 z-30 bg-slate-50 pt-6 -mt-6">
          <div
            class="bg-white p-6 rounded-t-xl rounded-b-none border border-slate-200 border-b-0 shadow-sm flex flex-wrap justify-between items-center gap-y-0 relative z-20"
          >
            <div class="flex flex-col min-w-[140px] order-1">
              <h3 class="font-bold text-slate-800 text-lg truncate">
                {{ activeTabLabel || $t('mod.design_system.hero.title') }}
              </h3>
              <p class="text-xs text-slate-500 truncate">
                Manage your {{ activeTabLabel?.toLowerCase() || 'design system' }} inventory
              </p>
            </div>

            <div class="order-3 w-full xl:order-2 xl:w-auto xl:flex-1 xl:flex xl:justify-end gap-3 flex items-center mt-4 xl:mt-0">
              <div class="relative flex-1 min-w-0 xl:w-auto xl:min-w-[240px] xl:flex-none">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search..."
                  class="w-full pl-9 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white"
                />
              </div>
              <button
                type="button"
                @click="toggleFilter"
                :class="[
                  'transition-colors flex items-center justify-center shrink-0 border rounded-xl p-2.5',
                  showFilter
                    ? 'bg-brand-50 border-brand-200 text-brand-700'
                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                ]"
              >
                <Filter :size="18" />
              </button>
            </div>

            <div class="order-2 xl:order-3 shrink-0">
              <button
                type="button"
                @click="triggerPrimaryAction"
                class="flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0 bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
              >
                <Plus :size="18" />
                <span>{{ $t('mod.design_system.actions.primary') }}</span>
              </button>
            </div>
          </div>

          <div
            v-if="showFilter"
            class="bg-white border-x border-slate-200 border-b-0 p-6 relative z-10 animate-slideDown"
          >
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <select class="input-field">
                <option>{{ $t('mod.design_system.filters.all_categories') }}</option>
              </select>
              <select class="input-field">
                <option>{{ $t('mod.design_system.filters.all_types') }}</option>
              </select>
              <select class="input-field">
                <option>{{ $t('mod.design_system.filters.active_only') }}</option>
              </select>
            </div>
          </div>
        </div>

        <main class="bg-white border border-slate-200 border-t-0 shadow-sm rounded-b-xl rounded-t-none overflow-hidden flex-1 relative z-0">
          <div class="p-2 md:p-6 flex-1">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-2 md:gap-6">
              <div
                v-for="item in paginatedItems"
                :key="item.id"
                class="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden"
              >
                <div class="h-40 bg-slate-100 relative overflow-hidden">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                  <div class="absolute bottom-3 left-3">
                    <span
                      class="flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold shadow-sm border backdrop-blur-md bg-white/95 text-blue-700"
                    >
                      <Clock :size="14" />
                      {{ $t('mod.design_system.cards.service_label') }}
                    </span>
                  </div>
                </div>
                <div class="p-5 flex-1 flex flex-col">
                  <div class="flex justify-between items-start mb-2">
                    <span
                      class="text-[10px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200 px-1.5 rounded bg-slate-50"
                    >
                      {{ $t(item.typeKey) }}
                    </span>
                    <div class="font-bold text-lg text-slate-900">
                      {{ formatPrice(item.price) }}
                    </div>
                  </div>
                  <h3 class="font-bold text-slate-800 text-lg mb-2 leading-tight">
                    {{ $t(item.titleKey) }}
                  </h3>
                  <p class="text-slate-500 text-sm mb-4 line-clamp-2 flex-1">
                    {{ $t(item.descKey) }}
                  </p>
                  <div class="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center">
                    <div class="flex gap-2">
                      <button
                        type="button"
                        class="p-1.5 text-slate-400 hover:text-brand-600 bg-slate-50 rounded-md"
                        :title="$t('mod.design_system.cards.edit')"
                      >
                        <Edit2 :size="16" />
                      </button>
                      <button
                        type="button"
                        class="p-1.5 text-slate-400 hover:text-rose-600 bg-slate-50 rounded-md"
                        :title="$t('mod.design_system.cards.delete')"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="paginatedItems.length === 0" class="col-span-full py-12 text-center text-slate-400">
                <p class="text-lg font-medium">
                  {{ $t('mod.design_system.empty_state', { query: searchQuery }) }}
                </p>
              </div>
            </div>
          </div>

          <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4 mt-auto">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 text-sm text-slate-600">
              <span>
                {{
                  $t('mod.design_system.pagination.summary', {
                    start: totalItems > 0 ? startItem : 0,
                    end: endItem,
                    total: totalItems,
                  })
                }}
              </span>
              <select
                v-model.number="itemsPerPage"
                class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer"
              >
                <option v-for="option in itemsPerPageOptions" :key="option" :value="option">
                  {{ $t('mod.design_system.pagination.per_page', { count: option }) }}
                </option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                type="button"
                @click="goToPreviousPage"
                :disabled="currentPage === 1"
                class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                :title="$t('mod.design_system.pagination.previous')"
              >
                <ChevronLeft :size="16" />
              </button>

              <button
                v-for="page in pageNumbers"
                :key="`page-desktop-${page}`"
                type="button"
                @click="goToPage(page)"
                :disabled="typeof page !== 'number'"
                :class="[
                  'min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors',
                  page === currentPage
                    ? 'bg-brand-600 text-white shadow-sm'
                    : typeof page === 'number'
                      ? 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'
                      : 'text-slate-400 cursor-default'
                ]"
              >
                {{ page }}
              </button>

              <button
                type="button"
                @click="goToNextPage"
                :disabled="currentPage === totalPages || totalPages === 0"
                class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                :title="$t('mod.design_system.pagination.next')"
              >
                <ChevronRight :size="16" />
              </button>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Layers,
  List,
  Package,
  Ticket,
  LayoutTemplate,
  Tag,
  Plus,
  Filter,
  Search,
  Clock,
  Edit2,
  Trash2,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const { t, locale } = useI18n()

const activeTab = ref('catalog')
const searchQuery = ref('')
const showFilter = ref(false)

const itemsPerPage = ref(6)
const currentPage = ref(1)

const itemsPerPageOptions = [6, 12, 24, 48]

const items = computed(() => [
  {
    id: 1,
    titleKey: 'mod.design_system.items.deep_tissue.title',
    descKey: 'mod.design_system.items.deep_tissue.description',
    typeKey: 'mod.design_system.types.wellness',
    price: 120,
  },
  {
    id: 2,
    titleKey: 'mod.design_system.items.swedish.title',
    descKey: 'mod.design_system.items.swedish.description',
    typeKey: 'mod.design_system.types.wellness',
    price: 90,
  },
  {
    id: 3,
    titleKey: 'mod.design_system.items.yoga.title',
    descKey: 'mod.design_system.items.yoga.description',
    typeKey: 'mod.design_system.types.fitness',
    price: 25,
  },
  {
    id: 4,
    titleKey: 'mod.design_system.items.pilates.title',
    descKey: 'mod.design_system.items.pilates.description',
    typeKey: 'mod.design_system.types.fitness',
    price: 40,
  },
  {
    id: 5,
    titleKey: 'mod.design_system.items.nutrition.title',
    descKey: 'mod.design_system.items.nutrition.description',
    typeKey: 'mod.design_system.types.health',
    price: 150,
  },
  {
    id: 6,
    titleKey: 'mod.design_system.items.acupuncture.title',
    descKey: 'mod.design_system.items.acupuncture.description',
    typeKey: 'mod.design_system.types.health',
    price: 110,
  },
  {
    id: 7,
    titleKey: 'mod.design_system.items.meditation.title',
    descKey: 'mod.design_system.items.meditation.description',
    typeKey: 'mod.design_system.types.mindfulness',
    price: 15,
  },
  {
    id: 8,
    titleKey: 'mod.design_system.items.hot_stone.title',
    descKey: 'mod.design_system.items.hot_stone.description',
    typeKey: 'mod.design_system.types.wellness',
    price: 130,
  },
  {
    id: 9,
    titleKey: 'mod.design_system.items.crossfit.title',
    descKey: 'mod.design_system.items.crossfit.description',
    typeKey: 'mod.design_system.types.fitness',
    price: 30,
  },
  {
    id: 10,
    titleKey: 'mod.design_system.items.physiotherapy.title',
    descKey: 'mod.design_system.items.physiotherapy.description',
    typeKey: 'mod.design_system.types.health',
    price: 140,
  },
  {
    id: 11,
    titleKey: 'mod.design_system.items.reiki.title',
    descKey: 'mod.design_system.items.reiki.description',
    typeKey: 'mod.design_system.types.mindfulness',
    price: 80,
  },
  {
    id: 12,
    titleKey: 'mod.design_system.items.spin.title',
    descKey: 'mod.design_system.items.spin.description',
    typeKey: 'mod.design_system.types.fitness',
    price: 20,
  },
  {
    id: 13,
    titleKey: 'mod.design_system.items.facial.title',
    descKey: 'mod.design_system.items.facial.description',
    typeKey: 'mod.design_system.types.beauty',
    price: 95,
  },
  {
    id: 14,
    titleKey: 'mod.design_system.items.manicure.title',
    descKey: 'mod.design_system.items.manicure.description',
    typeKey: 'mod.design_system.types.beauty',
    price: 50,
  },
  {
    id: 15,
    titleKey: 'mod.design_system.items.personal_training.title',
    descKey: 'mod.design_system.items.personal_training.description',
    typeKey: 'mod.design_system.types.fitness',
    price: 100,
  },
])

const tabs = computed(() => [
  { id: 'catalog', label: t('mod.design_system.tabs.catalog'), icon: Layers },
  { id: 'categories', label: t('mod.design_system.tabs.categories'), icon: List },
  { id: 'bundles', label: t('mod.design_system.tabs.bundles'), icon: Package },
  { id: 'vouchers', label: t('mod.design_system.tabs.vouchers'), icon: Ticket },
  { id: 'forms', label: t('mod.design_system.tabs.forms'), icon: LayoutTemplate },
  { id: 'tags', label: t('mod.design_system.tabs.tags'), icon: Tag },
])

const activeTabLabel = computed(() => tabs.value.find((tab) => tab.id === activeTab.value)?.label)

const filteredItems = computed(() => {
  if (!searchQuery.value) {
    return items.value
  }
  const lowerQuery = searchQuery.value.toLowerCase()
  return items.value.filter((item) => {
    const title = t(item.titleKey).toLowerCase()
    const type = t(item.typeKey).toLowerCase()
    const desc = t(item.descKey).toLowerCase()
    return title.includes(lowerQuery) || type.includes(lowerQuery) || desc.includes(lowerQuery)
  })
})

const totalItems = computed(() => filteredItems.value.length)
const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))
const startItem = computed(() => (currentPage.value - 1) * itemsPerPage.value + 1)
const endItem = computed(() => Math.min(currentPage.value * itemsPerPage.value, totalItems.value))

const paginatedItems = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value
  return filteredItems.value.slice(startIndex, startIndex + itemsPerPage.value)
})

const pageNumbers = computed(() => {
  const delta = 1
  const range: Array<number> = []
  const rangeWithDots: Array<number | string> = []
  let last: number | undefined

  if (totalPages.value <= 1) {
    return [1]
  }

  range.push(1)
  for (let i = currentPage.value - delta; i <= currentPage.value + delta; i += 1) {
    if (i < totalPages.value && i > 1) {
      range.push(i)
    }
  }
  range.push(totalPages.value)

  for (const number of range) {
    if (last !== undefined) {
      if (number - last === 2) {
        rangeWithDots.push(last + 1)
      } else if (number - last !== 1) {
        rangeWithDots.push(t('mod.design_system.pagination.ellipsis'))
      }
    }
    rangeWithDots.push(number)
    last = number
  }

  return rangeWithDots
})

const formatPrice = (value: number) =>
  new Intl.NumberFormat(locale.value, {
    style: 'currency',
    currency: t('mod.design_system.currency_code'),
    maximumFractionDigits: 0,
  }).format(value)

const toggleFilter = () => {
  showFilter.value = !showFilter.value
}

const triggerPrimaryAction = () => {
  window.alert(t('mod.design_system.actions.primary_alert'))
}

const goToPreviousPage = () => {
  currentPage.value = Math.max(currentPage.value - 1, 1)
}

const goToNextPage = () => {
  currentPage.value = Math.min(currentPage.value + 1, totalPages.value)
}

const goToPage = (page: number | string) => {
  if (typeof page === 'number') {
    currentPage.value = page
  }
}

watch([searchQuery, itemsPerPage], () => {
  currentPage.value = 1
})
</script>

<style>
.input-field {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.5rem;
  font-size: 0.875rem;
  background-color: white;
}
</style>
