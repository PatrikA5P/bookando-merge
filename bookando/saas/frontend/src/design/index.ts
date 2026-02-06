/**
 * BOOKANDO DESIGN SYSTEM — Zentraler Export
 *
 * Alle Design-Tokens, Komponentenstile und Hilfsfunktionen
 * werden über diesen Einstiegspunkt exportiert.
 *
 * Verwendung in Komponenten:
 *   import { BUTTON_STYLES, getModuleDesign, GRID_STYLES } from '@/design';
 */

// Design Tokens
export {
  BREAKPOINTS,
  BREAKPOINT_QUERIES,
  COLORS,
  MODULE_DESIGNS,
  TYPOGRAPHY,
  SPACING,
  BORDER_RADIUS,
  SHADOWS,
  TRANSITIONS,
  ANIMATIONS,
  Z_INDEX,
  LAYOUT,
  getModuleDesign,
  moduleDesignExists,
  getModuleNames,
} from './tokens';

export type { ModuleDesignConfig } from './tokens';

// Component Styles
export {
  BUTTON_STYLES,
  BUTTON_SIZES,
  INPUT_STYLES,
  LABEL_STYLES,
  CARD_STYLES,
  TABLE_STYLES,
  BADGE_STYLES,
  STATUS_COLORS,
  getStatusColors,
  GRID_STYLES,
  LIST_STYLES,
  MODAL_STYLES,
  TAB_STYLES,
  AVATAR_STYLES,
  TOAST_STYLES,
  SKELETON_STYLES,
  EMPTY_STATE_STYLES,
} from './components';
