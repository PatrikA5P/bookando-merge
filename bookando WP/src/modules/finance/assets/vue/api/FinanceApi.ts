import http from '@assets/http'

export type FinanceItem = {
  description: string
  type: string
  reference?: string
  quantity: number
  unit_price: number
  tax_rate: number
  total?: number
  account?: string
}

export type FinanceDocument = {
  id?: string
  number?: string
  customer: string
  date: string
  due_date: string
  status: string
  currency: string
  auto_generated?: boolean
  items: FinanceItem[]
  subtotal?: number
  tax_total?: number
  total?: number
  created_at?: string
  updated_at?: string
}

export type FinanceSettings = {
  auto_invoice: boolean
  auto_send: boolean
  batch_mode: 'manual' | 'daily' | 'weekly' | 'monthly'
}

export type DiscountCode = {
  id?: string
  code: string
  description: string
  discount_type: 'percentage' | 'fixed'
  value: number
  valid_from: string | null
  valid_to: string | null
  max_uses: number | null
  usage_count?: number
  applies_to?: string[]
}

export type FinanceState = {
  invoices: FinanceDocument[]
  credit_notes: FinanceDocument[]
  discount_codes: DiscountCode[]
  settings: FinanceSettings
  ledger: {
    accounts: { code: string; name: string; type: string }[]
    exported_at: string | null
  }
}

export type LedgerExport = {
  generated_at: string
  from: string | null
  to: string | null
  entries: Array<{
    document_id: string
    document_type: string
    document_no: string
    date: string
    customer: string
    account: string
    description: string
    amount: number
    tax_rate: number
    currency: string
  }>
  settings: FinanceSettings
}

const BASE_URL = (window as any).BOOKANDO_VARS?.rest_url || '/wp-json/bookando/v1/finance'

export async function fetchState(): Promise<FinanceState> {
  const { data } = await http.get<FinanceState>(`${BASE_URL}/state`)
  return data
}

export async function saveInvoice(payload: Partial<FinanceDocument>): Promise<FinanceDocument> {
  const { data } = await http.post<FinanceDocument>(`${BASE_URL}/invoices`, payload)
  return data
}

export async function deleteInvoice(id: string): Promise<boolean> {
  const { data } = await http.delete<{ deleted: boolean }>(`${BASE_URL}/invoices/${id}`)
  return !!data?.deleted
}

export async function saveCreditNote(payload: Partial<FinanceDocument>): Promise<FinanceDocument> {
  const { data } = await http.post<FinanceDocument>(`${BASE_URL}/credit_notes`, payload)
  return data
}

export async function deleteCreditNote(id: string): Promise<boolean> {
  const { data } = await http.delete<{ deleted: boolean }>(`${BASE_URL}/credit_notes/${id}`)
  return !!data?.deleted
}

export async function saveFinanceSettings(settings: Partial<FinanceSettings>): Promise<FinanceSettings> {
  const { data } = await http.post<FinanceSettings>(`${BASE_URL}/settings`, settings)
  return data
}

export async function exportLedger(payload: { from?: string; to?: string }): Promise<LedgerExport> {
  const { data } = await http.post<LedgerExport>(`${BASE_URL}/export`, payload)
  return data
}

export async function saveDiscountCode(payload: Partial<DiscountCode>): Promise<DiscountCode> {
  const { data } = await http.post<DiscountCode>(`${BASE_URL}/discount_codes`, payload)
  return data
}

export async function deleteDiscountCode(id: string): Promise<boolean> {
  const { data } = await http.delete<{ deleted: boolean }>(`${BASE_URL}/discount_codes/${id}`)
  return !!data?.deleted
}
