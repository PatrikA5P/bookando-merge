<script setup lang="ts">
/**
 * CategoriesTab — Kategorieverwaltung
 *
 * Hierarchische Liste von Kategorien mit Inline-Bearbeitung,
 * Sortierung, Unterkategorien und Erstellungsmodal.
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useOffersStore } from '@/stores/offers';
import type { Category } from '@/stores/offers';
import { CARD_STYLES, BADGE_STYLES, BUTTON_STYLES, LIST_STYLES, MODAL_STYLES, INPUT_STYLES, LABEL_STYLES, GRID_STYLES } from '@/design';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BModal from '@/components/ui/BModal.vue';
import BInput from '@/components/ui/BInput.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BSelect from '@/components/ui/BSelect.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const store = useOffersStore();

const showModal = ref(false);
const editingCategory = ref<Category | null>(null);

// Inline editing
const inlineEditId = ref<string | null>(null);
const inlineEditName = ref('');
const inlineEditOrder = ref(0);

// Form
const form = ref({
  name: '',
  description: '',
  parentId: '' as string | undefined,
  sortOrder: 0,
});
const errors = ref<Record<string, string>>({});

// Hierarchische Ansicht: Eltern-Kategorien und Kinder
const parentCategories = computed(() =>
  store.categories
    .filter(c => !c.parentId)
    .sort((a, b) => a.sortOrder - b.sortOrder)
);

const childCategories = computed(() => {
  const map: Record<string, Category[]> = {};
  store.categories
    .filter(c => c.parentId)
    .sort((a, b) => a.sortOrder - b.sortOrder)
    .forEach(c => {
      if (!map[c.parentId!]) map[c.parentId!] = [];
      map[c.parentId!].push(c);
    });
  return map;
});

const parentOptions = computed(() => [
  { value: '', label: '-- Hauptkategorie --' },
  ...store.categories.filter(c => !c.parentId).map(c => ({
    value: c.id,
    label: c.name,
  })),
]);

function getChildCount(parentId: string): number {
  return childCategories.value[parentId]?.length || 0;
}

function startInlineEdit(category: Category) {
  inlineEditId.value = category.id;
  inlineEditName.value = category.name;
  inlineEditOrder.value = category.sortOrder;
}

function cancelInlineEdit() {
  inlineEditId.value = null;
}

function saveInlineEdit(category: Category) {
  if (!inlineEditName.value.trim()) {
    toast.error(t('common.required'));
    return;
  }
  store.updateCategory(category.id, {
    name: inlineEditName.value.trim(),
    sortOrder: inlineEditOrder.value,
  });
  inlineEditId.value = null;
  toast.success(t('common.saved'));
}

function onCreateCategory(parentId?: string) {
  editingCategory.value = null;
  form.value = {
    name: '',
    description: '',
    parentId: parentId || undefined,
    sortOrder: store.categories.length + 1,
  };
  errors.value = {};
  showModal.value = true;
}

function onEditCategory(category: Category) {
  editingCategory.value = category;
  form.value = {
    name: category.name,
    description: category.description,
    parentId: category.parentId || '',
    sortOrder: category.sortOrder,
  };
  errors.value = {};
  showModal.value = true;
}

function onDeleteCategory(category: Category) {
  // Pruefen ob Services zugeordnet sind
  if (category.serviceCount > 0) {
    toast.warning(`Kategorie hat noch ${category.serviceCount} Dienstleistungen`);
    return;
  }
  // Pruefen ob Unterkategorien existieren
  if (getChildCount(category.id) > 0) {
    toast.warning('Kategorie hat noch Unterkategorien');
    return;
  }
  store.deleteCategory(category.id);
  toast.success(`"${category.name}" geloescht`);
}

function moveCategoryUp(category: Category) {
  const siblings = category.parentId
    ? (childCategories.value[category.parentId] || [])
    : parentCategories.value;
  const index = siblings.findIndex(c => c.id === category.id);
  if (index > 0) {
    const prevOrder = siblings[index - 1].sortOrder;
    store.updateCategory(siblings[index - 1].id, { sortOrder: category.sortOrder });
    store.updateCategory(category.id, { sortOrder: prevOrder });
  }
}

function moveCategoryDown(category: Category) {
  const siblings = category.parentId
    ? (childCategories.value[category.parentId] || [])
    : parentCategories.value;
  const index = siblings.findIndex(c => c.id === category.id);
  if (index < siblings.length - 1) {
    const nextOrder = siblings[index + 1].sortOrder;
    store.updateCategory(siblings[index + 1].id, { sortOrder: category.sortOrder });
    store.updateCategory(category.id, { sortOrder: nextOrder });
  }
}

function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!form.value.name.trim()) {
    errs.name = t('common.required');
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

function onSave() {
  if (!validate()) return;

  if (editingCategory.value) {
    store.updateCategory(editingCategory.value.id, {
      name: form.value.name.trim(),
      description: form.value.description,
      parentId: form.value.parentId || undefined,
      sortOrder: form.value.sortOrder,
    });
    toast.success(t('common.saved'));
  } else {
    store.addCategory({
      name: form.value.name.trim(),
      description: form.value.description,
      parentId: form.value.parentId || undefined,
      sortOrder: form.value.sortOrder,
    });
    toast.success(t('common.saved'));
  }

  showModal.value = false;
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
      <div>
        <h2 class="text-lg font-semibold text-slate-900">{{ t('offers.categories') }}</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ store.categories.length }} {{ t('offers.categories') }}</p>
      </div>
      <BButton variant="primary" @click="onCreateCategory()">
        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('offers.categories') }} {{ t('common.create') }}
      </BButton>
    </div>

    <!-- Leerer Zustand -->
    <BEmptyState
      v-if="store.categories.length === 0"
      title="Keine Kategorien"
      description="Erstellen Sie Ihre erste Kategorie zur Gruppierung von Dienstleistungen."
      icon="folder"
      :action-label="t('common.create')"
      @action="onCreateCategory()"
    />

    <!-- Kategorie-Liste (hierarchisch) -->
    <div v-else class="space-y-3">
      <div
        v-for="parent in parentCategories"
        :key="parent.id"
        :class="CARD_STYLES.base"
        class="overflow-hidden"
      >
        <!-- Eltern-Kategorie -->
        <div class="p-4 flex items-center gap-4 hover:bg-slate-50 transition-colors">
          <!-- Bild-Platzhalter -->
          <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-50 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
          </div>

          <!-- Inline Edit oder Anzeige -->
          <div v-if="inlineEditId === parent.id" class="flex-1 flex items-center gap-3">
            <input
              v-model="inlineEditName"
              :class="INPUT_STYLES.base"
              class="!py-1.5 max-w-[200px]"
              @keydown.enter="saveInlineEdit(parent)"
              @keydown.escape="cancelInlineEdit"
            />
            <input
              v-model="inlineEditOrder"
              type="number"
              :class="INPUT_STYLES.base"
              class="!py-1.5 w-20"
              @keydown.enter="saveInlineEdit(parent)"
            />
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="saveInlineEdit(parent)"
            >
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </button>
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="cancelInlineEdit"
            >
              <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div v-else class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <h3 class="text-sm font-semibold text-slate-900">{{ parent.name }}</h3>
              <BBadge variant="default">{{ parent.serviceCount }} {{ t('offers.catalog') }}</BBadge>
              <span class="text-xs text-slate-400">#{{ parent.sortOrder }}</span>
            </div>
            <p v-if="parent.description" class="text-xs text-slate-500 mt-0.5 truncate">{{ parent.description }}</p>
          </div>

          <!-- Aktionen -->
          <div v-if="inlineEditId !== parent.id" class="flex items-center gap-1 shrink-0">
            <!-- Sortierung -->
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="moveCategoryUp(parent)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
              </svg>
            </button>
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="moveCategoryDown(parent)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div class="w-px h-5 bg-slate-200 mx-1" />
            <!-- Inline Edit -->
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="startInlineEdit(parent)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <!-- Unterkategorie hinzufuegen -->
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="onCreateCategory(parent.id)"
              title="Unterkategorie"
            >
              <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
            <!-- Vollstaendig bearbeiten -->
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="onEditCategory(parent)"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
            <!-- Loeschen -->
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="onDeleteCategory(parent)"
            >
              <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Kinder-Kategorien -->
        <div v-if="childCategories[parent.id]?.length" class="border-t border-slate-100">
          <div
            v-for="child in childCategories[parent.id]"
            :key="child.id"
            class="pl-16 pr-4 py-3 flex items-center gap-4 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-b-0"
          >
            <!-- Einzugs-Indikator -->
            <div class="w-8 h-8 bg-slate-100 rounded-md flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
              </svg>
            </div>

            <!-- Inline edit child -->
            <div v-if="inlineEditId === child.id" class="flex-1 flex items-center gap-3">
              <input
                v-model="inlineEditName"
                :class="INPUT_STYLES.base"
                class="!py-1.5 max-w-[200px]"
                @keydown.enter="saveInlineEdit(child)"
                @keydown.escape="cancelInlineEdit"
              />
              <input
                v-model="inlineEditOrder"
                type="number"
                :class="INPUT_STYLES.base"
                class="!py-1.5 w-20"
                @keydown.enter="saveInlineEdit(child)"
              />
              <button :class="BUTTON_STYLES.icon" class="!p-1" @click="saveInlineEdit(child)">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
              <button :class="BUTTON_STYLES.icon" class="!p-1" @click="cancelInlineEdit">
                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div v-else class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-slate-700">{{ child.name }}</span>
                <BBadge variant="default">{{ child.serviceCount }}</BBadge>
                <span class="text-xs text-slate-400">#{{ child.sortOrder }}</span>
              </div>
              <p v-if="child.description" class="text-xs text-slate-500 truncate">{{ child.description }}</p>
            </div>

            <!-- Aktionen fuer Kinder -->
            <div v-if="inlineEditId !== child.id" class="flex items-center gap-1 shrink-0">
              <button :class="BUTTON_STYLES.icon" class="!p-1" @click="moveCategoryUp(child)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
              </button>
              <button :class="BUTTON_STYLES.icon" class="!p-1" @click="moveCategoryDown(child)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <button :class="BUTTON_STYLES.icon" class="!p-1" @click="startInlineEdit(child)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button :class="BUTTON_STYLES.icon" class="!p-1" @click="onDeleteCategory(child)">
                <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Modal -->
    <BModal
      :model-value="showModal"
      :title="editingCategory ? t('common.edit') + ': ' + editingCategory.name : t('offers.categories') + ' ' + t('common.create')"
      size="md"
      @update:model-value="showModal = false"
      @close="showModal = false"
    >
      <div class="space-y-4">
        <BInput
          v-model="form.name"
          :label="t('common.name')"
          :placeholder="t('offers.categories') + ' Name'"
          :required="true"
          :error="errors.name"
        />

        <BTextarea
          v-model="form.description"
          :label="t('offers.description')"
          :placeholder="t('offers.description') + '...'"
          :rows="2"
        />

        <BSelect
          v-model="form.parentId"
          label="Uebergeordnete Kategorie"
          :options="parentOptions"
        />

        <BInput
          v-model="form.sortOrder"
          type="number"
          label="Sortierung"
          :hint="'Niedrigere Zahl = weiter oben'"
        />

        <!-- Bild-Platzhalter -->
        <div>
          <label :class="LABEL_STYLES.base">{{ t('offers.image') }}</label>
          <div :class="CARD_STYLES.empty" class="!p-8">
            <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-xs text-slate-500">{{ t('offers.image') }} hochladen</p>
          </div>
        </div>
      </div>

      <template #footer>
        <BButton variant="secondary" @click="showModal = false">
          {{ t('common.cancel') }}
        </BButton>
        <BButton variant="primary" @click="onSave">
          {{ t('common.save') }}
        </BButton>
      </template>
    </BModal>
  </div>
</template>
