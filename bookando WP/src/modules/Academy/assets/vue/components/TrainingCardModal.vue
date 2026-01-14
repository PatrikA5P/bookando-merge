<template>
  <div class="bookando-academy-modal" role="dialog" aria-modal="true">
    <div class="bookando-academy-modal__content bookando-academy-modal__content--wide">
      <header class="bookando-modal__header">
        <h3 class="bookando-h5 bookando-m-0">
          {{ card?.id ? t('core.common.edit') : t('mod.academy.actions.add_card') }}
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
        @submit.prevent="handleSubmit"
      >
        <!-- Basic Information -->
        <div class="bookando-academy-section">
          <h4 class="bookando-h6 bookando-mb-md">{{ t('mod.academy.labels.basic_info') }}</h4>

          <div class="bookando-grid-two">
            <BookandoField
              id="academy_card_student"
              v-model="form.student"
              :label="t('mod.academy.labels.student')"
              required
            />
            <BookandoField
              id="academy_card_instructor"
              v-model="form.instructor"
              :label="t('mod.academy.labels.instructor')"
            />
          </div>

          <div class="bookando-grid-two">
            <BookandoField
              id="academy_card_category"
              v-model="form.category"
              type="select"
              :label="t('mod.academy.labels.category')"
              required
              @change="handleCategoryChange"
            >
              <option value="">{{ t('mod.academy.labels.select_category') }}</option>
              <option value="B">Kategorie B (PKW)</option>
              <option value="A">Kategorie A (Motorrad)</option>
            </BookandoField>

            <BookandoField
              id="academy_card_program"
              v-model="form.program"
              :label="t('mod.academy.labels.program')"
            />
          </div>

          <BookandoField
            id="academy_card_notes"
            v-model="form.notes"
            type="textarea"
            :label="t('mod.academy.labels.notes')"
          />
        </div>

        <!-- Main Topics & Lessons with Drag & Drop -->
        <div class="bookando-academy-section">
          <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-md">
            <h4 class="bookando-h6 bookando-m-0">{{ t('mod.academy.labels.main_topics') }}</h4>
            <AppButton
              icon="plus"
              variant="secondary"
              size="small"
              @click.prevent="addMainTopic"
            >
              {{ t('mod.academy.actions.add_topic') }}
            </AppButton>
          </div>

          <div
            v-if="form.main_topics.length === 0"
            class="bookando-alert bookando-alert--info"
          >
            {{ t('mod.academy.messages.no_topics') }}
          </div>

          <div class="training-main-topics">
            <div
              v-for="(topic, topicIndex) in sortedMainTopics"
              :key="topic.id"
              class="training-topic-card"
              draggable="true"
              @dragstart="handleTopicDragStart($event, topicIndex)"
              @dragover.prevent
              @drop="handleTopicDrop($event, topicIndex)"
            >
              <div class="training-topic-header">
                <div class="training-topic-drag-handle">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <line x1="3" y1="6" x2="21" y2="6" stroke-width="2" />
                    <line x1="3" y1="12" x2="21" y2="12" stroke-width="2" />
                    <line x1="3" y1="18" x2="21" y2="18" stroke-width="2" />
                  </svg>
                </div>
                <input
                  v-model="topic.title"
                  type="text"
                  class="training-topic-title-input"
                  :placeholder="t('mod.academy.labels.topic_title')"
                />
                <div class="training-topic-actions">
                  <AppButton
                    icon="chevron-down"
                    variant="ghost"
                    size="square"
                    btn-type="icononly"
                    @click.prevent="toggleTopicExpand(topic.id)"
                  >
                    <template #icon>
                      <svg
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        :style="{ transform: expandedTopics.has(topic.id) ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.2s' }"
                      >
                        <polyline points="6 9 12 15 18 9" stroke-width="2" />
                      </svg>
                    </template>
                  </AppButton>
                  <AppButton
                    icon="trash"
                    variant="ghost"
                    size="square"
                    btn-type="icononly"
                    @click.prevent="removeMainTopic(topicIndex)"
                  />
                </div>
              </div>

              <div v-show="expandedTopics.has(topic.id)" class="training-topic-body">
                <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                  <span class="bookando-text-sm bookando-text-muted">
                    {{ topic.lessons.length }} {{ t('mod.academy.labels.lessons') }}
                  </span>
                  <AppButton
                    icon="plus"
                    variant="ghost"
                    size="small"
                    @click.prevent="addLesson(topicIndex)"
                  >
                    {{ t('mod.academy.actions.add_lesson') }}
                  </AppButton>
                </div>

                <div class="training-lessons-list">
                  <div
                    v-for="(lesson, lessonIndex) in topic.lessons"
                    :key="lesson.id"
                    class="training-lesson-item"
                    draggable="true"
                    @dragstart="handleLessonDragStart($event, topicIndex, lessonIndex)"
                    @dragover.prevent
                    @drop="handleLessonDrop($event, topicIndex, lessonIndex)"
                  >
                    <div class="training-lesson-drag-handle">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <line x1="3" y1="6" x2="21" y2="6" stroke-width="2" />
                        <line x1="3" y1="12" x2="21" y2="12" stroke-width="2" />
                        <line x1="3" y1="18" x2="21" y2="18" stroke-width="2" />
                      </svg>
                    </div>

                    <input
                      type="checkbox"
                      v-model="lesson.completed"
                      class="training-lesson-checkbox"
                      @change="updateLessonCompletion(topicIndex, lessonIndex)"
                    />

                    <input
                      v-model="lesson.title"
                      type="text"
                      class="training-lesson-title-input"
                      :class="{ 'completed': lesson.completed }"
                      :placeholder="t('mod.academy.labels.lesson_title')"
                    />

                    <div class="training-lesson-actions">
                      <AppButton
                        icon="paperclip"
                        variant="ghost"
                        size="square"
                        btn-type="icononly"
                        :tooltip="t('mod.academy.actions.manage_resources')"
                        @click.prevent="openResourceManager(topicIndex, lessonIndex)"
                      >
                        <template v-if="lesson.resources.length > 0">
                          <span class="resource-count-badge">{{ lesson.resources.length }}</span>
                        </template>
                      </AppButton>
                      <AppButton
                        icon="message-square"
                        variant="ghost"
                        size="square"
                        btn-type="icononly"
                        :tooltip="t('mod.academy.labels.notes')"
                        @click.prevent="openLessonNotes(topicIndex, lessonIndex)"
                      />
                      <AppButton
                        icon="trash"
                        variant="ghost"
                        size="square"
                        btn-type="icononly"
                        @click.prevent="removeLesson(topicIndex, lessonIndex)"
                      />
                    </div>
                  </div>

                  <div
                    v-if="topic.lessons.length === 0"
                    class="bookando-alert bookando-alert--info bookando-alert--sm"
                  >
                    {{ t('mod.academy.messages.no_lessons') }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="error"
          class="bookando-alert bookando-alert--danger"
        >
          {{ error }}
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
          @click="handleSubmit"
        >
          {{ t('mod.academy.actions.save') }}
        </AppButton>
      </div>
    </div>
  </div>

  <!-- Lesson Notes Modal -->
  <transition name="fade">
    <div
      v-if="notesModal.show"
      class="bookando-academy-modal bookando-academy-modal--overlay"
      @click.self="closeNotesModal"
    >
      <div class="bookando-academy-modal__content bookando-academy-modal__content--small">
        <header class="bookando-modal__header">
          <h4 class="bookando-h6 bookando-m-0">{{ t('mod.academy.labels.lesson_notes') }}</h4>
          <AppButton
            icon="x"
            variant="ghost"
            size="square"
            btn-type="icononly"
            @click="closeNotesModal"
          />
        </header>
        <div class="bookando-p-md">
          <BookandoField
            id="lesson_notes"
            v-model="notesModal.notes"
            type="textarea"
            :label="t('mod.academy.labels.notes')"
            :rows="6"
          />
        </div>
        <div class="bookando-academy-modal__footer">
          <AppButton variant="secondary" @click="closeNotesModal">
            {{ t('mod.academy.actions.cancel') }}
          </AppButton>
          <AppButton variant="primary" @click="saveNotesModal">
            {{ t('mod.academy.actions.save') }}
          </AppButton>
        </div>
      </div>
    </div>
  </transition>

  <!-- Resource Manager Modal -->
  <ResourceManager
    v-if="resourceModal.show"
    :lesson="resourceModal.lesson"
    :available-courses="availableCourses"
    @close="closeResourceManager"
    @save="saveResourceManager"
  />
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import ResourceManager from './ResourceManager.vue'

import {
  type TrainingCard,
  type TrainingMainTopic,
  type TrainingLesson,
  getDefaultTrainingCardKategorieA,
  getDefaultTrainingCardKategorieB,
  saveTrainingCard,
  type AcademyCourse,
} from '../api/AcademyApi'

interface Props {
  card?: TrainingCard | null
  availableCourses?: AcademyCourse[]
}

interface Emits {
  (e: 'close'): void
  (e: 'save', card: TrainingCard): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()
const { t } = useI18n()

const saving = ref(false)
const error = ref<string | null>(null)
const expandedTopics = ref<Set<string>>(new Set())

// Drag & Drop State
const draggedTopicIndex = ref<number | null>(null)
const draggedLessonData = ref<{ topicIndex: number; lessonIndex: number } | null>(null)

// Notes Modal State
const notesModal = reactive({
  show: false,
  topicIndex: -1,
  lessonIndex: -1,
  notes: ''
})

// Resource Manager Modal State
const resourceModal = reactive<{
  show: boolean
  topicIndex: number
  lessonIndex: number
  lesson: TrainingLesson | null
}>({
  show: false,
  topicIndex: -1,
  lessonIndex: -1,
  lesson: null
})

// Form Data
const form = reactive<Partial<TrainingCard>>({
  student: '',
  instructor: '',
  program: '',
  category: undefined,
  progress: 0,
  notes: '',
  milestones: [],
  main_topics: []
})

// Initialize form with card data or empty
watch(() => props.card, (newCard) => {
  if (newCard) {
    Object.assign(form, {
      ...newCard,
      main_topics: JSON.parse(JSON.stringify(newCard.main_topics || []))
    })
    // Expand all topics by default when editing
    if (form.main_topics) {
      form.main_topics.forEach(topic => expandedTopics.value.add(topic.id))
    }
  }
}, { immediate: true })

const sortedMainTopics = computed(() => {
  if (!form.main_topics) return []
  return [...form.main_topics].sort((a, b) => a.order - b.order)
})

// Category Change Handler - Load Template
function handleCategoryChange() {
  if (!form.category) return

  const confirmed = form.main_topics && form.main_topics.length > 0
    ? confirm(t('mod.academy.messages.confirm_category_change'))
    : true

  if (!confirmed) {
    return
  }

  const template = form.category === 'A'
    ? getDefaultTrainingCardKategorieA()
    : getDefaultTrainingCardKategorieB()

  form.program = template.program
  form.main_topics = template.main_topics
  form.milestones = template.milestones

  // Expand all topics
  expandedTopics.value.clear()
  form.main_topics?.forEach(topic => expandedTopics.value.add(topic.id))
}

// Topic Management
function addMainTopic() {
  if (!form.main_topics) form.main_topics = []

  const maxOrder = form.main_topics.reduce((max, t) => Math.max(max, t.order), 0)
  const newTopic: TrainingMainTopic = {
    id: `topic_${Date.now()}`,
    title: t('mod.academy.labels.new_topic'),
    order: maxOrder + 1,
    lessons: []
  }

  form.main_topics.push(newTopic)
  expandedTopics.value.add(newTopic.id)
}

function removeMainTopic(index: number) {
  if (!form.main_topics) return
  if (!confirm(t('mod.academy.messages.confirm_delete_topic'))) return

  const topic = sortedMainTopics.value[index]
  const actualIndex = form.main_topics.findIndex(t => t.id === topic.id)
  if (actualIndex >= 0) {
    form.main_topics.splice(actualIndex, 1)
    expandedTopics.value.delete(topic.id)
  }
}

function toggleTopicExpand(topicId: string) {
  if (expandedTopics.value.has(topicId)) {
    expandedTopics.value.delete(topicId)
  } else {
    expandedTopics.value.add(topicId)
  }
}

// Lesson Management
function addLesson(topicIndex: number) {
  const topic = sortedMainTopics.value[topicIndex]
  const newLesson: TrainingLesson = {
    id: `lesson_${Date.now()}`,
    title: t('mod.academy.labels.new_lesson'),
    completed: false,
    completed_at: null,
    notes: '',
    resources: []
  }
  topic.lessons.push(newLesson)
}

function removeLesson(topicIndex: number, lessonIndex: number) {
  if (!confirm(t('mod.academy.messages.confirm_delete_lesson'))) return

  const topic = sortedMainTopics.value[topicIndex]
  topic.lessons.splice(lessonIndex, 1)
}

function updateLessonCompletion(topicIndex: number, lessonIndex: number) {
  const lesson = sortedMainTopics.value[topicIndex].lessons[lessonIndex]
  lesson.completed_at = lesson.completed ? new Date().toISOString() : null
}

// Drag & Drop: Topics
function handleTopicDragStart(event: DragEvent, index: number) {
  draggedTopicIndex.value = index
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
  }
}

function handleTopicDrop(event: DragEvent, targetIndex: number) {
  event.preventDefault()

  if (draggedTopicIndex.value === null || !form.main_topics) return
  if (draggedTopicIndex.value === targetIndex) return

  const sorted = sortedMainTopics.value
  const draggedTopic = sorted[draggedTopicIndex.value]
  const targetTopic = sorted[targetIndex]

  // Swap orders
  const tempOrder = draggedTopic.order
  draggedTopic.order = targetTopic.order
  targetTopic.order = tempOrder

  draggedTopicIndex.value = null
}

// Drag & Drop: Lessons
function handleLessonDragStart(event: DragEvent, topicIndex: number, lessonIndex: number) {
  draggedLessonData.value = { topicIndex, lessonIndex }
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
  }
}

function handleLessonDrop(event: DragEvent, targetTopicIndex: number, targetLessonIndex: number) {
  event.preventDefault()

  if (!draggedLessonData.value) return

  const { topicIndex: srcTopicIndex, lessonIndex: srcLessonIndex } = draggedLessonData.value

  // Same topic - reorder lessons
  if (srcTopicIndex === targetTopicIndex) {
    const topic = sortedMainTopics.value[srcTopicIndex]
    const [movedLesson] = topic.lessons.splice(srcLessonIndex, 1)
    topic.lessons.splice(targetLessonIndex, 0, movedLesson)
  } else {
    // Move lesson to different topic
    const srcTopic = sortedMainTopics.value[srcTopicIndex]
    const targetTopic = sortedMainTopics.value[targetTopicIndex]
    const [movedLesson] = srcTopic.lessons.splice(srcLessonIndex, 1)
    targetTopic.lessons.splice(targetLessonIndex, 0, movedLesson)
  }

  draggedLessonData.value = null
}

// Notes Modal
function openLessonNotes(topicIndex: number, lessonIndex: number) {
  const lesson = sortedMainTopics.value[topicIndex].lessons[lessonIndex]
  notesModal.show = true
  notesModal.topicIndex = topicIndex
  notesModal.lessonIndex = lessonIndex
  notesModal.notes = lesson.notes || ''
}

function closeNotesModal() {
  notesModal.show = false
}

function saveNotesModal() {
  const lesson = sortedMainTopics.value[notesModal.topicIndex].lessons[notesModal.lessonIndex]
  lesson.notes = notesModal.notes
  closeNotesModal()
}

// Resource Manager
function openResourceManager(topicIndex: number, lessonIndex: number) {
  const lesson = sortedMainTopics.value[topicIndex].lessons[lessonIndex]
  resourceModal.show = true
  resourceModal.topicIndex = topicIndex
  resourceModal.lessonIndex = lessonIndex
  resourceModal.lesson = JSON.parse(JSON.stringify(lesson))
}

function closeResourceManager() {
  resourceModal.show = false
  resourceModal.lesson = null
}

function saveResourceManager(updatedLesson: TrainingLesson) {
  const lesson = sortedMainTopics.value[resourceModal.topicIndex].lessons[resourceModal.lessonIndex]
  lesson.resources = updatedLesson.resources
  closeResourceManager()
}

// Submit
async function handleSubmit() {
  if (saving.value) return

  error.value = null

  if (!form.student || !form.category) {
    error.value = t('mod.academy.messages.required_fields')
    return
  }

  saving.value = true

  try {
    const saved = await saveTrainingCard(form)
    emit('save', saved)
  } catch (err: any) {
    console.error('[Bookando] Failed to save training card', err)
    error.value = err?.message || t('mod.academy.messages.save_error')
  } finally {
    saving.value = false
  }
}
</script>

<style>
.bookando-academy-modal__content--wide {
  max-width: 1200px;
  width: 95vw;
}

.bookando-academy-modal__content--small {
  max-width: 500px;
}

.bookando-academy-modal--overlay {
  z-index: 10000;
}

.bookando-academy-section {
  margin-bottom: clamp(1.1875rem, 0.5rem + 2vw, 2.5rem);
  padding-bottom: clamp(1.1875rem, 0.5rem + 2vw, 2.5rem);
  border-bottom: 1px solid #e5e7eb;
}

.bookando-academy-section:last-child {
  border-bottom: none;
}

.training-main-topics {
  display: flex;
  flex-direction: column;
  gap: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
}

.training-topic-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  overflow: hidden;
  transition: box-shadow 0.2s;
}

.training-topic-card:hover {
  box-shadow: 0 4px 16px rgba(16, 40, 60, 0.09);
}

.training-topic-header {
  display: flex;
  align-items: center;
  gap: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
  padding: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.training-topic-drag-handle {
  cursor: move;
  color: #9ca3af;
  display: flex;
  align-items: center;
}

.training-topic-drag-handle:hover {
  color: #6b7280;
}

.training-topic-title-input {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  font-size: 1rem;
  font-weight: 600;
  color: #23272f;
  background: #ffffff;
}

.training-topic-title-input:focus {
  outline: none;
  border-color: #12DE9D;
  box-shadow: 0 0 0 3px rgba(18, 222, 157, 0.1);
}

.training-topic-actions {
  display: flex;
  gap: 0.25rem;
}

.training-topic-body {
  padding: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
}

.training-lessons-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.training-lesson-item {
  display: flex;
  align-items: center;
  gap: clamp(0.6875rem, 0.3rem + 1.2vw, 1.5rem);
  padding: 0.75rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  transition: background 0.2s;
}

.training-lesson-item:hover {
  background: #f3f4f6;
}

.training-lesson-drag-handle {
  cursor: move;
  color: #9ca3af;
  display: flex;
  align-items: center;
}

.training-lesson-drag-handle:hover {
  color: #6b7280;
}

.training-lesson-checkbox {
  width: 1.25rem;
  height: 1.25rem;
  cursor: pointer;
}

.training-lesson-title-input {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
  font-size: 0.9375rem;
  color: #23272f;
  background: transparent;
  transition: all 0.2s;
}

.training-lesson-title-input:hover {
  background: #ffffff;
  border-color: #e5e7eb;
}

.training-lesson-title-input:focus {
  outline: none;
  background: #ffffff;
  border-color: #12DE9D;
  box-shadow: 0 0 0 3px rgba(18, 222, 157, 0.1);
}

.training-lesson-title-input.completed {
  text-decoration: line-through;
  color: #9ca3af;
}

.training-lesson-actions {
  display: flex;
  gap: 0.25rem;
  position: relative;
}

.resource-count-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #12DE9D;
  color: #ffffff;
  font-size: 0.625rem;
  font-weight: 700;
  padding: 0.125rem 0.375rem;
  border-radius: 999px;
  min-width: 1.125rem;
  text-align: center;
}
</style>
