<!-- AppBulkAction.vue -->
<template>
  <transition name="slide-up">
    <div
      v-if="selected.length"
      class="bookando-bulk-slidein"
      role="region"
      aria-live="polite"
      aria-atomic="true"
    >
      <span class="bulk-selected-count">{{ selected.length }} {{ L.selected }}</span>

      <div class="bookando-bulk-actions bookando-flex bookando-justify-end bookando-items-center bookando-gap-md">
        <!-- Dropdown -->
        <BookandoField
          v-model="localValue"
          type="dropdown"
          :options="bulkOptions"
          option-label="label"
          option-value="value"
          :placeholder="L.choose"
          :disabled="isBusy"
          :appearance="'default'"
          :z-index="10020"
          :teleport="false"
          :dropup="true"
          :grouped="false"
          label=""
          class="bulk-select"
        />

        <!-- Abbrechen -->
        <slot name="cancel-button">
          <AppButton
            variant="secondary"
            type="button"
            :disabled="isBusy"
            @click="onCancelClick"
          >
            {{ L.cancel }}
          </AppButton>
        </slot>

        <!-- Anwenden -->
        <slot name="apply-button">
          <AppButton
            variant="primary"
            type="button"
            :disabled="!localValue || isBusy"
            :loading="isBusy"
            @click="onApplyClick"
          >
            {{ isBusy ? L.applying : L.apply }}
          </AppButton>
        </slot>

        <slot name="extra-buttons" />
      </div>

      <AppModal
        v-if="showConfirm"
        :show="showConfirm"
        :title="L.confirmTitle"
        :confirm-text="L.confirmOk"
        :cancel-text="L.confirmCancel"
        :close-on-backdrop="false"
        :close-on-esc="true"
        @confirm="confirmApply"
        @cancel="cancelConfirm"
      >
        {{ L.confirmMessage }}
      </AppModal>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppModal from '@core/Design/components/AppModal.vue'

const { t } = useI18n()

const props = defineProps({
  selected: { type: Array, default: () => [] },
  loading:  { type: Boolean, default: false },
  modelValue: { type: String, default: '' },
  bulkOptions: { type: Array, default: () => [] },

  // Keine t()-Defaults hier:
  labelSelected: { type: String, default: '' },
  labelChoose:   { type: String, default: '' },
  labelApply:    { type: String, default: '' },
  labelApplying: { type: String, default: '' },
  labelCancel:   { type: String, default: '' },

  confirmBeforeApply: { type: Boolean, default: false },
  confirmTitle:       { type: String, default: '' },
  confirmMessage:     { type: String, default: '' },
  confirmLabel:       { type: String, default: '' },
  confirmCancelLabel: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue', 'apply', 'cancel'])

// i18n-Fallbacks per computed (kein Hoisting-Problem)
const L = {
  selected:        computed(() => props.labelSelected      || t('core.bulk.selected')),
  choose:          computed(() => props.labelChoose        || t('core.bulk.choose')),
  apply:           computed(() => props.labelApply         || t('core.bulk.apply')),
  applying:        computed(() => props.labelApplying      || t('core.bulk.applying')),
  cancel:          computed(() => props.labelCancel        || t('core.common.cancel')),
  confirmTitle:    computed(() => props.confirmTitle       || t('core.bulk.confirmTitle')),
  confirmMessage:  computed(() => props.confirmMessage     || t('core.bulk.confirmMessage')),
  confirmOk:       computed(() => props.confirmLabel       || t('core.bulk.confirm')),
  confirmCancel:   computed(() => props.confirmCancelLabel || t('core.common.cancel'))
}

const showConfirm = ref(false)
const localValue = ref(props.modelValue)
const isBusy = computed(() => props.loading)

watch(() => props.modelValue, v => (localValue.value = v as string))
watch(localValue, value => emit('update:modelValue', value))

function onApplyClick() {
  props.confirmBeforeApply ? showConfirm.value = true : triggerApply()
}
function triggerApply() {
  emit('apply', localValue.value)
  emit('update:modelValue', '')
  localValue.value = ''
}
function confirmApply() { showConfirm.value = false; triggerApply() }
function cancelConfirm() { showConfirm.value = false }
function onCancelClick() { if (!isBusy.value) emit('cancel') }
</script>
