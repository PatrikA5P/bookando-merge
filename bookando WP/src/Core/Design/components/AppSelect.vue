<!-- AppSelect.vue -->
<template>
  <AppFormGroup
    :id="id"
    :label="label"
    :required="required"
    :hint="hint"
    :error="error"
  >
    <select
      :id="id"
      :multiple="multiple"
      :disabled="disabled"
      :required="required"
      :aria-invalid="!!error"
      :aria-multiselectable="multiple ? 'true' : undefined"
      :class="[
        'bookando-control',
        sizeClass,
        { 'bookando-control--danger': !!error, 'bookando-control--readonly': readonly }
      ]"
      v-bind="$attrs"
      :tabindex="readonly ? -1 : undefined"
      :style="readonly ? 'pointer-events:none' : ''"
      @change="onChange"
    >
      <!-- Leere Option explizit -->
      <option
        v-if="allowEmpty && !multiple"
        :value="emptyValue"
        :selected="modelValue === emptyValue"
      >
        {{ placeholder || t('bookando.select_empty', 'Bitte auswählen...') }}
      </option>
      <!-- Optionen -->
      <option
        v-for="opt in options"
        :key="resolveValue(opt)"
        :value="resolveValue(opt)"
        :selected="isSelected(opt)"
      >
        {{ formatLabel(opt) }}
      </option>
    </select>
  </AppFormGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppFormGroup from './AppFormGroup.vue'

const props = defineProps({
  modelValue: [String, Number, Array],
  options: { type: Array, default: () => [] },
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },
  label: String,
  id: String,
  placeholder: String,
  hint: String,
  multiple: Boolean,
  mode: { type: String, default: 'basic' }, // basic | flag-label | flag-label-dial
  allowEmpty: { type: Boolean, default: true },
  emptyValue: { type: [String, Number], default: '' },
  disabled: Boolean,
  readonly: Boolean,
  required: Boolean,
  error: String,
  size: { type: String, default: 'md' }
})

const emit = defineEmits(['update:modelValue'])
const { t } = useI18n()

const sizeClass = computed(() =>
  props.size === 'sm' ? 'bookando-control--sm'
    : props.size === 'lg' ? 'bookando-control--lg'
    : ''
)

// Hilfsfunktionen
function resolveValue(opt: any): string | number {
  return typeof opt === 'object' ? opt?.[props.optionValue] ?? opt?.code ?? String(opt) : opt
}
function resolveLabel(opt: any): string {
  return typeof opt === 'object' ? opt?.[props.optionLabel] ?? opt?.label ?? opt?.name ?? String(opt) : String(opt)
}
function formatLabel(opt: any): string {
  const label = resolveLabel(opt)
  const flag = opt?.flag ?? ''
  const dial = opt?.dial_code ?? ''
  if (props.mode === 'flag-label-dial') return `${flag} ${label} ${dial}`.trim()
  if (props.mode === 'flag-label') return `${flag} ${label}`.trim()
  return label
}
function isSelected(opt: any): boolean {
  const val = resolveValue(opt)
  return props.multiple
    ? Array.isArray(props.modelValue) && props.modelValue.includes(val)
    : props.modelValue === val
}

function onChange(event: Event) {
  const target = event.target as HTMLSelectElement
  if (props.disabled || props.readonly) return
  if (props.multiple) {
    const selected = Array.from(target.selectedOptions).map(o => o.value)
    emit('update:modelValue', selected)
  } else {
    emit('update:modelValue', target.value)
  }
}
</script>
