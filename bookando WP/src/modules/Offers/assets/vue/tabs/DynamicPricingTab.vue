<template>
  <div class="p-6 space-y-6">
    <!-- Info Banner -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <h3 class="font-bold text-blue-900 mb-1">{{ $t('mod.offers.dynamic_pricing.info_title') }}</h3>
          <p class="text-sm text-blue-700">
            {{ $t('mod.offers.dynamic_pricing.info_text') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-slate-900">{{ $t('mod.offers.dynamic_pricing.rules') }}</h2>
      <button
        @click="openCreateDialog"
        class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 text-white rounded-lg font-medium hover:bg-rose-700 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('mod.offers.dynamic_pricing.create_rule') }}
      </button>
    </div>

    <!-- Pricing Rules -->
    <div v-if="!loading && rules.length > 0" class="space-y-4">
      <div
        v-for="rule in rules"
        :key="rule.id"
        class="bg-white rounded-xl border-2 border-slate-200 overflow-hidden hover:border-rose-300 transition-all"
      >
        <!-- Rule Header -->
        <div class="bg-slate-50 px-5 py-3 flex items-center justify-between border-b border-slate-200">
          <div class="flex items-center gap-3">
            <h3 class="font-bold text-slate-900">{{ rule.name }}</h3>
            <span
              :class="[
                'px-2.5 py-0.5 text-xs font-bold rounded-full',
                rule.enabled ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'
              ]"
            >
              {{ rule.enabled ? $t('core.common.active') : $t('core.common.inactive') }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                :checked="rule.enabled"
                @change="toggleRule(rule)"
                class="sr-only peer"
              >
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-rose-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
            </label>
            <button
              @click="editRule(rule)"
              class="p-2 text-slate-600 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Rule Details -->
        <div class="p-5 space-y-4">
          <!-- Conditions -->
          <div>
            <h4 class="text-sm font-bold text-slate-700 mb-2">{{ $t('mod.offers.dynamic_pricing.conditions') }}:</h4>
            <div class="space-y-2">
              <div
                v-for="(condition, idx) in rule.conditions"
                :key="idx"
                class="flex items-center gap-2 text-sm bg-blue-50 border border-blue-200 rounded-lg px-3 py-2"
              >
                <span class="font-medium text-blue-900">{{ condition.field }}</span>
                <span class="text-blue-600">{{ condition.operator }}</span>
                <span class="font-semibold text-blue-900">{{ condition.value }}</span>
              </div>
            </div>
          </div>

          <!-- Price Adjustment -->
          <div class="bg-gradient-to-r from-rose-50 to-pink-50 border border-rose-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-slate-700">{{ $t('mod.offers.dynamic_pricing.adjustment') }}:</span>
              <div class="text-right">
                <div class="text-2xl font-bold text-rose-700">
                  {{ rule.adjustment_type === 'percentage' ? `${rule.adjustment_value > 0 ? '+' : ''}${rule.adjustment_value}%` : `${rule.adjustment_value > 0 ? '+' : ''}${rule.adjustment_value} ${rule.currency}` }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ rule.adjustment_type === 'percentage' ? $t('mod.offers.dynamic_pricing.percentage') : $t('mod.offers.dynamic_pricing.fixed_amount') }}
                </div>
              </div>
            </div>
          </div>

          <!-- Applicable Offers -->
          <div v-if="rule.applicable_offers?.length">
            <h4 class="text-sm font-bold text-slate-700 mb-2">{{ $t('mod.offers.dynamic_pricing.applicable_to') }}:</h4>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="offer in rule.applicable_offers"
                :key="offer"
                class="px-3 py-1 bg-slate-100 text-slate-700 text-sm rounded-full"
              >
                {{ offer }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.dynamic_pricing.no_rules') }}</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

// State
const loading = ref(false)
const rules = ref<any[]>([
  {
    id: 1,
    name: 'Frühbucher-Rabatt',
    enabled: true,
    conditions: [
      { field: 'Buchungszeitpunkt', operator: 'mindestens', value: '14 Tage vorher' }
    ],
    adjustment_type: 'percentage',
    adjustment_value: -10,
    currency: 'CHF',
    applicable_offers: ['Alle Kurse', 'Pakete']
  },
  {
    id: 2,
    name: 'Wochenend-Zuschlag',
    enabled: true,
    conditions: [
      { field: 'Wochentag', operator: 'ist', value: 'Samstag oder Sonntag' }
    ],
    adjustment_type: 'percentage',
    adjustment_value: 20,
    currency: 'CHF',
    applicable_offers: ['Fahrstunden']
  },
  {
    id: 3,
    name: 'Gruppen-Rabatt',
    enabled: false,
    conditions: [
      { field: 'Teilnehmerzahl', operator: '>=', value: '5' }
    ],
    adjustment_type: 'fixed',
    adjustment_value: -50,
    currency: 'CHF',
    applicable_offers: ['Kurse']
  }
])

// Actions
const openCreateDialog = () => {
  console.log('Create pricing rule')
}

const toggleRule = (rule: any) => {
  rule.enabled = !rule.enabled
  // TODO: Update via API
}

const editRule = (rule: any) => {
  console.log('Edit rule:', rule)
}

onMounted(() => {
  // TODO: Load rules from API
})
</script>
