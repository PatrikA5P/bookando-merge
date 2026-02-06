/**
 * Finance Store — Finanzverwaltung (GeBüV-konform)
 *
 * Pinia Store für:
 * - Rechnungen (QR-Bill / Swiss QR Reference)
 * - Buchhaltung (Journal, Kontenplan)
 * - Lohnbuchhaltung (Swissdec ELM 5.0/5.5)
 * - Mahnwesen (Mahnstufe 1–3)
 *
 * Alle Beträge in Minor Units (Rappen/Cents).
 * Währung: CHF (Standard), EUR als Option.
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

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

function generateQrReference(): string {
  const digits = Array.from({ length: 26 }, () => Math.floor(Math.random() * 10)).join('');
  return digits;
}

function generateInvoiceNumber(year: number, seq: number): string {
  return `INV-${year}-${String(seq).padStart(5, '0')}`;
}

// ============================================================================
// STORE
// ============================================================================

export const useFinanceStore = defineStore('finance', () => {
  // ------- Invoices -------
  const invoices = ref<Invoice[]>([
    {
      id: 'inv-001',
      number: generateInvoiceNumber(2026, 1),
      customerId: 'cust-001',
      customerName: 'Max Muster',
      status: 'PAID',
      issueDate: '2026-01-05',
      dueDate: '2026-02-04',
      lineItems: [
        { id: 'li-001', description: 'Haarschnitt Herren', quantity: 1, unitPriceMinor: 4500, totalMinor: 4500, vatRatePercent: 8.1 },
        { id: 'li-002', description: 'Bartpflege', quantity: 1, unitPriceMinor: 2500, totalMinor: 2500, vatRatePercent: 8.1 },
      ],
      totalMinor: 7000,
      taxMinor: 567,
      currency: 'CHF',
      dunningLevel: 0,
      qrReference: generateQrReference(),
      paymentMethod: 'QR_BILL',
    },
    {
      id: 'inv-002',
      number: generateInvoiceNumber(2026, 2),
      customerId: 'cust-002',
      customerName: 'Anna Müller',
      status: 'SENT',
      issueDate: '2026-01-12',
      dueDate: '2026-02-11',
      lineItems: [
        { id: 'li-003', description: 'Coloration komplett', quantity: 1, unitPriceMinor: 12000, totalMinor: 12000, vatRatePercent: 8.1 },
        { id: 'li-004', description: 'Schnitt & Föhnen', quantity: 1, unitPriceMinor: 6500, totalMinor: 6500, vatRatePercent: 8.1 },
      ],
      totalMinor: 18500,
      taxMinor: 1499,
      currency: 'CHF',
      dunningLevel: 0,
      qrReference: generateQrReference(),
      paymentMethod: 'QR_BILL',
    },
    {
      id: 'inv-003',
      number: generateInvoiceNumber(2026, 3),
      customerId: 'cust-003',
      customerName: 'Peter Schmidt',
      status: 'OVERDUE',
      issueDate: '2025-12-01',
      dueDate: '2025-12-31',
      lineItems: [
        { id: 'li-005', description: 'Gesichtsbehandlung Deluxe', quantity: 1, unitPriceMinor: 15000, totalMinor: 15000, vatRatePercent: 8.1 },
      ],
      totalMinor: 15000,
      taxMinor: 1215,
      currency: 'CHF',
      dunningLevel: 2,
      qrReference: generateQrReference(),
      paymentMethod: 'BANK_TRANSFER',
    },
    {
      id: 'inv-004',
      number: generateInvoiceNumber(2026, 4),
      customerId: 'cust-004',
      customerName: 'Sandra Keller',
      status: 'DRAFT',
      issueDate: '2026-02-01',
      dueDate: '2026-03-03',
      lineItems: [
        { id: 'li-006', description: 'Massage 60min', quantity: 2, unitPriceMinor: 9000, totalMinor: 18000, vatRatePercent: 8.1 },
        { id: 'li-007', description: 'Aromaöl-Upgrade', quantity: 2, unitPriceMinor: 1500, totalMinor: 3000, vatRatePercent: 8.1 },
      ],
      totalMinor: 21000,
      taxMinor: 1701,
      currency: 'CHF',
      dunningLevel: 0,
      qrReference: generateQrReference(),
      paymentMethod: 'QR_BILL',
    },
    {
      id: 'inv-005',
      number: generateInvoiceNumber(2026, 5),
      customerId: 'cust-005',
      customerName: 'Thomas Brunner',
      status: 'PAID',
      issueDate: '2026-01-20',
      dueDate: '2026-02-19',
      lineItems: [
        { id: 'li-008', description: 'Maniküre', quantity: 1, unitPriceMinor: 5500, totalMinor: 5500, vatRatePercent: 8.1 },
        { id: 'li-009', description: 'Pediküre', quantity: 1, unitPriceMinor: 6500, totalMinor: 6500, vatRatePercent: 8.1 },
      ],
      totalMinor: 12000,
      taxMinor: 972,
      currency: 'CHF',
      dunningLevel: 0,
      qrReference: generateQrReference(),
      paymentMethod: 'TWINT',
    },
    {
      id: 'inv-006',
      number: generateInvoiceNumber(2026, 6),
      customerId: 'cust-006',
      customerName: 'Laura Meier',
      status: 'OVERDUE',
      issueDate: '2025-11-15',
      dueDate: '2025-12-15',
      lineItems: [
        { id: 'li-010', description: 'Balayage + Pflege', quantity: 1, unitPriceMinor: 22000, totalMinor: 22000, vatRatePercent: 8.1 },
      ],
      totalMinor: 22000,
      taxMinor: 1782,
      currency: 'CHF',
      dunningLevel: 3,
      qrReference: generateQrReference(),
      paymentMethod: 'QR_BILL',
    },
    {
      id: 'inv-007',
      number: generateInvoiceNumber(2026, 7),
      customerId: 'cust-002',
      customerName: 'Anna Müller',
      status: 'PAID',
      issueDate: '2025-12-10',
      dueDate: '2026-01-09',
      lineItems: [
        { id: 'li-011', description: 'Wimpern-Lifting', quantity: 1, unitPriceMinor: 8500, totalMinor: 8500, vatRatePercent: 8.1 },
      ],
      totalMinor: 8500,
      taxMinor: 689,
      currency: 'CHF',
      dunningLevel: 0,
      qrReference: generateQrReference(),
      paymentMethod: 'CARD',
    },
    {
      id: 'inv-008',
      number: generateInvoiceNumber(2026, 8),
      customerId: 'cust-007',
      customerName: 'Rico Frei',
      status: 'CANCELLED',
      issueDate: '2026-01-25',
      dueDate: '2026-02-24',
      lineItems: [
        { id: 'li-012', description: 'Hochzeitsstyling Paket', quantity: 1, unitPriceMinor: 35000, totalMinor: 35000, vatRatePercent: 8.1 },
      ],
      totalMinor: 35000,
      taxMinor: 2835,
      currency: 'CHF',
      dunningLevel: 0,
      qrReference: generateQrReference(),
      paymentMethod: 'BANK_TRANSFER',
    },
  ]);

  // ------- Journal Entries -------
  const journalEntries = ref<AccountEntry[]>([
    {
      id: 'je-001',
      date: '2026-01-05',
      account: '1100',
      contraAccount: '3400',
      description: 'Rechnung INV-2026-00001 – Max Muster',
      debitMinor: 7000,
      creditMinor: 0,
      reference: 'INV-2026-00001',
    },
    {
      id: 'je-002',
      date: '2026-01-12',
      account: '1100',
      contraAccount: '3400',
      description: 'Rechnung INV-2026-00002 – Anna Müller',
      debitMinor: 18500,
      creditMinor: 0,
      reference: 'INV-2026-00002',
    },
    {
      id: 'je-003',
      date: '2026-01-20',
      account: '1020',
      contraAccount: '1100',
      description: 'Zahlung Thomas Brunner (TWINT)',
      debitMinor: 12000,
      creditMinor: 0,
      reference: 'INV-2026-00005',
    },
    {
      id: 'je-004',
      date: '2026-01-31',
      account: '5000',
      contraAccount: '1020',
      description: 'Löhne Januar 2026',
      debitMinor: 0,
      creditMinor: 1850000,
      reference: 'SAL-2026-01',
    },
    {
      id: 'je-005',
      date: '2026-01-31',
      account: '2200',
      contraAccount: '1020',
      description: 'MwSt-Abrechnung Q4/2025',
      debitMinor: 0,
      creditMinor: 485000,
      reference: 'VAT-2025-Q4',
    },
  ]);

  // ------- Chart of Accounts -------
  const chartOfAccounts = ref<ChartAccount[]>([
    { number: '1020', name: 'Bankguthaben (PostFinance)', type: 'ASSET', balanceMinor: 8745000 },
    { number: '1100', name: 'Forderungen aus Lieferungen/Leistungen', type: 'ASSET', balanceMinor: 5550000 },
    { number: '1170', name: 'Vorsteuer MwSt', type: 'ASSET', balanceMinor: 124000 },
    { number: '1200', name: 'Warenvorräte', type: 'ASSET', balanceMinor: 320000 },
    { number: '2000', name: 'Verbindlichkeiten aus L/L', type: 'LIABILITY', balanceMinor: 185000 },
    { number: '2200', name: 'Geschuldete MwSt', type: 'LIABILITY', balanceMinor: 485000 },
    { number: '2270', name: 'Sozialversicherungen', type: 'LIABILITY', balanceMinor: 310000 },
    { number: '2800', name: 'Eigenkapital', type: 'LIABILITY', balanceMinor: 5000000 },
    { number: '3400', name: 'Dienstleistungsertrag', type: 'REVENUE', balanceMinor: 12450000 },
    { number: '3800', name: 'Rabatte / Skonti', type: 'REVENUE', balanceMinor: -85000 },
    { number: '4400', name: 'Aufwand Material', type: 'EXPENSE', balanceMinor: 890000 },
    { number: '5000', name: 'Lohnaufwand', type: 'EXPENSE', balanceMinor: 5550000 },
    { number: '5700', name: 'Sozialversicherungsaufwand', type: 'EXPENSE', balanceMinor: 720000 },
    { number: '6000', name: 'Mietaufwand', type: 'EXPENSE', balanceMinor: 3600000 },
    { number: '6500', name: 'Verwaltungsaufwand', type: 'EXPENSE', balanceMinor: 245000 },
  ]);

  // ------- Salary Declarations -------
  const salaryDeclarations = ref<SalaryDeclaration[]>([
    {
      id: 'sal-001',
      employeeId: 'emp-001',
      employeeName: 'Lisa Weber',
      year: 2026,
      month: 1,
      grossMinor: 650000,
      ahvMinor: 34450,
      alvMinor: 7150,
      bvgMinor: 32500,
      nbuMinor: 4550,
      taxMinor: 45500,
      netMinor: 525850,
      status: 'CONFIRMED',
    },
    {
      id: 'sal-002',
      employeeId: 'emp-002',
      employeeName: 'Marco Bianchi',
      year: 2026,
      month: 1,
      grossMinor: 580000,
      ahvMinor: 30740,
      alvMinor: 6380,
      bvgMinor: 29000,
      nbuMinor: 4060,
      taxMinor: 38200,
      netMinor: 471620,
      status: 'SUBMITTED',
    },
    {
      id: 'sal-003',
      employeeId: 'emp-003',
      employeeName: 'Sarah Keller',
      year: 2026,
      month: 1,
      grossMinor: 620000,
      ahvMinor: 32860,
      alvMinor: 6820,
      bvgMinor: 31000,
      nbuMinor: 4340,
      taxMinor: 42500,
      netMinor: 502480,
      status: 'DRAFT',
    },
  ]);

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
  function createInvoice(data: Omit<Invoice, 'id' | 'number' | 'qrReference'>): Invoice {
    const invoice: Invoice = {
      ...data,
      id: `inv-${Date.now()}`,
      number: nextInvoiceNumber.value,
      qrReference: generateQrReference(),
    };
    invoices.value.push(invoice);
    return invoice;
  }

  function updateInvoice(id: string, data: Partial<Invoice>) {
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value[idx] = { ...invoices.value[idx], ...data };
    }
  }

  function sendInvoice(id: string) {
    updateInvoice(id, { status: 'SENT' });
  }

  function markAsPaid(id: string) {
    updateInvoice(id, { status: 'PAID', dunningLevel: 0 });
  }

  function cancelInvoice(id: string) {
    updateInvoice(id, { status: 'CANCELLED' });
  }

  function sendReminder(id: string) {
    const inv = invoices.value.find(i => i.id === id);
    if (inv && inv.status === 'OVERDUE' && inv.dunningLevel < 3) {
      updateInvoice(id, {
        dunningLevel: Math.min(inv.dunningLevel + 1, 3) as 0 | 1 | 2 | 3,
      });
    }
  }

  function deleteInvoice(id: string) {
    const idx = invoices.value.findIndex(inv => inv.id === id);
    if (idx !== -1) {
      invoices.value.splice(idx, 1);
    }
  }

  function submitSalaryDeclaration(id: string) {
    const idx = salaryDeclarations.value.findIndex(s => s.id === id);
    if (idx !== -1) {
      salaryDeclarations.value[idx] = {
        ...salaryDeclarations.value[idx],
        status: 'SUBMITTED',
      };
    }
  }

  return {
    // State
    invoices,
    journalEntries,
    chartOfAccounts,
    salaryDeclarations,

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
