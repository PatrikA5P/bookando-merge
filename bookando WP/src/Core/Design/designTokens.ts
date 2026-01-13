/**
 * BOOKANDO DESIGN TOKENS
 *
 * Zentrale Design-Tokens portiert von "bookando Design" React Repo
 * Verwendet für konsistentes Design über alle Module hinweg
 *
 * @see bookando Design/utils/designTokens.ts (original source)
 */

// ============================================================================
// MODUL-SPEZIFISCHE DESIGN TOKENS
// ============================================================================

export interface ModuleDesignConfig {
  /** Gradient für den Hero-Bereich (Tailwind classes) */
  gradient: string;
  /** Primärfarbe für Akzente (Tailwind color) */
  accentColor: string;
  /** Hintergrundfarbe für aktive Tabs/Buttons */
  activeBg: string;
  /** Textfarbe für aktive Elemente */
  activeText: string;
  /** Border-Farbe für aktive Elemente */
  activeBorder: string;
  /** Hover-Hintergrundfarbe */
  hoverBg: string;
}

export const MODULE_DESIGNS: Record<string, ModuleDesignConfig> = {
  // Dashboard - Brand Blue
  dashboard: {
    gradient: 'from-brand-700 to-brand-900',
    accentColor: 'brand-600',
    activeBg: 'bg-brand-50',
    activeText: 'text-brand-700',
    activeBorder: 'border-brand-200',
    hoverBg: 'hover:bg-brand-50'
  },

  // Customers - Emerald/Green (Community, Growth)
  customers: {
    gradient: 'from-emerald-700 to-teal-900',
    accentColor: 'emerald-600',
    activeBg: 'bg-emerald-50',
    activeText: 'text-emerald-700',
    activeBorder: 'border-emerald-200',
    hoverBg: 'hover:bg-emerald-50'
  },

  // Employees - Slate/Professional
  employees: {
    gradient: 'from-slate-700 to-slate-900',
    accentColor: 'slate-700',
    activeBg: 'bg-slate-100',
    activeText: 'text-slate-900',
    activeBorder: 'border-slate-300',
    hoverBg: 'hover:bg-slate-100'
  },

  // Workday - Orange/Amber (Time, Energy)
  workday: {
    gradient: 'from-amber-600 to-orange-800',
    accentColor: 'amber-600',
    activeBg: 'bg-amber-50',
    activeText: 'text-amber-700',
    activeBorder: 'border-amber-200',
    hoverBg: 'hover:bg-amber-50'
  },

  // Finance - Purple/Violet (Premium, Value)
  finance: {
    gradient: 'from-purple-700 to-violet-900',
    accentColor: 'purple-600',
    activeBg: 'bg-purple-50',
    activeText: 'text-purple-700',
    activeBorder: 'border-purple-200',
    hoverBg: 'hover:bg-purple-50'
  },

  // Offers - Blue (Services, Catalog)
  offers: {
    gradient: 'from-blue-700 to-sky-900',
    accentColor: 'blue-600',
    activeBg: 'bg-blue-50',
    activeText: 'text-blue-700',
    activeBorder: 'border-blue-200',
    hoverBg: 'hover:bg-blue-50'
  },

  // Academy - Rose/Pink (Education, Learning)
  academy: {
    gradient: 'from-rose-700 to-pink-900',
    accentColor: 'rose-600',
    activeBg: 'bg-rose-50',
    activeText: 'text-rose-700',
    activeBorder: 'border-rose-200',
    hoverBg: 'hover:bg-rose-50'
  },

  // Resources - Cyan/Teal (Assets, Infrastructure)
  resources: {
    gradient: 'from-cyan-700 to-teal-900',
    accentColor: 'cyan-600',
    activeBg: 'bg-cyan-50',
    activeText: 'text-cyan-700',
    activeBorder: 'border-cyan-200',
    hoverBg: 'hover:bg-cyan-50'
  },

  // Partner Hub - Indigo (Network, Connections)
  partnerhub: {
    gradient: 'from-indigo-700 to-indigo-950',
    accentColor: 'indigo-600',
    activeBg: 'bg-indigo-50',
    activeText: 'text-indigo-700',
    activeBorder: 'border-indigo-200',
    hoverBg: 'hover:bg-indigo-50'
  },

  // Tools - Fuchsia/Purple (Utilities, Power)
  tools: {
    gradient: 'from-fuchsia-700 to-purple-900',
    accentColor: 'fuchsia-600',
    activeBg: 'bg-fuchsia-50',
    activeText: 'text-fuchsia-700',
    activeBorder: 'border-fuchsia-200',
    hoverBg: 'hover:bg-fuchsia-50'
  },

  // Settings - Slate/Gray (Configuration, System)
  settings: {
    gradient: 'from-slate-800 to-slate-950',
    accentColor: 'slate-700',
    activeBg: 'bg-slate-100',
    activeText: 'text-slate-900',
    activeBorder: 'border-slate-300',
    hoverBg: 'hover:bg-slate-100'
  },
};

// ============================================================================
// GEMEINSAME DESIGN TOKENS
// ============================================================================

export const SPACING = {
  xs: '0.25rem',    // 4px
  sm: '0.5rem',     // 8px
  md: '0.75rem',    // 12px
  lg: '1rem',       // 16px
  xl: '1.5rem',     // 24px
  '2xl': '2rem',    // 32px
  '3xl': '3rem',    // 48px
  '4xl': '4rem',    // 64px
} as const;

export const BORDER_RADIUS = {
  none: '0',
  sm: '0.375rem',   // 6px
  md: '0.5rem',     // 8px
  lg: '0.75rem',    // 12px
  xl: '1rem',       // 16px
  '2xl': '1.5rem',  // 24px
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

export const FONT_SIZES = {
  xs: '0.75rem',    // 12px
  sm: '0.875rem',   // 14px
  base: '1rem',     // 16px
  lg: '1.125rem',   // 18px
  xl: '1.25rem',    // 20px
  '2xl': '1.5rem',  // 24px
  '3xl': '1.875rem',// 30px
  '4xl': '2.25rem', // 36px
} as const;

export const FONT_WEIGHTS = {
  normal: '400',
  medium: '500',
  semibold: '600',
  bold: '700',
  extrabold: '800',
} as const;

// ============================================================================
// WIEDERVERWENDBARE CSS-KLASSEN ALS STRINGS
// ============================================================================

export const BUTTON_STYLES = {
  primary: 'bg-brand-600 hover:bg-brand-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors',
  secondary: 'bg-white hover:bg-slate-50 text-slate-700 px-5 py-2.5 rounded-xl text-sm font-medium border border-slate-200 shadow-sm transition-colors',
  danger: 'bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-colors',
  ghost: 'bg-transparent hover:bg-slate-100 text-slate-700 px-5 py-2.5 rounded-xl text-sm font-medium transition-colors',
  icon: 'p-2 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors',
} as const;

export const INPUT_STYLES = {
  base: 'w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all',
  error: 'w-full px-3 py-2 border border-red-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all',
  search: 'w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all',
} as const;

export const CARD_STYLES = {
  default: 'bg-white rounded-xl border border-slate-200 shadow-sm p-6',
  hover: 'bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-lg transition-all duration-300',
  interactive: 'bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-lg hover:border-brand-200 transition-all duration-300 cursor-pointer',
} as const;

export const BADGE_STYLES = {
  default: 'px-2 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700',
  success: 'px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700',
  warning: 'px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700',
  danger: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700',
  info: 'px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
  brand: 'px-2 py-1 rounded-full text-xs font-medium bg-brand-100 text-brand-700',
} as const;

export const TABLE_STYLES = {
  container: 'w-full overflow-hidden rounded-xl border border-slate-200 shadow-sm',
  thead: 'bg-slate-50 border-b border-slate-200',
  th: 'px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider',
  tbody: 'bg-white divide-y divide-slate-200',
  tr: 'hover:bg-slate-50 transition-colors',
  td: 'px-6 py-4 text-sm text-slate-900',
} as const;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get module-specific design configuration
 */
export function getModuleDesign(moduleName: string): ModuleDesignConfig {
  const design = MODULE_DESIGNS[moduleName.toLowerCase()];
  if (!design) {
    // Fallback to dashboard design
    return MODULE_DESIGNS.dashboard;
  }
  return design;
}

/**
 * Generate hero gradient classes for module
 */
export function getModuleGradient(moduleName: string): string {
  const design = getModuleDesign(moduleName);
  return `bg-gradient-to-br ${design.gradient}`;
}

/**
 * Generate accent color class for module
 */
export function getModuleAccent(moduleName: string): string {
  const design = getModuleDesign(moduleName);
  return `text-${design.accentColor}`;
}
