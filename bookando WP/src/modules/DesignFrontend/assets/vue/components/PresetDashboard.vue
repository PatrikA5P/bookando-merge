<template>
  <div class="min-h-screen p-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-8">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="$emit('back')"
            class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors"
          >
            <ArrowLeftIcon :size="20" />
          </button>
          <div>
            <h1 class="text-3xl font-bold text-white">{{ contextTitle }}</h1>
            <p class="text-slate-400 mt-1">Manage your design presets</p>
          </div>
        </div>
        <div class="flex gap-3">
          <button
            @click="$emit('import-export')"
            class="flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg transition-colors border border-slate-700"
          >
            <DownloadIcon :size="18" />
            Import/Export
          </button>
          <button
            @click="$emit('create')"
            class="flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors shadow-lg shadow-brand-500/30"
          >
            <PlusIcon :size="18" />
            Create New
          </button>
        </div>
      </div>
    </div>

    <!-- Preset Grid -->
    <div class="max-w-7xl mx-auto">
      <div v-if="presets.length === 0" class="text-center py-16">
        <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
          <LayoutIcon :size="40" class="text-slate-600" />
        </div>
        <h3 class="text-xl font-bold text-white mb-2">No presets yet</h3>
        <p class="text-slate-400 mb-6">Create your first design preset to get started</p>
        <button
          @click="$emit('create')"
          class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors inline-flex items-center gap-2"
        >
          <PlusIcon :size="18} />
          Create First Preset
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="preset in presets"
          :key="preset.id"
          class="group bg-slate-800 border border-slate-700 rounded-xl overflow-hidden hover:border-brand-500 transition-all duration-300"
        >
          <!-- Preview -->
          <div
            class="h-40 p-4 relative overflow-hidden"
            :style="{
              background: preset.theme?.colors?.background || '#f8fafc'
            }"
          >
            <div class="absolute inset-0 opacity-50">
              <div class="w-full h-8 mb-2" :style="{ background: preset.theme?.colors?.primary || '#0284c7', borderRadius: (preset.theme?.shape?.radius || 14) + 'px' }"></div>
              <div class="w-3/4 h-6 mb-2" :style="{ background: preset.theme?.colors?.surface || '#ffffff', borderRadius: (preset.theme?.shape?.radius || 14) + 'px' }"></div>
              <div class="w-1/2 h-6" :style="{ background: preset.theme?.colors?.surface || '#ffffff', borderRadius: (preset.theme?.shape?.radius || 14) + 'px' }"></div>
            </div>
          </div>

          <!-- Info -->
          <div class="p-5 bg-slate-800">
            <h3 class="font-bold text-white text-lg mb-1 truncate">{{ preset.name }}</h3>
            <p class="text-xs text-slate-400 mb-4">
              Created {{ formatDate(preset.createdAt) }}
            </p>

            <!-- Color Swatches -->
            <div class="flex gap-2 mb-4">
              <div
                v-for="(colorKey, index) in ['primary', 'secondary', 'success', 'danger']"
                :key="index"
                class="w-8 h-8 rounded-lg border-2 border-slate-700"
                :style="{ background: preset.theme?.colors?.[colorKey] || '#cbd5e1' }"
                :title="colorKey"
              ></div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <button
                @click="$emit('edit', preset)"
                class="flex-1 px-3 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-medium transition-colors"
              >
                Edit
              </button>
              <button
                @click="$emit('duplicate', preset.id)"
                class="p-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors"
                title="Duplicate"
              >
                <CopyIcon :size="16" />
              </button>
              <button
                @click="$emit('delete', preset.id)"
                class="p-2 bg-slate-700 hover:bg-rose-600 text-white rounded-lg transition-colors"
                title="Delete"
              >
                <Trash2Icon :size="16" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  ArrowLeft as ArrowLeftIcon,
  Download as DownloadIcon,
  Plus as PlusIcon,
  Layout as LayoutIcon,
  Copy as CopyIcon,
  Trash2 as Trash2Icon
} from 'lucide-vue-next'

interface Props {
  context: 'widget' | 'offerForm' | 'customer' | 'employee'
  presets: any[]
}

const props = defineProps<Props>()

defineEmits<{
  back: []
  create: []
  edit: [preset: any]
  duplicate: [id: string]
  delete: [id: string]
  'import-export': []
}>()

const contextTitle = computed(() => {
  const titles = {
    widget: 'Booking Widget Designs',
    offerForm: 'Offer Form Designs',
    customer: 'Customer Portal Designs',
    employee: 'Employee Hub Designs'
  }
  return titles[props.context] || 'Designs'
})

const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffDays === 0) return 'Today'
  if (diffDays === 1) return 'Yesterday'
  if (diffDays < 7) return diffDays + ' days ago'
  if (diffDays < 30) return Math.floor(diffDays / 7) + ' weeks ago'
  if (diffDays < 365) return Math.floor(diffDays / 30) + ' months ago'
  return Math.floor(diffDays / 365) + ' years ago'
}
</script>
