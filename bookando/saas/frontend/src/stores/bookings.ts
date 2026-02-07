/**
 * Bookings Store — Buchungsverwaltung
 *
 * Gemaess MODUL_ANALYSE.md Abschnitt 2.6:
 * - Booking mit Statusmaschine (PENDING → CONFIRMED → PAID → COMPLETED / NO_SHOW)
 * - Cancellation von jedem nicht-finalen Status moeglich
 * - Alle Preise in Integer Minor Units (Rappen/Cents)
 * - Validierte Status-Transitionen via isTransitionAllowed
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';
import { formatMoney } from '@/utils/money';
import type {
  Booking,
  BookingStatus,
  PaymentStatus,
  BookingParticipant,
  BookingExtra,
  BookingResourceReservation,
  BookingFormData,
} from '@/types/domain/bookings';
import {
  BOOKING_STATUS_TRANSITIONS,
  FINAL_BOOKING_STATUSES,
  BOOKING_STATUS_LABELS,
  BOOKING_STATUS_COLORS,
  PAYMENT_STATUS_LABELS,
  isTransitionAllowed,
  getNextStatuses,
  isFinalStatus,
} from '@/types/domain/bookings';

// Re-export domain types for backward compatibility
export type {
  Booking,
  BookingStatus,
  PaymentStatus,
  BookingParticipant,
  BookingExtra,
  BookingResourceReservation,
  BookingFormData,
};

export {
  BOOKING_STATUS_TRANSITIONS,
  FINAL_BOOKING_STATUSES,
  BOOKING_STATUS_LABELS,
  BOOKING_STATUS_COLORS,
  PAYMENT_STATUS_LABELS,
  isTransitionAllowed,
  getNextStatuses,
  isFinalStatus,
  formatMoney,
};

// ============================================================================
// FILTERS
// ============================================================================

export interface BookingFilters {
  search: string;
  status: BookingStatus | '';
  customerId: string;
  employeeId: string;
  dateFrom: string;
  dateTo: string;
}

// ============================================================================
// STORE
// ============================================================================

export const useBookingsStore = defineStore('bookings', () => {
  // ── State ──────────────────────────────────────────────────────────────
  const bookings = ref<Booking[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<BookingFilters>({
    search: '',
    status: '',
    customerId: '',
    employeeId: '',
    dateFrom: '',
    dateTo: '',
  });

  // ── Filtered Views ─────────────────────────────────────────────────────
  const filteredBookings = computed(() => {
    let result = bookings.value;

    if (filters.value.search) {
      const q = filters.value.search.toLowerCase();
      result = result.filter(b =>
        b.bookingNumber.toLowerCase().includes(q) ||
        (b.customerName || '').toLowerCase().includes(q) ||
        (b.employeeName || '').toLowerCase().includes(q) ||
        (b.offerTitle || '').toLowerCase().includes(q)
      );
    }

    if (filters.value.status) {
      result = result.filter(b => b.status === filters.value.status);
    }

    if (filters.value.customerId) {
      result = result.filter(b => b.customerId === filters.value.customerId);
    }

    if (filters.value.employeeId) {
      result = result.filter(b => b.employeeId === filters.value.employeeId);
    }

    if (filters.value.dateFrom) {
      const from = new Date(filters.value.dateFrom).getTime();
      result = result.filter(b => new Date(b.scheduledAt).getTime() >= from);
    }

    if (filters.value.dateTo) {
      const to = new Date(filters.value.dateTo).getTime();
      result = result.filter(b => new Date(b.scheduledAt).getTime() <= to);
    }

    return result;
  });

  // ── Status-filtered Collections ────────────────────────────────────────
  const pendingBookings = computed(() =>
    bookings.value.filter(b => b.status === 'PENDING')
  );

  const confirmedBookings = computed(() =>
    bookings.value.filter(b => b.status === 'CONFIRMED')
  );

  const paidBookings = computed(() =>
    bookings.value.filter(b => b.status === 'PAID')
  );

  const completedBookings = computed(() =>
    bookings.value.filter(b => b.status === 'COMPLETED')
  );

  const cancelledBookings = computed(() =>
    bookings.value.filter(b => b.status === 'CANCELLED')
  );

  // ── Counts & Time-based Views ──────────────────────────────────────────
  const bookingCount = computed(() => bookings.value.length);

  const todayBookings = computed(() => {
    const now = new Date();
    const todayStr = now.toISOString().slice(0, 10);
    return bookings.value.filter(b => b.scheduledAt.slice(0, 10) === todayStr);
  });

  const upcomingBookings = computed(() => {
    const now = new Date().getTime();
    return bookings.value.filter(b => new Date(b.scheduledAt).getTime() > now);
  });

  // ── Fetch Actions ──────────────────────────────────────────────────────
  async function fetchBookings(params?: Record<string, string | number | boolean | undefined>): Promise<void> {
    try {
      const response = await api.get<{ data: Booking[] }>('/v1/bookings', params);
      bookings.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Buchungen konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await fetchBookings();
    } catch {
      // fetchBookings already sets error.value
    } finally {
      loading.value = false;
    }
  }

  // ── Lookups ────────────────────────────────────────────────────────────
  function getBookingById(id: string): Booking | undefined {
    return bookings.value.find(b => b.id === id);
  }

  // ── CRUD: Bookings ────────────────────────────────────────────────────
  async function createBooking(data: BookingFormData): Promise<Booking> {
    try {
      const response = await api.post<{ data: Booking }>('/v1/bookings', data);
      bookings.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Buchung konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateBooking(id: string, data: Partial<BookingFormData>): Promise<Booking> {
    try {
      const response = await api.put<{ data: Booking }>(`/v1/bookings/${id}`, data);
      const index = bookings.value.findIndex(b => b.id === id);
      if (index !== -1) {
        bookings.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Buchung konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  // ── Status Transitions ─────────────────────────────────────────────────
  async function transitionStatus(id: string, newStatus: BookingStatus): Promise<Booking> {
    const booking = bookings.value.find(b => b.id === id);
    if (!booking) {
      const message = `Buchung ${id} nicht gefunden`;
      error.value = message;
      throw new Error(message);
    }

    if (!isTransitionAllowed(booking.status, newStatus)) {
      const message = `Statusuebergang von ${BOOKING_STATUS_LABELS[booking.status]} nach ${BOOKING_STATUS_LABELS[newStatus]} ist nicht erlaubt`;
      error.value = message;
      throw new Error(message);
    }

    try {
      const response = await api.patch<{ data: Booking }>(
        `/v1/bookings/${id}/status`,
        { status: newStatus }
      );
      const index = bookings.value.findIndex(b => b.id === id);
      if (index !== -1) {
        bookings.value[index] = response.data;
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Status konnte nicht geaendert werden';
      error.value = message;
      throw err;
    }
  }

  // ── Cancellation ───────────────────────────────────────────────────────
  async function cancelBooking(id: string, reason?: string): Promise<Booking> {
    const result = await transitionStatus(id, 'CANCELLED');

    // Set cancel reason locally if provided (server should also handle this)
    if (reason) {
      const index = bookings.value.findIndex(b => b.id === id);
      if (index !== -1) {
        bookings.value[index] = { ...bookings.value[index], cancelReason: reason };
      }
    }

    return result;
  }

  // ── Filter Actions ─────────────────────────────────────────────────────
  function setFilters(newFilters: Partial<BookingFilters>) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = { search: '', status: '', customerId: '', employeeId: '', dateFrom: '', dateTo: '' };
  }

  return {
    // State
    bookings,
    loading,
    error,
    filters,

    // Filtered views
    filteredBookings,

    // Status-filtered collections
    pendingBookings,
    confirmedBookings,
    paidBookings,
    completedBookings,
    cancelledBookings,

    // Counts & time-based
    bookingCount,
    todayBookings,
    upcomingBookings,

    // Fetch
    fetchBookings,
    fetchAll,

    // Lookups
    getBookingById,

    // Booking CRUD
    createBooking,
    updateBooking,

    // Status transitions
    transitionStatus,
    cancelBooking,

    // Filters
    setFilters,
    resetFilters,
  };
});
