<!-- EmployeesTable.vue -->
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
      storage-key="employees"
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

      <!-- Header employee -->
      <template #header-employee>
        <span>{{ fieldLabel(t, 'employee', MODULE) }}</span>
      </template>

      <!-- Cell employee -->
      <template #cell-employee="{ item }">
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

      <template #cell-language="{ item }">
        <span
          v-if="item.language"
          class="flag"
        >{{ languageFlag(item.language) }}</span>
        {{ languageLabel(item.language, locale) || item.language || '–' }}
      </template>

      <template #cell-gender="{ item }">
        {{ genderLabel(item.gender, locale) || '–' }}
      </template>

      <template #cell-birthdate="{ item }">
        {{ formatDate(item.birthdate, locale) }}
      </template>

      <template #cell-work_locations="{ item }">
        {{ formatWorkLocations(item.work_locations) }}
      </template>

      <template #cell-created_at="{ item }">
        {{ formatDatetime(item.created_at, locale) }}
      </template>

      <template #cell-updated_at="{ item }">
        {{ formatDatetime(item.updated_at, locale) }}
      </template>

      <template #cell-deleted_at="{ item }">
        {{ formatDatetime(item.deleted_at, locale) }}
      </template>

      <template #cell-status="{ item }">
        <span :class="['bookando-status-label', statusClass(item.status)]">
          <span class="status-label-text">{{ statusLabel(item.status, locale) }}</span>
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
    </AppTable>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppTable from '@core/Design/components/AppTable.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import { genderLabel, languageFlag, languageLabel, statusLabel, formatDate, formatDatetime } from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'
import { initials, normalizePhone, statusClass, formatWorkLocations } from '../../../composables/useEmployeeData'

const { t, locale } = useI18n()
const emit = defineEmits(['edit', 'delete', 'select', 'sort', 'update:col-widths', 'quick', 'row-click'])
const MODULE = 'employees'

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
  sortKey: { type: String, default: 'employee' },
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
