<script setup lang="ts">
/**
 * InvoicePreview — Swiss QR-Bill Vorschau
 *
 * Zeigt eine vollständige Rechnungsvorschau mit:
 * - Firmen-Header (Logo-Platzhalter, Adresse)
 * - Rechnungsnummer, Daten
 * - Positionstabelle mit Totals
 * - Swiss QR-Bill Zahlungsteil (A6, unten)
 * - QR-Code-Platzhalter mit Swiss Cross
 * - Druckfunktion
 */
import BModal from '@/components/ui/BModal.vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import { useI18n } from '@/composables/useI18n';
import { useAppStore } from '@/stores/app';
import type { Invoice } from '@/stores/finance';
import { BUTTON_STYLES, TABLE_STYLES, CARD_STYLES } from '@/design';

const props = defineProps<{
  isOpen: boolean;
  invoice: Invoice | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'edit', invoice: Invoice): void;
}>();

const { t } = useI18n();
const appStore = useAppStore();

// Company data (mock)
const company = {
  name: 'Bookando Beauty GmbH',
  street: 'Bahnhofstrasse 42',
  city: '8001 Zürich',
  country: 'CH',
  phone: '+41 44 123 45 67',
  email: 'info@bookando-beauty.ch',
  uid: 'CHE-123.456.789',
  iban: 'CH93 0076 2011 6238 5295 7',
};

function getStatusKey(status: string): string {
  return status.toLowerCase();
}

function handlePrint() {
  window.print();
}

function handleEdit() {
  if (props.invoice) {
    emit('edit', props.invoice);
  }
}
</script>

<template>
  <BModal
    :model-value="isOpen"
    :title="t('finance.preview.title')"
    size="xl"
    @update:model-value="(val: boolean) => !val && emit('close')"
  >
    <div v-if="invoice" class="space-y-6">
      <!-- Invoice Document -->
      <div class="bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden print:shadow-none print:border-none">

        <!-- Company Header -->
        <div class="p-8 border-b border-slate-200">
          <div class="flex items-start justify-between">
            <div>
              <!-- Logo Placeholder -->
              <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <h2 class="text-lg font-bold text-slate-900">{{ company.name }}</h2>
              <p class="text-sm text-slate-500">{{ company.street }}</p>
              <p class="text-sm text-slate-500">{{ company.city }}</p>
              <p class="text-xs text-slate-400 mt-1">{{ t('finance.preview.uid') }}: {{ company.uid }}</p>
            </div>
            <div class="text-right">
              <h1 class="text-2xl font-bold text-slate-900 mb-2">{{ t('finance.preview.invoiceTitle') }}</h1>
              <div class="space-y-1 text-sm">
                <p><span class="text-slate-500">{{ t('finance.invoices.number') }}:</span> <span class="font-medium">{{ invoice.number }}</span></p>
                <p><span class="text-slate-500">{{ t('finance.invoices.date') }}:</span> <span class="font-medium">{{ appStore.formatDate(invoice.issueDate) }}</span></p>
                <p><span class="text-slate-500">{{ t('finance.preview.dueDate') }}:</span> <span class="font-medium">{{ appStore.formatDate(invoice.dueDate) }}</span></p>
              </div>
              <div class="mt-3">
                <BBadge :status="getStatusKey(invoice.status)" dot>
                  {{ t(`finance.status.${invoice.status}`) }}
                </BBadge>
              </div>
            </div>
          </div>

          <!-- Customer Address -->
          <div class="mt-8">
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">{{ t('finance.preview.billTo') }}</p>
            <p class="text-sm font-medium text-slate-900">{{ invoice.customerName }}</p>
          </div>
        </div>

        <!-- Line Items Table -->
        <div class="p-8">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-slate-200">
                <th class="pb-3 text-xs font-semibold text-slate-500 uppercase">{{ t('finance.invoices.description') }}</th>
                <th class="pb-3 text-xs font-semibold text-slate-500 uppercase text-center w-16">{{ t('finance.invoices.qty') }}</th>
                <th class="pb-3 text-xs font-semibold text-slate-500 uppercase text-right w-28">{{ t('finance.invoices.price') }}</th>
                <th class="pb-3 text-xs font-semibold text-slate-500 uppercase text-center w-20">{{ t('finance.invoices.vatRate') }}</th>
                <th class="pb-3 text-xs font-semibold text-slate-500 uppercase text-right w-28">{{ t('finance.invoices.total') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in invoice.lineItems" :key="item.id" class="border-b border-slate-100">
                <td class="py-3 text-sm text-slate-700">{{ item.description }}</td>
                <td class="py-3 text-sm text-slate-700 text-center">{{ item.quantity }}</td>
                <td class="py-3 text-sm text-slate-700 text-right">{{ appStore.formatPrice(item.unitPriceMinor) }}</td>
                <td class="py-3 text-sm text-slate-500 text-center">{{ item.vatRatePercent }}%</td>
                <td class="py-3 text-sm font-medium text-slate-900 text-right">{{ appStore.formatPrice(item.totalMinor) }}</td>
              </tr>
            </tbody>
          </table>

          <!-- Totals -->
          <div class="mt-6 flex justify-end">
            <div class="w-72 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">{{ t('finance.invoices.subtotal') }}</span>
                <span class="text-slate-900">{{ appStore.formatPrice(invoice.totalMinor - invoice.taxMinor) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">{{ t('finance.preview.vatAmount') }}</span>
                <span class="text-slate-700">{{ appStore.formatPrice(invoice.taxMinor) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold pt-2 border-t-2 border-slate-900">
                <span class="text-slate-900">{{ t('finance.invoices.grandTotal') }}</span>
                <span class="text-slate-900">{{ appStore.formatPrice(invoice.totalMinor) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Swiss QR-Bill Payment Slip (A6 format) -->
        <div class="border-t-2 border-dashed border-slate-400 mt-4">
          <div class="p-6 bg-white">
            <!-- Scissors indicator -->
            <div class="flex items-center gap-2 -mt-9 mb-4">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
              </svg>
              <span class="text-xs text-slate-400">{{ t('finance.preview.cutHere') }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Receipt (Empfangsschein) -->
              <div class="md:col-span-1 md:border-r md:border-slate-200 md:pr-6">
                <h4 class="text-xs font-bold text-slate-900 uppercase mb-3">{{ t('finance.preview.receipt') }}</h4>
                <div class="space-y-2 text-xs">
                  <div>
                    <p class="font-medium text-slate-500">{{ t('finance.preview.accountPayableTo') }}</p>
                    <p class="text-slate-900">{{ company.iban }}</p>
                    <p class="text-slate-900">{{ company.name }}</p>
                    <p class="text-slate-700">{{ company.street }}</p>
                    <p class="text-slate-700">{{ company.city }}</p>
                  </div>
                  <div>
                    <p class="font-medium text-slate-500">{{ t('finance.preview.reference') }}</p>
                    <p class="text-slate-900 font-mono text-[10px] break-all">{{ invoice.qrReference }}</p>
                  </div>
                  <div>
                    <p class="font-medium text-slate-500">{{ t('finance.preview.amount') }}</p>
                    <p class="text-slate-900 font-medium">{{ invoice.currency }} {{ (invoice.totalMinor / 100).toFixed(2) }}</p>
                  </div>
                </div>
              </div>

              <!-- Payment Part (Zahlteil) -->
              <div class="md:col-span-2">
                <h4 class="text-xs font-bold text-slate-900 uppercase mb-3">{{ t('finance.preview.paymentPart') }}</h4>
                <div class="flex gap-6">
                  <!-- QR Code Placeholder -->
                  <div class="w-36 h-36 border-2 border-slate-900 rounded-sm flex items-center justify-center bg-white shrink-0 relative">
                    <!-- Swiss Cross in center -->
                    <div class="w-8 h-8 bg-slate-900 flex items-center justify-center">
                      <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6V5z" />
                      </svg>
                    </div>
                    <!-- QR pattern hint -->
                    <div class="absolute inset-2 border border-slate-200 rounded-sm pointer-events-none"></div>
                    <p class="absolute bottom-1 text-[8px] text-slate-400">QR-Code</p>
                  </div>

                  <!-- Payment Info -->
                  <div class="space-y-3 text-xs flex-1">
                    <div>
                      <p class="font-medium text-slate-500">{{ t('finance.preview.accountPayableTo') }}</p>
                      <p class="text-slate-900">{{ company.iban }}</p>
                      <p class="text-slate-900">{{ company.name }}</p>
                      <p class="text-slate-700">{{ company.street }}, {{ company.city }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-slate-500">{{ t('finance.preview.reference') }}</p>
                      <p class="text-slate-900 font-mono text-[10px] break-all">{{ invoice.qrReference }}</p>
                    </div>
                    <div>
                      <p class="font-medium text-slate-500">{{ t('finance.preview.payableBy') }}</p>
                      <p class="text-slate-900">{{ invoice.customerName }}</p>
                    </div>
                    <div class="flex items-end justify-between">
                      <div>
                        <p class="font-medium text-slate-500">{{ t('finance.preview.currency') }}</p>
                        <p class="text-slate-900 font-medium">{{ invoice.currency }}</p>
                      </div>
                      <div>
                        <p class="font-medium text-slate-500">{{ t('finance.preview.amount') }}</p>
                        <p class="text-lg font-bold text-slate-900">{{ (invoice.totalMinor / 100).toFixed(2) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="flex items-center justify-between w-full">
        <BButton variant="ghost" @click="emit('close')">
          {{ t('common.close') }}
        </BButton>
        <div class="flex items-center gap-2">
          <BButton variant="secondary" @click="handleEdit">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ t('common.edit') }}
          </BButton>
          <BButton variant="primary" @click="handlePrint">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            {{ t('finance.preview.print') }}
          </BButton>
        </div>
      </div>
    </template>
  </BModal>
</template>
