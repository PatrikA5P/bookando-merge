<!-- AppFilterBar.vue -->
<template>
  <div :class="['bookando-filter-bar', containerClass]">
    <!-- DESKTOP: 1 Row -->
    <div
      v-if="!stack"
      class="bookando-filter-bar-row"
      :style="rowStyleComputed"
    >
      <div class="bookando-filter-bar-left">
        <slot
          name="left"
          :stack="false"
        />
      </div>
      <div class="bookando-filter-bar-center">
        <slot
          name="center"
          :stack="false"
        />
      </div>
      <div class="bookando-filter-bar-right">
        <slot
          name="right"
          :stack="false"
        />
      </div>
    </div>

    <!-- MOBILE/TABLET: 2 Rows -->
    <div
      v-else
      class="bookando-filter-bar-stack"
    >
      <!-- Row 1: Suche + mobiler Filter -->
      <div
        class="bookando-filter-bar-row"
        :style="rowStyleComputed"
      >
        <div class="bookando-filter-bar-left">
          <slot
            name="left"
            :stack="true"
          />
        </div>
      </div>
      <!-- Row 2: Sort (links) + Import/Export (rechts) -->
      <div class="bookando-filter-bar-row">
        <div class="bookando-filter-bar-center">
          <slot
            name="center"
            :stack="true"
          />
        </div>
        <div class="bookando-filter-bar-right">
          <slot
            name="right"
            :stack="true"
          />
        </div>
      </div>
    </div>

    <slot name="below" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useResponsive } from '@core/Composables/useResponsive'
type BP = 'sm'|'md'|'lg'|'xl'

const props = withDefaults(defineProps<{
  ratio?: [number, number, number]
  ratioMobile?: [number, number]
  containerClass?: string | string[] | Record<string, boolean>
  /** Unterhalb dieses Breakpoints wird gestapelt */
  stackBelow?: BP
  /** optionale CSS Vars (z.B. Prozente/Max-Breiten) */
  layoutVars?: Record<string, string | number>
}>(), {
  ratio: () => [2, 1, 1],
  ratioMobile: () => [2, 1],
  containerClass: '',
  stackBelow: 'md'
})

const { isBelow } = useResponsive()
const stack = isBelow(props.stackBelow)

const rowStyle = computed(() => ({
  '--flex-bar-left': props.ratio[0],
  '--flex-bar-center': props.ratio[1],
  '--flex-bar-right': props.ratio[2],
  '--flex-bar-center-mobile': props.ratioMobile[0],
  '--flex-bar-right-mobile': props.ratioMobile[1],
}))
const rowStyleComputed = computed(() => ({ ...rowStyle.value, ...(props.layoutVars || {}) }))
</script>

<style scoped>
.bookando-filter-bar {
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 0.75rem;
  min-height: 64px;
  gap: clamp(0.3125rem, 0.15rem + 0.7vw, 0.8125rem);
  /* wichtig: Dropdowns nicht abschneiden */
  position: relative;
  overflow: visible;
}

.bookando-filter-bar-row {
  display: flex;
  flex-wrap: nowrap;
  align-items: stretch;
  min-height: 64px;
  gap: var(--fb-row-gap, clamp(0.3125rem, 0.15rem + 0.7vw, 0.8125rem));
}

.bookando-filter-bar-row > .bookando-filter-bar-left,
.bookando-filter-bar-row > .bookando-filter-bar-center,
.bookando-filter-bar-row > .bookando-filter-bar-right {
  display: flex;
  align-items: center;
  gap: var(--fb-inner-gap, clamp(0.3125rem, 0.15rem + 0.7vw, 0.8125rem));
  min-width: 0;
}

.bookando-filter-bar-row > .bookando-filter-bar-left {
  flex: 1 1 var(--fb-left-pct, 50%);
  max-width: var(--fb-left-max, none);
  justify-content: flex-start;
}
.bookando-filter-bar-row > .bookando-filter-bar-center {
  flex: 0 1 var(--fb-center-pct, 30%);
  max-width: var(--fb-center-max, none);
  justify-content: flex-start;
}
.bookando-filter-bar-row > .bookando-filter-bar-right {
  flex: 0 1 var(--fb-right-pct, 20%);
  max-width: var(--fb-right-max, none);
  justify-content: flex-end;
}

.bookando-filter-bar-stack {
  display: flex;
  flex-direction: column;
  gap: clamp(0.3125rem, 0.15rem + 0.7vw, 0.8125rem);
}
</style>
