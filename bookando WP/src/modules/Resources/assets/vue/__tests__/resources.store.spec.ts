import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('../api/ResourcesApi', () => ({
  fetchState: vi.fn(),
  saveResource: vi.fn(),
  deleteResource: vi.fn(),
}))

import { fetchState, saveResource, deleteResource } from '../api/ResourcesApi'
import { useResourcesStore } from '../store/resourcesStore'

const sampleEntry = {
  id: '1',
  name: 'Raum A',
  description: 'Besprechungsraum',
  capacity: 5,
  tags: ['meeting'],
  availability: [],
  type: 'rooms' as const,
}

describe('useResourcesStore', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  it('loads resources into state', async () => {
    ;(fetchState as Mock).mockResolvedValue({
      locations: [{ ...sampleEntry, id: 'loc-1', type: 'locations' }],
      rooms: [sampleEntry],
      materials: [],
    })

    const store = useResourcesStore()

    await store.loadResources()

    expect(fetchState).toHaveBeenCalledTimes(1)
    expect(store.resources.rooms).toHaveLength(1)
    expect(store.resources.locations[0].id).toBe('loc-1')
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('sets error when loading fails', async () => {
    ;(fetchState as Mock).mockRejectedValue(new Error('load failed'))

    const store = useResourcesStore()

    await store.loadResources()

    expect(store.error).toBe('load failed')
    expect(store.loading).toBe(false)
  })

  it('persists and removes resources', async () => {
    ;(saveResource as Mock).mockResolvedValue(sampleEntry)

    const store = useResourcesStore()

    const saved = await store.persistResource('rooms', sampleEntry)

    expect(saveResource).toHaveBeenCalledWith('rooms', sampleEntry)
    expect(saved).toEqual(sampleEntry)
    expect(store.resources.rooms).toHaveLength(1)

    ;(deleteResource as Mock).mockResolvedValue(true)

    const removed = await store.removeResource('rooms', '1')

    expect(deleteResource).toHaveBeenCalledWith('rooms', '1')
    expect(removed).toBe(true)
    expect(store.resources.rooms).toHaveLength(0)
  })
})

