<!-- AppInputText.vue -->
<template>
  <input
    :id="id"
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :required="required"
    :aria-invalid="!!error"
    :aria-describedby="ariaDescribedby"
    :class="[
      'bookando-control',
      sizeClass,
      { 'bookando-control--danger': !!error }
    ]"
    v-bind="$attrs"
    @input="onInput"
  >
</template>

<script setup lang="ts">
import { computed } from 'vue'

export interface AppInputTextProps {
  modelValue?: string
  id?: string
  type?: 'text' | 'email' | 'password' | 'tel' | 'url' | 'search' | 'number'
  placeholder?: string
  disabled?: boolean
  required?: boolean
  error?: string
  hint?: string
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<AppInputTextProps>(), {
  modelValue: '',
  type: 'text',
  size: 'md'
})

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'bookando-control--sm'
    case 'lg': return 'bookando-control--lg'
    default: return ''
  }
})

const ariaDescribedby = computed(() => {
  const ids: string[] = []
  if (props.error && props.id) ids.push(`${props.id}-error`)
  if (props.hint && props.id) ids.push(`${props.id}-hint`)
  return ids.length > 0 ? ids.join(' ') : undefined
})

function onInput(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>
