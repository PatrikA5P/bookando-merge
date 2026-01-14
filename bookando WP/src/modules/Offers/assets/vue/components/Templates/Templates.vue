<template>
  <div class="offers-simple-view">
    <div class="offers-simple-view__toolbar">
      <div class="offers-simple-view__filters">
        <BookandoField
          id="templates_search"
          v-model="search"
          type="search"
          :placeholder="t('ui.search.placeholder') || 'Vorlagen durchsuchen'"
        />
        <BookandoField
          id="templates_type"
          v-model="typeFilter"
          type="dropdown"
          :label="t('fields.type') || 'Typ'"
          :options="typeOptions"
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
          style="--offers-simple-cols: 2fr 1fr 1fr 1.2fr auto"
        >
          <div class="offers-simple-table__head">
            <span>{{ t('fields.name') || 'Name' }}</span>
            <span>{{ t('fields.type') || 'Typ' }}</span>
            <span>{{ t('mod.services.category.label') || 'Kategorie' }}</span>
            <span>{{ t('ui.common.updated_at') || 'Zuletzt aktualisiert' }}</span>
            <span />
          </div>

          <template v-if="filtered.length">
            <article
              v-for="template in filtered"
              :key="template.id"
              class="offers-simple-table__row"
            >
              <div>
                <div class="bookando-font-medium">
                  {{ template.name }}
                </div>
                <div
                  v-if="template.description"
                  class="offers-simple-table__meta"
                >
                  <AppIcon name="align-left" />
                  <span>{{ truncate(template.description, 90) }}</span>
                </div>
                <div
                  v-if="template.defaults"
                  class="offers-simple-table__meta"
                >
                  <AppIcon name="sliders" />
                  <span>{{ defaultsLabel(template.defaults) }}</span>
                </div>
              </div>
              <div>
                <span
                  :class="[
                    'bookando-badge',
                    template.type === 'course' ? 'bookando-badge--info' : 'bookando-badge--secondary'
                  ]"
                >
                  {{ typeLabel(template.type) }}
                </span>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="folder" />
                <span>{{ categoryName(template.category_id) || '—' }}</span>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="clock" />
                <span>{{ formatUpdated(template.updated_at) }}</span>
              </div>
              <div class="bookando-inline-flex bookando-gap-xxs bookando-justify-end">
                <AppButton
                  icon="edit"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.edit')"
                  @click="$emit('edit', template)"
                />
                <AppButton
                  icon="trash-2"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.delete')"
                  @click="$emit('delete', template)"
                />
              </div>
            </article>
          </template>

          <div
            v-else
            class="offers-simple-empty"
          >
            {{ t('mod.offers.templates.empty') || 'Noch keine Vorlagen angelegt.' }}
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

type TemplateDefaults = {
  price?: number | null
  duration_min?: number | null
  capacity?: number | null
}

type Template = {
  id: Id
  name: string
  type: 'course' | 'service'
  category_id?: Id | null
  tag_ids?: Id[]
  description?: string | null
  defaults?: TemplateDefaults
  updated_at?: string | null
  status?: 'active' | 'archived'
}

type Category = { id: Id; name: string }

type Props = {
  templates: Template[]
  categories: Category[]
  tags: { id: Id; name: string }[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (event: 'create'): void
  (event: 'edit', value: Template): void
  (event: 'delete', value: Template): void
}>()

const { t } = useI18n()

const search = ref('')
const typeFilter = ref<'course' | 'service' | undefined>()

const typeOptions = computed(() => ([
  { label: t('mod.offers.templates.type_course') || 'Kurse', value: 'course' },
  { label: t('mod.offers.templates.type_service') || 'Dienstleistungen', value: 'service' },
]))

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  return (props.templates || [])
    .filter(template => !typeFilter.value || template.type === typeFilter.value)
    .filter(template => !term || template.name.toLowerCase().includes(term) || (template.description || '').toLowerCase().includes(term))
    .sort((a, b) => a.name.localeCompare(b.name, 'de'))
})

function categoryName(id?: Id | null) {
  if (!id) return null
  return props.categories.find(cat => cat.id === id)?.name || null
}

function formatUpdated(value?: string | null) {
  if (!value) return t('ui.common.not_available') || 'n/a'
  try {
    return new Intl.DateTimeFormat('de-CH', {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(value))
  } catch (e) {
    return value
  }
}

function defaultsLabel(defaults?: TemplateDefaults) {
  if (!defaults) return t('ui.common.not_available') || 'n/a'
  const parts: string[] = []
  if (defaults.price != null) parts.push(`${defaults.price.toLocaleString('de-CH', { style: 'currency', currency: 'CHF' })}`)
  if (defaults.duration_min != null) parts.push(`${defaults.duration_min} min`)
  if (defaults.capacity != null) parts.push(`${defaults.capacity} TN`)
  return parts.length ? parts.join(' · ') : t('ui.common.not_available') || 'n/a'
}

function typeLabel(type: Template['type']) {
  return type === 'course'
    ? (t('mod.offers.templates.type_course_single') || 'Kurs')
    : (t('mod.offers.templates.type_service_single') || 'Dienstleistung')
}

function truncate(text = '', length = 90) {
  if (text.length <= length) return text
  return `${text.slice(0, length).trim()}…`
}
</script>
