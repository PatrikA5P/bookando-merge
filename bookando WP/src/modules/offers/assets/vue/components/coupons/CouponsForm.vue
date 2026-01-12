<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="coupon-form-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <template #header>
        <h2 id="coupon-form-title">
          {{ form.id ? t('mod.offers.coupons.edit') || 'Gutschein bearbeiten' : t('mod.offers.coupons.add') || 'Gutschein anlegen' }}
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

      <template #default>
        <form
          :id="formId"
          class="bookando-form"
          novalidate
          autocomplete="off"
          @submit.prevent="onSubmit"
        >
          <AppServicesFormSection
            icon="percent"
            :title="t('mod.offers.coupons.details') || 'Gutschein-Einstellungen'"
            layout="stack"
            compact
          >
            <BookandoField
              id="coupon_code"
              v-model="form.code"
              type="text"
              :label="t('fields.code') || 'Code'"
              required
            />
            <BookandoField
              id="coupon_status"
              v-model="form.status"
              type="dropdown"
              :label="t('fields.status') || 'Status'"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              mode="basic"
            />
            <BookandoField
              id="coupon_type"
              v-model="form.discount_type"
              type="dropdown"
              :label="t('mod.offers.coupons.discount_type') || 'Rabattart'"
              :options="discountOptions"
              option-label="label"
              option-value="value"
              mode="basic"
            />
            <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
              <BookandoField
                id="coupon_value"
                v-model.number="form.discount_value"
                type="number"
                min="0"
                step="0.5"
                :label="t('mod.offers.coupons.discount_value') || 'Rabattwert'"
              />
              <BookandoField
                id="coupon_min_order"
                v-model.number="form.min_order"
                type="number"
                min="0"
                step="1"
                :label="t('mod.offers.coupons.min_order') || 'Mindestbestellwert'"
              />
            </div>
            <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
              <AppDatepicker
                v-model="form.valid_from"
                type="date"
                format="yyyy-MM-dd"
                model-type="yyyy-MM-dd"
                :clearable="true"
              />
              <AppDatepicker
                v-model="form.valid_until"
                type="date"
                format="yyyy-MM-dd"
                model-type="yyyy-MM-dd"
                :clearable="true"
              />
            </div>
            <BookandoField
              id="coupon_usage_limit"
              v-model.number="form.usage_limit"
              type="number"
              min="0"
              step="1"
              :label="t('mod.offers.coupons.usage_limit') || 'Max. Einlösungen'"
            />
            <AppRichTextField
              id="coupon_description"
              v-model="form.description"
              :label="t('fields.description')"
              :placeholder="t('fields.description')"
              :min-height="180"
            />
          </AppServicesFormSection>
        </form>
      </template>

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
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'
import AppServicesFormSection from '../services/ui/AppServicesFormSection.vue'
import AppDatepicker from '@core/Design/components/AppDatepicker.vue'

type Coupon = {
  id: number
  code: string
  description?: string | null
  discount_type: 'percent' | 'fixed'
  discount_value: number
  min_order?: number | null
  valid_from?: string | null
  valid_until?: string | null
  usage_limit?: number | null
  currency?: string
  status?: 'active' | 'hidden'
}

const { t } = useI18n()

const props = defineProps<{ modelValue: Coupon | null }>()
const emit = defineEmits<{
  (event: 'save', value: Coupon): void
  (event: 'cancel'): void
}>()

const formId = `coupon-${Math.random().toString(36).slice(2, 8)}`
const empty: Coupon = {
  id: 0,
  code: '',
  description: '',
  discount_type: 'percent',
  discount_value: 10,
  min_order: null,
  valid_from: null,
  valid_until: null,
  usage_limit: null,
  currency: 'CHF',
  status: 'active',
}

const form = ref<Coupon>({ ...empty })

watch(() => props.modelValue, value => {
  form.value = value ? { ...empty, ...value } : { ...empty }
}, { immediate: true })

const statusOptions = computed(() => ([
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' },
]))

const discountOptions = computed(() => ([
  { label: t('mod.services.pricing.percent') || '%', value: 'percent' },
  { label: t('mod.services.pricing.fixed') || 'Fix', value: 'fixed' },
]))

function onSubmit() {
  emit('save', { ...form.value })
}

function onCancel() {
  emit('cancel')
}
</script>
