<script setup lang="ts">
/**
 * Mitarbeiter-Modul — Mitarbeiterverwaltung
 *
 * Kartenbasierte Übersicht aller Mitarbeiter.
 * TODO: Vue Query für Server-State, Bulk-Operationen,
 *       Kalender-Integration, Abwesenheitsverwaltung
 */
import { ref, computed } from 'vue';
import ModuleLayout from '@/components/layout/ModuleLayout.vue';

interface Employee {
  id: string;
  firstName: string;
  lastName: string;
  role: string;
  email: string;
  phone: string;
  status: 'active' | 'inactive' | 'vacation';
  avatar?: string;
}

const searchQuery = ref('');

// Mock-Daten (TODO: durch API-Calls ersetzen)
const employees = ref<Employee[]>([
  { id: 'emp-001', firstName: 'Lisa', lastName: 'Weber', role: 'Friseurin', email: 'lisa@beispiel.ch', phone: '+41 79 111 22 33', status: 'active' },
  { id: 'emp-002', firstName: 'Marco', lastName: 'Bianchi', role: 'Barbier', email: 'marco@beispiel.ch', phone: '+41 79 222 33 44', status: 'active' },
  { id: 'emp-003', firstName: 'Sarah', lastName: 'Keller', role: 'Kosmetikerin', email: 'sarah@beispiel.ch', phone: '+41 79 333 44 55', status: 'vacation' },
  { id: 'emp-004', firstName: 'Thomas', lastName: 'Brunner', role: 'Masseur', email: 'thomas@beispiel.ch', phone: '+41 79 444 55 66', status: 'inactive' },
]);

const filteredEmployees = computed(() => {
  if (!searchQuery.value) return employees.value;
  const q = searchQuery.value.toLowerCase();
  return employees.value.filter(e =>
    `${e.firstName} ${e.lastName}`.toLowerCase().includes(q) ||
    e.role.toLowerCase().includes(q) ||
    e.email.toLowerCase().includes(q)
  );
});

const statusLabel: Record<string, string> = {
  active: 'Aktiv',
  inactive: 'Inaktiv',
  vacation: 'Urlaub',
};

const statusColor: Record<string, string> = {
  active: 'bg-emerald-100 text-emerald-700',
  inactive: 'bg-slate-100 text-slate-600',
  vacation: 'bg-amber-100 text-amber-700',
};

function getInitials(emp: Employee): string {
  return `${emp.firstName[0]}${emp.lastName[0]}`;
}
</script>

<template>
  <ModuleLayout
    module-name="employees"
    title="Mitarbeiter"
    :subtitle="`${employees.length} Mitarbeiter`"
    :show-fab="true"
    fab-label="Neuen Mitarbeiter erstellen"
  >
    <template #header-actions>
      <button
        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors hidden md:inline-flex items-center gap-1.5"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Neuer Mitarbeiter
      </button>
    </template>

    <!-- Suchleiste -->
    <div class="mb-6">
      <div class="relative max-w-md">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Mitarbeiter suchen..."
          class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors"
        />
      </div>
    </div>

    <!-- Mitarbeiter-Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div
        v-for="emp in filteredEmployees"
        :key="emp.id"
        class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow cursor-pointer group"
      >
        <div class="flex items-start gap-4">
          <!-- Avatar -->
          <div class="w-12 h-12 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-semibold text-sm shrink-0 group-hover:bg-slate-300 transition-colors">
            {{ getInitials(emp) }}
          </div>
          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h3 class="text-sm font-semibold text-slate-900 truncate">
              {{ emp.firstName }} {{ emp.lastName }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ emp.role }}</p>
            <span
              :class="[
                'inline-block mt-2 px-2 py-0.5 text-xs font-medium rounded-full',
                statusColor[emp.status],
              ]"
            >
              {{ statusLabel[emp.status] }}
            </span>
          </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5">
          <p class="text-xs text-slate-500 truncate">
            <svg class="inline w-3.5 h-3.5 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            {{ emp.email }}
          </p>
          <p class="text-xs text-slate-500">
            <svg class="inline w-3.5 h-3.5 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            {{ emp.phone }}
          </p>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="filteredEmployees.length === 0" class="text-center py-16">
      <div class="w-16 h-16 mx-auto bg-slate-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <h3 class="text-sm font-medium text-slate-900">Keine Mitarbeiter gefunden</h3>
      <p class="text-sm text-slate-500 mt-1">Passen Sie Ihre Suche an oder erstellen Sie einen neuen Mitarbeiter.</p>
    </div>
  </ModuleLayout>
</template>
