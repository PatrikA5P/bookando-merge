<!-- TagsForm.vue -->
<template>
  <div
    class="bookando-dialog-wrapper active"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tag-title"
  >
    <div
      class="bookando-form-overlay active"
      tabindex="-1"
      @click="onCancel"
    />

    <AppForm>
      <template #header>
        <h2 id="tag-title">
          {{ form.id ? t('mod.tags.edit') || 'Schlagwort bearbeiten' : t('mod.tags.add') || 'Schlagwort hinzufügen' }}
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
              id="status"
              v-model="form.status"
              type="dropdown"
              :label="t('fields.status') || 'Status'"
              :options="[ { label: t('core.status.active') || 'Aktiv', value:'active' }, { label: t('core.status.hidden') || 'Versteckt', value:'hidden' } ]"
              option-label="label"
              option-value="value"
              mode="basic"
              clearable
            />
          </div>
        </form>
      </template>

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

type Tag = { id:number; name:string; slug?:string; color?:string|null; status?:'active'|'hidden' }
const { t } = useI18n()
const props = defineProps<{ modelValue: Tag | null }>()
const emit = defineEmits<{ (event:'save', value:Tag):void; (event:'cancel'):void }>()

const formId = `tag-${Math.random().toString(36).slice(2,8)}`
const empty:Tag = { id:0, name:'', slug:'', color:null, status:'active' }
const form = ref<Tag>({ ...empty })

watch(() => props.modelValue, (value) => { form.value = value ? { ...empty, ...value } : { ...empty } }, { immediate:true })

function onSubmit(){ emit('save', { ...form.value }) }
function onCancel(){ emit('cancel') }
</script>
