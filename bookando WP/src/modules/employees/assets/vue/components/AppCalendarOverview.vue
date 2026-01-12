<!-- AppCalendarOverview.vue -->
<template>
  <div class="bookando-flex bookando-flex-col bookando-gap-sm">
    <!-- Kopfzeile -->
    <div class="bookando-flex bookando-justify-between bookando-items-center">
      <div class="bookando-flex bookando-items-center bookando-gap-xs">
        <h3 class="bookando-m-0">
          {{ t('mod.employees.form.calendar.overview_title') || 'Kalenderübersicht' }}
        </h3>
      </div>

      <AppButton
        variant="primary"
        size="dynamic"
        icon="plus"
        :aria-label="t('mod.employees.form.calendar.add_account') || 'Kalenderkonto hinzufügen'"
        @click="emit('open-add')"
      >
        {{ t('mod.employees.form.calendar.add_account') || 'Kalenderkonto hinzufügen' }}
      </AppButton>
    </div>

    <!-- Busy-Quellen (lesen) -->
    <div class="bookando-flex bookando-items-center bookando-gap-xs">
      <h4 class="bookando-m-0">
        {{ t('mod.employees.form.calendar.read_title') || 'Kalender, die auf beschäftigte Zeiten überprüft werden sollen' }}
      </h4>
      <AppPopover
        trigger-mode="icon"
        trigger-icon="info"
        trigger-variant="standard"
      >
        <template #content>
          <div class="bookando-text-sm">
            {{
              t('mod.employees.form.calendar.read_hint')
                || 'Diese Kalender werden verwendet, um Doppelbuchungen zu vermeiden'
            }}
          </div>
        </template>
      </AppPopover>
    </div>

    <div class="bookando-card bookando-p-sm bookando-flex bookando-flex-col bookando-gap-sm">
      <template v-if="busyCalendars.length">
        <div
          v-for="cal in busyCalendars"
          :key="'ro:' + cal.calendar + ':' + cal.calendar_id"
          class="bookando-flex bookando-items-center bookando-justify-between"
        >
          <div class="bookando-flex bookando-items-center bookando-gap-sm">
            <img
              :src="iconFor(cal.calendar)"
              alt=""
              width="28"
              height="28"
              @error="onIconError"
            >
            <div>
              <div class="bookando-font-semibold">
                {{ cal.label || providerLabel(cal) }}
              </div>
              <div class="bookando-text-sm bookando-text-muted">
                {{ cal.subLabel }}
              </div>
            </div>
          </div>

          <AppButton
            icon="x"
            btn-type="icononly"
            variant="standard"
            size="square"
            :aria-label="t('core.common.remove') || 'Entfernen'"
            @click="remove(cal)"
          />
        </div>
      </template>
      <div
        v-else
        class="bookando-text-sm bookando-text-muted"
      >
        –
      </div>
    </div>

    <!-- Write-Targets (schreiben) -->
    <div class="bookando-flex bookando-items-center bookando-gap-xs">
      <h4 class="bookando-m-0">
        {{ t('mod.employees.form.calendar.write_title') || 'Kalender, denen Ereignisse hinzugefügt werden sollen' }}
      </h4>
      <AppPopover
        trigger-mode="icon"
        trigger-icon="info"
        trigger-variant="standard"
      >
        <template #content>
          <div class="bookando-text-sm">
            {{
              t('mod.employees.form.calendar.write_hint')
                || 'Es können ein oder mehrere Kalender ausgewählt werden'
            }}
          </div>
        </template>
      </AppPopover>
    </div>

    <div class="bookando-card bookando-p-sm bookando-flex bookando-flex-col bookando-gap-sm">
      <template v-if="writeCalendars.length">
        <div
          v-for="cal in writeCalendars"
          :key="'wb:' + cal.calendar + ':' + cal.calendar_id"
          class="bookando-flex bookando-items-center bookando-justify-between"
        >
          <div class="bookando-flex bookando-items-center bookando-gap-sm">
            <img
              :src="iconFor(cal.calendar)"
              alt=""
              width="28"
              height="28"
              @error="onIconError"
            >
            <div>
              <div class="bookando-font-semibold">
                {{ cal.label || providerLabel(cal) }}
              </div>
              <div class="bookando-text-sm bookando-text-muted">
                {{ cal.subLabel }}
              </div>
            </div>
          </div>

          <AppButton
            icon="chevron-down"
            btn-type="icononly"
            variant="standard"
            size="square"
            aria-label="Optionen"
          />
        </div>
      </template>
      <div
        v-else
        class="bookando-text-sm bookando-text-muted"
      >
        –
      </div>
    </div>

    <!-- Sync-Settings -->
    <div>
      <h4 class="bookando-mb-xs">
        {{ t('mod.employees.form.calendar.sync_title') || 'Synchronisierungseinstellungen' }}
      </h4>

      <div class="bookando-card bookando-p-sm bookando-flex bookando-flex-col bookando-gap-sm">
        <label class="bookando-flex bookando-items-center bookando-gap-xs">
          <input
            v-model="respectBuffer"
            type="checkbox"
          >
          <span>
            {{ t('mod.employees.form.calendar.sync_buffer') || 'Puffer für diesen Kalender berücksichtigen' }}
          </span>
          <AppPopover
            trigger-mode="icon"
            trigger-icon="info"
            trigger-variant="standard"
          >
            <template #content>
              <div class="bookando-text-sm">
                {{
                  t('mod.employees.form.calendar.sync_help')
                    || 'Synchronisierungseinstellungen\n\nPuffer für diesen Kalender berücksichtigen\n\nÄnderungen von diesem Kalender automatisch synchronisieren'
                }}
              </div>
            </template>
          </AppPopover>
        </label>

        <label class="bookando-flex bookando-items-center bookando-gap-xs">
          <input
            v-model="autoSync"
            type="checkbox"
          >
          <span>
            {{
              t('mod.employees.form.calendar.sync_changes')
                || 'Änderungen von diesem Kalender automatisch mit Calendly synchronisieren'
            }}
          </span>
          <AppPopover
            trigger-mode="icon"
            trigger-icon="info"
            trigger-variant="standard"
          >
            <template #content>
              <div class="bookando-text-sm">
                {{
                  t('mod.employees.form.calendar.sync_help')
                    || 'Synchronisierungseinstellungen\n\nPuffer für diesen Kalender berücksichtigen\n\nÄnderungen von diesem Kalender automatisch synchronisieren'
                }}
              </div>
            </template>
          </AppPopover>
        </label>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { EmployeesApi } from '@core/Api/apiClient'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'

const { t } = useI18n()

type CalProvider = 'google' | 'outlook' | 'exchange' | 'apple'
type EmpCalendar = {
  id?: number
  calendar: CalProvider
  calendar_id: string
  label?: string
  subLabel?: string
  mode?: 'ro' | 'wb' | 'read' | 'write'
}

const props = defineProps<{
  employeeId: number | string
  calendars?: EmpCalendar[]
}>()

const emit = defineEmits<{
  (event: 'change-calendars', list: EmpCalendar[]): void
  (event: 'open-add'): void
}>()

/** Lokale Arbeitskopie der Liste (für Entfernen etc.) */
const list = ref<EmpCalendar[]>([...(props.calendars || [])])

/** Externe Änderungen an props.calendars übernehmen */
watch(
  () => props.calendars,
  (val) => { list.value = [...(val || [])] },
  { deep: true }
)

/** Nach außen gespiegelte Teilmengen */
const busyCalendars  = computed(() => list.value.filter(c => c.mode === 'ro' || c.mode === 'read' || !c.mode))
const writeCalendars = computed(() => list.value.filter(c => c.mode === 'wb' || c.mode === 'write'))

/** Helper */
function providerLabel(c: EmpCalendar) {
  const key =
    c.calendar === 'google'   ? 'google_calendar'
  : c.calendar === 'outlook'  ? 'outlook_calendar'
  : c.calendar === 'exchange' ? 'exchange_calendar'
  :                             'apple_calendar'

  return t(`mod.employees.form.calendar.labels.${key}`) || 'Kalender'
}
function iconFor(provider: CalProvider) {
  if (provider === 'google')   return 'https://assets.calendly.com/mfe/mf-publisher/frontend/media/google-calendar-9d502e45f709b07b91a1.svg'
  if (provider === 'outlook')  return 'https://assets.calendly.com/mfe/mf-publisher/frontend/media/microsoft-1634db12d61fd3a7f9d9.svg'
  return 'https://assets.calendly.com/mfe/mf-publisher/frontend/media/calendar-2d88f.svg'
}
function onIconError(ev: Event) {
  const el = ev.target as HTMLImageElement
  el.src = 'https://assets.calendly.com/mfe/mf-publisher/frontend/media/calendar-2d88f.svg'
}

/** Aktionen */
async function remove(cal: EmpCalendar) {
  try {
    // ICS-Verbindungen sauber trennen, wenn connection_id bekannt
    if (cal.calendar === 'apple' && cal.id) {
      await EmployeesApi.disconnectIcs(Number(props.employeeId), Number(cal.id))
    }
  } catch (_e:any) {
    // optional: Toast anzeigen
  } finally {
    // aus der Auswahl entfernen (OAuth-Kalender-Auswahl nur lokal rausnehmen)
    list.value = list.value.filter(c =>
      !(c.calendar === cal.calendar && c.calendar_id === cal.calendar_id && c.mode === cal.mode)
    )
    emit('change-calendars', [...list.value])
  }
}

/** Sync-Settings (vorerst nur lokal – später an API binden) */
const respectBuffer = ref(true)
const autoSync = ref(true)
</script>
