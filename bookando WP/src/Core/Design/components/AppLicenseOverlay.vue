<!-- AppLicenseOverlay.vue -->
<template>
  <section
    v-if="!hidden"
    class="bookando-license-overlay bookando-bg-white bookando-rounded-lg bookando-shadow-1 bookando-p-xxl bookando-text-center"
    role="region"
    :aria-labelledby="headingId"
  >
    <h3
      :id="headingId"
      class="bookando-mb-md"
    >
      {{ t('mod.license.upgrade_required') || 'Upgrade erforderlich' }}
    </h3>

    <p class="bookando-mb-lg">
      {{ t('mod.license.not_in_plan') || 'Dieses Modul ist im aktuellen Plan nicht enthalten.' }}
      <br>
      <b v-if="plan">
        {{ t('mod.license.required', { plan }) || (plan + ' ist erforderlich.') }}
      </b>
      <b v-else>
        {{ t('mod.license.required_generic') || 'Lizenz erforderlich' }}
      </b>
    </p>

    <div class="bookando-flex bookando-gap-sm bookando-justify-center">
      <!-- Primäre Aktion: Upgrade -->
      <a
        class="bookando-btn bookando-btn--primary"
        :href="upgradeUrl"
        target="_blank"
        rel="noopener"
      >
        {{ t('mod.license.upgrade_now') || 'Jetzt upgraden' }}
      </a>

      <!-- Sekundär: Overlay schliessen (optional) -->
      <button
        v-if="dismissible"
        type="button"
        class="bookando-btn"
        @click="hidden = true"
      >
        {{ t('core.common.ok') || 'OK' }}
      </button>
    </div>

    <!-- Erweiterbar: eigene Aktionen/Erklärungen -->
    <div class="bookando-mt-md">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = withDefaults(defineProps<{
  plan?: string | null
  upgradeUrl?: string
  dismissible?: boolean
}>(), {
  plan: null,
  upgradeUrl: 'https://bookando.de/upgrade',
  dismissible: true
})

const { t } = useI18n()
const hidden = ref(false)
const headingId = computed(() => 'lic-ovl-' + Math.random().toString(36).slice(2, 8))
</script>

<style scoped>
.bookando-license-overlay {
  border: 1px dashed #ef4444; /* leichtes visuelles Signal */
}
</style>
