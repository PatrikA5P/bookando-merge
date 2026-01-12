// src/Core/Composables/useNotifier.ts
export type NoticeLevel = 'success' | 'info' | 'warning' | 'danger'
export type NoticePayload = { level: NoticeLevel; message: string; timeoutMs?: number; id: string }

export function notify(level: NoticeLevel, message: string, timeoutMs?: number) {
  const detail: NoticePayload = {
    level,
    message,
    id: Math.random().toString(36).slice(2),
  }
  if (typeof timeoutMs === 'number') detail.timeoutMs = timeoutMs
  window.dispatchEvent(new CustomEvent('bookando:notify', { detail }))
}

// optionale Helfer
export const notifySuccess = (msg: string, ms?: number) => notify('success', msg, ms)
export const notifyInfo    = (msg: string, ms?: number) => notify('info', msg, ms)
export const notifyWarn    = (msg: string, ms?: number) => notify('warning', msg, ms)
export const notifyError   = (msg: string, ms?: number) => notify('danger', msg, ms)
