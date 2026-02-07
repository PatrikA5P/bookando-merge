<script setup lang="ts">
/**
 * VouchersTab — Gutschein-Verwaltung
 *
 * Tabelle aller Gutscheine mit Erstellung, Statusanzeige,
 * Nutzungsfortschritt und Copy-Funktion.
 *
 * GOLD STANDARD: BFormPanel (SlideIn) statt BModal (Overlay).
 * Neue Domain-Types: Voucher mit discountType/discountValue statt type/value.
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useOffersStore } from '@/stores/offers';
import type { Voucher, VoucherDiscountType, VoucherCategory } from '@/stores/offers';
import { formatMoney } from '@/utils/money';
import { CARD_STYLES, BUTTON_STYLES, LABEL_STYLES } from '@/design';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BTable from '@/components/ui/BTable.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BFormPanel from '@/components/ui/BFormPanel.vue';
import BFormSection from '@/components/ui/BFormSection.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BMoneyInput from '@/components/ui/BMoneyInput.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const appStore = useAppStore();
const store = useOffersStore();

const showPanel = ref(false);
const editingVoucher = ref<Voucher | null>(null);
const saving = ref(false);

// Form
const form = ref({
  title: '',
  code: '',
  category: 'PROMOTION' as VoucherCategory,
  discountType: 'PERCENTAGE' as VoucherDiscountType,
  discountValue: 10,
  maxUses: 100,
  maxUsesPerCustomer: undefined as number | undefined,
  expiresAt: '',
  active: true,
  minOrderCents: 0,
});
const errors = ref<Record<string, string>>({});

const isEditing = computed(() => !!editingVoucher.value);
const dirty = computed(() => form.value.code !== '' || form.value.title !== '');

const categoryOptions = [
  { value: 'PROMOTION', label: 'Promotion' },
  { value: 'GIFT_CARD', label: 'Geschenkkarte' },
];

const discountTypeOptions = [
  { value: 'PERCENTAGE', label: 'Prozent (%)' },
  { value: 'FIXED', label: 'Festbetrag (' + appStore.currency + ')' },
];

const columns = computed(() => [
  { key: 'code', label: t('offers.code'), sortable: true },
  { key: 'discountType', label: t('common.type'), sortable: true },
  { key: 'discountValue', label: 'Wert', sortable: true },
  { key: 'usage', label: t('offers.usageCount'), sortable: false },
  { key: 'expiresAt', label: t('offers.expiresAt'), sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: '', align: 'right' as const },
]);

const tableData = computed(() =>
  store.vouchers.map(v => ({
    id: v.id,
    title: v.title,
    code: v.code,
    category: v.category,
    discountType: v.discountType,
    discountValue: v.discountValue,
    maxUses: v.maxUses,
    usedCount: v.usedCount,
    expiresAt: v.expiresAt,
    active: v.active,
    minOrderCents: v.minOrderCents,
    status: getVoucherStatus(v),
    usagePercent: v.maxUses > 0 ? Math.round((v.usedCount / v.maxUses) * 100) : 0,
  }))
);

function getVoucherStatus(v: Voucher): string {
  if (v.usedCount >= v.maxUses) return 'depleted';
  if (v.expiresAt) {
    const now = new Date();
    const expiry = new Date(v.expiresAt);
    if (expiry < now) return 'expired';
  }
  if (v.active) return 'active';
  return 'inactive';
}

function getStatusBadgeVariant(status: string): 'success' | 'danger' | 'default' {
  switch (status) {
    case 'active': return 'success';
    case 'expired': return 'danger';
    case 'depleted': return 'default';
    default: return 'default';
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case 'active': return t('common.active');
    case 'expired': return 'Abgelaufen';
    case 'depleted': return 'Aufgebraucht';
    default: return t('common.inactive');
  }
}

function formatDiscountValue(voucher: { discountType: string; discountValue: number }): string {
  if (voucher.discountType === 'PERCENTAGE') {
    return voucher.discountValue + '%';
  }
  return formatMoney(voucher.discountValue);
}

function copyCode(code: string) {
  navigator.clipboard.writeText(code).then(() => {
    toast.success(`Code "${code}" kopiert`);
  }).catch(() => {
    toast.error('Kopieren fehlgeschlagen');
  });
}

function generateCode(): string {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let result = '';
  for (let i = 0; i < 8; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

function onCreateVoucher() {
  editingVoucher.value = null;
  form.value = {
    title: '',
    code: generateCode(),
    category: 'PROMOTION',
    discountType: 'PERCENTAGE',
    discountValue: 10,
    maxUses: 100,
    maxUsesPerCustomer: undefined,
    expiresAt: new Date(Date.now() + 90 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
    active: true,
    minOrderCents: 0,
  };
  errors.value = {};
  showPanel.value = true;
}

function onEditVoucher(row: Record<string, unknown>) {
  const voucher = store.vouchers.find(v => v.id === row.id);
  if (!voucher) return;
  editingVoucher.value = voucher;
  form.value = {
    title: voucher.title,
    code: voucher.code,
    category: voucher.category,
    discountType: voucher.discountType,
    discountValue: voucher.discountValue,
    maxUses: voucher.maxUses,
    maxUsesPerCustomer: voucher.maxUsesPerCustomer,
    expiresAt: voucher.expiresAt || '',
    active: voucher.active,
    minOrderCents: voucher.minOrderCents || 0,
  };
  errors.value = {};
  showPanel.value = true;
}

function onDeleteVoucher(id: string) {
  store.deleteVoucher(id);
  toast.success('Gutschein geloescht');
}

function validate(): boolean {
  const errs: Record<string, string> = {};
  if (!form.value.code.trim()) {
    errs.code = t('common.required');
  }
  if (form.value.discountValue <= 0) {
    errs.discountValue = 'Wert > 0';
  }
  if (form.value.discountType === 'PERCENTAGE' && form.value.discountValue > 100) {
    errs.discountValue = 'Max 100%';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

async function onSave() {
  if (!validate()) return;

  saving.value = true;
  try {
    const data = {
      title: form.value.title,
      code: form.value.code,
      category: form.value.category,
      discountType: form.value.discountType,
      discountValue: form.value.discountValue,
      maxUses: form.value.maxUses,
      maxUsesPerCustomer: form.value.maxUsesPerCustomer,
      expiresAt: form.value.expiresAt || undefined,
      active: form.value.active,
      minOrderCents: form.value.minOrderCents > 0 ? form.value.minOrderCents : undefined,
    };

    if (editingVoucher.value) {
      await store.updateVoucher(editingVoucher.value.id, data);
    } else {
      await store.addVoucher(data);
    }
    toast.success(t('common.saved'));
    showPanel.value = false;
  } catch {
    toast.error('Fehler beim Speichern');
  } finally {
    saving.value = false;
  }
}

function onPanelClose() {
  showPanel.value = false;
  editingVoucher.value = null;
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
      <h2 class="text-lg font-semibold text-slate-900">{{ t('offers.vouchers') }}</h2>
      <BButton variant="primary" @click="onCreateVoucher">
        <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('offers.vouchers') }} {{ t('common.create') }}
      </BButton>
    </div>

    <!-- Leerer Zustand -->
    <BEmptyState
      v-if="store.vouchers.length === 0"
      title="Keine Gutscheine"
      description="Erstellen Sie Ihren ersten Gutscheincode."
      icon="inbox"
      :action-label="t('common.create')"
      @action="onCreateVoucher"
    />

    <!-- Mobile Cards -->
    <div v-else-if="isMobile" class="space-y-3">
      <div
        v-for="voucher in tableData"
        :key="voucher.id"
        :class="CARD_STYLES.listItem"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <code class="text-sm font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded">
              {{ voucher.code }}
            </code>
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              @click="copyCode(voucher.code as string)"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
          <BBadge :variant="getStatusBadgeVariant(voucher.status as string)" :dot="true">
            {{ getStatusLabel(voucher.status as string) }}
          </BBadge>
        </div>
        <div class="flex items-center justify-between text-sm text-slate-600">
          <span class="font-medium">{{ formatDiscountValue(voucher as any) }}</span>
          <span>{{ voucher.usedCount }}/{{ voucher.maxUses }}</span>
        </div>
        <!-- Usage bar -->
        <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-300"
            :class="[
              (voucher.usagePercent as number) >= 100 ? 'bg-slate-400' :
              (voucher.usagePercent as number) >= 75 ? 'bg-amber-500' : 'bg-emerald-500'
            ]"
            :style="{ width: Math.min(voucher.usagePercent as number, 100) + '%' }"
          />
        </div>
        <div v-if="voucher.expiresAt" class="text-xs text-slate-400 mt-1">
          {{ t('offers.expiresAt') }}: {{ appStore.formatDate(voucher.expiresAt as string) }}
        </div>
      </div>
    </div>

    <!-- Desktop Table -->
    <BTable
      v-else
      :columns="columns"
      :data="tableData"
      :empty-title="'Keine Gutscheine'"
      @row-click="onEditVoucher"
    >
      <!-- Code -->
      <template #cell-code="{ row }">
        <div class="flex items-center gap-2">
          <code class="text-sm font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded">
            {{ row.code }}
          </code>
          <button
            :class="BUTTON_STYLES.icon"
            class="!p-1"
            @click.stop="copyCode(row.code as string)"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </button>
        </div>
      </template>

      <!-- Type -->
      <template #cell-discountType="{ row }">
        <BBadge :variant="row.discountType === 'PERCENTAGE' ? 'info' : 'brand'">
          {{ row.discountType === 'PERCENTAGE' ? 'Prozent' : 'Festbetrag' }}
        </BBadge>
      </template>

      <!-- Value -->
      <template #cell-discountValue="{ row }">
        <span class="font-medium text-slate-900">
          {{ formatDiscountValue(row as any) }}
        </span>
      </template>

      <!-- Usage -->
      <template #cell-usage="{ row }">
        <div class="min-w-[120px]">
          <div class="flex items-center justify-between text-xs text-slate-600 mb-1">
            <span>{{ row.usedCount }}/{{ row.maxUses }}</span>
            <span>{{ row.usagePercent }}%</span>
          </div>
          <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-300"
              :class="[
                (row.usagePercent as number) >= 100 ? 'bg-slate-400' :
                (row.usagePercent as number) >= 75 ? 'bg-amber-500' : 'bg-emerald-500'
              ]"
              :style="{ width: Math.min(row.usagePercent as number, 100) + '%' }"
            />
          </div>
        </div>
      </template>

      <!-- Expires -->
      <template #cell-expiresAt="{ row }">
        <span class="text-sm text-slate-600">{{ row.expiresAt ? appStore.formatDate(row.expiresAt as string) : '—' }}</span>
      </template>

      <!-- Status -->
      <template #cell-status="{ row }">
        <BBadge :variant="getStatusBadgeVariant(row.status as string)" :dot="true">
          {{ getStatusLabel(row.status as string) }}
        </BBadge>
      </template>

      <!-- Actions -->
      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <button
            :class="BUTTON_STYLES.icon"
            @click.stop="onDeleteVoucher(row.id as string)"
          >
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </template>
    </BTable>

    <!-- Voucher FormPanel (SlideIn Gold Standard) -->
    <BFormPanel
      :model-value="showPanel"
      :title="isEditing ? 'Gutschein bearbeiten: ' + editingVoucher?.code : 'Neuer Gutschein'"
      :mode="isEditing ? 'edit' : 'create'"
      size="md"
      :saving="saving"
      :dirty="dirty"
      @update:model-value="onPanelClose"
      @save="onSave"
      @cancel="onPanelClose"
    >
      <BFormSection title="Gutschein-Code" :columns="1" divided>
        <div>
          <label :class="LABEL_STYLES.required">{{ t('offers.code') }}</label>
          <div class="flex gap-2">
            <BInput
              v-model="form.code"
              :placeholder="'MEINCODE'"
              :error="errors.code"
              class="flex-1"
            />
            <BButton variant="secondary" @click="form.code = generateCode()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </BButton>
          </div>
        </div>
        <BInput
          v-model="form.title"
          label="Bezeichnung"
          placeholder="z.B. Winteraktion 2026"
        />
      </BFormSection>

      <BFormSection title="Kategorie & Rabatt" :columns="2" divided>
        <BSelect
          v-model="form.category"
          label="Kategorie"
          :options="categoryOptions"
        />
        <BSelect
          v-model="form.discountType"
          label="Rabatt-Typ"
          :options="discountTypeOptions"
        />
        <BInput
          v-model="form.discountValue"
          type="number"
          :label="form.discountType === 'PERCENTAGE' ? 'Prozent (%)' : 'Betrag (Rappen)'"
          :error="errors.discountValue"
          :required="true"
        />
        <BInput
          v-model="form.maxUses"
          type="number"
          label="Max. Einloesungen"
          :required="true"
        />
      </BFormSection>

      <BFormSection title="Einschraenkungen" :columns="2" divided>
        <BInput
          v-model="form.expiresAt"
          type="date"
          :label="t('offers.expiresAt')"
        />
        <BInput
          v-model="form.maxUsesPerCustomer"
          type="number"
          label="Max. pro Kunde"
          hint="Leer = unbegrenzt"
        />
        <BMoneyInput
          v-model="form.minOrderCents"
          label="Mindestbestellwert"
          hint="0 = kein Mindestbestellwert"
        />
      </BFormSection>

      <BFormSection title="Status" :columns="1">
        <BToggle
          v-model="form.active"
          :label="t('common.active')"
        />
      </BFormSection>

      <!-- Delete button in footer-left -->
      <template v-if="isEditing" #footer-left>
        <button
          class="px-4 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
          @click="() => { if (editingVoucher) { onDeleteVoucher(editingVoucher.id); showPanel = false; } }"
        >
          Loeschen
        </button>
      </template>
    </BFormPanel>
  </div>
</template>
