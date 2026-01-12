<!-- AppTimeInput.vue -->
<template>
  <div
    class="bookando-form-group"
    :class="{ 'bookando-form-group--error': !isvalid }"
  >
    <input
      v-model="zeitText"
      type="text"
      class="bookando-control"
      :placeholder="platzhalter"
      :aria-invalid="!isvalid"
      @blur="pruefeZeit"
      @input="feedbackWaehrenEingabe"
    >
    <!-- Optionales Icon für Live-Feedback -->
    <span
      v-if="feedbackIcon"
      :class="feedbackIcon"
      :title="!isvalid ? ($t('ui.common.invalid_input') || 'Ungültige Eingabe') : ''"
    >
      <!-- Hier könnte ein SVG-Icon eingebunden werden -->
    </span>
    <!-- Fehlermeldung unter dem Feld -->
    <div
      v-if="!isvalid"
      class="bookando-form-error"
    >
      {{ $t('ui.time.invalid_format_hint') || 'Bitte eine gültige Zeit im Format HH:MM eingeben.' }}
    </div>
  </div>
</template>

<script>

// ggf. import customParseFormat from 'dayjs/plugin/customParseFormat'; dayjs.extend(customParseFormat);

export default {
  name: 'AppTimeInput',
  props: {
    modelValue: { type: String, default: '' },
    platzhalter: { type: String, default: 'HH:MM' },
    liveFeedback: { type: Boolean, default: true }
  },
  emits: ['update:modelValue'],
  data() {
    return {
      zeitText: this.modelValue || '',
      isvalid: true,
      feedbackIcon: null  // z.B. 'bookando-icon--success' / '--danger'
    };
  },
  watch: {
    modelValue(neu) {
      // Externes Update übernehmen
      this.zeitText = neu;
    }
  },
  methods: {
    pruefeZeit() {
      if (!this.zeitText) {
        // Leeres Feld (hier als gültig erachtet, kann angepasst werden)
        this.isvalid = true;
        this.$emit('update:modelValue', '');
        return;
      }
      const ZEIT_REGEX = /^([01]?\d|2[0-3])[:.]([0-5]\d)$/;
      this.isvalid = ZEIT_REGEX.test(this.zeitText);
      if (!this.isvalid) {
        // Fehlerfall: keine Aktualisierung des v-model-Werts, Feld wird markiert
        return;
      }
      // Gültiger Wert -> formatieren und an Parent komponenten
      let [h, m] = this.zeitText.split(/[:.]/);
      h = h.padStart(2, '0');
      m = m.padStart(2, '0');
      const formatiert = `${h}:${m}`;
      this.zeitText = formatiert;
      this.$emit('update:modelValue', formatiert);
    },
    feedbackWaehrenEingabe() {
      if (!this.liveFeedback) return;
      // Unerlaubte Zeichen filtern (nur Ziffern und ':' oder '.')
      this.zeitText = this.zeitText.replace(/[^0-9:.]/g, '');
      // Live-Icon Logik:
      if (!this.zeitText) {
        this.feedbackIcon = null;
      } else if (/^([01]?\d|2[0-3])[:.]([0-5]\d)$/.test(this.zeitText)) {
        // bereits vollständiges gültiges Format
        this.feedbackIcon = 'bookando-icon--success';
      } else if ((this.zeitText.match(/[:.]/g) || []).length > 1) {
        // mehr als ein Trennzeichen -> definitiv falsch
        this.feedbackIcon = 'bookando-icon--danger';
      } else {
        // sonst kein spezielles Icon während Eingabe
        this.feedbackIcon = null;
      }
    }
  }
};
</script>

<!-- Optional: eigenes Styling, falls nötig -->
<style scoped lang="scss">
/* Da wir Bookando-Utility-Klassen nutzen, ist eigenes CSS meist nicht nötig.
   Falls doch, z.B. um das Feedback-Icon relativ zum Input zu positionieren: */
span.bookando-icon--success, span.bookando-icon--danger {
  /* Beispiel: Icon rechts im Feld anzeigen */
  margin-left: -1.5rem; /* Icon überlappt rechte Input-Innenkante */
  pointer-events: none;
}
</style>
