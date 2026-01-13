<!-- DesignTab.vue - Design Customization with 3-Level Navigation -->
<template>
  <div class="bookando-tools-design">
    <!-- Level 1: Category Selection Grid -->
    <div
      v-if="currentLevel === 'category'"
      class="design-level design-level-category"
    >
      <div class="level-header">
        <h1>Design-Vorlagen</h1>
        <p>Wählen Sie eine Kategorie, um Ihre Design-Vorlagen zu verwalten</p>
      </div>

      <div class="bookando-card-grid">
        <div
          v-for="category in categories"
          :key="category.value"
          class="bookando-card is-hoverable category-card"
          @click="selectCategory(category.value)"
        >
          <div class="category-card-icon">
            {{ category.icon }}
          </div>
          <h3>{{ category.label }}</h3>
          <p>{{ category.description }}</p>
          <span
            v-if="category.badge"
            class="category-badge"
          >{{ category.badge }}</span>
        </div>
      </div>
    </div>

    <!-- Level 2: Template Selection/List -->
    <div
      v-if="currentLevel === 'templates'"
      class="design-level design-level-templates"
    >
      <div class="level-header">
        <button
          class="bookando-btn bookando-btn--text"
          @click="backToCategories"
        >
          ← Zurück
        </button>
        <div class="level-header-title">
          <h2>{{ getCategoryLabel(selectedCategory) }}</h2>
          <p>Wählen Sie eine Vorlage zum Anpassen oder erstellen Sie eine neue</p>
        </div>
        <button
          class="bookando-btn bookando-btn--primary"
          @click="createNewTemplate"
        >
          <span>+</span> Neue Vorlage
        </button>
      </div>

      <div
        v-if="filteredTemplates.length > 0"
        class="template-grid"
      >
        <div
          v-for="template in filteredTemplates"
          :key="template.id"
          class="bookando-card is-hoverable template-card"
        >
          <div class="template-card-header">
            <h3>{{ template.name }}</h3>
            <span class="template-meta">{{ formatDate(template.updatedAt) }}</span>
          </div>
          <div class="template-card-preview">
            <div class="preview-placeholder">
              Vorschau
            </div>
          </div>
          <div class="template-card-actions">
            <button
              class="bookando-btn bookando-btn--small"
              @click="editTemplate(template)"
            >
              Anpassen
            </button>
            <button
              class="bookando-btn bookando-btn--small bookando-btn--text"
              @click="duplicateTemplate(template)"
            >
              Duplizieren
            </button>
            <button
              class="bookando-btn bookando-btn--small bookando-btn--square bookando-btn--text"
              @click="deleteTemplate(template.id)"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <div
        v-else
        class="empty-state"
      >
        <div class="empty-state-icon">
          📋
        </div>
        <h3>Noch keine Vorlagen</h3>
        <p>Erstellen Sie Ihre erste Design-Vorlage für {{ getCategoryLabel(selectedCategory) }}</p>
        <button
          class="bookando-btn bookando-btn--primary"
          @click="createNewTemplate"
        >
          <span>+</span> Erste Vorlage erstellen
        </button>
      </div>
    </div>

    <!-- Level 3: Customization Split-View -->
    <div
      v-if="currentLevel === 'customize'"
      class="design-level design-level-customize"
    >
      <div class="customize-header">
        <button
          class="bookando-btn bookando-btn--text"
          @click="backToTemplates"
        >
          ← Zurück zu Vorlagen
        </button>
        <div class="customize-header-title">
          <input
            v-model="currentTemplate.name"
            class="template-name-input"
            placeholder="Vorlagen-Name"
          >
        </div>
        <div class="customize-header-actions">
          <button
            class="bookando-btn bookando-btn--text"
            @click="resetTemplate"
          >
            Zurücksetzen
          </button>
          <button
            class="bookando-btn bookando-btn--primary"
            @click="saveTemplate"
          >
            Speichern
          </button>
        </div>
      </div>

      <div class="customize-split-view">
        <!-- Settings Sidebar (40%) -->
        <div class="customize-sidebar">
          <div class="sidebar-scroll">
            <!-- Global Settings -->
            <div class="settings-section">
              <h3 class="section-title">
                Globale Einstellungen
              </h3>

              <!-- Typography -->
              <div class="setting-group">
                <button
                  class="setting-group-header"
                  @click="toggleAccordion('typography')"
                >
                  <span>Typografie</span>
                  <span class="accordion-icon">{{ openAccordions.typography ? '−' : '+' }}</span>
                </button>
                <div
                  v-show="openAccordions.typography"
                  class="setting-group-body"
                >
                  <div class="setting-item">
                    <label>Schriftfamilie</label>
                    <select v-model="currentTemplate.globalSettings.typography.fontFamily">
                      <option value="Inter">
                        Inter
                      </option>
                      <option value="Roboto">
                        Roboto
                      </option>
                      <option value="Open Sans">
                        Open Sans
                      </option>
                      <option value="Lato">
                        Lato
                      </option>
                      <option value="Montserrat">
                        Montserrat
                      </option>
                    </select>
                  </div>
                  <div class="setting-item">
                    <label>Schriftgröße (px)</label>
                    <input
                      v-model.number="currentTemplate.globalSettings.typography.fontSize"
                      type="number"
                      min="12"
                      max="24"
                    >
                  </div>
                </div>
              </div>

              <!-- Colors Section -->
              <div class="setting-group">
                <button
                  class="setting-group-header"
                  @click="toggleAccordion('colors')"
                >
                  <span>Farben</span>
                  <span class="accordion-icon">{{ openAccordions.colors ? '−' : '+' }}</span>
                </button>
                <div
                  v-show="openAccordions.colors"
                  class="setting-group-body"
                >
                  <!-- Primary & State Colors -->
                  <div class="color-subsection">
                    <div class="subsection-label">
                      Primär & Status
                    </div>
                    <div class="setting-item">
                      <label>Primärfarbe</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.primary"
                        type="color"
                      >
                    </div>
                    <div class="setting-item">
                      <label>Erfolg</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.success"
                        type="color"
                      >
                    </div>
                    <div class="setting-item">
                      <label>Warnung</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.warning"
                        type="color"
                      >
                    </div>
                    <div class="setting-item">
                      <label>Fehler</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.error"
                        type="color"
                      >
                    </div>
                  </div>

                  <!-- Sidebar Colors -->
                  <div class="color-subsection">
                    <div class="subsection-label">
                      Seitenleiste
                    </div>
                    <div class="setting-item">
                      <label>Hintergrund</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.sidebar.background"
                        type="color"
                      >
                    </div>
                    <div class="setting-item">
                      <label>Text</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.sidebar.text"
                        type="color"
                      >
                    </div>
                  </div>

                  <!-- Content Colors -->
                  <div class="color-subsection">
                    <div class="subsection-label">
                      Inhalt
                    </div>
                    <div class="setting-item">
                      <label>Hintergrund</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.content.background"
                        type="color"
                      >
                    </div>
                    <div class="setting-item">
                      <label>Überschrift</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.content.heading"
                        type="color"
                      >
                    </div>
                    <div class="setting-item">
                      <label>Text</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.content.text"
                        type="color"
                      >
                    </div>
                  </div>

                  <!-- Form Elements -->
                  <div class="color-subsection">
                    <div class="subsection-label">
                      Formular-Elemente
                    </div>
                    <div class="setting-item setting-item-toggle">
                      <label>Einheitliche Farben</label>
                      <input
                        v-model="currentTemplate.globalSettings.colors.formElements.unified"
                        type="checkbox"
                        class="liquid-toggle"
                      >
                    </div>
                    <div v-if="currentTemplate.globalSettings.colors.formElements.input">
                      <div class="setting-item">
                        <label>Hintergrund</label>
                        <input
                          v-model="currentTemplate.globalSettings.colors.formElements.input.background"
                          type="color"
                        >
                      </div>
                      <div class="setting-item">
                        <label>Rahmen</label>
                        <input
                          v-model="currentTemplate.globalSettings.colors.formElements.input.border"
                          type="color"
                        >
                      </div>
                      <div class="setting-item">
                        <label>Text</label>
                        <input
                          v-model="currentTemplate.globalSettings.colors.formElements.input.text"
                          type="color"
                        >
                      </div>
                    </div>
                  </div>

                  <!-- Buttons -->
                  <div class="color-subsection">
                    <div class="subsection-label">
                      Buttons
                    </div>
                    <div class="button-colors-grid">
                      <div class="button-color-group">
                        <span class="button-group-label">Primär</span>
                        <div class="setting-item">
                          <label>Hintergrund</label>
                          <input
                            v-model="currentTemplate.globalSettings.colors.buttons.primary.background"
                            type="color"
                          >
                        </div>
                        <div class="setting-item">
                          <label>Text</label>
                          <input
                            v-model="currentTemplate.globalSettings.colors.buttons.primary.text"
                            type="color"
                          >
                        </div>
                        <div class="setting-item setting-item-toggle">
                          <label>Globale Rahmen</label>
                          <input
                            v-model="currentTemplate.globalSettings.colors.buttons.primary.useGlobalBorder"
                            type="checkbox"
                            class="liquid-toggle"
                          >
                        </div>
                      </div>
                      <div class="button-color-group">
                        <span class="button-group-label">Sekundär</span>
                        <div class="setting-item">
                          <label>Hintergrund</label>
                          <input
                            v-model="currentTemplate.globalSettings.colors.buttons.secondary.background"
                            type="color"
                          >
                        </div>
                        <div class="setting-item">
                          <label>Text</label>
                          <input
                            v-model="currentTemplate.globalSettings.colors.buttons.secondary.text"
                            type="color"
                          >
                        </div>
                        <div class="setting-item setting-item-toggle">
                          <label>Globale Rahmen</label>
                          <input
                            v-model="currentTemplate.globalSettings.colors.buttons.secondary.useGlobalBorder"
                            type="checkbox"
                            class="liquid-toggle"
                          >
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Borders -->
              <div class="setting-group">
                <button
                  class="setting-group-header"
                  @click="toggleAccordion('borders')"
                >
                  <span>Rahmen & Rundung</span>
                  <span class="accordion-icon">{{ openAccordions.borders ? '−' : '+' }}</span>
                </button>
                <div
                  v-show="openAccordions.borders"
                  class="setting-group-body"
                >
                  <div class="setting-item">
                    <label>Rahmenstärke (px)</label>
                    <input
                      v-model.number="currentTemplate.globalSettings.borders.width"
                      type="number"
                      min="0"
                      max="5"
                    >
                  </div>
                  <div class="setting-item">
                    <label>Eckenrundung (px)</label>
                    <input
                      v-model.number="currentTemplate.globalSettings.borders.radius"
                      type="number"
                      min="0"
                      max="32"
                    >
                  </div>
                </div>
              </div>
            </div>

            <!-- Section Settings -->
            <div
              v-if="currentTemplate.sections"
              class="settings-section"
            >
              <h3 class="section-title">
                Abschnitts-Einstellungen
              </h3>

              <!-- Categories Section -->
              <div
                v-if="currentTemplate.sections.categories"
                class="setting-group"
              >
                <button
                  class="setting-group-header"
                  @click="toggleAccordion('categories')"
                >
                  <span>Kategorien</span>
                  <span class="accordion-icon">{{ openAccordions.categories ? '−' : '+' }}</span>
                </button>
                <div
                  v-show="openAccordions.categories"
                  class="setting-group-body"
                >
                  <div class="setting-item setting-item-toggle">
                    <label>Seitenfarbe anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.categories.options.showCardSideColor"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Akzentfarbe anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.categories.options.showAccentColor"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Dienste-Anzahl anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.categories.options.showTotalServicesCount"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>

                  <!-- Labels Subsection -->
                  <div class="subsection-label">
                    Beschriftungen
                  </div>
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.categories.labels.primaryButton"
                    label="Primär-Button"
                  />
                </div>
              </div>

              <!-- Services Overview Section -->
              <div
                v-if="currentTemplate.sections.servicesOverview"
                class="setting-group"
              >
                <button
                  class="setting-group-header"
                  @click="toggleAccordion('servicesOverview')"
                >
                  <span>Dienste-Übersicht</span>
                  <span class="accordion-icon">{{ openAccordions.servicesOverview ? '−' : '+' }}</span>
                </button>
                <div
                  v-show="openAccordions.servicesOverview"
                  class="setting-group-body"
                >
                  <!-- Layout Options -->
                  <div class="subsection-label">
                    Layout
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Hintergrund anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.layout.showBackground"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Seitenleiste anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.layout.showSidebar"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Karten-Farben anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.layout.showCardColors"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>

                  <!-- Filter Options -->
                  <div class="subsection-label">
                    Filter
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Suche anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.filters.showSearch"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Mitarbeiter-Filter</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.filters.showEmployeeFilter"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Standort-Filter</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.filters.showLocationFilter"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>

                  <!-- Service Info Options -->
                  <div class="subsection-label">
                    Dienst-Informationen
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>MwSt. anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.serviceInfo.showVatVisibility"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Dienst-Badge</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.serviceInfo.showServiceBadge"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Preis anzeigen</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.serviceInfo.showServicePrice"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>
                  <div class="setting-item setting-item-toggle">
                    <label>Aktion-Button</label>
                    <input
                      v-model="currentTemplate.sections.servicesOverview.options.serviceInfo.showActionButton"
                      type="checkbox"
                      class="liquid-toggle"
                    >
                  </div>

                  <!-- Labels Subsection -->
                  <div class="subsection-label">
                    Beschriftungen
                  </div>
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.backButton"
                    label="Zurück-Button"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.service"
                    label="Dienst"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.bookNow"
                    label="Jetzt buchen"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.free"
                    label="Gratis"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.multipleLocations"
                    label="Mehrere Standorte"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.viewAllPhotos"
                    label="Alle Fotos ansehen"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.aboutService"
                    label="Über die Dienstleistung"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.employees"
                    label="Mitarbeiter"
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.vat"
                    label="Mwst."
                  />
                  <MultilingualLabelInput
                    v-model="currentTemplate.sections.servicesOverview.labels.inclVat"
                    label="Inkl. Mwst."
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Live Preview (60%) -->
        <div class="customize-preview">
          <div class="preview-toolbar">
            <div class="preview-toolbar-title">
              Live-Vorschau
            </div>
            <div class="preview-toolbar-actions">
              <button
                class="preview-device-btn"
                :class="{ active: previewDevice === 'desktop' }"
                title="Desktop"
                @click="previewDevice = 'desktop'"
              >
                🖥️
              </button>
              <button
                class="preview-device-btn"
                :class="{ active: previewDevice === 'tablet' }"
                title="Tablet"
                @click="previewDevice = 'tablet'"
              >
                📱
              </button>
              <button
                class="preview-device-btn"
                :class="{ active: previewDevice === 'mobile' }"
                title="Mobile"
                @click="previewDevice = 'mobile'"
              >
                📱
              </button>
            </div>
          </div>
          <div
            class="preview-container"
            :class="`preview-device-${previewDevice}`"
          >
            <component
              :is="previewComponent"
              :template="currentTemplate"
              :css-variables="cssVariables"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type {
  DesignTemplate,
  TemplateCategory,
  CategoryCard,
} from './types'
import {
  getDefaultGlobalSettings,
  getDefaultCategoriesSection,
  getDefaultServicesOverviewSection,
} from './types'
import ServiceCatalogPreview from './previews/ServiceCatalogPreview.vue'
import EventListPreview from './previews/EventListPreview.vue'
import CustomerPanelPreview from './previews/CustomerPanelPreview.vue'
import EmployeePanelPreview from './previews/EmployeePanelPreview.vue'
import MultilingualLabelInput from './MultilingualLabelInput.vue'

// Navigation State
type NavigationLevel = 'category' | 'templates' | 'customize'
const currentLevel = ref<NavigationLevel>('category')
const selectedCategory = ref<TemplateCategory | null>(null)
const currentTemplate = ref<DesignTemplate | null>(null)

// Category Cards for Level 1
const categories = ref<CategoryCard[]>([
  {
    value: 'service_catalog',
    label: 'Katalog Buchungsformular',
    description: 'Für Dienstleistungskataloge mit Kategorien und Service-Auswahl',
    icon: '📋',
  },
  {
    value: 'event_list',
    label: 'Veranstaltungsliste Buchungsformular',
    description: 'Für Events, Kurse und terminbasierte Angebote',
    icon: '📅',
    badge: 'Neu',
  },
  {
    value: 'step_by_step',
    label: 'Schritt-für-Schritt-Buchungsformular',
    description: 'Geführte Buchung mit mehreren Schritten',
    icon: '🎯',
  },
  {
    value: 'customer_portal',
    label: 'Kundenportal',
    description: 'Für Kundenbereiche mit Buchungshistorie und Profilverwaltung',
    icon: '👤',
  },
  {
    value: 'employee_portal',
    label: 'Mitarbeiterportal',
    description: 'Für Mitarbeiterbereiche mit Terminverwaltung',
    icon: '👥',
  },
])

// Templates State
const templates = ref<DesignTemplate[]>([])

// UI State
const openAccordions = ref<Record<string, boolean>>({
  typography: true,
  colors: false,
  borders: false,
  categories: false,
  servicesOverview: false,
})
const previewDevice = ref<'desktop' | 'tablet' | 'mobile'>('desktop')

// Computed
const filteredTemplates = computed(() => {
  if (!selectedCategory.value) return []
  return templates.value.filter(t => t.category === selectedCategory.value)
})

const previewComponent = computed(() => {
  if (!currentTemplate.value) return null

  const components: Record<string, any> = {
    service_catalog: ServiceCatalogPreview,
    event_list: EventListPreview,
    step_by_step: ServiceCatalogPreview, // TODO: Create StepByStepPreview
    customer_portal: CustomerPanelPreview,
    employee_portal: EmployeePanelPreview,
  }

  return components[currentTemplate.value.category] || ServiceCatalogPreview
})

const cssVariables = computed(() => {
  if (!currentTemplate.value) return {}

  const { globalSettings } = currentTemplate.value
  const { typography, colors, borders } = globalSettings

  return {
    '--bookando-font-family': typography.fontFamily,
    '--bookando-font-size': `${typography.fontSize}px`,
    '--bookando-border-width': `${borders.width}px`,
    '--bookando-border-radius': `${borders.radius}px`,
    '--bookando-primary': colors.primary,
    '--bookando-success': colors.success,
    '--bookando-warning': colors.warning,
    '--bookando-error': colors.error,
    '--bookando-sidebar-bg': colors.sidebar.background,
    '--bookando-sidebar-text': colors.sidebar.text,
    '--bookando-content-bg': colors.content.background,
    '--bookando-content-heading': colors.content.heading,
    '--bookando-content-text': colors.content.text,
    '--bookando-input-bg': colors.formElements.input?.background || '#FFFFFF',
    '--bookando-input-text': colors.formElements.input?.text || '#354052',
    '--bookando-input-border': colors.formElements.input?.border || '#E2E6EC',
    '--bookando-btn-primary-bg': colors.buttons.primary.background,
    '--bookando-btn-primary-text': colors.buttons.primary.text,
    '--bookando-btn-secondary-bg': colors.buttons.secondary.background,
    '--bookando-btn-secondary-text': colors.buttons.secondary.text,
  }
})

// Methods
const selectCategory = (category: TemplateCategory) => {
  selectedCategory.value = category
  currentLevel.value = 'templates'
}

const backToCategories = () => {
  currentLevel.value = 'category'
  selectedCategory.value = null
}

const backToTemplates = () => {
  currentLevel.value = 'templates'
  currentTemplate.value = null
}

const createNewTemplate = () => {
  if (!selectedCategory.value) return

  currentTemplate.value = {
    id: null,
    name: 'Neue Vorlage',
    category: selectedCategory.value,
    globalSettings: getDefaultGlobalSettings(),
    sections: {
      categories: getDefaultCategoriesSection(),
      servicesOverview: getDefaultServicesOverviewSection(),
    },
  }
  currentLevel.value = 'customize'
}

const editTemplate = (template: DesignTemplate) => {
  currentTemplate.value = JSON.parse(JSON.stringify(template)) // Deep clone
  currentLevel.value = 'customize'
}

const duplicateTemplate = (template: DesignTemplate) => {
  const duplicated: DesignTemplate = {
    ...JSON.parse(JSON.stringify(template)),
    id: null,
    name: `${template.name} (Kopie)`,
  }
  templates.value.push(duplicated)
}

const deleteTemplate = (id: number | null) => {
  if (!id) return
  if (confirm('Möchten Sie diese Vorlage wirklich löschen?')) {
    templates.value = templates.value.filter(t => t.id !== id)
  }
}

const resetTemplate = () => {
  if (!currentTemplate.value) return
  if (confirm('Möchten Sie alle Einstellungen zurücksetzen?')) {
    currentTemplate.value.globalSettings = getDefaultGlobalSettings()
    if (currentTemplate.value.sections) {
      currentTemplate.value.sections.categories = getDefaultCategoriesSection()
      currentTemplate.value.sections.servicesOverview = getDefaultServicesOverviewSection()
    }
  }
}

const saveTemplate = () => {
  if (!currentTemplate.value) return

  // TODO: API Call zum Speichern

  // Update or add template
  if (currentTemplate.value.id) {
    const index = templates.value.findIndex(t => t.id === currentTemplate.value!.id)
    if (index !== -1) {
      templates.value[index] = { ...currentTemplate.value, updatedAt: new Date().toISOString() }
    }
  } else {
    const newId = Math.max(0, ...templates.value.map(t => t.id || 0)) + 1
    templates.value.push({
      ...currentTemplate.value,
      id: newId,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    })
  }

  alert('Vorlage erfolgreich gespeichert!')
  backToTemplates()
}

const toggleAccordion = (key: string) => {
  openAccordions.value[key] = !openAccordions.value[key]
}

const getCategoryLabel = (category: TemplateCategory | null) => {
  if (!category) return ''
  const found = categories.value.find(c => c.value === category)
  return found?.label || category
}

const formatDate = (dateString?: string) => {
  if (!dateString) return 'Neu'
  const date = new Date(dateString)
  return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

// Load templates on mount
onMounted(() => {
  // TODO: Load from API
  templates.value = [
    {
      id: 1,
      name: 'Standard Katalog',
      category: 'service_catalog',
      globalSettings: getDefaultGlobalSettings(),
      sections: {
        categories: getDefaultCategoriesSection(),
        servicesOverview: getDefaultServicesOverviewSection(),
      },
      createdAt: '2025-01-10T10:00:00Z',
      updatedAt: '2025-01-12T14:30:00Z',
    },
    {
      id: 2,
      name: 'Events Sommer 2025',
      category: 'event_list',
      globalSettings: getDefaultGlobalSettings(),
      sections: {},
      createdAt: '2025-01-11T09:00:00Z',
      updatedAt: '2025-01-11T09:00:00Z',
    },
  ]
})
</script>

<style lang="scss">
@use '@scss/variables' as *;

.bookando-tools-design {
  min-height: 100vh;
  background: $bookando-white;

  // Level Container
  .design-level {
    padding: $bookando-spacing-3xl;
  }

  // Level Header
  .level-header {
    margin-bottom: $bookando-spacing-3xl;

    h1 {
      margin: 0 0 $bookando-spacing-sm 0;
      font-size: 32px;
      font-weight: 600;
      color: $bookando-text-primary;
    }

    h2 {
      margin: 0 0 $bookando-spacing-xs 0;
      font-size: 24px;
      font-weight: 600;
      color: $bookando-text-primary;
    }

    p {
      margin: 0;
      color: $bookando-text-secondary;
      font-size: 15px;
    }

    &.level-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: $bookando-spacing-lg;

      .level-header-title {
        flex: 1;
      }
    }
  }

  // === LEVEL 1: CATEGORY GRID ===
  .design-level-category {
    .category-card {
      position: relative;
      padding: $bookando-spacing-3xl;
      text-align: center;
      cursor: pointer;
      transition: transform 0.2s;

      &:hover {
        transform: translateY(-4px);
      }

      .category-card-icon {
        font-size: 48px;
        margin-bottom: $bookando-spacing-md;
      }

      h3 {
        margin: 0 0 $bookando-spacing-sm 0;
        font-size: 18px;
        font-weight: 600;
        color: $bookando-text-primary;
      }

      p {
        margin: 0;
        font-size: 14px;
        color: $bookando-text-secondary;
        line-height: 1.5;
      }

      .category-badge {
        position: absolute;
        top: $bookando-spacing-md;
        right: $bookando-spacing-md;
        padding: 4px 12px;
        background: $bookando-primary;
        color: $bookando-white;
        font-size: 12px;
        font-weight: 600;
        border-radius: $bookando-radius-sm;
      }
    }
  }

  // === LEVEL 2: TEMPLATE LIST ===
  .design-level-templates {
    .template-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: $bookando-spacing-lg;
    }

    .template-card {
      padding: $bookando-spacing-lg;
      display: flex;
      flex-direction: column;
      gap: $bookando-spacing-md;

      .template-card-header {
        h3 {
          margin: 0 0 $bookando-spacing-xs 0;
          font-size: 16px;
          font-weight: 600;
          color: $bookando-text-primary;
        }

        .template-meta {
          font-size: 13px;
          color: $bookando-text-secondary;
        }
      }

      .template-card-preview {
        height: 180px;
        background: $bookando-gray-100;
        border-radius: $bookando-radius-sm;
        display: flex;
        align-items: center;
        justify-content: center;

        .preview-placeholder {
          color: $bookando-text-secondary;
          font-size: 14px;
        }
      }

      .template-card-actions {
        display: flex;
        gap: $bookando-spacing-xs;
        align-items: center;

        button:first-child {
          flex: 1;
        }
      }
    }

    .empty-state {
      text-align: center;
      padding: $bookando-spacing-4xl 0;

      .empty-state-icon {
        font-size: 64px;
        margin-bottom: $bookando-spacing-lg;
      }

      h3 {
        margin: 0 0 $bookando-spacing-sm 0;
        font-size: 20px;
        font-weight: 600;
        color: $bookando-text-primary;
      }

      p {
        margin: 0 0 $bookando-spacing-xl 0;
        color: $bookando-text-secondary;
      }
    }
  }

  // === LEVEL 3: CUSTOMIZATION SPLIT-VIEW ===
  .design-level-customize {
    padding: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
  }

  .customize-header {
    display: flex;
    align-items: center;
    gap: $bookando-spacing-lg;
    padding: $bookando-spacing-lg $bookando-spacing-xl;
    border-bottom: 1px solid $bookando-gray-200;
    background: $bookando-white;

    .customize-header-title {
      flex: 1;

      .template-name-input {
        width: 100%;
        padding: $bookando-spacing-xs $bookando-spacing-sm;
        font-size: 18px;
        font-weight: 600;
        border: 1px solid transparent;
        border-radius: $bookando-radius-sm;
        color: $bookando-text-primary;
        transition: border-color 0.2s;

        &:hover {
          border-color: $bookando-gray-300;
        }

        &:focus {
          outline: none;
          border-color: $bookando-primary;
        }
      }
    }

    .customize-header-actions {
      display: flex;
      gap: $bookando-spacing-sm;
    }
  }

  .customize-split-view {
    display: flex;
    flex: 1;
    overflow: hidden;
  }

  // Settings Sidebar (40%)
  .customize-sidebar {
    width: 40%;
    min-width: 400px;
    max-width: 600px;
    background: $bookando-gray-100;
    border-right: 1px solid $bookando-gray-200;
    overflow-y: auto;

    .sidebar-scroll {
      padding: $bookando-spacing-xl;
    }

    .settings-section {
      margin-bottom: $bookando-spacing-3xl;

      .section-title {
        margin: 0 0 $bookando-spacing-lg 0;
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: $bookando-text-secondary;
      }
    }

    .setting-group {
      background: $bookando-white;
      border: 1px solid $bookando-gray-200;
      border-radius: $bookando-radius-sm;
      margin-bottom: $bookando-spacing-md;
      overflow: hidden;

      .setting-group-header {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: $bookando-spacing-md $bookando-spacing-lg;
        background: $bookando-white;
        border: none;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
        color: $bookando-text-primary;
        transition: background 0.2s;

        &:hover {
          background: $bookando-gray-100;
        }

        .accordion-icon {
          font-size: 18px;
          font-weight: bold;
          color: $bookando-text-secondary;
        }
      }

      .setting-group-body {
        padding: $bookando-spacing-md $bookando-spacing-lg;
        border-top: 1px solid $bookando-gray-200;
      }
    }

    .setting-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: $bookando-spacing-md;

      &:last-child {
        margin-bottom: 0;
      }

      label {
        font-size: 14px;
        color: $bookando-text-secondary;
        font-weight: 500;
      }

      input[type="color"] {
        width: 60px;
        height: 36px;
        border: 1px solid $bookando-gray-300;
        border-radius: $bookando-radius-sm;
        cursor: pointer;
        background: $bookando-white;

        &::-webkit-color-swatch-wrapper {
          padding: 2px;
        }

        &::-webkit-color-swatch {
          border: none;
          border-radius: calc($bookando-radius-sm - 2px);
        }
      }

      input[type="number"],
      select {
        padding: 8px 12px;
        border: 1px solid $bookando-gray-300;
        border-radius: $bookando-radius-sm;
        background: $bookando-white;
        color: $bookando-text-primary;
        font-size: 14px;
        min-width: 120px;

        &:focus {
          outline: none;
          border-color: $bookando-primary;
        }
      }

      input[type="number"] {
        width: 80px;
      }

      &.setting-item-toggle {
        input[type="checkbox"] {
          // liquid-toggle styles from Core Design
        }
      }
    }

    .color-subsection {
      padding: $bookando-spacing-md 0;
      border-top: 1px solid $bookando-gray-100;

      &:first-child {
        border-top: none;
        padding-top: 0;
      }

      .subsection-label {
        font-size: 13px;
        font-weight: 600;
        color: $bookando-text-secondary;
        margin-bottom: $bookando-spacing-sm;
        text-transform: uppercase;
        letter-spacing: 0.3px;
      }
    }

    .button-colors-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: $bookando-spacing-lg;

      .button-color-group {
        padding: $bookando-spacing-md;
        background: $bookando-gray-100;
        border-radius: $bookando-radius-sm;

        .button-group-label {
          display: block;
          font-size: 12px;
          font-weight: 600;
          color: $bookando-text-secondary;
          margin-bottom: $bookando-spacing-sm;
          text-transform: uppercase;
        }

        .setting-item {
          margin-bottom: $bookando-spacing-sm;

          input[type="color"] {
            width: 50px;
            height: 32px;
          }
        }
      }
    }
  }

  // Live Preview (60%)
  .customize-preview {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: $bookando-gray-100;
  }

  .preview-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: $bookando-spacing-md $bookando-spacing-xl;
    background: $bookando-white;
    border-bottom: 1px solid $bookando-gray-200;

    .preview-toolbar-title {
      font-size: 14px;
      font-weight: 600;
      color: $bookando-text-primary;
    }

    .preview-toolbar-actions {
      display: flex;
      gap: $bookando-spacing-xs;
    }

    .preview-device-btn {
      padding: $bookando-spacing-xs $bookando-spacing-sm;
      background: transparent;
      border: 1px solid $bookando-gray-300;
      border-radius: $bookando-radius-sm;
      cursor: pointer;
      font-size: 18px;
      transition: all 0.2s;

      &:hover {
        background: $bookando-gray-100;
      }

      &.active {
        background: $bookando-primary;
        border-color: $bookando-primary;
      }
    }
  }

  .preview-container {
    flex: 1;
    overflow: auto;
    padding: $bookando-spacing-3xl;
    display: flex;
    justify-content: center;
    align-items: flex-start;

    &.preview-device-desktop {
      max-width: 100%;
    }

    &.preview-device-tablet {
      max-width: 768px;
      margin: 0 auto;
    }

    &.preview-device-mobile {
      max-width: 375px;
      margin: 0 auto;
    }
  }

  // Responsive
  @media (max-width: 1366px) {
    .customize-sidebar {
      width: 45%;
      min-width: 350px;
    }
  }

  @media (max-width: 1024px) {
    .customize-split-view {
      flex-direction: column;
    }

    .customize-sidebar {
      width: 100%;
      max-width: 100%;
      border-right: none;
      border-bottom: 1px solid $bookando-gray-200;
    }

    .customize-preview {
      min-height: 600px;
    }
  }
}
</style>
