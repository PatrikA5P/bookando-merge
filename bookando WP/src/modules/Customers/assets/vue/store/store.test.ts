import { beforeEach, afterEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import { useCustomersStore } from './store'
import {
  getCustomers,
  getCustomer,
  createCustomer,
  updateCustomer,
  deleteCustomer as apiDeleteCustomer,
} from '../api/CustomersApi'

vi.mock('../api/CustomersApi', () => ({
  getCustomers: vi.fn(),
  getCustomer: vi.fn(),
  createCustomer: vi.fn(),
  updateCustomer: vi.fn(),
  deleteCustomer: vi.fn(),
}))

const mockedGetCustomers = vi.mocked(getCustomers)
const mockedGetCustomer = vi.mocked(getCustomer)
const mockedCreateCustomer = vi.mocked(createCustomer)
const mockedUpdateCustomer = vi.mocked(updateCustomer)
const mockedDeleteCustomer = vi.mocked(apiDeleteCustomer)

describe('useCustomersStore', () => {
  let warnSpy: ReturnType<typeof vi.spyOn>

  beforeEach(() => {
    setActivePinia(createPinia())
    mockedGetCustomers.mockReset()
    mockedGetCustomer.mockReset()
    mockedCreateCustomer.mockReset()
    mockedUpdateCustomer.mockReset()
    mockedDeleteCustomer.mockReset()

    const storage: Record<string, string> = {}
    vi.stubGlobal('localStorage', {
      getItem: vi.fn((key: string) => (key in storage ? storage[key] : null)),
      setItem: vi.fn((key: string, value: string) => {
        storage[key] = String(value)
      }),
      removeItem: vi.fn((key: string) => {
        delete storage[key]
      }),
    })

    warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})
  })

  afterEach(() => {
    warnSpy.mockRestore()
    vi.unstubAllGlobals()
  })

  it('loads customers and updates state', async () => {
    mockedGetCustomers.mockResolvedValueOnce([{ id: 1, first_name: 'Anna' } as any])

    const store = useCustomersStore()
    await store.load()

    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
    expect(store.items).toHaveLength(1)
    expect(store.items[0].first_name).toBe('Anna')
    expect(mockedGetCustomers).toHaveBeenCalledTimes(1)
  })

  it('resets state when loading fails', async () => {
    mockedGetCustomers.mockRejectedValueOnce(new Error('kaputt'))

    const store = useCustomersStore()
    await store.load()

    expect(store.loading).toBe(false)
    expect(store.items).toEqual([])
    expect(store.error).toBe('kaputt')
  })

  it('fetches a customer by id and returns null on failure', async () => {
    mockedGetCustomer.mockResolvedValueOnce({ id: 5 } as any)
    mockedGetCustomer.mockRejectedValueOnce(new Error('fail'))

    const store = useCustomersStore()

    await expect(store.fetchById(5)).resolves.toEqual({ id: 5 })
    await expect(store.fetchById(7)).resolves.toBeNull()
    expect(mockedGetCustomer).toHaveBeenCalledTimes(2)
  })

  it('saves a new customer and reloads the list', async () => {
    mockedCreateCustomer.mockResolvedValueOnce({ id: 9 } as any)
    mockedGetCustomers.mockResolvedValueOnce([{ id: 9, first_name: 'Lisa' } as any])

    const store = useCustomersStore()
    const result = await store.save({ first_name: 'Lisa' } as any)

    expect(result).toBe(true)
    expect(mockedCreateCustomer).toHaveBeenCalledTimes(1)
    expect(mockedUpdateCustomer).not.toHaveBeenCalled()
    expect(store.items[0].id).toBe(9)
  })

  it('updates an existing customer', async () => {
    mockedUpdateCustomer.mockResolvedValueOnce({ updated: true } as any)
    mockedGetCustomers.mockResolvedValueOnce([{ id: 3, first_name: 'Max' } as any])

    const store = useCustomersStore()
    const result = await store.save({ id: 3, first_name: 'Max' } as any)

    expect(result).toBe(true)
    expect(mockedUpdateCustomer).toHaveBeenCalledWith(3, expect.objectContaining({ first_name: 'Max' }))
    expect(mockedCreateCustomer).not.toHaveBeenCalled()
    expect(store.items[0].id).toBe(3)
  })

  it('captures errors during save operations', async () => {
    mockedCreateCustomer.mockRejectedValueOnce(new Error('nope'))

    const store = useCustomersStore()
    const result = await store.save({ first_name: 'Error' } as any)

    expect(result).toBe(false)
    expect(store.error).toBe('nope')
    expect(store.loading).toBe(false)
  })

  it('removes a customer and reloads the list', async () => {
    mockedDeleteCustomer.mockResolvedValueOnce({ ok: true } as any)
    mockedGetCustomers.mockResolvedValueOnce([])

    const store = useCustomersStore()
    const result = await store.remove(11)

    expect(result).toBe(true)
    expect(mockedDeleteCustomer).toHaveBeenCalledWith(11)
    expect(store.items).toEqual([])
  })

  it('persists and clones filter selections', () => {
    const store = useCustomersStore()
    store.setActiveFilterFields(['status', 'city'])

    expect(store.activeFilterFields).toEqual(['status', 'city'])
    expect(localStorage.setItem).toHaveBeenCalledWith(
      'bookando_customers_active_filter_fields',
      JSON.stringify(['status', 'city'])
    )

    const original = store.activeFilterFields
    store.setActiveFilterFields(original)
    expect(store.activeFilterFields).not.toBe(original)
  })

  it('resets column settings via localStorage helpers', () => {
    const store = useCustomersStore()
    store.setVisibleColumns(['name'])
    store.setColWidths({ name: 200 })
    store.resetColumnSettings()

    expect(store.visibleColumns).toEqual([])
    expect(store.colWidths).toEqual({})
    expect(localStorage.removeItem).toHaveBeenCalledWith('bookando_customers_visible_columns')
    expect(localStorage.removeItem).toHaveBeenCalledWith('bookando_customers_col_widths')
    expect(warnSpy).not.toHaveBeenCalled()
  })
})
