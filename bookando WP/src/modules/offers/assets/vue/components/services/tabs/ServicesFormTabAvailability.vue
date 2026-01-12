<!-- ServicesFormTabAvailability.vue -->
<template>
  <section
    class="services-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="calendar"
      :title="t('mod.services.availability.header') || 'Verfügbarkeiten'"
      :description="t('mod.services.availability.hint') || 'Diese Slots ergänzen die Arbeitszeiten deiner Mitarbeitenden.'"
      layout="stack"
    >
      <div class="services-form__stack">
        <AppServiceAvailabilityCard
          v-for="(day, idx) in form.availability"
          :key="day.key"
          :day-key="day.key"
          :title="dayLabel(day.key)"
          :combos="day.combos"
          :location-options="locations"
          location-option-label="name"
          location-option-value="id"
          @apply-to-days="({ toDayKeys }) => applyAvailToDays(idx, toDayKeys)"
          @save-combo="({ index, value }) => saveAvailCombo(idx, index, value)"
          @delete-combo="(i) => removeAvailCombo(idx, i)"
        />
      </div>
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppServiceAvailabilityCard from '../ui/AppServiceAvailabilityCard.vue'
import AppServicesFormSection from '../ui/AppServicesFormSection.vue'
import type { ServiceFormVm } from '../ServicesForm.vue'

const { t } = useI18n()
const model = defineModel<ServiceFormVm>({ local: false })
const form = computed({ get: () => model.value!, set: v => (model.value = v) })

const props = defineProps<{
  locations: Array<{ id:number; name:string }>,
  dayOptions: Array<{label:string; value:string}>
}>()

function dayLabel(key:any){ return (props.dayOptions.find(d=>d.value===key)?.label) as string }
function applyAvailToDays(fromIdx:number, toKeys:string[]){
  const src = form.value.availability[fromIdx]
  const cloneValue = <T>(value: T): T => JSON.parse(JSON.stringify(value)) as T
  const keyToIdx:Record<string,number> = { mon:0,tue:1,wed:2,thu:3,fri:4,sat:5,sun:6 }
  for (const k of toKeys){ const i = keyToIdx[k]; if (i==null || i===fromIdx) continue; form.value.availability[i].combos = cloneValue(src.combos) }
}
type TR = { start:string; end:string }
type AvCombo = { id?: number; locationIds: number[]; work: TR[] }
function saveAvailCombo(dayIdx:number, index:number|null, combo:AvCombo){
  const day = form.value.availability[dayIdx]
  const copy:AvCombo = { locationIds:[...(combo.locationIds || [])], work:[...(combo.work || [])] }
  if (index == null) {
    const id = Math.max(0, ...day.combos.map(c => c.id || 0)) + 1
    day.combos.push({ id, ...copy })
  } else {
    const old = day.combos[index]
    day.combos.splice(index, 1, { id: old.id, ...copy })
  }
}
function removeAvailCombo(dayIdx:number, index:number){ form.value.availability[dayIdx].combos.splice(index, 1) }
</script>
