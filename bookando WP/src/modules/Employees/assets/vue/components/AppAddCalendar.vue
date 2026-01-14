<!-- AppAddCalendar.vue -->
<template>
  <!-- Eigenes Overlay, über dem Employees-Dialog (Z-Index via Utilities) -->
  <div
    v-if="open"
    class="bookando-dialog-wrapper active bookando-z-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="addcal-title"
  >
    <div
      class="bookando-form-overlay active bookando-z-overlay"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <!-- HEADER -->
      <template #header>
        <h2 id="addcal-title">
          {{ t('mod.employees.form.calendar.availability') || 'Verfügbarkeit' }}
        </h2>
        <AppButton
          icon="x"
          btn-type="icononly"
          variant="standard"
          size="square"
          icon-size="md"
          @click="onCancel"
        />
      </template>

      <!-- BODY -->
      <template #default>
        <!-- Einleitung -->
        <h3 class="bookando-mt-0 bookando-mb-xxs">
          {{ t('mod.employees.form.calendar.connect_existing') || 'Einen vorhandenen Kalender verbinden' }}
        </h3>
        <p class="bookando-text-sm bookando-text-muted bookando-mb-sm">
          {{
            t('mod.employees.form.calendar.connect_existing_hint')
              || 'Für die Dauer Ihrer Kalenderereignisse werden Sie als nicht verfügbar angezeigt, damit Sie nicht doppelt gebucht werden.'
          }}
        </p>

        <!-- GOOGLE: Kopf + Card -->
        <div class="bookando-flex bookando-items-center bookando-gap-xs bookando-mt-lg bookando-mb-sm">
          <img
            src="https://assets.calendly.com/mfe/mf-publisher/frontend/media/google-calendar-9d502e45f709b07b91a1.svg"
            alt=""
            width="20"
            height="20"
          >
          <div class="bookando-text-md bookando-font-semibold">
            Google
          </div>
        </div>

        <div
          class="bookando-card is-hoverable"
          role="button"
          tabindex="0"
          aria-label="Google-Kalender verbinden"
          @click="startOauth('google')"
          @keydown.enter="startOauth('google')"
          @keydown.space.prevent="startOauth('google')"
        >
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr auto; align-items: center;"
          >
            <div class="bookando-min-w-0">
              <div class="bookando-font-semibold">
                Google-Kalender
              </div>
              <div class="bookando-text-sm bookando-text-muted">
                Gmail, G Suite
              </div>
            </div>
            <!-- Caret rechts, Farbe erbt von Text -->
            <span
              class="bookando-flex bookando-items-center"
              aria-hidden="true"
            >
              <AppIcon
                name="chevron-right"
                :size="20"
              />
            </span>
          </div>
        </div>

        <!-- MICROSOFT: Kopf + Outlook-Card -->
        <div class="bookando-flex bookando-items-center bookando-gap-xs bookando-mt-lg bookando-mb-sm">
          <img
            src="https://assets.calendly.com/mfe/mf-publisher/frontend/media/microsoft-1634db12d61fd3a7f9d9.svg"
            alt=""
            width="20"
            height="20"
          >
          <div class="bookando-text-md bookando-font-semibold">
            Microsoft
          </div>
        </div>

        <div
          class="bookando-card is-hoverable bookando-mt-xxs"
          role="button"
          tabindex="0"
          aria-label="Outlook-Kalender verbinden"
          @click="startOauth('microsoft')"
          @keydown.enter="startOauth('microsoft')"
          @keydown.space.prevent="startOauth('microsoft')"
        >
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr auto; align-items: center;"
          >
            <div class="bookando-min-w-0">
              <div class="bookando-font-semibold">
                Outlook-Kalender
              </div>
              <div class="bookando-text-sm bookando-text-muted">
                Office-365 / Outlook / Hotmail
              </div>
            </div>
            <span
              class="bookando-flex bookando-items-center"
              aria-hidden="true"
            >
              <AppIcon
                name="chevron-right"
                :size="20"
              />
            </span>
          </div>
        </div>

        <!-- Exchange: Accordions (Card-Accordion-Pattern aus deiner SCSS) -->
        <div
          class="bookando-card bookando-card--accordion bookando-mt-xs"
          :aria-expanded="showExchange ? 'true' : 'false'"
        >
          <div
            class="bookando-grid bookando-cursor-pointer"
            style="--bookando-grid-cols: 1fr auto; align-items: center;"
            role="button"
            tabindex="0"
            aria-controls="exchange-panel"
            @click="toggle('exchange')"
            @keydown.enter.prevent="toggle('exchange')"
            @keydown.space.prevent="toggle('exchange')"
          >
            <div class="bookando-min-w-0">
              <div class="bookando-font-semibold">
                Exchange-Kalender
              </div>
              <div class="bookando-text-sm bookando-text-muted">
                Exchange-Server 2013 / 2016 / 2019 (EWS/AutoDiscover)
              </div>
            </div>
            <span
              class="bookando-flex bookando-items-center"
              aria-hidden="true"
            >
              <AppIcon
                v-if="!showExchange"
                name="chevron-right"
                :size="20"
              />
              <AppIcon
                v-else
                name="chevron-up"
                :size="20"
              />
            </span>
          </div>

          <div
            id="exchange-panel"
            class="bookando-card__panel"
            :class="{ 'is-open': showExchange }"
          >
            <div class="bookando-card__panel-inner">
              <div
                class="bookando-grid"
                style="--bookando-grid-cols: 1fr 1fr; gap: .5rem;"
              >
                <BookandoField
                  id="ex_url"
                  v-model="exchange.url"
                  type="text"
                  :label="t('fields.url') || 'URL (EWS/AutoDiscover)'"
                  placeholder="https://mail.domain.tld/EWS/Exchange.asmx"
                />
                <BookandoField
                  id="ex_email"
                  v-model="exchange.email"
                  type="email"
                  :label="t('fields.email') || 'E-Mail'"
                />
                <BookandoField
                  id="ex_user"
                  v-model="exchange.username"
                  type="text"
                  :label="t('fields.username') || 'Benutzername'"
                />
                <BookandoField
                  id="ex_pass"
                  v-model="exchange.password"
                  type="password"
                  :label="t('fields.password') || 'Passwort'"
                />
              </div>
              <div class="bookando-flex bookando-justify-end bookando-mt-sm">
                <AppButton
                  variant="standard"
                  size="dynamic"
                  :disabled="saving"
                  @click="connectExchange"
                >
                  {{ saving ? (t('ui.saving') || 'Speichere…') : (t('mod.employees.form.calendar.connect') || 'Verbinden') }}
                </AppButton>
              </div>
            </div>
          </div>
        </div>

        <!-- APPLE: Kopf + Accordion -->
        <div class="bookando-flex bookando-items-center bookando-gap-xs bookando-mt-lg bookando-mb-sm">
          <img
            src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg"
            alt=""
            width="18"
            height="18"
          >
          <div class="bookando-text-md bookando-font-semibold">
            Apple / iCloud (ICS)
          </div>
        </div>

        <div
          class="bookando-card bookando-card--accordion bookando-mt-xs"
          :aria-expanded="showApple ? 'true' : 'false'"
        >
          <div
            class="bookando-grid bookando-cursor-pointer"
            style="--bookando-grid-cols: 1fr auto; align-items: center;"
            role="button"
            tabindex="0"
            aria-controls="apple-panel"
            @click="toggle('apple')"
            @keydown.enter.prevent="toggle('apple')"
            @keydown.space.prevent="toggle('apple')"
          >
            <div class="bookando-min-w-0">
              <div class="bookando-font-semibold">
                iCloud / ICS
              </div>
              <div class="bookando-text-sm bookando-text-muted">
                Privater ICS-Feed (Lesen) oder E-Mail-Einladungen (.ics)
              </div>
            </div>
            <span
              class="bookando-flex bookando-items-center"
              aria-hidden="true"
            >
              <AppIcon
                v-if="!showApple"
                name="chevron-right"
                :size="20"
              />
              <AppIcon
                v-else
                name="chevron-up"
                :size="20"
              />
            </span>
          </div>

          <div
            id="apple-panel"
            class="bookando-card__panel"
            :class="{ 'is-open': showApple }"
          >
            <div class="bookando-card__panel-inner">
              <div
                class="bookando-grid"
                style="--bookando-grid-cols: 1fr 1fr; gap: .5rem;"
              >
                <BookandoField
                  id="ics_url"
                  v-model="apple.icsUrl"
                  type="text"
                  :label="t('mod.employees.form.calendar.ics_feed') || 'Privater ICS-Feed (Lesen)'"
                  placeholder="webcal://pXX-caldav.icloud.com/published/2/XXXX..."
                />
              </div>
              <div class="bookando-flex bookando-justify-end bookando-gap-sm bookando-mt-sm">
                <AppButton
                  variant="secondary"
                  btn-type="textonly"
                  size="dynamic"
                  :disabled="true"
                  title="Bald verfügbar"
                >
                  {{ t('mod.employees.form.calendar.test_ics') || 'ICS prüfen' }}
                </AppButton>
                <AppButton
                  variant="standard"
                  size="dynamic"
                  :disabled="saving"
                  @click="saveApple"
                >
                  {{ saving ? (t('ui.saving') || 'Speichere…') : (t('core.common.save') || 'Speichern') }}
                </AppButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Rückmeldung -->
        <p
          v-if="feedback"
          class="bookando-text-sm bookando-mt-sm"
          :class="feedbackClass"
        >
          {{ feedback }}
        </p>
      </template>

      <!-- FOOTER -->
      <template #footer>
        <div class="bookando-form-buttons bookando-form-buttons--split">
          <div />
          <AppButton
            variant="secondary"
            btn-type="textonly"
            size="dynamic"
            type="button"
            @click="onCancel"
          >
            {{ t('core.common.close') || 'Schliessen' }}
          </AppButton>
        </div>
      </template>
    </AppForm>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import { apiGet, apiPost } from '@core/Api/apiClient'
import * as EmployeesApi from '../api/EmployeesApi'

const { t } = useI18n()

const props = withDefaults(defineProps<{
  open:boolean;
  employeeId:number|string;
  mode?:'ro'|'wb'
}>(), { open:true, mode:'ro' })

const emit = defineEmits<{
  (event:'close'): void
  (event:'connected', payload: {
    // neu & normalisiert – was der Store/PUT versteht:
    provider: 'google' | 'microsoft' | 'exchange' | 'ics',
    access?: 'ro' | 'rw',
    is_busy_source?: 0 | 1,
    is_default_write?: 0 | 1,
    url?: string,             // für ICS
    calendar_id?: string,     // für OAuth/Exchange
    name?: string | null,
    id?: number,
    connection_id?: number,
    // Backcompat – falls Parent noch alte Keys erwartet:
    calendar?: 'google' | 'outlook' | 'exchange' | 'apple',
    mode?: 'ro' | 'wb',
    token?: string | null,
  }): void
}>()

// UI- und Formularzustände
const saving = ref(false)
const feedback = ref('')
const feedbackClass = computed(() => feedback.value.startsWith('✔') ? 'bookando-text-success' : 'bookando-text-danger')

// lokale Models für Accordion-Bereiche
const apple = ref({ icsUrl: '', inviteEmail: '' })
const exchange = ref({ url: '', email: '', username: '', password: '' })
const showExchange = ref(false)
const showApple = ref(false)

watch(() => props.open, () => { feedback.value = '' })

function onCancel () { emit('close') }
function toggle (which: 'exchange' | 'apple') {
  if (which === 'exchange') showExchange.value = !showExchange.value
  else showApple.value = !showApple.value
}

async function startOauth (provider: 'google' | 'microsoft') {
  try {
    saving.value = true
    const res = await EmployeesApi.startOauth(Number(props.employeeId), provider, props.mode)
    const url = res?.auth_url
    if (!url) throw new Error('No auth_url')
    window.open(url, '_blank', 'noopener')
    feedback.value = '✔ Anmeldefenster geöffnet.'
  } catch (error:any) {
    feedback.value = 'Fehler: ' + (error?.message || String(error))
  } finally { saving.value = false }
}

async function connectExchange () {
  try {
    saving.value = true
    feedback.value = ''
    const res = await apiPost('/wp-json/bookando/v1/integrations/exchange/connect', {
      employee_id: props.employeeId,
      url: exchange.value.url,
      email: exchange.value.email,
      username: exchange.value.username,
      password: exchange.value.password,
      mode: props.mode
    })
    emit('connected', {
      provider: 'exchange',
      calendar_id: res?.calendar_id || exchange.value.email,
      name: res?.name || 'Exchange',
      access: props.mode === 'wb' ? 'rw' : 'ro',
      is_busy_source: 1,
      is_default_write: props.mode === 'wb' ? 1 : 0,
      id: res?.id,
      connection_id: res?.connection_id,
      // backcompat:
      calendar: 'exchange' as const,
      mode: props.mode!,
      token: res?.token || null,
    })
    feedback.value = '✔ Exchange verbunden.'
    emit('close')
  } catch (error: any) {
    feedback.value = 'Fehler: ' + (error?.message || String(error))
  } finally {
    saving.value = false
  }
}

async function testIcsLink () {
  try {
    saving.value = true
    feedback.value = ''
    // (falls Validation benötigt wird) – scoped unter /employees/{id}/...
    const res = await apiGet(`${BOOKANDO_VARS?.rest_url}/${props.employeeId}/calendar/connections/ics/validate`, {
      url: apple.value.icsUrl
    })
    feedback.value = (res?.ok ? '✔ ICS-Feed erreichbar.' : 'ICS-Feed konnte nicht gelesen werden.')
  } catch (error:any) {
    feedback.value = 'Fehler: ' + (error?.message || String(error))
  } finally { saving.value = false }
}

async function saveApple () {
  // Nur RO-ICS verbinden
  try {
    saving.value = true
    feedback.value = ''
    const res = await EmployeesApi.connectIcs(Number(props.employeeId), { url: apple.value.icsUrl })
    emit('connected', {
      provider: 'ics',
      url: (apple.value.icsUrl || '').trim(),
      name: res?.name || 'ICS',
      access: 'ro',
      is_busy_source: 1,
      is_default_write: 0,
      id: res?.id,                    // Kalender-ID lt. Backend
      connection_id: res?.connection_id,
      calendar_id: res?.calendar_id,  // Hash vom Server (optional nützlich im UI)
      // backcompat:
      calendar: 'apple' as const,
      mode: 'ro' as const,
      token: null,
    })
    feedback.value = '✔ Apple/ICS verbunden.'
    emit('close')
  } catch (error:any) {
    feedback.value = 'Fehler: ' + (error?.message || String(error))
  } finally { saving.value = false }
}
</script>
