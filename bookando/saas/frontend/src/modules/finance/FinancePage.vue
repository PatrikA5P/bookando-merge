<script setup lang="ts">
/**
 * Finanzen-Modul — Zahlungen, Rechnungen & Buchhaltung
 *
 * Tabs: Zahlungen, Rechnungen, Gutschriften, Rabattcodes, Export
 * TODO: 5 Payment-Gateways (Stripe, PayPal, Mollie, Klarna, TWINT),
 *       Rechnungsgenerierung (PDF), Ledger-Export, Gutschriften
 */
import { ref } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';
import type { Tab } from '@/components/layout/ModuleLayout.vue';

const tabs: Tab[] = [
  { id: 'payments', label: 'Zahlungen' },
  { id: 'invoices', label: 'Rechnungen' },
  { id: 'credits', label: 'Gutschriften' },
  { id: 'discounts', label: 'Rabattcodes' },
  { id: 'export', label: 'Export' },
];

const activeTab = ref('payments');
</script>

<template>
  <ModuleLayout
    module-name="finance"
    title="Finanzen"
    subtitle="Zahlungen, Rechnungen und Buchhaltung"
    :tabs="tabs"
    :active-tab="activeTab"
    @tab-change="(id: string) => activeTab = id"
  >
    <!-- Zahlungen -->
    <div v-if="activeTab === 'payments'">
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">Zahlungen</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
          <p class="text-xs text-slate-400 mt-2">Stripe, PayPal, Mollie, Klarna &amp; TWINT Integration</p>
        </div>
      </div>
    </div>

    <!-- Platzhalter für andere Tabs -->
    <div v-else>
      <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto bg-purple-50 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
            </svg>
          </div>
          <h3 class="text-sm font-semibold text-slate-900">{{ tabs.find(t => t.id === activeTab)?.label }}</h3>
          <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>
