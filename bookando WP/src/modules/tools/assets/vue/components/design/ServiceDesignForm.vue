<!-- ServiceDesignForm.vue - Waterfall/Drill-Down Form for Service Design -->
<template>
  <div class="service-design-form">
    <!-- Waterfall Container - shows multiple panels side by side -->
    <div
      class="waterfall-container"
      :style="waterfallStyle"
    >
      <!-- PANEL 0: Main Form -->
      <div class="waterfall-panel">
        <div class="panel-content">
          <!-- Name der Vorlage -->
          <div class="bookando-card bookando-mb-md">
            <div class="bookando-card-header">
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="edit-3" />
                <h3 class="bookando-m-0">
                  Name der Vorlage
                </h3>
              </div>
            </div>
            <div class="bookando-card-body">
              <BookandoField
                v-model="template.name"
                type="text"
                label="Name"
                placeholder="z.B. Standard Dienstleistung"
              />
            </div>
          </div>

          <!-- Globale Einstellungen -->
          <div class="section-header">
            <h4>Globale Einstellungen</h4>
          </div>

          <AppCard
            :hide-header="false"
            title="Globale Einstellungen"
            icon="settings"
            :hoverable="true"
            :clickable="true"
            rounded="sm"
            shadow="1"
            padding="md"
            class="bookando-mb-sm drill-down-card"
            @click="openPanel('global-settings', 0)"
          >
            <p class="bookando-text-muted bookando-m-0">
              Schriftarten, Farben und Rahmeneinstellungen
            </p>
            <template #actions>
              <AppIcon name="chevron-right" />
            </template>
          </AppCard>

          <!-- Abschnitte -->
          <div class="section-header">
            <h4>Abschnitte</h4>
          </div>

          <!-- Kategorien -->
          <AppCard
            :hide-header="false"
            title="Kategorien"
            icon="grid"
            :hoverable="true"
            :clickable="true"
            rounded="sm"
            shadow="1"
            padding="md"
            class="bookando-mb-sm drill-down-card"
            @click="openPanel('categories', 0)"
          >
            <p class="bookando-text-muted bookando-m-0">
              Konfiguriere die Anzeige und das Design der Kategorien
            </p>
            <template #actions>
              <AppIcon name="chevron-right" />
            </template>
          </AppCard>

          <!-- Überblick der Dienstleistungen -->
          <AppCard
            :hide-header="false"
            title="Überblick der Dienstleistungen"
            icon="list"
            :hoverable="true"
            :clickable="true"
            rounded="sm"
            shadow="1"
            padding="md"
            class="bookando-mb-sm drill-down-card"
            @click="openPanel('services-overview', 0)"
          >
            <p class="bookando-text-muted bookando-m-0">
              Anzeige und Filteroptionen für die Dienstleistungsliste
            </p>
            <template #actions>
              <AppIcon name="chevron-right" />
            </template>
          </AppCard>

          <!-- Dienstleistungsdetails -->
          <AppCard
            :hide-header="false"
            title="Dienstleistungsdetails"
            icon="file-text"
            :hoverable="true"
            :clickable="true"
            rounded="sm"
            shadow="1"
            padding="md"
            class="bookando-mb-sm drill-down-card"
            @click="openPanel('service-details', 0)"
          >
            <p class="bookando-text-muted bookando-m-0">
              Detailansicht einer einzelnen Dienstleistung
            </p>
            <template #actions>
              <AppIcon name="chevron-right" />
            </template>
          </AppCard>

          <!-- Paketdetails -->
          <AppCard
            :hide-header="false"
            title="Paketdetails"
            icon="package"
            :hoverable="true"
            :clickable="true"
            rounded="sm"
            shadow="1"
            padding="md"
            class="bookando-mb-sm drill-down-card"
            @click="openPanel('package-details', 0)"
          >
            <p class="bookando-text-muted bookando-m-0">
              Anzeige und Konfiguration von Dienstleistungspaketen
            </p>
            <template #actions>
              <AppIcon name="chevron-right" />
            </template>
          </AppCard>
        </div>
      </div>

      <!-- PANEL 1: Globale Einstellungen -->
      <div
        v-if="panelStack[0] === 'global-settings'"
        class="waterfall-panel"
      >
        <div class="panel-header">
          <AppButton
            icon="arrow-left"
            btn-type="icononly"
            variant="standard"
            size="square"
            @click="closePanel(0)"
          />
          <h3>Globale Einstellungen</h3>
        </div>

        <div class="panel-content">
          <!-- Schriftarten -->
          <div class="bookando-card bookando-mb-md">
            <div class="bookando-card-header">
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="type" />
                <h3 class="bookando-m-0">
                  Schriftarten
                </h3>
              </div>
            </div>
            <div class="bookando-card-body">
              <BookandoField
                v-model="template.globalSettings.fontFamily"
                type="dropdown"
                label="Schriftart"
                :options="fontOptions"
                option-label="label"
                option-value="value"
                mode="basic"
              />
            </div>
          </div>

          <!-- Farben (Clickable Card) -->
          <AppCard
            :hide-header="false"
            title="Farben"
            icon="palette"
            :hoverable="true"
            :clickable="true"
            rounded="sm"
            shadow="1"
            padding="md"
            class="bookando-mb-sm drill-down-card"
            @click="openPanel('colors', 1)"
          >
            <p class="bookando-text-muted bookando-m-0">
              Alle Farbeinstellungen für die Dienstleistungen
            </p>
            <template #actions>
              <AppIcon name="chevron-right" />
            </template>
          </AppCard>

          <!-- Rahmen -->
          <div class="bookando-card bookando-mb-md">
            <div class="bookando-card-header">
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="square" />
                <h3 class="bookando-m-0">
                  Rahmen
                </h3>
              </div>
            </div>
            <div class="bookando-card-body">
              <AppRangeInput
                v-model="template.globalSettings.border.width"
                label="Rahmenbreite (px)"
                :min="0"
                :max="10"
                :step="1"
              />
              <AppRangeInput
                v-model="template.globalSettings.border.radius"
                label="Eckenradius (px)"
                :min="0"
                :max="50"
                :step="1"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- PANEL 2: Farben (Nested) -->
      <div
        v-if="panelStack[1] === 'colors'"
        class="waterfall-panel"
      >
        <div class="panel-header">
          <AppButton
            icon="arrow-left"
            btn-type="icononly"
            variant="standard"
            size="square"
            @click="closePanel(1)"
          />
          <h3>Farben</h3>
        </div>

        <div class="panel-content">
          <!-- Primär- und Zustandsfarben (Accordion) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('colors-primary')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="droplet" />
                <h3 class="bookando-m-0">
                  Primär- und Zustandsfarben
                </h3>
              </div>
              <AppIcon :name="openSections['colors-primary'] ? 'chevron-up' : 'chevron-down'" />
            </div>
            <div
              v-show="openSections['colors-primary']"
              class="bookando-card-body"
            >
              <div class="color-field">
                <label>Primärfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.primary" />
              </div>
              <div class="color-field">
                <label>Erfolg Farbe</label>
                <AppColorInput v-model="template.globalSettings.colors.success" />
              </div>
              <div class="color-field">
                <label>Warnung Farbe</label>
                <AppColorInput v-model="template.globalSettings.colors.warning" />
              </div>
              <div class="color-field">
                <label>Fehlerfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.error" />
              </div>
            </div>
          </div>

          <!-- Seitenleiste (Accordion) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('colors-sidebar')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="sidebar" />
                <h3 class="bookando-m-0">
                  Seitenleiste
                </h3>
              </div>
              <AppIcon :name="openSections['colors-sidebar'] ? 'chevron-up' : 'chevron-down'" />
            </div>
            <div
              v-show="openSections['colors-sidebar']"
              class="bookando-card-body"
            >
              <div class="color-field">
                <label>Hintergrundfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.sidebar.background" />
              </div>
              <div class="color-field">
                <label>Textfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.sidebar.text" />
              </div>
            </div>
          </div>

          <!-- Inhalt (Accordion) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('colors-content')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="file-text" />
                <h3 class="bookando-m-0">
                  Inhalt
                </h3>
              </div>
              <AppIcon :name="openSections['colors-content'] ? 'chevron-up' : 'chevron-down'" />
            </div>
            <div
              v-show="openSections['colors-content']"
              class="bookando-card-body"
            >
              <div class="color-field">
                <label>Hintergrundfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.content.background" />
              </div>
              <div class="color-field">
                <label>Textfarbe für Überschrift</label>
                <AppColorInput v-model="template.globalSettings.colors.content.heading" />
              </div>
              <div class="color-field">
                <label>Textfarbe des Inhalts</label>
                <AppColorInput v-model="template.globalSettings.colors.content.text" />
              </div>
            </div>
          </div>

          <!-- Eingabefelder (Accordion) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('colors-input')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="edit-3" />
                <h3 class="bookando-m-0">
                  Eingabefelder
                </h3>
              </div>
              <AppIcon :name="openSections['colors-input'] ? 'chevron-up' : 'chevron-down'" />
            </div>
            <div
              v-show="openSections['colors-input']"
              class="bookando-card-body"
            >
              <div class="color-field">
                <label>Hintergrundfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.input.background" />
              </div>
              <div class="color-field">
                <label>Rahmenfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.input.border" />
              </div>
              <div class="color-field">
                <label>Textfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.input.text" />
              </div>
              <div class="color-field">
                <label>Platzhalter Farbe</label>
                <AppColorInput v-model="template.globalSettings.colors.input.placeholder" />
              </div>
            </div>
          </div>

          <!-- Dropdowns (Accordion) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('colors-dropdown')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="chevron-down" />
                <h3 class="bookando-m-0">
                  Dropdowns
                </h3>
              </div>
              <AppIcon :name="openSections['colors-dropdown'] ? 'chevron-up' : 'chevron-down'" />
            </div>
            <div
              v-show="openSections['colors-dropdown']"
              class="bookando-card-body"
            >
              <div class="color-field">
                <label>Hintergrundfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.dropdown.background" />
              </div>
              <div class="color-field">
                <label>Rahmenfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.dropdown.border" />
              </div>
              <div class="color-field">
                <label>Textfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.dropdown.text" />
              </div>
            </div>
          </div>

          <!-- Karten (Accordion) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('colors-card')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="credit-card" />
                <h3 class="bookando-m-0">
                  Karten
                </h3>
              </div>
              <AppIcon :name="openSections['colors-card'] ? 'chevron-up' : 'chevron-down'" />
            </div>
            <div
              v-show="openSections['colors-card']"
              class="bookando-card-body"
            >
              <div class="color-field">
                <label>Hintergrundfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.card.background" />
              </div>
              <div class="color-field">
                <label>Rahmenfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.card.border" />
              </div>
              <div class="color-field">
                <label>Textfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.card.text" />
              </div>
            </div>
          </div>

          <!-- Buttons (Accordion) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('colors-buttons')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="square" />
                <h3 class="bookando-m-0">
                  Buttons
                </h3>
              </div>
              <AppIcon :name="openSections['colors-buttons'] ? 'chevron-up' : 'chevron-down'" />
            </div>
            <div
              v-show="openSections['colors-buttons']"
              class="bookando-card-body"
            >
              <!-- Primary Button -->
              <h5 class="subsection-title">
                Primäre Schaltfläche
              </h5>
              <div class="color-field">
                <label>Hintergrundfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.buttons.primary.background" />
              </div>
              <div class="color-field">
                <label>Textfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.buttons.primary.text" />
              </div>
              <BookandoField
                v-model="template.globalSettings.colors.buttons.primary.padding"
                type="number"
                label="Padding (px)"
                :min="0"
                :max="50"
              />
              <div class="color-field">
                <label>Rahmenfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.buttons.primary.borderColor" />
              </div>
              <BookandoField
                v-model="template.globalSettings.colors.buttons.primary.borderWidth"
                type="number"
                label="Rahmenbreite (px)"
                :min="0"
                :max="10"
              />
              <BookandoField
                v-model="template.globalSettings.colors.buttons.primary.borderRadius"
                type="number"
                label="Eckenradius (px)"
                :min="0"
                :max="50"
              />

              <!-- Secondary Button -->
              <h5 class="subsection-title">
                Sekundäre Schaltfläche
              </h5>
              <div class="color-field">
                <label>Hintergrundfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.buttons.secondary.background" />
              </div>
              <div class="color-field">
                <label>Textfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.buttons.secondary.text" />
              </div>
              <BookandoField
                v-model="template.globalSettings.colors.buttons.secondary.padding"
                type="number"
                label="Padding (px)"
                :min="0"
                :max="50"
              />
              <div class="color-field">
                <label>Rahmenfarbe</label>
                <AppColorInput v-model="template.globalSettings.colors.buttons.secondary.borderColor" />
              </div>
              <BookandoField
                v-model="template.globalSettings.colors.buttons.secondary.borderWidth"
                type="number"
                label="Rahmenbreite (px)"
                :min="0"
                :max="10"
              />
              <BookandoField
                v-model="template.globalSettings.colors.buttons.secondary.borderRadius"
                type="number"
                label="Eckenradius (px)"
                :min="0"
                :max="50"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- PANEL 3: Kategorien -->
      <div
        v-if="panelStack[0] === 'categories'"
        class="waterfall-panel"
      >
        <div class="panel-header">
          <AppButton
            icon="arrow-left"
            btn-type="icononly"
            variant="standard"
            size="square"
            @click="closePanel(0)"
          />
          <h3>Kategorien</h3>
        </div>

        <div class="panel-content">
          <!-- Optionen (Expandable) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('categories-options')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="sliders" />
                <h3 class="bookando-m-0">
                  Optionen
                </h3>
              </div>
              <AppIcon :name="openSections['categories-options'] ? 'chevron-up' : 'chevron-down'" />
            </div>

            <div
              v-show="openSections['categories-options']"
              class="bookando-card-body bookando-p-0"
            >
              <BookandoField
                v-model="template.categories.options.showCardSideColor"
                label="Kartenseitenfarbe anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.categories.options.showAccentColor"
                label="Akzentfarbe anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.categories.options.showTotalCount"
                label="Gesamtanzahl Dienstleistungen anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
            </div>
          </div>

          <!-- Beschriftung (Expandable) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('categories-labels')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="type" />
                <h3 class="bookando-m-0">
                  Beschriftung
                </h3>
              </div>
              <AppIcon :name="openSections['categories-labels'] ? 'chevron-up' : 'chevron-down'" />
            </div>

            <div
              v-show="openSections['categories-labels']"
              class="bookando-card-body"
            >
              <h5 class="subsection-title">
                Primäre Schaltfläche
              </h5>
              <BookandoField
                v-model="template.categories.labels.primaryButton.default"
                type="text"
                label="Standard"
                placeholder="z.B. Weiter"
              />
              <BookandoField
                v-model="template.categories.labels.primaryButton.de"
                type="text"
                label="Deutsch"
                placeholder="z.B. Weiter"
              />
              <BookandoField
                v-model="template.categories.labels.primaryButton.en"
                type="text"
                label="Englisch"
                placeholder="e.g. Continue"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- PANEL 4: Überblick der Dienstleistungen -->
      <div
        v-if="panelStack[0] === 'services-overview'"
        class="waterfall-panel"
      >
        <div class="panel-header">
          <AppButton
            icon="arrow-left"
            btn-type="icononly"
            variant="standard"
            size="square"
            @click="closePanel(0)"
          />
          <h3>Überblick der Dienstleistungen</h3>
        </div>

        <div class="panel-content">
          <!-- Optionen (Expandable) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('services-options')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="eye" />
                <h3 class="bookando-m-0">
                  Optionen
                </h3>
              </div>
              <AppIcon :name="openSections['services-options'] ? 'chevron-up' : 'chevron-down'" />
            </div>

            <div
              v-show="openSections['services-options']"
              class="bookando-card-body bookando-p-0"
            >
              <BookandoField
                v-model="template.servicesOverview.options.showBackground"
                label="Hintergrund anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showSearch"
                label="&quot;Suche&quot; anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showEmployeeFilter"
                label="&quot;Nach Mitarbeiter filtern&quot; anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showLocationFilter"
                label="&quot;Nach Standort filtern&quot; anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showCategorySidebar"
                label="Seitenleiste für Kategorien anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showCardColor"
                label="Kartenfarbe anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showVat"
                label="MwSt. Sichtbarkeit anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showBadge"
                label="Dienstleistung Badge anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showPrice"
                label="Preis der Dienstleistung anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showActionButton"
                label="Aktionsbutton anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showCategory"
                label="Dienstleistungskategorie anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showDuration"
                label="Dauer der Dienstleistung anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showCapacity"
                label="Dienstleistungskapazität anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showLocation"
                label="Ort der Dienstleistung anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
              <BookandoField
                v-model="template.servicesOverview.options.showEmployeesButton"
                label="Schaltfläche &quot;Mitarbeiter anzeigen&quot; anzeigen"
                type="toggle"
                :row="true"
                :classes="toggleFieldClasses"
              />
            </div>
          </div>

          <!-- Beschriftungen (Expandable) -->
          <div class="bookando-card bookando-mb-md">
            <div
              class="bookando-card-header"
              style="cursor: pointer;"
              @click="toggleSection('services-labels')"
            >
              <div class="bookando-flex bookando-items-center bookando-gap-sm">
                <AppIcon name="type" />
                <h3 class="bookando-m-0">
                  Beschriftungen
                </h3>
              </div>
              <AppIcon :name="openSections['services-labels'] ? 'chevron-up' : 'chevron-down'" />
            </div>

            <div
              v-show="openSections['services-labels']"
              class="bookando-card-body"
            >
              <!-- Seiten Header - Zurück -->
              <h5 class="subsection-title">
                Seiten Header - Zurück
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.back.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Zurück"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.back.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Zurück"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.back.en"
                type="text"
                label="English (UK)"
                placeholder="Back"
              />

              <!-- Dienstleistung -->
              <h5 class="subsection-title">
                Dienstleistung
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.service.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Dienstleistung"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.service.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Dienstleistung"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.service.en"
                type="text"
                label="English (UK)"
                placeholder="Service"
              />

              <!-- Jetzt buchen -->
              <h5 class="subsection-title">
                Jetzt buchen
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.bookNow.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Jetzt buchen"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.bookNow.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Jetzt buchen"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.bookNow.en"
                type="text"
                label="English (UK)"
                placeholder="Book now"
              />

              <!-- Gratis -->
              <h5 class="subsection-title">
                Gratis
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.free.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Gratis"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.free.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Gratis"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.free.en"
                type="text"
                label="English (UK)"
                placeholder="Free"
              />

              <!-- Mehrere Standorte -->
              <h5 class="subsection-title">
                Mehrere Standorte
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.multipleLocations.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Mehrere Standorte"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.multipleLocations.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Mehrere Standorte"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.multipleLocations.en"
                type="text"
                label="English (UK)"
                placeholder="Multiple locations"
              />

              <!-- Hauptinhalt - Alle Fotos ansehen -->
              <h5 class="subsection-title">
                Hauptinhalt - Alle Fotos ansehen
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.viewAllPhotos.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Alle Fotos ansehen"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.viewAllPhotos.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Alle Fotos ansehen"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.viewAllPhotos.en"
                type="text"
                label="English (UK)"
                placeholder="View all photos"
              />

              <!-- Über die Dienstleistung -->
              <h5 class="subsection-title">
                Über die Dienstleistung
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.aboutService.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Über die Dienstleistung"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.aboutService.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Über die Dienstleistung"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.aboutService.en"
                type="text"
                label="English (UK)"
                placeholder="About the service"
              />

              <!-- Mitarbeiter -->
              <h5 class="subsection-title">
                Mitarbeiter
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.employees.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Mitarbeiter"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.employees.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Mitarbeiter"
              />
              <BookandoField
                v-model="template.servicesOverview.labels.employees.en"
                type="text"
                label="English (UK)"
                placeholder="Employees"
              />

              <!-- MwSt. -->
              <h5 class="subsection-title">
                MwSt.
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.vat.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="MwSt."
              />
              <BookandoField
                v-model="template.servicesOverview.labels.vat.de"
                type="text"
                label="German (Switzerland)"
                placeholder="MwSt."
              />
              <BookandoField
                v-model="template.servicesOverview.labels.vat.en"
                type="text"
                label="English (UK)"
                placeholder="VAT"
              />

              <!-- Inkl. MwSt. -->
              <h5 class="subsection-title">
                Inkl. MwSt.
              </h5>
              <BookandoField
                v-model="template.servicesOverview.labels.inclVat.default"
                type="text"
                label="Standard Beschriftung"
                placeholder="Inkl. MwSt."
              />
              <BookandoField
                v-model="template.servicesOverview.labels.inclVat.de"
                type="text"
                label="German (Switzerland)"
                placeholder="Inkl. MwSt."
              />
              <BookandoField
                v-model="template.servicesOverview.labels.inclVat.en"
                type="text"
                label="English (UK)"
                placeholder="Incl. VAT"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- PANEL 5: Service Details (Placeholder) -->
      <div
        v-if="panelStack[0] === 'service-details'"
        class="waterfall-panel"
      >
        <div class="panel-header">
          <AppButton
            icon="arrow-left"
            btn-type="icononly"
            variant="standard"
            size="square"
            @click="closePanel(0)"
          />
          <h3>Dienstleistungsdetails</h3>
        </div>

        <div class="panel-content">
          <div class="bookando-card">
            <div class="bookando-card-body">
              <p class="bookando-text-muted">
                Konfiguration folgt...
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- PANEL 6: Package Details (Placeholder) -->
      <div
        v-if="panelStack[0] === 'package-details'"
        class="waterfall-panel"
      >
        <div class="panel-header">
          <AppButton
            icon="arrow-left"
            btn-type="icononly"
            variant="standard"
            size="square"
            @click="closePanel(0)"
          />
          <h3>Paketdetails</h3>
        </div>

        <div class="panel-content">
          <div class="bookando-card">
            <div class="bookando-card-body">
              <p class="bookando-text-muted">
                Konfiguration folgt...
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppCard from '@core/Design/components/AppCard.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppColorInput from '@core/Design/components/AppColorInput.vue'
import AppRangeInput from '@core/Design/components/AppRangeInput.vue'

const props = defineProps<{
  modelValue: any
}>()

const emit = defineEmits<{
  (event: 'update:modelValue', value: any): void
}>()

// Font Options
const fontOptions = [
  { label: 'Inter', value: 'Inter' },
  { label: 'Roboto', value: 'Roboto' },
  { label: 'Open Sans', value: 'Open Sans' },
  { label: 'Montserrat', value: 'Montserrat' }
]

// Local template state
const template = ref(props.modelValue || createDefaultTemplate())

// Toggle Field Classes (like SettingsRolesForm)
const toggleFieldClasses =
  'bookando-border bookando-rounded bookando-p-md bookando-mb-sm bookando-flex bookando-items-center bookando-justify-content-between'

// Panel Navigation - Multi-level stack
const panelStack = ref<(string | null)[]>([null, null, null])

// Open/Close States for expandable sections
const openSections = ref({
  'colors-primary': true,
  'colors-sidebar': false,
  'colors-content': false,
  'colors-input': false,
  'colors-dropdown': false,
  'colors-card': false,
  'colors-buttons': false,
  'categories-options': true,
  'categories-labels': false,
  'services-options': true,
  'services-labels': false
})

// Computed waterfall style
const waterfallStyle = computed(() => {
  const depth = panelStack.value.filter(p => p !== null).length
  return {
    transform: `translateX(-${depth * 50}%)`
  }
})

// Methods
function openPanel(panelName: string, level: number) {
  panelStack.value[level] = panelName
  // Clear deeper levels
  for (let i = level + 1; i < panelStack.value.length; i++) {
    panelStack.value[i] = null
  }
}

function closePanel(level: number) {
  panelStack.value[level] = null
  // Clear deeper levels
  for (let i = level + 1; i < panelStack.value.length; i++) {
    panelStack.value[i] = null
  }
}

function toggleSection(section: string) {
  openSections.value[section] = !openSections.value[section]
}

// Create default template structure
function createDefaultTemplate() {
  return {
    name: 'Neue Dienstleistungsvorlage',
    globalSettings: {
      fontFamily: 'Inter',
      colors: {
        primary: '#12DE9D',
        success: '#10B981',
        warning: '#F59E0B',
        error: '#EF4444',
        sidebar: {
          background: '#1F2937',
          text: '#F9FAFB'
        },
        content: {
          background: '#FFFFFF',
          heading: '#111827',
          text: '#4B5563'
        },
        input: {
          background: '#FFFFFF',
          border: '#D1D5DB',
          text: '#111827',
          placeholder: '#9CA3AF'
        },
        dropdown: {
          background: '#FFFFFF',
          border: '#D1D5DB',
          text: '#111827'
        },
        card: {
          background: '#FFFFFF',
          border: '#E5E7EB',
          text: '#111827'
        },
        buttons: {
          primary: {
            background: '#12DE9D',
            text: '#FFFFFF',
            padding: 12,
            borderColor: '#12DE9D',
            borderWidth: 1,
            borderRadius: 8
          },
          secondary: {
            background: '#F3F4F6',
            text: '#111827',
            padding: 12,
            borderColor: '#D1D5DB',
            borderWidth: 1,
            borderRadius: 8
          }
        }
      },
      border: {
        width: 1,
        radius: 8
      }
    },
    categories: {
      options: {
        showCardSideColor: true,
        showAccentColor: true,
        showTotalCount: true
      },
      labels: {
        primaryButton: {
          default: 'Weiter',
          de: 'Weiter',
          en: 'Continue'
        }
      }
    },
    servicesOverview: {
      options: {
        showBackground: true,
        showSearch: true,
        showEmployeeFilter: true,
        showLocationFilter: true,
        showCategorySidebar: true,
        showCardColor: true,
        showVat: false,
        showBadge: true,
        showPrice: true,
        showActionButton: true,
        showCategory: true,
        showDuration: true,
        showCapacity: true,
        showLocation: true,
        showEmployeesButton: true
      },
      labels: {
        back: { default: 'Zurück', de: 'Zurück', en: 'Back' },
        service: { default: 'Dienstleistung', de: 'Dienstleistung', en: 'Service' },
        bookNow: { default: 'Jetzt buchen', de: 'Jetzt buchen', en: 'Book now' },
        free: { default: 'Gratis', de: 'Gratis', en: 'Free' },
        multipleLocations: { default: 'Mehrere Standorte', de: 'Mehrere Standorte', en: 'Multiple locations' },
        viewAllPhotos: { default: 'Alle Fotos ansehen', de: 'Alle Fotos ansehen', en: 'View all photos' },
        aboutService: { default: 'Über die Dienstleistung', de: 'Über die Dienstleistung', en: 'About the service' },
        employees: { default: 'Mitarbeiter', de: 'Mitarbeiter', en: 'Employees' },
        vat: { default: 'MwSt.', de: 'MwSt.', en: 'VAT' },
        inclVat: { default: 'Inkl. MwSt.', de: 'Inkl. MwSt.', en: 'Incl. VAT' }
      }
    }
  }
}

// Watch template changes and emit to parent
import { watch } from 'vue'
watch(template, (newValue) => {
  emit('update:modelValue', newValue)
}, { deep: true })
</script>

<style scoped lang="scss">
@use '@core/Design/assets/scss/variables' as *;

.service-design-form {
  height: 100%;
  overflow: hidden;
  position: relative;
}

.waterfall-container {
  display: flex;
  height: 100%;
  transition: transform 0.3s ease-in-out;
  width: 300%; // Support for 3 levels
}

.waterfall-panel {
  min-width: 33.333%;
  max-width: 33.333%;
  height: 100%;
  display: flex;
  flex-direction: column;
  background: $bookando-white;
}

.panel-header {
  display: flex;
  align-items: center;
  gap: $bookando-spacing-md;
  padding: $bookando-spacing-md;
  border-bottom: 1px solid $bookando-border-light;
  background: $bookando-bg-soft;

  h3 {
    margin: 0;
    font-size: $bookando-font-size-lg;
    font-weight: $bookando-font-weight-semi-bold;
    color: $bookando-text-dark;
  }
}

.panel-content {
  flex: 1;
  overflow-y: auto;
  padding: $bookando-spacing-md;
}

.section-header {
  margin: $bookando-spacing-lg 0 $bookando-spacing-md 0;

  h4 {
    margin: 0;
    font-size: $bookando-font-size-md;
    font-weight: $bookando-font-weight-semi-bold;
    color: $bookando-text-muted;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
}

.drill-down-card {
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

.subsection-title {
  margin: $bookando-spacing-md 0 $bookando-spacing-sm 0;
  font-size: $bookando-font-size-sm;
  font-weight: $bookando-font-weight-semi-bold;
  color: $bookando-text-dark;
  padding-top: $bookando-spacing-md;
  border-top: 1px solid $bookando-border-light;

  &:first-child {
    border-top: none;
    padding-top: 0;
    margin-top: 0;
  }
}

.color-field {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: $bookando-spacing-sm;

  label {
    flex: 1;
    margin: 0;
    font-size: $bookando-font-size-sm;
    color: $bookando-text-dark;
  }
}
</style>
