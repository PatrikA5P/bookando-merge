<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="event-form-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <template #header>
        <h2 id="event-form-title">
          {{ form.id ? t('mod.offers.events.edit') || 'Event bearbeiten' : t('mod.offers.events.add') || 'Event anlegen' }}
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
            icon="calendar"
            :title="t('mod.offers.events.details') || 'Eventdetails'"
            layout="stack"
            compact
          >
            <BookandoField
              id="event_title"
              v-model="form.title"
              type="text"
              :label="t('fields.title') || 'Titel'"
              required
            />
            <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
              <AppDatepicker
                v-model="form.start_at"
                type="datetime"
                format="yyyy-MM-dd HH:mm"
                model-type="yyyy-MM-dd HH:mm"
                :clearable="true"
              />
              <AppDatepicker
                v-model="form.end_at"
                type="datetime"
                format="yyyy-MM-dd HH:mm"
                model-type="yyyy-MM-dd HH:mm"
                :clearable="true"
              />
            </div>
            <BookandoField
              id="event_location"
              v-model="form.location"
              type="text"
              :label="t('fields.location') || 'Ort'"
              placeholder="z.B. Seminarraum 1"
            />
            <BookandoField
              id="event_capacity"
              v-model.number="form.capacity"
              type="number"
              min="1"
              step="1"
              :label="t('mod.services.capacity_max') || 'Kapazität'"
            />
            <BookandoField
              id="event_category"
              v-model="form.category_id"
              type="dropdown"
              :label="t('mod.services.category.label') || 'Kategorie'"
              :options="categories"
              option-label="name"
              option-value="id"
              mode="basic"
              clearable
            />
            <BookandoField
              id="event_status"
              v-model="form.status"
              type="dropdown"
              :label="t('fields.status') || 'Status'"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              mode="basic"
            />
            <AppRichTextField
              id="event_description"
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
import AppDatepicker from '@core/Design/components/AppDatepicker.vue'

type Id = number

type Event = {
  id: Id
  title: string
  start_at: string
  end_at: string
  location?: string | null
  capacity?: number | null
  status?: 'active' | 'hidden'
  category_id: Id | null
  description?: string | null
}

type Category = { id: Id; name: string }

const { t } = useI18n()

const props = defineProps<{
  modelValue: Event | null
  categories: Category[]
}>()

const emit = defineEmits<{
  (event: 'save', value: Event): void
  (event: 'cancel'): void
}>()

const formId = `event-${Math.random().toString(36).slice(2, 8)}`
const empty: Event = {
  id: 0,
  title: '',
  start_at: '',
  end_at: '',
  location: '',
  capacity: null,
  status: 'active',
  category_id: null,
  description: '',
}

const form = ref<Event>({ ...empty })

watch(() => props.modelValue, value => {
  form.value = value ? { ...empty, ...value } : { ...empty }
}, { immediate: true })

const statusOptions = computed(() => ([
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' },
]))

const categories = computed(() => props.categories || [])

function onSubmit() {
  emit('save', { ...form.value })
}

function onCancel() {
  emit('cancel')
}
</script>
