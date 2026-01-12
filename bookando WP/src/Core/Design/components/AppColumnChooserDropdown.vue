<!-- AppColumnChooserDropdown.vue -->
<template>
  <div
    ref="chooser"
    class="bookando-column-chooser-dropdown"
  >
    <div class="bookando-chooser-grids">
      <!-- Aktiv -->
      <div class="bookando-chooser-section">
        <div class="bookando-chooser-title">
          {{ t('Angezeigt') }}
        </div>
        <draggable
          v-model="activeColumns"
          group="columns"
          item-key="key"
          :animation="160"
          ghost-class="chooser-drag-ghost"
          chosen-class="chooser-drag-chosen"
          drag-class="chooser-dragging"
        >
          <template #item="{ element }">
            <div class="bookando-chooser-item">
              <span
                class="bookando-drag-handle"
                title="Spalte verschieben"
              >⋮⋮</span>
              {{ element.label }}
              <button
                class="bookando-chooser-hide-btn"
                @click="deactivateColumn(element.key)"
              >
                ×
              </button>
            </div>
          </template>
        </draggable>
      </div>
      <!-- Deaktiv -->
      <div class="bookando-chooser-section">
        <div class="bookando-chooser-title">
          {{ t('Versteckt') }}
        </div>
        <draggable
          v-model="inactiveColumns"
          group="columns"
          item-key="key"
          :animation="160"
          ghost-class="chooser-drag-ghost"
        >
          <template #item="{ element }">
            <div class="bookando-chooser-item bookando-chooser-item--inactive">
              {{ element.label }}
              <button
                class="bookando-chooser-show-btn"
                @click="activateColumn(element.key)"
              >
                +
              </button>
            </div>
          </template>
        </draggable>
      </div>
    </div>
    <div class="bookando-chooser-actions">
      <button
        type="button"
        @click="onOk"
      >
        {{ t('OK') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import draggable from 'vuedraggable'
import { useI18n } from 'vue-i18n'
const { t } = useI18n()


interface Column {
  key: string;
  label: string;
  // ggf. weitere Props wie sortable, visible, etc.
}

const props = defineProps<{
  columns: Column[];
  modelValue: string[];
}>();

const emit = defineEmits(['update:visibleColumns', 'update:order', 'close'])

const localColumns = ref<any[]>([])
const activeColumns = ref<any[]>([])
const inactiveColumns = ref<any[]>([])

watch(
  () => [props.columns, props.visibleColumns],
  () => {
    activeColumns.value = props.columns.filter(col => props.visibleColumns.includes(col.key))
    inactiveColumns.value = props.columns.filter(col => !props.visibleColumns.includes(col.key))
  },
  { immediate: true, deep: true }
)

function activateColumn(key: string) {
  if (!props.visibleColumns.includes(key)) {
    emit('update:visibleColumns', [...props.visibleColumns, key])
  }
}
function deactivateColumn(key: string) {
  emit('update:visibleColumns', props.visibleColumns.filter(k => k !== key))
}
function onOk() {
  // Reihenfolge übergeben
  emit('update:visibleColumns', activeColumns.value.map(col => col.key))
  emit('update:order', activeColumns.value.map(col => col.key))
  emit('close')
}
</script>
