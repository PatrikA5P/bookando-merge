// src/assets/http/index.ts
import { restClient, rootClient, extClient, setApiBase, setToken, clearToken, setTenant } from './client'
import type { AxiosRequestConfig, AxiosResponse } from 'axios'

export type HttpOptions = {
  /** absolute:true → /wp-json (root) verwenden; sonst /wp-json/bookando/v1 */
  absolute?: boolean
  /** beliebige axios-Config-Erweiterungen (headers, timeout, etc.) */
  axios?: AxiosRequestConfig
}

/* ----------------------------- *
 * Helpers für Pfadnormalisierung
 * ----------------------------- */
const RX_HTTP      = /^https?:\/\//i
const RX_WPJSON    = /^\/?wp-json\/?/i
const RX_NAMESPACE = /^\/?bookando\/v\d+\//i

function normPath(p: string): string {
  return String(p || '').replace(/^\/+|\/+$/g, '')
}
function joinPath(a: string, b: string): string {
  const A = normPath(a)
  const B = normPath(b)
  return A && B ? `${A}/${B}` : (A || B)
}

/** entscheidet Client + Ziel-URL (ohne führendes /wp-json) */
function resolvePathAndClient(path: string, absolute?: boolean): { client: typeof restClient; url: string } {
  const raw = String(path || '')

  // absolute HTTP-URL → direkt verwenden (Client egal, axios nutzt absolute URL)
  if (RX_HTTP.test(raw)) {
    return { client: restClient, url: raw }
  }

  const looksRooty = RX_WPJSON.test(raw) || RX_NAMESPACE.test(raw)
  const useRoot    = absolute === true || looksRooty

  // /wp-json/... entfernen, falls mitgegeben
  let url = raw.replace(RX_WPJSON, '')
  url = normPath(url)

  return { client: useRoot ? rootClient : restClient, url }
}

/* ----------------------------- *
 * Core-Methoden (global)
 * → geben AxiosResponse-kompatible Objekte zurück
 * ----------------------------- */
async function GET<T = any>(path: string, params?: Record<string, any>, options: HttpOptions = {}): Promise<AxiosResponse<T>> {
  const { client, url } = resolvePathAndClient(path, options.absolute)
  const res = await client.get<T>(url, { params, ...(options.axios || {}) })
  return res
}
async function POST<T = any>(path: string, body?: any, options: HttpOptions = {}): Promise<AxiosResponse<T>> {
  const { client, url } = resolvePathAndClient(path, options.absolute)
  const res = await client.post<T>(url, body, { ...(options.axios || {}) })
  return res
}
async function PUT<T = any>(path: string, body?: any, options: HttpOptions = {}): Promise<AxiosResponse<T>> {
  const { client, url } = resolvePathAndClient(path, options.absolute)
  const res = await client.put<T>(url, body, { ...(options.axios || {}) })
  return res
}
async function PATCH<T = any>(path: string, body?: any, options: HttpOptions = {}): Promise<AxiosResponse<T>> {
  const { client, url } = resolvePathAndClient(path, options.absolute)
  const res = await client.patch<T>(url, body, { ...(options.axios || {}) })
  return res
}
async function DEL<T = any>(path: string, params?: Record<string, any>, options: HttpOptions = {}): Promise<AxiosResponse<T>> {
  const { client, url } = resolvePathAndClient(path, options.absolute)
  const res = await client.delete<T>(url, { params, ...(options.axios || {}) })
  return res
}

/* ----------------------------- *
 * Modul-Client
 * - hängt den Modul-Slug nur an, wenn der Subpfad NICHT rooty ist
 * - hardent gegen "employees/bookando/v1/..."
 * ----------------------------- */
function moduleClient(slug: string) {
  const base = normPath(slug) // z.B. "employees"

  function fixPathMaybeRooty(sub: string, o: HttpOptions) {
    const s = normPath(sub)
    const looksRooty = RX_WPJSON.test(s) || RX_NAMESPACE.test(s)

    // Rooty → auf Root-Client umschalten, Pfad unverändert nutzen
    const opts = looksRooty ? { ...o, absolute: true } : o
    let path = looksRooty ? s : joinPath(base, s)

    // Guard: "employees/bookando/v1/..." → "bookando/v1/..."
    if (new RegExp(`^${base}\\/bookando\\/v\\d+\\/`, 'i').test(path)) {
      path = path.replace(new RegExp(`^${base}\\/`, 'i'), '')
    }
    return { path, opts }
  }

  return {
    get <T = any>(p = '', params?: Record<string, any>, o: HttpOptions = {}) {
      const { path, opts } = fixPathMaybeRooty(p, o)
      return GET<T>(path, params, opts)
    },
    post<T = any>(p = '', body?: any, o: HttpOptions = {}) {
      const { path, opts } = fixPathMaybeRooty(p, o)
      return POST<T>(path, body, opts)
    },
    put <T = any>(p = '', body?: any, o: HttpOptions = {}) {
      const { path, opts } = fixPathMaybeRooty(p, o)
      return PUT<T>(path, body, opts)
    },
    patch<T = any>(p = '', body?: any, o: HttpOptions = {}) {
      const { path, opts } = fixPathMaybeRooty(p, o)
      return PATCH<T>(path, body, opts)
    },
    del <T = any>(p = '', params?: Record<string, any>, o: HttpOptions = {}) {
      const { path, opts } = fixPathMaybeRooty(p, o)
      return DEL<T>(path, params, opts)
    },
  }
}

/* ----------------------------- *
 * Optional: explizit externer Client (SaaS/API)
 * → liefert ebenfalls AxiosResponse-kompatibel
 * ----------------------------- */
function external() {
  const c = extClient()
  return {
    get : async <T = any>(path: string, params?: Record<string, any>, axios?: AxiosRequestConfig): Promise<AxiosResponse<T>> =>
      c.get<T>(path, { params, ...(axios || {}) }),
    post: async <T = any>(path: string, body?: any, axios?: AxiosRequestConfig): Promise<AxiosResponse<T>> =>
      c.post<T>(path, body, axios),
    put : async <T = any>(path: string, body?: any, axios?: AxiosRequestConfig): Promise<AxiosResponse<T>> =>
      c.put<T>(path, body, axios),
    patch: async <T = any>(path: string, body?: any, axios?: AxiosRequestConfig): Promise<AxiosResponse<T>> =>
      c.patch<T>(path, body, axios),
    del : async <T = any>(path: string, params?: Record<string, any>, axios?: AxiosRequestConfig): Promise<AxiosResponse<T>> =>
      c.delete<T>(path, { params, ...(axios || {}) }),
  }
}

/* ----------------------------- *
 * Default-Export
 * ----------------------------- */
const http = {
  // globale Methoden (Base: /wp-json/bookando/v1)
  get : GET,
  post: POST,
  put : PUT,
  patch: PATCH,
  del : DEL,

  // Modul-Scope: http.module('employees').get('…')
  module: moduleClient,

  // Extern/SaaS (falls genutzt)
  external,

  // Runtime-Setter (von config.ts/auth.ts verwendet)
  setApiBase,
  setToken,
  clearToken,
  setTenant,
}

export default http
export { setApiBase, setToken, clearToken, setTenant }
