<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
      <!-- Sticky Header -->
      <div class="sticky top-0 bg-white border-b border-slate-200 p-6 rounded-t-xl z-10">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-slate-900">
              {{ course ? $t('mod.offers.kurse.edit_course') : $t('mod.offers.kurse.add_course') }}
            </h2>
            <p class="text-sm text-slate-500 mt-1">{{ $t('mod.offers.kurse.form_subtitle') }}</p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-400 hover:text-slate-600"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Basic Information -->
        <div>
          <h3 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $t('mod.offers.kurse.basic_info') }}
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Course Title -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.title') }} <span class="text-rose-600">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                placeholder="e.g. Yoga for Beginners"
              >
            </div>

            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.category') }}
              </label>
              <input
                v-model="form.category"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                placeholder="e.g. Wellness, Fitness"
              >
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.status') }} <span class="text-rose-600">*</span>
              </label>
              <select
                v-model="form.status"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all bg-white"
              >
                <option value="active">{{ $t('core.status.active') }}</option>
                <option value="inactive">{{ $t('core.status.inactive') }}</option>
                <option value="full">{{ $t('mod.offers.kurse.status_full') }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Schedule & Capacity -->
        <div>
          <h3 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            {{ $t('mod.offers.kurse.schedule_capacity') }}
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Start Date -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.start_date') }}
              </label>
              <input
                v-model="form.start_date"
                type="date"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
              >
            </div>

            <!-- End Date -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.end_date') }}
              </label>
              <input
                v-model="form.end_date"
                type="date"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
              >
            </div>

            <!-- Start Time -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.start_time') }}
              </label>
              <input
                v-model="form.start_time"
                type="time"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
              >
            </div>

            <!-- Duration (minutes) -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.duration') }} ({{ $t('common.minutes') }})
              </label>
              <input
                v-model="form.duration_minutes"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                placeholder="60"
              >
            </div>

            <!-- Max Participants -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.offers.kurse.max_participants') }}
              </label>
              <input
                v-model="form.max_participants"
                type="number"
                min="1"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                placeholder="12"
              >
            </div>

            <!-- Current Participants (read-only for new courses) -->
            <div v-if="course">
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.offers.kurse.current_participants') }}
              </label>
              <input
                v-model="form.current_participants"
                type="number"
                readonly
                class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-600 outline-none"
              >
            </div>
          </div>
        </div>

        <!-- Pricing -->
        <div>
          <h3 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $t('mod.offers.pricing') }}
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Price -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.price') }} <span class="text-rose-600">*</span>
              </label>
              <input
                v-model="form.price"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all"
                placeholder="99.00"
              >
            </div>

            <!-- Currency -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.currency') }}
              </label>
              <select
                v-model="form.currency"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all bg-white"
              >
                <option value="CHF">CHF</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div>
          <h3 class="text-base font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
            {{ $t('fields.description') }}
          </h3>

          <textarea
            v-model="form.description"
            rows="4"
            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-all resize-none"
            placeholder="Describe the course content, requirements, goals..."
          ></textarea>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="p-4 bg-rose-50 border border-rose-200 rounded-lg">
          <div class="flex items-center gap-2 text-rose-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ error }}</span>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors"
          >
            {{ $t('core.common.cancel') }}
          </button>

          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-2.5 text-sm font-bold text-white bg-accent-500 hover:bg-accent-700 rounded-xl shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? $t('core.common.saving') : $t('core.common.save') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import httpBase from '@assets/http'

interface Props {
  course?: any
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const { t: $t } = useI18n()
const http = httpBase.module('offers')

// Form State
const loading = ref(false)
const error = ref('')

const form = reactive({
  title: props.course?.title || '',
  category: props.course?.category || '',
  status: props.course?.status || 'active',
  start_date: props.course?.start_date || '',
  end_date: props.course?.end_date || '',
  start_time: props.course?.start_time || '',
  duration_minutes: props.course?.duration_minutes || 60,
  max_participants: props.course?.max_participants || 12,
  current_participants: props.course?.current_participants || 0,
  price: props.course?.price || '',
  currency: props.course?.currency || 'CHF',
  description: props.course?.description || '',
  type: 'course' // Mark as course type
})

// Handle form submission
const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    if (props.course) {
      // Update existing course
      await http.put(`${props.course.id}`, form)
    } else {
      // Create new course
      await http.post('', form)
    }
    emit('saved')
  } catch (e: any) {
    error.value = e.response?.data?.message || $t('core.errors.save_failed')
    console.error('Failed to save course:', e)
  } finally {
    loading.value = false
  }
}
</script>
