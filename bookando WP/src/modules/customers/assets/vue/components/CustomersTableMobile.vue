<!-- CustomersTableMobile.vue -->
<template>
  <div
    :class="[
      'bookando-container',
      'bookando-pl-none',
      'bookando-pr-none',
      'bookando-table-cards-context',
      'bookando-table-cards--md',
      containerClass
    ]"
  >
    <!-- Sticky Header -->
    <div class="bookando-table-cards-header">
      <div class="header-col header-checkbox">
        <AppCheckbox
          :model-value="allSelected"
          :aria-label="t('ui.a11y.select_all')"
          align="left"
          @update:model-value="toggleAll"
          @click.stop
        />
      </div>
      <div class="header-col header-title">
        {{ fieldLabel(t, 'customer', MODULE) }}
      </div>
      <div class="header-col header-actions">
        {{ t('core.common.actions') }}
      </div>
    </div>

    <!-- Cards -->
    <div class="bookando-table-cards">
      <div
        v-for="item in items"
        :key="item.id"
        class="bookando-table-card bookando-table-card--md bookando-table-card--comfy"
        :class="{ 'is-selected': selectedItems.includes(item.id), 'is-expanded': expandedItems.has(item.id) }"
        @click="toggleExpand(item.id)"
      >
        <div class="bookando-table-card__row bookando-table-card__row--top">
          <div class="bookando-table-card__select">
            <AppCheckbox
              :model-value="selectedItems.includes(item.id)"
              :aria-label="t('ui.a11y.select_item')"
              align="left"
              @update:model-value="val => toggleItem(item.id, val)"
              @click.stop
            />
          </div>

          <!-- Identity row -->
          <div class="bookando-table-card__main-left">
            <div class="bookando-card customer-identity-card customer-identity-card--flat">
              <div class="bookando-flex customer-identity-row">
                <div class="bookando-table-card__thumb">
                  <AppAvatar
                    :src="avatarSrc(item)"
                    :initials="initials(item)"
                    size="sm"
                    fit="cover"
                    :alt="`${item.first_name ?? ''} ${item.last_name ?? ''}`.trim()"
                    @error="event => onAvatarError(event, item)"
                  />
                </div>
                <div class="bookando-table-card__meta">
                  <div class="title">
                    {{ item.first_name }} {{ item.last_name }}
                    <span class="bookando-text-muted bookando-text-sm"> (ID {{ item.id }})</span>
                  </div>
                  <div class="muted">
                    <AppIcon
                      name="mail"
                      class="bookando-icon bookando-mr-xxs"
                    />
                    <template v-if="item.email && String(item.email).trim()">
                      <a
                        :href="`mailto:${item.email}`"
                        class="bookando-link"
                        @click.stop
                      >{{ item.email }}</a>
                    </template>
                    <template v-else>
                      –
                    </template>
                  </div>
                  <div class="muted">
                    <AppIcon
                      name="phone"
                      class="bookando-icon bookando-mr-xxs"
                    />
                    <template v-if="item.phone && String(item.phone).trim()">
                      <a
                        :href="`tel:${normalizePhone(item.phone)}`"
                        class="bookando-link"
                        @click.stop
                      >{{ item.phone }}</a>
                    </template>
                    <template v-else>
                      –
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Chevron oben, Actions unten -->
          <div class="bookando-table-card__right">
            <div
              class="bookando-table-card__chevron"
              aria-hidden="true"
            >
              <AppIcon
                :name="expandedItems.has(item.id) ? 'chevron-up' : 'chevron-down'"
                class="bookando-icon bookando-icon--md"
              />
            </div>
            <div
              class="bookando-table-card__actions"
              @click.stop
            >
              <AppButton
                icon="edit"
                variant="standard"
                size="square"
                btn-type="icononly"
                icon-size="md"
                :tooltip="t('core.common.edit')"
                @click.stop="$emit('edit', item)"
              />
              <AppPopover
                trigger-mode="icon"
                trigger-icon="more-horizontal"
                trigger-variant="standard"
                :offset="2"
                width="content"
                :panel-min-width="220"
                panel-class="qa-menu"
                :close-on-item-click="true"
              >
                <template #content="{ close }">
                  <div
                    class="popover-menu"
                    role="none"
                  >
                    <template
                      v-for="opt in quickOptions"
                      :key="opt.value"
                    >
                      <div
                        class="dropdown-option"
                        role="menuitem"
                        :aria-disabled="opt.disabled ? 'true' : undefined"
                        :class="{ 'bookando-text-muted': opt.disabled }"
                        @click.stop="!opt.disabled && onQuickOption(opt.value, item, close)"
                      >
                        <AppIcon
                          :name="opt.icon"
                          class="dropdown-icon"
                          :class="opt.className"
                        />
                        <span class="option-label">{{ opt.label }}</span>
                      </div>
                      <div
                        v-if="opt.separatorAfter"
                        class="dropdown-separator"
                        aria-hidden="true"
                      />
                    </template>
                  </div>
                </template>
              </AppPopover>
            </div>
          </div>
        </div>

        <!-- Details -->
        <transition name="accordion-bounce">
          <div
            v-if="expandedItems.has(item.id)"
            class="bookando-table-card__details"
            @click.stop
          >
            <div class="bookando-card details-card">
              <div class="bookando-card__header">
                <AppTabs
                  :tabs="tabsDef"
                  :model-value="getDetailTab(item.id)"
                  content-padding="xxs"
                  @update:model-value="val => setDetailTab(item.id, val)"
                />
              </div>
              <div class="bookando-card__body">
                <div class="details-anim">
                  <transition
                    :name="panelTransition(item.id)"
                    mode="out-in"
                    @before-leave="onPanelBeforeLeave"
                    @enter="onPanelEnter"
                    @after-enter="onPanelAfterEnter"
                  >
                    <!-- Info -->
                    <section
                      v-if="getDetailTab(item.id) === 'info'"
                      key="panel-info"
                    >
                      <div class="bookando-grid details-grid-2col">
                        <div class="bookando-table-detail">
                          <div
                            v-for="(field, idx) in dataFields"
                            :key="field.key + '-' + idx"
                            class="detail-row"
                          >
                            <div class="detail-label">
                              {{ field.label }}:
                            </div>
                            <div class="detail-value">
                              <template v-if="field.key === 'country' && item.country">
                                <span class="flag">{{ countryFlag(item.country) }}</span>
                                {{ countryLabel(item.country, locale.value) || item.country }}
                              </template>
                              <template v-else-if="field.key === 'language' && item.language">
                                <span class="flag">{{ languageFlag(item.language) }}</span>
                                {{ languageLabel(item.language, locale.value) }}
                              </template>
                              <template v-else-if="field.key === 'status'">
                                <span :class="['bookando-status-label', statusClass(item.status)]">{{ statusLabel(item.status, locale.value) }}</span>
                              </template>
                              <template v-else-if="field.key === 'email'">
                                <template v-if="item.email && String(item.email).trim()">
                                  <a
                                    :href="`mailto:${item.email}`"
                                    class="bookando-link"
                                  >{{ item.email }}</a>
                                </template>
                                <template v-else>
                                  –
                                </template>
                              </template>
                              <template v-else-if="field.key === 'phone'">
                                <template v-if="item.phone && String(item.phone).trim()">
                                  <a
                                    :href="`tel:${normalizePhone(item.phone)}`"
                                    class="bookando-link"
                                  >{{ item.phone }}</a>
                                </template>
                                <template v-else>
                                  –
                                </template>
                              </template>
                              <template v-else-if="field.key === 'gender'">
                                {{ genderLabel(item.gender, locale.value) || '–' }}
                              </template>
                              <template v-else-if="field.key === 'birthdate'">
                                {{ formatDate(item.birthdate, locale.value) }}
                              </template>
                              <template v-else-if="['created_at','updated_at','deleted_at'].includes(field.key)">
                                {{ formatDatetime(item[field.key], locale.value) }}
                              </template>
                              <template v-else>
                                {{ item[field.key] || '–' }}
                              </template>
                            </div>
                          </div>
                          <div v-if="item.meta">
                            <div
                              v-for="(val, key) in item.meta"
                              :key="key"
                              class="detail-row"
                            >
                              <div class="detail-label">
                                {{ key }}:
                              </div>
                              <div class="detail-value">
                                {{ val }}
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </section>

                    <!-- Activity -->
                    <section
                      v-else
                      key="panel-activity"
                    >
                      <div class="bookando-table-detail">
                        <div class="detail-row">
                          <span class="detail-label">{{ t('mod.customers.stats.total_appointments') }}:</span>
                          <span class="detail-value"><a
                            href="#"
                            class="bookando-link"
                          >{{ item.total_appointments || 0 }}</a></span>
                        </div>
                        <div class="detail-row">
                          <span class="detail-label">{{ t('mod.customers.stats.last_appointment') }}:</span>
                          <span class="detail-value bookando-text-muted">{{ item.last_appointment || '–' }}</span>
                        </div>
                        <div class="detail-row">
                          <span class="detail-label">{{ t('mod.customers.stats.next_appointment') }}:</span>
                          <span class="detail-value bookando-text-muted">–</span>
                        </div>
                      </div>
                    </section>
                  </transition>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <div
      v-if="!items.length"
      class="bookando-table-cards-empty"
    >
      {{ t('ui.table.no_results') }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCheckbox from '@core/Design/components/AppCheckbox.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import { countryFlag, countryLabel, languageFlag, languageLabel, statusLabel, genderLabel, formatDate, formatDatetime } from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'
import { buildCustomerDataFields, initials, normalizePhone, statusClass, avatarSrcFromAny as avatarSrc } from '../../../composables/useCustomerData'

const MODULE = 'customers'
const { t, locale } = useI18n()

const props = defineProps({ items: { type: Array, required: true }, selectedItems: { type: Array, default: () => [] }, containerClass: { type: [String, Array, Object], default: '' } })
const emit = defineEmits(['edit','delete','select','quick'])

const expandedItems = ref(new Set<number>())
function toggleExpand(id: number){ expandedItems.value.has(id) ? expandedItems.value.delete(id) : expandedItems.value.add(id) }

const allSelected = computed(() => (props.items as any[]).length > 0 && (props.items as any[]).every(i => (props.selectedItems as any[]).includes(i.id)))
function toggleAll(val: boolean){ const ids = val ? (props.items as any[]).map(i => i.id) : []; emit('select', ids) }
function toggleItem(id:number, checked:boolean){ const arr = (props.selectedItems as any[]).slice(); const idx = arr.indexOf(id); if (checked && idx === -1) arr.push(id); else if (!checked && idx !== -1) arr.splice(idx,1); emit('select', arr) }

type DetailTab = 'info' | 'activity'
const tabsDef = computed(() => ([
  { label: t('mod.customers.tabs.info'), value: 'info' },
  { label: t('mod.customers.tabs.activity'), value: 'activity' },
]))
const detailTabs = ref<Map<number, DetailTab>>(new Map())
const lastTabs   = ref<Map<number, DetailTab>>(new Map())
function getDetailTab(id:number): DetailTab { return detailTabs.value.get(id) || 'info' }
function setDetailTab(id:number, value:DetailTab){ lastTabs.value.set(id, getDetailTab(id)); detailTabs.value.set(id, value) }
function panelTransition(id:number){ const prev = lastTabs.value.get(id) || 'info'; const next = getDetailTab(id); return prev === 'info' && next === 'activity' ? 'panel-slide-left' : prev === 'activity' && next === 'info' ? 'panel-slide-right' : 'panel-fade' }

/* Height & Bounce helpers */
function getAnimWrapper(el: Element | null): HTMLElement | null {
  return (el && el.parentElement && (el.parentElement as HTMLElement).classList.contains('details-anim'))
    ? (el.parentElement as HTMLElement)
    : (el ? (el.closest('.details-anim') as HTMLElement | null) : null)
}
function getDetailsCard(el: Element | null): HTMLElement | null {
  /* ⬇️ leading space entfernt */
  return el ? (el.closest('.details-card') as HTMLElement | null) : null
}
function onPanelBeforeLeave(el: Element){ const w = getAnimWrapper(el); if (!w) return; const h = w.offsetHeight; w.style.height = `${h}px`; w.setAttribute('data-prev-h', String(h)) }
function onPanelEnter(el: Element){
  const w = getAnimWrapper(el); const card = getDetailsCard(el); if (!w || !card) return
  const prevHAttr = w.getAttribute('data-prev-h'); const prevH = prevHAttr ? Number(prevHAttr) : w.offsetHeight
  if (!w.style.height) w.style.height = `${prevH}px`
  const nextH = (el as HTMLElement).offsetHeight
  card.classList.remove('card-bounce-grow','card-bounce-shrink'); void (card as HTMLElement).offsetWidth
  card.classList.add(nextH >= prevH ? 'card-bounce-grow' : 'card-bounce-shrink')
  requestAnimationFrame(()=>{ w.style.height = `${nextH}px` })
  const onDone = (event: TransitionEvent)=>{ if (event.propertyName !== 'height') return; w.removeEventListener('transitionend', onDone); w.style.height=''; w.removeAttribute('data-prev-h') }
  w.addEventListener('transitionend', onDone)
}
function onPanelAfterEnter(_el: Element){ /* no-op */ }

const dataFields = buildCustomerDataFields((k:string)=>t(k), MODULE)

type QuickKey = 'soft_delete' | 'hard_delete' | 'block' | 'activate' | 'export'
type QuickOption = { value: QuickKey; label: string; icon: string; className?: string; disabled?: boolean; separatorAfter?: boolean }
const quickOptions = computed<QuickOption[]>(() => [
  { value: 'soft_delete', label: t('core.actions.soft_delete.label'), icon: 'user-minus',  className: 'bookando-text-danger' },
  { value: 'hard_delete', label: t('core.actions.hard_delete.label'), icon: 'trash-2',     className: 'bookando-text-danger' },
  { value: 'block',       label: t('core.actions.block.label'),       icon: 'user-x',      className: 'bookando-text-warning' },
  { value: 'activate',    label: t('core.actions.activate.label'),    icon: 'user-check',  className: 'bookando-text-success' },
  { value: 'export',      label: t('core.actions.export.label'),      icon: 'download',    className: 'bookando-text-accent' },
])
function onQuickOption(action: QuickKey, item: any, close?: () => void){ close?.(); (emit as any)('quick', { action, item }) }

function onAvatarError(_: Event, item: any){ try { item.avatar_url = '' } catch {} }
</script>
