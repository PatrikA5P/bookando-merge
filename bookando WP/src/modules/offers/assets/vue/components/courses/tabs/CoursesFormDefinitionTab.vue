<template>
  <section
    class="courses-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="book-open"
      :title="t('mod.offers.courses.definition.section.general') || 'Allgemeine Informationen'"
      :description="t('mod.offers.courses.definition.section.general_hint') || 'Lege den Rahmen deines Kurses fest.'"
      :columns="3"
    >
      <BookandoField
        id="course_mode"
        v-model="form.mode"
        type="dropdown"
        :label="t('mod.offers.courses.mode.label') || 'Format'"
        :options="modeOptions"
        option-label="label"
        option-value="value"
        mode="basic"
      />

      <BookandoField
        id="course_status"
        v-model="form.status"
        type="dropdown"
        :label="t('fields.status') || 'Status'"
        :options="statusOptions"
        option-label="label"
        option-value="value"
        mode="basic"
      />

      <BookandoField
        id="course_max_participants"
        v-model="form.max_participants"
        type="number"
        min="1"
        :label="t('mod.offers.courses.max_participants') || 'Maximal Teilnehmende'"
        :placeholder="t('mod.offers.courses.max_participants_placeholder') || 'z. B. 25'"
      />

      <BookandoField
        id="course_name"
        v-model="form.name"
        type="text"
        :label="t('fields.title') || 'Titel'"
        required
      />

      <BookandoField
        id="course_author"
        v-model="form.author"
        type="text"
        :label="t('mod.offers.courses.author') || 'Autor*in'"
        :placeholder="t('mod.offers.courses.author_placeholder') || 'Name der verantwortlichen Person'"
      />

      <BookandoField
        id="course_difficulty"
        v-model="form.difficulty"
        type="dropdown"
        :label="t('mod.offers.courses.difficulty.label') || 'Schwierigkeitsgrad'"
        :options="difficultyOptions"
        option-label="label"
        option-value="value"
        clearable
        mode="basic"
      />
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="globe"
      :title="t('mod.offers.courses.definition.section.visibility') || 'Sichtbarkeit'"
      :description="t('mod.offers.courses.definition.section.visibility_hint') || 'Steuere, wer deinen Kurs sehen kann.'"
      :columns="3"
    >
      <BookandoField
        id="course_visibility"
        v-model="form.visibility"
        type="dropdown"
        :label="t('mod.offers.courses.visibility.label') || 'Sichtbarkeit'"
        :options="visibilityOptions"
        option-label="label"
        option-value="value"
        mode="basic"
      />

      <BookandoField
        id="course_visibility_from"
        v-model="form.visibility_from"
        type="date"
        :label="t('mod.offers.courses.visibility.from') || 'Anzeige ab'"
        clearable
      />

      <BookandoField
        id="course_visibility_until"
        v-model="form.visibility_until"
        type="date"
        :label="t('mod.offers.courses.visibility.until') || 'Anzeige bis'"
        clearable
      />
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="image"
      :title="t('mod.offers.courses.definition.section.media') || 'Medien'"
      :description="t('mod.offers.courses.definition.section.media_hint') || 'Gestalte den ersten Eindruck deines Kurses.'"
      layout="stack"
      compact
    >
      <div class="courses-form__media">
        <div class="courses-form__cover">
          <div
            class="courses-form__cover-preview"
            :class="{ 'is-empty': !coverPreview }"
            :style="coverPreviewStyle"
          >
            <span v-if="!coverPreview">
              {{ t('mod.offers.courses.cover.placeholder') || 'Noch kein Beitragsbild ausgewählt' }}
            </span>
          </div>
          <div class="courses-form__cover-actions">
            <AppButton
              size="sm"
              variant="secondary"
              icon="upload"
              @click="openCoverDialog"
            >
              {{ t('mod.offers.courses.cover.select') || 'Beitragsbild wählen' }}
            </AppButton>
            <AppButton
              v-if="coverPreview"
              size="sm"
              variant="ghost"
              icon="x"
              @click="removeCover"
            >
              {{ t('core.common.remove') }}
            </AppButton>
          </div>
          <input
            ref="coverInput"
            type="file"
            accept="image/*"
            class="bookando-hide"
            @change="onCoverSelect"
          >
        </div>

        <BookandoField
          id="course_intro_video"
          v-model="form.intro_video_url"
          type="text"
          :label="t('mod.offers.courses.intro_video') || 'Intro-Video'"
          :placeholder="t('mod.offers.courses.intro_video_placeholder') || 'URL zu YouTube, Vimeo oder eigener Datei'"
        />
      </div>
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="tag"
      :title="t('mod.offers.courses.definition.section.classification') || 'Kategorisierung'"
      :description="t('mod.offers.courses.definition.section.classification_hint') || 'Hilf Interessierten beim Finden deines Kursangebots.'"
      :columns="2"
    >
      <BookandoField
        id="course_category"
        v-model="form.category_id"
        type="dropdown"
        :label="t('mod.services.category.label') || 'Kategorie'"
        :options="categories"
        option-label="name"
        option-value="id"
        mode="basic"
        clearable
        searchable
      />

      <BookandoField
        id="course_tags"
        v-model="form.tag_ids"
        type="dropdown"
        multiple
        searchable
        clearable
        :label="t('mod.services.tags') || 'Schlagwörter'"
        :options="tags"
        option-label="name"
        option-value="id"
      />
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="file-text"
      :title="t('fields.description') || 'Beschreibung'"
      :description="t('mod.offers.courses.definition.section.description_hint') || 'Vermittle Inhalt, Mehrwert und Besonderheiten.'"
      layout="stack"
      compact
    >
      <AppRichTextField
        id="course_description"
        v-model="form.description"
        :label="t('fields.description') || 'Beschreibung'"
        :placeholder="t('fields.description') || 'Beschreibung'"
        :min-height="220"
      />
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'
import AppServicesFormSection from '../../services/ui/AppServicesFormSection.vue'
import type {
  CourseFormVm,
  CourseModeOption,
  CourseVisibilityOption,
  CourseDifficultyOption,
  CourseStatusOption,
} from '../CoursesForm.vue'

type Category = { id: number; name: string }
type Tag = { id: number; name: string }

const model = defineModel<CourseFormVm>({ local: false })
const form = computed({
  get: () => model.value!,
  set: value => (model.value = value),
})

const props = defineProps<{
  categories: Category[]
  tags: Tag[]
  modeOptions: CourseModeOption[]
  visibilityOptions: CourseVisibilityOption[]
  difficultyOptions: CourseDifficultyOption[]
  statusOptions: CourseStatusOption[]
}>()

const { t } = useI18n()

const coverInput = ref<HTMLInputElement | null>(null)
const lastPreview = ref<string | null>(null)

const categories = computed(() => props.categories || [])
const tags = computed(() => props.tags || [])
const modeOptions = computed(() => props.modeOptions || [])
const visibilityOptions = computed(() => props.visibilityOptions || [])
const difficultyOptions = computed(() => props.difficultyOptions || [])
const statusOptions = computed(() => props.statusOptions || [])

const coverPreview = computed(() => form.value.cover_image_preview || form.value.cover_image || '')
const coverPreviewStyle = computed(() => coverPreview.value
  ? { backgroundImage: `url(${coverPreview.value})` }
  : {})

watch(coverPreview, (value, prev) => {
  if (prev && prev.startsWith('blob:') && prev !== value) {
    URL.revokeObjectURL(prev)
  }
  lastPreview.value = value
})

onUnmounted(() => {
  if (lastPreview.value && lastPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(lastPreview.value)
  }
})

function openCoverDialog() {
  coverInput.value?.click()
}

function onCoverSelect(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) {
    return
  }

  if (form.value.cover_image_preview && form.value.cover_image_preview.startsWith('blob:')) {
    URL.revokeObjectURL(form.value.cover_image_preview)
  }

  form.value.cover_image_file = file
  form.value.cover_image_preview = URL.createObjectURL(file)
  form.value.cover_image = file.name
  input.value = ''
}

function removeCover() {
  if (form.value.cover_image_preview && form.value.cover_image_preview.startsWith('blob:')) {
    URL.revokeObjectURL(form.value.cover_image_preview)
  }
  form.value.cover_image_preview = null
  form.value.cover_image_file = null
  form.value.cover_image = null
}
</script>
