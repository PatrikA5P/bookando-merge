/**
 * Standardized Data Loading Pattern for all Bookando Modules
 *
 * USAGE:
 * ```ts
 * import { createDataLoader } from '@core/Design/assets/utils/dataLoader'
 *
 * const { data, loading, error, load, refresh } = createDataLoader(
 *   'customers',      // module slug
 *   getCustomers,     // API function
 *   {
 *     cache: true,    // Enable localStorage caching
 *     cacheTTL: 300,  // Cache for 5 minutes
 *     retry: 2        // Retry failed requests 2 times
 *   }
 * )
 *
 * onMounted(() => load())
 * ```
 */

import { ref, computed } from 'vue'

export interface DataLoaderOptions<T> {
  cache?: boolean
  cacheTTL?: number // seconds
  retry?: number
  transform?: (data: any) => T[]
  onError?: (error: Error) => void
  onSuccess?: (data: T[]) => void
}

export interface DataLoaderResult<T> {
  data: typeof ref<T[]>
  loading: typeof ref<boolean>
  error: typeof ref<string | null>
  load: () => Promise<void>
  refresh: () => Promise<void>
  clear: () => void
}

const CACHE_PREFIX = 'bookando_data_'
const CACHE_TS_PREFIX = 'bookando_data_ts_'

export function createDataLoader<T = any>(
  moduleSlug: string,
  fetchFn: (...args: any[]) => Promise<T[] | { data: T[] }>,
  options: DataLoaderOptions<T> = {}
): DataLoaderResult<T> {
  const {
    cache = false,
    cacheTTL = 300,
    retry = 1,
    transform,
    onError,
    onSuccess
  } = options

  const data = ref<T[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const cacheKey = `${CACHE_PREFIX}${moduleSlug}`
  const cacheTsKey = `${CACHE_TS_PREFIX}${moduleSlug}`

  /**
   * Check if cached data is still valid
   */
  function isCacheValid(): boolean {
    if (!cache) return false
    try {
      const timestamp = localStorage.getItem(cacheTsKey)
      if (!timestamp) return false
      const age = (Date.now() - parseInt(timestamp, 10)) / 1000
      return age < cacheTTL
    } catch {
      return false
    }
  }

  /**
   * Get cached data
   */
  function getCachedData(): T[] | null {
    if (!cache || !isCacheValid()) return null
    try {
      const cached = localStorage.getItem(cacheKey)
      if (!cached) return null
      return JSON.parse(cached) as T[]
    } catch {
      return null
    }
  }

  /**
   * Set cached data
   */
  function setCachedData(items: T[]): void {
    if (!cache) return
    try {
      localStorage.setItem(cacheKey, JSON.stringify(items))
      localStorage.setItem(cacheTsKey, String(Date.now()))
    } catch (e) {
      console.warn(`[DataLoader] Failed to cache data for ${moduleSlug}:`, e)
    }
  }

  /**
   * Clear cached data
   */
  function clear(): void {
    try {
      localStorage.removeItem(cacheKey)
      localStorage.removeItem(cacheTsKey)
    } catch {}
    data.value = []
    error.value = null
  }

  /**
   * Load data with retry logic
   */
  async function loadWithRetry(attempt = 0): Promise<T[]> {
    try {
      const response = await fetchFn()

      // Handle different response formats
      let items: T[]
      if (Array.isArray(response)) {
        items = response
      } else if (response && typeof response === 'object' && 'data' in response) {
        items = (response as { data: T[] }).data
      } else {
        throw new Error('Invalid response format')
      }

      // Apply transform if provided
      if (transform) {
        items = transform(items)
      }

      return items
    } catch (err: any) {
      if (attempt < retry) {
        // Exponential backoff: 200ms, 400ms, 800ms
        const delay = 200 * Math.pow(2, attempt)
        await new Promise(resolve => setTimeout(resolve, delay))
        return loadWithRetry(attempt + 1)
      }
      throw err
    }
  }

  /**
   * Load data (use cache if available)
   */
  async function load(): Promise<void> {
    // Check cache first
    const cached = getCachedData()
    if (cached) {
      data.value = cached
      return
    }

    // Load from API
    loading.value = true
    error.value = null

    try {
      const items = await loadWithRetry()
      data.value = items
      setCachedData(items)

      if (onSuccess) {
        onSuccess(items)
      }
    } catch (err: any) {
      const errorMsg = err?.message || 'Fehler beim Laden der Daten'
      error.value = errorMsg
      data.value = []

      if (onError) {
        onError(err)
      } else {
        console.error(`[DataLoader] Error loading ${moduleSlug}:`, err)
      }
    } finally {
      loading.value = false
    }
  }

  /**
   * Refresh data (bypass cache)
   */
  async function refresh(): Promise<void> {
    clear()
    await load()
  }

  return {
    data,
    loading,
    error,
    load,
    refresh,
    clear
  }
}

/**
 * Create a simple data loader without caching or retry
 */
export function createSimpleLoader<T = any>(
  fetchFn: () => Promise<T[]>
) {
  return createDataLoader<T>('simple', fetchFn, { cache: false, retry: 0 })
}
