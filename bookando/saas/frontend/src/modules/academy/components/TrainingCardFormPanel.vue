<script setup lang="ts">
/**
 * TrainingCardFormPanel — Gold Standard SlideIn fuer Ausbildungskarten-Vorlagen
 *
 * Erlaubt das Erstellen/Bearbeiten von TrainingCardTemplates:
 * - Allgemeine Infos (Titel, Beschreibung, Bewertungstyp)
 * - Kapitel mit Items (Skill-Checkliste)
 * - Bewertungskonfiguration (SLIDER/BUTTONS/STARS)
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useTrainingCardsStore, GRADING_TYPE_LABELS } from '@/stores/training-cards';
import type { TrainingCardTemplate, TrainingCardChapter, GradingType, TemplateStatus } from '@/stores/training-cards';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BButton from '@/components/ui/BButton.vue';
import BConfirmDialog from '@/components/ui/BConfirmDialog.vue';

const { t } = useI18n();
const toast = useToast();
const store = useTrainingCardsStore();

const props = defineProps<{
  modelValue: boolean;
  template?: TrainingCardTemplate | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved'): void;
  (e: 'deleted', id: string): void;
}>();

const saving = ref(false);
const dirty = ref(false);
const showDeleteConfirm = ref(false);
const activeTabIndex = ref(0);

const isEditing = computed(() => !!props.template);
const mode = computed(() => isEditing.value ? 'edit' : 'create');
const panelTitle = computed(() => isEditing.value ? 'Ausbildungskarte bearbeiten' : 'Neue Ausbildungskarte');

const tabs = ['Allgemein', 'Kapitel & Items', 'Bewertung'];

// ── Form State ───────────────────────────────────────────────────────────
const title = ref('');
const description = ref('');
const status = ref<TemplateStatus>('ACTIVE');
const gradingType = ref<GradingType>('SLIDER');
const gradingMin = ref(1);
const gradingMax = ref(5);
const gradingLabelMin = ref('');
const gradingLabelMax = ref('');
const errors = ref<Record<string, string>>({});

interface LocalChapter {
  id: string;
  title: string;
  sortOrder: number;
  items: LocalItem[];
}
interface LocalItem {
  id: string;
  title: string;
  description: string;
  sortOrder: number;
}

const chapters = ref<LocalChapter[]>([]);
const newChapterTitle = ref('');
const newItemTitles = ref<Record<string, string>>({});

// ── Options ──────────────────────────────────────────────────────────────
const gradingOptions = Object.entries(GRADING_TYPE_LABELS).map(([v, l]) => ({ value: v, label: l }));
const statusOptions = [
  { value: 'ACTIVE', label: 'Aktiv' },
  { value: 'ARCHIVED', label: 'Archiviert' },
];

// ── Watch dirty ──────────────────────────────────────────────────────────
watch([title, description, status, gradingType, gradingMin, gradingMax, gradingLabelMin, gradingLabelMax, chapters], () => {
  dirty.value = true;
}, { deep: true });

// ── Reset on open ────────────────────────────────────────────────────────
watch(() => [props.modelValue, props.template], () => {
  if (props.modelValue) {
    errors.value = {};
    activeTabIndex.value = 0;
    dirty.value = false;
    newChapterTitle.value = '';
    newItemTitles.value = {};

    if (props.template) {
      title.value = props.template.title;
      description.value = props.template.description || '';
      status.value = props.template.status;
      gradingType.value = props.template.gradingType;
      gradingMin.value = props.template.gradingMin;
      gradingMax.value = props.template.gradingMax;
      gradingLabelMin.value = props.template.gradingLabelMin || '';
      gradingLabelMax.value = props.template.gradingLabelMax || '';
      chapters.value = props.template.chapters.map(ch => ({
        id: ch.id,
        title: ch.title,
        sortOrder: ch.sortOrder,
        items: ch.items.map(it => ({
          id: it.id,
          title: it.title,
          description: it.description || '',
          sortOrder: it.sortOrder,
        })),
      }));
    } else {
      title.value = '';
      description.value = '';
      status.value = 'ACTIVE';
      gradingType.value = 'SLIDER';
      gradingMin.value = 1;
      gradingMax.value = 5;
      gradingLabelMin.value = 'Anfaenger';
      gradingLabelMax.value = 'Experte';
      chapters.value = [];
    }
    setTimeout(() => { dirty.value = false; }, 0);
  }
}, { immediate: true });

// ── Chapter Actions ──────────────────────────────────────────────────────
function addChapter() {
  if (!newChapterTitle.value.trim()) return;
  chapters.value.push({
    id: `ch-${Date.now()}`,
    title: newChapterTitle.value.trim(),
    sortOrder: chapters.value.length + 1,
    items: [],
  });
  newChapterTitle.value = '';
}

function removeChapter(index: number) {
  chapters.value.splice(index, 1);
  chapters.value.forEach((ch, i) => { ch.sortOrder = i + 1; });
}

function addItem(chapterIndex: number) {
  const chapterId = chapters.value[chapterIndex].id;
  const itemTitle = (newItemTitles.value[chapterId] || '').trim();
  if (!itemTitle) return;

  chapters.value[chapterIndex].items.push({
    id: `it-${Date.now()}`,
    title: itemTitle,
    description: '',
    sortOrder: chapters.value[chapterIndex].items.length + 1,
  });
  newItemTitles.value[chapterId] = '';
}

function removeItem(chapterIndex: number, itemIndex: number) {
  chapters.value[chapterIndex].items.splice(itemIndex, 1);
  chapters.value[chapterIndex].items.forEach((it, i) => { it.sortOrder = i + 1; });
}

const totalItemCount = computed(() =>
  chapters.value.reduce((sum, ch) => sum + ch.items.length, 0)
);

// ── Validation ───────────────────────────────────────────────────────────
function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!title.value.trim()) errs.title = 'Pflichtfeld';
  if (chapters.value.length === 0) errs.chapters = 'Mindestens ein Kapitel erforderlich';
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

// ── Save ─────────────────────────────────────────────────────────────────
async function handleSave() {
  if (!validate()) {
    if (errors.value.chapters) activeTabIndex.value = 1;
    else activeTabIndex.value = 0;
    return;
  }

  saving.value = true;
  try {
    const payload: any = {
      title: title.value.trim(),
      description: description.value.trim() || undefined,
      status: status.value,
      gradingType: gradingType.value,
      gradingMin: gradingMin.value,
      gradingMax: gradingMax.value,
      gradingLabelMin: gradingLabelMin.value || undefined,
      gradingLabelMax: gradingLabelMax.value || undefined,
      chapters: chapters.value.map(ch => ({
        title: ch.title,
        sortOrder: ch.sortOrder,
        items: ch.items.map(it => ({
          title: it.title,
          description: it.description || undefined,
          sortOrder: it.sortOrder,
          media: [],
        })),
      })),
    };

    if (isEditing.value && props.template) {
      await store.updateTemplate(props.template.id, payload);
    } else {
      await store.createTemplate(payload);
    }

    toast.success(t('common.savedSuccessfully'));
    dirty.value = false;
    emit('saved');
    emit('update:modelValue', false);
  } catch {
    toast.error(t('common.errorOccurred'));
  } finally {
    saving.value = false;
  }
}

// ── Delete ───────────────────────────────────────────────────────────────
async function handleDelete() {
  if (!props.template) return;
  try {
    await store.deleteTemplate(props.template.id);
    toast.success(t('common.deletedSuccessfully'));
    emit('deleted', props.template.id);
    emit('update:modelValue', false);
  } catch {
    toast.error(t('common.errorOccurred'));
  }
  showDeleteConfirm.value = false;
}
</script>

<template>
  <BFormPanel
    :model-value="modelValue"
    :title="panelTitle"
    :mode="mode"
    size="xl"
    :tabs="tabs"
    :saving="saving"
    :dirty="dirty"
    @update:model-value="$emit('update:modelValue', $event)"
    @save="handleSave"
    @cancel="$emit('update:modelValue', false)"
    @tab-change="(idx: number) => activeTabIndex = idx"
  >
    <!-- Tab 0: Allgemein -->
    <template #tab-0>
      <BFormSection title="Vorlage" :columns="1">
        <BInput v-model="title" label="Titel" placeholder="z.B. Fahrausbildung Kat. B" :error="errors.title" required />
        <BTextarea v-model="description" label="Beschreibung" placeholder="Optionale Beschreibung..." :rows="3" />
      </BFormSection>
      <BFormSection :columns="2">
        <BSelect v-model="status" :options="statusOptions" label="Status" />
      </BFormSection>
    </template>

    <!-- Tab 1: Kapitel & Items -->
    <template #tab-1>
      <div class="space-y-4">
        <!-- Summary -->
        <div class="flex items-center justify-between p-3 bg-violet-50 rounded-lg">
          <span class="text-sm font-medium text-violet-700">{{ chapters.length }} Kapitel</span>
          <span class="text-sm text-violet-600">{{ totalItemCount }} Items</span>
        </div>

        <p v-if="errors.chapters" class="text-sm text-red-600">{{ errors.chapters }}</p>

        <!-- Chapters -->
        <div v-for="(chapter, ci) in chapters" :key="chapter.id" class="border border-slate-200 rounded-lg overflow-hidden">
          <!-- Chapter Header -->
          <div class="flex items-center justify-between p-3 bg-slate-50 border-b border-slate-200">
            <div class="flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-violet-100 text-violet-700 text-xs font-bold flex items-center justify-center">{{ ci + 1 }}</span>
              <span class="text-sm font-semibold text-slate-800">{{ chapter.title }}</span>
              <span class="text-xs text-slate-400">({{ chapter.items.length }} Items)</span>
            </div>
            <button class="p-1 rounded hover:bg-red-50 text-slate-400 hover:text-red-600" @click="removeChapter(ci)">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>
          </div>

          <!-- Items -->
          <div class="p-3 space-y-2">
            <div
              v-for="(item, ii) in chapter.items"
              :key="item.id"
              class="flex items-center gap-2 px-3 py-2 bg-white border border-slate-100 rounded-lg"
            >
              <span class="text-xs text-slate-400 w-5 text-center">{{ ii + 1 }}.</span>
              <span class="flex-1 text-sm text-slate-700">{{ item.title }}</span>
              <button class="p-0.5 rounded hover:bg-red-50 text-slate-300 hover:text-red-500" @click="removeItem(ci, ii)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <!-- Add Item -->
            <div class="flex gap-2 pt-1">
              <input
                v-model="newItemTitles[chapter.id]"
                type="text"
                class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                placeholder="Neues Item..."
                @keydown.enter="addItem(ci)"
              />
              <BButton variant="ghost" size="sm" @click="addItem(ci)">+</BButton>
            </div>
          </div>
        </div>

        <!-- Add Chapter -->
        <div class="flex gap-2">
          <BInput v-model="newChapterTitle" placeholder="Neues Kapitel..." @keydown.enter="addChapter" />
          <BButton variant="secondary" :disabled="!newChapterTitle.trim()" @click="addChapter">
            Kapitel hinzufuegen
          </BButton>
        </div>
      </div>
    </template>

    <!-- Tab 2: Bewertung -->
    <template #tab-2>
      <BFormSection title="Bewertungssystem" :columns="1">
        <BSelect v-model="gradingType" :options="gradingOptions" label="Bewertungstyp" />
      </BFormSection>
      <BFormSection :columns="2">
        <BInput v-model.number="gradingMin" type="number" label="Minimum" placeholder="1" />
        <BInput v-model.number="gradingMax" type="number" label="Maximum" placeholder="5" />
      </BFormSection>
      <BFormSection :columns="2">
        <BInput v-model="gradingLabelMin" label="Label Minimum" placeholder="z.B. Anfaenger" />
        <BInput v-model="gradingLabelMax" label="Label Maximum" placeholder="z.B. Experte" />
      </BFormSection>

      <!-- Preview -->
      <div class="p-4 bg-slate-50 rounded-lg mx-4 mt-4">
        <p class="text-xs font-medium text-slate-500 mb-3">Vorschau</p>
        <div v-if="gradingType === 'STARS'" class="flex items-center gap-1">
          <svg v-for="n in gradingMax" :key="n" :class="['w-6 h-6', n <= 3 ? 'text-amber-400' : 'text-slate-200']" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <div v-else-if="gradingType === 'BUTTONS'" class="flex items-center gap-2">
          <button
            v-for="n in (gradingMax - gradingMin + 1)"
            :key="n"
            :class="['px-3 py-1.5 rounded-lg text-sm font-medium border', n <= 3 ? 'bg-violet-100 border-violet-300 text-violet-700' : 'bg-white border-slate-200 text-slate-500']"
          >
            {{ gradingMin + n - 1 }}
          </button>
        </div>
        <div v-else class="space-y-1">
          <div class="flex items-center justify-between text-xs text-slate-500">
            <span>{{ gradingLabelMin || gradingMin }}</span>
            <span>{{ gradingLabelMax || gradingMax }}</span>
          </div>
          <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full w-3/5 bg-gradient-to-r from-violet-400 to-violet-600 rounded-full" />
          </div>
        </div>
      </div>
    </template>

    <!-- Footer: Delete -->
    <template v-if="isEditing" #footer-left>
      <BButton variant="ghost" class="text-red-600 hover:text-red-700 hover:bg-red-50" @click="showDeleteConfirm = true">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
        {{ t('common.delete') }}
      </BButton>
    </template>
  </BFormPanel>

  <BConfirmDialog
    v-model="showDeleteConfirm"
    :title="t('common.confirmDelete')"
    :message="`'${title}' wirklich loeschen?`"
    confirm-variant="danger"
    :confirm-label="t('common.delete')"
    @confirm="handleDelete"
  />
</template>
