<!-- ServicesFormTabNotifications.vue -->
<template>
  <section
    class="services-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="bell"
      :title="t('mod.services.notifications') || 'Benachrichtigungen & Workflow'"
      :description="t('mod.services.notifications_hint') || 'Automatisiere Erinnerungen und Follow-ups für deine Kundschaft.'"
      layout="stack"
    >
      <div class="services-form__toggle-card">
        <div class="services-form__toggle-row">
          <BookandoField
            v-model="form.send_calendar_invite"
            :label="t('mod.services.send_calendar_invite') || 'Kalendereinladung (ICS) senden'"
            type="toggle"
            :row="true"
          />
          <span class="services-form__hint">
            {{ t('mod.services.send_calendar_invite_hint') || 'Kalendereinladungen helfen Kund*innen, Termine direkt im Kalender zu sichern.' }}
          </span>
        </div>
      </div>

      <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
        <div class="services-form__toggle-card">
          <div class="services-form__toggle-row">
            <BookandoField
              v-model="form.email_reminder_enabled"
              :label="t('mod.services.email_reminder') || 'E-Mail Erinnerung'"
              type="toggle"
              :row="true"
            />
            <span class="services-form__hint">
              {{ t('mod.services.email_reminder_hint') || 'Sende automatische Erinnerungen vor dem Termin.' }}
            </span>
          </div>
          <div
            v-if="form.email_reminder_enabled"
            class="services-form__grid services-form__grid--two services-form__grid--stretch"
          >
            <BookandoField
              id="email_reminder_value"
              v-model.number="form.email_reminder_value"
              type="number"
              min="1"
              step="1"
              :label="t('mod.services.reminder_when') || 'Vor dem Termin'"
            />
            <BookandoField
              id="email_reminder_unit"
              v-model="form.email_reminder_unit"
              type="dropdown"
              :label="t('mod.services.unit') || 'Einheit'"
              :options="periodUnits"
              option-label="label"
              option-value="value"
              mode="basic"
            />
          </div>
        </div>

        <div class="services-form__toggle-card">
          <div class="services-form__toggle-row">
            <BookandoField
              v-model="form.sms_reminder_enabled"
              :label="t('mod.services.sms_reminder') || 'SMS-Erinnerung'"
              type="toggle"
              :row="true"
            />
            <span class="services-form__hint">
              {{ t('mod.services.sms_reminder_hint') || 'Perfekt für Last-Minute-Updates oder Premium-Kundenservice.' }}
            </span>
          </div>
          <div
            v-if="form.sms_reminder_enabled"
            class="services-form__grid services-form__grid--two services-form__grid--stretch"
          >
            <BookandoField
              id="sms_reminder_value"
              v-model.number="form.sms_reminder_value"
              type="number"
              min="1"
              step="1"
              :label="t('mod.services.reminder_when') || 'Vor dem Termin'"
            />
            <BookandoField
              id="sms_reminder_unit"
              v-model="form.sms_reminder_unit"
              type="dropdown"
              :label="t('mod.services.unit') || 'Einheit'"
              :options="periodUnits"
              option-label="label"
              option-value="value"
              mode="basic"
            />
          </div>
        </div>

        <div class="services-form__toggle-card services-form__column-span-all">
          <div class="services-form__toggle-row">
            <BookandoField
              v-model="form.followup_email_enabled"
              :label="t('mod.services.followup_email') || 'E-Mail zur Nachbereitung'"
              type="toggle"
              :row="true"
            />
            <span class="services-form__hint">
              {{ t('mod.services.followup_hint') || 'Halte Kund*innen nach dem Termin informiert oder bitte um Feedback.' }}
            </span>
          </div>
          <div
            v-if="form.followup_email_enabled"
            class="services-form__grid services-form__grid--two services-form__grid--stretch"
          >
            <BookandoField
              id="followup_email_value"
              v-model.number="form.followup_email_value"
              type="number"
              min="1"
              step="1"
              :label="t('mod.services.followup_when') || 'Nach dem Termin'"
            />
            <BookandoField
              id="followup_email_unit"
              v-model="form.followup_email_unit"
              type="dropdown"
              :label="t('mod.services.unit') || 'Einheit'"
              :options="periodUnits"
              option-label="label"
              option-value="value"
              mode="basic"
            />
          </div>
        </div>
      </div>
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppServicesFormSection from '../ui/AppServicesFormSection.vue'
import type { ServiceFormVm } from '../ServicesForm.vue'

const { t } = useI18n()
const model = defineModel<ServiceFormVm>({ local: false })
const form = computed({ get: () => model.value!, set: v => (model.value = v) })
defineProps<{ periodUnits: Array<{label:string; value:string}> }>()
</script>
