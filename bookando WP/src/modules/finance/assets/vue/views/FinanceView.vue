<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <!-- Header -->
        <template #header>
          <AppPageHeader
            :title="t('mod.finance.title')"
            hide-brand-below="md"
          >
            <template #actions>
              <div class="bookando-flex bookando-gap-sm">
                <AppButton
                  icon="rotate-cw"
                  variant="ghost"
                  :loading="loading"
                  @click="loadState"
                >
                  {{ t('mod.finance.actions.refresh') }}
                </AppButton>
                <AppButton
                  icon="file-text"
                  variant="secondary"
                  @click="openDocumentForm('invoice')"
                >
                  {{ t('mod.finance.actions.add_invoice') }}
                </AppButton>
                <AppButton
                  icon="file-minus"
                  variant="primary"
                  @click="openDocumentForm('credit')"
                >
                  {{ t('mod.finance.actions.add_credit') }}
                </AppButton>
              </div>
            </template>
          </AppPageHeader>
        </template>

        <!-- Tabs Navigation -->
        <template #nav>
          <AppTabs
            v-model="currentTab"
            :tabs="tabItems"
            nav-only
          />
        </template>

        <!-- Main Content -->
        <div
          v-if="error"
          class="bookando-alert bookando-alert--danger bookando-mb-md"
          role="alert"
        >
          {{ error }}
        </div>

        <div v-if="currentTab === 'invoices'">
          <AppDataCard :title="t('mod.finance.tabs.invoices')">
            <template #header>
              <AppButton
                size="square"
                btn-type="icononly"
                variant="ghost"
                icon="plus"
                icon-size="lg"
                :tooltip="t('mod.finance.actions.add_invoice')"
                @click="openDocumentForm('invoice')"
              />
            </template>
            <table class="bookando-finance-table">
              <thead>
                <tr>
                  <th>{{ t('mod.finance.labels.number') }}</th>
                  <th>{{ t('mod.finance.labels.customer') }}</th>
                  <th>{{ t('mod.finance.labels.date') }}</th>
                  <th>{{ t('mod.finance.labels.due_date') }}</th>
                  <th>{{ t('mod.finance.labels.status') }}</th>
                  <th class="bookando-text-right">
                    {{ t('mod.finance.labels.total') }}
                  </th>
                  <th />
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="invoice in invoices"
                  :key="invoice.id"
                >
                  <td>{{ invoice.number }}</td>
                  <td>{{ invoice.customer }}</td>
                  <td>{{ formatDate(invoice.date) }}</td>
                  <td>{{ formatDate(invoice.due_date) }}</td>
                  <td>{{ statusLabel(invoice.status) }}</td>
                  <td class="bookando-text-right">
                    {{ formatMoney(invoice.total, invoice.currency) }}
                  </td>
                  <td class="bookando-text-right">
                    <AppButton
                      variant="secondary"
                      size="square"
                      btn-type="icononly"
                      icon="edit-3"
                      icon-size="md"
                      :tooltip="t('core.common.edit')"
                      @click="openDocumentForm('invoice', invoice)"
                    />
                    <AppButton
                      variant="danger"
                      size="square"
                      btn-type="icononly"
                      icon="trash"
                      icon-size="md"
                      :loading="deletingId === invoice.id"
                      :tooltip="t('mod.finance.actions.delete')"
                      @click="removeDocument('invoice', invoice)"
                    />
                  </td>
                </tr>
                <tr v-if="!invoices.length">
                  <td
                    :colspan="7"
                    class="bookando-finance-empty"
                  >
                    {{ t('mod.finance.messages.load_error') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </AppDataCard>
        </div>

        <div v-else-if="currentTab === 'credit'">
          <AppDataCard :title="t('mod.finance.tabs.creditNotes')">
            <template #header>
              <AppButton
                size="square"
                btn-type="icononly"
                variant="ghost"
                icon="plus"
                icon-size="lg"
                :tooltip="t('mod.finance.actions.add_credit')"
                @click="openDocumentForm('credit')"
              />
            </template>
            <table class="bookando-finance-table">
              <thead>
                <tr>
                  <th>{{ t('mod.finance.labels.number') }}</th>
                  <th>{{ t('mod.finance.labels.customer') }}</th>
                  <th>{{ t('mod.finance.labels.date') }}</th>
                  <th>{{ t('mod.finance.labels.status') }}</th>
                  <th class="bookando-text-right">
                    {{ t('mod.finance.labels.total') }}
                  </th>
                  <th />
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="note in creditNotes"
                  :key="note.id"
                >
                  <td>{{ note.number }}</td>
                  <td>{{ note.customer }}</td>
                  <td>{{ formatDate(note.date) }}</td>
                  <td>{{ statusLabel(note.status) }}</td>
                  <td class="bookando-text-right">
                    {{ formatMoney(note.total, note.currency) }}
                  </td>
                  <td class="bookando-text-right">
                    <AppButton
                      variant="secondary"
                      size="square"
                      btn-type="icononly"
                      icon="edit-3"
                      icon-size="md"
                      :tooltip="t('core.common.edit')"
                      @click="openDocumentForm('credit', note)"
                    />
                    <AppButton
                      variant="danger"
                      size="square"
                      btn-type="icononly"
                      icon="trash"
                      icon-size="md"
                      :loading="deletingId === note.id"
                      :tooltip="t('mod.finance.actions.delete')"
                      @click="removeDocument('credit', note)"
                    />
                  </td>
                </tr>
                <tr v-if="!creditNotes.length">
                  <td
                    :colspan="6"
                    class="bookando-finance-empty"
                  >
                    {{ t('mod.finance.messages.load_error') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </AppDataCard>
        </div>

        <div v-else-if="currentTab === 'discounts'">
          <AppDataCard :title="t('mod.finance.tabs.discountCodes')">
            <template #header>
              <AppButton
                size="square"
                btn-type="icononly"
                variant="ghost"
                icon="plus"
                icon-size="lg"
                :tooltip="t('mod.finance.actions.add_discount')"
                @click="openDiscountForm()"
              />
            </template>
            <table class="bookando-finance-table">
              <thead>
                <tr>
                  <th>{{ t('mod.finance.labels.discount_code') }}</th>
                  <th>{{ t('mod.finance.labels.description') }}</th>
                  <th>{{ t('mod.finance.labels.discount_type') }}</th>
                  <th>{{ t('mod.finance.labels.value') }}</th>
                  <th>{{ t('mod.finance.labels.validity') }}</th>
                  <th>{{ t('mod.finance.labels.usage') }}</th>
                  <th>{{ t('mod.finance.labels.applies_to') }}</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="code in discountCodes"
                  :key="code.id || code.code"
                >
                  <td>{{ code.code }}</td>
                  <td>{{ code.description || '—' }}</td>
                  <td>{{ discountTypeLabel(code.discount_type) }}</td>
                  <td>
                    <span v-if="code.discount_type === 'fixed'">
                      {{ formatMoney(code.value, documentForm.currency) }}
                    </span>
                    <span v-else>
                      {{ code.value }}%
                    </span>
                  </td>
                  <td>{{ discountValidity(code) }}</td>
                  <td>
                    <span v-if="code.max_uses">
                      {{ code.usage_count ?? 0 }} / {{ code.max_uses }}
                    </span>
                    <span v-else>
                      {{ code.usage_count ?? 0 }}
                    </span>
                  </td>
                  <td>{{ code.applies_to?.length ? code.applies_to.join(', ') : '—' }}</td>
                  <td class="bookando-text-right">
                    <AppButton
                      variant="secondary"
                      size="square"
                      btn-type="icononly"
                      icon="edit-3"
                      icon-size="md"
                      :tooltip="t('core.common.edit')"
                      @click="openDiscountForm(code)"
                    />
                    <AppButton
                      variant="danger"
                      size="square"
                      btn-type="icononly"
                      icon="trash"
                      icon-size="md"
                      :loading="deletingId === code.id"
                      :tooltip="t('mod.finance.actions.delete')"
                      @click="removeDiscount(code)"
                    />
                  </td>
                </tr>
                <tr v-if="!discountCodes.length">
                  <td
                    :colspan="8"
                    class="bookando-finance-empty"
                  >
                    {{ t('mod.finance.messages.no_discounts') }}
                  </td>
                </tr>
              </tbody>
            </table>
          </AppDataCard>
        </div>

        <div v-else-if="currentTab === 'ledger'">
          <AppDataCard :title="t('mod.finance.tabs.ledger')">
            <template #header>
              <AppButton
                icon="download"
                variant="primary"
                :loading="exporting"
                @click="performExport"
              >
                {{ t('mod.finance.actions.export') }}
              </AppButton>
            </template>
            <div class="bookando-finance-grid-two">
              <BookandoField
                id="finance_export_from"
                v-model="exportFilters.from"
                type="date"
                :label="t('mod.finance.labels.document_date_range') + ' – Start'"
                clearable
              />
              <BookandoField
                id="finance_export_to"
                v-model="exportFilters.to"
                type="date"
                :label="t('mod.finance.labels.document_date_range') + ' – Ende'"
                clearable
              />
            </div>

            <div class="bookando-divider" />
            <h3 class="bookando-h6">
              Konten
            </h3>
            <ul class="bookando-list bookando-m-0">
              <li
                v-for="account in ledgerAccounts"
                :key="account.code"
              >
                {{ account.code }} · {{ account.name }} ({{ account.type }})
              </li>
            </ul>

            <div
              v-if="exportResult"
              class="bookando-alert bookando-alert--success"
            >
              {{ t('mod.finance.messages.export_success') }}
              <br>
              {{ exportResult.entries.length }} Einträge · {{ formatDateTime(exportResult.generated_at) }}
            </div>
          </AppDataCard>
        </div>

        <div v-else>
          <AppDataCard :title="t('mod.finance.tabs.settings')">
            <div class="bookando-finance-export">
              <BookandoField
                id="finance_auto_invoice"
                v-model="settingsForm.auto_invoice"
                type="toggle"
                :label="t('mod.finance.labels.auto_invoice')"
              />
              <BookandoField
                id="finance_auto_send"
                v-model="settingsForm.auto_send"
                type="toggle"
                :label="t('mod.finance.labels.auto_send')"
              />
              <BookandoField
                id="finance_batch_mode"
                v-model="settingsForm.batch_mode"
                type="dropdown"
                :options="batchOptions"
                option-label="label"
                option-value="value"
                :label="t('mod.finance.labels.batch_mode')"
              />
              <div class="bookando-flex bookando-gap-sm bookando-mt-sm">
                <AppButton
                  variant="primary"
                  :loading="savingSettings"
                  @click="submitSettings"
                >
                  {{ t('mod.finance.actions.save') }}
                </AppButton>
              </div>
            </div>
          </AppDataCard>
        </div>
      </AppPageLayout>
    </div>

    <transition name="fade">
      <div
        v-if="showDocumentModal"
        class="bookando-finance-modal"
        role="dialog"
        aria-modal="true"
      >
        <div class="bookando-finance-modal__content">
          <header class="bookando-modal__header">
            <h3 class="bookando-h5 bookando-m-0">
              {{ editingType === 'invoice' ? t('mod.finance.actions.add_invoice') : t('mod.finance.actions.add_credit') }}
            </h3>
            <AppButton
              icon="x"
              variant="ghost"
              size="square"
              btn-type="icononly"
              icon-size="md"
              @click="closeDocumentModal"
            />
          </header>

          <form
            class="bookando-finance-modal__form"
            autocomplete="off"
            @submit.prevent="submitDocument"
          >
            <div class="bookando-finance-grid-two">
              <BookandoField
                id="finance_doc_customer"
                v-model="documentForm.customer"
                :label="t('mod.finance.labels.customer')"
                required
              />
              <BookandoField
                id="finance_doc_currency"
                v-model="documentForm.currency"
                :label="t('mod.finance.labels.currency')"
              />
            </div>
            <div class="bookando-finance-grid-two">
              <BookandoField
                id="finance_doc_date"
                v-model="documentForm.date"
                type="date"
                :label="t('mod.finance.labels.date')"
              />
              <BookandoField
                id="finance_doc_due"
                v-model="documentForm.due_date"
                type="date"
                :label="t('mod.finance.labels.due_date')"
              />
            </div>
            <BookandoField
              id="finance_doc_status"
              v-model="documentForm.status"
              type="dropdown"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              :label="t('mod.finance.labels.status')"
            />

            <div class="bookando-divider" />
            <div class="bookando-flex bookando-justify-between bookando-items-center">
              <strong>{{ t('mod.finance.labels.items') }}</strong>
              <AppButton
                icon="plus"
                variant="ghost"
                size="square"
                btn-type="icononly"
                icon-size="md"
                @click.prevent="addItem"
              />
            </div>
            <div class="bookando-finance-items">
              <div
                v-for="(item, idx) in documentForm.items"
                :key="idx"
                class="bookando-card bookando-p-sm"
              >
                <BookandoField
                  :id="`finance_item_desc_${idx}`"
                  v-model="item.description"
                  :label="t('mod.finance.labels.description')"
                />
                <div class="bookando-finance-grid-two">
                  <BookandoField
                    :id="`finance_item_type_${idx}`"
                    v-model="item.type"
                    :label="t('mod.finance.labels.type')"
                  />
                  <BookandoField
                    :id="`finance_item_ref_${idx}`"
                    v-model="item.reference"
                    :label="t('mod.finance.labels.reference')"
                  />
                </div>
                <div class="bookando-finance-grid-two">
                  <BookandoField
                    :id="`finance_item_qty_${idx}`"
                    v-model.number="item.quantity"
                    type="number"
                    :min="0"
                    :label="t('mod.finance.labels.quantity')"
                  />
                  <BookandoField
                    :id="`finance_item_price_${idx}`"
                    v-model.number="item.unit_price"
                    type="number"
                    step="0.05"
                    :label="t('mod.finance.labels.unit_price')"
                  />
                </div>
                <BookandoField
                  :id="`finance_item_tax_${idx}`"
                  v-model.number="item.tax_rate"
                  type="number"
                  step="0.1"
                  :label="t('mod.finance.labels.tax_rate')"
                />
                <div class="bookando-flex bookando-justify-between bookando-items-center">
                  <span class="bookando-text-sm bookando-text-muted">
                    {{ formatMoney(itemTotal(item), documentForm.currency) }}
                  </span>
                  <AppButton
                    variant="ghost"
                    size="square"
                    btn-type="icononly"
                    icon="trash"
                    icon-size="md"
                    @click.prevent="removeItem(idx)"
                  />
                </div>
              </div>
            </div>

            <div class="bookando-divider" />
            <div class="bookando-flex bookando-justify-end bookando-gap-lg">
              <div>
                <div class="bookando-text-sm bookando-text-muted">
                  Subtotal
                </div>
                <strong>{{ formatMoney(documentTotals.subtotal, documentForm.currency) }}</strong>
              </div>
              <div>
                <div class="bookando-text-sm bookando-text-muted">
                  MwSt.
                </div>
                <strong>{{ formatMoney(documentTotals.tax_total, documentForm.currency) }}</strong>
              </div>
              <div>
                <div class="bookando-text-sm bookando-text-muted">
                  Total
                </div>
                <strong>{{ formatMoney(documentTotals.total, documentForm.currency) }}</strong>
              </div>
            </div>

            <div
              v-if="documentError"
              class="bookando-alert bookando-alert--danger"
            >
              {{ documentError }}
            </div>
          </form>

          <div class="bookando-finance-modal__footer">
            <AppButton
              variant="secondary"
              @click="closeDocumentModal"
            >
              {{ t('mod.finance.actions.cancel') }}
            </AppButton>
            <AppButton
              variant="primary"
              :loading="savingDocument"
              @click="submitDocument"
            >
              {{ t('mod.finance.actions.save') }}
            </AppButton>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="showDiscountModal"
        class="bookando-finance-modal"
        role="dialog"
        aria-modal="true"
      >
        <div class="bookando-finance-modal__content">
          <header class="bookando-modal__header">
            <h3 class="bookando-h5 bookando-m-0">
              {{ activeDiscount ? t('mod.finance.actions.edit_discount') : t('mod.finance.actions.add_discount') }}
            </h3>
            <AppButton
              icon="x"
              variant="ghost"
              size="square"
              btn-type="icononly"
              icon-size="md"
              @click="closeDiscountModal"
            />
          </header>

          <form
            class="bookando-finance-modal__form"
            autocomplete="off"
            @submit.prevent="submitDiscount"
          >
            <BookandoField
              id="finance_discount_code"
              v-model="discountForm.code"
              :label="t('mod.finance.labels.discount_code')"
              required
            />
            <BookandoField
              id="finance_discount_description"
              v-model="discountForm.description"
              :label="t('mod.finance.labels.description')"
            />
            <div class="bookando-finance-grid-two">
              <BookandoField
                id="finance_discount_type"
                v-model="discountForm.discount_type"
                type="dropdown"
                :options="discountTypeOptions"
                option-label="label"
                option-value="value"
                :label="t('mod.finance.labels.discount_type')"
              />
              <BookandoField
                id="finance_discount_value"
                v-model.number="discountForm.value"
                type="number"
                step="0.1"
                :label="t('mod.finance.labels.value')"
              />
            </div>
            <div class="bookando-finance-grid-two">
              <BookandoField
                id="finance_discount_valid_from"
                v-model="discountForm.valid_from"
                type="date"
                :label="t('mod.finance.labels.valid_from')"
              />
              <BookandoField
                id="finance_discount_valid_to"
                v-model="discountForm.valid_to"
                type="date"
                :label="t('mod.finance.labels.valid_to')"
              />
            </div>
            <BookandoField
              id="finance_discount_max"
              v-model.number="discountForm.max_uses"
              type="number"
              :min="0"
              :label="t('mod.finance.labels.max_uses')"
            />
            <BookandoField
              id="finance_discount_targets"
              v-model="discountTargets"
              :label="t('mod.finance.labels.applies_to')"
              :help="t('mod.finance.labels.applies_to_hint')"
            />

            <div
              v-if="discountError"
              class="bookando-alert bookando-alert--danger"
            >
              {{ discountError }}
            </div>
          </form>

          <div class="bookando-finance-modal__footer">
            <AppButton
              variant="secondary"
              @click="closeDiscountModal"
            >
              {{ t('mod.finance.actions.cancel') }}
            </AppButton>
            <AppButton
              variant="primary"
              :loading="savingDiscount"
              @click="submitDiscount"
            >
              {{ t('mod.finance.actions.save') }}
            </AppButton>
          </div>
        </div>
      </div>
    </transition>
  </AppShell>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppDataCard from '@core/Design/components/AppDataCard.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import { notify } from '@core/Composables/useNotifier'

import {
  fetchState,
  saveInvoice,
  deleteInvoice,
  saveCreditNote,
  deleteCreditNote,
  saveFinanceSettings,
  exportLedger,
  saveDiscountCode,
  deleteDiscountCode,
  type FinanceDocument,
  type FinanceItem,
  type FinanceSettings,
  type FinanceState,
  type DiscountCode,
} from '../api/FinanceApi'

const { t } = useI18n()

const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

const loading = ref(false)
const error = ref<string | null>(null)
const deletingId = ref<string | null>(null)

const state = reactive<FinanceState>({
  invoices: [],
  credit_notes: [],
  discount_codes: [],
  settings: {
    auto_invoice: false,
    auto_send: false,
    batch_mode: 'manual'
  },
  ledger: {
    accounts: [],
    exported_at: null
  }
})

const invoices = computed(() => state.invoices)
const creditNotes = computed(() => state.credit_notes)
const discountCodes = computed(() => state.discount_codes)
const ledgerAccounts = computed(() => state.ledger.accounts || [])

const currentTab = ref<'invoices' | 'credit' | 'discounts' | 'ledger' | 'settings'>('invoices')
const tabItems = computed(() => ([
  { label: t('mod.finance.tabs.invoices'), value: 'invoices' },
  { label: t('mod.finance.tabs.creditNotes'), value: 'credit' },
  { label: t('mod.finance.tabs.discountCodes'), value: 'discounts' },
  { label: t('mod.finance.tabs.ledger'), value: 'ledger' },
  { label: t('mod.finance.tabs.settings'), value: 'settings' }
]))

const statusOptions = computed(() => ([
  { label: t('mod.finance.statuses.open'), value: 'open' },
  { label: t('mod.finance.statuses.paid'), value: 'paid' },
  { label: t('mod.finance.statuses.draft'), value: 'draft' },
  { label: t('mod.finance.statuses.cancelled'), value: 'cancelled' },
]))

const batchOptions = computed(() => ([
  { label: t('core.common.manual') || 'Manuell', value: 'manual' },
  { label: 'Täglich', value: 'daily' },
  { label: 'Wöchentlich', value: 'weekly' },
  { label: 'Monatlich', value: 'monthly' }
]))

const discountTypeOptions = computed(() => ([
  { label: t('mod.finance.labels.discount_percentage'), value: 'percentage' },
  { label: t('mod.finance.labels.discount_fixed'), value: 'fixed' },
]))

const showDocumentModal = ref(false)
const savingDocument = ref(false)
const documentError = ref<string | null>(null)
const editingType = ref<'invoice' | 'credit'>('invoice')

const defaultDocument = (): FinanceDocument => ({
  id: undefined,
  number: '',
  customer: '',
  date: today(),
  due_date: today(),
  status: 'draft',
  currency: 'CHF',
  items: [createEmptyItem()],
})

const documentForm = reactive<FinanceDocument>(defaultDocument())
const activeDocument = ref<FinanceDocument | null>(null)

const showDiscountModal = ref(false)
const savingDiscount = ref(false)
const discountError = ref<string | null>(null)
const activeDiscount = ref<DiscountCode | null>(null)
const discountTargets = ref('')

const defaultDiscount = (): DiscountCode => ({
  id: undefined,
  code: '',
  description: '',
  discount_type: 'percentage',
  value: 10,
  valid_from: null,
  valid_to: null,
  max_uses: null,
  usage_count: 0,
  applies_to: [],
})

const discountForm = reactive<DiscountCode>(defaultDiscount())

const settingsForm = reactive<FinanceSettings>({
  auto_invoice: false,
  auto_send: false,
  batch_mode: 'manual'
})
const savingSettings = ref(false)

const exportFilters = reactive<{ from: string | null; to: string | null }>({ from: null, to: null })
const exportResult = ref<any>(null)
const exporting = ref(false)

// Use computed instead of deep watcher for better performance
const documentTotals = computed(() => {
  return calculateTotals(documentForm.items)
})

onMounted(async () => {
  await loadState()
})

async function loadState() {
  loading.value = true
  error.value = null
  try {
    const data = await fetchState()
    state.invoices = data.invoices || []
    state.credit_notes = data.credit_notes || []
    state.discount_codes = data.discount_codes || []
    state.settings = data.settings || state.settings
    state.ledger = data.ledger || state.ledger
    Object.assign(settingsForm, state.settings)
  } catch (err: any) {
    console.error('[Bookando] Failed to load finance data', err)
    error.value = err?.message || t('mod.finance.messages.load_error')
  } finally {
    loading.value = false
  }
}

function openDocumentForm(type: 'invoice' | 'credit', doc?: FinanceDocument) {
  editingType.value = type
  activeDocument.value = doc ? { ...doc } : null
  Object.assign(documentForm, defaultDocument(), doc ? { ...doc } : {})
  if (!documentForm.items?.length) {
    documentForm.items = [createEmptyItem()]
  }
  documentError.value = null
  showDocumentModal.value = true
}

function closeDocumentModal() {
  showDocumentModal.value = false
}

function createEmptyItem(): FinanceItem {
  return { description: '', type: 'service', quantity: 1, unit_price: 0, tax_rate: 7.7 }
}

function addItem() {
  documentForm.items.push(createEmptyItem())
}

function removeItem(index: number) {
  documentForm.items.splice(index, 1)
  if (!documentForm.items.length) {
    documentForm.items.push(createEmptyItem())
  }
}

function itemTotal(item: FinanceItem): number {
  return Math.round(item.quantity * item.unit_price * 100) / 100
}

function calculateTotals(items: FinanceItem[]) {
  let subtotal = 0
  let tax = 0
  for (const item of items) {
    const total = itemTotal(item)
    subtotal += total
    tax += total * (item.tax_rate / 100)
  }
  subtotal = Math.round(subtotal * 100) / 100
  tax = Math.round(tax * 100) / 100
  return { subtotal, tax_total: tax, total: Math.round((subtotal + tax) * 100) / 100 }
}

async function submitDocument() {
  if (savingDocument.value) return
  savingDocument.value = true
  documentError.value = null
  try {
    if (!documentForm.customer) {
      documentError.value = t('mod.finance.labels.customer')
      savingDocument.value = false
      return
    }
    const payload: Partial<FinanceDocument> = {
      ...documentForm,
      items: documentForm.items.filter((item) => item.description || item.reference)
    }
    if (!payload.items?.length) {
      payload.items = [createEmptyItem()]
    }
    let saved: FinanceDocument
    if (editingType.value === 'invoice') {
      saved = await saveInvoice(payload)
      upsertLocal(state.invoices, saved)
    } else {
      saved = await saveCreditNote(payload)
      upsertLocal(state.credit_notes, saved)
    }
    notify({ type: 'success', message: t('mod.finance.messages.save_success') })
    showDocumentModal.value = false
  } catch (err: any) {
    console.error('[Bookando] Failed to save finance document', err)
    documentError.value = err?.message || t('mod.finance.messages.save_error')
  } finally {
    savingDocument.value = false
  }
}

async function removeDocument(type: 'invoice' | 'credit', doc: FinanceDocument) {
  if (!doc.id) return
  deletingId.value = doc.id
  try {
    const ok = type === 'invoice' ? await deleteInvoice(doc.id) : await deleteCreditNote(doc.id)
    if (ok) {
      if (type === 'invoice') {
        state.invoices = state.invoices.filter(entry => entry.id !== doc.id)
      } else {
        state.credit_notes = state.credit_notes.filter(entry => entry.id !== doc.id)
      }
      notify({ type: 'success', message: t('mod.finance.messages.delete_success') })
    } else {
      notify({ type: 'danger', message: t('mod.finance.messages.delete_error') })
    }
  } catch (err) {
    console.error('[Bookando] Failed to delete finance document', err)
    notify({ type: 'danger', message: t('mod.finance.messages.delete_error') })
  } finally {
    deletingId.value = null
  }
}

function openDiscountForm(code?: DiscountCode) {
  activeDiscount.value = code ? { ...code } : null
  Object.assign(discountForm, defaultDiscount(), code ? { ...code } : {})
  discountTargets.value = code?.applies_to?.join(', ') || ''
  discountError.value = null
  showDiscountModal.value = true
}

function closeDiscountModal() {
  showDiscountModal.value = false
}

async function submitDiscount() {
  if (savingDiscount.value) return
  savingDiscount.value = true
  discountError.value = null

  try {
    if (!discountForm.code) {
      discountError.value = t('mod.finance.labels.discount_code_required')
      savingDiscount.value = false
      return
    }

    const applies = discountTargets.value
      .split(',')
      .map(entry => entry.trim())
      .filter(Boolean)

    const payload: Partial<DiscountCode> = {
      ...discountForm,
      applies_to: applies,
    }

    const saved = await saveDiscountCode(payload)
    upsertLocal(state.discount_codes, saved)
    notify({ type: 'success', message: t('mod.finance.messages.discount_saved') })
    showDiscountModal.value = false
  } catch (err: any) {
    console.error('[Bookando] Failed to save discount code', err)
    discountError.value = err?.message || t('mod.finance.messages.save_error')
  } finally {
    savingDiscount.value = false
  }
}

async function removeDiscount(code: DiscountCode) {
  if (!code.id) return
  deletingId.value = code.id
  try {
    const ok = await deleteDiscountCode(code.id)
    if (ok) {
      state.discount_codes = state.discount_codes.filter(entry => entry.id !== code.id)
      notify({ type: 'success', message: t('mod.finance.messages.discount_deleted') })
    } else {
      notify({ type: 'danger', message: t('mod.finance.messages.delete_error') })
    }
  } catch (err) {
    console.error('[Bookando] Failed to delete discount code', err)
    notify({ type: 'danger', message: t('mod.finance.messages.delete_error') })
  } finally {
    deletingId.value = null
  }
}

function discountTypeLabel(type: string) {
  return type === 'fixed'
    ? t('mod.finance.labels.discount_fixed')
    : t('mod.finance.labels.discount_percentage')
}

function discountValidity(code: DiscountCode) {
  if (!code.valid_from && !code.valid_to) {
    return t('mod.finance.labels.discount_open')
  }
  const from = code.valid_from ? formatDate(code.valid_from) : '—'
  const to = code.valid_to ? formatDate(code.valid_to) : '—'
  return `${from} → ${to}`
}

async function submitSettings() {
  if (savingSettings.value) return
  savingSettings.value = true
  try {
    const updated = await saveFinanceSettings(settingsForm)
    state.settings = updated
    notify({ type: 'success', message: t('mod.finance.messages.save_success') })
  } catch (err: any) {
    console.error('[Bookando] Failed to save finance settings', err)
    notify({ type: 'danger', message: t('mod.finance.messages.save_error') })
  } finally {
    savingSettings.value = false
  }
}

async function performExport() {
  exporting.value = true
  exportResult.value = null
  try {
    const result = await exportLedger({ from: exportFilters.from || undefined, to: exportFilters.to || undefined })
    exportResult.value = result
    downloadCsv(result)
    notify({ type: 'success', message: t('mod.finance.messages.export_success') })
  } catch (err: any) {
    console.error('[Bookando] Failed to export ledger', err)
    notify({ type: 'danger', message: t('mod.finance.messages.save_error') })
  } finally {
    exporting.value = false
  }
}

function downloadCsv(result: any) {
  if (!result?.entries?.length) {
    return
  }
  const header = ['document_id', 'document_type', 'document_no', 'date', 'customer', 'account', 'description', 'amount', 'tax_rate', 'currency']
  const rows = [header.join(';')]
  for (const entry of result.entries) {
    rows.push(header.map((key) => String(entry[key] ?? '')).join(';'))
  }
  const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `bookando-fibu-export-${Date.now()}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

function upsertLocal<T extends { id?: string }>(list: T[], saved: T) {
  const index = list.findIndex(entry => entry.id === saved.id)
  if (index >= 0) {
    list.splice(index, 1, saved)
  } else {
    list.push(saved)
  }
}

function statusLabel(status?: string) {
  const map: Record<string, string> = {
    open: t('mod.finance.statuses.open'),
    paid: t('mod.finance.statuses.paid'),
    draft: t('mod.finance.statuses.draft'),
    cancelled: t('mod.finance.statuses.cancelled'),
  }
  return map[status || 'open'] || status || 'open'
}

function today() {
  return new Date().toISOString().slice(0, 10)
}

function formatDate(value?: string) {
  if (!value) return '–'
  return value
}

function formatDateTime(value?: string) {
  if (!value) return '–'
  return value.replace('T', ' ')
}

function formatMoney(amount?: number | string, currency = 'CHF') {
  if (amount === undefined || amount === null) return '–'
  const numeric = typeof amount === 'number' ? amount : Number(amount)
  if (Number.isNaN(numeric)) {
    return `${amount} ${currency}`
  }
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(numeric)
  } catch (e) {
    return `${numeric.toFixed(2)} ${currency}`
  }
}
</script>
