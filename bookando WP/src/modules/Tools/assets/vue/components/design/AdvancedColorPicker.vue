<!-- AdvancedColorPicker.vue - Small square color picker with Gradient/HEX/RGB/Alpha support -->
<template>
  <div class="advanced-color-picker">
    <div
      class="color-preview-box"
      :style="previewStyle"
      @click="togglePicker"
    >
      <div
        v-if="showAlpha"
        class="alpha-pattern"
      />
      <div
        class="color-overlay"
        :style="colorStyle"
      />
    </div>

    <!-- Picker Dropdown -->
    <div
      v-if="isOpen"
      v-click-outside="closePicker"
      class="color-picker-dropdown"
    >
      <!-- Tabs: Solid / Gradient -->
      <div class="picker-tabs">
        <button
          :class="['tab', { active: mode === 'solid' }]"
          @click="mode = 'solid'"
        >
          Solid
        </button>
        <button
          :class="['tab', { active: mode === 'gradient' }]"
          @click="mode = 'gradient'"
        >
          Gradient
        </button>
      </div>

      <!-- SOLID Mode -->
      <div
        v-if="mode === 'solid'"
        class="picker-content"
      >
        <!-- Color Spectrum -->
        <div
          class="color-spectrum"
          @mousedown="startSpectrumDrag"
        >
          <div class="spectrum-saturation" />
          <div class="spectrum-brightness" />
          <div
            class="spectrum-cursor"
            :style="{
              left: `${saturation * 100}%`,
              top: `${(1 - brightness) * 100}%`
            }"
          />
        </div>

        <!-- Hue Slider -->
        <div
          class="hue-slider"
          @mousedown="startHueDrag"
        >
          <div
            class="hue-cursor"
            :style="{ left: `${(hue / 360) * 100}%` }"
          />
        </div>

        <!-- Alpha Slider -->
        <div
          v-if="showAlpha"
          class="alpha-slider"
          @mousedown="startAlphaDrag"
        >
          <div class="alpha-pattern" />
          <div
            class="alpha-gradient"
            :style="{
              background: `linear-gradient(to right, transparent, ${solidColorWithoutAlpha})`
            }"
          />
          <div
            class="alpha-cursor"
            :style="{ left: `${alpha * 100}%` }"
          />
        </div>

        <!-- Input Fields -->
        <div class="color-inputs">
          <div class="input-group">
            <label>HEX</label>
            <input
              v-model="hexInput"
              type="text"
              @input="updateFromHex"
              @blur="validateHex"
            >
          </div>

          <div class="input-group">
            <label>R</label>
            <input
              v-model.number="rgb.r"
              type="number"
              min="0"
              max="255"
              @input="updateFromRgb"
            >
          </div>

          <div class="input-group">
            <label>G</label>
            <input
              v-model.number="rgb.g"
              type="number"
              min="0"
              max="255"
              @input="updateFromRgb"
            >
          </div>

          <div class="input-group">
            <label>B</label>
            <input
              v-model.number="rgb.b"
              type="number"
              min="0"
              max="255"
              @input="updateFromRgb"
            >
          </div>

          <div
            v-if="showAlpha"
            class="input-group"
          >
            <label>A</label>
            <input
              v-model.number="alphaPercent"
              type="number"
              min="0"
              max="100"
              @input="updateFromAlphaPercent"
            >
          </div>
        </div>

        <!-- Preset Colors -->
        <div class="color-presets">
          <div
            v-for="preset in presetColors"
            :key="preset"
            class="preset-color"
            :style="{ backgroundColor: preset }"
            @click="setColor(preset)"
          />
        </div>
      </div>

      <!-- GRADIENT Mode -->
      <div
        v-if="mode === 'gradient'"
        class="picker-content"
      >
        <!-- Gradient Preview -->
        <div
          class="gradient-preview"
          :style="{ background: gradientValue }"
        />

        <!-- Gradient Type -->
        <div class="gradient-type-selector">
          <button
            :class="['type-btn', { active: gradientType === 'linear' }]"
            @click="gradientType = 'linear'"
          >
            Linear
          </button>
          <button
            :class="['type-btn', { active: gradientType === 'radial' }]"
            @click="gradientType = 'radial'"
          >
            Radial
          </button>
        </div>

        <!-- Angle (for linear) -->
        <div
          v-if="gradientType === 'linear'"
          class="gradient-angle"
        >
          <label>Angle</label>
          <input
            v-model.number="gradientAngle"
            type="range"
            min="0"
            max="360"
            @input="updateGradient"
          >
          <span>{{ gradientAngle }}°</span>
        </div>

        <!-- Color Stops -->
        <div class="gradient-stops">
          <div
            v-for="(stop, index) in gradientStops"
            :key="index"
            class="gradient-stop"
          >
            <div
              class="stop-color"
              :style="{ backgroundColor: stop.color }"
              @click="selectGradientStop(index)"
            />
            <input
              v-model.number="stop.position"
              type="range"
              min="0"
              max="100"
              @input="updateGradient"
            >
            <button
              v-if="gradientStops.length > 2"
              class="remove-stop"
              @click="removeGradientStop(index)"
            >
              ×
            </button>
          </div>
        </div>

        <button
          class="add-stop-btn"
          @click="addGradientStop"
        >
          + Add Color Stop
        </button>

        <!-- Selected Stop Color Picker -->
        <div
          v-if="selectedStopIndex !== null"
          class="stop-color-editor"
        >
          <label>Stop {{ selectedStopIndex + 1 }} Color</label>
          <input
            v-model="gradientStops[selectedStopIndex].color"
            type="text"
            @input="updateGradient"
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

const props = defineProps<{
  modelValue: string
  showAlpha?: boolean
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

// State
const isOpen = ref(false)
const mode = ref<'solid' | 'gradient'>('solid')

// Solid Color State
const hue = ref(0)
const saturation = ref(1)
const brightness = ref(1)
const alpha = ref(1)
const rgb = ref({ r: 255, g: 0, b: 0 })
const hexInput = ref('#FF0000')

// Gradient State
const gradientType = ref<'linear' | 'radial'>('linear')
const gradientAngle = ref(90)
const gradientStops = ref([
  { color: '#FF0000', position: 0 },
  { color: '#0000FF', position: 100 }
])
const selectedStopIndex = ref<number | null>(null)

// Preset Colors
const presetColors = [
  '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF',
  '#000000', '#FFFFFF', '#808080', '#FF8800', '#8800FF', '#00FF88'
]

// Computed
const alphaPercent = computed({
  get: () => Math.round(alpha.value * 100),
  set: (val: number) => {
    alpha.value = Math.max(0, Math.min(100, val)) / 100
  }
})

const solidColorWithoutAlpha = computed(() => {
  return `rgb(${rgb.value.r}, ${rgb.value.g}, ${rgb.value.b})`
})

const solidColorWithAlpha = computed(() => {
  if (props.showAlpha && alpha.value < 1) {
    return `rgba(${rgb.value.r}, ${rgb.value.g}, ${rgb.value.b}, ${alpha.value})`
  }
  return solidColorWithoutAlpha.value
})

const gradientValue = computed(() => {
  const stops = gradientStops.value
    .map(s => `${s.color} ${s.position}%`)
    .join(', ')

  if (gradientType.value === 'linear') {
    return `linear-gradient(${gradientAngle.value}deg, ${stops})`
  }
  return `radial-gradient(circle, ${stops})`
})

const previewStyle = computed(() => {
  if (mode.value === 'gradient') {
    return { background: gradientValue.value }
  }
  return { background: solidColorWithAlpha.value }
})

const colorStyle = computed(() => {
  if (mode.value === 'solid') {
    return { background: solidColorWithAlpha.value }
  }
  return {}
})

// Methods
function togglePicker() {
  isOpen.value = !isOpen.value
}

function closePicker() {
  isOpen.value = false
}

function parseInputColor(color: string) {
  if (color.startsWith('linear-gradient') || color.startsWith('radial-gradient')) {
    mode.value = 'gradient'
    parseGradient(color)
  } else {
    mode.value = 'solid'
    parseSolidColor(color)
  }
}

function parseSolidColor(color: string) {
  // Parse HEX, RGB, RGBA
  const hexMatch = color.match(/^#([0-9A-Fa-f]{6})([0-9A-Fa-f]{2})?$/)
  if (hexMatch) {
    const r = parseInt(hexMatch[1].substring(0, 2), 16)
    const g = parseInt(hexMatch[1].substring(2, 4), 16)
    const b = parseInt(hexMatch[1].substring(4, 6), 16)
    const a = hexMatch[2] ? parseInt(hexMatch[2], 16) / 255 : 1

    rgb.value = { r, g, b }
    alpha.value = a
    updateHSB()
    updateHexInput()
    return
  }

  const rgbaMatch = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)$/)
  if (rgbaMatch) {
    rgb.value = {
      r: parseInt(rgbaMatch[1]),
      g: parseInt(rgbaMatch[2]),
      b: parseInt(rgbaMatch[3])
    }
    alpha.value = rgbaMatch[4] ? parseFloat(rgbaMatch[4]) : 1
    updateHSB()
    updateHexInput()
  }
}

function parseGradient(gradient: string) {
  // Simple gradient parser
  const isLinear = gradient.startsWith('linear-gradient')
  gradientType.value = isLinear ? 'linear' : 'radial'

  if (isLinear) {
    const angleMatch = gradient.match(/(\d+)deg/)
    if (angleMatch) {
      gradientAngle.value = parseInt(angleMatch[1])
    }
  }

  // Parse color stops (simplified)
  const stopMatches = gradient.matchAll(/(#[0-9A-Fa-f]{6}|rgba?\([^)]+\))\s+(\d+)%/g)
  const stops = []
  for (const match of stopMatches) {
    stops.push({
      color: match[1],
      position: parseInt(match[2])
    })
  }

  if (stops.length >= 2) {
    gradientStops.value = stops
  }
}

function updateHSB() {
  const r = rgb.value.r / 255
  const g = rgb.value.g / 255
  const b = rgb.value.b / 255

  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const delta = max - min

  brightness.value = max
  saturation.value = max === 0 ? 0 : delta / max

  if (delta === 0) {
    hue.value = 0
  } else if (max === r) {
    hue.value = 60 * (((g - b) / delta) % 6)
  } else if (max === g) {
    hue.value = 60 * ((b - r) / delta + 2)
  } else {
    hue.value = 60 * ((r - g) / delta + 4)
  }

  if (hue.value < 0) hue.value += 360
}

function updateRGBFromHSB() {
  const h = hue.value
  const s = saturation.value
  const v = brightness.value

  const c = v * s
  const x = c * (1 - Math.abs(((h / 60) % 2) - 1))
  const m = v - c

  let r = 0, g = 0, b = 0

  if (h >= 0 && h < 60) {
    r = c; g = x; b = 0
  } else if (h >= 60 && h < 120) {
    r = x; g = c; b = 0
  } else if (h >= 120 && h < 180) {
    r = 0; g = c; b = x
  } else if (h >= 180 && h < 240) {
    r = 0; g = x; b = c
  } else if (h >= 240 && h < 300) {
    r = x; g = 0; b = c
  } else {
    r = c; g = 0; b = x
  }

  rgb.value = {
    r: Math.round((r + m) * 255),
    g: Math.round((g + m) * 255),
    b: Math.round((b + m) * 255)
  }
}

function updateHexInput() {
  const r = rgb.value.r.toString(16).padStart(2, '0')
  const g = rgb.value.g.toString(16).padStart(2, '0')
  const b = rgb.value.b.toString(16).padStart(2, '0')
  hexInput.value = `#${r}${g}${b}`.toUpperCase()
}

function updateFromHex() {
  const hex = hexInput.value.replace('#', '')
  if (hex.length === 6) {
    rgb.value = {
      r: parseInt(hex.substring(0, 2), 16),
      g: parseInt(hex.substring(2, 4), 16),
      b: parseInt(hex.substring(4, 6), 16)
    }
    updateHSB()
    emitColor()
  }
}

function validateHex() {
  updateHexInput()
}

function updateFromRgb() {
  updateHSB()
  updateHexInput()
  emitColor()
}

function updateFromAlphaPercent() {
  emitColor()
}

function setColor(color: string) {
  parseSolidColor(color)
  emitColor()
}

function startSpectrumDrag(e: MouseEvent) {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()

  const updateSpectrum = (event: MouseEvent) => {
    saturation.value = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width))
    brightness.value = Math.max(0, Math.min(1, 1 - (event.clientY - rect.top) / rect.height))
    updateRGBFromHSB()
    updateHexInput()
    emitColor()
  }

  updateSpectrum(e)

  const onMove = (event: MouseEvent) => updateSpectrum(event)
  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function startHueDrag(e: MouseEvent) {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()

  const updateHue = (event: MouseEvent) => {
    hue.value = Math.max(0, Math.min(360, ((event.clientX - rect.left) / rect.width) * 360))
    updateRGBFromHSB()
    updateHexInput()
    emitColor()
  }

  updateHue(e)

  const onMove = (event: MouseEvent) => updateHue(event)
  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function startAlphaDrag(e: MouseEvent) {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()

  const updateAlpha = (event: MouseEvent) => {
    alpha.value = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width))
    emitColor()
  }

  updateAlpha(e)

  const onMove = (event: MouseEvent) => updateAlpha(event)
  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function addGradientStop() {
  const newPosition = 50
  gradientStops.value.push({
    color: '#808080',
    position: newPosition
  })
  gradientStops.value.sort((a, b) => a.position - b.position)
  updateGradient()
}

function removeGradientStop(index: number) {
  if (gradientStops.value.length > 2) {
    gradientStops.value.splice(index, 1)
    if (selectedStopIndex.value === index) {
      selectedStopIndex.value = null
    }
    updateGradient()
  }
}

function selectGradientStop(index: number) {
  selectedStopIndex.value = index
}

function updateGradient() {
  emitColor()
}

function emitColor() {
  let colorValue = ''

  if (mode.value === 'solid') {
    colorValue = solidColorWithAlpha.value
  } else {
    colorValue = gradientValue.value
  }

  emit('update:modelValue', colorValue)
}

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (newValue && newValue !== (mode.value === 'solid' ? solidColorWithAlpha.value : gradientValue.value)) {
    parseInputColor(newValue)
  }
}, { immediate: true })

// Click outside directive
const vClickOutside = {
  mounted(el: HTMLElement, binding: any) {
    el._clickOutside = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value()
      }
    }
    document.addEventListener('click', el._clickOutside)
  },
  unmounted(el: HTMLElement) {
    document.removeEventListener('click', el._clickOutside)
    delete el._clickOutside
  }
}
</script>

<style scoped lang="scss">
@use '@scss/variables' as *;

.advanced-color-picker {
  position: relative;
  display: inline-block;
}

.color-preview-box {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #E2E6EC;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.2s;

  &:hover {
    border-color: #1A84EE;
    box-shadow: 0 2px 8px rgba(26, 132, 238, 0.2);
  }
}

.alpha-pattern {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(45deg, #ccc 25%, transparent 25%),
    linear-gradient(-45deg, #ccc 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, #ccc 75%),
    linear-gradient(-45deg, transparent 75%, #ccc 75%);
  background-size: 8px 8px;
  background-position: 0 0, 0 4px, 4px -4px, -4px 0px;
}

.color-overlay {
  position: absolute;
  inset: 0;
}

.color-picker-dropdown {
  position: absolute;
  top: 38px;
  left: 0;
  z-index: 1000;
  width: 280px;
  background: #ffffff;
  border: 1px solid #E2E6EC;
  border-radius: 6px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  padding: 16px;
}

.picker-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;

  .tab {
    flex: 1;
    padding: 8px;
    border: 1px solid #E2E6EC;
    background: #ffffff;
    border-radius: 4px;
    font-size: 14px;
    color: #354052;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      border-color: #1A84EE;
    }

    &.active {
      background: #1A84EE;
      border-color: #1A84EE;
      color: #ffffff;
    }
  }
}

.picker-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.color-spectrum {
  width: 100%;
  height: 150px;
  position: relative;
  border-radius: 4px;
  background: linear-gradient(to right,
    #FF0000 0%,
    #FFFF00 17%,
    #00FF00 33%,
    #00FFFF 50%,
    #0000FF 67%,
    #FF00FF 83%,
    #FF0000 100%
  );
  cursor: crosshair;
  overflow: hidden;
}

.spectrum-saturation {
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, white, transparent);
}

.spectrum-brightness {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent, black);
}

.spectrum-cursor {
  position: absolute;
  width: 12px;
  height: 12px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.hue-slider {
  position: relative;
  width: 100%;
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(to right,
    #FF0000 0%,
    #FFFF00 17%,
    #00FF00 33%,
    #00FFFF 50%,
    #0000FF 67%,
    #FF00FF 83%,
    #FF0000 100%
  );
  cursor: pointer;
}

.hue-cursor,
.alpha-cursor {
  position: absolute;
  top: 50%;
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.alpha-slider {
  position: relative;
  width: 100%;
  height: 12px;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;

  .alpha-pattern {
    position: absolute;
    inset: 0;
  }

  .alpha-gradient {
    position: absolute;
    inset: 0;
  }
}

.color-inputs {
  display: flex;
  gap: 6px;

  .input-group {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;

    label {
      font-size: 11px;
      color: #7F8FA4;
      font-weight: 500;
      text-transform: uppercase;
    }

    input {
      width: 100%;
      padding: 6px 8px;
      border: 1px solid #E2E6EC;
      border-radius: 4px;
      font-size: 13px;
      color: #354052;
      background: #ffffff;

      &:focus {
        outline: none;
        border-color: #1A84EE;
      }

      &[type="number"] {
        text-align: center;
      }
    }
  }
}

.color-presets {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 6px;

  .preset-color {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 4px;
    border: 1px solid #E2E6EC;
    cursor: pointer;
    transition: transform 0.2s;

    &:hover {
      transform: scale(1.1);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }
  }
}

.gradient-preview {
  width: 100%;
  height: 60px;
  border-radius: 4px;
  border: 1px solid #E2E6EC;
}

.gradient-type-selector {
  display: flex;
  gap: 8px;

  .type-btn {
    flex: 1;
    padding: 8px;
    border: 1px solid #E2E6EC;
    background: #ffffff;
    border-radius: 4px;
    font-size: 13px;
    color: #354052;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      border-color: #1A84EE;
    }

    &.active {
      background: #1A84EE;
      border-color: #1A84EE;
      color: #ffffff;
    }
  }
}

.gradient-angle {
  display: flex;
  align-items: center;
  gap: 12px;

  label {
    font-size: 13px;
    color: #354052;
    font-weight: 500;
  }

  input[type="range"] {
    flex: 1;
  }

  span {
    font-size: 13px;
    color: #7F8FA4;
    min-width: 40px;
    text-align: right;
  }
}

.gradient-stops {
  display: flex;
  flex-direction: column;
  gap: 8px;

  .gradient-stop {
    display: flex;
    align-items: center;
    gap: 8px;

    .stop-color {
      width: 24px;
      height: 24px;
      border-radius: 4px;
      border: 1px solid #E2E6EC;
      cursor: pointer;
      transition: transform 0.2s;

      &:hover {
        transform: scale(1.1);
      }
    }

    input[type="range"] {
      flex: 1;
    }

    .remove-stop {
      width: 24px;
      height: 24px;
      border: 1px solid #E2E6EC;
      background: #ffffff;
      border-radius: 4px;
      color: #FF0040;
      font-size: 18px;
      line-height: 1;
      cursor: pointer;
      transition: all 0.2s;

      &:hover {
        background: #FF0040;
        border-color: #FF0040;
        color: #ffffff;
      }
    }
  }
}

.add-stop-btn {
  width: 100%;
  padding: 8px;
  border: 1px dashed #E2E6EC;
  background: #f9f9f9;
  border-radius: 4px;
  font-size: 13px;
  color: #354052;
  cursor: pointer;
  transition: all 0.2s;

  &:hover {
    border-color: #1A84EE;
    background: #f0f7ff;
    color: #1A84EE;
  }
}

.stop-color-editor {
  padding: 12px;
  background: #f9f9f9;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  gap: 8px;

  label {
    font-size: 13px;
    color: #354052;
    font-weight: 500;
  }

  input {
    width: 100%;
    padding: 8px;
    border: 1px solid #E2E6EC;
    border-radius: 4px;
    font-size: 13px;
    color: #354052;
    background: #ffffff;

    &:focus {
      outline: none;
      border-color: #1A84EE;
    }
  }
}
</style>
