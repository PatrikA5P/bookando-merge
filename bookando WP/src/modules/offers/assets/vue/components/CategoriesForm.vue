<!-- CategoriesForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="srv-cat-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <!-- Header -->
      <template #header>
        <h2 id="srv-cat-title">
          {{ form.id ? t('mod.services.category.edit') || 'Kategorie bearbeiten' : t('mod.services.category.add') || 'Kategorie hinzufügen' }}
        </h2>
        <AppButton
          icon="x"
          btn-type="icononly"
          variant="standard"
          size="square"
          icon-size="md"
          @click="onCancel"
        />
      </template>

      <!-- Body -->
      <template #default>
        <form
          :id="formId"
          class="bookando-form"
          novalidate
          autocomplete="off"
          @submit.prevent="onSubmit"
        >
          <BookandoField
            id="name"
            v-model="form.name"
            type="text"
            :label="t('fields.name') || 'Name'"
            required
          />
          <div class="bookando-grid two-columns">
            <BookandoField
              id="slug"
              v-model="form.slug"
              type="text"
              :label="t('fields.slug') || 'Slug'"
              placeholder="sprechende-url"
            />
            <BookandoField
              id="color"
              v-model="form.color"
              type="color"
              :label="t('fields.color') || 'Farbe'"
              clearable
            />
          </div>
          <div class="bookando-grid two-columns">
            <BookandoField
              id="sort"
              v-model.number="form.sort"
              type="number"
              :label="t('fields.sort') || 'Sortierung'"
              min="0"
            />
            <BookandoField
              id="status"
              v-model="form.status"
              type="dropdown"
              :label="t('fields.status') || 'Status'"
              :options="[
                { label: t('core.status.active') || 'Aktiv', value: 'active' },
                { label: t('core.status.hidden') || 'Versteckt', value: 'hidden' }
              ]"
              option-label="label"
              option-value="value"
              mode="basic"
              clearable
            />
          </div>
          <AppRichTextField
            id="description"
            v-model="form.description"
            :label="t('fields.description')"
            :placeholder="t('fields.description')"
            :min-height="180"
          />
        </form>
      </template>

      <!-- Footer -->
      <template #footer>
        <div class="bookando-form-buttons bookando-form-buttons--split">
          <AppButton
            btn-type="textonly"
            variant="secondary"
            size="dynamic"
            type="button"
            @click="onCancel"
          >
            {{ t('core.common.cancel') }}
          </AppButton>
          <AppButton
            btn-type="full"
            variant="primary"
            size="dynamic"
            type="submit"
            :form="formId"
          >
            {{ t('core.common.save') }}
          </AppButton>
        </div>
      </template>
    </AppForm>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppForm from '@core/Design/components/AppForm.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import BookandoField from '@core/Design/components/BookandoField.vue'
import AppRichTextField from '@core/Design/components/AppRichTextField.vue'

type Category = { id:number; name:string; slug?:string; color?:string|null; sort?:number; status?:'active'|'hidden'; description?:string|null }
const { t } = useI18n()
const props = defineProps<{ modelValue: Category | null }>()
const emit = defineEmits<{ (event:'save', value:Category):void; (event:'cancel'):void }>()

const formId = `srv-cat-${Math.random().toString(36).slice(2,8)}`
const empty:Category = { id:0, name:'', slug:'', color:null, sort:0, status:'active', description:null }
const form = ref<Category>({ ...empty })

watch(() => props.modelValue, (value) => { form.value = value ? { ...empty, ...value } : { ...empty } }, { immediate:true })

function onSubmit(){ emit('save', { ...form.value }) }
function onCancel(){ emit('cancel') }
</script>
