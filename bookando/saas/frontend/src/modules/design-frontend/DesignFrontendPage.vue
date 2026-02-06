<script setup lang="ts">
/**
 * Design Frontend — Buchungsformular-Anpassung
 *
 * Ermöglicht die visuelle Gestaltung des öffentlichen Buchungsformulars:
 * - Farb-Themes für das Frontend-Widget
 * - Logo & Branding-Upload
 * - Layout-Optionen (Kalender, Liste, Karten)
 * - Vorschau des Buchungsformulars
 * - CSS-Export für WordPress-Integration
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import BButton from '@/components/ui/BButton.vue';
import { CARD_STYLES, INPUT_STYLES, LABEL_STYLES, BUTTON_STYLES } from '@/design';
import { useToast } from '@/composables/useToast';

const { t } = useI18n();
const toast = useToast();

const activeTab = ref('theme');
const tabs = computed(() => [
  { id: 'theme', label: t('designFrontend.tabs.theme') },
  { id: 'layout', label: t('designFrontend.tabs.layout') },
  { id: 'preview', label: t('designFrontend.tabs.preview') },
  { id: 'embed', label: t('designFrontend.tabs.embed') },
]);

// Theme configuration
const theme = ref({
  primaryColor: '#2563eb',
  accentColor: '#10b981',
  backgroundColor: '#ffffff',
  textColor: '#1e293b',
  borderRadius: '12',
  fontFamily: 'Inter',
  buttonStyle: 'rounded' as 'rounded' | 'pill' | 'square',
  headerStyle: 'gradient' as 'gradient' | 'solid' | 'minimal',
});

// Layout configuration
const layout = ref({
  mode: 'calendar' as 'calendar' | 'list' | 'cards',
  showServiceImages: true,
  showEmployeePhotos: true,
  showPrices: true,
  showDuration: true,
  groupByCategory: true,
  showAvailabilityBars: true,
  compactMode: false,
});

// Preset themes
const presets = [
  { name: 'Bookando Blue', primary: '#2563eb', accent: '#10b981', bg: '#ffffff', text: '#1e293b' },
  { name: 'Warm Coral', primary: '#f43f5e', accent: '#f59e0b', bg: '#fffbeb', text: '#1c1917' },
  { name: 'Forest Green', primary: '#059669', accent: '#8b5cf6', bg: '#f0fdf4', text: '#14532d' },
  { name: 'Night Owl', primary: '#8b5cf6', accent: '#06b6d4', bg: '#0f172a', text: '#f8fafc' },
  { name: 'Minimal Gray', primary: '#475569', accent: '#3b82f6', bg: '#f8fafc', text: '#0f172a' },
  { name: 'Swiss Red', primary: '#dc2626', accent: '#fbbf24', bg: '#ffffff', text: '#1e293b' },
];

function applyPreset(preset: typeof presets[0]) {
  theme.value.primaryColor = preset.primary;
  theme.value.accentColor = preset.accent;
  theme.value.backgroundColor = preset.bg;
  theme.value.textColor = preset.text;
}

// Generate CSS custom properties
const cssVariables = computed(() => {
  return `:root {
  --bookando-primary: ${theme.value.primaryColor};
  --bookando-accent: ${theme.value.accentColor};
  --bookando-bg: ${theme.value.backgroundColor};
  --bookando-text: ${theme.value.textColor};
  --bookando-radius: ${theme.value.borderRadius}px;
  --bookando-font: '${theme.value.fontFamily}', sans-serif;
}`;
});

const embedCode = computed(() => {
  return `<!-- Bookando Booking Widget -->
<div id="bookando-widget" data-tenant="YOUR_TENANT_ID"></div>
<script src="https://cdn.bookando.ch/widget/v1/bookando.js"><\/script>
<style>
${cssVariables.value}
</style>`;
});

function saveTheme() {
  // TODO: API call to save theme settings
  toast.success(t('designFrontend.themeSaved'));
}

function copyEmbedCode() {
  navigator.clipboard.writeText(embedCode.value);
  toast.success(t('designFrontend.codeCopied'));
}
</script>

<template>
  <ModuleLayout
    module-name="tools"
    :title="t('designFrontend.title')"
    :subtitle="t('designFrontend.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="activeTab = $event"
  >
    <template #header-actions>
      <BButton variant="primary" class="!text-xs !bg-white/20 !border-white/30 !text-white" @click="saveTheme">
        {{ t('common.save') }}
      </BButton>
    </template>

    <!-- THEME -->
    <div v-if="activeTab === 'theme'" class="space-y-6">
      <!-- Presets -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ t('designFrontend.presets') }}</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <button
            v-for="preset in presets"
            :key="preset.name"
            class="text-left rounded-xl border-2 p-3 transition-all hover:shadow-md"
            :class="theme.primaryColor === preset.primary ? 'border-brand-500 shadow-md' : 'border-slate-200'"
            @click="applyPreset(preset)"
          >
            <div class="flex gap-1.5 mb-2">
              <div class="w-6 h-6 rounded-full border border-slate-200" :style="{ backgroundColor: preset.primary }" />
              <div class="w-6 h-6 rounded-full border border-slate-200" :style="{ backgroundColor: preset.accent }" />
            </div>
            <p class="text-xs font-medium text-slate-700 truncate">{{ preset.name }}</p>
          </button>
        </div>
      </section>

      <!-- Color Pickers -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ t('designFrontend.customColors') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label :class="LABEL_STYLES.base">{{ t('designFrontend.primaryColor') }}</label>
            <div class="flex items-center gap-2">
              <input type="color" v-model="theme.primaryColor" class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0" />
              <input v-model="theme.primaryColor" :class="INPUT_STYLES.base" class="font-mono text-sm" />
            </div>
          </div>
          <div>
            <label :class="LABEL_STYLES.base">{{ t('designFrontend.accentColor') }}</label>
            <div class="flex items-center gap-2">
              <input type="color" v-model="theme.accentColor" class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0" />
              <input v-model="theme.accentColor" :class="INPUT_STYLES.base" class="font-mono text-sm" />
            </div>
          </div>
          <div>
            <label :class="LABEL_STYLES.base">{{ t('designFrontend.backgroundColor') }}</label>
            <div class="flex items-center gap-2">
              <input type="color" v-model="theme.backgroundColor" class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0" />
              <input v-model="theme.backgroundColor" :class="INPUT_STYLES.base" class="font-mono text-sm" />
            </div>
          </div>
          <div>
            <label :class="LABEL_STYLES.base">{{ t('designFrontend.textColor') }}</label>
            <div class="flex items-center gap-2">
              <input type="color" v-model="theme.textColor" class="w-10 h-10 rounded-lg cursor-pointer border-0 p-0" />
              <input v-model="theme.textColor" :class="INPUT_STYLES.base" class="font-mono text-sm" />
            </div>
          </div>
        </div>
      </section>

      <!-- Style Options -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ t('designFrontend.styleOptions') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label :class="LABEL_STYLES.base">{{ t('designFrontend.borderRadius') }}</label>
            <div class="flex items-center gap-3">
              <input type="range" v-model="theme.borderRadius" min="0" max="24" class="flex-1" />
              <span class="text-sm text-slate-600 font-mono w-12">{{ theme.borderRadius }}px</span>
            </div>
          </div>
          <div>
            <label :class="LABEL_STYLES.base">{{ t('designFrontend.buttonStyle') }}</label>
            <select v-model="theme.buttonStyle" :class="INPUT_STYLES.select">
              <option value="rounded">Rounded</option>
              <option value="pill">Pill</option>
              <option value="square">Square</option>
            </select>
          </div>
          <div>
            <label :class="LABEL_STYLES.base">{{ t('designFrontend.fontFamily') }}</label>
            <select v-model="theme.fontFamily" :class="INPUT_STYLES.select">
              <option value="Inter">Inter</option>
              <option value="Roboto">Roboto</option>
              <option value="Open Sans">Open Sans</option>
              <option value="Poppins">Poppins</option>
              <option value="system-ui">System Default</option>
            </select>
          </div>
        </div>
      </section>
    </div>

    <!-- LAYOUT -->
    <div v-if="activeTab === 'layout'" class="space-y-6">
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ t('designFrontend.displayMode') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <button
            v-for="mode in (['calendar', 'list', 'cards'] as const)"
            :key="mode"
            class="p-4 rounded-xl border-2 text-left transition-all"
            :class="layout.mode === mode ? 'border-brand-500 bg-brand-50' : 'border-slate-200 hover:border-slate-300'"
            @click="layout.mode = mode"
          >
            <div class="text-2xl mb-2">{{ mode === 'calendar' ? '📅' : mode === 'list' ? '📋' : '🃏' }}</div>
            <p class="text-sm font-medium text-slate-900 capitalize">{{ mode }}</p>
            <p class="text-xs text-slate-500 mt-1">
              {{ mode === 'calendar' ? t('designFrontend.calendarDesc') : mode === 'list' ? t('designFrontend.listDesc') : t('designFrontend.cardsDesc') }}
            </p>
          </button>
        </div>
      </section>

      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ t('designFrontend.displayOptions') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="layout.showServiceImages" :class="INPUT_STYLES.checkbox" />
            <span class="text-sm text-slate-700">{{ t('designFrontend.showServiceImages') }}</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="layout.showEmployeePhotos" :class="INPUT_STYLES.checkbox" />
            <span class="text-sm text-slate-700">{{ t('designFrontend.showEmployeePhotos') }}</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="layout.showPrices" :class="INPUT_STYLES.checkbox" />
            <span class="text-sm text-slate-700">{{ t('designFrontend.showPrices') }}</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="layout.showDuration" :class="INPUT_STYLES.checkbox" />
            <span class="text-sm text-slate-700">{{ t('designFrontend.showDuration') }}</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="layout.groupByCategory" :class="INPUT_STYLES.checkbox" />
            <span class="text-sm text-slate-700">{{ t('designFrontend.groupByCategory') }}</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="layout.showAvailabilityBars" :class="INPUT_STYLES.checkbox" />
            <span class="text-sm text-slate-700">{{ t('designFrontend.showAvailability') }}</span>
          </label>
        </div>
      </section>
    </div>

    <!-- PREVIEW -->
    <div v-if="activeTab === 'preview'" class="space-y-6">
      <div :class="CARD_STYLES.base" class="overflow-hidden">
        <!-- Preview Frame -->
        <div
          class="p-6"
          :style="{
            backgroundColor: theme.backgroundColor,
            color: theme.textColor,
            fontFamily: `'${theme.fontFamily}', sans-serif`,
          }"
        >
          <!-- Header -->
          <div
            class="p-6 mb-6 text-white"
            :style="{
              backgroundColor: theme.primaryColor,
              borderRadius: `${theme.borderRadius}px`,
            }"
          >
            <h2 class="text-xl font-bold">Salon Beispiel</h2>
            <p class="text-sm opacity-80 mt-1">Buchen Sie Ihren Termin online</p>
          </div>

          <!-- Service Cards -->
          <div class="space-y-3">
            <div
              v-for="i in 3"
              :key="i"
              class="flex items-center gap-4 p-4 border transition-shadow hover:shadow-md"
              :style="{
                borderColor: theme.primaryColor + '30',
                borderRadius: `${theme.borderRadius}px`,
              }"
            >
              <div
                v-if="layout.showServiceImages"
                class="w-16 h-16 rounded-lg shrink-0"
                :style="{ backgroundColor: theme.accentColor + '20', borderRadius: `${Math.max(4, Number(theme.borderRadius) - 4)}px` }"
              />
              <div class="flex-1 min-w-0">
                <h3 class="font-medium" :style="{ color: theme.textColor }">
                  {{ ['Haarschnitt Damen', 'Farbe & Strähnen', 'Styling Hochsteck'][i - 1] }}
                </h3>
                <p class="text-sm opacity-60 mt-0.5">
                  <span v-if="layout.showDuration">{{ [45, 90, 60][i - 1] }} Min</span>
                  <span v-if="layout.showDuration && layout.showPrices"> · </span>
                  <span v-if="layout.showPrices">CHF {{ [65, 120, 85][i - 1] }}.00</span>
                </p>
              </div>
              <button
                class="px-4 py-2 text-sm font-medium text-white shrink-0"
                :style="{
                  backgroundColor: theme.primaryColor,
                  borderRadius: theme.buttonStyle === 'pill' ? '999px' : theme.buttonStyle === 'square' ? '4px' : `${theme.borderRadius}px`,
                }"
              >
                Buchen
              </button>
            </div>
          </div>

          <!-- Availability -->
          <div v-if="layout.showAvailabilityBars" class="mt-6">
            <h3 class="font-medium mb-3" :style="{ color: theme.textColor }">Verfügbarkeit</h3>
            <div class="grid grid-cols-7 gap-1">
              <div
                v-for="day in 7"
                :key="day"
                class="text-center py-2 text-xs font-medium"
                :style="{
                  backgroundColor: day === 3 ? theme.primaryColor : (day % 2 ? theme.accentColor + '20' : 'transparent'),
                  color: day === 3 ? '#fff' : theme.textColor,
                  borderRadius: `${theme.borderRadius}px`,
                }"
              >
                {{ ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'][day - 1] }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- EMBED CODE -->
    <div v-if="activeTab === 'embed'" class="space-y-6">
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-2">{{ t('designFrontend.embedTitle') }}</h2>
        <p class="text-sm text-slate-500 mb-4">{{ t('designFrontend.embedDescription') }}</p>
        <div class="relative">
          <pre class="bg-slate-900 text-slate-100 p-4 rounded-xl text-xs overflow-x-auto font-mono leading-relaxed">{{ embedCode }}</pre>
          <button
            :class="BUTTON_STYLES.secondary"
            class="absolute top-3 right-3 !py-1 !px-3 !text-xs"
            @click="copyEmbedCode"
          >
            {{ t('common.copy') }}
          </button>
        </div>
      </section>

      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-2">CSS Variables</h2>
        <pre class="bg-slate-900 text-slate-100 p-4 rounded-xl text-xs overflow-x-auto font-mono leading-relaxed">{{ cssVariables }}</pre>
      </section>
    </div>
  </ModuleLayout>
</template>
