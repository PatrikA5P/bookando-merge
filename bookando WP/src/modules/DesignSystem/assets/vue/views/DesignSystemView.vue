<template>
  <!-- 1) Globales Layout & Modul-Wrapper -->
  <div class="bookando-admin bookando-admin-page flex flex-col min-h-full bg-slate-50/50">
    <!-- 8) Mobile Layout -->
    <div class="lg:hidden flex flex-col min-h-screen">
      <!-- Mobile Header -->
      <header class="sticky top-0 left-0 right-0 z-20 shadow-lg bg-gradient-to-r from-blue-900 to-slate-800 text-white">
        <div class="px-4 pt-4 pb-2">
          <div class="flex items-center gap-2 mb-3">
            <Layers :size="20" class="text-white/80" />
            <div class="min-w-0">
              <div class="font-bold text-lg truncate">{{ t('mod.design_system.hero.title') }}</div>
              <div class="text-xs text-white/70 truncate">{{ t('mod.design_system.hero.description') }}</div>
            </div>
          </div>

          <!-- Mobile Tabs -->
          <div class="flex overflow-x-auto no-scrollbar gap-2 pb-1">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              @click="setTab(tab.id)"
              class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap"
              :class="activeTab === tab.id ? 'bg-white text-brand-700 shadow-sm' : 'bg-white/10 text-white hover:bg-white/20'"
            >
              <component :is="tab.icon" :size="16" />
              <span>{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <!-- Mobile Suche & Filter -->
        <div class="px-4 pb-3 flex gap-2">
          <div class="relative flex-1">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70" :size="16" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="t('mod.design_system.search.placeholder')"
              class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
            />
          </div>

          <button
            type="button"
            @click="toggleFilter"
            class="p-2 rounded-lg border border-white/20 transition-colors"
            :class="showFilter ? 'bg-white text-brand-700' : 'bg-white/10 text-white hover:bg-white/20'"
          >
            <Filter :size="20" />
          </button>
        </div>
      </header>

      <!-- Mobile Filter Dropdown (nutzt bewusst die gleiche Filter-Grid-Logik) -->
      <div v-if="showFilter" class="bg-white border-b border-slate-200 p-4 shadow-inner max-h-[50vh] overflow-y-auto text-slate-800">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <select v-model="filterCategory" class="w-full border border-slate-200 rounded-lg px-2 py-2 text-sm bg-white">
            <option value="all">{{ t('mod.design_system.filters.all_categories') }}</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>

          <select v-model="filterType" class="w-full border border-slate-200 rounded-lg px-2 py-2 text-sm bg-white">
            <option value="all">{{ t('mod.design_system.filters.all_types') }}</option>
            <option v-for="ty in types" :key="ty" :value="ty">{{ ty }}</option>
          </select>

          <select v-model="filterStatus" class="w-full border border-slate-200 rounded-lg px-2 py-2 text-sm bg-white">
            <option value="all">{{ t('mod.design_system.filters.all_status') }}</option>
            <option value="active">{{ t('mod.design_system.filters.active_only') }}</option>
            <option value="inactive">{{ t('mod.design_system.filters.inactive_only') }}</option>
          </select>
        </div>
      </div>

      <!-- 5) Content Area -->
      <main class="bg-white border border-slate-200 border-t-0 shadow-sm rounded-b-xl rounded-t-none overflow-hidden flex-1 relative z-0">
        <div class="p-2 md:p-6 flex-1">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-2 md:gap-6">
            <!-- 6) Cards -->
            <article
              v-for="item in paginatedItems"
              :key="item.id"
              class="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden"
            >
              <div class="h-40 bg-slate-100 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>

                <div class="absolute bottom-3 left-3 flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold shadow-sm border backdrop-blur-md bg-white/95 text-blue-700">
                  <Clock :size="14" />
                  {{ t('mod.design_system.cards.service_label') }}
                </div>
              </div>

              <div class="p-5 flex-1 flex flex-col">
                <div class="flex justify-between items-start mb-2">
                  <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200 px-1.5 rounded bg-slate-50">
                    {{ item.type }}
                  </span>
                  <div class="font-bold text-lg text-slate-900">
                    {{ formatPrice(item.price) }}
                  </div>
                </div>

                <h3 class="font-bold text-slate-800 text-lg mb-2 leading-tight">
                  {{ item.title }}
                </h3>

                <p class="text-slate-500 text-sm mb-4 line-clamp-2 flex-1">
                  {{ item.description }}
                </p>

                <footer class="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center">
                  <div class="flex gap-2">
                    <button
                      type="button"
                      class="p-1.5 text-slate-400 hover:text-brand-600 bg-slate-50 rounded-md"
                      :title="t('mod.design_system.cards.edit')"
                      @click="onEdit(item)"
                    >
                      <Edit2 :size="16" />
                    </button>

                    <button
                      type="button"
                      class="p-1.5 text-slate-400 hover:text-rose-600 bg-slate-50 rounded-md"
                      :title="t('mod.design_system.cards.delete')"
                      @click="onDelete(item)"
                    >
                      <Trash2 :size="16" />
                    </button>
                  </div>
                </footer>
              </div>
            </article>

            <div v-if="paginatedItems.length === 0" class="col-span-full py-12 text-center text-slate-400">
              <p class="text-lg font-medium">
                {{ t('mod.design_system.empty_state', { query: searchQuery }) }}
              </p>
            </div>
          </div>
        </div>

        <!-- 7) Pagination & Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4 mt-auto">
          <div class="flex items-center gap-4 text-sm text-slate-600">
            <span>
              {{ t('mod.design_system.pagination.summary', { start: totalItems ? startItem : 0, end: endItem, total: totalItems }) }}
            </span>

            <select v-model.number="itemsPerPage" class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
              <option v-for="n in itemsPerPageOptions" :key="n" :value="n">
                {{ t('mod.design_system.pagination.per_page', { count: n }) }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-1">
            <button
              type="button"
              @click="goToPreviousPage"
              :disabled="currentPage === 1"
              class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              :title="t('mod.design_system.pagination.previous')"
            >
              <ChevronLeft :size="16" />
            </button>

            <button
              v-for="p in pageNumbers"
              :key="`page-mobile-${p}`"
              type="button"
              @click="typeof p === 'number' && goToPage(p)"
              :disabled="typeof p !== 'number'"
              class="min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors"
              :class="
                p === currentPage
                  ? 'bg-brand-600 text-white shadow-sm'
                  : typeof p === 'number'
                    ? 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'
                    : 'bg-transparent text-slate-400 cursor-default'
              "
            >
              {{ p }}
            </button>

            <button
              type="button"
              @click="goToNextPage"
              :disabled="currentPage === totalPages || totalPages === 0"
              class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              :title="t('mod.design_system.pagination.next')"
            >
              <ChevronRight :size="16" />
            </button>
          </div>
        </div>
      </main>

      <!-- Mobile FAB -->
      <button
        type="button"
        @click="triggerPrimaryAction"
        class="fixed bottom-6 right-6 w-14 h-14 bg-brand-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 hover:bg-brand-700 active:scale-95 transition-all"
        :title="t('mod.design_system.actions.primary')"
      >
        <Plus :size="28" />
      </button>
    </div>

    <!-- 2) + 3) Desktop Layout -->
    <div class="hidden lg:flex min-h-full p-6 gap-6 items-start">
      <!-- Sidebar -->
      <aside class="w-72 flex-shrink-0 flex flex-col gap-6 sticky top-6 self-start z-40">
        <div class="relative overflow-hidden shrink-0 transition-all bg-gradient-to-br from-blue-900 to-slate-800 text-white p-6 shadow-lg rounded-xl flex flex-col justify-center">
          <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
              <Layers :size="24" class="text-white/80" />
              <h2 class="font-bold text-xl">{{ t('mod.design_system.hero.title') }}</h2>
            </div>
            <p class="text-xs max-w-2xl text-white/70">{{ t('mod.design_system.hero.description') }}</p>
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
            @click="setTab(tab.id)"
            class="w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 transition-all"
            :class="activeTab === tab.id ? 'bg-slate-100 text-slate-900 font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
          >
            <component :is="tab.icon" :size="18" />
            <span class="truncate">{{ tab.label }}</span>
          </button>
        </nav>
      </aside>

      <!-- Content Column -->
      <section class="flex-1 flex flex-col min-w-0">
        <!-- Sticky Header & Action Bar -->
        <div class="sticky top-0 z-30 bg-slate-50 pt-6 -mt-6">
          <div class="bg-white p-6 rounded-t-xl rounded-b-none border border-slate-200 border-b-0 shadow-sm flex flex-wrap justify-between items-center gap-y-0 relative z-20">
            <div class="min-w-0">
              <div class="font-bold text-slate-800 text-lg truncate">{{ activeTabLabel }}</div>
              <div class="text-xs text-slate-500 truncate">{{ t('mod.design_system.subtitle', { tab: activeTabLabel }) }}</div>
            </div>

            <div class="order-3 w-full xl:order-2 xl:w-auto xl:flex-1 xl:flex xl:justify-end gap-3 flex items-center mt-4 xl:mt-0">
              <div class="relative flex-1 min-w-0 xl:w-auto xl:min-w-[240px] xl:flex-none">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                <input
                  v-model="searchQuery"
                  type="text"
                  :placeholder="t('mod.design_system.search.placeholder')"
                  class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all"
                />
              </div>

              <button
                type="button"
                @click="toggleFilter"
                class="border rounded-xl p-2.5 bg-white border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors flex items-center justify-center shrink-0"
                :class="showFilter ? 'bg-brand-50 border-brand-200 text-brand-700' : ''"
              >
                <Filter :size="18" />
              </button>
            </div>

            <div class="order-2 xl:order-3">
              <button
                type="button"
                @click="triggerPrimaryAction"
                class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors flex items-center gap-2 whitespace-nowrap shrink-0"
              >
                <Plus :size="18" />
                <span>{{ t('mod.design_system.actions.primary') }}</span>
              </button>
            </div>
          </div>

          <!-- Filter Dropdown -->
          <div v-if="showFilter" class="bg-white border-x border-slate-200 border-b-0 p-6 relative z-10 animate-slideDown">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <select v-model="filterCategory" class="w-full border border-slate-200 rounded-lg px-2 py-2 text-sm bg-white">
                <option value="all">{{ t('mod.design_system.filters.all_categories') }}</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
              </select>

              <select v-model="filterType" class="w-full border border-slate-200 rounded-lg px-2 py-2 text-sm bg-white">
                <option value="all">{{ t('mod.design_system.filters.all_types') }}</option>
                <option v-for="ty in types" :key="ty" :value="ty">{{ ty }}</option>
              </select>

              <select v-model="filterStatus" class="w-full border border-slate-200 rounded-lg px-2 py-2 text-sm bg-white">
                <option value="all">{{ t('mod.design_system.filters.all_status') }}</option>
                <option value="active">{{ t('mod.design_system.filters.active_only') }}</option>
                <option value="inactive">{{ t('mod.design_system.filters.inactive_only') }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Content Area -->
        <main class="bg-white border border-slate-200 border-t-0 shadow-sm rounded-b-xl rounded-t-none overflow-hidden flex-1 relative z-0">
          <div class="p-2 md:p-6 flex-1">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-2 md:gap-6">
              <article
                v-for="item in paginatedItems"
                :key="item.id"
                class="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden"
              >
                <div class="h-40 bg-slate-100 relative overflow-hidden">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>

                  <div class="absolute bottom-3 left-3 flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold shadow-sm border backdrop-blur-md bg-white/95 text-blue-700">
                    <Clock :size="14" />
                    {{ t('mod.design_system.cards.service_label') }}
                  </div>
                </div>

                <div class="p-5 flex-1 flex flex-col">
                  <div class="flex justify-between items-start mb-2">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200 px-1.5 rounded bg-slate-50">
                      {{ item.type }}
                    </span>
                    <div class="font-bold text-lg text-slate-900">
                      {{ formatPrice(item.price) }}
                    </div>
                  </div>

                  <h3 class="font-bold text-slate-800 text-lg mb-2 leading-tight">
                    {{ item.title }}
                  </h3>

                  <p class="text-slate-500 text-sm mb-4 line-clamp-2 flex-1">
                    {{ item.description }}
                  </p>

                  <footer class="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center">
                    <div class="flex gap-2">
                      <button
                        type="button"
                        class="p-1.5 text-slate-400 hover:text-brand-600 bg-slate-50 rounded-md"
                        :title="t('mod.design_system.cards.edit')"
                        @click="onEdit(item)"
                      >
                        <Edit2 :size="16" />
                      </button>

                      <button
                        type="button"
                        class="p-1.5 text-slate-400 hover:text-rose-600 bg-slate-50 rounded-md"
                        :title="t('mod.design_system.cards.delete')"
                        @click="onDelete(item)"
                      >
                        <Trash2 :size="16" />
                      </button>
                    </div>
                  </footer>
                </div>
              </article>

              <div v-if="paginatedItems.length === 0" class="col-span-full py-12 text-center text-slate-400">
                <p class="text-lg font-medium">
                  {{ t('mod.design_system.empty_state', { query: searchQuery }) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div class="p-4 border-t border-slate-200 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4 mt-auto">
            <div class="flex items-center gap-4 text-sm text-slate-600">
              <span>
                {{ t('mod.design_system.pagination.summary', { start: totalItems ? startItem : 0, end: endItem, total: totalItems }) }}
              </span>

              <select v-model.number="itemsPerPage" class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                <option v-for="n in itemsPerPageOptions" :key="n" :value="n">
                  {{ t('mod.design_system.pagination.per_page', { count: n }) }}
                </option>
              </select>
            </div>

            <div class="flex items-center gap-1">
              <button
                type="button"
                @click="goToPreviousPage"
                :disabled="currentPage === 1"
                class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                :title="t('mod.design_system.pagination.previous')"
              >
                <ChevronLeft :size="16" />
              </button>

              <button
                v-for="p in pageNumbers"
                :key="`page-desktop-${p}`"
                type="button"
                @click="typeof p === 'number' && goToPage(p)"
                :disabled="typeof p !== 'number'"
                class="min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors"
                :class="
                  p === currentPage
                    ? 'bg-brand-600 text-white shadow-sm'
                    : typeof p === 'number'
                      ? 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'
                      : 'bg-transparent text-slate-400 cursor-default'
                "
              >
                {{ p }}
              </button>

              <button
                type="button"
                @click="goToNextPage"
                :disabled="currentPage === totalPages || totalPages === 0"
                class="p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                :title="t('mod.design_system.pagination.next')"
              >
                <ChevronRight :size="16" />
              </button>
            </div>
          </div>
        </main>
      </section>
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

type TabId = 'catalog' | 'components' | 'patterns' | 'labels' | 'layout' | 'tokens'

type Item = {
  id: number
  tab: TabId
  title: string
  description: string
  type: string
  category: string
  status: 'active' | 'inactive'
  price: number
}

const { t } = useI18n()

const tabs = computed(() => [
  { id: 'catalog' as TabId, label: t('mod.design_system.tabs.catalog'), icon: Package },
  { id: 'components' as TabId, label: t('mod.design_system.tabs.components'), icon: List },
  { id: 'patterns' as TabId, label: t('mod.design_system.tabs.patterns'), icon: LayoutTemplate },
  { id: 'labels' as TabId, label: t('mod.design_system.tabs.labels'), icon: Tag },
  { id: 'layout' as TabId, label: t('mod.design_system.tabs.layout'), icon: Ticket },
  { id: 'tokens' as TabId, label: t('mod.design_system.tabs.tokens'), icon: Layers },
])

const activeTab = ref<TabId>('catalog')
const searchQuery = ref('')
const showFilter = ref(false)

const filterCategory = ref<string>('all')
const filterType = ref<string>('all')
const filterStatus = ref<'all' | 'active' | 'inactive'>('all')

const itemsPerPageOptions = [6, 12, 24, 48]
const itemsPerPage = ref<number>(6)
const currentPage = ref<number>(1)

const items = ref<Item[]>([
  { id: 1, tab: 'catalog', title: 'Service Card', description: 'Beispielkarte mit Pricing & Actions.', type: 'Service', category: 'UI', status: 'active', price: 120 },
  { id: 2, tab: 'catalog', title: 'Produkt Karte', description: 'Produktdarstellung mit Badge im Bild.', type: 'Produkt', category: 'Commerce', status: 'active', price: 89 },
  { id: 3, tab: 'components', title: 'Input', description: 'Suche, Filter und Formularfelder.', type: 'Component', category: 'Forms', status: 'active', price: 0 },
  { id: 4, tab: 'components', title: 'Tabs', description: 'Mobile Pills und Desktop Sidebar Tabs.', type: 'Component', category: 'Navigation', status: 'active', price: 0 },
  { id: 5, tab: 'patterns', title: 'Header Bar', description: 'Sticky Header mit Actions, Suche und Filter.', type: 'Pattern', category: 'Layout', status: 'active', price: 0 },
  { id: 6, tab: 'labels', title: 'Badges', description: 'Floating Badge im Image-Bereich.', type: 'Token', category: 'Typography', status: 'inactive', price: 0 },
  { id: 7, tab: 'layout', title: 'Grid System', description: 'Responsive Grid (1/2/3 Spalten).', type: 'Pattern', category: 'Layout', status: 'active', price: 0 },
  { id: 8, tab: 'tokens', title: 'Brand Colors', description: 'bg-brand-600 usw. als konsistente Tokens.', type: 'Token', category: 'Color', status: 'active', price: 0 },
])

const categories = computed(() => {
  const set = new Set(items.value.map(i => i.category))
  return Array.from(set).sort()
})

const types = computed(() => {
  const set = new Set(items.value.map(i => i.type))
  return Array.from(set).sort()
})

const activeTabLabel = computed(() => tabs.value.find(ti => ti.id === activeTab.value)?.label ?? t('mod.design_system.hero.title'))

const filteredItems = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()

  return items.value
    .filter(i => i.tab === activeTab.value)
    .filter(i => (filterCategory.value === 'all' ? true : i.category === filterCategory.value))
    .filter(i => (filterType.value === 'all' ? true : i.type === filterType.value))
    .filter(i => (filterStatus.value === 'all' ? true : i.status === filterStatus.value))
    .filter(i => {
      if (!q) return true
      return (
        i.title.toLowerCase().includes(q) ||
        i.description.toLowerCase().includes(q) ||
        i.type.toLowerCase().includes(q) ||
        i.category.toLowerCase().includes(q)
      )
    })
})

const totalItems = computed(() => filteredItems.value.length)
const totalPages = computed(() => (totalItems.value === 0 ? 0 : Math.ceil(totalItems.value / itemsPerPage.value)))

const startItem = computed(() => {
  if (!totalItems.value) return 0
  return (currentPage.value - 1) * itemsPerPage.value + 1
})

const endItem = computed(() => {
  if (!totalItems.value) return 0
  return Math.min(currentPage.value * itemsPerPage.value, totalItems.value)
})

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return filteredItems.value.slice(start, start + itemsPerPage.value)
})

function toggleFilter() {
  showFilter.value = !showFilter.value
}

function setTab(tabId: TabId) {
  activeTab.value = tabId
  currentPage.value = 1
}

function goToPage(page: number) {
  currentPage.value = page
}

function goToPreviousPage() {
  if (currentPage.value > 1) currentPage.value -= 1
}

function goToNextPage() {
  if (currentPage.value < totalPages.value) currentPage.value += 1
}

function pageRange(total: number, current: number) {
  // kompakt: 1 … 4 5 6 … 12
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const out: Array<number | string> = []
  const push = (v: number | string) => out.push(v)

  push(1)
  if (current > 4) push('…')

  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)

  for (let p = start; p <= end; p++) push(p)

  if (current < total - 3) push('…')
  push(total)

  return out
}

const pageNumbers = computed(() => pageRange(totalPages.value, currentPage.value))

function formatPrice(value: number) {
  if (!value) return '—'
  try {
    return new Intl.NumberFormat('de-CH', { style: 'currency', currency: 'CHF' }).format(value)
  } catch {
    return `${value} CHF`
  }
}

function triggerPrimaryAction() {
  // Placeholder: hier kannst du Dialog/Route/Emitter anbinden
  console.log('Primary action triggered')
}

function onEdit(item: Item) {
  console.log('Edit', item)
}

function onDelete(item: Item) {
  console.log('Delete', item)
}

// Reset pagination wenn Filter/Suche oder PageSize sich aendert
watch([searchQuery, filterCategory, filterType, filterStatus, itemsPerPage, activeTab], () => {
  currentPage.value = 1
})
</script>

<style scoped>
/* no-scrollbar helper */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* animate-slideDown helper */
@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-slideDown {
  animation: slideDown 160ms ease-out;
}
</style>
