<script setup lang="ts">
/**
 * BTable — Zentrale Tabellen-Komponente
 *
 * Features:
 * - Responsive (horizontales Scrollen auf Mobile)
 * - Sticky Header
 * - Sortierung
 * - Pagination
 * - Empty State
 * - Loading Skeleton
 * - Selektierbare Zeilen
 *
 * Verbesserung gegenüber Referenz:
 * + Zentrale, wiederverwendbare Komponente (Referenz hatte inline-Tabellen pro Modul)
 * + Integrierte Pagination
 * + Loading-Skeleton
 * + a11y (role, aria-sort, etc.)
 */
import { computed } from 'vue';
import { TABLE_STYLES, EMPTY_STATE_STYLES, SKELETON_STYLES } from '@/design';

export interface Column {
  key: string;
  label: string;
  sortable?: boolean;
  align?: 'left' | 'center' | 'right';
  width?: string;
  class?: string;
}

const props = withDefaults(defineProps<{
  columns: Column[];
  data: Record<string, unknown>[];
  loading?: boolean;
  emptyTitle?: string;
  emptyMessage?: string;
  sortBy?: string;
  sortDir?: 'asc' | 'desc';
  page?: number;
  perPage?: number;
  total?: number;
  selectable?: boolean;
  selectedIds?: string[];
}>(), {
  loading: false,
  emptyTitle: 'Keine Einträge',
  emptyMessage: 'Es wurden keine Einträge gefunden.',
  sortDir: 'asc',
  page: 1,
  perPage: 25,
  total: 0,
  selectable: false,
});

const emit = defineEmits<{
  (e: 'sort', column: string): void;
  (e: 'page-change', page: number): void;
  (e: 'per-page-change', perPage: number): void;
  (e: 'row-click', row: Record<string, unknown>): void;
  (e: 'select', ids: string[]): void;
}>();

const totalPages = computed(() => Math.ceil(props.total / props.perPage));
const showPagination = computed(() => props.total > props.perPage);

const pages = computed(() => {
  const result: (number | '...')[] = [];
  const current = props.page;
  const total = totalPages.value;

  if (total <= 7) {
    for (let i = 1; i <= total; i++) result.push(i);
  } else {
    result.push(1);
    if (current > 3) result.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
      result.push(i);
    }
    if (current < total - 2) result.push('...');
    result.push(total);
  }

  return result;
});

function getAlignClass(align?: string) {
  if (align === 'center') return 'text-center';
  if (align === 'right') return 'text-right';
  return 'text-left';
}
</script>

<template>
  <div :class="TABLE_STYLES.container">
    <div :class="TABLE_STYLES.scrollContainer">
      <table :class="TABLE_STYLES.table" role="table">
        <!-- Header -->
        <thead :class="TABLE_STYLES.thead" role="rowgroup">
          <tr role="row">
            <th
              v-for="col in columns"
              :key="col.key"
              :class="[
                col.sortable ? TABLE_STYLES.thSortable : TABLE_STYLES.th,
                getAlignClass(col.align),
                col.class,
              ]"
              :style="col.width ? { width: col.width } : undefined"
              :aria-sort="sortBy === col.key ? (sortDir === 'asc' ? 'ascending' : 'descending') : undefined"
              role="columnheader"
              @click="col.sortable && emit('sort', col.key)"
            >
              <span class="flex items-center gap-1">
                {{ col.label }}
                <svg
                  v-if="col.sortable"
                  class="w-3 h-3 opacity-50"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                </svg>
              </span>
            </th>
          </tr>
        </thead>

        <!-- Body -->
        <tbody :class="TABLE_STYLES.tbody" role="rowgroup">
          <!-- Loading -->
          <template v-if="loading">
            <tr v-for="i in 5" :key="`skeleton-${i}`" role="row">
              <td v-for="col in columns" :key="col.key" :class="TABLE_STYLES.td">
                <div :class="SKELETON_STYLES.text" :style="{ width: `${60 + Math.random() * 40}%` }" />
              </td>
            </tr>
          </template>

          <!-- Empty -->
          <tr v-else-if="data.length === 0" role="row">
            <td :colspan="columns.length">
              <div :class="EMPTY_STATE_STYLES.container">
                <svg :class="EMPTY_STATE_STYLES.icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 :class="EMPTY_STATE_STYLES.title">{{ emptyTitle }}</h3>
                <p :class="EMPTY_STATE_STYLES.description">{{ emptyMessage }}</p>
                <slot name="empty-action" />
              </div>
            </td>
          </tr>

          <!-- Data Rows -->
          <template v-else>
            <tr
              v-for="row in data"
              :key="String(row.id)"
              :class="TABLE_STYLES.tr"
              role="row"
              @click="emit('row-click', row)"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                :class="[TABLE_STYLES.td, getAlignClass(col.align)]"
              >
                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                  {{ row[col.key] ?? '—' }}
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination && !loading" :class="TABLE_STYLES.pagination">
      <span :class="TABLE_STYLES.paginationInfo">
        {{ (page - 1) * perPage + 1 }}–{{ Math.min(page * perPage, total) }} von {{ total }}
      </span>

      <div :class="TABLE_STYLES.paginationButtons">
        <button
          :class="[TABLE_STYLES.paginationButton, TABLE_STYLES.paginationInactive]"
          :disabled="page <= 1"
          aria-label="Vorherige Seite"
          @click="emit('page-change', page - 1)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <template v-for="p in pages" :key="p">
          <span v-if="p === '...'" class="px-2 text-slate-400">...</span>
          <button
            v-else
            :class="[
              TABLE_STYLES.paginationButton,
              p === page ? TABLE_STYLES.paginationActive : TABLE_STYLES.paginationInactive,
            ]"
            @click="emit('page-change', p)"
          >
            {{ p }}
          </button>
        </template>

        <button
          :class="[TABLE_STYLES.paginationButton, TABLE_STYLES.paginationInactive]"
          :disabled="page >= totalPages"
          aria-label="Nächste Seite"
          @click="emit('page-change', page + 1)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>
