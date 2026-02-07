<script setup lang="ts">
/**
 * AppointmentChart -- Weekly appointments bar chart widget
 *
 * Displays a bar chart of weekly appointments.
 * Renders an SVG bar chart placeholder matching the reference BarChart style.
 * TODO: Replace with real charting library (e.g. ApexCharts / Chart.js)
 */
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

// Mock data matching reference
const chartData = [
  { name: 'Mon', appointments: 24 },
  { name: 'Tue', appointments: 18 },
  { name: 'Wed', appointments: 32 },
  { name: 'Thu', appointments: 20 },
  { name: 'Fri', appointments: 28 },
  { name: 'Sat', appointments: 15 },
  { name: 'Sun', appointments: 22 },
];

// SVG chart dimensions
const width = 400;
const height = 200;
const padding = { top: 10, right: 10, bottom: 30, left: 10 };
const chartWidth = width - padding.left - padding.right;
const chartHeight = height - padding.top - padding.bottom;

const maxAppointments = Math.max(...chartData.map(d => d.appointments));
const barWidth = chartWidth / chartData.length * 0.6;
const barGap = chartWidth / chartData.length * 0.4;

function getBarX(index: number): number {
  const slotWidth = chartWidth / chartData.length;
  return padding.left + slotWidth * index + (slotWidth - barWidth) / 2;
}

function getBarHeight(value: number): number {
  return (value / maxAppointments) * chartHeight;
}

function getBarY(value: number): number {
  return padding.top + chartHeight - getBarHeight(value);
}

function getLabelX(index: number): number {
  const slotWidth = chartWidth / chartData.length;
  return padding.left + slotWidth * index + slotWidth / 2;
}
</script>

<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ t('dashboard.weeklyAppointments') }}</h3>
    <div class="flex-1 min-h-[200px]">
      <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-full" preserveAspectRatio="xMidYMid meet">
        <!-- Horizontal grid lines -->
        <line
          v-for="i in 4"
          :key="'grid-' + i"
          :x1="padding.left"
          :y1="padding.top + (chartHeight / 4) * (4 - i)"
          :x2="width - padding.right"
          :y2="padding.top + (chartHeight / 4) * (4 - i)"
          stroke="#e2e8f0"
          stroke-dasharray="3 3"
        />

        <!-- Bars -->
        <rect
          v-for="(d, i) in chartData"
          :key="'bar-' + i"
          :x="getBarX(i)"
          :y="getBarY(d.appointments)"
          :width="barWidth"
          :height="getBarHeight(d.appointments)"
          rx="4"
          ry="4"
          fill="#3b82f6"
          class="hover:fill-blue-400 transition-colors"
        />

        <!-- X-axis labels -->
        <text
          v-for="(d, i) in chartData"
          :key="'x-' + i"
          :x="getLabelX(i)"
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
