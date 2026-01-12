<!-- CustomersForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="dialog-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <!-- Hard-Delete Confirm -->
    <AppModal
      :show="confirmHardDelete"
      module="customers"
      action="hard_delete"
      type="danger"
      :close-on-backdrop="false"
      :close-on-esc="true"
      @confirm="doHardDelete"
      @cancel="confirmHardDelete = false"
    />

    <AppForm>
      <template #header>
        <h2 id="dialog-title">
          {{ form.id ? t('mod.customers.actions.edit') : t('mod.customers.actions.add') }}
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

      <template #tabs>
        <AppTabs
          v-model="tab"
          :tabs="tabsDef"
          nav-only
        />
      </template>

      <template #default>
        <div v-if="form">
          <div class="bookando-flex bookando-flex-col bookando-items-center bookando-justify-center bookando-gap-sm bookando-width-full bookando-my-md">
            <AppAvatar
              :src="form.avatar_url"
              :initials="initials(form)"
              size="xl"
              :can-upload="true"
              :can-remove="!!form.avatar_url"
              @upload="openAvatarDialog"
              @remove="removeAvatar"
            />

            <span
              class="bookando-mt-xxs"
              :class="['bookando-status-label', statusClass(form.status)]"
            ><span class="status-label-text">{{ statusLabelForForm(form.status, form.deleted_at) }}</span></span>

            <input
              ref="avatarInput"
              type="file"
              accept="image/*"
              class="bookando-hide"
              @change="onAvatarFileSelect"
            >
            <span
              v-if="avatarError"
              class="bookando-alert bookando-alert--danger bookando-text-center"
            >{{ avatarError }}</span>
          </div>

          <div
            v-if="error"
            class="bookando-alert bookando-alert--danger"
            role="alert"
            aria-live="assertive"
          >
            {{ error }}
          </div>

          <section
            v-show="tab === 'info'"
            id="bookando-tabpanel-info"
            class="tab-content"
            role="tabpanel"
            aria-labelledby="bookando-tab-info"
            tabindex="0"
          >
            <form
              :id="formId"
              class="bookando-form"
              novalidate
              autocomplete="off"
              aria-describedby="form-error"
              @submit.prevent="onSubmit"
            >
              <div class="bookando-grid two-columns">
                <BookandoField
                  id="first_name"
                  v-model="form.first_name"
                  type="text"
                  :label="fieldLabel(t, 'first_name', MODULE)"
                  required
                />
                <BookandoField
                  id="last_name"
                  v-model="form.last_name"
                  type="text"
                  :label="fieldLabel(t, 'last_name', MODULE)"
                  required
                />
              </div>

              <BookandoField
                id="email"
                v-model="form.email"
                type="email"
                :label="fieldLabel(t, 'email', MODULE)"
                required
              />
              <BookandoField
                id="phone"
                v-model="form.phone"
                type="phone"
                :label="fieldLabel(t, 'phone', MODULE)"
                source="countries"
              />
              <BookandoField
                id="address"
                v-model="form.address"
                type="text"
                :label="fieldLabel(t, 'address', MODULE)"
              />
              <BookandoField
                id="address_2"
                v-model="form.address_2"
                type="text"
                :label="fieldLabel(t, 'address_2', MODULE)"
              />

              <div
                class="bookando-grid"
                style="--bookando-grid-cols: 1fr 2fr"
              >
                <BookandoField
                  id="zip"
                  v-model="form.zip"
                  type="text"
                  :label="fieldLabel(t, 'zip', MODULE)"
                />
                <BookandoField
                  id="city"
                  v-model="form.city"
                  type="text"
                  :label="fieldLabel(t, 'city', MODULE)"
                />
              </div>

              <BookandoField
                id="country"
                v-model="form.country"
                type="dropdown"
                source="countries"
                :searchable="true"
                :clearable="true"
                :label="fieldLabel(t, 'country', MODULE)"
                option-label="label"
                option-value="code"
                show-flag
                mode="flag-label"
              />

              <div class="bookando-grid two-columns">
                <BookandoField
                  id="gender"
                  v-model="form.gender"
                  type="dropdown"
                  searchable
                  source="genders"
                  :label="fieldLabel(t, 'gender', MODULE)"
                  option-label="label"
                  option-value="value"
                  mode="basic"
                  clearable
                />
                <BookandoField
                  id="birthdate"
                  v-model="form.birthdate"
                  type="date"
                  :label="fieldLabel(t, 'birthdate', MODULE)"
                  :placeholder="t('ui.date.select')"
                  :format="'dd.MM.yyyy'"
                  :clearable="true"
                  :auto-apply="true"
                  :text-input="true"
                  :commit-on="'blur'"
                  :auto-correct="true"
                  :model-type="'yyyy-MM-dd'"
                  :min-date="'1920-01-01'"
                  :max-date="'2099-12-31'"
                  input-icon="calendar"
                />
              </div>

              <BookandoField
                id="language"
                v-model="form.language"
                type="dropdown"
                source="languages"
                :searchable="true"
                :clearable="true"
                show-flag
                :label="fieldLabel(t, 'language', MODULE)"
                mode="flag-label"
                required
              />

              <BookandoField
                id="description"
                v-model="form.description"
                type="textarea"
                :label="fieldLabel(t, 'description', MODULE)"
              />

              <BookandoField
                id="note"
                v-model="form.note"
                type="textarea"
                :label="fieldLabel(t, 'note', MODULE)"
              />

              <BookandoField
                id="timezone"
                v-model="form.timezone"
                type="dropdown"
                source="timezones"
                :searchable="true"
                :clearable="true"
                :label="fieldLabel(t, 'timezone', MODULE)"
              />

              <BookandoField
                id="external_id"
                v-model="form.external_id"
                type="text"
                :label="fieldLabel(t, 'external_id', MODULE)"
                :helper="t('mod.customers.fields.external_id.helper')"
              />
            </form>
          </section>

          <section
            v-show="tab === 'activity'"
            id="bookando-tabpanel-activity"
            class="tab-content"
            role="tabpanel"
            aria-labelledby="bookando-tab-activity"
            tabindex="0"
          >
            <strong>{{ t('mod.customers.stats.total_appointments') }}:</strong> {{ form.total_appointments || 0 }}<br>
            <strong>{{ t('mod.customers.stats.last_appointment') }}:</strong> {{ form.last_appointment || '–' }}
          </section>
        </div>
      </template>

      <template #footer>
        <div class="bookando-form-buttons bookando-form-buttons--split">
          <div class="bookando-inline-flex bookando-items-center bookando-gap-sm">
            <AppButton
              btn-type="textonly"
              variant="secondary"
              size="dynamic"
              type="button"
              @click="onCancel"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
            <AppPopover
              trigger-mode="icon"
              trigger-icon="more-horizontal"
              trigger-variant="standard"
              :offset="2"
              width="content"
              :panel-min-width="240"
              :close-on-item-click="true"
            >
              <template #content="{ close }">
                <div
                  class="popover-menu"
                  role="menu"
                >
                  <div
                    v-for="opt in quickOptions"
                    :key="opt.value"
                    class="dropdown-option"
                    role="menuitem"
                    :aria-disabled="opt.disabled ? 'true' : undefined"
                    :class="{ 'bookando-text-muted': opt.disabled }"
                    @click.stop="!opt.disabled && onQuickAction(opt.value, close)"
                  >
                    <AppIcon
                      :name="opt.icon"
                      class="dropdown-icon"
                      :class="opt.className"
                    />
                    <span class="option-label">{{ opt.label }}</span>
                  </div>
                </div>
              </template>
            </AppPopover>
          </div>
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
import { onMounted, onUnmounted, ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Customer } from '../models/CustomersModel'
import httpBase from '@assets/http'
import { deleteCustomer as apiDeleteCustomer } from '../api/CustomersApi'
import AppForm from '@core/Design/components/AppForm.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import AppModal from '@core/Design/components/AppModal.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import { notify } from '@core/Composables/useNotifier'
import { statusLabel } from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'
import { initials, statusClass } from '../../../composables/useCustomerData'

const http = httpBase.module('customers')
const MODULE = 'customers'
const { t, locale } = useI18n()

const props = defineProps<{ modelValue: Customer | null }>()
const emit = defineEmits<{ (event: 'save', value: Customer): void; (event: 'cancel'): void }>()

/* Tabs */
const tabsDef = computed(() => ([
  { label: t('mod.customers.tabs.info'), value: 'info' },
  { label: t('mod.customers.tabs.activity'), value: 'activity' },
]))
const tab = ref<'info' | 'activity'>('info')

/* Form state */
const error = ref('')
const avatarError = ref('')
const formId = `bookando-form-${Math.random().toString(36).substring(2, 8)}`

const emptyCustomer: Customer = {
  id: undefined,
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  address: '',
  address_2: '',
  zip: '',
  city: '',
  country: null as any,
  gender: '',
  birthdate: '',
  language: 'de',
  note: '',
  description: '',
  avatar_url: '',
  timezone: '',
  external_id: '',
  deleted_at: null,
  status: 'active',
  total_appointments: 0,
  last_appointment: ''
}
const form = ref<Customer>({ ...emptyCustomer })
const avatarInput = ref<HTMLInputElement | null>(null)

/* Hard-Delete-Confirm */
const pendingHardDelete = ref(false)
const confirmHardDelete = ref(false)

/* Sync modelValue */
watch(() => props.modelValue, (value) => {
  form.value = value ? { ...emptyCustomer, ...value } : { ...emptyCustomer }
  pendingHardDelete.value = false
}, { immediate: true })

/* Body scroll lock */
onMounted(() => {
  const scrollY = window.scrollY
  document.body.style.top = `-${scrollY}px`
  document.body.style.position = 'fixed'
  document.body.style.width = '100%'
  document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
  const scrollY = Math.abs(parseInt(document.body.style.top || '0', 10))
  document.body.style.position = ''
  document.body.style.top = ''
  document.body.style.overflow = ''
  document.body.style.width = ''
  window.scrollTo(0, scrollY)
})

/* Avatar */
function openAvatarDialog(){ avatarError.value = ''; avatarInput.value?.click() }
function onAvatarFileSelect(event: Event){
  avatarError.value = ''
  const input = event.target as HTMLInputElement
  if (!input.files || !input.files[0]) return
  if (!form.value.id || !Number.isInteger(Number(form.value.id))){ const msg = t('ui.upload.saveFirst'); avatarError.value = msg; notify('danger', msg); input.value=''; return }
  const file = input.files[0]
  if (!file.type.startsWith('image/')){ const msg = t('ui.upload.only_images'); avatarError.value = msg; notify('danger', msg); return }
  if (file.size > 5 * 1024 * 1024){ const msg = t('ui.upload.too_large_5mb'); avatarError.value = msg; notify('danger', msg); return }
  uploadAvatar(file); input.value = ''
}
async function uploadAvatar(file: File){
  if (!form.value.id){ const msg = t('ui.upload.saveFirst'); avatarError.value = msg; notify('danger', msg); return }
  try{
    const fd = new FormData(); fd.append('avatar', file)
    const { data } = await http.post<any>(`bookando/v1/users/${Number(form.value.id)}/avatar`, fd, { absolute: true })
    form.value.avatar_url = data?.avatar_url || ''
    avatarError.value = ''
  } catch(_e:any){ const msg = _e?.message || t('ui.upload.avatar_upload_error'); avatarError.value = msg; notify('danger', msg) }
}
async function removeAvatar(){
  if (!form.value.id) return
  try{
    await http.del<any>(`bookando/v1/users/${form.value.id}/avatar`, undefined, { absolute: true })
    form.value.avatar_url = ''; avatarError.value = ''
  } catch(_e:any){ const msg = _e?.message || t('ui.upload.avatar_remove_error'); avatarError.value = msg; notify('danger', msg) }
}

/* Status-Anzeige */
function statusLabelForForm(status: string, deletedAt: string | null){ return (status === 'deleted' && !deletedAt) ? t('core.status.marked_for_deletion') : statusLabel(status, locale.value) }

/* Actions */
type QuickKey = 'soft_delete' | 'hard_delete' | 'block' | 'activate'
type QuickOption = { value: QuickKey; label: string; icon: string; className?: string; disabled?: boolean }
const quickOptions = computed<QuickOption[]>(() => { const s = form.value.status; const isMarked = s === 'deleted' && !form.value.deleted_at; return [ { value: 'block', label: t('core.actions.block.label'), icon: 'user-x', className: 'bookando-text-warning', disabled: s === 'blocked' }, { value: 'activate', label: t('core.actions.activate.label'), icon: 'user-check', className: 'bookando-text-success', disabled: s === 'active' }, { value: 'soft_delete', label: t('core.actions.soft_delete.label'), icon: 'user-minus', className: 'bookando-text-danger', disabled: isMarked }, { value: 'hard_delete', label: t('core.actions.hard_delete.label'), icon: 'trash-2', className: 'bookando-text-danger' } ] })
function onQuickAction(action: QuickKey, close?: () => void){ close?.(); switch(action){ case 'block': form.value.status='blocked'; pendingHardDelete.value=false; break; case 'activate': form.value.status='active'; form.value.deleted_at=null; pendingHardDelete.value=false; break; case 'soft_delete': form.value.status='deleted'; form.value.deleted_at=null; pendingHardDelete.value=false; break; case 'hard_delete': form.value.status='deleted'; form.value.deleted_at=null; pendingHardDelete.value=true; break } }

/* Validate + Submit */
function validate(): boolean { error.value=''; if (!form.value.first_name || !form.value.last_name){ error.value = t('mod.customers.validation.name_required'); return false } if (!form.value.email || !/.+@.+\..+/.test(form.value.email)){ error.value = t('mod.customers.validation.email_invalid'); return false } return true }
function onSubmit(){ if (!validate()) return; if (pendingHardDelete.value){ confirmHardDelete.value = true; return } emit('save', { ...form.value }) }
function onCancel(){ emit('cancel') }

/* Endgültig löschen */
async function doHardDelete(){ confirmHardDelete.value=false; try{ if (!form.value.id) throw new Error('No ID'); await apiDeleteCustomer(Number(form.value.id), { hard: true }); notify('success', t('mod.customers.messages.delete_success')); emit('cancel') } catch(_e:any){ const msg = _e?.message || t('mod.customers.messages.delete_error'); notify('danger', msg) } }
</script>
