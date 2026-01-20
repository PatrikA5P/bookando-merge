<template>
  <div class="p-6 space-y-6">
    <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.tools.system_information') }}</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.system_stats') }}</h4>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-sm text-slate-600">{{ $t('mod.tools.version') }}</span>
            <span class="text-sm font-medium text-slate-800">2.4.1</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-slate-600">{{ $t('mod.tools.database_size') }}</span>
            <span class="text-sm font-medium text-slate-800">245 MB</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-slate-600">{{ $t('mod.tools.uptime') }}</span>
            <span class="text-sm font-medium text-slate-800">15 {{ $t('mod.tools.days') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-slate-600">{{ $t('mod.tools.last_backup') }}</span>
            <span class="text-sm font-medium text-slate-800">{{ new Date().toLocaleDateString('de-CH') }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-xl border border-slate-200">
        <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.system_actions') }}</h4>
        <div class="space-y-2">
          <button class="w-full p-3 text-left hover:bg-slate-50 rounded-lg border border-slate-200 transition-colors">
            {{ $t('mod.tools.clear_cache') }}
          </button>
          <button class="w-full p-3 text-left hover:bg-slate-50 rounded-lg border border-slate-200 transition-colors">
            {{ $t('mod.tools.create_backup') }}
          </button>
          <button class="w-full p-3 text-left hover:bg-slate-50 rounded-lg border border-slate-200 transition-colors">
            {{ $t('mod.tools.check_updates') }}
          </button>
          <button class="w-full p-3 text-left hover:bg-rose-50 rounded-lg border border-rose-200 text-rose-600 transition-colors">
            {{ $t('mod.tools.restart_system') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t: $t } = useI18n()
</script>
