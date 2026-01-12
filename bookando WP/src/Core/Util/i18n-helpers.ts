// src/Core/Util/i18n-helpers.ts
export function humanizeFieldKey(key: string): string {
  const spaced = key.replace(/_/g, ' ').replace(/([a-z0-9])([A-Z])/g, '$1 $2')
  return spaced.charAt(0).toUpperCase() + spaced.slice(1)
}
export function fieldLabel(t: (k: string)=>string, fieldKey: string, moduleSlug?: string): string {
  if (moduleSlug) {
    const k = `fields.${moduleSlug}.${fieldKey}`; if (t(k) !== k) return t(k)
  }
  const globalKey = `fields.${fieldKey}`; if (t(globalKey) !== globalKey) return t(globalKey)
  return humanizeFieldKey(fieldKey)
}
