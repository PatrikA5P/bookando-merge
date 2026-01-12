<!-- AnimatedNumber.vue - Animated Number Counter -->
<template>
  <span>{{ formattedValue }}</span>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'

export interface AnimatedNumberProps {
  value: number
  duration?: number
  format?: 'number' | 'currency' | 'percentage'
}

const props = withDefaults(defineProps<AnimatedNumberProps>(), {
  duration: 1000,
  format: 'number'
})

const displayValue = ref(0)
const isAnimating = ref(false)

const formattedValue = computed(() => {
  const roundedValue = Math.round(displayValue.value)

  switch (props.format) {
    case 'currency':
      return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'EUR'
      }).format(roundedValue)

    case 'percentage':
      return `${roundedValue}%`

    case 'number':
    default:
      return new Intl.NumberFormat().format(roundedValue)
  }
})

function animate(target: number) {
  if (isAnimating.value) return

  isAnimating.value = true
  const start = displayValue.value
  const change = target - start
  const startTime = performance.now()

  const step = (currentTime: number) => {
    const elapsed = currentTime - startTime
    const progress = Math.min(elapsed / props.duration, 1)

    // Easing function (ease-out cubic)
    const easeOut = 1 - Math.pow(1 - progress, 3)

    displayValue.value = start + (change * easeOut)

    if (progress < 1) {
      requestAnimationFrame(step)
    } else {
      displayValue.value = target
      isAnimating.value = false
    }
  }

  requestAnimationFrame(step)
}

watch(() => props.value, (newValue) => {
  animate(newValue)
})

onMounted(() => {
  animate(props.value)
})
</script>
