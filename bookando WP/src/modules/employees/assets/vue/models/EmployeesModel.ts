// src/modules/employees/assets/vue/models/EmployeesModel.ts
// Modell fuer Mitarbeitende – orientiert an wp_bookando_users
export type EmployeeStatus = 'active' | 'blocked' | 'deleted'

export interface Employee {
  id?: number
  first_name: string
  last_name: string
  email: string
  phone?: string
  address?: string
  address_2?: string
  zip?: string
  city?: string
  country?: string | null
  gender?: 'm' | 'f' | 'd' | 'n' | ''
  birthdate?: string | null
  language?: 'de' | 'en' | 'fr' | 'it' | 'es'
  timezone?: string
  note?: string
  description?: string
  status?: EmployeeStatus
  avatar_url?: string
  external_id?: string
  badge_id?: string
  deleted_at?: string | null
  created_at?: string
  updated_at?: string
  // Aggregierbare Felder aus employees_* Tabellen (optional):
  location?: string | null
  services?: string[] | null
  calendar?: 'google' | 'microsoft' | 'exchange' | 'icloud' | 'ics' | null
}
