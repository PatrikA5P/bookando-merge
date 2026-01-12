<!-- AppAccordion.vue -->
<template>
  <div class="bookando-accordion">
    <div
      class="accordion-header"
      role="button"
      :aria-expanded="open.toString()"
      tabindex="0"
      @click="toggle"
      @keydown.enter.prevent="toggle"
      @keydown.space.prevent="toggle"
    >
      <slot name="header" />
    </div>
    <div
      v-show="open"
      class="accordion-body"
      role="region"
    >
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, defineProps, defineEmits } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue'])

const open = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  open.value = val
})

function toggle() {
  open.value = !open.value
  emit('update:modelValue', open.value)
}
</script>
