<script setup lang="ts">
/**
 * NotificationsTab -- Benachrichtigungsvorlagen verwalten
 *
 * Features:
 * - Vorlagen-Tabelle mit Kanal-Badges (Email/SMS/WhatsApp/Push)
 * - Template-Editor-Modal mit Platzhalter-Tags
 * - Vorschau der gerenderten Vorlage
 * - Test-Sende-Funktion
 */
import { ref, computed } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BTable from '@/components/ui/BTable.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BModal from '@/components/ui/BModal.vue';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useI18n } from '@/composables/useI18n';
import { BUTTON_STYLES, CARD_STYLES, BADGE_STYLES, TABLE_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const { isMobile } = useBreakpoint();

// Notification Template
interface NotificationTemplate {
  id: string;
  name: string;
  channel: 'email' | 'sms' | 'whatsapp' | 'push';
  trigger: string;
  subject?: string;
  body: string;
  status: 'active' | 'draft' | 'inactive';
}

const templates = ref<NotificationTemplate[]>([
  {
    id: 'tpl-1',
    name: t('tools.notifications.appointmentConfirmation') || 'Terminbestaetigung',
    channel: 'email',
    trigger: 'appointment.confirmed',
    subject: t('tools.notifications.appointmentConfirmationSubject') || 'Ihr Termin bei {company_name}',
    body: 'Liebe/r {customer_name},\n\nIhr Termin am {appointment_date} um {appointment_time} wurde bestaetigt.\n\nDienstleistung: {service_name}\nMitarbeiter: {employee_name}\n\nFreundliche Gruesse,\n{company_name}',
    status: 'active',
  },
  {
    id: 'tpl-2',
    name: t('tools.notifications.appointmentReminder') || 'Terminerinnerung',
    channel: 'sms',
    trigger: 'appointment.reminder_24h',
    body: '{customer_name}, Erinnerung: Ihr Termin morgen um {appointment_time} bei {company_name}. Bei Fragen: {company_phone}',
    status: 'active',
  },
  {
    id: 'tpl-3',
    name: t('tools.notifications.cancellationNotice') || 'Stornierungsbestaetigung',
    channel: 'email',
    trigger: 'appointment.cancelled',
    subject: t('tools.notifications.cancellationSubject') || 'Termin storniert - {company_name}',
    body: 'Liebe/r {customer_name},\n\nIhr Termin am {appointment_date} wurde storniert.\n\nBitte buchen Sie bei Bedarf einen neuen Termin.\n\nFreundliche Gruesse,\n{company_name}',
    status: 'active',
  },
  {
    id: 'tpl-4',
    name: t('tools.notifications.welcomeMessage') || 'Willkommensnachricht',
    channel: 'whatsapp',
    trigger: 'customer.created',
    body: 'Willkommen bei {company_name}, {customer_name}! Wir freuen uns, Sie als Kunden begruessen zu duerfen. Buchen Sie Ihren ersten Termin: {booking_link}',
    status: 'active',
  },
  {
    id: 'tpl-5',
    name: t('tools.notifications.paymentConfirmation') || 'Zahlungsbestaetigung',
    channel: 'push',
    trigger: 'payment.completed',
    body: 'Zahlung von {amount} CHF erhalten. Vielen Dank, {customer_name}!',
    status: 'draft',
  },
]);

// Editor state
const isEditorOpen = ref(false);
const editingTemplate = ref<NotificationTemplate | null>(null);
const editorForm = ref({
  name: '',
  channel: 'email' as NotificationTemplate['channel'],
  trigger: '',
  subject: '',
  body: '',
});

const showPreview = ref(false);

const templateColumns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'channel', label: t('tools.notifications.channel') || 'Kanal' },
  { key: 'trigger', label: t('tools.notifications.trigger') || 'Ausloeser' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', width: '140px', align: 'right' as const },
];

const channelOptions = [
  { value: 'email', label: 'E-Mail' },
  { value: 'sms', label: 'SMS' },
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'push', label: 'Push' },
];

const triggerOptions = [
  { value: 'appointment.confirmed', label: t('tools.notifications.triggerConfirmed') || 'Termin bestaetigt' },
  { value: 'appointment.reminder_24h', label: t('tools.notifications.triggerReminder') || 'Terminerinnerung (24h)' },
  { value: 'appointment.cancelled', label: t('tools.notifications.triggerCancelled') || 'Termin storniert' },
  { value: 'customer.created', label: t('tools.notifications.triggerCustomerCreated') || 'Neuer Kunde' },
  { value: 'payment.completed', label: t('tools.notifications.triggerPayment') || 'Zahlung abgeschlossen' },
  { value: 'invoice.created', label: t('tools.notifications.triggerInvoice') || 'Rechnung erstellt' },
];

const placeholderTags = [
  '{customer_name}',
  '{appointment_date}',
  '{appointment_time}',
  '{service_name}',
  '{employee_name}',
  '{company_name}',
  '{company_phone}',
  '{booking_link}',
  '{amount}',
];

const channelConfig: Record<string, { icon: string; color: string; label: string }> = {
  email: { icon: 'email', color: 'info', label: 'E-Mail' },
  sms: { icon: 'sms', color: 'success', label: 'SMS' },
  whatsapp: { icon: 'whatsapp', color: 'success', label: 'WhatsApp' },
  push: { icon: 'push', color: 'purple', label: 'Push' },
};

const triggerLabels: Record<string, string> = {
  'appointment.confirmed': t('tools.notifications.triggerConfirmed') || 'Termin bestaetigt',
  'appointment.reminder_24h': t('tools.notifications.triggerReminder') || 'Erinnerung (24h)',
  'appointment.cancelled': t('tools.notifications.triggerCancelled') || 'Termin storniert',
  'customer.created': t('tools.notifications.triggerCustomerCreated') || 'Neuer Kunde',
  'payment.completed': t('tools.notifications.triggerPayment') || 'Zahlung abgeschlossen',
  'invoice.created': t('tools.notifications.triggerInvoice') || 'Rechnung erstellt',
};

// Preview rendering
const previewBody = computed(() => {
  const sampleData: Record<string, string> = {
    '{customer_name}': 'Max Muster',
    '{appointment_date}': '15. Januar 2026',
    '{appointment_time}': '14:30',
    '{service_name}': 'Herrenhaarschnitt',
    '{employee_name}': 'Lisa Weber',
    '{company_name}': 'Salon Bookando',
    '{company_phone}': '+41 44 123 45 67',
    '{booking_link}': 'https://bookando.ch/buchen',
    '{amount}': '65.00',
  };

  let rendered = editorForm.value.body;
  for (const [tag, value] of Object.entries(sampleData)) {
    rendered = rendered.replaceAll(tag, value);
  }
  return rendered;
});

function openCreateTemplate() {
  editingTemplate.value = null;
  editorForm.value = {
    name: '',
    channel: 'email',
    trigger: '',
    subject: '',
    body: '',
  };
  showPreview.value = false;
  isEditorOpen.value = true;
}

function openEditTemplate(template: NotificationTemplate) {
  editingTemplate.value = template;
  editorForm.value = {
    name: template.name,
    channel: template.channel,
    trigger: template.trigger,
    subject: template.subject || '',
    body: template.body,
  };
  showPreview.value = false;
  isEditorOpen.value = true;
}

function saveTemplate() {
  if (editingTemplate.value) {
    const idx = templates.value.findIndex(tpl => tpl.id === editingTemplate.value!.id);
    if (idx !== -1) {
      templates.value[idx] = {
        ...templates.value[idx],
        ...editorForm.value,
      };
    }
    toast.success(t('tools.notifications.templateUpdated') || 'Vorlage aktualisiert');
  } else {
    templates.value.push({
      id: `tpl-${Date.now()}`,
      ...editorForm.value,
      status: 'draft',
    });
    toast.success(t('tools.notifications.templateCreated') || 'Vorlage erstellt');
  }
  isEditorOpen.value = false;
}

function testSend() {
  toast.info(t('tools.notifications.testSent') || 'Test-Nachricht wird gesendet...');
}

function insertTag(tag: string) {
  editorForm.value.body += tag;
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-slate-900">{{ t('tools.notifications.title') || 'Benachrichtigungsvorlagen' }}</h3>
        <p class="text-xs text-slate-500">{{ t('tools.notifications.description') || 'Verwalten Sie automatische Benachrichtigungen an Ihre Kunden' }}</p>
      </div>
      <BButton variant="primary" size="sm" @click="openCreateTemplate">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('tools.notifications.createTemplate') || 'Neue Vorlage' }}
      </BButton>
    </div>

    <!-- Templates Table -->
    <BTable
      :columns="templateColumns"
      :data="templates as unknown as Record<string, unknown>[]"
      :empty-title="t('tools.notifications.noTemplates') || 'Keine Vorlagen'"
      :empty-message="t('tools.notifications.noTemplatesDesc') || 'Erstellen Sie Ihre erste Benachrichtigungsvorlage'"
    >
      <template #cell-name="{ row }">
        <span class="text-sm font-medium text-slate-900">{{ (row as any).name }}</span>
      </template>

      <template #cell-channel="{ row }">
        <BBadge :variant="(channelConfig[(row as any).channel] || channelConfig.email).color as any">
          <!-- Channel Icons -->
          <svg v-if="(row as any).channel === 'email'" class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <svg v-else-if="(row as any).channel === 'sms'" class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
          </svg>
          <svg v-else-if="(row as any).channel === 'whatsapp'" class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
          <svg v-else-if="(row as any).channel === 'push'" class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          {{ (channelConfig[(row as any).channel] || channelConfig.email).label }}
        </BBadge>
      </template>

      <template #cell-trigger="{ row }">
        <span class="text-xs text-slate-500">{{ triggerLabels[(row as any).trigger] || (row as any).trigger }}</span>
      </template>

      <template #cell-status="{ row }">
        <BBadge
          :variant="(row as any).status === 'active' ? 'success' : (row as any).status === 'draft' ? 'warning' : 'default'"
          dot
        >
          {{ (row as any).status === 'active'
            ? (t('tools.notifications.active') || 'Aktiv')
            : (row as any).status === 'draft'
              ? (t('tools.notifications.draft') || 'Entwurf')
              : (t('tools.notifications.inactive') || 'Inaktiv')
          }}
        </BBadge>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <button
            :class="BUTTON_STYLES.icon"
            :title="t('common.edit') || 'Bearbeiten'"
            @click.stop="openEditTemplate(row as unknown as NotificationTemplate)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            :class="BUTTON_STYLES.icon"
            :title="t('tools.notifications.testSend') || 'Test senden'"
            @click.stop="testSend"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </div>
      </template>
    </BTable>

    <!-- Template Editor Modal -->
    <BModal
      v-model="isEditorOpen"
      :title="editingTemplate
        ? (t('tools.notifications.editTemplate') || 'Vorlage bearbeiten')
        : (t('tools.notifications.createTemplate') || 'Neue Vorlage')"
      size="xl"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BInput
            v-model="editorForm.name"
            :label="t('tools.notifications.templateName') || 'Name'"
            :placeholder="t('tools.notifications.templateNamePlaceholder') || 'z.B. Terminbestaetigung'"
            required
          />
          <BSelect
            v-model="editorForm.channel"
            :options="channelOptions"
            :label="t('tools.notifications.channel') || 'Kanal'"
            required
          />
        </div>

        <BSelect
          v-model="editorForm.trigger"
          :options="triggerOptions"
          :label="t('tools.notifications.triggerEvent') || 'Ausloeser-Ereignis'"
          :placeholder="t('tools.notifications.selectTrigger') || 'Ereignis auswaehlen...'"
          required
        />

        <BInput
          v-if="editorForm.channel === 'email'"
          v-model="editorForm.subject"
          :label="t('tools.notifications.subject') || 'Betreff'"
          :placeholder="t('tools.notifications.subjectPlaceholder') || 'z.B. Ihr Termin bei {company_name}'"
        />

        <!-- Placeholder Tags -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">
            {{ t('tools.notifications.placeholders') || 'Platzhalter' }}
          </label>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="tag in placeholderTags"
              :key="tag"
              class="px-2 py-1 text-[10px] font-mono bg-slate-100 hover:bg-slate-200 text-slate-600 rounded transition-colors"
              @click="insertTag(tag)"
            >
              {{ tag }}
            </button>
          </div>
        </div>

        <BTextarea
          v-model="editorForm.body"
          :label="t('tools.notifications.body') || 'Nachrichtentext'"
          :placeholder="t('tools.notifications.bodyPlaceholder') || 'Nachricht eingeben...'"
          :rows="6"
          required
        />

        <!-- Preview Toggle -->
        <div class="flex items-center gap-2">
          <button
            :class="[
              'text-xs font-medium px-3 py-1.5 rounded-lg transition-colors',
              showPreview ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
            ]"
            @click="showPreview = !showPreview"
          >
            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            {{ t('tools.notifications.preview') || 'Vorschau' }}
          </button>
        </div>

        <!-- Preview Area -->
        <div v-if="showPreview" class="bg-slate-50 border border-slate-200 rounded-lg p-4">
          <div class="text-xs text-slate-400 mb-2">{{ t('tools.notifications.previewLabel') || 'Vorschau (mit Beispieldaten)' }}</div>
          <div v-if="editorForm.channel === 'email' && editorForm.subject" class="text-sm font-semibold text-slate-900 mb-2 pb-2 border-b border-slate-200">
            {{ editorForm.subject.replaceAll('{company_name}', 'Salon Bookando') }}
          </div>
          <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ previewBody }}</div>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center gap-2 w-full justify-between">
          <BButton variant="ghost" size="sm" @click="testSend">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            {{ t('tools.notifications.testSend') || 'Test senden' }}
          </BButton>
          <div class="flex items-center gap-2">
            <BButton variant="secondary" @click="isEditorOpen = false">
              {{ t('common.cancel') || 'Abbrechen' }}
            </BButton>
            <BButton variant="primary" @click="saveTemplate">
              {{ t('common.save') || 'Speichern' }}
            </BButton>
          </div>
        </div>
      </template>
    </BModal>
  </div>
</template>
