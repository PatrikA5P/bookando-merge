<!-- AppDatepicker.vue -->
<template>
  <VueDatePicker
    v-bind="extraProps"
    :id="id"
    v-model="innerValue"
    :name="name"
    :placeholder="placeholder"
    :locale="locale"
    :format="displayFormat"
    :clearable="clearable"
    :editable="editable"
    :disabled="disabled"
    :max-date="maxDate"
    :min-date="minDate"
    :multi-dates="multiDates"
    :range="isRange"
    :time-picker="isTimeOnly"
    :enable-time-picker="hasDateAndTime"
    :auto-apply="autoApply"
    :model-type="modelType"
    :text-input="textInput"
    :text-input-options="textInputOptions"
    :input-debounce="inputDebounce"
    :range-separator="rangeSeparator"
    input-class="bookando-control"
    :select-text="t('ui.common.select')"
    :cancel-text="t('core.common.cancel')"
    :teleport="true"
    :hide-input-icon="hideInputIcon"
    @update:model-value="emitValue"
  >
    <template #input-icon>
      <AppIcon
        v-if="!hideInputIcon"
        :name="resolvedIcon"
        class="bookando-icon dp__input_icon"
        :aria-label="isTimeOnly ? (t('ui.a11y.time') || 'Uhr') : (t('ui.a11y.calendar') || 'Kalender')"
      />
    </template>

    <template #clear-icon="{ clear }">
      <AppIcon
        name="x"
        class="bookando-icon bookando-icon--clear bookando-cursor-pointer"
        :aria-label="t('ui.common.clear_field') || 'Feld leeren'"
        role="button"
        tabindex="0"
        @click.stop="clear"
        @keydown.enter.stop="clear"
        @keydown.space.prevent="clear"
      />
    </template>
  </VueDatePicker>
</template>

<script setup lang="ts">
/* lokal importieren – kein globales Register nötig */
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from './AppIcon.vue'

type PickerType =
  | 'date' | 'time' | 'datetime'
  | 'daterange' | 'timerange' | 'datetimerange'

const props = withDefaults(defineProps<{
  modelValue: any
  id?: string
  name?: string
  placeholder?: string

  /* Modus */
  type?: PickerType

  /* Anzeige-/I/O-Formate */
  format?: string
  modelType?: string  // z.B. 'yyyy-MM-dd' | 'date' | 'timestamp'
  rangeSeparator?: string

  /* UX */
  clearable?: boolean
  editable?: boolean
  disabled?: boolean
  autoApply?: boolean
  textInput?: boolean
  commitOn?: 'enter' | 'blur'
  inputDebounce?: number
  inputIcon?: string
  hideInputIcon?: boolean

  /* Limits/Mehrfach */
  maxDate?: string | number | Date
  minDate?: string | number | Date
  multiDates?: boolean

  /* Durchreichen beliebiger Props */
  extraProps?: Record<string, any>
}>(), {
  type: 'date',
  format: undefined,
  modelType: undefined,
  rangeSeparator: ' – ',

  clearable: true,
  editable: true,
  disabled: false,
  autoApply: true,
  textInput: true,
  commitOn: 'enter',
  inputDebounce: 400,
  inputIcon: undefined,
  hideInputIcon: false,

  multiDates: false,
  extraProps: () => ({})
})

const emit = defineEmits<{ (event: 'update:modelValue', value: any): void }>()
const { t, locale } = useI18n()

/* --------------------------------
   v-model <-> innerValue (einfach)
----------------------------------*/
const normalizeIn = (raw: any) => {
  if (Array.isArray(raw)) {
    const first = (raw[0] === '' || raw[0] == null) ? null : raw[0]
    const second = (raw[1] === '' || raw[1] == null) ? null : raw[1]
    if (!first && !second) return null
    return [first, second]
  }
  return raw === '' ? null : raw
}
const innerValue = ref<any>(normalizeIn(props.modelValue))
watch(() => props.modelValue, (value) => { innerValue.value = normalizeIn(value) })

function emitValue(value: any) {
  emit('update:modelValue', value)
}

/* --------------------------------
   Modus-Flags aus type
----------------------------------*/
const isRange       = computed(() => ['daterange', 'timerange', 'datetimerange'].includes(props.type!))
const isTimeOnly    = computed(() => ['time', 'timerange'].includes(props.type!))
const hasDateAndTime= computed(() => ['datetime', 'datetimerange'].includes(props.type!))

/* --------------------------------
   Anzeigeformat (Fallbacks)
----------------------------------*/
const displayFormat = computed(() => {
  if (props.format) return props.format
  if (isTimeOnly.value)      return 'HH:mm'
  if (hasDateAndTime.value)  return 'dd.MM.yyyy HH:mm'
  return 'dd.MM.yyyy'
})

/* --------------------------------
   Texteingabe-Optionen
----------------------------------*/
const textInputOptions = computed(() => ({
  format: displayFormat.value,
  rangeSeparator: props.rangeSeparator,
  enterSubmit: props.commitOn !== 'blur',
  openMenu: false
}))

/* --------------------------------
   Icon (auto, wenn nichts gesetzt)
----------------------------------*/
const resolvedIcon = computed(() => props.inputIcon || (isTimeOnly.value ? 'clock' : 'calendar'))

</script>
