<template>
  <div class="notifications-tab">
    <!-- Sub-tabs -->
    <div class="sub-tabs">
      <button
        :class="['sub-tab', { active: activeSubTab === 'notifications' }]"
        @click="activeSubTab = 'notifications'"
      >
        <span class="dashicons dashicons-email-alt" />
        Benachrichtigungen
      </button>
      <button
        :class="['sub-tab', { active: activeSubTab === 'logs' }]"
        @click="activeSubTab = 'logs'"
      >
        <span class="dashicons dashicons-list-view" />
        {{ t('mod.tools.notifications.viewLogs') }}
      </button>
    </div>

    <!-- Notifications List -->
    <div
      v-if="activeSubTab === 'notifications'"
      class="notifications-section"
    >
      <div class="tab-header">
        <div class="search-bar">
          <span class="dashicons dashicons-search" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('mod.tools.search')"
            class="search-input"
          >
        </div>
        <button
          class="btn btn-primary"
          @click="openNotificationModal()"
        >
          <span class="dashicons dashicons-plus-alt" />
          {{ t('mod.tools.notifications.addNotification') }}
        </button>
      </div>

      <div class="notifications-grid">
        <div
          v-for="notification in filteredNotifications"
          :key="notification.id"
          class="notification-card"
        >
          <div class="notification-header">
            <div class="notification-title">
              <span :class="['channel-icon', `channel-${notification.channel}`]">
                <span
                  v-if="notification.channel === 'email'"
                  class="dashicons dashicons-email"
                />
                <span
                  v-else-if="notification.channel === 'sms'"
                  class="dashicons dashicons-phone"
                />
                <span
                  v-else-if="notification.channel === 'whatsapp'"
                  class="dashicons dashicons-whatsapp"
                />
                <span
                  v-else
                  class="dashicons dashicons-bell"
                />
              </span>
              <h3>{{ notification.name }}</h3>
            </div>
            <span :class="['status-badge', notification.is_active ? 'active' : 'inactive']">
              {{ notification.is_active ? 'Aktiv' : 'Inaktiv' }}
            </span>
          </div>

          <div class="notification-details">
            <div class="detail-item">
              <strong>Auslöser:</strong>
              {{ t(`mod.tools.notifications.triggers.${notification.event_trigger}`) }}
            </div>
            <div class="detail-item">
              <strong>Kanal:</strong>
              {{ t(`mod.tools.notifications.channels.${notification.channel}`) }}
            </div>
            <div class="detail-item">
              <strong>Empfänger:</strong>
              {{ t(`mod.tools.notifications.recipients.${notification.recipient_type}`) }}
            </div>
            <div
              v-if="notification.send_delay > 0"
              class="detail-item"
            >
              <strong>Verzögerung:</strong>
              {{ notification.send_delay }} Minuten
            </div>
          </div>

          <div class="notification-actions">
            <button
              class="btn btn-secondary btn-sm"
              @click="testNotification(notification)"
            >
              <span class="dashicons dashicons-email-alt2" />
              {{ t('mod.tools.notifications.testNotification') }}
            </button>
            <button
              class="btn btn-secondary btn-sm"
              @click="openNotificationModal(notification)"
            >
              <span class="dashicons dashicons-edit" />
              {{ t('mod.tools.edit') }}
            </button>
            <button
              class="btn-icon btn-danger"
              @click="deleteNotification(notification.id)"
            >
              <span class="dashicons dashicons-trash" />
            </button>
          </div>
        </div>

        <div
          v-if="filteredNotifications.length === 0"
          class="no-data-card"
        >
          <span class="dashicons dashicons-bell" />
          <p>{{ t('mod.tools.noData') }}</p>
        </div>
      </div>
    </div>

    <!-- Logs Section -->
    <div
      v-else-if="activeSubTab === 'logs'"
      class="logs-section"
    >
      <div class="logs-filters">
        <select
          v-model="logFilter.channel"
          class="filter-select"
        >
          <option value="">
            Alle Kanäle
          </option>
          <option value="email">
            {{ t('mod.tools.notifications.channels.email') }}
          </option>
          <option value="sms">
            {{ t('mod.tools.notifications.channels.sms') }}
          </option>
          <option value="whatsapp">
            {{ t('mod.tools.notifications.channels.whatsapp') }}
          </option>
        </select>
        <select
          v-model="logFilter.status"
          class="filter-select"
        >
          <option value="">
            Alle Status
          </option>
          <option value="sent">
            {{ t('mod.tools.notifications.logs.statuses.sent') }}
          </option>
          <option value="delivered">
            {{ t('mod.tools.notifications.logs.statuses.delivered') }}
          </option>
          <option value="failed">
            {{ t('mod.tools.notifications.logs.statuses.failed') }}
          </option>
          <option value="pending">
            {{ t('mod.tools.notifications.logs.statuses.pending') }}
          </option>
        </select>
        <input
          v-model="logFilter.date"
          type="date"
          class="filter-date"
        >
      </div>

      <div class="table-container">
        <table class="logs-table">
          <thead>
            <tr>
              <th>{{ t('mod.tools.notifications.logs.sentAt') }}</th>
              <th>Benachrichtigung</th>
              <th>{{ t('mod.tools.notifications.logs.recipient') }}</th>
              <th>Kanal</th>
              <th>{{ t('mod.tools.notifications.logs.status') }}</th>
              <th>{{ t('mod.tools.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in filteredLogs"
              :key="log.id"
            >
              <td>{{ formatDate(log.sent_at) }}</td>
              <td>{{ log.notification_name }}</td>
              <td>{{ log.recipient }}</td>
              <td>
                <span :class="['channel-badge', `channel-${log.channel}`]">
                  {{ t(`mod.tools.notifications.channels.${log.channel}`) }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', `status-${log.status}`]">
                  {{ t(`mod.tools.notifications.logs.statuses.${log.status}`) }}
                </span>
              </td>
              <td>
                <button
                  class="btn-icon"
                  :title="'Details'"
                  @click="viewLogDetails(log)"
                >
                  <span class="dashicons dashicons-visibility" />
                </button>
              </td>
            </tr>
            <tr v-if="filteredLogs.length === 0">
              <td
                colspan="6"
                class="no-data"
              >
                {{ t('mod.tools.noData') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Notification Modal -->
    <div
      v-if="showModal"
      class="modal-overlay"
      @click.self="closeModal"
    >
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h2>{{ editingNotification ? t('mod.tools.notifications.editNotification') : t('mod.tools.notifications.addNotification') }}</h2>
          <button
            class="btn-close"
            @click="closeModal"
          >
            <span class="dashicons dashicons-no-alt" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group full-width">
              <label>{{ t('mod.tools.notifications.name') }} *</label>
              <input
                v-model="formData.name"
                type="text"
                class="form-control"
              >
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.notifications.eventTrigger') }} *</label>
              <select
                v-model="formData.event_trigger"
                class="form-control"
              >
                <option value="bookingCreated">
                  {{ t('mod.tools.notifications.triggers.bookingCreated') }}
                </option>
                <option value="bookingConfirmed">
                  {{ t('mod.tools.notifications.triggers.bookingConfirmed') }}
                </option>
                <option value="bookingCancelled">
                  {{ t('mod.tools.notifications.triggers.bookingCancelled') }}
                </option>
                <option value="bookingReminder">
                  {{ t('mod.tools.notifications.triggers.bookingReminder') }}
                </option>
                <option value="customerRegistered">
                  {{ t('mod.tools.notifications.triggers.customerRegistered') }}
                </option>
                <option value="paymentReceived">
                  {{ t('mod.tools.notifications.triggers.paymentReceived') }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.notifications.channel') }} *</label>
              <select
                v-model="formData.channel"
                class="form-control"
              >
                <option value="email">
                  {{ t('mod.tools.notifications.channels.email') }}
                </option>
                <option value="sms">
                  {{ t('mod.tools.notifications.channels.sms') }}
                </option>
                <option value="whatsapp">
                  {{ t('mod.tools.notifications.channels.whatsapp') }}
                </option>
                <option value="push">
                  {{ t('mod.tools.notifications.channels.push') }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.notifications.recipientType') }} *</label>
              <select
                v-model="formData.recipient_type"
                class="form-control"
              >
                <option value="customer">
                  {{ t('mod.tools.notifications.recipients.customer') }}
                </option>
                <option value="employee">
                  {{ t('mod.tools.notifications.recipients.employee') }}
                </option>
                <option value="admin">
                  {{ t('mod.tools.notifications.recipients.admin') }}
                </option>
                <option value="custom">
                  {{ t('mod.tools.notifications.recipients.custom') }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.notifications.sendDelay') }}</label>
              <input
                v-model="formData.send_delay"
                type="number"
                class="form-control"
                min="0"
              >
              <small>0 = Sofort versenden</small>
            </div>

            <div
              v-if="formData.channel === 'email'"
              class="form-group full-width"
            >
              <label>{{ t('mod.tools.notifications.subject') }} *</label>
              <input
                v-model="formData.subject"
                type="text"
                class="form-control"
              >
            </div>

            <div class="form-group full-width">
              <label>{{ t('mod.tools.notifications.message') }} *</label>
              <textarea
                v-model="formData.message"
                class="form-control"
                rows="6"
              />
            </div>

            <div class="form-group full-width">
              <div class="variables-help">
                <strong>{{ t('mod.tools.notifications.templateVariables') }}:</strong>
                <div class="variables-list">
                  <code>{{ customer_name }}</code>
                  <code>{{ service_name }}</code>
                  <code>{{ booking_date }}</code>
                  <code>{{ booking_time }}</code>
                  <code>{{ employee_name }}</code>
                  <code>{{ price }}</code>
                  <code>{{ location }}</code>
                </div>
              </div>
            </div>

            <div class="form-group checkbox-group">
              <label>
                <input
                  v-model="formData.is_active"
                  type="checkbox"
                >
                {{ t('mod.tools.customFields.active') }}
              </label>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button
            class="btn btn-secondary"
            @click="closeModal"
          >
            {{ t('mod.tools.cancel') }}
          </button>
          <button
            class="btn btn-primary"
            @click="saveNotification"
          >
            {{ t('mod.tools.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Log Details Modal -->
    <div
      v-if="showLogModal"
      class="modal-overlay"
      @click.self="closeLogModal"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h2>Log-Details</h2>
          <button
            class="btn-close"
            @click="closeLogModal"
          >
            <span class="dashicons dashicons-no-alt" />
          </button>
        </div>

        <div class="modal-body">
          <div
            v-if="selectedLog"
            class="log-details"
          >
            <div class="detail-row">
              <strong>Gesendet am:</strong>
              {{ formatDate(selectedLog.sent_at) }}
            </div>
            <div class="detail-row">
              <strong>Empfänger:</strong>
              {{ selectedLog.recipient }}
            </div>
            <div class="detail-row">
              <strong>Kanal:</strong>
              {{ t(`mod.tools.notifications.channels.${selectedLog.channel}`) }}
            </div>
            <div class="detail-row">
              <strong>Status:</strong>
              <span :class="['status-badge', `status-${selectedLog.status}`]">
                {{ t(`mod.tools.notifications.logs.statuses.${selectedLog.status}`) }}
              </span>
            </div>
            <div
              v-if="selectedLog.error_message"
              class="detail-row"
            >
              <strong>Fehler:</strong>
              <div class="error-message">
                {{ selectedLog.error_message }}
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button
            class="btn btn-secondary"
            @click="closeLogModal"
          >
            Schließen
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Notification {
  id?: number
  name: string
  event_trigger: string
  channel: string
  recipient_type: string
  subject?: string
  message: string
  is_active: boolean
  send_delay: number
}

interface NotificationLog {
  id: number
  notification_name: string
  recipient: string
  channel: string
  status: string
  sent_at: string
  error_message?: string
}

const activeSubTab = ref('notifications')
const searchQuery = ref('')
const showModal = ref(false)
const showLogModal = ref(false)
const editingNotification = ref<Notification | null>(null)
const selectedLog = ref<NotificationLog | null>(null)
const notifications = ref<Notification[]>([])
const logs = ref<NotificationLog[]>([])

const logFilter = ref({
  channel: '',
  status: '',
  date: ''
})

const formData = ref<Notification>({
  name: '',
  event_trigger: 'bookingCreated',
  channel: 'email',
  recipient_type: 'customer',
  subject: '',
  message: '',
  is_active: true,
  send_delay: 0
})

const filteredNotifications = computed(() => {
  if (!searchQuery.value) return notifications.value
  const query = searchQuery.value.toLowerCase()
  return notifications.value.filter(
    notification =>
      notification.name.toLowerCase().includes(query)
  )
})

const filteredLogs = computed(() => {
  let result = logs.value

  if (logFilter.value.channel) {
    result = result.filter(log => log.channel === logFilter.value.channel)
  }

  if (logFilter.value.status) {
    result = result.filter(log => log.status === logFilter.value.status)
  }

  if (logFilter.value.date) {
    result = result.filter(log => log.sent_at.startsWith(logFilter.value.date))
  }

  return result
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('de-DE', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

const openNotificationModal = (notification?: Notification) => {
  if (notification) {
    editingNotification.value = notification
    formData.value = { ...notification }
  } else {
    editingNotification.value = null
    formData.value = {
      name: '',
      event_trigger: 'bookingCreated',
      channel: 'email',
      recipient_type: 'customer',
      subject: '',
      message: '',
      is_active: true,
      send_delay: 0
    }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingNotification.value = null
}

const saveNotification = async () => {
  // API call to save notification
  console.log('Saving notification:', formData.value)
  closeModal()
}

const deleteNotification = async (id?: number) => {
  if (confirm('Möchten Sie diese Benachrichtigung wirklich löschen?')) {
    // API call to delete notification
    console.log('Deleting notification:', id)
  }
}

const testNotification = async (notification: Notification) => {
  // API call to send test notification
  console.log('Sending test notification:', notification)
  alert('Test-Benachrichtigung wurde versendet!')
}

const viewLogDetails = (log: NotificationLog) => {
  selectedLog.value = log
  showLogModal.value = true
}

const closeLogModal = () => {
  showLogModal.value = false
  selectedLog.value = null
}

onMounted(() => {
  // Load notifications and logs
})
</script>

<style>
.notifications-tab {
  padding: 1rem;
}

.sub-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #e5e7eb;
}

.sub-tab {
  padding: 0.75rem 1.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9375rem;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}

.sub-tab:hover {
  color: #111827;
}

.sub-tab.active {
  color: #2271b1;
  border-bottom-color: #2271b1;
}

.tab-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.search-bar {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-bar .dashicons {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2.5rem;
  border: 1px solid #dcdcde;
  border-radius: 4px;
  font-size: 0.875rem;
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

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

.notifications-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.notification-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.notification-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
  gap: 1rem;
}

.notification-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.channel-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.channel-icon.channel-email {
  background: #dbeafe;
  color: #2563eb;
}

.channel-icon.channel-sms {
  background: #fef3c7;
  color: #d97706;
}

.channel-icon.channel-whatsapp {
  background: #dcfce7;
  color: #16a34a;
}

.channel-icon.channel-push {
  background: #f3e8ff;
  color: #9333ea;
}

.notification-title h3 {
  margin: 0;
  font-size: 1.125rem;
  color: #111827;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.status-sent {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.status-delivered {
  background: #dcfce7;
  color: #166534;
}

.status-badge.status-failed {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.notification-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.detail-item strong {
  color: #374151;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.btn-icon {
  padding: 0.375rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-icon:hover {
  background: #f3f4f6;
  color: #111827;
}

.btn-icon.btn-danger:hover {
  background: #fee2e2;
  color: #991b1b;
}

.no-data-card {
  grid-column: 1 / -1;
  background: #f9fafb;
  border: 2px dashed #e5e7eb;
  border-radius: 8px;
  padding: 3rem;
  text-align: center;
  color: #9ca3af;
}

.no-data-card .dashicons {
  font-size: 48px;
  margin-bottom: 1rem;
}

.no-data-card p {
  margin: 0;
  font-size: 0.875rem;
}

/* Logs Section */
.logs-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-select,
.filter-date {
  padding: 0.5rem 0.75rem;
  border: 1px solid #dcdcde;
  border-radius: 4px;
  font-size: 0.875rem;
}

.table-container {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.logs-table {
  width: 100%;
  border-collapse: collapse;
}

.logs-table th {
  background: #f9fafb;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.logs-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.875rem;
}

.logs-table tbody tr:hover {
  background: #f9fafb;
}

.channel-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.channel-badge.channel-email {
  background: #dbeafe;
  color: #1e40af;
}

.channel-badge.channel-sms {
  background: #fef3c7;
  color: #92400e;
}

.channel-badge.channel-whatsapp {
  background: #dcfce7;
  color: #166534;
}

.no-data {
  text-align: center;
  padding: 2rem !important;
  color: #9ca3af;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-large {
  max-width: 900px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #111827;
}

.btn-close {
  padding: 0.25rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
  border-radius: 4px;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #111827;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  margin-bottom: 0.25rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: #374151;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
}

.form-control:focus {
  outline: none;
  border-color: #2271b1;
  box-shadow: 0 0 0 1px #2271b1;
}

.checkbox-group label {
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox-group input[type="checkbox"] {
  margin: 0;
}

.variables-help {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 1rem;
}

.variables-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.variables-list code {
  background: white;
  padding: 0.25rem 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  font-size: 0.8125rem;
  font-family: monospace;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.log-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-row strong {
  color: #374151;
  font-size: 0.875rem;
}

.error-message {
  background: #fee2e2;
  color: #991b1b;
  padding: 0.75rem;
  border-radius: 4px;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .tab-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-bar {
    max-width: none;
  }

  .notifications-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .logs-filters {
    flex-direction: column;
  }

  .table-container {
    overflow-x: auto;
  }
}
</style>
