<script setup lang="ts">
/**
 * Automation-Modul — Event-basierte Regeln
 *
 * Verknuepft Offers ↔ Academy ↔ TrainingCards ueber TriggerEvent → ActionType.
 * Gold Standard: BFormPanel SlideIn fuer Regeln.
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import RulesTab from './components/RulesTab.vue';
import AutomationRuleFormPanel from './components/AutomationRuleFormPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { useAutomationStore } from '@/stores/automation';
import type { AutomationRule } from '@/stores/automation';

const { t } = useI18n();
const store = useAutomationStore();

const activeTab = ref('rules');

const tabs = computed<Tab[]>(() => [
  { id: 'rules', label: 'Regeln', badge: store.ruleCount },
]);

// ── FormPanel State ──────────────────────────────────────────────────────
const showRulePanel = ref(false);
const editingRule = ref<AutomationRule | null>(null);

function handleCreate() {
  editingRule.value = null;
  showRulePanel.value = true;
}

function handleEdit(rule: AutomationRule) {
  editingRule.value = rule;
  showRulePanel.value = true;
}

function handleSaved() {
  showRulePanel.value = false;
  editingRule.value = null;
}
</script>

<template>
  <ModuleLayout
    module-name="automation"
    title="Automatisierung"
    subtitle="Event-basierte Regeln fuer automatische Aktionen"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    fab-label="Neue Regel"
    @tab-change="(id: string) => activeTab = id"
    @fab-click="handleCreate"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <span class="text-xs text-white/70">
          {{ store.activeRuleCount }} aktiv / {{ store.inactiveRuleCount }} inaktiv
        </span>
        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
          @click="handleCreate"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Neue Regel
        </button>
      </div>
    </template>

    <!-- Regeln -->
    <RulesTab
      v-if="activeTab === 'rules'"
      @edit="handleEdit"
      @create="handleCreate"
    />
  </ModuleLayout>

  <!-- Automation Rule Form Panel (SlideIn) -->
  <AutomationRuleFormPanel
    v-model="showRulePanel"
    :rule="editingRule"
    @saved="handleSaved"
    @deleted="handleSaved"
  />
</template>
