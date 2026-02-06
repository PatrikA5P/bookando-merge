/**
 * BOOKANDO DESIGN TOKENS
 *
 * Zentrales Design-System für alle Module.
 * Basierend auf der Referenz-Applikation, erweitert um:
 * - Vollständiges Responsive-System (Mobile/Tablet/Desktop)
 * - Zugänglichkeits-Tokens (a11y)
 * - Dark-Mode-Vorbereitung
 * - Animations-Tokens
 */

// ============================================================================
// BREAKPOINTS
// ============================================================================

export const BREAKPOINTS = {
  /** Mobile Portrait: 0–479px */
  xs: 0,
  /** Mobile Landscape: 480–639px */
  sm: 480,
  /** Tablet Portrait: 640–767px */
  md: 640,
  /** Tablet Landscape: 768–1023px */
  lg: 768,
  /** Desktop: 1024–1279px */
  xl: 1024,
  /** Large Desktop: 1280px+ */
  '2xl': 1280,
} as const;

export const BREAKPOINT_QUERIES = {
  xs: '(max-width: 479px)',
  sm: '(min-width: 480px) and (max-width: 639px)',
  md: '(min-width: 640px) and (max-width: 767px)',
  lg: '(min-width: 768px) and (max-width: 1023px)',
  xl: '(min-width: 1024px) and (max-width: 1279px)',
  '2xl': '(min-width: 1280px)',
  mobile: '(max-width: 639px)',
  tablet: '(min-width: 640px) and (max-width: 1023px)',
  desktop: '(min-width: 1024px)',
  touch: '(hover: none) and (pointer: coarse)',
  landscape: '(orientation: landscape)',
  portrait: '(orientation: portrait)',
} as const;

// ============================================================================
// FARBEN
// ============================================================================

export const COLORS = {
  brand: {
    50: '#eff6ff',
    100: '#dbeafe',
    200: '#bfdbfe',
    300: '#93c5fd',
    400: '#60a5fa',
    500: '#3b82f6',
    600: '#2563eb',
    700: '#1d4ed8',
    800: '#1e40af',
    900: '#1e3a8a',
    950: '#172554',
  },
  slate: {
    50: '#f8fafc',
    100: '#f1f5f9',
    200: '#e2e8f0',
    300: '#cbd5e1',
    400: '#94a3b8',
    500: '#64748b',
    600: '#475569',
    700: '#334155',
    800: '#1e293b',
    900: '#0f172a',
    950: '#020617',
  },
  semantic: {
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#3b82f6',
  },
} as const;

// ============================================================================
// MODUL-SPEZIFISCHE DESIGN-TOKENS
// ============================================================================

export interface ModuleDesignConfig {
  /** CSS-Klassen für den Hero-Gradient */
  gradient: string;
  /** Primäre Akzentfarbe (Tailwind) */
  accentColor: string;
  /** Hintergrund für aktive Elemente */
  activeBg: string;
  /** Text für aktive Elemente */
  activeText: string;
  /** Border für aktive Elemente */
  activeBorder: string;
  /** Hover-Hintergrund */
  hoverBg: string;
  /** Icon-Hintergrundfarbe (für Sidebar/Navigation) */
  iconBg: string;
  /** Icon-Textfarbe */
  iconText: string;
}

export const MODULE_DESIGNS: Record<string, ModuleDesignConfig> = {
  dashboard: {
    gradient: 'from-brand-700 to-brand-900',
    accentColor: 'brand-600',
    activeBg: 'bg-brand-50',
    activeText: 'text-brand-700',
    activeBorder: 'border-brand-200',
    hoverBg: 'hover:bg-brand-50',
    iconBg: 'bg-brand-100',
    iconText: 'text-brand-600',
  },
  appointments: {
    gradient: 'from-brand-700 to-brand-900',
    accentColor: 'brand-600',
    activeBg: 'bg-brand-50',
    activeText: 'text-brand-700',
    activeBorder: 'border-brand-200',
    hoverBg: 'hover:bg-brand-50',
    iconBg: 'bg-brand-100',
    iconText: 'text-brand-600',
  },
  customers: {
    gradient: 'from-emerald-700 to-teal-900',
    accentColor: 'emerald-600',
    activeBg: 'bg-emerald-50',
    activeText: 'text-emerald-700',
    activeBorder: 'border-emerald-200',
    hoverBg: 'hover:bg-emerald-50',
    iconBg: 'bg-emerald-100',
    iconText: 'text-emerald-600',
  },
  employees: {
    gradient: 'from-slate-700 to-slate-900',
    accentColor: 'slate-700',
    activeBg: 'bg-slate-100',
    activeText: 'text-slate-900',
    activeBorder: 'border-slate-300',
    hoverBg: 'hover:bg-slate-100',
    iconBg: 'bg-slate-200',
    iconText: 'text-slate-700',
  },
  workday: {
    gradient: 'from-amber-600 to-orange-800',
    accentColor: 'amber-600',
    activeBg: 'bg-amber-50',
    activeText: 'text-amber-700',
    activeBorder: 'border-amber-200',
    hoverBg: 'hover:bg-amber-50',
    iconBg: 'bg-amber-100',
    iconText: 'text-amber-600',
  },
  finance: {
    gradient: 'from-purple-700 to-violet-900',
    accentColor: 'purple-600',
    activeBg: 'bg-purple-50',
    activeText: 'text-purple-700',
    activeBorder: 'border-purple-200',
    hoverBg: 'hover:bg-purple-50',
    iconBg: 'bg-purple-100',
    iconText: 'text-purple-600',
  },
  offers: {
    gradient: 'from-blue-700 to-sky-900',
    accentColor: 'blue-600',
    activeBg: 'bg-blue-50',
    activeText: 'text-blue-700',
    activeBorder: 'border-blue-200',
    hoverBg: 'hover:bg-blue-50',
    iconBg: 'bg-blue-100',
    iconText: 'text-blue-600',
  },
  academy: {
    gradient: 'from-rose-700 to-pink-900',
    accentColor: 'rose-600',
    activeBg: 'bg-rose-50',
    activeText: 'text-rose-700',
    activeBorder: 'border-rose-200',
    hoverBg: 'hover:bg-rose-50',
    iconBg: 'bg-rose-100',
    iconText: 'text-rose-600',
  },
  resources: {
    gradient: 'from-cyan-700 to-teal-900',
    accentColor: 'cyan-600',
    activeBg: 'bg-cyan-50',
    activeText: 'text-cyan-700',
    activeBorder: 'border-cyan-200',
    hoverBg: 'hover:bg-cyan-50',
    iconBg: 'bg-cyan-100',
    iconText: 'text-cyan-600',
  },
  partnerhub: {
    gradient: 'from-indigo-700 to-indigo-950',
    accentColor: 'indigo-600',
    activeBg: 'bg-indigo-50',
    activeText: 'text-indigo-700',
    activeBorder: 'border-indigo-200',
    hoverBg: 'hover:bg-indigo-50',
    iconBg: 'bg-indigo-100',
    iconText: 'text-indigo-600',
  },
  tools: {
    gradient: 'from-fuchsia-700 to-purple-900',
    accentColor: 'fuchsia-600',
    activeBg: 'bg-fuchsia-50',
    activeText: 'text-fuchsia-700',
    activeBorder: 'border-fuchsia-200',
    hoverBg: 'hover:bg-fuchsia-50',
    iconBg: 'bg-fuchsia-100',
    iconText: 'text-fuchsia-600',
  },
  settings: {
    gradient: 'from-slate-800 to-slate-950',
    accentColor: 'slate-700',
    activeBg: 'bg-slate-100',
    activeText: 'text-slate-900',
    activeBorder: 'border-slate-300',
    hoverBg: 'hover:bg-slate-100',
    iconBg: 'bg-slate-200',
    iconText: 'text-slate-700',
  },
  'design-system': {
    gradient: 'from-fuchsia-700 to-purple-900',
    accentColor: 'fuchsia-600',
    activeBg: 'bg-fuchsia-50',
    activeText: 'text-fuchsia-700',
    activeBorder: 'border-fuchsia-200',
    hoverBg: 'hover:bg-fuchsia-50',
    iconBg: 'bg-fuchsia-100',
    iconText: 'text-fuchsia-600',
  },
  'design-frontend': {
    gradient: 'from-violet-700 to-purple-900',
    accentColor: 'violet-600',
    activeBg: 'bg-violet-50',
    activeText: 'text-violet-700',
    activeBorder: 'border-violet-200',
    hoverBg: 'hover:bg-violet-50',
    iconBg: 'bg-violet-100',
    iconText: 'text-violet-600',
  },
};

// ============================================================================
// TYPOGRAFIE
// ============================================================================

export const TYPOGRAPHY = {
  fontFamily: {
    sans: "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
    mono: "'JetBrains Mono', 'Fira Code', 'Consolas', monospace",
  },
  fontSize: {
    xs: ['0.75rem', { lineHeight: '1rem' }],       // 12px
    sm: ['0.875rem', { lineHeight: '1.25rem' }],    // 14px
    base: ['1rem', { lineHeight: '1.5rem' }],       // 16px
    lg: ['1.125rem', { lineHeight: '1.75rem' }],    // 18px
    xl: ['1.25rem', { lineHeight: '1.75rem' }],     // 20px
    '2xl': ['1.5rem', { lineHeight: '2rem' }],      // 24px
    '3xl': ['1.875rem', { lineHeight: '2.25rem' }], // 30px
    '4xl': ['2.25rem', { lineHeight: '2.5rem' }],   // 36px
  },
  fontWeight: {
    normal: '400',
    medium: '500',
    semibold: '600',
    bold: '700',
    extrabold: '800',
  },
} as const;

// ============================================================================
// ABSTÄNDE & DIMENSIONEN
// ============================================================================

export const SPACING = {
  xs: '0.25rem',   // 4px
  sm: '0.5rem',    // 8px
  md: '0.75rem',   // 12px
  lg: '1rem',      // 16px
  xl: '1.5rem',    // 24px
  '2xl': '2rem',   // 32px
  '3xl': '3rem',   // 48px
  '4xl': '4rem',   // 64px
} as const;

export const BORDER_RADIUS = {
  none: '0',
  sm: '0.375rem',  // 6px
  md: '0.5rem',    // 8px
  lg: '0.75rem',   // 12px
  xl: '1rem',      // 16px
  '2xl': '1.5rem', // 24px
  full: '9999px',
} as const;

export const SHADOWS = {
  none: 'none',
  sm: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
  md: '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
  lg: '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
  xl: '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
  '2xl': '0 25px 50px -12px rgb(0 0 0 / 0.25)',
} as const;

// ============================================================================
// ANIMATIONEN & ÜBERGÄNGE
// ============================================================================

export const TRANSITIONS = {
  fast: '150ms ease-in-out',
  normal: '200ms ease-in-out',
  slow: '300ms ease-in-out',
  spring: '500ms cubic-bezier(0.175, 0.885, 0.32, 1.275)',
} as const;

export const ANIMATIONS = {
  fadeIn: 'animate-fade-in',
  fadeOut: 'animate-fade-out',
  slideUp: 'animate-slide-up',
  slideDown: 'animate-slide-down',
  slideLeft: 'animate-slide-left',
  slideRight: 'animate-slide-right',
  scaleIn: 'animate-scale-in',
  spin: 'animate-spin',
  pulse: 'animate-pulse',
  bounce: 'animate-bounce',
} as const;

// ============================================================================
// Z-INDEX SKALA
// ============================================================================

export const Z_INDEX = {
  base: 0,
  dropdown: 10,
  sticky: 20,
  overlay: 30,
  modal: 40,
  popover: 50,
  toast: 60,
  tooltip: 70,
  max: 100,
} as const;

// ============================================================================
// LAYOUT-DIMENSIONEN
// ============================================================================

export const LAYOUT = {
  sidebar: {
    width: '280px',
    collapsedWidth: '72px',
  },
  header: {
    height: '64px',
    mobileHeight: '56px',
  },
  content: {
    maxWidth: '1440px',
    padding: {
      mobile: '1rem',
      tablet: '1.5rem',
      desktop: '2rem',
    },
  },
  modal: {
    sm: '28rem',   // 448px
    md: '32rem',   // 512px
    lg: '42rem',   // 672px
    xl: '56rem',   // 896px
    full: '100%',
  },
} as const;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Holt die Design-Konfiguration für ein Modul.
 */
export function getModuleDesign(moduleName: string): ModuleDesignConfig {
  const normalized = moduleName.toLowerCase().replace(/[\s-]+/g, '');
  return MODULE_DESIGNS[normalized] || MODULE_DESIGNS.dashboard;
}

/**
 * Prüft ob ein Modul-Design existiert.
 */
export function moduleDesignExists(moduleName: string): boolean {
  const normalized = moduleName.toLowerCase().replace(/[\s-]+/g, '');
  return normalized in MODULE_DESIGNS;
}

/**
 * Gibt alle verfügbaren Modul-Namen zurück.
 */
export function getModuleNames(): string[] {
  return Object.keys(MODULE_DESIGNS);
}
