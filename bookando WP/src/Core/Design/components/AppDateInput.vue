<!-- AppDateInput.vue -->
<template>
  <AppDatepicker
    :id="id"
    :name="name"
    :placeholder="placeholder"
    :format="displayFormat"
    :clearable="clearable"
    :editable="computedEditable"
    :disabled="disabled"
    :max-date="maxDate"
    :min-date="minDate"
    :multi-dates="multiDates"
    :auto-apply="autoApply"
    :model-type="'Date'"
    :type="'date'"
    :model-value="parsedModel"
    :extra-props="mergedExtraProps"
    @update:model-value="onUpdate"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import dayjs from 'dayjs'
import AppDatepicker from './AppDatepicker.vue'

const props = defineProps({
  modelValue: [String, Date],
  id: String,
  name: String,
  placeholder: String,
  // Standard-Form-Props (konsistent)
  label: String,
  hint: String,
  error: String,
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  // Datepicker-Props
  format: { type: String, default: 'dd.MM.yyyy' },
  clearable: { type: Boolean, default: true },
  editable: { type: Boolean, default: undefined },
  maxDate: [String, Date],
  minDate: [String, Date],
  multiDates: { type: Boolean, default: false },
  autoApply: { type: Boolean, default: true },
  extraProps: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:modelValue'])

const displayFormat = computed(() => props.format || 'dd.MM.yyyy')
const computedEditable = computed(() =>
  typeof props.editable === 'boolean' ? props.editable : !props.readonly
)

const parsedModel = computed<Date | null>(() => {
  if (!props.modelValue) return null
  if (props.modelValue instanceof Date) return props.modelValue
  const d = dayjs(props.modelValue, ['YYYY-MM-DD', dayjs.ISO_8601], true)
  return d.isValid() ? d.toDate() : null
})

const mergedExtraProps = computed(() => ({
  'aria-invalid': !!props.error,
  ...props.extraProps
}))

function onUpdate(val: Date | null) {
  const out = val ? dayjs(val).format('YYYY-MM-DD') : ''
  emit('update:modelValue', out)
}
</script>
