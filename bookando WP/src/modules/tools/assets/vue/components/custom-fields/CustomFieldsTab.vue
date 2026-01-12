<template>
  <div class="custom-fields-tab">
    <!-- Header -->
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
        @click="openFieldModal()"
      >
        <span class="dashicons dashicons-plus-alt" />
        {{ t('mod.tools.customFields.addField') }}
      </button>
    </div>

    <!-- Fields Table -->
    <div class="table-container">
      <table class="fields-table">
        <thead>
          <tr>
            <th>{{ t('mod.tools.customFields.fieldLabel') }}</th>
            <th>{{ t('mod.tools.customFields.fieldName') }}</th>
            <th>{{ t('mod.tools.customFields.fieldType') }}</th>
            <th>{{ t('mod.tools.customFields.entityType') }}</th>
            <th>{{ t('mod.tools.customFields.required') }}</th>
            <th>{{ t('mod.tools.customFields.active') }}</th>
            <th>{{ t('mod.tools.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="field in filteredFields"
            :key="field.id"
          >
            <td>{{ field.label }}</td>
            <td><code>{{ field.name }}</code></td>
            <td>{{ t(`mod.tools.customFields.types.${field.field_type}`) }}</td>
            <td>{{ t(`mod.tools.customFields.entities.${field.entity_type}`) }}</td>
            <td>
              <span :class="['badge', field.is_required ? 'badge-success' : 'badge-default']">
                {{ field.is_required ? 'Ja' : 'Nein' }}
              </span>
            </td>
            <td>
              <span :class="['badge', field.is_active ? 'badge-success' : 'badge-danger']">
                {{ field.is_active ? 'Aktiv' : 'Inaktiv' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button
                  class="btn-icon"
                  :title="t('mod.tools.edit')"
                  @click="openFieldModal(field)"
                >
                  <span class="dashicons dashicons-edit" />
                </button>
                <button
                  class="btn-icon btn-danger"
                  :title="t('mod.tools.delete')"
                  @click="deleteField(field.id)"
                >
                  <span class="dashicons dashicons-trash" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredFields.length === 0">
            <td
              colspan="7"
              class="no-data"
            >
              {{ t('mod.tools.noData') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="modal-overlay"
      @click.self="closeModal"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ editingField ? t('mod.tools.customFields.editField') : t('mod.tools.customFields.addField') }}</h2>
          <button
            class="btn-close"
            @click="closeModal"
          >
            <span class="dashicons dashicons-no-alt" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group">
              <label>{{ t('mod.tools.customFields.fieldName') }} *</label>
              <input
                v-model="formData.name"
                type="text"
                class="form-control"
              >
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.customFields.fieldLabel') }} *</label>
              <input
                v-model="formData.label"
                type="text"
                class="form-control"
              >
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.customFields.fieldType') }} *</label>
              <select
                v-model="formData.field_type"
                class="form-control"
              >
                <option value="text">
                  {{ t('mod.tools.customFields.types.text') }}
                </option>
                <option value="textarea">
                  {{ t('mod.tools.customFields.types.textarea') }}
                </option>
                <option value="number">
                  {{ t('mod.tools.customFields.types.number') }}
                </option>
                <option value="email">
                  {{ t('mod.tools.customFields.types.email') }}
                </option>
                <option value="phone">
                  {{ t('mod.tools.customFields.types.phone') }}
                </option>
                <option value="date">
                  {{ t('mod.tools.customFields.types.date') }}
                </option>
                <option value="time">
                  {{ t('mod.tools.customFields.types.time') }}
                </option>
                <option value="select">
                  {{ t('mod.tools.customFields.types.select') }}
                </option>
                <option value="checkbox">
                  {{ t('mod.tools.customFields.types.checkbox') }}
                </option>
                <option value="radio">
                  {{ t('mod.tools.customFields.types.radio') }}
                </option>
                <option value="file">
                  {{ t('mod.tools.customFields.types.file') }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.customFields.entityType') }} *</label>
              <select
                v-model="formData.entity_type"
                class="form-control"
              >
                <option value="customer">
                  {{ t('mod.tools.customFields.entities.customer') }}
                </option>
                <option value="booking">
                  {{ t('mod.tools.customFields.entities.booking') }}
                </option>
                <option value="service">
                  {{ t('mod.tools.customFields.entities.service') }}
                </option>
                <option value="employee">
                  {{ t('mod.tools.customFields.entities.employee') }}
                </option>
              </select>
            </div>

            <div
              v-if="['select', 'checkbox', 'radio'].includes(formData.field_type)"
              class="form-group"
            >
              <label>{{ t('mod.tools.customFields.options') }}</label>
              <textarea
                v-model="formData.options"
                class="form-control"
                rows="3"
                placeholder="Option 1, Option 2, Option 3"
              />
              <small>Kommagetrennte Werte</small>
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.customFields.position') }}</label>
              <input
                v-model="formData.position"
                type="number"
                class="form-control"
                min="0"
              >
            </div>

            <div class="form-group checkbox-group">
              <label>
                <input
                  v-model="formData.is_required"
                  type="checkbox"
                >
                {{ t('mod.tools.customFields.required') }}
              </label>
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
            @click="saveField"
          >
            {{ t('mod.tools.save') }}
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

interface CustomField {
  id?: number
  name: string
  label: string
  field_type: string
  entity_type: string
  options?: string
  is_required: boolean
  is_active: boolean
  position: number
}

const searchQuery = ref('')
const showModal = ref(false)
const editingField = ref<CustomField | null>(null)
const fields = ref<CustomField[]>([])

const formData = ref<CustomField>({
  name: '',
  label: '',
  field_type: 'text',
  entity_type: 'customer',
  options: '',
  is_required: false,
  is_active: true,
  position: 0
})

const filteredFields = computed(() => {
  if (!searchQuery.value) return fields.value
  const query = searchQuery.value.toLowerCase()
  return fields.value.filter(
    field =>
      field.name.toLowerCase().includes(query) ||
      field.label.toLowerCase().includes(query)
  )
})

const openFieldModal = (field?: CustomField) => {
  if (field) {
    editingField.value = field
    formData.value = { ...field }
  } else {
    editingField.value = null
    formData.value = {
      name: '',
      label: '',
      field_type: 'text',
      entity_type: 'customer',
      options: '',
      is_required: false,
      is_active: true,
      position: 0
    }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingField.value = null
}

const saveField = async () => {
  // API call to save field
  console.log('Saving field:', formData.value)
  closeModal()
}

const deleteField = async (id: number) => {
  if (confirm('Möchten Sie dieses Feld wirklich löschen?')) {
    // API call to delete field
    console.log('Deleting field:', id)
  }
}

onMounted(() => {
  // Load custom fields
})
</script>

<style>
.custom-fields-tab {
  padding: 1rem;
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

.table-container {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.fields-table {
  width: 100%;
  border-collapse: collapse;
}

.fields-table th {
  background: #f9fafb;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.fields-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.875rem;
}

.fields-table tbody tr:hover {
  background: #f9fafb;
}

.fields-table code {
  background: #f3f4f6;
  padding: 0.125rem 0.375rem;
  border-radius: 3px;
  font-size: 0.8125rem;
  font-family: monospace;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-default {
  background: #f3f4f6;
  color: #6b7280;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  padding: 0.25rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
  border-radius: 4px;
}

.btn-icon:hover {
  background: #f3f4f6;
  color: #111827;
}

.btn-icon.btn-danger:hover {
  background: #fee2e2;
  color: #991b1b;
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
  max-width: 800px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
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

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

@media (max-width: 768px) {
  .tab-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-bar {
    max-width: none;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    overflow-x: auto;
  }
}
</style>
