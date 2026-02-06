<script setup lang="ts">
/**
 * InvoiceModal — Rechnung erstellen / bearbeiten
 *
 * Features:
 * - Kundenauswahl (BSelect)
 * - Positionen-Tabelle mit Add/Remove
 * - Auto-Berechnung: Subtotal, MwSt, Gesamttotal
 * - Datums-Felder: Rechnungsdatum, Fällig (+30 Tage Standard)
 * - Zahlungsart, Notizen
 * - Fusszeile: Abbrechen / Entwurf / Senden
 */
import { ref, computed, watch } from 'vue';
import BModal from '@/components/ui/BModal.vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTextarea from '@/components/ui/BTextarea.vue';
import { useI18n } from '@/composables/useI18n';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useFinanceStore } from '@/stores/finance';
import type { Invoice, LineItem, PaymentMethod } from '@/stores/finance';
import { BUTTON_STYLES, TABLE_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';

const props = defineProps<{
  isOpen: boolean;
  invoice: Invoice | null;
}>();

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const { t } = useI18n();
const toast = useToast();
const appStore = useAppStore();
const financeStore = useFinanceStore();

// Form state
const customerId = ref('');
const customerName = ref('');
const issueDate = ref('');
const dueDate = ref('');
const paymentMethod = ref<PaymentMethod>('QR_BILL');
const notes = ref('');
const lineItems = ref<LineItem[]>([]);

// Customer options (mock)
const customerOptions = [
  { value: 'cust-001', label: 'Max Muster' },
  { value: 'cust-002', label: 'Anna Müller' },
  { value: 'cust-003', label: 'Peter Schmidt' },
  { value: 'cust-004', label: 'Sandra Keller' },
  { value: 'cust-005', label: 'Thomas Brunner' },
  { value: 'cust-006', label: 'Laura Meier' },
  { value: 'cust-007', label: 'Rico Frei' },
];

const paymentOptions = [
  { value: 'QR_BILL', label: 'QR-Rechnung (Einzahlungsschein)' },
  { value: 'BANK_TRANSFER', label: 'Banküberweisung' },
  { value: 'TWINT', label: 'TWINT' },
  { value: 'CASH', label: 'Bar' },
  { value: 'CARD', label: 'Karte (Debit/Kredit)' },
];

const vatRateOptions = [
  { value: '8.1', label: '8.1% (Normal)' },
  { value: '2.6', label: '2.6% (Reduziert)' },
  { value: '3.8', label: '3.8% (Beherbergung)' },
  { value: '0', label: '0% (Befreit)' },
];

// Computed
const isEditMode = computed(() => !!props.invoice);

const subtotalMinor = computed(() =>
  lineItems.value.reduce((sum, li) => sum + li.totalMinor, 0)
);

const taxByRate = computed(() => {
  const rates: Record<string, number> = {};
  for (const li of lineItems.value) {
    const key = `${li.vatRatePercent}%`;
    const tax = Math.round(li.totalMinor * li.vatRatePercent / 100);
    rates[key] = (rates[key] || 0) + tax;
  }
  return rates;
});

const totalTaxMinor = computed(() =>
  Object.values(taxByRate.value).reduce((sum, v) => sum + v, 0)
);

const grandTotalMinor = computed(() => subtotalMinor.value + totalTaxMinor.value);

// Initialize form
function initForm() {
  if (props.invoice) {
    customerId.value = props.invoice.customerId;
    customerName.value = props.invoice.customerName;
    issueDate.value = props.invoice.issueDate;
    dueDate.value = props.invoice.dueDate;
    paymentMethod.value = props.invoice.paymentMethod;
    notes.value = props.invoice.notes || '';
    lineItems.value = props.invoice.lineItems.map(li => ({ ...li }));
  } else {
    const today = new Date();
    const due = new Date(today);
    due.setDate(due.getDate() + 30);
    customerId.value = '';
    customerName.value = '';
    issueDate.value = today.toISOString().slice(0, 10);
    dueDate.value = due.toISOString().slice(0, 10);
    paymentMethod.value = 'QR_BILL';
    notes.value = '';
    lineItems.value = [createEmptyLineItem()];
  }
}

function createEmptyLineItem(): LineItem {
  return {
    id: `li-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
    description: '',
    quantity: 1,
    unitPriceMinor: 0,
    totalMinor: 0,
    vatRatePercent: 8.1,
  };
}

function addLineItem() {
  lineItems.value.push(createEmptyLineItem());
}

function removeLineItem(index: number) {
  if (lineItems.value.length > 1) {
    lineItems.value.splice(index, 1);
  }
}

function recalcLineItem(index: number) {
  const li = lineItems.value[index];
  li.totalMinor = Math.round(li.quantity * li.unitPriceMinor);
}

function onCustomerChange(val: string) {
  customerId.value = val;
  const opt = customerOptions.find(o => o.value === val);
  customerName.value = opt?.label || '';
}

// Save
function saveDraft() {
  if (!validateForm()) return;
  saveInvoice('DRAFT');
  toast.success(t('finance.invoices.draftSaved'));
  emit('close');
}

function saveAndSend() {
  if (!validateForm()) return;
  saveInvoice('SENT');
  toast.success(t('finance.invoices.sentSuccess'));
  emit('close');
}

function saveInvoice(status: 'DRAFT' | 'SENT') {
  const data = {
    customerId: customerId.value,
    customerName: customerName.value,
    status,
    issueDate: issueDate.value,
    dueDate: dueDate.value,
    lineItems: lineItems.value,
    totalMinor: grandTotalMinor.value,
    taxMinor: totalTaxMinor.value,
    currency: 'CHF' as const,
    dunningLevel: 0 as const,
    paymentMethod: paymentMethod.value,
    notes: notes.value,
  };

  if (props.invoice) {
    financeStore.updateInvoice(props.invoice.id, data);
  } else {
    financeStore.createInvoice(data);
  }
}

function validateForm(): boolean {
  if (!customerId.value) {
    toast.error(t('finance.invoices.validation.customerRequired'));
    return false;
  }
  if (lineItems.value.length === 0 || lineItems.value.every(li => !li.description)) {
    toast.error(t('finance.invoices.validation.lineItemRequired'));
    return false;
  }
  return true;
}

// Watch open state to init form
watch(() => props.isOpen, (open) => {
  if (open) initForm();
});
</script>

<template>
  <BModal
    :model-value="isOpen"
    :title="isEditMode ? t('finance.invoices.editTitle') : t('finance.invoices.createTitle')"
    size="xl"
    @update:model-value="(val: boolean) => !val && emit('close')"
  >
    <div class="space-y-6">
      <!-- Customer & Dates -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BSelect
          :model-value="customerId"
          :options="customerOptions"
          :label="t('finance.invoices.customer')"
          :placeholder="t('finance.invoices.selectCustomer')"
          required
          @update:model-value="onCustomerChange"
        />
        <BSelect
          v-model="paymentMethod"
          :options="paymentOptions"
          :label="t('finance.invoices.paymentMethod')"
        />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="issueDate"
          type="date"
          :label="t('finance.invoices.issueDate')"
          required
        />
        <BInput
          v-model="dueDate"
          type="date"
          :label="t('finance.invoices.dueDate')"
          required
          :hint="t('finance.invoices.dueDateHint')"
        />
      </div>

      <!-- Line Items -->
      <div>
        <label :class="LABEL_STYLES.base">{{ t('finance.invoices.lineItems') }}</label>
        <div class="mt-2 border border-slate-200 rounded-lg overflow-hidden">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase">{{ t('finance.invoices.description') }}</th>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase w-20 text-center">{{ t('finance.invoices.qty') }}</th>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase w-28 text-right">{{ t('finance.invoices.price') }}</th>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase w-24 text-center">{{ t('finance.invoices.vatRate') }}</th>
                <th class="p-3 text-xs font-semibold text-slate-500 uppercase w-28 text-right">{{ t('finance.invoices.total') }}</th>
                <th class="p-3 w-10"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, index) in lineItems" :key="item.id">
                <td class="p-2">
                  <input
                    v-model="item.description"
                    :class="INPUT_STYLES.base"
                    :placeholder="t('finance.invoices.descriptionPlaceholder')"
                    class="!py-1.5 !text-sm"
                  />
                </td>
                <td class="p-2">
                  <input
                    v-model.number="item.quantity"
                    type="number"
                    min="1"
                    :class="INPUT_STYLES.base"
                    class="!py-1.5 !text-sm text-center"
                    @input="recalcLineItem(index)"
                  />
                </td>
                <td class="p-2">
                  <input
                    v-model.number="item.unitPriceMinor"
                    type="number"
                    min="0"
                    step="100"
                    :class="INPUT_STYLES.base"
                    class="!py-1.5 !text-sm text-right"
                    @input="recalcLineItem(index)"
                  />
                </td>
                <td class="p-2">
                  <select
                    v-model.number="item.vatRatePercent"
                    :class="INPUT_STYLES.select"
                    class="!py-1.5 !text-sm"
                  >
                    <option v-for="vat in vatRateOptions" :key="vat.value" :value="Number(vat.value)">
                      {{ vat.label }}
                    </option>
                  </select>
                </td>
                <td class="p-2 text-right">
                  <span class="text-sm font-medium text-slate-900">{{ appStore.formatPrice(item.totalMinor) }}</span>
                </td>
                <td class="p-2 text-center">
                  <button
                    :class="BUTTON_STYLES.icon"
                    class="!p-1"
                    :disabled="lineItems.length <= 1"
                    @click="removeLineItem(index)"
                  >
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="p-3 border-t border-slate-200 bg-slate-50">
            <button
              :class="BUTTON_STYLES.ghost"
              class="!px-3 !py-1.5 text-xs"
              @click="addLineItem"
            >
              <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ t('finance.invoices.addLineItem') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Totals -->
      <div class="flex justify-end">
        <div class="w-full max-w-xs space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-slate-500">{{ t('finance.invoices.subtotal') }}</span>
            <span class="font-medium text-slate-900">{{ appStore.formatPrice(subtotalMinor) }}</span>
          </div>
          <template v-for="(amount, rate) in taxByRate" :key="rate">
            <div class="flex justify-between text-sm">
              <span class="text-slate-500">{{ t('finance.invoices.vatLabel') }} {{ rate }}</span>
              <span class="text-slate-700">{{ appStore.formatPrice(amount) }}</span>
            </div>
          </template>
          <div class="flex justify-between text-sm pt-2 border-t border-slate-200">
            <span class="font-bold text-slate-900">{{ t('finance.invoices.grandTotal') }}</span>
            <span class="font-bold text-slate-900">{{ appStore.formatPrice(grandTotalMinor) }}</span>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <BTextarea
        v-model="notes"
        :label="t('finance.invoices.notes')"
        :placeholder="t('finance.invoices.notesPlaceholder')"
        :rows="3"
      />
    </div>

    <!-- Footer -->
    <template #footer>
      <div class="flex items-center justify-between w-full">
        <BButton variant="ghost" @click="emit('close')">
          {{ t('common.cancel') }}
        </BButton>
        <div class="flex items-center gap-2">
          <BButton variant="secondary" @click="saveDraft">
            {{ t('finance.invoices.saveDraft') }}
          </BButton>
          <BButton variant="primary" @click="saveAndSend">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            {{ t('finance.invoices.sendInvoice') }}
          </BButton>
        </div>
      </div>
    </template>
  </BModal>
</template>
