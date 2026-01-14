<template>
  <!-- Minimal Module Stub - To be replaced with full ModuleLayout pattern -->
  <div class="flex flex-col min-h-screen bg-slate-50 p-6">
    <div class="max-w-7xl mx-auto w-full">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-purple-700 to-indigo-900 text-white p-8 rounded-xl shadow-lg mb-6">
        <div class="flex items-center gap-4 mb-3">
          <Package class="w-8 h-8" />
          <h1 class="text-2xl font-bold">{{ $t(titleKey) }}</h1>
        </div>
        <p class="text-white/80 text-sm">{{ $t(descriptionKey) }}</p>
      </div>

      <!-- Coming Soon Message -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
        <div class="max-w-md mx-auto">
          <Package class="w-16 h-16 mx-auto mb-4 text-slate-300" />
          <h2 class="text-xl font-bold text-slate-800 mb-2">{{ $t('common.coming_soon') }}</h2>
          <p class="text-slate-600 mb-6">
            {{ $t('common.module_in_development') }}
          </p>
          <div class="text-xs text-slate-400">
            {{ $t('common.module_name') }}: {{ moduleName }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Package } from 'lucide-vue-next'

defineProps<{
  moduleName: string
  titleKey: string
  descriptionKey: string
  icon?: string
}>()

const { t: $t } = useI18n()
</script>
