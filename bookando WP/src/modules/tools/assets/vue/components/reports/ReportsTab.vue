<template>
  <div class="reports-tab">
    <!-- Header with date range and export -->
    <div class="reports-header">
      <div class="date-range-selector">
        <label>{{ t('mod.tools.reports.dateRange') }}:</label>
        <input
          v-model="startDate"
          type="date"
          class="date-input"
        >
        <span>-</span>
        <input
          v-model="endDate"
          type="date"
          class="date-input"
        >
        <button
          class="btn btn-primary"
          @click="loadReports"
        >
          {{ t('mod.tools.reports.overview') }}
        </button>
      </div>

      <div class="export-actions">
        <button
          class="btn btn-secondary"
          @click="exportReport('pdf')"
        >
          <span class="dashicons dashicons-media-document" />
          {{ t('mod.tools.reports.exportPDF') }}
        </button>
        <button
          class="btn btn-secondary"
          @click="exportReport('csv')"
        >
          <span class="dashicons dashicons-media-spreadsheet" />
          {{ t('mod.tools.reports.exportCSV') }}
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon bookings">
          <span class="dashicons dashicons-calendar-alt" />
        </div>
        <div class="stat-content">
          <div class="stat-value">
            {{ reportData.total_bookings }}
          </div>
          <div class="stat-label">
            {{ t('mod.tools.reports.totalBookings') }}
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon revenue">
          <span class="dashicons dashicons-chart-line" />
        </div>
        <div class="stat-content">
          <div class="stat-value">
            {{ formatCurrency(reportData.total_revenue) }}
          </div>
          <div class="stat-label">
            {{ t('mod.tools.reports.totalRevenue') }}
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon customers">
          <span class="dashicons dashicons-groups" />
        </div>
        <div class="stat-content">
          <div class="stat-value">
            {{ reportData.total_customers }}
          </div>
          <div class="stat-label">
            {{ t('mod.tools.reports.totalCustomers') }}
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon conversion">
          <span class="dashicons dashicons-thumbs-up" />
        </div>
        <div class="stat-content">
          <div class="stat-value">
            {{ reportData.conversion_rate }}%
          </div>
          <div class="stat-label">
            {{ t('mod.tools.reports.conversionRate') }}
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
      <div class="chart-card">
        <h3>{{ t('mod.tools.reports.bookingsOverTime') }}</h3>
        <div class="chart-placeholder">
          <span class="dashicons dashicons-chart-line" />
          <p>{{ t('mod.tools.reports.bookingsOverTime') }}</p>
        </div>
      </div>

      <div class="chart-card">
        <h3>{{ t('mod.tools.reports.revenueByService') }}</h3>
        <div class="chart-placeholder">
          <span class="dashicons dashicons-chart-pie" />
          <p>{{ t('mod.tools.reports.revenueByService') }}</p>
        </div>
      </div>

      <div class="chart-card">
        <h3>{{ t('mod.tools.reports.topServices') }}</h3>
        <div class="chart-placeholder">
          <span class="dashicons dashicons-chart-bar" />
          <p>{{ t('mod.tools.reports.topServices') }}</p>
        </div>
      </div>

      <div class="chart-card">
        <h3>{{ t('mod.tools.reports.customerGrowth') }}</h3>
        <div class="chart-placeholder">
          <span class="dashicons dashicons-chart-area" />
          <p>{{ t('mod.tools.reports.customerGrowth') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const startDate = ref('')
const endDate = ref('')
const reportData = ref({
  total_bookings: 0,
  total_revenue: 0,
  total_customers: 0,
  conversion_rate: 0
})

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR'
  }).format(value)
}

const loadReports = async () => {
  try {
    const vars = (window as any).BOOKANDO_VARS || {}
    const restUrl = vars.rest_url || '/wp-json/bookando/v1/tools'
    const nonce = vars.nonce || ''

    const response = await fetch(
      `${restUrl}/reports?start_date=${startDate.value}&end_date=${endDate.value}`,
      {
        headers: {
          'X-WP-Nonce': nonce
        }
      }
    )
    const data = await response.json()
    if (data.success && data.data) {
      reportData.value = data.data
    }
  } catch (error) {
    console.error('Error loading reports:', error)
  }
}

const exportReport = async (format: string) => {
  try {
    const vars = (window as any).BOOKANDO_VARS || {}
    const restUrl = vars.rest_url || '/wp-json/bookando/v1/tools'
    const nonce = vars.nonce || ''

    const response = await fetch(
      `${restUrl}/reports/export`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce
        },
        body: JSON.stringify({
          format,
          start_date: startDate.value,
          end_date: endDate.value
        })
      }
    )
    const data = await response.json()
    console.log('Export initiated:', data)
  } catch (error) {
    console.error('Error exporting report:', error)
  }
}

onMounted(() => {
  // Set default date range (last 30 days)
  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - 30)

  endDate.value = end.toISOString().split('T')[0]
  startDate.value = start.toISOString().split('T')[0]

  loadReports()
})
</script>

<style>
.reports-tab {
  padding: 1rem;
}

.reports-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.date-range-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-input {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.export-actions {
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.btn-primary {
  background: #2271b1;
  color: white;
}

.btn-primary:hover {
  background: #135e96;
}

.btn-secondary {
  background: #f6f7f7;
  color: #2c3338;
  border: 1px solid #dcdcde;
}

.btn-secondary:hover {
  background: #f0f0f1;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
}

.stat-icon.bookings {
  background: #dbeafe;
  color: #2563eb;
}

.stat-icon.revenue {
  background: #dcfce7;
  color: #16a34a;
}

.stat-icon.customers {
  background: #fef3c7;
  color: #d97706;
}

.stat-icon.conversion {
  background: #f3e8ff;
  color: #9333ea;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}

.chart-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-card h3 {
  margin: 0 0 1rem 0;
  font-size: 1.125rem;
  color: #111827;
}

.chart-placeholder {
  height: 300px;
  background: #f9fafb;
  border: 2px dashed #e5e7eb;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.chart-placeholder .dashicons {
  font-size: 48px;
  margin-bottom: 0.5rem;
}

.chart-placeholder p {
  margin: 0;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .reports-header {
    flex-direction: column;
    align-items: stretch;
  }

  .date-range-selector {
    flex-wrap: wrap;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .charts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
