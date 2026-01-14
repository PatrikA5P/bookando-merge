<!-- ServicesFormTabSettings.vue -->
<template>
  <section
    class="services-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="settings"
      :title="t('mod.services.form.settings.title') || 'Buchungsregeln'"
      :description="t('mod.services.form.settings.description') || 'Steuere Standardstatus und Zeitfenster für Buchungen.'"
      layout="stack"
    >
      <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
        <BookandoField
          id="default_status"
          v-model="form.default_appointment_status"
          type="dropdown"
          :label="t('mod.services.form.settings.default_status')"
          :options="statusOpts"
          option-label="label"
          option-value="value"
          mode="basic"
        />
        <BookandoField
          id="booking_window_mode"
          v-model="form.booking_window_mode"
          type="dropdown"
          :label="t('mod.services.form.settings.booking_window')"
          :options="bookingWindowModes"
          option-label="label"
          option-value="value"
          mode="basic"
        />
      </div>

      <div
        v-if="form.booking_window_mode === 'max_days'"
        class="services-form__grid services-form__grid--two services-form__grid--stretch"
      >
        <BookandoField
          id="max_days_value"
          v-model.number="form.booking_window_value"
          type="number"
          min="1"
          step="1"
          :label="t('mod.services.max_days')"
        />
        <BookandoField
          id="max_days_type"
          v-model="form.booking_window_type"
          type="dropdown"
          :label="t('mod.services.day_type')"
          :options="[
            { label: t('ui.date.calendar_days') || 'Kalendertage', value: 'calendar' },
            { label: t('ui.date.business_days') || 'Werktage', value: 'business' }
          ]"
          option-label="label"
          option-value="value"
          mode="basic"
        />
      </div>

      <div
        v-else-if="form.booking_window_mode === 'date_range'"
        class="services-form__grid services-form__grid--two services-form__grid--stretch"
      >
        <AppDatepicker
          v-model="form.booking_start"
          type="date"
          format="yyyy-MM-dd"
          model-type="yyyy-MM-dd"
          :clearable="true"
        />
        <AppDatepicker
          v-model="form.booking_end"
          type="date"
          format="yyyy-MM-dd"
          model-type="yyyy-MM-dd"
          :clearable="true"
        />
      </div>
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="clock-3"
      :title="t('mod.services.form.settings.lead_title') || 'Vorlaufzeiten & Weiterleitung'"
      :description="t('mod.services.form.settings.lead_hint') || 'Definiere, wie kurzfristig gebucht, storniert oder verschoben werden darf.'"
      layout="stack"
      compact
    >
      <div class="services-form__stack">
        <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
          <BookandoField
            id="lead_book_val"
            v-model.number="form.lead_time_book_value"
            type="number"
            min="0"
            step="1"
            :label="t('mod.services.form.settings.lead_book')"
          />
          <BookandoField
            id="lead_book_unit"
            v-model="form.lead_time_book_unit"
            type="dropdown"
            :label="t('mod.services.unit')"
            :options="periodUnits"
            option-label="label"
            option-value="value"
            mode="basic"
          />
        </div>
        <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
          <BookandoField
            id="lead_cancel_val"
            v-model.number="form.lead_time_cancel_value"
            type="number"
            min="0"
            step="1"
            :label="t('mod.services.form.settings.lead_cancel')"
          />
          <BookandoField
            id="lead_cancel_unit"
            v-model="form.lead_time_cancel_unit"
            type="dropdown"
            :label="t('mod.services.unit')"
            :options="periodUnits"
            option-label="label"
            option-value="value"
            mode="basic"
          />
        </div>
        <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
          <BookandoField
            id="lead_reschedule_val"
            v-model.number="form.lead_time_reschedule_value"
            type="number"
            min="0"
            step="1"
            :label="t('mod.services.form.settings.lead_reschedule')"
          />
          <BookandoField
            id="lead_reschedule_unit"
            v-model="form.lead_time_reschedule_unit"
            type="dropdown"
            :label="t('mod.services.unit')"
            :options="periodUnits"
            option-label="label"
            option-value="value"
            mode="basic"
          />
        </div>
      </div>

      <BookandoField
        id="redirect_url"
        v-model="form.redirect_url"
        type="url"
        :label="t('mod.services.redirect_url')"
        placeholder="https://…"
      />
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="shield"
      :title="t('mod.services.limit_per_customer')"
      :description="t('mod.services.limit_per_customer_hint')"
      layout="stack"
    >
      <div class="services-form__toggle-card">
        <div class="services-form__toggle-row">
          <BookandoField
            v-model="form.limit_per_customer"
            :label="t('mod.services.limit_per_customer_toggle') || t('mod.services.limit_per_customer')"
            type="toggle"
            :row="true"
          />
          <AppTooltip :delay="250">
            <AppIcon
              name="info"
              class="bookando-text-muted"
            />
            <template #content>
              <div
                class="bookando-tooltip-p-sm"
                style="max-width:280px;"
              >
                {{ t('mod.services.limit_per_customer_hint') }}
              </div>
            </template>
          </AppTooltip>
        </div>

        <transition name="accordion">
          <div
            v-if="form.limit_per_customer"
            class="services-form__stack"
          >
            <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
              <BookandoField
                id="limit_count"
                v-model.number="form.limit_count"
                type="number"
                min="1"
                step="1"
                :label="t('mod.services.limit_count')"
              />
              <BookandoField
                id="limit_period_value"
                v-model.number="form.limit_period_value"
                type="number"
                min="1"
                step="1"
                :label="t('mod.services.limit_period_value')"
              />
            </div>
            <BookandoField
              id="limit_period_unit"
              v-model="form.limit_period_unit"
              type="dropdown"
              :label="t('mod.services.limit_period_unit')"
              :options="periodUnits"
              option-label="label"
              option-value="value"
              mode="basic"
            />
          </div>
        </transition>
      </div>
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppDatepicker from '@core/Design/components/AppDatepicker.vue'
import AppTooltip from '@core/Design/components/AppTooltip.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppServicesFormSection from '../ui/AppServicesFormSection.vue'
import type { ServiceFormVm } from '../ServicesForm.vue'

const { t } = useI18n()
const model = defineModel<ServiceFormVm>({ local: false })
const form = computed({ get: () => model.value!, set: v => (model.value = v) })

defineProps<{
  statusOpts: Array<{label:string; value:string}>
  bookingWindowModes: Array<{label:string; value:string}>
  periodUnits: Array<{label:string; value:string}>
}>()
</script>
