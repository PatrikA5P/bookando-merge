<template>
  <ModuleLayout
    :hero-title="$t('mod.academy.title')"
    :hero-description="$t('mod.academy.description')"
    :hero-icon="GraduationCapIcon"
    hero-gradient="bg-gradient-to-br from-purple-700 to-indigo-900"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:active-tab="handleTabChange"
    :show-search="false"
    :primary-action="currentPrimaryAction"
  >
    <!-- Courses Tab -->
    <CoursesTab
      v-if="activeTab === 'courses' && view === 'list'"
      :courses="store.courses"
      @add-course="handleCreateCourse"
      @edit-course="handleEditCourse"
    />

    <!-- Education Cards Tab -->
    <CardsTab
      v-if="activeTab === 'cards' && view === 'list'"
      :cards="eduCards"
      @create-card="handleCreateCard"
      @edit-card="handleEditCard"
      @delete-card="handleDeleteCard"
    />

    <!-- Lessons Tab -->
    <LessonsTab
      v-if="activeTab === 'lessons' && view === 'list'"
    />

    <!-- Badges Tab -->
    <BadgesTab
      v-if="activeTab === 'badges' && view === 'list'"
    />

    <!-- Course Editor (Full Screen) -->
    <CourseEditor
      v-if="view === 'editor' && selectedCourseId"
      :course="selectedCourse!"
      @save="handleSaveCourse"
      @back="handleBack"
    />

    <!-- Education Card Editor (Full Screen) -->
    <EducationCardEditor
      v-if="view === 'editor' && selectedCardId"
      :template="selectedCard!"
      @save="handleSaveCard"
      @back="handleBack"
    />
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAcademyStore } from '../store/academyStore'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import {
  GraduationCap as GraduationCapIcon,
  LayoutGrid as LayoutGridIcon,
  Book as BookIcon,
  CreditCard as CreditCardIcon,
  Award as AwardIcon,
  Plus as PlusIcon
} from 'lucide-vue-next'

// Import Tab Components
import CoursesTab from '../tabs/CoursesTab.vue'
import CardsTab from '../tabs/CardsTab.vue'
import LessonsTab from '../tabs/LessonsTab.vue'
import BadgesTab from '../tabs/BadgesTab.vue'

// Import Editor Components (will create stub implementations for now)
import CourseEditor from '../editors/CourseEditor.vue'
import EducationCardEditor from '../editors/EducationCardEditor.vue'

// Types (matching React types from Academy.tsx)
interface EducationMedia {
  type: 'image' | 'video'
  url: string
  label: string
}

interface EducationItem {
  id: string
  title: string
  description?: string
  media: EducationMedia[]
  originalLessonId?: string
}

interface EducationChapter {
  id: string
  title: string
  items: EducationItem[]
}

interface GradingConfig {
  type: 'slider' | 'buttons' | 'stars'
  min: number
  max: number
  labels?: { min: string; max: string }
}

interface AutomationRule {
  enabled: boolean
  triggerType: 'Service' | 'Category'
  triggerId: string
  allowMultiple: boolean
}

interface EducationCardTemplate {
  id: string
  title: string
  description: string
  chapters: EducationChapter[]
  grading: GradingConfig
  automation: AutomationRule
  active: boolean
}

const { t: $t } = useI18n()
const store = useAcademyStore()

// State
const activeTab = ref<'courses' | 'lessons' | 'badges' | 'cards'>('courses')
const view = ref<'list' | 'editor'>('list')
const selectedCourseId = ref<string | null>(null)
const selectedCardId = ref<string | null>(null)

// Local state for Education Cards (simulating separate store)
const eduCards = ref<EducationCardTemplate[]>([
  {
    id: 'ec_1',
    title: 'Fahrschul-Ausbildungskarte Klasse B',
    description: 'Standardisierter Ausbildungsplan für PKW.',
    chapters: [
      {
        id: 'c1',
        title: 'Grundstufe',
        items: [
          { id: 'i1', title: 'Einsteigen & Sitzeinstellung', media: [] },
          { id: 'i2', title: 'Anfahren & Schalten', media: [] }
        ]
      }
    ],
    grading: { type: 'buttons', min: 1, max: 5, labels: { min: 'Ungenügend', max: 'Sehr Gut' } },
    automation: { enabled: false, triggerType: 'Category', triggerId: '', allowMultiple: false },
    active: true
  }
])

// Tabs definition
const tabs = ref([
  { id: 'courses', icon: LayoutGridIcon, label: $t('mod.academy.tabs.courses') },
  { id: 'lessons', icon: BookIcon, label: $t('mod.academy.tabs.lessons') },
  { id: 'cards', icon: CreditCardIcon, label: $t('mod.academy.tabs.cards') },
  { id: 'badges', icon: AwardIcon, label: $t('mod.academy.tabs.badges') }
])

// Computed
const selectedCourse = computed(() => {
  return store.courses.find(c => c.id === selectedCourseId.value)
})

const selectedCard = computed(() => {
  return eduCards.value.find(c => c.id === selectedCardId.value)
})

const currentPrimaryAction = computed(() => {
  if (view.value === 'editor') return undefined

  if (activeTab.value === 'courses') {
    return {
      label: $t('mod.academy.actions.create_course'),
      icon: PlusIcon,
      onClick: handleCreateCourse
    }
  }

  if (activeTab.value === 'cards') {
    return {
      label: $t('mod.academy.actions.create_template'),
      icon: PlusIcon,
      onClick: handleCreateCard
    }
  }

  return undefined
})

// Methods
const handleTabChange = (tabId: string) => {
  activeTab.value = tabId as 'courses' | 'lessons' | 'badges' | 'cards'
  view.value = 'list'
}

const handleEditCourse = (courseId: string) => {
  selectedCourseId.value = courseId
  view.value = 'editor'
}

const handleCreateCourse = () => {
  const newCourse = {
    id: `new_${Date.now()}`,
    title: 'New Untitled Course',
    description: '',
    type: 'online',
    author: 'Current User',
    visibility: 'private',
    category: { id: 'uc', name: 'Uncategorized' },
    tags: [],
    difficulty: 'beginner',
    coverImage: '',
    studentsCount: 0,
    published: false,
    certificate: { enabled: false, templateId: 'default', showScore: true, signatureText: '' },
    curriculum: []
  }
  store.addCourse(newCourse)
  selectedCourseId.value = newCourse.id
  view.value = 'editor'
}

const handleSaveCourse = (updatedCourse: any) => {
  store.updateCourse(updatedCourse)
  view.value = 'list'
  selectedCourseId.value = null
}

// Card Handlers
const handleCreateCard = () => {
  const newCard: EducationCardTemplate = {
    id: `ec_${Date.now()}`,
    title: 'New Education Plan',
    description: '',
    chapters: [],
    grading: { type: 'buttons', min: 1, max: 4 },
    automation: { enabled: false, triggerType: 'Service', triggerId: '', allowMultiple: false },
    active: false
  }
  eduCards.value.push(newCard)
  selectedCardId.value = newCard.id
  view.value = 'editor'
}

const handleEditCard = (id: string) => {
  selectedCardId.value = id
  view.value = 'editor'
}

const handleSaveCard = (updatedCard: EducationCardTemplate) => {
  const index = eduCards.value.findIndex(c => c.id === updatedCard.id)
  if (index !== -1) {
    eduCards.value[index] = updatedCard
  }
  view.value = 'list'
  selectedCardId.value = null
}

const handleDeleteCard = (id: string) => {
  if (confirm($t('mod.academy.confirm_delete_card'))) {
    eduCards.value = eduCards.value.filter(c => c.id !== id)
  }
}

const handleBack = () => {
  view.value = 'list'
  selectedCourseId.value = null
  selectedCardId.value = null
}

// Lifecycle
onMounted(async () => {
  await store.loadCourses()
})
</script>
