<script setup lang="ts">
/**
 * RulesTab — Automations-Regeln Uebersicht
 *
 * Zeigt alle AutomationRules als Karten mit:
 * - Trigger → Action Visualisierung
 * - Aktiv/Inaktiv Toggle
 * - Filter nach TriggerEvent und ActionType
 */
import { ref, computed } from 'vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import {
  useAutomationStore,
  TRIGGER_EVENT_LABELS,
  ACTION_TYPE_LABELS,
} from '@/stores/automation';
import type { AutomationRule, TriggerEvent, ActionType } from '@/stores/automation';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const store = useAutomationStore();

const emit = defineEmits<{
  (e: 'edit', rule: AutomationRule): void;
  (e: 'create'): void;
}>();

const searchQuery = ref('');
const filterTrigger = ref<TriggerEvent | ''>('');
const filterAction = ref<ActionType | ''>('');

const triggerOptions = [
  { value: '', label: 'Alle Trigger' },
  ...Object.entries(TRIGGER_EVENT_LABELS).map(([v, l]) => ({ value: v, label: l })),
];

const actionOptions = [
  { value: '', label: 'Alle Aktionen' },
  ...Object.entries(ACTION_TYPE_LABELS).map(([v, l]) => ({ value: v, label: l })),
];

const filteredRules = computed(() => {
  let result = [...store.rules];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(r =>
      r.name.toLowerCase().includes(q) ||
      (r.description || '').toLowerCase().includes(q)
    );
  }

  if (filterTrigger.value) {
    result = result.filter(r => r.triggerEvent === filterTrigger.value);
  }

  if (filterAction.value) {
    result = result.filter(r => r.actionType === filterAction.value);
  }

  return result.sort((a, b) => a.priority - b.priority);
});

function getTriggerIcon(trigger: TriggerEvent): string {
  switch (trigger) {
    case 'BOOKING_CREATED':
    case 'BOOKING_CONFIRMED':
    case 'BOOKING_PAID':
    case 'BOOKING_CANCELLED':
      return 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z';
    case 'SESSION_COMPLETED':
    case 'FIRST_SESSION_OF_TYPE':
      return 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4';
    case 'COURSE_COMPLETED':
      return 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253';
    default:
      return 'M13 10V3L4 14h7v7l9-11h-7z';
  }
}

function getActionIcon(action: ActionType): string {
  switch (action) {
    case 'ASSIGN_TRAINING_CARD':
      return 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4';
    case 'ASSIGN_ONLINE_COURSE':
    case 'ENROLL_IN_COURSE':
      return 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253';
    case 'GRANT_BADGE':
      return 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z';
    case 'SEND_NOTIFICATION':
      return 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9';
    default:
      return 'M13 10V3L4 14h7v7l9-11h-7z';
  }
}

async function handleToggle(rule: AutomationRule) {
  try {
    await store.toggleRule(rule.id);
    toast.success(rule.active ? 'Regel deaktiviert' : 'Regel aktiviert');
  } catch {
    toast.error(t('common.errorOccurred'));
  }
}

async function handleDelete(rule: AutomationRule) {
  try {
    await store.deleteRule(rule.id);
    toast.success(t('common.deletedSuccessfully'));
  } catch {
    toast.error(t('common.errorOccurred'));
  }
}
</script>

<template>
  <!-- Search & Filters -->
  <div class="flex flex-col gap-4 mb-6">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <div class="flex-1">
        <BSearchBar v-model="searchQuery" placeholder="Regeln suchen..." />
      </div>
      <BButton variant="primary" class="hidden md:flex" @click="emit('create')">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Neue Regel
      </BButton>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="sm:w-56"><BSelect v-model="filterTrigger" :options="triggerOptions" /></div>
      <div class="sm:w-52"><BSelect v-model="filterAction" :options="actionOptions" /></div>
    </div>
  </div>

  <!-- Empty States -->
  <BEmptyState
    v-if="filteredRules.length === 0 && !searchQuery && !filterTrigger && !filterAction"
    title="Noch keine Automations-Regeln"
    description="Erstellen Sie Ihre erste Regel um Aktionen automatisch auszuloesen."
    icon="folder"
    action-label="Erste Regel erstellen"
    @action="emit('create')"
  />

  <BEmptyState
    v-else-if="filteredRules.length === 0"
    title="Keine Regeln gefunden"
    description="Passen Sie Ihre Filter an."
    icon="search"
  />

  <!-- Rule Cards -->
  <div v-else class="space-y-3">
    <div
      v-for="rule in filteredRules"
      :key="rule.id"
      :class="CARD_STYLES.hover"
      class="p-4 cursor-pointer"
      :style="{ opacity: rule.active ? 1 : 0.6 }"
      @click="emit('edit', rule)"
    >
      <div class="flex items-start gap-4">
        <!-- Active Indicator -->
        <button
          class="mt-1 shrink-0"
          :title="rule.active ? 'Deaktivieren' : 'Aktivieren'"
          @click.stop="handleToggle(rule)"
        >
          <div
            class="w-8 h-5 rounded-full transition-colors duration-200 relative"
            :class="rule.active ? 'bg-emerald-500' : 'bg-slate-300'"
          >
            <div
              class="absolute top-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform duration-200"
              :class="rule.active ? 'translate-x-3.5' : 'translate-x-0.5'"
            />
          </div>
        </button>

        <!-- Rule Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <h3 class="text-sm font-semibold text-slate-900 truncate">{{ rule.name }}</h3>
            <BBadge :variant="rule.active ? 'success' : 'default'" size="sm">
              {{ rule.active ? 'Aktiv' : 'Inaktiv' }}
            </BBadge>
          </div>
          <p v-if="rule.description" class="text-xs text-slate-500 mb-3 line-clamp-1">{{ rule.description }}</p>

          <!-- Trigger → Action Flow -->
          <div class="flex items-center gap-2 flex-wrap">
            <!-- Trigger -->
            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-amber-50 border border-amber-200">
              <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTriggerIcon(rule.triggerEvent)" />
              </svg>
              <span class="text-xs font-medium text-amber-700">{{ TRIGGER_EVENT_LABELS[rule.triggerEvent] }}</span>
            </div>

            <!-- Arrow -->
            <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>

            <!-- Action -->
            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-violet-50 border border-violet-200">
              <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getActionIcon(rule.actionType)" />
              </svg>
              <span class="text-xs font-medium text-violet-700">{{ ACTION_TYPE_LABELS[rule.actionType] }}</span>
            </div>
          </div>

          <!-- Context Info -->
          <div v-if="rule.triggerOfferTitle" class="mt-2 text-xs text-slate-400">
            Angebot: {{ rule.triggerOfferTitle }}
          </div>
        </div>

        <!-- Priority & Actions -->
        <div class="flex flex-col items-end gap-2 shrink-0">
          <span class="text-xs text-slate-400">Prio {{ rule.priority }}</span>
          <BButton variant="ghost" size="sm" @click.stop="handleDelete(rule)">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </BButton>
        </div>
      </div>
    </div>
  </div>
</template>
