<!-- SettingsCompanyForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="company-settings-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <!-- HEADER -->
      <template #header>
        <h2 id="company-settings-title">
          {{ t('mod.settings.cards.company.title') }}
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
          <!-- Logo Upload/Preview -->
          <div class="bookando-flex bookando-items-center bookando-gap-md bookando-mb-md">
            <div
              class="bookando-company-logo bookando-border bookando-rounded bookando-bg-light"
              :title="t('mod.settings.company_form.choose_logo')"
              style="width:72px;height:72px;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden;"
              @click="openMediaDialog"
            >
              <img
                v-if="form.logo_url"
                :src="form.logo_url"
                alt="Logo"
                style="max-width:100%;max-height:100%;"
              >
              <span
                v-else
                class="bookando-avatar bookando-rounded-full"
                style="width:72px;height:72px;display:flex;align-items:center;justify-content:center;"
              >
                <AppIcon
                  name="image"
                  style="font-size:2rem;color:#bbb;"
                />
              </span>
            </div>

            <div class="bookando-flex bookando-flex-col">
              <span class="bookando-text-xs bookando-text-muted">{{ t('mod.settings.company_form.logo') }}</span>
              <AppButton
                v-if="form.logo_url"
                class="bookando-mt-xxs"
                btn-type="textonly"
                variant="secondary"
                size="dynamic"
                type="button"
                @click="removeLogo"
              >
                {{ t('core.common.remove') }}
              </AppButton>
            </div>
          </div>

          <BookandoField
            id="company_name"
            v-model="form.name"
            type="text"
            :label="t('mod.settings.company_form.company_name')"
            required
          />

          <BookandoField
            id="company_address"
            v-model="form.address"
            type="text"
            :label="t('core.fields.address')"
            required
          />

          <BookandoField
            id="company_website"
            v-model="form.website"
            type="url"
            :label="t('core.fields.website')"
            placeholder="https://www.xyz.ch"
          />

          <BookandoField
            id="company_phone"
            v-model="form.phone"
            type="phone"
            :label="t('core.fields.phone')"
            source="countries"
            :default-country="'CH'"
            :placeholder="'z.B. 79 123 45 67'"
          />

          <BookandoField
            id="company_email"
            v-model="form.email"
            type="email"
            :label="t('core.fields.email')"
            placeholder="info@xyz.ch"
            required
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

      <!-- FOOTER -->
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
import { reactive, ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import { getCompanySettings, saveCompanySettings } from '../api/SettingsApi'

const { t } = useI18n()
const emit = defineEmits(['close'])

const formId = `bookando-form-${Math.random().toString(36).slice(2, 8)}`
const saved = ref(false)
const error = ref(false)

const form = reactive({
  logo_url: '',
  name: '',
  address: '',
  website: '',
  phone: '' as any, // phone component may return string or object
  email: ''
})

onMounted(loadCompanySettings)

async function loadCompanySettings() {
  try {
    const data = await getCompanySettings()
    form.logo_url = data?.logo_url || ''
    form.name     = data?.name || ''
    form.address  = data?.address || ''
    form.website  = data?.website || ''
    form.email    = data?.email || ''

    const raw = data?.phone
    if (typeof raw === 'string') {
      form.phone = raw
    } else if (raw && typeof raw === 'object') {
      const dial = String(raw.dial_code ?? raw.dialCode ?? raw.code ?? '')
      const num  = String(raw.number ?? raw.national ?? raw.value ?? raw.phone ?? '')
      form.phone = (dial + num).replace(/\s+/g, '')
    } else {
      form.phone = ''
    }
    if (!form.phone) form.phone = '+41 '
  } catch {
    // ignore
  }
}

async function onSubmit() {
  error.value = false
  try {
    let phone = ''
    if (form.phone && typeof form.phone === 'object') {
      phone = (form.phone.dialCode || '') + (form.phone.number || '')
    } else if (typeof form.phone === 'string') {
      phone = form.phone
    } else {
      phone = '+41'
    }

    await saveCompanySettings({
      logo_url: form.logo_url,
      name: form.name,
      address: form.address,
      website: form.website,
      phone,
      email: form.email
    })

    saved.value = true
    setTimeout(() => (saved.value = false), 1200)
    emit('close')
  } catch {
    error.value = true
  }
}

function removeLogo() { form.logo_url = '' }

function openMediaDialog() {
  const w: any = window as any
  if (w.wp && w.wp.media) {
    const mediaFrame = w.wp.media({
      title: t('mod.settings.company_form.choose_logo'),
      button: { text: t('mod.settings.company_form.use_logo') },
      multiple: false
    })
    mediaFrame.on('select', () => {
      const attachment = mediaFrame.state().get('selection').first().toJSON()
      form.logo_url = attachment.url
    })
    mediaFrame.open()
  } else {
    alert(t('ui.upload.media_unavailable'))
  }
}

function onCancel() { emit('close') }
</script>

<style scoped>
.bookando-company-logo:hover {
  box-shadow: 0 0 0 2px var(--bookando-color-primary, #4F46E5);
  border-color: var(--bookando-color-primary, #4F46E5);
  background: #fafaff;
}
</style>
