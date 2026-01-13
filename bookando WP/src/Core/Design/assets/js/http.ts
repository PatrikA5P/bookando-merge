import type { BookandoBridgeConfig, WordPressApiSettings } from '../../../../types/window'

type HttpMethod = 'GET'|'POST'|'PUT'|'PATCH'|'DELETE';
type Dict = Record<string, any>;

interface RequestOptions {
  headers?: Dict;
  query?: Dict;                   // ?page=1&per_page=20
  body?: any;                     // wird bei JSON automatisch serialisiert
  timeoutMs?: number;             // default 20000
  absolute?: boolean;             // true => rest_root statt modularem rest_url
  credentials?: RequestCredentials; // default: 'same-origin' im WP-Kontext, sonst 'omit'
}

interface Res<T=any> { ok: boolean; status: number; data: T; headers: Headers; }

const globalWindow = typeof window !== 'undefined' ? window : undefined;
const BRIDGE: BookandoBridgeConfig = globalWindow?.BOOKANDO_VARS ?? {};
const WP: WordPressApiSettings     = globalWindow?.wpApiSettings ?? {};

let RUNTIME = {
  // WordPress-Defaults via Bridge
  wp: {
    restRoot: String(BRIDGE.rest_root || WP.root || '/wp-json/'),
    restUrl : String(BRIDGE.rest_url  || BRIDGE.rest_root || WP.root || '/wp-json/'),
    nonce   : String(BRIDGE.rest_nonce|| WP.nonce || ''),
    lang    : String(BRIDGE.lang || document?.documentElement?.getAttribute('lang') || 'en'),
    origin  : String(BRIDGE.origin || globalWindow?.location?.origin || '')
  },
  // SaaS/extern (kannst du zur Laufzeit setzen)
  apiBase: '',   // z. B. https://api.bookando.cloud/v1/
  token  : '',   // Bearer Token
  tenant : '',   // optional: Tenant/Org-ID Header
};

function setApiBase(url: string) { RUNTIME.apiBase = url.replace(/\/+$/, '') + '/'; }
function setToken(token: string) { RUNTIME.token = token; }
function clearToken()            { RUNTIME.token = ''; }
function setTenant(id: string)   { RUNTIME.tenant = id; }

function isAbsoluteUrl(u: string) { return /^https?:\/\//i.test(u); }
function joinUrl(base: string, path: string) {
  return base.replace(/\/+$/, '') + '/' + String(path).replace(/^\/+/, '');
}

function buildUrl(path: string, opts: RequestOptions): string {
  // 1) explizit absolute REST im WP-Kontext
  if (opts.absolute === true) return joinUrl(RUNTIME.wp.restRoot, path);

  // 2) absolute http(s) passthrough
  if (isAbsoluteUrl(path)) return path;

  // 3) wenn /wp-json/... übergeben wurde
  if (/^\/?wp-json\//.test(path)) return joinUrl(RUNTIME.wp.origin || '', path);

  // 4) WP-Kontext: bevorzugt modulare rest_url (z. B. /wp-json/bookando/v1/customers)
  if (RUNTIME.wp.restUrl) return joinUrl(RUNTIME.wp.restUrl, path);

  // 5) SaaS/extern (falls gesetzt)
  if (RUNTIME.apiBase) return joinUrl(RUNTIME.apiBase, path);

  // 6) Fallback: root
  return joinUrl(RUNTIME.wp.restRoot, path);
}

function appendQuery(url: string, q?: Dict) {
  if (!q || typeof q !== 'object') return url;
  const usp = new URLSearchParams();
  Object.entries(q).forEach(([k, v]) => {
    if (v === undefined || v === null) return;
    if (Array.isArray(v)) v.forEach(x => usp.append(k, String(x)));
    else usp.append(k, String(v));
  });
  const sep = url.includes('?') ? '&' : '?';
  return url + (usp.toString() ? (sep + usp.toString()) : '');
}

/** Debug-Schalter zentral */
const isDebugHttp = () =>
  (typeof window !== 'undefined' && window.localStorage?.getItem('BOOKANDO_DEBUG_HTTP') === '1');

async function request<T=any>(method: HttpMethod, path: string, options: RequestOptions = {}): Promise<Res<T>> {
  const { headers = {}, query, body, timeoutMs = 20000 } = options;
  const url = appendQuery(buildUrl(path, options), query);

  const ctrl = new AbortController();
  const t = setTimeout(() => ctrl.abort(), timeoutMs);

  const isWP = Boolean(RUNTIME.wp.restUrl || RUNTIME.wp.restRoot);
  const baseHeaders: Dict = {
    'Accept': 'application/json',
    'Accept-Language': RUNTIME.wp.lang || 'en',
    'X-Requested-With': 'XMLHttpRequest'
  };

  // Auth
  if (isWP && RUNTIME.wp.nonce) {
    baseHeaders['X-WP-Nonce'] = RUNTIME.wp.nonce;
  } else if (RUNTIME.token) {
    baseHeaders['Authorization'] = `Bearer ${RUNTIME.token}`;
  }

  if (RUNTIME.tenant) {
    baseHeaders['X-Bookando-Tenant'] = RUNTIME.tenant;
  }

  // Body
  let payload: BodyInit | undefined;
  if (body !== undefined && body !== null) {
    if (body instanceof FormData) {
      payload = body; // Browser setzt korrekten boundary-Header selbst
    } else if (typeof body === 'string') {
      payload = body; // ggf. selbst gesetzter Content-Type
    } else {
      baseHeaders['Content-Type'] = headers['Content-Type'] || 'application/json';
      payload = JSON.stringify(body);
    }
  }

  const resp = await fetch(url, {
    method,
    headers: { ...baseHeaders, ...headers },
    body: payload,
    signal: ctrl.signal,
    credentials: options.credentials ?? (isWP ? 'same-origin' : 'omit'),
    mode: 'cors',
    cache: 'no-store',
  }).catch((err) => {
    clearTimeout(t);
    // Netzwerk/Abort → als Fetch-ähnliche Antwort wrappen
    const message = err?.name === 'AbortError' ? 'Request timeout' : 'Network error'
    return new Response(JSON.stringify({ message }), { status: 0 }) as any;
  });

  clearTimeout(t);

  const resHeaders = ('headers' in resp) ? (resp as Response).headers : new Headers();
  let data: any = null;

  try {
    const ct = resHeaders.get('content-type') || '';
    if (ct.includes('application/json')) data = await (resp as Response).json();
    else data = await (resp as Response).text();
  } catch {
    data = null;
  }

  const out: Res<T> = {
    ok: (resp as Response).ok,
    status: (resp as Response).status || 0,
    data,
    headers: resHeaders
  };

  // Debug-Logging für erfolgreiche Responses
  if (isDebugHttp() && (resp as Response).ok) {
    try {
      console.groupCollapsed(
        '%c[fetch][res]%c %s %c%s',
        'color:#059669','color:inherit',
        method.toUpperCase(),
        'color:#2563eb',
        url
      );
      console.groupEnd();
    } catch { /* noop */ }
  }

  if (!out.ok) {
    // Zentrale Fehlernormierung – damit deine UI immer konsistente Fehlertexte bekommt
    const msg = (data && (data.message || data.error || data.code)) || `HTTP ${out.status}`;
    throw Object.assign(new Error(String(msg)), { http: out });
  }

  return out;
}

// Public API
export default {
  setApiBase, setToken, clearToken, setTenant,
  get : <T=any>(path: string, query?: Dict, o: RequestOptions = {}) => request<T>('GET', path, { ...o, query }),
  post: <T=any>(path: string, body?: any, o: RequestOptions = {}) => request<T>('POST', path, { ...o, body }),
  put : <T=any>(path: string, body?: any, o: RequestOptions = {}) => request<T>('PUT', path, { ...o, body }),
  patch:<T=any>(path: string, body?: any, o: RequestOptions = {}) => request<T>('PATCH', path, { ...o, body }),
  del : <T=any>(path: string, query?: Dict, o: RequestOptions = {}) => request<T>('DELETE', path, { ...o, query }),
  request
};
