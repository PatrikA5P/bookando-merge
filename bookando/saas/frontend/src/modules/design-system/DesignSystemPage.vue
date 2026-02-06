<script setup lang="ts">
/**
 * Design System — Interaktive Vorschau aller Design-Tokens & UI-Komponenten
 *
 * Ermöglicht das Testen und Überprüfen aller Bookando-Designelemente:
 * - Farben & Modul-Paletten
 * - Typografie
 * - Buttons, Inputs, Badges, Cards
 * - Spacing, Schatten, Radii
 * - BSlideOver Vorschau
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BModal from '@/components/ui/BModal.vue';
import BSlideOver from '@/components/ui/BSlideOver.vue';
import {
  BUTTON_STYLES, BUTTON_SIZES, INPUT_STYLES, LABEL_STYLES,
  CARD_STYLES, BADGE_STYLES, GRID_STYLES,
  MODULE_DESIGNS, COLORS, TYPOGRAPHY, SPACING, BORDER_RADIUS, SHADOWS,
  getModuleDesign, getModuleNames,
} from '@/design';

const { t } = useI18n();

const activeTab = ref('colors');
const tabs = computed(() => [
  { id: 'colors', label: t('designSystem.tabs.colors') },
  { id: 'typography', label: t('designSystem.tabs.typography') },
  { id: 'components', label: t('designSystem.tabs.components') },
  { id: 'modules', label: t('designSystem.tabs.modules') },
  { id: 'spacing', label: t('designSystem.tabs.spacing') },
]);

// Demo state
const showModal = ref(false);
const showSlideOver = ref(false);
const demoInput = ref('');
const demoSelect = ref('');

const moduleNames = getModuleNames();

const brandColors = Object.entries(COLORS.brand).map(([shade, hex]) => ({ shade, hex }));
const slateColors = Object.entries(COLORS.slate).map(([shade, hex]) => ({ shade, hex }));
const spacingEntries = Object.entries(SPACING).map(([key, value]) => ({ key, value }));
const radiusEntries = Object.entries(BORDER_RADIUS).map(([key, value]) => ({ key, value }));
const shadowEntries = Object.entries(SHADOWS).map(([key, value]) => ({ key, value }));
</script>

<template>
  <ModuleLayout
    module-name="tools"
    :title="t('designSystem.title')"
    :subtitle="t('designSystem.subtitle')"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="activeTab = $event"
  >
    <!-- FARBEN -->
    <div v-if="activeTab === 'colors'" class="space-y-8">
      <!-- Brand-Farben -->
      <section>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Brand Colors</h2>
        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
          <div
            v-for="c in brandColors"
            :key="c.shade"
            class="text-center"
          >
            <div
              class="w-full aspect-square rounded-lg shadow-sm border border-slate-200"
              :style="{ backgroundColor: c.hex }"
            />
            <p class="text-xs text-slate-500 mt-1">{{ c.shade }}</p>
            <p class="text-[10px] text-slate-400 font-mono">{{ c.hex }}</p>
          </div>
        </div>
      </section>

      <!-- Slate-Farben -->
      <section>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Slate / Neutral</h2>
        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
          <div
            v-for="c in slateColors"
            :key="c.shade"
            class="text-center"
          >
            <div
              class="w-full aspect-square rounded-lg shadow-sm border border-slate-200"
              :style="{ backgroundColor: c.hex }"
            />
            <p class="text-xs text-slate-500 mt-1">{{ c.shade }}</p>
            <p class="text-[10px] text-slate-400 font-mono">{{ c.hex }}</p>
          </div>
        </div>
      </section>

      <!-- Semantische Farben -->
      <section>
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Semantic Colors</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div v-for="(hex, name) in COLORS.semantic" :key="name" class="text-center">
            <div
              class="h-16 rounded-lg shadow-sm border border-slate-200"
              :style="{ backgroundColor: hex }"
            />
            <p class="text-sm font-medium text-slate-700 mt-2 capitalize">{{ name }}</p>
            <p class="text-xs text-slate-400 font-mono">{{ hex }}</p>
          </div>
        </div>
      </section>
    </div>

    <!-- TYPOGRAFIE -->
    <div v-if="activeTab === 'typography'" class="space-y-8">
      <section :class="CARD_STYLES.base" class="p-6">
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

      <section :class="CARD_STYLES.base" class="p-6">
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

    <!-- KOMPONENTEN -->
    <div v-if="activeTab === 'components'" class="space-y-8">
      <!-- Buttons -->
      <section :class="CARD_STYLES.base" class="p-6">
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
        <h3 class="text-sm font-medium text-slate-700 mt-6 mb-3">Button Sizes</h3>
        <div class="flex flex-wrap items-center gap-3">
          <button v-for="(cls, size) in BUTTON_SIZES" :key="size" :class="['bg-brand-600 text-white rounded-xl font-medium', cls]">
            {{ size }}
          </button>
        </div>
      </section>

      <!-- Inputs -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Inputs</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label :class="LABEL_STYLES.base">Standard Input</label>
            <input v-model="demoInput" :class="INPUT_STYLES.base" placeholder="Placeholder..." />
          </div>
          <div>
            <label :class="LABEL_STYLES.required">Required Input</label>
            <input :class="INPUT_STYLES.base" placeholder="Required field..." />
          </div>
          <div>
            <label :class="LABEL_STYLES.base">Error Input</label>
            <input :class="INPUT_STYLES.error" value="Invalid value" />
            <p :class="LABEL_STYLES.error">This field is required.</p>
          </div>
          <div>
            <label :class="LABEL_STYLES.base">Select</label>
            <select v-model="demoSelect" :class="INPUT_STYLES.select">
              <option value="">Choose...</option>
              <option value="a">Option A</option>
              <option value="b">Option B</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label :class="LABEL_STYLES.base">Textarea</label>
            <textarea :class="INPUT_STYLES.textarea" placeholder="Enter text..." />
          </div>
        </div>
      </section>

      <!-- Badges -->
      <section :class="CARD_STYLES.base" class="p-6">
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
            <p class="text-sm text-slate-500 mt-1">Standard card style</p>
          </div>
          <div :class="CARD_STYLES.hover" class="p-4">
            <h3 class="font-medium text-slate-900">Hover Card</h3>
            <p class="text-sm text-slate-500 mt-1">Hover for shadow</p>
          </div>
          <div :class="CARD_STYLES.interactive" class="p-4">
            <h3 class="font-medium text-slate-900">Interactive Card</h3>
            <p class="text-sm text-slate-500 mt-1">Clickable card style</p>
          </div>
        </div>
      </section>

      <!-- Modals & SlideOvers -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Overlays</h2>
        <div class="flex flex-wrap gap-3">
          <BButton variant="secondary" @click="showModal = true">Open Modal</BButton>
          <BButton variant="primary" @click="showSlideOver = true">Open SlideOver</BButton>
        </div>
      </section>

      <BModal v-model="showModal" title="Demo Modal" size="md">
        <p class="text-sm text-slate-600">
          This is a standard modal dialog. On mobile it becomes a bottom sheet.
        </p>
        <div class="mt-4">
          <label :class="LABEL_STYLES.base">Sample Input</label>
          <input :class="INPUT_STYLES.base" placeholder="Type something..." />
        </div>
      </BModal>

      <BSlideOver v-model="showSlideOver" title="Demo SlideOver" subtitle="Slides from right on desktop, bottom on mobile">
        <div class="space-y-4">
          <p class="text-sm text-slate-600">
            The SlideOver component replaces overlay modals with a side panel pattern.
            It's optimized for forms and detail views.
          </p>
          <div>
            <label :class="LABEL_STYLES.base">Name</label>
            <input :class="INPUT_STYLES.base" placeholder="Max Muster" />
          </div>
          <div>
            <label :class="LABEL_STYLES.base">Email</label>
            <input :class="INPUT_STYLES.base" type="email" placeholder="max@example.ch" />
          </div>
          <div>
            <label :class="LABEL_STYLES.base">Notes</label>
            <textarea :class="INPUT_STYLES.textarea" placeholder="Additional notes..." />
          </div>
        </div>
        <template #footer>
          <div class="flex justify-end gap-3">
            <BButton variant="secondary" @click="showSlideOver = false">Cancel</BButton>
            <BButton variant="primary" @click="showSlideOver = false">Save</BButton>
          </div>
        </template>
      </BSlideOver>
    </div>

    <!-- MODULE DESIGNS -->
    <div v-if="activeTab === 'modules'" class="space-y-6">
      <p class="text-sm text-slate-500">
        {{ t('designSystem.modulesDescription') }}
      </p>
      <div :class="GRID_STYLES.cols3">
        <div
          v-for="name in moduleNames"
          :key="name"
          :class="CARD_STYLES.base"
          class="overflow-hidden"
        >
          <!-- Module Gradient Header -->
          <div
            :class="['bg-gradient-to-r h-20 flex items-end px-4 pb-3', getModuleDesign(name).gradient]"
          >
            <span class="text-white font-bold text-sm capitalize">{{ name }}</span>
          </div>
          <!-- Module Colors -->
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

    <!-- SPACING & LAYOUT -->
    <div v-if="activeTab === 'spacing'" class="space-y-8">
      <!-- Spacing -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Spacing Scale</h2>
        <div class="space-y-3">
          <div v-for="s in spacingEntries" :key="s.key" class="flex items-center gap-4">
            <span class="w-8 text-xs font-mono text-slate-400">{{ s.key }}</span>
            <div
              class="h-4 bg-brand-500 rounded"
              :style="{ width: `calc(${s.value} * 8)` }"
            />
            <span class="text-xs text-slate-400 font-mono">{{ s.value }}</span>
          </div>
        </div>
      </section>

      <!-- Border Radius -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Border Radius</h2>
        <div class="flex flex-wrap gap-4">
          <div v-for="r in radiusEntries" :key="r.key" class="text-center">
            <div
              class="w-16 h-16 bg-brand-100 border-2 border-brand-500"
              :style="{ borderRadius: r.value }"
            />
            <p class="text-xs text-slate-500 mt-2">{{ r.key }}</p>
            <p class="text-[10px] text-slate-400 font-mono">{{ r.value }}</p>
          </div>
        </div>
      </section>

      <!-- Shadows -->
      <section :class="CARD_STYLES.base" class="p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Shadows</h2>
        <div class="flex flex-wrap gap-6">
          <div v-for="s in shadowEntries" :key="s.key" class="text-center">
            <div
              class="w-24 h-24 bg-white rounded-xl"
              :style="{ boxShadow: s.value }"
            />
            <p class="text-xs text-slate-500 mt-2">{{ s.key }}</p>
          </div>
        </div>
      </section>
    </div>
  </ModuleLayout>
</template>
