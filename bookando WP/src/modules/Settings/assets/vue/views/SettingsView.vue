<template>
  <ModuleLayout
    :hero-title="$t('mod.settings.title')"
    :hero-description="$t('mod.settings.description')"
    :hero-icon="SettingsIcon"
    hero-gradient="bg-gradient-to-br from-slate-700 to-slate-900"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:active-tab="handleTabChange"
    :show-search="false"
  >
    <!-- General Settings Tab -->
    <div v-if="activeTab === 'general'" class="p-6">
      <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
          <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.settings.general') }}</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.system_language') }}</label>
              <select class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                <option value="de">Deutsch (DE)</option>
                <option value="en">English (US)</option>
                <option value="fr">Français (FR)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.timezone') }}</label>
              <select class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                <option value="Europe/Zurich">Europe/Zurich (CET)</option>
                <option value="Europe/Berlin">Europe/Berlin (CET)</option>
                <option value="Europe/Vienna">Europe/Vienna (CET)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.date_format') }}</label>
              <select class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                <option value="DD.MM.YYYY">DD.MM.YYYY</option>
                <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                <option value="YYYY-MM-DD">YYYY-MM-DD</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.currency') }}</label>
              <select class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                <option value="CHF">CHF - Swiss Franc</option>
                <option value="EUR">EUR - Euro</option>
                <option value="USD">USD - US Dollar</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <button class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
              <SaveIcon :size="16" /> {{ $t('mod.settings.save_changes') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Company Settings Tab -->
    <div v-if="activeTab === 'company'" class="p-6">
      <div class="max-w-3xl">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
          <h3 class="text-lg font-bold text-slate-800">{{ $t('mod.settings.company_details') }}</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.company_name') }}</label>
              <input type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" placeholder="Bookando GmbH" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.street') }}</label>
                <input type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.city') }}</label>
                <input type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.zip') }}</label>
                <input type="text" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.country') }}</label>
                <select class="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
                  <option value="CH">Schweiz</option>
                  <option value="DE">Deutschland</option>
                  <option value="AT">Österreich</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.email') }}</label>
                <input type="email" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" />
              </div>

              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.settings.phone') }}</label>
                <input type="tel" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm" />
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <button class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
              <SaveIcon :size="16" /> {{ $t('mod.settings.save_changes') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Integrations Tab -->
    <div v-if="activeTab === 'integrations'" class="p-6">
      <div class="max-w-4xl grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="integration in integrations" :key="integration.id" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
              <div :class="['w-12 h-12 rounded-lg flex items-center justify-center', integration.colorClass]">
                <component :is="integration.icon" :size="24" />
              </div>
              <div>
                <h4 class="font-bold text-slate-800">{{ integration.name }}</h4>
                <p class="text-xs text-slate-500">{{ integration.description }}</p>
              </div>
            </div>
            <div :class="['px-2 py-1 rounded-full text-xs font-medium', integration.connected ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600']">
              {{ integration.connected ? $t('mod.settings.connected') : $t('mod.settings.not_connected') }}
            </div>
          </div>

          <button :class="['w-full px-4 py-2 rounded-lg text-sm font-medium transition-colors', integration.connected ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-brand-600 text-white hover:bg-brand-700']">
            {{ integration.connected ? $t('mod.settings.disconnect') : $t('mod.settings.connect') }}
          </button>
        </div>
      </div>
    </div>

    <!-- License & Plan Tab -->
    <div v-if="activeTab === 'license'" class="p-6">
      <div class="max-w-3xl">
        <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-xl p-8 text-white mb-6">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h3 class="text-2xl font-bold mb-2">{{ $t('mod.settings.professional_plan') }}</h3>
              <p class="text-purple-100">{{ $t('mod.settings.active_until') }}: 31.12.2026</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm px-4 py-2 rounded-lg">
              <CheckCircleIcon :size="24" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <p class="text-purple-100 text-sm mb-1">{{ $t('mod.settings.users') }}</p>
              <p class="text-2xl font-bold">12 / 50</p>
            </div>
            <div>
              <p class="text-purple-100 text-sm mb-1">{{ $t('mod.settings.locations') }}</p>
              <p class="text-2xl font-bold">3 / 10</p>
            </div>
            <div>
              <p class="text-purple-100 text-sm mb-1">{{ $t('mod.settings.appointments') }}</p>
              <p class="text-2xl font-bold">∞</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.settings.license_key') }}</h4>
          <div class="flex gap-2">
            <input type="text" class="flex-1 border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm bg-slate-50" value="XXXX-XXXX-XXXX-XXXX" readonly />
            <button class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm font-medium text-slate-700">
              {{ $t('common.copy') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modules Tab -->
    <div v-if="activeTab === 'modules'" class="p-6">
      <div class="max-w-4xl grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="module in modules" :key="module.id" :class="['bg-white rounded-xl border shadow-sm p-4 flex items-center justify-between', module.enabled ? 'border-brand-200' : 'border-slate-200']">
          <div class="flex items-center gap-3">
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', module.enabled ? 'bg-brand-100 text-brand-600' : 'bg-slate-100 text-slate-400']">
              <component :is="module.icon" :size="20" />
            </div>
            <div>
              <h5 class="font-medium text-slate-800">{{ module.name }}</h5>
              <p class="text-xs text-slate-500">{{ module.description }}</p>
            </div>
          </div>
          <button @click="toggleModule(module.id)" :class="['w-12 h-6 rounded-full transition-colors relative', module.enabled ? 'bg-brand-600' : 'bg-slate-300']">
            <div :class="['absolute top-1 w-4 h-4 bg-white rounded-full transition-transform', module.enabled ? 'translate-x-7' : 'translate-x-1']"></div>
          </button>
        </div>
      </div>
    </div>

    <!-- Permissions Tab -->
    <div v-if="activeTab === 'permissions'" class="p-6">
      <div class="max-w-4xl">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <h3 class="text-lg font-bold text-slate-800 mb-4">{{ $t('mod.settings.roles_permissions') }}</h3>
          <p class="text-sm text-slate-600 mb-6">{{ $t('mod.settings.permissions_coming_soon') }}</p>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="role in roles" :key="role.id" class="border border-slate-200 rounded-lg p-4">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-bold text-slate-800">{{ role.name }}</h4>
                <ShieldIcon :size="18" :class="role.colorClass" />
              </div>
              <p class="text-xs text-slate-500 mb-3">{{ role.description }}</p>
              <p class="text-xs font-medium text-slate-600">{{ role.userCount }} {{ $t('mod.settings.users_assigned') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import {
  Settings as SettingsIcon,
  Globe as GlobeIcon,
  Building as BuildingIcon,
  Link as LinkIcon,
  Key as KeyIcon,
  ToggleLeft as ToggleLeftIcon,
  Lock as LockIcon,
  Save as SaveIcon,
  Mail as MailIcon,
  Video as VideoIcon,
  CreditCard as CreditCardIcon,
  CheckCircle as CheckCircleIcon,
  Shield as ShieldIcon,
  Users as UsersIcon,
  Calendar as CalendarIcon,
  Briefcase as BriefcaseIcon
} from 'lucide-vue-next'

const { t: $t } = useI18n()

// State
const activeTab = ref('general')

// Tabs definition
const tabs = ref([
  { id: 'general', icon: GlobeIcon, label: $t('mod.settings.general') },
  { id: 'company', icon: BuildingIcon, label: $t('mod.settings.company_details') },
  { id: 'integrations', icon: LinkIcon, label: $t('mod.settings.integrations') },
  { id: 'license', icon: KeyIcon, label: $t('mod.settings.license_plan') },
  { id: 'modules', icon: ToggleLeftIcon, label: $t('mod.settings.modules') },
  { id: 'permissions', icon: LockIcon, label: $t('mod.settings.roles_permissions') }
])

// Mock data
const integrations = ref([
  { id: 'google', name: 'Google Calendar', description: 'Sync appointments', icon: CalendarIcon, connected: true, colorClass: 'bg-blue-100 text-blue-600' },
  { id: 'stripe', name: 'Stripe', description: 'Payment processing', icon: CreditCardIcon, connected: true, colorClass: 'bg-purple-100 text-purple-600' },
  { id: 'zoom', name: 'Zoom', description: 'Video conferencing', icon: VideoIcon, connected: false, colorClass: 'bg-indigo-100 text-indigo-600' },
  { id: 'mailchimp', name: 'Mailchimp', description: 'Email marketing', icon: MailIcon, connected: false, colorClass: 'bg-amber-100 text-amber-600' }
])

const modules = ref([
  { id: 'appointments', name: 'Appointments', description: 'Booking system', icon: CalendarIcon, enabled: true },
  { id: 'customers', name: 'Customers', description: 'CRM', icon: UsersIcon, enabled: true },
  { id: 'finance', name: 'Finance', description: 'Accounting', icon: CreditCardIcon, enabled: true },
  { id: 'academy', name: 'Academy', description: 'LMS', icon: BriefcaseIcon, enabled: false }
])

const roles = ref([
  { id: 'admin', name: 'Administrator', description: 'Full access', userCount: 2, colorClass: 'text-rose-600' },
  { id: 'manager', name: 'Manager', description: 'Manage operations', userCount: 5, colorClass: 'text-amber-600' },
  { id: 'staff', name: 'Staff', description: 'Basic access', userCount: 15, colorClass: 'text-blue-600' }
])

// Methods
const handleTabChange = (tabId: string) => {
  activeTab.value = tabId
}

const toggleModule = (moduleId: string) => {
  const module = modules.value.find(m => m.id === moduleId)
  if (module) {
    module.enabled = !module.enabled
  }
}
</script>
