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
            {{ course?.id ? t('core.common.edit') : t('mod.academy.actions.add_course') }}
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

        <!-- TABS -->
        <div class="bookando-academy-modal__tabs">
          <button
            type="button"
            class="bookando-academy-modal__tab"
            :class="{ 'bookando-academy-modal__tab--active': activeTab === 'definition' }"
            @click="activeTab = 'definition'"
          >
            📋 {{ t('mod.academy.course_tabs.definition') }}
          </button>
          <button
            type="button"
            class="bookando-academy-modal__tab"
            :class="{ 'bookando-academy-modal__tab--active': activeTab === 'planning' }"
            @click="activeTab = 'planning'"
          >
            📚 {{ t('mod.academy.course_tabs.planning') }}
          </button>
        </div>

        <form
          class="bookando-academy-modal__form bookando-academy-modal__form--tabs"
          autocomplete="off"
          @submit.prevent="submit"
        >
          <!-- TAB 1: KURSDEFINITION -->
          <div
            v-show="activeTab === 'definition'"
            class="bookando-academy-tab-content"
          >
            <!-- Grundlegende Informationen -->
            <div class="bookando-academy-section">
              <h4 class="bookando-h6 bookando-mb-md">
                {{ t('mod.academy.sections.basic_info') }}
              </h4>

              <BookandoField
                id="academy_course_title"
                v-model="form.title"
                :label="t('mod.academy.labels.title')"
                required
              />

              <BookandoField
                id="academy_course_description"
                v-model="form.description"
                type="textarea"
                :rows="4"
                :label="t('mod.academy.labels.description')"
                required
              />

              <div class="bookando-grid-two">
                <BookandoField
                  id="academy_course_type"
                  v-model="form.course_type"
                  type="dropdown"
                  :label="t('mod.academy.labels.course_type')"
                  :options="courseTypeOptions"
                  option-label="label"
                  option-value="value"
                />

                <BookandoField
                  id="academy_course_author"
                  v-model="form.author"
                  :label="t('mod.academy.labels.author')"
                />
              </div>
            </div>

            <!-- Teilnahme & Sichtbarkeit -->
            <div class="bookando-academy-section">
              <h4 class="bookando-h6 bookando-mb-md">
                {{ t('mod.academy.sections.participation') }}
              </h4>

              <BookandoField
                id="academy_course_max_participants"
                v-model.number="form.max_participants"
                type="number"
                :min="0"
                :label="t('mod.academy.labels.max_participants')"
                :help-text="t('mod.academy.help.max_participants')"
              />

              <BookandoField
                id="academy_course_visibility"
                v-model="form.visibility"
                type="dropdown"
                :label="t('mod.academy.labels.visibility')"
                :options="visibilityOptions"
                option-label="label"
                option-value="value"
              />

              <div class="bookando-grid-two">
                <BookandoField
                  id="academy_course_display_from"
                  v-model="form.display_from"
                  type="date"
                  :label="t('mod.academy.labels.display_from')"
                />

                <BookandoField
                  id="academy_course_display_until"
                  v-model="form.display_until"
                  type="date"
                  :label="t('mod.academy.labels.display_until')"
                />
              </div>
            </div>

            <!-- Kategorisierung -->
            <div class="bookando-academy-section">
              <h4 class="bookando-h6 bookando-mb-md">
                {{ t('mod.academy.sections.categorization') }}
              </h4>

              <BookandoField
                id="academy_course_level"
                v-model="form.level"
                type="dropdown"
                :label="t('mod.academy.labels.level')"
                :options="levelOptions"
                option-label="label"
                option-value="value"
              />

              <BookandoField
                id="academy_course_category"
                v-model="form.category"
                :label="t('mod.academy.labels.category')"
              />

              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.tags') }}
                </label>
                <div class="bookando-academy-tags">
                  <span
                    v-for="(tag, idx) in form.tags"
                    :key="idx"
                    class="bookando-tag"
                  >
                    {{ tag }}
                    <button
                      type="button"
                      class="bookando-tag__remove"
                      @click="removeTag(idx)"
                    >
                      ×
                    </button>
                  </span>
                  <input
                    v-model="newTag"
                    type="text"
                    class="bookando-tag-input"
                    :placeholder="t('mod.academy.placeholders.add_tag')"
                    @keydown.enter.prevent="addTag"
                  >
                </div>
              </div>
            </div>

            <!-- Medien -->
            <div class="bookando-academy-section">
              <h4 class="bookando-h6 bookando-mb-md">
                {{ t('mod.academy.sections.media') }}
              </h4>

              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.featured_image') }}
                </label>
                <div class="bookando-academy-media-upload">
                  <div
                    v-if="form.featured_image"
                    class="bookando-academy-media-preview"
                  >
                    <img
                      :src="form.featured_image"
                      alt="Featured"
                    >
                    <button
                      type="button"
                      class="bookando-academy-media-remove"
                      @click="form.featured_image = null"
                    >
                      <span>×</span>
                    </button>
                  </div>
                  <div
                    v-else
                    class="bookando-academy-media-placeholder"
                  >
                    <span>📷</span>
                    <input
                      type="file"
                      accept="image/*"
                      @change="handleImageUpload($event, 'featured_image')"
                    >
                  </div>
                </div>
              </div>

              <BookandoField
                id="academy_course_intro_video"
                v-model="form.intro_video"
                :label="t('mod.academy.labels.intro_video')"
                :help-text="t('mod.academy.help.intro_video')"
              />

              <div
                v-if="form.intro_video"
                class="bookando-academy-video-preview"
              >
                <video
                  v-if="isVideoFile(form.intro_video)"
                  :src="form.intro_video"
                  controls
                  style="max-width: 100%; max-height: 200px;"
                />
                <iframe
                  v-else-if="isYouTubeUrl(form.intro_video)"
                  :src="getYouTubeEmbedUrl(form.intro_video)"
                  frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  allowfullscreen
                  style="width: 100%; height: 300px;"
                />
              </div>
            </div>
          </div>

          <!-- TAB 2: KURSPLANUNG -->
          <div
            v-show="activeTab === 'planning'"
            class="bookando-academy-tab-content"
          >
            <!-- Sequential Topics Option -->
            <div class="bookando-academy-section">
              <label class="bookando-checkbox">
                <input
                  v-model="form.sequential_topics"
                  type="checkbox"
                >
                <span>{{ t('mod.academy.labels.sequential_topics') }}</span>
              </label>
            </div>

            <!-- Topics List -->
            <div class="bookando-academy-section">
              <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-md">
                <h4 class="bookando-h6 bookando-m-0">
                  {{ t('mod.academy.labels.topics') }}
                </h4>
                <AppButton
                  icon="plus"
                  variant="secondary"
                  size="sm"
                  @click.prevent="addTopic"
                >
                  {{ t('mod.academy.actions.add_topic') }}
                </AppButton>
              </div>

              <div
                v-if="!form.topics || form.topics.length === 0"
                class="bookando-alert bookando-alert--info"
              >
                {{ t('mod.academy.messages.no_topics') }}
              </div>

              <draggable
                v-else
                v-model="form.topics"
                item-key="id"
                handle=".bookando-academy-topic__handle"
                class="bookando-academy-topics"
              >
                <template #item="{ element: topic, index: topicIdx }">
                  <div class="bookando-academy-topic">
                    <div class="bookando-academy-topic__header">
                      <div class="bookando-academy-topic__handle">
                        ⋮⋮
                      </div>
                      <div class="bookando-academy-topic__title">
                        <strong>📚 {{ topic.title || t('mod.academy.placeholders.topic_title') }}</strong>
                        <p
                          v-if="topic.summary"
                          class="bookando-text-muted bookando-text-sm bookando-m-0"
                        >
                          {{ topic.summary }}
                        </p>
                      </div>
                      <div class="bookando-academy-topic__actions">
                        <AppButton
                          variant="ghost"
                          size="square"
                          btn-type="icononly"
                          icon="edit-3"
                          icon-size="sm"
                          :tooltip="t('core.common.edit')"
                          @click.prevent="editTopic(topicIdx)"
                        />
                        <AppButton
                          variant="ghost"
                          size="square"
                          btn-type="icononly"
                          icon="trash"
                          icon-size="sm"
                          :tooltip="t('core.common.delete')"
                          @click.prevent="removeTopic(topicIdx)"
                        />
                      </div>
                    </div>

                    <div class="bookando-academy-topic__body">
                      <div class="bookando-academy-topic__stats">
                        <span>📄 {{ topic.lessons?.length || 0 }} {{ t('mod.academy.labels.lessons') }}</span>
                        <span>✅ {{ topic.quizzes?.length || 0 }} {{ t('mod.academy.labels.quizzes') }}</span>
                      </div>
                    </div>
                  </div>
                </template>
              </draggable>
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

  <!-- TOPIC EDITOR MODAL -->
  <TopicEditor
    v-if="showTopicEditor"
    :topic="editingTopic"
    @close="closeTopicEditor"
    @save="handleTopicSave"
  />
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import draggable from 'vuedraggable'

import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

import TopicEditor from './TopicEditor.vue'

import {
  saveCourse,
  type AcademyCourse,
  type Topic,
  type CourseType,
  type CourseVisibility,
  type CourseLevel,
} from '../api/AcademyApi'

const { t } = useI18n()

interface Props {
  course: AcademyCourse | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: [course: AcademyCourse]
}>()

// Form state
const activeTab = ref<'definition' | 'planning'>('definition')
const saving = ref(false)
const formError = ref<string | null>(null)
const newTag = ref('')

// Topic editor
const showTopicEditor = ref(false)
const editingTopicIndex = ref<number | null>(null)
const editingTopic = ref<Topic | null>(null)

// Options
const courseTypeOptions = [
  { label: '🌐 ' + t('mod.academy.course_types.online'), value: 'online' as CourseType },
  { label: '🏢 ' + t('mod.academy.course_types.physical'), value: 'physical' as CourseType },
]

const visibilityOptions = [
  { label: '🌐 ' + t('mod.academy.visibility.public'), value: 'public' as CourseVisibility },
  { label: '🔐 ' + t('mod.academy.visibility.logged_in'), value: 'logged_in' as CourseVisibility },
  { label: '🔒 ' + t('mod.academy.visibility.private'), value: 'private' as CourseVisibility },
]

const levelOptions = [
  { label: '⭐ ' + t('mod.academy.levels.beginner'), value: 'beginner' as CourseLevel },
  { label: '⭐⭐ ' + t('mod.academy.levels.intermediate'), value: 'intermediate' as CourseLevel },
  { label: '⭐⭐⭐ ' + t('mod.academy.levels.advanced'), value: 'advanced' as CourseLevel },
]

// Form data
const emptyForm = (): Partial<AcademyCourse> => ({
  id: undefined,
  title: '',
  description: '',
  course_type: 'online',
  author: '',
  max_participants: null,
  visibility: 'public',
  display_from: null,
  display_until: null,
  level: 'beginner',
  category: '',
  tags: [],
  featured_image: null,
  intro_video: null,
  sequential_topics: false,
  topics: [],
})

const form = reactive<Partial<AcademyCourse>>(emptyForm())

// Initialize form with course data
onMounted(() => {
  if (props.course) {
    Object.assign(form, {
      ...emptyForm(),
      ...props.course,
      topics: props.course.topics ? JSON.parse(JSON.stringify(props.course.topics)) : [],
    })
  }
})

// Tags management
function addTag() {
  const tag = newTag.value.trim()
  if (tag && !form.tags?.includes(tag)) {
    form.tags = [...(form.tags || []), tag]
    newTag.value = ''
  }
}

function removeTag(index: number) {
  form.tags = form.tags?.filter((_, i) => i !== index) || []
}

// Media handlers
function handleImageUpload(event: Event, field: 'featured_image') {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  // In a real implementation, this would upload to WordPress media library
  // For now, we'll use a data URL
  const reader = new FileReader()
  reader.onload = (e) => {
    form[field] = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

function isVideoFile(url: string): boolean {
  return /\.(mp4|webm|ogg)$/i.test(url)
}

function isYouTubeUrl(url: string): boolean {
  return /(?:youtube\.com\/watch\?v=|youtu\.be\/)/.test(url)
}

function getYouTubeEmbedUrl(url: string): string {
  const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
  return match ? `https://www.youtube.com/embed/${match[1]}` : ''
}

// Topic management
function addTopic() {
  editingTopicIndex.value = null
  editingTopic.value = {
    id: undefined,
    title: '',
    summary: '',
    lessons: [],
    quizzes: [],
  }
  showTopicEditor.value = true
}

function editTopic(index: number) {
  editingTopicIndex.value = index
  editingTopic.value = JSON.parse(JSON.stringify(form.topics![index]))
  showTopicEditor.value = true
}

function removeTopic(index: number) {
  if (confirm(t('mod.academy.messages.confirm_delete_topic'))) {
    form.topics = form.topics?.filter((_, i) => i !== index) || []
  }
}

function closeTopicEditor() {
  showTopicEditor.value = false
  editingTopicIndex.value = null
  editingTopic.value = null
}

function handleTopicSave(topic: Topic) {
  if (editingTopicIndex.value !== null) {
    // Update existing topic
    const topics = [...(form.topics || [])]
    topics[editingTopicIndex.value] = topic
    form.topics = topics
  } else {
    // Add new topic
    form.topics = [...(form.topics || []), topic]
  }
  closeTopicEditor()
}

// Form submission
async function submit() {
  if (saving.value) return

  // Validation
  if (!form.title?.trim()) {
    formError.value = t('mod.academy.errors.title_required')
    activeTab.value = 'definition'
    return
  }

  if (!form.description?.trim()) {
    formError.value = t('mod.academy.errors.description_required')
    activeTab.value = 'definition'
    return
  }

  saving.value = true
  formError.value = null

  try {
    const payload: Partial<AcademyCourse> = {
      id: form.id,
      title: form.title!,
      description: form.description!,
      course_type: form.course_type!,
      author: form.author || '',
      max_participants: form.max_participants || null,
      visibility: form.visibility!,
      display_from: form.display_from || null,
      display_until: form.display_until || null,
      level: form.level!,
      category: form.category || '',
      tags: form.tags || [],
      featured_image: form.featured_image || null,
      intro_video: form.intro_video || null,
      sequential_topics: form.sequential_topics || false,
      topics: form.topics || [],
    }

    const saved = await saveCourse(payload)
    emit('save', saved)
  } catch (err: any) {
    console.error('[Bookando] Failed to save course', err)
    formError.value = err?.message || t('mod.academy.messages.save_error')
  } finally {
    saving.value = false
  }
}
</script>
