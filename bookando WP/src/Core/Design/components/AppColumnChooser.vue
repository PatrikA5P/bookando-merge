<!-- AppColumnChooser.vue -->
<template>
  <div
    v-if="show"
    ref="dropdown"
    class="bookando-column-chooser-dropdown bookando-shadow-2 bookando-bg-white bookando-rounded-lg"
  >
    <div
      v-for="(col, idx) in localColumns"
      :key="col.key"
      class="bookando-column-chooser-option"
      draggable="true"
      @dragstart="onDragStart(idx)"
      @dragover.prevent="onDragOver(idx)"
      @drop="onDrop(idx)"
    >
      <span
        class="bookando-drag-handle"
        title="Spalte verschieben"
      >⋮⋮</span>
      <input
        type="checkbox"
        :checked="visibleColumns.includes(col.key)"
        @change="toggleColumn(col.key)"
      >
      <span>{{ col.label }}</span>
    </div>
    <div class="bookando-chooser-actions bookando-text-right bookando-mt-md">
      <button
        class="bookando-btn bookando-btn--primary"
        @click="emit('close')"
      >
        OK
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
const props = defineProps<{
  columns: any[]
  visibleColumns: string[]
  show: boolean
}>()
const emit = defineEmits(['update:visibleColumns', 'update:order', 'close'])

const localColumns = ref([...props.columns])
let dragIdx = -1

watch(() => props.columns, val => { localColumns.value = [...val] })

function toggleColumn(key: string) {
  const current = [...props.visibleColumns]
  const idx = current.indexOf(key)
  if (idx === -1) current.push(key)
  else current.splice(idx, 1)
  emit('update:visibleColumns', current)
}

function onDragStart(idx: number) {
  dragIdx = idx
}
function onDragOver(idx: number) {
  // für hover-Highlight falls gewünscht
}
function onDrop(idx: number) {
  if (dragIdx === -1 || dragIdx === idx) return
  const cols = [...localColumns.value]
  const [removed] = cols.splice(dragIdx, 1)
  cols.splice(idx, 0, removed)
  localColumns.value = cols
  dragIdx = -1
  emit('update:order', cols.map(col => col.key))
}
</script>
