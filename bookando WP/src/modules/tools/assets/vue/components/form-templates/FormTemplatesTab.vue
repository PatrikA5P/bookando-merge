<template>
  <div class="form-templates-tab">
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
        @click="openTemplateModal()"
      >
        <span class="dashicons dashicons-plus-alt" />
        {{ t('mod.tools.formTemplates.addTemplate') }}
      </button>
    </div>

    <!-- Templates Grid -->
    <div class="templates-grid">
      <div
        v-for="template in filteredTemplates"
        :key="template.id"
        class="template-card"
      >
        <div class="template-header">
          <h3>{{ template.name }}</h3>
          <span :class="['badge', template.is_default ? 'badge-primary' : 'badge-default']">
            {{ template.is_default ? 'Standard' : t(`mod.tools.formTemplates.types.${template.template_type}`) }}
          </span>
        </div>

        <p class="template-description">
          {{ template.description }}
        </p>

        <div class="template-info">
          <div class="info-item">
            <span class="dashicons dashicons-editor-table" />
            {{ template.fields?.length || 0 }} Felder
          </div>
          <div class="info-item">
            <span :class="['status-dot', template.is_active ? 'active' : 'inactive']" />
            {{ template.is_active ? 'Aktiv' : 'Inaktiv' }}
          </div>
        </div>

        <div class="template-actions">
          <button
            class="btn btn-secondary btn-sm"
            @click="previewTemplate(template)"
          >
            <span class="dashicons dashicons-visibility" />
            {{ t('mod.tools.formTemplates.preview') }}
          </button>
          <button
            class="btn btn-secondary btn-sm"
            @click="openTemplateModal(template)"
          >
            <span class="dashicons dashicons-edit" />
            {{ t('mod.tools.edit') }}
          </button>
          <button
            class="btn-icon btn-danger"
            @click="deleteTemplate(template.id)"
          >
            <span class="dashicons dashicons-trash" />
          </button>
        </div>
      </div>

      <div
        v-if="filteredTemplates.length === 0"
        class="no-data-card"
      >
        <span class="dashicons dashicons-clipboard" />
        <p>{{ t('mod.tools.noData') }}</p>
      </div>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="modal-overlay"
      @click.self="closeModal"
    >
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h2>{{ editingTemplate ? t('mod.tools.formTemplates.editTemplate') : t('mod.tools.formTemplates.addTemplate') }}</h2>
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
              <label>{{ t('mod.tools.formTemplates.templateName') }} *</label>
              <input
                v-model="formData.name"
                type="text"
                class="form-control"
              >
            </div>

            <div class="form-group full-width">
              <label>{{ t('mod.tools.formTemplates.description') }}</label>
              <textarea
                v-model="formData.description"
                class="form-control"
                rows="2"
              />
            </div>

            <div class="form-group">
              <label>{{ t('mod.tools.formTemplates.templateType') }} *</label>
              <select
                v-model="formData.template_type"
                class="form-control"
              >
                <option value="booking">
                  {{ t('mod.tools.formTemplates.types.booking') }}
                </option>
                <option value="registration">
                  {{ t('mod.tools.formTemplates.types.registration') }}
                </option>
                <option value="contact">
                  {{ t('mod.tools.formTemplates.types.contact') }}
                </option>
                <option value="feedback">
                  {{ t('mod.tools.formTemplates.types.feedback') }}
                </option>
              </select>
            </div>

            <div class="form-group checkbox-group">
              <label>
                <input
                  v-model="formData.is_default"
                  type="checkbox"
                >
                {{ t('mod.tools.formTemplates.isDefault') }}
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

          <!-- Field Builder -->
          <div class="field-builder">
            <div class="builder-header">
              <h3>{{ t('mod.tools.formTemplates.fields') }}</h3>
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
                <div class="field-details">
                  <input
                    v-model="field.label"
                    placeholder="Feldbezeichnung"
                    class="field-input"
                  >
                  <select
                    v-model="field.type"
                    class="field-type-select"
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
                    <option value="textarea">
                      Textbereich
                    </option>
                    <option value="select">
                      Auswahl
                    </option>
                    <option value="checkbox">
                      Checkbox
                    </option>
                  </select>
                  <label class="field-required">
                    <input
                      v-model="field.required"
                      type="checkbox"
                    >
                    Pflicht
                  </label>
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
            {{ t('mod.tools.cancel') }}
          </button>
          <button
            class="btn btn-primary"
            @click="saveTemplate"
          >
            {{ t('mod.tools.save') }}
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
          <h2>{{ t('mod.tools.formTemplates.preview') }}: {{ previewTemplate?.name }}</h2>
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
              v-for="(field, index) in previewTemplate?.fields"
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
                v-if="['text', 'email', 'phone'].includes(field.type)"
                :type="field.type"
                class="form-control"
                disabled
              >
              <textarea
                v-else-if="field.type === 'textarea'"
                class="form-control"
                rows="3"
                disabled
              />
              <select
                v-else-if="field.type === 'select'"
                class="form-control"
                disabled
              >
                <option>Option auswählen</option>
              </select>
              <label
                v-else-if="field.type === 'checkbox'"
                class="checkbox-preview"
              >
                <input
                  type="checkbox"
                  disabled
                >
                Checkbox-Option
              </label>
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
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface TemplateField {
  label: string
  type: string
  required: boolean
}

interface FormTemplate {
  id?: number
  name: string
  description: string
  template_type: string
  fields: TemplateField[]
  is_default: boolean
  is_active: boolean
}

const searchQuery = ref('')
const showModal = ref(false)
const showPreview = ref(false)
const editingTemplate = ref<FormTemplate | null>(null)
const previewTemplate = ref<FormTemplate | null>(null)
const templates = ref<FormTemplate[]>([])

const formData = ref<FormTemplate>({
  name: '',
  description: '',
  template_type: 'booking',
  fields: [],
  is_default: false,
  is_active: true
})

const filteredTemplates = computed(() => {
  if (!searchQuery.value) return templates.value
  const query = searchQuery.value.toLowerCase()
  return templates.value.filter(
    template =>
      template.name.toLowerCase().includes(query) ||
      template.description?.toLowerCase().includes(query)
  )
})

const openTemplateModal = (template?: FormTemplate) => {
  if (template) {
    editingTemplate.value = template
    formData.value = JSON.parse(JSON.stringify(template))
  } else {
    editingTemplate.value = null
    formData.value = {
      name: '',
      description: '',
      template_type: 'booking',
      fields: [],
      is_default: false,
      is_active: true
    }
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingTemplate.value = null
}

const previewTemplateHandler = (template: FormTemplate) => {
  previewTemplate.value = template
  showPreview.value = true
}

const closePreview = () => {
  showPreview.value = false
  previewTemplate.value = null
}

const addField = () => {
  formData.value.fields.push({
    label: '',
    type: 'text',
    required: false
  })
}

const removeField = (index: number) => {
  formData.value.fields.splice(index, 1)
}

const saveTemplate = async () => {
  // API call to save template
  closeModal()
}

const deleteTemplate = async (id?: number) => {
  if (confirm('Möchten Sie diese Vorlage wirklich löschen?')) {
    // API call to delete template
  }
}

onMounted(() => {
  // Load templates
})
</script>

<style>
.form-templates-tab {
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

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.template-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.template-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.template-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 0.75rem;
  gap: 1rem;
}

.template-header h3 {
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

.template-description {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1rem 0;
  line-height: 1.5;
}

.template-info {
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

.template-actions {
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
  align-items: center;
  gap: 0.75rem;
}

.drag-handle {
  cursor: move;
  color: #9ca3af;
}

.field-details {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.field-input {
  flex: 1;
  padding: 0.375rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
}

.field-type-select {
  padding: 0.375rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 0.875rem;
}

.field-required {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
  white-space: nowrap;
  cursor: pointer;
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

.checkbox-preview {
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

  .templates-grid {
    grid-template-columns: 1fr;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .field-details {
    flex-wrap: wrap;
  }
}
</style>
