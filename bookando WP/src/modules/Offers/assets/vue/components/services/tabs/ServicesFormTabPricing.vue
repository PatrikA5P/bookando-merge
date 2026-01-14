<!-- ServicesFormTabPricing.vue -->
<template>
  <section
    class="services-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="clock"
      :title="t('mod.services.pricing.section_duration') || 'Dauer & Basispreise'"
      :description="t('mod.services.pricing.section_duration_hint') || 'Lege Dauer und Standardpreise für diese Dienstleistung fest.'"
      layout="stack"
    >
      <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
        <BookandoField
          id="duration_value"
          v-model.number="durationValue"
          type="number"
          min="1"
          step="1"
          :label="t('mod.services.duration') || 'Dauer'"
        />
        <BookandoField
          id="duration_unit"
          v-model="durationUnit"
          type="dropdown"
          :label="t('mod.services.duration_unit') || 'Einheit'"
          :options="durationUnits"
          option-label="label"
          option-value="value"
          mode="basic"
        />
      </div>

      <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
        <BookandoField
          id="price"
          v-model.number="form.price"
          type="number"
          min="0"
          step="0.05"
          :label="t('fields.price') || 'Preis'"
        />
        <BookandoField
          id="sale_price"
          v-model.number="form.sale_price"
          type="number"
          min="0"
          step="0.05"
          :label="t('mod.services.sale_price') || 'Aktionspreis'"
        />
      </div>

      <div class="services-form__toggle-row">
        <BookandoField
          v-model="form.dynamic_pricing_enabled"
          :label="t('mod.services.pricing.dynamic_toggle') || 'Dynamische Preise & Preisaktionen aktivieren'"
          type="toggle"
          :row="true"
        />
        <span class="services-form__hint">
          {{ t('mod.services.pricing.dynamic_toggle_hint') || 'Aktiviere erweiterte Regeln für zeitlich begrenzte oder flexible Preise.' }}
        </span>
      </div>
    </AppServicesFormSection>

    <template v-if="form.dynamic_pricing_enabled">
      <AppServicesFormSection
        icon="sparkles"
        :title="t('mod.services.pricing.promotions') || 'Preisaktionen / Promotions'"
        :description="t('mod.services.pricing.promotions_hint') || 'Lege Aktionspreise mit Start- und Enddatum fest.'"
        layout="stack"
      >
        <div
          v-if="form.price_actions.length"
          class="services-form__stack"
        >
          <div
            v-for="(p, i) in form.price_actions"
            :key="p._localId"
            class="services-form__grid--promo"
          >
            <BookandoField
              :id="`pa_label_${i}`"
              v-model="p.label"
              type="text"
              :label="t('fields.label') || 'Label'"
            />
            <AppDatepicker
              v-model="p.start"
              type="datetime"
              format="yyyy-MM-dd HH:mm"
              model-type="yyyy-MM-dd HH:mm"
              :text-input="true"
              :clearable="true"
              commit-on="blur"
              :auto-apply="true"
            />
            <AppDatepicker
              v-model="p.end"
              type="datetime"
              format="yyyy-MM-dd HH:mm"
              model-type="yyyy-MM-dd HH:mm"
              :text-input="true"
              :clearable="true"
              commit-on="blur"
              :auto-apply="true"
            />
            <BookandoField
              :id="`pa_price_${i}`"
              v-model.number="p.price"
              type="number"
              min="0"
              step="0.05"
              :label="t('fields.price') || 'Preis'"
            />
            <div class="services-form__inline-actions">
              <AppButton
                icon="trash-2"
                btn-type="icononly"
                size="square"
                variant="standard"
                :tooltip="t('core.common.delete')"
                @click="removePromo(i)"
              />
            </div>
          </div>
        </div>
        <p
          v-else
          class="services-form__hint"
        >
          {{ t('mod.services.pricing.promotions_empty') || 'Noch keine Aktionen hinterlegt. Füge deine erste Promotion hinzu.' }}
        </p>
        <div class="services-form__inline-actions">
          <AppButton
            icon="plus"
            variant="primary"
            size="sm"
            @click="addPromo"
          >
            {{ t('core.common.add') }}
          </AppButton>
          <AppButton
            icon="trash-2"
            variant="secondary"
            size="sm"
            :disabled="!form.price_actions.length"
            @click="clearPromos"
          >
            {{ t('mod.services.pricing.clear_promotions') || 'Alle Promotions löschen' }}
          </AppButton>
        </div>
      </AppServicesFormSection>

      <AppServicesFormSection
        icon="sliders"
        :title="t('mod.services.pricing.dynamic') || 'Dynamische Preisregeln'"
        :description="t('mod.services.pricing.dynamic_hint') || 'Steuere Preise nach Wochentag, Datum oder Uhrzeit.'"
        layout="stack"
      >
        <div
          v-if="form.dynamic_rules.length"
          class="services-form__stack"
        >
          <div
            v-for="(r, i) in form.dynamic_rules"
            :key="r._localId"
            class="services-form__grid--rules"
          >
            <BookandoField
              :id="`dr_label_${i}`"
              v-model="r.label"
              type="text"
              :label="t('fields.label')"
              :placeholder="t('mod.services.form.pricing.example_label') || 'z.B. Sa 10–14 Uhr -10%'"
            />
            <BookandoField
              :id="`dr_days_${i}`"
              v-model="r.days"
              type="dropdown"
              multiple
              clearable
              searchable
              :label="t('fields.days') || 'Wochentage'"
              :options="dayOptions"
              option-value="value"
              option-label="label"
            />
            <AppDatepicker
              v-model="r.dateStart"
              type="date"
              format="yyyy-MM-dd"
              model-type="yyyy-MM-dd"
              :clearable="true"
            />
            <AppDatepicker
              v-model="r.dateEnd"
              type="date"
              format="yyyy-MM-dd"
              model-type="yyyy-MM-dd"
              :clearable="true"
            />
            <AppDatepicker
              v-model="r.timeStart"
              type="time"
              format="HH:mm"
              model-type="HH:mm"
              :clearable="true"
            />
            <AppDatepicker
              v-model="r.timeEnd"
              type="time"
              format="HH:mm"
              model-type="HH:mm"
              :clearable="true"
            />
            <div class="services-form__grid services-form__grid--stretch">
              <BookandoField
                :id="`dr_mode_${i}`"
                v-model="r.mode"
                type="dropdown"
                :options="modeOptions"
                option-label="label"
                option-value="value"
                mode="basic"
              />
              <BookandoField
                :id="`dr_amount_${i}`"
                v-model.number="r.amount"
                type="number"
                step="0.05"
                :label="t('fields.amount') || 'Betrag'"
              />
            </div>
            <div class="services-form__inline-actions">
              <AppButton
                icon="trash-2"
                btn-type="icononly"
                size="square"
                variant="standard"
                :tooltip="t('core.common.delete')"
                @click="removeRule(i)"
              />
            </div>
          </div>
        </div>
        <p
          v-else
          class="services-form__hint"
        >
          {{ t('mod.services.pricing.dynamic_empty') || 'Noch keine Regeln angelegt. Nutze dynamische Regeln für besondere Zeiten.' }}
        </p>
        <div class="services-form__inline-actions">
          <AppButton
            icon="plus"
            variant="primary"
            size="sm"
            @click="addRule"
          >
            {{ t('core.common.add') }}
          </AppButton>
          <AppButton
            icon="trash-2"
            variant="secondary"
            size="sm"
            :disabled="!form.dynamic_rules.length"
            @click="clearRules"
          >
            {{ t('mod.services.pricing.clear_rules') || 'Regeln zurücksetzen' }}
          </AppButton>
        </div>
      </AppServicesFormSection>
    </template>

    <AppServicesFormSection
      icon="activity"
      :title="t('mod.services.pricing.capacity_title') || 'Puffer & Kapazitäten'"
      :description="t('mod.services.pricing.capacity_hint') || 'Steuere Vor- und Nachbereitungszeiten sowie Gruppen­größen.'"
      layout="stack"
      compact
    >
      <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
        <BookandoField
          id="buffer_before"
          v-model.number="form.buffer_before_min"
          type="number"
          min="0"
          step="5"
          :label="t('mod.services.buffer_before') || 'Puffer vor Termin (Min)'"
        />
        <BookandoField
          id="buffer_after"
          v-model.number="form.buffer_after_min"
          type="number"
          min="0"
          step="5"
          :label="t('mod.services.buffer_after') || 'Puffer nach Termin (Min)'"
        />
      </div>
      <div class="services-form__grid services-form__grid--two services-form__grid--stretch">
        <BookandoField
          id="cap_min"
          v-model.number="form.capacity_min"
          type="number"
          min="1"
          step="1"
          :label="t('mod.services.capacity_min') || 'Minimale Kapazität'"
        />
        <BookandoField
          id="cap_max"
          v-model.number="form.capacity_max"
          type="number"
          min="1"
          step="1"
          :label="t('mod.services.capacity_max') || 'Maximale Kapazität'"
        />
      </div>
    </AppServicesFormSection>

    <AppServicesFormSection
      icon="layers"
      :title="t('mod.services.variants') || 'Weitere Dauer & Preise'"
      :description="t('mod.services.variants_hint') || 'Optionale Alternativen werden im Buchungsprozess angezeigt.'"
      layout="stack"
    >
      <div
        v-if="form.variants.length"
        class="services-form__stack"
      >
        <div
          v-for="(v, i) in form.variants"
          :key="v._localId || i"
          class="services-form__grid--variants"
        >
          <BookandoField
            :id="`vv_value_${i}`"
            v-model.number="v.value"
            type="number"
            min="1"
            step="1"
            :label="t('mod.services.duration') || 'Dauer'"
          />
          <BookandoField
            :id="`vv_unit_${i}`"
            v-model="v.unit"
            type="dropdown"
            :label="t('mod.services.duration_unit') || 'Einheit'"
            :options="durationUnits"
            option-label="label"
            option-value="value"
            mode="basic"
          />
          <BookandoField
            :id="`vv_price_${i}`"
            v-model.number="v.price"
            type="number"
            min="0"
            step="0.05"
            :label="t('fields.price') || 'Preis'"
          />
          <BookandoField
            :id="`vv_sale_${i}`"
            v-model.number="v.sale_price"
            type="number"
            min="0"
            step="0.05"
            :label="t('mod.services.sale_price') || 'Aktionspreis'"
          />
          <div class="services-form__inline-actions">
            <AppButton
              icon="trash-2"
              btn-type="icononly"
              size="square"
              variant="standard"
              :tooltip="t('core.common.delete')"
              @click="removeVariant(i)"
            />
          </div>
        </div>
      </div>
      <p
        v-else
        class="services-form__hint"
      >
        {{ t('mod.services.variants_empty') || 'Keine Varianten angelegt. Ergänze alternative Dauern oder Preise.' }}
      </p>
      <div class="services-form__inline-actions">
        <AppButton
          icon="plus"
          variant="primary"
          size="sm"
          @click="addVariant"
        >
          {{ t('core.common.add') }}
        </AppButton>
        <AppButton
          icon="trash-2"
          variant="secondary"
          size="sm"
          :disabled="!form.variants.length"
          @click="clearVariants"
        >
          {{ t('mod.services.variants_clear') || 'Varianten löschen' }}
        </AppButton>
      </div>
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppDatepicker from '@core/Design/components/AppDatepicker.vue'
import AppServicesFormSection from '../ui/AppServicesFormSection.vue'
import type { ServiceFormVm } from '../ServicesForm.vue'

type DurationUnit = 'min' | 'h'

type ModeOption = { label: string; value: 'fixed' | 'percent' }

const { t } = useI18n()
const model = defineModel<ServiceFormVm>({ local: false })
const form = computed({
  get: () => model.value!,
  set: value => (model.value = value),
})

defineProps<{
  durationUnits: Array<{ label: string; value: DurationUnit }>
  dayOptions: Array<{ label: string; value: string }>
}>()

const durationUnit = ref<DurationUnit>('min')
const durationValue = ref<number>(60)

watch(() => form.value.duration_min, (minutes) => {
  const m = minutes || 60
  if (m % 60 === 0) {
    durationUnit.value = 'h'
    durationValue.value = m / 60
  } else {
    durationUnit.value = 'min'
    durationValue.value = m
  }
}, { immediate: true })

watch([durationUnit, durationValue], () => {
  form.value.duration_min = durationUnit.value === 'h'
    ? durationValue.value * 60
    : durationValue.value
})

const modeOptions = computed<ModeOption[]>(() => ([
  { label: t('mod.services.pricing.fixed') || 'Fix', value: 'fixed' },
  { label: t('mod.services.pricing.percent') || '%', value: 'percent' },
]))

function addPromo() {
  const id = Math.max(0, ...form.value.price_actions.map(p => p._localId)) + 1
  form.value.price_actions.push({ _localId: id, price: form.value.sale_price || form.value.price })
}

function removePromo(index: number) {
  form.value.price_actions.splice(index, 1)
}

function clearPromos() {
  form.value.price_actions = []
}

function addRule() {
  const id = Math.max(0, ...form.value.dynamic_rules.map(r => r._localId)) + 1
  form.value.dynamic_rules.push({
    _localId: id,
    mode: 'percent',
    amount: -10,
    days: ['sat'] as any,
  })
}

function removeRule(index: number) {
  form.value.dynamic_rules.splice(index, 1)
}

function clearRules() {
  form.value.dynamic_rules = []
}

function addVariant() {
  const id = Math.max(0, ...form.value.variants.map(v => v._localId)) + 1
  form.value.variants.push({ _localId: id, value: 45, unit: 'min', price: form.value.price })
}

function removeVariant(index: number) {
  form.value.variants.splice(index, 1)
}

function clearVariants() {
  form.value.variants = []
}
</script>
