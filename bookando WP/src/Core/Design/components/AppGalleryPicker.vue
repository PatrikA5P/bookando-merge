<!-- AppGalleryPicker.vue -->
<template>
  <div
    class="bookando-grid"
    style="--bookando-grid-cols: repeat(3, 1fr); gap:1rem;"
  >
    <!-- vorhandene Bilder -->
    <div
      v-for="(img, i) in localItems"
      :key="img._localId"
      class="bookando-card"
      draggable="true"
      @dragstart="onDragStart(i, $event)"
      @dragover.prevent
      @drop="onDrop(i)"
    >
      <div class="bookando-card-body">
        <img
          :src="img.url"
          :alt="img.name || ((t('ui.common.image') || 'Bild') + ' ' + (i+1))"
          style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:.5rem;"
        >
        <div class="bookando-flex bookando-justify-between bookando-items-center bookando-mt-xxs">
          <small
            class="bookando-ellipsis"
            :title="img.name"
          >{{ img.name || (t('ui.common.image') || 'Bild') }}</small>
          <AppButton
            icon="trash-2"
            btn-type="icononly"
            size="square"
            variant="standard"
            :tooltip="t('core.common.delete')"
            @click="$emit('remove', i); removeAt(i)"
          />
        </div>
      </div>
    </div>

    <!-- Dropzone / Add -->
    <label
      class="bookando-card bookando-flex bookando-flex-col bookando-items-center bookando-justify-center
             bookando-border-dashed bookando-border bookando-rounded bookando-p-lg bookando-text-center
             bookando-cursor-pointer"
      :class="{ 'bookando-text-muted': localItems.length >= max }"
      @dragover.prevent
      @drop.prevent="onDropFiles($event.dataTransfer?.files)"
      @click="fileInput?.click()"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        multiple
        class="bookando-hide"
        @change="onInputChange"
      >
      <div>
        <AppIcon
          name="plus"
          class="bookando-mb-xs"
        />
        <div>{{ t('mod.services.gallery_add') || 'Bild hinzufügen' }}</div>
      </div>
    </label>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from './AppButton.vue'
import AppIcon from './AppIcon.vue'

export type GalleryItem = { _localId:number; url:string; name?:string; file?:File }

const { t } = useI18n()
const props = withDefaults(defineProps<{
  modelValue: GalleryItem[]
  max?: number
}>(), { max: 50 })

const emit = defineEmits<{
  (event:'update:modelValue', value:GalleryItem[]):void
  (event:'reorder', value:GalleryItem[]):void
  (event:'remove', index:number):void
}>()

const fileInput = ref<HTMLInputElement|null>(null)
const localItems = ref<GalleryItem[]>([])
watch(()=>props.modelValue, v => localItems.value = [...(v||[])], { immediate:true })

let dragIndex = -1
function onDragStart(i:number, ev:DragEvent){ dragIndex = i; ev.dataTransfer?.setData('text/plain', String(i)) }
function onDrop(i:number){
  if (dragIndex < 0 || dragIndex === i) return
  const arr = [...localItems.value]
  const [moved] = arr.splice(dragIndex, 1)
  arr.splice(i, 0, moved)
  dragIndex = -1
  update(arr); emit('reorder', arr)
}

function onInputChange(ev: Event){ onDropFiles((ev.target as HTMLInputElement).files) }
function onDropFiles(files?: FileList | null){
  if (!files || !files.length) return
  const arr = [...localItems.value]
  Array.from(files).forEach((f) => {
    if (arr.length >= props.max) return
    const url = URL.createObjectURL(f)
    arr.push({ _localId: nextId(arr), url, name: f.name, file: f })
  })
  update(arr)
}
function removeAt(i:number){ const arr=[...localItems.value]; arr.splice(i,1); update(arr) }
function update(items:GalleryItem[]){ localItems.value = items; emit('update:modelValue', items) }

function nextId(arr:GalleryItem[]){ return (arr.reduce((m,i)=>Math.max(m, i._localId||0),0) + 1) }
</script>
