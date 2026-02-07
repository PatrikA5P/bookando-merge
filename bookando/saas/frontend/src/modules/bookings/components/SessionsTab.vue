<script setup lang="ts">
/**
 * SessionsTab — Session-Uebersicht mit Filtern
 *
 * Zeigt Sessions (Durchfuehrungen) als Karten-Grid.
 * Status-Filter, Zeitraum, Instructor-Filter.
 */
import { ref, computed } from 'vue';
import BSearchBar from '@/components/ui/BSearchBar.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BInput from '@/components/ui/BInput.vue';
import { useI18n } from '@/composables/useI18n';
import {
  useSessionsStore,
  SESSION_STATUS_LABELS,
  SESSION_STATUS_COLORS,
} from '@/stores/sessions';
import type { Session, SessionStatus } from '@/stores/sessions';
import { CARD_STYLES, GRID_STYLES } from '@/design';

const { t } = useI18n();
const store = useSessionsStore();

const emit = defineEmits<{
  (e: 'edit', session: Session): void;
  (e: 'create'): void;
}>();

const searchQuery = ref('');
const filterStatus = ref<SessionStatus | ''>('');
const filterDateFrom = ref('');
const filterDateTo = ref('');

const statusOptions = [
  { value: '', label: 'Alle Status' },
  ...Object.entries(SESSION_STATUS_LABELS).map(([v, l]) => ({ value: v, label: l })),
];

const filteredSessions = computed(() => {
  let result = [...store.sessions];

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    result = result.filter(s =>
      (s.title || '').toLowerCase().includes(q) ||
      (s.offerTitle || '').toLowerCase().includes(q) ||
      (s.instructorName || '').toLowerCase().includes(q)
    );
  }

  if (filterStatus.value) {
    result = result.filter(s => s.status === filterStatus.value);
  }

  if (filterDateFrom.value) {
    const from = new Date(filterDateFrom.value).getTime();
    result = result.filter(s => new Date(s.startsAt).getTime() >= from);
  }

  if (filterDateTo.value) {
    const to = new Date(filterDateTo.value).getTime();
    result = result.filter(s => new Date(s.startsAt).getTime() <= to);
  }

  return result.sort((a, b) => new Date(a.startsAt).getTime() - new Date(b.startsAt).getTime());
});

function getStatusBadgeVariant(status: SessionStatus): 'default' | 'success' | 'warning' | 'info' {
  const map: Record<string, 'default' | 'success' | 'warning' | 'info'> = {
    warning: 'warning',
    success: 'success',
    info: 'info',
    brand: 'success',
    default: 'default',
  };
  return map[SESSION_STATUS_COLORS[status]] || 'default';
}

function formatDate(iso: string): string {
  const d = new Date(iso);
  return d.toLocaleDateString('de-CH', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatTimeRange(startsAt: string, endsAt: string): string {
  const s = new Date(startsAt);
  const e = new Date(endsAt);
  return `${s.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })} – ${e.toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })}`;
}

function getCapacityPercent(session: Session): number {
  if (!session.maxParticipants || session.maxParticipants === 0) return 0;
  return Math.round((session.currentEnrollment / session.maxParticipants) * 100);
}
</script>

<template>
  <!-- Search & Filters -->
  <div class="flex flex-col gap-4 mb-6">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <div class="flex-1">
        <BSearchBar v-model="searchQuery" placeholder="Sessions suchen..." />
      </div>
      <BButton variant="primary" class="hidden md:flex" @click="emit('create')">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Neue Session
      </BButton>
    </div>
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="sm:w-44"><BSelect v-model="filterStatus" :options="statusOptions" /></div>
      <div class="sm:w-40"><BInput v-model="filterDateFrom" type="date" placeholder="Von" /></div>
      <div class="sm:w-40"><BInput v-model="filterDateTo" type="date" placeholder="Bis" /></div>
    </div>
  </div>

  <!-- Empty State -->
  <BEmptyState
    v-if="filteredSessions.length === 0 && !searchQuery && !filterStatus"
    title="Noch keine Sessions vorhanden"
    description="Sessions werden automatisch bei Service-Buchungen erstellt oder manuell fuer Events angelegt."
    icon="folder"
    action-label="Session erstellen"
    @action="emit('create')"
  />

  <BEmptyState
    v-else-if="filteredSessions.length === 0"
    title="Keine Sessions gefunden"
    description="Passen Sie Ihre Filter an."
    icon="search"
  />

  <!-- Session Cards -->
  <div v-else :class="GRID_STYLES.cols3">
    <div
      v-for="session in filteredSessions"
      :key="session.id"
      :class="CARD_STYLES.hover"
      class="p-4 cursor-pointer"
      @click="emit('edit', session)"
    >
      <!-- Header -->
      <div class="flex items-start justify-between mb-3">
        <div class="min-w-0 flex-1">
          <h3 class="text-sm font-semibold text-slate-900 truncate">
            {{ session.title || session.offerTitle || 'Session' }}
          </h3>
          <p v-if="session.offerTitle && session.title" class="text-xs text-slate-500 truncate mt-0.5">
            {{ session.offerTitle }}
          </p>
        </div>
        <BBadge :variant="getStatusBadgeVariant(session.status)" class="ml-2 shrink-0">
          {{ SESSION_STATUS_LABELS[session.status] }}
        </BBadge>
      </div>

      <!-- Date & Time -->
      <div class="space-y-1.5 mb-3">
        <div class="flex items-center gap-1.5 text-xs text-slate-600">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          {{ formatDate(session.startsAt) }}
        </div>
        <div class="flex items-center gap-1.5 text-xs text-slate-600">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          {{ formatTimeRange(session.startsAt, session.endsAt) }}
        </div>
      </div>

      <!-- Instructor -->
      <div v-if="session.instructorName" class="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
        {{ session.instructorName }}
      </div>

      <!-- Capacity Bar -->
      <div v-if="session.maxParticipants" class="pt-3 border-t border-slate-100">
        <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
          <span>Teilnehmer</span>
          <span>{{ session.currentEnrollment }}/{{ session.maxParticipants }}</span>
        </div>
        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-300"
            :class="getCapacityPercent(session) >= 90 ? 'bg-red-500' : getCapacityPercent(session) >= 70 ? 'bg-amber-500' : 'bg-emerald-500'"
            :style="{ width: `${Math.min(getCapacityPercent(session), 100)}%` }"
          />
        </div>
      </div>
    </div>
  </div>
</template>
