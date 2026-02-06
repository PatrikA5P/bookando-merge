<script setup lang="ts">
/**
 * BToggle — Einheitlicher Toggle-Switch
 */
import { computed } from 'vue';

const props = withDefaults(defineProps<{
  modelValue: boolean;
  label?: string;
  description?: string;
  disabled?: boolean;
  id?: string;
}>(), {
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();

const toggleId = computed(() => props.id || `toggle-${Math.random().toString(36).slice(2, 9)}`);
</script>

<template>
  <div class="flex items-center justify-between gap-4">
    <div v-if="label || description">
      <label v-if="label" :for="toggleId" class="text-sm font-medium text-slate-700 cursor-pointer">
        {{ label }}
      </label>
      <p v-if="description" class="text-xs text-slate-500 mt-0.5">{{ description }}</p>
    </div>
    <button
      :id="toggleId"
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :disabled="disabled"
      :class="[
        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
        modelValue ? 'bg-brand-600' : 'bg-slate-200',
        disabled ? 'opacity-50 cursor-not-allowed' : '',
      ]"
      @click="!disabled && emit('update:modelValue', !modelValue)"
    >
      <span
        :class="[
          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform duration-200 ease-in-out',
          modelValue ? 'translate-x-5' : 'translate-x-0',
        ]"
      />
    </button>
  </div>
</template>
