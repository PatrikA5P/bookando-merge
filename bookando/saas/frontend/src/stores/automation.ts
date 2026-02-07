/**
 * Automation Store — Event-basierte Regeln
 *
 * Gemaess MODUL_ANALYSE.md Abschnitt 2.9 / 5.2:
 * - TriggerEvent (z.B. BOOKING_CONFIRMED) + Bedingung → ActionType (z.B. ASSIGN_TRAINING_CARD)
 * - Verknuepfung Offers ↔ Academy ↔ TrainingCards
 * - Regeln mit Prioritaet und Duplikat-Kontrolle
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';
import type {
  AutomationRule,
  AutomationRuleFormData,
  TriggerEvent,
  ActionType,
  ActionConfig,
  TriggerCondition,
} from '@/types/domain/automation';
import {
  TRIGGER_EVENT_LABELS,
  ACTION_TYPE_LABELS,
} from '@/types/domain/automation';

// Re-export domain types
export type {
  AutomationRule,
  AutomationRuleFormData,
  TriggerEvent,
  ActionType,
  ActionConfig,
  TriggerCondition,
};

export {
  TRIGGER_EVENT_LABELS,
  ACTION_TYPE_LABELS,
};

// ============================================================================
// FILTERS
// ============================================================================

export interface AutomationFilters {
  search: string;
  triggerEvent: TriggerEvent | '';
  actionType: ActionType | '';
  active: boolean | null;
}

// ============================================================================
// STORE
// ============================================================================

export const useAutomationStore = defineStore('automation', () => {
  // ── State ──────────────────────────────────────────────────────────────
  const rules = ref<AutomationRule[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<AutomationFilters>({
    search: '',
    triggerEvent: '',
    actionType: '',
    active: null,
  });

  // ── Filtered Views ─────────────────────────────────────────────────────
  const filteredRules = computed(() => {
    let result = rules.value;

    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(r =>
        r.name.toLowerCase().includes(q) ||
        (r.description || '').toLowerCase().includes(q) ||
        (r.triggerOfferTitle || '').toLowerCase().includes(q)
      );
    }

    if (filters.value.triggerEvent) {
      result = result.filter(r => r.triggerEvent === filters.value.triggerEvent);
    }

    if (filters.value.actionType) {
      result = result.filter(r => r.actionType === filters.value.actionType);
    }

    if (filters.value.active !== null) {
      result = result.filter(r => r.active === filters.value.active);
    }

    return result.sort((a, b) => a.priority - b.priority);
  });

  // ── Counts ────────────────────────────────────────────────────────────
  const ruleCount = computed(() => rules.value.length);
  const activeRuleCount = computed(() => rules.value.filter(r => r.active).length);
  const inactiveRuleCount = computed(() => rules.value.filter(r => !r.active).length);

  // ── Grouped Views ─────────────────────────────────────────────────────
  const rulesByTrigger = computed(() => {
    const grouped: Record<string, AutomationRule[]> = {};
    for (const rule of rules.value) {
      if (!grouped[rule.triggerEvent]) {
        grouped[rule.triggerEvent] = [];
      }
      grouped[rule.triggerEvent].push(rule);
    }
    return grouped;
  });

  // ── Fetch Actions ──────────────────────────────────────────────────────
  async function fetchRules(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get<{ data: AutomationRule[] }>('/v1/automation-rules');
      rules.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Regeln konnten nicht geladen werden';
      error.value = message;
      throw err;
    } finally {
      loading.value = false;
    }
  }

  // ── Lookups ────────────────────────────────────────────────────────────
  function getRuleById(id: string): AutomationRule | undefined {
    return rules.value.find(r => r.id === id);
  }

  function getRulesForOffer(offerId: string): AutomationRule[] {
    return rules.value.filter(r => r.triggerOfferId === offerId);
  }

  // ── CRUD ──────────────────────────────────────────────────────────────
  async function createRule(data: AutomationRuleFormData): Promise<AutomationRule> {
    try {
      const response = await api.post<{ data: AutomationRule }>('/v1/automation-rules', data);
      rules.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Regel konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateRule(id: string, data: Partial<AutomationRuleFormData>): Promise<AutomationRule> {
    try {
      const response = await api.put<{ data: AutomationRule }>(`/v1/automation-rules/${id}`, data);
      const index = rules.value.findIndex(r => r.id === id);
      if (index !== -1) {
        rules.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Regel konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteRule(id: string): Promise<void> {
    try {
      await api.delete(`/v1/automation-rules/${id}`);
      rules.value = rules.value.filter(r => r.id !== id);
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Regel konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  async function toggleRule(id: string): Promise<AutomationRule> {
    const rule = rules.value.find(r => r.id === id);
    if (!rule) {
      throw new Error(`Regel ${id} nicht gefunden`);
    }
    return updateRule(id, { active: !rule.active } as Partial<AutomationRuleFormData>);
  }

  // ── Filter Actions ─────────────────────────────────────────────────────
  function setFilters(newFilters: Partial<AutomationFilters>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = { search: '', triggerEvent: '', actionType: '', active: null };
  }

  return {
    // State
    rules,
    loading,
    error,
    filters,

    // Filtered views
    filteredRules,

    // Counts
    ruleCount,
    activeRuleCount,
    inactiveRuleCount,

    // Grouped
    rulesByTrigger,

    // Fetch
    fetchRules,

    // Lookups
    getRuleById,
    getRulesForOffer,

    // CRUD
    createRule,
    updateRule,
    deleteRule,
    toggleRule,

    // Filters
    setFilters,
    resetFilters,
  };
});
