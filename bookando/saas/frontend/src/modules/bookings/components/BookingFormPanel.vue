<script setup lang="ts">
/**
 * BookingFormPanel — Gold Standard SlideIn fuer Buchungen
 *
 * 3 Tabs:
 * - Buchung: Angebot, Kunde, Zeitpunkt, Dauer, Teilnehmer
 * - Extras & Preise: Extras, Rabatt, Preisanzeige (Minor Units!)
 * - Status: Status-Transitionen, Zahlung, Storno
 *
 * Alle Preise in Integer Minor Units (Rappen/Cents).
 */
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import {
  useBookingsStore,
  BOOKING_STATUS_LABELS,
  BOOKING_STATUS_COLORS,
  PAYMENT_STATUS_LABELS,
  isTransitionAllowed,
  getNextStatuses,
  isFinalStatus,
  formatMoney,
} from '@/stores/bookings';
import type {
  Booking,
  BookingStatus,
  BookingFormData,
} from '@/stores/bookings';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BMoneyInput from '@/components/ui/BMoneyInput.vue';
import BConfirmDialog from '@/components/ui/BConfirmDialog.vue';

const { t } = useI18n();
const toast = useToast();
const store = useBookingsStore();

const props = defineProps<{
  modelValue: boolean;
  booking?: Booking | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved', booking: Booking): void;
  (e: 'deleted', id: string): void;
}>();

// ── Form State ───────────────────────────────────────────────────────────
const saving = ref(false);
const dirty = ref(false);
const showCancelConfirm = ref(false);
const activeTab = ref('booking');

const isEditing = computed(() => !!props.booking);
const mode = computed(() => isEditing.value ? 'edit' : 'create');

const panelTitle = computed(() =>
  isEditing.value
    ? `Buchung ${props.booking?.bookingNumber || ''} bearbeiten`
    : 'Neue Buchung'
);

const tabs = computed(() => {
  const base = [
    { id: 'booking', label: 'Buchung' },
    { id: 'extras', label: 'Extras & Preise' },
  ];
  if (isEditing.value) {
    base.push({ id: 'status', label: 'Status' });
  }
  return base;
});

// ── Booking Fields ──────────────────────────────────────────────────────
const offerId = ref('');
const customerId = ref('');
const employeeId = ref('');
const scheduledAt = ref('');
const durationMinutes = ref(60);
const participantCount = ref(1);
const notes = ref('');
const errors = ref<Record<string, string>>({});

// ── Pricing Fields (Minor Units) ────────────────────────────────────────
const basePriceCents = ref(0);
const discountCents = ref(0);
const currency = ref('CHF');

// ── Cancel Fields ───────────────────────────────────────────────────────
const cancelReason = ref('');

// ── Status Transition ───────────────────────────────────────────────────
const nextStatuses = computed(() => {
  if (!props.booking) return [];
  return getNextStatuses(props.booking.status);
});

const canTransition = computed(() => nextStatuses.value.length > 0);

const totalPriceCents = computed(() => {
  return Math.max(0, basePriceCents.value - discountCents.value);
});

// ── Watch for dirty state ────────────────────────────────────────────────
watch([offerId, customerId, employeeId, scheduledAt, durationMinutes, participantCount, notes, basePriceCents, discountCents], () => {
  dirty.value = true;
}, { deep: true });

// ── Reset form when panel opens or booking changes ──────────────────────
watch(() => [props.modelValue, props.booking], () => {
  if (props.modelValue) {
    errors.value = {};
    dirty.value = false;
    activeTab.value = 'booking';

    if (props.booking && isEditing.value) {
      offerId.value = props.booking.offerId;
      customerId.value = props.booking.customerId;
      employeeId.value = props.booking.employeeId || '';
      scheduledAt.value = props.booking.scheduledAt.slice(0, 16); // datetime-local format
      durationMinutes.value = props.booking.durationMinutes;
      participantCount.value = props.booking.participantCount;
      basePriceCents.value = props.booking.basePriceCents;
      discountCents.value = props.booking.discountCents;
      currency.value = props.booking.currency;
      cancelReason.value = props.booking.cancelReason || '';
      notes.value = '';
    } else {
      offerId.value = '';
      customerId.value = '';
      employeeId.value = '';
      scheduledAt.value = '';
      durationMinutes.value = 60;
      participantCount.value = 1;
      basePriceCents.value = 0;
      discountCents.value = 0;
      currency.value = 'CHF';
      cancelReason.value = '';
      notes.value = '';
    }

    setTimeout(() => { dirty.value = false; }, 0);
  }
}, { immediate: true });

// ── Validation ───────────────────────────────────────────────────────────
function validate(): boolean {
  const errs: Record<string, string> = {};

  if (!offerId.value) errs.offerId = t('common.required');
  if (!customerId.value) errs.customerId = t('common.required');
  if (!scheduledAt.value) errs.scheduledAt = t('common.required');
  if (durationMinutes.value < 1) errs.durationMinutes = 'Mindestens 1 Minute';
  if (participantCount.value < 1) errs.participantCount = 'Mindestens 1 Teilnehmer';

  errors.value = errs;
  return Object.keys(errs).length === 0;
}

// ── Save ─────────────────────────────────────────────────────────────────
async function handleSave() {
  if (!validate()) return;

  saving.value = true;
  try {
    const payload: BookingFormData = {
      offerId: offerId.value,
      customerId: customerId.value,
      scheduledAt: new Date(scheduledAt.value).toISOString(),
      durationMinutes: durationMinutes.value,
      participantCount: participantCount.value,
      extras: [],
      resourceSelections: [],
      formResponses: {},
    };

    let saved: Booking;
    if (isEditing.value && props.booking) {
      saved = await store.updateBooking(props.booking.id, payload);
    } else {
      saved = await store.createBooking(payload);
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

// ── Status Transition ───────────────────────────────────────────────────
async function handleStatusTransition(newStatus: BookingStatus) {
  if (!props.booking) return;

  saving.value = true;
  try {
    const updated = await store.transitionStatus(props.booking.id, newStatus);
    toast.success(`Status geaendert: ${BOOKING_STATUS_LABELS[newStatus]}`);
    emit('saved', updated);
  } catch (err: unknown) {
    const message = err instanceof Error ? err.message : t('common.errorOccurred');
    toast.error(message);
  } finally {
    saving.value = false;
  }
}

// ── Cancel ───────────────────────────────────────────────────────────────
async function handleCancel() {
  if (!props.booking) return;

  saving.value = true;
  try {
    const updated = await store.cancelBooking(props.booking.id, cancelReason.value || undefined);
    toast.success('Buchung storniert');
    emit('saved', updated);
    emit('update:modelValue', false);
  } catch (err: unknown) {
    const message = err instanceof Error ? err.message : t('common.errorOccurred');
    toast.error(message);
  } finally {
    saving.value = false;
    showCancelConfirm.value = false;
  }
}

function handleClose() {
  emit('update:modelValue', false);
}

function getStatusBadgeVariant(status: BookingStatus): 'default' | 'success' | 'warning' | 'info' | 'danger' {
  const map: Record<string, 'default' | 'success' | 'warning' | 'info' | 'danger'> = {
    warning: 'warning',
    success: 'success',
    info: 'info',
    brand: 'success',
    danger: 'danger',
    default: 'default',
  };
  return map[BOOKING_STATUS_COLORS[status]] || 'default';
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
    @cancel="handleClose"
    @tab-change="(id: string) => activeTab = id"
  >
    <!-- ════════════════ TAB: Buchung ════════════════ -->
    <template v-if="activeTab === 'booking'">
      <BFormSection title="Buchungsdetails" :columns="1">
        <BInput
          v-model="offerId"
          label="Angebot-ID"
          placeholder="Angebot auswaehlen"
          :error="errors.offerId"
          required
        />
        <BInput
          v-model="customerId"
          label="Kunden-ID"
          placeholder="Kunde auswaehlen"
          :error="errors.customerId"
          required
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model="scheduledAt"
          type="datetime-local"
          label="Termin"
          :error="errors.scheduledAt"
          required
        />
        <BInput
          v-model.number="durationMinutes"
          type="number"
          label="Dauer (Minuten)"
          placeholder="60"
          :error="errors.durationMinutes"
          required
        />
      </BFormSection>

      <BFormSection :columns="2">
        <BInput
          v-model.number="participantCount"
          type="number"
          label="Teilnehmer"
          placeholder="1"
          :error="errors.participantCount"
          required
        />
        <BInput
          v-model="employeeId"
          label="Mitarbeiter-ID (optional)"
          placeholder="Mitarbeiter zuweisen"
        />
      </BFormSection>
    </template>

    <!-- ════════════════ TAB: Extras & Preise ════════════════ -->
    <template v-else-if="activeTab === 'extras'">
      <BFormSection title="Preiskalkulation" :columns="2">
        <BMoneyInput
          v-model="basePriceCents"
          label="Grundpreis"
          :currency="currency"
        />
        <BMoneyInput
          v-model="discountCents"
          label="Rabatt"
          :currency="currency"
        />
      </BFormSection>

      <!-- Price Summary -->
      <BFormSection title="Zusammenfassung" :columns="1" divided>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-slate-500">Grundpreis</span>
            <span class="font-medium">{{ formatMoney(basePriceCents, currency) }}</span>
          </div>
          <div v-if="discountCents > 0" class="flex justify-between text-emerald-600">
            <span>Rabatt</span>
            <span>-{{ formatMoney(discountCents, currency) }}</span>
          </div>
          <div class="flex justify-between pt-2 border-t border-slate-200">
            <span class="font-semibold text-slate-900">Total</span>
            <span class="font-bold text-slate-900 text-base">{{ formatMoney(totalPriceCents, currency) }}</span>
          </div>
        </div>
      </BFormSection>

      <!-- Extras List (readonly in edit mode) -->
      <BFormSection v-if="isEditing && booking && booking.extras.length > 0" title="Gebuchte Extras" :columns="1">
        <div class="space-y-2">
          <div
            v-for="extra in booking.extras"
            :key="extra.id"
            class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50"
          >
            <div>
              <span class="text-sm font-medium text-slate-700">{{ extra.name }}</span>
              <span v-if="extra.quantity > 1" class="text-xs text-slate-500 ml-1.5">x{{ extra.quantity }}</span>
            </div>
            <span class="text-sm font-medium text-slate-900">{{ formatMoney(extra.priceCents * extra.quantity, currency) }}</span>
          </div>
        </div>
      </BFormSection>
    </template>

    <!-- ════════════════ TAB: Status ════════════════ -->
    <template v-else-if="activeTab === 'status' && isEditing && booking">
      <!-- Current Status -->
      <BFormSection title="Aktueller Status" :columns="1">
        <div class="flex items-center gap-3">
          <BBadge :variant="getStatusBadgeVariant(booking.status)" size="lg">
            {{ BOOKING_STATUS_LABELS[booking.status] }}
          </BBadge>
          <BBadge
            :variant="booking.paymentStatus === 'PAID' ? 'success' : booking.paymentStatus === 'PARTIALLY_PAID' ? 'warning' : 'default'"
          >
            {{ PAYMENT_STATUS_LABELS[booking.paymentStatus] }}
          </BBadge>
        </div>
      </BFormSection>

      <!-- Status Transitions -->
      <BFormSection v-if="canTransition" title="Status aendern" :columns="1">
        <p class="text-xs text-slate-500 mb-3">Waehlen Sie den neuen Status fuer diese Buchung:</p>
        <div class="flex flex-wrap gap-2">
          <BButton
            v-for="status in nextStatuses"
            :key="status"
            :variant="status === 'CANCELLED' ? 'danger' : 'secondary'"
            size="sm"
            :disabled="saving"
            @click="status === 'CANCELLED' ? (showCancelConfirm = true) : handleStatusTransition(status)"
          >
            {{ BOOKING_STATUS_LABELS[status] }}
          </BButton>
        </div>
      </BFormSection>

      <BFormSection v-else title="Status" :columns="1">
        <p class="text-sm text-slate-500">
          Diese Buchung hat einen finalen Status erreicht. Keine weiteren Uebergaenge moeglich.
        </p>
      </BFormSection>

      <!-- Timestamps -->
      <BFormSection title="Zeitstempel" :columns="2" divided>
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-0.5">Erstellt</label>
          <span class="text-sm text-slate-700">{{ new Date(booking.createdAt).toLocaleString('de-CH') }}</span>
        </div>
        <div v-if="booking.confirmedAt">
          <label class="block text-xs font-medium text-slate-500 mb-0.5">Bestaetigt</label>
          <span class="text-sm text-slate-700">{{ new Date(booking.confirmedAt).toLocaleString('de-CH') }}</span>
        </div>
        <div v-if="booking.paidAt">
          <label class="block text-xs font-medium text-slate-500 mb-0.5">Bezahlt</label>
          <span class="text-sm text-slate-700">{{ new Date(booking.paidAt).toLocaleString('de-CH') }}</span>
        </div>
        <div v-if="booking.completedAt">
          <label class="block text-xs font-medium text-slate-500 mb-0.5">Abgeschlossen</label>
          <span class="text-sm text-slate-700">{{ new Date(booking.completedAt).toLocaleString('de-CH') }}</span>
        </div>
        <div v-if="booking.cancelledAt">
          <label class="block text-xs font-medium text-slate-500 mb-0.5">Storniert</label>
          <span class="text-sm text-slate-700">{{ new Date(booking.cancelledAt).toLocaleString('de-CH') }}</span>
        </div>
      </BFormSection>

      <!-- Cancel Reason (if cancelled) -->
      <BFormSection v-if="booking.status === 'CANCELLED' && booking.cancelReason" title="Storno-Grund" :columns="1">
        <p class="text-sm text-slate-700 bg-red-50 p-3 rounded-lg border border-red-100">
          {{ booking.cancelReason }}
        </p>
      </BFormSection>

      <!-- Resource Reservations -->
      <BFormSection v-if="booking.resourceReservations.length > 0" title="Ressourcen-Reservierungen" :columns="1">
        <div class="space-y-2">
          <div
            v-for="res in booking.resourceReservations"
            :key="res.id"
            class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50"
          >
            <div>
              <span class="text-sm font-medium text-slate-700">{{ res.resourceName }}</span>
              <span class="text-xs text-slate-400 ml-1.5">({{ res.resourceType }})</span>
            </div>
            <BBadge
              :variant="res.status === 'CONFIRMED' ? 'success' : res.status === 'TENTATIVE' ? 'warning' : 'default'"
              size="sm"
            >
              {{ res.status }}
            </BBadge>
          </div>
        </div>
      </BFormSection>
    </template>
  </BFormPanel>

  <!-- Cancel Confirmation -->
  <BConfirmDialog
    v-model="showCancelConfirm"
    title="Buchung stornieren"
    message="Moechten Sie diese Buchung wirklich stornieren? Diese Aktion kann nicht rueckgaengig gemacht werden."
    confirm-variant="danger"
    confirm-label="Stornieren"
    @confirm="handleCancel"
  >
    <template #body>
      <div class="mt-3">
        <BTextarea
          v-model="cancelReason"
          label="Storno-Grund (optional)"
          placeholder="Grund fuer die Stornierung..."
          :rows="3"
        />
      </div>
    </template>
  </BConfirmDialog>
</template>
