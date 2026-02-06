<script setup lang="ts">
/**
 * BSearchBar — Einheitliche Such-Komponente
 *
 * Konsistente Suche über alle Module.
 * Debounced Input, Escape zum Leeren.
 */
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { INPUT_STYLES } from '@/design';

const props = withDefaults(defineProps<{
  modelValue?: string;
  placeholder?: string;
  debounce?: number;
  autofocus?: boolean;
}>(), {
  modelValue: '',
  placeholder: 'Suchen...',
  debounce: 300,
  autofocus: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
}>();

const inputRef = ref<HTMLInputElement>();
const localValue = ref(props.modelValue);
let debounceTimer: ReturnType<typeof setTimeout>;

watch(() => props.modelValue, (val) => {
  localValue.value = val;
});

function onInput(event: Event) {
  const val = (event.target as HTMLInputElement).value;
  localValue.value = val;

  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    emit('update:modelValue', val);
  }, props.debounce);
}

function clear() {
  localValue.value = '';
  emit('update:modelValue', '');
  inputRef.value?.focus();
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && localValue.value) {
    clear();
  }
}

onMounted(() => {
  if (props.autofocus) inputRef.value?.focus();
});

onUnmounted(() => {
  clearTimeout(debounceTimer);
});
</script>

<template>
  <div class="relative">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
    <input
      ref="inputRef"
      type="search"
      :value="localValue"
      :placeholder="placeholder"
      :class="INPUT_STYLES.search"
      @input="onInput"
      @keydown="onKeydown"
    />
    <button
      v-if="localValue"
      class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 rounded text-slate-400 hover:text-slate-600 transition-colors"
      aria-label="Suche leeren"
      @click="clear"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>
