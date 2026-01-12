// src/modules/customers/model/CustomersModel.ts

export interface Customer {
  id?: number
  first_name: string
  last_name: string
  email: string
  phone?: string
  address?: string
  address_2?: string
  zip?: string
  city?: string
  country?: string
  gender?: 'm' | 'f' | 'd' | 'n' | ''
  birthdate?: string | null
  language?: 'de' | 'en' | 'fr' | 'it' | 'es'
  note?: string
  description?: string
  avatar_url?: string
  timezone?: string
  external_id?: string
  status?: 'active' | 'blocked' | 'deleted'
  deleted_at?: string | null
  total_appointments?: number
  last_appointment?: string
}
