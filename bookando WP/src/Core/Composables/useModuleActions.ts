// src/Core/Composables/useModuleActions.ts
import { ref } from 'vue'
import { apiPost } from '@core/Api/apiClient'

const ACTION_KEYS = ['save', 'soft_delete', 'hard_delete', 'block', 'activate', 'export'] as const
/** Zentrale Action-Keys (gemeinsamer Nenner) */
export type ActionKey = (typeof ACTION_KEYS)[number]

const DEFAULT_ALLOWED: readonly ActionKey[] = ['soft_delete', 'hard_delete', 'block', 'activate', 'export']

type ActionFeatureMap = Partial<Record<ActionKey, string[]>>

type PerformInput  = { ids?: Array<string | number>; payload?: unknown }
type PerformResult = { ok: boolean; message?: string }

/** Minimal-Registry: pro Modul, welche Actions angeboten werden und wohin gepostet wird */
type ModuleActionsConfig = {
  /** Bulk-/Action-Endpunkt (Default: /wp-json/bookando/v1/<slug>/bulk) */
  endpoint?: (slug: string) => string
  /** Welche Actions sind in diesem Modul per Default sinnvoll/nutzbar */
  allowed?: ActionKey[]
  /** Optional: Feature-/Lizenzgate pro Action */
  gate?: (action: ActionKey) => boolean
  /** Optional: Feature-Mapping für automatische Gates */
  features?: ActionFeatureMap
}

/**
 * Kleine eingebaute Registry.
 * - Schlank by default; Module können per Param überschreiben/erweitern.
 */
const DEFAULT_ENDPOINT = (slug: string) => `/wp-json/bookando/v1/${slug}/bulk`
const MODULE_ACTIONS: Record<string, ModuleActionsConfig> = {
  customers: {
    endpoint: DEFAULT_ENDPOINT,
    allowed: [...DEFAULT_ALLOWED],
  },
  employees: {
    endpoint: DEFAULT_ENDPOINT,
    allowed: [...DEFAULT_ALLOWED],
  },
  // weitere Module bei Bedarf ergänzen …
}

const LICENSE_FEATURES_KEY = 'license_features'
const MODULE_ACTIONS_KEY = 'module_actions'

/** i18n-Helfer: erzeugt Bulk-Options mit übersetzten Labels. */
export function getBulkActionOptions(
  t: (k: string) => string,
  moduleSlug: string,
  opts?: { include?: ActionKey[]; exclude?: ActionKey[] }
) {
  const cfg = resolveModuleActionsConfig(moduleSlug)
  const base = [...(cfg.allowed ?? DEFAULT_ALLOWED)]

  let list = base.slice()
  if (opts?.include?.length) {
    const inc = new Set(opts.include)
    list = base.filter(a => inc.has(a))
  }
  if (opts?.exclude?.length) {
    const ex = new Set(opts.exclude)
    list = list.filter(a => !ex.has(a))
  }

  // Übersetzungsschlüssel:
  // core.actions.<key>.label  /  core.actions.<key>.confirm (falls du Bestätigungen zentralisieren willst)
  const label = (k: ActionKey) => t(`core.actions.${k}.label`)

  return list.map(a => ({ value: a, label: label(a) }))
}

/**
 * Zentraler Action-Performer. Kann optional mit modul-lokaler Config/Gates überladen werden.
 *
 * Beispiel:
 *   const { loading, perform } = useModuleActions('customers', {
 *     gate: (a) => license.hasFeature(a)
 *   })
 */
export function useModuleActions(
  moduleSlug: string,
  local?: Pick<ModuleActionsConfig, 'endpoint' | 'gate'>
) {
  const loading = ref(false)
  const cfg = resolveModuleActionsConfig(moduleSlug)

  const gates: Array<(action: ActionKey) => boolean> = []
  const featureGate = createFeatureGate(cfg.features)
  if (featureGate) gates.push(featureGate)
  if (cfg.gate) gates.push(cfg.gate)
  if (local?.gate) gates.push(local.gate)

  const endpointResolver = local?.endpoint || cfg.endpoint || DEFAULT_ENDPOINT

  async function perform(
    action: ActionKey | string,
    { ids = [], payload }: PerformInput = {},
  ): Promise<PerformResult> {
    if (loading.value) return { ok: false, message: 'busy' }
    loading.value = true
    try {
      const actionKey = isActionKey(action) ? action : undefined
      if (actionKey) {
        for (const gate of gates) {
          if (!gate(actionKey)) {
            return { ok: false, message: 'Funktion nicht lizenziert' }
          }
        }
      }

      const endpoint = endpointResolver(moduleSlug)
      const res = await apiPost(endpoint, { action, ids, payload })
      if (!res?.ok) return { ok: false, message: res?.message || 'Fehler' }
      return { ok: true, message: res?.message || 'OK' }
    } catch (error: unknown) {
      const messageFromObject =
        typeof error === 'object' && error !== null && 'message' in error && typeof (error as { message?: unknown }).message === 'string'
          ? ((error as { message: string }).message ?? '').trim()
          : ''
      const message =
        messageFromObject.length > 0
          ? messageFromObject
          : typeof error === 'string' && error.trim().length
            ? error
            : 'Unbekannter Fehler beim Ausführen der Aktion'
      return { ok: false, message }
    } finally {
      loading.value = false
    }
  }

  return { loading, perform }
}

function resolveModuleActionsConfig(moduleSlug: string): ModuleActionsConfig {
  const base = MODULE_ACTIONS[moduleSlug] || {}
  const dynamic = readBridgeModuleActions(moduleSlug)

  return {
    endpoint: dynamic.endpoint ?? base.endpoint,
    allowed: cloneActionList(dynamic.allowed ?? base.allowed),
    gate: composeGates(base.gate, dynamic.gate),
    features: cloneFeatureMap(dynamic.features ?? base.features),
  }
}

function readBridgeModuleActions(moduleSlug: string): ModuleActionsConfig {
  const bridge = getBridgeVars()
  if (!bridge) return {}

  const slug = typeof bridge.slug === 'string' ? bridge.slug : undefined
  if (slug && slug !== moduleSlug) return {}

  const raw = bridge[MODULE_ACTIONS_KEY]
  if (!isPlainObject(raw)) return {}

  const allowed = normaliseActionList(raw.allowed)
  const features = normaliseFeatureMap(raw.features)
  const endpoint = normaliseEndpoint(raw.endpoint)

  return {
    allowed,
    features,
    endpoint,
  }
}

function getBridgeVars(): Record<string, any> | undefined {
  if (typeof window === 'undefined') return undefined
  const w = window as any
  const vars = w?.BOOKANDO_VARS
  return isPlainObject(vars) ? vars : undefined
}

function composeGates(
  ...gates: Array<ModuleActionsConfig['gate'] | undefined>
): ModuleActionsConfig['gate'] {
  const stack = gates.filter((gate): gate is NonNullable<typeof gate> => typeof gate === 'function')
  if (!stack.length) return undefined
  return (action: ActionKey) => stack.every(gate => gate(action))
}

function createFeatureGate(features?: ActionFeatureMap): ModuleActionsConfig['gate'] {
  if (!features) return undefined
  const entries = Object.entries(features) as Array<[ActionKey, string[]]>
  if (!entries.length) return undefined

  const licenseFeatures = getLicenseFeatureSet()
  return (action: ActionKey) => {
    const required = features[action]
    if (!required || required.length === 0) return true
    if (!licenseFeatures.size) return false
    return required.every(feature => licenseFeatures.has(feature))
  }
}

function getLicenseFeatureSet(): Set<string> {
  const bridge = getBridgeVars()
  if (!bridge) return new Set()
  const raw = bridge[LICENSE_FEATURES_KEY]
  return new Set(normaliseStringArray(raw))
}

function cloneActionList(list?: ActionKey[]): ActionKey[] | undefined {
  return list ? [...list] : undefined
}

function cloneFeatureMap(map?: ActionFeatureMap): ActionFeatureMap | undefined {
  if (!map) return undefined
  const clone: ActionFeatureMap = {}
  for (const [key, values] of Object.entries(map) as Array<[ActionKey, string[]]>) {
    clone[key] = [...values]
  }
  return clone
}

function normaliseActionList(value: unknown): ActionKey[] | undefined {
  if (!Array.isArray(value)) return undefined
  const unique = new Set<ActionKey>()
  for (const entry of value) {
    const normalised = typeof entry === 'string' ? entry.trim() : entry
    if (isActionKey(normalised)) {
      unique.add(normalised)
    }
  }
  return Array.from(unique)
}

function normaliseFeatureMap(value: unknown): ActionFeatureMap | undefined {
  if (!isPlainObject(value)) return undefined
  const map: ActionFeatureMap = {}
  for (const [key, raw] of Object.entries(value)) {
    if (!isActionKey(key)) continue
    const features = normaliseStringArray(raw)
    if (features.length) {
      map[key] = features
    }
  }
  return Object.keys(map).length ? map : undefined
}

function normaliseEndpoint(value: unknown): ((slug: string) => string) | undefined {
  if (typeof value !== 'string') return undefined
  const template = value.trim()
  if (!template) return undefined
  return (slug: string) =>
    template
      .replaceAll('{slug}', slug)
      .replaceAll(':slug', slug)
}

function normaliseStringArray(value: unknown): string[] {
  if (Array.isArray(value)) {
    return value
      .map(item => (typeof item === 'string' ? item.trim() : ''))
      .filter((item): item is string => item.length > 0)
  }
  if (typeof value === 'string') {
    const trimmed = value.trim()
    return trimmed ? [trimmed] : []
  }
  return []
}

function isPlainObject(value: unknown): value is Record<string, any> {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

function isActionKey(value: unknown): value is ActionKey {
  return typeof value === 'string' && ACTION_KEYS.includes(value as ActionKey)
}
