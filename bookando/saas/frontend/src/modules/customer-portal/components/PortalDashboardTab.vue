<script setup lang="ts">
/**
 * PortalDashboardTab — Kundenportal Startseite
 *
 * Zeigt dem Kunden eine Uebersicht mit:
 * - Naechste Termine
 * - Aktive Ausbildungskarten
 * - Kursfortschritt
 * - Verdiente Badges
 */
import { computed } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BEmptyState from '@/components/ui/BEmptyState.vue';
import { useI18n } from '@/composables/useI18n';
import {
  useBookingsStore,
  BOOKING_STATUS_LABELS,
  isFinalStatus,
  formatMoney,
} from '@/stores/bookings';
import { useAcademyStore } from '@/stores/academy';
import { useTrainingCardsStore } from '@/stores/training-cards';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();
const bookingsStore = useBookingsStore();
const academyStore = useAcademyStore();
const trainingCardsStore = useTrainingCardsStore();

// ── Upcoming Bookings ───────────────────────────────────────────────────
const nextBookings = computed(() => {
  const now = new Date().getTime();
  return bookingsStore.bookings
    .filter(b => new Date(b.scheduledAt).getTime() > now && !isFinalStatus(b.status))
    .sort((a, b) => new Date(a.scheduledAt).getTime() - new Date(b.scheduledAt).getTime())
    .slice(0, 5);
});

// ── Active Training Cards ───────────────────────────────────────────────
const activeAssignments = computed(() =>
  trainingCardsStore.assignments.filter(a => a.status === 'ACTIVE').slice(0, 3)
);

// ── Enrolled Courses ────────────────────────────────────────────────────
const enrolledCourses = computed(() => {
  const ids = new Set(academyStore.enrollments.map(e => e.courseId));
  return academyStore.courses
    .filter(c => ids.has(c.id) && c.status === 'PUBLISHED')
    .slice(0, 4);
});

// ── Stats ───────────────────────────────────────────────────────────────
const totalBookings = computed(() => bookingsStore.bookings.length);
const activeCards = computed(() => trainingCardsStore.assignments.filter(a => a.status === 'ACTIVE').length);
const enrolledCount = computed(() => academyStore.enrollments.length);
const badgeCount = computed(() => academyStore.badges.length);

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('de-CH', { weekday: 'short', day: '2-digit', month: '2-digit' });
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' });
}

function getTemplateTitle(templateId: string): string {
  return trainingCardsStore.templates.find(t => t.id === templateId)?.title || 'Ausbildungskarte';
}

function getAssignmentProgress(assignment: { itemProgress: { status: string }[] }): number {
  if (assignment.itemProgress.length === 0) return 0;
  const completed = assignment.itemProgress.filter(p => p.status === 'COMPLETED' || p.status === 'GRADED').length;
  return Math.round((completed / assignment.itemProgress.length) * 100);
}
</script>

<template>
  <div>
    <!-- Stats Overview -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-brand-600">{{ totalBookings }}</div>
        <div class="text-xs text-slate-500 mt-1">Buchungen</div>
      </div>
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-violet-600">{{ activeCards }}</div>
        <div class="text-xs text-slate-500 mt-1">Ausbildungskarten</div>
      </div>
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-cyan-600">{{ enrolledCount }}</div>
        <div class="text-xs text-slate-500 mt-1">Kurse</div>
      </div>
      <div :class="CARD_STYLES.base" class="p-4 text-center">
        <div class="text-2xl font-bold text-amber-600">{{ badgeCount }}</div>
        <div class="text-xs text-slate-500 mt-1">Badges</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Upcoming Bookings -->
      <div>
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Naechste Termine</h3>
        <div v-if="nextBookings.length > 0" class="space-y-2">
          <div
            v-for="booking in nextBookings"
            :key="booking.id"
            class="flex items-center gap-3 p-3 rounded-xl bg-white border border-slate-100 hover:border-brand-200 transition-colors"
          >
            <div class="w-10 h-10 rounded-lg bg-brand-50 text-brand-600 flex flex-col items-center justify-center shrink-0 text-xs">
              <span class="font-bold leading-none">{{ new Date(booking.scheduledAt).getDate() }}</span>
              <span class="text-[9px] leading-none mt-0.5">{{ new Date(booking.scheduledAt).toLocaleDateString('de-CH', { month: 'short' }) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="text-sm font-medium text-slate-900 truncate">{{ booking.offerTitle || 'Termin' }}</h4>
              <p class="text-xs text-slate-500">{{ formatTime(booking.scheduledAt) }} · {{ booking.durationMinutes }} Min.</p>
            </div>
            <span class="text-xs font-medium text-brand-600">{{ formatMoney(booking.totalPriceCents, booking.currency) }}</span>
          </div>
        </div>
        <div v-else class="text-sm text-slate-400 bg-slate-50 rounded-xl p-6 text-center">
          Keine anstehenden Termine
        </div>
      </div>

      <!-- Active Training Cards -->
      <div>
        <h3 class="text-sm font-semibold text-slate-900 mb-3">Aktive Ausbildungskarten</h3>
        <div v-if="activeAssignments.length > 0" class="space-y-2">
          <div
            v-for="assignment in activeAssignments"
            :key="assignment.id"
            class="p-3 rounded-xl bg-white border border-slate-100"
          >
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-medium text-slate-900 truncate">{{ getTemplateTitle(assignment.templateId) }}</h4>
              <span class="text-xs font-medium text-violet-600">{{ getAssignmentProgress(assignment) }}%</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-300"
                :style="{ width: `${getAssignmentProgress(assignment)}%` }"
              />
            </div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-400 bg-slate-50 rounded-xl p-6 text-center">
          Keine aktiven Ausbildungskarten
        </div>
      </div>
    </div>

    <!-- Enrolled Courses -->
    <div v-if="enrolledCourses.length > 0" class="mt-6">
      <h3 class="text-sm font-semibold text-slate-900 mb-3">Meine Kurse</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div
          v-for="course in enrolledCourses"
          :key="course.id"
          class="p-3 rounded-xl bg-white border border-slate-100"
        >
          <h4 class="text-sm font-medium text-slate-900 line-clamp-1">{{ course.title }}</h4>
          <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ course.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
