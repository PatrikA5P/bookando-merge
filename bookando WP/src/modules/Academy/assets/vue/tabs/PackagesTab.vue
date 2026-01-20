<template>
  <div class="p-6">
    <!-- Header mit Aktion -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">{{ $t('mod.academy.packages.title') }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $t('mod.academy.packages.description') }}</p>
      </div>
      <button
        @click="$emit('create-package')"
        class="flex items-center gap-2 bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors font-medium"
      >
        <PlusIcon :size="18" />
        {{ $t('mod.academy.packages.create') }}
      </button>
    </div>

    <!-- Packages Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="pkg in packages"
        :key="pkg.id"
        @click="$emit('edit-package', pkg.id)"
        class="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg hover:border-brand-200 transition-all cursor-pointer overflow-hidden flex flex-col"
      >
        <!-- Header mit Kategorie-Badge -->
        <div class="bg-gradient-to-r from-brand-500 to-brand-600 p-4">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <span
                v-if="pkg.category"
                class="inline-block bg-white/20 text-white text-xs font-bold px-2 py-1 rounded mb-2"
              >
                {{ pkg.category }}
              </span>
              <h3 class="text-lg font-bold text-white leading-tight">
                {{ pkg.title }}
              </h3>
            </div>
            <PackageIcon :size="32" class="text-white/80" />
          </div>
        </div>

        <!-- Package Content -->
        <div class="p-5 flex-1 flex flex-col">
          <p class="text-sm text-slate-600 mb-4 line-clamp-2 flex-1">
            {{ pkg.description || $t('mod.academy.packages.no_description') }}
          </p>

          <!-- Items Summary -->
          <div class="mb-4">
            <div class="text-xs font-semibold text-slate-600 uppercase mb-2">
              {{ $t('mod.academy.packages.includes') }}
            </div>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="(item, idx) in (pkg.items || []).slice(0, 3)"
                :key="idx"
                class="text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded"
              >
                {{ item.quantity }}x {{ item.title }}
              </span>
              <span
                v-if="(pkg.items || []).length > 3"
                class="text-xs bg-slate-100 text-slate-500 px-2 py-1 rounded"
              >
                +{{ (pkg.items || []).length - 3 }} {{ $t('mod.academy.packages.more') }}
              </span>
            </div>
          </div>

          <!-- Pricing -->
          <div class="border-t border-slate-100 pt-4">
            <div class="flex items-end justify-between">
              <div>
                <div v-if="pkg.originalPrice && pkg.originalPrice > pkg.price" class="text-xs text-slate-400 line-through">
                  {{ formatPrice(pkg.originalPrice, pkg.currency) }}
                </div>
                <div class="text-2xl font-bold text-brand-600">
                  {{ formatPrice(pkg.price, pkg.currency) }}
                </div>
              </div>
              <div v-if="pkg.discountPercent && pkg.discountPercent > 0" class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-1 rounded">
                -{{ pkg.discountPercent }}%
              </div>
            </div>

            <!-- Validity -->
            <div v-if="pkg.validityDays" class="text-xs text-slate-500 mt-2 flex items-center gap-1">
              <ClockIcon :size="12" />
              {{ $t('mod.academy.packages.valid_for', { days: pkg.validityDays }) }}
            </div>
          </div>
        </div>

        <!-- Status Footer -->
        <div class="px-5 pb-4">
          <span
            :class="[
              'inline-block text-xs font-bold px-2 py-1 rounded',
              pkg.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'
            ]"
          >
            {{ $t(`mod.academy.packages.status.${pkg.status}`) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!packages || packages.length === 0" class="text-center py-16">
      <PackageIcon :size="64" class="mx-auto text-slate-300 mb-4" />
      <h3 class="text-lg font-semibold text-slate-600 mb-2">
        {{ $t('mod.academy.packages.empty_title') }}
      </h3>
      <p class="text-sm text-slate-500 mb-6">
        {{ $t('mod.academy.packages.empty_description') }}
      </p>
      <button
        @click="$emit('create-package')"
        class="inline-flex items-center gap-2 bg-brand-600 text-white px-6 py-3 rounded-lg hover:bg-brand-700 transition-colors font-medium"
      >
        <PlusIcon :size="18" />
        {{ $t('mod.academy.packages.create_first') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Package as PackageIcon, Plus as PlusIcon, Clock as ClockIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

interface PackageItem {
  type: 'course' | 'lesson' | 'training_hours'
  id?: string
  title: string
  quantity: number
  description?: string
}

interface Package {
  id: string
  title: string
  description?: string
  items?: PackageItem[]
  price: number
  originalPrice?: number
  discountPercent?: number
  currency: string
  validityDays?: number
  category?: string
  status: 'active' | 'inactive' | 'archived'
}

defineProps<{
  packages: Package[]
}>()

defineEmits<{
  'create-package': []
  'edit-package': [packageId: string]
}>()

const { t: $t } = useI18n()

const formatPrice = (price: number, currency: string = 'CHF'): string => {
  return new Intl.NumberFormat('de-CH', {
    style: 'currency',
    currency: currency,
  }).format(price)
}
</script>
