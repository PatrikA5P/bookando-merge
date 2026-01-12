<!-- SettingsRolesForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="settings-roles-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <!-- HEADER -->
      <template #header>
        <h2 id="settings-roles-title">
          {{ t('mod.settings.roles_form.title') }}
        </h2>
        <AppButton
          icon="x"
          btn-type="icononly"
          variant="standard"
          size="square"
          icon-size="md"
          :aria-label="t('core.common.close')"
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
        <!-- EMPLOYEE -->
        <section
          v-show="tab === 'employee'"
          id="bookando-tabpanel-employee"
          class="tab-content"
          role="tabpanel"
          aria-labelledby="bookando-tab-employee"
          tabindex="0"
        >
          <form
            :id="formId"
            class="bookando-form"
            novalidate
            autocomplete="off"
            @submit.prevent="onSubmit"
          >
            <BookandoField
              v-model="employee.can_configure_services"
              :label="t('mod.settings.roles_form.fields.employee.can_configure_services')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.can_edit_schedule"
              :label="t('mod.settings.roles_form.fields.employee.can_edit_schedule')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.can_edit_days_off"
              :label="t('mod.settings.roles_form.fields.employee.can_edit_days_off')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.can_edit_special_days"
              :label="t('mod.settings.roles_form.fields.employee.can_edit_special_days')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.can_manage_appointments"
              :label="t('mod.settings.roles_form.fields.employee.can_manage_appointments')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.can_manage_events"
              :label="t('mod.settings.roles_form.fields.employee.can_manage_events')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.employee_area_enabled"
              :label="t('mod.settings.roles_form.fields.employee.employee_area_enabled')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.employee_area_url"
              :label="t('mod.settings.roles_form.fields.employee.employee_area_url')"
              type="url"
              :placeholder="'https://bruderwerk.ch/instruktor-hub/'"
            />
            <BookandoField
              v-model="employee.can_manage_badges"
              :label="t('mod.settings.roles_form.fields.employee.can_manage_badges')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="employee.max_appointments"
              :label="t('mod.settings.roles_form.fields.employee.max_appointments')"
              type="number"
              :min="0"
              :placeholder="t('mod.settings.general_form.limits.zero_hint')"
            />
          </form>
        </section>

        <!-- CUSTOMER -->
        <section
          v-show="tab === 'customer'"
          id="bookando-tabpanel-customer"
          class="tab-content"
          role="tabpanel"
          aria-labelledby="bookando-tab-customer"
          tabindex="0"
        >
          <form
            :id="formId"
            class="bookando-form"
            novalidate
            autocomplete="off"
            @submit.prevent="onSubmit"
          >
            <BookandoField
              v-model="customer.check_existing_contact"
              :label="t('mod.settings.roles_form.fields.customer.check_existing_contact')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.auto_create_account"
              :label="t('mod.settings.roles_form.fields.customer.auto_create_account')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.can_reschedule_appointments"
              :label="t('mod.settings.roles_form.fields.customer.can_reschedule_appointments')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.customer_area_enabled"
              :label="t('mod.settings.roles_form.fields.customer.customer_area_enabled')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.customer_area_url"
              :label="t('mod.settings.roles_form.fields.customer.customer_area_url')"
              type="url"
            />
            <BookandoField
              v-model="customer.require_password"
              :label="t('mod.settings.roles_form.fields.customer.require_password')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.can_self_delete"
              :label="t('mod.settings.roles_form.fields.customer.can_self_delete')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.can_cancel_packages"
              :label="t('mod.settings.roles_form.fields.customer.can_cancel_packages')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.enable_no_show_tag"
              :label="t('mod.settings.roles_form.fields.customer.enable_no_show_tag')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="customer.max_appointments"
              :label="t('mod.settings.roles_form.fields.customer.max_appointments')"
              type="number"
              :min="0"
              :placeholder="t('mod.settings.general_form.limits.zero_hint')"
            />
            <BookandoField
              v-model="customer.max_packages"
              :label="t('mod.settings.roles_form.fields.customer.max_packages')"
              type="number"
              :min="0"
              :placeholder="t('mod.settings.general_form.limits.zero_hint')"
            />
            <BookandoField
              v-model="customer.max_events"
              :label="t('mod.settings.roles_form.fields.customer.max_events')"
              type="number"
              :min="0"
              :placeholder="t('mod.settings.general_form.limits.zero_hint')"
            />
          </form>
        </section>

        <!-- ADMIN -->
        <section
          v-show="tab === 'admin'"
          id="bookando-tabpanel-admin"
          class="tab-content"
          role="tabpanel"
          aria-labelledby="bookando-tab-admin"
          tabindex="0"
        >
          <form
            :id="formId"
            class="bookando-form"
            novalidate
            autocomplete="off"
            @submit.prevent="onSubmit"
          >
            <BookandoField
              v-model="admin.can_always_book"
              :label="t('mod.settings.roles_form.fields.admin.can_always_book')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
            <BookandoField
              v-model="admin.book_window_depends_on_service"
              :label="t('mod.settings.roles_form.fields.admin.book_window_depends_on_service')"
              type="toggle"
              :row="true"
              :classes="toggleFieldClasses"
            />
          </form>
        </section>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import { getRoleSettings, saveRoleSettings, BookandoRoleSlugs } from '../api/SettingsApi'

const { t } = useI18n()
const emit = defineEmits(['close'])

const formId = `bookando-form-${Math.random().toString(36).slice(2, 8)}`
const tab = ref<'employee' | 'customer' | 'admin'>('employee')

const tabsDef = computed(() => ([
  { label: t('mod.settings.roles_form.tabs.employee'), value: 'employee' },
  { label: t('mod.settings.roles_form.tabs.customer'), value: 'customer' },
  { label: t('mod.settings.roles_form.tabs.admin'), value: 'admin' }
]))

// Reactive Role Objects (kein .value im Template nötig)
const employee = reactive({
  can_configure_services: false,
  can_edit_schedule: false,
  can_edit_days_off: false,
  can_edit_special_days: false,
  can_manage_appointments: false,
  can_manage_events: false,
  employee_area_enabled: false,
  employee_area_url: '',
  can_manage_badges: false,
  max_appointments: 0
})

const customer = reactive({
  check_existing_contact: true,
  auto_create_account: true,
  can_reschedule_appointments: false,
  customer_area_enabled: false,
  customer_area_url: '',
  require_password: true,
  can_self_delete: false,
  can_cancel_packages: false,
  enable_no_show_tag: false,
  max_appointments: 0,
  max_packages: 0,
  max_events: 0
})

const admin = reactive({
  can_always_book: true,
  book_window_depends_on_service: true
})

const roleMap: Record<string, any> = {
  [BookandoRoleSlugs.EMPLOYEE]: employee,
  [BookandoRoleSlugs.CUSTOMER]: customer,
  [BookandoRoleSlugs.ADMIN]: admin
}

const toggleFieldClasses =
  'bookando-border bookando-rounded bookando-p-md bookando-mb-sm bookando-flex bookando-items-center bookando-justify-content-between'

// Laden & Speichern
onMounted(loadAllRoleSettings)

async function loadAllRoleSettings() {
  await Promise.all(
    Object.entries(roleMap).map(async ([slug, target]) => {
      const data = await getRoleSettings(slug)
      for (const key in target) {
        if (Object.prototype.hasOwnProperty.call(data ?? {}, key)) {
          target[key] = typeof target[key] === 'number' ? Number(data[key]) : data[key]
        }
      }
    })
  )
}

async function onSubmit() {
  await Promise.all(
    Object.entries(roleMap).map(([slug, target]) => saveRoleSettings(slug, { ...target }))
  )
  emit('close')
}

function onCancel() { emit('close') }
</script>
