  <!-- AppColumnChooserDraggable.vue -->
<template>
  <AppOverlay @close="$emit('close')">
    <template #title>
      {{ t('Spalten auswählen') }}
    </template>
    <template #actions>
      <!-- Breiten optimieren -->
      <AppButton
        icon="stretch"
        variant="standard"
        size="square"
        btn-type="icononly"
        icon-size="md"
        :tooltip="t('Alle Spalten auf optimale Breite setzen')"
        @click="$emit('autosize')"
      />
      <!-- Werkseinstellung: Spalten & Breiten zurücksetzen -->
      <AppButton
        icon="refresh-cw"
        variant="standard"
        size="square"
        btn-type="icononly"
        icon-size="md"
        :tooltip="t('Spalten & Breiten zurücksetzen (Werkseinstellung)')"
        @click="$emit('reset')"
      />
    </template>

    <div class="bookando-column-chooser-draggable">
      <div class="chooser-section">
        <div class="chooser-section-title">
          {{ t('Angezeigt') }}
        </div>
        <draggable
          :list="activeColumns"
          item-key="key"
          :animation="160"
          ghost-class="chooser-drag-ghost"
          chosen-class="chooser-drag-chosen"
          drag-class="is-dragging"
          class="draggable-list"
          @end="onDragEnd"
        >
          <template #item="{ element }">
            <div class="draggable-item">
              <span class="bookando-drag-handle">⋮⋮</span>
              <span>{{ element.label }}</span>
              <button
                class="bookando-chooser-hide-btn"
                type="button"
                :title="t('Verbergen')"
                @click="hideColumn(element.key)"
              >
                ⟶
              </button>
            </div>
          </template>
        </draggable>
      </div>
      <div class="chooser-separator" />
      <div class="chooser-section">
        <div class="chooser-section-title">
          {{ t('Versteckt') }}
        </div>
        <div class="draggable-list">
          <div
            v-for="col in inactiveColumns"
            :key="col.key"
            class="draggable-item"
          >
            <button
              class="bookando-chooser-show-btn"
              type="button"
              :title="t('Anzeigen')"
              @click="showColumn(col.key)"
            >
              ⟵
            </button>
            <span>{{ col.label }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppOverlay>
</template>

  <script setup lang="ts">
  import { ref, watch } from 'vue'
  import draggable from 'vuedraggable'
  import { useI18n } from 'vue-i18n'
  import AppButton from './AppButton.vue'
  import AppOverlay from '@core/Design/components/AppOverlay.vue'
  const { t } = useI18n()

  interface Column {
    key: string;
    label: string;
  }

  const props = defineProps<{
    columns: Column[];
    modelValue: string[];
  }>()

  const emit = defineEmits(['update:modelValue', 'update:order', 'close', 'reset', 'autosize'])

  const activeColumns = ref<Column[]>([])
  const inactiveColumns = ref<Column[]>([])

  watch(
    () => [props.columns, props.modelValue],
    () => {
      if (!Array.isArray(props.columns) || !props.columns.length) {
        activeColumns.value = []
        inactiveColumns.value = []
        return
      }
      // <<<<<<<<<<<<<<<<<<< DAS HIER IST ENTSCHEIDEND >>>>>>>>>>>>>>>>>>
      activeColumns.value = (props.modelValue || [])
        .map(key => props.columns.find(col => col.key === key))
        .filter(Boolean)
      inactiveColumns.value = props.columns.filter(col => !(props.modelValue || []).includes(col.key))
    },
    { immediate: true, deep: true }
  )

  function showColumn(key: string) {
    if (!props.modelValue.includes(key)) {
      emit('update:modelValue', [...props.modelValue, key])
    }
  }
  function hideColumn(key: string) {
    emit('update:modelValue', props.modelValue.filter(k => k !== key))
  }
  function onDragEnd() {
    // Reihenfolge aus dem neuen Array im Draggable
    const newOrder = activeColumns.value.map(col => col.key)
    emit('update:modelValue', [...newOrder]) // neue Referenz!
    emit('update:order', [...newOrder])
  }
  </script>
