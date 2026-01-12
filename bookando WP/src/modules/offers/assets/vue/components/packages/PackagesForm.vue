<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="package-form-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <template #header>
        <h2 id="package-form-title">
          {{ form.id ? t('mod.offers.packages.edit') || 'Paket bearbeiten' : t('mod.offers.packages.add') || 'Paket anlegen' }}
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

      <template #default>
        <form
          :id="formId"
          class="bookando-form"
          novalidate
          autocomplete="off"
          @submit.prevent="onSubmit"
        >
          <AppServicesFormSection
            icon="layers"
            :title="t('mod.offers.packages.details') || 'Paketdetails'"
            layout="stack"
            compact
          >
            <BookandoField
              id="package_name"
              v-model="form.name"
              type="text"
              :label="t('fields.name') || 'Name'"
              required
            />
            <BookandoField
              id="package_services"
              v-model="form.service_ids"
              type="dropdown"
              multiple
              searchable
              clearable
              :label="t('mod.offers.packages.includes') || 'Enthaltene Leistungen'"
              :options="services"
              option-label="name"
              option-value="id"
            />
            <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
              <BookandoField
                id="package_price"
                v-model.number="form.price"
                type="number"
                min="0"
                step="0.05"
                :label="t('fields.price') || 'Preis'"
              />
              <BookandoField
                id="package_sale_price"
                v-model.number="form.sale_price"
                type="number"
                min="0"
                step="0.05"
                :label="t('mod.services.sale_price') || 'Aktionspreis'"
              />
            </div>
            <BookandoField
              id="package_status"
              v-model="form.status"
              type="dropdown"
              :label="t('fields.status') || 'Status'"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              mode="basic"
            />
            <AppRichTextField
              id="package_description"
              v-model="form.description"
              :label="t('fields.description')"
              :placeholder="t('fields.description')"
              :min-height="200"
            />
          </AppServicesFormSection>
        </form>
      </template>

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
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'
import AppServicesFormSection from '../services/ui/AppServicesFormSection.vue'

type Id = number

type Package = {
  id: Id
  name: string
  service_ids: Id[]
  price: number
  sale_price?: number | null
  currency?: string
  status?: 'active' | 'hidden'
  description?: string | null
}

type Service = { id: Id; name: string }

const { t } = useI18n()

const props = defineProps<{
  modelValue: Package | null
  services: Service[]
}>()

const emit = defineEmits<{
  (event: 'save', value: Package): void
  (event: 'cancel'): void
}>()

const formId = `package-${Math.random().toString(36).slice(2, 8)}`
const empty: Package = {
  id: 0,
  name: '',
  service_ids: [],
  price: 0,
  sale_price: null,
  currency: 'CHF',
  status: 'active',
  description: '',
}

const form = ref<Package>({ ...empty })

watch(() => props.modelValue, value => {
  form.value = value ? { ...empty, ...value } : { ...empty }
}, { immediate: true })

const statusOptions = computed(() => ([
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' },
]))

const services = computed(() => props.services || [])

function onSubmit() {
  emit('save', { ...form.value })
}

function onCancel() {
  emit('cancel')
}
</script>
