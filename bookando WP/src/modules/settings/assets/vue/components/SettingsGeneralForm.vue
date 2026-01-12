<!-- SettingsGeneralForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="settings-general-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <!-- HEADER -->
      <template #header>
        <h2 id="settings-general-title">
          {{ t('mod.settings.general_form.title') }}
        </h2>
        <AppButton
          icon="x"
          btn-type="icononly"
          variant="standard"
          size="square"
          icon-size="md"
          :aria-label="t('core.common.close')"
          @click="onCancel"
        />
      </template>

      <!-- BODY -->
      <template #default>
        <form
          :id="formId"
          class="bookando-form"
          novalidate
          autocomplete="off"
          @submit.prevent="onSubmit"
        >
          <!-- Sprache -->
          <BookandoField
            id="language"
            v-model="form.lang"
            :label="t('core.fields.language')"
            type="dropdown"
            searchable
            :options="languageOptions"
            option-label="label"
            option-value="code"
            show-flag
            required
            mode="flag-label"
            clearable
          />

          <p
            class="bookando-text-muted bookando-mt-xs"
            style="font-size:0.93em;"
          >
            <span v-if="form.lang === 'system' || !form.lang">
              {{ t('mod.settings.general_form.system_language_named', { lang: wpLangLabel }) }}
            </span>
          </p>

          <!-- Limits -->
          <BookandoField
            id="limit_appointments"
            v-model="form.limits.max_appointments"
            :label="t('mod.settings.general_form.limits.max_appointments')"
            type="number"
            :min="0"
            :placeholder="t('mod.settings.general_form.limits.zero_hint')"
          />
          <BookandoField
            id="limit_packages"
            v-model="form.limits.max_packages"
            :label="t('mod.settings.general_form.limits.max_packages')"
            type="number"
            :min="0"
            :placeholder="t('mod.settings.general_form.limits.zero_hint')"
          />
          <BookandoField
            id="limit_events"
            v-model="form.limits.max_events"
            :label="t('mod.settings.general_form.limits.max_events')"
            type="number"
            :min="0"
            :placeholder="t('mod.settings.general_form.limits.zero_hint')"
          />

          <div
            v-if="error"
            class="bookando-alert bookando-alert--danger bookando-mt-sm"
            role="alert"
            aria-live="assertive"
          >
            {{ t('mod.settings.messages.save_error') }}
          </div>
          <div
            v-if="saved"
            class="bookando-alert bookando-alert--success bookando-mt-sm"
            role="status"
            aria-live="polite"
          >
            {{ t('core.common.saved') }}
          </div>
        </form>
      </template>

      <!-- FOOTER (sticky) -->
      <template #footer>
        <div class="bookando-form-buttons bookando-form-buttons--split">
          <AppButton
            btn-type="textonly"
            variant="secondary"
            size="dynamic"
            type="button"
            @click="onCancel"
          >
            {{ t('core.common.cancel') }}
          </AppButton>

          <AppButton
            btn-type="full"
            variant="primary"
            size="dynamic"
            type="submit"
            :form="formId"
          >
            {{ t('core.common.save') }}
          </AppButton>
        </div>
      </template>
    </AppForm>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import { getSettings, saveSettings, BookandoSettingsAction } from '../api/SettingsApi'
import { messages } from '@core/Design/i18n'
import { getSupportedLangs } from '@core/Design/data/language-mapping'
import { getLanguages } from '@core/Design/data/languages'
import { applyGlobalLocale } from '@core/Locale/bridge'

const { t, locale } = useI18n()
const emit = defineEmits(['close'])

const formId = `bookando-form-${Math.random().toString(36).slice(2, 8)}`
const saved = ref(false)
const error = ref(false)

const wpLangCode = (window.BOOKANDO_VARS?.lang ?? 'de').split('_')[0]
const wpLangMap: Record<string, string> = { de: 'Deutsch', en: 'English', fr: 'Français', it: 'Italiano' }
const wpLangLabel = wpLangMap[wpLangCode] || (window.BOOKANDO_VARS?.lang || 'System')

const languageOptions = computed(() => [
  { code: 'system', label: t('mod.settings.general_form.system_language_named', { lang: wpLangLabel }) },
  ...getLanguages(getSupportedLangs(), locale.value)
])

const form = ref({
  lang: 'system',
  limits: { max_appointments: 0, max_packages: 0, max_events: 0 }
})

onMounted(async () => {
  try {
    const res = await getSettings(BookandoSettingsAction.GENERAL)
    form.value.lang = !res?.lang ? 'system' : res.lang
    form.value.limits.max_appointments = res?.limits?.max_appointments ?? 0
    form.value.limits.max_packages     = res?.limits?.max_packages ?? 0
    form.value.limits.max_events       = res?.limits?.max_events ?? 0
  } catch {
    // nur UI-Hinweis, nicht fatal
  }
})

async function onSubmit() {
  error.value = false
  try {
    await saveSettings(BookandoSettingsAction.GENERAL, {
      lang: form.value.lang === 'system' ? null : form.value.lang,
      limits: {
        max_appointments: Number(form.value.limits.max_appointments) || 0,
        max_packages: Number(form.value.limits.max_packages) || 0,
        max_events: Number(form.value.limits.max_events) || 0
      }
    })

    const chosenRaw =
      form.value.lang === 'system'
        ? ((window as any).BOOKANDO_VARS?.wp_locale || navigator.language || 'en')
        : form.value.lang
    applyGlobalLocale(chosenRaw)

    saved.value = true
    setTimeout(() => (saved.value = false), 1200)
    emit('close')
  } catch {
    error.value = true
  }
}

function onCancel() { emit('close') }
</script>
