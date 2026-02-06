<script setup lang="ts">
/**
 * AppShell — Hauptlayout der Applikation
 *
 * Enthält:
 * - Sidebar (Desktop: fixed, Mobile: Overlay)
 * - Header mit Breadcrumb, Suche, User-Menü
 * - Main-Content-Bereich
 *
 * Verbesserungen gegenüber Referenz:
 * + Vue Router Integration (statt State-Switch)
 * + Responsive Sidebar (Overlay auf Mobile)
 * + Breadcrumb-Navigation
 * + Globale Suche
 * + Keyboard-Shortcuts
 */
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { LAYOUT } from '@/design';

const props = withDefaults(defineProps<{
  /** Sidebar eingeklappt */
  sidebarCollapsed?: boolean;
}>(), {
  sidebarCollapsed: false,
});

const emit = defineEmits<{
  (e: 'toggle-sidebar'): void;
}>();

const route = useRoute();
const isMobileMenuOpen = ref(false);

// Schliesse Mobile-Menü bei Navigation
watch(() => route.path, () => {
  isMobileMenuOpen.value = false;
});
</script>

<template>
  <div class="flex h-screen bg-slate-50 overflow-hidden">
    <!-- Mobile Overlay -->
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 lg:hidden"
      @click="isMobileMenuOpen = false"
    />

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-40 bg-white border-r border-slate-200 flex flex-col transition-all duration-300',
        'lg:relative lg:translate-x-0',
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-[280px]',
        'w-[280px]',
      ]"
    >
      <slot name="sidebar" />
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Header -->
      <header class="h-14 lg:h-16 bg-white border-b border-slate-200 flex items-center px-4 lg:px-6 gap-4 shrink-0 z-20">
        <!-- Mobile Menu Toggle -->
        <button
          class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-slate-100 text-slate-600"
          aria-label="Menü öffnen"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Desktop Sidebar Toggle -->
        <button
          class="hidden lg:block p-2 -ml-2 rounded-lg hover:bg-slate-100 text-slate-600"
          aria-label="Sidebar umschalten"
          @click="emit('toggle-sidebar')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
          </svg>
        </button>

        <!-- Breadcrumb / Title -->
        <div class="flex-1 min-w-0">
          <slot name="header-content" />
        </div>

        <!-- Header Right -->
        <div class="flex items-center gap-2">
          <slot name="header-right" />
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>
