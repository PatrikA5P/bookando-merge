<template>
  <div class="p-6 space-y-6">
    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('mod.offers.coupons.search_placeholder')"
          class="w-80 px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
        <select
          v-model="filterStatus"
          class="px-3 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
          <option value="">{{ $t('core.common.all_status') }}</option>
          <option value="active">{{ $t('core.common.active') }}</option>
          <option value="expired">{{ $t('mod.offers.coupons.expired') }}</option>
          <option value="used">{{ $t('mod.offers.coupons.used') }}</option>
        </select>
      </div>
      <button
        @click="openCreateDialog"
        class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 text-white rounded-lg font-medium hover:bg-rose-700 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('mod.offers.coupons.create') }}
      </button>
    </div>

    <!-- Coupons Grid -->
    <div v-if="!loading && filteredCoupons.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="coupon in filteredCoupons"
        :key="coupon.id"
        class="relative bg-gradient-to-br from-white to-slate-50 rounded-xl border-2 border-dashed overflow-hidden hover:shadow-lg transition-all"
        :class="{
          'border-green-300': coupon.status === 'active',
          'border-slate-300': coupon.status === 'expired',
          'border-blue-300': coupon.status === 'used'
        }"
      >
        <!-- Coupon Header -->
        <div class="p-5 pb-3">
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <div class="text-2xl font-bold text-rose-700 mb-1 font-mono tracking-wider">
                {{ coupon.code }}
              </div>
              <p class="text-sm text-slate-600">{{ coupon.description }}</p>
            </div>
            <span
              :class="[
                'px-2.5 py-1 text-xs font-bold rounded-full',
                coupon.status === 'active' ? 'bg-green-100 text-green-700' :
                coupon.status === 'expired' ? 'bg-slate-100 text-slate-600' :
                'bg-blue-100 text-blue-700'
              ]"
            >
              {{ coupon.status === 'active' ? $t('core.common.active') :
                 coupon.status === 'expired' ? $t('mod.offers.coupons.expired') :
                 $t('mod.offers.coupons.used') }}
            </span>
          </div>

          <!-- Discount Value -->
          <div class="bg-gradient-to-r from-rose-600 to-pink-700 text-white p-4 rounded-lg mb-3">
            <div class="text-center">
              <div class="text-3xl font-bold">
                {{ coupon.discount_type === 'percentage' ? `${coupon.discount_value}%` : `${coupon.discount_value} ${coupon.currency}` }}
              </div>
              <div class="text-xs text-rose-100 mt-1">{{ $t('mod.offers.coupons.discount') }}</div>
            </div>
          </div>

          <!-- Details -->
          <div class="space-y-2 text-sm">
            <div v-if="coupon.valid_until" class="flex items-center gap-2 text-slate-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {{ $t('mod.offers.coupons.valid_until') }}: {{ formatDate(coupon.valid_until) }}
            </div>
            <div class="flex items-center gap-2 text-slate-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ coupon.usage_count || 0 }} / {{ coupon.usage_limit || '∞' }} {{ $t('mod.offers.coupons.uses') }}
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="bg-slate-100 px-5 py-3 flex gap-2">
          <button
            @click="copyCouponCode(coupon.code)"
            class="flex-1 px-3 py-1.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            {{ $t('mod.offers.coupons.copy_code') }}
          </button>
          <button
            @click="editCoupon(coupon)"
            class="px-3 py-1.5 text-sm font-medium text-rose-700 bg-white border border-rose-300 rounded-lg hover:bg-rose-50 transition-colors"
          >
            {{ $t('core.actions.edit') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.coupons.no_coupons') }}</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

// State
const loading = ref(false)
const searchQuery = ref('')
const filterStatus = ref('')
const coupons = ref<any[]>([
  {
    id: 1,
    code: 'SOMMER2026',
    description: 'Sommer-Rabatt auf alle Fahrstunden',
    discount_type: 'percentage',
    discount_value: 15,
    currency: 'CHF',
    valid_until: '2026-08-31',
    usage_limit: 50,
    usage_count: 12,
    status: 'active'
  },
  {
    id: 2,
    code: 'NEUKUNDE50',
    description: 'Willkommensrabatt für Neukunden',
    discount_type: 'fixed',
    discount_value: 50,
    currency: 'CHF',
    valid_until: '2026-12-31',
    usage_limit: 100,
    usage_count: 45,
    status: 'active'
  }
])

// Filtered coupons
const filteredCoupons = computed(() => {
  let result = coupons.value

  if (filterStatus.value) {
    result = result.filter(c => c.status === filterStatus.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(c =>
      c.code.toLowerCase().includes(query) ||
      c.description.toLowerCase().includes(query)
    )
  }

  return result
})

// Format date
const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

// Actions
const openCreateDialog = () => {
  console.log('Create coupon')
}

const copyCouponCode = (code: string) => {
  navigator.clipboard.writeText(code)
  // TODO: Show toast notification
}

const editCoupon = (coupon: any) => {
  console.log('Edit coupon:', coupon)
}

onMounted(() => {
  // TODO: Load coupons from API
})
</script>
