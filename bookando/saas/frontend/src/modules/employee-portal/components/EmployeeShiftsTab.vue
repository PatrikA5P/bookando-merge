<script setup lang="ts">
/**
 * EmployeeShiftsTab — Schichtplan und Zeiterfassung
 *
 * Zeigt dem Mitarbeiter seinen Schichtplan und Zeiteintraege.
 */
import { ref, computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import BSelect from '@/components/ui/BSelect.vue';
import { useI18n } from '@/composables/useI18n';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();

// ── Shift Types ─────────────────────────────────────────────────────────
const SHIFT_TYPE_LABELS: Record<string, string> = {
  EARLY: 'Frueh',
  LATE: 'Spaet',
  NIGHT: 'Nacht',
  OFF: 'Frei',
};

const SHIFT_TYPE_COLORS: Record<string, string> = {
  EARLY: 'bg-amber-100 text-amber-700 border-amber-200',
  LATE: 'bg-blue-100 text-blue-700 border-blue-200',
  NIGHT: 'bg-indigo-100 text-indigo-700 border-indigo-200',
  OFF: 'bg-slate-100 text-slate-500 border-slate-200',
};

const TIME_ENTRY_TYPE_LABELS: Record<string, string> = {
  WORK: 'Arbeit',
  BREAK: 'Pause',
  MEETING: 'Meeting',
  TRAVEL: 'Reise',
};

const TIME_ENTRY_STATUS_LABELS: Record<string, string> = {
  PENDING: 'Ausstehend',
  APPROVED: 'Genehmigt',
  REJECTED: 'Abgelehnt',
};

// ── Mock data (would come from store in production) ─────────────────────
const currentWeekShifts = ref([
  { id: '1', date: new Date().toISOString().slice(0, 10), shiftType: 'EARLY', startTime: '06:00', endTime: '14:00' },
]);

const recentTimeEntries = ref([
  { id: '1', date: new Date().toISOString().slice(0, 10), startTime: '06:00', endTime: '14:00', type: 'WORK', status: 'APPROVED', notes: '' },
]);

// ── Week Navigation ─────────────────────────────────────────────────────
const weekOffset = ref(0);

const weekDays = computed(() => {
  const today = new Date();
  const monday = new Date(today);
  monday.setDate(today.getDate() - today.getDay() + 1 + weekOffset.value * 7);

  return Array.from({ length: 7 }, (_, i) => {
    const day = new Date(monday);
    day.setDate(monday.getDate() + i);
    return {
      date: day,
      dateStr: day.toISOString().slice(0, 10),
      dayName: day.toLocaleDateString('de-CH', { weekday: 'short' }),
      dayNum: day.getDate(),
      isToday: day.toISOString().slice(0, 10) === new Date().toISOString().slice(0, 10),
    };
  });
});

function getShiftForDay(dateStr: string) {
  return currentWeekShifts.value.find(s => s.date === dateStr);
}

// ── Time Tracking ───────────────────────────────────────────────────────
const isClockRunning = ref(false);
const clockStartTime = ref('');

function toggleClock() {
  if (isClockRunning.value) {
    isClockRunning.value = false;
    clockStartTime.value = '';
  } else {
    isClockRunning.value = true;
    clockStartTime.value = new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' });
  }
}
</script>

<template>
  <div>
    <!-- Clock Widget -->
    <div :class="CARD_STYLES.base" class="p-5 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">Zeiterfassung</h3>
          <p v-if="isClockRunning" class="text-xs text-emerald-600 mt-0.5">
            Gestartet um {{ clockStartTime }}
          </p>
          <p v-else class="text-xs text-slate-500 mt-0.5">Nicht aktiv</p>
        </div>
        <BButton
          :variant="isClockRunning ? 'danger' : 'primary'"
          @click="toggleClock"
        >
          <svg v-if="!isClockRunning" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
          </svg>
          {{ isClockRunning ? 'Stoppen' : 'Starten' }}
        </BButton>
      </div>
    </div>

    <!-- Shift Plan -->
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Schichtplan</h3>
    <div class="flex items-center justify-between mb-4">
      <BButton variant="ghost" size="sm" @click="weekOffset--">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </BButton>
      <button
        v-if="weekOffset !== 0"
        class="text-xs text-brand-600 hover:text-brand-700"
        @click="weekOffset = 0"
      >
        Aktuelle Woche
      </button>
      <BButton variant="ghost" size="sm" @click="weekOffset++">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
      </BButton>
    </div>

    <div class="grid grid-cols-7 gap-2 mb-8">
      <div
        v-for="day in weekDays"
        :key="day.dateStr"
        class="text-center"
      >
        <div class="text-xs text-slate-400 mb-1">{{ day.dayName }}</div>
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center mx-auto text-sm font-medium mb-2"
          :class="day.isToday ? 'bg-brand-600 text-white' : 'text-slate-700'"
        >
          {{ day.dayNum }}
        </div>
        <div v-if="getShiftForDay(day.dateStr)" class="px-1">
          <div
            class="px-2 py-1.5 rounded-lg border text-[10px] font-medium text-center"
            :class="SHIFT_TYPE_COLORS[getShiftForDay(day.dateStr)!.shiftType]"
          >
            {{ SHIFT_TYPE_LABELS[getShiftForDay(day.dateStr)!.shiftType] }}
            <div class="text-[9px] opacity-75 mt-0.5">
              {{ getShiftForDay(day.dateStr)!.startTime }}–{{ getShiftForDay(day.dateStr)!.endTime }}
            </div>
          </div>
        </div>
        <div v-else class="px-1">
          <div class="px-2 py-1.5 rounded-lg border border-dashed border-slate-200 text-[10px] text-slate-300 text-center">
            —
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Time Entries -->
    <h3 class="text-sm font-semibold text-slate-900 mb-3">Letzte Zeiteintraege</h3>
    <BEmptyState
      v-if="recentTimeEntries.length === 0"
      title="Keine Zeiteintraege"
      description="Starten Sie die Zeiterfassung ueber den Button oben."
      icon="folder"
    />

    <div v-else class="space-y-2">
      <div
        v-for="entry in recentTimeEntries"
        :key="entry.id"
        :class="CARD_STYLES.base"
        class="p-3 flex items-center gap-3"
      >
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-700">{{ new Date(entry.date).toLocaleDateString('de-CH', { weekday: 'short', day: '2-digit', month: '2-digit' }) }}</span>
            <BBadge variant="info" size="sm">{{ TIME_ENTRY_TYPE_LABELS[entry.type] }}</BBadge>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">{{ entry.startTime }} – {{ entry.endTime || 'laufend' }}</p>
        </div>
        <BBadge
          :variant="entry.status === 'APPROVED' ? 'success' : entry.status === 'REJECTED' ? 'danger' : 'warning'"
          size="sm"
        >
          {{ TIME_ENTRY_STATUS_LABELS[entry.status] }}
        </BBadge>
      </div>
    </div>
  </div>
</template>
