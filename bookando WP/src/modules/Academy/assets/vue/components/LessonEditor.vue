<template>
  <transition name="fade">
    <div
      class="bookando-academy-modal bookando-academy-modal--large"
      role="dialog"
      aria-modal="true"
      @click.self="$emit('close')"
    >
      <div class="bookando-academy-modal__content">
        <header class="bookando-modal__header">
          <h3 class="bookando-h5 bookando-m-0">
            {{ lesson?.id ? t('mod.academy.actions.edit_lesson') : t('mod.academy.actions.add_lesson') }}
          </h3>
          <AppButton
            icon="x"
            variant="ghost"
            size="square"
            btn-type="icononly"
            icon-size="md"
            @click="$emit('close')"
          />
        </header>

        <form
          class="bookando-academy-modal__form"
          autocomplete="off"
          @submit.prevent="submit"
        >
          <BookandoField
            id="lesson_title"
            v-model="form.title"
            :label="t('mod.academy.labels.lesson_name')"
            required
          />

          <div class="bookando-field">
            <label
              class="bookando-field__label"
              for="lesson_content"
            >
              {{ t('mod.academy.labels.lesson_content') }}
            </label>
            <textarea
              id="lesson_content"
              v-model="form.content"
              class="bookando-field__input"
              :rows="10"
              :placeholder="t('mod.academy.placeholders.lesson_content')"
            />
            <p class="bookando-field__help">
              {{ t('mod.academy.help.lesson_content') }}
            </p>
          </div>

          <!-- Images Section -->
          <div class="bookando-academy-section">
            <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
              <label class="bookando-field__label bookando-m-0">
                📷 {{ t('mod.academy.labels.images') }}
              </label>
              <AppButton
                icon="plus"
                variant="ghost"
                size="sm"
                @click.prevent="addImageUrl"
              >
                {{ t('mod.academy.actions.add_image_url') }}
              </AppButton>
            </div>

            <div class="bookando-academy-media-grid">
              <div
                v-for="(image, idx) in form.images"
                :key="idx"
                class="bookando-academy-media-item"
              >
                <img
                  :src="image"
                  :alt="`Image ${idx + 1}`"
                >
                <button
                  type="button"
                  class="bookando-academy-media-remove"
                  @click="removeImage(idx)"
                >
                  ×
                </button>
              </div>
              <div class="bookando-academy-media-placeholder">
                <span>📷</span>
                <input
                  type="file"
                  accept="image/*"
                  multiple
                  @change="handleImageUpload"
                >
              </div>
            </div>
          </div>

          <!-- Videos Section -->
          <div class="bookando-academy-section">
            <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
              <label class="bookando-field__label bookando-m-0">
                🎥 {{ t('mod.academy.labels.videos') }}
              </label>
              <AppButton
                icon="plus"
                variant="ghost"
                size="sm"
                @click.prevent="addVideoUrl"
              >
                {{ t('mod.academy.actions.add_video') }}
              </AppButton>
            </div>

            <div
              v-if="form.videos.length === 0"
              class="bookando-alert bookando-alert--info"
            >
              {{ t('mod.academy.messages.no_videos') }}
            </div>

            <div
              v-else
              class="bookando-academy-list"
            >
              <div
                v-for="(video, idx) in form.videos"
                :key="idx"
                class="bookando-academy-list__item bookando-flex bookando-justify-between bookando-items-center"
              >
                <div class="bookando-flex bookando-items-center bookando-gap-sm">
                  <span>🎥</span>
                  <span class="bookando-text-sm">{{ formatVideoUrl(video) }}</span>
                </div>
                <AppButton
                  variant="ghost"
                  size="square"
                  btn-type="icononly"
                  icon="trash"
                  icon-size="sm"
                  @click.prevent="removeVideo(idx)"
                />
              </div>
            </div>
          </div>

          <!-- Files Section -->
          <div class="bookando-academy-section">
            <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
              <label class="bookando-field__label bookando-m-0">
                📎 {{ t('mod.academy.labels.files') }}
              </label>
              <AppButton
                icon="plus"
                variant="ghost"
                size="sm"
                @click.prevent="addFileUrl"
              >
                {{ t('mod.academy.actions.add_file') }}
              </AppButton>
            </div>

            <div
              v-if="form.files.length === 0"
              class="bookando-alert bookando-alert--info"
            >
              {{ t('mod.academy.messages.no_files') }}
            </div>

            <div
              v-else
              class="bookando-academy-list"
            >
              <div
                v-for="(file, idx) in form.files"
                :key="idx"
                class="bookando-academy-list__item bookando-flex bookando-justify-between bookando-items-center"
              >
                <div class="bookando-flex bookando-items-center bookando-gap-sm">
                  <span>📎</span>
                  <span class="bookando-text-sm">{{ formatFileUrl(file) }}</span>
                </div>
                <AppButton
                  variant="ghost"
                  size="square"
                  btn-type="icononly"
                  icon="trash"
                  icon-size="sm"
                  @click.prevent="removeFile(idx)"
                />
              </div>
            </div>
          </div>

          <div
            v-if="formError"
            class="bookando-alert bookando-alert--danger"
          >
            {{ formError }}
          </div>
        </form>

        <div class="bookando-academy-modal__footer">
          <AppButton
            variant="secondary"
            @click="$emit('close')"
          >
            {{ t('mod.academy.actions.cancel') }}
          </AppButton>
          <AppButton
            variant="primary"
            :loading="saving"
            @click="submit"
          >
            {{ t('mod.academy.actions.save') }}
          </AppButton>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

import type { Lesson } from '../api/AcademyApi'

const { t } = useI18n()

interface Props {
  lesson: Lesson | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: [lesson: Lesson]
}>()

// Form state
const saving = ref(false)
const formError = ref<string | null>(null)

// Form data
const emptyForm = (): Lesson => ({
  id: undefined,
  title: '',
  content: '',
  images: [],
  videos: [],
  files: [],
})

const form = reactive<Lesson>(emptyForm())

// Initialize form with lesson data
onMounted(() => {
  if (props.lesson) {
    Object.assign(form, {
      ...emptyForm(),
      ...props.lesson,
      images: props.lesson.images ? [...props.lesson.images] : [],
      videos: props.lesson.videos ? [...props.lesson.videos] : [],
      files: props.lesson.files ? [...props.lesson.files] : [],
    })
  }
})

// Image handlers
function handleImageUpload(event: Event) {
  const input = event.target as HTMLInputElement
  const files = input.files
  if (!files || files.length === 0) return

  // In a real implementation, this would upload to WordPress media library
  // For now, we'll use data URLs
  Array.from(files).forEach((file) => {
    const reader = new FileReader()
    reader.onload = (e) => {
      form.images = [...form.images, e.target?.result as string]
    }
    reader.readAsDataURL(file)
  })

  input.value = ''
}

function addImageUrl() {
  const url = prompt(t('mod.academy.prompts.enter_image_url'))
  if (url && url.trim()) {
    form.images = [...form.images, url.trim()]
  }
}

function removeImage(index: number) {
  form.images = form.images.filter((_, i) => i !== index)
}

// Video handlers
function addVideoUrl() {
  const url = prompt(t('mod.academy.prompts.enter_video_url'))
  if (url && url.trim()) {
    form.videos = [...form.videos, url.trim()]
  }
}

function removeVideo(index: number) {
  form.videos = form.videos.filter((_, i) => i !== index)
}

function formatVideoUrl(url: string): string {
  try {
    const urlObj = new URL(url)
    return urlObj.hostname + urlObj.pathname
  } catch {
    return url.length > 50 ? url.substring(0, 47) + '...' : url
  }
}

// File handlers
function addFileUrl() {
  const url = prompt(t('mod.academy.prompts.enter_file_url'))
  if (url && url.trim()) {
    form.files = [...form.files, url.trim()]
  }
}

function removeFile(index: number) {
  form.files = form.files.filter((_, i) => i !== index)
}

function formatFileUrl(url: string): string {
  try {
    const urlObj = new URL(url)
    const path = urlObj.pathname
    const filename = path.split('/').pop() || path
    return filename || url
  } catch {
    return url.split('/').pop() || url
  }
}

// Form submission
function submit() {
  if (saving.value) return

  // Validation
  if (!form.title?.trim()) {
    formError.value = t('mod.academy.errors.lesson_name_required')
    return
  }

  saving.value = true
  formError.value = null

  try {
    // Generate ID if new lesson
    if (!form.id) {
      form.id = `lesson_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    }

    emit('save', { ...form })
  } catch (err: any) {
    console.error('[Bookando] Failed to save lesson', err)
    formError.value = err?.message || t('mod.academy.messages.save_error')
  } finally {
    saving.value = false
  }
}
</script>
