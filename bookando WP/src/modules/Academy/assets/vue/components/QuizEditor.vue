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
            {{ quiz?.id ? t('mod.academy.actions.edit_quiz') : t('mod.academy.actions.add_quiz') }}
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
            :class="{ 'bookando-academy-modal__tab--active': activeTab === 'general' }"
            @click="activeTab = 'general'"
          >
            📋 {{ t('mod.academy.quiz_tabs.general') }}
          </button>
          <button
            type="button"
            class="bookando-academy-modal__tab"
            :class="{ 'bookando-academy-modal__tab--active': activeTab === 'questions' }"
            @click="activeTab = 'questions'"
          >
            ❓ {{ t('mod.academy.quiz_tabs.questions') }}
          </button>
          <button
            type="button"
            class="bookando-academy-modal__tab"
            :class="{ 'bookando-academy-modal__tab--active': activeTab === 'settings' }"
            @click="activeTab = 'settings'"
          >
            ⚙️ {{ t('mod.academy.quiz_tabs.settings') }}
          </button>
        </div>

        <form
          class="bookando-academy-modal__form bookando-academy-modal__form--tabs"
          autocomplete="off"
          @submit.prevent="submit"
        >
          <!-- TAB 1: GENERAL -->
          <div
            v-show="activeTab === 'general'"
            class="bookando-academy-tab-content"
          >
            <BookandoField
              id="quiz_title"
              v-model="form.title"
              :label="t('mod.academy.labels.quiz_title')"
              required
            />

            <BookandoField
              id="quiz_summary"
              v-model="form.summary"
              type="textarea"
              :rows="3"
              :label="t('mod.academy.labels.quiz_summary')"
            />
          </div>

          <!-- TAB 2: QUESTIONS -->
          <div
            v-show="activeTab === 'questions'"
            class="bookando-academy-tab-content"
          >
            <div class="bookando-academy-section">
              <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                <h4 class="bookando-h6 bookando-m-0">
                  {{ t('mod.academy.labels.questions') }}
                </h4>
                <div class="bookando-dropdown">
                  <AppButton
                    icon="plus"
                    variant="secondary"
                    size="sm"
                    @click.prevent="toggleQuestionTypeMenu"
                  >
                    {{ t('mod.academy.actions.add_question') }}
                  </AppButton>
                  <div
                    v-if="showQuestionTypeMenu"
                    class="bookando-dropdown__menu bookando-dropdown__menu--right"
                  >
                    <button
                      v-for="type in questionTypes"
                      :key="type.value"
                      type="button"
                      class="bookando-dropdown__item"
                      @click="addQuestion(type.value)"
                    >
                      {{ type.icon }} {{ type.label }}
                    </button>
                  </div>
                </div>
              </div>

              <div
                v-if="!form.questions || form.questions.length === 0"
                class="bookando-alert bookando-alert--info"
              >
                {{ t('mod.academy.messages.no_questions') }}
              </div>

              <draggable
                v-else
                v-model="form.questions"
                item-key="id"
                handle=".bookando-academy-item__handle"
                class="bookando-academy-items"
              >
                <template #item="{ element: question, index: questionIdx }">
                  <div class="bookando-academy-item">
                    <div class="bookando-academy-item__handle">
                      ⋮⋮
                    </div>
                    <div class="bookando-academy-item__content">
                      <strong>{{ getQuestionIcon(question.type) }} {{ t('mod.academy.labels.question') }} {{ questionIdx + 1 }}</strong>
                      <div class="bookando-text-sm bookando-text-muted">
                        {{ getQuestionTypeLabel(question.type) }}
                        • {{ question.points }} {{ t('mod.academy.labels.points') }}
                        <span v-if="question.time_limit">• {{ question.time_limit }}s</span>
                      </div>
                      <p
                        v-if="question.question_text"
                        class="bookando-text-sm bookando-mt-xs"
                      >
                        {{ truncate(question.question_text, 100) }}
                      </p>
                    </div>
                    <div class="bookando-academy-item__actions">
                      <AppButton
                        variant="ghost"
                        size="square"
                        btn-type="icononly"
                        icon="edit-3"
                        icon-size="sm"
                        @click.prevent="editQuestion(questionIdx)"
                      />
                      <AppButton
                        variant="ghost"
                        size="square"
                        btn-type="icononly"
                        icon="trash"
                        icon-size="sm"
                        @click.prevent="removeQuestion(questionIdx)"
                      />
                    </div>
                  </div>
                </template>
              </draggable>
            </div>
          </div>

          <!-- TAB 3: SETTINGS -->
          <div
            v-show="activeTab === 'settings'"
            class="bookando-academy-tab-content"
          >
            <div class="bookando-academy-section">
              <h4 class="bookando-h6 bookando-mb-md">
                {{ t('mod.academy.sections.quiz_attempts') }}
              </h4>

              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.attempts_allowed') }}
                </label>
                <div class="bookando-flex bookando-gap-sm bookando-items-center">
                  <label class="bookando-radio">
                    <input
                      v-model="attemptsUnlimited"
                      type="radio"
                      :value="true"
                    >
                    <span>{{ t('mod.academy.labels.unlimited') }}</span>
                  </label>
                  <label class="bookando-radio">
                    <input
                      v-model="attemptsUnlimited"
                      type="radio"
                      :value="false"
                    >
                    <span>{{ t('mod.academy.labels.limited') }}</span>
                  </label>
                  <BookandoField
                    v-if="!attemptsUnlimited"
                    id="attempts_allowed"
                    v-model.number="form.settings.attempts_allowed"
                    type="number"
                    :min="1"
                    class="bookando-field--inline"
                  />
                </div>
              </div>

              <BookandoField
                id="pass_percentage"
                v-model.number="form.settings.pass_percentage"
                type="number"
                :min="0"
                :max="100"
                :label="t('mod.academy.labels.pass_percentage')"
              />
            </div>

            <div class="bookando-academy-section">
              <h4 class="bookando-h6 bookando-mb-md">
                {{ t('mod.academy.sections.quiz_display') }}
              </h4>

              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.questions_to_show') }}
                </label>
                <div class="bookando-flex bookando-gap-sm bookando-items-center">
                  <label class="bookando-radio">
                    <input
                      v-model="showAllQuestions"
                      type="radio"
                      :value="true"
                    >
                    <span>{{ t('mod.academy.labels.all_questions') }}</span>
                  </label>
                  <label class="bookando-radio">
                    <input
                      v-model="showAllQuestions"
                      type="radio"
                      :value="false"
                    >
                    <span>{{ t('mod.academy.labels.random_selection') }}</span>
                  </label>
                  <BookandoField
                    v-if="!showAllQuestions"
                    id="questions_to_show"
                    v-model.number="form.settings.questions_to_show"
                    type="number"
                    :min="1"
                    class="bookando-field--inline"
                  />
                </div>
              </div>

              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.answer_order') }}
                </label>
                <label class="bookando-checkbox">
                  <input
                    v-model="form.settings.randomize_answers"
                    type="checkbox"
                  >
                  <span>{{ t('mod.academy.labels.randomize_answers') }}</span>
                </label>
              </div>

              <BookandoField
                id="question_layout"
                v-model="form.settings.question_layout"
                type="dropdown"
                :label="t('mod.academy.labels.question_layout')"
                :options="layoutOptions"
                option-label="label"
                option-value="value"
              />

              <BookandoField
                id="show_feedback"
                v-model="form.settings.show_feedback"
                type="dropdown"
                :label="t('mod.academy.labels.show_feedback')"
                :options="feedbackOptions"
                option-label="label"
                option-value="value"
              />
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

  <!-- QUESTION EDITOR MODAL -->
  <QuestionEditor
    v-if="showQuestionEditor"
    :question="editingQuestion"
    @close="closeQuestionEditor"
    @save="handleQuestionSave"
  />
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import draggable from 'vuedraggable'

import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

import QuestionEditor from './QuestionEditor.vue'

import type { Quiz, Question, QuestionType } from '../api/AcademyApi'

const { t } = useI18n()

interface Props {
  quiz: Quiz | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: [quiz: Quiz]
}>()

// Form state
const activeTab = ref<'general' | 'questions' | 'settings'>('general')
const saving = ref(false)
const formError = ref<string | null>(null)
const showQuestionTypeMenu = ref(false)

// Question editor
const showQuestionEditor = ref(false)
const editingQuestionIndex = ref<number | null>(null)
const editingQuestion = ref<Question | null>(null)

// Settings helpers
const attemptsUnlimited = ref(true)
const showAllQuestions = ref(true)

// Options
const questionTypes = [
  { value: 'quiz_single' as QuestionType, label: t('mod.academy.question_types.quiz_single'), icon: '◉' },
  { value: 'quiz_multiple' as QuestionType, label: t('mod.academy.question_types.quiz_multiple'), icon: '☑' },
  { value: 'true_false' as QuestionType, label: t('mod.academy.question_types.true_false'), icon: '✓✗' },
  { value: 'slider' as QuestionType, label: t('mod.academy.question_types.slider'), icon: '━━━◯━' },
  { value: 'pin_answer' as QuestionType, label: t('mod.academy.question_types.pin_answer'), icon: '📍' },
  { value: 'essay' as QuestionType, label: t('mod.academy.question_types.essay'), icon: '📝' },
  { value: 'fill_blank' as QuestionType, label: t('mod.academy.question_types.fill_blank'), icon: '___' },
  { value: 'short_answer' as QuestionType, label: t('mod.academy.question_types.short_answer'), icon: '✏️' },
  { value: 'matching' as QuestionType, label: t('mod.academy.question_types.matching'), icon: '↔️' },
  { value: 'image_answer' as QuestionType, label: t('mod.academy.question_types.image_answer'), icon: '🖼️' },
  { value: 'sorting' as QuestionType, label: t('mod.academy.question_types.sorting'), icon: '↕️' },
  { value: 'puzzle' as QuestionType, label: t('mod.academy.question_types.puzzle'), icon: '🧩' },
]

const layoutOptions = [
  { label: t('mod.academy.layouts.single'), value: 'single' },
  { label: t('mod.academy.layouts.all'), value: 'all' },
]

const feedbackOptions = [
  { label: t('mod.academy.feedback.immediate'), value: 'immediate' },
  { label: t('mod.academy.feedback.end'), value: 'end' },
]

// Form data
const emptyForm = (): Quiz => ({
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
})

const form = reactive<Quiz>(emptyForm())

// Initialize form with quiz data
onMounted(() => {
  if (props.quiz) {
    Object.assign(form, {
      ...emptyForm(),
      ...props.quiz,
      questions: props.quiz.questions ? JSON.parse(JSON.stringify(props.quiz.questions)) : [],
      settings: { ...emptyForm().settings, ...props.quiz.settings },
    })
  }

  // Set helpers from form values
  attemptsUnlimited.value = form.settings.attempts_allowed === null
  showAllQuestions.value = form.settings.questions_to_show === null
})

// Watch helpers and update form
watch(attemptsUnlimited, (unlimited) => {
  if (unlimited) {
    form.settings.attempts_allowed = null
  } else if (!form.settings.attempts_allowed) {
    form.settings.attempts_allowed = 3
  }
})

watch(showAllQuestions, (showAll) => {
  if (showAll) {
    form.settings.questions_to_show = null
  } else if (!form.settings.questions_to_show) {
    form.settings.questions_to_show = 10
  }
})

// Question management
function toggleQuestionTypeMenu() {
  showQuestionTypeMenu.value = !showQuestionTypeMenu.value
}

function addQuestion(type: QuestionType) {
  showQuestionTypeMenu.value = false
  editingQuestionIndex.value = null
  editingQuestion.value = {
    id: undefined,
    type,
    question_text: '',
    time_limit: null,
    points: 10,
    data: getDefaultQuestionData(type),
  }
  showQuestionEditor.value = true
}

function editQuestion(index: number) {
  editingQuestionIndex.value = index
  editingQuestion.value = JSON.parse(JSON.stringify(form.questions[index]))
  showQuestionEditor.value = true
}

function removeQuestion(index: number) {
  if (confirm(t('mod.academy.messages.confirm_delete_question'))) {
    form.questions = form.questions.filter((_, i) => i !== index)
  }
}

function closeQuestionEditor() {
  showQuestionEditor.value = false
  editingQuestionIndex.value = null
  editingQuestion.value = null
}

function handleQuestionSave(question: Question) {
  if (editingQuestionIndex.value !== null) {
    // Update existing question
    const questions = [...form.questions]
    questions[editingQuestionIndex.value] = question
    form.questions = questions
  } else {
    // Add new question
    form.questions = [...form.questions, question]
  }
  closeQuestionEditor()
}

function getDefaultQuestionData(type: QuestionType): any {
  switch (type) {
    case 'quiz_single':
    case 'quiz_multiple':
      return {
        options: [
          { text: '', correct: false },
          { text: '', correct: false },
        ],
      }
    case 'true_false':
      return { correct_answer: true }
    case 'slider':
      return { min: 0, max: 100, step: 5, correct_value: 50 }
    case 'pin_answer':
      return { image_url: '', pins: [] }
    case 'essay':
    case 'short_answer':
      return { sample_answer: '' }
    case 'fill_blank':
      return { text_with_blanks: '', answers: [] }
    case 'matching':
      return { pairs: [] }
    case 'image_answer':
      return { image_url: '', correct_answer: '' }
    case 'sorting':
      return { items: [] }
    case 'puzzle':
      return { left_items: [], right_items: [], correct_matches: [] }
    default:
      return {}
  }
}

function getQuestionIcon(type: QuestionType): string {
  const found = questionTypes.find((qt) => qt.value === type)
  return found?.icon || '❓'
}

function getQuestionTypeLabel(type: QuestionType): string {
  const found = questionTypes.find((qt) => qt.value === type)
  return found?.label || type
}

function truncate(text: string, length: number): string {
  if (text.length <= length) return text
  return text.substring(0, length) + '...'
}

// Form submission
function submit() {
  if (saving.value) return

  // Validation
  if (!form.title?.trim()) {
    formError.value = t('mod.academy.errors.quiz_title_required')
    activeTab.value = 'general'
    return
  }

  saving.value = true
  formError.value = null

  try {
    // Generate ID if new quiz
    if (!form.id) {
      form.id = `quiz_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    }

    emit('save', { ...form })
  } catch (err: any) {
    console.error('[Bookando] Failed to save quiz', err)
    formError.value = err?.message || t('mod.academy.messages.save_error')
  } finally {
    saving.value = false
  }
}
</script>
