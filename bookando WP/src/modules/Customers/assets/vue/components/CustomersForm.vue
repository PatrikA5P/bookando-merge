<template>
  <!-- Customers Form Modal - Full Implementation -->
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="sticky top-0 bg-white border-b border-slate-200 p-6 rounded-t-xl z-10">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-slate-900">
              {{ customer ? $t('mod.customers.edit_customer') : $t('mod.customers.add_customer') }}
            </h2>
            <p class="text-sm text-slate-500 mt-1">
              {{ $t('mod.customers.form_description') }}
            </p>
          </div>
          <button
            @click="$emit('close')"
            class="p-2 hover:bg-slate-100 rounded-lg transition-colors"
            :aria-label="$t('common.close')"
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
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.customers.personal_info') }}</h3>
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
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
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
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
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
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
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
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
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
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
              >
                <option value="">{{ $t('common.please_select') }}</option>
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
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
              >
            </div>
          </div>
        </div>

        <!-- Address -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.customers.address_info') }}</h3>
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
                :placeholder="$t('fields.street_placeholder')"
              >
            </div>

            <!-- Address Line 2 (Address Supplement) -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.address_line_2') }}
              </label>
              <input
                v-model="form.address_line_2"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                :placeholder="$t('fields.address_line_2_placeholder')"
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
                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
                  :placeholder="$t('fields.zip')"
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
                  class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
                  :placeholder="$t('fields.city')"
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
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-emerald-500 outline-none transition-colors"
                :placeholder="$t('fields.country')"
              >
            </div>
          </div>
        </div>

        <!-- Additional Info -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.customers.additional_info') }}</h3>
          <div class="space-y-4">
            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.notes') }}
              </label>
              <textarea
                v-model="form.notes"
                rows="3"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors resize-none"
                :placeholder="$t('fields.notes_placeholder')"
              ></textarea>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">
                {{ $t('fields.status') }}
              </label>
              <select
                v-model="form.status"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
              >
                <option value="active">{{ $t('core.status.active') }}</option>
                <option value="blocked">{{ $t('core.status.blocked') }}</option>
                <option value="deleted">{{ $t('core.status.deleted') }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Custom Fields -->
        <div>
          <h3 class="text-sm font-bold text-slate-700 uppercase mb-4">{{ $t('mod.customers.custom_fields') }}</h3>
          <div class="space-y-3">
            <!-- Existing Custom Fields -->
            <div v-for="(field, index) in form.custom_fields" :key="index" class="flex gap-2">
              <input
                v-model="field.key"
                type="text"
                class="w-1/3 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                :placeholder="$t('fields.custom_field_name')"
              >
              <input
                v-model="field.value"
                type="text"
                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-brand-500 outline-none transition-colors"
                :placeholder="$t('fields.custom_field_value')"
              >
              <button
                type="button"
                @click="removeCustomField(index)"
                class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"
                :aria-label="$t('common.delete')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>

            <!-- Add Custom Field Button -->
            <button
              type="button"
              @click="addCustomField"
              class="w-full py-2.5 border-2 border-dashed border-slate-300 rounded-lg text-slate-600 hover:border-brand-500 hover:text-brand-600 font-medium transition-colors flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              {{ $t('mod.customers.add_custom_field') }}
            </button>

            <p class="text-xs text-slate-500 italic">
              {{ $t('mod.customers.custom_fields_help') }}
            </p>
          </div>
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

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-5 py-2.5 border border-slate-300 text-slate-700 rounded-xl hover:bg-slate-50 font-medium transition-colors"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-5 py-2.5 bg-accent-500 hover:bg-accent-700 text-white rounded-xl font-bold shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span>{{ $t('common.save') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCustomersStore } from '../store/store'

const props = defineProps<{
  customer?: any
}>()

const emit = defineEmits<{
  close: []
  saved: []
}>()

const { t: $t } = useI18n()
const store = useCustomersStore()

const loading = ref(false)
const error = ref('')

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  gender: '',
  birthday: '',
  street: '',
  address_line_2: '',
  zip: '',
  city: '',
  country: '',
  notes: '',
  status: 'active',
  custom_fields: [] as Array<{ key: string; value: string }>
})

onMounted(() => {
  if (props.customer) {
    form.value = {
      first_name: props.customer.first_name || '',
      last_name: props.customer.last_name || '',
      email: props.customer.email || '',
      phone: props.customer.phone || '',
      gender: props.customer.gender || '',
      birthday: props.customer.birthday || '',
      street: props.customer.street || props.customer.address || '', // Fallback für migration
      address_line_2: props.customer.address_line_2 || '',
      zip: props.customer.zip || '',
      city: props.customer.city || '',
      country: props.customer.country || '',
      notes: props.customer.notes || '',
      status: props.customer.status || 'active',
      custom_fields: props.customer.custom_fields || []
    }
  }
})

// Custom Fields Management
const addCustomField = () => {
  form.value.custom_fields.push({ key: '', value: '' })
}

const removeCustomField = (index: number) => {
  form.value.custom_fields.splice(index, 1)
}

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    // Use store.save() which handles both create and update
    const customer = props.customer
      ? { id: props.customer.id, ...form.value }
      : form.value

    const success = await store.save(customer)

    if (success) {
      emit('saved')
      emit('close')
    } else {
      error.value = store.error || $t('core.actions.save.error')
    }
  } catch (e: any) {
    error.value = e.message || $t('core.actions.save.error')
  } finally {
    loading.value = false
  }
}
</script>
