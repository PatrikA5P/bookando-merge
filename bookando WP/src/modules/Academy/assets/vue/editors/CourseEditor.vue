<template>
  <div class="flex flex-col h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-fadeIn">
    <!-- Editor Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
      <div class="flex items-center gap-4">
        <button @click="$emit('back')" class="p-2 hover:bg-white rounded-full text-slate-500 transition-colors border border-transparent hover:border-slate-200">
          <ArrowLeftIcon :size="20" />
        </button>
        <div>
          <input
            v-model="formData.title"
            type="text"
            class="bg-transparent text-xl font-bold text-slate-800 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 rounded px-2 -ml-2"
            :placeholder="$t('mod.academy.course_title')"
          />
          <div class="text-xs text-slate-500 px-1">
            {{ formData.published ? $t('mod.academy.status.published') : $t('mod.academy.status.draft') }} • {{ $t('mod.academy.last_saved') }}
          </div>
        </div>
      </div>
      <button @click="handleSave" class="flex items-center gap-2 bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors font-medium">
        <SaveIcon :size="18" /> {{ $t('common.save') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-slate-200 px-6">
      <button
        v-for="tab in editorTabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        :class="['px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2', activeTab === tab.id ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700']"
      >
        <component :is="tab.icon" :size="16" /> {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="flex-1 overflow-y-auto bg-slate-50/50 p-6">
      <!-- Definition Tab -->
      <div v-if="activeTab === 'definition'" class="max-w-4xl mx-auto space-y-8">
        <!-- Basic Info -->
        <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <BookOpenIcon :size="20" class="text-brand-600" /> {{ $t('mod.academy.basic_information') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="col-span-full">
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.description') }}</label>
              <textarea
                v-model="formData.description"
                rows="4"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.course_type') }}</label>
              <select
                v-model="formData.type"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
              >
                <option value="online">{{ $t('mod.academy.online_course') }}</option>
                <option value="in-person">{{ $t('mod.academy.in_person_class') }}</option>
                <option value="hybrid">{{ $t('mod.academy.blended_learning') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.author_instructor') }}</label>
              <input
                v-model="formData.author"
                type="text"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
              />
            </div>
          </div>
        </section>

        <!-- Visibility & Participation -->
        <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <EyeIcon :size="20" class="text-brand-600" /> {{ $t('mod.academy.visibility_participation') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.visibility') }}</label>
              <select
                v-model="formData.visibility"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
              >
                <option value="private">🔒 {{ $t('mod.academy.private_draft') }}</option>
                <option value="internal">🏢 {{ $t('mod.academy.internal_only') }}</option>
                <option value="public">🌍 {{ $t('mod.academy.public') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.difficulty') }}</label>
              <select
                v-model="formData.difficulty"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
              >
                <option value="beginner">{{ $t('mod.academy.beginner') }}</option>
                <option value="intermediate">{{ $t('mod.academy.intermediate') }}</option>
                <option value="advanced">{{ $t('mod.academy.advanced') }}</option>
              </select>
            </div>
          </div>
        </section>

        <!-- Media -->
        <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <ImageIcon :size="20" class="text-brand-600" /> {{ $t('mod.academy.media') }}
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.cover_image_url') }}</label>
              <div class="flex gap-4">
                <input
                  v-model="formData.coverImage"
                  type="text"
                  class="flex-1 border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                />
                <div class="w-24 h-16 bg-slate-100 rounded-lg border border-slate-200 overflow-hidden">
                  <img v-if="formData.coverImage" :src="formData.coverImage" alt="Preview" class="w-full h-full object-cover" />
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Planning Tab -->
      <div v-if="activeTab === 'planning'" class="max-w-4xl mx-auto space-y-6">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.academy.course_curriculum') }}</h3>
          <button @click="addTopic" class="flex items-center gap-2 text-brand-600 font-medium hover:bg-brand-50 px-3 py-2 rounded-lg transition-colors">
            <PlusIcon :size="18" /> {{ $t('mod.academy.add_topic') }}
          </button>
        </div>

        <div v-if="formData.curriculum.length === 0" class="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl text-slate-400">
          <ListIcon :size="48" class="mx-auto mb-2 opacity-50" />
          <p>{{ $t('mod.academy.no_topics_yet') }}</p>
        </div>

        <div v-else class="space-y-4">
          <div v-for="(topic, index) in formData.curriculum" :key="topic.id" class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <!-- Topic Header -->
            <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-start gap-3 group">
              <button @click="toggleTopic(topic.id)" class="mt-1 text-slate-400 hover:text-slate-600">
                <ChevronRightIcon v-if="!openTopics.includes(topic.id)" :size="20" />
                <ChevronDownIcon v-else :size="20" />
              </button>
              <div class="flex-1">
                <input
                  v-model="topic.title"
                  type="text"
                  class="w-full font-bold text-slate-800 bg-transparent focus:bg-white focus:ring-2 focus:ring-brand-500 px-2 -ml-2 rounded"
                  :placeholder="$t('mod.academy.topic_title')"
                />
                <textarea
                  v-model="topic.summary"
                  rows="2"
                  class="w-full text-sm text-slate-600 mt-2 bg-transparent focus:bg-white focus:ring-2 focus:ring-brand-500 px-2 -ml-2 rounded"
                  :placeholder="$t('mod.academy.topic_summary')"
                />
              </div>
              <button @click="deleteTopic(topic.id)" class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-rose-600">
                <Trash2Icon :size="18" />
              </button>
            </div>

            <!-- Topic Content -->
            <div v-if="openTopics.includes(topic.id)" class="p-4 space-y-3">
              <div v-for="item in topic.items" :key="item.id" class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                <component :is="item.type === 'lesson' ? BookOpenIcon : HelpCircleIcon" :size="18" :class="item.type === 'lesson' ? 'text-blue-600' : 'text-purple-600'" />
                <span class="flex-1 font-medium text-slate-800">{{ item.title }}</span>
                <button class="text-slate-400 hover:text-brand-600">
                  <Edit2Icon :size="16" />
                </button>
              </div>

              <div class="flex gap-2">
                <button @click="addLessonToTopic(topic.id)" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 border-2 border-dashed border-slate-300 rounded-lg text-sm text-slate-600 hover:border-brand-400 hover:text-brand-600 transition-colors">
                  <BookOpenIcon :size="16" /> {{ $t('mod.academy.add_lesson') }}
                </button>
                <button @click="addQuizToTopic(topic.id)" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 border-2 border-dashed border-slate-300 rounded-lg text-sm text-slate-600 hover:border-purple-400 hover:text-purple-600 transition-colors">
                  <HelpCircleIcon :size="16" /> {{ $t('mod.academy.add_quiz') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Certificate Tab -->
      <div v-if="activeTab === 'certificate'" class="max-w-4xl mx-auto space-y-6">
        <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <AwardIcon :size="20" class="text-brand-600" /> {{ $t('mod.academy.certificate_settings') }}
          </h3>

          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <input
                v-model="formData.certificate.enabled"
                type="checkbox"
                class="w-5 h-5 text-brand-600 border-slate-300 rounded focus:ring-brand-500"
              />
              <label class="text-sm font-medium text-slate-700">{{ $t('mod.academy.enable_certificate') }}</label>
            </div>

            <div v-if="formData.certificate.enabled" class="space-y-4 pl-8">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.certificate_template') }}</label>
                <select
                  v-model="formData.certificate.templateId"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none bg-white"
                >
                  <option value="default">{{ $t('mod.academy.default_template') }}</option>
                  <option value="premium">{{ $t('mod.academy.premium_template') }}</option>
                </select>
              </div>

              <div class="flex items-center gap-3">
                <input
                  v-model="formData.certificate.showScore"
                  type="checkbox"
                  class="w-5 h-5 text-brand-600 border-slate-300 rounded focus:ring-brand-500"
                />
                <label class="text-sm font-medium text-slate-700">{{ $t('mod.academy.show_score_on_certificate') }}</label>
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.signature_text') }}</label>
                <input
                  v-model="formData.certificate.signatureText"
                  type="text"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-brand-500 outline-none"
                  :placeholder="$t('mod.academy.signature_placeholder')"
                />
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeft as ArrowLeftIcon,
  Save as SaveIcon,
  BookOpen as BookOpenIcon,
  Eye as EyeIcon,
  Image as ImageIcon,
  List as ListIcon,
  Plus as PlusIcon,
  Award as AwardIcon,
  ChevronRight as ChevronRightIcon,
  ChevronDown as ChevronDownIcon,
  Trash2 as Trash2Icon,
  Edit2 as Edit2Icon,
  HelpCircle as HelpCircleIcon,
  LayoutIcon
} from 'lucide-vue-next'

interface Course {
  id: string
  title: string
  description: string
  type: string
  author: string
  visibility: string
  category: { id: string; name: string }
  tags: string[]
  difficulty: string
  coverImage: string
  studentsCount: number
  published: boolean
  certificate: {
    enabled: boolean
    templateId: string
    showScore: boolean
    signatureText: string
  }
  curriculum: any[]
}

const props = defineProps<{
  course: Course
}>()

const emit = defineEmits<{
  back: []
  save: [course: Course]
}>()

const { t: $t } = useI18n()

// State
const activeTab = ref('definition')
const formData = reactive({ ...props.course })
const openTopics = ref<string[]>([])

// Tabs
const editorTabs = ref([
  { id: 'definition', icon: LayoutIcon, label: $t('mod.academy.course_definition') },
  { id: 'planning', icon: ListIcon, label: $t('mod.academy.course_planning') },
  { id: 'certificate', icon: AwardIcon, label: $t('mod.academy.certificate') }
])

// Methods
const handleSave = () => {
  emit('save', formData)
}

const addTopic = () => {
  const newTopic = {
    id: `t_${Date.now()}`,
    title: 'New Topic',
    summary: '',
    items: []
  }
  formData.curriculum.push(newTopic)
  openTopics.value.push(newTopic.id)
}

const deleteTopic = (id: string) => {
  if (confirm($t('mod.academy.confirm_delete_topic'))) {
    formData.curriculum = formData.curriculum.filter(t => t.id !== id)
    openTopics.value = openTopics.value.filter(tid => tid !== id)
  }
}

const toggleTopic = (id: string) => {
  const index = openTopics.value.indexOf(id)
  if (index > -1) {
    openTopics.value.splice(index, 1)
  } else {
    openTopics.value.push(id)
  }
}

const addLessonToTopic = (topicId: string) => {
  const newLesson = {
    id: `l_${Date.now()}`,
    type: 'lesson',
    title: 'New Lesson',
    content: '',
    mediaUrls: [],
    fileAttachments: []
  }

  const topic = formData.curriculum.find(t => t.id === topicId)
  if (topic) {
    topic.items.push(newLesson)
  }
}

const addQuizToTopic = (topicId: string) => {
  const newQuiz = {
    id: `q_${Date.now()}`,
    type: 'quiz',
    title: 'New Quiz',
    summary: '',
    questions: [],
    settings: { allowedAttempts: 0, passingScore: 70, shuffleQuestions: false }
  }

  const topic = formData.curriculum.find(t => t.id === topicId)
  if (topic) {
    topic.items.push(newQuiz)
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>
