<template>
  <div v-if="type === 'Off'" class="text-xs text-slate-300 text-center py-1">OFF</div>
  <div v-else :class="['flex flex-col items-center justify-center px-1 py-1.5 rounded border text-[10px] font-medium cursor-pointer hover:brightness-95 transition-all h-full', badgeClass]">
    <div class="flex items-center gap-1 mb-0.5">
      <component :is="iconComponent" :size="12" />
      {{ type }}
    </div>
    <div>{{ start }}-{{ end }}</div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Sun as SunIcon, Sunset as SunsetIcon, Moon as MoonIcon } from 'lucide-vue-next'

interface Props {
  type: 'Early' | 'Late' | 'Night' | 'Off'
  start: string
  end: string
}

const props = defineProps<Props>()

const badgeClass = computed(() => {
  switch (props.type) {
    case 'Early': return 'bg-sky-50 text-sky-700 border-sky-200'
    case 'Late': return 'bg-amber-50 text-amber-700 border-amber-200'
    case 'Night': return 'bg-indigo-50 text-indigo-700 border-indigo-200'
    default: return 'bg-slate-100 text-slate-700 border-slate-200'
  }
})

const iconComponent = computed(() => {
  switch (props.type) {
    case 'Early': return SunIcon
    case 'Late': return SunsetIcon
    case 'Night': return MoonIcon
    default: return SunIcon
  }
})
</script>
