// src/Core/Design/helpers/resolveIcon.ts

/**
 * Liefert den vollständigen Pfad zum Icon innerhalb des Bookando-Plugins.
 * Hinweis: Nutzt KEIN import.meta.url, damit Build und WP kompatibel bleiben.
 * 
 * @param name Dateiname ohne ".svg", z. B. "user-plus"
 * @returns string – relativer URL-Pfad zur Icon-Datei
 */
export function resolveIcon(name: string): string {
  const base =
    (window as any).BOOKANDO_VARS?.iconBase ??
    '/wp-content/plugins/bookando/src/Core/Design/assets/icons/';
  return `${base}${name}.svg`;
}
