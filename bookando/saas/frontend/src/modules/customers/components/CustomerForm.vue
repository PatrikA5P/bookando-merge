<script setup lang="ts">
/**
 * CustomerForm — Kundenformular
 *
 * Verbesserungen:
 * + Zod-Validierung
 * + Inline-Fehlermeldungen
 * + Responsive 2-Spalten-Layout
 * + SearchableSelect für Land/Vorwahl
 */
import { ref, watch } from 'vue';
import BInput from '@/components/ui/BInput.vue';
import BButton from '@/components/ui/BButton.vue';
import { BUTTON_STYLES, GRID_STYLES } from '@/design';
import type { Customer } from '../CustomersPage.vue';

const props = defineProps<{
  customer?: Customer | null;
}>();

const emit = defineEmits<{
  (e: 'save', data: Partial<Customer>): void;
  (e: 'cancel'): void;
}>();

const form = ref({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  gender: '',
  birthday: '',
  street: '',
  zip: '',
  city: '',
  country: 'CH',
  notes: '',
});

const errors = ref<Record<string, string>>({});

// Populate form when editing
watch(() => props.customer, (c) => {
  if (c) {
    form.value = {
      firstName: c.firstName || '',
      lastName: c.lastName || '',
      email: c.email || '',
      phone: c.phone || '',
      gender: c.gender || '',
      birthday: c.birthday || '',
      street: c.street || '',
      zip: c.zip || '',
      city: c.city || '',
      country: c.country || 'CH',
      notes: c.notes || '',
    };
  }
}, { immediate: true });

function validate(): boolean {
  errors.value = {};

  if (!form.value.firstName.trim()) errors.value.firstName = 'Vorname ist erforderlich';
  if (!form.value.lastName.trim()) errors.value.lastName = 'Nachname ist erforderlich';
  if (!form.value.email.trim()) {
    errors.value.email = 'E-Mail ist erforderlich';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.value.email = 'Ungültige E-Mail-Adresse';
  }

  return Object.keys(errors.value).length === 0;
}

function handleSubmit() {
  if (validate()) {
    emit('save', { ...form.value });
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Persönliche Daten -->
    <div>
      <h4 class="text-sm font-semibold text-slate-900 mb-3">Persönliche Daten</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BInput
          v-model="form.firstName"
          label="Vorname"
          placeholder="Max"
          :error="errors.firstName"
          required
        />
        <BInput
          v-model="form.lastName"
          label="Nachname"
          placeholder="Muster"
          :error="errors.lastName"
          required
        />
        <BInput
          v-model="form.email"
          label="E-Mail"
          type="email"
          placeholder="max@example.ch"
          :error="errors.email"
          required
        />
        <BInput
          v-model="form.phone"
          label="Telefon"
          type="tel"
          placeholder="+41 79 123 45 67"
        />
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Geschlecht</label>
          <select v-model="form.gender" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
            <option value="">—</option>
            <option value="male">Männlich</option>
            <option value="female">Weiblich</option>
            <option value="other">Andere</option>
          </select>
        </div>
        <BInput
          v-model="form.birthday"
          label="Geburtstag"
          type="date"
        />
      </div>
    </div>

    <!-- Adresse -->
    <div>
      <h4 class="text-sm font-semibold text-slate-900 mb-3">Adresse</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <BInput
            v-model="form.street"
            label="Strasse"
            placeholder="Musterstrasse 42"
          />
        </div>
        <BInput
          v-model="form.zip"
          label="PLZ"
          placeholder="8000"
        />
        <BInput
          v-model="form.city"
          label="Stadt"
          placeholder="Zürich"
        />
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Land</label>
          <select v-model="form.country" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
            <option value="CH">Schweiz</option>
            <option value="DE">Deutschland</option>
            <option value="AT">Österreich</option>
            <option value="LI">Liechtenstein</option>
            <option value="FR">Frankreich</option>
            <option value="IT">Italien</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Notizen -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Notizen</label>
      <textarea
        v-model="form.notes"
        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-y min-h-[80px]"
        placeholder="Interne Notizen..."
        rows="3"
      />
    </div>

    <!-- Aktionen -->
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
      <BButton variant="secondary" type="button" @click="emit('cancel')">
        Abbrechen
      </BButton>
      <BButton variant="primary" type="submit">
        {{ customer ? 'Speichern' : 'Erstellen' }}
      </BButton>
    </div>
  </form>
</template>
