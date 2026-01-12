<!-- EmployeesDetailsAccordion.vue -->
<template>
  <div class="bookando-flex bookando-flex-col bookando-gap-sm">
    <!-- Loading -->
    <div
      v-if="loading"
      class="bookando-inline-flex bookando-items-center bookando-gap-xs bookando-text-sm"
    >
      <AppIcon
        name="loader"
        class="bookando-icon bookando-spin"
        aria-hidden="true"
      />
      <span>{{ t('core.common.loading') }}</span>
    </div>

    <!-- Content -->
    <template v-else-if="employee">
      <AppAccordion
        :items="accordionItems"
        :default-open="['working_days']"
        multiple
      >
        <!-- Working Days -->
        <template #header-working_days>
          <div class="bookando-inline-flex bookando-items-center bookando-gap-xs">
            <AppIcon
              name="calendar"
              class="bookando-icon"
              aria-hidden="true"
            />
            <span>{{ t('mod.employees.form.working_days.title') || 'Arbeitszeiten' }}</span>
            <span
              v-if="workdaysCount"
              class="bookando-badge"
            >{{ workdaysCount }}</span>
          </div>
        </template>
        <template #content-working_days>
          <div
            v-if="hasWorkdayData"
            class="bookando-flex bookando-flex-col bookando-gap-xs"
          >
            <div
              v-for="(day, idx) in workingDaysDisplay"
              :key="`wd-${idx}`"
              class="bookando-grid"
              style="--bookando-grid-cols: 9rem 1fr; gap:.5rem;"
            >
              <span class="bookando-text-sm bookando-text-muted">{{ day.label }}</span>
              <div class="bookando-flex bookando-flex-col bookando-gap-xxs">
                <template
                  v-for="(combo, cidx) in day.combos"
                  :key="`combo-${cidx}`"
                >
                  <div class="bookando-flex bookando-flex-col bookando-gap-xxs">
                    <div class="bookando-inline-flex bookando-gap-xs bookando-text-sm">
                      <span>{{ formatComboServices(combo) }}</span>
                      <span>·</span>
                      <span>{{ formatComboLocations(combo) }}</span>
                    </div>
                    <div class="bookando-inline-flex bookando-flex-wrap bookando-gap-xxs bookando-text-sm">
                      <span
                        v-for="(time, tidx) in (combo.work || [])"
                        :key="`t-${tidx}`"
                        class="bookando-badge-subtle"
                      >
                        {{ time.start }} – {{ time.end }}
                      </span>
                    </div>
                    <div
                      v-if="combo.breaks?.length"
                      class="bookando-inline-flex bookando-flex-wrap bookando-gap-xxs bookando-text-xs bookando-text-muted"
                    >
                      <span>{{ t('mod.employees.form.working_days.breaks') || 'Pausen' }}:</span>
                      <span
                        v-for="(brk, bidx) in combo.breaks"
                        :key="`b-${bidx}`"
                        class="bookando-badge-subtle"
                      >
                        {{ brk.start }} – {{ brk.end }}
                      </span>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>
          <div
            v-else
            class="bookando-text-sm bookando-text-muted"
          >
            {{ t('mod.employees.form.working_days.no_data') || 'Keine Arbeitszeiten erfasst' }}
          </div>
        </template>

        <!-- Days Off -->
        <template #header-days_off>
          <div class="bookando-inline-flex bookando-items-center bookando-gap-xs">
            <AppIcon
              name="x-circle"
              class="bookando-icon"
              aria-hidden="true"
            />
            <span>{{ t('mod.employees.form.days_off.title') || 'Abwesenheiten' }}</span>
            <span
              v-if="daysOffCount"
              class="bookando-badge"
            >{{ daysOffCount }}</span>
          </div>
        </template>
        <template #content-days_off>
          <div
            v-if="hasDaysOffData"
            class="bookando-flex bookando-flex-col bookando-gap-xxs"
          >
            <div
              v-for="(item, idx) in daysOffDisplay"
              :key="`off-${idx}`"
              class="bookando-grid"
              style="--bookando-grid-cols: 9rem 1fr; gap:.5rem;"
            >
              <span class="bookando-text-sm bookando-text-muted">
                {{ formatDateRange(item.dateStart || item.start, item.dateEnd || item.end) }}
              </span>
              <div class="bookando-inline-flex bookando-items-center bookando-gap-xxs bookando-text-sm">
                <span class="bookando-font-medium">{{ item.title || t('mod.employees.form.days_off.untitled') }}</span>
                <span
                  v-if="item.repeatYearly"
                  class="bookando-inline-flex bookando-items-center bookando-gap-xxs"
                >
                  <AppIcon
                    name="refresh-ccw"
                    class="bookando-icon"
                    aria-hidden="true"
                  />
                  {{ t('mod.employees.form.days_off.yearly') }}
                </span>
                <span
                  v-if="item.note"
                  class="bookando-text-muted"
                >· {{ item.note }}</span>
              </div>
            </div>
          </div>
          <div
            v-else
            class="bookando-text-sm bookando-text-muted"
          >
            {{ t('mod.employees.form.days_off.no_data') || 'Keine Abwesenheiten erfasst' }}
          </div>
        </template>

        <!-- Special Days -->
        <template #header-special_days>
          <div class="bookando-inline-flex bookando-items-center bookando-gap-xs">
            <AppIcon
              name="star"
              class="bookando-icon"
              aria-hidden="true"
            />
            <span>{{ t('mod.employees.form.special_days.title') || 'Spezielle Tage' }}</span>
            <span
              v-if="specialDaysCount"
              class="bookando-badge"
            >{{ specialDaysCount }}</span>
          </div>
        </template>
        <template #content-special_days>
          <div
            v-if="hasSpecialDaysData"
            class="bookando-flex bookando-flex-col bookando-gap-xxs"
          >
            <div
              v-for="(card, idx) in specialDaysDisplay"
              :key="`sd-${idx}`"
              class="bookando-grid"
              style="--bookando-grid-cols: 9rem 1fr; gap:.5rem;"
            >
              <span class="bookando-text-sm bookando-text-muted">
                {{ formatDateRange(card.dateStart || card.start_date, card.dateEnd || card.end_date) }}
              </span>
              <div class="bookando-flex bookando-flex-col bookando-gap-xxs">
                <template v-if="card.items?.length">
                  <div
                    v-for="(combo, cidx) in card.items"
                    :key="`sdc-${cidx}`"
                    class="bookando-flex bookando-flex-col bookando-gap-xxs"
                  >
                    <div class="bookando-inline-flex bookando-gap-xs bookando-text-sm">
                      <span>{{ formatComboServices(combo) }}</span>
                      <span>·</span>
                      <span>{{ formatComboLocations(combo) }}</span>
                    </div>
                    <div class="bookando-inline-flex bookando-flex-wrap bookando-gap-xxs bookando-text-sm">
                      <span
                        v-for="(time, tidx) in (combo.work || [])"
                        :key="`st-${tidx}`"
                        class="bookando-badge-subtle"
                      >
                        {{ time.start }} – {{ time.end }}
                      </span>
                    </div>
                  </div>
                </template>
                <span
                  v-else
                  class="bookando-text-sm bookando-text-muted"
                >{{ t('mod.employees.form.special_days.no_hours') }}</span>
              </div>
            </div>
          </div>
          <div
            v-else
            class="bookando-text-sm bookando-text-muted"
          >
            {{ t('mod.employees.form.special_days.no_data') || 'Keine speziellen Tage erfasst' }}
          </div>
        </template>

        <!-- Services -->
        <template #header-services>
          <div class="bookando-inline-flex bookando-items-center bookando-gap-xs">
            <AppIcon
              name="briefcase"
              class="bookando-icon"
              aria-hidden="true"
            />
            <span>{{ t('mod.employees.form.services.title') || 'Dienstleistungen' }}</span>
            <span
              v-if="servicesCount"
              class="bookando-badge"
            >{{ servicesCount }}</span>
          </div>
        </template>
        <template #content-services>
          <div
            v-if="hasServicesData"
            class="bookando-flex bookando-flex-col bookando-gap-xxs"
          >
            <div
              v-for="(service, idx) in servicesDisplay"
              :key="`srv-${idx}`"
              class="bookando-grid"
              style="--bookando-grid-cols: 1fr auto auto; gap:.5rem; align-items:center;"
            >
              <span class="bookando-text-sm">{{ service.name }}</span>
              <span
                v-if="service.price"
                class="bookando-text-sm bookando-text-muted"
              >{{ formatPrice(service.price) }}</span>
              <span
                v-if="service.duration"
                class="bookando-text-sm bookando-text-muted"
              >{{ service.duration }} min</span>
            </div>
          </div>
          <div
            v-else
            class="bookando-text-sm bookando-text-muted"
          >
            {{ t('mod.employees.form.services.no_data') || 'Keine Dienstleistungen zugewiesen' }}
          </div>
        </template>
      </AppAccordion>

      <div class="bookando-flex bookando-justify-end">
        <AppButton
          icon="refresh-ccw"
          variant="standard"
          size="small"
          btn-type="full"
          @click="$emit('refresh')"
        >
          {{ t('core.common.refresh') }}
        </AppButton>
      </div>
    </template>

    <!-- Error -->
    <div
      v-else
      class="bookando-inline-flex bookando-items-center bookando-gap-xs bookando-text-sm"
    >
      <AppIcon
        name="alert-circle"
        class="bookando-icon"
        aria-hidden="true"
      />
      <span>{{ t('core.common.error_loading') }}</span>
      <AppButton
        icon="refresh-ccw"
        variant="primary"
        size="small"
        @click="$emit('refresh')"
      >
        {{ t('core.common.retry') }}
      </AppButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAccordion from '@core/Design/components/AppAccordion.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'

const { t, locale } = useI18n()

const props = defineProps({
  employee: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})
defineEmits(['refresh'])

/* Accordion items (nur IDs, Labels liefert Header-Slot) */
const accordionItems = computed(() => [
  { id: 'working_days', label: t('mod.employees.form.working_days.title') },
  { id: 'days_off', label: t('mod.employees.form.days_off.title') },
  { id: 'special_days', label: t('mod.employees.form.special_days.title') },
  { id: 'services', label: t('mod.employees.form.services.title') }
])

/* Working Days */
const hasWorkdayData = computed(() => !!(props.employee?.workday_sets?.length || props.employee?.working_hours?.length))

const workingDaysDisplay = computed(() => {
  if (!props.employee) return []
  const dayKeys = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']
  const dayLabels: Record<string,string> = {
    monday:    t('ui.days.mon'),
    tuesday:   t('ui.days.tue'),
    wednesday: t('ui.days.wed'),
    thursday:  t('ui.days.thu'),
    friday:    t('ui.days.fri'),
    saturday:  t('ui.days.sat'),
    sunday:    t('ui.days.sun'),
  }
  const workdaySets = props.employee.workday_sets || []
  return dayKeys
    .map(dayKey => {
      const dayData = workdaySets.find((set: any) => set.day_key === dayKey)
      return { key: dayKey, label: dayLabels[dayKey] || dayKey, combos: dayData?.combos || [] }
    })
    .filter(d => (d.combos || []).length > 0)
})
const workdaysCount = computed(() => workingDaysDisplay.value.length)

/* Days Off */
const hasDaysOffData = computed(() => !!(props.employee?.days_off?.length))
const daysOffDisplay = computed(() => {
  const arr = props.employee?.days_off || []
  return [...arr].sort((a: any, b: any) => (a.dateStart || a.start || '').localeCompare(b.dateStart || b.start || ''))
})
const daysOffCount = computed(() => daysOffDisplay.value.length)

/* Special Days */
const hasSpecialDaysData = computed(() => !!(props.employee?.special_day_sets?.length || props.employee?.special_days?.length))
const specialDaysDisplay = computed(() => {
  const specialSets = props.employee?.special_day_sets || props.employee?.special_days || []
  return [...specialSets].sort((a: any, b: any) => (a.dateStart || a.start_date || '').localeCompare(b.dateStart || b.start_date || ''))
})
const specialDaysCount = computed(() => specialDaysDisplay.value.length)

/* Services */
const hasServicesData = computed(() => !!(props.employee?.assigned_services?.length || props.employee?.services?.length))
const servicesDisplay = computed(() => {
  const services = props.employee?.assigned_services || props.employee?.services || []
  return [...services].sort((a: any, b: any) => (a.name || '').localeCompare(b.name || ''))
})
const servicesCount = computed(() => servicesDisplay.value.length)

/* Formatters */
function formatComboServices(combo: any): string {
  const n = Array.isArray(combo?.serviceIds) ? combo.serviceIds.length : 0
  if (!n) return t('core.common.all') || 'Alle'
  return `${n} ${n === 1 ? (t('mod.employees.form.working_days.service') as string) : (t('mod.employees.form.working_days.services') as string)}`
}
function formatComboLocations(combo: any): string {
  const n = Array.isArray(combo?.locationIds) ? combo.locationIds.length : 0
  if (!n) return t('core.common.all') || 'Alle'
  return `${n} ${n === 1 ? (t('mod.employees.form.working_days.location') as string) : (t('mod.employees.form.working_days.locations') as string)}`
}
function formatDateRange(start?: string, end?: string): string {
  if (!start) return '–'
  const fmt = (s: string) => {
    const [y,m,d] = String(s).split('-')
    if (!y || !m || !d) return s
    return `${d}.${m}.${y}`
  }
  const S = fmt(start)
  if (!end || end === start) return S
  return `${S} – ${fmt(end)}`
}
function formatPrice(price: number | string): string {
  const num = typeof price === 'string' ? parseFloat(price) : (price ?? 0)
  return new Intl.NumberFormat(locale.value || 'de-CH', { style: 'currency', currency: 'CHF' }).format(num || 0)
}
</script>
