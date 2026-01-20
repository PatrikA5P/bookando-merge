<template>
  <div class="flex flex-col h-full bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden animate-fadeIn">
    <!-- Header -->
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
            :placeholder="$t('mod.academy.education_plan_title')"
          />
          <input
            v-model="formData.description"
            type="text"
            class="block text-xs text-slate-500 bg-transparent border-none p-0 px-1 w-96 focus:ring-0"
            :placeholder="$t('mod.academy.add_description')"
          />
        </div>
      </div>
      <button @click="handleSave" class="flex items-center gap-2 bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors font-medium">
        <SaveIcon :size="18" /> {{ $t('mod.academy.save_template') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-slate-200 px-6">
      <button
        @click="activeTab = 'structure'"
        :class="['px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2', activeTab === 'structure' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700']"
      >
        <ListIcon :size="16" /> {{ $t('mod.academy.structure_content') }}
      </button>
      <button
        @click="activeTab = 'config'"
        :class="['px-4 py-3 text-sm font-medium border-b-2 transition-colors flex items-center gap-2', activeTab === 'config' ? 'border-brand-600 text-brand-600' : 'border-transparent text-slate-500 hover:text-slate-700']"
      >
        <SettingsIcon :size="16" /> {{ $t('mod.academy.grading_automation') }}
      </button>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto bg-slate-50/50 p-6">
      <!-- Structure Tab -->
      <div v-if="activeTab === 'structure'" class="max-w-4xl mx-auto space-y-6">
        <div v-for="chapter in formData.chapters" :key="chapter.id" class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
          <div class="p-3 bg-slate-50 border-b border-slate-100 flex items-center gap-2 group">
            <GripVerticalIcon :size="16" class="text-slate-300 cursor-move" />
            <input
              v-model="chapter.title"
              class="font-bold text-slate-800 bg-transparent border-none focus:ring-0 p-0 flex-1"
              :placeholder="$t('mod.academy.chapter_title')"
            />
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button
                @click="openImportModal(chapter.id)"
                class="text-xs bg-white border border-slate-200 text-slate-600 px-2 py-1 rounded hover:text-brand-600"
              >
                {{ $t('mod.academy.import_lesson') }}
              </button>
              <button @click="deleteChapter(chapter.id)" class="text-slate-400 hover:text-rose-600 p-1">
                <Trash2Icon :size="16" />
              </button>
            </div>
          </div>
          <div class="p-3 space-y-2">
            <div
              v-for="item in chapter.items"
              :key="item.id"
              class="flex items-center gap-3 p-3 border border-slate-100 rounded-lg hover:bg-slate-50 transition-colors group"
            >
              <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                <BookOpenIcon :size="16" />
              </div>
              <div class="flex-1">
                <div class="font-medium text-sm text-slate-700">{{ item.title }}</div>
                <div v-if="item.media.length > 0" class="flex gap-2 mt-1">
                  <span
                    v-for="(m, i) in item.media"
                    :key="i"
                    class="text-[10px] bg-slate-100 text-slate-500 px-1.5 rounded border border-slate-200 flex items-center gap-1"
                  >
                    <VideoIcon v-if="m.type === 'video'" :size="10" />
                    <ImageIcon v-else :size="10" />
                    {{ m.label }}
                  </span>
                </div>
              </div>
              <button
                @click="openEditItemModal(item.id)"
                class="p-2 text-slate-400 hover:text-brand-600 hover:bg-white rounded-lg transition-colors"
                :title="$t('mod.academy.edit_details')"
              >
                <Edit2Icon :size="16" />
              </button>
              <button
                @click="deleteItem(chapter.id, item.id)"
                class="p-2 text-slate-400 hover:text-rose-600 hover:bg-white rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <Trash2Icon :size="16" />
              </button>
            </div>
            <button
              @click="addLessonToChapter(chapter.id)"
              class="w-full py-2 border-2 border-dashed border-slate-200 rounded-lg text-slate-400 text-xs font-bold hover:border-brand-300 hover:text-brand-600 flex justify-center items-center gap-1 transition-colors"
            >
              <PlusIcon :size="14" /> {{ $t('mod.academy.add_lesson') }}
            </button>
          </div>
        </div>

        <button @click="addChapter" class="flex items-center gap-2 text-brand-600 font-medium hover:bg-brand-50 px-4 py-2 rounded-lg transition-colors mx-auto">
          <PlusIcon :size="18" /> {{ $t('mod.academy.add_new_chapter') }}
        </button>
      </div>

      <!-- Config Tab -->
      <div v-if="activeTab === 'config'" class="max-w-3xl mx-auto space-y-8">
        <!-- Grading Config -->
        <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
          <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <StarIcon :size="20" class="text-brand-600" /> {{ $t('mod.academy.grading_system') }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.visual_style') }}</label>
              <select
                v-model="formData.grading.type"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white"
              >
                <option value="buttons">{{ $t('mod.academy.buttons_numbers') }}</option>
                <option value="slider">{{ $t('mod.academy.slider') }}</option>
                <option value="stars">{{ $t('mod.academy.stars') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.min_value') }}</label>
              <input v-model.number="formData.grading.min" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.max_value') }}</label>
              <input v-model.number="formData.grading.max" type="number" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
            </div>
          </div>
          <div class="mb-2 text-sm font-medium text-slate-500">{{ $t('mod.academy.preview') }}</div>
          <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-center gap-2">
            <!-- Buttons Preview -->
            <div v-if="formData.grading.type === 'buttons'" class="flex gap-2">
              <div
                v-for="i in gradingElements"
                :key="i"
                class="w-10 h-10 rounded-lg border border-slate-300 bg-white flex items-center justify-center font-bold text-slate-700 shadow-sm"
              >
                {{ i }}
              </div>
            </div>
            <!-- Stars Preview -->
            <div v-else-if="formData.grading.type === 'stars'" class="flex gap-1">
              <StarIcon
                v-for="i in gradingElements"
                :key="i"
                :size="24"
                class="text-slate-300 fill-current"
              />
            </div>
            <!-- Slider Preview -->
            <div v-else class="w-full max-w-md">
              <input type="range" :min="formData.grading.min" :max="formData.grading.max" class="w-full" disabled />
              <div class="flex justify-between text-xs text-slate-500 mt-1">
                <span>{{ formData.grading.min }} ({{ formData.grading.labels?.min || $t('mod.academy.min') }})</span>
                <span>{{ formData.grading.max }} ({{ formData.grading.labels?.max || $t('mod.academy.max') }})</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Automation Rules -->
        <section class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
              <ZapIcon :size="20" class="text-amber-500" /> {{ $t('mod.academy.automation_rules') }}
            </h3>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="formData.automation.enabled" type="checkbox" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
              <span class="ml-3 text-sm font-medium text-slate-700">{{ $t('mod.academy.enable_auto_assign') }}</span>
            </label>
          </div>

          <div v-if="formData.automation.enabled" class="space-y-4 p-4 bg-amber-50 rounded-lg border border-amber-100 animate-fadeIn">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-amber-800 uppercase mb-1">{{ $t('mod.academy.trigger_type') }}</label>
                <select
                  v-model="formData.automation.triggerType"
                  class="w-full border border-amber-200 rounded-lg px-3 py-2 bg-white focus:ring-amber-500"
                >
                  <option value="Service">{{ $t('mod.academy.booked_service') }}</option>
                  <option value="Category">{{ $t('mod.academy.booked_category') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-amber-800 uppercase mb-1">
                  {{ formData.automation.triggerType === 'Service' ? $t('mod.academy.specific_service') : $t('mod.academy.category_name') }}
                </label>
                <select
                  v-model="formData.automation.triggerId"
                  class="w-full border border-amber-200 rounded-lg px-3 py-2 bg-white focus:ring-amber-500"
                >
                  <option value="">{{ $t('mod.academy.select') }}</option>
                  <option v-for="opt in triggerOptions" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
                </select>
              </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer pt-2">
              <input
                v-model="formData.automation.allowMultiple"
                type="checkbox"
                class="rounded text-amber-600 focus:ring-amber-500"
              />
              <span class="text-sm text-amber-900">{{ $t('mod.academy.allow_multiple_cards') }}</span>
            </label>
          </div>
        </section>
      </div>
    </div>

    <!-- Import Lesson Modal -->
    <div v-if="importModalOpen" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="font-bold text-lg mb-4">{{ $t('mod.academy.import_lesson') }}</h3>
        <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-lg mb-4">
          <button
            v-for="lesson in mockLessons"
            :key="lesson.id"
            @click="importLessonToChapter(lesson)"
            class="w-full text-left p-3 hover:bg-slate-50 border-b border-slate-100 last:border-0 flex justify-between items-center"
          >
            <span class="text-sm font-medium text-slate-700">{{ lesson.title }}</span>
            <PlusIcon :size="14" class="text-slate-400" />
          </button>
        </div>
        <button @click="importModalOpen = false" class="w-full py-2 border border-slate-300 rounded-lg text-slate-600 text-sm font-medium hover:bg-slate-50">
          {{ $t('common.cancel') }}
        </button>
      </div>
    </div>

    <!-- Edit Item Modal -->
    <div v-if="editingItemId" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 animate-slideUp">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-bold text-lg">{{ $t('mod.academy.edit_lesson_details') }}</h3>
          <button @click="editingItemId = null"><XIcon :size="20" class="text-slate-400" /></button>
        </div>

        <div v-if="editingItem" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.lesson_title') }}</label>
            <input
              v-model="editingItem.title"
              class="w-full border border-slate-300 rounded-lg px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.description_notes') }}</label>
            <textarea
              v-model="editingItem.description"
              class="w-full border border-slate-300 rounded-lg px-3 py-2 h-24 text-sm"
              :placeholder="$t('mod.academy.instructions_placeholder')"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">{{ $t('mod.academy.attached_media') }}</label>
            <div class="space-y-2 mb-3">
              <div
                v-for="(m, i) in editingItem.media"
                :key="i"
                class="flex items-center justify-between p-2 bg-slate-50 rounded border border-slate-200"
              >
                <div class="flex items-center gap-2 text-sm">
                  <VideoIcon v-if="m.type === 'video'" :size="14" class="text-purple-500" />
                  <ImageIcon v-else :size="14" class="text-blue-500" />
                  <span class="font-medium">{{ m.label }}</span>
                  <span class="text-xs text-slate-400 truncate max-w-[150px]">{{ m.url }}</span>
                </div>
                <button @click="removeMedia(i)" class="text-rose-500 hover:text-rose-700">
                  <Trash2Icon :size="14" />
                </button>
              </div>
              <p v-if="editingItem.media.length === 0" class="text-xs text-slate-400 italic">{{ $t('mod.academy.no_media_attached') }}</p>
            </div>
            <div class="flex gap-2">
              <button @click="addMedia('image')" class="flex-1 py-2 bg-white border border-slate-300 hover:bg-slate-50 rounded text-xs font-medium flex items-center justify-center gap-1">
                <ImageIcon :size="14" /> {{ $t('mod.academy.add_image') }}
              </button>
              <button @click="addMedia('video')" class="flex-1 py-2 bg-white border border-slate-300 hover:bg-slate-50 rounded text-xs font-medium flex items-center justify-center gap-1">
                <VideoIcon :size="14" /> {{ $t('mod.academy.add_video') }}
              </button>
            </div>
          </div>
          <div class="pt-4 border-t border-slate-100 text-right">
            <button @click="editingItemId = null" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium">
              {{ $t('common.done') }}
            </button>
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
  ArrowLeft as ArrowLeftIcon,
  Save as SaveIcon,
  Plus as PlusIcon,
  Trash2 as Trash2Icon,
  Settings as SettingsIcon,
  X as XIcon,
  GripVertical as GripVerticalIcon,
  Star as StarIcon,
  Zap as ZapIcon,
  BookOpen as BookOpenIcon,
  List as ListIcon,
  Video as VideoIcon,
  Image as ImageIcon,
  Edit2 as Edit2Icon
} from 'lucide-vue-next'

interface Media {
  type: 'image' | 'video'
  url: string
  label: string
}

interface EducationItem {
  id: string
  title: string
  description?: string
  media: Media[]
  originalLessonId?: string
}

interface EducationChapter {
  id: string
  title: string
  items: EducationItem[]
}

interface GradingConfig {
  type: 'buttons' | 'slider' | 'stars'
  min: number
  max: number
  labels?: {
    min?: string
    max?: string
  }
}

interface AutomationConfig {
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
  automation: AutomationConfig
}

const props = defineProps<{
  template: EducationCardTemplate
}>()

const emit = defineEmits<{
  back: []
  save: [template: EducationCardTemplate]
}>()

const { t: $t } = useI18n()

const formData = reactive<EducationCardTemplate>({
  ...props.template,
  chapters: [...(props.template.chapters || [])],
  grading: { ...(props.template.grading || { type: 'buttons', min: 1, max: 5 }) },
  automation: { ...(props.template.automation || { enabled: false, triggerType: 'Service', triggerId: '', allowMultiple: false }) }
})

const activeTab = ref<'structure' | 'config'>('structure')
const importModalOpen = ref(false)
const targetChapterId = ref<string | null>(null)
const editingItemId = ref<string | null>(null)

// Mock data
const mockLessons = ref([
  { id: 'l1', title: 'Introduction to Safety', mediaUrls: [] },
  { id: 'l2', title: 'Advanced Techniques', mediaUrls: ['https://example.com/video.mp4'] },
  { id: 'l3', title: 'Best Practices', mediaUrls: [] }
])

const mockServices = ref([
  { id: 's1', name: 'Massage Therapy' },
  { id: 's2', name: 'Yoga Session' }
])

const mockCategories = ref([
  { id: 'c1', name: 'Wellness' },
  { id: 'c2', name: 'Fitness' }
])

// Computed
const gradingElements = computed(() => {
  const elements = []
  for (let i = formData.grading.min; i <= formData.grading.max; i++) {
    elements.push(i)
  }
  return elements
})

const triggerOptions = computed(() =>
  formData.automation.triggerType === 'Service' ? mockServices.value : mockCategories.value
)

const editingItem = computed(() => {
  for (const c of formData.chapters) {
    const item = c.items.find(i => i.id === editingItemId.value)
    if (item) return item
  }
  return null
})

// Methods
const addChapter = () => {
  const newChapter: EducationChapter = {
    id: `c_${Date.now()}`,
    title: $t('mod.academy.new_chapter'),
    items: []
  }
  formData.chapters.push(newChapter)
}

const deleteChapter = (id: string) => {
  if (confirm($t('mod.academy.delete_chapter_confirm'))) {
    const index = formData.chapters.findIndex(c => c.id === id)
    if (index !== -1) {
      formData.chapters.splice(index, 1)
    }
  }
}

const addLessonToChapter = (chapterId: string) => {
  const newItem: EducationItem = {
    id: `i_${Date.now()}`,
    title: $t('mod.academy.new_lesson'),
    media: []
  }
  const chapter = formData.chapters.find(c => c.id === chapterId)
  if (chapter) {
    chapter.items.push(newItem)
    editingItemId.value = newItem.id
  }
}

const openImportModal = (chapterId: string) => {
  targetChapterId.value = chapterId
  importModalOpen.value = true
}

const importLessonToChapter = (lesson: any) => {
  if (!targetChapterId.value) return

  const newItem: EducationItem = {
    id: `i_${Date.now()}`,
    title: lesson.title,
    description: $t('mod.academy.imported_from_lessons'),
    originalLessonId: lesson.id,
    media: lesson.mediaUrls?.map((url: string) => ({ type: 'image', url, label: 'Resource' })) || []
  }

  const chapter = formData.chapters.find(c => c.id === targetChapterId.value)
  if (chapter) {
    chapter.items.push(newItem)
  }

  importModalOpen.value = false
  targetChapterId.value = null
}

const deleteItem = (chapterId: string, itemId: string) => {
  const chapter = formData.chapters.find(c => c.id === chapterId)
  if (chapter) {
    const index = chapter.items.findIndex(i => i.id === itemId)
    if (index !== -1) {
      chapter.items.splice(index, 1)
    }
  }
}

const openEditItemModal = (itemId: string) => {
  editingItemId.value = itemId
}

const addMedia = (type: 'image' | 'video') => {
  if (!editingItem.value) return

  const url = prompt(`${type === 'image' ? $t('mod.academy.image') : $t('mod.academy.video')} URL:`)
  const label = prompt($t('mod.academy.label_caption'))

  if (url && label) {
    editingItem.value.media.push({ type, url, label })
  }
}

const removeMedia = (index: number) => {
  if (editingItem.value) {
    editingItem.value.media.splice(index, 1)
  }
}

const handleSave = () => {
  emit('save', formData)
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

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}

.animate-slideUp {
  animation: slideUp 0.2s ease-out;
}
</style>
