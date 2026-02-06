/**
 * BOOKANDO KOMPONENTENSTILE
 *
 * Zentrale CSS-Klassen-Definitionen für alle UI-Komponenten.
 * Jede Komponente hat vordefinierte Varianten die konsistent
 * über alle Module hinweg verwendet werden.
 */

// ============================================================================
// BUTTONS
// ============================================================================

export const BUTTON_STYLES = {
  // Hauptaktionen (Erstellen, Speichern)
  primary: 'bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',

  // Sekundäre Aktionen (Abbrechen, Filter)
  secondary: 'bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 px-5 py-2.5 rounded-xl text-sm font-medium border border-slate-200 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',

  // Gefährliche Aktionen (Löschen)
  danger: 'bg-red-600 hover:bg-red-700 active:bg-red-800 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',

  // Unsichtbare Buttons (Links, Menüs)
  ghost: 'bg-transparent hover:bg-slate-100 active:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',

  // Icon-Buttons (Toolbar)
  icon: 'p-2 rounded-lg hover:bg-slate-100 active:bg-slate-200 text-slate-600 hover:text-slate-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed',

  // Icon-Button rund
  iconRound: 'p-2.5 rounded-full hover:bg-slate-100 active:bg-slate-200 text-slate-600 hover:text-slate-900 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1',

  // Floating Action Button (Mobile)
  fab: 'fixed bottom-6 right-6 w-14 h-14 bg-brand-600 hover:bg-brand-700 text-white rounded-full shadow-xl flex items-center justify-center transition-all duration-200 z-30 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 md:hidden',
} as const;

// Button-Grössen
export const BUTTON_SIZES = {
  xs: 'px-2.5 py-1 text-xs',
  sm: 'px-3.5 py-1.5 text-sm',
  md: 'px-5 py-2.5 text-sm',
  lg: 'px-6 py-3 text-base',
  xl: 'px-8 py-4 text-lg',
} as const;

// ============================================================================
// INPUTS / FORMFELDER
// ============================================================================

export const INPUT_STYLES = {
  base: 'w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed',

  error: 'w-full px-3 py-2.5 border border-red-300 rounded-lg text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200',

  search: 'w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white text-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200',

  select: 'w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer disabled:bg-slate-50 disabled:cursor-not-allowed',

  textarea: 'w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 resize-y min-h-[80px]',

  checkbox: 'w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 focus:ring-offset-0 transition-colors',

  radio: 'w-4 h-4 border-slate-300 text-brand-600 focus:ring-brand-500 focus:ring-offset-0 transition-colors',

  toggle: 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
} as const;

export const LABEL_STYLES = {
  base: 'block text-sm font-medium text-slate-700 mb-1',
  required: 'block text-sm font-medium text-slate-700 mb-1 after:content-["*"] after:ml-0.5 after:text-red-500',
  error: 'text-sm text-red-600 mt-1',
  hint: 'text-xs text-slate-500 mt-1',
} as const;

// ============================================================================
// CARDS
// ============================================================================

export const CARD_STYLES = {
  // Basis-Karte
  base: 'bg-white rounded-xl border border-slate-200 shadow-sm',
  hover: 'bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg transition-all duration-300',
  interactive: 'bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg hover:border-brand-200 transition-all duration-300 cursor-pointer',
  elevated: 'bg-white rounded-xl border border-slate-200 shadow-md',
  flat: 'bg-white rounded-xl border border-slate-200',
  ghost: 'bg-slate-50 rounded-xl border border-slate-100',

  // Karten-Teile
  header: 'p-6 border-b border-slate-200',
  headerCompact: 'p-4 border-b border-slate-100',
  body: 'p-6',
  bodyCompact: 'p-4',
  footer: 'p-6 border-t border-slate-200 bg-slate-50',
  footerActions: 'p-4 border-t border-slate-100 flex justify-end gap-3',

  // Spezial-Karten
  gridItem: 'bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300 flex flex-col',
  listItem: 'bg-white rounded-lg border border-slate-200 shadow-sm p-4 hover:shadow-md transition-shadow',
  empty: 'border-2 border-dashed border-slate-300 rounded-xl p-8 flex flex-col items-center justify-center text-slate-400 hover:border-brand-400 hover:text-brand-600 hover:bg-slate-50 transition-all cursor-pointer',
  stat: 'bg-white rounded-xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow',

  // Karte mit Banner (z.B. Mitarbeiter)
  banner: 'h-24 bg-gradient-to-r relative',
  bannerContent: 'p-6 pt-0 -mt-8 relative z-10',
} as const;

// ============================================================================
// TABELLEN
// ============================================================================

export const TABLE_STYLES = {
  container: 'bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden',
  scrollContainer: 'overflow-x-auto',
  table: 'w-full text-left border-collapse',

  // Header
  thead: 'bg-slate-50 border-b border-slate-200 sticky top-0 z-10',
  th: 'p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap',
  thSortable: 'p-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:bg-slate-100 select-none transition-colors whitespace-nowrap',

  // Body
  tbody: 'divide-y divide-slate-200',
  tr: 'hover:bg-slate-50 transition-colors',
  trClickable: 'hover:bg-slate-50 transition-colors cursor-pointer',
  trSelected: 'bg-brand-50 hover:bg-brand-100',
  trDisabled: 'opacity-60 bg-slate-50',
  td: 'p-4 text-sm text-slate-700',
  tdCompact: 'p-3 text-sm text-slate-700',
  tdBold: 'p-4 text-sm text-slate-900 font-medium',
  tdMuted: 'p-4 text-sm text-slate-500',

  // Pagination
  pagination: 'flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-slate-200 bg-slate-50',
  paginationInfo: 'text-sm text-slate-600',
  paginationButtons: 'flex items-center gap-1',
  paginationButton: 'min-w-[32px] h-8 px-2 rounded-lg text-sm font-medium transition-colors',
  paginationActive: 'bg-brand-600 text-white shadow-sm',
  paginationInactive: 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50',
} as const;

// ============================================================================
// BADGES / STATUS
// ============================================================================

export const BADGE_STYLES = {
  default: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700',
  success: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700',
  warning: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700',
  danger: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700',
  info: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700',
  brand: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-700',
  purple: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700',
  outline: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border border-slate-300 text-slate-600',
} as const;

export const STATUS_COLORS: Record<string, { bg: string; text: string; border: string; dot: string }> = {
  active: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
  inactive: { bg: 'bg-slate-100', text: 'text-slate-600', border: 'border-slate-200', dot: 'bg-slate-400' },
  pending: { bg: 'bg-amber-100', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500' },
  approved: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
  confirmed: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
  rejected: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-500' },
  cancelled: { bg: 'bg-slate-100', text: 'text-slate-600', border: 'border-slate-200', dot: 'bg-slate-400' },
  completed: { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' },
  inProgress: { bg: 'bg-brand-100', text: 'text-brand-700', border: 'border-brand-200', dot: 'bg-brand-500' },
  overdue: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200', dot: 'bg-red-500' },
  draft: { bg: 'bg-slate-100', text: 'text-slate-500', border: 'border-slate-200', dot: 'bg-slate-400' },
  noShow: { bg: 'bg-rose-100', text: 'text-rose-700', border: 'border-rose-200', dot: 'bg-rose-500' },
  paid: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500' },
  sent: { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200', dot: 'bg-blue-500' },
};

/**
 * Holt Status-Farben für einen gegebenen Status-String.
 */
export function getStatusColors(status: string) {
  const normalized = status.toLowerCase().replace(/[\s_-]+/g, '');
  return STATUS_COLORS[normalized] || STATUS_COLORS.inactive;
}

// ============================================================================
// GRID LAYOUTS
// ============================================================================

export const GRID_STYLES = {
  cols1: 'grid grid-cols-1 gap-6',
  cols2: 'grid grid-cols-1 md:grid-cols-2 gap-6',
  cols3: 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6',
  cols4: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6',
  cols2Dense: 'grid grid-cols-1 md:grid-cols-2 gap-4',
  cols3Dense: 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4',
  cols4Dense: 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4',
  autoFit: 'grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-6',
  autoFitSmall: 'grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4',
} as const;

// ============================================================================
// LISTEN
// ============================================================================

export const LIST_STYLES = {
  container: 'space-y-3',
  containerDense: 'space-y-2',
  item: 'flex items-center justify-between p-4 bg-white border border-slate-200 rounded-lg hover:shadow-sm transition-shadow',
  itemClickable: 'flex items-center justify-between p-4 bg-white border border-slate-200 rounded-lg hover:shadow-md hover:border-brand-200 transition-all cursor-pointer',
  itemActive: 'flex items-center justify-between p-4 bg-brand-50 border border-brand-200 rounded-lg shadow-sm',
} as const;

// ============================================================================
// MODALS / DIALOGE
// ============================================================================

export const MODAL_STYLES = {
  overlay: 'fixed inset-0 bg-black/50 backdrop-blur-sm z-40 flex items-center justify-center p-4',
  container: 'bg-white rounded-2xl shadow-2xl w-full max-h-[90vh] flex flex-col overflow-hidden',
  header: 'flex items-center justify-between px-6 py-4 border-b border-slate-200',
  title: 'text-lg font-bold text-slate-900',
  body: 'p-6 overflow-y-auto flex-1',
  footer: 'flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50',

  // Mobile Bottom Sheet
  bottomSheet: 'fixed inset-x-0 bottom-0 bg-white rounded-t-2xl shadow-2xl z-40 max-h-[85vh] flex flex-col overflow-hidden',
  bottomSheetHandle: 'w-12 h-1.5 bg-slate-300 rounded-full mx-auto mt-3 mb-2',
} as const;

// ============================================================================
// NAVIGATION / TABS
// ============================================================================

export const TAB_STYLES = {
  container: 'flex gap-1 overflow-x-auto scrollbar-hide',
  tab: 'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition-all duration-200 whitespace-nowrap',
  tabActive: 'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 whitespace-nowrap',
  tabBadge: 'ml-1 px-1.5 py-0.5 text-xs rounded-full',
} as const;

// ============================================================================
// AVATAR
// ============================================================================

export const AVATAR_STYLES = {
  image: {
    xs: 'w-6 h-6 rounded-full object-cover',
    sm: 'w-8 h-8 rounded-full object-cover',
    md: 'w-10 h-10 rounded-full object-cover',
    lg: 'w-12 h-12 rounded-full object-cover',
    xl: 'w-16 h-16 rounded-full object-cover',
    '2xl': 'w-20 h-20 rounded-full object-cover',
  },
  initials: {
    xs: 'w-6 h-6 rounded-full flex items-center justify-center font-semibold text-[10px] uppercase',
    sm: 'w-8 h-8 rounded-full flex items-center justify-center font-semibold text-xs uppercase',
    md: 'w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm uppercase',
    lg: 'w-12 h-12 rounded-full flex items-center justify-center font-semibold text-base uppercase',
    xl: 'w-16 h-16 rounded-full flex items-center justify-center font-bold text-xl uppercase',
    '2xl': 'w-20 h-20 rounded-full flex items-center justify-center font-bold text-2xl uppercase',
  },
} as const;

// ============================================================================
// TOAST / SNACKBAR
// ============================================================================

export const TOAST_STYLES = {
  container: 'fixed bottom-4 right-4 z-60 flex flex-col gap-2 pointer-events-none',
  base: 'pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border min-w-[300px] max-w-[420px]',
  success: 'bg-white border-emerald-200 text-emerald-800',
  error: 'bg-white border-red-200 text-red-800',
  warning: 'bg-white border-amber-200 text-amber-800',
  info: 'bg-white border-blue-200 text-blue-800',
} as const;

// ============================================================================
// SKELETON LOADER
// ============================================================================

export const SKELETON_STYLES = {
  base: 'animate-pulse bg-slate-200 rounded',
  text: 'animate-pulse bg-slate-200 rounded h-4',
  textSm: 'animate-pulse bg-slate-200 rounded h-3',
  heading: 'animate-pulse bg-slate-200 rounded h-6',
  avatar: 'animate-pulse bg-slate-200 rounded-full',
  card: 'animate-pulse bg-slate-200 rounded-xl h-48',
  row: 'animate-pulse bg-slate-200 rounded h-12',
} as const;

// ============================================================================
// EMPTY STATES
// ============================================================================

export const EMPTY_STATE_STYLES = {
  container: 'flex flex-col items-center justify-center py-16 px-8 text-center',
  icon: 'w-16 h-16 text-slate-300 mb-4',
  title: 'text-lg font-semibold text-slate-600 mb-2',
  description: 'text-sm text-slate-400 mb-6 max-w-md',
  action: 'mt-2',
} as const;
