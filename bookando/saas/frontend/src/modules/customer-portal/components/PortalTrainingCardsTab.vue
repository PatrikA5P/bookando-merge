<script setup lang="ts">
/**
 * PortalTrainingCardsTab — Kundenseitige Ausbildungskarten-Ansicht
 *
 * Zeigt dem Kunden seine zugewiesenen Ausbildungskarten
 * mit Kapitel-Fortschritt und Bewertungen.
 */
import { computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import {
  useTrainingCardsStore,
  GRADING_TYPE_LABELS,
  ITEM_PROGRESS_STATUS_LABELS,
  ITEM_PROGRESS_STATUS_COLORS,
  ASSIGNMENT_STATUS_LABELS,
} from '@/stores/training-cards';
import type { TrainingCardAssignment } from '@/stores/training-cards';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const store = useTrainingCardsStore();

const myAssignments = computed(() => store.assignments);

function getTemplate(assignment: TrainingCardAssignment) {
  return store.templates.find(t => t.id === assignment.templateId);
}

function getCompletedItemCount(assignment: TrainingCardAssignment): number {
  return assignment.itemProgress.filter(p =>
    p.status === 'COMPLETED' || p.status === 'GRADED'
  ).length;
}

function getTotalItemCount(assignment: TrainingCardAssignment): number {
  const tmpl = getTemplate(assignment);
  if (!tmpl) return 0;
  return tmpl.chapters.reduce((sum, ch) => sum + ch.items.length, 0);
}

function getProgressPercent(assignment: TrainingCardAssignment): number {
  const total = getTotalItemCount(assignment);
  if (total === 0) return 0;
  return Math.round((getCompletedItemCount(assignment) / total) * 100);
}

function getStatusVariant(status: string): 'default' | 'success' | 'warning' | 'info' {
  switch (status) {
    case 'ACTIVE': return 'info';
    case 'COMPLETED': return 'success';
    case 'EXPIRED': return 'warning';
    default: return 'default';
  }
}
</script>

<template>
  <div>
    <BEmptyState
      v-if="myAssignments.length === 0"
      title="Keine Ausbildungskarten"
      description="Ihnen wurden noch keine Ausbildungskarten zugewiesen."
      icon="folder"
    />

    <div v-else class="space-y-4">
      <div
        v-for="assignment in myAssignments"
        :key="assignment.id"
        :class="CARD_STYLES.base"
        class="overflow-hidden"
      >
        <!-- Progress Bar Header -->
        <div class="h-2 bg-slate-100">
          <div
            class="h-full bg-gradient-to-r from-violet-500 to-purple-500 transition-all duration-500"
            :style="{ width: `${getProgressPercent(assignment)}%` }"
          />
        </div>

        <div class="p-5">
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div>
              <h3 class="text-base font-semibold text-slate-900">
                {{ getTemplate(assignment)?.title || 'Ausbildungskarte' }}
              </h3>
              <p v-if="getTemplate(assignment)?.description" class="text-sm text-slate-500 mt-0.5">
                {{ getTemplate(assignment)?.description }}
              </p>
            </div>
            <BBadge :variant="getStatusVariant(assignment.status)">
              {{ ASSIGNMENT_STATUS_LABELS[assignment.status] || assignment.status }}
            </BBadge>
          </div>

          <!-- Progress Summary -->
          <div class="flex items-center gap-4 mb-4 text-sm">
            <div class="flex items-center gap-2">
              <span class="text-slate-500">Fortschritt:</span>
              <span class="font-semibold text-violet-600">{{ getCompletedItemCount(assignment) }}/{{ getTotalItemCount(assignment) }} Items</span>
              <span class="text-slate-400">({{ getProgressPercent(assignment) }}%)</span>
            </div>
          </div>

          <!-- Chapter Details -->
          <div v-if="getTemplate(assignment)" class="space-y-3">
            <div
              v-for="chapter in getTemplate(assignment)!.chapters"
              :key="chapter.id"
              class="rounded-lg border border-slate-100 overflow-hidden"
            >
              <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                <h4 class="text-sm font-medium text-slate-700">{{ chapter.title }}</h4>
              </div>

              <div class="divide-y divide-slate-50">
                <div
                  v-for="item in chapter.items"
                  :key="item.id"
                  class="px-4 py-2 flex items-center justify-between"
                >
                  <span class="text-sm text-slate-600">{{ item.title }}</span>
                  <div class="flex items-center gap-2">
                    <!-- Grade if available -->
                    <span
                      v-if="assignment.itemProgress.find(p => p.itemId === item.id)?.grade !== undefined"
                      class="text-xs font-medium text-violet-600"
                    >
                      Note: {{ assignment.itemProgress.find(p => p.itemId === item.id)?.grade }}
                    </span>
                    <!-- Status -->
                    <BBadge
                      :variant="(assignment.itemProgress.find(p => p.itemId === item.id)?.status === 'COMPLETED' || assignment.itemProgress.find(p => p.itemId === item.id)?.status === 'GRADED') ? 'success' : 'default'"
                      size="sm"
                    >
                      {{ ITEM_PROGRESS_STATUS_LABELS[assignment.itemProgress.find(p => p.itemId === item.id)?.status || 'NOT_STARTED'] }}
                    </BBadge>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="assignment.notes.length > 0" class="mt-4 pt-4 border-t border-slate-100">
            <h4 class="text-sm font-medium text-slate-700 mb-2">Notizen</h4>
            <div class="space-y-2">
              <div
                v-for="note in assignment.notes.slice(-3)"
                :key="note.id"
                class="text-xs text-slate-500 bg-slate-50 rounded-lg p-2.5"
              >
                <p>{{ note.text }}</p>
                <span class="text-slate-400 mt-1 block">{{ new Date(note.createdAt).toLocaleDateString('de-CH') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
