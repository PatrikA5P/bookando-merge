<template>
  <div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.tools.api_management') }}</h3>
      <button class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 flex items-center gap-2">
        <PlusIcon :size="18" /> {{ $t('mod.tools.create_api_key') }}
      </button>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200">
      <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.api_keys') }}</h4>
      <div class="space-y-3">
        <div v-for="key in apiKeys" :key="key.id" class="flex items-center justify-between p-4 border border-slate-200 rounded-lg hover:bg-slate-50">
          <div>
            <div class="font-mono text-sm font-medium text-slate-800">{{ key.key }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $t('mod.tools.created') }}: {{ key.created }} | {{ $t('mod.tools.last_used') }}: {{ key.lastUsed }}</div>
          </div>
          <div class="flex gap-2">
            <button class="p-2 text-slate-400 hover:text-brand-600 rounded transition-colors">
              <CopyIcon :size="16" />
            </button>
            <button class="p-2 text-slate-400 hover:text-rose-600 rounded transition-colors">
              <Trash2Icon :size="16" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white p-6 rounded-xl border border-slate-200">
      <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.tools.api_documentation') }}</h4>
      <p class="text-sm text-slate-600 mb-4">{{ $t('mod.tools.api_doc_desc') }}</p>
      <button class="px-4 py-2 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 text-sm font-medium">
        {{ $t('mod.tools.view_documentation') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Plus as PlusIcon, Copy as CopyIcon, Trash2 as Trash2Icon } from 'lucide-vue-next'
const { t: $t } = useI18n()

const apiKeys = ref([
  { id: 1, key: 'bk_live_abc123def456ghi789jkl', created: '15.01.2024', lastUsed: '20.01.2024' },
  { id: 2, key: 'bk_live_xyz987uvw654rst321opq', created: '10.01.2024', lastUsed: '19.01.2024' }
])
</script>
