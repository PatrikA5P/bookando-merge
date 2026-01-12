/**
 * Standardisierte API-Response-Typen für Bookando
 *
 * Diese Typen definieren die Struktur von Backend-Responses.
 * Der HTTP-Client (client.ts) unwrappt automatisch das Response::ok() Format.
 */

/**
 * Standard-Response-Format von Response::ok()
 *
 * BACKEND sendet: { data: {...}, meta: { success: true } }
 * FRONTEND empfängt nach Unwrapping: {...} (nur data-Inhalt)
 */
export interface BookandoApiResponse<T = any> {
  /** Die eigentlichen Nutzdaten */
  data: T
  /** Metadaten über die Response */
  meta: BookandoApiMeta
}

/**
 * Metadaten für API-Responses
 */
export interface BookandoApiMeta {
  /** Erfolgs-Status */
  success: boolean
  /** HTTP-Status-Code (bei Errors) */
  status?: number
  /** Weitere Meta-Informationen */
  [key: string]: any
}

/**
 * Error-Response von Response::error()
 */
export interface BookandoApiError {
  /** null bei Errors */
  data: null
  /** Error-Details */
  error: {
    /** Error-Code (z.B. 'not_found', 'validation_error') */
    code: string
    /** Menschenlesbare Error-Message */
    message: string
    /** Zusätzliche Error-Details (optional) */
    details?: any
  }
  /** Meta mit success: false */
  meta: BookandoApiMeta & { success: false }
}

/**
 * Paginated List Response
 *
 * Wird von listCustomers(), listEmployees() etc. zurückgegeben
 */
export interface BookandoPaginatedResponse<T = any> {
  /** Array der Ergebnisse */
  data: T[]
  /** Gesamtanzahl (über alle Seiten) */
  total: number
  /** Aktuelle Seitengröße */
  limit: number
  /** Aktueller Offset */
  offset: number
}

/**
 * Custom Fields werden dynamisch aus Systemeinstellungen geladen
 * und sind nicht fest in der DB-Struktur definiert
 */
export type CustomFields = Record<string, any>

/**
 * Form Rules: Validierungsregeln für Formulare
 * Definiert in Settings, nicht in der DB
 */
export interface FormRules {
  required?: boolean
  min?: number
  max?: number
  pattern?: string
  [key: string]: any
}
