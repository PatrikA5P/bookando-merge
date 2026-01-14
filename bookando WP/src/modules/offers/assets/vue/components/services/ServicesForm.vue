<!-- ServicesForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="srv-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <!-- HEADER -->
      <template #header>
        <h2 id="srv-title">
          {{ form.id ? t('mod.services.edit') || 'Dienstleistung bearbeiten' : t('mod.services.add') || 'Dienstleistung hinzufügen' }}
        </h2>
        <AppButton
          icon="x"
          btn-type="icononly"
          variant="standard"
          size="square"
          icon-size="md"
          @click="onCancel"
        />
      </template>

      <!-- TABS -->
      <template #tabs>
        <AppTabs
          v-model="tab"
          :tabs="tabsDef"
          nav-only
        />
      </template>

      <!-- BODY -->
      <template #default>
        <form
          :id="formId"
          class="bookando-form services-form"
          novalidate
          autocomplete="off"
          @submit.prevent="onSubmit"
        >
          <KeepAlive>
            <component
              :is="currentTab"
              v-model="form"
              :categories="categories"
              :locations="locations"
              :employees="employees"
              :custom-fields="customFields"
              :period-units="periodUnits"
              :duration-units="durationUnits"
              :booking-window-modes="bookingWindowModes"
              :status-opts="statusOpts"
              :day-options="dayOptions"
            />
          </KeepAlive>
        </form>
      </template>

      <!-- FOOTER -->
      <template #footer>
        <div class="bookando-form-buttons bookando-form-buttons--split">
          <AppButton
            btn-type="textonly"
            variant="secondary"
            size="dynamic"
            type="button"
            @click="onCancel"
          >
            {{ t('core.common.cancel') }}
          </AppButton>
          <AppButton
            btn-type="full"
            variant="primary"
            size="dynamic"
            type="submit"
            :form="formId"
          >
            {{ t('core.common.save') }}
          </AppButton>
        </div>
      </template>
    </AppForm>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { defineAsyncComponent } from 'vue'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'


type Id = number
type Category   = { id:Id; name:string }
type Location   = { id:Id; name:string }
type Employee   = { id:Id; full_name:string }
type CustomFld  = { id:Id; label:string }

type TR = { start:string; end:string }
type AvCombo = { id?: number; locationIds: number[]; work: TR[] }
type AvDay = { key:'mon'|'tue'|'wed'|'thu'|'fri'|'sat'|'sun'; combos: AvCombo[] }

type PriceAction = { _localId:number; id?:Id; label?:string; start?:string; end?:string; price:number }
type DynamicRule = {
  _localId:number; id?:Id; label?:string; mode:'fixed'|'percent'; amount:number;
  days?: Array<'mon'|'tue'|'wed'|'thu'|'fri'|'sat'|'sun'>;
  dateStart?:string|null; dateEnd?:string|null; timeStart?:string|null; timeEnd?:string|null;
}
type Variant = { _localId:number; value:number; unit:'min'|'h'; price:number; sale_price?:number }
type GalleryItem = { _localId:number; url:string; name?:string; file?:File }

type BookingWindowMode = 'max_days'|'date_range'|'unlimited'

export type ServiceFormVm = {
  id?:Id
  // Details
  name:string
  category_id: Id | null
  show_on_website:boolean
  recurrence_mode: 'all'|'daily'|'weekly'|'monthly'|'disabled'
  limit_per_customer:boolean
  limit_count:number
  limit_period_value:number
  limit_period_unit:'hour'|'day'|'week'|'month'|'year'
  location_ids: Id[]
  employee_ids: Id[]
  avatar_url?:string
  avatar_border?:string
  description?:string
  // Pricing
  duration_min:number
  price:number
  sale_price?:number
  dynamic_pricing_enabled:boolean
  price_actions:PriceAction[]
  dynamic_rules:DynamicRule[]
  variants: Variant[]
  buffer_before_min:number
  buffer_after_min:number
  capacity_min:number
  capacity_max:number
  // Availability (NEU)
  availability: AvDay[]
  // Form
  custom_field_ids: Id[]
  // Notifications
  send_calendar_invite:boolean
  email_reminder_enabled:boolean; email_reminder_value:number; email_reminder_unit:ServiceFormVm['limit_period_unit']
  sms_reminder_enabled:boolean;   sms_reminder_value:number;   sms_reminder_unit:ServiceFormVm['limit_period_unit']
  followup_email_enabled:boolean; followup_email_value:number; followup_email_unit:ServiceFormVm['limit_period_unit']
  // Settings
  default_appointment_status:'confirmed'|'pending'
  booking_window_mode:BookingWindowMode
  booking_window_value:number
  booking_window_type:'calendar'|'business'
  booking_start?:string|null
  booking_end?:string|null
  lead_time_book_value:number;       lead_time_book_unit:ServiceFormVm['limit_period_unit']
  lead_time_cancel_value:number;     lead_time_cancel_unit:ServiceFormVm['limit_period_unit']
  lead_time_reschedule_value:number; lead_time_reschedule_unit:ServiceFormVm['limit_period_unit']
  redirect_url?:string|null
  // Galerie
  gallery: GalleryItem[]
}

const props = defineProps<{
  modelValue: Partial<ServiceFormVm> | null,
  categories: Category[],
  locations: Location[],
  employees: Employee[],
  customFields: CustomFld[]
}>()
const emit = defineEmits<{ (event:'save', value:ServiceFormVm):void; (event:'cancel'):void }>()
const { t } = useI18n()

/* ------------------ Shared options ------------------ */
const periodUnits = [
  { label: 'Minute', value: 'hour' }, // (Legacy-Label belassen)
  { label: 'Stunde', value: 'hour' },
  { label: 'Tag',    value: 'day' },
  { label: 'Woche',  value: 'week' },
  { label: 'Monat',  value: 'month' },
  { label: 'Jahr',   value: 'year' },
] as const
const durationUnits = [
  { label: 'Min', value: 'min' },
  { label: 'h',   value: 'h' }
] as const
const bookingWindowModes = [
  { label: t('mod.services.form.settings.max_days'), value: 'max_days' },
  { label: t('mod.services.form.settings.daterange'), value: 'date_range' },
  { label: t('mod.services.form.settings.unlimited'), value: 'unlimited' }
] as const
const statusOpts = computed(() => ([
  { label: t('core.status.confirmed') || 'Freigegeben', value: 'confirmed' },
  { label: t('core.status.pending') || 'Ausstehend', value: 'pending' }
]))
const dayOptions = computed(() => ([
  { label: t('ui.days.mon') || 'Montag', value: 'mon' },
  { label: t('ui.days.tue') || 'Dienstag', value: 'tue' },
  { label: t('ui.days.wed') || 'Mittwoch', value: 'wed' },
  { label: t('ui.days.thu') || 'Donnerstag', value: 'thu' },
  { label: t('ui.days.fri') || 'Freitag', value: 'fri' },
  { label: t('ui.days.sat') || 'Samstag', value: 'sat' },
  { label: t('ui.days.sun') || 'Sonntag', value: 'sun' },
]))

/* ------------------ Empty & hydration ------------------ */
const emptyAvailability: AvDay[] = [
  { key:'mon', combos:[] },
  { key:'tue', combos:[] },
  { key:'wed', combos:[] },
  { key:'thu', combos:[] },
  { key:'fri', combos:[] },
  { key:'sat', combos:[] },
  { key:'sun', combos:[] },
]
const empty: ServiceFormVm = {
  id: 0,
  name: '',
  category_id: null,
  show_on_website: true,
  recurrence_mode: 'disabled',
  limit_per_customer: false,
  limit_count: 1,
  limit_period_value: 1,
  limit_period_unit: 'month',
  location_ids: [],
  employee_ids: [],
  avatar_url: '',
  avatar_border: '#12DE9D',
  description: '',
  duration_min: 60,
  price: 0,
  sale_price: 0,
  dynamic_pricing_enabled: false,
  price_actions: [],
  dynamic_rules: [],
  variants: [],
  buffer_before_min: 0,
  buffer_after_min: 0,
  capacity_min: 1,
  capacity_max: 1,
  availability: JSON.parse(JSON.stringify(emptyAvailability)),
  custom_field_ids: [],
  send_calendar_invite: true,
  email_reminder_enabled: false, email_reminder_value: 24, email_reminder_unit: 'hour',
  sms_reminder_enabled:   false, sms_reminder_value:   2,  sms_reminder_unit:   'hour',
  followup_email_enabled: false, followup_email_value: 24, followup_email_unit: 'hour',
  default_appointment_status: 'confirmed',
  booking_window_mode: 'max_days',
  booking_window_value: 60,
  booking_window_type: 'calendar',
  booking_start: null,
  booking_end: null,
  lead_time_book_value: 4,  lead_time_book_unit: 'hour',
  lead_time_cancel_value: 4,  lead_time_cancel_unit: 'hour',
  lead_time_reschedule_value: 4,  lead_time_reschedule_unit: 'hour',
  redirect_url: '',
  gallery: []
}

function normalizeAvailability(input:any): AvDay[] {
  if (Array.isArray(input) && input.every((d:any)=> Array.isArray(d?.combos))) return input as AvDay[]
  if (Array.isArray(input) && input.every((d:any)=> 'enabled' in d || 'slots' in d)) {
    const map:Record<string,AvDay> = Object.fromEntries(emptyAvailability.map(d=>[d.key,{key:d.key as any, combos:[]}]))
    for (const d of input) {
      if (d?.enabled && Array.isArray(d?.slots) && d.slots.length) {
        map[d.key].combos.push({ locationIds: [], work: d.slots.map((s:any)=>({start:s.start,end:s.end})) })
      }
    }
    return Object.values(map)
  }
  return JSON.parse(JSON.stringify(emptyAvailability))
}

/* ------------------ Form state ------------------ */
const form = ref<ServiceFormVm>({ ...empty })
watch(() => props.modelValue, (value) => {
  const base = { ...empty, ...(value || {}) } as ServiceFormVm
  base.price_actions  = (base.price_actions || []).map((p:any,i:number)=>({ _localId:i+1, ...p }))
  base.dynamic_rules  = (base.dynamic_rules || []).map((r:any,i:number)=>({ _localId:i+1, ...r }))
  base.variants       = (base.variants || []).map((vv:any,i:number)=>({ _localId:i+1, ...vv, value: vv?.value ?? vv?._value ?? null }))
  base.gallery        = (base.gallery || []).map((g:any,i:number)=>({ _localId:i+1, ...g }))
  base.availability   = normalizeAvailability((value as any)?.availability)
  form.value = base
}, { immediate:true })

/* ------------------ Tabs ------------------ */
const tabsDef = computed(() => ([
  { label: t('core.common.details') || 'Details', value: 'details' },
  { label: t('mod.services.form.tab.pricing') || 'Dauer & Preise', value: 'pricing' },
  { label: t('mod.services.form.tab.availability') || 'Verfügbarkeiten', value: 'availability' },
  { label: t('mod.services.form') || 'Buchungsformular', value: 'form' },
  { label: t('mod.services.form.tab.notifications') || 'Benachrichtigungen', value: 'notifications' },
  { label: t('mod.services.form.tab.gallery') || 'Galerie', value: 'gallery' },
  { label: t('mod.services.form.tab.settings') || 'Einstellungen', value: 'settings' },
]))
type TabKey = 'details'|'pricing'|'availability'|'form'|'notifications'|'gallery'|'settings'
const tab = ref<TabKey>('details')

/* Lazy import (kann auch statisch importiert werden) */
const Tabs = {
  details:       defineAsyncComponent(() => import('./tabs/ServicesFormTabDetails.vue')),
  pricing:       defineAsyncComponent(() => import('./tabs/ServicesFormTabPricing.vue')),
  availability:  defineAsyncComponent(() => import('./tabs/ServicesFormTabAvailability.vue')),
  form:          defineAsyncComponent(() => import('./tabs/ServicesFormTabForm.vue')),
  notifications: defineAsyncComponent(() => import('./tabs/ServicesFormTabNotifications.vue')),
  gallery:       defineAsyncComponent(() => import('./tabs/ServicesFormTabGallery.vue')),
  settings:      defineAsyncComponent(() => import('./tabs/ServicesFormTabSettings.vue')),
}
const currentTab = computed(() => Tabs[tab.value])

/* ------------------ Submit / Cancel ------------------ */
const formId = `srv-${Math.random().toString(36).slice(2,8)}`
function onSubmit(){
  const clean = JSON.parse(JSON.stringify(form.value)) as ServiceFormVm
  clean.price_actions.forEach((p:any)=>delete p._localId)
  clean.dynamic_rules.forEach((r:any)=>delete r._localId)
  clean.variants = clean.variants.map((variant:any) => ({
    ...variant,
    minutes: variant.unit === 'h' ? variant.value * 60 : variant.value
  })) as any
  clean.gallery = clean.gallery.map((g:any) => ({ url: g.url, name: g.name })) as any
  emit('save', clean)
}
function onCancel(){ emit('cancel') }

/* ------------------ Derived props ------------------ */
const categories  = computed(()=> props.categories || [])
const locations   = computed(()=> props.locations  || [])
const employees   = computed(()=> props.employees  || [])
const customFields= computed(()=> props.customFields || [])
</script>
