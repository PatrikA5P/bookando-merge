<template>
  <div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-bold text-slate-800">{{ $t('mod.academy.education_cards') }}</h2>
      <button
        @click="$emit('create-card')"
        class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center gap-2 font-medium"
      >
        <PlusIcon :size="18" /> {{ $t('mod.academy.actions.new_template') }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="card in cards"
        :key="card.id"
        class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-5 flex flex-col group"
      >
        <div class="flex justify-between items-start mb-4">
          <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
            <CreditCardIcon :size="24" />
          </div>
          <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click="$emit('edit-card', card.id)"
              class="p-2 text-slate-400 hover:text-brand-600 hover:bg-slate-50 rounded-lg transition-colors"
            >
              <Edit2Icon :size="16" />
            </button>
            <button
              @click="$emit('delete-card', card.id)"
              class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
            >
              <Trash2Icon :size="16" />
            </button>
          </div>
        </div>

        <h3 class="font-bold text-slate-800 text-lg mb-1">{{ card.title }}</h3>
        <p class="text-sm text-slate-500 mb-4 flex-1">
          {{ card.description || $t('mod.academy.no_description') }}
        </p>

        <div class="flex items-center gap-4 text-xs text-slate-500 pt-4 border-t border-slate-50">
          <span class="flex items-center gap-1">
            <ListIcon :size="14" /> {{ card.chapters.length }} {{ $t('mod.academy.chapters') }}
          </span>
          <span class="flex items-center gap-1">
            <ZapIcon
              :size="14"
              :class="card.automation.enabled ? 'text-amber-500' : 'text-slate-300'"
            />
            {{ card.automation.enabled ? $t('mod.academy.auto_assign') : $t('mod.academy.manual') }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Plus as PlusIcon,
  Edit2 as Edit2Icon,
  Trash2 as Trash2Icon,
  CreditCard as CreditCardIcon,
  List as ListIcon,
  Zap as ZapIcon
} from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

interface EducationCardTemplate {
  id: string
  title: string
  description: string
  chapters: any[]
  automation: {
    enabled: boolean
  }
}

defineProps<{
  cards: EducationCardTemplate[]
}>()

defineEmits<{
  'create-card': []
  'edit-card': [cardId: string]
  'delete-card': [cardId: string]
}>()

const { t: $t } = useI18n()
</script>
