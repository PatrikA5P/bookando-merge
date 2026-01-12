// src/Core/Locale/messages.ts
export type I18nMessages = Record<string, any>

type LocalModuleLike = {
  messages?: I18nMessages
  default?: { messages?: I18nMessages }
  [key: string]: unknown
}

type LocalSource =
  | I18nMessages
  | Record<string, LocalModuleLike>
  | undefined
  | null

function isRecord(value: unknown): value is Record<string, any> {
  return !!value && typeof value === 'object'
}

function isModuleMap(value: LocalSource): value is Record<string, LocalModuleLike> {
  if (!isRecord(value)) {
    return false
  }

  return Object.values(value).some((entry) => {
    if (!isRecord(entry)) {
      return false
    }

    if ('messages' in entry && isRecord((entry as LocalModuleLike).messages)) {
      return true
    }

    const defaultExport = (entry as LocalModuleLike).default
    return isRecord(defaultExport) && 'messages' in defaultExport
  })
}

function resolveLocalMessages(source: LocalSource): I18nMessages | undefined {
  if (!source) {
    return undefined
  }

  if (!isModuleMap(source)) {
    return source as I18nMessages
  }

  for (const entry of Object.values(source)) {
    if (isRecord(entry?.messages)) {
      return entry.messages as I18nMessages
    }

    const defaultExport = entry?.default
    if (isRecord(defaultExport) && isRecord(defaultExport.messages)) {
      return defaultExport.messages as I18nMessages
    }
  }

  return undefined
}

export function deepMerge<T extends Record<string, any>>(target: T, source: Record<string, any>): T {
  for (const key of Object.keys(source)) {
    const incoming = source[key]
    const current = (target as any)[key]

    if (isRecord(incoming) && !Array.isArray(incoming)) {
      const base = isRecord(current) && !Array.isArray(current) ? current : {}
      ;(target as any)[key] = deepMerge({ ...base }, incoming)
    } else if (Array.isArray(incoming)) {
      ;(target as any)[key] = [...incoming]
    } else {
      ;(target as any)[key] = incoming
    }
  }

  return target
}

export function mergeI18nMessages(
  coreMessages: I18nMessages,
  localSource?: LocalSource,
): I18nMessages {
  const localMessages = resolveLocalMessages(localSource)
  const languages = new Set([
    ...Object.keys(coreMessages || {}),
    ...Object.keys(localMessages || {}),
  ])

  const merged: I18nMessages = {}

  for (const lang of languages) {
    const base = deepMerge({}, (coreMessages?.[lang] as Record<string, any>) || {})
    const withLocal = localMessages?.[lang]
      ? deepMerge(base, localMessages[lang] as Record<string, any>)
      : base

    merged[lang] = withLocal
  }

  return merged
}

export { resolveLocalMessages as _resolveLocalMessages }
