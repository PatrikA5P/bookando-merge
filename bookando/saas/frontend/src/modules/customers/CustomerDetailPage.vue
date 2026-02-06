<script setup lang="ts">
/**
 * CustomerDetailPage — Kunden-Detailansicht
 *
 * NEU gegenüber Referenz (die hatte keine Detailseite):
 * - Kunden-Timeline (alle Interaktionen)
 * - Buchungshistorie
 * - Offene Rechnungen
 * - Custom Fields
 * - DSGVO-Aktionen (Export, Löschung)
 * - Consent-Übersicht
 */
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import { CARD_STYLES, BUTTON_STYLES, BADGE_STYLES } from '@/design';

const route = useRoute();
const router = useRouter();
const customerId = computed(() => route.params.id as string);

// TODO: Vue Query fetch by ID
const customer = ref({
  id: customerId.value,
  firstName: 'Max',
  lastName: 'Muster',
  email: 'max@example.ch',
  phone: '+41 79 123 45 67',
  status: 'ACTIVE',
  city: 'Zürich',
  country: 'CH',
  street: 'Musterstrasse 42',
  zip: '8000',
  createdAt: '2025-01-15',
  notes: 'VIP Kunde, bevorzugt Termine am Morgen.',
});

const tabs = [
  { id: 'overview', label: 'Übersicht' },
  { id: 'bookings', label: 'Buchungen' },
  { id: 'invoices', label: 'Rechnungen' },
  { id: 'timeline', label: 'Timeline' },
  { id: 'gdpr', label: 'DSGVO' },
];
const activeTab = ref('overview');
</script>

<template>
  <ModuleLayout
    module-name="customers"
    :title="`${customer.firstName} ${customer.lastName}`"
    subtitle="Kundendetail"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(t: string) => activeTab = t"
  >
    <template #header-actions>
      <div class="flex items-center gap-2">
        <button
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30"
          @click="router.push('/customers')"
        >
          Zurück zur Liste
        </button>
      </div>
    </template>

    <!-- Übersicht -->
    <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Profil-Karte -->
      <div :class="CARD_STYLES.base" class="lg:col-span-1">
        <div class="p-6 text-center">
          <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-2xl mx-auto">
            {{ customer.firstName[0] }}{{ customer.lastName[0] }}
          </div>
          <h2 class="mt-4 text-lg font-bold text-slate-900">{{ customer.firstName }} {{ customer.lastName }}</h2>
          <BBadge :status="customer.status" dot class="mt-2">
            {{ customer.status === 'ACTIVE' ? 'Aktiv' : 'Gesperrt' }}
          </BBadge>

          <div class="mt-6 space-y-3 text-left">
            <div class="flex items-center gap-3 text-sm">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span class="text-slate-700">{{ customer.email }}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              <span class="text-slate-700">{{ customer.phone }}</span>
            </div>
            <div class="flex items-center gap-3 text-sm">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span class="text-slate-700">{{ customer.street }}, {{ customer.zip }} {{ customer.city }}</span>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-200">
            <p class="text-xs text-slate-400">Kunde seit {{ customer.createdAt }}</p>
          </div>
        </div>
      </div>

      <!-- Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Quick Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-brand-600">24</p>
            <p class="text-xs text-slate-500 mt-1">Buchungen</p>
          </div>
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">CHF 3'420</p>
            <p class="text-xs text-slate-500 mt-1">Umsatz</p>
          </div>
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-purple-600">0</p>
            <p class="text-xs text-slate-500 mt-1">Offen</p>
          </div>
          <div :class="CARD_STYLES.stat" class="!p-4 text-center">
            <p class="text-2xl font-bold text-amber-600">2</p>
            <p class="text-xs text-slate-500 mt-1">No-Shows</p>
          </div>
        </div>

        <!-- Notizen -->
        <div :class="CARD_STYLES.base">
          <div :class="CARD_STYLES.headerCompact">
            <h3 class="text-sm font-semibold text-slate-900">Notizen</h3>
          </div>
          <div :class="CARD_STYLES.bodyCompact">
            <p class="text-sm text-slate-600">{{ customer.notes || 'Keine Notizen vorhanden.' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- DSGVO Tab -->
    <div v-else-if="activeTab === 'gdpr'" class="max-w-2xl space-y-6">
      <div :class="CARD_STYLES.base">
        <div :class="CARD_STYLES.header">
          <h3 class="text-base font-semibold text-slate-900">Datenschutz (DSG / DSGVO)</h3>
        </div>
        <div :class="CARD_STYLES.body" class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div>
              <p class="text-sm font-medium text-blue-900">Art. 15 — Auskunftsrecht</p>
              <p class="text-xs text-blue-600 mt-0.5">Alle gespeicherten Daten des Kunden exportieren</p>
            </div>
            <BButton variant="secondary" class="!text-xs">
              Daten exportieren
            </BButton>
          </div>

          <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
            <div>
              <p class="text-sm font-medium text-red-900">Art. 17 — Recht auf Löschung</p>
              <p class="text-xs text-red-600 mt-0.5">Alle personenbezogenen Daten unwiderruflich löschen (Crypto-Shredding)</p>
            </div>
            <BButton variant="danger" class="!text-xs">
              Daten löschen
            </BButton>
          </div>

          <div class="flex items-center justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
            <div>
              <p class="text-sm font-medium text-slate-900">Einwilligungen</p>
              <p class="text-xs text-slate-500 mt-0.5">Übersicht aller Consent-Entscheidungen</p>
            </div>
            <BButton variant="ghost" class="!text-xs">
              Anzeigen
            </BButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Placeholder für andere Tabs -->
    <div v-else class="flex items-center justify-center py-16">
      <div class="text-center text-slate-400">
        <p class="text-sm">{{ activeTab }}-Ansicht wird implementiert</p>
      </div>
    </div>
  </ModuleLayout>
</template>
