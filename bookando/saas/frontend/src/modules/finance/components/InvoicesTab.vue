<script setup lang="ts">
/**
 * InvoicesTab — Rechnungsliste & -verwaltung
 *
 * Features:
 * - Suche / Filter
 * - BTable mit Status-Badges, Mahnstufe-Anzeige
 * - Responsive: Karten auf Mobile, Tabelle auf Desktop
 * - Aktionen: Ansehen, Senden, Mahnen, Bezahlt markieren
 */
import { ref, computed, watch } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BTable from '@/components/ui/BTable.vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import InvoiceModal from './InvoiceModal.vue';
import InvoicePreview from './InvoicePreview.vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useFinanceStore } from '@/stores/finance';
import type { Invoice } from '@/stores/finance';
import { BUTTON_STYLES, CARD_STYLES, BADGE_STYLES, TABLE_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const appStore = useAppStore();
const financeStore = useFinanceStore();

// State
const searchQuery = ref('');
const isModalOpen = ref(false);
const isPreviewOpen = ref(false);
const editingInvoice = ref<Invoice | null>(null);
const previewInvoice = ref<Invoice | null>(null);
const page = ref(1);
const perPage = ref(25);
const sortBy = ref('issueDate');
const sortDir = ref<'asc' | 'desc'>('desc');

// Filtered & sorted
const filteredInvoices = computed(() => {
  let result = [...financeStore.invoices];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(inv =>
      inv.number.toLowerCase().includes(q) ||
      inv.customerName.toLowerCase().includes(q) ||
      inv.status.toLowerCase().includes(q)
    );
  }

  result.sort((a, b) => {
    const aVal = String((a as Record<string, unknown>)[sortBy.value] || '');
    const bVal = String((b as Record<string, unknown>)[sortBy.value] || '');
    const cmp = aVal.localeCompare(bVal, 'de');
    return sortDir.value === 'asc' ? cmp : -cmp;
  });

  return result;
});

const total = computed(() => filteredInvoices.value.length);

const paginatedInvoices = computed(() => {
  const start = (page.value - 1) * perPage.value;
  return filteredInvoices.value.slice(start, start + perPage.value);
});

// Table columns
const columns = [
  { key: 'number', label: 'Nr.', sortable: true },
  { key: 'customerName', label: 'Kunde', sortable: true },
  { key: 'issueDate', label: 'Datum', sortable: true },
  { key: 'dueDate', label: 'Fällig', sortable: true },
  { key: 'totalMinor', label: 'Total', sortable: true, align: 'right' as const },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', width: '140px', align: 'right' as const },
];

// Status mapping for BBadge
function getStatusKey(status: string): string {
  return status.toLowerCase();
}

// Actions
function openCreate() {
  editingInvoice.value = null;
  isModalOpen.value = true;
}

function openEdit(invoice: Invoice) {
  editingInvoice.value = invoice;
  isModalOpen.value = true;
}

function openPreview(invoice: Invoice) {
  previewInvoice.value = invoice;
  isPreviewOpen.value = true;
}

function handleSend(invoice: Invoice) {
  financeStore.sendInvoice(invoice.id);
  toast.success(t('finance.invoices.sentSuccess'));
}

function handleRemind(invoice: Invoice) {
  financeStore.sendReminder(invoice.id);
  toast.info(t('finance.invoices.reminderSent'));
}

function handleMarkPaid(invoice: Invoice) {
  financeStore.markAsPaid(invoice.id);
  toast.success(t('finance.invoices.paidSuccess'));
}

function handleSort(column: string) {
  if (sortBy.value === column) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = column;
    sortDir.value = 'asc';
  }
}

function handleModalClose() {
  isModalOpen.value = false;
  editingInvoice.value = null;
}

// Reset page on search
watch(searchQuery, () => {
  page.value = 1;
});
</script>

<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <div class="flex-1">
        <BSearchBar
          v-model="searchQuery"
          :placeholder="t('finance.invoices.searchPlaceholder')"
        />
      </div>
      <BButton
        variant="primary"
        class="hidden md:flex"
        @click="openCreate"
      >
        <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('finance.newInvoice') }}
      </BButton>
    </div>

    <!-- Mobile: Card view -->
    <div v-if="isMobile" class="space-y-3">
      <div
        v-for="inv in paginatedInvoices"
        :key="inv.id"
        :class="CARD_STYLES.listItem"
        class="cursor-pointer"
        @click="openPreview(inv)"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900">{{ inv.customerName }}</p>
            <p class="text-xs text-slate-500">{{ inv.number }}</p>
          </div>
          <BBadge :status="getStatusKey(inv.status)" dot>
            {{ t(`finance.status.${inv.status}`) }}
          </BBadge>
        </div>
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span>{{ appStore.formatDate(inv.issueDate) }} &mdash; {{ appStore.formatDate(inv.dueDate) }}</span>
          <span class="font-bold text-sm text-slate-900">{{ appStore.formatPrice(inv.totalMinor) }}</span>
        </div>
        <!-- Dunning Level -->
        <div v-if="inv.dunningLevel > 0" class="mt-2">
          <span :class="BADGE_STYLES.danger">
            {{ t('finance.invoices.dunningLevel') }} {{ inv.dunningLevel }}
          </span>
        </div>
        <!-- Actions -->
        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center gap-2">
          <button
            v-if="inv.status === 'DRAFT'"
            :class="BUTTON_STYLES.ghost"
            class="!px-3 !py-1.5 text-xs"
            @click.stop="handleSend(inv)"
          >
            {{ t('finance.invoices.send') }}
          </button>
          <button
            v-if="inv.status === 'OVERDUE'"
            :class="BUTTON_STYLES.ghost"
            class="!px-3 !py-1.5 text-xs"
            @click.stop="handleRemind(inv)"
          >
            {{ t('finance.invoices.remind') }}
          </button>
          <button
            v-if="inv.status === 'SENT' || inv.status === 'OVERDUE'"
            :class="BUTTON_STYLES.ghost"
            class="!px-3 !py-1.5 text-xs"
            @click.stop="handleMarkPaid(inv)"
          >
            {{ t('finance.invoices.markPaid') }}
          </button>
        </div>
      </div>

      <BEmptyState
        v-if="paginatedInvoices.length === 0"
        :title="t('finance.invoices.emptyTitle')"
        :description="t('finance.invoices.emptyDescription')"
        icon="inbox"
        :action-label="t('finance.newInvoice')"
        @action="openCreate"
      />
    </div>

    <!-- Desktop: Table view -->
    <BTable
      v-else
      :columns="columns"
      :data="paginatedInvoices as Record<string, unknown>[]"
      :sort-by="sortBy"
      :sort-dir="sortDir"
      :page="page"
      :per-page="perPage"
      :total="total"
      :empty-title="t('finance.invoices.emptyTitle')"
      :empty-message="t('finance.invoices.emptyDescription')"
      @sort="handleSort"
      @page-change="(p: number) => page = p"
      @row-click="(row: Record<string, unknown>) => openPreview(row as unknown as Invoice)"
    >
      <template #cell-number="{ row }">
        <span class="text-sm font-medium text-slate-900">{{ (row as Record<string, unknown>).number }}</span>
      </template>

      <template #cell-issueDate="{ row }">
        <span class="text-sm text-slate-500">{{ appStore.formatDate(String((row as Record<string, unknown>).issueDate)) }}</span>
      </template>

      <template #cell-dueDate="{ row }">
        <span class="text-sm text-slate-500">{{ appStore.formatDate(String((row as Record<string, unknown>).dueDate)) }}</span>
      </template>

      <template #cell-totalMinor="{ row }">
        <span class="text-sm font-semibold text-slate-900">{{ appStore.formatPrice(Number((row as Record<string, unknown>).totalMinor)) }}</span>
      </template>

      <template #cell-status="{ row }">
        <div class="flex items-center gap-2">
          <BBadge :status="getStatusKey(String((row as Record<string, unknown>).status))" dot>
            {{ t(`finance.status.${(row as Record<string, unknown>).status}`) }}
          </BBadge>
          <span
            v-if="Number((row as Record<string, unknown>).dunningLevel) > 0"
            :class="BADGE_STYLES.danger"
          >
            {{ t('finance.invoices.dunningLevel') }} {{ (row as Record<string, unknown>).dunningLevel }}
          </span>
        </div>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <button
            :class="BUTTON_STYLES.icon"
            :title="t('finance.invoices.view')"
            @click.stop="openPreview(row as unknown as Invoice)"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
          <button
            v-if="(row as Record<string, unknown>).status === 'DRAFT'"
            :class="BUTTON_STYLES.icon"
            :title="t('finance.invoices.send')"
            @click.stop="handleSend(row as unknown as Invoice)"
          >
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
          <button
            v-if="(row as Record<string, unknown>).status === 'OVERDUE'"
            :class="BUTTON_STYLES.icon"
            :title="t('finance.invoices.remind')"
            @click.stop="handleRemind(row as unknown as Invoice)"
          >
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </button>
          <button
            v-if="(row as Record<string, unknown>).status === 'SENT' || (row as Record<string, unknown>).status === 'OVERDUE'"
            :class="BUTTON_STYLES.icon"
            :title="t('finance.invoices.markPaid')"
            @click.stop="handleMarkPaid(row as unknown as Invoice)"
          >
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </button>
        </div>
      </template>

      <template #empty-action>
        <BButton variant="primary" class="mt-2" @click="openCreate">
          {{ t('finance.newInvoice') }}
        </BButton>
      </template>
    </BTable>

    <!-- Invoice Modal (Create / Edit) -->
    <InvoiceModal
      :is-open="isModalOpen"
      :invoice="editingInvoice"
      @close="handleModalClose"
    />

    <!-- Invoice Preview (QR-Bill) -->
    <InvoicePreview
      :is-open="isPreviewOpen"
      :invoice="previewInvoice"
      @close="isPreviewOpen = false"
      @edit="(inv: Invoice) => { isPreviewOpen = false; openEdit(inv); }"
    />
  </div>
</template>
