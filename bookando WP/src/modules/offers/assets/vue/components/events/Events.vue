<template>
  <div class="offers-simple-view">
    <div class="offers-simple-view__toolbar">
      <div class="offers-simple-view__filters">
        <BookandoField
          id="event_search"
          v-model="search"
          type="search"
          :placeholder="t('ui.search.placeholder') || 'Events durchsuchen'"
        />
        <BookandoField
          id="event_status"
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
          style="--offers-simple-cols: 2fr 1.5fr 1fr 0.8fr auto"
        >
          <div class="offers-simple-table__head">
            <span>{{ t('fields.title') || 'Titel' }}</span>
            <span>{{ t('fields.period') || 'Zeitraum' }}</span>
            <span>{{ t('fields.location') || 'Ort' }}</span>
            <span>{{ t('mod.services.capacity_max') || 'Kapazität' }}</span>
            <span />
          </div>

          <template v-if="filtered.length">
            <article
              v-for="event in filtered"
              :key="event.id"
              class="offers-simple-table__row"
            >
              <div>
                <div class="bookando-font-medium">
                  {{ event.title }}
                </div>
                <div class="offers-simple-table__meta">
                  <span
                    :class="[
                      'bookando-badge',
                      event.status === 'active' ? 'bookando-badge--success' : 'bookando-badge--secondary'
                    ]"
                  >
                    {{ event.status === 'active' ? t('core.status.active') || 'Aktiv' : t('core.status.hidden') || 'Versteckt' }}
                  </span>
                  <AppIcon name="folder" />
                  <span>{{ categoryName(event.category_id) || t('ui.common.none') || 'Keine' }}</span>
                </div>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="calendar" />
                <span>{{ formatRange(event.start_at, event.end_at) }}</span>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="map-pin" />
                <span>{{ event.location || '—' }}</span>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="users" />
                <span>{{ event.capacity || '—' }}</span>
              </div>
              <div class="bookando-inline-flex bookando-gap-xxs bookando-justify-end">
                <AppButton
                  icon="edit"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.edit')"
                  @click="$emit('edit', event)"
                />
                <AppButton
                  icon="trash-2"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.delete')"
                  @click="$emit('delete', event)"
                />
              </div>
            </article>
          </template>

          <div
            v-else
            class="offers-simple-empty"
          >
            {{ t('mod.offers.events.empty') || 'Noch keine Events angelegt.' }}
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

type Event = {
  id: Id
  title: string
  start_at: string
  end_at: string
  location?: string | null
  capacity?: number | null
  status?: 'active' | 'hidden'
  category_id: Id | null
}

type Category = { id: Id; name: string }

const props = defineProps<{
  events: Event[]
  categories: Category[]
}>()

const emit = defineEmits<{
  (event: 'create'): void
  (event: 'edit', value: Event): void
  (event: 'delete', value: Event): void
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
  return (props.events || [])
    .filter(evt => !statusFilter.value || evt.status === statusFilter.value)
    .filter(evt => !term || evt.title.toLowerCase().includes(term))
    .sort((a, b) => new Date(a.start_at).getTime() - new Date(b.start_at).getTime())
})

function categoryName(id: Id | null | undefined) {
  if (!id) return null
  return props.categories.find(cat => cat.id === id)?.name || null
}

function formatRange(start: string, end: string) {
  const startDate = new Date(start)
  const endDate = new Date(end)
  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
    return start || '—'
  }
  const options: Intl.DateTimeFormatOptions = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }
  return `${startDate.toLocaleString(undefined, options)} – ${endDate.toLocaleString(undefined, options)}`
}
</script>
