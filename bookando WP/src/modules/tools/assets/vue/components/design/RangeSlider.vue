<template>
  <div class="range-slider">
    <label
      v-if="label"
      class="range-label"
    >{{ label }}</label>
    <div class="range-control">
      <input
        type="range"
        :min="min"
        :max="max"
        :step="step"
        :value="modelValue"
        class="range-input"
        @input="handleInput"
      >
      <input
        type="number"
        :min="min"
        :max="max"
        :step="step"
        :value="modelValue"
        class="range-number"
        @input="handleNumberInput"
      >
      <span
        v-if="unit"
        class="range-unit"
      >{{ unit }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  modelValue: number
  min?: number
  max?: number
  step?: number
  label?: string
  unit?: string
}>(), {
  min: 0,
  max: 100,
  step: 1
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void
}>()

const handleInput = (event: Event) => {
  const value = parseFloat((event.target as HTMLInputElement).value)
  emit('update:modelValue', value)
}

const handleNumberInput = (event: Event) => {
  const value = parseFloat((event.target as HTMLInputElement).value)
  if (!isNaN(value)) {
    emit('update:modelValue', Math.min(Math.max(value, props.min), props.max))
  }
}
</script>

<style scoped>
.range-slider {
  margin-bottom: 1rem;
}

.range-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.5rem;
}

.range-control {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.range-input {
  flex: 1;
  height: 6px;
  border-radius: 3px;
  background: #e5e7eb;
  outline: none;
  appearance: none;
}

.range-input::-webkit-slider-thumb {
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #2271b1;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.range-input::-moz-range-thumb {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #2271b1;
  cursor: pointer;
  border: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.range-input:focus::-webkit-slider-thumb {
  box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.2);
}

.range-input:focus::-moz-range-thumb {
  box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.2);
}

.range-number {
  width: 70px;
  padding: 0.375rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
  text-align: center;
}

.range-number:focus {
  outline: none;
  border-color: #2271b1;
  box-shadow: 0 0 0 1px #2271b1;
}

.range-unit {
  font-size: 0.875rem;
  color: #6b7280;
  min-width: 30px;
}
</style>
