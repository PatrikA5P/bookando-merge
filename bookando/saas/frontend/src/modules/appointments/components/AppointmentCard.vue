<script setup lang="ts">
/**
 * AppointmentCard -- Reusable Appointment Card Component
 *
 * Compact card used in CalendarView (mobile day) and ListView (mobile).
 * Shows: time range, service name, customer name, employee (avatar initials),
 * status badge with dot indicator, price, and colored left border by status.
 *
 * Props:
 * - appointment: Appointment object
 * - compact: reduces padding/font sizes (for tight calendar grids)
 * - showDate: show the date below the time (for cross-date lists)
 */
import { computed } from 'vue';
import { CARD_STYLES, AVATAR_STYLES, getStatusColors } from '@/design';
import BBadge from '@/components/ui/BBadge.vue';
import { useI18n } from '@/composables/useI18n';
import { useDesignStore } from '@/stores/design';
import { useAppStore } from '@/stores/app';
import type { Appointment } from '@/stores/appointments';

const props = withDefaults(defineProps<{
  appointment: Appointment;
  compact?: boolean;
  showDate?: boolean;
}>(), {
  compact: false,
  showDate: false,
});

const emit = defineEmits<{
  (e: 'click', appointment: Appointment): void;
}>();

const { t } = useI18n();
const designStore = useDesignStore();
const appStore = useAppStore();

// Status color mapping to design system keys
const statusKey = computed(() => {
  const map: Record<string, string> = {
    PENDING: 'pending',
    CONFIRMED: 'confirmed',
    COMPLETED: 'completed',
    CANCELLED: 'cancelled',
    NO_SHOW: 'noShow',
  };
  return map[props.appointment.status] || 'inactive';
});

const statusColors = computed(() => getStatusColors(statusKey.value));

const statusLabel = computed(() => {
  const labels: Record<string, string> = {
    PENDING: t('common.pending'),
    CONFIRMED: t('appointments.confirmed'),
    COMPLETED: t('common.completed'),
    CANCELLED: t('common.cancelled'),
    NO_SHOW: t('appointments.noShow'),
  };
  return labels[props.appointment.status] || props.appointment.status;
});

// Left border color by status
const borderColorClass = computed(() => {
  const map: Record<string, string> = {
    PENDING: 'border-l-amber-500',
    CONFIRMED: 'border-l-emerald-500',
    COMPLETED: 'border-l-blue-500',
    CANCELLED: 'border-l-slate-400',
    NO_SHOW: 'border-l-rose-500',
  };
  return map[props.appointment.status] || 'border-l-slate-400';
});

const formattedPrice = computed(() => appStore.formatPrice(props.appointment.priceMinor));

// Employee initials for avatar
const initials = computed(() => {
  const parts = props.appointment.employeeName.split(' ');
  if (parts.length >= 2) {
    return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
  }
  return parts[0]?.[0]?.toUpperCase() || '?';
});

function formatDateDisplay(dateStr: string): string {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('de-CH', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
  });
}
</script>

<template>
  <div
    :class="[
      CARD_STYLES.interactive,
      'border-l-4 flex gap-3',
      borderColorClass,
      compact ? 'p-2' : 'p-4',
    ]"
    role="button"
    tabindex="0"
    :aria-label="`${appointment.serviceName} - ${appointment.customerName}`"
    @click="emit('click', appointment)"
    @keydown.enter="emit('click', appointment)"
  >
    <!-- Time Column -->
    <div class="flex-shrink-0 text-center" :class="compact ? 'w-12' : 'w-16'">
      <div :class="compact ? 'text-xs font-bold text-slate-900' : 'text-sm font-bold text-slate-900'">
        {{ appointment.startTime }}
      </div>
      <div :class="compact ? 'text-[10px] text-slate-400' : 'text-xs text-slate-500'">
        {{ appointment.endTime }}
      </div>
      <div v-if="showDate" class="text-[10px] text-slate-400 mt-0.5">
        {{ formatDateDisplay(appointment.date) }}
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <!-- Top row: service name + status badge -->
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
          <h4 :class="compact ? 'text-xs font-semibold text-slate-900 truncate' : 'text-sm font-semibold text-slate-900 truncate'">
            {{ appointment.serviceName }}
          </h4>
          <p :class="compact ? 'text-[10px] text-slate-500 truncate' : 'text-xs text-slate-600 truncate mt-0.5'">
            <svg class="w-3 h-3 inline-block mr-0.5 -mt-0.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ appointment.customerName }}
          </p>
        </div>
        <BBadge :status="statusKey" dot class="flex-shrink-0">
          <template v-if="!compact">{{ statusLabel }}</template>
        </BBadge>
      </div>

      <!-- Bottom row: employee + duration + price (hidden in compact) -->
      <div v-if="!compact" class="flex items-center justify-between mt-2">
        <!-- Employee -->
        <div class="flex items-center gap-1.5">
          <div
            :class="[AVATAR_STYLES.initials.xs, 'bg-brand-100 text-brand-700']"
          >
            {{ initials }}
          </div>
          <span class="text-xs text-slate-500 truncate max-w-[120px]">{{ appointment.employeeName }}</span>
        </div>
        <!-- Duration + Price -->
        <div class="flex items-center gap-2">
          <span class="text-[10px] text-slate-400">{{ appointment.duration }} min</span>
          <span class="text-xs font-medium text-slate-700">{{ formattedPrice }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
