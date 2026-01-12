<!-- AppRangeInput.vue -->
<template>
  <AppFormGroup
    :label="label"
    :required="required"
    :hint="hint"
    :error="error"
    :for="id"
  >
    <div class="bookando-slider">
      <div class="slider-label">
        <span><slot name="min-label">{{ minLabel }}</slot></span>
        <span class="slider-value">{{ modelValue }}</span>
        <span><slot name="max-label">{{ maxLabel }}</slot></span>
      </div>
      <input
        :id="id"
        type="range"
        :name="name"
        class="bookando-control"
        :min="min"
        :max="max"
        :step="step"
        :value="modelValue"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :aria-valuemin="min"
        :aria-valuemax="max"
        :aria-valuenow="modelValue"
        :aria-invalid="!!error"
        :class="{
          'bookando-control--danger': !!error,
          'bookando-control--readonly': readonly
        }"
        v-bind="$attrs"
        @input="$emit('update:modelValue', Number($event.target.value))"
      >
    </div>
  </AppFormGroup>
</template>

<script setup lang="ts">
import AppFormGroup from './AppFormGroup.vue'

const props = defineProps({
  modelValue: [String, Number],
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  step: { type: Number, default: 1 },
  label: String,
  minLabel: String,
  maxLabel: String,
  id: String,
  name: String,
  hint: String,
  error: String,
  disabled: Boolean,
  readonly: Boolean,
  required: Boolean,
})

defineEmits(['update:modelValue'])
</script>
