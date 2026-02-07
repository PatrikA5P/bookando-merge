<script setup lang="ts">
/**
 * EmployeeDashboardTab — Mitarbeiterpanel Startseite
 *
 * Zeigt dem Mitarbeiter:
 * - Heutige Termine/Sessions
 * - Offene Buchungen
 * - Verfuegbarkeitsstatus
 * - Wochen-Statistiken
 */
import { computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import { useI18n } from '@/composables/useI18n';
import {
  useBookingsStore,
  BOOKING_STATUS_LABELS,
  formatMoney,
} from '@/stores/bookings';
import {
  useSessionsStore,
  SESSION_STATUS_LABELS,
  SESSION_STATUS_COLORS,
} from '@/stores/sessions';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const bookingsStore = useBookingsStore();
const sessionsStore = useSessionsStore();

// ── Today's Sessions ────────────────────────────────────────────────────
const todaySessions = computed(() => sessionsStore.todaySessions);
const upcomingSessions = computed(() => sessionsStore.upcomingSessions.slice(0, 5));

// ── Today's Bookings ────────────────────────────────────────────────────
const todayBookings = computed(() => bookingsStore.todayBookings);
const pendingBookings = computed(() => bookingsStore.pendingBookings);

// ── Stats ───────────────────────────────────────────────────────────────
const todaySessionCount = computed(() => todaySessions.value.length);
const todayBookingCount = computed(() => todayBookings.value.length);
const pendingCount = computed(() => pendingBookings.value.length);
const completedTodayCount = computed(() =>
  bookingsStore.bookings.filter(b => {
    const today = new Date().toISOString().slice(0, 10);
    return b.completedAt && b.completedAt.slice(0, 10) === today;
  }).length
);

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' });
}

function formatTimeRange(startsAt: string, endsAt: string): string {
  return `${formatTime(startsAt)} – ${formatTime(endsAt)}`;
}

function getSessionStatusVariant(status: string): 'default' | 'success' | 'warning' | 'info' {
  const map: Record<string, 'default' | 'success' | 'warning' | 'info'> = {
    warning: 'warning', success: 'success', info: 'info', brand: 'success', default: 'default',
  };
  return map[SESSION_STATUS_COLORS[status as keyof typeof SESSION_STATUS_COLORS]] || 'default';
}
</script>

<template>
  <div>
    <!-- Stats Overview -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-brand-600">{{ todaySessionCount }}</div>
        <div class="text-xs text-slate-500 mt-1">Sessions heute</div>
      </div>
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-cyan-600">{{ todayBookingCount }}</div>
        <div class="text-xs text-slate-500 mt-1">Buchungen heute</div>
      </div>
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-amber-600">{{ pendingCount }}</div>
        <div class="text-xs text-slate-500 mt-1">Ausstehend</div>
      </div>
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-emerald-600">{{ completedTodayCount }}</div>
        <div class="text-xs text-slate-500 mt-1">Abgeschlossen</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Today's Sessions -->
      <div>
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Heutige Sessions</h3>
        <div v-if="todaySessions.length > 0" class="space-y-2">
          <div
            v-for="session in todaySessions"
            :key="session.id"
            class="flex items-center gap-3 p-3 rounded-xl bg-white border border-slate-100"
          >
            <div class="w-1 h-10 rounded-full" :class="session.status === 'COMPLETED' ? 'bg-emerald-400' : session.status === 'IN_PROGRESS' ? 'bg-blue-400' : 'bg-amber-400'" />
            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-medium text-slate-900 truncate">{{ session.title || session.offerTitle || 'Session' }}</h4>
              <p class="text-xs text-slate-500">{{ formatTimeRange(session.startsAt, session.endsAt) }}</p>
            </div>
            <BBadge :variant="getSessionStatusVariant(session.status)" size="sm">
              {{ SESSION_STATUS_LABELS[session.status] }}
            </BBadge>
          </div>
        </div>
        <div v-else class="text-sm text-slate-400 bg-slate-50 rounded-xl p-6 text-center">
          Keine Sessions heute
        </div>
      </div>

      <!-- Upcoming Sessions -->
      <div>
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Naechste Sessions</h3>
        <div v-if="upcomingSessions.length > 0" class="space-y-2">
          <div
            v-for="session in upcomingSessions"
            :key="session.id"
            class="flex items-center gap-3 p-3 rounded-xl bg-white border border-slate-100"
          >
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex flex-col items-center justify-center shrink-0 text-xs">
              <span class="font-bold leading-none">{{ new Date(session.startsAt).getDate() }}</span>
              <span class="text-[9px] leading-none mt-0.5">{{ new Date(session.startsAt).toLocaleDateString('de-CH', { month: 'short' }) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-medium text-slate-900 truncate">{{ session.title || session.offerTitle || 'Session' }}</h4>
              <p class="text-xs text-slate-500">{{ formatTimeRange(session.startsAt, session.endsAt) }}</p>
            </div>
            <div v-if="session.maxParticipants" class="text-xs text-slate-400 shrink-0">
              {{ session.currentEnrollment }}/{{ session.maxParticipants }}
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-400 bg-slate-50 rounded-xl p-6 text-center">
          Keine anstehenden Sessions
        </div>
      </div>
    </div>

    <!-- Pending Bookings -->
    <div v-if="pendingBookings.length > 0" class="mt-6">
      <h3 class="text-sm font-semibold text-slate-900 mb-3">Ausstehende Buchungen</h3>
      <div class="space-y-2">
        <div
          v-for="booking in pendingBookings.slice(0, 5)"
          :key="booking.id"
          class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100"
        >
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono text-amber-500">{{ booking.bookingNumber }}</span>
              <h4 class="text-sm font-medium text-slate-900 truncate">{{ booking.offerTitle || 'Buchung' }}</h4>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">{{ booking.customerName || '—' }} · {{ formatTime(booking.scheduledAt) }}</p>
          </div>
          <span class="text-sm font-medium text-slate-900">{{ formatMoney(booking.totalPriceCents, booking.currency) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
