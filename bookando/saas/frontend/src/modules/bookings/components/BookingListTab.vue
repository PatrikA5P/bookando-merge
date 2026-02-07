<script setup lang="ts">
/**
 * BookingListTab — Buchungsliste mit Filtern und Statusanzeige
 *
 * Zeigt alle Buchungen als Tabelle/Karten mit:
 * - Suche nach Buchungsnummer, Kunde, Mitarbeiter, Angebot
 * - Filter nach Status, Zeitraum
 * - Status-Badges mit Farbcoding
 * - Preise in formatierter Minor-Unit-Darstellung
 */
import { ref, computed } from 'vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BInput from '@/components/ui/BInput.vue';
import { useI18n } from '@/composables/useI18n';
import {
  useBookingsStore,
  BOOKING_STATUS_LABELS,
  BOOKING_STATUS_COLORS,
  PAYMENT_STATUS_LABELS,
  formatMoney,
} from '@/stores/bookings';
import type { Booking, BookingStatus } from '@/stores/bookings';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const store = useBookingsStore();

const emit = defineEmits<{
  (e: 'edit', booking: Booking): void;
  (e: 'create'): void;
}>();

const searchQuery = ref('');
const filterStatus = ref<BookingStatus | ''>('');
const filterDateFrom = ref('');
const filterDateTo = ref('');

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
      (b.customerName || '').toLowerCase().includes(q) ||
      (b.employeeName || '').toLowerCase().includes(q) ||
      (b.offerTitle || '').toLowerCase().includes(q)
    );
  }

  if (filterStatus.value) {
    result = result.filter(b => b.status === filterStatus.value);
  }

  if (filterDateFrom.value) {
    const from = new Date(filterDateFrom.value).getTime();
    result = result.filter(b => new Date(b.scheduledAt).getTime() >= from);
  }

  if (filterDateTo.value) {
    const to = new Date(filterDateTo.value).getTime();
    result = result.filter(b => new Date(b.scheduledAt).getTime() <= to);
  }

  // Sort by scheduledAt descending (newest first)
  return result.sort((a, b) => new Date(b.scheduledAt).getTime() - new Date(a.scheduledAt).getTime());
});

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

function formatDate(iso: string): string {
  const d = new Date(iso);
  return d.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatTime(iso: string): string {
  const d = new Date(iso);
  return d.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' });
}

function formatDateTime(iso: string): string {
  return `${formatDate(iso)}, ${formatTime(iso)}`;
}
</script>

<template>
  <!-- Search & Filters -->
  <div class="flex flex-col gap-4 mb-6">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <div class="flex-1">
        <BSearchBar v-model="searchQuery" placeholder="Buchungen suchen (Nummer, Kunde, Angebot)..." />
      </div>
      <BButton variant="primary" class="hidden md:flex" @click="emit('create')">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Neue Buchung
      </BButton>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="sm:w-44"><BSelect v-model="filterStatus" :options="statusOptions" /></div>
      <div class="sm:w-40"><BInput v-model="filterDateFrom" type="date" placeholder="Von" /></div>
      <div class="sm:w-40"><BInput v-model="filterDateTo" type="date" placeholder="Bis" /></div>
    </div>
  </div>

  <!-- Empty States -->
  <BEmptyState
    v-if="filteredBookings.length === 0 && !searchQuery && !filterStatus && !filterDateFrom && !filterDateTo"
    title="Noch keine Buchungen vorhanden"
    description="Erstellen Sie Ihre erste Buchung."
    icon="folder"
    action-label="Erste Buchung erstellen"
    @action="emit('create')"
  />

  <BEmptyState
    v-else-if="filteredBookings.length === 0"
    title="Keine Buchungen gefunden"
    description="Passen Sie Ihre Filter an."
    icon="search"
  />

  <!-- Booking Cards -->
  <div v-else class="space-y-3">
    <div
      v-for="booking in filteredBookings"
      :key="booking.id"
      :class="CARD_STYLES.hover"
      class="p-4 cursor-pointer"
      @click="emit('edit', booking)"
    >
      <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <!-- Left: Main Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-mono text-slate-400">{{ booking.bookingNumber }}</span>
            <BBadge :variant="getStatusBadgeVariant(booking.status)">
              {{ BOOKING_STATUS_LABELS[booking.status] }}
            </BBadge>
          </div>
          <h3 class="text-sm font-semibold text-slate-900 truncate">{{ booking.offerTitle || 'Buchung' }}</h3>
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1 text-xs text-slate-500">
            <div class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              {{ booking.customerName || '—' }}
            </div>
            <div v-if="booking.employeeName" class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
              {{ booking.employeeName }}
            </div>
            <div class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              {{ formatDateTime(booking.scheduledAt) }}
            </div>
            <div class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              {{ booking.durationMinutes }} Min.
            </div>
            <div v-if="booking.participantCount > 1" class="flex items-center gap-1">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
              {{ booking.participantCount }} TN
            </div>
          </div>
        </div>

        <!-- Right: Price & Payment -->
        <div class="flex sm:flex-col items-center sm:items-end gap-2 sm:gap-1 shrink-0">
          <span class="text-sm font-bold text-slate-900">{{ formatMoney(booking.totalPriceCents, booking.currency) }}</span>
          <BBadge
            :variant="booking.paymentStatus === 'PAID' ? 'success' : booking.paymentStatus === 'PARTIALLY_PAID' ? 'warning' : 'default'"
            size="sm"
          >
            {{ PAYMENT_STATUS_LABELS[booking.paymentStatus] }}
          </BBadge>
        </div>
      </div>
    </div>
  </div>
</template>
