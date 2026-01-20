<template>
  <div class="p-6 space-y-6">
    <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.tools.data_import') }}</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.import_customers') }}</h4>
        <p class="text-sm text-slate-600 mb-4">{{ $t('mod.tools.import_customers_desc') }}</p>
        <div class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-brand-400 transition-colors cursor-pointer">
          <UploadIcon :size="32" class="mx-auto text-slate-400 mb-2" />
          <p class="text-sm font-medium text-slate-700">{{ $t('mod.tools.drop_file_or_click') }}</p>
          <p class="text-xs text-slate-500 mt-1">CSV, Excel (max 10MB)</p>
        </div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.import_appointments') }}</h4>
        <p class="text-sm text-slate-600 mb-4">{{ $t('mod.tools.import_appointments_desc') }}</p>
        <div class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-brand-400 transition-colors cursor-pointer">
          <UploadIcon :size="32" class="mx-auto text-slate-400 mb-2" />
          <p class="text-sm font-medium text-slate-700">{{ $t('mod.tools.drop_file_or_click') }}</p>
          <p class="text-xs text-slate-500 mt-1">CSV, iCal (max 10MB)</p>
        </div>
      </div>
    </div>

    <div class="bg-amber-50 p-4 rounded-lg border border-amber-200">
      <p class="text-sm text-amber-800">
        <strong>{{ $t('mod.tools.note') }}:</strong> {{ $t('mod.tools.import_note') }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Upload as UploadIcon } from 'lucide-vue-next'
const { t: $t } = useI18n()
</script>
