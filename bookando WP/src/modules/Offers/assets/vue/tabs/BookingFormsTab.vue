<template>
  <div class="p-6 space-y-6">
    <!-- Info Banner -->
    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-5">
      <div class="flex items-start gap-3">
        <svg class="w-6 h-6 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <div>
          <h3 class="font-bold text-emerald-900 mb-1">{{ $t('mod.offers.booking_forms.info_title') }}</h3>
          <p class="text-sm text-emerald-700">
            {{ $t('mod.offers.booking_forms.info_text') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('mod.offers.booking_forms.search_placeholder')"
          class="w-80 px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
        >
      </div>
      <button
        @click="openFormBuilder"
        class="flex items-center gap-2 px-5 py-2.5 bg-rose-600 text-white rounded-lg font-medium hover:bg-rose-700 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $t('mod.offers.booking_forms.create') }}
      </button>
    </div>

    <!-- Forms Grid -->
    <div v-if="!loading && filteredForms.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div
        v-for="form in filteredForms"
        :key="form.id"
        class="bg-white rounded-xl border-2 border-slate-200 overflow-hidden hover:border-rose-300 hover:shadow-lg transition-all"
      >
        <!-- Form Header -->
        <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-5 py-4 border-b border-slate-200">
          <div class="flex items-start justify-between mb-2">
            <div class="flex-1">
              <h3 class="text-lg font-bold text-slate-900 mb-1">{{ form.name }}</h3>
              <p class="text-sm text-slate-600">{{ form.description }}</p>
            </div>
            <span
              :class="[
                'px-2.5 py-1 text-xs font-bold rounded-full',
                form.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'
              ]"
            >
              {{ form.status === 'active' ? $t('core.common.active') : $t('core.common.inactive') }}
            </span>
          </div>
        </div>

        <!-- Form Preview -->
        <div class="p-5 bg-slate-50">
          <div class="bg-white border border-slate-200 rounded-lg p-4 space-y-3">
            <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">{{ $t('mod.offers.booking_forms.form_preview') }}</h4>

            <!-- Form Fields Preview -->
            <div
              v-for="field in form.fields.slice(0, 3)"
              :key="field.id"
              class="flex items-start gap-3"
            >
              <svg
                v-if="field.type === 'text'"
                class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
              </svg>
              <svg
                v-else-if="field.type === 'select'"
                class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg
                v-else-if="field.type === 'checkbox'"
                class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="flex-1">
                <div class="text-sm font-medium text-slate-700">{{ field.label }}</div>
                <div class="text-xs text-slate-500">{{ field.type }}</div>
              </div>
              <span v-if="field.required" class="text-red-500 text-sm font-bold">*</span>
            </div>

            <div v-if="form.fields.length > 3" class="text-center pt-2 border-t border-slate-100">
              <span class="text-xs text-slate-500">+ {{ form.fields.length - 3 }} {{ $t('mod.offers.booking_forms.more_fields') }}</span>
            </div>
          </div>
        </div>

        <!-- Form Stats & Actions -->
        <div class="bg-slate-50 px-5 py-4 border-t border-slate-200">
          <div class="flex items-center justify-between mb-3">
            <div class="flex gap-4 text-sm">
              <div>
                <span class="font-bold text-slate-900">{{ form.submissions || 0 }}</span>
                <span class="text-slate-500"> {{ $t('mod.offers.booking_forms.submissions') }}</span>
              </div>
              <div>
                <span class="font-bold text-slate-900">{{ form.fields.length }}</span>
                <span class="text-slate-500"> {{ $t('mod.offers.booking_forms.fields') }}</span>
              </div>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              @click="editForm(form)"
              class="flex-1 px-4 py-2 text-sm font-medium text-rose-700 border border-rose-300 rounded-lg hover:bg-rose-50 transition-colors"
            >
              {{ $t('core.actions.edit') }}
            </button>
            <button
              @click="duplicateForm(form)"
              class="px-4 py-2 text-sm font-medium text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
              :title="$t('core.actions.duplicate')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
            <button
              @click="viewSubmissions(form)"
              class="px-4 py-2 text-sm font-medium text-blue-700 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors"
              :title="$t('mod.offers.booking_forms.view_submissions')"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loading" class="text-center py-12 text-slate-400">
      <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="text-lg font-medium">{{ $t('mod.offers.booking_forms.no_forms') }}</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t: $t } = useI18n()

// State
const loading = ref(false)
const searchQuery = ref('')
const forms = ref<any[]>([
  {
    id: 1,
    name: 'Standard Buchungsformular',
    description: 'Allgemeines Formular für alle Dienstleistungen',
    status: 'active',
    submissions: 143,
    fields: [
      { id: 1, label: 'Vorname', type: 'text', required: true },
      { id: 2, label: 'Nachname', type: 'text', required: true },
      { id: 3, label: 'E-Mail', type: 'email', required: true },
      { id: 4, label: 'Telefon', type: 'tel', required: true },
      { id: 5, label: 'Bevorzugter Termin', type: 'date', required: false }
    ]
  },
  {
    id: 2,
    name: 'Kurs-Anmeldung',
    description: 'Spezialformular für Kursanmeldungen mit Zusatzfeldern',
    status: 'active',
    submissions: 87,
    fields: [
      { id: 1, label: 'Vollständiger Name', type: 'text', required: true },
      { id: 2, label: 'Geburtsdatum', type: 'date', required: true },
      { id: 3, label: 'E-Mail', type: 'email', required: true },
      { id: 4, label: 'Telefon', type: 'tel', required: true },
      { id: 5, label: 'Kursauswahl', type: 'select', required: true },
      { id: 6, label: 'Vorkenntnisse', type: 'textarea', required: false },
      { id: 7, label: 'Einverständniserklärung', type: 'checkbox', required: true }
    ]
  }
])

// Filtered forms
const filteredForms = computed(() => {
  if (!searchQuery.value) return forms.value
  const query = searchQuery.value.toLowerCase()
  return forms.value.filter(form =>
    form.name.toLowerCase().includes(query) ||
    form.description.toLowerCase().includes(query)
  )
})

// Actions
const openFormBuilder = () => {
  console.log('Open form builder')
  // TODO: Open form builder modal/page
}

const editForm = (form: any) => {
  console.log('Edit form:', form)
}

const duplicateForm = (form: any) => {
  console.log('Duplicate form:', form)
}

const viewSubmissions = (form: any) => {
  console.log('View submissions for form:', form)
}

onMounted(() => {
  // TODO: Load forms from API
})
</script>
