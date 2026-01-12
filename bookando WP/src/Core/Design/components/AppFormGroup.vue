<!-- AppFormGroup.vue -->
<template>
  <div :class="formGroupClass">
    <label
      v-if="label"
      :for="id"
      class="bookando-label"
    >
      <slot name="label">{{ label }}</slot><span>:</span>
      <span
        v-if="required"
        class="bookando-text-danger"
      >*</span>
    </label>
    <slot />
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

export interface AppFormGroupProps {
  label?: string
  id?: string
  required?: boolean
  hint?: string
  error?: string
  row?: boolean
  classes?: string
}

const props = defineProps<AppFormGroupProps>()

const formGroupClass = computed(() =>
  [
    'bookando-form-group',
    props.row && 'bookando-form-group--row',
    props.classes
  ].filter(Boolean).join(' ')
)

const hintId = computed(() => props.id ? `${props.id}-hint` : undefined)
const errorId = computed(() => props.id ? `${props.id}-error` : undefined)
</script>
