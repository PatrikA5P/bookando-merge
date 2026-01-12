/**
 * useTableSelection
 *
 * Composable für Table Selection (Checkbox-Logik)
 * Verwaltet ausgewählte Items und bietet Toggle-Funktionen
 *
 * @example
 * ```typescript
 * const {
 *   selectedItems,
 *   allSelected,
 *   toggleItem,
 *   toggleAll,
 *   isSelected
 * } = useTableSelection({
 *   items: computed(() => props.items),
 *   selectedItems: computed(() => props.selectedItems),
 *   rowKeyField: computed(() => props.rowKeyField),
 *   onUpdate: (selected) => emit('update:selected', selected)
 * })
 * ```
 */

import { computed, type ComputedRef } from 'vue'

export type RowKey = string | number

export interface UseTableSelectionOptions {
  /** Items in der Tabelle */
  items: ComputedRef<any[]>

  /** Aktuell ausgewählte Item-IDs */
  selectedItems: ComputedRef<RowKey[] | undefined>

  /** Feld für Row-Key (default: 'id') */
  rowKeyField?: ComputedRef<string | undefined>

  /** Callback bei Selection-Änderung */
  onUpdate: (selected: RowKey[]) => void
}

export interface UseTableSelectionReturn {
  /** Aktuell ausgewählte Items */
  selectedItems: ComputedRef<RowKey[]>

  /** Sind alle Items ausgewählt? */
  allSelected: ComputedRef<boolean>

  /** Toggle einzelnes Item */
  toggleItem: (id: RowKey, checked: boolean) => void

  /** Toggle alle Items */
  toggleAll: (checked: boolean) => void

  /** Ist Item ausgewählt? */
  isSelected: (item: any) => boolean

  /** Anzahl ausgewählter Items */
  selectedCount: ComputedRef<number>

  /** Sind einige (aber nicht alle) Items ausgewählt? */
  someSelected: ComputedRef<boolean>

  /** Row-Key für Item extrahieren */
  rowKey: (item: any) => RowKey
}

export function useTableSelection(
  options: UseTableSelectionOptions
): UseTableSelectionReturn {
  const {
    items,
    selectedItems: selectedItemsProp,
    rowKeyField,
    onUpdate
  } = options

  // Selected Items (mit Fallback auf leeres Array)
  const selectedItems = computed<RowKey[]>(() => selectedItemsProp.value ?? [])

  // Row-Key Funktion
  function rowKey(item: any): RowKey {
    const field = rowKeyField?.value
    if (field && item?.[field] != null) return item[field]
    return item?.id ?? item?.key ?? item?._id
  }

  // Ist Item ausgewählt?
  function isSelected(item: any): boolean {
    return selectedItems.value.includes(rowKey(item))
  }

  // Toggle einzelnes Item
  function toggleItem(id: RowKey, checked: boolean) {
    const set = new Set(selectedItems.value)
    if (checked) {
      set.add(id)
    } else {
      set.delete(id)
    }
    onUpdate(Array.from(set))
  }

  // Toggle alle Items
  function toggleAll(checked: boolean) {
    const ids = checked ? items.value.map(item => rowKey(item)) : []
    onUpdate(ids)
  }

  // Computed: Sind alle ausgewählt?
  const allSelected = computed<boolean>(() => {
    const itemsArray = items.value
    if (!itemsArray || itemsArray.length === 0) return false
    return itemsArray.every(item => isSelected(item))
  })

  // Anzahl ausgewählter Items
  const selectedCount = computed(() => selectedItems.value.length)

  // Sind einige (aber nicht alle) ausgewählt?
  const someSelected = computed(() => {
    const count = selectedCount.value
    return count > 0 && count < items.value.length
  })

  return {
    selectedItems,
    allSelected,
    toggleItem,
    toggleAll,
    isSelected,
    selectedCount,
    someSelected,
    rowKey
  }
}
