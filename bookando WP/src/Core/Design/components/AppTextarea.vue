<!-- AppTextarea.vue -->
<template>
  <textarea
    :id="id"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    :aria-invalid="!!error"
    :aria-describedby="ariaDescribedby"
    :class="[
      'bookando-control',
      sizeClass,
      {
        'bookando-control--danger': !!error,
        'bookando-control--readonly': readonly,
      }
    ]"
    rows="4"
    v-bind="$attrs"
    @input="onInput"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'

export interface AppTextareaProps {
  modelValue?: string
  label?: string
  id?: string
  placeholder?: string
  hint?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  error?: string
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<AppTextareaProps>(), {
  modelValue: '',
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
  if (props.disabled || props.readonly) return
  const el = event.target as HTMLTextAreaElement
  emit('update:modelValue', el.value)
}
</script>
