<template>
  <div class="notifications-matrix-tab">
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
        Verlauf
      </button>
    </div>

    <!-- Notifications Matrix -->
    <div
      v-if="activeSubTab === 'notifications'"
      class="notifications-section"
    >
      <div class="tab-header">
        <h3>Benachrichtigungen konfigurieren</h3>
        <button
          class="btn btn-primary"
          @click="openMatrixModal()"
        >
          <span class="dashicons dashicons-plus-alt" />
          Neue Benachrichtigung
        </button>
      </div>

      <!-- Saved Notifications List -->
      <div class="saved-notifications">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="notification-item"
        >
          <div class="notification-summary">
            <div class="notification-name">
              <span class="dashicons dashicons-bell" />
              <strong>{{ notification.name }}</strong>
            </div>
            <div class="notification-count">
              {{ getVariantCount(notification) }} Konfiguration(en)
            </div>
          </div>
          <div class="notification-actions">
            <button
              class="btn btn-secondary btn-sm"
              @click="openMatrixModal(notification)"
            >
              <span class="dashicons dashicons-edit" />
              Bearbeiten
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
          v-if="notifications.length === 0"
          class="no-data-card"
        >
          <span class="dashicons dashicons-bell" />
          <p>Noch keine Benachrichtigungen konfiguriert</p>
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
            E-Mail
          </option>
          <option value="sms">
            SMS
          </option>
          <option value="whatsapp">
            WhatsApp
          </option>
          <option value="push">
            Push
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
            Gesendet
          </option>
          <option value="delivered">
            Zugestellt
          </option>
          <option value="failed">
            Fehlgeschlagen
          </option>
          <option value="pending">
            Ausstehend
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
              <th>Gesendet am</th>
              <th>Benachrichtigung</th>
              <th>Empfänger</th>
              <th>Kanal</th>
              <th>Status</th>
              <th>Aktionen</th>
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
                  {{ getChannelLabel(log.channel) }}
                </span>
              </td>
              <td>
                <span :class="['status-badge', `status-${log.status}`]">
                  {{ getStatusLabel(log.status) }}
                </span>
              </td>
              <td>
                <button
                  class="btn-icon"
                  title="Details"
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
                Keine Einträge gefunden
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Matrix Modal (Fullscreen) -->
    <div
      v-if="showMatrixModal"
      class="modal-overlay"
      @click.self="closeMatrixModal"
    >
      <div class="modal-content modal-fullscreen">
        <div class="modal-header">
          <h2>{{ editingNotification ? 'Benachrichtigung bearbeiten' : 'Neue Benachrichtigung' }}</h2>
          <button
            class="btn-close"
            @click="closeMatrixModal"
          >
            <span class="dashicons dashicons-no-alt" />
          </button>
        </div>

        <div class="modal-body">
          <!-- Notification Name -->
          <div class="notification-name-field">
            <label>Benachrichtigungsname *</label>
            <input
              v-model="matrixData.name"
              type="text"
              class="form-control"
              placeholder="z.B. Buchungsbestätigung"
            >
          </div>

          <!-- Matrix Grid -->
          <div class="matrix-container">
            <!-- Header: Channels -->
            <div class="matrix-header">
              <div class="matrix-corner">
                <div class="corner-label">Empfänger / Auslöser</div>
              </div>
              <div class="channel-tabs">
                <button
                  v-for="channel in channels"
                  :key="channel.value"
                  :class="['channel-tab', { active: activeChannel === channel.value }]"
                  @click="activeChannel = channel.value"
                >
                  <span :class="`dashicons dashicons-${channel.icon}`" />
                  {{ channel.label }}
                </button>
              </div>
            </div>

            <!-- Matrix Body -->
            <div class="matrix-body">
              <!-- Left: Recipients & Triggers -->
              <div class="recipients-triggers-grid">
                <div
                  v-for="recipient in recipients"
                  :key="recipient.value"
                  class="recipient-section"
                >
                  <div class="recipient-header">
                    <span :class="`dashicons dashicons-${recipient.icon}`" />
                    {{ recipient.label }}
                  </div>

                  <div class="triggers-list">
                    <div
                      v-for="trigger in triggers"
                      :key="trigger.value"
                      class="trigger-row"
                    >
                      <div class="trigger-label">
                        {{ trigger.label }}
                      </div>
                      <div class="trigger-config">
                        <label class="checkbox-label">
                          <input
                            v-model="matrixData.variants[getVariantKey(recipient.value, trigger.value, activeChannel)].enabled"
                            type="checkbox"
                            @change="onVariantToggle(recipient.value, trigger.value, activeChannel)"
                          >
                          <span>Aktiv</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right: Configuration Panel -->
              <div class="config-panel">
                <div
                  v-for="recipient in recipients"
                  :key="`config-${recipient.value}`"
                  class="recipient-configs"
                >
                  <div
                    v-for="trigger in triggers"
                    :key="`config-${recipient.value}-${trigger.value}`"
                  >
                    <div
                      v-if="matrixData.variants[getVariantKey(recipient.value, trigger.value, activeChannel)]?.enabled"
                      class="variant-config"
                    >
                      <div class="config-header">
                        <h4>{{ recipient.label }} → {{ trigger.label }} → {{ getChannelLabel(activeChannel) }}</h4>
                      </div>

                      <div class="config-fields">
                        <!-- Subject (nur für E-Mail) -->
                        <div
                          v-if="activeChannel === 'email'"
                          class="form-group"
                        >
                          <label>Betreff *</label>
                          <input
                            v-model="matrixData.variants[getVariantKey(recipient.value, trigger.value, activeChannel)].subject"
                            type="text"
                            class="form-control"
                            placeholder="z.B. Ihre Buchung wurde bestätigt"
                          >
                        </div>

                        <!-- Message -->
                        <div class="form-group">
                          <label>Nachricht *</label>
                          <textarea
                            v-model="matrixData.variants[getVariantKey(recipient.value, trigger.value, activeChannel)].message"
                            class="form-control"
                            rows="4"
                            :placeholder="getMessagePlaceholder(activeChannel)"
                          />
                        </div>

                        <!-- Send Delay -->
                        <div class="form-group">
                          <label>Verzögerung (Minuten)</label>
                          <input
                            v-model="matrixData.variants[getVariantKey(recipient.value, trigger.value, activeChannel)].send_delay"
                            type="number"
                            class="form-control"
                            min="0"
                            placeholder="0 = Sofort"
                          >
                        </div>

                        <!-- Template Variables Help -->
                        <div class="variables-help">
                          <strong>Verfügbare Variablen:</strong>
                          <div class="variables-list">
                            <code v-pre>{{customer_name}}</code>
                            <code v-pre>{{service_name}}</code>
                            <code v-pre>{{booking_date}}</code>
                            <code v-pre>{{booking_time}}</code>
                            <code v-pre>{{employee_name}}</code>
                            <code v-pre>{{price}}</code>
                            <code v-pre>{{location}}</code>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  v-if="!hasAnyEnabledVariants"
                  class="no-config-message"
                >
                  <span class="dashicons dashicons-info" />
                  <p>Aktivieren Sie eine Kombination links, um die Nachricht zu konfigurieren.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button
            class="btn btn-secondary"
            @click="closeMatrixModal"
          >
            Abbrechen
          </button>
          <button
            class="btn btn-primary"
            :disabled="!canSaveMatrix"
            @click="saveMatrix"
          >
            Speichern
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
              {{ getChannelLabel(selectedLog.channel) }}
            </div>
            <div class="detail-row">
              <strong>Status:</strong>
              <span :class="['status-badge', `status-${selectedLog.status}`]">
                {{ getStatusLabel(selectedLog.status) }}
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
import { ref, computed } from 'vue'

interface NotificationVariant {
  enabled: boolean
  subject?: string
  message: string
  send_delay: number
}

interface NotificationMatrix {
  id?: number
  name: string
  variants: Record<string, NotificationVariant>
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
const showMatrixModal = ref(false)
const showLogModal = ref(false)
const activeChannel = ref('email')
const editingNotification = ref<NotificationMatrix | null>(null)
const selectedLog = ref<NotificationLog | null>(null)

const notifications = ref<NotificationMatrix[]>([])
const logs = ref<NotificationLog[]>([])

const logFilter = ref({
  channel: '',
  status: '',
  date: ''
})

const matrixData = ref<NotificationMatrix>({
  name: '',
  variants: {}
})

const channels = [
  { value: 'email', label: 'E-Mail', icon: 'email' },
  { value: 'sms', label: 'SMS', icon: 'phone' },
  { value: 'whatsapp', label: 'WhatsApp', icon: 'whatsapp' },
  { value: 'push', label: 'Push', icon: 'bell' }
]

const recipients = [
  { value: 'customer', label: 'Kunde', icon: 'admin-users' },
  { value: 'employee', label: 'Mitarbeiter', icon: 'businessperson' },
  { value: 'admin', label: 'Administrator', icon: 'admin-generic' }
]

const triggers = [
  { value: 'bookingCreated', label: 'Buchung erstellt' },
  { value: 'bookingConfirmed', label: 'Buchung bestätigt' },
  { value: 'bookingCancelled', label: 'Buchung storniert' },
  { value: 'bookingReminder', label: 'Buchungs-Erinnerung' },
  { value: 'paymentReceived', label: 'Zahlung erhalten' },
  { value: 'customerRegistered', label: 'Kunde registriert' }
]

const getVariantKey = (recipient: string, trigger: string, channel: string): string => {
  return `${recipient}:${trigger}:${channel}`
}

const hasAnyEnabledVariants = computed(() => {
  return Object.values(matrixData.value.variants).some(v => v.enabled)
})

const canSaveMatrix = computed(() => {
  if (!matrixData.value.name.trim()) return false

  const enabledVariants = Object.values(matrixData.value.variants).filter(v => v.enabled)
  if (enabledVariants.length === 0) return false

  // Check all enabled variants have required fields
  return enabledVariants.every(v => {
    return v.message?.trim().length > 0
  })
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

const getChannelLabel = (channel: string): string => {
  return channels.find(c => c.value === channel)?.label || channel
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    sent: 'Gesendet',
    delivered: 'Zugestellt',
    failed: 'Fehlgeschlagen',
    pending: 'Ausstehend'
  }
  return labels[status] || status
}

const getMessagePlaceholder = (channel: string): string => {
  const placeholders: Record<string, string> = {
    email: 'Hallo {{customer_name}},\n\nIhre Buchung für {{service_name}} am {{booking_date}} wurde bestätigt.',
    sms: 'Ihre Buchung für {{service_name}} am {{booking_date}} wurde bestätigt.',
    whatsapp: 'Hallo {{customer_name}}! Ihre Buchung für {{service_name}} am {{booking_date}} wurde bestätigt.',
    push: 'Buchung bestätigt: {{service_name}} am {{booking_date}}'
  }
  return placeholders[channel] || 'Nachricht...'
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('de-DE', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(date)
}

const getVariantCount = (notification: NotificationMatrix): number => {
  return Object.values(notification.variants).filter(v => v.enabled).length
}

const onVariantToggle = (recipient: string, trigger: string, channel: string) => {
  const key = getVariantKey(recipient, trigger, channel)
  if (!matrixData.value.variants[key]) {
    matrixData.value.variants[key] = {
      enabled: false,
      subject: '',
      message: '',
      send_delay: 0
    }
  }
}

const openMatrixModal = (notification?: NotificationMatrix) => {
  if (notification) {
    editingNotification.value = notification
    matrixData.value = JSON.parse(JSON.stringify(notification))
  } else {
    editingNotification.value = null
    matrixData.value = {
      name: '',
      variants: {}
    }

    // Initialize all variants as disabled
    recipients.forEach(recipient => {
      triggers.forEach(trigger => {
        channels.forEach(channel => {
          const key = getVariantKey(recipient.value, trigger.value, channel.value)
          matrixData.value.variants[key] = {
            enabled: false,
            subject: '',
            message: '',
            send_delay: 0
          }
        })
      })
    })
  }
  showMatrixModal.value = true
}

const closeMatrixModal = () => {
  showMatrixModal.value = false
  editingNotification.value = null
}

const saveMatrix = async () => {
  // API call to save notification matrix

  // Simulate save
  if (editingNotification.value) {
    const index = notifications.value.findIndex(n => n.id === editingNotification.value!.id)
    if (index !== -1) {
      notifications.value[index] = { ...matrixData.value, id: editingNotification.value.id }
    }
  } else {
    notifications.value.push({
      ...matrixData.value,
      id: Date.now()
    })
  }

  closeMatrixModal()
}

const deleteNotification = async (id?: number) => {
  if (confirm('Möchten Sie diese Benachrichtigung wirklich löschen?')) {
    // API call to delete notification
    notifications.value = notifications.value.filter(n => n.id !== id)
  }
}

const viewLogDetails = (log: NotificationLog) => {
  selectedLog.value = log
  showLogModal.value = true
}

const closeLogModal = () => {
  showLogModal.value = false
  selectedLog.value = null
}
</script>

<style>
.notifications-matrix-tab {
  padding: 1rem;
  height: 100%;
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
}

.tab-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #111827;
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
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #2271b1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
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

/* Saved Notifications */
.saved-notifications {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.notification-item {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.notification-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

.notification-summary {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 2rem;
}

.notification-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
}

.notification-name .dashicons {
  color: #2271b1;
}

.notification-count {
  font-size: 0.875rem;
  color: #6b7280;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.no-data-card {
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

.channel-badge.channel-push {
  background: #f3e8ff;
  color: #6b21a8;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.625rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
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
  padding: 1rem;
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

.modal-fullscreen {
  max-width: 95vw;
  max-height: 95vh;
  width: 95vw;
  height: 95vh;
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
  flex: 1;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* Notification Name Field */
.notification-name-field {
  margin-bottom: 1.5rem;
}

.notification-name-field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #111827;
}

.form-control {
  width: 100%;
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

/* Matrix Container */
.matrix-container {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  background: white;
}

.matrix-header {
  display: flex;
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.matrix-corner {
  width: 280px;
  padding: 1rem;
  border-right: 2px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
}

.corner-label {
  font-weight: 600;
  color: #6b7280;
  font-size: 0.875rem;
  text-align: center;
}

.channel-tabs {
  flex: 1;
  display: flex;
  gap: 0;
}

.channel-tab {
  flex: 1;
  padding: 1rem;
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  border-right: 1px solid #e5e7eb;
  transition: all 0.2s;
}

.channel-tab:last-child {
  border-right: none;
}

.channel-tab:hover {
  background: white;
  color: #111827;
}

.channel-tab.active {
  background: white;
  color: #2271b1;
  border-bottom: 3px solid #2271b1;
}

.matrix-body {
  display: flex;
  min-height: 500px;
}

.recipients-triggers-grid {
  width: 280px;
  border-right: 2px solid #e5e7eb;
  overflow-y: auto;
}

.recipient-section {
  border-bottom: 1px solid #e5e7eb;
}

.recipient-section:last-child {
  border-bottom: none;
}

.recipient-header {
  padding: 0.75rem 1rem;
  background: #f9fafb;
  font-weight: 600;
  font-size: 0.875rem;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.triggers-list {
  display: flex;
  flex-direction: column;
}

.trigger-row {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  gap: 0.75rem;
}

.trigger-row:last-child {
  border-bottom: none;
}

.trigger-label {
  flex: 1;
  font-size: 0.8125rem;
  color: #374151;
}

.trigger-config {
  display: flex;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  color: #6b7280;
  cursor: pointer;
  white-space: nowrap;
}

.checkbox-label input {
  margin: 0;
  cursor: pointer;
}

.config-panel {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
  background: #fafafa;
}

.recipient-configs {
  margin-bottom: 1.5rem;
}

.recipient-configs:last-child {
  margin-bottom: 0;
}

.variant-config {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.variant-config:last-child {
  margin-bottom: 0;
}

.config-header {
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
}

.config-header h4 {
  margin: 0;
  font-size: 0.9375rem;
  color: #111827;
}

.config-fields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: #374151;
}

.variables-help {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 6px;
  padding: 1rem;
  font-size: 0.8125rem;
}

.variables-help strong {
  display: block;
  margin-bottom: 0.5rem;
  color: #0c4a6e;
}

.variables-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.variables-list code {
  background: white;
  padding: 0.25rem 0.5rem;
  border: 1px solid #bae6fd;
  border-radius: 4px;
  font-size: 0.75rem;
  font-family: monospace;
  color: #0369a1;
}

.no-config-message {
  text-align: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}

.no-config-message .dashicons {
  font-size: 48px;
  margin-bottom: 1rem;
}

.no-config-message p {
  margin: 0;
  font-size: 0.875rem;
}

/* Log Details */
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

@media (max-width: 1200px) {
  .matrix-body {
    flex-direction: column;
  }

  .recipients-triggers-grid {
    width: 100%;
    border-right: none;
    border-bottom: 2px solid #e5e7eb;
  }

  .config-panel {
    max-height: 400px;
  }
}
</style>
