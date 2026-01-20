<template>
  <div class="p-6 space-y-6">
    <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.tools.notification_settings') }}</h3>

    <div class="space-y-4">
      <div v-for="setting in notificationSettings" :key="setting.id" class="bg-white p-6 rounded-xl border border-slate-200">
        <div class="flex justify-between items-start mb-3">
          <div>
            <h4 class="font-bold text-slate-800">{{ setting.title }}</h4>
            <p class="text-sm text-slate-600 mt-1">{{ setting.description }}</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input v-model="setting.enabled" type="checkbox" class="sr-only peer" />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600"></div>
          </label>
        </div>
        <div v-if="setting.enabled" class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-100">
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="setting.email" class="rounded text-brand-600" />
            <span class="text-slate-700">E-Mail</span>
          </label>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="setting.push" class="rounded text-brand-600" />
            <span class="text-slate-700">Push</span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
const { t: $t } = useI18n()

const notificationSettings = ref([
  { id: 1, title: $t('mod.tools.new_appointments'), description: $t('mod.tools.new_appointments_desc'), enabled: true, email: true, push: true },
  { id: 2, title: $t('mod.tools.cancellations'), description: $t('mod.tools.cancellations_desc'), enabled: true, email: true, push: false },
  { id: 3, title: $t('mod.tools.reminders'), description: $t('mod.tools.reminders_desc'), enabled: true, email: false, push: true },
  { id: 4, title: $t('mod.tools.system_alerts'), description: $t('mod.tools.system_alerts_desc'), enabled: false, email: false, push: false }
])
</script>
