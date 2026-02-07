<script setup lang="ts">
/**
 * RevenueChart -- Revenue area chart widget
 *
 * Displays a revenue analytics area chart.
 * Currently renders a visual SVG placeholder matching the reference AreaChart style.
 * TODO: Replace with real charting library (e.g. ApexCharts / Chart.js)
 */
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

// Mock data matching reference
const chartData = [
  { name: 'Mon', revenue: 4000 },
  { name: 'Tue', revenue: 3000 },
  { name: 'Wed', revenue: 2000 },
  { name: 'Thu', revenue: 2780 },
  { name: 'Fri', revenue: 1890 },
  { name: 'Sat', revenue: 2390 },
  { name: 'Sun', revenue: 3490 },
];

// SVG chart dimensions
const width = 600;
const height = 200;
const padding = { top: 10, right: 10, bottom: 30, left: 50 };
const chartWidth = width - padding.left - padding.right;
const chartHeight = height - padding.top - padding.bottom;

const maxRevenue = Math.max(...chartData.map(d => d.revenue));

function getX(index: number): number {
  return padding.left + (index / (chartData.length - 1)) * chartWidth;
}

function getY(value: number): number {
  return padding.top + chartHeight - (value / maxRevenue) * chartHeight;
}

// Build SVG path for the area
const linePath = chartData.map((d, i) => `${i === 0 ? 'M' : 'L'}${getX(i)},${getY(d.revenue)}`).join(' ');
const areaPath = `${linePath} L${getX(chartData.length - 1)},${padding.top + chartHeight} L${getX(0)},${padding.top + chartHeight} Z`;

// Y-axis ticks
const yTicks = [0, 1000, 2000, 3000, 4000];
</script>

<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ t('dashboard.revenueAnalytics') }}</h3>
    <div class="flex-1 min-h-[200px]">
      <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-full" preserveAspectRatio="xMidYMid meet">
        <defs>
          <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stop-color="#0ea5e9" stop-opacity="0.1" />
            <stop offset="95%" stop-color="#0ea5e9" stop-opacity="0" />
          </linearGradient>
        </defs>

        <!-- Grid lines (horizontal) -->
        <line
          v-for="tick in yTicks"
          :key="tick"
          :x1="padding.left"
          :y1="getY(tick)"
          :x2="width - padding.right"
          :y2="getY(tick)"
          stroke="#e2e8f0"
          stroke-dasharray="3 3"
        />

        <!-- Y-axis labels -->
        <text
          v-for="tick in yTicks"
          :key="'label-' + tick"
          :x="padding.left - 8"
          :y="getY(tick) + 4"
          text-anchor="end"
          fill="#64748b"
          font-size="11"
        >
          CHF {{ tick.toLocaleString() }}
        </text>

        <!-- Area fill -->
        <path :d="areaPath" fill="url(#colorRevenue)" />

        <!-- Line -->
        <path :d="linePath" fill="none" stroke="#0ea5e9" stroke-width="2" />

        <!-- Data points -->
        <circle
          v-for="(d, i) in chartData"
          :key="'point-' + i"
          :cx="getX(i)"
          :cy="getY(d.revenue)"
          r="3"
          fill="#0ea5e9"
          class="opacity-0 hover:opacity-100 transition-opacity"
        />

        <!-- X-axis labels -->
        <text
          v-for="(d, i) in chartData"
          :key="'x-' + i"
          :x="getX(i)"
          :y="height - 5"
          text-anchor="middle"
          fill="#64748b"
          font-size="11"
        >
          {{ d.name }}
        </text>
      </svg>
    </div>
  </div>
</template>
