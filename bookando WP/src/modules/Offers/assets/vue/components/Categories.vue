<template>
  <div class="categories-view">
    <!-- Kopf mit Suche + Add -->
    <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mb-sm">
      <div
        class="bookando-grid"
        style="--bookando-grid-cols: 320px 160px; gap:.5rem;"
      >
        <BookandoField
          id="cat_search"
          v-model="search"
          type="text"
          input-icon="search"
          :placeholder="t('ui.search.placeholder') || 'Suchen...'"
        />
        <BookandoField
          id="cat_status"
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

    <!-- Liste -->
    <div class="bookando-card">
      <div class="bookando-card-body">
        <div
          class="bookando-grid"
          style="--bookando-grid-cols: 1fr 120px 120px 120px auto; gap:.5rem; align-items:center;"
        >
          <div class="bookando-font-semibold">
            {{ t('fields.name') || 'Name' }}
          </div>
          <div class="bookando-font-semibold">
            {{ t('fields.color') || 'Farbe' }}
          </div>
          <div class="bookando-font-semibold">
            {{ t('fields.sort') || 'Sort.' }}
          </div>
          <div class="bookando-font-semibold">
            {{ t('ui.table.usage') || 'Nutzung' }}
          </div>
          <div />

          <template
            v-for="cat in filtered"
            :key="cat.id"
          >
            <div class="bookando-ellipsis">
              <span class="bookando-font-medium">{{ cat.name }}</span>
              <span
                v-if="cat.status==='hidden'"
                class="bookando-text-muted"
              > · {{ t('core.status.hidden') || 'Versteckt' }}</span>
            </div>
            <div>
              <span
                v-if="cat.color"
                class="bookando-color-dot"
                :style="{ background: cat.color }"
              />
              <span
                v-else
                class="bookando-text-muted"
              >—</span>
            </div>
            <div>{{ cat.sort ?? 0 }}</div>
            <div>{{ usageMap?.[cat.id] ?? 0 }}</div>
            <div class="bookando-inline-flex bookando-justify-end bookando-gap-xxs">
              <AppButton
                icon="edit"
                variant="standard"
                size="square"
                btn-type="icononly"
                :tooltip="t('core.common.edit')"
                @click="$emit('edit', cat)"
              />
              <AppButton
                icon="trash-2"
                variant="standard"
                size="square"
                btn-type="icononly"
                :tooltip="t('core.common.delete')"
                @click="onDelete(cat)"
              />
            </div>
          </template>
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

type Category = { id:number; name:string; color?:string|null; sort?:number; status?:'active'|'hidden' }
const props = defineProps<{ categories: Category[], usageMap?: Record<number, number> }>()
const emit = defineEmits<{ (event:'create'):void; (event:'edit', c:Category):void; (event:'delete', c:Category):void; (event:'refresh'):void }>()
const { t } = useI18n()

const search = ref('')
const statusFilter = ref<string|undefined>()
const statusOptions = computed(() => ([
  { label: t('core.status.active') || 'Aktiv', value: 'active' },
  { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' }
]))

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  return [...(props.categories||[])]
    .filter(c => (!statusFilter.value || c.status === statusFilter.value))
    .filter(c => !term || c.name.toLowerCase().includes(term))
    .sort((a,b) => (a.sort||0)-(b.sort||0) || (a.name||'').localeCompare(b.name||'', 'de'))
})

function onDelete(cat:Category){
  if (confirm(t('ui.confirm.delete') || 'Wirklich löschen?')) emit('delete', cat)
}
</script>

<style scoped>
.bookando-color-dot{ width:12px; height:12px; border-radius:999px; display:inline-block; vertical-align:middle }
</style>
