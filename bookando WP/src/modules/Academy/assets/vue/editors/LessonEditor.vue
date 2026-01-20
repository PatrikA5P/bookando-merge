<template>
  <div class="bg-white rounded-xl border border-slate-200 shadow-lg p-0 overflow-hidden flex flex-col h-full animate-fadeIn">
    <!-- Header -->
    <div class="flex justify-between items-center p-6 border-b border-slate-100 bg-slate-50">
      <div class="flex items-center gap-4 w-full max-w-2xl">
        <button @click="$emit('cancel')" class="text-slate-400 hover:text-slate-600">
          <ArrowLeftIcon :size="24" />
        </button>
        <div class="flex-1">
          <input
            v-model="formData.title"
            class="w-full bg-transparent font-bold text-xl text-slate-800 focus:outline-none placeholder-slate-300"
            :placeholder="$t('mod.academy.lesson_title')"
          />
        </div>
      </div>
      <div class="flex items-center gap-4">
        <select
          v-model="formData.groupId"
          class="bg-white border border-slate-300 text-slate-700 text-sm rounded-lg focus:ring-brand-500 focus:border-brand-500 block p-2.5"
        >
          <option value="">{{ $t('mod.academy.select_group') }}</option>
          <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.title }}</option>
        </select>
        <button
          @click="handleSave"
          class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium flex items-center gap-2 shadow-sm"
        >
          <SaveIcon :size="18" /> {{ $t('mod.academy.save_lesson') }}
        </button>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto p-6 bg-slate-50/30">
      <div class="max-w-5xl mx-auto space-y-6">
        <!-- Rich Content Editor -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <!-- Toolbar -->
          <div class="flex items-center justify-between px-4 py-2 border-b border-slate-100 bg-slate-50/50 sticky top-0 z-10">
            <div class="flex items-center gap-1">
              <button @click="handleFormat('bold')" class="p-1.5 hover:bg-slate-200 rounded text-slate-600" :title="$t('mod.academy.bold')">
                <BoldIcon :size="16" />
              </button>
              <button @click="handleFormat('italic')" class="p-1.5 hover:bg-slate-200 rounded text-slate-600" :title="$t('mod.academy.italic')">
                <ItalicIcon :size="16" />
              </button>
              <div class="w-px h-4 bg-slate-300 mx-1"></div>
              <button @click="handleFormat('insertUnorderedList')" class="p-1.5 hover:bg-slate-200 rounded text-slate-600" :title="$t('mod.academy.list')">
                <ListIcon :size="16" />
              </button>
              <button @click="handleLink" class="p-1.5 hover:bg-slate-200 rounded text-slate-600" :title="$t('mod.academy.link')">
                <LinkIcon :size="16" />
              </button>
            </div>
            <button
              @click="toggleContentMode"
              :class="['flex items-center gap-2 px-3 py-1 rounded text-xs font-medium border transition-colors', contentMode === 'html' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50']"
            >
              <CodeIcon :size="14" /> {{ contentMode === 'visual' ? $t('mod.academy.source') : $t('mod.academy.visual') }}
            </button>
          </div>

          <div class="p-4 relative min-h-[20rem]">
            <div
              v-if="contentMode === 'visual'"
              ref="editorRef"
              class="w-full h-64 focus:outline-none resize-y leading-relaxed text-sm overflow-auto"
              contenteditable="true"
              @input="handleContentInput"
              style="min-height: 16rem"
            ></div>
            <textarea
              v-else
              v-model="formData.content"
              class="w-full h-64 focus:outline-none resize-y leading-relaxed text-sm font-mono text-slate-600 bg-slate-50 p-2 rounded border border-slate-200"
              :placeholder="$t('mod.academy.html_placeholder')"
            />
          </div>
        </div>

        <!-- Attachments Separated -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="p-4 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
              <PaperclipIcon :size="18" class="text-brand-600" /> {{ $t('mod.academy.attachments_media') }}
            </h3>
          </div>
          <div class="p-6 space-y-8">
            <AttachmentSection
              type="image"
              :items="imageAttachments"
              @add="triggerFileUpload('image')"
              @update="updateAttachment"
              @remove="removeAttachment"
            />
            <div class="h-px bg-slate-100 w-full"></div>
            <AttachmentSection
              type="video"
              :items="videoAttachments"
              @add="triggerFileUpload('video')"
              @update="updateAttachment"
              @remove="removeAttachment"
            />
            <div class="h-px bg-slate-100 w-full"></div>
            <AttachmentSection
              type="document"
              :items="documentAttachments"
              @add="triggerFileUpload('document')"
              @update="updateAttachment"
              @remove="removeAttachment"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden file inputs -->
    <input ref="imageInputRef" type="file" class="hidden" accept="image/*" @change="(e) => handleFileUpload(e, 'image')" />
    <input ref="videoInputRef" type="file" class="hidden" accept="video/*" @change="(e) => handleFileUpload(e, 'video')" />
    <input ref="docInputRef" type="file" class="hidden" accept=".pdf,.doc,.docx,.txt" @change="(e) => handleFileUpload(e, 'document')" />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeft as ArrowLeftIcon,
  Save as SaveIcon,
  Bold as BoldIcon,
  Italic as ItalicIcon,
  List as ListIcon,
  Link as LinkIcon,
  Code as CodeIcon,
  Paperclip as PaperclipIcon,
  Plus as PlusIcon,
  Trash2 as Trash2Icon,
  Image as ImageIcon,
  Video as VideoIcon,
  FileText as FileTextIcon
} from 'lucide-vue-next'
import AttachmentSection from '../components/AttachmentSection.vue'

interface StructuredAttachment {
  id: string
  type: 'image' | 'video' | 'document'
  url: string
  name: string
  description: string
}

interface ExtendedLesson {
  id?: string
  title: string
  content: string
  groupId?: string
  structuredAttachments?: StructuredAttachment[]
}

interface LessonGroup {
  id: string
  title: string
}

const props = defineProps<{
  lesson: ExtendedLesson
  groups: LessonGroup[]
}>()

const emit = defineEmits<{
  save: [lesson: ExtendedLesson]
  cancel: []
}>()

const { t: $t } = useI18n()

const formData = reactive<ExtendedLesson>({ ...props.lesson })
const contentMode = ref<'visual' | 'html'>('visual')
const editorRef = ref<HTMLDivElement>()
const imageInputRef = ref<HTMLInputElement>()
const videoInputRef = ref<HTMLInputElement>()
const docInputRef = ref<HTMLInputElement>()

// Computed
const imageAttachments = computed(() =>
  (formData.structuredAttachments || []).filter(a => a.type === 'image')
)

const videoAttachments = computed(() =>
  (formData.structuredAttachments || []).filter(a => a.type === 'video')
)

const documentAttachments = computed(() =>
  (formData.structuredAttachments || []).filter(a => a.type === 'document')
)

// Initialize editor content
onMounted(() => {
  if (editorRef.value && formData.content) {
    editorRef.value.innerHTML = formData.content
  }
})

// Methods
const handleContentInput = (e: Event) => {
  if (e.target instanceof HTMLDivElement) {
    formData.content = e.target.innerHTML
  }
}

const handleFormat = (command: string) => {
  document.execCommand(command, false, undefined)
  editorRef.value?.focus()
}

const handleLink = () => {
  const url = prompt($t('mod.academy.enter_link_url'))
  if (url) {
    document.execCommand('createLink', false, url)
    editorRef.value?.focus()
  }
}

const toggleContentMode = () => {
  if (contentMode.value === 'visual' && editorRef.value) {
    formData.content = editorRef.value.innerHTML
  }
  contentMode.value = contentMode.value === 'visual' ? 'html' : 'visual'

  // Update editor content when switching back to visual
  if (contentMode.value === 'visual') {
    setTimeout(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = formData.content
      }
    }, 0)
  }
}

const triggerFileUpload = (type: 'image' | 'video' | 'document') => {
  if (type === 'image') imageInputRef.value?.click()
  else if (type === 'video') videoInputRef.value?.click()
  else docInputRef.value?.click()
}

const handleFileUpload = (e: Event, type: 'image' | 'video' | 'document') => {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = (event) => {
    if (event.target?.result) {
      const newAtt: StructuredAttachment = {
        id: `att_${Date.now()}`,
        type,
        url: event.target.result as string,
        name: file.name,
        description: ''
      }
      if (!formData.structuredAttachments) {
        formData.structuredAttachments = []
      }
      formData.structuredAttachments.push(newAtt)
    }
  }
  reader.readAsDataURL(file)

  // Reset input
  input.value = ''
}

const updateAttachment = (id: string, changes: Partial<StructuredAttachment>) => {
  if (formData.structuredAttachments) {
    const index = formData.structuredAttachments.findIndex(a => a.id === id)
    if (index !== -1) {
      formData.structuredAttachments[index] = {
        ...formData.structuredAttachments[index],
        ...changes
      }
    }
  }
}

const removeAttachment = (id: string) => {
  if (formData.structuredAttachments) {
    formData.structuredAttachments = formData.structuredAttachments.filter(a => a.id !== id)
  }
}

const handleSave = () => {
  // Sync visual editor content before saving
  if (contentMode.value === 'visual' && editorRef.value) {
    formData.content = editorRef.value.innerHTML
  }
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

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}

[contenteditable] {
  outline: none;
}

[contenteditable]:focus {
  outline: 2px solid rgb(99 102 241 / 0.1);
  outline-offset: 2px;
  border-radius: 0.375rem;
}
</style>
