/**
 * BOOKANDO i18n — Zentrales Übersetzungssystem
 *
 * Typsicheres, reaktives Übersetzungssystem.
 * Alle Strings kommen aus den Locale-Dateien, NIE hardcoded.
 *
 * Verwendung:
 *   const { t, locale, setLocale } = useI18n();
 *   t('common.save')           // → 'Speichern'
 *   t('customers.title')       // → 'Kunden'
 *   t('common.showing', { from: 1, to: 25, total: 100 })
 */
import { ref, computed, type Ref } from 'vue';

export type Locale = 'de' | 'en' | 'fr' | 'it';

const currentLocale = ref<Locale>('de');

// Alle Übersetzungen werden lazy geladen
const messages: Record<Locale, Record<string, unknown>> = {
  de: {},
  en: {},
  fr: {},
  it: {},
};

let loaded: Partial<Record<Locale, boolean>> = {};

async function loadLocale(locale: Locale): Promise<void> {
  if (loaded[locale]) return;
  try {
    const common = await import(`@/locales/${locale}/common.ts`);
    const modules = await import(`@/locales/${locale}/modules.ts`);
    messages[locale] = {
      common: common.default,
      ...modules.default,
    };
    loaded[locale] = true;
  } catch {
    console.warn(`[i18n] Failed to load locale: ${locale}`);
  }
}

// Pre-load default locale
loadLocale('de');

function resolve(obj: Record<string, unknown>, path: string): string {
  const keys = path.split('.');
  let current: unknown = obj;
  for (const key of keys) {
    if (current && typeof current === 'object' && key in (current as Record<string, unknown>)) {
      current = (current as Record<string, unknown>)[key];
    } else {
      return path; // Fallback: return key path
    }
  }
  return typeof current === 'string' ? current : path;
}

function interpolate(template: string, params?: Record<string, string | number>): string {
  if (!params) return template;
  return template.replace(/\{(\w+)\}/g, (_, key) => {
    return params[key] !== undefined ? String(params[key]) : `{${key}}`;
  });
}

export function useI18n() {
  const locale = currentLocale;

  function t(key: string, params?: Record<string, string | number>): string {
    const msg = messages[locale.value];
    const result = resolve(msg as Record<string, unknown>, key);
    return interpolate(result, params);
  }

  async function setLocale(newLocale: Locale) {
    await loadLocale(newLocale);
    currentLocale.value = newLocale;
  }

  const availableLocales: Locale[] = ['de', 'en', 'fr', 'it'];
  const localeLabels: Record<Locale, string> = {
    de: 'Deutsch',
    en: 'English',
    fr: 'Français',
    it: 'Italiano',
  };

  return {
    t,
    locale,
    setLocale,
    availableLocales,
    localeLabels,
  };
}
