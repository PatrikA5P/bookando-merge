<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md animate-fadeIn flex flex-col max-h-[80vh]">
      <div class="p-6 border-b border-slate-100 flex justify-between items-center">
        <h3 class="font-bold text-lg text-slate-800">{{ $t('mod.academy.manage_lesson_groups') }}</h3>
        <button @click="$emit('close')">
          <XIcon :size="20" class="text-slate-400 hover:text-slate-600" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-6 bg-slate-50 space-y-2">
        <p v-if="groups.length === 0" class="text-center text-slate-400 text-sm py-4">
          {{ $t('mod.academy.no_groups_yet') }}
        </p>
        <div
          v-for="group in groups"
          :key="group.id"
          class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-lg shadow-sm"
        >
          <div class="flex items-center gap-3">
            <FolderIcon :size="18" class="text-indigo-500" />
            <span class="text-sm font-medium text-slate-700">{{ group.title }}</span>
          </div>
          <button
            @click="$emit('delete', group.id)"
            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors"
          >
            <Trash2Icon :size="16" />
          </button>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="p-4 border-t border-slate-100 bg-white rounded-b-xl flex gap-2">
        <input
          v-model="newGroupTitle"
          class="flex-1 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
          :placeholder="$t('mod.academy.new_group_placeholder')"
          autofocus
        />
        <button
          type="submit"
          :disabled="!newGroupTitle.trim()"
          class="px-4 py-2 bg-brand-600 disabled:bg-slate-300 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition-colors"
        >
          {{ $t('common.add') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Plus as PlusIcon, Trash2 as Trash2Icon, X as XIcon, Folder as FolderIcon } from 'lucide-vue-next'

interface LessonGroup {
  id: string
  title: string
}

defineProps<{
  groups: LessonGroup[]
}>()

const emit = defineEmits<{
  add: [title: string]
  delete: [id: string]
  close: []
}>()

const { t: $t } = useI18n()

const newGroupTitle = ref('')

const handleSubmit = () => {
  if (newGroupTitle.value.trim()) {
    emit('add', newGroupTitle.value)
    newGroupTitle.value = ''
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.2s ease-out;
}
</style>
