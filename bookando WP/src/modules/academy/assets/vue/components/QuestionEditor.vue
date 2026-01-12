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
            {{ question?.id ? t('mod.academy.actions.edit_question') : t('mod.academy.actions.add_question') }}
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
          <!-- Question Type (read-only after creation) -->
          <div class="bookando-field">
            <label class="bookando-field__label">
              {{ t('mod.academy.labels.question_type') }}
            </label>
            <div class="bookando-field__static">
              {{ getQuestionIcon(form.type) }} {{ getQuestionTypeLabel(form.type) }}
            </div>
          </div>

          <!-- Question Text -->
          <BookandoField
            id="question_text"
            v-model="form.question_text"
            type="textarea"
            :rows="3"
            :label="t('mod.academy.labels.question_text')"
            required
          />

          <!-- Time Limit & Points -->
          <div class="bookando-grid-two">
            <BookandoField
              id="time_limit"
              v-model.number="form.time_limit"
              type="number"
              :min="0"
              :label="t('mod.academy.labels.time_limit')"
              :help-text="t('mod.academy.help.time_limit')"
            />

            <BookandoField
              id="points"
              v-model.number="form.points"
              type="number"
              :min="1"
              :label="t('mod.academy.labels.points')"
              required
            />
          </div>

          <!-- Question Type Specific Fields -->
          <div class="bookando-academy-section">
            <h4 class="bookando-h6 bookando-mb-md">
              {{ t('mod.academy.sections.question_data') }}
            </h4>

            <!-- QUIZ SINGLE / MULTIPLE -->
            <div v-if="form.type === 'quiz_single' || form.type === 'quiz_multiple'">
              <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                <label class="bookando-field__label bookando-m-0">
                  {{ t('mod.academy.labels.answer_options') }}
                </label>
                <AppButton
                  icon="plus"
                  variant="ghost"
                  size="sm"
                  @click.prevent="addOption"
                >
                  {{ t('mod.academy.actions.add_option') }}
                </AppButton>
              </div>

              <div class="bookando-academy-options">
                <div
                  v-for="(option, idx) in form.data.options"
                  :key="idx"
                  class="bookando-academy-option"
                >
                  <label class="bookando-checkbox">
                    <input
                      v-model="option.correct"
                      type="checkbox"
                    >
                    <span>{{ t('mod.academy.labels.correct') }}</span>
                  </label>
                  <BookandoField
                    :id="`option_${idx}`"
                    v-model="option.text"
                    :placeholder="t('mod.academy.placeholders.option_text')"
                  />
                  <AppButton
                    variant="ghost"
                    size="square"
                    btn-type="icononly"
                    icon="trash"
                    icon-size="sm"
                    @click.prevent="removeOption(idx)"
                  />
                </div>
              </div>
            </div>

            <!-- TRUE / FALSE -->
            <div v-else-if="form.type === 'true_false'">
              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.correct_answer') }}
                </label>
                <div class="bookando-flex bookando-gap-sm">
                  <label class="bookando-radio">
                    <input
                      v-model="form.data.correct_answer"
                      type="radio"
                      :value="true"
                    >
                    <span>{{ t('mod.academy.labels.true') }}</span>
                  </label>
                  <label class="bookando-radio">
                    <input
                      v-model="form.data.correct_answer"
                      type="radio"
                      :value="false"
                    >
                    <span>{{ t('mod.academy.labels.false') }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- SLIDER -->
            <div v-else-if="form.type === 'slider'">
              <div class="bookando-grid-three">
                <BookandoField
                  id="slider_min"
                  v-model.number="form.data.min"
                  type="number"
                  :label="t('mod.academy.labels.min')"
                />
                <BookandoField
                  id="slider_max"
                  v-model.number="form.data.max"
                  type="number"
                  :label="t('mod.academy.labels.max')"
                />
                <BookandoField
                  id="slider_step"
                  v-model.number="form.data.step"
                  type="number"
                  :min="1"
                  :label="t('mod.academy.labels.step')"
                />
              </div>

              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.correct_answer_type') }}
                </label>
                <div class="bookando-flex bookando-gap-sm">
                  <label class="bookando-radio">
                    <input
                      v-model="sliderAnswerType"
                      type="radio"
                      value="exact"
                    >
                    <span>{{ t('mod.academy.labels.exact_value') }}</span>
                  </label>
                  <label class="bookando-radio">
                    <input
                      v-model="sliderAnswerType"
                      type="radio"
                      value="range"
                    >
                    <span>{{ t('mod.academy.labels.range') }}</span>
                  </label>
                </div>
              </div>

              <BookandoField
                v-if="sliderAnswerType === 'exact'"
                id="slider_correct_value"
                v-model.number="form.data.correct_value"
                type="number"
                :min="form.data.min"
                :max="form.data.max"
                :label="t('mod.academy.labels.correct_value')"
              />

              <div
                v-else
                class="bookando-grid-two"
              >
                <BookandoField
                  id="slider_correct_range_min"
                  v-model.number="form.data.correct_range_min"
                  type="number"
                  :min="form.data.min"
                  :max="form.data.max"
                  :label="t('mod.academy.labels.range_min')"
                />
                <BookandoField
                  id="slider_correct_range_max"
                  v-model.number="form.data.correct_range_max"
                  type="number"
                  :min="form.data.min"
                  :max="form.data.max"
                  :label="t('mod.academy.labels.range_max')"
                />
              </div>
            </div>

            <!-- PIN ANSWER -->
            <div v-else-if="form.type === 'pin_answer'">
              <BookandoField
                id="pin_image_url"
                v-model="form.data.image_url"
                :label="t('mod.academy.labels.image_url')"
                required
              />

              <div
                v-if="form.data.image_url"
                class="bookando-academy-pin-container"
              >
                <img
                  :src="form.data.image_url"
                  alt="Pin target"
                  @click="handlePinClick"
                >
                <div
                  v-for="(pin, idx) in form.data.pins"
                  :key="idx"
                  class="bookando-academy-pin"
                  :style="{ left: pin.x + 'px', top: pin.y + 'px' }"
                  @click.stop="removePin(idx)"
                >
                  📍
                </div>
              </div>

              <div
                v-if="form.data.pins && form.data.pins.length > 0"
                class="bookando-text-sm bookando-text-muted bookando-mt-sm"
              >
                {{ t('mod.academy.help.pin_count', { count: form.data.pins.length }) }}
              </div>
            </div>

            <!-- ESSAY / SHORT ANSWER -->
            <div v-else-if="form.type === 'essay' || form.type === 'short_answer'">
              <BookandoField
                id="sample_answer"
                v-model="form.data.sample_answer"
                type="textarea"
                :rows="form.type === 'essay' ? 6 : 3"
                :label="t('mod.academy.labels.sample_answer')"
                :help-text="t('mod.academy.help.sample_answer')"
              />
            </div>

            <!-- FILL BLANK -->
            <div v-else-if="form.type === 'fill_blank'">
              <BookandoField
                id="text_with_blanks"
                v-model="form.data.text_with_blanks"
                type="textarea"
                :rows="3"
                :label="t('mod.academy.labels.text_with_blanks')"
                :help-text="t('mod.academy.help.fill_blank')"
                required
              />

              <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                <label class="bookando-field__label bookando-m-0">
                  {{ t('mod.academy.labels.answers') }}
                </label>
                <AppButton
                  icon="plus"
                  variant="ghost"
                  size="sm"
                  @click.prevent="addBlankAnswer"
                >
                  {{ t('mod.academy.actions.add_answer') }}
                </AppButton>
              </div>

              <div class="bookando-academy-list">
                <div
                  v-for="(answer, idx) in form.data.answers"
                  :key="idx"
                  class="bookando-academy-list__item bookando-flex bookando-gap-sm"
                >
                  <BookandoField
                    :id="`blank_answer_${idx}`"
                    v-model="form.data.answers[idx]"
                    :placeholder="`${t('mod.academy.labels.blank')} ${idx + 1}`"
                  />
                  <AppButton
                    variant="ghost"
                    size="square"
                    btn-type="icononly"
                    icon="trash"
                    icon-size="sm"
                    @click.prevent="removeBlankAnswer(idx)"
                  />
                </div>
              </div>
            </div>

            <!-- MATCHING -->
            <div v-else-if="form.type === 'matching'">
              <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                <label class="bookando-field__label bookando-m-0">
                  {{ t('mod.academy.labels.matching_pairs') }}
                </label>
                <AppButton
                  icon="plus"
                  variant="ghost"
                  size="sm"
                  @click.prevent="addMatchingPair"
                >
                  {{ t('mod.academy.actions.add_pair') }}
                </AppButton>
              </div>

              <div class="bookando-academy-matching">
                <div
                  v-for="(pair, idx) in form.data.pairs"
                  :key="idx"
                  class="bookando-academy-matching__pair"
                >
                  <BookandoField
                    :id="`pair_left_${idx}`"
                    v-model="pair.left"
                    :placeholder="t('mod.academy.placeholders.left_item')"
                  />
                  <span class="bookando-academy-matching__arrow">↔️</span>
                  <BookandoField
                    :id="`pair_right_${idx}`"
                    v-model="pair.right"
                    :placeholder="t('mod.academy.placeholders.right_item')"
                  />
                  <AppButton
                    variant="ghost"
                    size="square"
                    btn-type="icononly"
                    icon="trash"
                    icon-size="sm"
                    @click.prevent="removeMatchingPair(idx)"
                  />
                </div>
              </div>
            </div>

            <!-- IMAGE ANSWER -->
            <div v-else-if="form.type === 'image_answer'">
              <BookandoField
                id="image_answer_url"
                v-model="form.data.image_url"
                :label="t('mod.academy.labels.image_url')"
                required
              />

              <div
                v-if="form.data.image_url"
                class="bookando-academy-image-preview"
              >
                <img
                  :src="form.data.image_url"
                  alt="Question image"
                >
              </div>

              <BookandoField
                id="image_correct_answer"
                v-model="form.data.correct_answer"
                :label="t('mod.academy.labels.correct_answer')"
                required
              />
            </div>

            <!-- SORTING -->
            <div v-else-if="form.type === 'sorting'">
              <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                <label class="bookando-field__label bookando-m-0">
                  {{ t('mod.academy.labels.items_to_sort') }}
                </label>
                <AppButton
                  icon="plus"
                  variant="ghost"
                  size="sm"
                  @click.prevent="addSortItem"
                >
                  {{ t('mod.academy.actions.add_item') }}
                </AppButton>
              </div>

              <draggable
                v-model="form.data.items"
                item-key="index"
                class="bookando-academy-sortable"
              >
                <template #item="{ element: item, index: idx }">
                  <div class="bookando-academy-sortable__item">
                    <span class="bookando-academy-sortable__handle">⋮⋮</span>
                    <BookandoField
                      :id="`sort_item_${idx}`"
                      v-model="form.data.items[idx]"
                      :placeholder="`${t('mod.academy.labels.item')} ${idx + 1}`"
                    />
                    <AppButton
                      variant="ghost"
                      size="square"
                      btn-type="icononly"
                      icon="trash"
                      icon-size="sm"
                      @click.prevent="removeSortItem(idx)"
                    />
                  </div>
                </template>
              </draggable>
              <p class="bookando-field__help bookando-mt-sm">
                {{ t('mod.academy.help.sorting') }}
              </p>
            </div>

            <!-- PUZZLE -->
            <div v-else-if="form.type === 'puzzle'">
              <p class="bookando-field__help bookando-mb-md">
                {{ t('mod.academy.help.puzzle') }}
              </p>

              <div class="bookando-grid-two bookando-mb-md">
                <div>
                  <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                    <label class="bookando-field__label bookando-m-0">
                      {{ t('mod.academy.labels.left_items') }}
                    </label>
                    <AppButton
                      icon="plus"
                      variant="ghost"
                      size="xs"
                      @click.prevent="addPuzzleLeftItem"
                    >
                      +
                    </AppButton>
                  </div>
                  <div class="bookando-academy-list">
                    <div
                      v-for="(item, idx) in form.data.left_items"
                      :key="idx"
                      class="bookando-academy-list__item bookando-flex bookando-gap-sm"
                    >
                      <span class="bookando-text-sm">{{ idx }}</span>
                      <BookandoField
                        :id="`puzzle_left_${idx}`"
                        v-model="form.data.left_items[idx]"
                      />
                      <AppButton
                        variant="ghost"
                        size="square"
                        btn-type="icononly"
                        icon="trash"
                        icon-size="sm"
                        @click.prevent="removePuzzleLeftItem(idx)"
                      />
                    </div>
                  </div>
                </div>

                <div>
                  <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
                    <label class="bookando-field__label bookando-m-0">
                      {{ t('mod.academy.labels.right_items') }}
                    </label>
                    <AppButton
                      icon="plus"
                      variant="ghost"
                      size="xs"
                      @click.prevent="addPuzzleRightItem"
                    >
                      +
                    </AppButton>
                  </div>
                  <div class="bookando-academy-list">
                    <div
                      v-for="(item, idx) in form.data.right_items"
                      :key="idx"
                      class="bookando-academy-list__item bookando-flex bookando-gap-sm"
                    >
                      <span class="bookando-text-sm">{{ idx }}</span>
                      <BookandoField
                        :id="`puzzle_right_${idx}`"
                        v-model="form.data.right_items[idx]"
                      />
                      <AppButton
                        variant="ghost"
                        size="square"
                        btn-type="icononly"
                        icon="trash"
                        icon-size="sm"
                        @click.prevent="removePuzzleRightItem(idx)"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div class="bookando-field">
                <label class="bookando-field__label">
                  {{ t('mod.academy.labels.correct_matches') }}
                </label>
                <p class="bookando-field__help">
                  {{ t('mod.academy.help.puzzle_matches') }}
                </p>
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
import { onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import draggable from 'vuedraggable'

import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'

import type { Question, QuestionType } from '../api/AcademyApi'

const { t } = useI18n()

interface Props {
  question: Question | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: [question: Question]
}>()

// Form state
const saving = ref(false)
const formError = ref<string | null>(null)
const sliderAnswerType = ref<'exact' | 'range'>('exact')

// Form data
const form = reactive<Question>({
  id: undefined,
  type: 'quiz_single',
  question_text: '',
  time_limit: null,
  points: 10,
  data: {},
})

// Initialize form with question data
onMounted(() => {
  if (props.question) {
    Object.assign(form, JSON.parse(JSON.stringify(props.question)))

    // Set slider answer type
    if (form.type === 'slider') {
      sliderAnswerType.value = form.data.correct_value !== null && form.data.correct_value !== undefined ? 'exact' : 'range'
    }
  }
})

// Watch slider answer type and clear opposite values
watch(sliderAnswerType, (type) => {
  if (type === 'exact') {
    form.data.correct_range_min = null
    form.data.correct_range_max = null
  } else {
    form.data.correct_value = null
  }
})

// Quiz options
function addOption() {
  if (!form.data.options) form.data.options = []
  form.data.options.push({ text: '', correct: false })
}

function removeOption(index: number) {
  if (form.data.options && form.data.options.length > 2) {
    form.data.options.splice(index, 1)
  }
}

// Pin answer
function handlePinClick(event: MouseEvent) {
  const rect = (event.target as HTMLElement).getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top

  if (!form.data.pins) form.data.pins = []
  form.data.pins.push({ x: Math.round(x), y: Math.round(y), tolerance: 10 })
}

function removePin(index: number) {
  if (form.data.pins) {
    form.data.pins.splice(index, 1)
  }
}

// Fill blank
function addBlankAnswer() {
  if (!form.data.answers) form.data.answers = []
  form.data.answers.push('')
}

function removeBlankAnswer(index: number) {
  if (form.data.answers) {
    form.data.answers.splice(index, 1)
  }
}

// Matching
function addMatchingPair() {
  if (!form.data.pairs) form.data.pairs = []
  form.data.pairs.push({ left: '', right: '' })
}

function removeMatchingPair(index: number) {
  if (form.data.pairs) {
    form.data.pairs.splice(index, 1)
  }
}

// Sorting
function addSortItem() {
  if (!form.data.items) form.data.items = []
  form.data.items.push('')
}

function removeSortItem(index: number) {
  if (form.data.items) {
    form.data.items.splice(index, 1)
  }
}

// Puzzle
function addPuzzleLeftItem() {
  if (!form.data.left_items) form.data.left_items = []
  form.data.left_items.push('')
}

function removePuzzleLeftItem(index: number) {
  if (form.data.left_items) {
    form.data.left_items.splice(index, 1)
  }
}

function addPuzzleRightItem() {
  if (!form.data.right_items) form.data.right_items = []
  form.data.right_items.push('')
}

function removePuzzleRightItem(index: number) {
  if (form.data.right_items) {
    form.data.right_items.splice(index, 1)
  }
}

// Helpers
function getQuestionIcon(type: QuestionType): string {
  const icons: Record<QuestionType, string> = {
    quiz_single: '◉',
    quiz_multiple: '☑',
    true_false: '✓✗',
    slider: '━━━◯━',
    pin_answer: '📍',
    essay: '📝',
    fill_blank: '___',
    short_answer: '✏️',
    matching: '↔️',
    image_answer: '🖼️',
    sorting: '↕️',
    puzzle: '🧩',
  }
  return icons[type] || '❓'
}

function getQuestionTypeLabel(type: QuestionType): string {
  return t(`mod.academy.question_types.${type}`)
}

// Form submission
function submit() {
  if (saving.value) return

  // Validation
  if (!form.question_text?.trim()) {
    formError.value = t('mod.academy.errors.question_text_required')
    return
  }

  if (!form.points || form.points < 1) {
    formError.value = t('mod.academy.errors.points_required')
    return
  }

  saving.value = true
  formError.value = null

  try {
    // Generate ID if new question
    if (!form.id) {
      form.id = `question_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
    }

    emit('save', { ...form })
  } catch (err: any) {
    console.error('[Bookando] Failed to save question', err)
    formError.value = err?.message || t('mod.academy.messages.save_error')
  } finally {
    saving.value = false
  }
}
</script>
