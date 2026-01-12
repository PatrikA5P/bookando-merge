<!-- AppColorInput.vue -->
<template>
  <AppFormGroup
    :label="label"
    :required="required"
    :hint="hint"
    :error="error"
    :for="id"
  >
    <input
      :id="id"
      type="color"
      :name="name"
      class="bookando-control"
      :value="modelValue"
      :disabled="disabled"
      :required="required"
      :aria-invalid="!!error"
      v-bind="$attrs"
      @input="$emit('update:modelValue', $event.target.value)"
    >
  </AppFormGroup>
</template>

<script setup lang="ts">
import AppFormGroup from './AppFormGroup.vue'

const props = defineProps({
  modelValue: String,
  label: String,
  id: String,
  name: String,
  hint: String,
  error: String,
  disabled: Boolean,
  required: Boolean,
})
defineEmits(['update:modelValue'])
</script>
