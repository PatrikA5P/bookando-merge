// src/Core/Design/helpers/resolveIcon.ts

/**
 * Liefert den vollständigen Pfad zum Icon innerhalb des Bookando-Plugins.
 * Hinweis: Nutzt KEIN import.meta.url, damit Build und WP kompatibel bleiben.
 * 
 * @param name Dateiname ohne ".svg", z. B. "user-plus"
 * @returns string – relativer URL-Pfad zur Icon-Datei
 */
export function resolveIcon(name: string): string {
  return `/wp-content/plugins/bookando/src/Core/Design/assets/icons/${name}.svg`
}
