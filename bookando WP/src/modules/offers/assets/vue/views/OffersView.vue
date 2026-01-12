<!-- OffersView.vue -->
<template>
  <AppShell>
    <div class="bookando-admin-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <template #header>
          <AppPageHeader
            :title="t('mod.offers.title') || 'Angebote'"
            hide-brand-below="md"
          />
        </template>

        <template #nav>
          <AppTabs
            v-model="currentTab"
            :tabs="tabsForTabs"
            nav-only
          />
        </template>

        <!-- COURSES -->
        <div
          v-if="currentTab === 'courses'"
          class="bookando-container bookando-py-sm"
        >
          <Courses
            :courses="courses"
            :categories="categories"
            :tags="tags"
            @create="openCourseForm()"
            @edit="openCourseForm($event)"
            @delete="deleteCourse"
          />
        </div>

        <!-- SERVICES -->
        <div
          v-else-if="currentTab === 'services'"
          class="bookando-container bookando-py-sm"
        >
          <Services
            :categories="categories"
            :tags="tags"
            :services="services"
            :loading="loading"
            :sort-state="sortState"
            :is-mobile-view="isMobileView"
            @sort="onSort"
            @create-service="openServiceForm()"
            @edit-service="openServiceForm($event)"
            @duplicate-service="duplicateService"
            @delete-service="deleteService"
            @refresh="loadData"
          />
        </div>

        <!-- EVENTS -->
        <div
          v-else-if="currentTab === 'events'"
          class="bookando-container bookando-py-sm"
        >
          <Events
            :events="events"
            :categories="categories"
            @create="openEventForm()"
            @edit="openEventForm($event)"
            @delete="deleteEvent"
          />
        </div>

        <!-- PACKAGES -->
        <div
          v-else-if="currentTab === 'packages'"
          class="bookando-container bookando-py-sm"
        >
          <Packages
            :packages="servicePackages"
            :services="services"
            @create="openPackageForm()"
            @edit="openPackageForm($event)"
            @delete="deletePackage"
          />
        </div>

        <!-- COUPONS -->
        <div
          v-else-if="currentTab === 'coupons'"
          class="bookando-container bookando-py-sm"
        >
          <Coupons
            :coupons="coupons"
            @create="openCouponForm()"
            @edit="openCouponForm($event)"
            @delete="deleteCoupon"
          />
        </div>

        <!-- CATEGORIES -->
        <div
          v-else-if="currentTab === 'categories'"
          class="bookando-container"
        >
          <Categories
            :categories="categories"
            :usage-map="categoryUsage"
            @create="openCategoryForm()"
            @edit="openCategoryForm($event)"
            @delete="deleteCategory"
            @refresh="loadData"
          />
        </div>

        <!-- TAGS -->
        <div
          v-else-if="currentTab === 'tags'"
          class="bookando-container"
        >
          <Tags
            :tags="tags"
            :usage-map="tagUsage"
            @create="openTagForm()"
            @edit="openTagForm($event)"
            @delete="deleteTag"
            @merge="mergeTags"
            @refresh="loadData"
          />
        </div>

        <!-- Platzhalter für weitere Tabs -->
        <div
          v-else
          class="bookando-container"
        >
          <div class="bookando-card">
            <div class="bookando-card-body">
              <p class="bookando-m-0">
                {{ activeLabel }} – {{ t('ui.common.coming_soon') || 'Bald verfügbar' }}
              </p>
            </div>
          </div>
        </div>

        <!-- FORMS -->
        <CategoriesForm
          v-if="showCategoryForm"
          :model-value="editingCategory"
          @save="saveCategory"
          @cancel="closeCategoryForm"
        />
        <TagsForm
          v-if="showTagForm"
          :model-value="editingTag"
          @save="saveTag"
          @cancel="closeTagForm"
        />
        <CoursesForm
          v-if="showCourseForm"
          :model-value="editingCourse"
          :categories="categories"
          :tags="tags"
          @save="saveCourse"
          @cancel="closeCourseForm"
        />
        <EventsForm
          v-if="showEventForm"
          :model-value="editingEvent"
          :categories="categories"
          @save="saveEvent"
          @cancel="closeEventForm"
        />
        <PackagesForm
          v-if="showPackageForm"
          :model-value="editingPackage"
          :services="services"
          @save="savePackage"
          @cancel="closePackageForm"
        />
        <CouponsForm
          v-if="showCouponForm"
          :model-value="editingCoupon"
          @save="saveCoupon"
          @cancel="closeCouponForm"
        />
        <ServicesForm
          v-if="showServiceForm"
          :model-value="editingService"
          :categories="categories"
          :tags="tags"
          @save="saveService"
          @cancel="closeServiceForm"
        />
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'

import Services from '../components/services/Services.vue'
import ServicesForm from '../components/services/ServicesForm.vue'
import Courses from '../components/courses/Courses.vue'
import CoursesForm from '../components/courses/CoursesForm.vue'
import Events from '../components/events/Events.vue'
import EventsForm from '../components/events/EventsForm.vue'
import Packages from '../components/packages/Packages.vue'
import PackagesForm from '../components/packages/PackagesForm.vue'
import Coupons from '../components/coupons/Coupons.vue'
import CouponsForm from '../components/coupons/CouponsForm.vue'
import Categories from '../components/Categories.vue'
import CategoriesForm from '../components/CategoriesForm.vue'
import Tags from '../components/Tags.vue'
import TagsForm from '../components/TagsForm.vue'

/* ► Breakpoints ausschließlich über useResponsive.ts */
import { useResponsive } from '@core/Composables/useResponsive'

const { t } = useI18n()

/* License / Module */
const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = BOOKANDO.required_plan ?? null

/* Tabs */
const tabsI18n = computed(() => ([
  { key: 'courses',     label: t('mod.offers.tabs.courses')     || 'Kurse' },
  { key: 'services',    label: t('mod.offers.tabs.services')    || 'Dienstleistungen' },
  { key: 'events',      label: t('mod.offers.tabs.events')      || 'Events' },
  { key: 'packages',    label: t('mod.offers.tabs.packages')    || 'Pakete' },
  { key: 'coupons',     label: t('mod.offers.tabs.coupons')     || 'Gutscheine' },
  { key: 'templates',   label: t('mod.offers.tabs.templates')   || 'Vorlagen' },
  { key: 'categories',  label: t('mod.offers.tabs.categories')  || 'Kategorien' },
  { key: 'tags',        label: t('mod.offers.tabs.tags')        || 'Schlagwörter' },
]))
type MainTab = 'courses'|'services'|'events'|'packages'|'coupons'|'categories'|'tags'
const currentTab = ref<MainTab>('services')
const activeLabel = computed(() => tabsI18n.value.find(t => t.key === currentTab.value)?.label || '')
const tabsForTabs = computed(() => tabsI18n.value.map(t => ({ label: t.label, value: t.key })))

/* ► Breakpoint-State (analog EmployeesView): */
const { isBelowMd } = useResponsive()
const isMobileView = computed(() => !!isBelowMd.value)

/* Types */
type Category = { id:number; name:string; slug?:string; color?:string|null; sort?:number; status?:'active'|'hidden'; description?:string|null }
type Tag = { id:number; name:string; slug?:string; color?:string|null; status?:'active'|'hidden' }
type PriceAction = { id:number; label?:string; start?:string; end?:string; price:number; banner?:string; highlight?:'accent'|'warning'|'danger' }
type DynamicRule = {
  id:number; label?:string; mode:'fixed'|'percent'; amount:number;
  days?: Array<'mon'|'tue'|'wed'|'thu'|'fri'|'sat'|'sun'>;
  dateStart?:string|null; dateEnd?:string|null; timeStart?:string|null; timeEnd?:string|null;
}
type AvailabilitySlot = { start:string; end:string }
type AvailabilityDay = { key:'mon'|'tue'|'wed'|'thu'|'fri'|'sat'|'sun'; enabled:boolean; slots:AvailabilitySlot[] }
type Service = {
  id:number
  category_id:number|null
  tag_ids:number[]
  name:string
  description?:string
  duration_min:number
  price:number
  currency?:string
  external_product_id?:string
  status?:'active'|'hidden'
  price_actions?:PriceAction[]
  dynamic_rules?:DynamicRule[]
  availability?:AvailabilityDay[]
}
type Course = {
  id:number
  category_id:number|null
  tag_ids:number[]
  name:string
  description?:string
  status?:'active'|'hidden'
}
type Event = {
  id:number
  category_id:number|null
  title:string
  start_at:string
  end_at:string
  location?:string|null
  capacity?:number|null
  status?:'active'|'hidden'
  description?:string|null
}
type ServicePackage = {
  id:number
  name:string
  service_ids:number[]
  price:number
  sale_price?:number|null
  currency?:string
  status?:'active'|'hidden'
  description?:string|null
}
type Coupon = {
  id:number
  code:string
  description?:string|null
  discount_type:'percent'|'fixed'
  discount_value:number
  min_order?:number|null
  valid_from?:string|null
  valid_until?:string|null
  usage_limit?:number|null
  currency?:string
  status?:'active'|'hidden'
}

/* State */
const categories = ref<Category[]>([])
const tags       = ref<Tag[]>([])
const services   = ref<Service[]>([])
const courses    = ref<Course[]>([])
const events     = ref<Event[]>([])
const servicePackages = ref<ServicePackage[]>([])
const coupons    = ref<Coupon[]>([])
const loading    = ref(false)
const sortState  = ref<{ key:'name'|'price'|'duration'|'category'|'status'; direction:'asc'|'desc' }>({ key:'name', direction:'asc' })

/* Load dummy data (wie geliefert) */
async function loadData(){
  loading.value = true
  try {
    if (!categories.value.length) {
      categories.value = [
        { id: 1, name: 'Fahrstunden',  slug:'fahrstunden',  color:'#4772f2', sort:1, status:'active' },
        { id: 2, name: 'Schnupper',    slug:'schnupper',    color:'#00b5ad', sort:2, status:'active' },
        { id: 3, name: 'Werkstatt',    slug:'werkstatt',    color:'#8a8d93', sort:3, status:'hidden' },
      ]
    }
    if (!tags.value.length) {
      tags.value = [
        { id: 101, name:'Aktion',     slug:'aktion',     color:null, status:'active' },
        { id: 102, name:'Einsteiger', slug:'einsteiger', color:null, status:'active' },
        { id: 103, name:'Intensiv',   slug:'intensiv',   color:null, status:'active' },
      ]
    }
    if (!services.value.length) {
      services.value = [
        {
          id: 10, category_id: 1, tag_ids:[101,103],
          name: 'Fahrstunde 45 Min', duration_min: 45, price: 85, currency: 'CHF', status: 'active',
          price_actions: [{ id:1, label:'Frühlingsaktion', start:'2025-03-01', end:'2025-04-30', price:79, banner:'-7 CHF', highlight:'accent' }],
          dynamic_rules: [{ id:1, mode:'percent', amount:-10, days:['sat'], label:'Samstags -10%' }],
          availability: [
            { key:'mon', enabled:true,  slots:[{start:'08:00', end:'12:00'},{start:'13:00', end:'17:00'}] },
            { key:'tue', enabled:true,  slots:[{start:'08:00', end:'12:00'}] },
            { key:'wed', enabled:false, slots:[] },
            { key:'thu', enabled:true,  slots:[{start:'10:00', end:'16:00'}] },
            { key:'fri', enabled:true,  slots:[{start:'08:00', end:'14:00'}] },
            { key:'sat', enabled:true,  slots:[{start:'09:00', end:'12:00'}] },
            { key:'sun', enabled:false, slots:[] },
          ]
        },
        { id: 11, category_id: 1, tag_ids:[102], name:'Fahrstunde 60 Min', duration_min:60, price:110, currency:'CHF', status:'active' },
        { id: 12, category_id: 2, tag_ids:[102], name:'Schnupperkurs',     duration_min:60, price: 50, currency:'CHF', status:'active' },
      ]
    }
    if (!courses.value.length) {
      courses.value = [
        {
          id: 201,
          category_id: 2,
          tag_ids:[102],
          name:'Basiskurs A',
          description:'Dieser Gruppenkurs stärkt die Sicherheit auf dem Bike und kombiniert Theorie mit Praxis.',
          status:'active',
          notify_participants: true,
          sessions: [
            { date: '2025-04-12', time_start: '09:00', time_end: '12:00' },
            { date: '2025-04-13', time_start: '09:00', time_end: '12:30' },
          ],
          is_recurring: false,
          booking_starts_immediately: true,
          booking_closes_on_start: true,
          location_id: 'bruderwerk',
          organizer_id: 'francesco',
          team_ids: ['team-a'],
          price: 195,
          capacity: 12,
          allow_group_booking: true,
          allow_repeat_booking: true,
          deposit_enabled: false,
          close_on_minimum_enabled: false,
          limit_extra_enabled: false,
          gallery: [
            { _localId: 1, url: 'https://bruderwerk.ch/wp-content/uploads/2024/11/20230414_203723-scaled.jpg', name: '20230414_203723-scaled.jpg' },
          ],
          color_mode: 'preset',
          color_value: '#1788FB',
          show_on_website: true,
          waitlist_enabled: false,
          cancellation_lead_time: '2_days',
          redirect_url: 'https://www.bruderwerk.ch/my-ridehub',
          payment_link_enabled: true,
          payment_on_site: true,
          google_meet_enabled: false,
          lesson_space_enabled: false,
        },
      ]
    }
    if (!events.value.length) {
      events.value = [
        {
          id: 301,
          title: 'Intensiv-Workshop',
          category_id: 1,
          start_at: '2025-05-12T09:00',
          end_at: '2025-05-12T12:30',
          location: 'Seminarraum B',
          capacity: 18,
          status: 'active',
          description: 'Praxisnaher Vormittags-Workshop mit Fahrtrainer:innen.'
        }
      ]
    }
    if (!servicePackages.value.length) {
      servicePackages.value = [
        {
          id: 401,
          name: 'Fahrpaket Starter',
          service_ids: [10, 11],
          price: 185,
          sale_price: 169,
          currency: 'CHF',
          status: 'active',
          description: 'Kombiniert zwei Fahrstunden mit 60 Minuten zum Vorteilspreis.'
        }
      ]
    }
    if (!coupons.value.length) {
      coupons.value = [
        {
          id: 501,
          code: 'SPRING10',
          discount_type: 'percent',
          discount_value: 10,
          min_order: 100,
          valid_from: '2025-03-01',
          valid_until: '2025-04-30',
          usage_limit: 250,
          currency: 'CHF',
          status: 'active',
          description: 'Frühlingsaktion für alle Dienstleistungen.'
        }
      ]
    }
    if (!templates.value.length) {
      templates.value = [
        {
          id: 601,
          type: 'course',
          name: 'Standard Gruppenkurs',
          category_id: 2,
          tag_ids: [102],
          description: 'Basiseinstellungen für Gruppenkurse inklusive Kapazitäten und Preis.',
          defaults: { price: 189, capacity: 10 },
          updated_at: '2025-02-15T10:30:00Z',
          status: 'active',
          share_with_team: true,
          available_online: true,
        },
        {
          id: 602,
          type: 'service',
          name: 'Privatlektion 60 Min',
          category_id: 1,
          tag_ids: [101],
          description: 'Empfohlene Standardeinstellungen für private Fahrstunden.',
          defaults: { price: 110, duration_min: 60 },
          updated_at: '2025-02-10T08:00:00Z',
          status: 'active',
          share_with_team: true,
          available_online: false,
        },
      ]
    }
    if (!events.value.length) {
      events.value = [
        {
          id: 301,
          title: 'Intensiv-Workshop',
          category_id: 1,
          start_at: '2025-05-12T09:00',
          end_at: '2025-05-12T12:30',
          location: 'Seminarraum B',
          capacity: 18,
          status: 'active',
          description: 'Praxisnaher Vormittags-Workshop mit Fahrtrainer:innen.'
        }
      ]
    }
    if (!servicePackages.value.length) {
      servicePackages.value = [
        {
          id: 401,
          name: 'Fahrpaket Starter',
          service_ids: [10, 11],
          price: 185,
          sale_price: 169,
          currency: 'CHF',
          status: 'active',
          description: 'Kombiniert zwei Fahrstunden mit 60 Minuten zum Vorteilspreis.'
        }
      ]
    }
    if (!coupons.value.length) {
      coupons.value = [
        {
          id: 501,
          code: 'SPRING10',
          discount_type: 'percent',
          discount_value: 10,
          min_order: 100,
          valid_from: '2025-03-01',
          valid_until: '2025-04-30',
          usage_limit: 250,
          currency: 'CHF',
          status: 'active',
          description: 'Frühlingsaktion für alle Dienstleistungen.'
        }
      ]
    }
  } finally {
    loading.value = false
  }
}
onMounted(loadData)

/* Derived usage counters */
const categoryUsage = computed<Record<number, number>>(() => {
  const m:Record<number, number> = {}
  categories.value.forEach(c => m[c.id] = 0)
  services.value.forEach(s => { if (s.category_id) m[s.category_id] = (m[s.category_id]||0) + 1 })
  courses.value.forEach(c => { if (c.category_id) m[c.category_id] = (m[c.category_id]||0) + 1 })
  events.value.forEach(e => { if (e.category_id) m[e.category_id] = (m[e.category_id]||0) + 1 })
  return m
})
const tagUsage = computed<Record<number, number>>(() => {
  const m:Record<number, number> = {}
  tags.value.forEach(t => m[t.id] = 0)
  services.value.forEach(s => s.tag_ids?.forEach(id => m[id] = (m[id]||0) + 1))
  courses.value.forEach(c => c.tag_ids?.forEach(id => m[id] = (m[id]||0) + 1))
  return m
})

/* Sorting passt Services durch */
function onSort(s:{ key:any; direction:any }) { sortState.value = s }

/* CATEGORY FORMS */
const showCategoryForm = ref(false)
const editingCategory  = ref<Category|null>(null)
function openCategoryForm(cat?:Category){ editingCategory.value = cat ? { ...cat } : { id:0, name:'', status:'active' }; showCategoryForm.value = true }
function closeCategoryForm(){ showCategoryForm.value = false; editingCategory.value = null }
function saveCategory(cat:Category){
  if (!cat.id) {
    cat.id = Math.max(0, ...categories.value.map(c=>c.id)) + 1
    categories.value.push(cat)
  } else {
    const i = categories.value.findIndex(c=>c.id===cat.id)
    if (i>=0) categories.value.splice(i,1,cat)
  }
  closeCategoryForm()
}
function deleteCategory(cat:Category){
  services.value = services.value.map(s => (s.category_id === cat.id ? { ...s, category_id: null } : s))
  courses.value  = courses.value.map(c => (c.category_id === cat.id ? { ...c, category_id: null } : c))
  events.value   = events.value.map(e => (e.category_id === cat.id ? { ...e, category_id: null } : e))
  categories.value = categories.value.filter(c => c.id !== cat.id)
}

/* TAG FORMS */
const showTagForm = ref(false)
const editingTag  = ref<Tag|null>(null)
function openTagForm(tag?:Tag){ editingTag.value = tag ? { ...tag } : { id:0, name:'', status:'active' }; showTagForm.value = true }
function closeTagForm(){ showTagForm.value = false; editingTag.value = null }
function saveTag(tag:Tag){
  if (!tag.id) {
    tag.id = Math.max(0, ...tags.value.map(t=>t.id)) + 1
    tags.value.push(tag)
  } else {
    const i = tags.value.findIndex(t=>t.id===tag.id)
    if (i>=0) tags.value.splice(i,1,tag)
  }
  closeTagForm()
}
function deleteTag(tag:Tag){
  const delId = tag.id
  services.value = services.value.map(s => ({ ...s, tag_ids: (s.tag_ids||[]).filter(id => id !== delId) }))
  courses.value  = courses.value.map(c => ({ ...c, tag_ids: (c.tag_ids||[]).filter(id => id !== delId) }))
  tags.value = tags.value.filter(t => t.id !== delId)
}
function mergeTags(_payload:{ sourceIds:number[]; targetId:number }){
  const { sourceIds, targetId } = _payload
  if (!sourceIds.length || !targetId) return
  const set = new Set(sourceIds.filter(id => id !== targetId))
  const uniq = (arr:number[]) => Array.from(new Set(arr))
  services.value = services.value.map(s => ({ ...s, tag_ids: uniq((s.tag_ids||[]).map(id => set.has(id) ? targetId : id)) }))
  courses.value  = courses.value.map(c => ({ ...c, tag_ids: uniq((c.tag_ids||[]).map(id => set.has(id) ? targetId : id)) }))
  tags.value = tags.value.filter(t => !set.has(t.id))
}

/* COURSE FORMS */
const showCourseForm = ref(false)
const editingCourse  = ref<Course|null>(null)
function openCourseForm(course?:Course){ editingCourse.value = course ? { ...course } : null; showCourseForm.value = true }
function closeCourseForm(){ showCourseForm.value = false; editingCourse.value = null }
function saveCourse(course:Course){
  if (!course.id) {
    course.id = Math.max(0, ...courses.value.map(c => c.id)) + 1
    courses.value.push(course)
  } else {
    const i = courses.value.findIndex(c => c.id === course.id)
    if (i >= 0) courses.value.splice(i, 1, course)
  }
  closeCourseForm()
}
function deleteCourse(course:Course){
  courses.value = courses.value.filter(c => c.id !== course.id)
}

/* EVENT FORMS */
const showEventForm = ref(false)
const editingEvent  = ref<Event|null>(null)
function openEventForm(event?:Event){ editingEvent.value = event ? { ...event } : null; showEventForm.value = true }
function closeEventForm(){ showEventForm.value = false; editingEvent.value = null }
function saveEvent(event:Event){
  if (!event.id) {
    event.id = Math.max(0, ...events.value.map(e => e.id)) + 1
    events.value.push(event)
  } else {
    const i = events.value.findIndex(e => e.id === event.id)
    if (i >= 0) events.value.splice(i, 1, event)
  }
  closeEventForm()
}
function deleteEvent(event:Event){
  events.value = events.value.filter(e => e.id !== event.id)
}

/* PACKAGE FORMS */
const showPackageForm = ref(false)
const editingPackage  = ref<ServicePackage|null>(null)
function openPackageForm(pkg?:ServicePackage){ editingPackage.value = pkg ? { ...pkg } : null; showPackageForm.value = true }
function closePackageForm(){ showPackageForm.value = false; editingPackage.value = null }
function savePackage(pkg:ServicePackage){
  if (!pkg.id) {
    pkg.id = Math.max(0, ...servicePackages.value.map(p => p.id)) + 1
    servicePackages.value.push(pkg)
  } else {
    const i = servicePackages.value.findIndex(p => p.id === pkg.id)
    if (i >= 0) servicePackages.value.splice(i, 1, pkg)
  }
  closePackageForm()
}
function deletePackage(pkg:ServicePackage){
  servicePackages.value = servicePackages.value.filter(p => p.id !== pkg.id)
}

/* COUPON FORMS */
const showCouponForm = ref(false)
const editingCoupon  = ref<Coupon|null>(null)
function openCouponForm(coupon?:Coupon){ editingCoupon.value = coupon ? { ...coupon } : null; showCouponForm.value = true }
function closeCouponForm(){ showCouponForm.value = false; editingCoupon.value = null }
function saveCoupon(coupon:Coupon){
  if (!coupon.id) {
    coupon.id = Math.max(0, ...coupons.value.map(c => c.id)) + 1
    coupons.value.push(coupon)
  } else {
    const i = coupons.value.findIndex(c => c.id === coupon.id)
    if (i >= 0) coupons.value.splice(i, 1, coupon)
  }
  closeCouponForm()
}
function deleteCoupon(coupon:Coupon){
  coupons.value = coupons.value.filter(c => c.id !== coupon.id)
}

/* SERVICE FORMS */
const showServiceForm = ref(false)
const editingService  = ref<Service|null>(null)
function openServiceForm(srv?:Service){ editingService.value = srv ? { ...srv } : null; showServiceForm.value = true }
function closeServiceForm(){ showServiceForm.value = false; editingService.value = null }
function saveService(srv:Service){
  if (!srv.id) {
    srv.id = Math.max(0, ...services.value.map(s=>s.id)) + 1
    services.value.push(srv)
  } else {
    const i = services.value.findIndex(s=>s.id===srv.id)
    if (i>=0) services.value.splice(i,1,srv)
  }
  closeServiceForm()
}
function duplicateService(srv:Service){
  const copy = JSON.parse(JSON.stringify(srv)) as Service
  copy.id = 0; copy.name = `${srv.name} (${t('core.common.copy') || 'Kopie'})`
  openServiceForm(copy)
}
function deleteService(srv:Service){
  services.value = services.value.filter(s => s.id !== srv.id)
  servicePackages.value = servicePackages.value.map(pkg => ({
    ...pkg,
    service_ids: pkg.service_ids.filter(id => id !== srv.id)
  }))
}
</script>
