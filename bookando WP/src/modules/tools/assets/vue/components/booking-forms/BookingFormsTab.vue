<template>
  <div class="booking-forms-tab">
    <!-- Header -->
    <div class="tab-header">
      <div class="search-bar">
        <span class="dashicons dashicons-search" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Formular suchen..."
          class="search-input"
        >
      </div>
      <button
        class="btn btn-primary"
        @click="openFormModal()"
      >
        <span class="dashicons dashicons-plus-alt" />
        Neues Buchungsformular
      </button>
    </div>

    <!-- Forms Grid -->
    <div class="forms-grid">
      <div
        v-for="form in filteredForms"
        :key="form.id"
        class="form-card"
      >
        <div class="form-header">
          <h3>{{ form.name }}</h3>
          <span :class="['badge', form.is_default ? 'badge-primary' : 'badge-default']">
            {{ form.is_default ? 'Standard' : 'Benutzerdefiniert' }}
          </span>
        </div>

        <p
          v-if="form.description"
          class="form-description"
        >
          {{ form.description }}
        </p>

        <div class="form-info">
          <div class="info-item">
            <span class="dashicons dashicons-editor-table" />
            {{ form.fields?.length || 0 }} Felder
          </div>
          <div class="info-item">
            <span :class="['status-dot', form.is_active ? 'active' : 'inactive']" />
            {{ form.is_active ? 'Aktiv' : 'Inaktiv' }}
          </div>
        </div>

        <div class="form-actions">
          <button
            class="btn btn-secondary btn-sm"
            @click="previewForm(form)"
          >
            <span class="dashicons dashicons-visibility" />
            Vorschau
          </button>
          <button
            class="btn btn-secondary btn-sm"
            @click="openFormModal(form)"
          >
            <span class="dashicons dashicons-edit" />
            Bearbeiten
          </button>
          <button
            v-if="!form.is_default"
            class="btn-icon btn-danger"
            @click="deleteForm(form.id)"
          >
            <span class="dashicons dashicons-trash" />
          </button>
        </div>
      </div>

      <div
        v-if="filteredForms.length === 0"
        class="no-data-card"
      >
        <span class="dashicons dashicons-clipboard" />
        <p>Keine Buchungsformulare vorhanden</p>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="showModal"
      class="modal-overlay"
      @click.self="closeModal"
    >
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h2>{{ editingForm ? 'Buchungsformular bearbeiten' : 'Neues Buchungsformular' }}</h2>
          <button
            class="btn-close"
            @click="closeModal"
          >
            <span class="dashicons dashicons-no-alt" />
          </button>
        </div>

        <div class="modal-body">
          <!-- Basic Info -->
          <div class="form-grid">
            <div class="form-group full-width">
              <label>Name *</label>
              <input
                v-model="formData.name"
                type="text"
                class="form-control"
                placeholder="z.B. Yoga-Kurs Anmeldung"
              >
            </div>

            <div class="form-group full-width">
              <label>Beschreibung</label>
              <textarea
                v-model="formData.description"
                class="form-control"
                rows="2"
                placeholder="Optionale Beschreibung für dieses Formular"
              />
            </div>

            <div class="form-group checkbox-group">
              <label>
                <input
                  v-model="formData.is_default"
                  type="checkbox"
                >
                Als Standard-Formular setzen
              </label>
            </div>

            <div class="form-group checkbox-group">
              <label>
                <input
                  v-model="formData.is_active"
                  type="checkbox"
                >
                Aktiv
              </label>
            </div>
          </div>

          <!-- Field Builder -->
          <div class="field-builder">
            <div class="builder-header">
              <h3>Formularfelder</h3>
              <button
                class="btn btn-sm btn-secondary"
                @click="addField"
              >
                <span class="dashicons dashicons-plus-alt" />
                Feld hinzufügen
              </button>
            </div>

            <div class="fields-list">
              <div
                v-for="(field, index) in formData.fields"
                :key="index"
                class="field-item"
              >
                <span class="drag-handle dashicons dashicons-menu" />

                <div class="field-content">
                  <!-- Row 1: Label and Type -->
                  <div class="field-row">
                    <input
                      v-model="field.label"
                      placeholder="Feldbezeichnung *"
                      class="field-input field-label"
                    >
                    <select
                      v-model="field.type"
                      class="field-select field-type"
                    >
                      <option value="text">
                        Text
                      </option>
                      <option value="email">
                        E-Mail
                      </option>
                      <option value="phone">
                        Telefon
                      </option>
                      <option value="number">
                        Zahl
                      </option>
                      <option value="date">
                        Datum
                      </option>
                      <option value="time">
                        Uhrzeit
                      </option>
                      <option value="textarea">
                        Textbereich
                      </option>
                      <option value="select">
                        Auswahl (Dropdown)
                      </option>
                      <option value="radio">
                        Radio-Buttons
                      </option>
                      <option value="checkbox">
                        Checkboxen
                      </option>
                      <option value="file">
                        Datei-Upload
                      </option>
                    </select>
                  </div>

                  <!-- Row 2: Options (if select/radio/checkbox) -->
                  <div
                    v-if="['select', 'radio', 'checkbox'].includes(field.type)"
                    class="field-row"
                  >
                    <input
                      v-model="field.options"
                      placeholder="Optionen (kommagetrennt): Option 1, Option 2, Option 3"
                      class="field-input field-options"
                    >
                  </div>

                  <!-- Row 3: Validation & Settings -->
                  <div class="field-row field-settings">
                    <label class="field-checkbox">
                      <input
                        v-model="field.required"
                        type="checkbox"
                      >
                      Pflichtfeld
                    </label>

                    <input
                      v-model="field.placeholder"
                      placeholder="Platzhalter (optional)"
                      class="field-input field-placeholder"
                    >

                    <input
                      v-if="field.type === 'text' || field.type === 'textarea'"
                      v-model="field.validation"
                      placeholder="Validierung (optional)"
                      class="field-input field-validation"
                    >
                  </div>
                </div>

                <button
                  class="btn-icon btn-danger"
                  @click="removeField(index)"
                >
                  <span class="dashicons dashicons-trash" />
                </button>
              </div>

              <div
                v-if="formData.fields.length === 0"
                class="no-fields"
              >
                Keine Felder vorhanden. Klicken Sie auf "Feld hinzufügen", um zu beginnen.
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button
            class="btn btn-secondary"
            @click="closeModal"
          >
            Abbrechen
          </button>
          <button
            class="btn btn-primary"
            :disabled="!canSave"
            @click="saveForm"
          >
            Speichern
          </button>
        </div>
      </div>
    </div>

    <!-- Preview Modal -->
    <div
      v-if="showPreview"
      class="modal-overlay"
      @click.self="closePreview"
    >
      <div class="modal-content">
        <div class="modal-header">
          <h2>Vorschau: {{ previewData?.name }}</h2>
          <button
            class="btn-close"
            @click="closePreview"
          >
            <span class="dashicons dashicons-no-alt" />
          </button>
        </div>

        <div class="modal-body">
          <div class="form-preview">
            <div
              v-for="(field, index) in previewData?.fields"
              :key="index"
              class="preview-field"
            >
              <label>
                {{ field.label }}
                <span
                  v-if="field.required"
                  class="required-star"
                >*</span>
              </label>

              <input
                v-if="['text', 'email', 'phone', 'number'].includes(field.type)"
                :type="field.type"
                :placeholder="field.placeholder"
                class="form-control"
                disabled
              >

              <input
                v-else-if="['date', 'time'].includes(field.type)"
                :type="field.type"
                class="form-control"
                disabled
              >

              <textarea
                v-else-if="field.type === 'textarea'"
                :placeholder="field.placeholder"
                class="form-control"
                rows="3"
                disabled
              />

              <select
                v-else-if="field.type === 'select'"
                class="form-control"
                disabled
              >
                <option value="">
                  Bitte wählen...
                </option>
                <option
                  v-for="(option, optIndex) in parseOptions(field.options)"
                  :key="optIndex"
                >
                  {{ option }}
                </option>
              </select>

              <div
                v-else-if="field.type === 'radio'"
                class="radio-group"
              >
                <label
                  v-for="(option, optIndex) in parseOptions(field.options)"
                  :key="optIndex"
                  class="radio-option"
                >
                  <input
                    type="radio"
                    :name="`preview-radio-${index}`"
                    disabled
                  >
                  {{ option }}
                </label>
              </div>

              <div
                v-else-if="field.type === 'checkbox'"
                class="checkbox-group-preview"
              >
                <label
                  v-for="(option, optIndex) in parseOptions(field.options)"
                  :key="optIndex"
                  class="checkbox-option"
                >
                  <input
                    type="checkbox"
                    disabled
                  >
                  {{ option }}
                </label>
              </div>

              <input
                v-else-if="field.type === 'file'"
                type="file"
                class="form-control"
                disabled
              >
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button
            class="btn btn-secondary"
            @click="closePreview"
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

interface FormField {
  label: string
  type: string
  required: boolean
  placeholder?: string
  options?: string
  validation?: string
}

interface BookingForm {
  id?: number
  name: string
  description: string
  fields: FormField[]
  is_default: boolean
  is_active: boolean
}

const searchQuery = ref('')
const showModal = ref(false)
const showPreview = ref(false)
const editingForm = ref<BookingForm | null>(null)
const previewData = ref<BookingForm | null>(null)
const forms = ref<BookingForm[]>([
  {
    id: 1,
    name: 'Standard-Buchungsformular',
    description: 'Basis-Formular für alle Buchungen',
    fields: [
      { label: 'Besondere Wünsche', type: 'textarea', required: false, placeholder: 'Optional' }
    ],
    is_default: true,
    is_active: true
  }
])

const formData = ref<BookingForm>({
  name: '',
  description: '',
  fields: [],
  is_default: false,
  is_active: true
})

const filteredForms = computed(() => {
  if (!searchQuery.value) return forms.value
  const query = searchQuery.value.toLowerCase()
  return forms.value.filter(
    form =>
      form.name.toLowerCase().includes(query) ||
      form.description?.toLowerCase().includes(query)
  )
})

const canSave = computed(() => {
  return formData.value.name.trim().length > 0
})

const parseOptions = (optionsString?: string): string[] => {
  if (!optionsString) return []
  return optionsString.split(',').map(o => o.trim()).filter(o => o.length > 0)
}

const openFormModal = (form?: BookingForm) => {
  if (form) {
    editingForm.value = form
    formData.value = JSON.parse(JSON.stringify(form))
  } else {
    editingForm.value = null
    formData.value = {
      name: '',
      description: '',
      fields: [],
      is_default: false,
      is_active: true
    }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingForm.value = null
}

const previewForm = (form: BookingForm) => {
  previewData.value = form
  showPreview.value = true
}

const closePreview = () => {
  showPreview.value = false
  previewData.value = null
}

const addField = () => {
  formData.value.fields.push({
    label: '',
    type: 'text',
    required: false,
    placeholder: ''
  })
}

const removeField = (index: number) => {
  formData.value.fields.splice(index, 1)
}

const saveForm = async () => {
  // API call to save form

  // Simulate save
  if (editingForm.value) {
    const index = forms.value.findIndex(f => f.id === editingForm.value!.id)
    if (index !== -1) {
      forms.value[index] = { ...formData.value, id: editingForm.value.id }
    }
  } else {
    forms.value.push({
      ...formData.value,
      id: Date.now()
    })
  }

  closeModal()
}

const deleteForm = async (id?: number) => {
  if (confirm('Möchten Sie dieses Buchungsformular wirklich löschen?')) {
    // API call to delete form
    forms.value = forms.value.filter(f => f.id !== id)
  }
}
</script>

<style>
.booking-forms-tab {
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

.forms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.form-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.form-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 0.75rem;
  gap: 1rem;
}

.form-header h3 {
  margin: 0;
  font-size: 1.125rem;
  color: #111827;
  flex: 1;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.badge-primary {
  background: #dbeafe;
  color: #1e40af;
}

.badge-default {
  background: #f3f4f6;
  color: #6b7280;
}

.form-description {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1rem 0;
  line-height: 1.5;
}

.form-info {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-dot.active {
  background: #16a34a;
}

.status-dot.inactive {
  background: #dc2626;
}

.form-actions {
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
  max-width: 1000px;
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
  margin-bottom: 1.5rem;
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

/* Field Builder */
.field-builder {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
}

.builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.builder-header h3 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.fields-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.field-item {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 0.75rem;
  display: flex;
  align-items: start;
  gap: 0.75rem;
}

.drag-handle {
  cursor: move;
  color: #9ca3af;
  margin-top: 0.5rem;
}

.field-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.field-input,
.field-select {
  padding: 0.375rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
}

.field-label {
  flex: 2;
}

.field-type {
  flex: 1;
  min-width: 160px;
}

.field-options {
  flex: 1;
}

.field-settings {
  flex-wrap: wrap;
}

.field-checkbox {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
  white-space: nowrap;
  cursor: pointer;
}

.field-checkbox input {
  margin: 0;
}

.field-placeholder,
.field-validation {
  flex: 1;
  min-width: 150px;
}

.no-fields {
  text-align: center;
  padding: 2rem;
  color: #9ca3af;
  font-size: 0.875rem;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* Preview */
.form-preview {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.preview-field label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: #374151;
}

.required-star {
  color: #dc2626;
}

.radio-group,
.checkbox-group-preview {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.radio-option,
.checkbox-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  cursor: default;
}

@media (max-width: 768px) {
  .tab-header {
    flex-direction: column;
    align-items: stretch;
  }

  .search-bar {
    max-width: none;
  }

  .forms-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .field-row {
    flex-wrap: wrap;
  }

  .field-type {
    min-width: 100%;
  }
}
</style>
