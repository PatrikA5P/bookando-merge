<script setup lang="ts">
/**
 * TrainingCardsTab — Ausbildungskarten-Vorlagen Uebersicht
 *
 * Zeigt alle TrainingCardTemplates als Karten-Grid.
 * Erstellen/Bearbeiten ueber TrainingCardFormPanel (SlideIn).
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useTrainingCardsStore, GRADING_TYPE_LABELS } from '@/stores/training-cards';
import type { TrainingCardTemplate } from '@/stores/training-cards';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { CARD_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const store = useTrainingCardsStore();

const emit = defineEmits<{
  (e: 'edit', template: TrainingCardTemplate): void;
  (e: 'create'): void;
}>();

const searchQuery = ref('');

const filteredTemplates = computed(() => {
  let result = [...store.templates];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(tmpl =>
      tmpl.title.toLowerCase().includes(q) ||
      (tmpl.description || '').toLowerCase().includes(q)
    );
  }

  return result;
});

function getChapterCount(tmpl: TrainingCardTemplate): number {
  return tmpl.chapters.length;
}

function getItemCount(tmpl: TrainingCardTemplate): number {
  return tmpl.chapters.reduce((sum, ch) => sum + ch.items.length, 0);
}

function getGradingLabel(tmpl: TrainingCardTemplate): string {
  return `${GRADING_TYPE_LABELS[tmpl.gradingType]} (${tmpl.gradingMin}–${tmpl.gradingMax})`;
}

async function handleDelete(tmpl: TrainingCardTemplate) {
  try {
    await store.deleteTemplate(tmpl.id);
    toast.success(t('common.deletedSuccessfully'));
  } catch {
    toast.error(t('common.errorOccurred'));
  }
}
</script>

<template>
  <div>
    <!-- Search + Create -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <div class="flex-1 max-w-md">
        <BSearchBar v-model="searchQuery" placeholder="Vorlagen suchen..." />
      </div>
      <BButton variant="primary" @click="emit('create')">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Neue Vorlage
      </BButton>
    </div>

    <!-- Template Cards -->
    <div v-if="filteredTemplates.length > 0" :class="GRID_STYLES.cols3">
      <div
        v-for="tmpl in filteredTemplates"
        :key="tmpl.id"
        :class="CARD_STYLES.hover"
        class="p-5 cursor-pointer"
        @click="emit('edit', tmpl)"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ tmpl.title }}</h3>
              <p v-if="tmpl.description" class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ tmpl.description }}</p>
            </div>
          </div>
          <BBadge :variant="tmpl.status === 'ACTIVE' ? 'success' : 'info'">
            {{ tmpl.status === 'ACTIVE' ? 'Aktiv' : 'Archiviert' }}
          </BBadge>
        </div>

        <!-- Stats -->
        <div class="flex items-center gap-4 mb-3">
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            {{ getChapterCount(tmpl) }} Kapitel
          </div>
          <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            {{ getItemCount(tmpl) }} Items
          </div>
        </div>

        <!-- Grading Info -->
        <div class="text-xs text-slate-400 mb-4">
          Bewertung: {{ getGradingLabel(tmpl) }}
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
          <BButton variant="ghost" size="sm" @click.stop="emit('edit', tmpl)">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            {{ t('common.edit') }}
          </BButton>
          <BButton variant="ghost" size="sm" @click.stop="handleDelete(tmpl)">
            <svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            {{ t('common.delete') }}
          </BButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <BEmptyState
      v-else
      title="Keine Ausbildungskarten-Vorlagen"
      description="Erstellen Sie Ihre erste Vorlage fuer die Ausbildungskarten."
      icon="folder"
      action-label="Erste Vorlage erstellen"
      @action="emit('create')"
    />
  </div>
</template>
