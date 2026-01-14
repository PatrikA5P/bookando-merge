import { useModuleActions, getBulkActionOptions, type ActionKey } from '@core/Composables/useModuleActions'
import { useEmployeesStore } from './assets/vue/store/store'

export function useEmployeeActions() {
  const store = useEmployeesStore()
  const { loading, perform } = useModuleActions('employees')

  async function run(
    action: ActionKey,
    ids: number[] = [],
    payload?: any
  ) {
    const res = await perform(action, { ids, payload })
    if (res.ok && ['save','soft_delete','hard_delete','block','activate'].includes(action)) {
      await store.load()
    }
    return res
  }

  return { loading, run }
}

/** Bulk-Options mit i18n fuers Employees-Modul */
export function employeesBulkOptions(t: (k: string) => string) {
  // Optional: exclude / include via Optionen steuern
  return getBulkActionOptions(t, 'employees')
}
