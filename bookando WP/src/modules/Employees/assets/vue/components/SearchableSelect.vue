<template>
  <div ref="containerRef" class="relative">
    <div
      @click="isOpen = !isOpen"
      class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white flex justify-between items-center cursor-pointer focus:ring-2 focus:ring-brand-500 min-h-[38px]"
    >
      <span :class="selectedOption ? 'text-slate-800' : 'text-slate-400'">
        <slot v-if="selectedOption && $slots.option" name="option" :option="selectedOption">
          {{ selectedOption.label }}
        </slot>
        <span v-else-if="selectedOption">{{ selectedOption.label }}</span>
        <span v-else>{{ placeholder }}</span>
      </span>
      <ChevronDownIcon :size="16" class="text-slate-400" />
    </div>

    <div
      v-if="isOpen"
      class="absolute z-50 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-60 flex flex-col overflow-hidden"
    >
      <div class="p-2 border-b border-slate-100 bg-slate-50">
        <div class="relative">
          <SearchIconLucide :size="14" class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            ref="searchInputRef"
            v-model="search"
            type="text"
            class="w-full pl-8 pr-2 py-1 text-sm border border-slate-200 rounded focus:outline-none focus:border-brand-500"
            placeholder="Search..."
          />
        </div>
      </div>
      <div class="overflow-y-auto flex-1">
        <div
          v-for="opt in filteredOptions"
          :key="opt.value"
          @click="selectOption(opt.value)"
          :class="[
            'px-3 py-2 text-sm cursor-pointer hover:bg-slate-50 flex items-center justify-between',
            modelValue === opt.value ? 'bg-brand-50 text-brand-700' : 'text-slate-700'
          ]"
        >
          <slot v-if="$slots.option" name="option" :option="opt">
            <span>{{ opt.label }}</span>
          </slot>
          <span v-else>{{ opt.label }}</span>
          <CheckIcon v-if="modelValue === opt.value" :size="14" />
        </div>
        <div v-if="filteredOptions.length === 0" class="p-3 text-center text-xs text-slate-400">
          No results found
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { ChevronDown as ChevronDownIcon, Search as SearchIconLucide, Check as CheckIcon } from 'lucide-vue-next'

interface Option {
  value: string
  label: string
  subLabel?: string
}

interface Props {
  options: Option[]
  modelValue: string
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select...'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const isOpen = ref(false)
const search = ref('')
const containerRef = ref<HTMLElement>()
const searchInputRef = ref<HTMLInputElement>()

const filteredOptions = computed(() =>
  props.options.filter(opt =>
    opt.label.toLowerCase().includes(search.value.toLowerCase()) ||
    (opt.subLabel && opt.subLabel.toLowerCase().includes(search.value.toLowerCase()))
  )
)

const selectedOption = computed(() => props.options.find(o => o.value === props.modelValue))

const selectOption = (value: string) => {
  emit('update:modelValue', value)
  isOpen.value = false
  search.value = ''
}

const handleClickOutside = (event: MouseEvent) => {
  if (containerRef.value && !containerRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

watch(isOpen, async (newVal) => {
  if (newVal) {
    await nextTick()
    searchInputRef.value?.focus()
  }
})

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})
</script>
