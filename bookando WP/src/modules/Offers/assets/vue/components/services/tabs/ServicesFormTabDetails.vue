<!-- ServicesFormTabDetails.vue -->
<template>
  <section
    class="services-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      :title="t('mod.services.form.details.section_title') || 'Basisinformationen'"
      :description="t('mod.services.form.details.section_hint') || 'Vergebe einen prägnanten Namen und ordne die Dienstleistung ein.'"
      icon="id-card"
      :columns="3"
    >
      <div
        class="services-form__avatar-block"
        :style="{ '--avatar-ring': form.avatar_border || '#12DE9D' }"
      >
        <AppAvatar
          class="bookando-avatar--ring"
          :src="form.avatar_url"
          :initials="(form.name || 'SR').slice(0,2).toUpperCase()"
          size="xl"
          :can-upload="true"
          :can-remove="!!form.avatar_url"
          @upload="openAvatarDialog"
          @remove="removeAvatar"
        />
        <input
          ref="avatarInput"
          type="file"
          accept="image/*"
          class="bookando-hide"
          @change="onAvatarFileSelect"
        >
        <input
          ref="borderPicker"
          type="color"
          class="bookando-hide"
          :value="form.avatar_border"
          @input="onBorderPick"
        >
        <div class="services-form__avatar-actions">
          <AppButton
            size="sm"
            variant="secondary"
            icon="upload"
            @click="openAvatarDialog"
          >
            {{ t('mod.services.form.details.upload_avatar') || 'Bild auswählen' }}
          </AppButton>
          <AppButton
            v-if="form.avatar_url"
            size="sm"
            variant="ghost"
            icon="x"
            @click="removeAvatar"
          >
            {{ t('core.common.remove') }}
          </AppButton>
          <AppButton
            size="sm"
            variant="ghost"
            icon="droplet"
            @click="borderPicker?.click()"
          >
            {{ t('mod.services.avatar_border') || 'Randfarbe' }}
          </AppButton>
        </div>
        <p class="services-form__hint">
          {{ t('mod.services.form.details.avatar_hint') || 'Tipp: Bilder im Querformat wirken besonders gut.' }}
        </p>
      </div>

      <div class="services-form__column-span-2">
        <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
          <BookandoField
            id="name"
            v-model="form.name"
            type="text"
            :label="t('fields.name')"
            required
          />

          <BookandoField
            id="category_id"
            v-model="form.category_id"
            type="dropdown"
            :label="t('mod.services.category.label')"
            :options="categories"
            option-label="name"
            option-value="id"
            clearable
            searchable
            mode="basic"
          />

          <div class="services-form__toggle-row services-form__column-span-all">
            <BookandoField
              v-model="form.show_on_website"
              :label="t('mod.services.form.details.show_on_website')"
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
                  {{ t('mod.services.form.details.show_on_website_hint') }}
                </div>
              </template>
            </AppTooltip>
          </div>

          <BookandoField
            id="recurrence"
            v-model="form.recurrence_mode"
            type="dropdown"
            :label="t('mod.services.form.details.recurrence')"
            :options="recurrenceOpts"
            option-label="label"
            option-value="value"
            mode="basic"
          />
        </div>
      </div>
    </AppServicesFormSection>

    <AppServicesFormSection
      :title="t('mod.services.form.details.assignment_title') || 'Zuordnung'"
      :description="t('mod.services.form.details.assignment_hint') || 'Definiere, wo und von wem die Dienstleistung angeboten wird.'"
      icon="map"
      :columns="2"
    >
      <BookandoField
        id="location_ids"
        v-model="form.location_ids"
        type="dropdown"
        multiple
        searchable
        clearable
        :label="t('mod.services.form.details.locations')"
        :options="locations"
        option-label="name"
        option-value="id"
      />

      <BookandoField
        id="employee_ids"
        v-model="form.employee_ids"
        type="dropdown"
        multiple
        searchable
        clearable
        :label="t('mod.services.form.details.employees')"
        :options="employees"
        option-label="full_name"
        option-value="id"
      />
    </AppServicesFormSection>

    <AppServicesFormSection
      :title="t('fields.description') || 'Beschreibung'"
      :description="t('mod.services.form.details.description_hint') || 'Beschreibe Nutzen, Ablauf und Besonderheiten für Kund*innen.'"
      icon="file-text"
      layout="stack"
      compact
    >
      <AppRichTextField
        id="description"
        v-model="form.description"
        :label="t('fields.description')"
        :placeholder="t('fields.description')"
        :min-height="220"
      />
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'
import AppTooltip from '@core/Design/components/AppTooltip.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppServicesFormSection from '../ui/AppServicesFormSection.vue'
import httpBase from '@assets/http'
import type { ServiceFormVm } from '../ServicesForm.vue'

const http = httpBase.module('offers')
const { t } = useI18n()

/* v-model */
const model = defineModel<ServiceFormVm>({ local: false })
const form = computed({
  get: () => model.value!,
  set: (value) => (model.value = value)
})

/* Props */
defineProps<{
  categories: Array<{ id:number; name:string }>,
  locations: Array<{ id:number; name:string }>,
  employees: Array<{ id:number; full_name:string }>,
  periodUnits: Array<{label:string; value:string}>
}>()

const recurrenceOpts = [
  { label: t('core.common.all'), value: 'all' },
  { label: t('ui.recurring.daily'),   value: 'daily' },
  { label: t('ui.recurring.weekly'),  value: 'weekly' },
  { label: t('ui.recurring.monthly'), value: 'monthly' },
  { label: t('core.common.disabled'), value: 'disabled' },
] as const

/* Avatar logic */
const avatarInput = ref<HTMLInputElement|null>(null)
const borderPicker = ref<HTMLInputElement|null>(null)

function openAvatarDialog(){ avatarInput.value?.click() }
function onBorderPick(event: Event){
  const val = (event.target as HTMLInputElement).value
  form.value.avatar_border = val
}
function onAvatarFileSelect(ev: Event){
  const input = ev.target as HTMLInputElement
  const file = input.files?.[0]; if (!file) return
  if (!file.type.startsWith('image/')) return
  if (!form.value.id){ form.value.avatar_url = URL.createObjectURL(file); input.value=''; return }
  uploadAvatar(file); input.value=''
}
async function uploadAvatar(file: File){
  try{
    const fd = new FormData(); fd.append('avatar', file)
    const { data } = await http.post<any>(`bookando/v1/services/${Number(form.value.id)}/avatar`, fd, { absolute: true })
    form.value.avatar_url = data?.avatar_url || form.value.avatar_url
  }catch(error){
    console.warn('[services] Avatar upload failed', error)
  }
}
async function removeAvatar(){
  if (!form.value.id){ form.value.avatar_url=''; return }
  try{
    await http.del<any>(`bookando/v1/services/${Number(form.value.id)}/avatar`, undefined, { absolute: true })
    form.value.avatar_url=''
  }catch(error){
    console.warn('[services] Avatar removal failed', error)
  }
}
</script>
