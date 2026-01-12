<!-- AppSearchInput.vue -->
<template>
  <div>
    <label
      v-if="label"
      :for="id"
      class="bookando-label bookando-sr-only"
    >
      <slot name="label">{{ label }}</slot>
    </label>

    <div
      class="bookando-search-wrapper"
      :class="classes"
    >
      <span class="bookando-input-icon">
        <AppIcon
          name="search"
          :size="iconSize"
          alt="🔍"
        />
      </span>
      <input
        :id="id"
        type="search"
        class="bookando-search-input"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :aria-label="ariaLabel || label"
        :aria-invalid="!!error"
        :aria-describedby="ariaDescribedby"
        v-bind="$attrs"
        @input="onInput"
      >
    </div>

    <div
      v-if="hint"
      :id="hintId"
      class="bookando-text-muted bookando-mt-xs bookando-text-sm"
    >
      <slot name="hint">
        {{ hint }}
      </slot>
    </div>
    <div
      v-if="error"
      :id="errorId"
      class="form-error"
      role="alert"
    >
      <slot name="error">
        {{ error }}
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'

export interface AppSearchInputProps {
  modelValue?: string
  id?: string
  label?: string
  classes?: string
  placeholder?: string
  ariaLabel?: string
  hint?: string
  error?: string
  disabled?: boolean
  readonly?: boolean
  iconSize?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | number | string
}

const props = withDefaults(defineProps<AppSearchInputProps>(), {
  label: 'Suchen',
  placeholder: 'Suchen...',
  iconSize: 'md',
  classes: ''
})

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

const hintId = computed(() => props.id ? `${props.id}-hint` : undefined)
const errorId = computed(() => props.id ? `${props.id}-error` : undefined)

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
