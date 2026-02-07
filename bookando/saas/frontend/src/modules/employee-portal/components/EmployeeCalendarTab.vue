<script setup lang="ts">
/**
 * EmployeeCalendarTab — Mitarbeiter-Kalenderansicht
 *
 * Zeigt Sessions und CalendarBlocks in einer Wochen-Uebersicht.
 * Verfuegbarkeitsregeln und Abwesenheiten.
 */
import { ref, computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import BSelect from '@/components/ui/BSelect.vue';
import { useI18n } from '@/composables/useI18n';
import {
  useSessionsStore,
  SESSION_STATUS_LABELS,
  CALENDAR_BLOCK_TYPE_LABELS,
  APPROVAL_STATUS_LABELS,
  APPROVAL_STATUS_COLORS,
  DAY_OF_WEEK_LABELS,
} from '@/stores/sessions';
import type { CalendarBlock } from '@/stores/sessions';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const store = useSessionsStore();

// ── Week Navigation ─────────────────────────────────────────────────────
const currentWeekOffset = ref(0);

const weekDays = computed(() => {
  const today = new Date();
  const monday = new Date(today);
  monday.setDate(today.getDate() - today.getDay() + 1 + currentWeekOffset.value * 7);

  return Array.from({ length: 7 }, (_, i) => {
    const day = new Date(monday);
    day.setDate(monday.getDate() + i);
    return day;
  });
});

const weekLabel = computed(() => {
  const start = weekDays.value[0];
  const end = weekDays.value[6];
  return `${start.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit' })} – ${end.toLocaleDateString('de-CH', { day: '2-digit', month: '2-digit', year: 'numeric' })}`;
});

function isToday(date: Date): boolean {
  const today = new Date();
  return date.toISOString().slice(0, 10) === today.toISOString().slice(0, 10);
}

function getSessionsForDay(date: Date) {
  const dayStr = date.toISOString().slice(0, 10);
  return store.sessions.filter(s => s.startsAt.slice(0, 10) === dayStr);
}

function getBlocksForDay(date: Date) {
  const dayStr = date.toISOString().slice(0, 10);
  return store.calendarBlocks.filter(b => {
    const blockStart = b.startsAt.slice(0, 10);
    const blockEnd = b.endsAt.slice(0, 10);
    return dayStr >= blockStart && dayStr <= blockEnd;
  });
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' });
}

function getBlockColor(block: CalendarBlock): string {
  switch (block.blockType) {
    case 'VACATION': return 'bg-emerald-100 border-emerald-200 text-emerald-700';
    case 'SICK': return 'bg-red-100 border-red-200 text-red-700';
    case 'ABSENCE': return 'bg-amber-100 border-amber-200 text-amber-700';
    case 'PERSONAL': return 'bg-purple-100 border-purple-200 text-purple-700';
    case 'EXTERNAL_BUSY': return 'bg-slate-100 border-slate-200 text-slate-700';
    case 'MANUAL_BLOCK': return 'bg-slate-100 border-slate-200 text-slate-600';
    default: return 'bg-slate-100 border-slate-200 text-slate-600';
  }
}
</script>

<template>
  <div>
    <!-- Week Navigation -->
    <div class="flex items-center justify-between mb-6">
      <BButton variant="ghost" size="sm" @click="currentWeekOffset--">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
      </BButton>
      <div class="text-center">
        <h3 class="text-sm font-semibold text-slate-900">{{ weekLabel }}</h3>
        <button
          v-if="currentWeekOffset !== 0"
          class="text-xs text-brand-600 hover:text-brand-700 mt-0.5"
          @click="currentWeekOffset = 0"
        >
          Heute
        </button>
      </div>
      <BButton variant="ghost" size="sm" @click="currentWeekOffset++">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
      </BButton>
    </div>

    <!-- Week Grid -->
    <div class="grid grid-cols-7 gap-2">
      <!-- Day Headers -->
      <div
        v-for="(day, idx) in weekDays"
        :key="'header-' + idx"
        class="text-center py-2"
      >
        <div class="text-xs font-medium text-slate-400">{{ DAY_OF_WEEK_LABELS[idx]?.slice(0, 2) }}</div>
        <div
          class="text-sm font-semibold mt-0.5 w-8 h-8 rounded-full flex items-center justify-center mx-auto"
          :class="isToday(day) ? 'bg-brand-600 text-white' : 'text-slate-700'"
        >
          {{ day.getDate() }}
        </div>
      </div>

      <!-- Day Content -->
      <div
        v-for="(day, idx) in weekDays"
        :key="'content-' + idx"
        class="min-h-[120px] rounded-lg border border-slate-100 p-1.5 space-y-1"
        :class="isToday(day) ? 'bg-brand-50/30 border-brand-200' : 'bg-white'"
      >
        <!-- Sessions -->
        <div
          v-for="session in getSessionsForDay(day)"
          :key="session.id"
          class="px-1.5 py-1 rounded text-[10px] bg-brand-100 border border-brand-200 text-brand-700 truncate cursor-pointer hover:bg-brand-200 transition-colors"
          :title="(session.title || session.offerTitle || 'Session') + ' ' + formatTime(session.startsAt)"
        >
          <span class="font-medium">{{ formatTime(session.startsAt) }}</span>
          <span class="ml-0.5">{{ session.title || session.offerTitle || 'Session' }}</span>
        </div>

        <!-- Calendar Blocks -->
        <div
          v-for="block in getBlocksForDay(day)"
          :key="block.id"
          class="px-1.5 py-1 rounded text-[10px] border truncate"
          :class="getBlockColor(block)"
        >
          {{ CALENDAR_BLOCK_TYPE_LABELS[block.blockType] }}
        </div>
      </div>
    </div>

    <!-- Availability Rules -->
    <div v-if="store.availabilityRules.length > 0" class="mt-8">
      <h3 class="text-sm font-semibold text-slate-900 mb-3">Verfuegbarkeitsregeln</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div
          v-for="rule in store.availabilityRules"
          :key="rule.id"
          :class="CARD_STYLES.base"
          class="p-3"
        >
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-medium text-slate-700">{{ DAY_OF_WEEK_LABELS[rule.dayOfWeek] }}</span>
            <span class="text-xs text-slate-400">{{ rule.startTime }} – {{ rule.endTime }}</span>
          </div>
          <div class="text-xs text-slate-400">
            Ab {{ new Date(rule.validFrom).toLocaleDateString('de-CH') }}
            <span v-if="rule.validUntil"> bis {{ new Date(rule.validUntil).toLocaleDateString('de-CH') }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Absence Approvals -->
    <div v-if="store.pendingApprovals.length > 0" class="mt-8">
      <h3 class="text-sm font-semibold text-slate-900 mb-3">Ausstehende Abwesenheits-Antraege</h3>
      <div class="space-y-2">
        <div
          v-for="block in store.pendingApprovals"
          :key="block.id"
          class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100"
        >
          <div class="flex-1">
            <span class="text-sm font-medium text-slate-700">{{ CALENDAR_BLOCK_TYPE_LABELS[block.blockType] }}</span>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ new Date(block.startsAt).toLocaleDateString('de-CH') }} – {{ new Date(block.endsAt).toLocaleDateString('de-CH') }}
            </p>
            <p v-if="block.reason" class="text-xs text-slate-400 mt-0.5">{{ block.reason }}</p>
          </div>
          <BBadge :variant="'warning'" size="sm">
            {{ APPROVAL_STATUS_LABELS[block.approvalStatus] }}
          </BBadge>
        </div>
      </div>
    </div>
  </div>
</template>
