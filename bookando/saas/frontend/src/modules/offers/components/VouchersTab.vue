<script setup lang="ts">
/**
 * VouchersTab — Gutschein-Verwaltung
 *
 * Tabelle aller Gutscheine mit Erstellung, Statusanzeige,
 * Nutzungsfortschritt und Copy-Funktion.
 */
import { ref, computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useAppStore } from '@/stores/app';
import { useOffersStore } from '@/stores/offers';
import type { Voucher, VoucherType } from '@/stores/offers';
import { CARD_STYLES, BADGE_STYLES, BUTTON_STYLES, TABLE_STYLES, MODAL_STYLES, INPUT_STYLES, LABEL_STYLES } from '@/design';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BTable from '@/components/ui/BTable.vue';
import BToggle from '@/components/ui/BToggle.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BModal from '@/components/ui/BModal.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const toast = useToast();
const appStore = useAppStore();
const store = useOffersStore();

const showModal = ref(false);
const editingVoucher = ref<Voucher | null>(null);

// Form
const form = ref({
  code: '',
  type: 'PERCENTAGE' as VoucherType,
  value: 0,
  maxUses: 100,
  expiresAt: '',
  active: true,
  minOrderMinor: 0,
});
const errors = ref<Record<string, string>>({});

const typeOptions = [
  { value: 'PERCENTAGE', label: 'Prozent (%)' },
  { value: 'FIXED', label: 'Festbetrag (' + appStore.currency + ')' },
];

const columns = computed(() => [
  { key: 'code', label: t('offers.code'), sortable: true },
  { key: 'type', label: t('common.type'), sortable: true },
  { key: 'value', label: 'Wert', sortable: true },
  { key: 'usage', label: t('offers.usageCount'), sortable: false },
  { key: 'expiresAt', label: t('offers.expiresAt'), sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: '', align: 'right' as const },
]);

const tableData = computed(() =>
  store.vouchers.map(v => ({
    id: v.id,
    code: v.code,
    type: v.type,
    value: v.value,
    maxUses: v.maxUses,
    usedCount: v.usedCount,
    expiresAt: v.expiresAt,
    active: v.active,
    minOrderMinor: v.minOrderMinor,
    // Computed status
    status: getVoucherStatus(v),
    usagePercent: v.maxUses > 0 ? Math.round((v.usedCount / v.maxUses) * 100) : 0,
  }))
);

function getVoucherStatus(v: Voucher): string {
  if (v.usedCount >= v.maxUses) return 'depleted';
  const now = new Date();
  const expiry = new Date(v.expiresAt);
  if (expiry < now) return 'expired';
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

function formatValue(voucher: { type: string; value: number }): string {
  if (voucher.type === 'PERCENTAGE') {
    return voucher.value + '%';
  }
  return appStore.formatPrice(voucher.value);
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
    code: generateCode(),
    type: 'PERCENTAGE',
    value: 10,
    maxUses: 100,
    expiresAt: new Date(Date.now() + 90 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
    active: true,
    minOrderMinor: 0,
  };
  errors.value = {};
  showModal.value = true;
}

function onEditVoucher(row: Record<string, unknown>) {
  const voucher = store.vouchers.find(v => v.id === row.id);
  if (!voucher) return;
  editingVoucher.value = voucher;
  form.value = {
    code: voucher.code,
    type: voucher.type,
    value: voucher.value,
    maxUses: voucher.maxUses,
    expiresAt: voucher.expiresAt,
    active: voucher.active,
    minOrderMinor: voucher.minOrderMinor || 0,
  };
  errors.value = {};
  showModal.value = true;
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
  if (form.value.value <= 0) {
    errs.value = 'Wert > 0';
  }
  if (form.value.type === 'PERCENTAGE' && form.value.value > 100) {
    errs.value = 'Max 100%';
  }
  if (!form.value.expiresAt) {
    errs.expiresAt = t('common.required');
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
}

function onSave() {
  if (!validate()) return;

  const minOrder = form.value.minOrderMinor > 0
    ? Math.round(form.value.minOrderMinor * 100)
    : undefined;

  if (editingVoucher.value) {
    store.updateVoucher(editingVoucher.value.id, {
      code: form.value.code,
      type: form.value.type,
      value: form.value.type === 'FIXED' ? Math.round(form.value.value * 100) : form.value.value,
      maxUses: form.value.maxUses,
      expiresAt: form.value.expiresAt,
      active: form.value.active,
      minOrderMinor: minOrder,
    });
    toast.success(t('common.saved'));
  } else {
    store.addVoucher({
      code: form.value.code,
      type: form.value.type,
      value: form.value.type === 'FIXED' ? Math.round(form.value.value * 100) : form.value.value,
      maxUses: form.value.maxUses,
      expiresAt: form.value.expiresAt,
      active: form.value.active,
      minOrderMinor: minOrder,
    });
    toast.success(t('common.saved'));
  }

  showModal.value = false;
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
          <span class="font-medium">{{ formatValue(voucher as any) }}</span>
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
        <div class="text-xs text-slate-400 mt-1">
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
      <template #cell-type="{ row }">
        <BBadge :variant="row.type === 'PERCENTAGE' ? 'info' : 'brand'">
          {{ row.type === 'PERCENTAGE' ? 'Prozent' : 'Festbetrag' }}
        </BBadge>
      </template>

      <!-- Value -->
      <template #cell-value="{ row }">
        <span class="font-medium text-slate-900">
          {{ formatValue(row as any) }}
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
        <span class="text-sm text-slate-600">{{ appStore.formatDate(row.expiresAt as string) }}</span>
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

    <!-- Voucher Modal -->
    <BModal
      :model-value="showModal"
      :title="editingVoucher ? t('common.edit') + ': ' + editingVoucher.code : t('offers.vouchers') + ' ' + t('common.create')"
      size="md"
      @update:model-value="showModal = false"
      @close="showModal = false"
    >
      <div class="space-y-4">
        <!-- Code -->
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

        <div class="grid grid-cols-2 gap-4">
          <BSelect
            v-model="form.type"
            label="Typ"
            :options="typeOptions"
          />
          <BInput
            v-model="form.value"
            type="number"
            :label="form.type === 'PERCENTAGE' ? 'Prozent (%)' : 'Betrag (' + appStore.currency + ')'"
            :error="errors.value"
            :required="true"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <BInput
            v-model="form.maxUses"
            type="number"
            label="Max. Einloesungen"
            :required="true"
          />
          <BInput
            v-model="form.expiresAt"
            type="date"
            :label="t('offers.expiresAt')"
            :error="errors.expiresAt"
            :required="true"
          />
        </div>

        <BInput
          v-model="form.minOrderMinor"
          type="number"
          :label="'Mindestbestellwert (' + appStore.currency + ')'"
          :hint="'0 = kein Mindestbestellwert'"
        />

        <BToggle
          v-model="form.active"
          :label="t('common.active')"
        />
      </div>

      <template #footer>
        <BButton variant="secondary" @click="showModal = false">
          {{ t('common.cancel') }}
        </BButton>
        <BButton variant="primary" @click="onSave">
          {{ t('common.save') }}
        </BButton>
      </template>
    </BModal>
  </div>
</template>
