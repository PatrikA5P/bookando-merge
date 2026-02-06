<script setup lang="ts">
/**
 * LessonsTab — Lektionsbibliothek mit Gruppenfilter
 *
 * Features:
 * - BTable-Ansicht auf Desktop (Titel, Typ, Gruppe, Dauer, Medien, Aktionen)
 * - Karten-Layout auf Mobile
 * - Gruppenfilter-Dropdown
 * - Typ-Badges: TEXT=blue, VIDEO=purple, INTERACTIVE=amber
 * - Lektion erstellen/bearbeiten via Modal
 * - Gruppenverwaltung (erstellen/umbenennen)
 */
import { ref, computed, watch } from 'vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTable from '@/components/ui/BTable.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BModal from '@/components/ui/BModal.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAcademyStore } from '@/stores/academy';
import type { Lesson, LessonType, LessonGroup } from '@/stores/academy';
import { BUTTON_STYLES, BADGE_STYLES, CARD_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const academyStore = useAcademyStore();

// ---- Props ----
const showModal = defineModel<boolean>('showModal', { default: false });

// ---- State ----
const searchQuery = ref('');
const filterGroup = ref('');
const editingLesson = ref<Lesson | null>(null);
const showGroupModal = ref(false);
const editingGroup = ref<LessonGroup | null>(null);
const groupName = ref('');

// ---- Lesson Form ----
const lessonForm = ref({
  title: '',
  type: 'TEXT' as string,
  groupId: '' as string,
  newGroupName: '',
  content: '',
  duration: 30,
});

// ---- Filter Options ----
const groupOptions = computed(() => [
  { value: '', label: 'Alle Gruppen' },
  { value: '__ungrouped', label: 'Ohne Gruppe' },
  ...academyStore.lessonGroups.map(g => ({ value: g.id, label: g.name })),
]);

const groupSelectOptions = computed(() => [
  { value: '', label: 'Ohne Gruppe' },
  { value: '__new', label: '+ Neue Gruppe' },
  ...academyStore.lessonGroups.map(g => ({ value: g.id, label: g.name })),
]);

const typeOptions = [
  { value: 'TEXT', label: 'Text' },
  { value: 'VIDEO', label: 'Video' },
  { value: 'INTERACTIVE', label: 'Interaktiv' },
];

// ---- Computed ----
const filteredLessons = computed(() => {
  let result = [...academyStore.lessons];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(l =>
      l.title.toLowerCase().includes(q) ||
      l.content.toLowerCase().includes(q)
    );
  }

  if (filterGroup.value === '__ungrouped') {
    result = result.filter(l => !l.groupId);
  } else if (filterGroup.value) {
    result = result.filter(l => l.groupId === filterGroup.value);
  }

  return result;
});

// ---- Table Columns ----
const columns = [
  { key: 'title', label: 'Titel', sortable: true },
  { key: 'type', label: 'Typ' },
  { key: 'groupName', label: 'Gruppe', sortable: true },
  { key: 'duration', label: 'Dauer', align: 'right' as const },
  { key: 'media', label: 'Medien' },
  { key: 'actions', label: '', width: '80px', align: 'right' as const },
];

// ---- Helpers ----
function getTypeLabel(type: LessonType): string {
  switch (type) {
    case 'TEXT': return 'Text';
    case 'VIDEO': return 'Video';
    case 'INTERACTIVE': return 'Interaktiv';
    default: return type;
  }
}

function getTypeBadgeVariant(type: LessonType): 'info' | 'purple' | 'warning' {
  switch (type) {
    case 'TEXT': return 'info';
    case 'VIDEO': return 'purple';
    case 'INTERACTIVE': return 'warning';
    default: return 'info';
  }
}

function formatDuration(minutes: number): string {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  if (h > 0 && m > 0) return `${h}h ${m}min`;
  if (h > 0) return `${h}h`;
  return `${m}min`;
}

// ---- Lesson Modal Actions ----
function openCreate() {
  editingLesson.value = null;
  lessonForm.value = {
    title: '',
    type: 'TEXT',
    groupId: '',
    newGroupName: '',
    content: '',
    duration: 30,
  };
  showModal.value = true;
}

function openEdit(lesson: Lesson) {
  editingLesson.value = lesson;
  lessonForm.value = {
    title: lesson.title,
    type: lesson.type,
    groupId: lesson.groupId || '',
    newGroupName: '',
    content: lesson.content,
    duration: lesson.duration,
  };
  showModal.value = true;
}

function handleRowClick(row: Record<string, unknown>) {
  const lesson = academyStore.lessons.find(l => l.id === row.id);
  if (lesson) openEdit(lesson);
}

function handleSaveLesson() {
  let groupId = lessonForm.value.groupId;
  let groupName: string | undefined;

  // Create new group if selected
  if (groupId === '__new' && lessonForm.value.newGroupName.trim()) {
    const newGroup = academyStore.addLessonGroup(lessonForm.value.newGroupName.trim());
    groupId = newGroup.id;
    groupName = newGroup.name;
    toast.success(`Gruppe "${newGroup.name}" erstellt`);
  } else if (groupId && groupId !== '__new') {
    const group = academyStore.lessonGroups.find(g => g.id === groupId);
    groupName = group?.name;
  } else {
    groupId = '';
    groupName = undefined;
  }

  if (editingLesson.value) {
    academyStore.updateLesson(editingLesson.value.id, {
      title: lessonForm.value.title,
      type: lessonForm.value.type as LessonType,
      groupId: groupId || undefined,
      groupName,
      content: lessonForm.value.content,
      duration: lessonForm.value.duration,
    });
    toast.success('Lektion aktualisiert');
  } else {
    academyStore.addLesson({
      title: lessonForm.value.title,
      type: lessonForm.value.type as LessonType,
      groupId: groupId || undefined,
      groupName,
      content: lessonForm.value.content,
      duration: lessonForm.value.duration,
      mediaUrls: [],
    });
    toast.success('Lektion erstellt');
  }

  showModal.value = false;
  editingLesson.value = null;
}

function handleDeleteLesson(lesson: Lesson) {
  academyStore.deleteLesson(lesson.id);
  toast.success('Lektion geloescht');
}

// ---- Group Management ----
function openGroupManagement() {
  editingGroup.value = null;
  groupName.value = '';
  showGroupModal.value = true;
}

function handleCreateGroup() {
  if (!groupName.value.trim()) return;
  academyStore.addLessonGroup(groupName.value.trim());
  toast.success('Gruppe erstellt');
  groupName.value = '';
}

function handleRenameGroup(group: LessonGroup) {
  editingGroup.value = group;
  groupName.value = group.name;
}

function handleSaveGroupRename() {
  if (!editingGroup.value || !groupName.value.trim()) return;
  academyStore.renameLessonGroup(editingGroup.value.id, groupName.value.trim());
  toast.success('Gruppe umbenannt');
  editingGroup.value = null;
  groupName.value = '';
}

function cancelGroupRename() {
  editingGroup.value = null;
  groupName.value = '';
}

const isLessonValid = computed(() => {
  return lessonForm.value.title.trim().length > 0 && lessonForm.value.duration > 0;
});

// Watch to reset modal state
watch(showModal, (val) => {
  if (!val) {
    editingLesson.value = null;
  }
});
</script>

<template>
  <!-- Search & Filters -->
  <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-6">
    <div class="flex-1">
      <BSearchBar
        v-model="searchQuery"
        placeholder="Lektionen suchen..."
      />
    </div>
    <div class="sm:w-48">
      <BSelect
        v-model="filterGroup"
        :options="groupOptions"
      />
    </div>
    <BButton
      variant="secondary"
      @click="openGroupManagement"
    >
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
      </svg>
      <span class="hidden sm:inline">Gruppen</span>
    </BButton>
    <BButton
      variant="primary"
      class="hidden md:flex"
      @click="openCreate"
    >
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      Neue Lektion
    </BButton>
  </div>

  <!-- Empty State -->
  <BEmptyState
    v-if="filteredLessons.length === 0 && !searchQuery && !filterGroup"
    title="Noch keine Lektionen vorhanden"
    description="Erstellen Sie Ihre erste Lektion fuer die Academy."
    icon="folder"
    action-label="Erste Lektion erstellen"
    @action="openCreate"
  />

  <BEmptyState
    v-else-if="filteredLessons.length === 0"
    title="Keine Lektionen gefunden"
    description="Passen Sie Ihre Suchkriterien oder den Gruppenfilter an."
    icon="search"
  />

  <!-- Mobile: Card Layout -->
  <div v-else-if="isMobile" class="space-y-3">
    <div
      v-for="lesson in filteredLessons"
      :key="lesson.id"
      :class="CARD_STYLES.listItem"
      class="cursor-pointer"
      @click="openEdit(lesson)"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <BBadge :variant="getTypeBadgeVariant(lesson.type)">
              {{ getTypeLabel(lesson.type) }}
            </BBadge>
            <span v-if="lesson.groupName" class="text-xs text-slate-400">
              {{ lesson.groupName }}
            </span>
          </div>
          <h4 class="text-sm font-medium text-slate-900 truncate">{{ lesson.title }}</h4>
          <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ lesson.content }}</p>
        </div>
        <div class="text-right shrink-0">
          <span class="text-xs text-slate-500">{{ formatDuration(lesson.duration) }}</span>
          <div v-if="lesson.mediaUrls.length > 0" class="mt-1">
            <svg class="w-4 h-4 text-slate-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
          </div>
        </div>
      </div>
      <div class="flex items-center justify-end gap-1 mt-2 pt-2 border-t border-slate-100">
        <button
          :class="BUTTON_STYLES.icon"
          class="!p-1.5"
          title="Bearbeiten"
          @click.stop="openEdit(lesson)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>
        <button
          :class="BUTTON_STYLES.icon"
          class="!p-1.5 text-red-400 hover:text-red-600"
          title="Loeschen"
          @click.stop="handleDeleteLesson(lesson)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Desktop: Table -->
  <BTable
    v-else
    :columns="columns"
    :data="filteredLessons as unknown as Record<string, unknown>[]"
    :total="filteredLessons.length"
    empty-title="Keine Lektionen gefunden"
    empty-message="Erstellen Sie Ihre erste Lektion oder passen Sie die Filter an."
    @row-click="handleRowClick"
  >
    <template #cell-title="{ row }">
      <span class="text-sm font-medium text-slate-900">
        {{ (row as unknown as Lesson).title }}
      </span>
    </template>

    <template #cell-type="{ row }">
      <BBadge :variant="getTypeBadgeVariant((row as unknown as Lesson).type)">
        {{ getTypeLabel((row as unknown as Lesson).type) }}
      </BBadge>
    </template>

    <template #cell-groupName="{ row }">
      <span v-if="(row as unknown as Lesson).groupName" class="text-sm text-slate-600">
        {{ (row as unknown as Lesson).groupName }}
      </span>
      <span v-else class="text-sm text-slate-400">—</span>
    </template>

    <template #cell-duration="{ row }">
      <span class="text-sm text-slate-600">
        {{ formatDuration((row as unknown as Lesson).duration) }}
      </span>
    </template>

    <template #cell-media="{ row }">
      <div v-if="(row as unknown as Lesson).mediaUrls.length > 0" class="flex items-center gap-1">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
        </svg>
        <span class="text-xs text-slate-500">{{ (row as unknown as Lesson).mediaUrls.length }}</span>
      </div>
      <span v-else class="text-sm text-slate-400">—</span>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex items-center justify-end gap-1">
        <button
          :class="BUTTON_STYLES.icon"
          title="Bearbeiten"
          @click.stop="openEdit(row as unknown as Lesson)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>
        <button
          :class="BUTTON_STYLES.icon"
          class="text-red-400 hover:text-red-600"
          title="Loeschen"
          @click.stop="handleDeleteLesson(row as unknown as Lesson)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </template>

    <template #empty-action>
      <BButton variant="primary" class="mt-2" @click="openCreate">
        Erste Lektion erstellen
      </BButton>
    </template>
  </BTable>

  <!-- Lesson Create/Edit Modal -->
  <BModal
    :model-value="showModal"
    :title="editingLesson ? 'Lektion bearbeiten' : 'Neue Lektion'"
    size="lg"
    @update:model-value="showModal = $event"
    @close="showModal = false"
  >
    <div class="space-y-5">
      <BInput
        v-model="lessonForm.title"
        label="Titel"
        placeholder="z.B. Schnitttechniken: Graduierung"
        required
      />

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <BSelect
          v-model="lessonForm.type"
          :options="typeOptions"
          label="Typ"
          required
        />

        <BSelect
          v-model="lessonForm.groupId"
          :options="groupSelectOptions"
          label="Gruppe"
        />
      </div>

      <!-- New Group Name -->
      <BInput
        v-if="lessonForm.groupId === '__new'"
        v-model="lessonForm.newGroupName"
        label="Name der neuen Gruppe"
        placeholder="z.B. Fortgeschrittene Techniken"
        required
      />

      <BTextarea
        v-model="lessonForm.content"
        label="Inhalt"
        placeholder="Lektionsinhalt beschreiben..."
        :rows="5"
      />

      <BInput
        v-model="lessonForm.duration"
        type="number"
        label="Dauer (Minuten)"
        :placeholder="'30'"
        required
      />
    </div>

    <template #footer>
      <BButton variant="secondary" @click="showModal = false">
        Abbrechen
      </BButton>
      <BButton variant="primary" :disabled="!isLessonValid" @click="handleSaveLesson">
        {{ editingLesson ? 'Speichern' : 'Erstellen' }}
      </BButton>
    </template>
  </BModal>

  <!-- Group Management Modal -->
  <BModal
    v-model="showGroupModal"
    title="Gruppen verwalten"
    size="md"
    @close="showGroupModal = false"
  >
    <div class="space-y-4">
      <!-- Create Group -->
      <div>
        <p :class="LABEL_STYLES.base">Neue Gruppe erstellen</p>
        <div class="flex gap-2">
          <div class="flex-1">
            <input
              v-if="!editingGroup"
              v-model="groupName"
              :class="INPUT_STYLES.base"
              placeholder="Gruppenname..."
              @keydown.enter="handleCreateGroup"
            />
          </div>
          <BButton
            v-if="!editingGroup"
            variant="primary"
            :disabled="!groupName.trim()"
            @click="handleCreateGroup"
          >
            Erstellen
          </BButton>
        </div>
      </div>

      <!-- Existing Groups -->
      <div v-if="academyStore.lessonGroups.length > 0" class="border-t border-slate-200 pt-4">
        <p :class="LABEL_STYLES.base" class="!mb-3">Bestehende Gruppen</p>
        <div class="space-y-2">
          <div
            v-for="group in academyStore.lessonGroups"
            :key="group.id"
            class="flex items-center justify-between p-3 bg-slate-50 rounded-lg"
          >
            <template v-if="editingGroup?.id === group.id">
              <div class="flex-1 flex gap-2">
                <input
                  v-model="groupName"
                  :class="INPUT_STYLES.base"
                  @keydown.enter="handleSaveGroupRename"
                  @keydown.escape="cancelGroupRename"
                />
                <BButton variant="primary" size="sm" @click="handleSaveGroupRename">
                  Speichern
                </BButton>
                <BButton variant="secondary" size="sm" @click="cancelGroupRename">
                  Abbrechen
                </BButton>
              </div>
            </template>
            <template v-else>
              <div>
                <p class="text-sm font-medium text-slate-700">{{ group.name }}</p>
                <p class="text-xs text-slate-500">
                  {{ academyStore.lessons.filter(l => l.groupId === group.id).length }} Lektionen
                </p>
              </div>
              <button
                :class="BUTTON_STYLES.icon"
                title="Umbenennen"
                @click="handleRenameGroup(group)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
            </template>
          </div>
        </div>
      </div>

      <div v-else class="py-4 text-center text-sm text-slate-500">
        Noch keine Gruppen vorhanden
      </div>
    </div>

    <template #footer>
      <BButton variant="secondary" @click="showGroupModal = false">
        Schliessen
      </BButton>
    </template>
  </BModal>
</template>
