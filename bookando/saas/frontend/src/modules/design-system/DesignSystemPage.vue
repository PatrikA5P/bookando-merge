<script setup lang="ts">
/**
 * Design System — Interaktive Live-Vorschau & Design-Konfiguration
 *
 * Zeigt eine vollständige, interaktive Vorschau des ModuleLayout-Patterns:
 * - Live-Preview: 4-Quadrant-Layout mit/ohne Tabs, clickbar
 * - Farben, Typografie, Komponenten, Module, Spacing als Einstellungs-Tabs
 * - Wahl zwischen Layouts (mit Tabs / ohne Tabs)
 * - Alle Einstellungen wirken sich auf die Live-Preview aus
 *
 * Orientiert an: Bookando reference/modules/DesignTemplates.tsx
 */
import { ref, computed, reactive } from 'vue';
import { useI18n } from '@/composables/useI18n';
import ModuleLayout, { type Tab } from '@/components/layout/ModuleLayout.vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BSlideOver from '@/components/ui/BSlideOver.vue';
import {
  BUTTON_STYLES, BUTTON_SIZES, INPUT_STYLES, LABEL_STYLES,
  CARD_STYLES, BADGE_STYLES, GRID_STYLES,
  MODULE_DESIGNS, COLORS, TYPOGRAPHY, SPACING, BORDER_RADIUS, SHADOWS,
  getModuleDesign, getModuleNames,
} from '@/design';

const { t } = useI18n();

// ============================================================
// DESIGN SYSTEM TABS (left sidebar in ModuleLayout)
// ============================================================
const activeTab = ref('live-preview');
const tabs: Tab[] = [
  { id: 'live-preview', label: 'Live Preview' },
  { id: 'colors', label: t('designSystem.tabs.colors') },
  { id: 'typography', label: t('designSystem.tabs.typography') },
  { id: 'components', label: t('designSystem.tabs.components') },
  { id: 'modules', label: t('designSystem.tabs.modules') },
  { id: 'spacing', label: t('designSystem.tabs.spacing') },
];

// ============================================================
// LIVE PREVIEW STATE — configures the preview module layout
// ============================================================
const preview = reactive({
  layoutMode: 'with-tabs' as 'with-tabs' | 'without-tabs',
  moduleName: 'dashboard',
  title: 'Beispiel-Modul',
  subtitle: 'Live-Vorschau des 4-Quadrant-Layouts',
  activePreviewTab: 'overview',
  showSearch: true,
  showFilter: true,
  contentType: 'cards' as 'cards' | 'table' | 'grid',
});

const previewTabs = computed<Tab[]>(() => {
  if (preview.layoutMode === 'without-tabs') return [];
  return [
    { id: 'overview', label: 'Übersicht', badge: 12 },
    { id: 'details', label: 'Details' },
    { id: 'history', label: 'Verlauf', badge: 3 },
    { id: 'settings', label: 'Einstellungen' },
  ];
});

const moduleNames = getModuleNames();

// Demo data for live preview
const demoCards = [
  { id: 1, title: 'Haarschnitt Damen', price: 'CHF 65.00', duration: '45 Min', status: 'active' },
  { id: 2, title: 'Farbe & Strähnen', price: 'CHF 120.00', duration: '90 Min', status: 'active' },
  { id: 3, title: 'Styling Hochsteck', price: 'CHF 85.00', duration: '60 Min', status: 'draft' },
  { id: 4, title: 'Bartpflege', price: 'CHF 35.00', duration: '30 Min', status: 'active' },
  { id: 5, title: 'Kopfmassage', price: 'CHF 45.00', duration: '20 Min', status: 'inactive' },
  { id: 6, title: 'Intensivkur', price: 'CHF 55.00', duration: '40 Min', status: 'active' },
];

const demoTableRows = [
  { name: 'Anna Müller', email: 'anna@example.ch', role: 'Admin', status: 'Aktiv' },
  { name: 'Peter Meier', email: 'peter@example.ch', role: 'Mitarbeiter', status: 'Aktiv' },
  { name: 'Lisa Huber', email: 'lisa@example.ch', role: 'Manager', status: 'Urlaub' },
  { name: 'Marco Bianchi', email: 'marco@example.ch', role: 'Praktikant', status: 'Inaktiv' },
];

// ============================================================
// COLOR SWATCHES
// ============================================================
const brandColors = Object.entries(COLORS.brand).map(([shade, hex]) => ({ shade, hex }));
const slateColors = Object.entries(COLORS.slate).map(([shade, hex]) => ({ shade, hex }));
const spacingEntries = Object.entries(SPACING).map(([key, value]) => ({ key, value }));
const radiusEntries = Object.entries(BORDER_RADIUS).map(([key, value]) => ({ key, value }));
const shadowEntries = Object.entries(SHADOWS).map(([key, value]) => ({ key, value }));

// SlideOver demo
const showSlideOver = ref(false);
</script>

<template>
  <ModuleLayout
    module-name="design-system"
    :title="t('designSystem.title')"
    :subtitle="t('designSystem.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-search="false"
    :show-filter="false"
    @tab-change="activeTab = $event"
  >
    <!-- Tab Icons -->
    <template #tab-icon-live-preview>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
    </template>
    <template #tab-icon-colors>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
      </svg>
    </template>
    <template #tab-icon-typography>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
      </svg>
    </template>
    <template #tab-icon-components>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
    </template>
    <template #tab-icon-modules>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
      </svg>
    </template>
    <template #tab-icon-spacing>
      <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
      </svg>
    </template>

    <!-- ============================================================ -->
    <!-- LIVE PREVIEW TAB                                             -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'live-preview'" class="flex flex-col">
      <!-- Preview Controls Bar -->
      <div class="p-4 border-b border-slate-200 bg-slate-50 flex flex-wrap gap-4 items-center">
        <!-- Layout Mode -->
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-500 uppercase">Layout:</span>
          <div class="flex rounded-lg border border-slate-200 overflow-hidden">
            <button
              :class="[
                'px-3 py-1.5 text-xs font-medium transition-colors',
                preview.layoutMode === 'with-tabs' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50',
              ]"
              @click="preview.layoutMode = 'with-tabs'"
            >
              Mit Tabs
            </button>
            <button
              :class="[
                'px-3 py-1.5 text-xs font-medium transition-colors border-l border-slate-200',
                preview.layoutMode === 'without-tabs' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50',
              ]"
              @click="preview.layoutMode = 'without-tabs'"
            >
              Ohne Tabs
            </button>
          </div>
        </div>

        <!-- Module Theme -->
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-500 uppercase">Modul:</span>
          <select
            v-model="preview.moduleName"
            class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 bg-white focus:ring-2 focus:ring-brand-500"
          >
            <option v-for="name in moduleNames" :key="name" :value="name">
              {{ name }}
            </option>
          </select>
        </div>

        <!-- Content Type -->
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-500 uppercase">Inhalt:</span>
          <div class="flex rounded-lg border border-slate-200 overflow-hidden">
            <button
              v-for="ct in (['cards', 'table', 'grid'] as const)"
              :key="ct"
              :class="[
                'px-3 py-1.5 text-xs font-medium transition-colors border-l border-slate-200 first:border-l-0',
                preview.contentType === ct ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50',
              ]"
              @click="preview.contentType = ct"
            >
              {{ ct === 'cards' ? 'Karten' : ct === 'table' ? 'Tabelle' : 'Grid' }}
            </button>
          </div>
        </div>

        <!-- Toggles -->
        <label class="flex items-center gap-1.5 cursor-pointer">
          <input type="checkbox" v-model="preview.showSearch" class="w-3.5 h-3.5 rounded border-slate-300 text-brand-600" />
          <span class="text-xs text-slate-600">Suche</span>
        </label>
        <label class="flex items-center gap-1.5 cursor-pointer">
          <input type="checkbox" v-model="preview.showFilter" class="w-3.5 h-3.5 rounded border-slate-300 text-brand-600" />
          <span class="text-xs text-slate-600">Filter</span>
        </label>
      </div>

      <!-- Live Preview Container (scaled down to fit) -->
      <div class="relative overflow-hidden bg-slate-100 border-b border-slate-200" style="min-height: 600px;">
        <!-- Scaled preview wrapper -->
        <div class="origin-top-left" style="transform: scale(0.75); width: 133.33%; height: 133.33%;">
          <!-- The actual ModuleLayout rendered as a live preview -->
          <ModuleLayout
            :module-name="preview.moduleName"
            :title="preview.title"
            :subtitle="preview.subtitle"
            :tabs="previewTabs"
            :active-tab="preview.activePreviewTab"
            :show-search="preview.showSearch"
            :show-filter="preview.showFilter"
            search-placeholder="Suchen..."
            show-fab
            fab-label="Erstellen"
            @tab-change="preview.activePreviewTab = $event"
          >
            <!-- Primary Action -->
            <template #primary-action>
              <button class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm transition-colors">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Erstellen
              </button>
            </template>

            <!-- Filter Content -->
            <template #filter-content>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                  <select class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    <option>Alle</option>
                    <option>Aktiv</option>
                    <option>Entwurf</option>
                    <option>Inaktiv</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Kategorie</label>
                  <select class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    <option>Alle Kategorien</option>
                    <option>Haare</option>
                    <option>Wellness</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sortierung</label>
                  <select class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    <option>Name (A-Z)</option>
                    <option>Preis (aufsteigend)</option>
                    <option>Neueste zuerst</option>
                  </select>
                </div>
              </div>
            </template>

            <!-- Content: Cards View -->
            <div v-if="preview.contentType === 'cards'" class="p-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                  v-for="card in demoCards"
                  :key="card.id"
                  class="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all flex flex-col overflow-hidden cursor-pointer"
                >
                  <!-- Card Banner -->
                  <div :class="['h-20 bg-gradient-to-r relative', getModuleDesign(preview.moduleName).gradient]">
                    <div class="absolute bottom-3 left-4 text-white">
                      <p class="font-bold text-sm">{{ card.title }}</p>
                    </div>
                  </div>
                  <!-- Card Body -->
                  <div class="p-4 flex-1">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-sm font-semibold text-slate-900">{{ card.price }}</span>
                      <span
                        :class="[
                          'px-2 py-0.5 rounded-full text-[10px] font-medium',
                          card.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                          card.status === 'draft' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600',
                        ]"
                      >
                        {{ card.status === 'active' ? 'Aktiv' : card.status === 'draft' ? 'Entwurf' : 'Inaktiv' }}
                      </span>
                    </div>
                    <p class="text-xs text-slate-500">{{ card.duration }}</p>
                  </div>
                </div>
              </div>

              <!-- Pagination -->
              <div class="mt-6 pt-4 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">6 Einträge</span>
                <div class="flex gap-1">
                  <button class="px-3 py-1 rounded-lg text-xs font-medium bg-brand-600 text-white">1</button>
                  <button class="px-3 py-1 rounded-lg text-xs font-medium bg-white border border-slate-300 text-slate-600">2</button>
                </div>
              </div>
            </div>

            <!-- Content: Table View -->
            <div v-if="preview.contentType === 'table'" class="flex-1 overflow-y-auto">
              <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
                  <tr>
                    <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                    <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">E-Mail</th>
                    <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Rolle</th>
                    <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                  <tr
                    v-for="row in demoTableRows"
                    :key="row.email"
                    class="hover:bg-slate-50 transition-colors cursor-pointer"
                  >
                    <td class="p-4 text-sm text-slate-900 font-medium">{{ row.name }}</td>
                    <td class="p-4 text-sm text-slate-600">{{ row.email }}</td>
                    <td class="p-4 text-sm text-slate-600">{{ row.role }}</td>
                    <td class="p-4">
                      <span
                        :class="[
                          'px-2 py-0.5 rounded-full text-[10px] font-medium',
                          row.status === 'Aktiv' ? 'bg-emerald-100 text-emerald-700' :
                          row.status === 'Urlaub' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600',
                        ]"
                      >
                        {{ row.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <span class="text-xs text-slate-500">Zeige 1-4 von 4</span>
                <div class="flex gap-1">
                  <button class="px-3 py-1 rounded-lg text-xs font-medium bg-brand-600 text-white">1</button>
                </div>
              </div>
            </div>

            <!-- Content: Grid View -->
            <div v-if="preview.contentType === 'grid'" class="p-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                  v-for="card in demoCards"
                  :key="card.id"
                  class="flex items-center gap-4 p-4 bg-white rounded-xl border border-slate-200 hover:shadow-md transition-all cursor-pointer"
                >
                  <div :class="['w-12 h-12 rounded-lg shrink-0 flex items-center justify-center', getModuleDesign(preview.moduleName).iconBg]">
                    <span :class="['text-sm font-bold', getModuleDesign(preview.moduleName).iconText]">
                      {{ card.title.charAt(0) }}
                    </span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-medium text-slate-900 truncate">{{ card.title }}</h4>
                    <p class="text-xs text-slate-500">{{ card.duration }} · {{ card.price }}</p>
                  </div>
                </div>
              </div>
            </div>
          </ModuleLayout>
        </div>
      </div>

      <!-- Layout Explanation -->
      <div class="p-6 bg-white border-t border-slate-200">
        <h3 class="text-sm font-bold text-slate-800 mb-3">Layout-Struktur</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Desktop mit Tabs</h4>
            <pre class="text-[10px] text-slate-600 font-mono leading-relaxed">┌──────────────┬────────────────────────┐
│ Hero-Card    │ Tab-Titel | Suche |  + │
│ (Gradient)   │ Filter-Panel (toggle)  │
├──────────────┤────────────────────────│
│ Vertikale    │ Inhaltsbereich         │
│ Tabs (links) │ (Karten/Tabelle/Grid)  │
│              │ Pagination             │
└──────────────┴────────────────────────┘</pre>
          </div>
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Desktop ohne Tabs</h4>
            <pre class="text-[10px] text-slate-600 font-mono leading-relaxed">┌──────────────┬────────────────────────┐
│ Hero-Card    │ Suche | Filter |  +    │
│ (Gradient)   │ Filter-Panel (toggle)  │
├───────────────────────────────────────┤
│ Inhaltsbereich (volle Breite)         │
│ (Karten / Tabelle / Grid)            │
│ Pagination                           │
└───────────────────────────────────────┘</pre>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- COLORS TAB                                                   -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'colors'" class="p-6 space-y-8">
      <section>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Brand Colors</h2>
        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
          <div v-for="c in brandColors" :key="c.shade" class="text-center">
            <div class="w-full aspect-square rounded-lg shadow-sm border border-slate-200" :style="{ backgroundColor: c.hex }" />
            <p class="text-xs text-slate-500 mt-1">{{ c.shade }}</p>
            <p class="text-[10px] text-slate-400 font-mono">{{ c.hex }}</p>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Slate / Neutral</h2>
        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
          <div v-for="c in slateColors" :key="c.shade" class="text-center">
            <div class="w-full aspect-square rounded-lg shadow-sm border border-slate-200" :style="{ backgroundColor: c.hex }" />
            <p class="text-xs text-slate-500 mt-1">{{ c.shade }}</p>
            <p class="text-[10px] text-slate-400 font-mono">{{ c.hex }}</p>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Semantic Colors</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div v-for="(hex, name) in COLORS.semantic" :key="name" class="text-center">
            <div class="h-16 rounded-lg shadow-sm border border-slate-200" :style="{ backgroundColor: hex }" />
            <p class="text-sm font-medium text-slate-700 mt-2 capitalize">{{ name }}</p>
            <p class="text-xs text-slate-400 font-mono">{{ hex }}</p>
          </div>
        </div>
      </section>
    </div>

    <!-- ============================================================ -->
    <!-- TYPOGRAPHY TAB                                                -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'typography'" class="p-6 space-y-8">
      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-6">Font Sizes</h2>
        <div class="space-y-4">
          <div v-for="(value, key) in TYPOGRAPHY.fontSize" :key="key" class="flex items-baseline gap-4">
            <span class="w-12 text-xs font-mono text-slate-400 shrink-0">{{ key }}</span>
            <span :style="{ fontSize: (value as [string, object])[0] }" class="text-slate-900">
              The quick brown fox jumps over the lazy dog
            </span>
            <span class="text-xs text-slate-400 font-mono shrink-0">{{ (value as [string, object])[0] }}</span>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-6">Font Weights</h2>
        <div class="space-y-3">
          <div v-for="(value, key) in TYPOGRAPHY.fontWeight" :key="key" class="flex items-baseline gap-4">
            <span class="w-20 text-xs font-mono text-slate-400 shrink-0">{{ key }}</span>
            <span :style="{ fontWeight: value }" class="text-lg text-slate-900">
              Bookando Design System — {{ key }}
            </span>
          </div>
        </div>
      </section>
    </div>

    <!-- ============================================================ -->
    <!-- COMPONENTS TAB                                                -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'components'" class="p-6 space-y-8">
      <!-- Buttons -->
      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Buttons</h2>
        <div class="flex flex-wrap gap-3">
          <BButton variant="primary">Primary</BButton>
          <BButton variant="secondary">Secondary</BButton>
          <button :class="BUTTON_STYLES.danger">Danger</button>
          <button :class="BUTTON_STYLES.ghost">Ghost</button>
          <button :class="BUTTON_STYLES.icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
        <h3 class="text-sm font-medium text-slate-700 mt-6 mb-3">Sizes</h3>
        <div class="flex flex-wrap items-center gap-3">
          <button v-for="(cls, size) in BUTTON_SIZES" :key="size" :class="['bg-brand-600 text-white rounded-xl font-medium', cls]">
            {{ size }}
          </button>
        </div>
      </section>

      <!-- Inputs -->
      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Inputs</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="LABEL_STYLES.base">Standard Input</label>
            <input :class="INPUT_STYLES.base" placeholder="Placeholder..." />
          </div>
          <div>
            <label :class="LABEL_STYLES.required">Required Input</label>
            <input :class="INPUT_STYLES.base" placeholder="Pflichtfeld..." />
          </div>
          <div>
            <label :class="LABEL_STYLES.base">Error Input</label>
            <input :class="INPUT_STYLES.error" value="Ungültiger Wert" />
            <p :class="LABEL_STYLES.error">Dieses Feld ist erforderlich.</p>
          </div>
          <div>
            <label :class="LABEL_STYLES.base">Select</label>
            <select :class="INPUT_STYLES.select">
              <option>Auswählen...</option>
              <option>Option A</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Badges -->
      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Badges</h2>
        <div class="flex flex-wrap gap-3">
          <span v-for="(cls, variant) in BADGE_STYLES" :key="variant" :class="cls">
            {{ variant }}
          </span>
        </div>
        <h3 class="text-sm font-medium text-slate-700 mt-6 mb-3">BBadge Component</h3>
        <div class="flex flex-wrap gap-3">
          <BBadge status="ACTIVE" dot>Active</BBadge>
          <BBadge status="BLOCKED" dot>Blocked</BBadge>
          <BBadge status="PENDING" dot>Pending</BBadge>
          <BBadge status="CANCELLED" dot>Cancelled</BBadge>
        </div>
      </section>

      <!-- Cards -->
      <section>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Cards</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div :class="CARD_STYLES.base" class="p-4">
            <h3 class="font-medium text-slate-900">Base Card</h3>
            <p class="text-sm text-slate-500 mt-1">Standard-Kartenstil</p>
          </div>
          <div :class="CARD_STYLES.hover" class="p-4">
            <h3 class="font-medium text-slate-900">Hover Card</h3>
            <p class="text-sm text-slate-500 mt-1">Hover für Schatten</p>
          </div>
          <div :class="CARD_STYLES.interactive" class="p-4">
            <h3 class="font-medium text-slate-900">Interactive Card</h3>
            <p class="text-sm text-slate-500 mt-1">Klickbarer Kartenstil</p>
          </div>
        </div>
      </section>

      <!-- SlideOver Demo -->
      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Overlays</h2>
        <div class="flex flex-wrap gap-3">
          <BButton variant="primary" @click="showSlideOver = true">Open SlideOver</BButton>
        </div>
      </section>

      <BSlideOver v-model="showSlideOver" title="Demo SlideOver" subtitle="Slides from right on desktop, bottom on mobile">
        <div class="space-y-4">
          <p class="text-sm text-slate-600">
            Das SlideOver-Pattern ersetzt Overlay-Modals durch ein Seitenpanel.
            Optimiert für Formulare und Detail-Ansichten.
          </p>
          <div>
            <label :class="LABEL_STYLES.base">Name</label>
            <input :class="INPUT_STYLES.base" placeholder="Max Muster" />
          </div>
          <div>
            <label :class="LABEL_STYLES.base">E-Mail</label>
            <input :class="INPUT_STYLES.base" type="email" placeholder="max@example.ch" />
          </div>
        </div>
        <template #footer>
          <div class="flex justify-end gap-3">
            <BButton variant="secondary" @click="showSlideOver = false">Abbrechen</BButton>
            <BButton variant="primary" @click="showSlideOver = false">Speichern</BButton>
          </div>
        </template>
      </BSlideOver>
    </div>

    <!-- ============================================================ -->
    <!-- MODULES TAB                                                   -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'modules'" class="p-6 space-y-6">
      <p class="text-sm text-slate-500">{{ t('designSystem.modulesDescription') }}</p>
      <div :class="GRID_STYLES.cols3">
        <div
          v-for="name in moduleNames"
          :key="name"
          :class="CARD_STYLES.base"
          class="overflow-hidden cursor-pointer hover:shadow-lg transition-all"
          @click="preview.moduleName = name; activeTab = 'live-preview'"
        >
          <div :class="['bg-gradient-to-r h-20 flex items-end px-4 pb-3', getModuleDesign(name).gradient]">
            <span class="text-white font-bold text-sm capitalize">{{ name }}</span>
          </div>
          <div class="p-4 space-y-2">
            <div class="flex items-center gap-2">
              <div :class="[getModuleDesign(name).activeBg, 'w-8 h-8 rounded-lg']" />
              <span class="text-xs text-slate-500">activeBg</span>
            </div>
            <div class="flex items-center gap-2">
              <div :class="[getModuleDesign(name).iconBg, 'w-8 h-8 rounded-lg flex items-center justify-center']">
                <span :class="[getModuleDesign(name).iconText, 'text-xs font-bold']">A</span>
              </div>
              <span class="text-xs text-slate-500">icon</span>
            </div>
            <div class="flex items-center gap-2">
              <span :class="[getModuleDesign(name).activeText, 'text-sm font-medium']">Active Text</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- SPACING TAB                                                   -->
    <!-- ============================================================ -->
    <div v-if="activeTab === 'spacing'" class="p-6 space-y-8">
      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Spacing Scale</h2>
        <div class="space-y-3">
          <div v-for="s in spacingEntries" :key="s.key" class="flex items-center gap-4">
            <span class="w-8 text-xs font-mono text-slate-400">{{ s.key }}</span>
            <div class="h-4 bg-brand-500 rounded" :style="{ width: `calc(${s.value} * 8)` }" />
            <span class="text-xs text-slate-400 font-mono">{{ s.value }}</span>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Border Radius</h2>
        <div class="flex flex-wrap gap-4">
          <div v-for="r in radiusEntries" :key="r.key" class="text-center">
            <div class="w-16 h-16 bg-brand-100 border-2 border-brand-500" :style="{ borderRadius: r.value }" />
            <p class="text-xs text-slate-500 mt-2">{{ r.key }}</p>
            <p class="text-[10px] text-slate-400 font-mono">{{ r.value }}</p>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Shadows</h2>
        <div class="flex flex-wrap gap-6">
          <div v-for="s in shadowEntries" :key="s.key" class="text-center">
            <div class="w-24 h-24 bg-white rounded-xl" :style="{ boxShadow: s.value }" />
            <p class="text-xs text-slate-500 mt-2">{{ s.key }}</p>
          </div>
        </div>
      </section>
    </div>
  </ModuleLayout>
</template>
