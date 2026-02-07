/**
 * Design Store — Reaktives Design-Token-System
 *
 * Zentraler Pinia Store für alle Design-Einstellungen.
 * Module importieren Design-Tokens über diesen Store statt statisch,
 * damit Änderungen im Design System Modul sich live auf alle Module auswirken.
 *
 * Features:
 * - Reaktive Modul-Farben (Gradient, Accent, Active, Icon)
 * - Reaktive Typografie (Font Family, Sizes, Weights)
 * - Reaktive Spacing & Border Radius
 * - Reaktive Component Styles (Buttons, Inputs, Cards, etc.)
 * - Persistierung in localStorage
 * - Reset auf Defaults
 */
import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import {
  MODULE_DESIGNS, COLORS, TYPOGRAPHY, SPACING, BORDER_RADIUS, SHADOWS,
  LAYOUT, TRANSITIONS, Z_INDEX,
  type ModuleDesignConfig,
} from '@/design/tokens';
import {
  BUTTON_STYLES, INPUT_STYLES, LABEL_STYLES, CARD_STYLES,
  TABLE_STYLES, BADGE_STYLES, TAB_STYLES, MODAL_STYLES,
} from '@/design/components';

// ============================================================================
// TYPES
// ============================================================================

export interface DesignOverrides {
  /** Custom brand color (hex) — overrides the default brand palette */
  brandColor: string | null;
  /** Custom font family */
  fontFamily: string | null;
  /** Custom border radius base (px) */
  borderRadius: number | null;
  /** Module-specific gradient overrides */
  moduleGradients: Record<string, string>;
  /** Module-specific accent color overrides */
  moduleAccents: Record<string, string>;
  /** Dark mode enabled */
  darkMode: boolean;
  /** Compact mode (reduced spacing) */
  compactMode: boolean;
}

const STORAGE_KEY = 'bookando-design-overrides';

function loadFromStorage(): Partial<DesignOverrides> {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function saveToStorage(overrides: DesignOverrides) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(overrides));
  } catch {
    // Ignore storage errors
  }
}

// ============================================================================
// STORE
// ============================================================================

export const useDesignStore = defineStore('design', () => {
  const stored = loadFromStorage();

  // Overrides
  const overrides = ref<DesignOverrides>({
    brandColor: stored.brandColor ?? null,
    fontFamily: stored.fontFamily ?? null,
    borderRadius: stored.borderRadius ?? null,
    moduleGradients: stored.moduleGradients ?? {},
    moduleAccents: stored.moduleAccents ?? {},
    darkMode: stored.darkMode ?? false,
    compactMode: stored.compactMode ?? false,
  });

  // Persist on change
  watch(overrides, (val) => saveToStorage(val), { deep: true });

  // ========================================================================
  // COMPUTED: Module designs with overrides applied
  // ========================================================================

  /**
   * Returns module design config with any overrides applied.
   */
  function getModuleDesign(moduleName: string): ModuleDesignConfig {
    const normalized = moduleName.toLowerCase().replace(/[\s-]+/g, '');
    const base = MODULE_DESIGNS[normalized] || MODULE_DESIGNS.dashboard;

    const gradientOverride = overrides.value.moduleGradients[normalized];
    const accentOverride = overrides.value.moduleAccents[normalized];

    if (!gradientOverride && !accentOverride) return base;

    return {
      ...base,
      ...(gradientOverride ? { gradient: gradientOverride } : {}),
      ...(accentOverride ? {
        activeBg: `bg-${accentOverride}-50`,
        activeText: `text-${accentOverride}-700`,
        activeBorder: `border-${accentOverride}-200`,
        hoverBg: `hover:bg-${accentOverride}-50`,
        iconBg: `bg-${accentOverride}-100`,
        iconText: `text-${accentOverride}-600`,
        accentColor: `${accentOverride}-600`,
      } : {}),
    };
  }

  /**
   * All module names available.
   */
  const moduleNames = computed(() => Object.keys(MODULE_DESIGNS));

  // ========================================================================
  // COMPUTED: Typography with overrides
  // ========================================================================

  const fontFamily = computed(() => {
    return overrides.value.fontFamily || TYPOGRAPHY.fontFamily.sans;
  });

  const fontSize = computed(() => TYPOGRAPHY.fontSize);
  const fontWeight = computed(() => TYPOGRAPHY.fontWeight);

  // ========================================================================
  // COMPUTED: Spacing with overrides
  // ========================================================================

  const spacing = computed(() => {
    if (!overrides.value.compactMode) return SPACING;
    // Compact mode: reduce spacing by ~25%
    const compact: Record<string, string> = {};
    for (const [key, value] of Object.entries(SPACING)) {
      const numVal = parseFloat(value);
      compact[key] = `${(numVal * 0.75).toFixed(3)}rem`;
    }
    return compact as typeof SPACING;
  });

  const borderRadius = computed(() => {
    if (overrides.value.borderRadius === null) return BORDER_RADIUS;
    const base = overrides.value.borderRadius;
    return {
      none: '0',
      sm: `${Math.max(0, base * 0.5)}px`,
      md: `${base * 0.67}px`,
      lg: `${base}px`,
      xl: `${base * 1.33}px`,
      '2xl': `${base * 2}px`,
      full: '9999px',
    };
  });

  // ========================================================================
  // COMPUTED: Colors
  // ========================================================================

  const colors = computed(() => COLORS);
  const shadows = computed(() => SHADOWS);

  // ========================================================================
  // COMPUTED: Component styles (static for now, reactive via computed)
  // ========================================================================

  const buttonStyles = computed(() => BUTTON_STYLES);
  const inputStyles = computed(() => INPUT_STYLES);
  const labelStyles = computed(() => LABEL_STYLES);
  const cardStyles = computed(() => CARD_STYLES);
  const tableStyles = computed(() => TABLE_STYLES);
  const badgeStyles = computed(() => BADGE_STYLES);
  const tabStyles = computed(() => TAB_STYLES);
  const modalStyles = computed(() => MODAL_STYLES);

  // ========================================================================
  // CSS Custom Properties — inject into :root for global effect
  // ========================================================================

  const cssVariables = computed(() => {
    const vars: Record<string, string> = {};

    if (overrides.value.fontFamily) {
      vars['--bookando-font'] = `'${overrides.value.fontFamily}', sans-serif`;
    }
    if (overrides.value.brandColor) {
      vars['--bookando-brand'] = overrides.value.brandColor;
    }
    if (overrides.value.borderRadius !== null) {
      vars['--bookando-radius'] = `${overrides.value.borderRadius}px`;
    }

    return vars;
  });

  /**
   * Apply CSS variables to document root.
   */
  function applyCssVariables() {
    const root = document.documentElement;
    for (const [key, value] of Object.entries(cssVariables.value)) {
      root.style.setProperty(key, value);
    }

    // Font family override
    if (overrides.value.fontFamily) {
      root.style.setProperty('font-family', `'${overrides.value.fontFamily}', sans-serif`);
    }
  }

  // Watch and apply
  watch(cssVariables, () => applyCssVariables(), { immediate: true });

  // ========================================================================
  // ACTIONS
  // ========================================================================

  function setModuleGradient(moduleName: string, gradient: string) {
    const normalized = moduleName.toLowerCase().replace(/[\s-]+/g, '');
    overrides.value.moduleGradients[normalized] = gradient;
  }

  function setModuleAccent(moduleName: string, accent: string) {
    const normalized = moduleName.toLowerCase().replace(/[\s-]+/g, '');
    overrides.value.moduleAccents[normalized] = accent;
  }

  function setBrandColor(color: string | null) {
    overrides.value.brandColor = color;
  }

  function setFontFamily(family: string | null) {
    overrides.value.fontFamily = family;
  }

  function setBorderRadius(radius: number | null) {
    overrides.value.borderRadius = radius;
  }

  function setCompactMode(compact: boolean) {
    overrides.value.compactMode = compact;
  }

  function setDarkMode(dark: boolean) {
    overrides.value.darkMode = dark;
  }

  function resetToDefaults() {
    overrides.value = {
      brandColor: null,
      fontFamily: null,
      borderRadius: null,
      moduleGradients: {},
      moduleAccents: {},
      darkMode: false,
      compactMode: false,
    };
    // Remove CSS overrides
    const root = document.documentElement;
    root.style.removeProperty('--bookando-font');
    root.style.removeProperty('--bookando-brand');
    root.style.removeProperty('--bookando-radius');
    root.style.removeProperty('font-family');
  }

  return {
    // State
    overrides,

    // Module designs
    getModuleDesign,
    moduleNames,

    // Typography
    fontFamily,
    fontSize,
    fontWeight,

    // Layout
    spacing,
    borderRadius,
    colors,
    shadows,

    // Component styles
    buttonStyles,
    inputStyles,
    labelStyles,
    cardStyles,
    tableStyles,
    badgeStyles,
    tabStyles,
    modalStyles,

    // CSS
    cssVariables,
    applyCssVariables,

    // Actions
    setModuleGradient,
    setModuleAccent,
    setBrandColor,
    setFontFamily,
    setBorderRadius,
    setCompactMode,
    setDarkMode,
    resetToDefaults,
  };
});
