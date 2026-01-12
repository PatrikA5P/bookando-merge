<!-- BookandoField.vue -->
<template>
  <AppFormGroup
    v-if="showFormGroup"
    :id="id"
    :label="label"
    :required="required"
    :hint="description || help"
    :error="error"
    :row="row"
    :classes="classes"
  >
    <template v-if="$slots.default">
      <slot />
    </template>

    <template v-else>
      <component
        :is="component"
        :model-value="localValue"
        v-bind="mergedProps"
        @update:model-value="updateValue"
      />
    </template>
  </AppFormGroup>

  <template v-else>
    <template v-if="$slots.default">
      <slot />
    </template>
    <component
      :is="component"
      v-else
      :model-value="localValue"
      v-bind="mergedProps"
      @update:model-value="updateValue"
    />
  </template>
</template>

<script setup lang="ts">
/**
 * BookandoField
 * - Reicht jetzt Date/Time-Sonder-Props explizit weiter:
 *   autoApply, textInput, commitOn, inputDebounce, autoCorrect,
 *   inputIcon, inputIconAriaLabel, hideInputIcon, rangeSeparator.
 * - D. h. du kannst direkt am <BookandoField> z. B. :autoApply="true" setzen,
 *   ohne extraProps.
 */
import { ref, watch, computed, useAttrs } from 'vue'
import { useI18n } from 'vue-i18n'
import AppFormGroup from './AppFormGroup.vue'

import AppInputText from './AppInputText.vue'
import AppTextarea from './AppTextarea.vue'
import AppSelect from './AppSelect.vue'
import AppDropdown from './AppDropdown.vue'
import AppPhoneInput from './AppPhoneInput.vue'
import AppRadioGroup from './AppRadioGroup.vue'
import AppCheckbox from './AppCheckbox.vue'
import AppToggle from './AppToggle.vue'
import AppFileInput from './AppFileInput.vue'
import AppColorInput from './AppColorInput.vue'
import AppSearchInput from './AppSearchInput.vue'

import { defineAsyncComponent } from 'vue'
import { countryOptions, languageOptions, genderOptions } from '@core/Design/domain/domainOptions'
const AsyncDatepicker = defineAsyncComponent(() => import('./AppDatepicker.vue'))

defineOptions({ inheritAttrs: false })
const attrs = useAttrs()

type AnyObj = Record<string, any>

const _props = withDefaults(defineProps<{
  modelValue: string | number | boolean | object | any[]
  type?: string
  id?: string
  name?: string
  label?: string

  /* Optionen */
  options?: any[]
  source?: 'countries' | 'languages' | 'genders' | string

  /* Bedienung/UX */
  placeholder?: string
  searchable?: boolean
  required?: boolean
  disabled?: boolean
  clearable?: boolean
  error?: string
  autocomplete?: string

  /* Optionen-Mapping */
  optionLabel?: string | ((opt: any) => string)
  optionValue?: string
  showFlag?: boolean
  multiple?: boolean
  mode?: string
  appearance?: string

  /* Hilfetexte, Icons, Layout (FormGroup) */
  description?: string
  help?: string
  prependIcon?: string
  appendIcon?: string
  grouped?: boolean
  row?: boolean
  classes?: string

  /* Popper/Teleport */
  zIndex?: number | string
  teleport?: boolean
  dropup?: boolean

  /* ---------- Date/Time Props (werden 1:1 an AppDatepicker weitergereicht) ---------- */
  format?: string
  editable?: boolean
  minDate?: string | Date | number
  maxDate?: string | Date | number
  multiDates?: boolean
  weekStart?: number
  modelType?: string

  /** Neu: direkte Steuerung ohne extraProps */
  autoApply?: boolean
  textInput?: boolean
  commitOn?: 'enter' | 'blur' | 'immediate'
  inputDebounce?: number
  autoCorrect?: boolean
  inputIcon?: string
  inputIconAriaLabel?: string
  hideInputIcon?: boolean
  rangeSeparator?: string

  /* Freie Extra-Props (bleibt möglich) */
  config?: AnyObj
  extraProps?: AnyObj

  /* Filtering/Ordering (Dropdowns) */
  include?: string[]
  exclude?: string[]
  orderFirst?: string[]
  sortBy?: 'label' | 'code'
  sortDir?: 'asc' | 'desc'
  optionsTransform?: (opts: any[]) => any[]
  dropdownSort?: 'asc' | 'desc' | false
}>(), {
  grouped: true,
  autocomplete: 'off',
  appearance: 'default',
  optionLabel: 'label',
  optionValue: 'value',
  mode: 'basic'
})

const props = _props
const emit = defineEmits<{ (event: 'update:modelValue', value: any): void }>()
const { locale } = useI18n()

/* v-model Puffer */
const localValue = ref(props.modelValue)
watch(() => props.modelValue, v => { localValue.value = v })
function updateValue(val: any) { localValue.value = val; emit('update:modelValue', val) }

/* Ziel-Komponente wählen */
const component = computed(() => {
  switch (props.type) {
    case 'textarea':       return AppTextarea
    case 'phone':          return AppPhoneInput
    case 'select':         return AppSelect
    case 'dropdown':       return AppDropdown
    case 'radio':          return AppRadioGroup
    case 'checkbox':       return AppCheckbox
    case 'toggle':         return AppToggle
    case 'file':           return AppFileInput
    case 'color':          return AppColorInput
    case 'date':           return AsyncDatepicker
    case 'time':           return AsyncDatepicker
    case 'datetime':       return AsyncDatepicker
    case 'daterange':      return AsyncDatepicker
    case 'timerange':      return AsyncDatepicker
    case 'datetimerange':  return AsyncDatepicker
    case 'search':         return AppSearchInput
    default:               return AppInputText
  }
})

/* Anzeige-Entscheide */
const showFormGroup = computed(() => {
  if (props.grouped === false) return false
  if (['search', 'filter', 'inline'].includes((props.type || ''))) return false
  return !!(props.label && props.label.trim() !== '')
})

const resolvedOptionLabel = computed(() => props.optionLabel ?? 'label')
const resolvedOptionValue = computed(() => {
  if (props.optionValue) return props.optionValue
  if (props.source === 'countries' || props.source === 'languages') return 'code'
  return 'value'
})
const resolvedShowFlag = computed<boolean>(() => {
  if (props.showFlag !== undefined && props.showFlag !== null) return !!props.showFlag
  return props.source === 'countries' || props.source === 'languages'
})
const resolvedMode = computed(() => props.mode ?? (resolvedShowFlag.value ? 'flag-label' : 'basic'))
const resolvedSearchable = computed<boolean | undefined>(() => props.searchable)
const resolvedClearable  = computed<boolean | undefined>(() => props.clearable)

/* Multi → Single Fallback */
watch(
  () => [props.multiple, props.modelValue] as const,
  ([multiple, v]) => {
    if (!multiple && Array.isArray(v)) {
      const next = v.length ? v[0] : null
      emit('update:modelValue', next)
    }
  },
  { immediate: true }
)

/* Options-Pipeline */
const labelOf = (opt: any) => {
  const ol = resolvedOptionLabel.value as any
  return typeof ol === 'function' ? ol(opt) : (opt?.[ol] ?? opt?.name ?? opt?.label ?? '')
}
const valueOf = (opt: any) => {
  const ov = resolvedOptionValue.value as any
  return opt?.[ov] ?? opt?.code ?? opt?.value ?? opt?.id ?? ''
}
const userCustomOrdering = computed(() =>
  !!(props.include?.length || props.exclude?.length || props.orderFirst?.length || props.optionsTransform || props.sortBy || props.sortDir)
)
const resolvedOptions = computed(() => {
  const base = props.options
    ? props.options.slice()
    : props.source === 'countries' ? countryOptions(locale.value)
    : props.source === 'languages' ? languageOptions(locale.value)
    : props.source === 'genders'   ? genderOptions(locale.value)
    : []
  if (!userCustomOrdering.value) return base.filter(Boolean)
  let opts = base.filter(Boolean)
  if (props.include?.length) {
    const allow = new Set(props.include.map(String))
    opts = opts.filter(o => allow.has(String(valueOf(o))))
  }
  if (props.exclude?.length) {
    const deny = new Set(props.exclude.map(String))
    opts = opts.filter(o => !deny.has(String(valueOf(o))))
  }
  if (props.orderFirst?.length) {
    const prio = new Map(props.orderFirst.map((c, i) => [String(c), i]))
    const BIG = 1e6
    opts.sort((a, b) => (prio.get(String(valueOf(a))) ?? BIG) - (prio.get(String(valueOf(b))) ?? BIG))
  }
  if (typeof props.optionsTransform === 'function') {
    const out = props.optionsTransform(opts.slice())
    if (Array.isArray(out)) opts = out
  }
  if (props.sortBy || props.sortDir) {
    const by  = props.sortBy ?? 'label'
    const dir = props.sortDir === 'desc' ? -1 : 1
    const collator = new Intl.Collator(locale.value, { sensitivity: 'base', numeric: true })
    const key = (o: any) => by === 'code' ? String(valueOf(o) ?? '') : String(labelOf(o) ?? '')
    opts.sort((a, b) => collator.compare(key(a), key(b)) * dir)
  }
  return opts
})

/* Props für das innere Feld */
const componentProps = computed<AnyObj>(() => {
  const shared: AnyObj = {
    id: props.id,
    name: props.name,
    label: showFormGroup.value ? undefined : props.label,
    placeholder: props.placeholder,
    required: props.required,
    disabled: props.disabled,
    autocomplete: props.autocomplete,
    error: props.error,
    prependIcon: props.prependIcon,
    appendIcon: props.appendIcon,
    zIndex: props.zIndex,
    teleport: props.teleport,
    dropup: props.dropup,
    appearance: props.appearance,
    clearable: resolvedClearable.value
  }

  const optionProps: AnyObj = {
    optionLabel: resolvedOptionLabel.value,
    optionValue: resolvedOptionValue.value,
    options: resolvedOptions.value,
    source: props.source,
    showFlag: resolvedShowFlag.value,
    multiple: props.multiple,
    mode: resolvedMode.value,
    searchable: resolvedSearchable.value
  }

  /* 🔁 Date/Time: alles sauber durchreichen */
  const dateProps: AnyObj = {
    type: props.type,          // 'date' | 'time' | 'datetime' | '...range'
    format: props.format,
    editable: props.editable ?? true,
    minDate: props.minDate,
    maxDate: props.maxDate,
    multiDates: props.multiDates,
    modelType: props.modelType ?? (
      ['date','daterange'].includes(props.type || '') ? 'yyyy-MM-dd' : undefined
    ),
    autoApply: props.autoApply,
    textInput: props.textInput,
    commitOn: props.commitOn,
    inputDebounce: props.inputDebounce,
    inputIcon: props.inputIcon,
    hideInputIcon: props.hideInputIcon,
    rangeSeparator: props.rangeSeparator
  }

  const extra = {
    ...(props.config || {}),
    ...(props.extraProps || {})
  }

  if (['select','dropdown','radio','checkbox'].includes((props.type || ''))) {
    const sortForInner =
      props.dropdownSort !== undefined
        ? props.dropdownSort
        : (userCustomOrdering.value ? false : undefined)
    return { ...shared, ...optionProps, sort: sortForInner, ...extra }
  }

  if (['date','time','datetime','daterange','timerange','datetimerange'].includes(props.type || '')) {
    return { ...shared, ...dateProps, ...extra }
  }

  return { ...shared, ...extra }
})

/* Attrs (unbekannte Props) + unsere Props zusammenführen.
   Reihenfolge: attrs zuerst, dann componentProps → unsere Props gewinnen. */
const mergedProps = computed(() => {
  return { ...(attrs as Record<string, any>), ...(componentProps.value as any) }
})
</script>
