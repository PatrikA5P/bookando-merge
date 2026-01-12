export interface BookandoBridgeConfig {
  rest_root?: string
  rest_url?: string
  rest_url_base?: string
  rest_nonce?: string
  lang?: string
  origin?: string
  license_features?: string[]
  module_actions?: {
    allowed?: string[]
    endpoint?: string
    features?: Record<string, string | string[]>
  }
  [key: string]: unknown
}

export interface WordPressApiSettings {
  root?: string
  nonce?: string
  [key: string]: unknown
}

declare global {
  interface Window {
    BOOKANDO_VARS?: BookandoBridgeConfig
    wpApiSettings?: WordPressApiSettings
    BOOKANDO_CLOUD_API?: string
    BOOKANDO_TENANT?: string
    BOOKANDO_TOKEN?: string
  }
}

export {}
