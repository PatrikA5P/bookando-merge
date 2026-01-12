<!-- MultilingualLabelInput.vue - Component for editing multilingual labels -->
<template>
  <div class="multilingual-label-input">
    <div class="label-header">
      <label>{{ label }}</label>
      <button
        class="expand-btn"
        type="button"
        :title="expanded ? 'Sprachen ausblenden' : 'Sprachen anzeigen'"
        @click="expanded = !expanded"
      >
        {{ expanded ? '−' : '🌐' }}
      </button>
    </div>

    <div class="label-inputs">
      <!-- Default (always shown) -->
      <div class="input-row">
        <span class="lang-badge">Standard</span>
        <input
          type="text"
          :value="modelValue.default"
          :placeholder="`${label} (Standard)`"
          @input="updateLabel('default', ($event.target as HTMLInputElement).value)"
        >
      </div>

      <!-- German & English (shown when expanded) -->
      <template v-if="expanded">
        <div class="input-row">
          <span class="lang-badge">DE</span>
          <input
            type="text"
            :value="modelValue.de || ''"
            placeholder="Deutsch (optional)"
            @input="updateLabel('de', ($event.target as HTMLInputElement).value)"
          >
        </div>
        <div class="input-row">
          <span class="lang-badge">EN</span>
          <input
            type="text"
            :value="modelValue.en || ''"
            placeholder="English (optional)"
            @input="updateLabel('en', ($event.target as HTMLInputElement).value)"
          >
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { MultilingualLabel } from './types'

interface Props {
  label: string
  modelValue: MultilingualLabel
}

interface Emits {
  (e: 'update:modelValue', value: MultilingualLabel): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const expanded = ref(false)

const updateLabel = (key: keyof MultilingualLabel, value: string) => {
  const updated = { ...props.modelValue }
  if (key === 'default') {
    updated.default = value
  } else {
    if (value.trim() === '') {
      delete updated[key]
    } else {
      updated[key] = value
    }
  }
  emit('update:modelValue', updated)
}
</script>

<style lang="scss" scoped>
@use '@scss/variables' as *;

.multilingual-label-input {
  margin-bottom: $bookando-spacing-md;

  .label-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: $bookando-spacing-xs;

    label {
      font-size: 14px;
      color: $bookando-text-secondary;
      font-weight: 500;
    }

    .expand-btn {
      padding: 4px 8px;
      background: transparent;
      border: 1px solid $bookando-gray-300;
      border-radius: $bookando-radius-xs;
      cursor: pointer;
      font-size: 12px;
      color: $bookando-text-secondary;
      transition: all 0.2s;

      &:hover {
        background: $bookando-gray-100;
        border-color: $bookando-primary;
        color: $bookando-primary;
      }
    }
  }

  .label-inputs {
    display: flex;
    flex-direction: column;
    gap: $bookando-spacing-xs;

    .input-row {
      display: flex;
      align-items: center;
      gap: $bookando-spacing-xs;

      .lang-badge {
        min-width: 70px;
        padding: 6px 10px;
        background: $bookando-gray-100;
        border: 1px solid $bookando-gray-300;
        border-radius: $bookando-radius-xs;
        font-size: 11px;
        font-weight: 600;
        text-align: center;
        color: $bookando-text-secondary;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      input[type="text"] {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid $bookando-gray-300;
        border-radius: $bookando-radius-sm;
        background: $bookando-white;
        color: $bookando-text-primary;
        font-size: 14px;

        &:focus {
          outline: none;
          border-color: $bookando-primary;
        }

        &::placeholder {
          color: $bookando-text-tertiary;
          font-style: italic;
        }
      }
    }
  }
}
</style>
