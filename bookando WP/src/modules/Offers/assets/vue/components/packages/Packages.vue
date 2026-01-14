<template>
  <div class="offers-simple-view">
    <div class="offers-simple-view__toolbar">
      <div class="offers-simple-view__filters">
        <BookandoField
          id="package_search"
          v-model="search"
          type="search"
          :placeholder="t('ui.search.placeholder') || 'Pakete durchsuchen'"
        />
        <BookandoField
          id="package_status"
          v-model="statusFilter"
          type="dropdown"
          :label="t('fields.status') || 'Status'"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          mode="basic"
          clearable
          hide-label
        />
      </div>
      <AppButton
        icon="plus"
        variant="primary"
        size="dynamic"
        @click="$emit('create')"
      >
        {{ t('core.common.add') }}
      </AppButton>
    </div>

    <div class="bookando-card">
      <div class="bookando-card-body">
        <div
          class="offers-simple-table"
          style="--offers-simple-cols: 2fr 1fr 1fr auto"
        >
          <div class="offers-simple-table__head">
            <span>{{ t('fields.name') || 'Name' }}</span>
            <span>{{ t('mod.offers.packages.includes') || 'Inhalte' }}</span>
            <span>{{ t('fields.price') || 'Preis' }}</span>
            <span />
          </div>

          <template v-if="filtered.length">
            <article
              v-for="pkg in filtered"
              :key="pkg.id"
              class="offers-simple-table__row"
            >
              <div>
                <div class="bookando-font-medium">
                  {{ pkg.name }}
                </div>
                <div class="offers-simple-table__meta">
                  <span
                    :class="[
                      'bookando-badge',
                      pkg.status === 'active' ? 'bookando-badge--success' : 'bookando-badge--secondary'
                    ]"
                  >
                    {{ pkg.status === 'active' ? t('core.status.active') || 'Aktiv' : t('core.status.hidden') || 'Versteckt' }}
                  </span>
                  <AppIcon name="package" />
                  <span>{{ pkg.service_ids.length }} {{ t('mod.offers.packages.items') || 'Leistungen' }}</span>
                </div>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="check-square" />
                <span>
                  <template v-if="pkg.service_ids.length">
                    {{ pkg.service_ids.slice(0, 3).map(serviceName).join(', ') }}
                    <span v-if="pkg.service_ids.length > 3">+{{ pkg.service_ids.length - 3 }}</span>
                  </template>
                  <template v-else>
                    {{ t('ui.common.none') || 'Keine' }}
                  </template>
                </span>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="credit-card" />
                <span>
                  {{ formatPrice(pkg.price, pkg.currency) }}
                  <template v-if="pkg.sale_price">
                    · <strong>{{ formatPrice(pkg.sale_price, pkg.currency) }}</strong>
                  </template>
                </span>
              </div>
              <div class="bookando-inline-flex bookando-gap-xxs bookando-justify-end">
                <AppButton
                  icon="edit"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.edit')"
                  @click="$emit('edit', pkg)"
                />
                <AppButton
                  icon="trash-2"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.delete')"
                  @click="$emit('delete', pkg)"
                />
              </div>
            </article>
          </template>

          <div
            v-else
            class="offers-simple-empty"
          >
            {{ t('mod.offers.packages.empty') || 'Noch keine Pakete angelegt.' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'


type Id = number

type Package = {
  id: Id
  name: string
  service_ids: Id[]
  price: number
  sale_price?: number | null
  currency?: string
  status?: 'active' | 'hidden'
}

type Service = { id: Id; name: string }

const props = defineProps<{
  packages: Package[]
  services: Service[]
}>()

const emit = defineEmits<{
  (event: 'create'): void
  (event: 'edit', value: Package): void
  (event: 'delete', value: Package): void
}>()

const { t } = useI18n()

const search = ref('')
const statusFilter = ref<string | undefined>()

const statusOptions = computed(() => ([
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' },
]))

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  return (props.packages || [])
    .filter(pkg => !statusFilter.value || pkg.status === statusFilter.value)
    .filter(pkg => !term || pkg.name.toLowerCase().includes(term))
    .sort((a, b) => a.name.localeCompare(b.name, 'de'))
})

function serviceName(id: Id) {
  return props.services.find(service => service.id === id)?.name || `#${id}`
}

function formatPrice(value: number, currency = 'CHF') {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(value)
}
</script>
