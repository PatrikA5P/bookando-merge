<script setup lang="ts">
/**
 * BadgesTab — Badge-Verwaltung
 *
 * Features:
 * - Responsive Grid (2/4 Spalten)
 * - Badge-Karten mit farbigem Icon-Kreis, Name, Beschreibung, Kursanzahl
 * - Inline-Formular zum Erstellen neuer Badges (oben)
 * - Loeschen mit BConfirmDialog
 * - Bearbeiten inline
 */
import { ref, computed } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BConfirmDialog from '@/components/ui/BConfirmDialog.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useAcademyStore } from '@/stores/academy';
import type { Badge } from '@/stores/academy';
import { CARD_STYLES, BUTTON_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const academyStore = useAcademyStore();

// ---- State ----
const showCreateForm = ref(false);
const deletingBadge = ref<Badge | null>(null);
const showDeleteConfirm = ref(false);

// ---- Create Form ----
const createForm = ref({
  name: '',
  icon: 'star',
  color: '#f43f5e',
  description: '',
});

const iconOptions = [
  { value: 'star', label: 'Stern' },
  { value: 'shield', label: 'Schild' },
  { value: 'crown', label: 'Krone' },
  { value: 'palette', label: 'Palette' },
  { value: 'scissors', label: 'Schere' },
  { value: 'trophy', label: 'Pokal' },
  { value: 'flame', label: 'Flamme' },
  { value: 'heart', label: 'Herz' },
];

const colorOptions = [
  { value: '#f43f5e', label: 'Rose' },
  { value: '#8b5cf6', label: 'Violett' },
  { value: '#f59e0b', label: 'Amber' },
  { value: '#10b981', label: 'Emerald' },
  { value: '#3b82f6', label: 'Blau' },
  { value: '#ec4899', label: 'Pink' },
  { value: '#06b6d4', label: 'Cyan' },
  { value: '#f97316', label: 'Orange' },
];

// ---- Computed ----
const isCreateValid = computed(() => {
  return createForm.value.name.trim().length > 0;
});

// ---- Actions ----
function toggleCreateForm() {
  showCreateForm.value = !showCreateForm.value;
  if (showCreateForm.value) {
    createForm.value = {
      name: '',
      icon: 'star',
      color: '#f43f5e',
      description: '',
    };
  }
}

function handleCreate() {
  if (!isCreateValid.value) return;

  academyStore.addBadge({
    name: createForm.value.name.trim(),
    icon: createForm.value.icon,
    color: createForm.value.color,
    description: createForm.value.description.trim(),
  });

  toast.success('Badge erstellt');
  showCreateForm.value = false;
  createForm.value = { name: '', icon: 'star', color: '#f43f5e', description: '' };
}

function confirmDelete(badge: Badge) {
  deletingBadge.value = badge;
  showDeleteConfirm.value = true;
}

function handleDelete() {
  if (!deletingBadge.value) return;
  academyStore.deleteBadge(deletingBadge.value.id);
  toast.success('Badge geloescht');
  showDeleteConfirm.value = false;
  deletingBadge.value = null;
}

function handleCancelDelete() {
  showDeleteConfirm.value = false;
  deletingBadge.value = null;
}

// ---- Badge Icon SVG ----
function getBadgeIconPath(icon: string): string {
  switch (icon) {
    case 'star':
      return 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z';
    case 'shield':
      return 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
    case 'crown':
      return 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z';
    case 'palette':
      return 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01';
    case 'scissors':
      return 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z';
    case 'trophy':
      return 'M5 3h14M5 3v4a7 7 0 007 7m-7-7H3m16 0h-2m2 0v4a7 7 0 01-7 7m0 0v3m-4 0h8';
    case 'flame':
      return 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z';
    case 'heart':
      return 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z';
    default:
      return 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z';
  }
}
</script>

<template>
  <!-- Create Badge Inline Form -->
  <div class="mb-6">
    <div v-if="!showCreateForm" class="flex justify-end">
      <BButton variant="primary" @click="toggleCreateForm">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Neues Badge
      </BButton>
    </div>

    <div
      v-else
      :class="CARD_STYLES.base"
      class="p-5"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-slate-900">Neues Badge erstellen</h3>
        <button
          :class="BUTTON_STYLES.icon"
          @click="toggleCreateForm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <BInput
          v-model="createForm.name"
          label="Badge-Name"
          placeholder="z.B. Koloristik Profi"
          required
        />

        <BInput
          v-model="createForm.description"
          label="Beschreibung"
          placeholder="Kurze Beschreibung..."
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <!-- Icon Selection -->
        <div>
          <p :class="LABEL_STYLES.base">Icon</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in iconOptions"
              :key="option.value"
              :class="[
                'w-9 h-9 rounded-lg border-2 flex items-center justify-center transition-all',
                createForm.icon === option.value
                  ? 'border-rose-500 bg-rose-50'
                  : 'border-slate-200 hover:border-slate-300',
              ]"
              :title="option.label"
              @click="createForm.icon = option.value"
            >
              <svg
                class="w-4 h-4"
                :class="createForm.icon === option.value ? 'text-rose-600' : 'text-slate-500'"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  :d="getBadgeIconPath(option.value)"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Color Selection -->
        <div>
          <p :class="LABEL_STYLES.base">Farbe</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in colorOptions"
              :key="option.value"
              :class="[
                'w-9 h-9 rounded-lg border-2 flex items-center justify-center transition-all',
                createForm.color === option.value
                  ? 'border-slate-800 ring-2 ring-offset-1 ring-slate-400'
                  : 'border-transparent',
              ]"
              :style="{ backgroundColor: option.value }"
              :title="option.label"
              @click="createForm.color = option.value"
            >
              <svg
                v-if="createForm.color === option.value"
                class="w-4 h-4 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Preview + Actions -->
      <div class="flex items-center justify-between pt-4 border-t border-slate-200">
        <!-- Preview -->
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center"
            :style="{ backgroundColor: createForm.color + '20' }"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              :style="{ color: createForm.color }"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                :d="getBadgeIconPath(createForm.icon)"
              />
            </svg>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-700">
              {{ createForm.name || 'Badge-Name' }}
            </p>
            <p class="text-xs text-slate-500">
              {{ createForm.description || 'Beschreibung' }}
            </p>
          </div>
        </div>

        <div class="flex gap-2">
          <BButton variant="secondary" @click="toggleCreateForm">
            Abbrechen
          </BButton>
          <BButton variant="primary" :disabled="!isCreateValid" @click="handleCreate">
            Erstellen
          </BButton>
        </div>
      </div>
    </div>
  </div>

  <!-- Empty State -->
  <BEmptyState
    v-if="academyStore.badges.length === 0"
    title="Noch keine Badges vorhanden"
    description="Erstellen Sie Ihr erstes Badge fuer Kurszertifizierungen."
    icon="inbox"
    action-label="Erstes Badge erstellen"
    @action="toggleCreateForm"
  />

  <!-- Badge Grid -->
  <div
    v-else
    class="grid grid-cols-2 md:grid-cols-4 gap-4"
  >
    <div
      v-for="badge in academyStore.badges"
      :key="badge.id"
      :class="CARD_STYLES.hover"
      class="p-5 text-center group"
    >
      <!-- Icon Circle -->
      <div
        class="w-14 h-14 mx-auto rounded-full flex items-center justify-center mb-3"
        :style="{ backgroundColor: badge.color + '20' }"
      >
        <svg
          class="w-7 h-7"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          :style="{ color: badge.color }"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            :d="getBadgeIconPath(badge.icon)"
          />
        </svg>
      </div>

      <!-- Name -->
      <h4 class="text-sm font-semibold text-slate-900 mb-1">
        {{ badge.name }}
      </h4>

      <!-- Description -->
      <p class="text-xs text-slate-500 mb-3 line-clamp-2">
        {{ badge.description }}
      </p>

      <!-- Course Count -->
      <div class="text-xs text-slate-400 mb-3">
        {{ badge.courseCount === 1 ? '1 Kurs' : `${badge.courseCount} Kurse` }}
      </div>

      <!-- Delete Button (visible on hover) -->
      <button
        :class="BUTTON_STYLES.icon"
        class="mx-auto opacity-0 group-hover:opacity-100 transition-opacity text-red-400 hover:text-red-600"
        title="Badge loeschen"
        @click.stop="confirmDelete(badge)"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
      </button>
    </div>
  </div>

  <!-- Delete Confirmation -->
  <BConfirmDialog
    v-model="showDeleteConfirm"
    title="Badge loeschen"
    :message="`Moechten Sie das Badge '${deletingBadge?.name}' wirklich loeschen? Es wird auch von allen verknuepften Kursen entfernt.`"
    confirm-label="Loeschen"
    cancel-label="Abbrechen"
    variant="danger"
    @confirm="handleDelete"
    @cancel="handleCancelDelete"
  />
</template>
