<script setup lang="ts">
/**
 * CustomerDetailPage — Kunden-Dashboard
 *
 * 5 Tabs: Übersicht, Buchungen, Rechnungen, Timeline, DSGVO
 * Foundation-konform: Tenant-first, Money als Integer Minor Units, Event-basierte Kommunikation
 */
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useAppStore } from '@/stores/app';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const appStore = useAppStore();
const designStore = useDesignStore();

const customerId = computed(() => route.params.id as string);

// TODO: Vue Query — fetch via GET /api/v1/customers/:id (tenantId from auth context)
const customer = ref({
  id: customerId.value,
  tenantId: 1,
  firstName: 'Max',
  lastName: 'Muster',
  email: 'max@example.ch',
  phone: '+41 79 123 45 67',
  status: 'ACTIVE' as const,
  gender: 'male',
  birthday: '1985-06-15',
  city: 'Zürich',
  country: 'CH',
  street: 'Musterstrasse 42',
  zip: '8000',
  tags: ['VIP', 'Stammkunde'],
  notes: 'VIP Kunde, bevorzugt Termine am Morgen.',
  createdAt: '2025-01-15',
  updatedAt: '2026-01-20',
});

// Tabs
const tabs = computed<Tab[]>(() => [
  { id: 'overview', label: t('customers.overview') },
  { id: 'bookings', label: t('customers.bookings') },
  { id: 'invoices', label: t('customers.invoices'), badge: mockInvoices.filter(i => i.status === 'OVERDUE').length || undefined },
  { id: 'timeline', label: t('customers.timeline') },
  { id: 'gdpr', label: t('customers.gdpr') },
]);
const activeTab = ref('overview');

// Mock stats (TODO: Vue Query aggregation endpoint)
const stats = computed(() => ({
  totalBookings: 24,
  totalRevenueMinor: 342000, // CHF 3'420.00
  openInvoices: 1,
  noShows: 2,
}));

// Mock bookings
const mockBookings = [
  { id: 'apt-1', date: '2026-02-10', time: '09:00', service: 'Herrenhaarschnitt', employee: 'Lisa Weber', duration: 45, priceMinor: 6500, status: 'CONFIRMED' },
  { id: 'apt-2', date: '2026-01-28', time: '14:30', service: 'Bartpflege', employee: 'Marco Rossi', duration: 30, priceMinor: 3500, status: 'COMPLETED' },
  { id: 'apt-3', date: '2026-01-15', time: '10:00', service: 'Komplett-Paket', employee: 'Lisa Weber', duration: 90, priceMinor: 12000, status: 'COMPLETED' },
  { id: 'apt-4', date: '2025-12-20', time: '11:00', service: 'Herrenhaarschnitt', employee: 'Sophie Dubois', duration: 45, priceMinor: 6500, status: 'NO_SHOW' },
  { id: 'apt-5', date: '2025-12-05', time: '09:30', service: 'Färben', employee: 'Lisa Weber', duration: 120, priceMinor: 18000, status: 'COMPLETED' },
];

// Mock invoices
const mockInvoices = [
  { id: 'inv-1', number: 'INV-2026-0042', date: '2026-01-28', dueDate: '2026-02-28', totalMinor: 3500, status: 'SENT' },
  { id: 'inv-2', number: 'INV-2026-0035', date: '2026-01-15', dueDate: '2026-02-15', totalMinor: 12000, status: 'PAID' },
  { id: 'inv-3', number: 'INV-2025-0198', date: '2025-12-05', dueDate: '2026-01-05', totalMinor: 18000, status: 'OVERDUE' },
  { id: 'inv-4', number: 'INV-2025-0180', date: '2025-11-20', dueDate: '2025-12-20', totalMinor: 6500, status: 'PAID' },
];

// Mock timeline events
const mockTimeline = [
  { id: 'evt-1', type: 'booking', date: '2026-02-10 09:00', description: 'Termin gebucht: Herrenhaarschnitt bei Lisa Weber', icon: 'calendar' },
  { id: 'evt-2', type: 'invoice', date: '2026-01-28 15:00', description: 'Rechnung INV-2026-0042 erstellt (CHF 35.00)', icon: 'document' },
  { id: 'evt-3', type: 'booking', date: '2026-01-28 14:30', description: 'Termin abgeschlossen: Bartpflege', icon: 'check' },
  { id: 'evt-4', type: 'payment', date: '2026-01-20 10:15', description: 'Zahlung eingegangen: CHF 120.00 (INV-2026-0035)', icon: 'banknote' },
  { id: 'evt-5', type: 'note', date: '2026-01-10 08:30', description: 'Notiz aktualisiert: VIP-Status hinzugefügt', icon: 'pencil' },
  { id: 'evt-6', type: 'noshow', date: '2025-12-20 11:00', description: 'Nicht erschienen: Herrenhaarschnitt', icon: 'x' },
  { id: 'evt-7', type: 'created', date: '2025-01-15 12:00', description: 'Kundenprofil erstellt', icon: 'user-plus' },
];

// Mock consents
const mockConsents = [
  { id: 'con-1', type: 'marketing_email', granted: true, date: '2025-01-15', description: 'E-Mail Marketing' },
  { id: 'con-2', type: 'marketing_sms', granted: false, date: '2025-01-15', description: 'SMS Marketing' },
  { id: 'con-3', type: 'data_processing', granted: true, date: '2025-01-15', description: 'Datenverarbeitung' },
  { id: 'con-4', type: 'third_party', granted: false, date: '2025-01-15', description: 'Datenweitergabe an Dritte' },
];

function formatPrice(minorUnits: number): string {
  return appStore.formatPrice(minorUnits);
}

function getStatusColor(status: string): string {
  const map: Record<string, string> = {
    CONFIRMED: 'bg-blue-100 text-blue-700',
    COMPLETED: 'bg-emerald-100 text-emerald-700',
    CANCELLED: 'bg-red-100 text-red-700',
    NO_SHOW: 'bg-amber-100 text-amber-700',
    PENDING: 'bg-slate-100 text-slate-600',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

function getInvoiceStatusColor(status: string): string {
  const map: Record<string, string> = {
    DRAFT: 'bg-slate-100 text-slate-600',
    SENT: 'bg-blue-100 text-blue-700',
    PAID: 'bg-emerald-100 text-emerald-700',
    OVERDUE: 'bg-red-100 text-red-700',
    CANCELLED: 'bg-slate-100 text-slate-500',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

function getTimelineIcon(icon: string): string {
  const map: Record<string, string> = {
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    banknote: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    pencil: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    x: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    'user-plus': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
  };
  return map[icon] || map.check;
}

function getTimelineColor(type: string): string {
  const map: Record<string, string> = {
    booking: 'bg-blue-100 text-blue-600',
    invoice: 'bg-purple-100 text-purple-600',
    payment: 'bg-emerald-100 text-emerald-600',
    note: 'bg-amber-100 text-amber-600',
    noshow: 'bg-red-100 text-red-600',
    created: 'bg-slate-100 text-slate-600',
  };
  return map[type] || 'bg-slate-100 text-slate-600';
}
</script>

<template>
  <ModuleLayout
    module-name="customers"
    :title="`${customer.firstName} ${customer.lastName}`"
    :subtitle="t('customers.overview')"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors"
          @click="router.push('/customers')"
        >
          {{ t('common.back') }}
        </button>
        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          {{ t('common.edit') }}
        </button>
      </div>
    </template>

    <!-- ==================== ÜBERSICHT ==================== -->
    <div v-if="activeTab === 'overview'" class="p-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profil-Karte -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 text-center">
              <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-2xl mx-auto">
                {{ customer.firstName[0] }}{{ customer.lastName[0] }}
              </div>
              <h2 class="mt-4 text-lg font-bold text-slate-900">{{ customer.firstName }} {{ customer.lastName }}</h2>
              <BBadge :status="customer.status" dot class="mt-2">
                {{ customer.status === 'ACTIVE' ? t('customers.status.active') : t('customers.status.blocked') }}
              </BBadge>

              <div v-if="customer.tags?.length" class="flex flex-wrap justify-center gap-1.5 mt-3">
                <span
                  v-for="tag in customer.tags"
                  :key="tag"
                  class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-slate-100 text-slate-600"
                >
                  {{ tag }}
                </span>
              </div>

              <div class="mt-6 space-y-3 text-left">
                <div class="flex items-center gap-3 text-sm">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <span class="text-slate-700 truncate">{{ customer.email }}</span>
                </div>
                <div class="flex items-center gap-3 text-sm">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <span class="text-slate-700">{{ customer.phone }}</span>
                </div>
                <div class="flex items-center gap-3 text-sm">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-slate-700">{{ customer.street }}, {{ customer.zip }} {{ customer.city }}</span>
                </div>
              </div>

              <div class="mt-6 pt-4 border-t border-slate-200">
                <p class="text-xs text-slate-400">{{ t('customers.customerSince') }} {{ customer.createdAt }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats + Notizen -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Quick Stats -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
              <p class="text-2xl font-bold text-brand-600">{{ stats.totalBookings }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ t('customers.totalBookings') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
              <p class="text-2xl font-bold text-emerald-600">{{ formatPrice(stats.totalRevenueMinor) }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ t('customers.totalRevenue') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
              <p class="text-2xl font-bold text-purple-600">{{ stats.openInvoices }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ t('customers.openInvoices') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 text-center">
              <p class="text-2xl font-bold text-amber-600">{{ stats.noShows }}</p>
              <p class="text-xs text-slate-500 mt-1">{{ t('customers.noShows') }}</p>
            </div>
          </div>

          <!-- Letzte Buchung -->
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 border-b border-slate-100">
              <h3 class="text-sm font-semibold text-slate-900">{{ t('dashboard.upcomingAppointments') }}</h3>
            </div>
            <div class="divide-y divide-slate-100">
              <div
                v-for="booking in mockBookings.slice(0, 3)"
                :key="booking.id"
                class="px-5 py-3 flex items-center justify-between"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-10 text-center shrink-0">
                    <p class="text-xs font-bold text-slate-900">{{ booking.date.split('-')[2] }}</p>
                    <p class="text-[10px] text-slate-400">{{ booking.date.split('-')[1] }}/{{ booking.date.split('-')[0].slice(2) }}</p>
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-900 truncate">{{ booking.service }}</p>
                    <p class="text-xs text-slate-500">{{ booking.time }} · {{ booking.employee }} · {{ booking.duration }} min</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                  <span class="text-sm font-medium text-slate-700">{{ formatPrice(booking.priceMinor) }}</span>
                  <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getStatusColor(booking.status)]">
                    {{ booking.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Notizen -->
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 border-b border-slate-100">
              <h3 class="text-sm font-semibold text-slate-900">{{ t('customers.notes') }}</h3>
            </div>
            <div class="p-5">
              <p class="text-sm text-slate-600">{{ customer.notes || t('common.noResults') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== BUCHUNGEN ==================== -->
    <div v-else-if="activeTab === 'bookings'" class="p-6">
      <!-- Desktop Table -->
      <div class="hidden md:block">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectDate') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectTime') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectService') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.selectEmployee') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.duration') }}</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('appointments.price') }}</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="booking in mockBookings" :key="booking.id" class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3 text-sm text-slate-900">{{ booking.date }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ booking.time }}</td>
                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ booking.service }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ booking.employee }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ booking.duration }} min</td>
                <td class="px-4 py-3 text-sm text-slate-900 text-right font-medium">{{ formatPrice(booking.priceMinor) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getStatusColor(booking.status)]">
                    {{ booking.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden space-y-3">
        <div
          v-for="booking in mockBookings"
          :key="booking.id"
          class="bg-white rounded-xl border border-slate-200 shadow-sm p-4"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-slate-900">{{ booking.service }}</span>
            <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getStatusColor(booking.status)]">
              {{ booking.status }}
            </span>
          </div>
          <div class="text-xs text-slate-500 space-y-1">
            <p>{{ booking.date }} · {{ booking.time }} · {{ booking.duration }} min</p>
            <p>{{ booking.employee }}</p>
          </div>
          <p class="text-sm font-medium text-slate-900 mt-2">{{ formatPrice(booking.priceMinor) }}</p>
        </div>
      </div>
    </div>

    <!-- ==================== RECHNUNGEN ==================== -->
    <div v-else-if="activeTab === 'invoices'" class="p-6">
      <!-- Desktop Table -->
      <div class="hidden md:block">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.invoiceNumber') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.issueDate') }}</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.dueDate') }}</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('finance.total') }}</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="inv in mockInvoices" :key="inv.id" class="hover:bg-slate-50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ inv.number }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ inv.date }}</td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ inv.dueDate }}</td>
                <td class="px-4 py-3 text-sm text-slate-900 text-right font-medium">{{ formatPrice(inv.totalMinor) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getInvoiceStatusColor(inv.status)]">
                    {{ t(`finance.status.${inv.status.toLowerCase()}`) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <button class="text-xs text-brand-600 hover:text-brand-700 font-medium">
                    {{ t('common.preview') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden space-y-3">
        <div
          v-for="inv in mockInvoices"
          :key="inv.id"
          class="bg-white rounded-xl border border-slate-200 shadow-sm p-4"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-slate-900">{{ inv.number }}</span>
            <span :class="['px-2 py-0.5 text-[10px] font-medium rounded-full', getInvoiceStatusColor(inv.status)]">
              {{ t(`finance.status.${inv.status.toLowerCase()}`) }}
            </span>
          </div>
          <div class="text-xs text-slate-500 space-y-1">
            <p>{{ t('finance.issueDate') }}: {{ inv.date }}</p>
            <p>{{ t('finance.dueDate') }}: {{ inv.dueDate }}</p>
          </div>
          <p class="text-sm font-bold text-slate-900 mt-2">{{ formatPrice(inv.totalMinor) }}</p>
        </div>
      </div>
    </div>

    <!-- ==================== TIMELINE ==================== -->
    <div v-else-if="activeTab === 'timeline'" class="p-6">
      <div class="relative">
        <!-- Timeline Line -->
        <div class="absolute left-5 top-0 bottom-0 w-px bg-slate-200" />

        <div class="space-y-6">
          <div
            v-for="evt in mockTimeline"
            :key="evt.id"
            class="relative flex items-start gap-4 pl-12"
          >
            <!-- Icon -->
            <div :class="['absolute left-2 w-7 h-7 rounded-full flex items-center justify-center shrink-0', getTimelineColor(evt.type)]">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getTimelineIcon(evt.icon)" />
              </svg>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex-1 min-w-0">
              <p class="text-sm text-slate-700">{{ evt.description }}</p>
              <p class="text-xs text-slate-400 mt-1">{{ evt.date }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== DSGVO ==================== -->
    <div v-else-if="activeTab === 'gdpr'" class="p-6 max-w-3xl space-y-6">
      <!-- Data Export -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-3 border-b border-slate-100">
          <h3 class="text-sm font-semibold text-slate-900">{{ t('customers.gdpr') }} (DSG / DSGVO)</h3>
        </div>
        <div class="p-5 space-y-4">
          <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div>
              <p class="text-sm font-medium text-blue-900">Art. 15 — {{ t('customers.gdprExport') }}</p>
              <p class="text-xs text-blue-600 mt-0.5">{{ t('customers.gdprExportDesc') }}</p>
            </div>
            <BButton variant="secondary" class="!text-xs shrink-0">
              {{ t('customers.gdprExport') }}
            </BButton>
          </div>

          <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
            <div>
              <p class="text-sm font-medium text-red-900">Art. 17 — {{ t('customers.gdprDelete') }}</p>
              <p class="text-xs text-red-600 mt-0.5">{{ t('customers.gdprDeleteDesc') }}</p>
            </div>
            <BButton variant="danger" class="!text-xs shrink-0">
              {{ t('customers.gdprDelete') }}
            </BButton>
          </div>
        </div>
      </div>

      <!-- Consents -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-3 border-b border-slate-100">
          <h3 class="text-sm font-semibold text-slate-900">{{ t('customers.consents') }}</h3>
        </div>
        <div class="divide-y divide-slate-100">
          <div
            v-for="consent in mockConsents"
            :key="consent.id"
            class="px-5 py-3 flex items-center justify-between"
          >
            <div>
              <p class="text-sm font-medium text-slate-900">{{ consent.description }}</p>
              <p class="text-xs text-slate-400">{{ consent.date }}</p>
            </div>
            <span :class="[
              'px-2 py-0.5 text-[10px] font-medium rounded-full',
              consent.granted ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700',
            ]">
              {{ consent.granted ? t('common.approved') : t('common.rejected') }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
