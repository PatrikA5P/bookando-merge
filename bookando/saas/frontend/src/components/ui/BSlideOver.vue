<script setup lang="ts">
/**
 * BSlideOver — Slide-over Panel-Komponente
 *
 * Ersetzt Overlay-Modals mit einem Slide-over-Panel-Muster
 * nach UX-Best-Practices.
 *
 * Features:
 * - Desktop (lg+): Gleitet von rechts ein, 50% Breite (max-w-2xl)
 * - Tablet (md–lg): Gleitet von rechts ein, 75% Breite
 * - Mobile (< md): Vollbild, gleitet von unten ein
 * - Backdrop-Blur mit semi-transparentem Overlay
 * - Escape zum Schliessen
 * - Scrollbarer Body mit Sticky Header/Footer
 * - Verschiedene Grössen (sm, md, lg, xl)
 */
import { watch, onUnmounted } from 'vue';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  title?: string;
  subtitle?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  /** Schliessen durch Klick auf Overlay erlauben */
  closeOnOverlay?: boolean;
  /** Schliessen durch Escape erlauben */
  closeOnEscape?: boolean;
}>(), {
  size: 'lg',
  closeOnOverlay: true,
  closeOnEscape: true,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'close'): void;
}>();

const sizeClasses: Record<string, string> = {
  sm: 'md:max-w-sm',
  md: 'md:max-w-md',
  lg: 'md:max-w-2xl',
  xl: 'md:max-w-4xl',
};

function close() {
  emit('update:modelValue', false);
  emit('close');
}

function handleOverlayClick() {
  if (props.closeOnOverlay) close();
}

function handleEscape(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.closeOnEscape) close();
}

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
    <!-- Overlay -->
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
        class="fixed inset-0 z-40 bg-black/30 backdrop-blur-sm"
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
        class="fixed inset-0 md:inset-y-0 md:left-auto md:right-0 z-50 flex flex-col bg-white shadow-2xl"
        :class="[sizeClasses[size] || sizeClasses.lg, 'md:w-full']"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white shrink-0">
          <div>
            <h2 v-if="title" class="text-lg font-semibold text-slate-900">{{ title }}</h2>
            <p v-if="subtitle" class="text-sm text-slate-500 mt-0.5">{{ subtitle }}</p>
          </div>
          <div class="flex items-center gap-2">
            <slot name="header-actions" />
            <button
              class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
              @click="close"
              aria-label="Schliessen"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body (scrollbar) -->
        <div class="flex-1 overflow-y-auto px-6 py-6">
          <slot />
        </div>

        <!-- Footer (sticky) -->
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-200 bg-white shrink-0">
          <slot name="footer" />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
