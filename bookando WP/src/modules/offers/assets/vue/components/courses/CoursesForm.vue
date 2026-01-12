<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="course-form-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <template #header>
        <h2 id="course-form-title">
          {{ form.id ? t('mod.offers.courses.edit') || 'Kurs bearbeiten' : t('mod.offers.courses.add') || 'Kurs anlegen' }}
        </h2>
        <AppButton
          icon="x"
          btn-type="icononly"
          variant="standard"
          size="square"
          icon-size="md"
          @click="onCancel"
        />
      </template>

      <template #tabs>
        <AppTabs
          v-model="tab"
          :tabs="tabItems"
          nav-only
        />
      </template>

      <template #default>
        <form
          :id="formId"
          class="bookando-form services-form courses-form"
          novalidate
          autocomplete="off"
          @submit.prevent="onSubmit"
        >
          <div v-if="tab === 'details'">
            <section class="services-form__panel">
              <AppServicesFormSection
                icon="book-open"
                :title="t('mod.offers.courses.details') || 'Kursdetails'"
                :description="t('mod.offers.courses.details_hint') || 'Beschreibe den Kurs und lege seine Eckdaten fest.'"
                :columns="2"
              >
                <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
                  <BookandoField
                    id="course_name"
                    v-model="form.name"
                    type="text"
                    :label="t('fields.name') || 'Name'"
                    required
                  />

                  <div class="courses-form__translate-action">
                    <AppButton
                      icon="globe"
                      size="sm"
                      variant="ghost"
                      type="button"
                    >
                      {{ t('ui.common.translate') || 'Übersetzen' }}
                    </AppButton>
                  </div>

                  <BookandoField
                    id="course_category"
                    v-model="form.category_id"
                    type="dropdown"
                    :label="t('mod.services.category.label') || 'Kategorie'"
                    :options="categories"
                    option-label="name"
                    option-value="id"
                    mode="basic"
                    clearable
                    searchable
                  />

                  <BookandoField
                    id="course_status"
                    v-model="form.status"
                    type="dropdown"
                    :label="t('fields.status') || 'Status'"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    mode="basic"
                  />

                  <BookandoField
                    id="course_tags"
                    v-model="form.tag_ids"
                    type="dropdown"
                    multiple
                    searchable
                    clearable
                    :label="t('mod.services.tags') || 'Schlagwörter'"
                    :options="tags"
                    option-label="name"
                    option-value="id"
                  />

                  <BookandoField
                    id="course_notify"
                    v-model="form.notify_participants"
                    type="toggle"
                    :label="t('mod.offers.courses.notify_participants') || 'Teilnehmer benachrichtigen'"
                    :row="true"
                  />
                </div>

                <AppServicesFormSection
                  class="services-form__column-span-all"
                  icon="calendar"
                  :title="t('mod.offers.courses.schedule') || 'Termine & Zeiten'"
                  :description="t('mod.offers.courses.schedule_hint') || 'Plane mehrere Termine und passe Buchungsfenster an.'"
                  layout="stack"
                  compact
                >
                  <div class="courses-form__sessions">
                    <article
                      v-for="session in form.sessions"
                      :key="session._localId"
                      class="courses-form__session"
                    >
                      <header class="courses-form__session-header">
                        <h4>
                          {{ t('mod.offers.courses.session') || 'Termin' }}
                          {{ sessionIndex(session) + 1 }}
                        </h4>
                        <div class="courses-form__session-actions">
                          <AppButton
                            v-if="form.sessions.length > 1"
                            icon="trash-2"
                            size="sm"
                            variant="ghost"
                            type="button"
                            @click="removeSession(session._localId)"
                          >
                            {{ t('core.common.remove') }}
                          </AppButton>
                        </div>
                      </header>

                      <div class="courses-form__session-grid">
                        <BookandoField
                          :id="`session-${session._localId}-date`"
                          v-model="session.date"
                          type="date"
                          :label="t('fields.date_range.start') || 'Datum'"
                          clearable
                        />
                        <BookandoField
                          :id="`session-${session._localId}-start`"
                          v-model="session.time_start"
                          type="time"
                          :label="t('fields.time.start') || 'Startzeit'"
                          clearable
                        />
                        <BookandoField
                          :id="`session-${session._localId}-end`"
                          v-model="session.time_end"
                          type="time"
                          :label="t('fields.time.end') || 'Endzeit'"
                          clearable
                        />
                      </div>
                    </article>
                  </div>

                  <div class="courses-form__sessions-add">
                    <AppButton
                      icon="plus"
                      variant="secondary"
                      size="sm"
                      type="button"
                      @click="addSession"
                    >
                      {{ t('mod.offers.courses.add_session') || 'Datum hinzufügen' }}
                    </AppButton>
                  </div>

                  <div class="courses-form__toggles">
                    <BookandoField
                      id="course_recurring"
                      v-model="form.is_recurring"
                      type="toggle"
                      :label="t('mod.offers.courses.recurring') || 'Wiederkehrender Kurs'"
                      :row="true"
                    />
                    <BookandoField
                      id="course_booking_immediate"
                      v-model="form.booking_starts_immediately"
                      type="toggle"
                      :label="t('mod.offers.courses.booking_starts_now') || 'Buchungen starten sofort'"
                      :row="true"
                    />
                  </div>

                  <div
                    v-if="!form.booking_starts_immediately"
                    class="courses-form__session-grid courses-form__session-grid--compact"
                  >
                    <BookandoField
                      id="course_booking_start_date"
                      v-model="form.booking_start_date"
                      type="date"
                      :label="t('mod.offers.courses.booking_start_date') || 'Startet am'"
                      clearable
                    />
                    <BookandoField
                      id="course_booking_start_time"
                      v-model="form.booking_start_time"
                      type="time"
                      :label="t('mod.offers.courses.booking_start_time') || 'Startzeit'"
                      clearable
                    />
                  </div>

                  <BookandoField
                    id="course_booking_auto_close"
                    v-model="form.booking_closes_on_start"
                    type="toggle"
                    :label="t('mod.offers.courses.booking_auto_close') || 'Buchung endet zum Kursstart'"
                    :row="true"
                  />

                  <div
                    v-if="!form.booking_closes_on_start"
                    class="courses-form__session-grid courses-form__session-grid--compact"
                  >
                    <BookandoField
                      id="course_booking_end_date"
                      v-model="form.booking_end_date"
                      type="date"
                      :label="t('mod.offers.courses.booking_end_date') || 'Schließt am'"
                      clearable
                    />
                    <BookandoField
                      id="course_booking_end_time"
                      v-model="form.booking_end_time"
                      type="time"
                      :label="t('mod.offers.courses.booking_end_time') || 'Endzeit'"
                      clearable
                    />
                  </div>
                </AppServicesFormSection>

                <AppServicesFormSection
                  class="services-form__column-span-all"
                  icon="map"
                  :title="t('mod.offers.courses.assignment') || 'Zuweisung'"
                  :description="t('mod.offers.courses.assignment_hint') || 'Lege Ort, Organisator:innen und Teams fest.'"
                  :columns="2"
                  compact
                >
                  <BookandoField
                    id="course_location"
                    v-model="form.location_id"
                    type="dropdown"
                    :label="t('mod.offers.courses.location') || 'Adresse'"
                    :options="locationOptions"
                    option-label="label"
                    option-value="value"
                    mode="basic"
                  />
                  <BookandoField
                    v-if="form.location_id === 'custom'"
                    id="course_location_custom"
                    v-model="form.custom_location"
                    type="text"
                    :label="t('mod.offers.courses.custom_location') || 'Benutzerdefinierte Adresse'"
                  />
                  <BookandoField
                    id="course_organizer"
                    v-model="form.organizer_id"
                    type="dropdown"
                    :label="t('mod.offers.courses.organizer') || 'Organisator'"
                    :options="organizerOptions"
                    option-label="label"
                    option-value="value"
                    mode="basic"
                    clearable
                  />
                  <BookandoField
                    id="course_team"
                    v-model="form.team_ids"
                    type="dropdown"
                    multiple
                    searchable
                    clearable
                    :label="t('mod.offers.courses.team') || 'Team'"
                    :options="teamOptions"
                    option-label="label"
                    option-value="value"
                  />
                </AppServicesFormSection>

                <AppServicesFormSection
                  class="services-form__column-span-all"
                  icon="align-left"
                  :title="t('fields.description') || 'Beschreibung'"
                  layout="stack"
                >
                  <div class="courses-form__description-toolbar">
                    <AppButton
                      size="sm"
                      variant="primary"
                      type="button"
                    >
                      {{ t('mod.offers.courses.text_mode') || 'Textmodus' }}
                    </AppButton>
                    <AppButton
                      size="sm"
                      variant="secondary"
                      type="button"
                    >
                      {{ t('mod.offers.courses.html_mode') || 'HTML-Modus' }}
                    </AppButton>
                    <AppButton
                      class="courses-form__translate-link"
                      icon="globe"
                      size="sm"
                      variant="ghost"
                      type="button"
                    >
                      {{ t('ui.common.translate') || 'Übersetzen' }}
                    </AppButton>
                  </div>
                  <div class="bookando-alert bookando-alert--warning">
                    {{ t('mod.offers.courses.description_warning') || 'Nutze den Textmodus nicht, wenn bereits HTML-Inhalte vorhanden sind.' }}
                  </div>
                  <AppRichTextField
                    id="course_description"
                    v-model="form.description"
                    :label="null"
                    :placeholder="t('fields.description') || 'Beschreibung eingeben'"
                    :min-height="200"
                  />
                </AppServicesFormSection>
              </AppServicesFormSection>
            </section>
          </div>

          <div v-else-if="tab === 'pricing'">
            <section class="services-form__panel">
              <AppServicesFormSection
                icon="credit-card"
                :title="t('mod.offers.courses.pricing') || 'Preisgestaltung'"
                :description="t('mod.offers.courses.pricing_hint') || 'Lege Preis, Kapazität und Buchungsregeln fest.'"
                :columns="2"
              >
                <div class="bookando-alert bookando-alert--warning services-form__column-span-all">
                  {{ t('mod.offers.courses.pricing_warning') || 'Für Kurse mit bestätigten Teilnehmer:innen sind Preisänderungen nur eingeschränkt möglich.' }}
                </div>

                <BookandoField
                  id="course_price"
                  v-model="form.price"
                  type="number"
                  :label="t('fields.price') || 'Preis'"
                  min="0"
                  step="0.1"
                />
                <BookandoField
                  id="course_capacity"
                  v-model="form.capacity"
                  type="number"
                  :label="t('mod.offers.courses.capacity') || 'Maximale Plätze'"
                  min="1"
                />
                <BookandoField
                  id="course_allow_group"
                  v-model="form.allow_group_booking"
                  type="toggle"
                  :label="t('mod.offers.courses.allow_group_booking') || 'Mehrere Personen pro Buchung erlauben'"
                  :row="true"
                />
                <BookandoField
                  id="course_allow_repeat"
                  v-model="form.allow_repeat_booking"
                  type="toggle"
                  :label="t('mod.offers.courses.allow_repeat_booking') || 'Mehrfachbuchungen pro Kunde erlauben'"
                  :row="true"
                />

                <AppServicesFormSection
                  class="services-form__column-span-all"
                  icon="shield-check"
                  :title="t('mod.offers.courses.advanced_pricing') || 'Erweiterte Preisoptionen'"
                  compact
                >
                  <div class="services-form__grid services-form__grid--two">
                    <BookandoField
                      id="course_deposit_enabled"
                      v-model="form.deposit_enabled"
                      type="toggle"
                      :label="t('mod.offers.courses.deposit_enabled') || 'Anzahlung verlangen'"
                      :row="true"
                    />
                    <BookandoField
                      v-if="form.deposit_enabled"
                      id="course_deposit_amount"
                      v-model="form.deposit_amount"
                      type="number"
                      :label="t('mod.offers.courses.deposit_amount') || 'Anzahlung'"
                      min="0"
                      step="0.1"
                    />
                    <BookandoField
                      id="course_close_on_minimum"
                      v-model="form.close_on_minimum_enabled"
                      type="toggle"
                      :label="t('mod.offers.courses.close_on_minimum') || 'Kurs nach Mindestteilnehmerzahl schließen'"
                      :row="true"
                    />
                    <BookandoField
                      v-if="form.close_on_minimum_enabled"
                      id="course_close_on_minimum_value"
                      v-model="form.close_on_minimum_value"
                      type="number"
                      :label="t('mod.offers.courses.close_on_minimum_value') || 'Mindestanzahl'"
                      min="1"
                    />
                    <BookandoField
                      id="course_limit_guests"
                      v-model="form.limit_extra_enabled"
                      type="toggle"
                      :label="t('mod.offers.courses.limit_extra') || 'Zusätzliche Personen begrenzen'"
                      :row="true"
                    />
                    <BookandoField
                      v-if="form.limit_extra_enabled"
                      id="course_limit_guests_value"
                      v-model="form.limit_extra_value"
                      type="number"
                      :label="t('mod.offers.courses.limit_extra_value') || 'Max. zusätzliche Personen'"
                      min="0"
                    />
                  </div>
                </AppServicesFormSection>
              </AppServicesFormSection>
            </section>
          </div>

          <div v-else-if="tab === 'customize'">
            <section class="services-form__panel">
              <AppServicesFormSection
                icon="image"
                :title="t('mod.offers.courses.customize') || 'Anpassen'"
                :description="t('mod.offers.courses.customize_hint') || 'Bilder und Farbschema für die Darstellung festlegen.'"
                layout="stack"
              >
                <div class="courses-form__gallery">
                  <div
                    v-for="image in form.gallery"
                    :key="image._localId"
                    class="courses-form__gallery-item"
                  >
                    <div
                      class="courses-form__gallery-thumb"
                      :style="{ backgroundImage: image.url ? `url(${image.url})` : 'none' }"
                    >
                      <span v-if="!image.url">{{ t('ui.common.no_image') || 'Kein Bild' }}</span>
                    </div>
                    <div class="courses-form__gallery-meta">
                      <span>{{ image.name || t('ui.common.image') || 'Bild' }}</span>
                      <AppButton
                        icon="trash-2"
                        size="xs"
                        variant="ghost"
                        type="button"
                        @click="removeGalleryImage(image._localId)"
                      />
                    </div>
                  </div>

                  <label class="courses-form__gallery-upload">
                    <input
                      ref="galleryInput"
                      type="file"
                      accept="image/*"
                      class="bookando-hide"
                      multiple
                      @change="onGalleryUpload"
                    >
                    <AppButton
                      icon="upload"
                      variant="secondary"
                      size="sm"
                      type="button"
                    >
                      {{ t('mod.offers.courses.add_media') || 'Medien hinzufügen' }}
                    </AppButton>
                  </label>
                </div>

                <div class="courses-form__colors">
                  <p class="courses-form__colors-label">
                    {{ t('mod.offers.courses.colors') || 'Farbschema' }}
                  </p>
                  <div class="courses-form__swatches">
                    <button
                      v-for="color in presetColors"
                      :key="color"
                      type="button"
                      class="courses-form__swatch"
                      :class="{ 'is-active': form.color_mode === 'preset' && form.color_value === color }"
                      :style="{ backgroundColor: color }"
                      @click="selectPresetColor(color)"
                    >
                      <span class="sr-only">{{ color }}</span>
                    </button>
                  </div>
                  <div class="courses-form__custom-color">
                    <BookandoField
                      id="course_color_mode"
                      v-model="form.color_mode"
                      type="dropdown"
                      :label="t('mod.offers.courses.color_mode') || 'Farbmodus'"
                      :options="colorModeOptions"
                      option-label="label"
                      option-value="value"
                      mode="basic"
                    />
                    <BookandoField
                      v-if="form.color_mode === 'custom'"
                      id="course_color_value"
                      v-model="form.color_value"
                      type="text"
                      :label="t('mod.offers.courses.custom_color') || 'Hex-Farbe'"
                      placeholder="#FFD500"
                    />
                  </div>
                  <BookandoField
                    id="course_show_on_website"
                    v-model="form.show_on_website"
                    type="toggle"
                    :label="t('mod.offers.courses.show_on_website') || 'Auf der Website anzeigen'"
                    :row="true"
                  />
                </div>
              </AppServicesFormSection>
            </section>
          </div>

          <div v-else-if="tab === 'waitingList'">
            <section class="services-form__panel">
              <AppServicesFormSection
                icon="users"
                :title="t('mod.offers.courses.waiting_list') || 'Warteliste'"
                :description="t('mod.offers.courses.waiting_list_hint') || 'Aktiviere Wartelistenfunktionen für ausgebuchte Kurse.'"
                layout="stack"
              >
                <div class="courses-form__licence">
                  <AppIcon name="lock" />
                  <div>
                    <h4>{{ t('mod.offers.courses.waiting_list_pro') || 'Verfügbar in der Pro-Lizenz' }}</h4>
                    <p>{{ t('mod.offers.courses.waiting_list_upgrade') || 'Upgrade erforderlich, um Wartelisten zu nutzen.' }}</p>
                  </div>
                  <AppButton
                    variant="primary"
                    size="sm"
                    type="button"
                  >
                    {{ t('ui.common.upgrade') || 'Upgrade' }}
                  </AppButton>
                </div>
                <div class="courses-form__waiting">
                  <BookandoField
                    id="course_waitlist_enabled"
                    v-model="form.waitlist_enabled"
                    type="toggle"
                    :label="t('mod.offers.courses.waiting_list_enabled') || 'Warteliste anzeigen, wenn Kurs ausgebucht'"
                    :row="true"
                    disabled
                  />
                </div>
              </AppServicesFormSection>
            </section>
          </div>

          <div v-else>
            <section class="services-form__panel">
              <AppServicesFormSection
                icon="settings"
                :title="t('mod.offers.courses.settings') || 'Einstellungen'"
                :description="t('mod.offers.courses.settings_hint') || 'Definiere allgemeine Regeln, Zahlungen und Integrationen.'"
                :columns="2"
              >
                <AppServicesFormSection
                  class="services-form__column-span-all"
                  icon="sliders"
                  :title="t('mod.offers.courses.general_settings') || 'Allgemein'"
                  compact
                >
                  <div class="services-form__grid services-form__grid--two">
                    <BookandoField
                      id="course_cancellation_period"
                      v-model="form.cancellation_lead_time"
                      type="dropdown"
                      :label="t('mod.offers.courses.cancellation_period') || 'Mindestvorlaufzeit für Stornierungen'"
                      :options="cancellationOptions"
                      option-label="label"
                      option-value="value"
                      mode="basic"
                    />
                    <BookandoField
                      id="course_redirect_url"
                      v-model="form.redirect_url"
                      type="text"
                      :label="t('mod.offers.courses.redirect_url') || 'Weiterleitungs-URL nach Buchung'"
                      placeholder="https://www.example.com/"
                    />
                  </div>
                </AppServicesFormSection>

                <AppServicesFormSection
                  class="services-form__column-span-all"
                  icon="credit-card"
                  :title="t('mod.offers.courses.payment_settings') || 'Zahlungen'"
                  compact
                >
                  <div class="services-form__grid services-form__grid--two">
                    <BookandoField
                      id="course_payment_link"
                      v-model="form.payment_link_enabled"
                      type="toggle"
                      :label="t('mod.offers.courses.payment_link') || 'Zahlung über Payment Link erlauben'"
                      :row="true"
                    />
                    <BookandoField
                      id="course_payment_onsite"
                      v-model="form.payment_on_site"
                      type="toggle"
                      :label="t('mod.offers.courses.payment_on_site') || 'Zahlung vor Ort'"
                      :row="true"
                    />
                  </div>
                </AppServicesFormSection>

                <AppServicesFormSection
                  class="services-form__column-span-all"
                  icon="link"
                  :title="t('mod.offers.courses.integrations') || 'Integrationen'"
                  compact
                >
                  <div class="services-form__grid services-form__grid--two">
                    <BookandoField
                      id="course_google_meet"
                      v-model="form.google_meet_enabled"
                      type="toggle"
                      :label="t('mod.offers.courses.google_meet') || 'Google Meet aktivieren'"
                      :row="true"
                    />
                    <BookandoField
                      id="course_lesson_space"
                      v-model="form.lesson_space_enabled"
                      type="toggle"
                      :label="t('mod.offers.courses.lesson_space') || 'Lesson Space'"
                      :row="true"
                    />
                  </div>
                </AppServicesFormSection>
              </AppServicesFormSection>
            </section>
          </div>
        </form>
      </template>

      <template #footer>
        <div class="bookando-form-buttons bookando-form-buttons--split">
          <AppButton
            btn-type="textonly"
            variant="secondary"
            size="dynamic"
            type="button"
            @click="onCancel"
          >
            {{ t('core.common.cancel') }}
          </AppButton>
          <AppButton
            btn-type="full"
            variant="primary"
            size="dynamic"
            type="submit"
            :form="formId"
          >
            {{ t('core.common.save') }}
          </AppButton>
        </div>
      </template>
    </AppForm>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'
import AppServicesFormSection from '../services/ui/AppServicesFormSection.vue'

import './courses-form.scss'
import '../services/services-form.scss'

type Id = number

type Option<T extends string> = { label: string; value: T }

type CourseSession = {
  _localId: number
  date: string | null
  time_start: string | null
  time_end: string | null
}

type GalleryItem = {
  _localId: number
  url: string
  name?: string
}

type CourseLessonLink = {
  _localId: number
  id?: Id
  label: string
  url: string
}

type CourseLesson = {
  _localId: number
  id?: Id
  name: string
  content: string
  images: CourseLessonLink[]
  videos: CourseLessonLink[]
  resources: CourseLessonLink[]
}

type CourseQuestionOption = {
  _localId: number
  id?: Id
  text: string
  isCorrect: boolean
}

type CourseQuestionPin = {
  _localId: number
  label: string
}

type CourseQuestionPair = {
  _localId: number
  left: string
  right: string
}

type CourseQuestionOrderingItem = {
  _localId: number
  text: string
}

export type CourseQuestionType =
  | 'quiz_single'
  | 'quiz_multi'
  | 'true_false'
  | 'slider'
  | 'pin'
  | 'essay'
  | 'cloze'
  | 'short_answer'
  | 'matching'
  | 'ordering'
  | 'puzzle'

export type CourseQuestion = {
  _localId: number
  id?: Id
  prompt: string
  type: CourseQuestionType
  time_limit: number | null
  points: number
  options: CourseQuestionOption[]
  slider?: {
    min: number
    max: number
    step: number
    correctMin: number
    correctMax: number
  } | null
  pins: CourseQuestionPin[]
  pinBackground: string
  matchingPairs: CourseQuestionPair[]
  orderingItems: CourseQuestionOrderingItem[]
  puzzlePairs: CourseQuestionPair[]
  answerText: string
  clozeText: string
  answerImage: string
}

type CourseTestSettings = {
  attemptsMode: 'unlimited' | 'limited'
  attemptsValue: number | null
  minScore: number | null
  questionSelection: 'all' | 'limited'
  questionCount: number | null
  shuffleAnswers: 'random' | 'fixed'
  layout: 'single' | 'list'
  feedback: 'instant' | 'end'
}

export type CourseTest = {
  _localId: number
  id?: Id
  title: string
  summary: string
  questions: CourseQuestion[]
  settings: CourseTestSettings
}

export type CourseTopic = {
  _localId: number
  id?: Id
  title: string
  summary: string
  lessons: CourseLesson[]
  tests: CourseTest[]
}

export type CourseMode = 'online' | 'physical'
export type CourseVisibility = 'public' | 'login' | 'private'
export type CourseDifficulty = 'beginner' | 'intermediate' | 'advanced' | 'expert'
export type CourseStatus = 'active' | 'hidden'

export type CourseFormVm = {
  id?: Id
  name: string
  description: string
  status: CourseStatus
  mode: CourseMode
  max_participants: number | null
  visibility: CourseVisibility
  visibility_from: string | null
  visibility_until: string | null
  difficulty: CourseDifficulty | null
  cover_image: string | null
  cover_image_file: File | null
  cover_image_preview: string | null
  intro_video_url: string | null
  category_id: Id | null
  tag_ids: Id[]
  notify_participants: boolean
  sessions: CourseSession[]
  is_recurring: boolean
  booking_starts_immediately: boolean
  booking_start_date: string | null
  booking_start_time: string | null
  booking_closes_on_start: boolean
  booking_end_date: string | null
  booking_end_time: string | null
  location_id: string | Id | null
  custom_location?: string
  organizer_id: string | null
  team_ids: string[]
  price: number | null
  capacity: number | null
  allow_group_booking: boolean
  allow_repeat_booking: boolean
  deposit_enabled: boolean
  deposit_amount: number | null
  close_on_minimum_enabled: boolean
  close_on_minimum_value: number | null
  limit_extra_enabled: boolean
  limit_extra_value: number | null
  gallery: GalleryItem[]
  color_mode: 'preset' | 'custom'
  color_value: string
  show_on_website: boolean
  waitlist_enabled: boolean
  cancellation_lead_time: string
  redirect_url: string
  payment_link_enabled: boolean
  payment_on_site: boolean
  google_meet_enabled: boolean
  lesson_space_enabled: boolean
  author: string
  topics_sequential: boolean
  topics: CourseTopic[]
}

type Category = { id: Id; name: string }
type Tag = { id: Id; name: string }

type CourseTab = 'details' | 'pricing' | 'customize' | 'waitingList' | 'settings'

export type CourseModeOption = Option<CourseMode>
export type CourseVisibilityOption = Option<CourseVisibility>
export type CourseDifficultyOption = Option<CourseDifficulty>
export type CourseStatusOption = Option<CourseStatus>

const defaultTestSettings: CourseTestSettings = {
  attemptsMode: 'unlimited',
  attemptsValue: null,
  minScore: 0,
  questionSelection: 'all',
  questionCount: null,
  shuffleAnswers: 'random',
  layout: 'single',
  feedback: 'end',
}

const { t } = useI18n()

const props = defineProps<{
  modelValue: Partial<CourseFormVm> | null
  categories: Category[]
  tags: Tag[]
}>()

const emit = defineEmits<{
  (event: 'save', value: CourseFormVm): void
  (event: 'cancel'): void
}>()

const formId = `course-${Math.random().toString(36).slice(2, 8)}`
const tab = ref<CourseTab>('details')
const galleryInput = ref<HTMLInputElement | null>(null)

const emptySession = (): CourseSession => ({
  _localId: Date.now() + Math.random(),
  date: null,
  time_start: null,
  time_end: null,
})

const emptyGalleryItem = (url = ''): GalleryItem => ({
  _localId: Date.now() + Math.random(),
  url,
})

const emptyCourse: CourseFormVm = {
  id: undefined,
  name: '',
  description: '',
  status: 'active',
  mode: 'online',
  max_participants: null,
  visibility: 'public',
  visibility_from: null,
  visibility_until: null,
  difficulty: null,
  cover_image: null,
  cover_image_file: null,
  cover_image_preview: null,
  intro_video_url: null,
  category_id: null,
  tag_ids: [],
  notify_participants: true,
  sessions: [emptySession()],
  is_recurring: false,
  booking_starts_immediately: true,
  booking_start_date: null,
  booking_start_time: null,
  booking_closes_on_start: true,
  booking_end_date: null,
  booking_end_time: null,
  location_id: null,
  custom_location: '',
  organizer_id: null,
  team_ids: [],
  price: null,
  capacity: null,
  allow_group_booking: false,
  allow_repeat_booking: true,
  deposit_enabled: false,
  deposit_amount: null,
  close_on_minimum_enabled: false,
  close_on_minimum_value: null,
  limit_extra_enabled: false,
  limit_extra_value: null,
  gallery: [],
  color_mode: 'preset',
  color_value: '#1788FB',
  show_on_website: true,
  waitlist_enabled: false,
  cancellation_lead_time: '2_days',
  redirect_url: '',
  payment_link_enabled: false,
  payment_on_site: true,
  google_meet_enabled: false,
  lesson_space_enabled: false,
  author: '',
  topics_sequential: false,
  topics: [],
}

const form = ref<CourseFormVm>({ ...emptyCourse })

watch(
  () => props.modelValue,
  value => {
    form.value = normalizeCourse(value)
    tab.value = 'details'
  },
  { immediate: true },
)

const categories = computed(() => props.categories || [])
const tags = computed(() => props.tags || [])

const statusOptions = computed<CourseStatusOption[]>(() => [
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' },
])

const tabItems = computed(() => [
  { label: t('mod.offers.courses.tab_details') || 'Details', value: 'details' },
  { label: t('mod.offers.courses.tab_pricing') || 'Preisgestaltung', value: 'pricing' },
  { label: t('mod.offers.courses.tab_customize') || 'Anpassen', value: 'customize' },
  { label: t('mod.offers.courses.tab_waiting') || 'Warteliste', value: 'waitingList' },
  { label: t('mod.offers.courses.tab_settings') || 'Einstellungen', value: 'settings' },
])

const locationOptions = computed(() => [
  { label: t('mod.offers.courses.location_custom') || 'Benutzerdefinierte Adresse', value: 'custom' },
  { label: 'bruderwerk. GmbH', value: 'bruderwerk' },
  { label: 'Ultraterrain Geisingen', value: 'geisingen' },
])

const organizerOptions = computed(() => [
  { label: 'Francesco Augello', value: 'francesco' },
  { label: 'Mehrere Mitarbeiter', value: 'multi' },
  { label: 'Patrik Augello', value: 'patrik' },
  { label: 'Simon Augello', value: 'simon' },
])

const teamOptions = computed(() => [
  { label: 'Team A', value: 'team-a' },
  { label: 'Team B', value: 'team-b' },
  { label: 'Team C', value: 'team-c' },
])

const presetColors = ['#1788FB', '#4BBEC6', '#FBC22D', '#FA3C52', '#774DFB', '#26CC2B', '#FD7E35']

const colorModeOptions = computed(() => [
  { label: t('mod.offers.courses.color_mode_preset') || 'Voreingestellte Farben', value: 'preset' },
  { label: t('mod.offers.courses.color_mode_custom') || 'Benutzerdefiniert', value: 'custom' },
])

const cancellationOptions = computed(() => [
  { label: t('mod.offers.courses.cancellation.disabled') || 'Deaktiviert', value: 'disabled' },
  { label: '6 Std', value: '6_hours' },
  { label: '12 Std', value: '12_hours' },
  { label: '1 Tag', value: '1_day' },
  { label: '2 Tage', value: '2_days' },
  { label: '1 Woche', value: '1_week' },
  { label: '2 Wochen', value: '2_weeks' },
])

function addSession() {
  form.value.sessions.push(emptySession())
}

function removeSession(localId: number) {
  form.value.sessions = form.value.sessions.filter(item => item._localId !== localId)
  if (!form.value.sessions.length) {
    form.value.sessions.push(emptySession())
  }
}

function sessionIndex(session: CourseSession) {
  return form.value.sessions.findIndex(item => item._localId === session._localId)
}

function selectPresetColor(color: string) {
  form.value.color_mode = 'preset'
  form.value.color_value = color
}

function removeGalleryImage(localId: number) {
  const removed = form.value.gallery.find(image => image._localId === localId)
  if (removed?.url?.startsWith('blob:')) {
    URL.revokeObjectURL(removed.url)
  }
  form.value.gallery = form.value.gallery.filter(item => item._localId !== localId)
}

function onGalleryUpload(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input?.files?.length) {
    return
  }

  Array.from(input.files).forEach(file => {
    const url = URL.createObjectURL(file)
    form.value.gallery.push({
      _localId: Date.now() + Math.random(),
      url,
      name: file.name,
    })
  })

  input.value = ''
}

function onSubmit() {
  const payload =
    typeof structuredClone === 'function'
      ? structuredClone(form.value)
      : JSON.parse(JSON.stringify(form.value))

  sanitizeBeforeSubmit(payload)
  emit('save', payload)
}

function sanitizeBeforeSubmit(course: CourseFormVm) {
  course.cover_image_preview = null

  course.sessions.forEach(session => {
    delete session._localId
  })

  course.gallery.forEach(image => {
    if (image.url.startsWith('blob:')) {
      URL.revokeObjectURL(image.url)
    }
    delete image._localId
  })

  course.topics.forEach(topic => {
    delete topic._localId
    topic.lessons.forEach(lesson => {
      delete lesson._localId
      lesson.images.forEach(image => delete image._localId)
      lesson.videos.forEach(video => delete video._localId)
      lesson.resources.forEach(resource => delete resource._localId)
    })
    topic.tests.forEach(test => {
      delete test._localId
      test.questions.forEach(question => {
        delete question._localId
        question.options.forEach(option => delete option._localId)
        question.pins.forEach(pin => delete pin._localId)
        question.matchingPairs.forEach(pair => delete pair._localId)
        question.orderingItems.forEach(item => delete item._localId)
        question.puzzlePairs.forEach(pair => delete pair._localId)
      })
    })
  })
}

function onCancel() {
  emit('cancel')
}

function normalizeCourse(value: Partial<CourseFormVm> | null): CourseFormVm {
  const base = { ...emptyCourse, ...(value || {}) }

  const sessionsSource =
    value?.sessions && value.sessions.length ? value.sessions : emptyCourse.sessions

  const sessions = sessionsSource.map((session, index) => ({
    ...emptySession(),
    ...session,
    _localId: session?._localId ?? Date.now() + index + Math.random(),
  }))

  const gallery = (value?.gallery || []).map((image, index) => ({
    ...emptyGalleryItem(image.url || ''),
    ...image,
    _localId: image?._localId ?? Date.now() + index + Math.random(),
  }))

  const topics = (value?.topics || []).map((topic, topicIndex) => ({
    _localId: topic._localId ?? topicIndex + 1,
    id: topic.id,
    title: topic.title || '',
    summary: topic.summary || '',
    lessons: (topic.lessons || []).map((lesson, lessonIndex) => ({
      _localId: lesson._localId ?? lessonIndex + 1,
      id: lesson.id,
      name: lesson.name || '',
      content: lesson.content || '',
      images: (lesson.images || []).map((image, imageIndex) => ({
        _localId: image._localId ?? imageIndex + 1,
        id: image.id,
        label: image.label || '',
        url: image.url || '',
      })),
      videos: (lesson.videos || []).map((video, videoIndex) => ({
        _localId: video._localId ?? videoIndex + 1,
        id: video.id,
        label: video.label || '',
        url: video.url || '',
      })),
      resources: (lesson.resources || []).map((resource, resourceIndex) => ({
        _localId: resource._localId ?? resourceIndex + 1,
        id: resource.id,
        label: resource.label || '',
        url: resource.url || '',
      })),
    })),
    tests: (topic.tests || []).map((test, testIndex) => ({
      _localId: test._localId ?? testIndex + 1,
      id: test.id,
      title: test.title || '',
      summary: test.summary || '',
      settings: { ...defaultTestSettings, ...(test.settings || {}) },
      questions: (test.questions || []).map((question, questionIndex) =>
        normalizeQuestion(question, questionIndex),
      ),
    })),
  }))

  return {
    ...base,
    tag_ids: Array.isArray(base.tag_ids) ? [...base.tag_ids] : [],
    team_ids: Array.isArray(base.team_ids) ? [...base.team_ids] : [],
    sessions,
    gallery,
    topics,
    topics_sequential: Boolean(base.topics_sequential),
  }
}

function normalizeQuestion(question: Partial<CourseQuestion>, questionIndex: number): CourseQuestion {
  const base: CourseQuestion = {
    _localId: question._localId ?? questionIndex + 1,
    id: question.id,
    prompt: question.prompt || '',
    type: (question.type as CourseQuestionType) || 'quiz_single',
    time_limit: typeof question.time_limit === 'number' ? question.time_limit : null,
    points: typeof question.points === 'number' ? question.points : 1,
    options: (question.options || []).map((option, optionIndex) => ({
      _localId: option._localId ?? optionIndex + 1,
      id: option.id,
      text: option.text || '',
      isCorrect: Boolean(option.isCorrect),
    })),
    slider: question.slider
      ? {
          min: Number.isFinite(question.slider.min) ? Number(question.slider.min) : 0,
          max: Number.isFinite(question.slider.max) ? Number(question.slider.max) : 100,
          step: Number.isFinite(question.slider.step) ? Number(question.slider.step) : 5,
          correctMin: Number.isFinite(question.slider.correctMin)
            ? Number(question.slider.correctMin)
            : 0,
          correctMax: Number.isFinite(question.slider.correctMax)
            ? Number(question.slider.correctMax)
            : 100,
        }
      : null,
    pins: (question.pins || []).map((pin, pinIndex) => ({
      _localId: pin._localId ?? pinIndex + 1,
      label: pin.label || '',
    })),
    pinBackground: question.pinBackground || '',
    matchingPairs: (question.matchingPairs || []).map((pair, pairIndex) => ({
      _localId: pair._localId ?? pairIndex + 1,
      left: pair.left || '',
      right: pair.right || '',
    })),
    orderingItems: (question.orderingItems || []).map((item, itemIndex) => ({
      _localId: item._localId ?? itemIndex + 1,
      text: item.text || '',
    })),
    puzzlePairs: (question.puzzlePairs || []).map((pair, pairIndex) => ({
      _localId: pair._localId ?? pairIndex + 1,
      left: pair.left || '',
      right: pair.right || '',
    })),
    answerText: question.answerText || '',
    clozeText: question.clozeText || '',
    answerImage: question.answerImage || '',
  }

  if ((base.type === 'quiz_single' || base.type === 'quiz_multi') && base.options.length === 0) {
    base.options = [
      { _localId: 1, text: '', isCorrect: true },
      { _localId: 2, text: '', isCorrect: false },
    ]
  }

  if (base.type === 'true_false') {
    const trueLabel = t('core.common.true') || 'Richtig'
    const falseLabel = t('core.common.false') || 'Falsch'
    base.options = [
      { _localId: 1, text: trueLabel, isCorrect: question.options?.[0]?.isCorrect ?? true },
      { _localId: 2, text: falseLabel, isCorrect: question.options?.[1]?.isCorrect ?? false },
    ]
  }

  if (base.type === 'slider' && !base.slider) {
    base.slider = { min: 0, max: 100, step: 5, correctMin: 40, correctMax: 60 }
  }

  if (base.type === 'pin' && base.pins.length === 0) {
    base.pins = [{ _localId: 1, label: '' }]
  }

  if (base.type === 'matching' && base.matchingPairs.length === 0) {
    base.matchingPairs = [{ _localId: 1, left: '', right: '' }]
  }

  if (base.type === 'ordering' && base.orderingItems.length === 0) {
    base.orderingItems = [
      { _localId: 1, text: '' },
      { _localId: 2, text: '' },
    ]
  }

  if (base.type === 'puzzle' && base.puzzlePairs.length === 0) {
    base.puzzlePairs = [{ _localId: 1, left: '', right: '' }]
  }

  return base
}

</script>

