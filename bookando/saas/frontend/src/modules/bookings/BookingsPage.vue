<script setup lang="ts">
/**
 * Buchungs-Modul — Buchungen & Sessions
 *
 * Refactored: Domain-Typen, Statusmaschine, BFormPanel (Gold Standard SlideIn).
 * 2 Tabs: Buchungen (BookingListTab) + Sessions (SessionsTab).
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import BookingListTab from './components/BookingListTab.vue';
import SessionsTab from './components/SessionsTab.vue';
import BookingFormPanel from './components/BookingFormPanel.vue';
import { useI18n } from '@/composables/useI18n';
import { useBookingsStore } from '@/stores/bookings';
import { useSessionsStore } from '@/stores/sessions';
import type { Booking } from '@/stores/bookings';
import type { Session } from '@/stores/sessions';

const { t } = useI18n();
const bookingsStore = useBookingsStore();
const sessionsStore = useSessionsStore();

const activeTab = ref('bookings');

const tabs = computed<Tab[]>(() => [
  { id: 'bookings', label: 'Buchungen', badge: bookingsStore.bookingCount },
  { id: 'sessions', label: 'Sessions', badge: sessionsStore.sessionCount },
]);

// ── BookingFormPanel State ────────────────────────────────────────────────
const showBookingPanel = ref(false);
const editingBooking = ref<Booking | null>(null);

function handleCreateBooking() {
  editingBooking.value = null;
  showBookingPanel.value = true;
}

function handleEditBooking(booking: Booking) {
  editingBooking.value = booking;
  showBookingPanel.value = true;
}

function handleBookingSaved() {
  showBookingPanel.value = false;
  editingBooking.value = null;
}

// ── Session handling (redirect to create) ────────────────────────────────
function handleCreateSession() {
  // Sessions are typically auto-created via offers; manual creation
  // would be handled by a dedicated SessionFormPanel in future iterations
  handleCreateBooking();
}

function handleEditSession(_session: Session) {
  // Find the related booking if exists, otherwise open session detail
  // For now we show the booking panel as sessions are tightly coupled
}

// ── FAB ──────────────────────────────────────────────────────────────────
const fabLabel = computed(() => {
  return activeTab.value === 'bookings' ? 'Neue Buchung' : 'Neue Session';
});

function handleFabClick() {
  if (activeTab.value === 'bookings') handleCreateBooking();
  else handleCreateSession();
}
</script>

<template>
  <ModuleLayout
    module-name="bookings"
    title="Buchungen"
    subtitle="Buchungen und Sessions verwalten"
    :tabs="tabs"
    :active-tab="activeTab"
    :show-fab="true"
    :fab-label="fabLabel"
    @tab-change="(id: string) => activeTab = id"
    @fab-click="handleFabClick"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
        @click="handleFabClick"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ fabLabel }}
      </button>
    </template>

    <!-- Buchungen -->
    <BookingListTab
      v-if="activeTab === 'bookings'"
      @edit="handleEditBooking"
      @create="handleCreateBooking"
    />

    <!-- Sessions -->
    <SessionsTab
      v-else-if="activeTab === 'sessions'"
      @edit="handleEditSession"
      @create="handleCreateSession"
    />
  </ModuleLayout>

  <!-- Booking Form Panel (SlideIn) -->
  <BookingFormPanel
    v-model="showBookingPanel"
    :booking="editingBooking"
    @saved="handleBookingSaved"
    @deleted="handleBookingSaved"
  />
</template>
