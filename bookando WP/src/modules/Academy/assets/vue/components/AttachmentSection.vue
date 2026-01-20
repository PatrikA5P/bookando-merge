<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h4 class="text-sm font-bold text-slate-600 uppercase flex items-center gap-2">
        <ImageIcon v-if="type === 'image'" :size="14" />
        <VideoIcon v-if="type === 'video'" :size="14" />
        <FileTextIcon v-if="type === 'document'" :size="14" />
        {{ $t(`mod.academy.${type}s`) }}
      </h4>
      <button
        @click="$emit('add')"
        class="text-xs bg-white border border-slate-300 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-300 px-2 py-1 rounded transition-colors flex items-center gap-1"
      >
        <PlusIcon :size="12" /> {{ $t('mod.academy.add') }} {{ $t(`mod.academy.${type === 'document' ? 'file' : type}`) }}
      </button>
    </div>

    <div v-if="items.length === 0" class="p-4 border-2 border-dashed border-slate-100 rounded-lg text-center text-xs text-slate-400 bg-slate-50/50">
      {{ $t('mod.academy.no_attachments', { type: $t(`mod.academy.${type}s`) }) }}
    </div>

    <div :class="['grid gap-3', type === 'image' ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4' : 'grid-cols-1']">
      <div
        v-for="att in items"
        :key="att.id"
        :class="['relative group bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm', type !== 'image' ? 'p-3 flex gap-3 items-start' : '']"
      >
        <!-- Preview Area for Images -->
        <div v-if="type === 'image'" class="aspect-video bg-slate-100 relative overflow-hidden">
          <img :src="att.url" class="w-full h-full object-cover" alt="preview" />
          <button
            @click="$emit('remove', att.id)"
            class="absolute top-2 right-2 bg-white/90 p-1 rounded-full text-rose-500 hover:text-rose-700 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <Trash2Icon :size="14" />
          </button>
        </div>

        <!-- Icon for non-images -->
        <div
          v-if="type !== 'image'"
          :class="['w-12 h-12 rounded-lg flex items-center justify-center shrink-0', type === 'video' ? 'bg-purple-50 text-purple-600' : 'bg-blue-50 text-blue-600']"
        >
          <VideoIcon v-if="type === 'video'" :size="20" />
          <FileTextIcon v-else :size="20" />
        </div>

        <!-- Inputs -->
        <div :class="['flex-1 min-w-0', type === 'image' ? 'p-2' : '']">
          <input
            :value="att.name"
            @input="(e) => $emit('update', att.id, { name: (e.target as HTMLInputElement).value })"
            class="w-full text-sm font-medium text-slate-800 border-none p-0 focus:ring-0 bg-transparent placeholder-slate-400 mb-1"
            :placeholder="$t('mod.academy.title')"
          />
          <input
            :value="att.description"
            @input="(e) => $emit('update', att.id, { description: (e.target as HTMLInputElement).value })"
            class="w-full text-xs text-slate-500 border-none p-0 focus:ring-0 bg-transparent placeholder-slate-300"
            :placeholder="$t('mod.academy.description_placeholder')"
          />
        </div>

        <!-- List Remove Button -->
        <button
          v-if="type !== 'image'"
          @click="$emit('remove', att.id)"
          class="text-slate-300 hover:text-rose-500 p-1"
        >
          <Trash2Icon :size="16" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import {
  Plus as PlusIcon,
  Trash2 as Trash2Icon,
  Image as ImageIcon,
  Video as VideoIcon,
  FileText as FileTextIcon
} from 'lucide-vue-next'

interface StructuredAttachment {
  id: string
  type: 'image' | 'video' | 'document'
  url: string
  name: string
  description: string
}

defineProps<{
  type: 'image' | 'video' | 'document'
  items: StructuredAttachment[]
}>()

defineEmits<{
  add: []
  update: [id: string, changes: Partial<StructuredAttachment>]
  remove: [id: string]
}>()

const { t: $t } = useI18n()
</script>
