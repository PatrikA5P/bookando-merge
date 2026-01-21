<template>
  <!-- Lesson Editor Full Screen -->
  <LessonEditor
    v-if="view === 'editor'"
    :lesson="selectedLesson"
    :groups="groups"
    @save="handleSaveLesson"
    @cancel="handleCancel"
  />

  <!-- Lesson List -->
  <div v-else class="p-6 flex flex-col h-full space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-800">{{ $t('mod.academy.lessons_library') }}</h2>
        <p class="text-sm text-slate-500">{{ $t('mod.academy.lessons_subtitle') }}</p>
      </div>
      <div class="flex gap-2">
        <button
          @click="isGroupManagerOpen = true"
          class="p-2 bg-white border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-lg shadow-sm transition-colors"
          :title="$t('mod.academy.manage_groups')"
        >
          <SettingsIcon :size="20" />
        </button>
        <button
          @click="handleCreateLesson"
          class="bg-brand-600 border border-transparent text-white hover:bg-brand-700 px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 font-medium transition-colors"
        >
          <PlusIcon :size="18" /> {{ $t('mod.academy.actions.create_lesson') }}
        </button>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col flex-1 overflow-hidden">
      <!-- Filter Toolbar -->
      <div class="p-4 border-b border-slate-100 flex gap-4 items-center">
        <div class="relative flex-1 max-w-md">
          <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16" />
          <input
            v-model="searchTerm"
            type="text"
            :placeholder="$t('mod.academy.search_lessons')"
            class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
          />
        </div>

        <div class="relative min-w-[180px]">
          <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none">
            <FilterIcon :size="16" />
          </div>
          <select
            v-model="selectedGroupId"
            class="w-full pl-9 pr-8 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 bg-white appearance-none cursor-pointer"
          >
            <option value="all">{{ $t('mod.academy.all_groups') }}</option>
            <option v-for="group in groups" :key="group.id" :value="group.id">
              {{ group.title }}
            </option>
          </select>
          <ChevronDownIcon :size="14" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
        </div>
      </div>

      <!-- Lesson List -->
      <div class="overflow-y-auto flex-1">
        <table class="w-full text-left">
          <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase sticky top-0">
            <tr>
              <th class="p-4">{{ $t('mod.academy.lesson_title') }}</th>
              <th class="p-4">{{ $t('mod.academy.group') }}</th>
              <th class="p-4">{{ $t('mod.academy.attachments') }}</th>
              <th class="p-4 text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="lesson in filteredLessons" :key="lesson.id" class="hover:bg-slate-50 group">
              <td class="p-4">
                <div class="font-medium text-slate-800">{{ lesson.title }}</div>
                <div class="text-xs text-slate-400 line-clamp-1 max-w-md">
                  {{ stripHtml(lesson.content) || $t('mod.academy.no_content') }}
                </div>
              </td>
              <td class="p-4 text-sm text-slate-500">
                <span
                  v-if="lesson.groupId"
                  class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-medium border border-indigo-100"
                >
                  {{ getGroupTitle(lesson.groupId) }}
                </span>
                <span v-else class="text-slate-400 italic text-xs">{{ $t('mod.academy.unassigned') }}</span>
              </td>
              <td class="p-4 text-sm text-slate-500">
                <div v-if="getTotalAttachments(lesson) > 0" class="flex items-center gap-2">
                  <PaperclipIcon :size="14" />
                  {{ getTotalAttachments(lesson) }} {{ $t('mod.academy.items') }}
                </div>
                <span v-else>-</span>
              </td>
              <td class="p-4 text-right">
                <button
                  @click="handleEditLesson(lesson)"
                  class="p-2 text-slate-400 hover:text-brand-600 rounded-full hover:bg-brand-50 transition-colors"
                >
                  <Edit2Icon :size="16" />
                </button>
              </td>
            </tr>
            <tr v-if="filteredLessons.length === 0">
              <td colSpan="4" class="p-12 text-center text-slate-400">
                <div class="flex flex-col items-center">
                  <BookOpenIcon :size="48" class="opacity-20 mb-4" />
                  <p>{{ $t('mod.academy.no_lessons_found') }}</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Group Manager Modal (placeholder for now) -->
    <div
      v-if="isGroupManagerOpen"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="isGroupManagerOpen = false"
    >
      <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-slate-800 mb-4">{{ $t('mod.academy.manage_groups') }}</h3>
        <p class="text-sm text-slate-500 mb-4">{{ $t('mod.academy.group_management_coming_soon') }}</p>
        <button
          @click="isGroupManagerOpen = false"
          class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-medium w-full"
        >
          {{ $t('common.close') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Plus as PlusIcon,
  Settings as SettingsIcon,
  Search as SearchIcon,
  Filter as FilterIcon,
  ChevronDown as ChevronDownIcon,
  Paperclip as PaperclipIcon,
  Edit2 as Edit2Icon,
  BookOpen as BookOpenIcon
} from 'lucide-vue-next'
import LessonEditor from '../editors/LessonEditor.vue'

interface LessonGroup {
  id: string
  title: string
}

interface Lesson {
  id: string
  title: string
  content?: string
  mediaUrls?: string[]
  fileAttachments?: string[]
  groupId?: string
}

const { t: $t } = useI18n()

// State
const searchTerm = ref('')
const selectedGroupId = ref('all')
const isGroupManagerOpen = ref(false)
const view = ref<'list' | 'editor'>('list')
const selectedLesson = ref<Lesson | null>(null)

// Mock data
const groups = ref<LessonGroup[]>([
  { id: 'lg1', title: 'Basics' },
  { id: 'lg2', title: 'Advanced Techniques' },
  { id: 'lg3', title: 'Theory & Background' }
])

const lessons = ref<Lesson[]>([
  {
    id: 'l1',
    title: 'Introduction to Safety',
    content: 'Basic safety protocols and procedures.',
    groupId: 'lg1',
    mediaUrls: [],
    fileAttachments: ['safety-guide.pdf']
  },
  {
    id: 'l2',
    title: 'Advanced Techniques',
    content: 'More complex procedures for experienced staff.',
    groupId: 'lg2',
    mediaUrls: ['video1.mp4'],
    fileAttachments: []
  }
])

// Computed
const filteredLessons = computed(() => {
  return lessons.value.filter(lesson => {
    const matchesSearch = lesson.title.toLowerCase().includes(searchTerm.value.toLowerCase())
    const matchesGroup = selectedGroupId.value === 'all' || lesson.groupId === selectedGroupId.value
    return matchesSearch && matchesGroup
  })
})

// Methods
const stripHtml = (html?: string) => {
  if (!html) return ''
  return html.replace(/<[^>]*>?/gm, '')
}

const getGroupTitle = (groupId: string) => {
  return groups.value.find(g => g.id === groupId)?.title || ''
}

const getTotalAttachments = (lesson: Lesson) => {
  return (lesson.mediaUrls?.length || 0) + (lesson.fileAttachments?.length || 0)
}

const handleCreateLesson = () => {
  selectedLesson.value = {
    id: `new_${Date.now()}`,
    title: '',
    content: '',
    mediaUrls: [],
    fileAttachments: [],
    groupId: ''
  }
  view.value = 'editor'
}

const handleEditLesson = (lesson: Lesson) => {
  selectedLesson.value = { ...lesson }
  view.value = 'editor'
}

const handleSaveLesson = (updatedLesson: Lesson) => {
  const index = lessons.value.findIndex(l => l.id === updatedLesson.id)
  if (index !== -1) {
    lessons.value[index] = updatedLesson
  } else {
    lessons.value.push(updatedLesson)
  }
  view.value = 'list'
  selectedLesson.value = null
}

const handleCancel = () => {
  view.value = 'list'
  selectedLesson.value = null
}
</script>
