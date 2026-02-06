<script setup lang="ts">
/**
 * Sidebar — Navigationsleiste
 *
 * Features:
 * - Modul-Navigation mit Icons und Labels
 * - Eingeklappter Modus (nur Icons)
 * - Aktiver Modul-Indikator mit Modul-Farbe
 * - User-Profil am unteren Rand
 * - Responsive: Overlay auf Mobile
 */
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getModuleDesign } from '@/design';

export interface NavItem {
  id: string;
  label: string;
  icon: string;
  route: string;
  module: string;
  badge?: number;
}

const props = withDefaults(defineProps<{
  items: NavItem[];
  collapsed?: boolean;
}>(), {
  collapsed: false,
});

const route = useRoute();
const router = useRouter();

const activeModule = computed(() => {
  const path = route.path.split('/')[1] || 'dashboard';
  return path;
});

function navigate(item: NavItem) {
  router.push(item.route);
}
</script>

<template>
  <div class="flex flex-col h-full">
    <!-- Logo -->
    <div class="h-14 lg:h-16 flex items-center px-4 border-b border-slate-200 shrink-0">
      <div v-if="!collapsed" class="flex items-center gap-3">
        <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
          <span class="text-white font-bold text-sm">B</span>
        </div>
        <span class="text-lg font-bold text-slate-900">Bookando</span>
      </div>
      <div v-else class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center mx-auto">
        <span class="text-white font-bold text-sm">B</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
      <button
        v-for="item in items"
        :key="item.id"
        :class="[
          'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200',
          activeModule === item.module
            ? `${getModuleDesign(item.module).activeBg} ${getModuleDesign(item.module).activeText}`
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
          collapsed ? 'justify-center' : '',
        ]"
        :title="collapsed ? item.label : undefined"
        @click="navigate(item)"
      >
        <!-- Icon Placeholder (wird durch Lucide Icons ersetzt) -->
        <div
          :class="[
            'w-5 h-5 rounded shrink-0',
            activeModule === item.module
              ? getModuleDesign(item.module).iconText
              : 'text-slate-500',
          ]"
        >
          <slot :name="`icon-${item.id}`" />
        </div>

        <span v-if="!collapsed" class="truncate">{{ item.label }}</span>

        <span
          v-if="!collapsed && item.badge && item.badge > 0"
          class="ml-auto px-1.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-600"
        >
          {{ item.badge > 99 ? '99+' : item.badge }}
        </span>
      </button>
    </nav>

    <!-- User -->
    <div class="border-t border-slate-200 p-3 shrink-0">
      <slot name="user-menu" />
    </div>
  </div>
</template>
