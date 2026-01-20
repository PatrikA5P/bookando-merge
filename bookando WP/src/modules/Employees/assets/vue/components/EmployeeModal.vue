<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-200 flex justify-between items-center bg-white">
        <div>
          <h3 class="text-xl font-bold text-slate-800">
            {{ employee ? $t('common.edit') + ' Employee' : $t('employees.actions.add') }}
          </h3>
          <p class="text-sm text-slate-500">
            {{ employee ? `ID: ${employee.id}` : 'Create a new staff profile' }}
          </p>
        </div>
        <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 p-1 rounded-md hover:bg-slate-100">
          <XIcon :size="24" />
        </button>
      </div>

      <!-- Modal Tabs -->
      <div class="flex border-b border-slate-200 bg-slate-50 px-6">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'py-3 px-4 text-sm font-medium flex items-center gap-2 border-b-2 transition-colors',
            activeTab === tab.id
              ? 'border-brand-600 text-brand-600 bg-white'
              : 'border-transparent text-slate-500 hover:text-slate-700'
          ]"
        >
          <component :is="tab.icon" :size="16" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Scrollable Content -->
      <div class="flex-1 overflow-y-auto p-6 md:p-8 bg-slate-50/30">
        <!-- Profile Tab -->
        <div v-if="activeTab === 'profile'" class="space-y-8 max-w-3xl mx-auto">
          <!-- Avatar Section -->
          <div class="flex items-center gap-6 p-4 bg-white border border-slate-200 rounded-xl shadow-sm">
            <div class="w-20 h-20 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
              <img v-if="formData.avatar" :src="formData.avatar" alt="Profile" class="w-full h-full object-cover" />
              <UserIcon v-else :size="32" class="text-slate-300" />
            </div>
            <div>
              <h4 class="font-medium text-slate-800 mb-1">Profile Picture</h4>
              <p class="text-xs text-slate-500 mb-3">Recommended dimensions: 300x300px.</p>
              <div class="flex gap-3">
                <button
                  @click="$refs.fileInput.click()"
                  class="px-3 py-1.5 border border-slate-300 rounded-md text-sm font-medium text-slate-700 hover:bg-slate-50 flex items-center gap-2 bg-white"
                >
                  <UploadIcon :size="14" /> Upload
                </button>
                <button
                  v-if="formData.avatar"
                  @click="formData.avatar = ''"
                  class="px-3 py-1.5 text-rose-600 hover:text-rose-700 text-sm font-medium hover:bg-rose-50 rounded-md"
                >
                  Remove
                </button>
                <input
                  ref="fileInput"
                  type="file"
                  @change="handleImageUpload"
                  class="hidden"
                  accept="image/*"
                />
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label :class="labelClass">{{ $t('common.first_name') }}</label>
              <input type="text" :class="inputClass" v-model="formData.firstName" />
            </div>
            <div>
              <label :class="labelClass">{{ $t('common.last_name') }}</label>
              <input type="text" :class="inputClass" v-model="formData.lastName" />
            </div>
            <div>
              <label :class="labelClass">{{ $t('common.email') }}</label>
              <input type="email" :class="inputClass" v-model="formData.email" />
            </div>
            <div>
              <label :class="labelClass">{{ $t('common.phone') }}</label>
              <div class="flex gap-2">
                <div class="w-32 shrink-0">
                  <SearchableSelect
                    v-model="phoneDetails.code"
                    :options="dialCodeOptions"
                    :placeholder="'+41'"
                  >
                    <template #option="{ option }">
                      <span class="font-mono">{{ option.label }}</span>
                    </template>
                  </SearchableSelect>
                </div>
                <input
                  type="tel"
                  :class="inputClass + ' flex-1'"
                  v-model="phoneDetails.number"
                  placeholder="79 123 45 67"
                />
              </div>
            </div>
            <div>
              <label :class="labelClass">{{ $t('common.gender') }}</label>
              <select :class="inputClass" v-model="formData.gender">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div>
              <label :class="labelClass">{{ $t('common.birthday') }}</label>
              <input type="date" :class="inputClass" v-model="formData.birthday" />
            </div>
            <div class="col-span-full">
              <label :class="labelClass">Description / Bio</label>
              <textarea :class="inputClass" rows="3" v-model="formData.description"></textarea>
            </div>
          </div>
        </div>

        <!-- Address Tab -->
        <div v-else-if="activeTab === 'address'" class="max-w-2xl mx-auto space-y-6">
          <h4 class="font-bold text-slate-800 text-lg mb-4 border-b pb-2">Location Details</h4>
          <div>
            <label :class="labelClass">{{ $t('common.address') }}</label>
            <input type="text" :class="inputClass" v-model="formData.address" placeholder="Street and Number" />
          </div>
          <div class="grid grid-cols-2 gap-6">
            <div>
              <label :class="labelClass">Zip Code</label>
              <input type="text" :class="inputClass" v-model="formData.zip" />
            </div>
            <div>
              <label :class="labelClass">City</label>
              <input type="text" :class="inputClass" v-model="formData.city" />
            </div>
          </div>
          <div>
            <label :class="labelClass">Country</label>
            <SearchableSelect
              v-model="formData.country"
              :options="countryOptions"
              placeholder="Select Country..."
            />
          </div>
        </div>
        <!-- HR Tab -->
        <div v-else-if="activeTab === 'hr'" class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
          <div class="space-y-6">
            <h4 class="font-bold text-slate-800 border-b pb-2">{{ $t('employees.employment') }}</h4>
            <div>
              <label :class="labelClass">{{ $t('employees.position') }}</label>
              <input type="text" :class="inputClass" v-model="formData.position" />
            </div>
            <div>
              <label :class="labelClass">{{ $t('employees.department') }}</label>
              <input type="text" :class="inputClass" v-model="formData.department" />
            </div>
            <div>
              <label :class="labelClass">Badge ID / Clock-in ID</label>
              <input type="text" :class="inputClass" v-model="formData.badgeId" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label :class="labelClass">{{ $t('employees.hire_date') }}</label>
                <input type="date" :class="inputClass" v-model="formData.hireDate" />
              </div>
              <div>
                <label :class="labelClass">Exit Date (Optional)</label>
                <input type="date" :class="inputClass" v-model="formData.exitDate" />
              </div>
            </div>
          </div>
          <div class="space-y-6">
            <h4 class="font-bold text-slate-800 border-b pb-2">{{ $t('common.status') }} & Access</h4>
            <div>
              <label :class="labelClass">{{ $t('common.status') }}</label>
              <select :class="inputClass" v-model="formData.status">
                <option value="Active">Active</option>
                <option value="Vacation">Vacation</option>
                <option value="Sick Leave">Sick Leave</option>
                <option value="Pause">Pause</option>
                <option value="Terminated">Terminated</option>
              </select>
            </div>
            <div>
              <label :class="labelClass">{{ $t('employees.role') }}</label>
              <select :class="inputClass" v-model="formData.role">
                <option value="Admin">Admin</option>
                <option value="Manager">Manager</option>
                <option value="Employee">Employee</option>
              </select>
            </div>
            <div>
              <label :class="labelClass">Hub Password</label>
              <div class="relative">
                <input
                  :type="showPassword ? 'text' : 'password'"
                  :class="inputClass + ' pr-10'"
                  v-model="formData.hubPassword"
                  placeholder="Set password"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                  <EyeOffIcon v-if="showPassword" :size="16" />
                  <EyeIcon v-else :size="16" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Services Tab -->
        <div v-else-if="activeTab === 'services'" class="max-w-3xl mx-auto">
          <h4 class="font-bold text-slate-800 border-b pb-2 mb-6">Assigned Services & Courses</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="service in availableServices"
              :key="service"
              @click="toggleService(service)"
              :class="[
                'flex items-center gap-3 p-4 rounded-lg border cursor-pointer transition-all shadow-sm',
                formData.assignedServices.includes(service)
                  ? 'bg-brand-50 border-brand-200 ring-1 ring-brand-200'
                  : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50'
              ]"
            >
              <div
                :class="[
                  'w-5 h-5 rounded border flex items-center justify-center shrink-0',
                  formData.assignedServices.includes(service)
                    ? 'bg-brand-600 border-brand-600 text-white'
                    : 'bg-white border-slate-300'
                ]"
              >
                <CheckIcon v-if="formData.assignedServices.includes(service)" :size="12" />
              </div>
              <span
                :class="[
                  'text-sm font-medium',
                  formData.assignedServices.includes(service) ? 'text-brand-800' : 'text-slate-700'
                ]"
              >
                {{ service }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="p-6 border-t border-slate-200 bg-white flex justify-end gap-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium text-sm"
        >
          {{ $t('common.cancel') }}
        </button>
        <button
          @click="handleSave"
          class="px-6 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-medium shadow-sm text-sm"
        >
          {{ $t('common.save') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  X as XIcon,
  User as UserIcon,
  MapPin as MapPinIcon,
  Shield as ShieldIcon,
  Check as CheckIcon,
  Upload as UploadIcon,
  Eye as EyeIcon,
  EyeOff as EyeOffIcon
} from 'lucide-vue-next'
import SearchableSelect from './SearchableSelect.vue'

enum EmployeeStatus {
  ACTIVE = 'Active',
  VACATION = 'Vacation',
  SICK_LEAVE = 'Sick Leave',
  PAUSE = 'Pause',
  TERMINATED = 'Terminated'
}

interface Employee {
  id: string
  firstName: string
  lastName: string
  email: string
  phone?: string
  gender: string
  birthday: string
  position: string
  department: string
  role?: string
  hireDate: string
  status: EmployeeStatus
  address?: string
  zip?: string
  city?: string
  country?: string
  hubPassword?: string
  badgeId?: string
  description?: string
  assignedServices: string[]
  avatar?: string
  exitDate?: string
}

interface Props {
  employee: Employee | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
  save: [employee: Employee]
}>()

const { t: $t } = useI18n()

const activeTab = ref<'profile' | 'address' | 'hr' | 'services'>('profile')
const showPassword = ref(false)
const fileInput = ref<HTMLInputElement>()

const tabs = [
  { id: 'profile', icon: UserIcon, label: $t('employees.personal_info') },
  { id: 'address', icon: MapPinIcon, label: $t('common.address') },
  { id: 'hr', icon: ShieldIcon, label: 'HR & Access' },
  { id: 'services', icon: CheckIcon, label: 'Services' }
]

const availableServices = [
  'Deep Tissue Massage',
  'Yoga for Beginners',
  'Nutrition Masterclass',
  'Physiotherapy Init',
  'Weekend Meditation Retreat',
  'Personal Training'
]

const inputClass = 'w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm transition-shadow'
const labelClass = 'block text-sm font-medium text-slate-700 mb-1'

// Parse phone
const parsePhone = (phoneStr?: string) => {
  if (!phoneStr) return { code: '+41', number: '' }
  const parts = phoneStr.split(' ')
  if (parts.length >= 2) {
    return { code: parts[0], number: parts.slice(1).join(' ') }
  }
  return { code: '+41', number: phoneStr }
}

const initialPhone = parsePhone(props.employee?.phone)

const formData = reactive<Employee>(
  props.employee
    ? { ...props.employee }
    : {
        id: '',
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        gender: 'Male',
        birthday: '',
        position: '',
        department: '',
        hireDate: '',
        status: EmployeeStatus.ACTIVE,
        role: 'Employee',
        address: '',
        zip: '',
        city: '',
        country: '',
        assignedServices: [],
        description: '',
        hubPassword: '',
        badgeId: '',
        avatar: ''
      }
)

const phoneDetails = reactive({
  code: initialPhone.code,
  number: initialPhone.number
})

// Update phone when parts change
watch(
  phoneDetails,
  () => {
    formData.phone = phoneDetails.number ? phoneDetails.code + ' ' + phoneDetails.number : ''
  },
  { deep: true }
)

const countries = [
  { name: 'Switzerland', code: 'CH', dial_code: '+41', flag: '🇨🇭' },
  { name: 'Germany', code: 'DE', dial_code: '+49', flag: '🇩🇪' },
  { name: 'Austria', code: 'AT', dial_code: '+43', flag: '🇦🇹' },
  { name: 'France', code: 'FR', dial_code: '+33', flag: '🇫🇷' },
  { name: 'Italy', code: 'IT', dial_code: '+39', flag: '🇮🇹' },
  { name: 'United States', code: 'US', dial_code: '+1', flag: '🇺🇸' }
]

const dialCodeOptions = countries.map(c => ({
  value: c.dial_code,
  label: c.flag + ' ' + c.dial_code,
  subLabel: c.name
}))

const countryOptions = countries.map(c => ({
  value: c.name,
  label: c.name,
  subLabel: c.code
}))

const handleImageUpload = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) {
    const reader = new FileReader()
    reader.onloadend = () => {
      formData.avatar = reader.result as string
    }
    reader.readAsDataURL(file)
  }
}

const toggleService = (service: string) => {
  const index = formData.assignedServices.indexOf(service)
  if (index > -1) {
    formData.assignedServices.splice(index, 1)
  } else {
    formData.assignedServices.push(service)
  }
}

const handleSave = () => {
  emit('save', { ...formData })
}
</script>
