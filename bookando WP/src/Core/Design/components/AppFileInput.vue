<!-- AppFileInput.vue -->
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
      type="file"
      :name="name"
      class="bookando-control"
      :disabled="disabled"
      :required="required"
      :multiple="multiple"
      :aria-invalid="!!error"
      v-bind="$attrs"
      @change="onChange"
    >
  </AppFormGroup>
</template>

<script setup lang="ts">
import AppFormGroup from './AppFormGroup.vue'

const props = defineProps({
  modelValue: [File, Array] as unknown as File | File[],
  label: String,
  id: String,
  name: String,
  hint: String,
  error: String,
  disabled: Boolean,
  required: Boolean,
  multiple: Boolean
})

const emit = defineEmits(['update:modelValue'])

function onChange(event: Event) {
  const input = event.target as HTMLInputElement
  if (props.multiple) {
    emit('update:modelValue', input.files ? Array.from(input.files) : [])
  } else {
    emit('update:modelValue', input.files ? input.files[0] : null)
  }
}
</script>
