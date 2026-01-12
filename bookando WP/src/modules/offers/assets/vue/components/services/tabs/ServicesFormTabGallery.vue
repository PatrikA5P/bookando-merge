<!-- ServicesFormTabGallery.vue -->
<template>
  <section
    class="services-form__panel"
    role="tabpanel"
    tabindex="0"
  >
    <AppServicesFormSection
      icon="image"
      :title="t('mod.services.gallery') || 'Galerie'"
      :description="t('mod.services.gallery_hint') || 'Lade hochwertige Bilder hoch, um Kund*innen einen Eindruck zu vermitteln.'"
      layout="stack"
      compact
    >
      <AppGalleryPicker
        v-model="form.gallery"
        :max="50"
        @reorder="onGalleryReorder"
      />
      <p class="services-form__hint">
        {{ t('mod.services.gallery_hint_secondary') || 'Du kannst bis zu 50 Bilder hinzufügen und per Drag & Drop sortieren.' }}
      </p>
    </AppServicesFormSection>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppGalleryPicker from '@core/Design/components/AppGalleryPicker.vue'
import AppServicesFormSection from '../ui/AppServicesFormSection.vue'
import type { ServiceFormVm } from '../ServicesForm.vue'

const { t } = useI18n()
const model = defineModel<ServiceFormVm>({ local: false })
const form = computed({ get: () => model.value!, set: v => (model.value = v) })
function onGalleryReorder(items: any){ form.value.gallery = items }
</script>
