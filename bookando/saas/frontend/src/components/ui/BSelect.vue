<script setup lang="ts">
/**
 * BSelect — Zentrale Select-Komponente
 *
 * Einheitliches Dropdown für alle Module.
 * Unterstützt Label, Error-State, Placeholder, Disabled.
 */
import { computed } from 'vue';
import { INPUT_STYLES, LABEL_STYLES } from '@/design';

export interface SelectOption {
  value: string;
  label: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<{
  modelValue?: string;
  options: SelectOption[];
  label?: string;
  placeholder?: string;
  error?: string;
  hint?: string;
  required?: boolean;
  disabled?: boolean;
  id?: string;
}>(), {
  placeholder: '',
  required: false,
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

const inputId = computed(() => props.id || `select-${Math.random().toString(36).slice(2, 9)}`);
</script>

<template>
  <div>
    <label
      v-if="label"
      :for="inputId"
      :class="required ? LABEL_STYLES.required : LABEL_STYLES.base"
    >
      {{ label }}
    </label>
    <div class="relative">
      <select
        :id="inputId"
        :value="modelValue"
        :disabled="disabled"
        :required="required"
        :class="[
          error ? INPUT_STYLES.error : INPUT_STYLES.select,
          'pr-10',
        ]"
        :aria-invalid="!!error"
        @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option
          v-for="opt in options"
          :key="opt.value"
          :value="opt.value"
          :disabled="opt.disabled"
        >
          {{ opt.label }}
        </option>
      </select>
      <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </div>
    <p v-if="error" :class="LABEL_STYLES.error" role="alert">{{ error }}</p>
    <p v-else-if="hint" :class="LABEL_STYLES.hint">{{ hint }}</p>
  </div>
</template>
