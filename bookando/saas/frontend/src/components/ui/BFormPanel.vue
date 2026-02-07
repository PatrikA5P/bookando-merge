<script setup lang="ts">
/**
 * BFormPanel — Gold Standard fuer alle Erfassen/Bearbeiten-Formulare
 *
 * DESIGN-ENTSCHEIDUNG (MODUL_ANALYSE.md):
 * Kein zentriertes Overlay-Modal. Stattdessen SlideIn-Panel von rechts,
 * das sich wie eine Detail-Ansicht anfuehlt und den Hauptinhalt nicht
 * vollstaendig verdeckt.
 *
 * Features:
 * - Desktop (lg+): SlideIn von rechts, konfigurierbare Breite
 * - Tablet (md): SlideIn von rechts, 80% Breite
 * - Mobile (< md): Fullscreen, SlideUp von unten
 * - Sticky Header mit Titel + Schliessen + optionale Tabs
 * - Scrollbarer Body fuer Formularinhalt
 * - Sticky Footer mit Abbrechen/Speichern Buttons
 * - Unsaved-Changes-Warnung
 * - Loading-State waehrend Speichern
 * - Sektions-Unterstuetzung mit Tabs
 * - Backdrop: halbtransparent, klickbar zum Schliessen
 *
 * Usage:
 *   <BFormPanel
 *     v-model="showPanel"
 *     :title="isNew ? 'Angebot erstellen' : 'Angebot bearbeiten'"
 *     :tabs="['Allgemein', 'Preise', 'Verfuegbarkeit']"
 *     :saving="isSaving"
 *     :dirty="hasUnsavedChanges"
 *     @save="handleSave"
 *     @cancel="handleCancel"
 *   >
 *     <template #tab-0> ... </template>
 *     <template #tab-1> ... </template>
 *   </BFormPanel>
 */
import { ref, watch, onUnmounted, computed } from 'vue';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  title: string;
  subtitle?: string;

  /** Panel-Groesse */
  size?: 'sm' | 'md' | 'lg' | 'xl';

  /** Tab-Navigation innerhalb des Panels */
  tabs?: string[];

  /** Speichern-Button */
  saveLabel?: string;
  cancelLabel?: string;
  saving?: boolean;
  disabled?: boolean;

  /** Dirty-State fuer Unsaved-Changes-Warnung */
  dirty?: boolean;

  /** Schliessen-Verhalten */
  closeOnOverlay?: boolean;
  closeOnEscape?: boolean;

  /** Modus: create oder edit */
  mode?: 'create' | 'edit';
}>(), {
  size: 'lg',
  saveLabel: 'Speichern',
  cancelLabel: 'Abbrechen',
  saving: false,
  disabled: false,
  dirty: false,
  closeOnOverlay: true,
  closeOnEscape: true,
  mode: 'create',
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save'): void;
  (e: 'cancel'): void;
  (e: 'close'): void;
  (e: 'tab-change', index: number): void;
}>();

// Active tab
const activeTab = ref(0);

// Width classes per size
const sizeClasses: Record<string, string> = {
  sm: 'md:max-w-md',
  md: 'md:max-w-xl',
  lg: 'md:max-w-2xl',
  xl: 'md:max-w-4xl',
};

const hasTabs = computed(() => props.tabs && props.tabs.length > 0);

// ---- Close Logic ----

function requestClose() {
  if (props.dirty) {
    if (!confirm('Es gibt ungespeicherte Aenderungen. Wirklich schliessen?')) {
      return;
    }
  }
  close();
}

function close() {
  emit('update:modelValue', false);
  emit('close');
  // Tab zuruecksetzen beim Schliessen
  activeTab.value = 0;
}

function handleOverlayClick() {
  if (props.closeOnOverlay) requestClose();
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.closeOnEscape) requestClose();
}

function handleCancel() {
  emit('cancel');
  requestClose();
}

function handleSave() {
  emit('save');
}

function setTab(index: number) {
  activeTab.value = index;
  emit('tab-change', index);
}

// ---- Body scroll lock ----

watch(() => props.modelValue, (open) => {
  if (open) {
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', handleEscape);
  } else {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', handleEscape);
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
  document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-40 bg-black/20 backdrop-blur-[2px]"
        @click="handleOverlayClick"
      />
    </Transition>

    <!-- Panel -->
    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-y-full md:translate-y-0 md:translate-x-full"
      enter-to-class="translate-y-0 md:translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-y-0 md:translate-x-0"
      leave-to-class="translate-y-full md:translate-y-0 md:translate-x-full"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 md:inset-y-0 md:left-auto md:right-0 z-50 flex flex-col bg-white shadow-2xl border-l border-slate-200"
        :class="[sizeClasses[size] || sizeClasses.lg, 'md:w-full']"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <!-- ============================================================ -->
        <!-- HEADER (sticky) -->
        <!-- ============================================================ -->
        <div class="shrink-0 border-b border-slate-200 bg-white">
          <!-- Title Row -->
          <div class="flex items-center justify-between px-6 py-4">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span
                  v-if="mode === 'create'"
                  class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-brand-100 text-brand-700"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                  </svg>
                </span>
                <span
                  v-else
                  class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-slate-100 text-slate-600"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                  </svg>
                </span>
                <h2 class="text-lg font-semibold text-slate-900 truncate">{{ title }}</h2>
              </div>
              <p v-if="subtitle" class="text-sm text-slate-500 mt-0.5 ml-8">{{ subtitle }}</p>
            </div>

            <div class="flex items-center gap-2 shrink-0 ml-4">
              <slot name="header-actions" />
              <!-- Dirty Indicator -->
              <span
                v-if="dirty"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                Ungespeichert
              </span>
              <!-- Close Button -->
              <button
                class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                @click="requestClose"
                aria-label="Schliessen"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Tab Navigation (optional) -->
          <div v-if="hasTabs" class="px-6 -mb-px">
            <nav class="flex gap-1 overflow-x-auto scrollbar-hide" role="tablist">
              <button
                v-for="(tab, index) in tabs"
                :key="index"
                role="tab"
                :aria-selected="activeTab === index"
                class="relative px-4 py-2.5 text-sm font-medium whitespace-nowrap transition-colors rounded-t-lg"
                :class="activeTab === index
                  ? 'text-brand-700 bg-brand-50'
                  : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                @click="setTab(index)"
              >
                {{ tab }}
                <!-- Active indicator line -->
                <span
                  v-if="activeTab === index"
                  class="absolute bottom-0 left-2 right-2 h-0.5 bg-brand-600 rounded-full"
                />
              </button>
            </nav>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- BODY (scrollbar) -->
        <!-- ============================================================ -->
        <div class="flex-1 overflow-y-auto">
          <div class="px-6 py-6">
            <!-- Wenn Tabs definiert: Named Slots pro Tab -->
            <template v-if="hasTabs">
              <template v-for="(tab, index) in tabs" :key="index">
                <div v-show="activeTab === index">
                  <slot :name="`tab-${index}`" />
                </div>
              </template>
            </template>

            <!-- Wenn keine Tabs: Default Slot -->
            <template v-else>
              <slot />
            </template>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- FOOTER (sticky) -->
        <!-- ============================================================ -->
        <div class="shrink-0 flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50/80">
          <!-- Linke Seite: optionaler Slot fuer z.B. Loeschen-Button -->
          <div>
            <slot name="footer-left" />
          </div>

          <!-- Rechte Seite: Cancel + Save -->
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="px-5 py-2.5 rounded-xl text-sm font-medium border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors"
              @click="handleCancel"
              :disabled="saving"
            >
              {{ cancelLabel }}
            </button>
            <button
              type="button"
              class="px-5 py-2.5 rounded-xl text-sm font-bold bg-brand-600 text-white hover:bg-brand-700 shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
              :disabled="saving || disabled"
              @click="handleSave"
            >
              <!-- Loading Spinner -->
              <svg
                v-if="saving"
                class="animate-spin w-4 h-4"
                fill="none" viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              {{ saving ? 'Speichert...' : saveLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
