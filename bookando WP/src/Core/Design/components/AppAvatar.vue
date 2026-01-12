<!-- AppAvatar.vue -->
<template>
  <span
    class="bookando-avatar"
    :class="[
      sizeClass,
      isInteractive ? 'bookando-avatar--interactive' : '',
      hasImage ? '' : 'bookando-avatar--empty'
    ]"
    :title="title || ''"
  >
    <img
      v-if="hasImage && !failed"
      :src="url"
      class="bookando-avatar__img"
      :alt="alt || t('ui.common.image')"
      @error="failed = true"
    >
    <span
      v-else
      class="bookando-avatar__initials"
      aria-hidden="true"
    >
      {{ initials }}
    </span>

    <!-- Hover-Overlay (graut aus) -->
    <span
      class="bookando-avatar__overlay"
      aria-hidden="true"
    >
      <!-- zentrierter Upload-Button -->
      <button
        v-if="canUpload"
        type="button"
        class="bookando-avatar__action bookando-avatar__btn bookando-avatar__btn--center"
        :aria-label="uploadLabel"
        @click="$emit('upload')"
      >
        <AppIcon
          name="upload"
          class="bookando-icon bookando-icon--md"
          :aria-label="uploadLabel || t('ui.upload.avatar_upload')"
        />
      </button>
    </span>

    <!-- Corner Remove-Badge -->
    <button
      v-if="canRemove"
      type="button"
      class="bookando-avatar__badge-remove bookando-avatar__btn bookando-avatar__btn--corner"
      :aria-label="removeLabel"
      @click="$emit('remove')"
    >
      <AppIcon
        name="trash"
        class="bookando-icon bookando-icon--sm"
        :aria-label="removeLabel || t('ui.upload.avatar_remove')"
      />
    </button>
  </span>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIcon from '@core/Design/components/AppIcon.vue'

const props = defineProps<{
  src?: string | Record<string, any> | null
  initials?: string
  size?: 'xxs'|'xs'|'sm'|'md'|'lg'|'xl'
  alt?: string
  title?: string
  fit?: 'cover' | 'contain'
  canUpload?: boolean
  canRemove?: boolean
}>()

defineEmits<{ (event:'upload'): void; (event:'remove'): void }>()

const { t } = useI18n()
const failed = ref(false)

const url = computed(() => {
  const v = props.src as any
  if (!v) return ''
  if (typeof v === 'string') return v
  if (typeof v.url === 'string') return v.url
  if (typeof v.src === 'string') return v.src
  if (v.sizes) return v.sizes.medium || v.sizes.full || v.sizes.thumbnail || ''
  return ''
})

const hasImage = computed(() => !!url.value && !failed.value)
const isInteractive = computed(() => !!(props.canUpload || props.canRemove))
const sizeClass = computed(() => `bookando-avatar--${props.size ?? 'md'}`)
const uploadLabel = computed(() => t?.('ui.upload.avatar_upload') ?? 'Avatar hochladen')
const removeLabel = computed(() => t?.('ui.upload.avatar_remove') ?? 'Avatar entfernen')
</script>
