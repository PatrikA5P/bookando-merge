<script setup lang="ts">
/**
 * BConfirmDialog — Bestätigungsdialog
 *
 * Verbesserung aus IMPROVEMENTS.md:
 * Bestätigungsdialoge für destruktive Aktionen.
 */
import BModal from './BModal.vue';
import BButton from './BButton.vue';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  title?: string;
  message?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: 'danger' | 'warning' | 'info';
  loading?: boolean;
}>(), {
  title: 'Bestätigung',
  message: 'Möchten Sie diese Aktion wirklich ausführen?',
  confirmLabel: 'Bestätigen',
  cancelLabel: 'Abbrechen',
  variant: 'danger',
  loading: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'confirm'): void;
  (e: 'cancel'): void;
}>();

function close() {
  emit('update:modelValue', false);
  emit('cancel');
}

function confirm() {
  emit('confirm');
}

const iconColors = {
  danger: 'bg-red-100 text-red-600',
  warning: 'bg-amber-100 text-amber-600',
  info: 'bg-blue-100 text-blue-600',
};
</script>

<template>
  <BModal :model-value="modelValue" :title="title" size="sm" @update:model-value="emit('update:modelValue', $event)">
    <div class="text-center sm:text-left">
      <div class="flex items-start gap-4">
        <div :class="['w-10 h-10 rounded-full flex items-center justify-center shrink-0', iconColors[variant]]">
          <svg v-if="variant === 'danger'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <svg v-else-if="variant === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-sm text-slate-600 mt-2">{{ message }}</p>
      </div>
    </div>

    <template #footer>
      <BButton variant="secondary" @click="close">{{ cancelLabel }}</BButton>
      <BButton :variant="variant === 'danger' ? 'danger' : 'primary'" :loading="loading" @click="confirm">
        {{ confirmLabel }}
      </BButton>
    </template>
  </BModal>
</template>
