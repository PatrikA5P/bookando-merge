// src/modules/customers/actions.ts
import { useModuleActions, getBulkActionOptions, type ActionKey } from '@core/Composables/useModuleActions'
import { useCustomersStore } from './assets/vue/store/store'

export function useCustomerActions() {
  const store = useCustomersStore()
  const { loading, perform } = useModuleActions('customers')

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

/** Optionaler Helper fürs Modul: erzeugt die Bulk-Options mit i18n */
export function customersBulkOptions(t: (k: string) => string) {
  // Beispiel: 'export' im Kundenmodul erlauben → ist bereits im Registry-Default enthalten.
  // Wenn du etwas ausschließen willst: getBulkActionOptions(t, 'customers', { exclude: ['export'] })
  return getBulkActionOptions(t, 'customers')
}
