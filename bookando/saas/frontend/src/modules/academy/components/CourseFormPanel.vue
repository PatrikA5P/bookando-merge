<script setup lang="ts">
/**
 * CourseFormPanel — Gold Standard SlideIn fuer Kurse
 *
 * Ersetzt CourseModal.vue (BModal → BFormPanel).
 * 3-Tab-Layout: Definition, Curriculum (Module→Lektionen), Zertifikat
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import {
  useAcademyStore,
  COURSE_TYPE_LABELS,
  COURSE_VISIBILITY_LABELS,
  COURSE_DIFFICULTY_LABELS,
} from '@/stores/academy';
import type { AcademyCourse } from '@/stores/academy';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BConfirmDialog from '@/components/ui/BConfirmDialog.vue';

const { t } = useI18n();
const toast = useToast();
const store = useAcademyStore();

const props = defineProps<{
  modelValue: boolean;
  course?: AcademyCourse | null;
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

const isEditing = computed(() => !!props.course);
const mode = computed(() => isEditing.value ? 'edit' : 'create');
const panelTitle = computed(() => isEditing.value ? 'Kurs bearbeiten' : 'Neuer Kurs');

const tabs = ['Definition', 'Curriculum', 'Zertifikat'];

// ── Form State ───────────────────────────────────────────────────────────
const title = ref('');
const description = ref('');
const courseType = ref('ONLINE');
const visibility = ref('INTERNAL');
const difficulty = ref('BEGINNER');
const categoryId = ref('');
const certificateEnabled = ref(false);
const badgeId = ref('');

// Curriculum: Module → Lessons simplified as items
interface CurriculumItem {
  id: string;
  type: 'LESSON' | 'QUIZ' | 'ASSIGNMENT';
  title: string;
  duration: number;
  order: number;
}
const curriculum = ref<CurriculumItem[]>([]);
const newItemType = ref<'LESSON' | 'QUIZ' | 'ASSIGNMENT'>('LESSON');
const newItemTitle = ref('');
const newItemDuration = ref(30);

const errors = ref<Record<string, string>>({});

// ── Options ──────────────────────────────────────────────────────────────
const typeOptions = Object.entries(COURSE_TYPE_LABELS).map(([v, l]) => ({ value: v, label: l }));
const visibilityOptions = Object.entries(COURSE_VISIBILITY_LABELS).map(([v, l]) => ({ value: v, label: l }));
const difficultyOptions = Object.entries(COURSE_DIFFICULTY_LABELS).map(([v, l]) => ({ value: v, label: l }));
const categoryOptions = computed(() => store.categories);
const badgeOptions = computed(() => [
  { value: '', label: 'Kein Badge' },
  ...store.badges.map(b => ({ value: b.id, label: b.name })),
]);

const curriculumItemTypes = [
  { value: 'LESSON', label: 'Lektion' },
  { value: 'QUIZ', label: 'Quiz' },
  { value: 'ASSIGNMENT', label: 'Aufgabe' },
];

// ── Watch dirty ──────────────────────────────────────────────────────────
watch([title, description, courseType, visibility, difficulty, categoryId, certificateEnabled, badgeId, curriculum], () => {
  dirty.value = true;
}, { deep: true });

// ── Reset on open ────────────────────────────────────────────────────────
watch(() => [props.modelValue, props.course], () => {
  if (props.modelValue) {
    errors.value = {};
    activeTabIndex.value = 0;
    dirty.value = false;

    if (props.course) {
      title.value = props.course.title;
      description.value = props.course.description;
      courseType.value = props.course.type;
      visibility.value = props.course.visibility;
      difficulty.value = props.course.difficulty;
      categoryId.value = props.course.categoryId || '';
      certificateEnabled.value = props.course.certificateEnabled;
      badgeId.value = props.course.badgeId || '';
      // Flatten modules→lessons into curriculum items for editing
      curriculum.value = props.course.modules.flatMap((m, mi) =>
        m.lessons.map((l, li) => ({
          id: l.id,
          type: 'LESSON' as const,
          title: `${m.title}: ${l.title}`,
          duration: l.durationMinutes || 30,
          order: mi * 100 + li,
        }))
      );
    } else {
      title.value = '';
      description.value = '';
      courseType.value = 'ONLINE';
      visibility.value = 'INTERNAL';
      difficulty.value = 'BEGINNER';
      categoryId.value = '';
      certificateEnabled.value = false;
      badgeId.value = '';
      curriculum.value = [];
    }
    setTimeout(() => { dirty.value = false; }, 0);
  }
}, { immediate: true });

// ── Curriculum Actions ───────────────────────────────────────────────────
const totalDuration = computed(() => {
  const total = curriculum.value.reduce((sum, item) => sum + item.duration, 0);
  const hours = Math.floor(total / 60);
  const minutes = total % 60;
  if (hours > 0 && minutes > 0) return `${hours}h ${minutes}min`;
  if (hours > 0) return `${hours}h`;
  return `${minutes}min`;
});

function addCurriculumItem() {
  if (!newItemTitle.value.trim()) return;
  curriculum.value.push({
    id: `ci-${Date.now()}`,
    type: newItemType.value,
    title: newItemTitle.value.trim(),
    duration: newItemDuration.value,
    order: curriculum.value.length + 1,
  });
  newItemTitle.value = '';
  newItemDuration.value = 30;
}

function removeCurriculumItem(id: string) {
  curriculum.value = curriculum.value
    .filter(item => item.id !== id)
    .map((item, idx) => ({ ...item, order: idx + 1 }));
}

function moveCurriculumItem(index: number, direction: 'up' | 'down') {
  const newIndex = direction === 'up' ? index - 1 : index + 1;
  if (newIndex < 0 || newIndex >= curriculum.value.length) return;
  const temp = curriculum.value[index];
  curriculum.value[index] = curriculum.value[newIndex];
  curriculum.value[newIndex] = temp;
  curriculum.value = curriculum.value.map((item, idx) => ({ ...item, order: idx + 1 }));
}

function getItemTypeVariant(type: string): 'info' | 'purple' | 'warning' {
  return type === 'QUIZ' ? 'purple' : type === 'ASSIGNMENT' ? 'warning' : 'info';
}

function getItemTypeLabel(type: string): string {
  return type === 'QUIZ' ? 'Quiz' : type === 'ASSIGNMENT' ? 'Aufgabe' : 'Lektion';
}

// ── Validation ───────────────────────────────────────────────────────────
function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!title.value.trim()) errs.title = 'Pflichtfeld';
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

// ── Save ─────────────────────────────────────────────────────────────────
async function handleSave() {
  if (!validate()) { activeTabIndex.value = 0; return; }

  saving.value = true;
  try {
    const data: Partial<AcademyCourse> = {
      title: title.value.trim(),
      description: description.value.trim(),
      type: courseType.value as AcademyCourse['type'],
      visibility: visibility.value as AcademyCourse['visibility'],
      difficulty: difficulty.value as AcademyCourse['difficulty'],
      categoryId: categoryId.value || undefined,
      certificateEnabled: certificateEnabled.value,
      badgeId: badgeId.value || undefined,
    };

    if (isEditing.value && props.course) {
      await store.updateCourse(props.course.id, data);
    } else {
      await store.addCourse(data);
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
  if (!props.course) return;
  try {
    await store.deleteCourse(props.course.id);
    toast.success(t('common.deletedSuccessfully'));
    emit('deleted', props.course.id);
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
    <!-- Tab 0: Definition -->
    <template #tab-0>
      <BFormSection title="Kurs-Details" :columns="1">
        <BInput
          v-model="title"
          label="Kurstitel"
          placeholder="z.B. Grundlagen der Fahrtheorie"
          :error="errors.title"
          required
        />
        <BTextarea
          v-model="description"
          label="Beschreibung"
          placeholder="Beschreiben Sie den Kursinhalt und die Lernziele..."
          :rows="4"
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BSelect v-model="courseType" :options="typeOptions" label="Kurstyp" />
        <BSelect v-model="visibility" :options="visibilityOptions" label="Sichtbarkeit" />
      </BFormSection>

      <BFormSection :columns="2">
        <BSelect v-model="difficulty" :options="difficultyOptions" label="Schwierigkeitsgrad" />
        <BSelect v-model="categoryId" :options="categoryOptions" label="Kategorie" />
      </BFormSection>
    </template>

    <!-- Tab 1: Curriculum -->
    <template #tab-1>
      <div class="space-y-5">
        <!-- Duration Summary -->
        <div class="flex items-center justify-between p-3 bg-rose-50 rounded-lg">
          <span class="text-sm font-medium text-rose-700">
            {{ curriculum.length }} Elemente
          </span>
          <span class="text-sm text-rose-600">
            Gesamtdauer: {{ totalDuration }}
          </span>
        </div>

        <!-- Items List -->
        <div v-if="curriculum.length > 0" class="space-y-2">
          <div
            v-for="(item, index) in curriculum"
            :key="item.id"
            class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-lg hover:border-rose-200 transition-colors"
          >
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-medium flex items-center justify-center shrink-0">
              {{ item.order }}
            </span>
            <BBadge :variant="getItemTypeVariant(item.type)" class="shrink-0">
              {{ getItemTypeLabel(item.type) }}
            </BBadge>
            <span class="flex-1 text-sm text-slate-700 truncate">{{ item.title }}</span>
            <span class="text-xs text-slate-400 shrink-0">{{ item.duration }}min</span>
            <div class="flex gap-0.5 shrink-0">
              <button
                class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600 disabled:opacity-30"
                :disabled="index === 0"
                @click="moveCurriculumItem(index, 'up')"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
              </button>
              <button
                class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600 disabled:opacity-30"
                :disabled="index === curriculum.length - 1"
                @click="moveCurriculumItem(index, 'down')"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
              </button>
            </div>
            <button
              class="p-1 rounded hover:bg-red-50 text-red-400 hover:text-red-600"
              @click="removeCurriculumItem(item.id)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
        </div>

        <div v-else class="py-8 text-center">
          <p class="text-sm text-slate-500">Noch keine Elemente im Curriculum</p>
          <p class="text-xs text-slate-400 mt-1">Fuegen Sie Lektionen, Quizze oder Aufgaben hinzu</p>
        </div>

        <!-- Add Item -->
        <div class="border-t border-slate-200 pt-4">
          <p class="text-xs font-medium text-slate-500 mb-3">Element hinzufuegen</p>
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="sm:w-32">
              <BSelect v-model="newItemType" :options="curriculumItemTypes" />
            </div>
            <div class="flex-1">
              <BInput v-model="newItemTitle" placeholder="Titel des Elements..." @keydown.enter="addCurriculumItem" />
            </div>
            <div class="sm:w-24">
              <BInput v-model.number="newItemDuration" type="number" placeholder="Min." />
            </div>
            <BButton variant="secondary" :disabled="!newItemTitle.trim()" @click="addCurriculumItem">
              Hinzufuegen
            </BButton>
          </div>
        </div>
      </div>
    </template>

    <!-- Tab 2: Zertifikat -->
    <template #tab-2>
      <BFormSection :columns="1">
        <BToggle
          v-model="certificateEnabled"
          label="Zertifikat aktivieren"
          description="Teilnehmer erhalten nach Abschluss ein Zertifikat"
        />
      </BFormSection>

      <template v-if="certificateEnabled">
        <BFormSection title="Zertifikat-Konfiguration" :columns="1">
          <BSelect v-model="badgeId" :options="badgeOptions" label="Badge zuweisen" hint="Optional: Teilnehmer erhalten dieses Badge bei Abschluss" />
        </BFormSection>

        <div v-if="badgeId" class="p-4 bg-slate-50 rounded-lg mx-4 mb-4">
          <p class="text-xs font-medium text-slate-500 mb-2">Badge-Vorschau</p>
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-full flex items-center justify-center"
              :style="{ backgroundColor: (store.getBadgeById(badgeId)?.color || '#3b82f6') + '20' }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="{ color: store.getBadgeById(badgeId)?.color }">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-900">{{ store.getBadgeById(badgeId)?.name }}</p>
              <p class="text-xs text-slate-500">{{ store.getBadgeById(badgeId)?.description }}</p>
            </div>
          </div>
        </div>
      </template>

      <div v-else class="py-8 text-center">
        <p class="text-sm text-slate-500">Zertifizierung ist deaktiviert</p>
        <p class="text-xs text-slate-400 mt-1">Aktivieren Sie die Option oben</p>
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
