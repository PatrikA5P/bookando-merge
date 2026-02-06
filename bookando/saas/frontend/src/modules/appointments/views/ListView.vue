<script setup lang="ts">
/**
 * Listenansicht — Termine
 *
 * Tabellarische Übersicht aller Termine mit Such- und Filterfunktion.
 * TODO: Vue Query, Server-Side Pagination, Inline-Bearbeitung
 */
import { ref } from 'vue';

const searchQuery = ref('');
</script>

<template>
  <div>
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-6">
      <div class="flex-1 relative max-w-md">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Termine suchen..."
          class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors"
        />
      </div>
    </div>

    <!-- Listen-Platzhalter -->
    <div class="bg-white rounded-xl border border-slate-200 p-8 min-h-[300px] flex items-center justify-center">
      <div class="text-center">
        <div class="w-16 h-16 mx-auto bg-brand-50 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
          </svg>
        </div>
        <h3 class="text-sm font-semibold text-slate-900">Listenansicht</h3>
        <p class="text-sm text-slate-500 mt-1">Wird implementiert</p>
        <p class="text-xs text-slate-400 mt-2">
          Tabellarische Ansicht mit Sortierung, Filterung und Paginierung
        </p>
      </div>
    </div>
  </div>
</template>
