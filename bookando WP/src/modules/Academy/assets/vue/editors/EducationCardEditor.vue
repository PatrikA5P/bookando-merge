<template>
  <div class="flex flex-col h-full bg-white">
    <!-- Header -->
    <div class="p-6 border-b border-slate-200 flex justify-between items-center bg-slate-50">
      <div class="flex items-center gap-4">
        <button
          @click="$emit('back')"
          class="p-2 hover:bg-slate-100 rounded-lg transition-colors"
        >
          <ArrowLeftIcon :size="20" />
        </button>
        <div>
          <h2 class="text-xl font-bold text-slate-800">{{ template.title }}</h2>
          <p class="text-sm text-slate-500">{{ $t('mod.academy.education_card_editor') }}</p>
        </div>
      </div>
      <div class="flex gap-2">
        <button
          @click="$emit('back')"
          class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
        >
          {{ $t('common.cancel') }}
        </button>
        <button
          @click="$emit('save', template)"
          class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
        >
          <SaveIcon :size="18" /> {{ $t('common.save') }}
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-auto p-6">
      <div class="max-w-4xl mx-auto">
        <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl p-12 text-center">
          <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
              <CreditCardIcon :size="32" />
            </div>
            <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.academy.education_card_editor_coming_soon') }}</h3>
            <p class="text-slate-600 max-w-md">
              {{ $t('mod.academy.education_card_editor_description') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ArrowLeft as ArrowLeftIcon, Save as SaveIcon, CreditCard as CreditCardIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

interface EducationCardTemplate {
  id: string
  title: string
  [key: string]: any
}

defineProps<{
  template: EducationCardTemplate
}>()

defineEmits<{
  back: []
  save: [template: EducationCardTemplate]
}>()

const { t: $t } = useI18n()
</script>
