<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
    <div class="bg-slate-800 rounded-xl shadow-2xl w-full max-w-3xl border border-slate-700">
      <!-- Header -->
      <div class="p-6 border-b border-slate-700 flex justify-between items-center">
        <div>
          <h3 class="text-xl font-bold text-white">Import / Export Designs</h3>
          <p class="text-sm text-slate-400 mt-1">Share your design presets or import from others</p>
        </div>
        <button
          @click="$emit('close')"
          class="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors"
        >
          <XIcon :size="20" />
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-700">
        <button
          @click="activeTab = 'export'"
          :class="[
            'flex-1 px-6 py-3 font-medium transition-colors',
            activeTab === 'export'
              ? 'text-white border-b-2 border-brand-500'
              : 'text-slate-400 hover:text-white'
          ]"
        >
          <DownloadIcon :size="18" class="inline mr-2" />
          Export
        </button>
        <button
          @click="activeTab = 'import'"
          :class="[
            'flex-1 px-6 py-3 font-medium transition-colors',
            activeTab === 'import'
              ? 'text-white border-b-2 border-brand-500'
              : 'text-slate-400 hover:text-white'
          ]"
        >
          <UploadIcon :size="18" class="inline mr-2" />
          Import
        </button>
      </div>

      <!-- Content -->
      <div class="p-6">
        <!-- Export Tab -->
        <div v-if="activeTab === 'export'" class="space-y-4">
          <p class="text-slate-300 text-sm">
            Copy the JSON below to share your design presets with others or back them up.
          </p>
          <div class="relative">
            <textarea
              ref="exportTextarea"
              :value="exportJson"
              readonly
              class="w-full h-64 bg-slate-900 border border-slate-700 rounded-lg p-4 text-slate-300 font-mono text-xs resize-none focus:outline-none focus:ring-2 focus:ring-brand-500"
            ></textarea>
            <button
              @click="copyToClipboard"
              class="absolute top-4 right-4 px-3 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm transition-colors flex items-center gap-2"
            >
              <CopyIcon :size="16" />
              {{ copied ? 'Copied!' : 'Copy' }}
            </button>
          </div>
          <button
            @click="downloadJson"
            class="w-full px-4 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
          >
            <DownloadIcon :size="18" />
            Download as File
          </button>
        </div>

        <!-- Import Tab -->
        <div v-else class="space-y-4">
          <p class="text-slate-300 text-sm">
            Paste the JSON you received or upload a file to import design presets.
          </p>
          <textarea
            v-model="importJson"
            placeholder="Paste JSON here..."
            class="w-full h-64 bg-slate-900 border border-slate-700 rounded-lg p-4 text-slate-300 font-mono text-xs resize-none focus:outline-none focus:ring-2 focus:ring-brand-500"
          ></textarea>
          <div class="flex gap-3">
            <label class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2 cursor-pointer">
              <UploadIcon :size="18" />
              Upload File
              <input
                type="file"
                accept=".json"
                @change="handleFileUpload"
                class="hidden"
              />
            </label>
            <button
              @click="handleImport"
              :disabled="!importJson.trim()"
              class="flex-1 px-4 py-3 bg-brand-600 hover:bg-brand-700 disabled:bg-slate-600 disabled:cursor-not-allowed text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
            >
              <CheckIcon :size="18" />
              Import Presets
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import {
  X as XIcon,
  Download as DownloadIcon,
  Upload as UploadIcon,
  Copy as CopyIcon,
  Check as CheckIcon
} from 'lucide-vue-next'

interface Props {
  presets: any[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  import: [json: string]
}>()

const activeTab = ref<'export' | 'import'>('export')
const importJson = ref('')
const copied = ref(false)
const exportTextarea = ref<HTMLTextAreaElement>()

const exportJson = computed(() => {
  return JSON.stringify(props.presets, null, 2)
})

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(exportJson.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    // Fallback
    exportTextarea.value?.select()
    document.execCommand('copy')
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  }
}

const downloadJson = () => {
  const blob = new Blob([exportJson.value], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'bookando-designs-' + new Date().toISOString().split('T')[0] + '.json'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const handleFileUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (e) => {
    importJson.value = e.target?.result as string
  }
  reader.readAsText(file)
}

const handleImport = () => {
  if (!importJson.value.trim()) return
  emit('import', importJson.value)
}
</script>
