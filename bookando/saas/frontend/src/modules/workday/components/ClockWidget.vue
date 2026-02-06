<script setup lang="ts">
/**
 * ClockWidget — Live Stempeluhr
 *
 * Zeigt den aktuellen Arbeitsstatus:
 * - NOT_STARTED: Noch nicht eingestempelt
 * - WORKING: Arbeitet (grüner pulsierender Punkt, laufende Uhr)
 * - ON_BREAK: Auf Pause (gelber pulsierender Punkt)
 *
 * Features:
 * - Grosser digitaler Timer mit Echtzeit-Update
 * - Clock In / Clock Out / Pause Start / Pause Ende Buttons
 * - Tageszusammenfassung (Startzeit, Pausenzeit, Nettostunden)
 * - ArG-konforme Pausenhinweise
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { CARD_STYLES, BUTTON_STYLES, BADGE_STYLES } from '@/design';
import { useI18n } from '@/composables/useI18n';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useWorkdayStore } from '@/stores/workday';
import BButton from '@/components/ui/BButton.vue';
import BBadge from '@/components/ui/BBadge.vue';

const { t } = useI18n();
const { isMobile } = useBreakpoint();
const store = useWorkdayStore();

// Live timer
const now = ref(new Date());
let timerInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  timerInterval = setInterval(() => {
    now.value = new Date();
  }, 1000);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});

/** Elapsed time since clock-in in seconds */
const elapsedSeconds = computed(() => {
  if (!store.clockStartTime) return 0;
  return Math.floor((now.value.getTime() - store.clockStartTime.getTime()) / 1000);
});

/** Current break elapsed in seconds (if on break) */
const breakElapsedSeconds = computed(() => {
  if (!store.breakStartTime) return 0;
  return Math.floor((now.value.getTime() - store.breakStartTime.getTime()) / 1000);
});

/** Total break so far in seconds */
const totalBreakSec = computed(() => {
  return store.totalBreakSeconds + breakElapsedSeconds.value;
});

/** Net working seconds */
const netWorkingSeconds = computed(() => {
  return Math.max(0, elapsedSeconds.value - totalBreakSec.value);
});

function formatTimer(totalSeconds: number): string {
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function formatTimeOfDay(date: Date): string {
  return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

const statusLabel = computed(() => {
  switch (store.clockState) {
    case 'WORKING': return t('workday.clockIn');
    case 'ON_BREAK': return t('workday.breakStart');
    default: return t('workday.clockIn');
  }
});

const statusDotClass = computed(() => {
  switch (store.clockState) {
    case 'WORKING': return 'bg-emerald-500';
    case 'ON_BREAK': return 'bg-amber-500';
    default: return 'bg-slate-300';
  }
});

const statusBgClass = computed(() => {
  switch (store.clockState) {
    case 'WORKING': return 'bg-emerald-50 border-emerald-200';
    case 'ON_BREAK': return 'bg-amber-50 border-amber-200';
    default: return 'bg-slate-50 border-slate-200';
  }
});
</script>

<template>
  <div :class="[CARD_STYLES.base, 'overflow-hidden']">
    <!-- Status banner -->
    <div
      :class="[
        'px-4 py-3 border-b flex items-center justify-between',
        statusBgClass,
      ]"
    >
      <div class="flex items-center gap-3">
        <!-- Pulsing dot -->
        <span class="relative flex h-3 w-3">
          <span
            v-if="store.clockState !== 'NOT_STARTED'"
            :class="[
              'animate-ping absolute inline-flex h-full w-full rounded-full opacity-75',
              statusDotClass,
            ]"
          />
          <span
            :class="[
              'relative inline-flex rounded-full h-3 w-3',
              statusDotClass,
            ]"
          />
        </span>
        <span class="text-sm font-semibold text-slate-700">
          <template v-if="store.clockState === 'NOT_STARTED'">
            {{ t('workday.clockIn') }} &mdash; {{ t('workday.timeTracking') }}
          </template>
          <template v-else-if="store.clockState === 'WORKING'">
            {{ t('workday.timeTracking') }}
          </template>
          <template v-else>
            {{ t('workday.breakStart') }}
          </template>
        </span>
      </div>
      <BBadge
        v-if="store.clockState === 'WORKING'"
        variant="success"
      >
        {{ t('common.active') }}
      </BBadge>
      <BBadge
        v-else-if="store.clockState === 'ON_BREAK'"
        variant="warning"
      >
        {{ t('workday.breakStart') }}
      </BBadge>
    </div>

    <!-- Timer area -->
    <div :class="['p-6', isMobile ? 'text-center' : 'flex items-center gap-8']">
      <!-- Big digital clock -->
      <div :class="[isMobile ? 'mb-4' : 'flex-shrink-0']">
        <div
          class="font-mono text-4xl md:text-5xl font-bold tracking-wider"
          :class="store.clockState === 'ON_BREAK' ? 'text-amber-600' : store.clockState === 'WORKING' ? 'text-emerald-600' : 'text-slate-400'"
        >
          {{ store.clockState === 'NOT_STARTED' ? '00:00:00' : formatTimer(store.clockState === 'ON_BREAK' ? breakElapsedSeconds : netWorkingSeconds) }}
        </div>
        <p class="text-xs text-slate-500 mt-1">
          <template v-if="store.clockState === 'ON_BREAK'">
            {{ t('workday.breakDuration') }}
          </template>
          <template v-else>
            {{ t('workday.netHours') }}
          </template>
        </p>
      </div>

      <!-- Actions + Summary -->
      <div :class="[isMobile ? '' : 'flex-1 flex items-center justify-between']">
        <!-- Action buttons -->
        <div :class="['flex gap-2', isMobile ? 'justify-center mb-4' : '']">
          <BButton
            v-if="store.clockState === 'NOT_STARTED'"
            variant="primary"
            @click="store.clockIn()"
          >
            <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
            </svg>
            {{ t('workday.clockIn') }}
          </BButton>

          <template v-else-if="store.clockState === 'WORKING'">
            <BButton
              variant="secondary"
              @click="store.startBreak()"
            >
              <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ t('workday.breakStart') }}
            </BButton>
            <BButton
              variant="danger"
              @click="store.clockOut()"
            >
              <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z" />
              </svg>
              {{ t('workday.clockOut') }}
            </BButton>
          </template>

          <BButton
            v-else-if="store.clockState === 'ON_BREAK'"
            variant="primary"
            @click="store.endBreak()"
          >
            <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
            </svg>
            {{ t('workday.breakEnd') }}
          </BButton>
        </div>

        <!-- Today's summary (only when clocked in) -->
        <div
          v-if="store.clockState !== 'NOT_STARTED' && store.clockStartTime"
          :class="[
            'grid gap-4 text-center',
            isMobile ? 'grid-cols-3 mt-2 pt-4 border-t border-slate-100' : 'grid-cols-3',
          ]"
        >
          <div>
            <p class="text-xs text-slate-500">{{ t('workday.clockIn') }}</p>
            <p class="text-sm font-semibold text-slate-800">
              {{ formatTimeOfDay(store.clockStartTime) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-slate-500">{{ t('workday.breakDuration') }}</p>
            <p class="text-sm font-semibold text-slate-800">
              {{ Math.floor(totalBreakSec / 60) }}min
            </p>
          </div>
          <div>
            <p class="text-xs text-slate-500">{{ t('workday.netHours') }}</p>
            <p class="text-sm font-semibold text-slate-800">
              {{ (netWorkingSeconds / 3600).toFixed(1) }}h
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
