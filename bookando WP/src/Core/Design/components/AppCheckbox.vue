<!-- AppCheckbox.vue -->
<template>
  <div
    class="bookando-checkbox-wrapper"
    :class="alignClass"
  >
    <label
      :for="id"
      class="bookando-checkbox-label"
    >
      <input
        :id="id"
        :name="name"
        type="checkbox"
        :class="[
          'bookando-checkbox',
          sizeClass,
          { 'bookando-checkbox--readonly': readonly, 'bookando-checkbox--danger': !!error }
        ]"
        :checked="modelValue"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :aria-checked="modelValue"
        :aria-disabled="disabled"
        :aria-readonly="readonly"
        :aria-required="required"
        @change="onChange"
      >
      <span class="bookando-checkbox-custom">
        <AppIcon
          v-if="modelValue"
          name="check"
          class="bookando-checkbox-checkmark"
        />
      </span>
      <span
        v-if="label"
        class="bookando-label-text"
      >
        <slot name="label">{{ label }}</slot>
        <span
          v-if="required"
          class="bookando-text-danger"
        >*</span>
      </span>
    </label>
    <div
      v-if="hint"
      class="bookando-text-muted bookando-ml-xs bookando-text-sm"
    >
      <slot name="hint">
        {{ hint }}
      </slot>
    </div>
    <div
      v-if="error"
      class="form-error bookando-ml-xs"
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

const props = defineProps({
  modelValue: Boolean,
  id: String,
  name: String,
  label: String,
  hint: String,
  error: String,
  disabled: Boolean,
  readonly: Boolean,
  required: Boolean,
  size: { type: String, default: 'md' },
  /** ✅ NEU: Ausrichtung der Checkbox im Wrapper */
  align: { type: String as () => 'left' | 'center' | 'right', default: 'center' }
})

const emit = defineEmits(['update:modelValue'])

const sizeClass = computed(() =>
  props.size === 'sm' ? 'bookando-checkbox--sm'
    : props.size === 'lg' ? 'bookando-checkbox--lg'
    : ''
)

/** CSS-Klasse für Alignment */
const alignClass = computed(() => `bookando-checkbox--align-${props.align}`)

function onChange(event: Event) {
  if (!props.readonly && !props.disabled) {
    emit('update:modelValue', (event.target as HTMLInputElement).checked)
  }
}
</script>
