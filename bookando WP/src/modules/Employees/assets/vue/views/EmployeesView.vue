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

    <!-- Grid View - Employee Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 p-6">
      <div
        v-for="employee in filteredEmployees"
        :key="employee.id"
        class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300 flex flex-col"
      >
        <!-- Card Header / Banner -->
        <div class="h-24 bg-gradient-to-r from-slate-800 to-slate-900 relative">
          <div class="absolute top-4 right-4">
            <span :class="[
              'px-2.5 py-1 text-[10px] font-bold uppercase rounded-full shadow-sm border border-white/10',
              employee.status === 'active' ? 'bg-emerald-500 text-white' :
              employee.status === 'vacation' ? 'bg-amber-500 text-white' : 'bg-slate-500 text-white'
            ]">
              {{ employee.status }}
            </span>
          </div>
        </div>

        <!-- Card Content -->
        <div class="px-6 flex-1 flex flex-col relative">
          <!-- Avatar - Overlapping Header -->
          <div class="-mt-12 mb-3 flex justify-between items-end">
            <div class="w-24 h-24 rounded-xl border-4 border-white shadow-md bg-white overflow-hidden flex items-center justify-center relative">
              <img
                v-if="employee.avatar"
                :src="employee.avatar"
                alt="Avatar"
                class="w-full h-full object-cover"
              >
              <div
                v-else
                class="w-full h-full bg-brand-100 text-brand-600 flex items-center justify-center text-3xl font-bold"
              >
                {{ getInitials(employee) }}
              </div>
            </div>
            <div class="mb-1">
              <button
                @click="handleEdit(employee)"
                class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition-colors"
              >
                <Edit2Icon :size="18" />
              </button>
            </div>
          </div>

          <!-- Info -->
          <div class="mb-6">
            <h3 class="font-bold text-lg text-slate-900 truncate">
              {{ employee.first_name }} {{ employee.last_name }}
            </h3>
            <p class="text-brand-600 font-medium text-sm">{{ employee.position || 'Employee' }}</p>

            <div class="mt-4 space-y-2.5">
              <div class="flex items-center gap-3 text-sm text-slate-600">
                <MailIcon :size="16" class="text-slate-400 shrink-0" />
                <span class="truncate">{{ employee.email }}</span>
              </div>
              <div v-if="employee.department" class="flex items-center gap-3 text-sm text-slate-600">
                <BriefcaseIcon :size="16" class="text-slate-400 shrink-0" />
                <span class="truncate">{{ employee.department }}</span>
              </div>
              <div v-if="employee.phone" class="flex items-center gap-3 text-sm text-slate-600">
                <PhoneIcon :size="16" class="text-slate-400 shrink-0" />
                <span class="truncate">{{ employee.phone }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center mt-auto">
          <div class="flex flex-col">
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">
              {{ $t('mod.employees.joined') }}
            </span>
            <span class="text-xs font-medium text-slate-700">
              {{ employee.hire_date || 'N/A' }}
            </span>
          </div>
          <div v-if="employee.role" class="flex items-center gap-1.5 px-2 py-1 rounded bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold">
            <ShieldIcon :size="12" /> {{ employee.role }}
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredEmployees.length === 0" class="col-span-full">
        <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
          <BriefcaseIcon :size="48" class="mx-auto mb-4 text-slate-300" />
          <h3 class="text-lg font-bold text-slate-800 mb-2">{{ $t('mod.employees.no_results') }}</h3>
          <p class="text-slate-600 mb-6">{{ $t('mod.employees.no_results_desc') }}</p>
          <button
            @click="openCreateDialog"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold transition-colors"
          >
            <PlusIcon :size="18" />
            {{ $t('mod.employees.actions.add') }}
          </button>
        </div>
      </div>
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
  Shield as ShieldIcon
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
