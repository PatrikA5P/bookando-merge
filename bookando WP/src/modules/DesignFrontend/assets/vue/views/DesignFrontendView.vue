<template>
  <div class="min-h-screen bg-slate-900">
    <!-- Step 1: Context Selector -->
    <ContextSelector
      v-if="step === 1"
      @select="handleContextSelect"
    />

    <!-- Step 2: Preset Dashboard -->
    <PresetDashboard
      v-else-if="step === 2 && selectedContext"
      :context="selectedContext"
      :presets="filteredPresets"
      @back="step = 1"
      @create="createNewPreset"
      @edit="editPreset"
      @duplicate="duplicatePreset"
      @delete="deletePreset"
      @import-export="importExportOpen = true"
    />

    <!-- Step 3: Editor Stage -->
    <EditorStage
      v-else-if="step === 3 && activePreset"
      :preset="activePreset"
      @save="savePreset"
      @back="step = 2"
    />

    <!-- Import/Export Modal -->
    <ImportExportModal
      v-if="importExportOpen"
      :presets="presets"
      @close="importExportOpen = false"
      @import="handleImport"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import ContextSelector from '../components/ContextSelector.vue'
import PresetDashboard from '../components/PresetDashboard.vue'
import EditorStage from '../components/EditorStage.vue'
import ImportExportModal from '../components/ImportExportModal.vue'

type PortalContext = 'widget' | 'offerForm' | 'customer' | 'employee'

interface ThemeConfig {
  colors: {
    primary: string
    secondary: string
    background: string
    surface: string
    text: string
    textMuted: string
    border: string
    success: string
    danger: string
  }
  typography: {
    fontFamily: string
    scale: number
  }
  shape: {
    radius: number
    borderWidth: number
    shadow: 'none' | 'sm' | 'md' | 'lg' | 'xl'
  }
  layout: {
    showSidebar: boolean
    showFooter: boolean
    compactMode: boolean
    showUserAvatar: boolean
    showSearch: boolean
    showFilters: boolean
    enableAnimations: boolean
  }
}

interface DesignPreset {
  id: string
  name: string
  context: PortalContext
  createdAt: string
  theme: ThemeConfig
  widgetBehavior?: any
  customerConfig?: any
  employeeConfig?: any
  texts?: Record<string, string>
}

const step = ref(1)
const selectedContext = ref<PortalContext | null>(null)
const activePreset = ref<DesignPreset | null>(null)
const importExportOpen = ref(false)

const defaultTheme: ThemeConfig = {
  colors: {
    primary: '#0284c7',
    secondary: '#475569',
    background: '#f8fafc',
    surface: '#ffffff',
    text: '#0f172a',
    textMuted: '#64748b',
    border: '#e2e8f0',
    success: '#10b981',
    danger: '#e11d48'
  },
  typography: {
    fontFamily: 'Inter',
    scale: 100
  },
  shape: {
    radius: 14,
    borderWidth: 1,
    shadow: 'sm'
  },
  layout: {
    showSidebar: true,
    showFooter: true,
    compactMode: false,
    showUserAvatar: true,
    showSearch: true,
    showFilters: true,
    enableAnimations: true
  }
}

const presets = ref<DesignPreset[]>([
  {
    id: 'preset-default-widget',
    name: 'Default Widget Theme',
    context: 'widget',
    createdAt: new Date().toISOString(),
    theme: JSON.parse(JSON.stringify(defaultTheme))
  },
  {
    id: 'preset-default-customer',
    name: 'Customer Portal Default',
    context: 'customer',
    createdAt: new Date().toISOString(),
    theme: JSON.parse(JSON.stringify(defaultTheme))
  }
])

const filteredPresets = computed(() =>
  selectedContext.value
    ? presets.value.filter(p => p.context === selectedContext.value)
    : []
)

const handleContextSelect = (context: PortalContext) => {
  selectedContext.value = context
  step.value = 2
}

const createNewPreset = () => {
  if (!selectedContext.value) return

  const timestamp = Date.now()
  const newPreset: DesignPreset = {
    id: 'preset-' + timestamp,
    name: 'New Custom Design',
    context: selectedContext.value,
    createdAt: new Date().toISOString(),
    theme: JSON.parse(JSON.stringify(defaultTheme))
  }

  presets.value.unshift(newPreset)
  activePreset.value = newPreset
  step.value = 3
}

const editPreset = (preset: DesignPreset) => {
  activePreset.value = JSON.parse(JSON.stringify(preset))
  step.value = 3
}

const duplicatePreset = (id: string) => {
  const preset = presets.value.find(p => p.id === id)
  if (!preset) return

  const timestamp = Date.now()
  const copy: DesignPreset = {
    ...JSON.parse(JSON.stringify(preset)),
    id: 'preset-' + timestamp,
    name: preset.name + ' (Copy)',
    createdAt: new Date().toISOString()
  }

  presets.value.unshift(copy)
}

const deletePreset = (id: string) => {
  const preset = presets.value.find(p => p.id === id)
  if (!preset) return

  if (confirm('Delete "' + preset.name + '"?')) {
    presets.value = presets.value.filter(p => p.id !== id)
    if (activePreset.value?.id === id) {
      activePreset.value = null
    }
  }
}

const savePreset = (updated: DesignPreset) => {
  const index = presets.value.findIndex(p => p.id === updated.id)
  if (index >= 0) {
    presets.value[index] = updated
  } else {
    presets.value.unshift(updated)
  }
  activePreset.value = updated
}

const handleImport = (jsonStr: string) => {
  try {
    const parsed = JSON.parse(jsonStr)
    if (!Array.isArray(parsed)) {
      alert('Invalid JSON.')
      return
    }

    const imported = parsed.filter((p: any) => p.id && p.name && p.context)
    presets.value.unshift(...imported)
    alert('Imported ' + imported.length + ' design(s).')
    importExportOpen.value = false
  } catch (e) {
    alert('Failed to parse JSON.')
  }
}
</script>
