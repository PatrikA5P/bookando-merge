<template>
  <AppShell>
    <div class="bookando-admin-page bookando-resources-page">
      <AppLicenseOverlay
        v-if="!moduleAllowed"
        :plan="requiredPlan"
      />

      <AppPageLayout v-else>
        <template #header>
          <AppPageHeader :title="t('mod.resources.title')">
            <template
              v-if="canWrite"
              #actions
            >
              <AppButton
                icon="plus"
                variant="primary"
                @click="openResourceForm()"
              >
                {{ addLabel }}
              </AppButton>
            </template>
          </AppPageHeader>
        </template>

        <template #nav>
          <AppTabs
            v-model="currentTab"
            :tabs="tabItems"
            nav-only
          />
        </template>

        <div
          v-if="hasError"
          class="bookando-alert bookando-alert--danger"
        >
          {{ errorMessage }}
        </div>

        <div
          v-if="loading"
          class="bookando-resource-empty"
        >
          {{ t('core.common.loading') || 'Lade...' }}
        </div>

        <template v-else>
          <div
            v-if="currentList.length"
            class="bookando-resources-grid"
          >
            <div
              v-for="entry in currentList"
              :key="entry.id"
              class="bookando-resource-card"
            >
              <div class="bookando-resource-card__header">
                <div>
                  <h3 class="bookando-h5 bookando-m-0">
                    {{ entry.name }}
                  </h3>
                  <p class="bookando-text-sm bookando-text-muted bookando-m-0">
                    {{ entry.description || '–' }}
                  </p>
                </div>
                <div
                  v-if="canWrite"
                  class="bookando-flex bookando-gap-xs"
                >
                  <AppButton
                    size="square"
                    btn-type="icononly"
                    variant="secondary"
                    icon="edit-3"
                    :tooltip="t('core.common.edit')"
                    @click="openResourceForm(entry)"
                  />
                  <AppButton
                    size="square"
                    btn-type="icononly"
                    variant="danger"
                    icon="trash"
                    :tooltip="t('mod.resources.actions.delete')"
                    :loading="deletingId === entry.id"
                    @click="removeResource(entry)"
                  />
                </div>
              </div>

              <div class="bookando-text-sm">
                <strong>{{ t('mod.resources.labels.capacity') }}:</strong>
                {{ entry.capacity ?? '–' }}
              </div>
              <div class="bookando-text-sm">
                <strong>{{ t('mod.resources.labels.tags') }}:</strong>
                {{ entry.tags?.length ? entry.tags.join(', ') : '–' }}
              </div>

              <div class="bookando-resource-availability">
                <strong class="bookando-text-sm">{{ t('mod.resources.labels.availability') }}</strong>
                <ul class="bookando-list bookando-mt-xs">
                  <li
                    v-for="slot in entry.availability"
                    :key="slot.id"
                  >
                    <span>{{ formatSlot(slot) }}</span>
                  </li>
                  <li
                    v-if="!entry.availability.length"
                    class="bookando-text-sm bookando-text-muted"
                  >
                    {{ t('mod.resources.messages.empty') }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div
            v-else
            class="bookando-resource-empty"
          >
            {{ t('mod.resources.messages.empty') }}
          </div>
        </template>

        <transition name="fade">
          <div
            v-if="showModal && canWrite"
            class="bookando-finance-modal"
          >
            <div class="bookando-finance-modal__content">
              <header class="bookando-modal__header">
                <h3 class="bookando-h5 bookando-m-0">
                  {{ modalTitle }}
                </h3>
                <AppButton
                  icon="x"
                  variant="ghost"
                  size="square"
                  btn-type="icononly"
                  icon-size="md"
                  @click="closeModal"
                />
              </header>

              <form
                class="bookando-finance-modal__form"
                @submit.prevent="submitResource"
              >
                <BookandoField
                  id="resource_name"
                  v-model="resourceForm.name"
                  :label="t('mod.resources.labels.name')"
                  required
                />
                <BookandoField
                  id="resource_description"
                  v-model="resourceForm.description"
                  :label="t('mod.resources.labels.description')"
                  type="textarea"
                />
                <BookandoField
                  id="resource_capacity"
                  v-model.number="resourceForm.capacity"
                  :label="t('mod.resources.labels.capacity')"
                  type="number"
                  :min="0"
                />
                <BookandoField
                  id="resource_tags"
                  v-model="tagsInput"
                  :label="t('mod.resources.labels.tags')"
                  help="Kommagetrennte Eingabe"
                />

                <div class="bookando-divider" />
                <div class="bookando-flex bookando-justify-between bookando-items-center">
                  <strong>{{ t('mod.resources.labels.availability') }}</strong>
                  <AppButton
                    icon="plus"
                    variant="ghost"
                    size="square"
                    btn-type="icononly"
                    icon-size="md"
                    :tooltip="t('mod.resources.actions.add_availability')"
                    @click.prevent="addSlot"
                  />
                </div>

                <div class="bookando-flex bookando-flex-col bookando-gap-sm">
                  <div
                    v-for="(slot, index) in resourceForm.availability"
                    :key="slot.id || index"
                    class="bookando-card bookando-p-sm"
                  >
                    <div class="bookando-finance-grid-two">
                      <BookandoField
                        :id="`availability_date_${index}`"
                        v-model="slot.date"
                        type="date"
                        :label="t('mod.resources.labels.date')"
                      />
                      <BookandoField
                        :id="`availability_capacity_${index}`"
                        v-model.number="slot.capacity"
                        type="number"
                        :label="t('mod.resources.labels.capacity')"
                      />
                    </div>
                    <div class="bookando-finance-grid-two">
                      <BookandoField
                        :id="`availability_start_${index}`"
                        v-model="slot.start"
                        type="time"
                        :label="t('mod.resources.labels.start')"
                      />
                      <BookandoField
                        :id="`availability_end_${index}`"
                        v-model="slot.end"
                        type="time"
                        :label="t('mod.resources.labels.end')"
                      />
                    </div>
                    <BookandoField
                      :id="`availability_notes_${index}`"
                      v-model="slot.notes"
                      :label="t('mod.resources.labels.notes')"
                    />
                    <div class="bookando-flex bookando-justify-end">
                      <AppButton
                        variant="ghost"
                        icon="trash"
                        size="square"
                        btn-type="icononly"
                        icon-size="md"
                        @click.prevent="removeSlot(index)"
                      />
                    </div>
                  </div>
                </div>
              </form>

              <div class="bookando-finance-modal__footer">
                <AppButton
                  variant="secondary"
                  @click="closeModal"
                >
                  {{ t('mod.resources.actions.cancel') }}
                </AppButton>
                <AppButton
                  variant="primary"
                  :loading="saving"
                  @click="submitResource"
                >
                  {{ t('mod.resources.actions.save') }}
                </AppButton>
              </div>
            </div>
          </div>
        </transition>
      </AppPageLayout>
    </div>
  </AppShell>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'

import AppShell from '@core/Design/components/AppShell.vue'
import AppLicenseOverlay from '@core/Design/components/AppLicenseOverlay.vue'
import AppPageLayout from '@core/Design/components/AppPageLayout.vue'
import AppPageHeader from '@core/Design/components/AppPageHeader.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import { notify } from '@core/Composables/useNotifier'

import {
  type ResourceEntry,
  type AvailabilitySlot,
} from '../api/ResourcesApi'
import { formatAvailabilitySlot } from '../utils/formatters'
import { useResourcesStore } from '../store/resourcesStore'

const BOOKANDO = (typeof window !== 'undefined' && (window as any).BOOKANDO_VARS) || {}
const moduleAllowed: boolean = BOOKANDO.module_allowed ?? true
const requiredPlan: string | null = typeof BOOKANDO.required_plan === 'string' && BOOKANDO.required_plan !== ''
  ? BOOKANDO.required_plan
  : null
const licenseFeatures: string[] = Array.isArray(BOOKANDO.license_features)
  ? BOOKANDO.license_features.filter((feature: unknown): feature is string => typeof feature === 'string')
  : []

const { t, locale } = useI18n()

const resourcesStore = useResourcesStore()
const { resources, loading, error: storeError, deletingId, saving } = storeToRefs(resourcesStore)

const currentTab = ref<ResourceEntry['type']>('locations')
const tabItems = computed(() => ([
  { label: t('mod.resources.tabs.locations'), value: 'locations' },
  { label: t('mod.resources.tabs.rooms'), value: 'rooms' },
  { label: t('mod.resources.tabs.materials'), value: 'materials' },
]))

const currentList = computed(() => resources.value[currentTab.value] || [])

const showModal = ref(false)
const resourceForm = reactive<ResourceEntry>(defaultResource('locations'))
const tagsInput = ref('')
const activeId = ref<string | null>(null)

const hasError = computed(() => Boolean(storeError.value))
const errorMessage = computed(() => storeError.value || t('mod.resources.messages.error'))

const addLabel = computed(() => {
  switch (currentTab.value) {
    case 'rooms':
      return t('mod.resources.actions.add_room')
    case 'materials':
      return t('mod.resources.actions.add_material')
    default:
      return t('mod.resources.actions.add_location')
  }
})

const canWrite = computed(() => licenseFeatures.includes('rest_api_write'))

const modalTitle = computed(() => {
  if (activeId.value) {
    const editLabel = t('core.common.edit') || 'Bearbeiten'
    return `${editLabel}: ${resourceForm.name || addLabel.value}`
  }
  return addLabel.value
})

onMounted(async () => {
  await resourcesStore.loadResources()
})

function openResourceForm(entry?: ResourceEntry) {
  if (!canWrite.value) return
  const type = entry?.type ?? currentTab.value
  Object.assign(
    resourceForm,
    defaultResource(type),
    entry ? JSON.parse(JSON.stringify(entry)) : {},
  )
  resourceForm.type = type
  tagsInput.value = resourceForm.tags?.join(', ') || ''
  activeId.value = entry?.id || null
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

function defaultResource(type: ResourceEntry['type']): ResourceEntry {
  return {
    id: undefined,
    name: '',
    description: '',
    capacity: null,
    tags: [],
    availability: [],
    type,
  }
}

function addSlot() {
  if (!canWrite.value) return
  resourceForm.availability.push({
    id: undefined,
    date: null,
    start: null,
    end: null,
    capacity: null,
    notes: '',
  })
}

function removeSlot(index: number) {
  if (!canWrite.value) return
  resourceForm.availability.splice(index, 1)
}

async function submitResource() {
  if (!canWrite.value || saving.value) return

  const tags = tagsInput.value
    .split(',')
    .map(tag => tag.trim())
    .filter(Boolean)

  const payload: Partial<ResourceEntry> = {
    ...resourceForm,
    tags,
    type: resourceForm.type,
  }

  try {
    await resourcesStore.persistResource(resourceForm.type, payload)
    notify({ type: 'success', message: t('mod.resources.messages.save_success') })
    showModal.value = false
  } catch (err: any) {
    console.error('[Bookando] Failed to save resource', err)
    notify({ type: 'danger', message: t('mod.resources.messages.error') })
  }
}

async function removeResource(entry: ResourceEntry) {
  if (!canWrite.value || !entry.id || deletingId.value) return
  try {
    const ok = await resourcesStore.removeResource(entry.type, entry.id)
    if (ok) {
      notify({ type: 'success', message: t('mod.resources.messages.delete_success') })
    }
  } catch (err) {
    console.error('[Bookando] Failed to delete resource', err)
    notify({ type: 'danger', message: t('mod.resources.messages.error') })
  }
}

function formatSlot(slot: AvailabilitySlot) {
  return formatAvailabilitySlot(slot, locale.value)
}
</script>
