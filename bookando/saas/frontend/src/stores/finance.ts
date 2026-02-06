/**
 * Finance Store — Finanzverwaltung (GeBueV-konform)
 *
 * Pinia Store fuer:
 * - Rechnungen (QR-Bill / Swiss QR Reference)
 * - Buchhaltung (Journal, Kontenplan)
 * - Lohnbuchhaltung (Swissdec ELM 5.0/5.5)
 * - Mahnwesen (Mahnstufe 1-3)
 *
 * Alle Betraege in Minor Units (Rappen/Cents).
 * Waehrung: CHF (Standard), EUR als Option.
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

// ============================================================================
// TYPES
// ============================================================================

export type InvoiceStatus = 'DRAFT' | 'SENT' | 'PAID' | 'OVERDUE' | 'CANCELLED';
export type PaymentMethod = 'BANK_TRANSFER' | 'QR_BILL' | 'TWINT' | 'CASH' | 'CARD';
export type SalaryStatus = 'DRAFT' | 'SUBMITTED' | 'CONFIRMED';

export interface LineItem {
  id: string;
  description: string;
  quantity: number;
  unitPriceMinor: number;
  totalMinor: number;
  vatRatePercent: number;
}

export interface Invoice {
  id: string;
  number: string;
  customerId: string;
  customerName: string;
  status: InvoiceStatus;
  issueDate: string;
  dueDate: string;
  lineItems: LineItem[];
  totalMinor: number;
  taxMinor: number;
  currency: 'CHF' | 'EUR';
  dunningLevel: 0 | 1 | 2 | 3;
  qrReference: string;
  paymentMethod: PaymentMethod;
  notes?: string;
}

export interface AccountEntry {
  id: string;
  date: string;
  account: string;
  contraAccount: string;
  description: string;
  debitMinor: number;
  creditMinor: number;
  reference: string;
}

export interface SalaryDeclaration {
  id: string;
  employeeId: string;
  employeeName: string;
  year: number;
  month: number;
  grossMinor: number;
  ahvMinor: number;
  alvMinor: number;
  bvgMinor: number;
  nbuMinor: number;
  taxMinor: number;
  netMinor: number;
  status: SalaryStatus;
}

export interface ChartAccount {
  number: string;
  name: string;
  type: 'ASSET' | 'LIABILITY' | 'REVENUE' | 'EXPENSE';
  balanceMinor: number;
}

// ============================================================================
// HELPER
// ============================================================================

function generateInvoiceNumber(year: number, seq: number): string {
  return `INV-${year}-${String(seq).padStart(5, '0')}`;
}

// ============================================================================
// STORE
// ============================================================================

export const useFinanceStore = defineStore('finance', () => {
  // ------- State -------
  const invoices = ref<Invoice[]>([]);
  const journalEntries = ref<AccountEntry[]>([]);
  const chartOfAccounts = ref<ChartAccount[]>([]);
  const salaryDeclarations = ref<SalaryDeclaration[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // ------- Fetch functions -------

  async function fetchInvoices(): Promise<void> {
    try {
      const response = await api.get<{ data: Invoice[] }>('/v1/invoices', { per_page: 100 });
      invoices.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load invoices';
    }
  }

  async function fetchJournalEntries(): Promise<void> {
    try {
      const response = await api.get<{ data: AccountEntry[] }>('/v1/finance/journal-entries', { per_page: 100 });
      journalEntries.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load journal entries';
    }
  }

  async function fetchChartAccounts(): Promise<void> {
    try {
      const response = await api.get<{ data: ChartAccount[] }>('/v1/finance/chart-accounts');
      chartOfAccounts.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load chart of accounts';
    }
  }

  async function fetchSalaryDeclarations(): Promise<void> {
    try {
      const response = await api.get<{ data: SalaryDeclaration[] }>('/v1/finance/salary-declarations', { per_page: 100 });
      salaryDeclarations.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load salary declarations';
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([
        fetchInvoices(),
        fetchJournalEntries(),
        fetchChartAccounts(),
        fetchSalaryDeclarations(),
      ]);
    } finally {
      loading.value = false;
    }
  }

  // ------- Computed -------
  const overdueInvoices = computed(() =>
    invoices.value.filter(inv => inv.status === 'OVERDUE')
  );

  const overdueCount = computed(() => overdueInvoices.value.length);

  const openInvoices = computed(() =>
    invoices.value.filter(inv => inv.status === 'SENT' || inv.status === 'OVERDUE')
  );

  const outstandingMinor = computed(() =>
    openInvoices.value.reduce((sum, inv) => sum + inv.totalMinor, 0)
  );

  const revenueThisMonthMinor = computed(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    return invoices.value
      .filter(inv => {
        if (inv.status === 'CANCELLED') return false;
        const d = new Date(inv.issueDate);
        return d.getFullYear() === year && d.getMonth() === month;
      })
      .reduce((sum, inv) => sum + inv.totalMinor, 0);
  });

  const profitThisMonthMinor = computed(() => {
    // Simplified: Revenue minus estimated 60% costs
    return Math.round(revenueThisMonthMinor.value * 0.4);
  });

  const latestInvoices = computed(() =>
    [...invoices.value]
      .sort((a, b) => new Date(b.issueDate).getTime() - new Date(a.issueDate).getTime())
      .slice(0, 5)
  );

  const nextInvoiceNumber = computed(() => {
    const year = new Date().getFullYear();
    const count = invoices.value.filter(inv =>
      inv.number.startsWith(`INV-${year}`)
    ).length;
    return generateInvoiceNumber(year, count + 1);
  });

  const totalSalaryGrossMinor = computed(() =>
    salaryDeclarations.value.reduce((sum, s) => sum + s.grossMinor, 0)
  );

  const totalSalaryNetMinor = computed(() =>
    salaryDeclarations.value.reduce((sum, s) => sum + s.netMinor, 0)
  );

  // ------- Account groupings -------
  const assetAccounts = computed(() =>
    chartOfAccounts.value.filter(a => a.type === 'ASSET')
  );

  const liabilityAccounts = computed(() =>
    chartOfAccounts.value.filter(a => a.type === 'LIABILITY')
  );

  const revenueAccounts = computed(() =>
    chartOfAccounts.value.filter(a => a.type === 'REVENUE')
  );

  const expenseAccounts = computed(() =>
    chartOfAccounts.value.filter(a => a.type === 'EXPENSE')
  );

  const totalAssetsMinor = computed(() =>
    assetAccounts.value.reduce((sum, a) => sum + a.balanceMinor, 0)
  );

  const totalLiabilitiesMinor = computed(() =>
    liabilityAccounts.value.reduce((sum, a) => sum + a.balanceMinor, 0)
  );

  const totalRevenueMinor = computed(() =>
    revenueAccounts.value.reduce((sum, a) => sum + a.balanceMinor, 0)
  );

  const totalExpensesMinor = computed(() =>
    expenseAccounts.value.reduce((sum, a) => sum + a.balanceMinor, 0)
  );

  // ------- Actions -------
  async function createInvoice(data: Omit<Invoice, 'id' | 'number' | 'qrReference'>): Promise<Invoice> {
    const response = await api.post<{ data: Invoice }>('/v1/invoices', data);
    invoices.value.push(response.data);
    return response.data;
  }

  async function updateInvoice(id: string, data: Partial<Invoice>): Promise<void> {
    const response = await api.put<{ data: Invoice }>(`/v1/invoices/${id}`, data);
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value[idx] = response.data;
    }
  }

  async function sendInvoice(id: string): Promise<void> {
    const response = await api.post<{ data: Invoice }>(`/v1/invoices/${id}/send`);
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value[idx] = response.data;
    }
  }

  async function markAsPaid(id: string): Promise<void> {
    const response = await api.post<{ data: Invoice }>(`/v1/invoices/${id}/mark-paid`);
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value[idx] = response.data;
    }
  }

  async function cancelInvoice(id: string): Promise<void> {
    const response = await api.post<{ data: Invoice }>(`/v1/invoices/${id}/cancel`);
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value[idx] = response.data;
    }
  }

  async function sendReminder(id: string): Promise<void> {
    const response = await api.post<{ data: Invoice }>(`/v1/invoices/${id}/remind`);
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value[idx] = response.data;
    }
  }

  async function deleteInvoice(id: string): Promise<void> {
    await api.delete(`/v1/invoices/${id}`);
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value.splice(idx, 1);
    }
  }

  async function submitSalaryDeclaration(id: string): Promise<void> {
    const response = await api.patch<{ data: SalaryDeclaration }>(`/v1/finance/salary-declarations/${id}/submit`);
    const idx = salaryDeclarations.value.findIndex(s => s.id === id);
    if (idx !== -1) {
      salaryDeclarations.value[idx] = response.data;
    }
  }

  return {
    // State
    invoices,
    journalEntries,
    chartOfAccounts,
    salaryDeclarations,
    loading,
    error,

    // Fetch
    fetchAll,
    fetchInvoices,
    fetchJournalEntries,
    fetchChartAccounts,
    fetchSalaryDeclarations,

    // Computed — Invoices
    overdueInvoices,
    overdueCount,
    openInvoices,
    outstandingMinor,
    revenueThisMonthMinor,
    profitThisMonthMinor,
    latestInvoices,
    nextInvoiceNumber,

    // Computed — Accounts
    assetAccounts,
    liabilityAccounts,
    revenueAccounts,
    expenseAccounts,
    totalAssetsMinor,
    totalLiabilitiesMinor,
    totalRevenueMinor,
    totalExpensesMinor,

    // Computed — Salary
    totalSalaryGrossMinor,
    totalSalaryNetMinor,

    // Actions
    createInvoice,
    updateInvoice,
    sendInvoice,
    markAsPaid,
    cancelInvoice,
    sendReminder,
    deleteInvoice,
    submitSalaryDeclaration,
  };
});
