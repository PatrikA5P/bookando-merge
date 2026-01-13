<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <template #header>
          <AppPageHeader
            :title="t('mod.academy.title')"
            hide-brand-below="md"
          >
            <template #actions>
              <div class="bookando-flex bookando-gap-sm">
                <AppButton
                  icon="rotate-cw"
                  variant="ghost"
                  :loading="loading"
                  @click="loadState"
                >
                  {{ t('mod.academy.actions.refresh') }}
                </AppButton>
                <AppButton
                  icon="book"
                  variant="secondary"
                  @click="openCourseForm()"
                >
                  {{ t('mod.academy.actions.add_course') }}
                </AppButton>
                <AppButton
                  icon="user-plus"
                  variant="primary"
                  @click="openTrainingForm()"
                >
                  {{ t('mod.academy.actions.add_card') }}
                </AppButton>
              </div>
            </template>
          </AppPageHeader>
        </template>

        <template #nav>
          <AppTabs
            v-model="currentTab"
            :tabs="tabItems"
            nav-only
          />
        </template>

        <div class="bookando-container bookando-pb-xl">
          <div
            v-if="error"
            class="bookando-alert bookando-alert--danger bookando-mb-md"
            role="alert"
          >
            {{ error }}
          </div>

          <!-- KURSE TAB -->
          <div v-if="currentTab === 'courses'">
            <div class="bookando-academy-card">
              <div class="bookando-academy-card__header">
                <h2 class="bookando-h5 bookando-m-0">
                  {{ t('mod.academy.tabs.courses') }}
                </h2>
                <AppButton
                  size="square"
                  btn-type="icononly"
                  variant="ghost"
                  icon="plus"
                  icon-size="lg"
                  :tooltip="t('mod.academy.actions.add_course')"
                  @click="openCourseForm()"
                />
              </div>
              <div class="bookando-academy-card__body">
                <div
                  v-if="!courses.length && !loading"
                  class="bookando-alert bookando-alert--info"
                >
                  <p>{{ t('mod.academy.messages.no_courses') }}</p>
                  <p class="bookando-m-0">
                    <strong>Tipp:</strong> Sie können vorgefertigte Kurs-Templates für Kategorie A (Motorrad) und B (PKW) erstellen:
                    <AppButton
                      variant="primary"
                      size="sm"
                      icon="download"
                      :loading="creatingTemplates"
                      @click="createTemplates"
                      style="margin-left: 8px;"
                    >
                      Kurs-Templates für Kat A & B erstellen
                    </AppButton>
                  </p>
                </div>

                <div
                  v-else
                  class="bookando-academy-list"
                >
                  <div
                    v-for="course in courses"
                    :key="course.id"
                    class="bookando-academy-list__item bookando-academy-course-item"
                  >
                    <div class="bookando-flex bookando-gap-md">
                      <div
                        v-if="course.featured_image"
                        class="bookando-academy-course-thumb"
                      >
                        <img
                          :src="course.featured_image"
                          :alt="course.title"
                        >
                      </div>
                      <div class="bookando-flex-grow">
                        <div class="bookando-flex bookando-justify-between bookando-items-start bookando-gap-md">
                          <div>
                            <div class="bookando-flex bookando-items-center bookando-gap-sm bookando-mb-xs">
                              <h3 class="bookando-h6 bookando-m-0">
                                {{ course.title }}
                              </h3>
                              <span
                                v-if="course.visibility"
                                class="bookando-badge"
                                :class="`bookando-badge--${visibilityVariant(course.visibility)}`"
                              >
                                {{ visibilityLabel(course.visibility) }}
                              </span>
                            </div>
                            <p class="bookando-text-muted bookando-mb-sm">
                              {{ course.description || '–' }}
                            </p>
                            <div class="bookando-flex bookando-gap-md bookando-text-sm bookando-text-muted bookando-flex-wrap">
                              <span v-if="course.category">{{ course.category }}</span>
                              <span v-if="course.level">{{ levelLabel(course.level) }}</span>
                              <span v-if="course.course_type">{{ course.course_type === 'online' ? '🌐 Online' : '🏢 Präsenz' }}</span>
                              <span v-if="course.topics?.length">{{ course.topics.length }} Themen</span>
                              <span v-if="totalLessons(course) > 0">{{ totalLessons(course) }} Lektionen</span>
                              <span v-if="totalQuizzes(course) > 0">{{ totalQuizzes(course) }} Tests</span>
                            </div>
                          </div>
                          <div class="bookando-flex bookando-gap-sm">
                            <AppButton
                              variant="secondary"
                              size="square"
                              btn-type="icononly"
                              icon="edit-3"
                              icon-size="md"
                              :tooltip="t('core.common.edit')"
                              @click="openCourseForm(course)"
                            />
                            <AppButton
                              variant="danger"
                              size="square"
                              btn-type="icononly"
                              icon="trash"
                              icon-size="md"
                              :loading="deletingId === course.id"
                              :tooltip="t('mod.academy.actions.delete')"
                              @click="removeCourse(course)"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- AUSBILDUNGSKARTEN TAB -->
          <div v-else>
            <div class="bookando-academy-card">
              <div class="bookando-academy-card__header">
                <h2 class="bookando-h5 bookando-m-0">
                  {{ t('mod.academy.tabs.training') }}
                </h2>
                <AppButton
                  size="square"
                  btn-type="icononly"
                  variant="ghost"
                  icon="plus"
                  icon-size="lg"
                  :tooltip="t('mod.academy.actions.add_card')"
                  @click="openTrainingForm()"
                />
              </div>
              <div class="bookando-academy-card__body">
                <div
                  v-if="!trainingCards.length && !loading"
                  class="bookando-alert bookando-alert--info"
                >
                  {{ t('mod.academy.messages.no_training') }}
                </div>

                <div class="bookando-academy-list">
                  <div
                    v-for="card in trainingCards"
                    :key="card.id"
                    class="bookando-academy-list__item"
                  >
                    <div class="bookando-flex bookando-justify-between bookando-items-start bookando-gap-md">
                      <div class="bookando-flex-grow">
                        <div class="bookando-flex bookando-items-center bookando-gap-sm bookando-mb-xs">
                          <h3 class="bookando-h6 bookando-m-0">
                            {{ card.student }}
                          </h3>
                          <span
                            v-if="card.category"
                            class="bookando-badge bookando-badge--primary"
                          >
                            {{ t('mod.academy.labels.category') }} {{ card.category }}
                          </span>
                        </div>
                        <p class="bookando-text-muted bookando-m-0">
                          {{ card.program }} · {{ card.instructor || '–' }}
                        </p>
                        <div
                          v-if="card.main_topics?.length"
                          class="bookando-flex bookando-gap-md bookando-text-sm bookando-text-muted bookando-mt-xs"
                        >
                          <span>{{ card.main_topics.length }} {{ t('mod.academy.labels.main_topics') }}</span>
                          <span>{{ totalTrainingLessons(card) }} {{ t('mod.academy.labels.lessons') }}</span>
                          <span>{{ completedTrainingLessons(card) }} {{ t('mod.academy.labels.completed') }}</span>
                        </div>
                        <div class="bookando-academy-progress bookando-mt-sm">
                          <div
                            class="bookando-academy-progress__bar"
                            :style="{ width: `${Math.round(trainingProgress(card) * 100)}%` }"
                          />
                        </div>
                        <p class="bookando-text-sm bookando-text-muted bookando-mt-xs">
                          {{ Math.round(trainingProgress(card) * 100) }}%
                        </p>
                      </div>
                      <div class="bookando-flex bookando-gap-sm">
                        <AppButton
                          variant="secondary"
                          size="square"
                          btn-type="icononly"
                          icon="edit-3"
                          icon-size="md"
                          :tooltip="t('core.common.edit')"
                          @click="openTrainingForm(card)"
                        />
                        <AppButton
                          variant="danger"
                          size="square"
                          btn-type="icononly"
                          icon="trash"
                          icon-size="md"
                          :loading="deletingId === card.id"
                          :tooltip="t('mod.academy.actions.delete')"
                          @click="removeTrainingCard(card)"
                        />
                      </div>
                    </div>

                    <div
                      v-if="card.notes"
                      class="bookando-text-sm bookando-mt-sm"
                    >
                      {{ card.notes }}
                    </div>

                    <div
                      v-if="card.milestones?.length"
                      class="bookando-academy-milestones bookando-mt-sm"
                    >
                      <strong class="bookando-text-sm">{{ t('mod.academy.labels.milestones') }}</strong>
                      <ul class="bookando-list bookando-m-0 bookando-ml-md">
                        <li
                          v-for="(milestone, idx) in card.milestones"
                          :key="`${card.id}-${idx}`"
                        >
                          <span>
                            <strong>{{ milestone.title }}</strong>
                            <span
                              v-if="milestone.completed"
                              class="bookando-text-success"
                            > · ✓</span>
                          </span>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </AppPageLayout>
    </div>

    <!-- KURS MODAL (ERWEITERT) -->
    <CourseModal
      v-if="showCourseModal"
      :course="activeCourse"
      @close="closeCourseModal"
      @save="handleCourseSave"
    />

    <!-- TRAINING CARD MODAL (ERWEITERT MIT DRAG & DROP) -->
    <TrainingCardModal
      v-if="showTrainingModal"
      :card="activeTraining"
      :available-courses="courses"
      @close="closeTrainingModal"
      @save="handleTrainingSave"
    />

    <!-- CONFIRMATION MODAL -->
    <AppModal
      :show="confirmState.show"
      :type="confirmState.type"
      :title="confirmState.title"
      :message="confirmState.message"
      :confirm-text="confirmState.confirmText"
      :cancel-text="confirmState.cancelText"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </AppShell>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import AppModal from '@core/Design/components/AppModal.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import { notify } from '@core/Composables/useNotifier'
import { useConfirm } from '../composables/useConfirm'

import CourseModal from '../components/CourseModal.vue'
import TrainingCardModal from '../components/TrainingCardModal.vue'

import {
  fetchState,
  saveCourse,
  deleteCourse,
  saveTrainingCard,
  deleteTrainingCard,
  updateTrainingProgress,
  getDefaultTrainingCardKategorieA,
  getDefaultTrainingCardKategorieB,
  type AcademyCourse,
  type TrainingCard,
  type TrainingMilestone,
} from '../api/AcademyApi'

const { t } = useI18n()
const { confirmState, confirm: confirmAction, handleConfirm, handleCancel } = useConfirm()

const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

const loading = ref(false)
const error = ref<string | null>(null)
const deletingId = ref<string | null>(null)
const creatingTemplates = ref(false)

const courses = ref<AcademyCourse[]>([])
const trainingCards = ref<TrainingCard[]>([])

const currentTab = ref<'courses' | 'training'>('courses')
const tabItems = computed(() => ([
  { label: t('mod.academy.tabs.courses'), value: 'courses' },
  { label: t('mod.academy.tabs.training'), value: 'training' }
]))

// Course Modal
const showCourseModal = ref(false)
const activeCourse = ref<AcademyCourse | null>(null)

// Training Modal
const showTrainingModal = ref(false)
const activeTraining = ref<TrainingCard | null>(null)

onMounted(async () => {
  await loadState()
})

async function loadState() {
  loading.value = true
  error.value = null
  try {
    const data = await fetchState()
    courses.value = data.courses || []
    trainingCards.value = data.training_cards || []
  } catch (err: any) {
    console.error('[Bookando] Failed to load academy data', err)
    error.value = err?.message || t('mod.academy.messages.load_error')
  } finally {
    loading.value = false
  }
}

async function createTemplates() {
  const confirmed = await confirmAction({
    title: 'Templates erstellen',
    message: 'Möchten Sie die Kurs-Templates für Kategorie A und B erstellen?\n\nDies fügt 2 neue Kurse hinzu.',
    confirmText: 'Erstellen',
    cancelText: 'Abbrechen',
    type: 'info'
  })
  if (!confirmed) {
    return
  }

  creatingTemplates.value = true
  try {
    // Importiere die Template-Funktionen
    const { getDefaultCourseKategorieA, getDefaultCourseKategorieB } = await import('../api/AcademyApi')

    // Erstelle Kategorie B Kurs
    const courseB = getDefaultCourseKategorieB()
    await saveCourse(courseB)

    // Erstelle Kategorie A Kurs
    const courseA = getDefaultCourseKategorieA()
    await saveCourse(courseA)

    // Lade Daten neu
    await loadState()

    notify({ type: 'success', message: 'Kurs-Templates erfolgreich erstellt!' })
  } catch (err: any) {
    console.error('[Bookando] Failed to create templates', err)
    notify({ type: 'danger', message: 'Fehler beim Erstellen der Templates: ' + (err?.message || 'Unbekannter Fehler') })
  } finally {
    creatingTemplates.value = false
  }
}

// ===== COURSE FUNCTIONS =====
function openCourseForm(course?: AcademyCourse) {
  activeCourse.value = course ? { ...course } : null
  showCourseModal.value = true
}

function closeCourseModal() {
  showCourseModal.value = false
  activeCourse.value = null
}

async function handleCourseSave(savedCourse: AcademyCourse) {
  const existingIndex = courses.value.findIndex(c => c.id === savedCourse.id)
  if (existingIndex >= 0) {
    courses.value.splice(existingIndex, 1, savedCourse)
  } else {
    courses.value.push(savedCourse)
  }
  notify({ type: 'success', message: t('mod.academy.messages.save_success') })
  closeCourseModal()
}

async function removeCourse(course: AcademyCourse) {

  if (!course.id) {
    console.error('[Academy] No course ID provided')
    return
  }


  try {
    const confirmed = await confirmAction({
      title: 'Kurs löschen',
      message: t('mod.academy.messages.confirm_delete'),
      confirmText: 'Löschen',
      cancelText: 'Abbrechen',
      type: 'danger'
    })


    if (!confirmed) {
      return
    }

    deletingId.value = course.id

    const ok = await deleteCourse(course.id)


    if (ok) {
      courses.value = courses.value.filter(c => c.id !== course.id)
      notify({ type: 'success', message: t('mod.academy.messages.delete_success') })
    } else {
      notify({ type: 'danger', message: t('mod.academy.messages.delete_error') })
    }
  } catch (err) {
    console.error('[Academy] Failed to delete course:', err)
    if (err instanceof Error) {
      console.error('[Academy] Error message:', err.message)
      console.error('[Academy] Error stack:', err.stack)
    }
    notify({ type: 'danger', message: t('mod.academy.messages.delete_error') })
  } finally {
    deletingId.value = null
  }
}

// ===== TRAINING FUNCTIONS =====
function openTrainingForm(card?: TrainingCard) {
  activeTraining.value = card ? { ...card } : null
  showTrainingModal.value = true
}

function closeTrainingModal() {
  showTrainingModal.value = false
  activeTraining.value = null
}

async function handleTrainingSave(savedCard: TrainingCard) {
  try {
    const saved = await saveTrainingCard(savedCard)
    const existingIndex = trainingCards.value.findIndex(c => c.id === saved.id)
    if (existingIndex >= 0) {
      trainingCards.value.splice(existingIndex, 1, saved)
    } else {
      trainingCards.value.push(saved)
    }
    notify({ type: 'success', message: t('mod.academy.messages.save_success') })
    closeTrainingModal()
  } catch (err: any) {
    console.error('[Bookando] Failed to save training card', err)
    notify({ type: 'danger', message: err?.message || t('mod.academy.messages.save_error') })
  }
}

async function removeTrainingCard(card: TrainingCard) {
  if (!card.id) return
  const confirmed = await confirmAction({
    title: 'Ausbildungskarte löschen',
    message: t('mod.academy.messages.confirm_delete'),
    confirmText: 'Löschen',
    cancelText: 'Abbrechen',
    type: 'danger'
  })
  if (!confirmed) return

  deletingId.value = card.id
  try {
    const ok = await deleteTrainingCard(card.id)
    if (ok) {
      trainingCards.value = trainingCards.value.filter(c => c.id !== card.id)
      notify({ type: 'success', message: t('mod.academy.messages.delete_success') })
    } else {
      notify({ type: 'danger', message: t('mod.academy.messages.delete_error') })
    }
  } catch (err) {
    console.error('[Bookando] Failed to delete training card', err)
    notify({ type: 'danger', message: t('mod.academy.messages.delete_error') })
  } finally {
    deletingId.value = null
  }
}

// ===== HELPER FUNCTIONS =====
function levelLabel(level?: string) {
  switch (level) {
    case 'intermediate':
      return '⭐⭐ ' + t('mod.academy.levels.intermediate')
    case 'advanced':
      return '⭐⭐⭐ ' + t('mod.academy.levels.advanced')
    default:
      return '⭐ ' + t('mod.academy.levels.beginner')
  }
}

function visibilityLabel(visibility: string) {
  switch (visibility) {
    case 'logged_in':
      return '🔐 ' + t('mod.academy.visibility.logged_in')
    case 'private':
      return '🔒 ' + t('mod.academy.visibility.private')
    default:
      return '🌐 ' + t('mod.academy.visibility.public')
  }
}

function visibilityVariant(visibility: string) {
  switch (visibility) {
    case 'private':
      return 'danger'
    case 'logged_in':
      return 'warning'
    default:
      return 'success'
  }
}

function totalLessons(course: AcademyCourse): number {
  return course.topics?.reduce((sum, topic) => sum + (topic.lessons?.length || 0), 0) || 0
}

function totalQuizzes(course: AcademyCourse): number {
  return course.topics?.reduce((sum, topic) => sum + (topic.quizzes?.length || 0), 0) || 0
}

function totalTrainingLessons(card: TrainingCard): number {
  return card.main_topics?.reduce((sum, topic) => sum + (topic.lessons?.length || 0), 0) || 0
}

function completedTrainingLessons(card: TrainingCard): number {
  return card.main_topics?.reduce((sum, topic) => {
    return sum + (topic.lessons?.filter(lesson => lesson.completed)?.length || 0)
  }, 0) || 0
}

function trainingProgress(card: TrainingCard): number {
  const total = totalTrainingLessons(card)
  if (total === 0) return card.progress || 0
  const completed = completedTrainingLessons(card)
  return completed / total
}
</script>
