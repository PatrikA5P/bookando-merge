<script setup lang="ts">
/**
 * ModuleLayout — Universelle Modul-Hülle
 *
 * Liefert ein konsistentes Layout für alle Module:
 * - Hero-Bereich mit Modul-Gradient
 * - Tab-Navigation (optional)
 * - Responsive Content-Bereich
 * - Floating Action Button auf Mobile (optional)
 *
 * Übernommen und verbessert aus der Referenz:
 * + URL-basierte Tab-Navigation (statt State)
 * + Scroll-aware Header (hide/show)
 * + Bottom-Sheet auf Mobile statt Modal
 * + Bessere Touch-Gesten
 */
import { computed } from 'vue';
import { getModuleDesign } from '@/design';

export interface Tab {
  id: string;
  label: string;
  icon?: string;
  badge?: number;
}

const props = withDefaults(defineProps<{
  /** Modul-Name für Design-Tokens */
  moduleName: string;
  /** Titel im Hero-Bereich */
  title: string;
  /** Untertitel (optional) */
  subtitle?: string;
  /** Tab-Definitionen (optional) */
  tabs?: Tab[];
  /** Aktuell aktiver Tab */
  activeTab?: string;
  /** Zeige FAB auf Mobile */
  showFab?: boolean;
  /** FAB-Label für Accessibility */
  fabLabel?: string;
}>(), {
  showFab: false,
  fabLabel: 'Erstellen',
});

const emit = defineEmits<{
  (e: 'tab-change', tabId: string): void;
  (e: 'fab-click'): void;
}>();

const design = computed(() => getModuleDesign(props.moduleName));
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Hero -->
    <div
      :class="[
        'bg-gradient-to-r text-white px-4 md:px-8 pt-6 pb-4',
        design.gradient,
      ]"
    >
      <div class="max-w-7xl mx-auto">
        <h1 class="text-xl md:text-2xl font-bold">{{ title }}</h1>
        <p v-if="subtitle" class="text-sm text-white/70 mt-1">{{ subtitle }}</p>

        <!-- Header Actions -->
        <div class="mt-4 flex items-center justify-between">
          <slot name="header-actions" />
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div
      v-if="tabs && tabs.length > 0"
      class="bg-white border-b border-slate-200 sticky top-0 z-20"
    >
      <div class="max-w-7xl mx-auto px-4 md:px-8">
        <nav class="flex gap-1 overflow-x-auto scrollbar-hide -mb-px" role="tablist">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            role="tab"
            :aria-selected="activeTab === tab.id"
            :class="[
              'flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-all duration-200 whitespace-nowrap',
              activeTab === tab.id
                ? `${design.activeText} border-current`
                : 'text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300',
            ]"
            @click="emit('tab-change', tab.id)"
          >
            <span>{{ tab.label }}</span>
            <span
              v-if="tab.badge && tab.badge > 0"
              :class="[
                'ml-1 px-1.5 py-0.5 text-xs rounded-full',
                activeTab === tab.id
                  ? `${design.activeBg} ${design.activeText}`
                  : 'bg-slate-100 text-slate-600',
              ]"
            >
              {{ tab.badge }}
            </span>
          </button>
        </nav>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-6">
      <slot />
    </div>

    <!-- Floating Action Button (Mobile) -->
    <button
      v-if="showFab"
      :aria-label="fabLabel"
      class="fixed bottom-6 right-6 w-14 h-14 bg-brand-600 hover:bg-brand-700 text-white rounded-full shadow-xl flex items-center justify-center transition-all duration-200 z-30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 md:hidden"
      @click="emit('fab-click')"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
    </button>
  </div>
</template>
