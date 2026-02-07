<script setup lang="ts">
/**
 * BMoneyInput — Eingabefeld fuer Geldbetraege
 *
 * Zeigt dem User Major Units (z.B. "85.00"), speichert aber intern
 * Minor Units (z.B. 8500) gemaess Money-Utility.
 *
 * Usage:
 *   <BMoneyInput
 *     v-model="priceCents"
 *     label="Preis"
 *     currency="CHF"
 *   />
 */
import { ref, watch, computed } from 'vue';
import { toMajorUnits, parseMoneyInput } from '@/utils/money';
import { INPUT_STYLES, LABEL_STYLES } from '@/design';

const props = withDefaults(defineProps<{
  modelValue: number; // Minor Units (Integer)
  label?: string;
  currency?: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
  hint?: string;
  min?: number; // Minor Units
  max?: number; // Minor Units
}>(), {
  currency: 'CHF',
  placeholder: '0.00',
  required: false,
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void;
}>();

// Internal display value (Major Units als String)
const displayValue = ref(formatDisplay(props.modelValue));

function formatDisplay(minorAmount: number): string {
  if (minorAmount === 0) return '';
  return toMajorUnits(minorAmount).toFixed(2);
}

// Sync von aussen → Display
watch(() => props.modelValue, (newVal) => {
  const currentParsed = parseMoneyInput(displayValue.value);
  // Nur updaten wenn der Wert von aussen anders ist
  if (currentParsed !== newVal) {
    displayValue.value = formatDisplay(newVal);
  }
});

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement;
  displayValue.value = target.value;
}

function handleBlur() {
  const parsed = parseMoneyInput(displayValue.value);
  if (parsed !== null) {
    // Clamp to min/max
    let value = parsed;
    if (props.min !== undefined && value < props.min) value = props.min;
    if (props.max !== undefined && value > props.max) value = props.max;

    emit('update:modelValue', value);
    displayValue.value = formatDisplay(value);
  } else if (displayValue.value.trim() === '') {
    emit('update:modelValue', 0);
    displayValue.value = '';
  }
}

const hasError = computed(() => !!props.error);
</script>

<template>
  <div>
    <label v-if="label" :class="required ? LABEL_STYLES.required : LABEL_STYLES.base">
      {{ label }}
    </label>
    <div class="relative">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500 font-medium pointer-events-none">
        {{ currency }}
      </span>
      <input
        type="text"
        inputmode="decimal"
        :class="[hasError ? INPUT_STYLES.error : INPUT_STYLES.base, 'pl-12 text-right']"
        :value="displayValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        @input="handleInput"
        @blur="handleBlur"
      />
    </div>
    <p v-if="error" :class="LABEL_STYLES.error">{{ error }}</p>
    <p v-else-if="hint" :class="LABEL_STYLES.hint">{{ hint }}</p>
  </div>
</template>
