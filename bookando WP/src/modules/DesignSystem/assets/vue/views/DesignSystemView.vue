<template>
  <div class="flex flex-col min-h-full bg-slate-50">
    <div class="flex flex-col gap-6 p-4 sm:p-6 lg:p-8">
      <div class="flex flex-col gap-4">
        <div
          v-if="headerMode === 'unified'"
          class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
        >
          <div class="flex flex-col lg:flex-row">
            <div
              class="relative overflow-hidden bg-gradient-to-br from-blue-900 to-slate-800 text-white p-6 flex-1"
            >
              <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                  <div class="p-2 bg-white/10 rounded-2xl">
                    <Layers class="text-white/90" :size="24" />
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
            <div class="flex-1 p-6">
              <div class="flex flex-col gap-4">
                <div class="flex flex-wrap items-center gap-3">
                  <div class="relative flex-1 min-w-[220px]">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                    <input
                      v-model="searchQuery"
                      type="text"
                      :placeholder="$t('mod.design_system.search_placeholder')"
                      class="w-full pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all border border-slate-200 rounded-xl bg-slate-50 focus:bg-white"
                    />
                  </div>
                  <button
                    type="button"
                    @click="toggleFilter"
                    :class="[
                      'transition-colors flex items-center justify-center shrink-0 border rounded-xl px-3 py-2.5 text-sm font-medium',
                      showFilter
                        ? 'bg-blue-50 border-blue-200 text-blue-700'
                        : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                    ]"
                    :title="$t('mod.design_system.actions.toggle_filter')"
                  >
                    <Filter :size="16" class="mr-2" />
                    <span>{{ $t('mod.design_system.actions.filter') }}</span>
                  </button>
                  <button
                    type="button"
                    class="flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
                    @click="triggerPrimaryAction"
                  >
                    <Plus :size="16" />
                    <span>{{ $t('mod.design_system.actions.primary') }}</span>
                  </button>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                  <span class="text-slate-500 font-semibold uppercase tracking-wide">
                    {{ $t('mod.design_system.layout.label') }}
                  </span>
                  <button
                    type="button"
                    @click="tabsLayout = 'left'"
                    :class="[
                      'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                      tabsLayout === 'left'
                        ? 'bg-slate-900 text-white border-slate-900'
                        : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                    ]"
                  >
                    <ToggleLeft :size="16" />
                    {{ $t('mod.design_system.layout.tabs_left') }}
                  </button>
                  <button
                    type="button"
                    @click="tabsLayout = 'top'"
                    :class="[
                      'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                      tabsLayout === 'top'
                        ? 'bg-slate-900 text-white border-slate-900'
                        : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                    ]"
                  >
                    <ToggleLeft :size="16" class="rotate-90" />
                    {{ $t('mod.design_system.layout.tabs_top') }}
                  </button>
                  <button
                    type="button"
                    @click="headerMode = 'unified'"
                    :class="[
                      'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                      headerMode === 'unified'
                        ? 'bg-slate-900 text-white border-slate-900'
                        : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                    ]"
                  >
                    <ToggleLeft :size="16" />
                    {{ $t('mod.design_system.layout.header_unified') }}
                  </button>
                  <button
                    type="button"
                    @click="headerMode = 'split'"
                    :class="[
                      'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                      headerMode === 'split'
                        ? 'bg-slate-900 text-white border-slate-900'
                        : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                    ]"
                  >
                    <ToggleLeft :size="16" class="rotate-90" />
                    {{ $t('mod.design_system.layout.header_split') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="flex flex-col gap-4 lg:flex-row lg:items-stretch">
          <div
            class="relative overflow-hidden bg-gradient-to-br from-blue-900 to-slate-800 text-white p-6 rounded-2xl shadow-lg flex-1"
          >
            <div class="relative z-10">
              <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-white/10 rounded-2xl">
                  <Layers class="text-white/90" :size="24" />
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

          <div class="flex-1 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex flex-col gap-4">
              <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[220px]">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                  <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="$t('mod.design_system.search_placeholder')"
                    class="w-full pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all border border-slate-200 rounded-xl bg-slate-50 focus:bg-white"
                  />
                </div>
                <button
                  type="button"
                  @click="toggleFilter"
                  :class="[
                    'transition-colors flex items-center justify-center shrink-0 border rounded-xl px-3 py-2.5 text-sm font-medium',
                    showFilter
                      ? 'bg-blue-50 border-blue-200 text-blue-700'
                      : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                  ]"
                  :title="$t('mod.design_system.actions.toggle_filter')"
                >
                  <Filter :size="16" class="mr-2" />
                  <span>{{ $t('mod.design_system.actions.filter') }}</span>
                </button>
                <button
                  type="button"
                  class="flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
                  @click="triggerPrimaryAction"
                >
                  <Plus :size="16" />
                  <span>{{ $t('mod.design_system.actions.primary') }}</span>
                </button>
              </div>
              <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-slate-500 font-semibold uppercase tracking-wide">
                  {{ $t('mod.design_system.layout.label') }}
                </span>
                <button
                  type="button"
                  @click="tabsLayout = 'left'"
                  :class="[
                    'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                    tabsLayout === 'left'
                      ? 'bg-slate-900 text-white border-slate-900'
                      : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                  ]"
                >
                  <ToggleLeft :size="16" />
                  {{ $t('mod.design_system.layout.tabs_left') }}
                </button>
                <button
                  type="button"
                  @click="tabsLayout = 'top'"
                  :class="[
                    'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                    tabsLayout === 'top'
                      ? 'bg-slate-900 text-white border-slate-900'
                      : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                  ]"
                >
                  <ToggleLeft :size="16" class="rotate-90" />
                  {{ $t('mod.design_system.layout.tabs_top') }}
                </button>
                <button
                  type="button"
                  @click="headerMode = 'unified'"
                  :class="[
                    'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                    headerMode === 'unified'
                      ? 'bg-slate-900 text-white border-slate-900'
                      : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                  ]"
                >
                  <ToggleLeft :size="16" />
                  {{ $t('mod.design_system.layout.header_unified') }}
                </button>
                <button
                  type="button"
                  @click="headerMode = 'split'"
                  :class="[
                    'flex items-center gap-2 px-3 py-2 border rounded-lg font-medium transition-colors',
                    headerMode === 'split'
                      ? 'bg-slate-900 text-white border-slate-900'
                      : 'bg-white text-slate-600 border-slate-200 hover:text-blue-600'
                  ]"
                >
                  <ToggleLeft :size="16" class="rotate-90" />
                  {{ $t('mod.design_system.layout.header_split') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div :class="['flex flex-col gap-6', tabsLayout === 'left' ? 'lg:flex-row' : '']">
        <nav
          class="text-sm"
          :class="tabsLayout === 'left'
            ? 'bg-white rounded-xl border border-slate-200 shadow-sm p-2 space-y-1 lg:w-64'
            : 'flex gap-2 overflow-x-auto pb-2 no-scrollbar'
          "
        >
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="activeTab = tab.id"
            :class="[
              'flex items-center gap-3 transition-all whitespace-nowrap',
              tabsLayout === 'left'
                ? activeTab === tab.id
                  ? 'w-full text-left px-4 py-2.5 rounded-lg text-sm font-bold bg-slate-100 text-slate-900 shadow-sm'
                  : 'w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50'
                : activeTab === tab.id
                  ? 'px-5 py-2.5 rounded-full text-sm font-bold border bg-slate-900 text-white border-slate-900 shadow-lg'
                  : 'px-5 py-2.5 rounded-full text-sm font-bold border bg-white text-slate-600 border-slate-200'
            ]"
          >
            <component :is="tab.icon" :size="18" />
            <span>{{ tab.label }}</span>
          </button>
        </nav>

        <div class="flex-1 flex flex-col">
          <div
            v-if="showFilter"
            class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 md:p-6 mb-4"
          >
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <select
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option>{{ $t('mod.design_system.filters.all_categories') }}</option>
              </select>
              <select
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option>{{ $t('mod.design_system.filters.all_types') }}</option>
              </select>
              <select
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
              >
                <option>{{ $t('mod.design_system.filters.active_only') }}</option>
              </select>
            </div>
          </div>
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
                        class="p-1.5 text-slate-400 hover:text-blue-600 bg-slate-50 rounded-md"
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
                    ? 'bg-blue-600 text-white shadow-sm'
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
  ToggleLeft,
  Filter,
  Search,
  Clock,
  Edit2,
  Trash2,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const { t, locale } = useI18n()

const tabsLayout = ref<'left' | 'top'>('left')
const headerMode = ref<'unified' | 'split'>('unified')

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
