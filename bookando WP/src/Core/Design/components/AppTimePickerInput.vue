<!-- src/Core/Design/components/AppTimePickerInput.vue -->
<template>
  <div
    class="bookando-form-group"
    :class="{ 'bookando-form-group--error': !isvalid }"
  >
    <div class="bookando-input-wrap bookando-flex bookando-items-center bookando-gap-xxs">
      <input
        v-model="zeitText"
        type="text"
        class="bookando-control"
        :placeholder="platzhalter"
        :aria-invalid="!isvalid"
        @blur="onBlurValidate"
        @input="onLiveFilter"
        @keydown.enter.prevent="onEnterCommit"
      >

      <!-- Icon öffnet den Time-Picker -->
      <button
        type="button"
        class="bookando-btn-icon"
        :aria-label="t('ui.time.select') || 'Zeit wählen'"
        @click="openPicker"
      >
        <!-- ersetze bei dir durch <AppIcon name="clock" /> -->
        <svg
          viewBox="0 0 24 24"
          width="18"
          height="18"
          aria-hidden="true"
        >
          <circle
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            fill="none"
          />
          <polyline
            points="12 6 12 12 16 14"
            stroke="currentColor"
            fill="none"
          />
        </svg>
      </button>
    </div>

    <div
      v-if="!isvalid"
      class="bookando-form-error"
    >
      {{ t('ui.time.invalid_format_hint') || 'Bitte eine gültige Zeit im Format HH:MM eingeben.' }}
    </div>

    <!-- Unsichtbarer VueDatePicker nur als Menü -->
    <VueDatePicker
      ref="dp"
      v-model="dpDate"
      :locale="locale"
      :time-picker="true"
      :auto-apply="true"
      :text-input="false"
      :hide-input-icon="true"
      :teleport="true"
      :enable-time-picker="true"
      input-class="bookando-control"
      menu-class="bookando-z-portal"
      :close-on-auto-apply="true"
      style="position:fixed; left:-9999px; top:-9999px; opacity:0; pointer-events:none;"
      @update:model-value="onPickerUpdate"
      @closed="onPickerClosed"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import VueDatePicker from '@vuepic/vue-datepicker'

const props = withDefaults(defineProps<{
  modelValue: string
  platzhalter?: string
  liveFeedback?: boolean
}>(), {
  modelValue: '',
  platzhalter: 'HH:MM',
  liveFeedback: true
})

const emit = defineEmits<{ (event:'update:modelValue', value:string): void }>()
const { t, locale } = useI18n()

const zeitText = ref(props.modelValue || '')
const isvalid = ref(true)
const dp = ref<InstanceType<typeof VueDatePicker> | null>(null)
const dpDate = ref<Date | null>(null)

watch(() => props.modelValue, (value) => {
  if (value !== zeitText.value) zeitText.value = value || ''
})

/* ---- Helpers ---- */
function hhmmToDate(hhmm:string): Date | null {
  const m = /^([01]?\d|2[0-3]):([0-5]\d)$/.exec(hhmm.trim())
  if (!m) return null
  const d = new Date()
  d.setHours(parseInt(m[1],10), parseInt(m[2],10), 0, 0)
  return d
}
function dateToHHMM(d:Date): string {
  const p = (n:number)=>String(n).padStart(2,'0')
  return `${p(d.getHours())}:${p(d.getMinutes())}`
}

function openPicker() {
  // Falls bereits Text vorhanden & gültig → Picker auf diese Zeit setzen
  const d = hhmmToDate(zeitText.value)
  dpDate.value = d || new Date()
  dp.value?.openMenu()
}

function onPickerUpdate(val:Date | null) {
  if (!val) return
  const formatted = dateToHHMM(val)
  zeitText.value = formatted
  isvalid.value = true
  emit('update:modelValue', formatted)
}

function onPickerClosed() {
  // nichts nötig; Input behält Kontrolle
}

function onLiveFilter(e: Event) {
  if (!props.liveFeedback) return
  const target = e.target as HTMLInputElement
  // nur Ziffern + : oder .
  target.value = target.value.replace(/[^0-9:.]/g, '')
  zeitText.value = target.value
}

function onEnterCommit() {
  onBlurValidate()
}

function onBlurValidate() {
  const raw = (zeitText.value || '').trim()
  if (!raw) {
    isvalid.value = true
    emit('update:modelValue', '')
    return
  }
  // Punkte erlauben, intern zu :
  const normalized = raw.replace('.', ':')
  const m = /^([01]?\d|2[0-3]):([0-5]\d)$/.exec(normalized)
  isvalid.value = !!m
  if (!isvalid.value) return

  const h = String(m![1]).padStart(2,'0')
  const mm = String(m![2]).padStart(2,'0')
  const out = `${h}:${mm}`
  zeitText.value = out
  emit('update:modelValue', out)
}
</script>

<style scoped>
.bookando-input-wrap .bookando-btn-icon {
  border: 0;
  background: transparent;
  cursor: pointer;
  padding: 0 .25rem;
  line-height: 1;
}
.bookando-z-portal { z-index: 2200; }
</style>
