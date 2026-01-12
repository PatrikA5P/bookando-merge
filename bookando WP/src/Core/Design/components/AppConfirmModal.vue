<!-- AppConfirmModal.vue -->
<template>
  <transition name="fade">
    <div
      v-if="visible"
      class="bookando-confirm-overlay"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      :aria-describedby="descId"
    >
      <div class="bookando-confirm-modal">
        <h3 :id="titleId">
          {{ title }}
        </h3>
        <p :id="descId">
          <slot />
        </p>
        <div class="modal-actions">
          <button
            class="bookando-btn"
            type="button"
            @click="$emit('confirm')"
          >
            {{ labelConfirm }}
          </button>
          <button
            class="bookando-btn bookando-btn--danger"
            type="button"
            @click="$emit('cancel')"
          >
            {{ labelCancel }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
/**
 * @component AppConfirmModal
 * @description
 * Accessible confirmation dialog modal for destructive or important actions.
 *
 * Features:
 * - ARIA-compliant dialog with proper role and labels
 * - Customizable title and button labels
 * - Fade transition animation
 * - Emits confirm/cancel events for parent handling
 * - Slot for custom message content
 *
 * @example
 * <AppConfirmModal
 *   :visible="showDeleteConfirm"
 *   :title="$t('confirmDelete')"
 *   :labelConfirm="$t('delete')"
 *   :labelCancel="$t('cancel')"
 *   @confirm="handleDelete"
 *   @cancel="showDeleteConfirm = false"
 * >
 *   Are you sure you want to delete this item? This action cannot be undone.
 * </AppConfirmModal>
 */
import { computed } from 'vue'

const props = defineProps({
  visible: { type: Boolean, required: true },
  title: { type: String, required: true },
  labelConfirm: { type: String, required: true },
  labelCancel: { type: String, required: true }
})

const emit = defineEmits(['confirm', 'cancel'])

const titleId = computed(() => 'confirm-modal-title')
const descId = computed(() => 'confirm-modal-desc')
</script>
