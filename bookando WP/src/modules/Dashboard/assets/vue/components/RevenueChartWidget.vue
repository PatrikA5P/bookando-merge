<template>
  <div class="h-full flex flex-col">
    <h3 class="text-base font-semibold text-slate-800 mb-4">{{ $t('dashboard.revenue_analytics') }}</h3>
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
  { name: 'Mon', revenue: 4000 },
  { name: 'Tue', revenue: 3000 },
  { name: 'Wed', revenue: 2000 },
  { name: 'Thu', revenue: 2780 },
  { name: 'Fri', revenue: 1890 },
  { name: 'Sat', revenue: 2390 },
  { name: 'Sun', revenue: 3490 }
]

onMounted(() => {
  if (!chartRef.value) return

  const width = chartRef.value.clientWidth
  const height = chartRef.value.clientHeight
  const padding = { top: 20, right: 20, bottom: 40, left: 50 }

  const chartWidth = width - padding.left - padding.right
  const chartHeight = height - padding.top - padding.bottom

  const maxRevenue = Math.max(...chartData.map(d => d.revenue))
  const minRevenue = 0

  // Create SVG content
  let svg = `<defs>
    <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
      <stop offset="5%" stop-color="#0ea5e9" stop-opacity="0.1"/>
      <stop offset="95%" stop-color="#0ea5e9" stop-opacity="0"/>
    </linearGradient>
  </defs>`

  // Grid lines
  const gridLines = 5
  for (let i = 0; i <= gridLines; i++) {
    const y = padding.top + (chartHeight / gridLines) * i
    svg += `<line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" stroke="#e2e8f0" stroke-dasharray="3 3" />`
    const value = Math.round(maxRevenue - (maxRevenue / gridLines) * i)
    svg += `<text x="${padding.left - 10}" y="${y + 4}" text-anchor="end" fill="#64748b" font-size="12">CHF ${value}</text>`
  }

  // X-axis labels and area path
  const points: string[] = []
  chartData.forEach((d, i) => {
    const x = padding.left + (chartWidth / (chartData.length - 1)) * i
    const y = padding.top + chartHeight - ((d.revenue - minRevenue) / (maxRevenue - minRevenue)) * chartHeight
    points.push(`${x},${y}`)

    // X-axis label
    svg += `<text x="${x}" y="${height - padding.bottom + 20}" text-anchor="middle" fill="#64748b" font-size="12">${d.name}</text>`
  })

  // Create area path
  const areaPath = `M${points[0]} L${points.join(' L')} L${padding.left + chartWidth},${padding.top + chartHeight} L${padding.left},${padding.top + chartHeight} Z`
  svg += `<path d="${areaPath}" fill="url(#colorRevenue)" />`

  // Create line path
  const linePath = `M${points.join(' L')}`
  svg += `<path d="${linePath}" stroke="#0ea5e9" stroke-width="2" fill="none" />`

  chartRef.value.innerHTML = svg
})
</script>
