<!-- CRMQuickStats.vue - Quick Statistics Grid Component -->
<template>
  <div class="crm-quick-stats" :class="statsClasses">
    <!-- Header -->
    <div v-if="showHeader" class="crm-quick-stats__header">
      <h3 v-if="title" class="crm-quick-stats__title">{{ title }}</h3>
      <slot name="headerActions" />
    </div>

    <!-- Stats Grid -->
    <div class="crm-quick-stats__grid" :style="gridStyle">
      <div
        v-for="stat in stats"
        :key="stat.id || stat.label"
        class="crm-stat-card"
        :class="getStatClasses(stat)"
        @click="handleStatClick(stat)"
      >
        <!-- Icon -->
        <div
          v-if="stat.icon"
          class="crm-stat-card__icon"
          :style="getIconStyle(stat)"
        >
          <AppIcon :name="stat.icon" />
        </div>

        <!-- Content -->
        <div class="crm-stat-card__content">
          <!-- Value -->
          <div class="crm-stat-card__value">
            <span v-if="stat.prefix" class="crm-stat-card__prefix">
              {{ stat.prefix }}
            </span>
            <AnimatedNumber
              v-if="animated"
              :value="stat.value"
              :duration="animationDuration"
              :format="stat.format"
            />
            <span v-else>{{ formatValue(stat.value, stat.format) }}</span>
            <span v-if="stat.suffix" class="crm-stat-card__suffix">
              {{ stat.suffix }}
            </span>
          </div>

          <!-- Label -->
          <div class="crm-stat-card__label">
            {{ stat.label }}
          </div>

          <!-- Trend Indicator -->
          <div
            v-if="stat.trend !== undefined"
            class="crm-stat-card__trend"
            :class="getTrendClasses(stat.trend)"
          >
            <AppIcon :name="getTrendIcon(stat.trend)" />
            <span>{{ Math.abs(stat.trend) }}%</span>
            <span v-if="stat.trendLabel" class="crm-stat-card__trend-label">
              {{ stat.trendLabel }}
            </span>
          </div>

          <!-- Comparison -->
          <div
            v-if="stat.comparison"
            class="crm-stat-card__comparison"
          >
            {{ stat.comparison }}
          </div>

          <!-- Progress Bar -->
          <div
            v-if="stat.progress !== undefined"
            class="crm-stat-card__progress"
          >
            <div
              class="crm-stat-card__progress-bar"
              :style="{ width: `${Math.min(100, Math.max(0, stat.progress))}%` }"
            />
          </div>
        </div>

        <!-- Badge -->
        <AppBadge
          v-if="stat.badge"
          :label="stat.badge.label"
          :variant="stat.badge.variant"
          size="sm"
          class="crm-stat-card__badge"
        />

        <!-- Custom Slot -->
        <div v-if="$slots[`stat-${stat.id}`]" class="crm-stat-card__custom">
          <slot :name="`stat-${stat.id}`" :stat="stat" />
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="!stats.length"
        class="crm-quick-stats__empty"
      >
        <AppIcon :name="emptyIcon" />
        <p>{{ emptyMessage }}</p>
      </div>
    </div>

    <!-- Footer -->
    <div v-if="$slots.footer || showFooter" class="crm-quick-stats__footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'
import AppBadge from './AppBadge.vue'
import AnimatedNumber from './AnimatedNumber.vue'

export interface StatBadge {
  label: string
  variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
}

export interface Stat {
  id?: string
  label: string
  value: number | string
  icon?: string
  iconColor?: string
  iconBg?: string
  prefix?: string
  suffix?: string
  format?: 'number' | 'currency' | 'percentage'
  trend?: number // Percentage change (positive or negative)
  trendLabel?: string
  comparison?: string
  progress?: number // 0-100
  badge?: StatBadge
  variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
  clickable?: boolean
  loading?: boolean
}

export interface CRMQuickStatsProps {
  stats: Stat[]
  title?: string
  columns?: number | 'auto'
  gap?: 'sm' | 'md' | 'lg'
  showHeader?: boolean
  showFooter?: boolean
  animated?: boolean
  animationDuration?: number
  emptyIcon?: string
  emptyMessage?: string
}

const props = withDefaults(defineProps<CRMQuickStatsProps>(), {
  title: '',
  columns: 'auto',
  gap: 'md',
  showHeader: false,
  showFooter: false,
  animated: true,
  animationDuration: 1000,
  emptyIcon: 'bar-chart',
  emptyMessage: 'No statistics available'
})

const emit = defineEmits<{
  (e: 'statClick', stat: Stat): void
}>()

// Computed
const statsClasses = computed(() => ({
  [`crm-quick-stats--gap-${props.gap}`]: true
}))

const gridStyle = computed(() => {
  if (props.columns === 'auto') {
    return {
      gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))'
    }
  }
  return {
    gridTemplateColumns: `repeat(${props.columns}, 1fr)`
  }
})

// Methods
function getStatClasses(stat: Stat) {
  return {
    [`crm-stat-card--${stat.variant}`]: stat.variant,
    'is-clickable': stat.clickable,
    'is-loading': stat.loading
  }
}

function getIconStyle(stat: Stat) {
  const style: Record<string, string> = {}

  if (stat.iconColor) {
    style.color = stat.iconColor
  }

  if (stat.iconBg) {
    style.backgroundColor = stat.iconBg
  }

  return style
}

function getTrendClasses(trend: number) {
  return {
    'is-positive': trend > 0,
    'is-negative': trend < 0,
    'is-neutral': trend === 0
  }
}

function getTrendIcon(trend: number): string {
  if (trend > 0) return 'trending-up'
  if (trend < 0) return 'trending-down'
  return 'minus'
}

function handleStatClick(stat: Stat) {
  if (stat.clickable) {
    emit('statClick', stat)
  }
}

function formatValue(value: number | string, format?: string): string {
  if (typeof value === 'string') return value

  switch (format) {
    case 'currency':
      return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'EUR'
      }).format(value)

    case 'percentage':
      return `${value}%`

    case 'number':
    default:
      return new Intl.NumberFormat().format(value)
  }
}
</script>

<style lang="scss" scoped>
// Component uses global styles from _crm-split-view.scss
// Add component-specific refinements here

.crm-quick-stats {
  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--bookando-spacing-lg);
  }

  &__title {
    font-size: var(--bookando-font-size-lg);
    font-weight: 600;
    margin: 0;
  }

  &__grid {
    display: grid;
    gap: var(--bookando-spacing-md);
  }

  &--gap-sm &__grid {
    gap: var(--bookando-spacing-sm);
  }

  &--gap-lg &__grid {
    gap: var(--bookando-spacing-lg);
  }

  &__empty {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--bookando-spacing-2xl);
    color: var(--bookando-text-muted);
    text-align: center;

    svg {
      width: 48px;
      height: 48px;
      margin-bottom: var(--bookando-spacing-md);
      opacity: 0.3;
    }
  }

  &__footer {
    margin-top: var(--bookando-spacing-lg);
    padding-top: var(--bookando-spacing-lg);
    border-top: 1px solid var(--bookando-border);
  }
}

.crm-stat-card {
  position: relative;

  &__badge {
    position: absolute;
    top: var(--bookando-spacing-sm);
    right: var(--bookando-spacing-sm);
  }

  &__trend {
    display: flex;
    align-items: center;
    gap: var(--bookando-spacing-xxs);
    font-size: var(--bookando-font-size-sm);
    font-weight: 500;
    margin-top: var(--bookando-spacing-xs);

    &.is-positive {
      color: var(--bookando-success);
    }

    &.is-negative {
      color: var(--bookando-danger);
    }

    &.is-neutral {
      color: var(--bookando-text-muted);
    }

    svg {
      width: 14px;
      height: 14px;
    }
  }

  &__trend-label {
    color: var(--bookando-text-muted);
    font-weight: 400;
    margin-left: var(--bookando-spacing-xxs);
  }

  &__comparison {
    font-size: var(--bookando-font-size-sm);
    color: var(--bookando-text-muted);
    margin-top: var(--bookando-spacing-xs);
  }

  &__progress {
    margin-top: var(--bookando-spacing-sm);
    height: 4px;
    background: var(--bookando-bg-soft);
    border-radius: 2px;
    overflow: hidden;
  }

  &__progress-bar {
    height: 100%;
    background: var(--bookando-primary);
    transition: width 0.6s ease;
    border-radius: 2px;
  }

  &.is-clickable {
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    &:active {
      transform: translateY(0);
    }
  }

  &.is-loading {
    opacity: 0.6;
    pointer-events: none;

    .crm-stat-card__value {
      &::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(
          90deg,
          transparent,
          rgba(255, 255, 255, 0.5),
          transparent
        );
        animation: loading 1.5s infinite;
      }
    }
  }

  // Variant styles
  &--primary {
    border-left: 3px solid var(--bookando-primary);
  }

  &--success {
    border-left: 3px solid var(--bookando-success);
  }

  &--warning {
    border-left: 3px solid var(--bookando-warning);
  }

  &--danger {
    border-left: 3px solid var(--bookando-danger);
  }

  &--info {
    border-left: 3px solid var(--bookando-info);
  }
}

@keyframes loading {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

// Responsive adjustments
@media (max-width: 768px) {
  .crm-quick-stats__grid {
    grid-template-columns: 1fr !important;
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  .crm-quick-stats__grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}
</style>
