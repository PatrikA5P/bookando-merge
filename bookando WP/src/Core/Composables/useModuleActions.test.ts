import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useModuleActions } from './useModuleActions'
import { apiPost } from '@core/Api/apiClient'

vi.mock('@core/Api/apiClient', () => ({
  apiPost: vi.fn(),
}))

const mockedApiPost = vi.mocked(apiPost)

describe('useModuleActions', () => {
  beforeEach(() => {
    mockedApiPost.mockReset()
  })

  it('returns the original error message when the request fails', async () => {
    mockedApiPost.mockRejectedValueOnce(new Error('Request exploded'))

    const { perform } = useModuleActions('customers')
    const result = await perform('soft_delete')

    expect(result).toEqual({ ok: false, message: 'Request exploded' })
  })

  it('falls back to a default message when the error is not descriptive', async () => {
    mockedApiPost.mockRejectedValueOnce({})

    const { perform } = useModuleActions('customers')
    const result = await perform('soft_delete')

    expect(result).toEqual({ ok: false, message: 'Unbekannter Fehler beim Ausführen der Aktion' })
  })
})
