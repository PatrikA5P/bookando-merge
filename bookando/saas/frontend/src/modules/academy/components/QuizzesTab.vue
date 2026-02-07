<script setup lang="ts">
/**
 * QuizzesTab — Quiz-Builder und Uebersicht
 *
 * Zeigt alle Quizze aller Kurse in einer Uebersicht.
 * Inline-Builder fuer neue Fragen (SINGLE_CHOICE, MULTIPLE_CHOICE, TRUE_FALSE, FREE_TEXT).
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useAcademyStore, QUESTION_TYPE_LABELS } from '@/stores/academy';
import type { AcademyQuiz, AcademyCourse, QuestionType } from '@/stores/academy';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const store = useAcademyStore();

const searchQuery = ref('');

// ── Flatten quizzes with course context ──────────────────────────────────
interface QuizWithCourse {
  quiz: AcademyQuiz;
  course: AcademyCourse;
}

const allQuizzes = computed<QuizWithCourse[]>(() => {
  const result: QuizWithCourse[] = [];
  for (const course of store.courses) {
    for (const quiz of course.quizzes) {
      result.push({ quiz, course });
    }
  }
  return result;
});

const filteredQuizzes = computed(() => {
  if (!searchQuery.value) return allQuizzes.value;
  const q = searchQuery.value.toLowerCase();
  return allQuizzes.value.filter(qc =>
    qc.quiz.title.toLowerCase().includes(q) ||
    qc.course.title.toLowerCase().includes(q)
  );
});

// ── Quiz Builder Panel ───────────────────────────────────────────────────
const showBuilder = ref(false);
const editingQuiz = ref<QuizWithCourse | null>(null);

// Builder form
const quizTitle = ref('');
const quizCourseId = ref('');
const passingScore = ref(70);
const timeLimitMinutes = ref<number | undefined>(undefined);
const maxAttempts = ref(3);

interface LocalQuestion {
  id: string;
  questionText: string;
  questionType: QuestionType;
  options: { text: string; isCorrect: boolean }[];
  explanation: string;
  points: number;
}
const questions = ref<LocalQuestion[]>([]);
const saving = ref(false);
const builderDirty = ref(false);

const courseOptions = computed(() => [
  { value: '', label: '-- Kurs waehlen --' },
  ...store.courses.map(c => ({ value: c.id, label: c.title })),
]);

const questionTypeOptions = Object.entries(QUESTION_TYPE_LABELS).map(([v, l]) => ({ value: v, label: l }));

function openCreateQuiz() {
  editingQuiz.value = null;
  quizTitle.value = '';
  quizCourseId.value = store.courses[0]?.id || '';
  passingScore.value = 70;
  timeLimitMinutes.value = undefined;
  maxAttempts.value = 3;
  questions.value = [];
  builderDirty.value = false;
  showBuilder.value = true;
}

function openEditQuiz(qc: QuizWithCourse) {
  editingQuiz.value = qc;
  quizTitle.value = qc.quiz.title;
  quizCourseId.value = qc.course.id;
  passingScore.value = qc.quiz.passingScore;
  timeLimitMinutes.value = qc.quiz.timeLimitMinutes;
  maxAttempts.value = qc.quiz.maxAttempts;
  questions.value = qc.quiz.questions.map(q => ({
    id: q.id,
    questionText: q.questionText,
    questionType: q.questionType,
    options: q.options ? [...q.options] : [],
    explanation: q.explanation || '',
    points: q.points,
  }));
  builderDirty.value = false;
  showBuilder.value = true;
}

// ── Question management ──────────────────────────────────────────────────
function addQuestion() {
  questions.value.push({
    id: `q-${Date.now()}`,
    questionText: '',
    questionType: 'SINGLE_CHOICE',
    options: [
      { text: '', isCorrect: true },
      { text: '', isCorrect: false },
    ],
    explanation: '',
    points: 1,
  });
  builderDirty.value = true;
}

function removeQuestion(index: number) {
  questions.value.splice(index, 1);
  builderDirty.value = true;
}

function addOption(questionIndex: number) {
  questions.value[questionIndex].options.push({ text: '', isCorrect: false });
  builderDirty.value = true;
}

function removeOption(questionIndex: number, optionIndex: number) {
  questions.value[questionIndex].options.splice(optionIndex, 1);
  builderDirty.value = true;
}

function toggleCorrect(questionIndex: number, optionIndex: number) {
  const q = questions.value[questionIndex];
  if (q.questionType === 'SINGLE_CHOICE') {
    q.options.forEach((o, i) => { o.isCorrect = i === optionIndex; });
  } else {
    q.options[optionIndex].isCorrect = !q.options[optionIndex].isCorrect;
  }
  builderDirty.value = true;
}

function getQuestionTypeVariant(type: QuestionType): 'info' | 'purple' | 'warning' | 'success' {
  switch (type) {
    case 'SINGLE_CHOICE': return 'info';
    case 'MULTIPLE_CHOICE': return 'purple';
    case 'TRUE_FALSE': return 'success';
    case 'FREE_TEXT': return 'warning';
    default: return 'info';
  }
}

// ── Save Quiz ────────────────────────────────────────────────────────────
async function handleSaveQuiz() {
  if (!quizTitle.value.trim() || !quizCourseId.value) return;

  saving.value = true;
  try {
    const data = {
      title: quizTitle.value.trim(),
      passingScore: passingScore.value,
      timeLimitMinutes: timeLimitMinutes.value,
      maxAttempts: maxAttempts.value,
      sortOrder: 1,
      questions: questions.value.map((q, i) => ({
        questionText: q.questionText,
        questionType: q.questionType,
        options: q.questionType !== 'FREE_TEXT' ? q.options : undefined,
        explanation: q.explanation || undefined,
        points: q.points,
        sortOrder: i + 1,
      })),
    };

    if (editingQuiz.value) {
      await store.updateQuiz(quizCourseId.value, editingQuiz.value.quiz.id, data as any);
    } else {
      await store.addQuiz(quizCourseId.value, data as any);
    }

    toast.success(t('common.savedSuccessfully'));
    showBuilder.value = false;
  } catch {
    toast.error(t('common.errorOccurred'));
  } finally {
    saving.value = false;
  }
}

async function handleDeleteQuiz() {
  if (!editingQuiz.value) return;
  try {
    await store.deleteQuiz(editingQuiz.value.course.id, editingQuiz.value.quiz.id);
    toast.success(t('common.deletedSuccessfully'));
    showBuilder.value = false;
  } catch {
    toast.error(t('common.errorOccurred'));
  }
}
</script>

<template>
  <div>
    <!-- Search + Create -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <div class="flex-1 max-w-md">
        <BSearchBar v-model="searchQuery" placeholder="Quizze suchen..." />
      </div>
      <BButton variant="primary" @click="openCreateQuiz">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Neues Quiz
      </BButton>
    </div>

    <!-- Quiz Cards -->
    <div v-if="filteredQuizzes.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="qc in filteredQuizzes"
        :key="qc.quiz.id"
        :class="CARD_STYLES.hover"
        class="p-5 cursor-pointer"
        @click="openEditQuiz(qc)"
      >
        <div class="flex items-start justify-between mb-3">
          <div>
            <h3 class="text-sm font-semibold text-slate-900">{{ qc.quiz.title }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ qc.course.title }}</p>
          </div>
          <BBadge variant="purple">{{ qc.quiz.questions.length }} Fragen</BBadge>
        </div>
        <div class="flex items-center gap-4 text-xs text-slate-500">
          <span>Bestehensgrenze: {{ qc.quiz.passingScore }}%</span>
          <span v-if="qc.quiz.timeLimitMinutes">{{ qc.quiz.timeLimitMinutes }} min</span>
          <span>Max. {{ qc.quiz.maxAttempts }} Versuche</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BEmptyState
      v-else
      title="Keine Quizze vorhanden"
      description="Erstellen Sie Ihr erstes Quiz fuer einen Kurs."
      icon="folder"
      action-label="Erstes Quiz erstellen"
      @action="openCreateQuiz"
    />

    <!-- Quiz Builder Panel (SlideIn) -->
    <BFormPanel
      v-model="showBuilder"
      :title="editingQuiz ? 'Quiz bearbeiten' : 'Neues Quiz'"
      :mode="editingQuiz ? 'edit' : 'create'"
      size="xl"
      :saving="saving"
      :dirty="builderDirty"
      @save="handleSaveQuiz"
      @cancel="showBuilder = false"
    >
      <!-- Quiz Meta -->
      <BFormSection title="Quiz-Details" :columns="1">
        <BInput v-model="quizTitle" label="Quiz-Titel" placeholder="z.B. Theorie-Pruefung Modul 1" required />
        <BSelect v-model="quizCourseId" :options="courseOptions" label="Kurs" required />
      </BFormSection>
      <BFormSection :columns="3">
        <BInput v-model.number="passingScore" type="number" label="Bestehensgrenze (%)" placeholder="70" />
        <BInput v-model.number="timeLimitMinutes" type="number" label="Zeitlimit (Min.)" placeholder="Optional" />
        <BInput v-model.number="maxAttempts" type="number" label="Max. Versuche" placeholder="3" />
      </BFormSection>

      <!-- Questions -->
      <BFormSection title="Fragen" :columns="1" divided>
        <div class="space-y-4">
          <div
            v-for="(question, qi) in questions"
            :key="question.id"
            class="border border-slate-200 rounded-lg p-4"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-violet-100 text-violet-700 text-xs font-bold flex items-center justify-center">{{ qi + 1 }}</span>
                <BBadge :variant="getQuestionTypeVariant(question.questionType)">
                  {{ QUESTION_TYPE_LABELS[question.questionType] }}
                </BBadge>
              </div>
              <button class="p-1 rounded hover:bg-red-50 text-slate-400 hover:text-red-600" @click="removeQuestion(qi)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <div class="space-y-3">
              <BTextarea v-model="question.questionText" label="Fragetext" placeholder="Die Frage..." :rows="2" />

              <div class="grid grid-cols-2 gap-3">
                <BSelect
                  v-model="question.questionType"
                  :options="questionTypeOptions"
                  label="Fragentyp"
                />
                <BInput v-model.number="question.points" type="number" label="Punkte" placeholder="1" />
              </div>

              <!-- Options (for choice types) -->
              <div v-if="question.questionType !== 'FREE_TEXT'" class="space-y-2">
                <p class="text-xs font-medium text-slate-500">Antwortoptionen</p>
                <div
                  v-for="(opt, oi) in question.options"
                  :key="oi"
                  class="flex items-center gap-2"
                >
                  <button
                    :class="['w-6 h-6 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors', opt.isCorrect ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 bg-white hover:border-slate-400']"
                    :title="opt.isCorrect ? 'Richtige Antwort' : 'Als richtig markieren'"
                    @click="toggleCorrect(qi, oi)"
                  >
                    <svg v-if="opt.isCorrect" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <input
                    v-model="opt.text"
                    type="text"
                    class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                    :placeholder="`Option ${oi + 1}`"
                  />
                  <button
                    v-if="question.options.length > 2"
                    class="p-1 rounded hover:bg-red-50 text-slate-300 hover:text-red-500"
                    @click="removeOption(qi, oi)"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                  </button>
                </div>
                <BButton variant="ghost" size="sm" @click="addOption(qi)">+ Option</BButton>
              </div>

              <BInput v-model="question.explanation" label="Erklaerung (optional)" placeholder="Wird nach der Beantwortung angezeigt..." />
            </div>
          </div>

          <!-- Add Question -->
          <BButton variant="secondary" class="w-full" @click="addQuestion">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Frage hinzufuegen
          </BButton>
        </div>
      </BFormSection>

      <!-- Footer: Delete -->
      <template v-if="editingQuiz" #footer-left>
        <BButton variant="ghost" class="text-red-600 hover:text-red-700 hover:bg-red-50" @click="handleDeleteQuiz">
          <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          {{ t('common.delete') }}
        </BButton>
      </template>
    </BFormPanel>
  </div>
</template>
