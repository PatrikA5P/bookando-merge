<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="templates-form-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <template #header>
        <h2 id="templates-form-title">
          {{ form.id ? t('mod.offers.templates.edit') || 'Vorlage bearbeiten' : t('mod.offers.templates.add') || 'Vorlage anlegen' }}
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
          class="bookando-form services-form"
          novalidate
          autocomplete="off"
          @submit.prevent="onSubmit"
        >
          <section class="services-form__panel">
            <AppServicesFormSection
              icon="layers"
              :title="t('mod.offers.templates.general') || 'Allgemein'"
              :description="t('mod.offers.templates.general_hint') || 'Benennung, Typ und Zuordnung festlegen.'"
              :columns="2"
            >
              <BookandoField
                id="template_name"
                v-model="form.name"
                type="text"
                :label="t('fields.name') || 'Name'"
                required
              />
              <BookandoField
                id="template_type"
                v-model="form.type"
                type="dropdown"
                :label="t('fields.type') || 'Typ'"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                mode="basic"
              />
              <BookandoField
                id="template_status"
                v-model="form.status"
                type="dropdown"
                :label="t('fields.status') || 'Status'"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                mode="basic"
              />
              <BookandoField
                id="template_category"
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
                id="template_tags"
                v-model="form.tag_ids"
                type="dropdown"
                multiple
                searchable
                clearable
                :label="t('mod.services.tags') || 'Schlagwörter'"
                :options="tags"
                option-label="name"
                option-value="id"
                class="services-form__column-span-2"
              />
              <AppRichTextField
                id="template_description"
                v-model="form.description"
                :label="t('fields.description') || 'Beschreibung'"
                :min-height="160"
                class="services-form__column-span-2"
              />
            </AppServicesFormSection>

            <AppServicesFormSection
              icon="sliders"
              :title="t('mod.offers.templates.defaults') || 'Standardwerte'"
              :description="t('mod.offers.templates.defaults_hint') || 'Lege vorausgewählte Werte für neue Kurse oder Dienstleistungen fest.'"
              :columns="2"
            >
              <BookandoField
                id="template_price"
                v-model="form.defaults.price"
                type="number"
                :label="t('fields.price') || 'Preis'"
                min="0"
                step="0.1"
              />
              <BookandoField
                v-if="form.type === 'service'"
                id="template_duration"
                v-model="form.defaults.duration_min"
                type="number"
                :label="t('mod.services.form.duration') || 'Dauer (Minuten)'"
                min="0"
              />
              <BookandoField
                v-else
                id="template_capacity"
                v-model="form.defaults.capacity"
                type="number"
                :label="t('mod.offers.courses.capacity') || 'Kapazität'"
                min="1"
              />
            </AppServicesFormSection>

            <AppServicesFormSection
              icon="sparkles"
              :title="t('mod.offers.templates.options') || 'Optionen'"
              layout="stack"
              compact
            >
              <div class="services-form__toggle-card">
                <BookandoField
                  id="template_share"
                  v-model="form.share_with_team"
                  type="toggle"
                  :label="t('mod.offers.templates.share_with_team') || 'Vorlage mit Team teilen'"
                  :row="true"
                />
                <BookandoField
                  id="template_show_public"
                  v-model="form.available_online"
                  type="toggle"
                  :label="t('mod.offers.templates.available_online') || 'Als Option im Frontend anbieten'"
                  :row="true"
                />
              </div>
            </AppServicesFormSection>
          </section>
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
import { computed, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'
import AppServicesFormSection from '../services/ui/AppServicesFormSection.vue'

import '../services/services-form.scss'

type Id = number

type TemplateDefaults = {
  price: number | null
  duration_min: number | null
  capacity: number | null
}

type Template = {
  id: Id
  name: string
  type: 'course' | 'service'
  status: 'active' | 'archived'
  category_id: Id | null
  tag_ids: Id[]
  description: string
  defaults: TemplateDefaults
  share_with_team: boolean
  available_online: boolean
}

type Category = { id: Id; name: string }

type Tag = { id: Id; name: string }

type Props = {
  modelValue: Partial<Template> | null
  categories: Category[]
  tags: Tag[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'save', value: Template): void
  (event: 'cancel'): void
}>()

const { t } = useI18n()

const formId = `template-${Math.random().toString(36).slice(2, 8)}`

const empty: Template = {
  id: 0,
  name: '',
  type: 'course',
  status: 'active',
  category_id: null,
  tag_ids: [],
  description: '',
  defaults: { price: null, duration_min: 60, capacity: 8 },
  share_with_team: true,
  available_online: true,
}

const form = reactive<Template>({ ...empty })

watch(() => props.modelValue, value => {
  const payload = value ? { ...empty, ...value } : { ...empty }
  form.id = payload.id ?? 0
  form.name = payload.name ?? ''
  form.type = payload.type ?? 'course'
  form.status = payload.status ?? 'active'
  form.category_id = payload.category_id ?? null
  form.tag_ids = Array.isArray(payload.tag_ids) ? [...payload.tag_ids] : []
  form.description = payload.description ?? ''
  form.defaults = {
    price: payload.defaults?.price ?? empty.defaults.price,
    duration_min: payload.defaults?.duration_min ?? empty.defaults.duration_min,
    capacity: payload.defaults?.capacity ?? empty.defaults.capacity,
  }
  form.share_with_team = payload.share_with_team ?? true
  form.available_online = payload.available_online ?? true
}, { immediate: true })

const categories = computed(() => props.categories || [])
const tags = computed(() => props.tags || [])

const typeOptions = computed(() => ([
  { label: t('mod.offers.templates.type_course_single') || 'Kurs', value: 'course' },
  { label: t('mod.offers.templates.type_service_single') || 'Dienstleistung', value: 'service' },
]))

const statusOptions = computed(() => ([
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.archived') || 'Archiviert', value: 'archived' },
]))

function onSubmit() {
  const payload: Template = {
    ...form,
    tag_ids: [...form.tag_ids],
    defaults: { ...form.defaults },
  }
  emit('save', payload)
}

function onCancel() {
  emit('cancel')
}
</script>
