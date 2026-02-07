<script setup lang="ts">
/**
 * ModuleLayout — Universelle Modul-Hülle (Reference Pattern)
 *
 * 4-Quadrant Layout:
 * ┌──────────────────────┬─────────────────────────────────────┐
 * │ HERO (Gradient Card) │ SEARCH / FILTER / PRIMARY ACTION   │
 * ├──────────────────────┤ Filter-Panel (expandable)           │
 * │ VERTICAL TABS (opt.) ├─────────────────────────────────────┤
 * │                      │ CONTENT AREA                        │
 * └──────────────────────┴─────────────────────────────────────┘
 *
 * Desktop mit Tabs: Sidebar (Hero + Tabs) | Main (Header + Content)
 * Desktop ohne Tabs: Hero links + Toolbar rechts | Content darunter
 * Mobile: Farbiger sticky Header + horizontale Tab-Pills + FAB
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useDesignStore } from '@/stores/design';

export interface Tab {
  id: string;
  label: string;
  icon?: string;
  badge?: number;
}

const props = withDefaults(defineProps<{
  moduleName: string;
  title: string;
  subtitle?: string;
  tabs?: Tab[];
  activeTab?: string;
  showFab?: boolean;
  fabLabel?: string;
  searchPlaceholder?: string;
  showSearch?: boolean;
  showFilter?: boolean;
}>(), {
  showFab: false,
  fabLabel: 'Erstellen',
  searchPlaceholder: 'Suchen...',
  showSearch: true,
  showFilter: true,
});

const emit = defineEmits<{
  (e: 'tab-change', tabId: string): void;
  (e: 'fab-click'): void;
  (e: 'search', query: string): void;
  (e: 'toggle-filter'): void;
}>();

const designStore = useDesignStore();
const design = computed(() => designStore.getModuleDesign(props.moduleName));
const hasTabs = computed(() => props.tabs && props.tabs.length > 0);

const searchQuery = ref('');
const filterOpen = ref(false);

// Mobile: scroll-aware header
const isHeaderVisible = ref(true);
let lastScrollY = 0;

function onScroll() {
  const currentY = window.scrollY;
  isHeaderVisible.value = currentY < 50 || currentY < lastScrollY;
  lastScrollY = currentY;
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
});

function onSearch() {
  emit('search', searchQuery.value);
}

function toggleFilter() {
  filterOpen.value = !filterOpen.value;
  emit('toggle-filter');
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- ============================================================ -->
    <!-- DESKTOP LAYOUT (lg+) — WITH TABS: Sidebar + Main            -->
    <!-- ============================================================ -->
    <div v-if="hasTabs" class="hidden lg:flex min-h-full p-6 gap-6 items-start">
      <!-- LEFT SIDEBAR: Hero + Vertical Tabs (w-72, sticky) -->
      <aside class="w-72 flex-shrink-0 flex flex-col gap-6 sticky top-6">
        <!-- Hero Card -->
        <div :class="['bg-gradient-to-br text-white p-6 rounded-xl relative overflow-hidden', design.gradient]">
          <div class="relative z-10">
            <div class="flex items-center gap-3 mb-2">
              <slot name="hero-icon">
                <div class="w-6 h-6 text-white/80">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                  </svg>
                </div>
              </slot>
              <h2 class="font-bold text-xl">{{ title }}</h2>
            </div>
            <p v-if="subtitle" class="text-xs text-white/70 max-w-2xl">{{ subtitle }}</p>
          </div>
          <!-- Watermark Icon -->
          <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
            <slot name="hero-watermark">
              <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </slot>
          </div>
        </div>

        <!-- Vertical Tab Navigation -->
        <nav class="bg-white rounded-xl border border-slate-200 shadow-sm p-2 space-y-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            :class="[
              'w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium flex items-center gap-3 transition-all whitespace-nowrap',
              activeTab === tab.id
                ? 'bg-slate-100 text-slate-900 font-bold shadow-sm'
                : 'text-slate-600 hover:bg-slate-50',
            ]"
            @click="emit('tab-change', tab.id)"
          >
            <slot :name="`tab-icon-${tab.id}`">
              <div class="w-[18px] h-[18px] shrink-0" />
            </slot>
            <span class="truncate">{{ tab.label }}</span>
            <span
              v-if="tab.badge && tab.badge > 0"
              :class="[
                'ml-auto text-[10px] px-1.5 py-0.5 rounded-full',
                activeTab === tab.id
                  ? 'bg-white text-slate-900'
                  : 'bg-slate-200 text-slate-600',
              ]"
            >
              {{ tab.badge }}
            </span>
          </button>
        </nav>
      </aside>

      <!-- RIGHT MAIN: Sticky Header + Content -->
      <div class="flex-1 flex flex-col min-w-0">
        <!-- Sticky Action Header -->
        <div class="sticky top-0 z-30 bg-slate-50 pt-6 -mt-6">
          <div class="bg-white p-6 rounded-t-xl border border-slate-200 border-b-0 shadow-sm flex flex-wrap justify-between items-center gap-y-0">
            <!-- Title (active tab) -->
            <div class="order-1 flex flex-col min-w-[140px]">
              <h3 class="font-bold text-slate-800 text-lg">
                {{ tabs?.find(t => t.id === activeTab)?.label || title }}
              </h3>
              <slot name="header-subtitle" />
            </div>

            <!-- Search / Filter (Order 3 on sm, Order 2 on xl) -->
            <div v-if="showSearch" class="order-3 w-full xl:order-2 xl:w-auto xl:flex-1 xl:flex xl:justify-end gap-3 flex items-center mt-4 xl:mt-0">
              <div class="relative flex-1 min-w-0 xl:w-auto xl:min-w-[240px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                  v-model="searchQuery"
                  type="text"
                  :placeholder="searchPlaceholder"
                  class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                  @input="onSearch"
                />
              </div>
              <button
                v-if="showFilter"
                :class="[
                  'p-2.5 border rounded-xl transition-colors shrink-0',
                  filterOpen
                    ? 'bg-brand-50 border-brand-200 text-brand-700'
                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                ]"
                @click="toggleFilter"
              >
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
              </button>
              <slot name="header-actions" />
            </div>

            <!-- Primary Action (Order 2 on sm, Order 3 on xl) -->
            <div class="order-2 xl:order-3 shrink-0" v-if="$slots['primary-action']">
              <div class="pl-3 xl:border-l xl:border-slate-100">
                <slot name="primary-action" />
              </div>
            </div>
          </div>

          <!-- Filter Panel (expandable) -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
          >
            <div v-if="filterOpen" class="bg-white border-x border-slate-200 p-6">
              <slot name="filter-content" />
            </div>
          </Transition>
        </div>

        <!-- Content Card -->
        <main class="bg-white border border-slate-200 border-t-0 shadow-sm rounded-b-xl overflow-hidden flex-1 relative">
          <slot />
        </main>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- DESKTOP LAYOUT (lg+) — WITHOUT TABS: Hero + Toolbar row      -->
    <!-- ============================================================ -->
    <div v-if="!hasTabs" class="hidden lg:flex flex-col min-h-full p-6 gap-6">
      <!-- Top Row: Hero + Action Toolbar -->
      <div class="sticky top-0 z-30 bg-slate-50 pt-6 -mt-6">
        <div class="flex gap-6 items-stretch">
          <!-- Hero Card (w-72) -->
          <div class="w-72 shrink-0 z-20">
            <div :class="['bg-gradient-to-br text-white p-6 rounded-xl relative overflow-hidden h-full', design.gradient]">
              <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                  <slot name="hero-icon">
                    <div class="w-6 h-6 text-white/80">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                      </svg>
                    </div>
                  </slot>
                  <h2 class="font-bold text-xl">{{ title }}</h2>
                </div>
                <p v-if="subtitle" class="text-xs text-white/70">{{ subtitle }}</p>
              </div>
              <div class="absolute right-0 top-0 opacity-10 transform translate-x-2 -translate-y-2 pointer-events-none">
                <slot name="hero-watermark" />
              </div>
            </div>
          </div>

          <!-- Action Toolbar -->
          <div class="flex-1 bg-white p-6 rounded-xl shadow-sm border border-slate-200 flex flex-col justify-center">
            <div class="flex items-center gap-3 w-full justify-between">
              <!-- Search -->
              <div v-if="showSearch" class="relative flex-1 min-w-0">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                  v-model="searchQuery"
                  type="text"
                  :placeholder="searchPlaceholder"
                  class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
                  @input="onSearch"
                />
              </div>

              <!-- Filter Toggle -->
              <button
                v-if="showFilter"
                :class="[
                  'p-2.5 border rounded-xl transition-colors shrink-0',
                  filterOpen
                    ? 'bg-brand-50 border-brand-200 text-brand-700'
                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                ]"
                @click="toggleFilter"
              >
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
              </button>

              <slot name="header-actions" />

              <!-- Primary Action -->
              <div v-if="$slots['primary-action']" class="pl-3 border-l border-slate-100">
                <slot name="primary-action" />
              </div>
            </div>

            <!-- Filter Panel -->
            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <div v-if="filterOpen" class="mt-4 pt-4 border-t border-slate-100">
                <slot name="filter-content" />
              </div>
            </Transition>
          </div>
        </div>
      </div>

      <!-- Content Card -->
      <main class="bg-white border border-slate-200 shadow-sm flex-1 flex flex-col rounded-xl overflow-hidden">
        <slot />
      </main>
    </div>

    <!-- ============================================================ -->
    <!-- MOBILE/TABLET LAYOUT (<lg)                                   -->
    <!-- ============================================================ -->
    <div class="lg:hidden flex flex-col min-h-screen">
      <!-- Sticky Colored Header (scroll-away) -->
      <div
        :class="[
          'sticky top-0 left-0 right-0 z-20 transition-transform duration-300',
          'bg-gradient-to-r text-white',
          design.gradient,
          isHeaderVisible ? 'translate-y-0' : '-translate-y-full',
        ]"
      >
        <!-- Module Title -->
        <div class="px-4 pt-4 pb-2">
          <div class="flex items-center gap-2 mb-3">
            <slot name="hero-icon">
              <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
              </svg>
            </slot>
            <h2 class="font-bold text-lg">{{ title }}</h2>
          </div>

          <!-- Horizontal Tab Pills (mobile) -->
          <div v-if="hasTabs" class="flex overflow-x-auto scrollbar-hide gap-2 pb-1">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              :class="[
                'flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-all shrink-0',
                activeTab === tab.id
                  ? 'bg-white text-brand-700 shadow-sm'
                  : 'bg-white/10 text-white hover:bg-white/20',
              ]"
              @click="emit('tab-change', tab.id)"
            >
              <slot :name="`tab-icon-${tab.id}`">
                <div class="w-4 h-4" />
              </slot>
              <span v-if="activeTab === tab.id">{{ tab.label }}</span>
            </button>
          </div>
        </div>

        <!-- Search + Filter (inside colored header) -->
        <div v-if="showSearch" class="px-4 pb-3 flex gap-2">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              class="w-full pl-9 pr-4 py-2 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
              @input="onSearch"
            />
          </div>
          <button
            v-if="showFilter"
            :class="[
              'p-2 rounded-lg border transition-colors shrink-0',
              filterOpen
                ? 'border-white bg-white/20 text-white'
                : 'border-white/20 bg-white/10 text-white hover:bg-white/20',
            ]"
            @click="toggleFilter"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
          </button>
        </div>

        <!-- Filter Content Panel (drops down from colored header) -->
        <Transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 max-h-0"
          enter-to-class="opacity-100 max-h-[50vh]"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 max-h-[50vh]"
          leave-to-class="opacity-0 max-h-0"
        >
          <div v-if="filterOpen" class="bg-white border-b border-slate-200 p-4 max-h-[50vh] overflow-y-auto text-slate-800">
            <slot name="filter-content" />
          </div>
        </Transition>
      </div>

      <!-- Content -->
      <div class="flex-1">
        <slot />
      </div>

      <!-- Floating Action Button (Mobile) -->
      <button
        v-if="showFab"
        :aria-label="fabLabel"
        class="fixed bottom-6 right-6 w-14 h-14 bg-brand-600 hover:bg-brand-700 text-white rounded-full shadow-2xl flex items-center justify-center transition-all duration-200 z-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 active:scale-95"
        @click="emit('fab-click')"
      >
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>
  </div>
</template>
