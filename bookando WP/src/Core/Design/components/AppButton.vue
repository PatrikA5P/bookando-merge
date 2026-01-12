<!-- AppButton.vue -->
<template>
  <!-- Mit Tooltip -->
  <AppTooltip
    v-if="tooltip"
    :text="tooltip"
    :position="tooltipPosition"
    :delay="tooltipDelay"
  >
    <component
      :is="asLabel ? 'label' : 'button'"
      v-bind="btnAttrs"
      :type="!asLabel ? nativeType : undefined"
      :disabled="disabled || loading"
      :class="buttonClasses"
      :style="style"
      @click="onClick"
    >
      <template v-if="showLeftIcon">
        <AppIcon
          :name="icon"
          :class="iconClasses"
          :style="iconInlineStyle"
        />
      </template>

      <template v-if="showText">
        <span
          class="bookando-btn__label"
          :class="textColorClass"
          :style="textInlineStyle"
        >
          <slot v-if="!loading" />
        </span>
      </template>

      <template v-if="showRightIcon">
        <AppIcon
          :name="icon"
          :class="iconClasses"
          :style="iconInlineStyle"
        />
      </template>

      <!-- ⚠️ Keine $attrs ans Input weiterreichen -->
      <input
        v-if="asLabel"
        type="file"
        class="sr-only"
        v-bind="fileInputAttrs"
      >
      <span
        v-if="loading"
        class="bookando-btn__spinner"
      />
    </component>
  </AppTooltip>

  <!-- Ohne Tooltip -->
  <component
    :is="asLabel ? 'label' : 'button'"
    v-else
    v-bind="btnAttrs"
    :type="!asLabel ? nativeType : undefined"
    :disabled="disabled || loading"
    :class="buttonClasses"
    :style="style"
    @click="onClick"
  >
    <template v-if="showLeftIcon">
      <AppIcon
        :name="icon"
        :class="iconClasses"
        :style="iconInlineStyle"
      />
    </template>

    <template v-if="showText">
      <span
        class="bookando-btn__label"
        :class="textColorClass"
        :style="textInlineStyle"
      >
        <slot v-if="!loading" />
      </span>
    </template>

    <template v-if="showRightIcon">
      <AppIcon
        :name="icon"
        :class="iconClasses"
        :style="iconInlineStyle"
      />
    </template>

    <!-- ⚠️ Keine $attrs ans Input weiterreichen -->
    <input
      v-if="asLabel"
      type="file"
      class="sr-only"
      v-bind="fileInputAttrs"
    >
    <span
      v-if="loading"
      class="bookando-btn__spinner"
    />
  </component>
</template>

<script setup lang="ts">
/**
 * @component AppButton
 * @description
 * Flexible, reusable button component with extensive customization options.
 *
 * Features:
 * - Multiple variants: primary, secondary, accent, danger, warning, success, info, muted
 * - Multiple button types: full, outline, ghost, link
 * - Icon support with customizable position (left/right) and independent coloring
 * - Responsive sizing: xs, sm, md, lg, dynamic
 * - Loading state with spinner
 * - Tooltip integration
 * - File upload mode (as label)
 * - Mobile-specific icon-only view
 *
 * @example
 * <AppButton
 *   variant="primary"
 *   icon="check"
 *   :loading="isSubmitting"
 *   @click="handleSubmit"
 * >
 *   Save Changes
 * </AppButton>
 */
import { computed, useAttrs, defineOptions } from 'vue'
import AppIcon from './AppIcon.vue'
import AppTooltip from './AppTooltip.vue'

defineOptions({ inheritAttrs: false })

const emit = defineEmits<{
  (event:'click', ev: MouseEvent): void
}>()

const TOKEN_COLOR_KEYS = [
  'text', 'primary', 'secondary', 'accent', 'danger', 'warning',
  'success', 'info', 'muted', 'white', 'dark', 'black'
]

const props = defineProps({
  isMobileView: { type: Boolean, default: false },
  iconOnlyOnMobile: { type: Boolean, default: false },

  variant:      { type: String,  default: 'primary' },
  size:         { type: String,  default: 'dynamic' },
  btnType:      { type: String,  default: 'full' },
  icon:         { type: String,  default: '' },
  iconPosition: { type: String,  default: 'left' },

  /** 🎨 Neu: unabhängige Farben */
  textColor:    { type: String,  default: '' }, // Keys aus $bookando-text-utilities ODER beliebiger CSS-Farbwert
  iconColor:    { type: String,  default: '' }, // dito

  iconSize:     { type: String,  default: 'md' },
  block:        { type: Boolean, default: false },
  loading:      { type: Boolean, default: false },
  asLabel:      { type: Boolean, default: false },
  type:         { type: String,  default: 'button' },
  disabled:     { type: Boolean, default: false },
  customClass:  { type: String,  default: '' },
  style:        { type: [String, Object], default: '' },

  // Tooltip
  tooltip:         { type: String, default: '' },
  tooltipPosition: { type: String, default: 'top' },
  tooltipDelay:    { type: Number, default: 1500 },
})

/* ---------- Attrs & sichere Weitergabe ---------- */
const attrs = useAttrs() as Record<string, any>

/**
 * Nur "sichere" Attribute an das interaktive Element weiterreichen.
 * Explizite Props (type, disabled, class, style) haben Vorrang.
 * onClick* wird nicht durchgereicht (wir emittieren selbst).
 */
const btnAttrs = computed(() => {
  const out: Record<string, any> = { ...attrs }
  for (const k of Object.keys(out)) {
    if (
      k === 'type' ||
      k === 'disabled' ||
      k === 'class' ||
      k === 'style' ||
      /^onClick/i.test(k)
    ) delete out[k]
  }
  return out
})

/** Für das File-Input nur echte Input-Attribute erlauben */
const fileInputAttrs = computed(() => {
  const allow = ['accept', 'multiple', 'name', 'capture']
  return Object.fromEntries(
    allow.filter(k => attrs[k] !== undefined).map(k => [k, attrs[k]])
  )
})

function onClick(ev: MouseEvent) {
  // Einheitliche, einmalige Klick-Emission
  emit('click', ev)
}

/* ---------- Dynamische Ableitungen ---------- */
const effectiveBtnType = computed(() => {
  if (props.iconOnlyOnMobile && props.isMobileView) return 'icononly'
  return props.btnType || 'full'
})
const effectiveSize = computed(() => {
  if (props.iconOnlyOnMobile && props.isMobileView) return 'square'
  return props.size || 'dynamic'
})

const buttonClasses = computed(() => [
  'bookando-btn',
  props.variant ? `bookando-btn--${props.variant}` : '',
  effectiveSize.value !== 'dynamic' ? `bookando-btn--${effectiveSize.value}` : '',
  {
    'bookando-btn--block': props.block,
    'bookando-btn--loading': props.loading,
    'bookando-btn--disabled': props.disabled,
    'bookando-btn--icononly': effectiveBtnType.value === 'icononly',
  },
  props.customClass
].filter(Boolean))

/** Utility-Klasse aus Tokens ableiten oder leeren String liefern */
const toTextUtility = (val?: string) =>
  val && TOKEN_COLOR_KEYS.includes(val) ? `bookando-text-${val}` : ''

/** Label (Text) – Klasse + Fallback-Inline-Style */
const textColorClass = computed(() => toTextUtility(props.textColor))
const textInlineStyle = computed(() =>
  !textColorClass.value && props.textColor
    ? { color: props.textColor as string }
    : undefined
)

/** Icon – Klasse + Fallback-Inline-Style */
const iconColorClass = computed(() => toTextUtility(props.iconColor))
const iconClasses = computed(() => [
  'bookando-btn__icon',
  'bookando-icon',
  props.iconSize ? `bookando-icon--${props.iconSize}` : 'bookando-icon--md',
  iconColorClass.value
].filter(Boolean))
const iconInlineStyle = computed(() =>
  !iconColorClass.value && props.iconColor
    ? { color: props.iconColor as string }
    : undefined
)

const showLeftIcon = computed(() =>
  props.icon &&
  (effectiveBtnType.value === 'full' || effectiveBtnType.value === 'icononly') &&
  props.iconPosition === 'left' &&
  !props.loading
)
const showRightIcon = computed(() =>
  props.icon &&
  (effectiveBtnType.value === 'full' || effectiveBtnType.value === 'icononly') &&
  props.iconPosition === 'right' &&
  !props.loading
)
const showText = computed(() =>
  (effectiveBtnType.value === 'full' || effectiveBtnType.value === 'textonly') &&
  !props.loading &&
  !(props.iconOnlyOnMobile && props.isMobileView)
)

const nativeType = computed(() =>
  ['button', 'submit', 'reset'].includes(props.type) ? props.type : 'button'
)
</script>
