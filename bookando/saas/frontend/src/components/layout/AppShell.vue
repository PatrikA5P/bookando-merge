<script setup lang="ts">
/**
 * AppShell — Hauptlayout der Applikation
 *
 * Wird als Route-Wrapper verwendet:
 * - Sidebar mit Navigation (direkt integriert)
 * - Header mit Mobile-Toggle und User-Info
 * - <router-view /> für Kind-Routen
 *
 * Verbesserungen gegenüber Referenz:
 * + Vue Router Integration (statt State-Switch)
 * + Responsive Sidebar (Overlay auf Mobile)
 * + Selbstständige Layout-Komponente (keine Slots nötig)
 */
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from '@/composables/useI18n';
import { useAuthStore } from '@/stores/auth';
import { useAppStore } from '@/stores/app';
import Sidebar, { type NavItem } from './Sidebar.vue';

const route = useRoute();
const authStore = useAuthStore();
const appStore = useAppStore();
const { t } = useI18n();

const sidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);

// Navigation items
const navItems = computed<NavItem[]>(() => [
  { id: 'dashboard', label: t('dashboard.title'), icon: 'layout-dashboard', route: '/dashboard', module: 'dashboard' },
  { id: 'appointments', label: t('appointments.title'), icon: 'calendar', route: '/appointments', module: 'appointments' },
  { id: 'customers', label: t('customers.title'), icon: 'users', route: '/customers', module: 'customers' },
  { id: 'employees', label: t('employees.title'), icon: 'user-check', route: '/employees', module: 'employees' },
  { id: 'workday', label: t('workday.title'), icon: 'clock', route: '/workday', module: 'workday' },
  { id: 'finance', label: t('finance.title'), icon: 'wallet', route: '/finance', module: 'finance' },
  { id: 'offers', label: t('offers.title'), icon: 'tag', route: '/offers', module: 'offers' },
  { id: 'academy', label: t('academy.title'), icon: 'graduation-cap', route: '/academy', module: 'academy' },
  { id: 'resources', label: t('resources.title'), icon: 'building', route: '/resources', module: 'resources' },
  { id: 'tools', label: t('tools.title'), icon: 'wrench', route: '/tools', module: 'tools' },
  { id: 'partnerhub', label: t('partnerhub.title'), icon: 'handshake', route: '/partnerhub', module: 'partnerhub' },
  { id: 'design-system', label: t('designSystem.title'), icon: 'palette', route: '/design-system', module: 'design-system' },
  { id: 'design-frontend', label: t('designFrontend.title'), icon: 'paintbrush', route: '/design-frontend', module: 'design-frontend' },
  { id: 'settings', label: t('settings.title'), icon: 'settings', route: '/settings', module: 'settings' },
]);

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value;
}

// Close mobile menu on navigation
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
      <Sidebar :items="navItems" :collapsed="sidebarCollapsed">
        <template #user-menu>
          <div v-if="authStore.user" :class="sidebarCollapsed ? 'flex justify-center' : 'flex items-center gap-3 px-1'">
            <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-semibold text-xs shrink-0">
              {{ authStore.initials }}
            </div>
            <div v-if="!sidebarCollapsed" class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-900 truncate">{{ authStore.fullName }}</p>
              <p class="text-xs text-slate-500 truncate">{{ authStore.user.email }}</p>
            </div>
          </div>
        </template>
      </Sidebar>
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
          @click="toggleSidebar"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
          </svg>
        </button>

        <!-- Title from route meta -->
        <div class="flex-1 min-w-0">
          <h1 class="text-sm font-medium text-slate-700 truncate">
            {{ route.meta.title || 'Bookando' }}
          </h1>
        </div>

        <!-- User Menu (header) -->
        <div class="flex items-center gap-2">
          <button
            v-if="authStore.isAuthenticated"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition-colors"
            @click="authStore.logout().then(() => $router.push('/login'))"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="text-xs text-slate-600 hidden sm:inline">Logout</span>
          </button>
        </div>
      </header>

      <!-- Content: Child routes render here -->
      <main class="flex-1 overflow-y-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>
