<!-- src/Core/Design/components/AppOverlay.vue -->
<template>
  <div class="bookando-overlay-card bookando-mt-md">
    <div class="bookando-overlay-header">
      <div class="overlay-title">
        <slot name="title" />
      </div>
      <div class="overlay-actions">
        <slot name="actions" />
        <AppButton
          icon="x"
          variant="standard"
          size="square"
          btn-type="icononly"
          icon-size="md"
          class="overlay-close"
          :tooltip="t('Schliessen')"
          @click="$emit('close')"
        />
      </div>
    </div>
    <div class="bookando-overlay-body">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AppButton from './AppButton.vue'
const { t } = useI18n()
</script>
