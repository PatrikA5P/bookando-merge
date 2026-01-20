<template>
  <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
      <div class="p-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
        <div class="flex items-center gap-4">
          <h3 class="text-xl font-bold text-slate-800">{{ $t('mod.academy.quiz_editor') }}</h3>
          <div class="flex bg-white rounded-lg border border-slate-200 p-1">
            <button
              v-for="t in tabs"
              :key="t"
              @click="currentTab = t"
              :class="['px-3 py-1 text-sm font-medium rounded capitalize', currentTab === t ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:text-slate-800']"
            >
              {{ $t(`mod.academy.${t}`) }}
            </button>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="$emit('cancel')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg font-medium">
            {{ $t('common.cancel') }}
          </button>
          <button @click="handleSave" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
            {{ $t('mod.academy.save_quiz') }}
          </button>
        </div>
      </div>

      <div class="flex-1 overflow-y-auto p-6 bg-slate-50/50">
        <!-- General Tab -->
        <div v-if="currentTab === 'general'" class="max-w-2xl mx-auto space-y-4 bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.quiz_title') }}</label>
            <input v-model="formData.title" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.summary_instructions') }}</label>
            <textarea v-model="formData.summary" class="w-full border border-slate-300 rounded-lg px-3 py-2 h-32 focus:ring-2 focus:ring-brand-500 outline-none bg-white" />
          </div>
        </div>

        <!-- Settings Tab -->
        <div v-if="currentTab === 'settings'" class="max-w-2xl mx-auto space-y-6 bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
          <div>
            <h4 class="font-bold text-slate-800 border-b pb-2 mb-4">{{ $t('mod.academy.attempts_scoring') }}</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.allowed_attempts') }}</label>
                <input
                  v-model.number="formData.settings.allowedAttempts"
                  type="number"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                />
                <p class="text-xs text-slate-400 mt-1">{{ $t('mod.academy.unlimited_attempts') }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.passing_score') }}</label>
                <input
                  v-model.number="formData.settings.passingScore"
                  type="number"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                />
              </div>
            </div>
          </div>
          <div>
            <h4 class="font-bold text-slate-800 border-b pb-2 mb-4">{{ $t('mod.academy.display_behavior') }}</h4>
            <div class="space-y-3">
              <label class="flex items-center gap-2">
                <input v-model="formData.settings.shuffleQuestions" type="checkbox" />
                <span class="text-sm text-slate-700">{{ $t('mod.academy.shuffle_questions') }}</span>
              </label>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.layout') }}</label>
                <select v-model="formData.settings.layout" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white">
                  <option value="Single Page">{{ $t('mod.academy.single_page') }}</option>
                  <option value="One per page">{{ $t('mod.academy.one_per_page') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.feedback') }}</label>
                <select v-model="formData.settings.feedbackMode" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white">
                  <option value="Immediate">{{ $t('mod.academy.immediate_feedback') }}</option>
                  <option value="End of Quiz">{{ $t('mod.academy.end_feedback') }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Questions Tab -->
        <div v-if="currentTab === 'questions'" class="flex gap-6 h-full">
          <!-- Question List Sidebar -->
          <div class="w-64 flex-shrink-0 flex flex-col gap-3">
            <div class="relative group">
              <button class="w-full py-2 bg-brand-600 text-white rounded-lg font-medium flex justify-center items-center gap-2 hover:bg-brand-700">
                <PlusIcon :size="18" /> {{ $t('mod.academy.add_question') }}
              </button>
              <!-- Dropdown -->
              <div class="hidden group-hover:block absolute top-full left-0 w-64 bg-white border border-slate-200 shadow-xl rounded-xl mt-2 p-2 z-10 grid grid-cols-1 gap-1">
                <button
                  v-for="qType in questionTypes"
                  :key="qType.type"
                  @click="addQuestion(qType.type)"
                  class="text-left px-3 py-2 text-sm hover:bg-slate-50 rounded flex items-center gap-2 text-slate-700"
                >
                  <component :is="qType.icon" :size="14" /> {{ qType.label }}
                </button>
              </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-2 pr-2">
              <div
                v-for="(q, i) in formData.questions"
                :key="q.id"
                @click="selectedQuestionIndex = i"
                :class="['p-3 bg-white border rounded-lg cursor-pointer group relative', selectedQuestionIndex === i ? 'border-brand-300 bg-brand-50' : 'border-slate-200 hover:border-brand-300']"
              >
                <div class="text-xs font-bold text-slate-400 mb-1">Q{{ i + 1 }} • {{ q.type }}</div>
                <div class="text-sm text-slate-800 truncate">{{ q.text }}</div>
                <button
                  @click.stop="deleteQuestion(q.id)"
                  class="absolute top-2 right-2 text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100"
                >
                  <XIcon :size="14" />
                </button>
              </div>
            </div>
          </div>

          <!-- Question Detail Editor -->
          <div class="flex-1 bg-white rounded-xl border border-slate-200 shadow-sm p-8 overflow-y-auto">
            <div v-if="selectedQuestion" class="space-y-6">
              <div class="flex justify-between">
                <h4 class="font-bold text-lg text-slate-800">{{ $t('mod.academy.editing_question') }} {{ selectedQuestionIndex + 1 }}</h4>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-slate-500">{{ $t('mod.academy.points') }}:</span>
                  <input
                    v-model.number="selectedQuestion.points"
                    type="number"
                    class="w-16 border border-slate-300 rounded px-2 py-1 text-sm"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.question_text') }}</label>
                <textarea
                  v-model="selectedQuestion.text"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 h-24 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                  :placeholder="$t('mod.academy.enter_question')"
                />
              </div>

              <!-- Answers/Options -->
              <div class="p-4 bg-slate-50 rounded-lg border border-slate-100">
                <p class="text-sm font-bold text-slate-600 mb-3">{{ $t('mod.academy.answers_options') }}</p>
                <div class="space-y-2">
                  <div v-for="(opt, idx) in selectedQuestion.options" :key="idx" class="flex items-center gap-2">
                    <input
                      type="radio"
                      :name="`correct-${selectedQuestion.id}`"
                      :checked="selectedQuestion.correctAnswer === opt"
                      @change="selectedQuestion.correctAnswer = opt"
                    />
                    <input
                      v-model="selectedQuestion.options[idx]"
                      class="flex-1 border border-slate-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                    />
                    <button @click="removeOption(idx)" class="text-slate-400 hover:text-rose-500">
                      <Trash2Icon :size="14" />
                    </button>
                  </div>
                  <button @click="addOption" class="text-brand-600 text-sm font-medium mt-2">
                    + {{ $t('mod.academy.add_option') }}
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.media_optional') }}</label>
                <input
                  v-model="selectedQuestion.mediaUrl"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                  :placeholder="$t('mod.academy.image_video_url')"
                />
              </div>
            </div>
            <div v-else class="h-full flex items-center justify-center text-slate-400">
              {{ $t('mod.academy.select_question') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Plus as PlusIcon,
  Trash2 as Trash2Icon,
  X as XIcon,
  CheckCircle as CheckCircleIcon,
  CheckSquare as CheckSquareIcon,
  Sliders as SlidersIcon,
  Move as MoveIcon,
  AlignLeft as AlignLeftIcon,
  Type as TypeIcon,
  Puzzle as PuzzleIcon
} from 'lucide-vue-next'

interface QuizQuestion {
  id: string
  text: string
  type: string
  points: number
  options: string[]
  correctAnswer: string
  mediaUrl?: string
}

interface QuizSettings {
  allowedAttempts: number
  passingScore: number
  shuffleQuestions: boolean
  layout: string
  feedbackMode: string
}

interface Quiz {
  id?: string
  title: string
  summary: string
  questions: QuizQuestion[]
  settings: QuizSettings
}

const props = defineProps<{
  quiz: Quiz
}>()

const emit = defineEmits<{
  save: [quiz: Quiz]
  cancel: []
}>()

const { t: $t } = useI18n()

const formData = reactive<Quiz>({
  ...props.quiz,
  questions: [...(props.quiz.questions || [])],
  settings: { ...(props.quiz.settings || {
    allowedAttempts: 0,
    passingScore: 70,
    shuffleQuestions: false,
    layout: 'Single Page',
    feedbackMode: 'Immediate'
  }) }
})

const currentTab = ref<'general' | 'questions' | 'settings'>('general')
const selectedQuestionIndex = ref<number | null>(null)

const tabs = ['general', 'questions', 'settings']

const questionTypes = [
  { type: 'Single Choice', label: $t('mod.academy.single_choice'), icon: CheckCircleIcon },
  { type: 'Multiple Choice', label: $t('mod.academy.multiple_choice'), icon: CheckSquareIcon },
  { type: 'True / False', label: $t('mod.academy.true_false'), icon: SlidersIcon },
  { type: 'Slider', label: $t('mod.academy.slider'), icon: MoveIcon },
  { type: 'Essay', label: $t('mod.academy.essay'), icon: AlignLeftIcon },
  { type: 'Fill Blanks', label: $t('mod.academy.fill_blanks'), icon: TypeIcon },
  { type: 'Matching', label: $t('mod.academy.matching'), icon: PuzzleIcon }
]

const selectedQuestion = computed(() =>
  selectedQuestionIndex.value !== null ? formData.questions[selectedQuestionIndex.value] : null
)

const addQuestion = (type: string) => {
  const newQ: QuizQuestion = {
    id: `q_${Date.now()}`,
    text: 'New Question',
    type,
    points: 1,
    options: ['Option 1', 'Option 2'],
    correctAnswer: 'Option 1'
  }
  formData.questions.push(newQ)
  selectedQuestionIndex.value = formData.questions.length - 1
}

const deleteQuestion = (id: string) => {
  const index = formData.questions.findIndex(q => q.id === id)
  if (index !== -1) {
    formData.questions.splice(index, 1)
    if (selectedQuestionIndex.value === index) {
      selectedQuestionIndex.value = null
    }
  }
}

const addOption = () => {
  if (selectedQuestion.value) {
    selectedQuestion.value.options.push(`Option ${selectedQuestion.value.options.length + 1}`)
  }
}

const removeOption = (idx: number) => {
  if (selectedQuestion.value && selectedQuestion.value.options.length > 2) {
    selectedQuestion.value.options.splice(idx, 1)
  }
}

const handleSave = () => {
  emit('save', formData)
}
</script>
