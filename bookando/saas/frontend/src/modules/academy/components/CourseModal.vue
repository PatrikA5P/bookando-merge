<script setup lang="ts">
/**
 * CourseModal — Kurs erstellen/bearbeiten
 *
 * 3-Tab-Dialog:
 * - Tab 1 "Definition": Grundlegende Kursinformationen
 * - Tab 2 "Curriculum": Geordnete Liste von Lektionen/Quizzen/Aufgaben
 * - Tab 3 "Zertifikat": Zertifikats- und Badge-Konfiguration
 *
 * Footer: Abbrechen, Entwurf speichern, Veroeffentlichen
 */
import { ref, computed, watch } from 'vue';
import BModal from '@/components/ui/BModal.vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BBadge from '@/components/ui/BBadge.vue';
import { useI18n } from '@/composables/useI18n';
import { useAcademyStore } from '@/stores/academy';
import type { Course, CurriculumItem, CurriculumItemType } from '@/stores/academy';
import { TAB_STYLES, BUTTON_STYLES, CARD_STYLES, BADGE_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';

const { t } = useI18n();
const academyStore = useAcademyStore();

// ---- Props / Emits ----
const props = defineProps<{
  show: boolean;
  course: Course | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'save', data: Partial<Course>, publish: boolean): void;
}>();

// ---- Tab State ----
const activeTab = ref<'definition' | 'curriculum' | 'certificate'>('definition');

const modalTabs = [
  { id: 'definition' as const, label: 'Definition' },
  { id: 'curriculum' as const, label: 'Curriculum' },
  { id: 'certificate' as const, label: 'Zertifikat' },
];

// ---- Form State ----
const form = ref({
  title: '',
  description: '',
  type: 'ONLINE' as string,
  visibility: 'INTERNAL' as string,
  difficulty: 'BEGINNER' as string,
  categoryId: '',
  certificateEnabled: false,
  badgeId: '' as string,
  certificateTitle: '',
  certificateDescription: '',
});

const curriculum = ref<CurriculumItem[]>([]);

// ---- Watch: Populate form on edit ----
watch(() => props.show, (isOpen) => {
  if (isOpen) {
    activeTab.value = 'definition';
    if (props.course) {
      form.value = {
        title: props.course.title,
        description: props.course.description,
        type: props.course.type,
        visibility: props.course.visibility,
        difficulty: props.course.difficulty,
        categoryId: props.course.categoryId,
        certificateEnabled: props.course.certificateEnabled,
        badgeId: props.course.badgeId || '',
        certificateTitle: props.course.title,
        certificateDescription: '',
      };
      curriculum.value = [...props.course.curriculum];
    } else {
      form.value = {
        title: '',
        description: '',
        type: 'ONLINE',
        visibility: 'INTERNAL',
        difficulty: 'BEGINNER',
        categoryId: '',
        certificateEnabled: false,
        badgeId: '',
        certificateTitle: '',
        certificateDescription: '',
      };
      curriculum.value = [];
    }
  }
});

// ---- Options ----
const typeOptions = [
  { value: 'ONLINE', label: 'Online' },
  { value: 'IN_PERSON', label: 'Vor Ort' },
  { value: 'BLENDED', label: 'Blended' },
];

const visibilityOptions = [
  { value: 'PRIVATE', label: 'Privat' },
  { value: 'INTERNAL', label: 'Intern' },
  { value: 'PUBLIC', label: 'Oeffentlich' },
];

const difficultyOptions = [
  { value: 'BEGINNER', label: 'Einsteiger' },
  { value: 'INTERMEDIATE', label: 'Fortgeschritten' },
  { value: 'ADVANCED', label: 'Experte' },
];

const categoryOptions = computed(() =>
  academyStore.categories.map(c => ({ value: c.value, label: c.label }))
);

const badgeOptions = computed(() => [
  { value: '', label: 'Kein Badge' },
  ...academyStore.badges.map(b => ({ value: b.id, label: b.name })),
]);

// ---- Curriculum ----
const totalDuration = computed(() => {
  const total = curriculum.value.reduce((sum, item) => sum + item.duration, 0);
  const hours = Math.floor(total / 60);
  const minutes = total % 60;
  if (hours > 0 && minutes > 0) return `${hours}h ${minutes}min`;
  if (hours > 0) return `${hours}h`;
  return `${minutes}min`;
});

const newItemType = ref<CurriculumItemType>('LESSON');
const newItemTitle = ref('');
const newItemDuration = ref(30);

function addCurriculumItem() {
  if (!newItemTitle.value.trim()) return;

  const item: CurriculumItem = {
    id: `ci-${Date.now()}`,
    type: newItemType.value,
    title: newItemTitle.value.trim(),
    duration: newItemDuration.value,
    order: curriculum.value.length + 1,
  };

  curriculum.value.push(item);
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

  // Reorder
  curriculum.value = curriculum.value.map((item, idx) => ({
    ...item,
    order: idx + 1,
  }));
}

function getItemTypeLabel(type: CurriculumItemType): string {
  switch (type) {
    case 'LESSON': return 'Lektion';
    case 'QUIZ': return 'Quiz';
    case 'ASSIGNMENT': return 'Aufgabe';
    default: return type;
  }
}

function getItemTypeVariant(type: CurriculumItemType): 'info' | 'purple' | 'warning' {
  switch (type) {
    case 'LESSON': return 'info';
    case 'QUIZ': return 'purple';
    case 'ASSIGNMENT': return 'warning';
    default: return 'info';
  }
}

const curriculumItemTypes = [
  { value: 'LESSON' as const, label: 'Lektion' },
  { value: 'QUIZ' as const, label: 'Quiz' },
  { value: 'ASSIGNMENT' as const, label: 'Aufgabe' },
];

// ---- Actions ----
function handleClose() {
  emit('close');
}

function handleSaveDraft() {
  emit('save', buildCourseData(), false);
}

function handlePublish() {
  emit('save', buildCourseData(), true);
}

function buildCourseData(): Partial<Course> {
  return {
    title: form.value.title,
    description: form.value.description,
    type: form.value.type as Course['type'],
    visibility: form.value.visibility as Course['visibility'],
    difficulty: form.value.difficulty as Course['difficulty'],
    categoryId: form.value.categoryId,
    certificateEnabled: form.value.certificateEnabled,
    badgeId: form.value.badgeId || undefined,
    curriculum: curriculum.value,
  };
}

const isValid = computed(() => {
  return form.value.title.trim().length > 0;
});
</script>

<template>
  <BModal
    :model-value="show"
    :title="course ? 'Kurs bearbeiten' : 'Neuer Kurs'"
    size="xl"
    @update:model-value="!$event && handleClose()"
    @close="handleClose"
  >
    <!-- Tab Navigation -->
    <div class="border-b border-slate-200 -mx-6 -mt-2 mb-6 px-6">
      <nav :class="TAB_STYLES.container" role="tablist">
        <button
          v-for="tab in modalTabs"
          :key="tab.id"
          role="tab"
          :aria-selected="activeTab === tab.id"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition-all duration-200 whitespace-nowrap',
            activeTab === tab.id
              ? 'text-rose-700 border-rose-500'
              : 'text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300',
          ]"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- Tab 1: Definition -->
    <div v-if="activeTab === 'definition'" class="space-y-5">
      <BInput
        v-model="form.title"
        label="Kurstitel"
        placeholder="z.B. Grundlagen der Haarpflege"
        required
      />

      <BTextarea
        v-model="form.description"
        label="Beschreibung"
        placeholder="Beschreiben Sie den Kursinhalt und die Lernziele..."
        :rows="4"
      />

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <BSelect
          v-model="form.type"
          :options="typeOptions"
          label="Kurstyp"
          required
        />

        <BSelect
          v-model="form.visibility"
          :options="visibilityOptions"
          label="Sichtbarkeit"
          required
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <BSelect
          v-model="form.difficulty"
          :options="difficultyOptions"
          label="Schwierigkeitsgrad"
          required
        />

        <BSelect
          v-model="form.categoryId"
          :options="categoryOptions"
          label="Kategorie"
          placeholder="Kategorie waehlen"
        />
      </div>
    </div>

    <!-- Tab 2: Curriculum -->
    <div v-else-if="activeTab === 'curriculum'" class="space-y-5">
      <!-- Duration Summary -->
      <div class="flex items-center justify-between p-3 bg-rose-50 rounded-lg">
        <span class="text-sm font-medium text-rose-700">
          {{ curriculum.length }} Elemente im Curriculum
        </span>
        <span class="text-sm text-rose-600">
          Gesamtdauer: {{ totalDuration }}
        </span>
      </div>

      <!-- Curriculum List -->
      <div v-if="curriculum.length > 0" class="space-y-2">
        <div
          v-for="(item, index) in curriculum"
          :key="item.id"
          class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-lg group hover:border-rose-200 transition-colors"
        >
          <!-- Drag Handle (visual) -->
          <div class="text-slate-300 cursor-grab shrink-0">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 6a2 2 0 112 0 2 2 0 01-2 0zm0 6a2 2 0 112 0 2 2 0 01-2 0zm0 6a2 2 0 112 0 2 2 0 01-2 0zm6-12a2 2 0 112 0 2 2 0 01-2 0zm0 6a2 2 0 112 0 2 2 0 01-2 0zm0 6a2 2 0 112 0 2 2 0 01-2 0z" />
            </svg>
          </div>

          <!-- Order Number -->
          <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs font-medium flex items-center justify-center shrink-0">
            {{ item.order }}
          </span>

          <!-- Type Badge -->
          <BBadge :variant="getItemTypeVariant(item.type)" class="shrink-0">
            {{ getItemTypeLabel(item.type) }}
          </BBadge>

          <!-- Title -->
          <span class="flex-1 text-sm text-slate-700 truncate">
            {{ item.title }}
          </span>

          <!-- Duration -->
          <span class="text-xs text-slate-400 shrink-0">
            {{ item.duration }}min
          </span>

          <!-- Move Buttons -->
          <div class="flex gap-0.5 shrink-0">
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              :disabled="index === 0"
              title="Nach oben"
              @click="moveCurriculumItem(index, 'up')"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
              </svg>
            </button>
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              :disabled="index === curriculum.length - 1"
              title="Nach unten"
              @click="moveCurriculumItem(index, 'down')"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
          </div>

          <!-- Remove -->
          <button
            :class="BUTTON_STYLES.icon"
            class="!p-1 text-red-400 hover:text-red-600"
            title="Entfernen"
            @click="removeCurriculumItem(item.id)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Empty Curriculum -->
      <div v-else class="py-8 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <p class="text-sm text-slate-500">Noch keine Elemente im Curriculum</p>
        <p class="text-xs text-slate-400 mt-1">Fuegen Sie Lektionen, Quizze oder Aufgaben hinzu</p>
      </div>

      <!-- Add Item Form -->
      <div class="border-t border-slate-200 pt-4">
        <p :class="LABEL_STYLES.base" class="!mb-3">Element hinzufuegen</p>
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="sm:w-32">
            <select
              v-model="newItemType"
              :class="INPUT_STYLES.select"
            >
              <option
                v-for="itemType in curriculumItemTypes"
                :key="itemType.value"
                :value="itemType.value"
              >
                {{ itemType.label }}
              </option>
            </select>
          </div>
          <div class="flex-1">
            <input
              v-model="newItemTitle"
              :class="INPUT_STYLES.base"
              placeholder="Titel des Elements..."
              @keydown.enter="addCurriculumItem"
            />
          </div>
          <div class="sm:w-24">
            <input
              v-model.number="newItemDuration"
              type="number"
              min="1"
              :class="INPUT_STYLES.base"
              placeholder="Min."
            />
          </div>
          <BButton
            variant="secondary"
            :disabled="!newItemTitle.trim()"
            @click="addCurriculumItem"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Hinzufuegen
          </BButton>
        </div>
      </div>
    </div>

    <!-- Tab 3: Certificate -->
    <div v-else-if="activeTab === 'certificate'" class="space-y-5">
      <BToggle
        v-model="form.certificateEnabled"
        label="Zertifikat aktivieren"
        description="Teilnehmer erhalten nach Abschluss ein Zertifikat"
      />

      <template v-if="form.certificateEnabled">
        <BInput
          v-model="form.certificateTitle"
          label="Zertifikatstitel"
          placeholder="z.B. Zertifikat Haarpflege Grundlagen"
        />

        <BSelect
          v-model="form.badgeId"
          :options="badgeOptions"
          label="Badge zuweisen"
          hint="Optional: Teilnehmer erhalten dieses Badge bei Abschluss"
        />

        <BTextarea
          v-model="form.certificateDescription"
          label="Zertifikatsbeschreibung"
          placeholder="Optionale Beschreibung fuer das Zertifikat..."
          :rows="3"
        />

        <!-- Badge Preview -->
        <div v-if="form.badgeId" class="p-4 bg-slate-50 rounded-lg">
          <p class="text-xs font-medium text-slate-500 mb-2">Badge-Vorschau</p>
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-full flex items-center justify-center"
              :style="{ backgroundColor: academyStore.getBadgeById(form.badgeId)?.color + '20' }"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                :style="{ color: academyStore.getBadgeById(form.badgeId)?.color }"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-900">
                {{ academyStore.getBadgeById(form.badgeId)?.name }}
              </p>
              <p class="text-xs text-slate-500">
                {{ academyStore.getBadgeById(form.badgeId)?.description }}
              </p>
            </div>
          </div>
        </div>
      </template>

      <div v-else class="py-8 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
        </svg>
        <p class="text-sm text-slate-500">Zertifizierung ist deaktiviert</p>
        <p class="text-xs text-slate-400 mt-1">Aktivieren Sie die Option oben, um Zertifikate zu konfigurieren</p>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <BButton variant="secondary" @click="handleClose">
        Abbrechen
      </BButton>
      <BButton variant="ghost" :disabled="!isValid" @click="handleSaveDraft">
        Entwurf speichern
      </BButton>
      <BButton variant="primary" :disabled="!isValid" @click="handlePublish">
        Veroeffentlichen
      </BButton>
    </template>
  </BModal>
</template>
