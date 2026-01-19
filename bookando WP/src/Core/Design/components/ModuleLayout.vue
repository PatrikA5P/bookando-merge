<template>
  <!-- ModuleLayout - Unified Module Pattern for Bookando WP -->
  <div class="flex flex-col min-h-full bg-slate-50/50">

    <!-- ==== MOBILE & TABLET LAYOUT (Up to LG breakpoint) ==== -->
    <div class="lg:hidden flex flex-col min-h-screen">
      <!-- Sticky Header Container -->
      <div
        :class="[
          'sticky top-0 left-0 right-0 z-20 transition-transform duration-300 ease-in-out shadow-lg text-white',
          getMobileGradient(),
          isHeaderVisible ? 'translate-y-0' : '-translate-y-full'
        ]"
      >
        <!-- Part 1: Title & Tabs -->
        <div class="px-4 pt-4 pb-2">
          <div class="flex items-center gap-2 mb-3">
            <component :is="heroIcon" :size="20" class="text-white/80" />
            <h2 class="font-bold text-lg">{{ heroTitle }}</h2>
          </div>

          <!-- Horizontal Scrollable Tabs (if tabs exist) -->
          <div v-if="tabs && tabs.length > 0" class="flex overflow-x-auto no-scrollbar gap-2 pb-1">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="handleTabChange(tab.id)"
              :class="[
                'flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap',
                activeTab === tab.id ? 'bg-white text-brand-700 shadow-sm' : 'bg-white/10 text-white hover:bg-white/20'
              ]"
            >
              <component :is="tab.icon" v-if="activeTab === tab.id" :size="16" />
              <span v-if="activeTab === tab.id">{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <!-- Part 2: Integrated Search & Filter -->
        <div class="px-4 pb-3 flex gap-2">
          <div v-if="showSearch" class="relative flex-1">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70" :size="16" />
            <input
              v-model="internalSearchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              class="w-full pl-9 pr-4 py-2 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
              @input="$emit('update:searchQuery', internalSearchQuery)"
            >
          </div>
          <button
            v-if="showFilterButton"
            @click="toggleFilter"
            :class="[
              'p-2 rounded-lg border border-white/20 transition-colors',
              isFilterOpen ? 'bg-white text-brand-700' : 'bg-white/10 text-white hover:bg-white/20'
            ]"
          >
            <Filter :size="20" />
          </button>
        </div>

        <!-- Filter Content Panel (inside sticky header, drops down from blue header) -->
        <div v-if="isFilterOpen && $slots.filterContent" class="bg-white border-b border-slate-200 p-4 shadow-inner max-h-[50vh] overflow-y-auto text-slate-800">
          <slot name="filterContent"></slot>
        </div>
      </div>

      <!-- Content Wrapper -->
      <div class="flex-1">
        <slot></slot>
      </div>

      <!-- Mobile Floating Action Button -->
      <button
        v-if="showPrimaryAction"
        @click="$emit('primaryAction')"
        class="fixed bottom-6 right-6 w-14 h-14 bg-brand-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 hover:bg-brand-700 active:scale-95 transition-all"
      >
        <component :is="primaryActionIcon" :size="28" />
      </button>
    </div>

    <!-- ==== DESKTOP LAYOUT (Large Screens) ==== -->
    <div class="hidden lg:flex min-h-full p-6 gap-6 items-start">

      <!-- Scenario 1: WITH TABS (Sidebar) -->
      <aside v-if="tabs && tabs.length > 0" class="w-72 flex-shrink-0 flex flex-col gap-6 sticky top-6 self-start z-40">
        <!-- Hero Section -->
        <div :class="['relative overflow-hidden shrink-0 transition-all text-white p-6 shadow-lg rounded-xl flex flex-col justify-center', heroGradient]">
          <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
              <component :is="heroIcon" :size="24" class="text-white/80" />
              <h2 class="font-bold text-xl">{{ heroTitle }}</h2>
            </div>
            <p class="text-xs max-w-2xl text-white/70">{{ heroDescription }}</p>
          </div>
          <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
            <component :is="heroIcon" :size="100" />
          </div>
        </div>

        <!-- Navigation Tabs (Sidebar Style) -->
        <nav class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 space-y-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="handleTabChange(tab.id)"
            :class="[
              'w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 transition-all',
              activeTab === tab.id ? 'bg-slate-100 text-slate-900 font-bold shadow-sm' : 'text-slate-600 hover:bg-slate-50'
            ]"
          >
            <component :is="tab.icon" :size="18" />
            {{ tab.label }}
            <span v-if="tab.badge" :class="['ml-auto text-[10px] px-1.5 py-0.5 rounded-full', activeTab === tab.id ? 'bg-white text-slate-900' : 'bg-slate-200 text-slate-600']">
              {{ tab.badge }}
            </span>
          </button>
        </nav>
      </aside>

      <!-- Main Content Area -->
      <div class="flex-1 flex flex-col min-w-0">

        <!-- Scenario 2: NO TABS (Full Width with Hero Left, Actions Right) -->
        <div v-if="!tabs || tabs.length === 0">
          <!-- Sticky Header Wrapper -->
          <div class="sticky top-0 z-30 bg-slate-50 pt-6 pb-6 -mt-6">
            <div class="flex gap-6 items-stretch">

              <!-- Left: Module Identity (Hero Section) -->
              <div class="w-72 shrink-0 z-20 relative">
                <div :class="['relative overflow-hidden shrink-0 transition-all text-white p-6 rounded-xl shadow-lg h-full flex flex-col justify-center', heroGradient]">
                  <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-2">
                      <component :is="heroIcon" :size="24" class="text-white/80" />
                      <h2 class="font-bold text-xl">{{ heroTitle }}</h2>
                    </div>
                    <p class="text-xs max-w-2xl text-white/70">{{ heroDescription }}</p>
                  </div>
                  <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
                    <component :is="heroIcon" :size="100" />
                  </div>
                </div>
              </div>

              <!-- Right: Actions Toolbar -->
              <div class="flex-1 bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-center relative z-20">
                <div class="flex items-center gap-3 w-full justify-between">
                  <div class="flex-1 flex gap-3">
                    <!-- Search -->
                    <div v-if="showSearch" class="relative flex-1">
                      <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                      <input
                        v-model="internalSearchQuery"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all border border-slate-200 rounded-xl bg-slate-50 focus:bg-white"
                        @input="$emit('update:searchQuery', internalSearchQuery)"
                      >
                    </div>

                    <!-- Filter Toggle -->
                    <button
                      v-if="showFilterButton"
                      @click="toggleFilter"
                      :class="[
                        'transition-colors flex items-center justify-center shrink-0 border rounded-xl p-2.5',
                        isFilterOpen ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                      ]"
                    >
                      <Filter :size="18" />
                    </button>

                    <!-- Extra Actions Slot -->
                    <slot name="actions"></slot>
                  </div>

                  <!-- Primary Action Button -->
                  <div v-if="showPrimaryAction" class="pl-3 border-l border-slate-100">
                    <button
                      @click="$emit('primaryAction')"
                      class="flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0 bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
                    >
                      <component :is="primaryActionIcon" :size="18" />
                      <span>{{ primaryActionLabel }}</span>
                    </button>
                  </div>
                </div>

                <!-- Filter Expansion inside the action box -->
                <div v-if="isFilterOpen && $slots.filterContent" class="mt-4 pt-4 border-t border-slate-100 animate-slideDown">
                  <slot name="filterContent"></slot>
                </div>
              </div>
            </div>
          </div>

          <!-- Content Body -->
          <main class="bg-white border border-slate-200 shadow-sm flex-1 flex flex-col z-0 relative rounded-xl overflow-hidden">
            <slot></slot>
          </main>
        </div>

        <!-- Scenario 1: WITH TABS (Standard Header + Content) -->
        <div v-if="tabs && tabs.length > 0" class="flex-1 flex flex-col min-w-0">
          <!-- Sticky Header -->
          <div class="sticky top-0 z-30 bg-slate-50 pt-6 -mt-6">
            <div class="bg-white p-6 rounded-t-xl rounded-b-none border border-slate-200 border-b-0 shadow-sm flex flex-wrap justify-between items-center gap-y-0 relative z-20">
              <!-- Title Section -->
              <div class="flex flex-col min-w-[140px] order-1">
                <h3 class="font-bold text-slate-800 text-lg truncate">
                  {{ getActiveTabLabel() }}
                </h3>
                <p class="text-xs text-slate-500 truncate">Manage your {{ getActiveTabLabel().toLowerCase() }}</p>
              </div>

              <!-- Actions Section (Search/Filter) -->
              <div class="order-3 w-full xl:order-2 xl:w-auto xl:flex-1 xl:flex xl:justify-end gap-3 flex items-center mt-4 xl:mt-0">
                <!-- Search -->
                <div v-if="showSearch" class="relative flex-1 min-w-0 xl:w-auto xl:min-w-[240px] xl:flex-none">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
                  <input
                    v-model="internalSearchQuery"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="w-full pl-9 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all border border-slate-200 rounded-xl bg-slate-50 focus:bg-white"
                    @input="$emit('update:searchQuery', internalSearchQuery)"
                  >
                </div>

                <!-- Filter Toggle -->
                <button
                  v-if="showFilterButton"
                  @click="toggleFilter"
                  :class="[
                    'transition-colors flex items-center justify-center shrink-0 border rounded-xl p-2.5',
                    isFilterOpen ? 'bg-brand-50 border-brand-200 text-brand-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                  ]"
                >
                  <Filter :size="18" />
                </button>

                <!-- Extra Actions -->
                <slot name="actions"></slot>
              </div>

              <!-- Primary Action Button -->
              <div v-if="showPrimaryAction" class="order-2 xl:order-3 shrink-0">
                <button
                  @click="$emit('primaryAction')"
                  class="flex items-center gap-2 shadow-sm whitespace-nowrap transition-colors shrink-0 bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
                >
                  <component :is="primaryActionIcon" :size="18" />
                  <span>{{ primaryActionLabel }}</span>
                </button>
              </div>
            </div>

            <!-- Filter Panel (Expands the container) -->
            <div v-if="isFilterOpen && $slots.filterContent" class="bg-white border-x border-slate-200 border-b-0 p-6 relative z-10 animate-slideDown">
              <slot name="filterContent"></slot>
            </div>
          </div>

          <!-- Content Card -->
          <main class="bg-white border border-slate-200 border-t-0 shadow-sm rounded-b-xl rounded-t-none overflow-hidden flex-1 relative z-0">
            <slot></slot>
          </main>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { Search, Filter } from 'lucide-vue-next'

interface Tab {
  id: string
  label: string
  icon: any
  badge?: number
}

interface Props {
  // Hero
  heroTitle: string
  heroDescription: string
  heroIcon: any
  heroGradient?: string

  // Tabs (optional)
  tabs?: Tab[]
  activeTab?: string

  // Search
  showSearch?: boolean
  searchQuery?: string
  searchPlaceholder?: string

  // Filter
  showFilterButton?: boolean
  filterOpen?: boolean

  // Primary Action
  showPrimaryAction?: boolean
  primaryActionLabel?: string
  primaryActionIcon?: any
}

const props = withDefaults(defineProps<Props>(), {
  heroGradient: 'bg-gradient-to-br from-slate-800 to-slate-900',
  showSearch: true,
  searchPlaceholder: 'Search...',
  showFilterButton: false,
  filterOpen: false,
  showPrimaryAction: true,
  primaryActionLabel: 'Add',
  tabs: () => []
})

const emit = defineEmits(['update:searchQuery', 'update:activeTab', 'update:filterOpen', 'primaryAction'])

// Internal state
const internalSearchQuery = ref(props.searchQuery || '')
const isFilterOpen = ref(props.filterOpen)

// Scroll direction for mobile sticky header
const scrollDirection = ref<'up' | 'down' | null>(null)
const scrolledToTop = ref(true)
const isHeaderVisible = computed(() => scrollDirection.value === 'up' || scrolledToTop.value)

// Watch for prop changes
watch(() => props.searchQuery, (newVal) => {
  internalSearchQuery.value = newVal || ''
})

watch(() => props.filterOpen, (newVal) => {
  isFilterOpen.value = newVal
})

// Methods
const handleTabChange = (tabId: string) => {
  emit('update:activeTab', tabId)
}

const toggleFilter = () => {
  isFilterOpen.value = !isFilterOpen.value
  emit('update:filterOpen', isFilterOpen.value)
}

const getActiveTabLabel = () => {
  if (!props.tabs || props.tabs.length === 0) return props.heroTitle
  const activeTabObj = props.tabs.find(t => t.id === props.activeTab)
  return activeTabObj ? activeTabObj.label : props.heroTitle
}

const getMobileGradient = () => {
  // Convert bg-gradient-to-br to bg-gradient-to-r for mobile header
  return props.heroGradient.replace('bg-gradient-to-br', 'bg-gradient-to-r')
}

// Setup scroll listener for mobile
onMounted(() => {
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

.no-scrollbar::-webkit-scrollbar {
  display: none;
}

.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
