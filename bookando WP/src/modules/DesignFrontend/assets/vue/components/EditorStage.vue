<template>
  <div class="min-h-screen flex flex-col">
    <!-- Top Bar -->
    <div class="bg-slate-800 border-b border-slate-700 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button
            @click="$emit('back')"
            class="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors"
          >
            <ArrowLeftIcon :size="20" />
          </button>
          <div>
            <input
              v-model="localPreset.name"
              class="bg-transparent text-white text-xl font-bold border-none outline-none focus:ring-0"
              @blur="handleSave"
            />
            <p class="text-xs text-slate-400">{{ contextLabel }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Device Toggle -->
          <div class="flex bg-slate-700 rounded-lg p-1">
            <button
              v-for="device in devices"
              :key="device.id"
              @click="currentDevice = device.id"
              :class="[
                'px-3 py-2 rounded-md text-sm font-medium transition-colors',
                currentDevice === device.id
                  ? 'bg-slate-600 text-white'
                  : 'text-slate-400 hover:text-white'
              ]"
            >
              <component :is="device.icon" :size="16" />
            </button>
          </div>

          <button
            @click="handleSave"
            class="flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition-colors"
          >
            <SaveIcon :size="18" />
            Save
          </button>
        </div>
      </div>
    </div>

    <!-- Main Editor -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Configuration Panel -->
      <div class="w-80 bg-slate-800 border-r border-slate-700 overflow-y-auto">
        <div class="p-6 space-y-6">
          <!-- Tabs -->
          <div class="flex flex-col gap-1">
            <button
              v-for="tab in editorTabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'flex items-center gap-3 px-4 py-3 rounded-lg text-left transition-colors',
                activeTab === tab.id
                  ? 'bg-slate-700 text-white'
                  : 'text-slate-400 hover:text-white hover:bg-slate-700/50'
              ]"
            >
              <component :is="tab.icon" :size="18" />
              <span class="font-medium">{{ tab.label }}</span>
            </button>
          </div>

          <!-- Tab Content -->
          <div class="pt-4 border-t border-slate-700">
            <!-- Colors Tab -->
            <div v-if="activeTab === 'colors'" class="space-y-4">
              <div v-for="(value, key) in localPreset.theme.colors" :key="key">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">{{ key }}</label>
                <div class="flex gap-2">
                  <input
                    type="color"
                    v-model="localPreset.theme.colors[key]"
                    class="w-12 h-10 rounded border-2 border-slate-600 cursor-pointer"
                  />
                  <input
                    type="text"
                    v-model="localPreset.theme.colors[key]"
                    class="flex-1 bg-slate-700 border border-slate-600 rounded px-3 py-2 text-white text-sm"
                  />
                </div>
              </div>
            </div>

            <!-- Typography Tab -->
            <div v-else-if="activeTab === 'typography'" class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Font Family</label>
                <select
                  v-model="localPreset.theme.typography.fontFamily"
                  class="w-full bg-slate-700 border border-slate-600 rounded px-3 py-2 text-white text-sm"
                >
                  <option value="Inter">Inter</option>
                  <option value="Roboto">Roboto</option>
                  <option value="Open Sans">Open Sans</option>
                  <option value="Lato">Lato</option>
                  <option value="Poppins">Poppins</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Scale (%)</label>
                <input
                  type="range"
                  v-model.number="localPreset.theme.typography.scale"
                  min="80"
                  max="120"
                  class="w-full"
                />
                <div class="text-white text-sm mt-1">{{ localPreset.theme.typography.scale }}%</div>
              </div>
            </div>

            <!-- Shape Tab -->
            <div v-else-if="activeTab === 'shape'" class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Border Radius (px)</label>
                <input
                  type="range"
                  v-model.number="localPreset.theme.shape.radius"
                  min="0"
                  max="24"
                  class="w-full"
                />
                <div class="text-white text-sm mt-1">{{ localPreset.theme.shape.radius }}px</div>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Border Width (px)</label>
                <input
                  type="range"
                  v-model.number="localPreset.theme.shape.borderWidth"
                  min="0"
                  max="4"
                  class="w-full"
                />
                <div class="text-white text-sm mt-1">{{ localPreset.theme.shape.borderWidth }}px</div>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Shadow</label>
                <select
                  v-model="localPreset.theme.shape.shadow"
                  class="w-full bg-slate-700 border border-slate-600 rounded px-3 py-2 text-white text-sm"
                >
                  <option value="none">None</option>
                  <option value="sm">Small</option>
                  <option value="md">Medium</option>
                  <option value="lg">Large</option>
                  <option value="xl">Extra Large</option>
                </select>
              </div>
            </div>

            <!-- Layout Tab -->
            <div v-else-if="activeTab === 'layout'" class="space-y-3">
              <label
                v-for="(value, key) in localPreset.theme.layout"
                :key="key"
                class="flex items-center justify-between p-3 bg-slate-700 rounded-lg cursor-pointer hover:bg-slate-600 transition-colors"
              >
                <span class="text-white text-sm capitalize">{{ formatLabel(key) }}</span>
                <input
                  type="checkbox"
                  v-model="localPreset.theme.layout[key]"
                  class="w-5 h-5 rounded border-slate-500 text-brand-600 focus:ring-brand-500 focus:ring-offset-slate-700"
                />
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview Area -->
      <div class="flex-1 bg-slate-900 overflow-auto p-8">
        <div class="flex items-center justify-center min-h-full">
          <div
            :class="[
              'bg-white shadow-2xl transition-all duration-300',
              deviceClass
            ]"
            :style="{
              borderRadius: localPreset.theme.shape.radius + 'px'
            }"
          >
            <!-- Preview Content -->
            <PreviewWidget :theme="localPreset.theme" :context="localPreset.context" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
import {
  ArrowLeft as ArrowLeftIcon,
  Save as SaveIcon,
  Monitor as MonitorIcon,
  Smartphone as SmartphoneIcon,
  Tablet as TabletIcon,
  Palette as PaletteIcon,
  Type as TypeIcon,
  Box as BoxIcon,
  Layout as LayoutIcon
} from 'lucide-vue-next'
import PreviewWidget from './PreviewWidget.vue'

interface Props {
  preset: any
}

const props = defineProps<Props>()

const emit = defineEmits<{
  save: [preset: any]
  back: []
}>()

const localPreset = reactive(JSON.parse(JSON.stringify(props.preset)))
const currentDevice = ref<'desktop' | 'tablet' | 'mobile'>('desktop')
const activeTab = ref('colors')

const devices = [
  { id: 'desktop', icon: MonitorIcon },
  { id: 'tablet', icon: TabletIcon },
  { id: 'mobile', icon: SmartphoneIcon }
]

const editorTabs = [
  { id: 'colors', label: 'Colors', icon: PaletteIcon },
  { id: 'typography', label: 'Typography', icon: TypeIcon },
  { id: 'shape', label: 'Shape', icon: BoxIcon },
  { id: 'layout', label: 'Layout', icon: LayoutIcon }
]

const contextLabel = computed(() => {
  const labels = {
    widget: 'Booking Widget',
    offerForm: 'Offer Form',
    customer: 'Customer Portal',
    employee: 'Employee Hub'
  }
  return labels[localPreset.context] || 'Design'
})

const deviceClass = computed(() => {
  const classes = {
    desktop: 'w-full max-w-5xl',
    tablet: 'w-full max-w-3xl',
    mobile: 'w-full max-w-sm'
  }
  return classes[currentDevice.value]
})

const formatLabel = (key: string) => {
  return key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())
}

const handleSave = () => {
  emit('save', JSON.parse(JSON.stringify(localPreset)))
}

// Auto-save on changes (debounced)
let saveTimeout: any = null
watch(localPreset, () => {
  clearTimeout(saveTimeout)
  saveTimeout = setTimeout(() => {
    handleSave()
  }, 1000)
}, { deep: true })
</script>
