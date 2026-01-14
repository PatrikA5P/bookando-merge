<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <!-- Sticky Header -->
      <div class="sticky top-0 bg-white border-b border-slate-200 p-6 rounded-t-xl z-10">
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-bold text-slate-900">
            {{ service ? $t('mod.services.edit_service') : $t('mod.services.add_service') }}
          </h2>
          <button
            @click="$emit('close')"
            class="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400 hover:text-slate-600"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <p class="text-sm text-slate-500 mt-1">{{ $t('mod.services.form_description') }}</p>
      </div>

      <!-- Form Body -->
      <div class="p-6 space-y-6">
        <!-- Basic Information -->
        <div>
          <h3 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $t('mod.services.basic_info') }}
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Service Name -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.name') }} <span class="text-rose-600">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all"
                :placeholder="$t('mod.services.name_placeholder')"
              >
            </div>

            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.services.category.label') }}
              </label>
              <input
                v-model="form.category"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all"
                :placeholder="$t('mod.services.category_placeholder')"
              >
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.status') }} <span class="text-rose-600">*</span>
              </label>
              <select
                v-model="form.status"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all appearance-none bg-white"
              >
                <option value="aktiv">{{ $t('core.status.active') }}</option>
                <option value="inaktiv">{{ $t('core.status.hidden') }}</option>
                <option value="hidden">{{ $t('mod.services.status.hidden') }}</option>
              </select>
            </div>

            <!-- Duration -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.services.duration') }}
              </label>
              <input
                v-model="form.duration"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all"
                :placeholder="$t('mod.services.duration_placeholder')"
              >
            </div>

            <!-- Price -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.price') }}
              </label>
              <input
                v-model="form.price"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all"
                :placeholder="$t('fields.price_placeholder')"
              >
            </div>
          </div>
        </div>

        <!-- Description -->
        <div>
          <h3 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
            {{ $t('fields.description') }}
          </h3>

          <textarea
            v-model="form.description"
            rows="4"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all resize-none"
            :placeholder="$t('mod.services.description_placeholder')"
          ></textarea>
        </div>

        <!-- Additional Information -->
        <div>
          <h3 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {{ $t('mod.services.notes') }}
          </h3>

          <textarea
            v-model="form.notes"
            rows="3"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent outline-none transition-all resize-none"
            :placeholder="$t('mod.services.notes_placeholder')"
          ></textarea>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="sticky bottom-0 bg-slate-50 border-t border-slate-200 px-6 py-4 rounded-b-xl flex items-center justify-between gap-3">
        <button
          @click="$emit('close')"
          class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
        >
          {{ $t('core.common.cancel') }}
        </button>

        <button
          @click="handleSubmit"
          :disabled="!isValid || loading"
          :class="[
            'px-5 py-2.5 text-sm font-bold text-white rounded-lg transition-all',
            isValid && !loading
              ? 'bg-rose-600 hover:bg-rose-700 shadow-sm'
              : 'bg-slate-300 cursor-not-allowed'
          ]"
        >
          <span v-if="!loading">{{ $t('core.common.save') }}</span>
          <span v-else class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $t('core.common.saving') }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useOffersStore } from '../store/store'

// Props & Emits
const props = defineProps<{
  service?: any
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

// i18n
const { t: $t } = useI18n()

// Store
const store = useOffersStore()

// Form State
const form = ref({
  title: '',
  category: '',
  description: '',
  duration: '',
  price: null as number | null,
  status: 'aktiv',
  notes: ''
})

const loading = ref(false)

// Validation
const isValid = computed(() => {
  return form.value.title.trim().length > 0 && form.value.status
})

// Initialize form if editing
onMounted(() => {
  if (props.service) {
    form.value = {
      title: props.service.title || props.service.name || '',
      category: props.service.category || '',
      description: props.service.description || '',
      duration: props.service.duration || '',
      price: props.service.price || null,
      status: props.service.status || 'aktiv',
      notes: props.service.notes || ''
    }
  }
})

// Submit Handler
const handleSubmit = async () => {
  if (!isValid.value || loading.value) return

  loading.value = true

  try {
    const payload = {
      ...form.value,
      id: props.service?.id
    }

    const success = await store.save(payload)

    if (success) {
      emit('saved')
      emit('close')
    } else {
      alert($t('mod.services.messages.save_error'))
    }
  } catch (error) {
    console.error('Save error:', error)
    alert($t('mod.services.messages.save_error'))
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
