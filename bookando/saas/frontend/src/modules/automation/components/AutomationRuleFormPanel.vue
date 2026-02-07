<script setup lang="ts">
/**
 * AutomationRuleFormPanel — Gold Standard SlideIn fuer Automations-Regeln
 *
 * 2 Tabs:
 * - Trigger: Event-Auswahl, Bedingungen, Angebot/Kategorie-Filter
 * - Action: Aktionstyp, typ-spezifische Konfiguration, Optionen
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import {
  useAutomationStore,
  TRIGGER_EVENT_LABELS,
  ACTION_TYPE_LABELS,
} from '@/stores/automation';
import type {
  AutomationRule,
  AutomationRuleFormData,
  TriggerEvent,
  ActionType,
} from '@/stores/automation';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BButton from '@/components/ui/BButton.vue';
import BConfirmDialog from '@/components/ui/BConfirmDialog.vue';

const { t } = useI18n();
const toast = useToast();
const store = useAutomationStore();

const props = defineProps<{
  modelValue: boolean;
  rule?: AutomationRule | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved', rule: AutomationRule): void;
  (e: 'deleted', id: string): void;
}>();

// ── Form State ───────────────────────────────────────────────────────────
const saving = ref(false);
const dirty = ref(false);
const showDeleteConfirm = ref(false);
const activeTab = ref('trigger');

const isEditing = computed(() => !!props.rule);
const mode = computed(() => isEditing.value ? 'edit' : 'create');

const panelTitle = computed(() =>
  isEditing.value ? 'Regel bearbeiten' : 'Neue Regel'
);

const tabs = [
  { id: 'trigger', label: 'Trigger' },
  { id: 'action', label: 'Aktion' },
];

// ── Common Fields ────────────────────────────────────────────────────────
const name = ref('');
const description = ref('');
const active = ref(true);
const priority = ref(10);
const allowDuplicate = ref(false);
const errors = ref<Record<string, string>>({});

// ── Trigger Fields ──────────────────────────────────────────────────────
const triggerEvent = ref<TriggerEvent>('BOOKING_CONFIRMED');
const triggerOfferId = ref('');
const triggerCategoryId = ref('');
const condSessionNumber = ref<number | undefined>(undefined);
const condMinBookingCount = ref<number | undefined>(undefined);
const condCustomerTag = ref('');

// ── Action Fields ───────────────────────────────────────────────────────
const actionType = ref<ActionType>('ASSIGN_TRAINING_CARD');

// Action config fields (flat, mapped to ActionConfig on save)
const trainingCardTemplateId = ref('');
const onlineCourseOfferId = ref('');
const accessDurationDays = ref<number | undefined>(undefined);
const badgeId = ref('');
const notifChannel = ref<'EMAIL' | 'SMS' | 'PUSH'>('EMAIL');
const notifSubject = ref('');
const notifMessage = ref('');
const academyCourseId = ref('');

// ── Options ──────────────────────────────────────────────────────────────
const triggerEventOptions = Object.entries(TRIGGER_EVENT_LABELS).map(([v, l]) => ({ value: v, label: l }));
const actionTypeOptions = Object.entries(ACTION_TYPE_LABELS).map(([v, l]) => ({ value: v, label: l }));
const channelOptions = [
  { value: 'EMAIL', label: 'E-Mail' },
  { value: 'SMS', label: 'SMS' },
  { value: 'PUSH', label: 'Push-Benachrichtigung' },
];

// ── Watch for dirty state ────────────────────────────────────────────────
watch([name, description, active, priority, allowDuplicate, triggerEvent, triggerOfferId, triggerCategoryId, condSessionNumber, condMinBookingCount, condCustomerTag, actionType, trainingCardTemplateId, onlineCourseOfferId, accessDurationDays, badgeId, notifChannel, notifSubject, notifMessage, academyCourseId], () => {
  dirty.value = true;
}, { deep: true });

// ── Reset form when panel opens ──────────────────────────────────────────
watch(() => [props.modelValue, props.rule], () => {
  if (props.modelValue) {
    errors.value = {};
    dirty.value = false;
    activeTab.value = 'trigger';

    if (props.rule && isEditing.value) {
      name.value = props.rule.name;
      description.value = props.rule.description || '';
      active.value = props.rule.active;
      priority.value = props.rule.priority;
      allowDuplicate.value = props.rule.allowDuplicate;

      triggerEvent.value = props.rule.triggerEvent;
      triggerOfferId.value = props.rule.triggerOfferId || '';
      triggerCategoryId.value = props.rule.triggerCategoryId || '';
      condSessionNumber.value = props.rule.triggerCondition?.sessionNumber;
      condMinBookingCount.value = props.rule.triggerCondition?.minBookingCount;
      condCustomerTag.value = props.rule.triggerCondition?.customerTag || '';

      actionType.value = props.rule.actionType;

      // Populate action config fields
      const cfg = props.rule.actionConfig as Record<string, unknown>;
      trainingCardTemplateId.value = (cfg.trainingCardTemplateId as string) || '';
      onlineCourseOfferId.value = (cfg.onlineCourseOfferId as string) || '';
      accessDurationDays.value = cfg.accessDurationDays as number | undefined;
      badgeId.value = (cfg.badgeId as string) || '';
      notifChannel.value = (cfg.channel as 'EMAIL' | 'SMS' | 'PUSH') || 'EMAIL';
      notifSubject.value = (cfg.subject as string) || '';
      notifMessage.value = (cfg.message as string) || '';
      academyCourseId.value = (cfg.academyCourseId as string) || '';
    } else {
      name.value = '';
      description.value = '';
      active.value = true;
      priority.value = 10;
      allowDuplicate.value = false;

      triggerEvent.value = 'BOOKING_CONFIRMED';
      triggerOfferId.value = '';
      triggerCategoryId.value = '';
      condSessionNumber.value = undefined;
      condMinBookingCount.value = undefined;
      condCustomerTag.value = '';

      actionType.value = 'ASSIGN_TRAINING_CARD';
      trainingCardTemplateId.value = '';
      onlineCourseOfferId.value = '';
      accessDurationDays.value = undefined;
      badgeId.value = '';
      notifChannel.value = 'EMAIL';
      notifSubject.value = '';
      notifMessage.value = '';
      academyCourseId.value = '';
    }

    setTimeout(() => { dirty.value = false; }, 0);
  }
}, { immediate: true });

// ── Validation ──────────────────────────────────────────────────────────
function validate(): boolean {
  const errs: Record<string, string> = {};

  if (!name.value.trim()) errs.name = t('common.required');

  // Validate action config
  switch (actionType.value) {
    case 'ASSIGN_TRAINING_CARD':
      if (!trainingCardTemplateId.value) errs.trainingCardTemplateId = t('common.required');
      break;
    case 'ASSIGN_ONLINE_COURSE':
      if (!onlineCourseOfferId.value) errs.onlineCourseOfferId = t('common.required');
      break;
    case 'GRANT_BADGE':
      if (!badgeId.value) errs.badgeId = t('common.required');
      break;
    case 'SEND_NOTIFICATION':
      if (!notifMessage.value.trim()) errs.notifMessage = t('common.required');
      break;
    case 'ENROLL_IN_COURSE':
      if (!academyCourseId.value) errs.academyCourseId = t('common.required');
      break;
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
}

// ── Build Action Config ─────────────────────────────────────────────────
function buildActionConfig(): Record<string, unknown> {
  switch (actionType.value) {
    case 'ASSIGN_TRAINING_CARD':
      return { trainingCardTemplateId: trainingCardTemplateId.value };
    case 'ASSIGN_ONLINE_COURSE':
      return {
        onlineCourseOfferId: onlineCourseOfferId.value,
        accessDurationDays: accessDurationDays.value,
      };
    case 'GRANT_BADGE':
      return { badgeId: badgeId.value };
    case 'SEND_NOTIFICATION':
      return {
        channel: notifChannel.value,
        subject: notifSubject.value || undefined,
        message: notifMessage.value,
      };
    case 'ENROLL_IN_COURSE':
      return { academyCourseId: academyCourseId.value };
    default:
      return {};
  }
}

// ── Save ─────────────────────────────────────────────────────────────────
async function handleSave() {
  if (!validate()) return;

  saving.value = true;
  try {
    const payload: AutomationRuleFormData = {
      name: name.value.trim(),
      description: description.value.trim() || undefined,
      active: active.value,
      triggerEvent: triggerEvent.value,
      triggerOfferId: triggerOfferId.value || undefined,
      triggerCategoryId: triggerCategoryId.value || undefined,
      triggerCondition: (condSessionNumber.value || condMinBookingCount.value || condCustomerTag.value) ? {
        sessionNumber: condSessionNumber.value,
        minBookingCount: condMinBookingCount.value,
        customerTag: condCustomerTag.value || undefined,
      } : undefined,
      actionType: actionType.value,
      actionConfig: buildActionConfig() as AutomationRuleFormData['actionConfig'],
      allowDuplicate: allowDuplicate.value,
      priority: priority.value,
    };

    let saved: AutomationRule;
    if (isEditing.value && props.rule) {
      saved = await store.updateRule(props.rule.id, payload);
    } else {
      saved = await store.createRule(payload);
    }

    toast.success(t('common.savedSuccessfully'));
    dirty.value = false;
    emit('saved', saved);
    emit('update:modelValue', false);
  } catch {
    toast.error(t('common.errorOccurred'));
  } finally {
    saving.value = false;
  }
}

// ── Delete ──────────────────────────────────────────────────────────────
async function handleDelete() {
  if (!props.rule) return;
  try {
    await store.deleteRule(props.rule.id);
    toast.success(t('common.deletedSuccessfully'));
    emit('deleted', props.rule.id);
    emit('update:modelValue', false);
  } catch {
    toast.error(t('common.errorOccurred'));
  }
  showDeleteConfirm.value = false;
}

function handleCancel() {
  emit('update:modelValue', false);
}
</script>

<template>
  <BFormPanel
    :model-value="modelValue"
    :title="panelTitle"
    :mode="mode"
    size="lg"
    :saving="saving"
    :dirty="dirty"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:model-value="$emit('update:modelValue', $event)"
    @save="handleSave"
    @cancel="handleCancel"
    @tab-change="(id: string) => activeTab = id"
  >
    <!-- ════════════════ TAB: Trigger ════════════════ -->
    <template v-if="activeTab === 'trigger'">
      <BFormSection title="Regel-Grunddaten" :columns="1">
        <BInput
          v-model="name"
          label="Name"
          placeholder="z.B. Ausbildungskarte bei erster Buchung"
          :error="errors.name"
          required
        />
        <BTextarea
          v-model="description"
          label="Beschreibung (optional)"
          placeholder="Was macht diese Regel?"
          :rows="2"
        />
      </BFormSection>

      <BFormSection title="Trigger-Event" :columns="1">
        <BSelect
          v-model="triggerEvent"
          label="Ausloesender Event"
          :options="triggerEventOptions"
        />
      </BFormSection>

      <BFormSection title="Filter (optional)" :columns="2">
        <BInput
          v-model="triggerOfferId"
          label="Angebot-ID"
          placeholder="Nur fuer bestimmtes Angebot"
        />
        <BInput
          v-model="triggerCategoryId"
          label="Kategorie-ID"
          placeholder="Nur fuer bestimmte Kategorie"
        />
      </BFormSection>

      <BFormSection title="Erweiterte Bedingungen (optional)" :columns="2">
        <BInput
          v-model.number="condSessionNumber"
          type="number"
          label="Session-Nummer"
          placeholder="z.B. 1 fuer erste Session"
        />
        <BInput
          v-model.number="condMinBookingCount"
          type="number"
          label="Min. Buchungsanzahl"
          placeholder="z.B. 3"
        />
        <BInput
          v-model="condCustomerTag"
          label="Kunden-Tag"
          placeholder="z.B. VIP"
        />
      </BFormSection>

      <BFormSection title="Optionen" :columns="2">
        <BInput
          v-model.number="priority"
          type="number"
          label="Prioritaet"
          placeholder="10"
        />
        <BSelect
          v-model="active"
          label="Status"
          :options="[{ value: true, label: 'Aktiv' }, { value: false, label: 'Inaktiv' }]"
        />
      </BFormSection>
    </template>

    <!-- ════════════════ TAB: Action ════════════════ -->
    <template v-else-if="activeTab === 'action'">
      <BFormSection title="Aktionstyp" :columns="1">
        <BSelect
          v-model="actionType"
          label="Aktion"
          :options="actionTypeOptions"
        />
      </BFormSection>

      <!-- ASSIGN_TRAINING_CARD -->
      <BFormSection v-if="actionType === 'ASSIGN_TRAINING_CARD'" title="Ausbildungskarte zuweisen" :columns="1">
        <BInput
          v-model="trainingCardTemplateId"
          label="Vorlagen-ID"
          placeholder="ID der Ausbildungskarten-Vorlage"
          :error="errors.trainingCardTemplateId"
          required
        />
      </BFormSection>

      <!-- ASSIGN_ONLINE_COURSE -->
      <BFormSection v-else-if="actionType === 'ASSIGN_ONLINE_COURSE'" title="Onlinekurs freischalten" :columns="1">
        <BInput
          v-model="onlineCourseOfferId"
          label="Onlinekurs-Angebot-ID"
          placeholder="ID des Onlinekurs-Angebots"
          :error="errors.onlineCourseOfferId"
          required
        />
        <BInput
          v-model.number="accessDurationDays"
          type="number"
          label="Zugangs-Dauer (Tage, optional)"
          placeholder="z.B. 90"
        />
      </BFormSection>

      <!-- GRANT_BADGE -->
      <BFormSection v-else-if="actionType === 'GRANT_BADGE'" title="Badge vergeben" :columns="1">
        <BInput
          v-model="badgeId"
          label="Badge-ID"
          placeholder="ID des zu vergebenden Badges"
          :error="errors.badgeId"
          required
        />
      </BFormSection>

      <!-- SEND_NOTIFICATION -->
      <BFormSection v-else-if="actionType === 'SEND_NOTIFICATION'" title="Benachrichtigung senden" :columns="1">
        <BSelect
          v-model="notifChannel"
          label="Kanal"
          :options="channelOptions"
        />
        <BInput
          v-model="notifSubject"
          label="Betreff (optional)"
          placeholder="Betreff der Nachricht"
        />
        <BTextarea
          v-model="notifMessage"
          label="Nachricht"
          placeholder="Text der Benachrichtigung..."
          :rows="4"
          :error="errors.notifMessage"
          required
        />
      </BFormSection>

      <!-- ENROLL_IN_COURSE -->
      <BFormSection v-else-if="actionType === 'ENROLL_IN_COURSE'" title="In Kurs einschreiben" :columns="1">
        <BInput
          v-model="academyCourseId"
          label="Academy-Kurs-ID"
          placeholder="ID des Kurses"
          :error="errors.academyCourseId"
          required
        />
      </BFormSection>

      <!-- Duplicate Control -->
      <BFormSection title="Duplikat-Kontrolle" :columns="1" divided>
        <BSelect
          v-model="allowDuplicate"
          label="Mehrfach-Ausfuehrung"
          :options="[{ value: false, label: 'Nur einmal pro Kunde/Buchung' }, { value: true, label: 'Bei jedem Trigger erneut ausfuehren' }]"
        />
      </BFormSection>
    </template>

    <!-- ────────────── FOOTER: DELETE BUTTON ────────────── -->
    <template v-if="isEditing" #footer-left>
      <BButton
        variant="ghost"
        class="text-red-600 hover:text-red-700 hover:bg-red-50"
        @click="showDeleteConfirm = true"
      >
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        {{ t('common.delete') }}
      </BButton>
    </template>
  </BFormPanel>

  <!-- Delete Confirmation -->
  <BConfirmDialog
    v-model="showDeleteConfirm"
    :title="t('common.confirmDelete')"
    :message="`Regel '${name}' wirklich loeschen?`"
    confirm-variant="danger"
    :confirm-label="t('common.delete')"
    @confirm="handleDelete"
  />
</template>
