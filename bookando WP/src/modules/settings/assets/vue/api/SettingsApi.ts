// src/modules/settings/assets/vue/api/SettingsApi.ts
import httpBase from '@assets/http'

// ⚙️ Modul-Client: /wp-json/bookando/v1/settings/...
const http = httpBase.module('settings')

/**
 * Bookando Settings API – explizite Endpunkte je Settings-Bereich
 *   /wp-json/bookando/v1/settings/company
 *   /wp-json/bookando/v1/settings/general
 *   /wp-json/bookando/v1/settings/roles/<role_slug>
 *   /wp-json/bookando/v1/settings/feature/<feature_key>
 * Im SPA nur relative Pfade übergeben – http.ts hängt sie an BOOKANDO_VARS.rest_url an.
 */

/** API response wrapper for settings */
interface SettingsResponse<T> {
  data: T
}

export async function getSettings<T = unknown>(type: string, subkey?: string): Promise<T> {
  const path = subkey ? `${type}/${subkey}` : type
  const { data } = await http.get<T | SettingsResponse<T>>(path)
  // Server kann Array oder {data:...} liefern – normalize:
  return (Array.isArray(data) || typeof data !== 'object') ? (data as T) : ((data as SettingsResponse<T>).data ?? data as T)
}

export async function saveSettings<T = unknown>(type: string, _payload: Record<string, unknown>, subkey?: string): Promise<T> {
  const path = subkey ? `${type}/${subkey}` : type
  const { data } = await http.post<T>(path, _payload)
  return data
}

/** Shortcuts */
export const BookandoSettingsAction = {
  GENERAL: 'general',
  COMPANY: 'company',
  ROLES:   'roles',
  // z. B. 'labels', 'notifications', ...
} as const

export const BookandoRoleSlugs = {
  EMPLOYEE: 'bookando_employee',
  CUSTOMER: 'bookando_customer',
  ADMIN:    'bookando_admin',
} as const

export function getGeneralSettings<T = unknown>() {
  return getSettings<T>(BookandoSettingsAction.GENERAL)
}
export function saveGeneralSettings<T = unknown>(data: Record<string, unknown>) {
  return saveSettings<T>(BookandoSettingsAction.GENERAL, data)
}

export function getCompanySettings<T = unknown>() {
  return getSettings<T>(BookandoSettingsAction.COMPANY)
}
export function saveCompanySettings<T = unknown>(data: Record<string, unknown>) {
  return saveSettings<T>(BookandoSettingsAction.COMPANY, data)
}

export function getRoleSettings<T = unknown>(slug: string) {
  return getSettings<T>(BookandoSettingsAction.ROLES, slug)
}
export function saveRoleSettings<T = unknown>(slug: string, data: Record<string, unknown>) {
  return saveSettings<T>(BookandoSettingsAction.ROLES, data, slug)
}
