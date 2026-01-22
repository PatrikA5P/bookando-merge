<template>
  <ModuleLayout
    hero-title="Employees"
    :hero-description="$t('mod.employees.description')"
    :hero-icon="BriefcaseIcon"
    hero-gradient="bg-gradient-to-br from-slate-700 to-slate-900"
    :show-search="true"
    v-model:search-query="searchQuery"
    :search-placeholder="$t('mod.employees.search_placeholder')"
    :show-primary-action="true"
    :primary-action-label="$t('mod.employees.actions.add')"
    :primary-action-icon="PlusIcon"
    @primary-action="openCreateDialog"
  >
    <template #actions>
      <button
        @click="handleExportCSV"
        class="flex items-center gap-2 px-4 py-2 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors"
      >
        <DownloadIcon :size="16" />
        <span class="hidden md:inline">{{ $t('common.export') }}</span>
      </button>
    </template>

    <div class="flex-1 overflow-y-auto">
      <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
          <tr>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.employees.table.employee') }}</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.employees.table.contact') }}</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.employees.table.department') }}</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.employees.table.status') }}</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('mod.employees.table.role') }}</th>
            <th class="p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">{{ $t('mod.employees.table.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="employee in filteredEmployees"
            :key="employee.id"
            class="hover:bg-slate-50 transition-colors group"
          >
            <td class="p-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-semibold text-sm uppercase shrink-0 overflow-hidden">
                  <img
                    v-if="employee.avatar_url"
                    :src="employee.avatar_url"
                    alt="Avatar"
                    class="w-full h-full object-cover"
                  >
                  <span v-else>{{ getInitials(employee) }}</span>
                </div>
                <div>
                  <div class="font-medium text-slate-900">{{ employee.first_name }} {{ employee.last_name }}</div>
                  <div class="text-xs text-slate-500">#{{ employee.id }}</div>
                </div>
              </div>
            </td>
            <td class="p-4">
              <div class="flex flex-col gap-1 text-sm text-slate-600">
                <div v-if="employee.email" class="flex items-center gap-2">
                  <MailIcon :size="14" class="text-slate-400" />
                  <span class="truncate">{{ employee.email }}</span>
                </div>
                <div v-if="employee.phone" class="flex items-center gap-2">
                  <PhoneIcon :size="14" class="text-slate-400" />
                  <span class="truncate">{{ employee.phone }}</span>
                </div>
                <span v-if="!employee.email && !employee.phone" class="text-slate-400">—</span>
              </div>
            </td>
            <td class="p-4">
              <div class="flex flex-col gap-0.5 text-sm text-slate-600">
                <div>{{ employee.position || $t('mod.employees.fallback_role') }}</div>
                <div v-if="employee.department" class="text-xs text-slate-400">{{ employee.department }}</div>
              </div>
            </td>
            <td class="p-4">
              <span :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border',
                employee.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '',
                employee.status === 'vacation' ? 'bg-amber-50 text-amber-700 border-amber-100' : '',
                employee.status === 'blocked' ? 'bg-rose-50 text-rose-700 border-rose-100' : '',
                employee.status === 'deleted' ? 'bg-slate-100 text-slate-600 border-slate-200' : ''
              ]">
                {{ employee.status }}
              </span>
            </td>
            <td class="p-4">
              <span v-if="employee.role" class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold">
                <ShieldIcon :size="12" /> {{ employee.role }}
              </span>
              <span v-else class="text-sm text-slate-400">—</span>
            </td>
            <td class="p-4 text-right">
              <button
                @click="handleEdit(employee)"
                class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-full transition-colors"
              >
                <Edit2Icon :size="18" />
              </button>
            </td>
          </tr>
          <tr v-if="filteredEmployees.length === 0">
            <td colspan="6" class="p-12 text-center">
              <div class="flex flex-col items-center text-slate-400">
                <SearchIcon :size="48" class="mb-4 opacity-20" />
                <p class="text-lg font-medium text-slate-600">{{ $t('mod.employees.no_results') }}</p>
                <p class="text-sm">{{ $t('mod.employees.no_results_desc') }}</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </ModuleLayout>

  <!-- Employee Form Modal -->
  <component
    v-if="showDialog"
    :is="EmployeesForm"
    :employee="editingEmployee"
    @close="showDialog = false"
    @saved="handleSaved"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEmployeesStore } from '../store/store'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import {
  Briefcase as BriefcaseIcon,
  Plus as PlusIcon,
  Download as DownloadIcon,
  Mail as MailIcon,
  Phone as PhoneIcon,
  Edit2 as Edit2Icon,
  Shield as ShieldIcon,
  Search as SearchIcon
} from 'lucide-vue-next'

const EmployeesForm = defineAsyncComponent(() => import('../components/EmployeesForm.vue'))

const { t: $t } = useI18n()
const store = useEmployeesStore()

// State
const searchQuery = ref('')
const showDialog = ref(false)
const editingEmployee = ref<any>(null)

// Computed
const filteredEmployees = computed(() => {
  const query = searchQuery.value.toLowerCase()
  if (!query) return store.items || []

  return (store.items || []).filter((employee: any) =>
    employee.first_name?.toLowerCase().includes(query) ||
    employee.last_name?.toLowerCase().includes(query) ||
    employee.email?.toLowerCase().includes(query) ||
    employee.position?.toLowerCase().includes(query) ||
    employee.department?.toLowerCase().includes(query)
  )
})

// Methods
const getInitials = (employee: any) => {
  const first = employee.first_name?.[0] || ''
  const last = employee.last_name?.[0] || ''
  return `${first}${last}`.toUpperCase()
}

const openCreateDialog = () => {
  editingEmployee.value = null
  showDialog.value = true
}

const handleEdit = (employee: any) => {
  editingEmployee.value = employee
  showDialog.value = true
}

const handleSaved = () => {
  showDialog.value = false
  editingEmployee.value = null
  store.load()
}

const handleExportCSV = () => {
  const headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Position', 'Department', 'Status', 'Hire Date']
  const rows = filteredEmployees.value.map((e: any) => [
    e.id,
    e.first_name,
    e.last_name,
    e.email,
    e.phone || '',
    e.position || '',
    e.department || '',
    e.status,
    e.hire_date || ''
  ])

  const csvContent = [headers, ...rows]
    .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `employees_export_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

// Lifecycle
onMounted(() => {
  store.load()
})
</script>
