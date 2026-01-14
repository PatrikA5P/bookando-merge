<!-- SettingsCard.vue -->
<template>
  <AppCard
    :hide-header="true"
    :disabled="disabled"
    :hoverable="true"
    :clickable="!disabled"
    :height="height"
    :height-px="heightPx"
    :expandable="false"
    rounded="sm"
    shadow="1"
    padding="md"
    body-padding="0"
    :cols="'1fr'"
    :rows="'auto 1fr auto'"
    place-items="start"
    @click="$emit('click')"
  >
    <!-- Row 1: Icon + Titel (Icon NUR neben dem Titel) -->
    <div
      class="bookando-flex bookando-items-center bookando-gap-sm"
      style="min-width:0;"
    >
      <AppIcon
        v-if="icon"
        :name="icon"
        class="bookando-icon--lg"
      />
      <h3
        class="bookando-ellipsis"
        style="margin:0;"
      >
        {{ title }}
      </h3>
    </div>

    <!-- Row 2: Inhalt (nimmt Resthoehe) -->
    <div
      class="bookando-flex bookando-flex-col bookando-gap-sm"
      style="min-height:0; width:100%;"
    >
      <p
        v-if="desc"
        class="bookando-text-muted"
        style="margin:0;"
      >
        {{ desc }}
      </p>
      <slot />
    </div>

    <!-- Row 3: Link unten links -->
    <div style="justify-self:start;">
      <a
        v-if="link && !disabled"
        class="bookando-card__link"
        href="#"
        @click.prevent="$emit('click')"
      >{{ link }}</a>
      <span
        v-else-if="link"
        class="bookando-card__link disabled"
      >{{ link }}</span>
      <slot name="link" />
    </div>
  </AppCard>
</template>

<script setup lang="ts">
import AppCard from '@core/Design/components/AppCard.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'

defineProps<{
  icon?: string
  title: string
  desc?: string
  link?: string
  disabled?: boolean
  height?: 'xxs'|'xs'|'sm'|'md'|'lg'|'xl'|'xxl'
  heightPx?: number
}>()

defineEmits<{ (event:'click'): void }>()
</script>
