<script setup lang="ts">
/**
 * PortalBookingsTab — Kundenseitige Buchungsuebersicht
 *
 * Zeigt dem Kunden seine Buchungen mit Status, Preis, Termin.
 * Storno-Moeglichkeit fuer nicht-finale Buchungen.
 */
import { ref, computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BConfirmDialog from '@/components/ui/BConfirmDialog.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import {
  useBookingsStore,
  BOOKING_STATUS_LABELS,
  BOOKING_STATUS_COLORS,
  PAYMENT_STATUS_LABELS,
  isFinalStatus,
  formatMoney,
} from '@/stores/bookings';
import type { Booking, BookingStatus } from '@/stores/bookings';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const store = useBookingsStore();

const searchQuery = ref('');
const filterStatus = ref<BookingStatus | ''>('');
const showCancelConfirm = ref(false);
const cancellingBooking = ref<Booking | null>(null);

const statusOptions = [
  { value: '', label: 'Alle Status' },
  ...Object.entries(BOOKING_STATUS_LABELS).map(([v, l]) => ({ value: v, label: l })),
];

const filteredBookings = computed(() => {
  let result = [...store.bookings];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(b =>
      b.bookingNumber.toLowerCase().includes(q) ||
      (b.offerTitle || '').toLowerCase().includes(q)
    );
  }

  if (filterStatus.value) {
    result = result.filter(b => b.status === filterStatus.value);
  }

  return result.sort((a, b) => new Date(b.scheduledAt).getTime() - new Date(a.scheduledAt).getTime());
});

const upcomingBookings = computed(() => {
  const now = new Date().getTime();
  return store.bookings
    .filter(b => new Date(b.scheduledAt).getTime() > now && !isFinalStatus(b.status))
    .sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime());
});

function getStatusBadgeVariant(status: BookingStatus): 'default' | 'success' | 'warning' | 'info' | 'danger' {
  const map: Record<string, 'default' | 'success' | 'warning' | 'info' | 'danger'> = {
    warning: 'warning', success: 'success', info: 'info', brand: 'success', danger: 'danger', default: 'default',
  };
  return map[BOOKING_STATUS_COLORS[status]] || 'default';
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('de-CH', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' });
}

function initiateCancel(booking: Booking) {
  cancellingBooking.value = booking;
  showCancelConfirm.value = true;
}

async function handleCancel() {
  if (!cancellingBooking.value) return;
  try {
    await store.cancelBooking(cancellingBooking.value.id);
    toast.success('Buchung storniert');
  } catch {
    toast.error(t('common.errorOccurred'));
  }
  showCancelConfirm.value = false;
  cancellingBooking.value = null;
}
</script>

<template>
  <div>
    <!-- Upcoming Bookings Highlight -->
    <div v-if="upcomingBookings.length > 0" class="mb-6">
      <h3 class="text-sm font-semibold text-slate-900 mb-3">Naechste Termine</h3>
      <div class="space-y-2">
        <div
          v-for="booking in upcomingBookings.slice(0, 3)"
          :key="booking.id"
          class="flex items-center gap-4 p-3 rounded-xl bg-gradient-to-r from-brand-50 to-cyan-50 border border-brand-100"
        >
          <div class="w-12 h-12 rounded-lg bg-brand-100 text-brand-600 flex flex-col items-center justify-center shrink-0">
            <span class="text-xs font-bold leading-none">{{ new Date(booking.scheduledAt).getDate() }}</span>
            <span class="text-[10px] leading-none mt-0.5">{{ new Date(booking.scheduledAt).toLocaleDateString('de-CH', { month: 'short' }) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-medium text-slate-900 truncate">{{ booking.offerTitle || 'Buchung' }}</h4>
            <p class="text-xs text-slate-500">{{ formatTime(booking.scheduledAt) }} · {{ booking.durationMinutes }} Min.</p>
          </div>
          <BBadge :variant="getStatusBadgeVariant(booking.status)" size="sm">
            {{ BOOKING_STATUS_LABELS[booking.status] }}
          </BBadge>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
      <div class="flex-1 max-w-md">
        <BSearchBar v-model="searchQuery" placeholder="Buchungen suchen..." />
      </div>
      <div class="sm:w-44"><BSelect v-model="filterStatus" :options="statusOptions" /></div>
    </div>

    <!-- Empty State -->
    <BEmptyState
      v-if="filteredBookings.length === 0"
      title="Keine Buchungen"
      description="Sie haben noch keine Buchungen."
      icon="folder"
    />

    <!-- Booking Cards -->
    <div v-else class="space-y-3">
      <div
        v-for="booking in filteredBookings"
        :key="booking.id"
        :class="CARD_STYLES.base"
        class="p-4"
      >
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-mono text-slate-400">{{ booking.bookingNumber }}</span>
              <BBadge :variant="getStatusBadgeVariant(booking.status)" size="sm">
                {{ BOOKING_STATUS_LABELS[booking.status] }}
              </BBadge>
            </div>
            <h3 class="text-sm font-semibold text-slate-900">{{ booking.offerTitle || 'Buchung' }}</h3>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5 text-xs text-slate-500">
              <span>{{ formatDate(booking.scheduledAt) }}</span>
              <span>{{ formatTime(booking.scheduledAt) }}</span>
              <span>{{ booking.durationMinutes }} Min.</span>
              <span v-if="booking.employeeName">mit {{ booking.employeeName }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 shrink-0">
            <div class="text-right">
              <span class="text-sm font-bold text-slate-900">{{ formatMoney(booking.totalPriceCents, booking.currency) }}</span>
              <div class="text-xs text-slate-500">{{ PAYMENT_STATUS_LABELS[booking.paymentStatus] }}</div>
            </div>
            <BButton
              v-if="!isFinalStatus(booking.status)"
              variant="ghost"
              size="sm"
              class="text-red-500 hover:text-red-700"
              @click="initiateCancel(booking)"
            >
              Stornieren
            </BButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation -->
    <BConfirmDialog
      v-model="showCancelConfirm"
      title="Buchung stornieren"
      message="Moechten Sie diese Buchung wirklich stornieren?"
      confirm-variant="danger"
      confirm-label="Stornieren"
      @confirm="handleCancel"
    />
  </div>
</template>
