<!-- ServicesFormTabForm.vue -->
<template>
  <section
    class="services-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="check-square"
      :title="t('mod.services.form') || 'Buchungsformular'"
      :description="t('mod.services.form_hint') || 'Wähle ein Buchungsformular aus, das bei der Buchung angezeigt werden soll.'"
      layout="stack"
      compact
    >
      <BookandoField
        id="booking_form_id"
        v-model="form.booking_form_id"
        type="dropdown"
        searchable
        clearable
        :label="t('mod.services.booking_form') || 'Buchungsformular'"
        :options="bookingForms"
        option-label="name"
        option-value="id"
      />
      <p class="services-form__hint">
        {{ t('mod.services.form_hint_secondary') || 'Tipp: Buchungsformulare können unter Werkzeuge → Buchungsformulare verwaltet werden.' }}
      </p>
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
const props = defineProps<{ bookingForms: Array<{ id:number; name:string }> }>()
</script>
