<template>
  <div class="bookando-academy-modal bookando-academy-modal--overlay" @click.self="$emit('close')">
    <div class="bookando-academy-modal__content bookando-academy-modal__content--medium">
      <header class="bookando-modal__header">
        <h4 class="bookando-h6 bookando-m-0">
          {{ t('mod.academy.labels.manage_resources') }}
        </h4>
        <AppButton
          icon="x"
          variant="ghost"
          size="square"
          btn-type="icononly"
          @click="$emit('close')"
        />
      </header>

      <div class="bookando-p-md">
        <!-- Resource Type Selector -->
        <div class="resource-type-selector bookando-mb-md">
          <AppButton
            v-for="type in resourceTypes"
            :key="type.value"
            :variant="selectedType === type.value ? 'primary' : 'secondary'"
            size="small"
            @click="selectedType = type.value"
          >
            {{ type.label }}
          </AppButton>
        </div>

        <!-- Add Resource Form -->
        <div class="resource-add-form bookando-mb-lg">
          <h5 class="bookando-h6 bookando-mb-sm">
            {{ t('mod.academy.actions.add_resource') }}
          </h5>

          <!-- Image Upload -->
          <div v-if="selectedType === 'image'" class="bookando-grid-one">
            <BookandoField
              id="resource_image_title"
              v-model="newResource.title"
              :label="t('mod.academy.labels.title')"
              required
            />
            <BookandoField
              id="resource_image_url"
              v-model="newResource.url"
              :label="t('mod.academy.labels.image_url')"
              required
              placeholder="https://..."
            />
            <BookandoField
              id="resource_image_desc"
              v-model="newResource.description"
              type="textarea"
              :label="t('mod.academy.labels.description')"
              :rows="2"
            />
          </div>

          <!-- Video URL -->
          <div v-else-if="selectedType === 'video'" class="bookando-grid-one">
            <BookandoField
              id="resource_video_title"
              v-model="newResource.title"
              :label="t('mod.academy.labels.title')"
              required
            />
            <BookandoField
              id="resource_video_url"
              v-model="newResource.url"
              :label="t('mod.academy.labels.video_url')"
              required
              placeholder="https://youtube.com/... oder https://vimeo.com/..."
            />
            <BookandoField
              id="resource_video_desc"
              v-model="newResource.description"
              type="textarea"
              :label="t('mod.academy.labels.description')"
              :rows="2"
            />
          </div>

          <!-- Course Link -->
          <div v-else-if="selectedType === 'course_link'" class="bookando-grid-one">
            <BookandoField
              id="resource_course"
              v-model="newResource.course_id"
              type="select"
              :label="t('mod.academy.labels.select_course')"
              required
            >
              <option value="">{{ t('mod.academy.labels.select_course') }}</option>
              <option
                v-for="course in availableCourses"
                :key="course.id"
                :value="course.id"
              >
                {{ course.title }}
              </option>
            </BookandoField>

            <BookandoField
              v-if="selectedCourse && selectedCourse.topics.length > 0"
              id="resource_topic"
              v-model="newResource.topic_id"
              type="select"
              :label="t('mod.academy.labels.select_topic')"
            >
              <option value="">{{ t('mod.academy.labels.all_topics') }}</option>
              <option
                v-for="topic in selectedCourse.topics"
                :key="topic.id"
                :value="topic.id"
              >
                {{ topic.title }}
              </option>
            </BookandoField>

            <BookandoField
              id="resource_course_desc"
              v-model="newResource.description"
              type="textarea"
              :label="t('mod.academy.labels.notes')"
              :rows="2"
            />
          </div>

          <!-- Lesson Link -->
          <div v-else-if="selectedType === 'lesson_link'" class="bookando-grid-one">
            <BookandoField
              id="resource_lesson_course"
              v-model="newResource.course_id"
              type="select"
              :label="t('mod.academy.labels.select_course')"
              required
            >
              <option value="">{{ t('mod.academy.labels.select_course') }}</option>
              <option
                v-for="course in availableCourses"
                :key="course.id"
                :value="course.id"
              >
                {{ course.title }}
              </option>
            </BookandoField>

            <BookandoField
              v-if="selectedCourse && selectedCourse.topics.length > 0"
              id="resource_lesson_topic"
              v-model="newResource.topic_id"
              type="select"
              :label="t('mod.academy.labels.select_topic')"
              required
            >
              <option value="">{{ t('mod.academy.labels.select_topic') }}</option>
              <option
                v-for="topic in selectedCourse.topics"
                :key="topic.id"
                :value="topic.id"
              >
                {{ topic.title }}
              </option>
            </BookandoField>

            <BookandoField
              v-if="selectedTopic && selectedTopic.lessons.length > 0"
              id="resource_lesson"
              v-model="newResource.lesson_id"
              type="select"
              :label="t('mod.academy.labels.select_lesson')"
              required
            >
              <option value="">{{ t('mod.academy.labels.select_lesson') }}</option>
              <option
                v-for="lessonItem in selectedTopic.lessons"
                :key="lessonItem.id"
                :value="lessonItem.id"
              >
                {{ lessonItem.name }}
              </option>
            </BookandoField>

            <BookandoField
              id="resource_lesson_desc"
              v-model="newResource.description"
              type="textarea"
              :label="t('mod.academy.labels.notes')"
              :rows="2"
            />
          </div>

          <AppButton
            variant="primary"
            icon="plus"
            @click="addResource"
          >
            {{ t('mod.academy.actions.add_resource') }}
          </AppButton>
        </div>

        <!-- Existing Resources List -->
        <div class="resource-list">
          <h5 class="bookando-h6 bookando-mb-sm">
            {{ t('mod.academy.labels.attached_resources') }}
            <span class="bookando-text-muted bookando-text-sm">({{ resources.length }})</span>
          </h5>

          <div
            v-if="resources.length === 0"
            class="bookando-alert bookando-alert--info"
          >
            {{ t('mod.academy.messages.no_resources') }}
          </div>

          <div v-else class="resource-items">
            <div
              v-for="(resource, index) in resources"
              :key="index"
              class="resource-item"
            >
              <div class="resource-item-icon">
                <svg v-if="resource.type === 'image'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-width="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/>
                  <polyline points="21 15 16 10 5 21" stroke-width="2"/>
                </svg>
                <svg v-else-if="resource.type === 'video'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3" stroke-width="2"/>
                </svg>
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke-width="2"/>
                  <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke-width="2"/>
                </svg>
              </div>

              <div class="resource-item-content">
                <div class="resource-item-title">{{ resource.title }}</div>
                <div class="resource-item-meta">
                  <span class="resource-type-badge" :class="`resource-type-${resource.type}`">
                    {{ getResourceTypeLabel(resource.type) }}
                  </span>
                  <span v-if="resource.description" class="bookando-text-sm bookando-text-muted">
                    {{ resource.description }}
                  </span>
                </div>
              </div>

              <AppButton
                icon="trash"
                variant="ghost"
                size="square"
                btn-type="icononly"
                @click="removeResource(index)"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="bookando-academy-modal__footer">
        <AppButton variant="secondary" @click="$emit('close')">
          {{ t('mod.academy.actions.cancel') }}
        </AppButton>
        <AppButton variant="primary" @click="handleSave">
          {{ t('mod.academy.actions.save') }}
        </AppButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

import type {
  TrainingLesson,
  TrainingLessonResource,
  AcademyCourse,
} from '../api/AcademyApi'

interface Props {
  lesson: TrainingLesson | null
  availableCourses?: AcademyCourse[]
}

interface Emits {
  (e: 'close'): void
  (e: 'save', lesson: TrainingLesson): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const { t } = useI18n()

const selectedType = ref<'image' | 'video' | 'course_link' | 'lesson_link'>('image')
const resources = ref<TrainingLessonResource[]>([])

const newResource = reactive<Partial<TrainingLessonResource>>({
  type: 'image',
  title: '',
  description: '',
  url: '',
  course_id: '',
  topic_id: '',
  lesson_id: ''
})

const resourceTypes = computed(() => [
  { value: 'image', label: t('mod.academy.resource_types.image') },
  { value: 'video', label: t('mod.academy.resource_types.video') },
  { value: 'course_link', label: t('mod.academy.resource_types.course_link') },
  { value: 'lesson_link', label: t('mod.academy.resource_types.lesson_link') },
])

const selectedCourse = computed(() => {
  if (!newResource.course_id || !props.availableCourses) return null
  return props.availableCourses.find(c => c.id === newResource.course_id) || null
})

const selectedTopic = computed(() => {
  if (!selectedCourse.value || !newResource.topic_id) return null
  return selectedCourse.value.topics.find(t => t.id === newResource.topic_id) || null
})

watch(() => props.lesson, (newLesson) => {
  if (newLesson) {
    resources.value = JSON.parse(JSON.stringify(newLesson.resources || []))
  }
}, { immediate: true })

watch(selectedType, (newType) => {
  newResource.type = newType
  resetNewResource()
})

watch(() => newResource.course_id, () => {
  newResource.topic_id = ''
  newResource.lesson_id = ''
})

watch(() => newResource.topic_id, () => {
  newResource.lesson_id = ''
})

function resetNewResource() {
  newResource.title = ''
  newResource.description = ''
  newResource.url = ''
  newResource.course_id = ''
  newResource.topic_id = ''
  newResource.lesson_id = ''
}

function addResource() {
  // Validate based on type
  if (selectedType.value === 'image' || selectedType.value === 'video') {
    if (!newResource.title || !newResource.url) {
      alert(t('mod.academy.messages.required_fields'))
      return
    }
  } else if (selectedType.value === 'course_link') {
    if (!newResource.course_id) {
      alert(t('mod.academy.messages.select_course'))
      return
    }
    // Auto-generate title
    const course = props.availableCourses?.find(c => c.id === newResource.course_id)
    const topic = course?.topics.find(t => t.id === newResource.topic_id)
    newResource.title = topic
      ? `${course?.title} - ${topic.title}`
      : course?.title || ''
  } else if (selectedType.value === 'lesson_link') {
    if (!newResource.course_id || !newResource.topic_id || !newResource.lesson_id) {
      alert(t('mod.academy.messages.select_lesson'))
      return
    }
    // Auto-generate title
    const course = props.availableCourses?.find(c => c.id === newResource.course_id)
    const topic = course?.topics.find(t => t.id === newResource.topic_id)
    const lesson = topic?.lessons.find(l => l.id === newResource.lesson_id)
    newResource.title = lesson
      ? `${course?.title} - ${topic?.title} - ${lesson.name}`
      : ''
  }

  const resource: TrainingLessonResource = {
    type: selectedType.value,
    title: newResource.title!,
    description: newResource.description,
    url: newResource.url,
    course_id: newResource.course_id,
    topic_id: newResource.topic_id,
    lesson_id: newResource.lesson_id
  }

  resources.value.push(resource)
  resetNewResource()
}

function removeResource(index: number) {
  resources.value.splice(index, 1)
}

function getResourceTypeLabel(type: string): string {
  const labels: Record<string, string> = {
    image: t('mod.academy.resource_types.image'),
    video: t('mod.academy.resource_types.video'),
    course_link: t('mod.academy.resource_types.course'),
    lesson_link: t('mod.academy.resource_types.lesson')
  }
  return labels[type] || type
}

function handleSave() {
  if (!props.lesson) return

  const updatedLesson: TrainingLesson = {
    ...props.lesson,
    resources: resources.value
  }

  emit('save', updatedLesson)
}
</script>

<style>
.bookando-academy-modal__content--medium {
  max-width: 700px;
  width: 90vw;
}

.resource-type-selector {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.resource-add-form {
  padding: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.resource-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.resource-item {
  display: flex;
  align-items: flex-start;
  gap: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
  padding: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  transition: background 0.2s;
}

.resource-item:hover {
  background: #f3f4f6;
}

.resource-item-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  color: #6b7280;
}

.resource-item-content {
  flex: 1;
  min-width: 0;
}

.resource-item-title {
  font-weight: 600;
  color: #23272f;
  margin-bottom: 0.25rem;
}

.resource-item-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.resource-type-badge {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 999px;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.resource-type-image {
  background: #dbeafe;
  color: #1e40af;
}

.resource-type-video {
  background: #fce7f3;
  color: #be123c;
}

.resource-type-course_link {
  background: #d1fae5;
  color: #065f46;
}

.resource-type-lesson_link {
  background: #fef3c7;
  color: #92400e;
}
</style>
