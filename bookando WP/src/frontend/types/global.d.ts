interface BookandoVars {
  rest_url?: string
  nonce?: string
  [key: string]: unknown
}

declare global {
  interface Window {
    BOOKANDO_VARS?: BookandoVars
    Sentry?: {
      captureException?: (error: unknown, context?: Record<string, unknown>) => void
      captureMessage?: (message: string, context?: Record<string, unknown>) => void
    }
  }
}

export {}
