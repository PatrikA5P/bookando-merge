<!-- AppPageHeader.vue -->
<template>
  <div class="bookando-page-header">
    <div class="bookando-header-left">
      <img
        :src="logo"
        :alt="t('core.brand') || 'Bookando'"
        class="bookando-logo"
      >
      <!-- Brand untenhalb von hideBrandBelow ausblenden -->
      <span
        v-show="!hideBrand"
        class="bookando-brand"
      >{{ t('core.brand') || 'Bookando' }}</span>

      <!-- Modultitel nur ausblenden, falls hideTitleBelow gesetzt ist -->
      <span
        v-show="!hideTitle"
        class="bookando-module-title bookando-borderl-lg-muted"
      >
        {{ title }}
      </span>
    </div>

    <div class="bookando-header-right">
      <slot name="right" />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * PageHeader mit anpassbarem Container (z.B. Padding via containerClass)
 *
 * Props:
 * - title:         string (Pflicht)
 * - containerClass: string[] | string   | Default: ''
 * - hideBrandOnMobile: boolean         | Default: true
 */
import { ref } from 'vue'
import logo from '@core/Design/assets/images/bookando-logo.png'
import { useResponsive } from '@core/Composables/useResponsive'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

type BP = 'sm'|'md'|'lg'|'xl'

const props = withDefaults(defineProps<{
  title: string
  containerClass?: string | string[] | Record<string, boolean>
  /** Blendet das Brand "Bookando" unterhalb des Breakpoints aus (Default: md) */
  hideBrandBelow?: BP
  /** Optional: Den Titel unterhalb eines Breakpoints ausblenden (Default: nichts → immer sichtbar) */
  hideTitleBelow?: BP | null
}>(), {
  containerClass: '',
  hideBrandBelow: 'md',
  hideTitleBelow: null
})

const { isBelow } = useResponsive()
const hideBrand = isBelow(props.hideBrandBelow)
const hideTitle = props.hideTitleBelow ? isBelow(props.hideTitleBelow) : ref(false)
</script>

