/**
 * Training Cards Store — Ausbildungskarten-Verwaltung
 *
 * Gemaess MODUL_ANALYSE.md Abschnitt 2.8:
 * - TrainingCardTemplate CRUD (Vorlagen mit Kapiteln und Items)
 * - TrainingCardAssignment (Zuweisung an Schueler)
 * - ItemProgress-Tracking und Notizen
 *
 * Use-Case: Schweizer Fahrschule — Fahrlehrer bewertet Schueler pro Lektion,
 * fuegt Notizen/Skizzen hinzu, Schueler sieht Fortschritt im Portal.
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';
import type {
  TrainingCardTemplate,
  TrainingCardChapter,
  TrainingCardItem,
  TrainingCardItemMedia,
  TrainingCardAssignment,
  TrainingCardItemProgress,
  TrainingCardNote,
  TrainingCardTemplateFormData,
  TrainingCardNoteFormData,
  ItemProgressUpdateData,
  GradingType,
  ItemProgressStatus,
  AssignmentSource,
  AssignmentStatus,
  NoteType,
  TemplateStatus,
} from '@/types/domain/training-cards';

// Re-export domain types for backward compatibility
export type {
  TrainingCardTemplate,
  TrainingCardChapter,
  TrainingCardItem,
  TrainingCardItemMedia,
  TrainingCardAssignment,
  TrainingCardItemProgress,
  TrainingCardNote,
  TrainingCardTemplateFormData,
  TrainingCardNoteFormData,
  ItemProgressUpdateData,
  GradingType,
  ItemProgressStatus,
  AssignmentSource,
  AssignmentStatus,
  NoteType,
  TemplateStatus,
};

// ============================================================================
// CONSTANTS
// ============================================================================

export const GRADING_TYPE_LABELS: Record<GradingType, string> = {
  SLIDER: 'Schieberegler',
  BUTTONS: 'Knoepfe',
  STARS: 'Sterne',
};

export const ITEM_PROGRESS_STATUS_LABELS: Record<ItemProgressStatus, string> = {
  NOT_STARTED: 'Nicht begonnen',
  IN_PROGRESS: 'In Bearbeitung',
  COMPLETED: 'Abgeschlossen',
  SKIPPED: 'Uebersprungen',
};

export const ITEM_PROGRESS_STATUS_COLORS: Record<ItemProgressStatus, string> = {
  NOT_STARTED: 'info',
  IN_PROGRESS: 'warning',
  COMPLETED: 'success',
  SKIPPED: 'danger',
};

export const ASSIGNMENT_STATUS_LABELS: Record<AssignmentStatus, string> = {
  ACTIVE: 'Aktiv',
  COMPLETED: 'Abgeschlossen',
  CANCELLED: 'Abgebrochen',
};

// ============================================================================
// STORE
// ============================================================================

export const useTrainingCardsStore = defineStore('training-cards', () => {
  // -- State ----------------------------------------------------------------
  const templates = ref<TrainingCardTemplate[]>([]);
  const assignments = ref<TrainingCardAssignment[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // -- Getters: Counts ------------------------------------------------------
  const templateCount = computed(() => templates.value.length);
  const assignmentCount = computed(() => assignments.value.length);

  // -- Getters: Template Filters --------------------------------------------
  const activeTemplates = computed(() =>
    templates.value.filter(t => t.status === 'ACTIVE')
  );

  const archivedTemplates = computed(() =>
    templates.value.filter(t => t.status === 'ARCHIVED')
  );

  // -- Getters: Assignment Filters ------------------------------------------
  const activeAssignments = computed(() =>
    assignments.value.filter(a => a.status === 'ACTIVE')
  );

  const completedAssignments = computed(() =>
    assignments.value.filter(a => a.status === 'COMPLETED')
  );

  // -- Fetch Actions --------------------------------------------------------
  async function fetchTemplates(): Promise<void> {
    try {
      const response = await api.get<{ data: TrainingCardTemplate[] }>('/v1/training-card-templates', { per_page: 200 });
      templates.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Vorlagen konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAssignments(customerId?: string): Promise<void> {
    try {
      const params = customerId ? { customerId } : {};
      const response = await api.get<{ data: TrainingCardAssignment[] }>('/v1/training-card-assignments', params);
      assignments.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Zuweisungen konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([fetchTemplates(), fetchAssignments()]);
    } catch {
      // Individual fetch functions already set error.value
    } finally {
      loading.value = false;
    }
  }

  // -- Lookups --------------------------------------------------------------
  function getTemplateById(id: string): TrainingCardTemplate | undefined {
    return templates.value.find(t => t.id === id);
  }

  function getAssignmentById(id: string): TrainingCardAssignment | undefined {
    return assignments.value.find(a => a.id === id);
  }

  // -- CRUD: Templates ------------------------------------------------------
  async function createTemplate(data: TrainingCardTemplateFormData): Promise<TrainingCardTemplate> {
    try {
      const response = await api.post<{ data: TrainingCardTemplate }>('/v1/training-card-templates', data);
      templates.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Vorlage konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateTemplate(id: string, data: Partial<TrainingCardTemplateFormData>): Promise<TrainingCardTemplate> {
    try {
      const response = await api.put<{ data: TrainingCardTemplate }>(`/v1/training-card-templates/${id}`, data);
      const index = templates.value.findIndex(t => t.id === id);
      if (index !== -1) {
        templates.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Vorlage konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteTemplate(id: string): Promise<boolean> {
    try {
      await api.delete(`/v1/training-card-templates/${id}`);
      templates.value = templates.value.filter(t => t.id !== id);
      return true;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Vorlage konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // -- CRUD: Assignments ----------------------------------------------------
  async function createAssignment(data: { templateId: string; customerId: string }): Promise<TrainingCardAssignment> {
    try {
      const response = await api.post<{ data: TrainingCardAssignment }>('/v1/training-card-assignments', data);
      assignments.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Zuweisung konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  // -- Item Progress --------------------------------------------------------
  async function updateItemProgress(assignmentId: string, itemId: string, data: ItemProgressUpdateData): Promise<TrainingCardItemProgress> {
    try {
      const response = await api.put<{ data: TrainingCardItemProgress }>(
        `/v1/training-card-assignments/${assignmentId}/items/${itemId}/progress`,
        data
      );

      // Update local assignment's itemProgress list
      const assignment = assignments.value.find(a => a.id === assignmentId);
      if (assignment) {
        const progressIndex = assignment.itemProgress.findIndex(p => p.itemId === itemId);
        if (progressIndex !== -1) {
          assignment.itemProgress[progressIndex] = response.data;
        } else {
          assignment.itemProgress.push(response.data);
        }
      }

      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Fortschritt konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  // -- Notes ----------------------------------------------------------------
  async function addNote(data: TrainingCardNoteFormData): Promise<TrainingCardNote> {
    try {
      const response = await api.post<{ data: TrainingCardNote }>(
        `/v1/training-card-assignments/${data.assignmentId}/notes`,
        data
      );

      // Update local assignment's notes list
      const assignment = assignments.value.find(a => a.id === data.assignmentId);
      if (assignment) {
        assignment.notes.push(response.data);
      }

      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Notiz konnte nicht hinzugefuegt werden';
      error.value = message;
      throw err;
    }
  }

  return {
    // State
    templates,
    assignments,
    loading,
    error,

    // Counts
    templateCount,
    assignmentCount,

    // Template filters
    activeTemplates,
    archivedTemplates,

    // Assignment filters
    activeAssignments,
    completedAssignments,

    // Fetch
    fetchTemplates,
    fetchAssignments,
    fetchAll,

    // Lookups
    getTemplateById,
    getAssignmentById,

    // Template CRUD
    createTemplate,
    updateTemplate,
    deleteTemplate,

    // Assignment CRUD
    createAssignment,

    // Item Progress
    updateItemProgress,

    // Notes
    addNote,
  };
});
