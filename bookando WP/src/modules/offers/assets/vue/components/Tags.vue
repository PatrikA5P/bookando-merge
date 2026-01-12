<!-- Tags.vue -->
<template>
  <div class="tags-view">
    <!-- Kopf mit Suche + Add -->
    <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
      <div
        class="bookando-grid"
        style="--bookando-grid-cols: 320px 160px; gap:.5rem;"
      >
        <BookandoField
          id="tag_search"
          v-model="search"
          type="text"
          input-icon="search"
          :placeholder="t('ui.search.placeholder') || 'Suchen...'"
        />
        <BookandoField
          id="tag_status"
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
      <div class="bookando-inline-flex bookando-gap-xs">
        <AppButton
          :disabled="selectedIds.length < 2"
          icon="git-merge"
          variant="standard"
          size="dynamic"
          @click="openMerge"
        >
          {{ t('mod.tags.merge') || 'Zusammenführen' }}
        </AppButton>
        <AppButton
          icon="plus"
          variant="primary"
          size="dynamic"
          @click="$emit('create')"
        >
          {{ t('core.common.add') }}
        </AppButton>
      </div>
    </div>

    <!-- Tabelle -->
    <div class="bookando-card">
      <div class="bookando-card-body">
        <div
          class="bookando-grid"
          style="--bookando-grid-cols: 40px 1fr 180px 120px auto; gap:.5rem; align-items:center;"
        >
          <div>
            <input
              type="checkbox"
              :checked="allSelected"
              @change="toggleAll($event)"
            >
          </div>
          <div class="bookando-font-semibold">
            {{ t('fields.name') || 'Name' }}
          </div>
          <div class="bookando-font-semibold">
            {{ t('fields.slug') || 'Slug' }}
          </div>
          <div class="bookando-font-semibold">
            {{ t('ui.table.usage') || 'Nutzung' }}
          </div>
          <div />

          <template
            v-for="tg in filtered"
            :key="tg.id"
          >
            <div>
              <input
                v-model="selectedIds"
                type="checkbox"
                :value="tg.id"
              >
            </div>
            <div class="bookando-ellipsis">
              <span class="bookando-font-medium">#{{ tg.name }}</span>
              <span
                v-if="tg.status==='hidden'"
                class="bookando-text-muted"
              > · {{ t('core.status.hidden') || 'Versteckt' }}</span>
            </div>
            <div class="bookando-text-muted">
              {{ tg.slug || '—' }}
            </div>
            <div>{{ usageMap?.[tg.id] ?? 0 }}</div>
            <div class="bookando-inline-flex bookando-justify-end bookando-gap-xxs">
              <AppButton
                icon="edit"
                variant="standard"
                size="square"
                btn-type="icononly"
                :tooltip="t('core.common.edit')"
                @click="$emit('edit', tg)"
              />
              <AppButton
                icon="trash-2"
                variant="standard"
                size="square"
                btn-type="icononly"
                :tooltip="t('core.common.delete')"
                @click="onDelete(tg)"
              />
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Merge Dialog -->
    <div
      v-if="showMerge"
      class="bookando-dialog-wrapper active"
      role="dialog"
      aria-modal="true"
      aria-labelledby="merge-title"
    >
      <div
        class="bookando-form-overlay active"
        tabindex="-1"
        @click="closeMerge"
      />
      <AppForm>
        <template #header>
          <h2 id="merge-title">
            {{ t('mod.tags.merge') || 'Schlagwörter zusammenführen' }}
          </h2>
          <AppButton
            icon="x"
            btn-type="icononly"
            variant="standard"
            size="square"
            icon-size="md"
            @click="closeMerge"
          />
        </template>
        <template #default>
          <div class="bookando-mb-sm">
            <p class="bookando-text-sm">
              {{ t('mod.tags.merge_hint') || 'Wähle das Ziel-Schlagwort. Alle ausgewählten Schlagwörter werden dorthin migriert.' }}
            </p>
          </div>
          <div
            class="bookando-grid"
            style="--bookando-grid-cols: 1fr; gap:.5rem;"
          >
            <BookandoField
              id="merge_target"
              v-model="mergeTargetId"
              type="dropdown"
              :label="t('mod.tags.merge_target') || 'Ziel-Schlagwort'"
              :options="availableTargets"
              option-label="name"
              option-value="id"
              searchable
              mode="basic"
            />
            <div class="bookando-text-sm">
              {{ t('mod.tags.merge_selected') || 'Ausgewählt' }}: {{ selectedIds.length }}
            </div>
          </div>
        </template>
        <template #footer>
          <div class="bookando-form-buttons bookando-form-buttons--split">
            <AppButton
              btn-type="textonly"
              variant="secondary"
              size="dynamic"
              type="button"
              @click="closeMerge"
            >
              {{ t('core.common.cancel') }}
            </AppButton>
            <AppButton
              :disabled="!mergeTargetId"
              btn-type="full"
              variant="primary"
              size="dynamic"
              type="button"
              @click="confirmMerge"
            >
              {{ t('core.common.save') }}
            </AppButton>
          </div>
        </template>
      </AppForm>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppForm from '@core/Design/components/AppForm.vue'

type Tag = { id:number; name:string; slug?:string; color?:string|null; status?:'active'|'hidden' }
const props = defineProps<{ tags: Tag[], usageMap?: Record<number, number> }>()
const emit = defineEmits<{ (event:'create'):void; (event:'edit', t:Tag):void; (event:'delete', t:Tag):void; (event:'merge', payload:{sourceIds:number[], targetId:number}):void; (event:'refresh'):void }>()
const { t } = useI18n()

const search = ref('')
const statusFilter = ref<string|undefined>()
const selectedIds = ref<number[]>([])

const statusOptions = computed(() => ([ { label: t('core.status.active') || 'Aktiv', value:'active' }, { label: t('core.status.hidden') || 'Versteckt', value:'hidden' } ]))
const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  return [...(props.tags||[])]
    .filter(tg => (!statusFilter.value || tg.status === statusFilter.value))
    .filter(tg => !term || tg.name.toLowerCase().includes(term) || (tg.slug||'').toLowerCase().includes(term))
    .sort((a,b) => (a.name||'').localeCompare(b.name||'', 'de'))
})
const allSelected = computed(() => filtered.value.length > 0 && filtered.value.every(tg => selectedIds.value.includes(tg.id)))
function toggleAll(event:Event){
  const checked = (event.target as HTMLInputElement).checked
  selectedIds.value = checked ? filtered.value.map(tg => tg.id) : []
}

function onDelete(tg:Tag){
  if (confirm(t('ui.confirm.delete') || 'Wirklich löschen?')) emit('delete', tg)
}

/* Merge Dialog */
const showMerge = ref(false)
const mergeTargetId = ref<number|null>(null)
const availableTargets = computed(() => props.tags.filter(t => !selectedIds.value.includes(t.id)))
function openMerge(){ showMerge.value = true; mergeTargetId.value = null }
function closeMerge(){ showMerge.value = false }
function confirmMerge(){
  if (!mergeTargetId.value) return
  emit('merge', { sourceIds: selectedIds.value, targetId: mergeTargetId.value })
  selectedIds.value = []
  closeMerge()
}
</script>
