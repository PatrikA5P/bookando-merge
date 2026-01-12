// src/modules/settings/assets/vue/store/store.ts
import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as api from '../api/SettingsApi'

type SectionKey = string
type SubKey = string | undefined

export const useSettingsStore = defineStore('settings', () => {
  // Wir halten Settings je (type, subkey) – flexibel & cache-freundlich
  const data = ref<Record<string, any>>({})
  const loading = ref(false)
  const error = ref<string | null>(null)

  function key(type: SectionKey, subkey?: SubKey) {
    return subkey ? `${type}::${subkey}` : type
  }

  async function load(type: SectionKey, subkey?: SubKey) {
    loading.value = true; error.value = null
    try {
      data.value[key(type, subkey)] = await api.getSettings(type, subkey)
      return data.value[key(type, subkey)]
    } catch (_e: any) {
      error.value = e?.message || 'Fehler beim Laden der Einstellungen.'
      return null
    } finally {
      loading.value = false
    }
  }

  async function save(type: SectionKey, _payload: any, subkey?: SubKey) {
    loading.value = true; error.value = null
    try {
      const res = await api.saveSettings(type, _payload, subkey)
      data.value[key(type, subkey)] = res
      return true
    } catch (_e: any) {
      error.value = e?.message || 'Fehler beim Speichern.'
      return false
    } finally {
      loading.value = false
    }
  }

  // Bequeme Shortcuts für häufige Bereiche:
  async function loadGeneral()  { return load('general') }
  async function saveGeneral(_v: any) { return save('general', _v) }

  async function loadCompany()  { return load('company') }
  async function saveCompany(_v: any) { return save('company', _v) }

  async function loadRole(slug: string)  { return load('roles', slug) }
  async function saveRole(slug: string, _v: any) { return save('roles', _v, slug) }

  return {
    data, loading, error,
    load, save,
    loadGeneral, saveGeneral,
    loadCompany, saveCompany,
    loadRole, saveRole,
  }
})
