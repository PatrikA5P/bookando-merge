<!-- AppDropdown.vue -->
<template>
  <div
    ref="wrapper"
    class="bookando-dropdown-wrapper"
    :class="[
      {
        'bookando-control--danger': !!error,
        'bookando-control--readonly': readonly,
        'bookando-control--disabled': disabled
      },
      widthClass
    ]"
  >
    <label
      v-if="label"
      :for="id"
      class="bookando-label bookando-sr-only"
    >
      <slot name="label">{{ label }}</slot>
    </label>

    <!-- Trigger / Button -->
    <div
      :id="id"
      ref="triggerEl"
      class="bookando-combobox-btn"
      :class="btnClass"
      :style="triggerStyle"
      role="combobox"
      aria-haspopup="listbox"
      :aria-expanded="dropdownOpen.toString()"
      :aria-controls="dropdownId"
      :aria-activedescendant="activeOptionId"
      :aria-disabled="disabled || readonly ? 'true' : undefined"
      :tabindex="disabled ? -1 : 0"
      @click="!disabled && !readonly && toggleDropdown()"
      @keydown.enter.prevent="!disabled && !readonly && toggleDropdown()"
      @keydown.space.prevent="!disabled && !readonly && toggleDropdown()"
      @keydown.esc="closeDropdown"
      @keydown.down.prevent="focusSearch"
    >
      <!-- Linkes Icon -->
      <span
        v-if="showLeftIcon"
        class="bookando-input-icon"
      >
        <AppIcon
          :name="iconLeft"
          :class="iconClass"
          :size="iconSize"
          :color="iconColor"
        />
      </span>

      <!-- Inhalt (mit Tag-Collapse) -->
      <div
        ref="contentEl"
        class="content-wrapper"
      >
        <template v-if="isMultiple && selectedOptions.length">
          <div
            v-for="opt in displayedTags"
            :key="resolveValue(opt)"
            class="bookando-multiselect-tag"
            data-role="tag"
          >
            <span
              v-if="shouldShowFlag(opt)"
              class="flag"
            >{{ opt.flag }}</span>
            <span>{{ renderLabel(opt) }}</span>
            <button
              class="remove-tag"
              :disabled="disabled"
              @click.stop="removeOption(opt)"
            >
              ×
            </button>
          </div>
          <div
            v-if="overflowCount > 0"
            class="bookando-multiselect-tag bookando-multiselect-tag--counter"
            data-role="counter"
          >
            +{{ overflowCount }}
          </div>
          <div
            ref="counterProbe"
            class="bookando-multiselect-tag bookando-multiselect-tag--counter bookando-tag-measure"
            aria-hidden="true"
          >
            +99
          </div>
        </template>

        <template v-else-if="!isMultiple && selectedOption">
          <span
            v-if="shouldShowFlag(selectedOption)"
            class="flag"
          >{{ selectedOption.flag }}</span>
          <span class="option-label">{{ renderLabel(selectedOption) }}</span>
        </template>

        <template v-else>
          <span class="bookando-text-muted">{{ placeholder || t('ui.common.select') }}</span>
        </template>
      </div>

      <!-- Rechts: Clear/Chevron -->
      <template v-if="!isMultiple">
        <AppIcon
          v-if="clearable && selectedOption"
          name="x"
          class="bookando-icon--clear"
          tabindex="0"
          role="button"
          :aria-label="t('ui.common.clear_field')"
          @click.stop="clearSelection"
          @keydown.enter.stop="clearSelection"
          @keydown.space.stop="clearSelection"
        />
        <AppIcon
          v-else
          name="chevron-down"
          class="bookando-icon--select"
          aria-hidden="true"
        />
      </template>
      <template v-else>
        <AppIcon
          name="chevron-down"
          class="bookando-icon--select"
          aria-hidden="true"
        />
      </template>
    </div>

    <!-- Panel: Teleport -->
    <Teleport
      v-if="dropdownOpen && isTeleported"
      to="body"
    >
      <div
        :id="dropdownId"
        ref="dropdownPanel"
        class="bookando-combobox-list"
        data-teleported="true"
        :class="{ dropup: showDropup }"
        role="listbox"
        :style="panelStyle"
      >
        <!-- Suchfeld mit Icon (analog AppSearch) -->
        <div
          v-if="searchable"
          class="bookando-search-wrapper bookando-dropdown-search-wrapper"
        >
          <span class="bookando-input-icon">
            <AppIcon
              name="search"
              size="sm"
            />
          </span>
          <input
            ref="searchInput"
            v-model="search"
            type="text"
            class="bookando-search-input"
            :placeholder="t('ui.search.placeholder') || 'Suchen...'"
            :disabled="disabled"
            @keydown.down.prevent="highlight(+1)"
            @keydown.up.prevent="highlight(-1)"
            @keydown.enter.prevent="enterActive"
            @keydown.esc="closeDropdown"
          >
        </div>

        <!-- Optionen -->
        <div
          v-for="(opt, idx) in listOptions"
          :id="`${dropdownId}-option-${idx}`"
          :key="resolveValue(opt)"
          class="dropdown-option"
          :class="{ 'is-active': searchable && highlightedIndex === idx, 'bookando-text-muted': opt.disabled }"
          role="option"
          :aria-selected="isSelected(opt) ? 'true' : 'false'"
          @click="!opt.disabled && toggleOption(opt)"
          @mouseover="searchable && (highlightedIndex = idx)"
        >
          <!-- Checkbox nur bei multiple -->
          <AppCheckbox
            v-if="isMultiple"
            class="dropdown-checkbox"
            size="sm"
            align="left"
            :model-value="isSelected(opt)"
            :disabled="disabled || opt.disabled"
            @update:model-value="() => toggleOption(opt)"
            @click.stop
          />

          <span
            v-if="shouldShowFlag(opt)"
            class="flag"
          >{{ opt.flag }}</span>
          <span class="option-label">
            {{ renderLabel(opt) }}
            <span
              v-if="opt.code"
              class="country-code"
            >({{ opt.code }})</span>
          </span>
          <span
            v-if="showDial(opt)"
            class="dial"
          >{{ opt.dial_code }}</span>
        </div>
      </div>
    </Teleport>

    <!-- Panel: Fallback ohne Teleport -->
    <div
      v-else
      v-show="dropdownOpen"
      :id="dropdownId"
      ref="dropdownPanel"
      class="bookando-combobox-list"
      data-teleported="false"
      :class="{ dropup: showDropup }"
      role="listbox"
      :style="{ zIndex: String(zIndex) }"
    >
      <!-- Suchfeld mit Icon (analog AppSearch) -->
      <div
        v-if="searchable"
        class="bookando-search-wrapper bookando-dropdown-search-wrapper"
      >
        <span class="bookando-input-icon">
          <AppIcon
            name="search"
            size="sm"
          />
        </span>
        <input
          ref="searchInput"
          v-model="search"
          type="text"
          class="bookando-search-input"
          :placeholder="t('ui.search.placeholder') || 'Suchen...'"
          :disabled="disabled"
          @keydown.down.prevent="highlight(+1)"
          @keydown.up.prevent="highlight(-1)"
          @keydown.enter.prevent="enterActive"
          @keydown.esc="closeDropdown"
        >
      </div>

      <!-- Optionen -->
      <div
        v-for="(opt, idx) in listOptions"
        :id="`${dropdownId}-option-${idx}`"
        :key="resolveValue(opt)"
        class="dropdown-option"
        :class="{ 'is-active': searchable && highlightedIndex === idx, 'bookando-text-muted': opt.disabled }"
        role="option"
        :aria-selected="isSelected(opt) ? 'true' : 'false'"
        @click="!opt.disabled && toggleOption(opt)"
        @mouseover="searchable && (highlightedIndex = idx)"
      >
        <AppCheckbox
          v-if="isMultiple"
          class="dropdown-checkbox"
          size="sm"
          align="left"
          :model-value="isSelected(opt)"
          :disabled="disabled || opt.disabled"
          @update:model-value="() => toggleOption(opt)"
          @click.stop
        />
        <span
          v-if="shouldShowFlag(opt)"
          class="flag"
        >{{ opt.flag }}</span>
        <span class="option-label">
          {{ renderLabel(opt) }}
          <span
            v-if="opt.code"
            class="country-code"
          >({{ opt.code }})</span>
        </span>
        <span
          v-if="showDial(opt)"
          class="dial"
        >{{ opt.dial_code }}</span>
      </div>
    </div>

    <!-- Hint/Error -->
    <div
      v-if="hint"
      class="bookando-text-muted bookando-mt-xs bookando-text-sm"
    >
      <slot name="hint">
        {{ hint }}
      </slot>
    </div>
    <div
      v-if="error"
      class="form-error"
    >
      <slot name="error">
        {{ error }}
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { computePosition, autoUpdate, flip, shift, offset as offsetMw } from '@floating-ui/dom'

import { useI18n } from 'vue-i18n'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppCheckbox from '@core/Design/components/AppCheckbox.vue'

const { t, locale } = useI18n()

let cleanup: (() => void) | null = null

interface OptionType {
  [key: string]: any;
  label?: string;
  value?: string | number;
  flag?: string;
  code?: string;
  dial_code?: string;
  disabled?: boolean;
}

const props = withDefaults(defineProps<{
  modelValue: string | number | Array<string | number> | null;
  options?: OptionType[];
  optionLabel?: string | ((opt: OptionType) => string);
  optionValue?: string;
  placeholder?: string;
  label?: string;

  iconLeft?: string;
  iconRight?: string;
  iconSize?: string;
  iconColor?: string;

  disabled?: boolean;
  readonly?: boolean;
  required?: boolean;

  multiple?: boolean;
  showFlag?: boolean;
  mode?: string;

  id?: string;
  error?: string;
  hint?: string;

  /** erzwingt Richtung; sonst Auto */
  dropup?: boolean;

  /** Panel teleportieren */
  teleport?: boolean;

  /** z-index für Panel */
  zIndex?: number | string;

  /** Abstand Trigger ↔ Panel */
  offset?: number;

  /** Panel-Breite = Trigger-Breite */
  matchTriggerWidth?: boolean;

  /** optionale Klassen auf dem Button */
  btnClass?: string | string[] | Record<string, boolean>;

  /** Sortierung */
  sort?: 'asc' | 'desc' | false;

  /** Suchleiste + Keyboard im Panel */
  searchable?: boolean;

  /** Einzel-Auswahl per X löschen */
  clearable?: boolean;

  /** Breite */
  width?: 'full' | 'content' | 'trigger' | number | string
  panelMinWidth?: number
  panelMaxWidth?: string
}>(), {
  teleport: true,
  zIndex: 10020,
  offset: 2,
  panelMinWidth: 220,
  panelMaxWidth: 'calc(100vw - 24px)',
  matchTriggerWidth: true,
  sort: 'asc',
  searchable: false,
  clearable: false,
  width: 'full'
})

const emit = defineEmits(['update:modelValue'])

const dropdownId = props.id
  ? `${props.id}-dropdown`
  : `dropdown-${Math.random().toString(36).substr(2, 6)}`

const wrapper = ref<HTMLElement | null>(null)
const triggerEl = ref<HTMLElement | null>(null)
const dropdownPanel = ref<HTMLElement | null>(null)
const dropdownOpen = ref(false)
const dropupAuto = ref(false)
const isTeleported = computed(() => props.teleport !== false)

const contentEl = ref<HTMLElement | null>(null)
const counterProbe = ref<HTMLElement | null>(null)

const isMultiple = computed(() => !!props.multiple)

/** Klassen + Inline-Style für den Trigger-Button */
const widthClass = computed(() => ({
  'is-width-full':     props.width === 'full',
  'is-width-content':  props.width === 'content',
  'is-width-trigger':  props.width === 'trigger'
}))

const triggerStyle = computed<Record<string, string>>(() => {
  const w = props.width
  const style: Record<string, string> = { maxWidth: '100%' }
  if (typeof w === 'number') { style.width = `${Math.max(0, Math.round(w))}px`; style.display = 'inline-flex'; return style }
  if (typeof w === 'string') {
    if (w === 'full')    { style.width = '100%'; style.display = 'flex'; return style }
    if (w === 'content') { style.width = 'auto'; style.display = 'inline-flex'; return style }
    if (w === 'trigger') { style.width = 'auto'; style.display = 'inline-flex'; return style }
    style.width = w; style.display = 'inline-flex'; return style
  }
  style.width = '100%'; style.display = 'flex'; return style
})

/* ---------- Suche ---------- */
const search = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
const highlightedIndex = ref(-1)

/* ---------- Sortierung + Filter ---------- */
function resolveValue(opt: any) {
  if (typeof opt !== 'object' || !opt) return opt
  const key = props.optionValue ?? 'value'
  return opt[key] ?? opt.code ?? opt.id ?? opt.label ?? String(opt)
}
function resolveLabel(opt: any) {
  return typeof props.optionLabel === 'function'
    ? (props.optionLabel as any)(opt)
    : (opt?.[props.optionLabel ?? 'label'] ?? opt?.name ?? opt?.label ?? String(opt))
}
const sortedOptions = computed(() => {
  const base = (props.options ?? []).filter(Boolean).slice()
  if (props.sort === false) return base
  const dir = props.sort === 'desc' ? -1 : 1
  const collator = new Intl.Collator(locale.value, { sensitivity: 'base', numeric: true })
  return base.sort((a, b) => collator.compare(String(resolveLabel(a) ?? ''), String(resolveLabel(b) ?? '')) * dir)
})
const filteredOptions = computed(() => {
  if (!props.searchable) return sortedOptions.value
  const q = search.value.toLowerCase()
  return sortedOptions.value.filter(opt => {
    const label = String(resolveLabel(opt) ?? '').toLowerCase()
    const dial  = (opt && typeof opt === 'object' && opt.dial_code) ? String(opt.dial_code) : ''
    return label.includes(q) || dial.includes(q)
  })
})
const listOptions = computed(() => filteredOptions.value)

/* ---------- Flag/Dial ---------- */
function shouldShowFlag(opt: any): boolean {
  const mode = typeof props.mode === 'string' ? props.mode : ''
  return !!opt && typeof opt === 'object' && (props.showFlag || mode.includes('flag')) && !!opt.flag
}
function showDial(opt: any) { return props.mode === 'flag-label-dial' && opt?.dial_code }
function renderLabel(opt: any) { return resolveLabel(opt) }

/* ---------- Selection ---------- */
const selectedOptions = computed(() => {
  if (!isMultiple.value) return []
  const vals = (props.modelValue as Array<any>) || []
  return vals.map(v => (props.options || []).find(o => resolveValue(o) === v)).filter(Boolean) as any[]
})
const selectedOption = computed(() =>
  !isMultiple.value ? (props.options || []).find(o => resolveValue(o) === props.modelValue) : null
)
function isSelected(opt: any) {
  const val = resolveValue(opt)
  return isMultiple.value ? ((props.modelValue as Array<any>) || []).includes(val) : val === props.modelValue
}
function toggleOption(opt: any) {
  if (props.disabled || props.readonly || opt.disabled) return
  const val = resolveValue(opt)
  if (isMultiple.value) {
    const current = [ ...((props.modelValue as Array<any>) || []) ]
    emit('update:modelValue', current.includes(val) ? current.filter(v => v !== val) : [...current, val])
  } else {
    emit('update:modelValue', val)
    closeDropdown()
  }
}
function removeOption(opt: any) {
  if (props.disabled || props.readonly) return
  const val = resolveValue(opt)
  const current = (props.modelValue as Array<any>) || []
  emit('update:modelValue', current.filter(v => v !== val))
}
function clearSelection() {
  if (!isMultiple.value) {
    emit('update:modelValue', null)
    closeDropdown()
  }
}

/* ---------- Tag-Collapse ---------- */
const visibleCount = ref<number>(Infinity)
const overflowCount = computed(() =>
  isMultiple.value ? Math.max(0, selectedOptions.value.length - Math.min(selectedOptions.value.length, visibleCount.value)) : 0
)
const displayedTags = computed(() =>
  isMultiple.value ? selectedOptions.value.slice(0, Math.min(selectedOptions.value.length, visibleCount.value)) : []
)
function measureCounterWidth(n: number): number {
  const el = counterProbe.value
  if (!el) return 0
  const prev = el.textContent
  el.textContent = `+${n}`
  const w = Math.ceil(el.getBoundingClientRect().width)
  el.textContent = prev
  return w
}
function recomputeTagOverflow() {
  if (!isMultiple.value) return
  visibleCount.value = 9999
  nextTick(() => {
    const el = contentEl.value
    if (!el) return
    const total = Math.floor(el.clientWidth)
    const style = getComputedStyle(el)
    const gap = parseFloat(style.columnGap || style.gap || '0') || 0
    const tagEls = Array.from(el.querySelectorAll<HTMLElement>('.bookando-multiselect-tag[data-role="tag"]'))
    if (!tagEls.length) { visibleCount.value = 0; return }
    let used = 0, count = 0
    for (const tag of tagEls) {
      const w = Math.ceil(tag.getBoundingClientRect().width)
      const extra = count > 0 ? gap : 0
      if (used + extra + w <= total) { used += extra + w; count++ } else { break }
    }
    const totalTags = tagEls.length
    if (count < totalTags) {
      const counterW = measureCounterWidth(totalTags - count)
      const need = (count > 0 ? gap : 0) + counterW
      while (count > 0 && used + need > total) {
        const lastW = Math.ceil(tagEls[count - 1].getBoundingClientRect().width)
        used -= (count > 1 ? gap : 0) + lastW
        count--
      }
    }
    visibleCount.value = Math.max(0, count)
  })
}

/* ---------- Öffnen/Schließen ---------- */
async function toggleDropdown() {
  if (props.disabled || props.readonly) return
  dropdownOpen.value = !dropdownOpen.value
  if (dropdownOpen.value) {
    await nextTick()
    if (isTeleported.value) { updatePosition() } else { measureDirection() }
    if (props.searchable) searchInput.value?.focus()
  }
}
watch(dropdownOpen, async open => {
  if (open && isTeleported.value) {
    await nextTick()
    cleanup = autoUpdate(triggerEl.value!, dropdownPanel.value!, updatePosition)
    updatePosition()
  } else {
    cleanup?.(); cleanup = null
  }
})
function closeDropdown() {
  dropdownOpen.value = false
  highlightedIndex.value = -1
  if (props.searchable) search.value = ''
}
const activeOptionId = computed<string | undefined>(() =>
  props.searchable && highlightedIndex.value >= 0 ? `${dropdownId}-option-${highlightedIndex.value}` : undefined
)

/* ---------- Richtung & Position ---------- */
const showDropup = computed(() => typeof props.dropup === 'boolean' ? props.dropup : dropupAuto.value)
const panelStyle = ref<Record<string, string>>({ zIndex: String(props.zIndex ?? 10020) })
function panelWidthFor(rect: DOMRect): string {
  if (props.matchTriggerWidth === true || ['full','content','trigger'].includes(String(props.width))) {
    return `${Math.round(rect.width)}px`
  }
  if (typeof props.width === 'number') return `${Math.max(0, Math.round(props.width))}px`
  if (typeof props.width === 'string')  return props.width
  return `${Math.round(rect.width)}px`
}
async function updatePosition() {
  if (!triggerEl.value || !dropdownPanel.value) return
  const placement = props.dropup === true ? 'top-start' : 'bottom-start'
  const rect = triggerEl.value.getBoundingClientRect()
  const { x, y } = await computePosition(triggerEl.value, dropdownPanel.value, {
    strategy: 'fixed',
    placement,
    middleware: [ offsetMw(Number(props.offset ?? 2)), flip(), shift({ padding: 8 }) ]
  })
  panelStyle.value = {
    position: 'fixed',
    left: `${Math.round(x)}px`,
    top: `${Math.round(y)}px`,
    width: panelWidthFor(rect),
    zIndex: String(props.zIndex ?? 10020),
    maxWidth: props.panelMaxWidth || ''
  }
}
function measureDirection() {
  if (!wrapper.value) return
  const panelH = dropdownPanel.value?.offsetHeight || 240
  const rect = wrapper.value.getBoundingClientRect()
  const spaceBelow = Math.max(0, window.innerHeight - rect.bottom)
  const spaceAbove = Math.max(0, rect.top)
  dropupAuto.value = spaceBelow < Math.min(panelH, 240) && spaceAbove > spaceBelow
}

/* ---------- Keyboard ---------- */
function focusSearch() { if (dropdownOpen.value && props.searchable) nextTick(() => searchInput.value?.focus()) }
function highlight(dir: number) {
  if (!dropdownOpen.value || !props.searchable) return
  let idx = highlightedIndex.value + dir
  idx = Math.max(0, Math.min(listOptions.value.length - 1, idx))
  highlightedIndex.value = idx
}
function enterActive() {
  if (!props.searchable) return
  if (highlightedIndex.value >= 0 && highlightedIndex.value < listOptions.value.length) {
    toggleOption(listOptions.value[highlightedIndex.value])
  }
}

/* ---------- Icons ---------- */
const showLeftIcon  = computed(() => !!props.iconLeft)
const showRightIcon = computed(() => !!props.iconRight)
const iconClass = 'bookando-icon'
const iconSize  = computed(() => props.iconSize ?? 'md')
const iconColor = computed(() => props.iconColor ?? undefined)

/* ---------- Outside-Click ---------- */
function isInside(target: Node | null) {
  return !!((target && wrapper.value?.contains(target)) || (target && dropdownPanel.value?.contains(target)))
}
function onGlobalPointerDown(e: PointerEvent) { if (dropdownOpen.value && !isInside(e.target as Node)) closeDropdown() }
function onGlobalClick(e: MouseEvent)        { if (dropdownOpen.value && !isInside(e.target as Node)) closeDropdown() }
function onGlobalKeydown(e: KeyboardEvent)   { if (e.key === 'Tab') closeDropdown() }

/* ---------- Lifecycle ---------- */
let ro: ResizeObserver | null = null
watch(selectedOptions, () => recomputeTagOverflow(), { deep: true })
onMounted(() => {
  document.addEventListener('pointerdown', onGlobalPointerDown, { capture: true })
  document.addEventListener('click', onGlobalClick, { capture: true })
  document.addEventListener('keydown', onGlobalKeydown, { capture: true })
  if (typeof window !== 'undefined') window.addEventListener('resize', recomputeTagOverflow)
  if (contentEl.value && 'ResizeObserver' in window) {
    ro = new ResizeObserver(() => recomputeTagOverflow()); ro?.observe(contentEl.value)
  }
  nextTick(recomputeTagOverflow)
})
onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onGlobalPointerDown, { capture: true } as any)
  document.removeEventListener('click', onGlobalClick, { capture: true } as any)
  document.removeEventListener('keydown', onGlobalKeydown, { capture: true } as any)
  cleanup?.()
  if (typeof window !== 'undefined') window.removeEventListener('resize', recomputeTagOverflow)
  ro?.disconnect()
})
</script>
