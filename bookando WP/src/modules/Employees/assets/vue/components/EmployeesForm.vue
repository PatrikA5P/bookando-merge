<template>
  <!-- Employees Form Modal - Simplified Implementation -->
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="sticky top-0 bg-white border-b border-slate-200 p-6 rounded-t-xl z-10">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-slate-900">
              {{ employee ? $t('mod.employees.actions.edit') : $t('mod.employees.actions.add') }}
            </h2>
            <p class="text-sm text-slate-500 mt-1">
              {{ $t('mod.employees.personal_info') }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 hover:bg-slate-100 rounded-lg transition-colors"
            :aria-label="$t('core.common.close')"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <!-- Personal Information -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.employees.personal_info') }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- First Name -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.first_name') }}
                <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.first_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                :placeholder="$t('fields.first_name')"
              >
            </div>

            <!-- Last Name -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.last_name') }}
                <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.last_name"
                type="text"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                :placeholder="$t('fields.last_name')"
              >
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.email') }}
                <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                :placeholder="$t('fields.email')"
              >
            </div>

            <!-- Phone -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.phone') }}
              </label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                :placeholder="$t('fields.phone')"
              >
            </div>

            <!-- Gender -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.gender') }}
              </label>
              <select
                v-model="form.gender"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
              >
                <option value="">{{ $t('core.common.please_select') }}</option>
                <option value="male">{{ $t('core.genders.male') }}</option>
                <option value="female">{{ $t('core.genders.female') }}</option>
                <option value="other">{{ $t('core.genders.other') }}</option>
                <option value="none">{{ $t('core.genders.none') }}</option>
              </select>
            </div>

            <!-- Birthday -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.birthday') }}
              </label>
              <input
                v-model="form.birthday"
                type="date"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
              >
            </div>
          </div>
        </div>

        <!-- Employment Information -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.employees.employment') }}</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Position -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.employees.position') }}
              </label>
              <input
                v-model="form.position"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="e.g. Senior Therapist"
              >
            </div>

            <!-- Department -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.employees.department') }}
              </label>
              <input
                v-model="form.department"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="e.g. Wellness, Fitness"
              >
            </div>

            <!-- Badge ID -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.badge') }}
              </label>
              <input
                v-model="form.badge_id"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="e.g. BADGE-001"
              >
            </div>

            <!-- Role -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.employees.role') }}
              </label>
              <select
                v-model="form.role"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
              >
                <option value="employee">Employee</option>
                <option value="manager">Manager</option>
                <option value="admin">Administrator</option>
              </select>
            </div>

            <!-- Hire Date -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.employees.hire_date') }}
              </label>
              <input
                v-model="form.hire_date"
                type="date"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
              >
            </div>

            <!-- Exit Date -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.employees.exit_date') }}
              </label>
              <input
                v-model="form.exit_date"
                type="date"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
              >
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.status') }}
                <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="form.status"
                required
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
              >
                <option value="active">Active</option>
                <option value="vacation">Vacation</option>
                <option value="sick_leave">Sick Leave</option>
                <option value="pause">Pause</option>
                <option value="terminated">Terminated</option>
              </select>
            </div>

            <!-- Hub Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('mod.employees.hub_password') }}
              </label>
              <input
                v-model="form.hub_password"
                type="password"
                autocomplete="new-password"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="Partner Hub Access"
              >
              <p class="text-xs text-slate-500 mt-1">Password for Partner Hub access</p>
            </div>
          </div>
        </div>

        <!-- Address -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('fields.address') }}</h3>
          <div class="space-y-4">
            <!-- Street Address -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.street') }}
              </label>
              <input
                v-model="form.street"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="e.g. 123 Main Street"
              >
            </div>

            <!-- Address Line 2 -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.address_line_2') }}
              </label>
              <input
                v-model="form.address_line_2"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="Apartment, suite, etc. (optional)"
              >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- ZIP -->
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                  {{ $t('fields.zip') }}
                </label>
                <input
                  v-model="form.zip"
                  type="text"
                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                  placeholder="10001"
                >
              </div>

              <!-- City -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                  {{ $t('fields.city') }}
                </label>
                <input
                  v-model="form.city"
                  type="text"
                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                  placeholder="New York"
                >
              </div>
            </div>

            <!-- Country -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.country') }}
              </label>
              <input
                v-model="form.country"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="United States"
              >
            </div>
          </div>
        </div>

        <!-- Profile & Bio -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.employees.profile') }}</h3>
          <div class="space-y-4">
            <!-- Avatar URL -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.avatar') }}
              </label>
              <input
                v-model="form.avatar"
                type="url"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                placeholder="https://example.com/avatar.jpg"
              >
              <p class="text-xs text-slate-500 mt-1">URL to employee profile picture</p>
            </div>

            <!-- Description / Bio -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.description') }}
              </label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors resize-none"
                placeholder="Employee bio, expertise, certifications..."
              ></textarea>
            </div>

            <!-- Internal Notes -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.notes') }}
              </label>
              <textarea
                v-model="form.notes"
                rows="2"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors resize-none"
                placeholder="Internal notes (not visible to customers)"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Assigned Services -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.employees.assigned_services') }}</h3>
          <div class="space-y-3">
            <p class="text-sm text-slate-600">{{ $t('mod.employees.assigned_services_help') }}</p>
            <!-- TODO: Replace with actual multi-select component or service picker -->
            <div class="p-4 border-2 border-dashed border-slate-200 rounded-lg text-center text-slate-500">
              <p class="text-sm">Service assignment will be implemented in Offers module integration</p>
              <p class="text-xs mt-1">For now, this can be managed via API</p>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="p-4 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-sm">
          {{ error }}
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-5 py-2.5 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium transition-colors"
          >
            {{ $t('core.common.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
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
import { useEmployeesStore } from '../store/store'

interface Props {
  employee?: any
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const { t: $t } = useI18n()
const store = useEmployeesStore()

// Form state
const loading = ref(false)
const error = ref('')

// Initialize form with employee data or empty values
const form = reactive({
  first_name: props.employee?.first_name || '',
  last_name: props.employee?.last_name || '',
  email: props.employee?.email || '',
  phone: props.employee?.phone || '',
  gender: props.employee?.gender || '',
  birthday: props.employee?.birthday || '',
  position: props.employee?.position || '',
  department: props.employee?.department || '',
  badge_id: props.employee?.badge_id || props.employee?.badge || '', // Fallback for migration
  hire_date: props.employee?.hire_date || '',
  exit_date: props.employee?.exit_date || '',
  role: props.employee?.role || 'employee',
  status: props.employee?.status || 'active',
  hub_password: props.employee?.hub_password || '',
  street: props.employee?.street || props.employee?.address || '', // Fallback for migration
  address_line_2: props.employee?.address_line_2 || '',
  zip: props.employee?.zip || '',
  city: props.employee?.city || '',
  country: props.employee?.country || '',
  avatar: props.employee?.avatar || '',
  description: props.employee?.description || '',
  notes: props.employee?.notes || '',
  assigned_services: props.employee?.assigned_services || []
})

// Handle form submission
const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    const employeeData = props.employee
      ? { id: props.employee.id, ...form }
      : form

    const success = await store.save(employeeData)

    if (success) {
      emit('saved')
      emit('close')
    } else {
      error.value = $t('mod.employees.messages.save_error')
    }
  } catch (e: any) {
    error.value = e.message || $t('mod.employees.messages.save_error')
  } finally {
    loading.value = false
  }
}
</script>
