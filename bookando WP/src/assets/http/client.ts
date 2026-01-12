// src/assets/http/client.ts

import axios, { AxiosError, AxiosInstance, InternalAxiosRequestConfig } from 'axios'
import type { BookandoBridgeConfig, WordPressApiSettings } from '../../types/window'

/** ----------------------------------------------------------------
 *  Runtime (WP-Bridge + optional SaaS)
 *  ---------------------------------------------------------------- */
type Runtime = {
  wp: {
    restRoot: string  // /wp-json/
    restUrl:  string  // /wp-json/bookando/v1   (OHNE Modul!)
    nonce:    string
    lang:     string
    origin:   string
  },
  apiBase: string,    // z.B. https://api.bookando.cloud/v1/
  token:   string,    // Bearer Token
  tenant:  string,    // X-Bookando-Tenant
}

const globalWindow = typeof window !== 'undefined' ? window : undefined

const BRIDGE: BookandoBridgeConfig = globalWindow?.BOOKANDO_VARS ?? {}
const WP: WordPressApiSettings     = globalWindow?.wpApiSettings ?? {}

/** ----------------------------------------------------------------
 *  WP-Bases robust ableiten
 *  - restRoot:   immer /wp-json/
 *  - restUrl:    immer /wp-json/bookando/v1 (OHNE Modul!)
 * ---------------------------------------------------------------- */
const wpRestRoot = String(BRIDGE.rest_root || WP.root || '/wp-json/')
  .replace(/\/+$/, '') + '/'

// Bevorzugt: neue Bridge-Variable „rest_url_base“
let wpRestUrlBase = String(BRIDGE.rest_url_base || '').replace(/\/+$/, '')
if (!wpRestUrlBase) {
  // Fallback: aus rest_url (modulspezifisch) die Base herauskürzen
  const ru = String(BRIDGE.rest_url || '').replace(/\/+$/, '')
  const m  = ru.match(/^(.*?\/wp-json\/bookando\/v1)(?:\/.*)?$/)
  if (m) {
    wpRestUrlBase = m[1]
  } else {
    // letzter Fallback: /wp-json/ + /bookando/v1
    wpRestUrlBase = wpRestRoot.replace(/\/+$/, '') + '/bookando/v1'
  }
}

const RUNTIME: Runtime = {
  wp: {
    restRoot: wpRestRoot,        // z.B. http://…/wp-json/
    restUrl : wpRestUrlBase,     // z.B. http://…/wp-json/bookando/v1  (OHNE Modul!)
    nonce   : String(BRIDGE.rest_nonce || WP.nonce || ''),
    lang    : String(BRIDGE.lang || document?.documentElement?.getAttribute('lang') || 'en'),
    origin  : String(BRIDGE.origin || globalWindow?.location?.origin || '')
  },
  apiBase: '',  // optional
  token:  '',
  tenant: ''
}

/** Public setters (werden unten exportiert) */
export function setApiBase(url: string) {
  const u = (url || '').trim().replace(/\/+$/, '')
  RUNTIME.apiBase = u // Pfade dann ohne führenden Slash aufrufen: 'auth/login'
}
export function setToken(tok: string)   { RUNTIME.token = tok || '' }
export function clearToken()            { RUNTIME.token = '' }
export function setTenant(id: string)   { RUNTIME.tenant = id || '' }

/** ----------------------------------------------------------------
 *  Debug-Schalter zentral
 *  ---------------------------------------------------------------- */
const isDebugHttp = () =>
  (typeof window !== 'undefined' && window.localStorage?.getItem('BOOKANDO_DEBUG_HTTP') === '1');

/** ----------------------------------------------------------------
 *  Axios Instanzen
 *  - restClient:  /wp-json/bookando/v1   (Standard für Module)
 *  - rootClient:  /wp-json               (absolute:true, z.B. users/avatar)
 *  - extClient:   SaaS-/API-Base (optional)
 *  ---------------------------------------------------------------- */
function makeClient(baseURL: string): AxiosInstance {
  // Basis immer normalisieren: ohne nachfolgende Slashes + genau ein abschließender Slash
  const normalizedBase = String(baseURL || '').replace(/\/+$/, '') + '/';

  // Frühwarnung gegen versehentlich doppelt genestete Bases
  if (/\/bookando\/v1\/[a-z0-9_-]+\/bookando\/v1/i.test(normalizedBase)) {
    console.warn('[http] WARN: baseURL wirkt doppelt genestet:', normalizedBase)
  }

  const inst = axios.create({
    baseURL: normalizedBase,
    withCredentials: true,
    timeout: 20000,
  });

  // --- Request Interceptor ---
  inst.interceptors.request.use((config: InternalAxiosRequestConfig) => {
    if (isDebugHttp()) {
      try {
        const base = String(config.baseURL || '')
        const url  = String(config.url || '')
        const full = base + url

        const stackLines = (new Error()).stack?.split('\n').slice(2, 8).join('\n') || ''

        console.groupCollapsed(
          '%c[http][req]%c %s %c%s',
          'color:#6b7280',
          'color:inherit',
          (config.method || 'get').toUpperCase(),
          'color:#2563eb',
          full
        )
        console.log('baseURL :', base)
        console.log('url     :', url)
        if (config.params) console.log('params  :', config.params)
        console.log('headers :', config.headers)

        if (/\/bookando\/v1\/employees\/bookando\/v1/i.test(full)) {
          console.warn('⚠️ Doppelter Namespace erkannt → irgendwo wird bereits "bookando/v1/..." an einen Modul-Client übergeben.')
        }

        console.log('caller  :\n' + stackLines)
        console.groupEnd()
      } catch { /* noop */ }
    }

    // ===== Accept / Sprache =====
    config.headers = {
      'Accept': 'application/json',
      'Accept-Language': RUNTIME.wp.lang || 'en',
      'X-Requested-With': 'XMLHttpRequest',
      ...(config.headers || {}),
    }

    // ===== Auth: WP Nonce → sonst Bearer =====
    const base = String(config.baseURL || '')
    if (base.startsWith(RUNTIME.wp.restRoot) || base.startsWith(RUNTIME.wp.restUrl)) {
      if (RUNTIME.wp.nonce) (config.headers as any)['X-WP-Nonce'] = RUNTIME.wp.nonce
    } else if (RUNTIME.token) {
      (config.headers as any)['Authorization'] = `Bearer ${RUNTIME.token}`
    }

    // ===== Tenant =====
    if (RUNTIME.tenant) {
      (config.headers as any)['X-Bookando-Tenant'] = RUNTIME.tenant
    }

    return config
  })

  // --- Response Interceptor (Erfolg + Fehler) ---
  inst.interceptors.response.use(
    (res) => {
      // Debug-Logging (BEFORE unwrapping)
      if (isDebugHttp()) {
        try {
          const cfg = res.config || {};
          const full = String(cfg.baseURL || '') + String(cfg.url || '');
          console.groupCollapsed(
            '%c[http][res]%c %s %c%s',
            'color:#059669', 'color:inherit',
            String((cfg.method || 'GET')).toUpperCase(),
            'color:#2563eb',
            full
          );
          console.log('status :', res.status);
          console.log('data (raw):', res.data);
          console.log('headers:', res.headers);
          console.groupEnd();
        } catch { /* noop */ }
      }

      // ⚙️ Response Unwrapper: Normalisiert Backend-Response-Format
      // Backend (via Response::ok): { data: {...}, meta: { success: true } }
      // Backend (legacy, direkt):   { data: [...], total: 142 }
      //
      // Frontend bekommt immer: res.data = das innere data-Objekt
      if (res.data && typeof res.data === 'object' && res.data !== null) {
        // Wenn Response das standardisierte Format hat: { data: ..., meta: ... }
        if ('data' in res.data && 'meta' in res.data) {
          // Unwrap: res.data = res.data.data (aber behalte meta für spezielle Fälle)
          const unwrapped = res.data.data
          const meta = res.data.meta

          // Store meta in response object for error handling/pagination
          res.data = unwrapped
          ;(res as any).__bookando_meta = meta
        }
        // Legacy-Format (Employees): { data: [...], total: 142 } bleibt unverändert
      }

      return res;
    },
    (error: AxiosError<any>) => {
      const status = error.response?.status
      const data   = error.response?.data as any
      const req    = error.config

      if (isDebugHttp()) {
        try {
          console.groupCollapsed(
            '%c[http][err]%c %s %c%s',
            'color:#b91c1c',
            'color:inherit',
            (req?.method || 'GET').toUpperCase(),
            'color:#b91c1c',
            (req?.baseURL || '') + (req?.url || '')
          )
          console.log('status :', status)
          console.log('data   :', data)
          console.log('config :', { baseURL:req?.baseURL, url:req?.url, params:req?.params, headers:req?.headers })
          console.groupEnd()
        } catch { /* noop */ }
      }

      const message =
        (data && (data.message || data.error || data.code)) ||
        error.message ||
        (status ? `HTTP ${status}` : 'Network error')

      const normalized = new Error(message) as Error & { status?: number; data?: unknown; code?: string }
      normalized.status = status
      normalized.data   = data
      normalized.code   = (data && data.code) || undefined
      throw normalized
    }
  )

  return inst
}

export const restClient = makeClient(RUNTIME.wp.restUrl)   // /wp-json/bookando/v1
export const rootClient = makeClient(RUNTIME.wp.restRoot)  // /wp-json
export const extClient  = () => makeClient(RUNTIME.apiBase || RUNTIME.wp.restUrl)

// Optional: kleine Helper-Funktionen, die von außen gesetzt werden können
export default {
  // Export der Setter für Runtime-Änderungen zur Laufzeit
  setApiBase,
  setToken,
  clearToken,
  setTenant,
}
