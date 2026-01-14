<template>
  <transition name="fade">
    <div
      class="bookando-academy-modal bookando-academy-modal--xlarge"
      role="dialog"
      aria-modal="true"
      @click.self="$emit('close')"
    >
      <div class="bookando-academy-modal__content">
        <header class="bookando-modal__header">
          <h3 class="bookando-h5 bookando-m-0">
            {{ topic?.id ? t('mod.academy.actions.edit_topic') : t('mod.academy.actions.add_topic') }}
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
          <!-- Topic Basic Info -->
          <div class="bookando-academy-section">
            <BookandoField
              id="topic_title"
              v-model="form.title"
              :label="t('mod.academy.labels.topic_title')"
              required
            />

            <BookandoField
              id="topic_summary"
              v-model="form.summary"
              type="textarea"
              :rows="3"
              :label="t('mod.academy.labels.topic_summary')"
            />
          </div>

          <!-- Lessons Section -->
          <div class="bookando-academy-section">
            <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
              <h4 class="bookando-h6 bookando-m-0">
                📄 {{ t('mod.academy.labels.lessons') }}
              </h4>
              <AppButton
                icon="plus"
                variant="secondary"
                size="sm"
                @click.prevent="addLesson"
              >
                {{ t('mod.academy.actions.add_lesson') }}
              </AppButton>
            </div>

            <div
              v-if="!form.lessons || form.lessons.length === 0"
              class="bookando-alert bookando-alert--info"
            >
              {{ t('mod.academy.messages.no_lessons') }}
            </div>

            <draggable
              v-else
              v-model="form.lessons"
              item-key="id"
              handle=".bookando-academy-item__handle"
              class="bookando-academy-items"
            >
              <template #item="{ element: lesson, index: lessonIdx }">
                <div class="bookando-academy-item">
                  <div class="bookando-academy-item__handle">
                    ⋮⋮
                  </div>
                  <div class="bookando-academy-item__content">
                    <strong>📄 {{ lesson.title || t('mod.academy.placeholders.lesson_name') }}</strong>
                    <div class="bookando-text-sm bookando-text-muted">
                      <span v-if="lesson.images?.length">{{ lesson.images.length }} 📷</span>
                      <span v-if="lesson.videos?.length">{{ lesson.videos.length }} 🎥</span>
                      <span v-if="lesson.files?.length">{{ lesson.files.length }} 📎</span>
                    </div>
                  </div>
                  <div class="bookando-academy-item__actions">
                    <AppButton
                      variant="ghost"
                      size="square"
                      btn-type="icononly"
                      icon="edit-3"
                      icon-size="sm"
                      @click.prevent="editLesson(lessonIdx)"
                    />
                    <AppButton
                      variant="ghost"
                      size="square"
                      btn-type="icononly"
                      icon="trash"
                      icon-size="sm"
                      @click.prevent="removeLesson(lessonIdx)"
                    />
                  </div>
                </div>
              </template>
            </draggable>
          </div>

          <!-- Quizzes Section -->
          <div class="bookando-academy-section">
            <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
              <h4 class="bookando-h6 bookando-m-0">
                ✅ {{ t('mod.academy.labels.quizzes') }}
              </h4>
              <AppButton
                icon="plus"
                variant="secondary"
                size="sm"
                @click.prevent="addQuiz"
              >
                {{ t('mod.academy.actions.add_quiz') }}
              </AppButton>
            </div>

            <div
              v-if="!form.quizzes || form.quizzes.length === 0"
              class="bookando-alert bookando-alert--info"
            >
              {{ t('mod.academy.messages.no_quizzes') }}
            </div>

            <draggable
              v-else
              v-model="form.quizzes"
              item-key="id"
              handle=".bookando-academy-item__handle"
              class="bookando-academy-items"
            >
              <template #item="{ element: quiz, index: quizIdx }">
                <div class="bookando-academy-item">
                  <div class="bookando-academy-item__handle">
                    ⋮⋮
                  </div>
                  <div class="bookando-academy-item__content">
                    <strong>✅ {{ quiz.title || t('mod.academy.placeholders.quiz_title') }}</strong>
                    <div class="bookando-text-sm bookando-text-muted">
                      {{ quiz.questions?.length || 0 }} {{ t('mod.academy.labels.questions') }}
                      • {{ quiz.settings?.pass_percentage || 70 }}% {{ t('mod.academy.labels.pass_threshold') }}
                    </div>
                  </div>
                  <div class="bookando-academy-item__actions">
                    <AppButton
                      variant="ghost"
                      size="square"
                      btn-type="icononly"
                      icon="edit-3"
                      icon-size="sm"
                      @click.prevent="editQuiz(quizIdx)"
                    />
                    <AppButton
                      variant="ghost"
                      size="square"
                      btn-type="icononly"
                      icon="trash"
                      icon-size="sm"
                      @click.prevent="removeQuiz(quizIdx)"
                    />
                  </div>
                </div>
              </template>
            </draggable>
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

  <!-- LESSON EDITOR MODAL -->
  <LessonEditor
    v-if="showLessonEditor"
    :lesson="editingLesson"
    @close="closeLessonEditor"
    @save="handleLessonSave"
  />

  <!-- QUIZ EDITOR MODAL -->
  <QuizEditor
    v-if="showQuizEditor"
    :quiz="editingQuiz"
    @close="closeQuizEditor"
    @save="handleQuizSave"
  />
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import draggable from 'vuedraggable'

import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

import LessonEditor from './LessonEditor.vue'
import QuizEditor from './QuizEditor.vue'

import type { Topic, Lesson, Quiz } from '../api/AcademyApi'

const { t } = useI18n()

interface Props {
  topic: Topic | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: [topic: Topic]
}>()

// Form state
const saving = ref(false)
const formError = ref<string | null>(null)

// Lesson editor
const showLessonEditor = ref(false)
const editingLessonIndex = ref<number | null>(null)
const editingLesson = ref<Lesson | null>(null)

// Quiz editor
const showQuizEditor = ref(false)
const editingQuizIndex = ref<number | null>(null)
const editingQuiz = ref<Quiz | null>(null)

// Form data
const emptyForm = (): Topic => ({
  id: undefined,
  title: '',
  summary: '',
  lessons: [],
  quizzes: [],
})

const form = reactive<Topic>(emptyForm())

// Initialize form with topic data
onMounted(() => {
  if (props.topic) {
    Object.assign(form, {
      ...emptyForm(),
      ...props.topic,
      lessons: props.topic.lessons ? JSON.parse(JSON.stringify(props.topic.lessons)) : [],
      quizzes: props.topic.quizzes ? JSON.parse(JSON.stringify(props.topic.quizzes)) : [],
    })
  }
})

// Lesson management
function addLesson() {
  editingLessonIndex.value = null
  editingLesson.value = {
    id: undefined,
    title: '',
    content: '',
    images: [],
    videos: [],
    files: [],
  }
  showLessonEditor.value = true
}

function editLesson(index: number) {
  editingLessonIndex.value = index
  editingLesson.value = JSON.parse(JSON.stringify(form.lessons[index]))
  showLessonEditor.value = true
}

function removeLesson(index: number) {
  if (confirm(t('mod.academy.messages.confirm_delete_lesson'))) {
    form.lessons = form.lessons.filter((_, i) => i !== index)
  }
}

function closeLessonEditor() {
  showLessonEditor.value = false
  editingLessonIndex.value = null
  editingLesson.value = null
}

function handleLessonSave(lesson: Lesson) {
  if (editingLessonIndex.value !== null) {
    // Update existing lesson
    const lessons = [...form.lessons]
    lessons[editingLessonIndex.value] = lesson
    form.lessons = lessons
  } else {
    // Add new lesson
    form.lessons = [...form.lessons, lesson]
  }
  closeLessonEditor()
}

// Quiz management
function addQuiz() {
  editingQuizIndex.value = null
  editingQuiz.value = {
    id: undefined,
    title: '',
    summary: '',
    questions: [],
    settings: {
      attempts_allowed: null,
      pass_percentage: 70,
      questions_to_show: null,
      randomize_answers: false,
      question_layout: 'single',
      show_feedback: 'immediate',
    },
  }
  showQuizEditor.value = true
}

function editQuiz(index: number) {
  editingQuizIndex.value = index
  editingQuiz.value = JSON.parse(JSON.stringify(form.quizzes[index]))
  showQuizEditor.value = true
}

function removeQuiz(index: number) {
  if (confirm(t('mod.academy.messages.confirm_delete_quiz'))) {
    form.quizzes = form.quizzes.filter((_, i) => i !== index)
  }
}

function closeQuizEditor() {
  showQuizEditor.value = false
  editingQuizIndex.value = null
  editingQuiz.value = null
}

function handleQuizSave(quiz: Quiz) {
  if (editingQuizIndex.value !== null) {
    // Update existing quiz
    const quizzes = [...form.quizzes]
    quizzes[editingQuizIndex.value] = quiz
    form.quizzes = quizzes
  } else {
    // Add new quiz
    form.quizzes = [...form.quizzes, quiz]
  }
  closeQuizEditor()
}

// Form submission
function submit() {
  if (saving.value) return

  // Validation
  if (!form.title?.trim()) {
    formError.value = t('mod.academy.errors.topic_title_required')
    return
  }

  saving.value = true
  formError.value = null

  try {
    // Generate ID if new topic
    if (!form.id) {
      form.id = `topic_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    }

    emit('save', { ...form })
  } catch (err: any) {
    console.error('[Bookando] Failed to save topic', err)
    formError.value = err?.message || t('mod.academy.messages.save_error')
  } finally {
    saving.value = false
  }
}
</script>
