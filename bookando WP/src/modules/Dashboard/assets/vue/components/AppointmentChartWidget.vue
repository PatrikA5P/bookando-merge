<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ $t('dashboard.weekly_appointments') }}</h3>
    <div class="flex-1 min-h-[200px]">
      <svg ref="chartRef" class="w-full h-full"></svg>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()
const chartRef = ref<SVGElement>()

const chartData = [
  { name: 'Mon', appointments: 24 },
  { name: 'Tue', appointments: 18 },
  { name: 'Wed', appointments: 32 },
  { name: 'Thu', appointments: 20 },
  { name: 'Fri', appointments: 28 },
  { name: 'Sat', appointments: 15 },
  { name: 'Sun', appointments: 22 }
]

onMounted(() => {
  if (!chartRef.value) return

  const width = chartRef.value.clientWidth
  const height = chartRef.value.clientHeight
  const padding = { top: 20, right: 20, bottom: 40, left: 50 }

  const chartWidth = width - padding.left - padding.right
  const chartHeight = height - padding.top - padding.bottom

  const maxAppointments = Math.max(...chartData.map(d => d.appointments))
  const barWidth = 30
  const barGap = (chartWidth - barWidth * chartData.length) / (chartData.length + 1)

  // Create SVG content
  let svg = ''

  // Grid lines
  const gridLines = 4
  for (let i = 0; i <= gridLines; i++) {
    const y = padding.top + (chartHeight / gridLines) * i
    svg += `<line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" stroke="#e2e8f0" stroke-dasharray="3 3" />`
    const value = Math.round(maxAppointments - (maxAppointments / gridLines) * i)
    svg += `<text x="${padding.left - 10}" y="${y + 4}" text-anchor="end" fill="#64748b" font-size="12">${value}</text>`
  }

  // Bars and labels
  chartData.forEach((d, i) => {
    const x = padding.left + barGap + (barWidth + barGap) * i
    const barHeight = (d.appointments / maxAppointments) * chartHeight
    const y = padding.top + chartHeight - barHeight

    // Bar
    svg += `<rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" fill="#3b82f6" rx="4" />`

    // X-axis label
    svg += `<text x="${x + barWidth / 2}" y="${height - padding.bottom + 20}" text-anchor="middle" fill="#64748b" font-size="12">${d.name}</text>`
  })

  chartRef.value.innerHTML = svg
})
</script>
