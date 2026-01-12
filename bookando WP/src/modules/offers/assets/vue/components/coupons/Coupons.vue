<template>
  <div class="offers-simple-view">
    <div class="offers-simple-view__toolbar">
      <div class="offers-simple-view__filters">
        <BookandoField
          id="coupon_search"
          v-model="search"
          type="search"
          :placeholder="t('ui.search.placeholder') || 'Gutscheine durchsuchen'"
        />
        <BookandoField
          id="coupon_status"
          v-model="statusFilter"
          type="dropdown"
          :label="t('fields.status') || 'Status'"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          mode="basic"
          clearable
          hide-label
        />
      </div>
      <AppButton
        icon="plus"
        variant="primary"
        size="dynamic"
        @click="$emit('create')"
      >
        {{ t('core.common.add') }}
      </AppButton>
    </div>

    <div class="bookando-card">
      <div class="bookando-card-body">
        <div
          class="offers-simple-table"
          style="--offers-simple-cols: 1.2fr 1fr 1.2fr 1fr auto"
        >
          <div class="offers-simple-table__head">
            <span>{{ t('fields.code') || 'Code' }}</span>
            <span>{{ t('mod.offers.coupons.discount') || 'Rabatt' }}</span>
            <span>{{ t('fields.period') || 'Zeitraum' }}</span>
            <span>{{ t('mod.offers.coupons.usage') || 'Nutzung' }}</span>
            <span />
          </div>

          <template v-if="filtered.length">
            <article
              v-for="coupon in filtered"
              :key="coupon.id"
              class="offers-simple-table__row"
            >
              <div>
                <div class="bookando-font-medium">
                  {{ coupon.code }}
                </div>
                <div class="offers-simple-table__meta">
                  <span
                    :class="[
                      'bookando-badge',
                      coupon.status === 'active' ? 'bookando-badge--success' : 'bookando-badge--secondary'
                    ]"
                  >
                    {{ coupon.status === 'active' ? t('core.status.active') || 'Aktiv' : t('core.status.hidden') || 'Versteckt' }}
                  </span>
                </div>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="percent" />
                <span>{{ formatDiscount(coupon) }}</span>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="calendar" />
                <span>{{ formatRange(coupon.valid_from, coupon.valid_until) }}</span>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="hash" />
                <span>
                  <template v-if="coupon.usage_limit">
                    {{ t('mod.offers.coupons.usage_limit') || 'Limit' }}: {{ coupon.usage_limit }}
                  </template>
                  <template v-else>
                    {{ t('mod.offers.coupons.unlimited') || 'Unbegrenzt' }}
                  </template>
                </span>
              </div>
              <div class="bookando-inline-flex bookando-gap-xxs bookando-justify-end">
                <AppButton
                  icon="edit"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.edit')"
                  @click="$emit('edit', coupon)"
                />
                <AppButton
                  icon="trash-2"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.delete')"
                  @click="$emit('delete', coupon)"
                />
              </div>
            </article>
          </template>

          <div
            v-else
            class="offers-simple-empty"
          >
            {{ t('mod.offers.coupons.empty') || 'Noch keine Gutscheine angelegt.' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'

import '../offers-simple.scss'

type Coupon = {
  id: number
  code: string
  description?: string | null
  discount_type: 'percent' | 'fixed'
  discount_value: number
  min_order?: number | null
  valid_from?: string | null
  valid_until?: string | null
  usage_limit?: number | null
  currency?: string
  status?: 'active' | 'hidden'
}

const props = defineProps<{ coupons: Coupon[] }>()
const emit = defineEmits<{
  (event: 'create'): void
  (event: 'edit', value: Coupon): void
  (event: 'delete', value: Coupon): void
}>()

const { t } = useI18n()

const search = ref('')
const statusFilter = ref<string | undefined>()

const statusOptions = computed(() => ([
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' },
]))

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  return (props.coupons || [])
    .filter(cp => !statusFilter.value || cp.status === statusFilter.value)
    .filter(cp => !term || cp.code.toLowerCase().includes(term))
    .sort((a, b) => a.code.localeCompare(b.code))
})

function formatDiscount(coupon: Coupon) {
  if (coupon.discount_type === 'percent') {
    return `${coupon.discount_value}%`
  }
  const currency = coupon.currency || 'CHF'
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(coupon.discount_value)
}

function formatRange(start?: string | null, end?: string | null) {
  if (!start && !end) return t('mod.offers.coupons.no_expiry') || 'Kein Ablauf'
  const fmt = (value: string | null | undefined) => {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleDateString()
  }
  return `${fmt(start)} – ${fmt(end)}`
}
</script>
