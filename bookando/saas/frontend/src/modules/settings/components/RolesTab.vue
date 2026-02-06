<script setup lang="ts">
import { ref } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import { useI18n } from '@/composables/useI18n';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();

interface Role {
  id: string;
  name: string;
  description: string;
  userCount: number;
  permissions: Record<string, { read: boolean; write: boolean; delete: boolean }>;
}

const modules = ['dashboard', 'appointments', 'customers', 'employees', 'finance', 'offers', 'academy', 'resources'];

const roles = ref<Role[]>([
  {
    id: 'admin',
    name: 'Administrator',
    description: 'Full access to all modules',
    userCount: 2,
    permissions: Object.fromEntries(modules.map(m => [m, { read: true, write: true, delete: true }])),
  },
  {
    id: 'manager',
    name: 'Manager',
    description: 'Can manage most modules',
    userCount: 3,
    permissions: Object.fromEntries(modules.map(m => [m, { read: true, write: true, delete: m !== 'finance' }])),
  },
  {
    id: 'employee',
    name: 'Employee',
    description: 'Limited access',
    userCount: 8,
    permissions: Object.fromEntries(modules.map(m => [m, { read: true, write: ['appointments', 'customers'].includes(m), delete: false }])),
  },
]);

const selectedRole = ref<Role>(roles.value[0]);

function selectRole(role: Role) {
  selectedRole.value = role;
}
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Role list -->
    <div class="space-y-2">
      <div
        v-for="role in roles"
        :key="role.id"
        :class="[
          'p-3 rounded-lg border cursor-pointer transition-all',
          selectedRole.id === role.id
            ? 'border-brand-500 bg-brand-50'
            : 'border-slate-200 hover:border-slate-300',
        ]"
        @click="selectRole(role)"
      >
        <h3 class="text-sm font-semibold text-slate-900">{{ role.name }}</h3>
        <p class="text-xs text-slate-500">{{ role.description }}</p>
        <p class="text-xs text-slate-400 mt-1">{{ role.userCount }} users</p>
      </div>
      <BButton variant="secondary" class="w-full mt-2">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('settings.newRole') }}
      </BButton>
    </div>

    <!-- Permission matrix -->
    <div class="lg:col-span-3">
      <div :class="CARD_STYLES.base">
        <div :class="CARD_STYLES.header">
          <h3 class="text-base font-semibold text-slate-900">{{ selectedRole.name }} — {{ t('settings.permissions') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-slate-200">
                <th class="text-left text-xs font-medium text-slate-500 uppercase tracking-wider px-4 py-3">Module</th>
                <th class="text-center text-xs font-medium text-slate-500 uppercase tracking-wider px-4 py-3">{{ t('settings.read') }}</th>
                <th class="text-center text-xs font-medium text-slate-500 uppercase tracking-wider px-4 py-3">{{ t('settings.write') }}</th>
                <th class="text-center text-xs font-medium text-slate-500 uppercase tracking-wider px-4 py-3">{{ t('settings.deletePermission') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="mod in modules" :key="mod" class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-3 text-sm font-medium text-slate-700">{{ t(`common.modules.${mod}`) }}</td>
                <td class="px-4 py-3 text-center">
                  <input type="checkbox" :checked="selectedRole.permissions[mod]?.read" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="checkbox" :checked="selectedRole.permissions[mod]?.write" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                </td>
                <td class="px-4 py-3 text-center">
                  <input type="checkbox" :checked="selectedRole.permissions[mod]?.delete" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
