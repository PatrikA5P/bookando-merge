<template>
  <!-- Employees Module - Pure Tailwind, ModuleLayout Pattern -->
  <div class="flex flex-col min-h-full bg-slate-50/50">

    <!-- ==== MOBILE & TABLET LAYOUT (< LG) ==== -->
    <div class="lg:hidden flex flex-col min-h-screen">
      <!-- Sticky Header -->
      <div
        :class="[
          'sticky top-0 left-0 right-0 z-20 transition-transform duration-300 ease-in-out shadow-lg',
          'bg-gradient-to-r from-indigo-700 to-purple-900 text-white',
          isHeaderVisible ? 'translate-y-0' : '-translate-y-full'
        ]"
      >
        <div class="px-4 pt-4 pb-2">
          <div class="flex items-center gap-2 mb-3">
            <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <h2 class="font-bold text-lg">{{ $t('mod.employees.title') }}</h2>
          </div>
        </div>

        <div class="px-4 pb-3 flex gap-2">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="$t('mod.employees.search_placeholder')"
              class="w-full pl-9 pr-4 py-2 rounded-lg text-sm bg-white/10 border border-white/20 text-white placeholder-white/60 focus:bg-white/20 focus:outline-none focus:ring-1 focus:ring-white/50"
            >
          </div>
        </div>
      </div>

      <div class="flex-1 p-4">
        <div class="text-center py-12 text-slate-500">
          <p>{{ $t('common.module_in_development') }}</p>
        </div>
      </div>

      <button
        @click="openCreateDialog"
        class="fixed bottom-6 right-6 w-14 h-14 bg-indigo-600 text-white rounded-full shadow-2xl flex items-center justify-center z-50 hover:bg-indigo-700"
      >
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </div>

    <!-- ==== DESKTOP LAYOUT (≥ LG) ==== -->
    <div class="hidden lg:flex min-h-full p-6 gap-6">
      <div class="flex-1">
        <div class="sticky top-0 z-30 bg-slate-50 pt-6 pb-6 -mt-6">
          <div class="flex gap-6">
            <div class="w-72 bg-gradient-to-br from-indigo-700 to-purple-900 text-white p-6 rounded-xl shadow-lg">
              <div class="flex items-center gap-3 mb-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h2 class="font-bold text-xl">{{ $t('mod.employees.title') }}</h2>
              </div>
              <p class="text-xs text-white/70">{{ $t('mod.employees.description') }}</p>
            </div>

            <div class="flex-1 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
              <div class="flex items-center justify-between">
                <input
                  v-model="searchQuery"
                  type="text"
                  :placeholder="$t('mod.employees.search_placeholder')"
                  class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                <button
                  @click="openCreateDialog"
                  class="ml-3 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold"
                >
                  {{ $t('mod.employees.actions.add') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
          <p class="text-slate-500">{{ $t('common.module_in_development') }}</p>
        </div>
      </div>
    </div>

    <component
      v-if="showDialog"
      :is="EmployeesForm"
      :employee="editingEmployee"
      @close="showDialog = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import { useI18n } from 'vue-i18n'
import { useEmployeesStore } from '../store/store'

const EmployeesForm = defineAsyncComponent(() => import('../components/EmployeesForm.vue'))

const { t: $t } = useI18n()
const store = useEmployeesStore()

const searchQuery = ref('')
const showDialog = ref(false)
const editingEmployee = ref<any>(null)

const scrollDirection = ref<'up' | 'down' | null>(null)
const scrolledToTop = ref(true)
const isHeaderVisible = computed(() => scrollDirection.value === 'up' || scrolledToTop.value)

const openCreateDialog = () => {
  editingEmployee.value = null
  showDialog.value = true
}

const handleSaved = () => {
  showDialog.value = false
  editingEmployee.value = null
  store.fetchAll()
}
</script>
