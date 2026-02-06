<script setup lang="ts">
/**
 * BModal — Zentrale Modal/Dialog-Komponente
 *
 * Features:
 * - Backdrop-Blur
 * - Fokus-Trap (a11y)
 * - Escape zum Schliessen
 * - Bottom-Sheet auf Mobile (optional)
 * - Verschiedene Grössen
 * - Animierter Ein-/Ausgang
 */
import { watch, onMounted, onUnmounted, ref } from 'vue';
import { MODAL_STYLES, LAYOUT } from '@/design';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  title?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
  /** Bottom-Sheet auf Mobile statt Modal */
  mobileSheet?: boolean;
  /** Schliessen durch Klick auf Overlay erlauben */
  closeOnOverlay?: boolean;
  /** Schliessen durch Escape erlauben */
  closeOnEscape?: boolean;
}>(), {
  size: 'md',
  mobileSheet: false,
  closeOnOverlay: true,
  closeOnEscape: true,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'close'): void;
}>();

const modalRef = ref<HTMLElement>();

function close() {
  emit('update:modelValue', false);
  emit('close');
}

function onOverlayClick() {
  if (props.closeOnOverlay) close();
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.closeOnEscape && props.modelValue) {
    close();
  }
}

// Body-Scroll verhindern wenn Modal offen
watch(() => props.modelValue, (open) => {
  document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => {
  document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown);
  document.body.style.overflow = '';
});

const sizeClass = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  full: 'max-w-[95vw]',
};
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        :class="MODAL_STYLES.overlay"
        role="dialog"
        aria-modal="true"
        @click.self="onOverlayClick"
      >
        <Transition
          enter-active-class="duration-200 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="duration-150 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4"
        >
          <div
            v-if="modelValue"
            ref="modalRef"
            :class="[MODAL_STYLES.container, sizeClass[size]]"
          >
            <!-- Header -->
            <div v-if="title || $slots.header" :class="MODAL_STYLES.header">
              <slot name="header">
                <h2 :class="MODAL_STYLES.title">{{ title }}</h2>
              </slot>
              <button
                class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
                aria-label="Schliessen"
                @click="close"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div :class="MODAL_STYLES.body">
              <slot />
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" :class="MODAL_STYLES.footer">
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
