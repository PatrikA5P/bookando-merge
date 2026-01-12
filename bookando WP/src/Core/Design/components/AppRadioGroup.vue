<!-- AppRadioGroup.vue -->

<!--
  ✅ Features
  - Orientierung: horizontal (mit Wrap) oder vertikal
  - Optik: runde oder eckige Box, Markierung als "check" (✓), "dot" (●) oder "fill" (volle Fläche)
  - Größen: sm | md | lg
  - Option-API: optionLabel / optionValue (wie bei Select/Dropdown)
  - A11y: <div role="radiogroup"> + native <input type="radio"> (Tastatur & Screenreader)

  💡 Hinweise
  - Es bleibt ein *Radio* (Single-Choice). Die Checkbox-Optik ist rein visuell.
  - Für Multiple-Choice nutze weiterhin AppCheckbox bzw. die Choice-Variante in AppSelect/AppDropdown.
  -->

<template>
  <AppFormGroup
    :id="id"
    :label="label"
    :hint="hint"
    :error="error"
    :required="required"
  >
    <div
      class="bookando-radio-group"
      :class="[
        orientationClass,
        sizeClass,
        { 'no-wrap': !wrap, 'is-disabled': disabled }
      ]"
      role="radiogroup"
      :aria-labelledby="id ? id + '-legend' : undefined"
    >
      <label
        v-for="(opt, i) in options"
        :key="i"
        class="bookando-radio"
        :class="{ 'bookando-radio--disabled': disabled || !!opt?.disabled }"
      >
        <!-- Visually hidden, aber zugänglich -->
        <input
          :id="id ? id + '-' + i : undefined"
          class="bookando-radio__input"
          type="radio"
          :name="groupName"
          :value="valueOf(opt)"
          :checked="isChecked(opt)"
          :disabled="disabled || !!opt?.disabled"
          :required="required"
          :aria-checked="isChecked(opt)"
          :aria-disabled="disabled || !!opt?.disabled"
          :aria-labelledby="id ? id + '-label-' + i : undefined"
          @change="onChange(valueOf(opt))"
        >

        <!-- Box + Mark (Optik steuerbar via shape/mark) -->
        <span
          class="bookando-radio__box"
          :class="[ shapeClass, markClass ]"
          aria-hidden="true"
        >
          <span class="bookando-radio__mark" />
        </span>

        <!-- Label -->
        <span
          :id="id ? id + '-label-' + i : undefined"
          class="bookando-radio__label"
        >
          {{ labelOf(opt) }}
        </span>
      </label>
    </div>
  </AppFormGroup>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppFormGroup from './AppFormGroup.vue'

type Orientation = 'horizontal' | 'vertical'
type Shape = 'square' | 'round'
type Mark = 'check' | 'dot' | 'fill'

const props = defineProps({
  modelValue: [String, Number],
  options: { type: Array as () => any[], default: () => [] },

  // Option-Mapping (wie bei Select/Dropdown)
  optionLabel: { type: String, default: 'label' },
  optionValue: { type: String, default: 'value' },

  // FormGroup & A11y
  label: String,
  id: String,
  name: String,
  hint: String,
  error: String,
  required: Boolean,

  // Zustand/Layout
  disabled: Boolean,
  size: { type: String as () => 'sm'|'md'|'lg', default: 'md' },

  // ✅ NEU: Ausrichtung & Wrap
  orientation: { type: String as () => Orientation, default: 'horizontal' },
  wrap: { type: Boolean, default: true },

  // ✅ NEU: Optik der Box/Markierung
  shape: { type: String as () => Shape, default: 'round' }, // 'square' → eckig
  mark:  { type: String as () => Mark,  default: 'dot' }    // 'check' | 'dot' | 'fill'
})

const emit = defineEmits(['update:modelValue'])

/* ------ Helpers: Label/Value/Checked ------ */
function valueOf(opt: any) {
  return typeof opt === 'object' ? opt?.[props.optionValue] ?? opt?.code ?? String(opt) : opt
}
function labelOf(opt: any) {
  return typeof opt === 'object' ? opt?.[props.optionLabel] ?? opt?.label ?? opt?.name ?? String(opt) : String(opt)
}
function isChecked(opt: any) {
  return props.modelValue === valueOf(opt)
}

/* ------ Name fürs Radiogroup ------ */
const groupName = computed(() =>
  props.name || props.id || `radio-${Math.random().toString(36).slice(2, 8)}`
)

/* ------ Klassen ------ */
const sizeClass = computed(() =>
  props.size === 'sm' ? 'is-sm'
    : props.size === 'lg' ? 'is-lg'
    : 'is-md'
)
const orientationClass = computed(() =>
  props.orientation === 'vertical' ? 'is-vertical' : 'is-horizontal'
)
const shapeClass = computed(() => (props.shape === 'square' ? 'is-square' : 'is-round'))
const markClass  = computed(() => `mark-${props.mark}`)

/* ------ Events ------ */
function onChange(val: string | number) {
  if (!props.disabled) emit('update:modelValue', val)
}
</script>

<style scoped>
/* Design-Token Defaults (über CSS-Variablen überschreibbar) */
.bookando-radio-group {
  --radio-color: var(--bookando-primary, #2563eb);
  --radio-border: var(--bookando-border, #e5e7eb);
  --radio-bg: #fff;
  --radio-size: 18px;
  --radio-radius: 9999px; /* round */
  --gap: .625rem;

  display: flex;
  gap: var(--gap);
  align-items: center;
}

/* Ausrichtung */
.bookando-radio-group.is-horizontal { flex-wrap: wrap; }
.bookando-radio-group.is-horizontal.no-wrap { flex-wrap: nowrap; }
.bookando-radio-group.is-vertical { flex-direction: column; align-items: flex-start; }

/* Größen */
.bookando-radio-group.is-sm { --radio-size: 16px; }
.bookando-radio-group.is-md { --radio-size: 18px; }
.bookando-radio-group.is-lg { --radio-size: 20px; }

/* Einzelnes Item */
.bookando-radio {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  cursor: pointer;
  user-select: none;
}
.bookando-radio--disabled { opacity: .55; cursor: not-allowed; }

/* Visually hidden Input – bleibt fokussierbar über Label-Klick */
.bookando-radio__input {
  position: absolute;
  inline-size: 1px;
  block-size: 1px;
  opacity: 0;
  pointer-events: none;
}

/* Box + Mark */
.bookando-radio__box {
  inline-size: var(--radio-size);
  block-size: var(--radio-size);
  border: 1.75px solid var(--radio-border);
  border-radius: var(--radio-radius);
  background: var(--radio-bg);
  display: inline-grid;
  place-items: center;
  transition: all .15s ease;
}
.bookando-radio__box.is-square { border-radius: .375rem; } /* eckig */

/* Mark: Grundzustand unsichtbar (außer bei fill) */
.bookando-radio__mark { transition: opacity .12s ease; opacity: 0; }

/* Mark-Varianten */
.bookando-radio__box.mark-check .bookando-radio__mark {
  inline-size: 6px; block-size: 11px;
  border-right: 2px solid #fff;
  border-bottom: 2px solid #fff;
  transform: rotate(45deg);
}
.bookando-radio__box.mark-dot .bookando-radio__mark {
  inline-size: 8px; block-size: 8px;
  border-radius: 9999px;
  background: var(--radio-color);
}
.bookando-radio__box.mark-fill .bookando-radio__mark { display: none; }

/* Checked-State via Sibling-Selektor */
.bookando-radio__input:checked + .bookando-radio__box {
  border-color: var(--radio-color);
}
.bookando-radio__input:checked + .bookando-radio__box.mark-fill {
  background: var(--radio-color);
}
.bookando-radio__input:checked + .bookando-radio__box.mark-check .bookando-radio__mark,
.bookando-radio__input:checked + .bookando-radio__box.mark-dot   .bookando-radio__mark {
  opacity: 1;
}

/* Label-Text */
.bookando-radio__label { line-height: 1.1; }
</style>
