<script setup lang="ts">
/**
 * BTextarea — Zentrale Textarea-Komponente
 */
import { computed } from 'vue';
import { INPUT_STYLES, LABEL_STYLES } from '@/design';

const props = withDefaults(defineProps<{
  modelValue?: string;
  label?: string;
  placeholder?: string;
  error?: string;
  hint?: string;
  required?: boolean;
  disabled?: boolean;
  rows?: number;
  id?: string;
}>(), {
  rows: 3,
  required: false,
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

const inputId = computed(() => props.id || `textarea-${Math.random().toString(36).slice(2, 9)}`);
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
    <textarea
      :id="inputId"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :rows="rows"
      :class="error ? INPUT_STYLES.error : INPUT_STYLES.textarea"
      :aria-invalid="!!error"
      @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
    <p v-if="error" :class="LABEL_STYLES.error" role="alert">{{ error }}</p>
    <p v-else-if="hint" :class="LABEL_STYLES.hint">{{ hint }}</p>
  </div>
</template>
