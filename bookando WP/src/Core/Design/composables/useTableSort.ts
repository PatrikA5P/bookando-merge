/**
 * useTableSort
 *
 * Composable für Table Sorting
 * Verwaltet Sort-State und bietet Helper-Funktionen
 *
 * @example
 * ```typescript
 * const {
 *   sortState,
 *   sortColumn,
 *   isSorted,
 *   getSortDirection
 * } = useTableSort({
 *   sortState: computed(() => props.sortState),
 *   onSort: (key) => emit('sort', key)
 * })
 * ```
 */

import { computed, type ComputedRef } from 'vue'

export type SortDirection = 'asc' | 'desc'

export interface SortState {
  key?: string
  direction?: SortDirection
}

export interface UseTableSortOptions {
  /** Aktueller Sort-State */
  sortState: ComputedRef<SortState | undefined>

  /** Callback bei Sort-Änderung */
  onSort: (key: string) => void
}

export interface UseTableSortReturn {
  /** Aktueller Sort-State */
  sortState: ComputedRef<SortState>

  /** Sortiere nach Spalte */
  sortColumn: (key: string) => void

  /** Ist Spalte sortiert? */
  isSorted: (key: string) => boolean

  /** Hole Sort-Direction für Spalte */
  getSortDirection: (key: string) => SortDirection | null

  /** ARIA Sort-Value für Spalte */
  getAriaSort: (key: string) => 'ascending' | 'descending' | 'none'
}

export function useTableSort(options: UseTableSortOptions): UseTableSortReturn {
  const { sortState: sortStateProp, onSort } = options

  // Sort-State mit Fallback
  const sortState = computed<SortState>(() => sortStateProp.value ?? {})

  // Sortiere nach Spalte
  function sortColumn(key: string) {
    onSort(key)
  }

  // Ist Spalte sortiert?
  function isSorted(key: string): boolean {
    return sortState.value.key === key
  }

  // Hole Sort-Direction für Spalte
  function getSortDirection(key: string): SortDirection | null {
    if (!isSorted(key)) return null
    return sortState.value.direction ?? null
  }

  // ARIA Sort-Value
  function getAriaSort(key: string): 'ascending' | 'descending' | 'none' {
    if (!isSorted(key)) return 'none'
    const dir = sortState.value.direction
    if (dir === 'asc') return 'ascending'
    if (dir === 'desc') return 'descending'
    return 'none'
  }

  return {
    sortState,
    sortColumn,
    isSorted,
    getSortDirection,
    getAriaSort
  }
}
