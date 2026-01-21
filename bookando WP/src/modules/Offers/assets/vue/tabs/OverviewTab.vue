<template>
  <div class="p-6 space-y-6">
    <!-- Sub-Navigation for Overview Sections -->
    <div class="flex gap-2 border-b border-slate-200">
      <button
        v-for="section in sections"
        :key="section.value"
        @click="activeSection = section.value"
        :class="[
          'px-4 py-2.5 text-sm font-semibold transition-all relative',
          activeSection === section.value
            ? 'text-rose-700 border-b-2 border-rose-700'
            : 'text-slate-600 hover:text-slate-900'
        ]"
      >
        {{ section.label }}
      </button>
    </div>

    <!-- Section Content -->
    <div class="animate-in fade-in duration-200">
      <!-- Dienstleistungen Section -->
      <DienstleistungenView v-if="activeSection === 'dienstleistungen'" />

      <!-- Kurse Section -->
      <KurseView v-if="activeSection === 'kurse'" />

      <!-- Online Section -->
      <OnlineView v-if="activeSection === 'online'" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DienstleistungenView from './overview/DienstleistungenView.vue'
import KurseView from './overview/KurseView.vue'
import OnlineView from './overview/OnlineView.vue'

const { t: $t } = useI18n()

// Active section state
const activeSection = ref<'dienstleistungen' | 'kurse' | 'online'>('dienstleistungen')

// Section configuration
const sections = [
  {
    value: 'dienstleistungen',
    label: $t('mod.offers.overview.sections.dienstleistungen')
  },
  {
    value: 'kurse',
    label: $t('mod.offers.overview.sections.kurse')
  },
  {
    value: 'online',
    label: $t('mod.offers.overview.sections.online')
  }
]
</script>
