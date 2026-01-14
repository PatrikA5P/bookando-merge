import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@assets/http', () => {
  const get = vi.fn()
  const post = vi.fn()
  const put = vi.fn()
  const del = vi.fn()
  return { default: { get, post, put, del } }
})

import http from '@assets/http'
import { bulk, create, getOne, list, remove, update } from '../api/OffersApi'

describe('OffersApi', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('list normalizes payload to array', async () => {
    ;(http.get as any).mockResolvedValue({ data: { data: [{ id: 1 }], meta: { total: 1 } } })

    const result = await list({ page: 2 })

    expect(http.get).toHaveBeenCalledWith('offers', { page: 2 })
    expect(result).toEqual([{ id: 1 }])
  })

  it('getOne returns entity', async () => {
    ;(http.get as any).mockResolvedValue({ data: { id: 5 } })

    const entity = await getOne(5)

    expect(http.get).toHaveBeenCalledWith('offers/5')
    expect(entity).toEqual({ id: 5 })
  })

  it('create forwards payload', async () => {
    ;(http.post as any).mockResolvedValue({ data: { data: { id: 7 } } })

    const response = await create({ title: 'Neu' })

    expect(http.post).toHaveBeenCalledWith('offers', { title: 'Neu' })
    expect(response).toEqual({ data: { id: 7 } })
  })

  it('update forwards payload', async () => {
    ;(http.put as any).mockResolvedValue({ data: { data: { updated: true } } })

    const response = await update(3, { title: 'Bearbeitet' })

    expect(http.put).toHaveBeenCalledWith('offers/3', { title: 'Bearbeitet' })
    expect(response).toEqual({ data: { updated: true } })
  })

  it('remove supports hard flag', async () => {
    ;(http.del as any).mockResolvedValue({ data: { data: { deleted: true } } })

    const response = await remove(9, { hard: true })

    expect(http.del).toHaveBeenCalledWith('offers/9', { hard: 1 })
    expect(response).toEqual({ data: { deleted: true } })
  })

  it('bulk posts action and ids', async () => {
    ;(http.post as any).mockResolvedValue({ data: { data: { deleted: 2 } } })

    const response = await bulk('delete_soft', [1, 2])

    expect(http.post).toHaveBeenCalledWith('offers/bulk', { action: 'delete_soft', ids: [1, 2] })
    expect(response).toEqual({ data: { deleted: 2 } })
  })
})
