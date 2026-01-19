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

  // Design System - Brand Colors
  designsystem: {
    gradient: 'from-blue-900 to-slate-800',
    accentColor: 'brand-600',
    activeBg: 'bg-brand-50',
    activeText: 'text-brand-700',
    activeBorder: 'border-brand-200',
    hoverBg: 'hover:bg-brand-50'
  },

  // Design Frontend - Brand Colors
  designfrontend: {
    gradient: 'from-brand-600 to-brand-800',
    accentColor: 'brand-600',
    activeBg: 'bg-brand-50',
    activeText: 'text-brand-700',
    activeBorder: 'border-brand-200',
    hoverBg: 'hover:bg-brand-50'
  }
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

// ============================================================================
// TABLE STYLES (Zentrale Tabellen-Definitionen)
// ============================================================================

export const TABLE_STYLES = {
  // Container Styles
  container: 'bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden',
  containerWithScroll: 'bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden overflow-x-auto',

  // Base Table
  table: 'w-full text-left border-collapse',

  // Header Styles
  thead: 'bg-slate-50 border-b border-slate-200 sticky top-0 z-10',
  theadAlt: 'bg-slate-100 border-b border-slate-300',
  th: 'p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider',
  thSortable: 'p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100 transition-colors',

  // Body Styles
  tbody: 'divide-y divide-slate-200',
  tbodyAlt: 'divide-y divide-slate-100',

  // Row Styles
  tr: 'hover:bg-slate-50 transition-colors',
  trClickable: 'hover:bg-slate-50 transition-colors cursor-pointer',
  trSelected: 'bg-brand-50 hover:bg-brand-100',
  trDisabled: 'opacity-60 bg-slate-50',

  // Cell Styles
  td: 'p-4 text-sm text-slate-700',
  tdCompact: 'p-3 text-sm text-slate-700',
  tdMuted: 'p-4 text-sm text-slate-500',
  tdBold: 'p-4 text-sm text-slate-900 font-medium',

  // Pagination
  pagination: 'flex items-center justify-between px-6 py-4 border-t border-slate-200 bg-slate-50',
  paginationButton: 'p-2 rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors',
  paginationPageButton: 'min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors',
  paginationPageActive: 'bg-brand-600 text-white shadow-sm',
  paginationPageInactive: 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50',
} as const;

// ============================================================================
// CARD STYLES (Zentrale Card-Definitionen)
// ============================================================================

export const CARD_STYLES_ADVANCED = {
  // Base Card Containers
  base: 'bg-white rounded-xl border border-slate-200 shadow-sm',
  baseHover: 'bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300',
  baseClickable: 'bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg hover:border-brand-200 transition-all duration-300 cursor-pointer',

  // Card Header/Title Sections
  header: 'p-6 border-b border-slate-200',
  headerCompact: 'p-4 border-b border-slate-100',
  headerGradient: 'p-6 bg-gradient-to-r from-slate-50 to-white border-b border-slate-200',

  // Card Body/Content
  body: 'p-6',
  bodyCompact: 'p-4',
  bodySpaced: 'p-6 space-y-4',

  // Card Footer
  footer: 'p-6 border-t border-slate-200 bg-slate-50',
  footerActions: 'p-4 border-t border-slate-100 flex justify-end gap-3',

  // Special Card Variants
  elevated: 'bg-white rounded-xl border border-slate-200 shadow-md',
  flat: 'bg-white rounded-xl border border-slate-200',
  ghost: 'bg-slate-50 rounded-xl border border-slate-100',

  // Grid Card (for grid layouts)
  gridItem: 'bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300 flex flex-col',

  // List Card (for list layouts)
  listItem: 'bg-white rounded-lg border border-slate-200 shadow-sm p-4 hover:shadow-md transition-shadow',

  // Card with Banner (like Employee cards)
  withBanner: {
    container: 'bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300 flex flex-col',
    banner: 'h-24 bg-gradient-to-r relative',
    content: 'p-6 flex-1',
    footer: 'p-4 border-t border-slate-100 bg-slate-50',
  },

  // Empty State Card
  empty: 'border-2 border-dashed border-slate-300 rounded-xl p-6 flex flex-col items-center justify-center text-slate-400 hover:border-brand-400 hover:text-brand-600 hover:bg-slate-50 transition-all',

  // Stat Card (for dashboards)
  stat: 'bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow',
} as const;

// ============================================================================
// CARD COMPONENT UTILITIES (Wiederverwendbare Card-Elemente)
// ============================================================================

export const CARD_ELEMENTS = {
  // Avatar Sizes
  avatar: {
    sm: 'w-8 h-8 rounded-full',
    md: 'w-10 h-10 rounded-full',
    lg: 'w-12 h-12 rounded-full',
    xl: 'w-16 h-16 rounded-full',
  },

  // Avatar Initials (with background)
  avatarInitials: {
    sm: 'w-8 h-8 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-semibold text-xs uppercase',
    md: 'w-10 h-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-semibold text-sm uppercase',
    lg: 'w-12 h-12 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-semibold text-base uppercase',
    xl: 'w-16 h-16 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold text-xl uppercase',
  },

  // Card Title
  title: {
    sm: 'text-base font-bold text-slate-900',
    md: 'text-lg font-bold text-slate-900',
    lg: 'text-xl font-bold text-slate-900',
  },

  // Card Subtitle
  subtitle: {
    sm: 'text-xs text-slate-500',
    md: 'text-sm text-slate-500',
    lg: 'text-base text-slate-500',
  },

  // Icon Container
  iconContainer: {
    sm: 'p-2 rounded-lg',
    md: 'p-3 rounded-lg',
    lg: 'p-4 rounded-xl',
  },

  // Meta Info (e.g., date, author)
  meta: 'text-xs text-slate-400',
  metaBold: 'text-xs text-slate-500 font-medium',

  // Divider
  divider: 'border-t border-slate-200 my-4',
  dividerDotted: 'border-t border-dashed border-slate-200 my-4',
} as const;

// ============================================================================
// LIST STYLES (für Listen-Ansichten)
// ============================================================================

export const LIST_STYLES = {
  container: 'space-y-3',
  containerDense: 'space-y-2',

  item: 'flex items-center justify-between p-4 bg-white border border-slate-200 rounded-lg hover:shadow-sm transition-shadow',
  itemClickable: 'flex items-center justify-between p-4 bg-white border border-slate-200 rounded-lg hover:shadow-md hover:border-brand-200 transition-all cursor-pointer',

  itemActive: 'flex items-center justify-between p-4 bg-brand-50 border border-brand-200 rounded-lg shadow-sm',
} as const;

// ============================================================================
// GRID STYLES (für Grid-Layouts)
// ============================================================================

export const GRID_STYLES = {
  // Standard Grid Layouts
  cols1: 'grid grid-cols-1 gap-6',
  cols2: 'grid grid-cols-1 md:grid-cols-2 gap-6',
  cols3: 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6',
  cols4: 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6',

  // Dense Grid (smaller gaps)
  cols2Dense: 'grid grid-cols-1 md:grid-cols-2 gap-4',
  cols3Dense: 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4',
  cols4Dense: 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4',

  // Auto-fit Grid (responsive columns)
  autoFit: 'grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-6',
  autoFitDense: 'grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-4',
} as const;

// ============================================================================
// STATUS FARBEN (für gemeinsame Verwendung)
// ============================================================================

export const STATUS_COLORS = {
  active: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200' },
  inactive: { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200' },
  pending: { bg: 'bg-amber-100', text: 'text-amber-700', border: 'border-amber-200' },
  approved: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200' },
  rejected: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200' },
  cancelled: { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200' },
  completed: { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200' },
  inProgress: { bg: 'bg-brand-100', text: 'text-brand-700', border: 'border-brand-200' },
  overdue: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200' },
  draft: { bg: 'bg-slate-100', text: 'text-slate-500', border: 'border-slate-200' },
} as const;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Holt die Design-Konfiguration für ein Modul
 * @param moduleName - Name des Moduls (lowercase)
 * @returns ModuleDesignConfig
 */
export function getModuleDesign(moduleName: string): ModuleDesignConfig {
  const normalized = moduleName.toLowerCase().replace(/\s+/g, '');
  return MODULE_DESIGNS[normalized] || MODULE_DESIGNS.dashboard;
}

/**
 * Generiert einen Gradient-String für inline styles
 * @param moduleName - Name des Moduls
 * @returns CSS gradient string
 */
export function getModuleGradient(moduleName: string): string {
  const design = getModuleDesign(moduleName);
  return design.gradient;
}

/**
 * Prüft ob ein Modul existiert
 * @param moduleName - Name des Moduls
 * @returns boolean
 */
export function moduleDesignExists(moduleName: string): boolean {
  const normalized = moduleName.toLowerCase().replace(/\s+/g, '');
  return normalized in MODULE_DESIGNS;
}

/**
 * Gibt alle verfügbaren Modul-Namen zurück
 * @returns Array of module names
 */
export function getAllModuleNames(): string[] {
  return Object.keys(MODULE_DESIGNS);
}

/**
 * Generiert kombinierte Card-Klassen für verschiedene Varianten
 * @param variant - Card-Variante
 * @param additionalClasses - Zusätzliche Klassen
 */
export function getCardClasses(
  variant: 'base' | 'hover' | 'clickable' | 'elevated' | 'flat' | 'ghost' | 'grid' | 'list' = 'base',
  additionalClasses: string = ''
): string {
  const baseClass = variant === 'grid' ? CARD_STYLES_ADVANCED.gridItem :
                    variant === 'list' ? CARD_STYLES_ADVANCED.listItem :
                    variant === 'hover' ? CARD_STYLES_ADVANCED.baseHover :
                    variant === 'clickable' ? CARD_STYLES_ADVANCED.baseClickable :
                    variant === 'elevated' ? CARD_STYLES_ADVANCED.elevated :
                    variant === 'flat' ? CARD_STYLES_ADVANCED.flat :
                    variant === 'ghost' ? CARD_STYLES_ADVANCED.ghost :
                    CARD_STYLES_ADVANCED.base;

  return additionalClasses ? `${baseClass} ${additionalClasses}` : baseClass;
}

/**
 * Generiert Table-Klassen mit optionalen Varianten
 */
export function getTableClasses(options?: {
  compact?: boolean;
  alternateHeader?: boolean;
  thinDividers?: boolean;
}): {
  container: string;
  table: string;
  thead: string;
  tbody: string;
  th: string;
  td: string;
  tr: string;
} {
  return {
    container: TABLE_STYLES.container,
    table: TABLE_STYLES.table,
    thead: options?.alternateHeader ? TABLE_STYLES.theadAlt : TABLE_STYLES.thead,
    tbody: options?.thinDividers ? TABLE_STYLES.tbodyAlt : TABLE_STYLES.tbody,
    th: TABLE_STYLES.th,
    td: options?.compact ? TABLE_STYLES.tdCompact : TABLE_STYLES.td,
    tr: TABLE_STYLES.tr,
  };
}

/**
 * Holt die Farben für einen Status
 * @param status - Status name
 * @returns Status color config
 */
export function getStatusColors(status: string) {
  const normalized = status.toLowerCase().replace(/\s+/g, '');
  return STATUS_COLORS[normalized as keyof typeof STATUS_COLORS] || STATUS_COLORS.inactive;
}
