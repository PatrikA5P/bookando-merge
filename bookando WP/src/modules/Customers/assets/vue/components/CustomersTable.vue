<!-- CustomersTable.vue -->
<template>
  <div
    :class="['bookando-container bookando-pr-none bookando-pl-none bookando-pt-md bookando-pb-md', containerClass]"
  >
    <AppTable
      :items="items"
      :columns="orderedColumns"
      :visible-columns="visibleColumns"
      :use-checkboxes="true"
      :use-actions="true"
      :empty-text="t('ui.table.no_results')"
      :col-widths="colWidths"
      :selected-items="selectedItems"
      storage-key="customers"
      :user-id="userId"
      :reset-widths-trigger="resetWidthsTrigger"
      :sort-state="{ key: sortKey, direction: sortDirection as 'asc' | 'desc' }"
      @sort="onSort"
      @update:selected="onSelect"
      @update:col-widths="onColWidthsUpdate"
      @row-click="$emit('row-click', $event)"
    >
      <!-- Sort-Icons pro Spalte -->
      <template
        v-for="col in allColumns"
        :key="col.key"
        #[`sort-icon-${col.key}`]
      >
        <AppIcon
          v-if="sortKey === col.key && sortDirection === 'asc'"
          name="chevron-up"
          class="bookando-icon"
          alt="▲"
        />
        <AppIcon
          v-else-if="sortKey === col.key && sortDirection === 'desc'"
          name="chevron-down"
          class="bookando-icon"
          alt="▼"
        />
      </template>

      <!-- Generischer Header-Fallback -->
      <template #header-cell="{ col }">
        <span>{{ col.label }}</span>
      </template>

      <!-- Individueller Header für 'customer' -->
      <template #header-customer>
        <span>{{ fieldLabel(t, 'customer', MODULE) }}</span>
      </template>

      <!-- Generischer Zellen-Fallback -->
      <template #cell="{ item, col }">
        {{ item[col.key] ?? '–' }}
      </template>

      <!-- Individuelle Zellen: Kundencard -->
      <template #cell-customer="{ item }">
        <div
          class="bookando-flex bookando-items-start"
          :style="cellGapStyle"
        >
          <AppAvatar
            :src="item.avatar_url"
            :initials="initials(item)"
            size="sm"
            fit="contain"
            :alt="`${item.first_name ?? ''} ${item.last_name ?? ''}`.trim()"
            class="bookando-mt-xxs"
          />
          <div class="bookando-flex bookando-flex-col bookando-items-start bookando-flex-fill">
            <span class="bookando-font-semibold">
              {{ item.last_name }}, {{ item.first_name }} <span class="bookando-text-muted">(ID {{ item.id }})</span>
            </span>
            <span
              v-if="item.email"
              class="bookando-text-sm bookando-text-muted"
            >
              <a
                :href="`mailto:${item.email}`"
                class="bookando-link"
              >{{ item.email }}</a>
            </span>
            <span
              v-if="item.phone"
              class="bookando-text-sm bookando-text-muted"
            >
              <a
                :href="`tel:${normalizePhone(item.phone)}`"
                class="bookando-link"
              >{{ item.phone }}</a>
            </span>
          </div>
        </div>
      </template>

      <template #cell-country="{ item }">
        <span
          v-if="item.country"
          class="flag"
        >{{ countryFlag(item.country) }}</span>
        {{ countryLabel(item.country, locale.value) || item.country || '–' }}
      </template>

      <template #cell-language="{ item }">
        <span
          v-if="item.language"
          class="flag"
        >{{ languageFlag(item.language) }}</span>
        {{ languageLabel(item.language, locale.value) || item.language || '–' }}
      </template>

      <template #cell-gender="{ item }">
        {{ genderLabel(item.gender, locale.value) || '–' }}
      </template>
      <template #cell-birthdate="{ item }">
        {{ formatDate(item.birthdate, locale.value) }}
      </template>
      <template #cell-created_at="{ item }">
        {{ formatDatetime(item.created_at, locale.value) }}
      </template>
      <template #cell-updated_at="{ item }">
        {{ formatDatetime(item.updated_at, locale.value) }}
      </template>
      <template #cell-deleted_at="{ item }">
        {{ formatDatetime(item.deleted_at, locale.value) }}
      </template>

      <template #cell-status="{ item }">
        <span :class="['bookando-status-label', statusClass(item.status)]">
          <span class="status-label-text">{{ statusLabel(item.status, locale.value) }}</span>
        </span>
      </template>

      <template #cell-phone="{ item }">
        <template v-if="item.phone && String(item.phone).trim()">
          <a
            :href="`tel:${normalizePhone(item.phone)}`"
            class="bookando-link"
          >{{ item.phone }}</a>
        </template>
        <template v-else>
          –
        </template>
      </template>

      <template #cell-email="{ item }">
        <template v-if="item.email && String(item.email).trim()">
          <a
            :href="`mailto:${item.email}`"
            class="bookando-link"
          >{{ item.email }}</a>
        </template>
        <template v-else>
          –
        </template>
      </template>

      <!-- Actions -->
      <template #actions="{ item }">
        <div class="bookando-inline-flex bookando-items-center bookando-gap-sm bookando-width-full bookando-justify-end actions-cell">
          <AppButton
            icon="edit"
            variant="standard"
            size="square"
            btn-type="icononly"
            icon-size="md"
            :tooltip="t('core.common.edit')"
            @click="$emit('edit', item)"
          />
          <AppPopover
            trigger-mode="icon"
            trigger-icon="more-horizontal"
            trigger-variant="standard"
            :offset="2"
            width="content"
            :panel-min-width="220"
            panel-class="qa-menu"
            :close-on-item-click="true"
          >
            <template #content="{ close }">
              <div
                class="popover-menu"
                role="none"
              >
                <template
                  v-for="opt in quickOptions"
                  :key="opt.value"
                >
                  <div
                    class="dropdown-option"
                    role="menuitem"
                    :aria-disabled="opt.disabled ? 'true' : undefined"
                    :class="{ 'bookando-text-muted': opt.disabled }"
                    @click.stop="!opt.disabled && onQuickOption(opt.value, item, close)"
                  >
                    <AppIcon
                      :name="opt.icon"
                      class="dropdown-icon"
                      :class="opt.className"
                    />
                    <span class="option-label">{{ opt.label }}</span>
                  </div>
                  <div
                    v-if="opt.separatorAfter"
                    class="dropdown-separator"
                    aria-hidden="true"
                  />
                </template>
              </div>
            </template>
          </AppPopover>
        </div>
      </template>

      <!-- Expanded row → Card mit Tabs (Info / Aktivität) -->
      <template #expanded-row="{ item }">
        <div class="bookando-card customer-detail-card">
          <div class="bookando-card__header">
            <AppTabs
              :tabs="tabsDef"
              :model-value="getDetailTab(item.id)"
              @update:model-value="val => setDetailTab(item.id, val)"
            />
          </div>
          <div class="bookando-card__body">
            <transition :name="panelTransition(item.id)">
              <!-- Info-Panel -->
              <section
                v-if="getDetailTab(item.id) === 'info'"
                key="panel-info"
              >
                <div
                  class="bookando-grid align-top customer-detail-grid"
                  style="--bookando-grid-cols: 1fr 1fr;"
                >
                  <div class="bookando-table-detail">
                    <div class="detail-title">
                      {{ t('mod.customers.sections.data') }}
                    </div>

                    <div
                      v-for="field in dataFields"
                      :key="field.key"
                      class="detail-row"
                    >
                      <div class="detail-label">
                        {{ field.label }}:
                      </div>
                      <div class="detail-value">
                        <template v-if="field.key === 'country' && item.country">
                          <span class="flag">{{ countryFlag(item.country) }}</span>
                          {{ countryLabel(item.country, locale.value) || item.country }}
                        </template>
                        <template v-else-if="field.key === 'language' && item.language">
                          <span class="flag">{{ languageFlag(item.language) }}</span>
                          {{ languageLabel(item.language, locale.value) }}
                        </template>
                        <template v-else-if="field.key === 'status'">
                          <span :class="['bookando-status-label', statusClass(item.status)]">{{ statusLabel(item.status, locale.value) }}</span>
                        </template>
                        <template v-else-if="field.key === 'email'">
                          <template v-if="item.email && String(item.email).trim()">
                            <a
                              :href="`mailto:${item.email}`"
                              class="bookando-link"
                            >{{ item.email }}</a>
                          </template>
                          <template v-else>
                            –
                          </template>
                        </template>
                        <template v-else-if="field.key === 'phone'">
                          <template v-if="item.phone && String(item.phone).trim()">
                            <a
                              :href="`tel:${normalizePhone(item.phone)}`"
                              class="bookando-link"
                            >{{ item.phone }}</a>
                          </template>
                          <template v-else>
                            –
                          </template>
                        </template>
                        <template v-else-if="field.key === 'gender'">
                          {{ genderLabel(item.gender, locale.value) || '–' }}
                        </template>
                        <template v-else-if="field.key === 'birthdate'">
                          {{ formatDate(item.birthdate, locale.value) }}
                        </template>
                        <template v-else-if="['created_at','updated_at','deleted_at'].includes(field.key)">
                          {{ formatDatetime(item[field.key], locale.value) }}
                        </template>
                        <template v-else>
                          {{ item[field.key] || '–' }}
                        </template>
                      </div>
                    </div>

                    <div v-if="item.meta">
                      <div
                        v-for="(val, key) in item.meta"
                        :key="key"
                        class="detail-row"
                      >
                        <div class="detail-label">
                          {{ key }}:
                        </div>
                        <div class="detail-value">
                          {{ val }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Spalte 2: History/Stats -->
                  <div class="bookando-table-detail customer-detail-info">
                    <div class="detail-title">
                      {{ t('mod.customers.sections.history') }}
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">{{ t('mod.customers.stats.total_appointments') }}:</span>
                      <span class="detail-value"><a
                        href="#"
                        class="bookando-link"
                      >{{ item.total_appointments || 0 }}</a></span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">{{ t('mod.customers.stats.last_appointment') }}:</span>
                      <span class="detail-value bookando-text-muted">{{ item.last_appointment || '–' }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">{{ t('mod.customers.stats.next_appointment') }}:</span>
                      <span class="detail-value bookando-text-muted">–</span>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Aktivität-Panel -->
              <section
                v-else
                key="panel-activity"
              >
                <div class="bookando-table-detail">
                  <div class="detail-title">
                    {{ t('mod.customers.tabs.activity') }}
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">{{ t('mod.customers.stats.total_appointments') }}:</span>
                    <span class="detail-value">{{ item.total_appointments || 0 }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">{{ t('mod.customers.stats.last_appointment') }}:</span>
                    <span class="detail-value">{{ item.last_appointment || '–' }}</span>
                  </div>
                  <div class="detail-row">
                    <span class="detail-label">{{ t('mod.customers.stats.next_appointment') }}:</span>
                    <span class="detail-value">–</span>
                  </div>
                </div>
              </section>
            </transition>
          </div>
        </div>
      </template>
    </AppTable>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppTable from '@core/Design/components/AppTable.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import { genderLabel, countryFlag, countryLabel, languageFlag, languageLabel, statusLabel, formatDate, formatDatetime } from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'
import { buildCustomerDataFields, initials, normalizePhone, statusClass } from '../../../composables/useCustomerData'

const { t, locale } = useI18n()
const emit = defineEmits(['edit', 'delete', 'select', 'sort', 'update:col-widths', 'quick', 'row-click'])
const MODULE = 'customers'

const userId = (
  typeof window !== 'undefined' &&
  (window as any)?.BOOKANDO_VARS?.current_user_id
) || undefined

const props = defineProps({
  items: { type: Array, required: true },
  columns: { type: Array, required: true },
  visibleColumns: { type: Array, required: true },
  colWidths: { type: Object, default: () => ({}) },
  selectedItems: { type: Array, default: () => [] },
  sortKey: { type: String, default: 'customer' },
  sortDirection: { type: String, default: 'asc' },
  containerClass: { type: [String, Array, Object], default: '' },
  resetWidthsTrigger: { type: Number, default: 0 }
})

const allColumns = computed(() => props.columns)
const orderedColumns = computed(() =>
  (props.visibleColumns as any[])
    .map(key => (props.columns as any[]).find((c) => c.key === key))
    .filter(Boolean)
)

const cellGapStyle = { gap: 'var(--sc-pad-x, 8px)' } as Record<string, string>
const dataFields = buildCustomerDataFields((k:string)=>t(k), MODULE)

type DetailTab = 'info' | 'activity'
const tabsDef = computed(() => ([
  { label: t('mod.customers.tabs.info'), value: 'info' },
  { label: t('mod.customers.tabs.activity'), value: 'activity' },
]))
const detailTabs = ref<Map<number, DetailTab>>(new Map())
const lastTabs   = ref<Map<number, DetailTab>>(new Map())
function getDetailTab(id:number): DetailTab { return detailTabs.value.get(id) || 'info' }
function setDetailTab(id:number, value:DetailTab){ lastTabs.value.set(id, getDetailTab(id)); detailTabs.value.set(id, value) }
function panelTransition(id:number){
  const prev = lastTabs.value.get(id) || 'info'
  const next = getDetailTab(id)
  return prev === 'info' && next === 'activity' ? 'panel-slide-left'
       : prev === 'activity' && next === 'info' ? 'panel-slide-right'
       : 'panel-fade'
}

function onSort(key: string) {
  let direction: 'asc' | 'desc' = 'asc'
  if (props.sortKey === key) direction = (props.sortDirection as any) === 'asc' ? 'desc' : 'asc'
  emit('sort', { key, direction })
}
function onSelect(selected: (string|number)[]) { emit('select', selected) }
function onColWidthsUpdate(newWidths: Record<string, number>) { emit('update:col-widths', newWidths) }

type QuickKey = 'soft_delete' | 'hard_delete' | 'block' | 'activate' | 'export'
type QuickOption = { value: QuickKey; label: string; icon: string; className?: string; disabled?: boolean; separatorAfter?: boolean }
const quickOptions = computed<QuickOption[]>(() => [
  { value: 'soft_delete', label: t('core.actions.soft_delete.label'), icon: 'user-minus',  className: 'bookando-text-danger' },
  { value: 'hard_delete', label: t('core.actions.hard_delete.label'), icon: 'trash-2',     className: 'bookando-text-danger' },
  { value: 'block',       label: t('core.actions.block.label'),       icon: 'user-x',      className: 'bookando-text-warning' },
  { value: 'activate',    label: t('core.actions.activate.label'),    icon: 'user-check',  className: 'bookando-text-success' },
  { value: 'export',      label: t('core.actions.export.label'),      icon: 'download',    className: 'bookando-text-accent' },
])
function onQuickOption(action: QuickKey, item: any, close?: () => void) { close?.(); (emit as any)('quick', { action, item }) }
</script>
