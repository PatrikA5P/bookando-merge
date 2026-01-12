import { defineStore } from 'pinia'
import { ref } from 'vue'

import {
  deleteResource,
  fetchState,
  saveResource,
  type ResourceEntry,
  type ResourcesState,
} from '../api/ResourcesApi'

function createEmptyState(): ResourcesState {
  return {
    locations: [],
    rooms: [],
    materials: [],
  }
}

export const useResourcesStore = defineStore('resources', () => {
  const resources = ref<ResourcesState>(createEmptyState())
  const loading = ref(false)
  const error = ref<string | null>(null)
  const saving = ref(false)
  const deletingId = ref<string | null>(null)

  async function loadResources() {
    loading.value = true
    error.value = null

    try {
      const data = await fetchState()
      resources.value = {
        locations: data.locations || [],
        rooms: data.rooms || [],
        materials: data.materials || [],
      }
    } catch (err: any) {
      console.error('[Bookando] Failed to load resources', err)
      error.value = err?.message ?? null
    } finally {
      loading.value = false
    }
  }

  async function persistResource(type: ResourceEntry['type'], payload: Partial<ResourceEntry>) {
    if (saving.value) return null

    saving.value = true
    error.value = null

    try {
      const saved = await saveResource(type, payload)
      const list = resources.value[type]
      const index = list.findIndex(item => item.id === saved.id)

      if (index >= 0) {
        list.splice(index, 1, saved)
      } else {
        list.push(saved)
      }

      return saved
    } catch (err: any) {
      console.error('[Bookando] Failed to save resource', err)
      error.value = err?.message ?? null
      throw err
    } finally {
      saving.value = false
    }
  }

  async function removeResource(type: ResourceEntry['type'], id: string) {
    if (deletingId.value) return false

    deletingId.value = id
    error.value = null

    try {
      const deleted = await deleteResource(type, id)

      if (deleted) {
        resources.value[type] = resources.value[type].filter(item => item.id !== id)
      } else {
        throw new Error('Failed to delete resource')
      }

      return true
    } catch (err: any) {
      console.error('[Bookando] Failed to delete resource', err)
      error.value = err?.message ?? null
      throw err
    } finally {
      deletingId.value = null
    }
  }

  return {
    resources,
    loading,
    error,
    saving,
    deletingId,
    loadResources,
    persistResource,
    removeResource,
  }
})

