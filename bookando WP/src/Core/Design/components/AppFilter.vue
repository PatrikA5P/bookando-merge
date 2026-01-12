<!-- AppFilter.vue -->
<template>
  <AppOverlay @close="$emit('close')">
    <template #title>
      {{ t('Filter') }}
    </template>
    <template #actions>
      <AppButton
        icon="refresh-cw"
        variant="standard"
        size="square"
        btn-type="icononly"
        icon-size="md"
        :tooltip="t('Filter zurücksetzen')"
        @click="clearAllFilters"
      />
    </template>

    <!-- Feld-Auswahl -->
    <BookandoField
      v-model="localActiveFilterFields"
      type="dropdown"
      searchable
      :label="t('Filter anzeigen')"
      :options="filterFieldOptions"
      option-label="label"
      option-value="key"
      multiple
      sort="asc"
      clearable
      btn-class="bookando-px-sm"
      :placeholder="t('Felder auswählen…')"
      class="bookando-mb-md"
      :teleport="true"
    />

    <!-- Nur aktive Filter rendern -->
    <div
      v-if="localActiveFilterFields.length"
      class="bookando-form-grid four-columns align-top"
    >
      <BookandoField
        v-for="key in localActiveFilterFields"
        :key="key"
        v-model="localModel[key]"
        type="dropdown"
        searchable
        :label="labels[key] || key"
        :source="domainSource(key)"
        :options="useExternalOptions(key) ? (filters[key] || []) : undefined"
        :option-label="useExternalOptions(key) ? 'label' : undefined"
        :option-value="useExternalOptions(key) ? 'value' : undefined"
        :show-flag="showFlagForKey(key)"
        :mode="modeForKey(key)"
        multiple
        sort="asc"
        grouped
        clearable
        btn-class="bookando-px-sm"
        :placeholder="t('Bitte wählen')"
        :teleport="true"
      />
    </div>
    <div
      v-else
      class="bookando-text-muted bookando-mb-md"
    >
      {{ t('Wähle mindestens ein Filterfeld') }}
    </div>
  </AppOverlay>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from './BookandoField.vue'
import AppButton from './AppButton.vue'
import AppOverlay from '@core/Design/components/AppOverlay.vue'

const { t } = useI18n()

/** ─────────────────────────────────────────────
 * Domain-Helfer: sagen dem Field, wann zentrale
 * Options/Flags/Modi verwendet werden sollen.
 * ───────────────────────────────────────────── */
const DOMAIN_SOURCES = {
  country:  'countries',
  language: 'languages',
  gender:   'genders'
} as const

function domainSource(key: string) {
  // Gibt 'countries' | 'languages' | 'genders' oder undefined zurück
  return (DOMAIN_SOURCES as Record<string, string | undefined>)[key]
}
function useExternalOptions(key: string) {
  // Nur wenn wir KEINE Domain-Quelle haben, nutzen wir filters[key]
  return !domainSource(key)
}
function showFlagForKey(key: string) {
  // Für Länder/Sprachen Flags via BookandoField-Default (undefined = Auto),
  // für andere Felder explizit ohne Flag.
  if (key === 'country' || key === 'language') return undefined // -> BookandoField aktiviert Flag automatisch
  return false
}
function modeForKey(key: string) {
  // Gleiche Logik wie oben: Flag-Labels nur bei Country/Language
  return (key === 'country' || key === 'language') ? 'flag-label' : 'basic'
}

interface FilterFieldOption { key: string; label: string }
const props = defineProps<{
  show: boolean
  filters: Record<string, any[]>
  modelValue: Record<string, any[]>
  labels: Record<string, string>
  filterFieldOptions: FilterFieldOption[]
  activeFilterFields: string[]
}>()

const emit = defineEmits(['update:modelValue', 'update:activeFilterFields', 'clear', 'close'])

// ——— Lokale Spiegel (sauber initialisieren)
const localModel = reactive<Record<string, any[]>>({ ...(props.modelValue || {}) })
const localActiveFilterFields = ref<string[]>([...(props.activeFilterFields || [])])

// ——— Utils: sicher in Plain-Objekte/Arrays konvertieren (kein structuredClone)
function toPlain<T>(value: T): T {
  return JSON.parse(JSON.stringify(value, (_k, val) => {
    if (val instanceof Map || val instanceof Set) return Array.from(val)
    if (typeof val === 'function' || val === undefined) return null
    return val
  })) as T
}

let timer: number | undefined
function debounce(fn: () => void, ms = 180) {
  if (timer) window.clearTimeout(timer)
  timer = window.setTimeout(fn, ms)
}

// ——— Externes modelValue sauber spiegeln (z. B. bei globalem Reset)
watch(() => props.modelValue, (val) => {
  // vollständiger Reset, damit keine veralteten Keys übrig bleiben
  Object.keys(localModel).forEach(k => delete (localModel as any)[k])
  Object.assign(localModel, toPlain(val || {}))
}, { deep: true })

// ——— Aktive Felder: alte Keys entfernen + sofort übernehmen
watch(localActiveFilterFields, (val) => {
  const active = new Set(val)
  for (const key of Object.keys(localModel)) {
    if (!active.has(key)) delete (localModel as any)[key]
  }
  emit('update:activeFilterFields', [...val])
  emit('update:modelValue', toPlain(localModel)) // sofortige Übernahme (entspricht „Übernehmen“)
}, { flush: 'post' })

// ——— Filterwerte: automatisch (debounced) übernehmen
watch(localModel, () => {
  debounce(() => emit('update:modelValue', toPlain(localModel)), 180)
}, { deep: true, flush: 'post' })

function clearAllFilters() {
  for (const key of localActiveFilterFields.value) {
    localModel[key] = []
  }
  emit('update:modelValue', toPlain(localModel)) // sofort übernehmen
  emit('clear')
}
</script>


<!--
Bemerkungen/UX:
- Die Feld-Auswahl ist IMMER sichtbar, auch wenn alle abgewählt sind.
- Es werden nur Felder angezeigt, die aktuell in "Felder auswählen" gewählt sind.
- clearable=true für alle Comboboxen ist korrekt und verbessert die UX.
- Die Filter/aktiven Felder werden "dynamisch" an den Parent und damit an den Store gemeldet – 
  persistieren musst du im Parent/Store (siehe unten)!
-->
