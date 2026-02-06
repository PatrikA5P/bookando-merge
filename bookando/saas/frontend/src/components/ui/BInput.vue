<script setup lang="ts">
/**
 * BInput — Zentrale Input-Komponente
 *
 * Unterstützt:
 * - Text, E-Mail, Passwort, Nummer, Datum, Tel
 * - Error-State mit Fehlermeldung
 * - Label und Hint-Text
 * - Required-Markierung
 * - Prefix/Suffix Slots
 */
import { computed } from 'vue';
import { INPUT_STYLES, LABEL_STYLES } from '@/design';

const props = withDefaults(defineProps<{
  modelValue?: string | number;
  type?: 'text' | 'email' | 'password' | 'number' | 'date' | 'time' | 'tel' | 'url';
  label?: string;
  placeholder?: string;
  error?: string;
  hint?: string;
  required?: boolean;
  disabled?: boolean;
  id?: string;
}>(), {
  type: 'text',
  required: false,
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number): void;
}>();

const inputId = computed(() => props.id || `input-${Math.random().toString(36).slice(2, 9)}`);

const inputClass = computed(() => {
  return props.error ? INPUT_STYLES.error : INPUT_STYLES.base;
});

function onInput(event: Event) {
  const target = event.target as HTMLInputElement;
  const value = props.type === 'number' ? Number(target.value) : target.value;
  emit('update:modelValue', value);
}
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
      <!-- Prefix -->
      <div v-if="$slots.prefix" class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
        <slot name="prefix" />
      </div>

      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :class="[inputClass, $slots.prefix ? 'pl-10' : '', $slots.suffix ? 'pr-10' : '']"
        :aria-invalid="!!error"
        :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
        @input="onInput"
      />

      <!-- Suffix -->
      <div v-if="$slots.suffix" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
        <slot name="suffix" />
      </div>
    </div>

    <p v-if="error" :id="`${inputId}-error`" :class="LABEL_STYLES.error" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" :id="`${inputId}-hint`" :class="LABEL_STYLES.hint">
      {{ hint }}
    </p>
  </div>
</template>
