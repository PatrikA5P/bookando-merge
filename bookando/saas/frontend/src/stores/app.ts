/**
 * App Store — Globaler Applikationszustand
 *
 * Enthält nur anwendungsweite Einstellungen:
 * - Sprache
 * - Sidebar-Zustand
 * - Aktive Module
 * - Theme-Einstellungen
 *
 * Verbesserung gegenüber Referenz:
 * Die Referenz hatte ALLES in einem monolithischen AppContext (1112 Zeilen).
 * Hier: Separater Store pro Fachbereich (auth, app, customers, appointments, etc.)
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export type Language = 'de' | 'en' | 'fr' | 'it';
export type Currency = 'CHF' | 'EUR' | 'USD';

export const useAppStore = defineStore('app', () => {
  // Sprache & Lokalisierung
  const language = ref<Language>('de');
  const currency = ref<Currency>('CHF');
  const timezone = ref('Europe/Zurich');
  const dateFormat = ref('dd.MM.yyyy');

  // UI-Zustand
  const sidebarCollapsed = ref(false);
  const isMobileMenuOpen = ref(false);

  // Aktive Module (konfigurierbar pro Tenant)
  const enabledModules = ref<string[]>([
    'dashboard', 'appointments', 'customers', 'employees',
    'workday', 'finance', 'offers', 'academy',
    'resources', 'settings', 'tools', 'partnerhub',
  ]);

  // Helper
  const isModuleEnabled = computed(() => {
    return (module: string) => enabledModules.value.includes(module);
  });

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
  }

  function setLanguage(lang: Language) {
    language.value = lang;
  }

  function setCurrency(curr: Currency) {
    currency.value = curr;
  }

  /**
   * Formatiert einen Preis gemäss aktueller Währung.
   * Beträge sind in Rappen/Cents (Minor Units) gespeichert.
   */
  function formatPrice(amountMinor: number): string {
    const amount = amountMinor / 100;
    return new Intl.NumberFormat(language.value === 'de' ? 'de-CH' : language.value, {
      style: 'currency',
      currency: currency.value,
    }).format(amount);
  }

  /**
   * Formatiert ein Datum gemäss aktuellem Datumsformat.
   */
  function formatDate(date: Date | string): string {
    const d = typeof date === 'string' ? new Date(date) : date;
    return new Intl.DateTimeFormat(language.value === 'de' ? 'de-CH' : language.value, {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).format(d);
  }

  return {
    language,
    currency,
    timezone,
    dateFormat,
    sidebarCollapsed,
    isMobileMenuOpen,
    enabledModules,
    isModuleEnabled,
    toggleSidebar,
    setLanguage,
    setCurrency,
    formatPrice,
    formatDate,
  };
});
