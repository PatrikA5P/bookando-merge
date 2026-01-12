<!-- AppModal.vue -->
<template>
  <!--
    Blockierendes, zentriertes Modal
    - Overlay + Box via .bookando-modal / .bookando-modal__content
    - Buttons weiterhin über <AppButton>
    - A11y: aria-labelledby/aria-describedby, ESC/Enter-Handling im Script
  -->
  <transition name="fade">
    <div
      v-if="show"
      class="bookando-modal"
      role="dialog"
      :aria-modal="true"
      :aria-labelledby="titleId"
      :aria-describedby="descId"
      tabindex="-1"
      @keydown.stop
      @click="onOverlayClick"
    >
      <div
        ref="content"
        class="bookando-modal__content"
        @click.stop
      >
        <!-- Titel -->
        <header class="bookando-modal__header">
          <h3 :id="titleId">
            {{ resolvedTitle }}
          </h3>
        </header>

        <!-- Nachricht / Slot -->
        <p :id="descId">
          <slot>{{ resolvedMessage }}</slot>
        </p>

        <!-- Aktionen (Footer) -->
        <footer class="bookando-modal__footer">
          <!-- Bestätigen -->
          <AppButton
            ref="confirmBtn"
            btn-type="full"
            :variant="effectiveConfirmVariant"
            size="dynamic"
            type="button"
            :disabled="busy"
            @click="onConfirm"
          >
            {{ resolvedConfirmText }}
          </AppButton>

          <!-- Abbrechen -->
          <AppButton
            ref="cancelBtn"
            btn-type="full"
            :variant="effectiveCancelVariant"
            size="dynamic"
            type="button"
            :disabled="busy"
            @click="onCancel"
          >
            {{ resolvedCancelText }}
          </AppButton>
        </footer>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
/**
 * AppModal – zentriertes Confirm/Cancel-Modal mit vereinheitlichter Optik.
 * - Nutzt .bookando-modal*, Buttons via <AppButton>
 * - ESC: Abbrechen (wenn closeOnEsc)
 * - Enter: Bestätigen (wenn nicht busy)
 * - Auto-Focus (confirm | cancel)
 * - Klick außerhalb (Overlay) optional zum Schließen (closeOnBackdrop)
 */
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFocusTrap } from '@core/Design/composables/useFocusTrap'
import AppButton from '@core/Design/components/AppButton.vue'

const props = defineProps<{
  show: boolean
  type?: 'default' | 'info' | 'success' | 'warning' | 'danger'
  module?: string
  action?: string
  titleKey?: string
  messageKey?: string
  confirmTextKey?: string
  cancelTextKey?: string
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  i18nParams?: Record<string, any>
  closeOnEsc?: boolean
  closeOnBackdrop?: boolean
  autoFocus?: 'confirm' | 'cancel' | null
  busy?: boolean
  confirmVariant?: string
  cancelVariant?: string
}>()

const { t, te } = useI18n()
const modalType = computed(() => props.type ?? 'default')
const closeOnEsc = computed(() => props.closeOnEsc ?? true)
const closeOnBackdrop = computed(() => props.closeOnBackdrop ?? false)
const autoFocus = computed(() => props.autoFocus ?? 'confirm')

const emit = defineEmits<{ (event: 'confirm'): void; (event: 'cancel'): void }>()
const titleId = `modal-title-${Math.random().toString(36).slice(2, 8)}`
const descId  = `modal-desc-${Math.random().toString(36).slice(2, 8)}`
const content    = ref<HTMLElement | null>(null)
const confirmBtn = ref<any>(null)
const cancelBtn  = ref<any>(null)

// Focus-Trap Setup
const { hasFocus, activate, deactivate } = useFocusTrap(content, {
  immediate: false,
  allowOutsideClick: true,
  escapeDeactivates: false, // Wir handhaben ESC selbst
  returnFocusOnDeactivate: true,
  initialFocus: false // Wir fokussieren manuell
})

const confirmVariantByType = computed(() => {
  switch (modalType.value) {
    case 'danger':  return 'danger'
    case 'warning': return 'warning'
    case 'success': return 'primary'
    case 'info':    return 'primary'
    default:        return 'primary'
  }
})
const effectiveConfirmVariant = computed(() =>
  (props.confirmVariant && String(props.confirmVariant)) || confirmVariantByType.value
)
const effectiveCancelVariant = computed(() =>
  (props.cancelVariant && String(props.cancelVariant)) || 'secondary'
)

function resolveKey(...candidates: (string | undefined)[]) {
  for (const key of candidates) { if (key && te(key)) return key }
  return undefined
}
const resolvedTitle = computed(() => {
  if (props.title) return props.title
  const key = resolveKey(
    props.titleKey,
    props.module && props.action ? `mod.${props.module}.actions.${props.action}.label` : undefined,
    props.action ? `core.actions.${props.action}.label` : undefined,
    'ui.dialog.confirm_title'
  )
  return key ? t(key, props.i18nParams) : t('ui.dialog.confirm_title')
})
const resolvedMessage = computed(() => {
  if (props.message) return props.message
  const key = resolveKey(
    props.messageKey,
    props.module && props.action ? `mod.${props.module}.actions.${props.action}.confirm` : undefined,
    props.action ? `core.actions.${props.action}.confirm` : undefined,
    'core.bulk.confirmMessage'
  )
  return key ? t(key, props.i18nParams) : t('core.bulk.confirmMessage')
})
const resolvedConfirmText = computed(() => {
  if (props.confirmText) return props.confirmText
  const key = resolveKey(
    props.confirmTextKey,
    props.action === 'hard_delete' ? 'core.common.delete' : undefined,
    'core.bulk.confirm'
  )
  return key ? t(key) : t('core.bulk.confirm')
})
const resolvedCancelText = computed(() => {
  if (props.cancelText) return props.cancelText
  const key = resolveKey(props.cancelTextKey, 'core.common.cancel')
  return key ? t(key) : t('core.common.cancel')
})

function onConfirm() { if (!props.busy) emit('confirm') }
function onCancel()  { if (!props.busy) emit('cancel') }
function onOverlayClick() { if (closeOnBackdrop.value && !props.busy) onCancel() }

function onKeyDown(event: KeyboardEvent) {
  if (!props.show || props.busy) return
  if (event.key === 'Escape' && closeOnEsc.value) { event.preventDefault(); onCancel() }
  else if (event.key === 'Enter') { event.preventDefault(); onConfirm() }
}

async function focusInitial() {
  await nextTick()

  // Aktiviere Focus-Trap
  if (content.value) {
    activate()
  }

  // Fokussiere gewünschtes Element
  await nextTick()
  if (autoFocus.value === 'confirm' && confirmBtn.value) {
    (confirmBtn.value.$el?.focus?.() ?? confirmBtn.value?.focus?.())
  } else if (autoFocus.value === 'cancel' && cancelBtn.value) {
    (cancelBtn.value.$el?.focus?.() ?? cancelBtn.value?.focus?.())
  } else {
    content.value?.focus?.()
  }
}

onMounted(() => { document.addEventListener('keydown', onKeyDown, { capture: true }) })
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeyDown, { capture: true })
  deactivate()
})

watch(() => props.show, async (open) => {
  if (open) {
    await focusInitial()
  } else {
    deactivate()
  }
})
</script>
