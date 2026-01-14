export function isRestSuccess(payload: any): boolean {
  if (!payload || typeof payload !== 'object') {
    return false
  }

  if (payload.meta && typeof payload.meta === 'object' && 'success' in payload.meta) {
    return Boolean(payload.meta.success)
  }

  if ('success' in payload) {
    return Boolean(payload.success)
  }

  return false
}

export function extractRestData<T = any>(payload: any): T | null {
  if (!isRestSuccess(payload)) {
    return null
  }

  if ('data' in payload) {
    return payload.data as T
  }

  return null
}
