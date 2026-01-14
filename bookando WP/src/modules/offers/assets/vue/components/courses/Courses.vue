<template>
  <div class="offers-simple-view">
    <div class="offers-simple-view__toolbar">
      <div class="offers-simple-view__filters">
        <BookandoField
          id="course_search"
          v-model="search"
          type="search"
          :placeholder="t('ui.search.placeholder') || 'Kurse durchsuchen'"
        />
        <BookandoField
          id="course_status"
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
          style="--offers-simple-cols: 2fr 1fr 1fr 1.2fr auto"
        >
          <div class="offers-simple-table__head">
            <span>{{ t('fields.name') || 'Name' }}</span>
            <span>{{ t('mod.services.category.label') || 'Kategorie' }}</span>
            <span>{{ t('fields.status') || 'Status' }}</span>
            <span>{{ t('mod.services.tags') || 'Schlagwörter' }}</span>
            <span />
          </div>

          <template v-if="filtered.length">
            <article
              v-for="course in filtered"
              :key="course.id"
              class="offers-simple-table__row"
            >
              <div>
                <div class="bookando-font-medium">
                  {{ course.name }}
                </div>
                <div
                  v-if="course.description"
                  class="offers-simple-table__meta"
                >
                  <AppIcon name="align-left" />
                  <span>{{ truncate(course.description, 80) }}</span>
                </div>
              </div>
              <div class="offers-simple-table__meta">
                <AppIcon name="folder" />
                <span>{{ categoryName(course.category_id) || '—' }}</span>
              </div>
              <div>
                <span
                  :class="[
                    'bookando-badge',
                    course.status === 'active' ? 'bookando-badge--success' : 'bookando-badge--secondary'
                  ]"
                >
                  {{ course.status === 'active' ? t('core.status.active') || 'Aktiv' : t('core.status.hidden') || 'Versteckt' }}
                </span>
              </div>
              <div class="offers-simple-table__tags">
                <span
                  v-for="tagId in course.tag_ids"
                  :key="tagId"
                  class="offers-simple-tag"
                >
                  {{ tagName(tagId) }}
                </span>
                <span
                  v-if="!course.tag_ids?.length"
                  class="offers-simple-table__meta"
                >
                  {{ t('ui.common.none') || 'Keine' }}
                </span>
              </div>
              <div class="bookando-inline-flex bookando-gap-xxs bookando-justify-end">
                <AppButton
                  icon="edit"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.edit')"
                  @click="$emit('edit', course)"
                />
                <AppButton
                  icon="trash-2"
                  variant="standard"
                  size="square"
                  btn-type="icononly"
                  :tooltip="t('core.common.delete')"
                  @click="$emit('delete', course)"
                />
              </div>
            </article>
          </template>

          <div
            v-else
            class="offers-simple-empty"
          >
            {{ t('mod.offers.courses.empty') || 'Noch keine Kurse angelegt.' }}
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

type Course = {
  id: Id
  name: string
  description?: string | null
  status?: 'active' | 'hidden'
  category_id: Id | null
  tag_ids: Id[]
}

type Category = { id: Id; name: string }
type Tag = { id: Id; name: string }

const props = defineProps<{
  courses: Course[]
  categories: Category[]
  tags: Tag[]
}>()

const emit = defineEmits<{
  (event: 'create'): void
  (event: 'edit', value: Course): void
  (event: 'delete', value: Course): void
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
  return (props.courses || [])
    .filter(course => !statusFilter.value || course.status === statusFilter.value)
    .filter(course => !term || course.name.toLowerCase().includes(term))
    .sort((a, b) => a.name.localeCompare(b.name, 'de'))
})

function categoryName(id: Id | null | undefined) {
  if (!id) return null
  return props.categories.find(cat => cat.id === id)?.name || null
}

function tagName(id: Id) {
  return props.tags.find(tag => tag.id === id)?.name || `#${id}`
}

function truncate(text = '', length = 80) {
  if (text.length <= length) return text
  return `${text.slice(0, length).trim()}…`
}
</script>
