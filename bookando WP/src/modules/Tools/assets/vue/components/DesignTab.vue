<template>
  <div class="p-6 space-y-6">
    <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.tools.design_customization') }}</h3>

    <div class="bg-white p-6 rounded-xl border border-slate-200">
      <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.color_scheme') }}</h4>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="color in colorSchemes" :key="color.name" @click="selectedColor = color.name" :class="['p-4 rounded-lg border-2 cursor-pointer transition-all', selectedColor === color.name ? 'border-brand-600 ring-2 ring-brand-200' : 'border-slate-200 hover:border-slate-300']">
          <div class="w-full h-12 rounded mb-2" :style="`background: ${color.primary}`"></div>
          <div class="text-sm font-medium text-center">{{ color.name }}</div>
        </div>
      </div>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200">
      <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.logo_branding') }}</h4>
      <div class="flex items-center gap-4">
        <div class="w-24 h-24 bg-slate-100 rounded-lg flex items-center justify-center border-2 border-dashed border-slate-300">
          <span class="text-xs text-slate-400">Logo</span>
        </div>
        <button class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
          {{ $t('mod.tools.upload_logo') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
const { t: $t } = useI18n()

const selectedColor = ref('Blue')
const colorSchemes = ref([
  { name: 'Blue', primary: '#3b82f6' },
  { name: 'Green', primary: '#10b981' },
  { name: 'Purple', primary: '#8b5cf6' },
  { name: 'Red', primary: '#ef4444' }
])
</script>
