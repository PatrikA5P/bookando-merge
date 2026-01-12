// src/modules/offers/assets/vue/store/store.ts
  import { defineStore } from 'pinia'
  import { ref } from 'vue'
  import type { Offers } from '../models/OffersModel'
  import * as api from '../api/OffersApi'

export const useOffersStore = defineStore('offers', () => {
  const items = ref<Offers[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

    async function load(params: Record<string, any> = {}) {
      loading.value = true; error.value = null
      try {
        items.value = await api.list(params)
      } catch (_e: any) {
        error.value = _e?.message || 'Load failed'
        items.value = []
      } finally {
        loading.value = false
      }
    }
  }

  async function fetchById(id: number | string): Promise<Offers | null> {
    try {
      return await api.getOne(id)
    } catch {
      return null
    }
  }

    async function save(entity: Offers) {
      loading.value = true; error.value = null
      try {
        if (entity.id) await api.update(entity.id, entity)
        else           await api.create(entity)
        await load()
        return true
      } catch (_e: any) {
        error.value = _e?.message || 'Save failed'
        return false
      } finally {
        loading.value = false
      }
      await load()
      return true
    } catch (caughtError: any) {
      error.value = caughtError?.message || 'Save failed'
      return false
    } finally {
      loading.value = false
    }
  }

    async function remove(id: number|string, opts: { hard?: boolean } = {}) {
      loading.value = true; error.value = null
      try {
        await api.remove(id, opts)
        await load()
        return true
      } catch (_e: any) {
        error.value = _e?.message || 'Delete failed'
        return false
      } finally {
        loading.value = false
      }
    }
  }

  return { items, loading, error, load, fetchById, save, remove }
})
  
