<!-- AppPhoneInput.vue -->
<template>
  <div
    ref="wrapper"
    class="phone-field-wrapper"
    role="combobox"
    :aria-expanded="dropdownOpen.toString()"
    aria-haspopup="listbox"
    :aria-owns="dropdownOpen ? countryListId : null"
    :class="{
      'has-dropdown': dropdownOpen,
      'bookando-control--danger': !!error,
      'bookando-control--readonly': readonly,
      'bookando-control--disabled': disabled
    }"
    style="position:relative"
  >
    <!-- 📍 Flag + Dial + Chevron / Clear -->
    <button
      type="button"
      class="phone-combobox-container"
      :aria-label="showClearCountry
        ? (t('ui.phone.clear_country') || 'Vorwahl zurücksetzen')
        : (t('ui.phone.choose_country') || 'Ländervorwahl wählen')"
      :tabindex="disabled ? -1 : 0"
      :disabled="disabled"
      @click="showClearCountry ? clearCountry() : (!disabled && !readonly && toggleDropdown())"
    >
      <span class="flag">{{ selectedCountry.flag }}</span>
      <span class="dial">{{ selectedCountry.dial_code }}</span>

      <!-- 'X' löscht zur Default-Vorwahl (nicht die Nummer) -->
      <AppIcon
        v-if="showClearCountry"
        name="x"
        class="bookando-icon--clear"
        :aria-label="t('ui.phone.clear_country') || 'Vorwahl zurücksetzen'"
        role="button"
        tabindex="0"
        @click.stop="clearCountry"
        @keydown.enter.stop="clearCountry"
        @keydown.space.prevent="clearCountry"
      />
      <AppIcon
        v-else
        name="chevron-down"
        class="bookando-icon--select"
        aria-hidden="true"
      />
    </button>

    <!-- 📞 Nummer -->
    <input
      :id="inputId"
      v-model="localNumber"
      class="phone-field"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :aria-invalid="!!error"
      :aria-describedby="error ? errorId : undefined"
      autocomplete="tel"
      :placeholder="placeholder"
      :name="name"
    >

    <!-- 🔽 Dropdown -->
    <div
      v-show="dropdownOpen"
      :id="countryListId"
      class="bookando-combobox-list"
      role="listbox"
      :style="{ position: 'absolute', left: 0, top: 'calc(100% + 2px)', width: '100%', zIndex: 2100 }"
    >
      <input
        v-model="search"
        type="text"
        class="bookando-dropdown-search"
        :placeholder="t('ui.search.placeholder') || 'Suchen...'"
        :aria-label="t('ui.search.countries') || 'Ländersuche'"
      >
      <div
        v-for="c in filteredCountries"
        :key="c.code"
        class="dropdown-option"
        role="option"
        :aria-selected="selectedCountry.code === c.code"
        @click="selectCountry(c)"
      >
        <span class="flag">{{ c.flag }}</span>
        <span class="option-label">
          {{ c.name }}
          <span
            v-if="c.code"
            class="country-code"
          >({{ c.code }})</span>
        </span>
        <span class="dial">{{ c.dial_code }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * AppPhoneInput – mit Default-Land Unterstützung
 * - Prop `default-country="CH"` o.ä. setzt die Start-/Reset-Vorwahl.
 * - Falls nicht gesetzt: Mapping aus BOOKANDO_VARS.lang / Browser-Locale.
 * - Fallbacks robust, längste Dial-Prefix-Matches, sortiert per Intl.Collator.
 */

import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { getCountries } from '@core/Design/data/countries-optimized'
import AppIcon from '@core/Design/components/AppIcon.vue'

defineOptions({ inheritAttrs: false })

/** Typen für Länder-Einträge */
interface Country {
  code: string
  name: string
  dial_code: string
  flag?: string
}

const props = defineProps({
  modelValue: { type: String, default: '' }, // z.B. "+41 791234567" ODER nur Nummer
  label: String,
  name: String,
  id: String,
  placeholder: String,
  hint: String,
  error: String,
  disabled: Boolean,
  readonly: Boolean,
  required: Boolean,
  /** Wichtig: kebab-case im Template: default-country="CH" */
  defaultCountry: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])
const { t, locale } = useI18n()

/* --------------------------------------------
   IDs & ARIA
-------------------------------------------- */
const inputId = computed(() => props.id || `phone-input-${Math.random().toString(36).substr(2, 6)}`)
const errorId = computed(() => `${inputId.value}-error`)
const countryListId = computed(() => `${inputId.value}-countries`)

/* --------------------------------------------
   State
-------------------------------------------- */
const wrapper = ref<HTMLElement | null>(null)
const dropdownOpen = ref(false)
const search = ref('')

/* --------------------------------------------
   Länder (sprachabhängig) + Sortierung
-------------------------------------------- */
const collator = computed(() => new Intl.Collator(locale.value, { sensitivity: 'base', numeric: true }))

const rawCountries = ref<Country[]>(getCountries(locale.value) as Country[])
watch(() => locale.value, (loc) => {
  rawCountries.value = getCountries(loc) as Country[]
})

const countries = computed<Country[]>(() =>
  rawCountries.value.slice().sort((a, b) => collator.value.compare(a.name, b.name))
)

/* --------------------------------------------
   Default-Land bestimmen
   Reihenfolge:
   1) explizites Prop `default-country`
   2) Mapping aus BOOKANDO_VARS.lang / navigator.language
   3) erstes Land der Liste
-------------------------------------------- */
function findByCode(code: string | null | undefined): Country | null {
  if (!code) return null
  const up = code.toUpperCase().trim()
  return countries.value.find(c => c.code === up) || null
}
function guessCountryFromLang(): string | null {
  const w: any = (typeof window !== 'undefined') ? (window as any) : {}
  const lang = (w?.BOOKANDO_VARS?.lang || navigator.language || 'en').toLowerCase().replace('_', '-')
  const key = lang.split('-')[0] // 'de', 'fr', 'it', 'en', 'es', ...
  // erste, sinnvolle Zuordnung
  const map: Record<string,string> = {
    'de': 'DE',
    'fr': 'FR',
    'it': 'IT',
    'en': 'US',
    'es': 'ES',
  }
  // Schweiz-/Österreich-Feintuning:
  if (lang.startsWith('de-ch') || lang.startsWith('fr-ch') || lang.startsWith('it-ch')) return 'CH'
  if (lang.startsWith('de-at')) return 'AT'
  if (lang.startsWith('en-gb')) return 'GB'
  return map[key] || null
}

const defaultCountryRef = computed<Country>(() => {
  // 1) explizites Prop
  const byProp = findByCode(props.defaultCountry)
  if (byProp) return byProp
  // 2) Sprache -> Land
  const byLang = findByCode(guessCountryFromLang())
  if (byLang) return byLang
  // 3) Fallback auf erstes Land
  return countries.value[0] ?? { code: '', name: '', dial_code: '', flag: '' }
})

/* --------------------------------------------
   Auswahl + Nummer
-------------------------------------------- */
const selectedCountry = ref<Country>(defaultCountryRef.value)
const localNumber = ref('')

/* "X" nur wenn nicht auf Default stehen */
const showClearCountry = computed(() =>
  selectedCountry.value.code !== defaultCountryRef.value.code
)

/* --------------------------------------------
   Emission der v-model-Ausgabe
-------------------------------------------- */
const emitPlainNumberOnce = ref(false)

watch([localNumber, selectedCountry], ([num, c]) => {
  const cleanNum = String(num || '')
  if (emitPlainNumberOnce.value) {
    emit('update:modelValue', cleanNum)
    emitPlainNumberOnce.value = false
  } else {
    emit('update:modelValue', (c?.dial_code || '') + cleanNum)
  }
})

/* --------------------------------------------
   Utils
-------------------------------------------- */
function findCountryByDialPrefix(value: string): Country | undefined {
  if (!value) return
  let best: Country | undefined
  for (const c of countries.value) {
    if (c.dial_code && value.startsWith(c.dial_code)) {
      if (!best || c.dial_code.length > best.dial_code.length) best = c
    }
  }
  return best
}

/* --------------------------------------------
   Model -> State übernehmen
-------------------------------------------- */
function applyModelToState(model: string) {
  const val = String(model || '').trim()
  const match = findCountryByDialPrefix(val)
  if (match) {
    selectedCountry.value = match
    localNumber.value = val.slice(match.dial_code.length)
  } else {
    // Keine passende Vorwahl → auf Default landen, Nummer roh
    selectedCountry.value = defaultCountryRef.value
    // Wenn Wert bereits mit '+' beginnt, nehme den Teil nach '+' als Nummer
    localNumber.value = val.startsWith('+') ? val.replace(/^\+\d+\s*/, '') : val
  }
}

/* --------------------------------------------
   Lifecycle / Watches
-------------------------------------------- */
onMounted(() => {
  applyModelToState(props.modelValue)
  document.addEventListener('click', onClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
})

watch(
  () => props.modelValue,
  (val) => {
    const current = (selectedCountry.value?.dial_code || '') + localNumber.value
    if (val !== current) applyModelToState(val || '')
  }
)

/* Wenn sich Länder-Liste ODER der berechnete Default ändert,
   versuche aktuelle Auswahl zu behalten – sonst auf neuen Default. */
watch([countries, defaultCountryRef], ([list, def]) => {
  if (!list.length) return
  const keep = list.find(c => c.code === selectedCountry.value.code)
  selectedCountry.value = keep || def
})

/* --------------------------------------------
   UI-Aktionen
-------------------------------------------- */
function toggleDropdown() {
  if (props.disabled || props.readonly) return
  dropdownOpen.value = !dropdownOpen.value
}

function onClickOutside(event: MouseEvent) {
  if (wrapper.value && !wrapper.value.contains(event.target as Node)) {
    dropdownOpen.value = false
  }
}

function selectCountry(c: Country) {
  selectedCountry.value = c
  dropdownOpen.value = false
  search.value = ''
}

function clearCountry() {
  // Setzt nur die Vorwahl auf "Default" zurück – Nummer bleibt erhalten.
  selectedCountry.value = defaultCountryRef.value
  emitPlainNumberOnce.value = true // einmalig die reine Nummer senden
}

/* --------------------------------------------
   Suche
-------------------------------------------- */
const filteredCountries = computed<Country[]>(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return countries.value
  return countries.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.dial_code.toLowerCase().includes(q) ||
    c.code.toLowerCase().includes(q)
  )
})
</script>
