import React, { useEffect, useMemo, useState } from 'react';
import {
  Calendar as CalendarIcon,
  Clock,
  Check,
  ChevronRight,
  ChevronLeft,
  ChevronDown,
  User,
  Monitor,
  Smartphone,
  CheckCircle,
  ArrowRight,
  Plus,
  Layout,
  CreditCard,
  Settings,
  Sidebar,
  AlignJustify,
  Maximize,
  Minimize,
  List,
  Columns,
  X,
  Filter,
  Grid as GridIcon,
  AlignLeft,
  Info,
  Eye,
  MapPin,
  BookOpen,
  Award,
  LogOut,
  Bell,
  Shield,
  Mail,
  FileText,
  Download,
  Briefcase,
  Umbrella,
  Banknote,
  Timer,
  Menu,
  Search,
  Phone,
  ClipboardList,
  GraduationCap,
  ExternalLink,
  Map,
  History,
  Play,
  Square,
  Pause,
  CalendarDays,
  Plane,
  Stethoscope,
  Baby,
  Palette,
  Type,
  Box,
  Layers,
  MousePointer2,
  Tablet,
  Users,
  Copy,
  RotateCcw,
  Trash2,
  Code,
} from 'lucide-react';

import { useApp } from '../context/AppContext';
import {
  ServiceItem,
  OfferExtra,
  EventSession,
  Customer,
  Appointment,
  TimeEntry,
  AbsenceRequest,
} from '../types';

/* -------------------------------------------------------------------------- */
/*                                   TYPES                                    */
/* -------------------------------------------------------------------------- */

type PortalTab = 'widget' | 'offerForm' | 'customer' | 'employee';
type PresetContext = PortalTab | 'all';

type Device = 'desktop' | 'tablet' | 'mobile';
type Orientation = 'portrait' | 'landscape';

type BookingStep = 'extras' | 'datetime' | 'details' | 'payment' | 'confirmation';

type StartView = 'categories' | 'services';
type FilterLayout = 'none' | 'top' | 'sidebar';
type ViewLayout = 'grid' | 'list';
type ScheduleDisplayMode = 'all' | 'limit' | 'interval';
type InteractionMode = 'modal' | 'inline';

interface BookingState {
  service: ServiceItem | null;
  extras: string[];
  date: Date | null;
  timeSlot: string | null;
  customer: {
    firstName: string;
    lastName: string;
    email: string;
    phone: string;
  };
  paymentMethod: string;
}

/* ----------------------------- THEME + EDITOR ---------------------------- */

interface ThemeConfig {
  colors: {
    primary: string;
    secondary: string;
    background: string;
    surface: string;
    text: string;
    textMuted: string;
    border: string;
    success: string;
    danger: string;
  };
  typography: {
    fontFamily: string;
    scale: number; // percentage
  };
  shape: {
    radius: number; // px
    borderWidth: number; // px
    shadow: 'none' | 'sm' | 'md' | 'lg' | 'xl';
  };
  layout: {
    showSidebar: boolean;
    showFooter: boolean;
    compactMode: boolean;

    showUserAvatar?: boolean;
    showSearch?: boolean;
    showFilters?: boolean;
    enableAnimations?: boolean;
    showDevOverlays?: boolean;
  };
  locale: {
    uiLanguage: 'en-US' | 'de-CH' | 'de-DE' | 'fr-CH' | 'it-CH';
  };
}

const defaultTheme: ThemeConfig = {
  colors: {
    primary: '#0284c7',
    secondary: '#475569',
    background: '#f8fafc',
    surface: '#ffffff',
    text: '#0f172a',
    textMuted: '#64748b',
    border: '#e2e8f0',
    success: '#10b981',
    danger: '#e11d48',
  },
  typography: {
    fontFamily: 'Inter',
    scale: 100,
  },
  shape: {
    radius: 14,
    borderWidth: 1,
    shadow: 'sm',
  },
  layout: {
    showSidebar: true,
    showFooter: true,
    compactMode: false,
    showUserAvatar: true,
    showSearch: true,
    showFilters: true,
    enableAnimations: true,
    showDevOverlays: true,
  },
  locale: {
    uiLanguage: 'en-US',
  },
};

interface WidgetBehaviorConfig {
  startView: StartView;
  filterLayout: FilterLayout;
  viewLayout: ViewLayout;
  scheduleMode: ScheduleDisplayMode;
  scheduleLimit: number;
  showWizardSidebar: boolean;
  interactionMode: InteractionMode;
  showDetailsButton: boolean;
  showPriceBadge: boolean;
  showScheduleCards: boolean;
}

const defaultWidgetBehavior: WidgetBehaviorConfig = {
  startView: 'services',
  filterLayout: 'top',
  viewLayout: 'grid',
  scheduleMode: 'limit',
  scheduleLimit: 3,
  showWizardSidebar: true,
  interactionMode: 'modal',
  showDetailsButton: true,
  showPriceBadge: true,
  showScheduleCards: true,
};

type CustomerPage = 'dashboard' | 'appointments' | 'invoices' | 'profile' | 'settings';
interface CustomerPortalConfig {
  showDashboard: boolean;
  showAppointments: boolean;
  showInvoices: boolean;
  showProfile: boolean;
  showSettings: boolean;
  showLogout: boolean;
}
const defaultCustomerPortalConfig: CustomerPortalConfig = {
  showDashboard: true,
  showAppointments: true,
  showInvoices: true,
  showProfile: true,
  showSettings: true,
  showLogout: true,
};

type EmployeePage = 'dashboard' | 'schedule' | 'clients' | 'tracking';
interface EmployeePortalConfig {
  showDashboard: boolean;
  showSchedule: boolean;
  showClients: boolean;
  showTracking: boolean;
}
const defaultEmployeePortalConfig: EmployeePortalConfig = {
  showDashboard: true,
  showSchedule: true,
  showClients: true,
  showTracking: true,
};

/* ------------------------------ TEXT OVERRIDES ----------------------------- */

type TextKey =
  | 'tab_widget'
  | 'tab_offerForm'
  | 'tab_customer'
  | 'tab_employee'
  | 'device_desktop'
  | 'device_tablet'
  | 'device_mobile'
  | 'studio_title'
  | 'studio_back'
  | 'studio_save'
  | 'studio_presets'
  | 'preset_new'
  | 'preset_update'
  | 'preset_load'
  | 'preset_duplicate'
  | 'preset_delete'
  | 'preset_export'
  | 'preset_import'
  | 'reset_theme'
  | 'reset_toggles'
  | 'editor_editing'
  | 'widget_search_placeholder'
  | 'widget_categories'
  | 'widget_clear'
  | 'widget_details'
  | 'widget_book'
  | 'widget_close'
  | 'wizard_progress'
  | 'wizard_extras'
  | 'wizard_datetime'
  | 'wizard_details'
  | 'wizard_payment'
  | 'wizard_confirmation'
  | 'wizard_back'
  | 'wizard_continue'
  | 'wizard_confirmed'
  | 'wizard_close'
  | 'wizard_select_date_hint'
  | 'wizard_available'
  | 'wizard_total'
  | 'wizard_payment_method_card'
  | 'wizard_payment_method_paypal'
  | 'offerform_title'
  | 'offerform_pick_offer'
  | 'portal_no_data';

type TextOverrides = Partial<Record<TextKey, string>>;

const defaultTexts: Record<ThemeConfig['locale']['uiLanguage'], Record<TextKey, string>> = {
  'en-US': {
    tab_widget: 'Widget',
    tab_offerForm: 'Offer Form',
    tab_customer: 'Customer',
    tab_employee: 'Employee',
    device_desktop: 'Desktop',
    device_tablet: 'Tablet',
    device_mobile: 'Mobile',
    studio_title: 'Design Studio',
    studio_back: 'Back',
    studio_save: 'Save',
    studio_presets: 'Designs',
    preset_new: 'Save as new',
    preset_update: 'Save changes',
    preset_load: 'Load',
    preset_duplicate: 'Duplicate',
    preset_delete: 'Delete',
    preset_export: 'Export',
    preset_import: 'Import',
    reset_theme: 'Reset Theme',
    reset_toggles: 'Reset Feature Toggles',
    editor_editing: 'Editing',
    widget_search_placeholder: 'Find a service...',
    widget_categories: 'Categories',
    widget_clear: 'Clear',
    widget_details: 'Details',
    widget_book: 'Book',
    widget_close: 'Close',
    wizard_progress: 'Progress',
    wizard_extras: 'Extras',
    wizard_datetime: 'Date & Time',
    wizard_details: 'Your Details',
    wizard_payment: 'Payment',
    wizard_confirmation: 'Confirmation',
    wizard_back: 'Back',
    wizard_continue: 'Continue',
    wizard_confirmed: 'Confirmed!',
    wizard_close: 'Close',
    wizard_select_date_hint: 'Select a date',
    wizard_available: 'Available',
    wizard_total: 'Total',
    wizard_payment_method_card: 'Credit Card',
    wizard_payment_method_paypal: 'Paypal',
    offerform_title: 'Offer Booking Form',
    offerform_pick_offer: 'Choose offer',
    portal_no_data: 'No data available.',
  },
  'de-CH': {
    tab_widget: 'Widget',
    tab_offerForm: 'Angebot buchen',
    tab_customer: 'Kunde',
    tab_employee: 'Mitarbeiter',
    device_desktop: 'Desktop',
    device_tablet: 'Tablet',
    device_mobile: 'Mobile',
    studio_title: 'Design Studio',
    studio_back: 'Zurueck',
    studio_save: 'Speichern',
    studio_presets: 'Designs',
    preset_new: 'Neu speichern',
    preset_update: 'Aenderungen speichern',
    preset_load: 'Laden',
    preset_duplicate: 'Kopieren',
    preset_delete: 'Loeschen',
    preset_export: 'Export',
    preset_import: 'Import',
    reset_theme: 'Theme zuruecksetzen',
    reset_toggles: 'Toggles zuruecksetzen',
    editor_editing: 'Bearbeitung',
    widget_search_placeholder: 'Service suchen...',
    widget_categories: 'Kategorien',
    widget_clear: 'Leeren',
    widget_details: 'Details',
    widget_book: 'Buchen',
    widget_close: 'Schliessen',
    wizard_progress: 'Fortschritt',
    wizard_extras: 'Extras',
    wizard_datetime: 'Datum & Zeit',
    wizard_details: 'Deine Angaben',
    wizard_payment: 'Zahlung',
    wizard_confirmation: 'Bestaetigung',
    wizard_back: 'Zurueck',
    wizard_continue: 'Weiter',
    wizard_confirmed: 'Bestaetigt!',
    wizard_close: 'Schliessen',
    wizard_select_date_hint: 'Bitte ein Datum waehlen',
    wizard_available: 'Verfuegbar',
    wizard_total: 'Total',
    wizard_payment_method_card: 'Kreditkarte',
    wizard_payment_method_paypal: 'Paypal',
    offerform_title: 'Angebot Buchungsformular',
    offerform_pick_offer: 'Angebot waehlen',
    portal_no_data: 'Keine Daten vorhanden.',
  },
  'de-DE': {
    tab_widget: 'Widget',
    tab_offerForm: 'Angebot buchen',
    tab_customer: 'Kunde',
    tab_employee: 'Mitarbeiter',
    device_desktop: 'Desktop',
    device_tablet: 'Tablet',
    device_mobile: 'Mobil',
    studio_title: 'Design Studio',
    studio_back: 'Zurueck',
    studio_save: 'Speichern',
    studio_presets: 'Designs',
    preset_new: 'Neu speichern',
    preset_update: 'Aenderungen speichern',
    preset_load: 'Laden',
    preset_duplicate: 'Kopieren',
    preset_delete: 'Loeschen',
    preset_export: 'Export',
    preset_import: 'Import',
    reset_theme: 'Theme zuruecksetzen',
    reset_toggles: 'Toggles zuruecksetzen',
    editor_editing: 'Bearbeitung',
    widget_search_placeholder: 'Service suchen...',
    widget_categories: 'Kategorien',
    widget_clear: 'Leeren',
    widget_details: 'Details',
    widget_book: 'Buchen',
    widget_close: 'Schliessen',
    wizard_progress: 'Fortschritt',
    wizard_extras: 'Extras',
    wizard_datetime: 'Datum & Zeit',
    wizard_details: 'Deine Angaben',
    wizard_payment: 'Zahlung',
    wizard_confirmation: 'Bestaetigung',
    wizard_back: 'Zurueck',
    wizard_continue: 'Weiter',
    wizard_confirmed: 'Bestaetigt!',
    wizard_close: 'Schliessen',
    wizard_select_date_hint: 'Bitte ein Datum waehlen',
    wizard_available: 'Verfuegbar',
    wizard_total: 'Gesamt',
    wizard_payment_method_card: 'Kreditkarte',
    wizard_payment_method_paypal: 'Paypal',
    offerform_title: 'Angebot Buchungsformular',
    offerform_pick_offer: 'Angebot waehlen',
    portal_no_data: 'Keine Daten vorhanden.',
  },
  'fr-CH': {
    tab_widget: 'Widget',
    tab_offerForm: 'Reserver',
    tab_customer: 'Client',
    tab_employee: 'Employe',
    device_desktop: 'Desktop',
    device_tablet: 'Tablette',
    device_mobile: 'Mobile',
    studio_title: 'Design Studio',
    studio_back: 'Retour',
    studio_save: 'Enregistrer',
    studio_presets: 'Designs',
    preset_new: 'Enregistrer nouveau',
    preset_update: 'Enregistrer',
    preset_load: 'Charger',
    preset_duplicate: 'Dupliquer',
    preset_delete: 'Supprimer',
    preset_export: 'Export',
    preset_import: 'Import',
    reset_theme: 'Reinitialiser theme',
    reset_toggles: 'Reinitialiser options',
    editor_editing: 'Edition',
    widget_search_placeholder: 'Rechercher...',
    widget_categories: 'Categories',
    widget_clear: 'Effacer',
    widget_details: 'Details',
    widget_book: 'Reserver',
    widget_close: 'Fermer',
    wizard_progress: 'Progression',
    wizard_extras: 'Extras',
    wizard_datetime: 'Date & heure',
    wizard_details: 'Vos informations',
    wizard_payment: 'Paiement',
    wizard_confirmation: 'Confirmation',
    wizard_back: 'Retour',
    wizard_continue: 'Continuer',
    wizard_confirmed: 'Confirme!',
    wizard_close: 'Fermer',
    wizard_select_date_hint: 'Choisissez une date',
    wizard_available: 'Disponible',
    wizard_total: 'Total',
    wizard_payment_method_card: 'Carte',
    wizard_payment_method_paypal: 'Paypal',
    offerform_title: 'Formulaire de reservation',
    offerform_pick_offer: 'Choisir une offre',
    portal_no_data: 'Pas de donnees.',
  },
  'it-CH': {
    tab_widget: 'Widget',
    tab_offerForm: 'Prenota',
    tab_customer: 'Cliente',
    tab_employee: 'Staff',
    device_desktop: 'Desktop',
    device_tablet: 'Tablet',
    device_mobile: 'Mobile',
    studio_title: 'Design Studio',
    studio_back: 'Indietro',
    studio_save: 'Salva',
    studio_presets: 'Design',
    preset_new: 'Salva nuovo',
    preset_update: 'Salva modifiche',
    preset_load: 'Carica',
    preset_duplicate: 'Copia',
    preset_delete: 'Elimina',
    preset_export: 'Export',
    preset_import: 'Import',
    reset_theme: 'Reset tema',
    reset_toggles: 'Reset opzioni',
    editor_editing: 'Modifica',
    widget_search_placeholder: 'Cerca servizio...',
    widget_categories: 'Categorie',
    widget_clear: 'Pulisci',
    widget_details: 'Dettagli',
    widget_book: 'Prenota',
    widget_close: 'Chiudi',
    wizard_progress: 'Progresso',
    wizard_extras: 'Extra',
    wizard_datetime: 'Data & ora',
    wizard_details: 'I tuoi dati',
    wizard_payment: 'Pagamento',
    wizard_confirmation: 'Conferma',
    wizard_back: 'Indietro',
    wizard_continue: 'Continua',
    wizard_confirmed: 'Confermato!',
    wizard_close: 'Chiudi',
    wizard_select_date_hint: 'Seleziona una data',
    wizard_available: 'Disponibile',
    wizard_total: 'Totale',
    wizard_payment_method_card: 'Carta',
    wizard_payment_method_paypal: 'Paypal',
    offerform_title: 'Modulo prenotazione',
    offerform_pick_offer: 'Scegli offerta',
    portal_no_data: 'Nessun dato.',
  },
};

function createTranslator(
  locale: ThemeConfig['locale']['uiLanguage'],
  overrides: TextOverrides,
) {
  const base = defaultTexts[locale] || defaultTexts['en-US'];
  return (key: TextKey) => overrides[key] ?? base[key] ?? key;
}

/* -------------------------------------------------------------------------- */
/*                          THEME VARS + UI HELPERS                           */
/* -------------------------------------------------------------------------- */

function shadowToCss(shadow: ThemeConfig['shape']['shadow']) {
  if (shadow === 'none') return 'none';
  if (shadow === 'sm') return '0 1px 2px 0 rgb(0 0 0 / 0.05)';
  if (shadow === 'md')
    return '0 4px 6px -1px rgb(0 0 0 / 0.10), 0 2px 4px -2px rgb(0 0 0 / 0.10)';
  if (shadow === 'lg')
    return '0 10px 15px -3px rgb(0 0 0 / 0.12), 0 4px 6px -4px rgb(0 0 0 / 0.12)';
  return '0 20px 25px -5px rgb(0 0 0 / 0.16), 0 10px 10px -5px rgb(0 0 0 / 0.14)';
}

function themeToVars(theme: ThemeConfig): React.CSSProperties {
  return {
    '--c-primary': theme.colors.primary,
    '--c-secondary': theme.colors.secondary,
    '--c-bg': theme.colors.background,
    '--c-surface': theme.colors.surface,
    '--c-text': theme.colors.text,
    '--c-text-muted': theme.colors.textMuted,
    '--c-border': theme.colors.border,
    '--c-success': theme.colors.success,
    '--c-danger': theme.colors.danger,
    '--radius': `${theme.shape.radius}px`,
    '--border-w': `${theme.shape.borderWidth}px`,
    '--font-family': theme.typography.fontFamily,
    '--font-scale': `${theme.typography.scale / 100}`,
    '--shadow': shadowToCss(theme.shape.shadow),
  } as React.CSSProperties;
}

/**
 * StudioUiWrapper
 * - setzt Theme-Variablen global fuer die Studio-UI (Header/Sidebar/Modals)
 * - entkoppelt Studio-Farben von Tailwind-slate (du kannst spaeter mehr UI auf vars umstellen)
 */
const StudioThemeWrapper: React.FC<{
  theme: ThemeConfig;
  children: React.ReactNode;
  className?: string;
}> = ({ theme, children, className = '' }) => {
  const studioVars: React.CSSProperties = {
    ...(themeToVars(theme) as any),

    // Studio-UI Vars (bewusst schlicht, ohne extra "Studio-Look" Presets)
    '--studio-bg': theme.colors.background,
    '--studio-panel': theme.colors.surface,
    '--studio-border': theme.colors.border,
    '--studio-text': theme.colors.text,
    '--studio-muted': theme.colors.textMuted,
    '--studio-accent': theme.colors.primary,
    '--studio-danger': theme.colors.danger,
  } as React.CSSProperties;

  return (
    <div
      className={`w-full h-full bg-[var(--studio-bg)] text-[var(--studio-text)] ${className}`}
      style={{
        ...(studioVars as any),
        fontFamily: 'var(--font-family)' as any,
        fontSize: 'calc(1rem * var(--font-scale))' as any,
      }}
    >
      {children}
    </div>
  );
};

/* ------------------------------ SIM PRIMITIVES ----------------------------- */

const SimCard: React.FC<{
  className?: string;
  onClick?: () => void;
  children: React.ReactNode;
}> = ({ className = '', onClick, children }) => (
  <div
    onClick={onClick}
    className={`bg-[var(--c-surface)] border border-[var(--c-border)] rounded-[var(--radius)] overflow-hidden ${className}`}
    style={{
      borderWidth: 'var(--border-w)' as any,
      boxShadow: 'var(--shadow)' as any,
    }}
  >
    {children}
  </div>
);

const SimButton: React.FC<{
  className?: string;
  onClick?: () => void;
  children: React.ReactNode;
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
  disabled?: boolean;
  title?: string;
}> = ({ className = '', onClick, children, variant = 'primary', disabled, title }) => {
  const base =
    'px-4 py-2 rounded-[var(--radius)] font-semibold transition-all flex items-center justify-center gap-2 select-none';
  const v =
    variant === 'primary'
      ? 'bg-[var(--c-primary)] text-white hover:opacity-90'
      : variant === 'secondary'
      ? 'bg-[var(--c-surface)] border border-[var(--c-border)] text-[var(--c-text)] hover:bg-[var(--c-bg)]'
      : variant === 'danger'
      ? 'bg-[var(--c-danger)] text-white hover:opacity-90'
      : 'bg-transparent text-[var(--c-text)] hover:bg-[var(--c-bg)]';
  return (
    <button
      title={title}
      disabled={disabled}
      onClick={disabled ? undefined : onClick}
      className={`${base} ${v} ${disabled ? 'opacity-50 cursor-not-allowed' : 'active:scale-[0.99]'} ${className}`}
      style={{ borderWidth: variant === 'secondary' ? ('var(--border-w)' as any) : 0 }}
    >
      {children}
    </button>
  );
};

const SimInput: React.FC<React.InputHTMLAttributes<HTMLInputElement> & { className?: string }> = ({
  className = '',
  ...props
}) => (
  <input
    {...props}
    className={`w-full bg-[var(--c-surface)] border border-[var(--c-border)] rounded-[var(--radius)] px-3 py-2 text-[var(--c-text)] focus:outline-none focus:ring-2 focus:ring-[var(--c-primary)] ${className}`}
    style={{ borderWidth: 'var(--border-w)' as any }}
  />
);

const SimTextarea: React.FC<
  React.TextareaHTMLAttributes<HTMLTextAreaElement> & { className?: string }
> = ({ className = '', ...props }) => (
  <textarea
    {...props}
    className={`w-full bg-[var(--c-surface)] border border-[var(--c-border)] rounded-[var(--radius)] px-3 py-2 text-[var(--c-text)] focus:outline-none focus:ring-2 focus:ring-[var(--c-primary)] ${className}`}
    style={{ borderWidth: 'var(--border-w)' as any }}
  />
);

const SimBadge: React.FC<{
  color?: 'primary' | 'secondary' | 'success' | 'danger';
  className?: string;
  children: React.ReactNode;
}> = ({ color = 'primary', className = '', children }) => (
  <span
    className={`text-[10px] font-bold px-2 py-1 rounded-[calc(var(--radius)/2)] border uppercase tracking-wide ${className}`}
    style={{
      borderColor: `var(--c-${color})`,
      color: `var(--c-${color})`,
      backgroundColor: `color-mix(in srgb, var(--c-${color}) 10%, transparent)`,
      borderWidth: 'var(--border-w)' as any,
    }}
  >
    {children}
  </span>
);

/* -------------------------------------------------------------------------- */
/*                                 EDITOR UI                                 */
/* -------------------------------------------------------------------------- */

const ColorPicker: React.FC<{
  label: string;
  value: string;
  onChange: (v: string) => void;
}> = ({ label, value, onChange }) => (
  <div className="flex items-center justify-between py-2 gap-3">
    <span className="text-sm text-[var(--studio-muted)]">{label}</span>
    <div className="flex items-center gap-2 bg-[var(--studio-panel)] border border-[var(--studio-border)] rounded-lg p-1 pr-3 shrink-0">
      <input
        type="color"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-6 h-6 rounded cursor-pointer border-none p-0 bg-transparent"
      />
      <span className="text-xs font-mono text-[var(--studio-muted)] uppercase">{value}</span>
    </div>
  </div>
);

const RangeSlider: React.FC<{
  label: string;
  value: number;
  min: number;
  max: number;
  unit?: string;
  onChange: (v: number) => void;
}> = ({ label, value, min, max, unit, onChange }) => (
  <div className="py-2">
    <div className="flex justify-between mb-1">
      <span className="text-sm text-[var(--studio-muted)]">{label}</span>
      <span className="text-xs font-bold text-[var(--studio-text)]">
        {value}
        {unit}
      </span>
    </div>
    <input
      type="range"
      min={min}
      max={max}
      value={value}
      onChange={(e) => onChange(parseInt(e.target.value, 10))}
      className="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-slate-800"
    />
  </div>
);

const SelectBox: React.FC<{
  label: string;
  value: string;
  options: string[];
  onChange: (v: any) => void;
}> = ({ label, value, options, onChange }) => (
  <div className="py-2">
    <span className="block text-sm text-[var(--studio-muted)] mb-1">{label}</span>
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full border border-[var(--studio-border)] rounded-lg px-3 py-2 text-sm bg-[var(--studio-panel)] focus:outline-none focus:ring-2 focus:ring-slate-500"
    >
      {options.map((opt) => (
        <option key={opt} value={opt}>
          {opt}
        </option>
      ))}
    </select>
  </div>
);

const ToggleSwitch: React.FC<{
  label: string;
  checked: boolean;
  onChange: (v: boolean) => void;
}> = ({ label, checked, onChange }) => (
  <div className="flex items-center justify-between py-2 gap-3">
    <span className="text-sm text-[var(--studio-muted)]">{label}</span>
    <button
      onClick={() => onChange(!checked)}
      className={`relative inline-flex h-5 w-9 items-center rounded-full transition-colors ${
        checked ? 'bg-emerald-500' : 'bg-slate-300'
      }`}
    >
      <span
        className={`inline-block h-3 w-3 transform rounded-full bg-white transition-transform ${
          checked ? 'translate-x-5' : 'translate-x-1'
        }`}
      />
    </button>
  </div>
);

type EditorSection =
  | 'colors'
  | 'shape'
  | 'layout'
  | 'widget'
  | 'texts'
  | 'customer'
  | 'employee';

const TextField: React.FC<{
  label: string;
  value: string;
  placeholder?: string;
  onChange: (v: string) => void;
}> = ({ label, value, placeholder, onChange }) => (
  <div className="py-2">
    <div className="text-xs font-bold text-[var(--studio-muted)] mb-1">{label}</div>
    <input
      value={value}
      placeholder={placeholder}
      onChange={(e) => onChange(e.target.value)}
      className="w-full border border-[var(--studio-border)] rounded-lg px-3 py-2 text-sm bg-[var(--studio-panel)] focus:outline-none focus:ring-2 focus:ring-slate-500"
    />
  </div>
);

const EditorSidebar: React.FC<{
  activeTab: PortalTab;
  theme: ThemeConfig;
  setTheme: (t: ThemeConfig) => void;
  widgetBehavior: WidgetBehaviorConfig;
  setWidgetBehavior: (c: WidgetBehaviorConfig) => void;
  customerConfig: CustomerPortalConfig;
  setCustomerConfig: (c: CustomerPortalConfig) => void;
  employeeConfig: EmployeePortalConfig;
  setEmployeeConfig: (c: EmployeePortalConfig) => void;
  texts: TextOverrides;
  setTexts: (t: TextOverrides) => void;
  t: (k: TextKey) => string;
}> = ({
  activeTab,
  theme,
  setTheme,
  widgetBehavior,
  setWidgetBehavior,
  customerConfig,
  setCustomerConfig,
  employeeConfig,
  setEmployeeConfig,
  texts,
  setTexts,
  t,
}) => {
  const [activeSection, setActiveSection] = useState<EditorSection>('colors');

  const SectionButton = ({ id, label, icon: Icon }: any) => (
    <button
      onClick={() => setActiveSection(id)}
      className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold transition-colors w-full ${
        activeSection === id
          ? 'bg-slate-100 text-slate-900'
          : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
      }`}
    >
      <Icon size={16} /> {label}
    </button>
  );

  const updateColor = (key: keyof ThemeConfig['colors'], val: string) =>
    setTheme({ ...theme, colors: { ...theme.colors, [key]: val } });
  const updateShape = (key: keyof ThemeConfig['shape'], val: any) =>
    setTheme({ ...theme, shape: { ...theme.shape, [key]: val } });
  const updateLayout = (key: keyof ThemeConfig['layout'], val: any) =>
    setTheme({ ...theme, layout: { ...theme.layout, [key]: val } });

  const toggleCustomer = (key: keyof CustomerPortalConfig) =>
    setCustomerConfig({ ...customerConfig, [key]: !customerConfig[key] });
  const toggleEmployee = (key: keyof EmployeePortalConfig) =>
    setEmployeeConfig({ ...employeeConfig, [key]: !employeeConfig[key] });

  const setText = (key: TextKey, val: string) => {
    const v = val.trim();
    if (!v) {
      const next = { ...texts };
      delete (next as any)[key];
      setTexts(next);
      return;
    }
    setTexts({ ...texts, [key]: v });
  };

  const textInputs: Array<{ key: TextKey; label: string }> = [
    { key: 'widget_search_placeholder', label: 'Widget: Search placeholder' },
    { key: 'widget_categories', label: 'Widget: Categories title' },
    { key: 'widget_details', label: 'Widget: Details button' },
    { key: 'widget_book', label: 'Widget: Book button' },
    { key: 'wizard_back', label: 'Wizard: Back button' },
    { key: 'wizard_continue', label: 'Wizard: Continue button' },
    { key: 'wizard_confirmed', label: 'Wizard: Confirmed title' },
    { key: 'wizard_close', label: 'Wizard: Close button' },
    { key: 'offerform_title', label: 'Offer form: Title' },
    { key: 'offerform_pick_offer', label: 'Offer form: Offer picker' },
    { key: 'portal_no_data', label: 'Portal: Empty state' },
  ];

  return (
    <div
      className="w-80 border-r bg-[var(--studio-panel)] flex flex-col h-full z-30 shadow-xl"
      style={{ borderColor: 'var(--studio-border)' as any }}
    >
      <div className="p-4 border-b" style={{ borderColor: 'var(--studio-border)' as any }}>
        <h3 className="font-bold text-[var(--studio-text)] flex items-center gap-2">
          <Palette size={18} /> {t('studio_title')}
        </h3>
        <p className="text-xs text-[var(--studio-muted)]">Theme + toggles + texts</p>
      </div>

      <div className="flex p-2 border-b overflow-x-auto gap-1" style={{ borderColor: 'var(--studio-border)' as any }}>
        <SectionButton id="colors" label="Colors" icon={Palette} />
        <SectionButton id="shape" label="Shape" icon={Box} />
        <SectionButton id="layout" label="Layout" icon={Layers} />
        <SectionButton id="widget" label="Widget" icon={MousePointer2} />
        <SectionButton id="texts" label="Texts" icon={Type} />
        <SectionButton id="customer" label="Customer" icon={User} />
        <SectionButton id="employee" label="Employee" icon={Briefcase} />
      </div>

      <div className="flex-1 overflow-y-auto p-6 space-y-6">
        {activeSection === 'colors' && (
          <div className="space-y-4">
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Brand</h4>
            <ColorPicker label="Primary" value={theme.colors.primary} onChange={(v) => updateColor('primary', v)} />
            <ColorPicker label="Secondary" value={theme.colors.secondary} onChange={(v) => updateColor('secondary', v)} />
            <div className="h-px bg-slate-100 my-4" />
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Surfaces</h4>
            <ColorPicker label="Background" value={theme.colors.background} onChange={(v) => updateColor('background', v)} />
            <ColorPicker label="Surface" value={theme.colors.surface} onChange={(v) => updateColor('surface', v)} />
            <ColorPicker label="Border" value={theme.colors.border} onChange={(v) => updateColor('border', v)} />
            <div className="h-px bg-slate-100 my-4" />
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Text + States</h4>
            <ColorPicker label="Text" value={theme.colors.text} onChange={(v) => updateColor('text', v)} />
            <ColorPicker label="Text Muted" value={theme.colors.textMuted} onChange={(v) => updateColor('textMuted', v)} />
            <ColorPicker label="Success" value={theme.colors.success} onChange={(v) => updateColor('success', v)} />
            <ColorPicker label="Danger" value={theme.colors.danger} onChange={(v) => updateColor('danger', v)} />
          </div>
        )}

        {activeSection === 'shape' && (
          <div className="space-y-4">
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Geometry</h4>
            <RangeSlider label="Corner Radius" value={theme.shape.radius} min={0} max={28} unit="px" onChange={(v) => updateShape('radius', v)} />
            <RangeSlider label="Border Width" value={theme.shape.borderWidth} min={0} max={4} unit="px" onChange={(v) => updateShape('borderWidth', v)} />
            <SelectBox label="Shadow" value={theme.shape.shadow} options={['none', 'sm', 'md', 'lg', 'xl']} onChange={(v) => updateShape('shadow', v)} />
            <div className="h-px bg-slate-100 my-4" />
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Typography</h4>
            <SelectBox
              label="Font Family"
              value={theme.typography.fontFamily}
              options={['Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins', 'Playfair Display', 'Courier New']}
              onChange={(v) => setTheme({ ...theme, typography: { ...theme.typography, fontFamily: v } })}
            />
            <RangeSlider
              label="Font Scale"
              value={theme.typography.scale}
              min={80}
              max={125}
              unit="%"
              onChange={(v) => setTheme({ ...theme, typography: { ...theme.typography, scale: v } })}
            />
          </div>
        )}

        {activeSection === 'layout' && (
          <div className="space-y-4">
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Structure</h4>
            <ToggleSwitch label="Show Sidebar (desktop)" checked={theme.layout.showSidebar} onChange={(v) => updateLayout('showSidebar', v)} />
            <ToggleSwitch label="Show Footer (sidebar)" checked={theme.layout.showFooter} onChange={(v) => updateLayout('showFooter', v)} />
            <ToggleSwitch label="Compact Mode" checked={theme.layout.compactMode} onChange={(v) => updateLayout('compactMode', v)} />

            <div className="h-px bg-slate-100 my-4" />
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Features</h4>
            <ToggleSwitch label="Show Avatar" checked={!!theme.layout.showUserAvatar} onChange={(v) => updateLayout('showUserAvatar', v)} />
            <ToggleSwitch label="Show Search" checked={!!theme.layout.showSearch} onChange={(v) => updateLayout('showSearch', v)} />
            <ToggleSwitch label="Enable Filters" checked={!!theme.layout.showFilters} onChange={(v) => updateLayout('showFilters', v)} />
            <ToggleSwitch label="Enable Animations" checked={!!theme.layout.enableAnimations} onChange={(v) => updateLayout('enableAnimations', v)} />
            <ToggleSwitch label="Show Dev Overlays" checked={!!theme.layout.showDevOverlays} onChange={(v) => updateLayout('showDevOverlays', v)} />

            <div className="h-px bg-slate-100 my-4" />
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Locale</h4>
            <SelectBox
              label="UI Language"
              value={theme.locale.uiLanguage}
              options={['en-US', 'de-CH', 'de-DE', 'fr-CH', 'it-CH']}
              onChange={(v) => setTheme({ ...theme, locale: { ...theme.locale, uiLanguage: v } })}
            />
          </div>
        )}

        {activeSection === 'widget' && (
          <div className="space-y-4">
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Behavior</h4>
            <SelectBox label="Start View" value={widgetBehavior.startView} options={['categories', 'services']} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, startView: v })} />
            <SelectBox label="Filter Layout" value={widgetBehavior.filterLayout} options={['none', 'top', 'sidebar']} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, filterLayout: v })} />
            <SelectBox label="View Layout" value={widgetBehavior.viewLayout} options={['grid', 'list']} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, viewLayout: v })} />
            <SelectBox label="Interaction" value={widgetBehavior.interactionMode} options={['modal', 'inline']} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, interactionMode: v })} />
            <SelectBox label="Schedule Mode" value={widgetBehavior.scheduleMode} options={['all', 'limit', 'interval']} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, scheduleMode: v })} />
            {widgetBehavior.scheduleMode === 'limit' && (
              <RangeSlider label="Schedule Limit" value={widgetBehavior.scheduleLimit} min={1} max={8} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, scheduleLimit: v })} />
            )}

            <div className="h-px bg-slate-100 my-4" />
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">UI Toggles</h4>
            <ToggleSwitch label="Wizard Sidebar (modal)" checked={widgetBehavior.showWizardSidebar} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, showWizardSidebar: v })} />
            <ToggleSwitch label="Show Details Button" checked={widgetBehavior.showDetailsButton} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, showDetailsButton: v })} />
            <ToggleSwitch label="Show Price Badge" checked={widgetBehavior.showPriceBadge} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, showPriceBadge: v })} />
            <ToggleSwitch label="Show Schedule Cards" checked={widgetBehavior.showScheduleCards} onChange={(v) => setWidgetBehavior({ ...widgetBehavior, showScheduleCards: v })} />
          </div>
        )}

        {activeSection === 'texts' && (
          <div className="space-y-2">
            <div className="text-xs text-[var(--studio-muted)] mb-2">
              Overrides apply per saved design. Leave empty to use defaults from the selected language.
            </div>
            {textInputs.map((row) => (
              <TextField
                key={row.key}
                label={row.label}
                value={texts[row.key] ?? ''}
                placeholder={t(row.key)}
                onChange={(v) => setText(row.key, v)}
              />
            ))}
          </div>
        )}

        {activeSection === 'customer' && (
          <div className="space-y-4">
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
              Customer Portal Sections
            </h4>
            {Object.entries(customerConfig).map(([k, v]) => (
              <ToggleSwitch
                key={k}
                label={k.replace('show', '')}
                checked={!!v}
                onChange={() => toggleCustomer(k as keyof CustomerPortalConfig)}
              />
            ))}
          </div>
        )}

        {activeSection === 'employee' && (
          <div className="space-y-4">
            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
              Employee App Sections
            </h4>
            {Object.entries(employeeConfig).map(([k, v]) => (
              <ToggleSwitch
                key={k}
                label={k.replace('show', '')}
                checked={!!v}
                onChange={() => toggleEmployee(k as keyof EmployeePortalConfig)}
              />
            ))}
          </div>
        )}
      </div>

      <div className="p-4 border-t bg-slate-50 space-y-2" style={{ borderColor: 'var(--studio-border)' as any }}>
        <button
          onClick={() => setTheme(defaultTheme)}
          className="w-full py-2 border rounded-lg text-xs font-bold hover:bg-white transition-colors"
          style={{ borderColor: 'var(--studio-border)' as any, color: 'var(--studio-text)' as any }}
        >
          {t('reset_theme')}
        </button>
        <button
          onClick={() => {
            setWidgetBehavior(defaultWidgetBehavior);
            setCustomerConfig(defaultCustomerPortalConfig);
            setEmployeeConfig(defaultEmployeePortalConfig);
          }}
          className="w-full py-2 border rounded-lg text-xs font-bold hover:bg-white transition-colors"
          style={{ borderColor: 'var(--studio-border)' as any, color: 'var(--studio-text)' as any }}
        >
          {t('reset_toggles')}
        </button>
        <div className="text-[11px] text-[var(--studio-muted)]">
          {t('editor_editing')}:&nbsp;
          <span className="font-semibold text-[var(--studio-text)]">
            {activeTab === 'widget'
              ? t('tab_widget')
              : activeTab === 'offerForm'
              ? t('tab_offerForm')
              : activeTab === 'customer'
              ? t('tab_customer')
              : t('tab_employee')}
          </span>
        </div>
      </div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                         DEVICE FRAME + THEME WRAPPER                       */
/* -------------------------------------------------------------------------- */

function deviceDims(device: Device, orientation: Orientation) {
  if (device === 'desktop') return { w: '100%', h: '100%' };
  if (device === 'tablet') {
    return orientation === 'portrait'
      ? { w: 768, h: 1024 }
      : { w: 1024, h: 768 };
  }
  // mobile
  return orientation === 'portrait'
    ? { w: 375, h: 812 }
    : { w: 812, h: 375 };
}

const DeviceFrame: React.FC<{
  device: Device;
  orientation: Orientation;
  children: React.ReactNode;
}> = ({ device, orientation, children }) => {
  const dim = deviceDims(device, orientation);
  const isMobileOrTablet = device !== 'desktop';

  const style = isMobileOrTablet
    ? { width: dim.w as number, height: dim.h as number }
    : { width: '100%', height: '100%' };

  const frameClass = isMobileOrTablet
    ? 'border-[12px] border-slate-800 rounded-[2.5rem] shadow-2xl bg-slate-900'
    : 'w-full h-full border border-slate-200 rounded-xl shadow-sm bg-white';

  const screenClass = isMobileOrTablet
    ? 'rounded-[1.8rem] overflow-hidden bg-white relative'
    : 'w-full h-full overflow-hidden bg-white relative';

  return (
    <div className="flex items-center justify-center p-6 min-h-full overflow-auto bg-slate-100/50">
      <div className={`relative transition-all duration-300 ease-in-out ${frameClass}`} style={style}>
        {device === 'mobile' && orientation === 'portrait' && (
          <div className="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-800 rounded-b-xl z-50 pointer-events-none" />
        )}
        <div className={`w-full h-full ${screenClass}`}>{children}</div>
      </div>
    </div>
  );
};

const SimulationWrapper: React.FC<{
  theme: ThemeConfig;
  children: React.ReactNode;
}> = ({ theme, children }) => {
  return (
    <div
      className={`w-full h-full flex flex-col bg-[var(--c-bg)] text-[var(--c-text)] ${
        theme.layout.enableAnimations ? '' : 'transition-none'
      }`}
      style={{
        ...(themeToVars(theme) as any),
        fontFamily: 'var(--font-family)' as any,
        fontSize: 'calc(1rem * var(--font-scale))' as any,
      }}
    >
      {children}
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                              SMALL UTILITIES                               */
/* -------------------------------------------------------------------------- */

function uid(prefix = 'id') {
  return `${prefix}_${Date.now()}_${Math.random().toString(16).slice(2)}`;
}

function safeJsonParse<T>(val: string | null): T | null {
  if (!val) return null;
  try {
    return JSON.parse(val) as T;
  } catch {
    return null;
  }
}

function clampStr(s: string, max = 40) {
  const t = (s ?? '').trim();
  return t.length > max ? `${t.slice(0, max - 1)}…` : t;
}

async function copyToClipboard(text: string) {
  try {
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    return false;
  }
}

/* -------------------------------------------------------------------------- */
/*                                CALENDAR UI                                 */
/* -------------------------------------------------------------------------- */

const MonthCalendar: React.FC<{
  selectedDate: Date | null;
  onSelect: (date: Date) => void;
  uiLanguage: ThemeConfig['locale']['uiLanguage'];
}> = ({ selectedDate, onSelect, uiLanguage }) => {
  const [viewDate, setViewDate] = useState(selectedDate || new Date());

  const getDaysInMonth = (year: number, month: number) => new Date(year, month + 1, 0).getDate();
  const getFirstDayOfMonth = (year: number, month: number) => {
    const day = new Date(year, month, 1).getDay();
    return day === 0 ? 6 : day - 1; // monday start
  };

  const daysInMonth = getDaysInMonth(viewDate.getFullYear(), viewDate.getMonth());
  const firstDay = getFirstDayOfMonth(viewDate.getFullYear(), viewDate.getMonth());

  const prevMonthDays = getDaysInMonth(viewDate.getFullYear(), viewDate.getMonth() - 1);
  const paddingDays = Array.from({ length: firstDay }, (_, i) => prevMonthDays - firstDay + i + 1);
  const currentDays = Array.from({ length: daysInMonth }, (_, i) => i + 1);

  const totalCells = 42;
  const remainingCells = totalCells - paddingDays.length - currentDays.length;
  const nextDays = Array.from({ length: remainingCells }, (_, i) => i + 1);

  const changeMonth = (delta: number) => {
    const newDate = new Date(viewDate);
    newDate.setMonth(newDate.getMonth() + delta);
    setViewDate(newDate);
  };

  const weekdayLabels =
    uiLanguage.startsWith('de')
      ? ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So']
      : uiLanguage.startsWith('fr')
      ? ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di']
      : uiLanguage.startsWith('it')
      ? ['Lu', 'Ma', 'Me', 'Gi', 'Ve', 'Sa', 'Do']
      : ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

  return (
    <SimCard className="p-4 select-none">
      <div className="flex justify-between items-center mb-4">
        <h4 className="font-bold text-[var(--c-text)]">
          {viewDate.toLocaleDateString(uiLanguage, { month: 'long', year: 'numeric' })}
        </h4>
        <div className="flex gap-1">
          <button onClick={() => changeMonth(-1)} className="p-1 hover:bg-[var(--c-bg)] rounded text-[var(--c-text-muted)]">
            <ChevronLeft size={20} />
          </button>
          <button onClick={() => changeMonth(1)} className="p-1 hover:bg-[var(--c-bg)] rounded text-[var(--c-text-muted)]">
            <ChevronRight size={20} />
          </button>
        </div>
      </div>

      <div className="grid grid-cols-7 gap-1 mb-2 text-center">
        {weekdayLabels.map((d) => (
          <div key={d} className="text-xs font-bold text-[var(--c-text-muted)] uppercase">
            {d}
          </div>
        ))}
      </div>

      <div className="grid grid-cols-7 gap-1 text-sm">
        {paddingDays.map((d) => (
          <div key={`prev-${d}`} className="h-9 flex items-center justify-center text-slate-300">
            {d}
          </div>
        ))}
        {currentDays.map((d) => {
          const dateObj = new Date(viewDate.getFullYear(), viewDate.getMonth(), d);
          const isSelected = selectedDate?.toDateString() === dateObj.toDateString();
          const isToday = new Date().toDateString() === dateObj.toDateString();

          return (
            <button
              key={d}
              onClick={() => onSelect(dateObj)}
              className={[
                'h-9 rounded-[calc(var(--radius)*0.75)] flex items-center justify-center font-semibold transition-all',
                isSelected ? 'text-white' : 'text-[var(--c-text)] hover:bg-[var(--c-bg)]',
                isToday && !isSelected ? 'font-extrabold' : '',
              ].join(' ')}
              style={{
                backgroundColor: isSelected
                  ? 'var(--c-primary)'
                  : isToday
                  ? 'color-mix(in srgb, var(--c-primary) 10%, transparent)'
                  : 'transparent',
                color: isSelected ? '#fff' : isToday ? 'var(--c-primary)' : 'var(--c-text)',
              }}
            >
              {d}
            </button>
          );
        })}
        {nextDays.map((d) => (
          <div key={`next-${d}`} className="h-9 flex items-center justify-center text-slate-300">
            {d}
          </div>
        ))}
      </div>
    </SimCard>
  );
};

/* -------------------------------------------------------------------------- */
/*                              BOOKING WIZARD                                */
/* -------------------------------------------------------------------------- */

const WizardSidebar: React.FC<{
  currentStep: BookingStep;
  steps: BookingStep[];
  t: (k: TextKey) => string;
}> = ({ currentStep, steps, t }) => {
  const stepLabels: Record<BookingStep, string> = {
    extras: t('wizard_extras'),
    datetime: t('wizard_datetime'),
    details: t('wizard_details'),
    payment: t('wizard_payment'),
    confirmation: t('wizard_confirmation'),
  };

  return (
    <div
      className="w-52 bg-[var(--c-bg)] border-r border-[var(--c-border)] p-4 hidden sm:block"
      style={{ borderRightWidth: 'var(--border-w)' as any }}
    >
      <h4 className="text-xs font-bold text-[var(--c-text-muted)] uppercase mb-4 tracking-wider">
        {t('wizard_progress')}
      </h4>
      <div className="space-y-6 relative">
        <div className="absolute left-3 top-2 bottom-2 w-0.5 bg-[var(--c-border)] -z-10" />
        {steps.map((s, idx) => {
          const currentIndex = steps.indexOf(currentStep);
          const isActive = s === currentStep;
          const isCompleted = steps.indexOf(s) < currentIndex;
          return (
            <div key={s} className="flex items-center gap-3 relative">
              <div
                className={[
                  'w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold border-2 transition-all shrink-0 bg-[var(--c-surface)]',
                  isActive
                    ? 'text-[var(--c-primary)]'
                    : isCompleted
                    ? 'text-[var(--c-success)]'
                    : 'text-slate-300',
                ].join(' ')}
                style={{
                  borderColor: isActive
                    ? 'var(--c-primary)'
                    : isCompleted
                    ? 'var(--c-success)'
                    : 'var(--c-border)',
                }}
              >
                {isCompleted ? <Check size={12} strokeWidth={3} /> : idx + 1}
              </div>
              <span
                className={`text-xs font-semibold ${
                  isActive ? 'text-[var(--c-text)]' : 'text-[var(--c-text-muted)]'
                }`}
              >
                {stepLabels[s]}
              </span>
            </div>
          );
        })}
      </div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                           BOOKING WIDGET SIMULATION                         */
/* -------------------------------------------------------------------------- */

const BookingWidgetSimulation: React.FC<{
  device: Device;
  theme: ThemeConfig;
  behavior: WidgetBehaviorConfig;
  t: (k: TextKey) => string;
}> = ({ device, theme, behavior, t }) => {
  const { services, offerCategories, formatPrice } = useApp();

  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [activeBookingId, setActiveBookingId] = useState<string | null>(null);
  const [modalService, setModalService] = useState<ServiceItem | null>(null);
  const [infoService, setInfoService] = useState<ServiceItem | null>(null);
  const [query, setQuery] = useState('');

  const visibleServices = services
    .filter((s) => !selectedCategory || s.category === selectedCategory || s.categories?.includes(selectedCategory))
    .filter((s) => {
      const q = query.trim().toLowerCase();
      if (!q) return true;
      return (s.title || '').toLowerCase().includes(q) || (s.description || '').toLowerCase().includes(q);
    });

  const showTopFilter =
    behavior.filterLayout === 'top' && (!!theme.layout.showSearch || !!theme.layout.showFilters);
  const showSidebarFilter =
    behavior.filterLayout === 'sidebar' && (!!theme.layout.showSearch || !!theme.layout.showFilters);

  const renderScheduleInfo = (service: ServiceItem) => {
    if (!behavior.showScheduleCards) return null;
    if (service.type !== 'Event' && service.type !== 'Online Course') return null;

    if (!service.sessions || service.sessions.length === 0) {
      return <div className="text-xs text-[var(--c-text-muted)] italic">No scheduled dates</div>;
    }

    if (behavior.scheduleMode === 'interval') {
      return (
        <div
          className="mt-2 text-xs text-[var(--c-text)] p-2 rounded-[var(--radius)] border bg-[var(--c-bg)]"
          style={{ borderColor: 'var(--c-border)', borderWidth: 'var(--border-w)' as any }}
        >
          Every Mon, Wed at 18:00
        </div>
      );
    }

    const limit = behavior.scheduleMode === 'all' ? service.sessions.length : behavior.scheduleLimit;
    return (
      <div className="mt-2 space-y-1">
        {service.sessions.slice(0, limit).map((s: any) => (
          <div
            key={s.id}
            className="text-xs flex justify-between p-1.5 rounded-[calc(var(--radius)*0.75)] border bg-[var(--c-bg)]"
            style={{ borderColor: 'var(--c-border)', borderWidth: 'var(--border-w)' as any }}
          >
            <span>{new Date(s.date).toLocaleDateString(theme.locale.uiLanguage)}</span>
            <span>{s.startTime}</span>
          </div>
        ))}
        {service.sessions.length > limit && (
          <div className="text-xs font-bold pl-1" style={{ color: 'var(--c-primary)' }}>
            +{service.sessions.length - limit} more...
          </div>
        )}
      </div>
    );
  };

  const categories =
    (offerCategories?.length ? offerCategories : ['Wellness', 'Fitness', 'Health'])
      .slice(0, 12)
      .map((cat: any) => (typeof cat === 'string' ? cat : cat?.name ?? 'Category'));

  return (
    <div className="flex h-full bg-[var(--c-bg)] relative overflow-hidden">
      {!!theme.layout.showDevOverlays && (
        <div className="absolute top-4 left-4 right-4 z-40 bg-slate-900/95 backdrop-blur text-white p-3 rounded-xl shadow-2xl flex flex-col gap-2 opacity-90 hover:opacity-100">
          <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
            <Settings size={12} /> Widget Preview
          </div>
          <div className="text-[11px] text-slate-300">Layout + texts are controlled by the editor.</div>
        </div>
      )}

      {showSidebarFilter && (
        <div
          className="w-64 bg-[var(--c-surface)] border-r border-[var(--c-border)] p-4 hidden md:flex md:flex-col gap-4"
          style={{ borderRightWidth: 'var(--border-w)' as any }}
        >
          {!!theme.layout.showSearch && (
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--c-text-muted)]" size={16} />
              <SimInput
                placeholder={t('widget_search_placeholder')}
                className="pl-9"
                value={query}
                onChange={(e) => setQuery((e.target as HTMLInputElement).value)}
              />
            </div>
          )}

          {!!theme.layout.showFilters && (
            <div className="space-y-2">
              <div className="text-xs font-bold text-[var(--c-text-muted)] uppercase">{t('widget_categories')}</div>
              <div className="flex flex-wrap gap-2">
                {categories.map((label) => {
                  const active = selectedCategory === label;
                  return (
                    <button
                      key={label}
                      onClick={() => setSelectedCategory(active ? null : label)}
                      className="px-3 py-1.5 rounded-[var(--radius)] text-xs font-semibold border"
                      style={{
                        backgroundColor: active ? 'var(--c-primary)' : 'var(--c-surface)',
                        color: active ? '#fff' : 'var(--c-text)',
                        borderColor: active ? 'transparent' : 'var(--c-border)',
                        borderWidth: 'var(--border-w)' as any,
                      }}
                    >
                      {label}
                    </button>
                  );
                })}
              </div>
              {selectedCategory && (
                <button
                  onClick={() => setSelectedCategory(null)}
                  className="text-xs font-extrabold mt-2"
                  style={{ color: 'var(--c-primary)' }}
                >
                  {t('widget_clear')}
                </button>
              )}
            </div>
          )}
        </div>
      )}

      <div className={`flex-1 flex flex-col h-full ${!!theme.layout.showDevOverlays ? 'pt-24' : 'pt-4'}`}>
        <div className={`flex-1 overflow-y-auto ${theme.layout.compactMode ? 'p-3' : 'p-4'} bg-[var(--c-bg)]`}>
          {showTopFilter && (
            <div className="flex gap-2 mb-4">
              {!!theme.layout.showSearch && (
                <div className="relative flex-1">
                  <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--c-text-muted)]" size={16} />
                  <SimInput
                    placeholder={t('widget_search_placeholder')}
                    className="pl-9"
                    value={query}
                    onChange={(e) => setQuery((e.target as HTMLInputElement).value)}
                  />
                </div>
              )}
              {!!theme.layout.showFilters && (
                <SimButton
                  variant="secondary"
                  className="px-3"
                  title="Filters"
                  onClick={() => {
                    if (categories.length === 0) return;
                    const idx = selectedCategory ? categories.indexOf(selectedCategory) : -1;
                    const next = idx >= categories.length - 1 ? null : categories[idx + 1];
                    setSelectedCategory(next);
                  }}
                >
                  <Filter size={18} />
                </SimButton>
              )}
            </div>
          )}

          {(behavior.startView === 'categories' || categories.length > 0) && behavior.filterLayout !== 'sidebar' && (
            <div className="mb-4">
              <div className="flex items-center justify-between">
                <h3 className="font-extrabold text-[var(--c-text)]">{t('widget_categories')}</h3>
                {selectedCategory && (
                  <button
                    onClick={() => setSelectedCategory(null)}
                    className="text-xs font-extrabold"
                    style={{ color: 'var(--c-primary)' }}
                  >
                    {t('widget_clear')}
                  </button>
                )}
              </div>
              <div className="flex gap-2 overflow-x-auto pb-2 no-scrollbar mt-2">
                {categories.map((label) => {
                  const active = selectedCategory === label;
                  return (
                    <button
                      key={label}
                      onClick={() => setSelectedCategory(active ? null : label)}
                      className="px-4 py-2 rounded-[var(--radius)] text-sm font-semibold border whitespace-nowrap"
                      style={{
                        backgroundColor: active ? 'var(--c-primary)' : 'var(--c-surface)',
                        color: active ? '#fff' : 'var(--c-text)',
                        borderColor: active ? 'transparent' : 'var(--c-border)',
                        borderWidth: 'var(--border-w)' as any,
                      }}
                    >
                      {label}
                    </button>
                  );
                })}
              </div>
            </div>
          )}

          <div
            className={`grid ${
              behavior.viewLayout === 'grid'
                ? device === 'mobile'
                  ? 'grid-cols-1'
                  : 'grid-cols-2'
                : 'grid-cols-1'
            } gap-4`}
          >
            {visibleServices.map((service) => (
              <SimCard key={service.id} className="flex flex-col">
                <div
                  className={`${
                    behavior.viewLayout === 'grid' ? 'h-32' : 'h-24 w-24 float-left'
                  } bg-slate-100 relative overflow-hidden`}
                >
                  {service.image ? <img src={service.image} className="w-full h-full object-cover" alt="" /> : null}
                  {behavior.showPriceBadge && (
                    <div className="absolute bottom-2 right-2">
                      <SimBadge color="primary">{formatPrice(service.price)}</SimBadge>
                    </div>
                  )}
                </div>

                <div className={`${theme.layout.compactMode ? 'p-3' : 'p-4'} flex-1 flex flex-col`}>
                  <div className="flex justify-between gap-3">
                    <h4 className="font-extrabold text-[var(--c-text)] text-sm line-clamp-1">{service.title}</h4>
                    {behavior.viewLayout === 'list' && (
                      <span className="font-extrabold text-sm" style={{ color: 'var(--c-primary)' }}>
                        {formatPrice(service.price)}
                      </span>
                    )}
                  </div>

                  <p className="text-xs text-[var(--c-text-muted)] line-clamp-2 mb-2">
                    {(service.description || '').replace(/<[^>]*>?/gm, '')}
                  </p>

                  {renderScheduleInfo(service)}

                  <div className="mt-auto pt-3 flex gap-2">
                    {behavior.showDetailsButton && (
                      <SimButton variant="secondary" className="flex-1 text-xs" onClick={() => setInfoService(service)}>
                        {t('widget_details')}
                      </SimButton>
                    )}

                    <SimButton
                      variant="primary"
                      className="flex-1 text-xs"
                      onClick={() => {
                        if (behavior.interactionMode === 'modal') setModalService(service);
                        else setActiveBookingId(activeBookingId === service.id ? null : service.id);
                      }}
                    >
                      {behavior.interactionMode === 'inline' && activeBookingId === service.id
                        ? t('widget_close')
                        : t('widget_book')}
                    </SimButton>
                  </div>
                </div>

                {activeBookingId === service.id && behavior.interactionMode === 'inline' && (
                  <div
                    className="border-t border-[var(--c-border)] bg-[var(--c-bg)]"
                    style={{ borderTopWidth: 'var(--border-w)' as any }}
                  >
                    <BookingWizard
                      service={service}
                      layout="inline"
                      showSidebar={behavior.showWizardSidebar}
                      uiLanguage={theme.locale.uiLanguage}
                      t={t}
                      onClose={() => setActiveBookingId(null)}
                    />
                  </div>
                )}
              </SimCard>
            ))}

            {visibleServices.length === 0 && (
              <div className="col-span-full text-center py-10 text-[var(--c-text-muted)] italic">
                {t('portal_no_data')}
              </div>
            )}
          </div>
        </div>

        {behavior.interactionMode === 'modal' && modalService && (
          <div className="absolute inset-0 z-40 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div
              className="bg-[var(--c-surface)] w-full max-w-4xl h-[600px] rounded-[calc(var(--radius)*1.5)] overflow-hidden flex flex-col"
              style={{ boxShadow: 'var(--shadow)' as any }}
            >
              <div
                className="p-4 border-b border-[var(--c-border)] flex justify-between items-center bg-[var(--c-bg)]"
                style={{ borderBottomWidth: 'var(--border-w)' as any }}
              >
                <h3 className="font-extrabold text-[var(--c-text)]">{modalService.title}</h3>
                <button
                  onClick={() => setModalService(null)}
                  className="text-[var(--c-text-muted)] hover:text-[var(--c-text)]"
                >
                  <X size={16} />
                </button>
              </div>
              <div className="flex-1 overflow-hidden flex">
                <BookingWizard
                  service={modalService}
                  layout="modal"
                  showSidebar={behavior.showWizardSidebar}
                  uiLanguage={theme.locale.uiLanguage}
                  t={t}
                  onClose={() => setModalService(null)}
                />
              </div>
            </div>
          </div>
        )}

        {infoService && <ServiceInfoModal service={infoService} t={t} onClose={() => setInfoService(null)} />}
      </div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                       OFFER BOOKING FORM (SEPARATE TAB)                    */
/* -------------------------------------------------------------------------- */

const OfferBookingFormSimulation: React.FC<{
  theme: ThemeConfig;
  device: Device;
  behavior: WidgetBehaviorConfig;
  t: (k: TextKey) => string;
}> = ({ theme, device, behavior, t }) => {
  const { services } = useApp();
  const offers = services;

  const [offerId, setOfferId] = useState<string>(() => offers?.[0]?.id ?? '');
  const offer = offers.find((s) => s.id === offerId) ?? offers[0] ?? null;

  useEffect(() => {
    if (!offerId && offers?.[0]?.id) setOfferId(offers[0].id);
  }, [offerId, offers]);

  return (
    <div className="flex flex-col h-full bg-[var(--c-bg)]">
      <div
        className={`${theme.layout.compactMode ? 'p-4' : 'p-6'} border-b border-[var(--c-border)] bg-[var(--c-surface)]`}
        style={{ borderBottomWidth: 'var(--border-w)' as any }}
      >
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="text-xs font-extrabold uppercase tracking-wide text-[var(--c-text-muted)]">
              {t('offerform_title')}
            </div>
            <div className="text-lg font-extrabold text-[var(--c-text)] mt-1">
              {offer?.title ?? t('portal_no_data')}
            </div>
          </div>

          <div className="w-full md:w-[420px]">
            <div className="text-xs font-bold text-[var(--c-text-muted)] mb-1">{t('offerform_pick_offer')}</div>
            <select
              value={offerId}
              onChange={(e) => setOfferId(e.target.value)}
              className="w-full border border-[var(--c-border)] rounded-[var(--radius)] px-3 py-2 text-sm bg-[var(--c-surface)] focus:outline-none focus:ring-2 focus:ring-[var(--c-primary)]"
              style={{ borderWidth: 'var(--border-w)' as any }}
            >
              {offers.map((s) => (
                <option key={s.id} value={s.id}>
                  {clampStr(s.title || 'Offer', 60)}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      <div className="flex-1 overflow-hidden p-4">
        {offer ? (
          <SimCard className="h-full">
            <div className="h-full overflow-hidden flex flex-col">
              <div
                className="p-4 border-b border-[var(--c-border)] bg-[var(--c-bg)]"
                style={{ borderBottomWidth: 'var(--border-w)' as any }}
              >
                <div className="text-sm font-extrabold text-[var(--c-text)] flex items-center gap-2">
                  <ClipboardList size={16} className="text-[var(--c-primary)]" />
                  Step-by-step form
                </div>
                <div className="text-xs text-[var(--c-text-muted)] mt-1">
                  This is separated from the website widget preview.
                </div>
              </div>

              <div className="flex-1 overflow-hidden">
                <BookingWizard
                  service={offer}
                  layout="modal"
                  showSidebar={behavior.showWizardSidebar}
                  uiLanguage={theme.locale.uiLanguage}
                  t={t}
                  onClose={() => {}}
                />
              </div>
            </div>
          </SimCard>
        ) : (
          <div className="h-full flex items-center justify-center text-[var(--c-text-muted)] italic">
            {t('portal_no_data')}
          </div>
        )}
      </div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                           CUSTOMER PORTAL (SIMPLIFIED)                      */
/* -------------------------------------------------------------------------- */

const CustomerPortalSimulation: React.FC<{
  theme: ThemeConfig;
  config: CustomerPortalConfig;
  t: (k: TextKey) => string;
}> = ({ theme, config, t }) => {
  const { appointments, invoices, formatPrice } = useApp();
  const [activePage, setActivePage] = useState<CustomerPage>('dashboard');

  const canShow = (p: CustomerPage) =>
    !!config[`show${p.charAt(0).toUpperCase() + p.slice(1)}` as keyof CustomerPortalConfig];

  useEffect(() => {
    const order: CustomerPage[] = ['dashboard', 'appointments', 'invoices', 'profile', 'settings'];
    if (!canShow(activePage)) {
      const next = order.find((p) => canShow(p));
      if (next) setActivePage(next);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [config]);

  const NavBtn = ({ id, label, icon: Icon }: { id: CustomerPage; label: string; icon: any }) => {
    const active = activePage === id;
    return (
      <button
        onClick={() => setActivePage(id)}
        className={`w-full text-left px-4 py-3 rounded-[var(--radius)] text-sm font-semibold flex items-center gap-3 transition-colors ${
          active ? 'bg-[var(--c-bg)]' : 'hover:bg-[var(--c-bg)]'
        }`}
        style={{ color: active ? 'var(--c-primary)' : 'var(--c-text-muted)' }}
      >
        <Icon size={18} /> {label}
      </button>
    );
  };

  const Dashboard = () => (
    <div className="space-y-4">
      <h2 className="text-2xl font-extrabold text-[var(--c-text)]">Welcome back</h2>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <SimCard className="p-4">
          <div className="text-xs font-bold text-[var(--c-text-muted)] uppercase">Upcoming</div>
          <div className="text-2xl font-extrabold text-[var(--c-text)] mt-1">
            {appointments.filter((a) => a.status === 'Confirmed').length}
          </div>
        </SimCard>
        <SimCard className="p-4">
          <div className="text-xs font-bold text-[var(--c-text-muted)] uppercase">Invoices</div>
          <div className="text-2xl font-extrabold text-[var(--c-text)] mt-1">
            {invoices.filter((i) => i.category === 'Customer').length}
          </div>
        </SimCard>
        <SimCard className="p-4">
          <div className="text-xs font-bold text-[var(--c-text-muted)] uppercase">Support</div>
          <div className="text-sm text-[var(--c-text-muted)] mt-1">chat@example.com</div>
        </SimCard>
      </div>
    </div>
  );

  const Appointments = () => (
    <div className="space-y-3">
      <h2 className="text-xl font-extrabold text-[var(--c-text)]">My Appointments</h2>
      {appointments.length === 0 ? (
        <div className="text-[var(--c-text-muted)] italic">{t('portal_no_data')}</div>
      ) : (
        appointments.slice(0, 8).map((apt) => (
          <SimCard key={apt.id} className="p-4 flex items-center justify-between">
            <div className="min-w-0">
              <div className="font-extrabold text-[var(--c-text)] truncate">{apt.serviceName}</div>
              <div className="text-xs text-[var(--c-text-muted)] mt-1 flex items-center gap-2">
                <CalendarIcon size={12} /> {apt.date} <Clock size={12} /> {apt.startTime}
              </div>
            </div>
            <div className="text-right shrink-0">
              <div className="font-extrabold text-[var(--c-text)]">{formatPrice(apt.price)}</div>
              <SimBadge color={apt.status === 'Confirmed' ? 'success' : 'secondary'}>{apt.status}</SimBadge>
            </div>
          </SimCard>
        ))
      )}
    </div>
  );

  const Invoices = () => (
    <div className="space-y-3">
      <h2 className="text-xl font-extrabold text-[var(--c-text)]">My Invoices</h2>
      {invoices.filter((i) => i.category === 'Customer').length === 0 ? (
        <div className="text-[var(--c-text-muted)] italic">{t('portal_no_data')}</div>
      ) : (
        invoices
          .filter((i) => i.category === 'Customer')
          .slice(0, 10)
          .map((inv) => (
            <SimCard key={inv.id} className="p-4 flex items-center justify-between gap-4">
              <div className="flex items-center gap-3 min-w-0">
                <div
                  className="p-2 rounded-lg"
                  style={{
                    background: 'color-mix(in srgb, var(--c-primary) 10%, transparent)',
                    color: 'var(--c-primary)',
                  }}
                >
                  <FileText size={16} />
                </div>
                <div className="min-w-0">
                  <div className="font-extrabold text-[var(--c-text)] truncate">Invoice #{inv.id}</div>
                  <div className="text-xs text-[var(--c-text-muted)]">{inv.date}</div>
                </div>
              </div>
              <div className="text-right shrink-0">
                <div className="font-extrabold text-[var(--c-text)]">{formatPrice(inv.amount)}</div>
                <SimBadge
                  color={inv.status === 'Paid' ? 'success' : inv.status === 'Overdue' ? 'danger' : 'secondary'}
                >
                  {inv.status}
                </SimBadge>
              </div>
            </SimCard>
          ))
      )}
    </div>
  );

  const Profile = () => (
    <div className="space-y-4 max-w-xl">
      <h2 className="text-xl font-extrabold text-[var(--c-text)]">Profile</h2>
      <SimCard className="p-6 space-y-3">
        <div className="text-sm text-[var(--c-text-muted)]">This is a lightweight preview.</div>
        <div className="grid grid-cols-2 gap-3">
          <SimInput placeholder="First Name" defaultValue="Sarah" />
          <SimInput placeholder="Last Name" defaultValue="Jenkins" />
          <SimInput className="col-span-2" placeholder="Email" defaultValue="sarah@example.com" />
        </div>
        <SimButton variant="primary">Save</SimButton>
      </SimCard>
    </div>
  );

  const SettingsView = () => (
    <div className="space-y-4 max-w-xl">
      <h2 className="text-xl font-extrabold text-[var(--c-text)]">Settings</h2>
      <SimCard className="p-6 space-y-5">
        <ToggleSwitch label="Email Notifications" checked={true} onChange={() => {}} />
        <ToggleSwitch label="Security Alerts" checked={false} onChange={() => {}} />
      </SimCard>
    </div>
  );

  const content =
    activePage === 'dashboard' ? (
      <Dashboard />
    ) : activePage === 'appointments' ? (
      <Appointments />
    ) : activePage === 'invoices' ? (
      <Invoices />
    ) : activePage === 'profile' ? (
      <Profile />
    ) : (
      <SettingsView />
    );

  return (
    <div className="flex h-full bg-[var(--c-bg)] relative">
      {!!theme.layout.showSidebar && (
        <div
          className="hidden md:flex w-64 bg-[var(--c-surface)] border-r border-[var(--c-border)] flex-col"
          style={{ borderRightWidth: 'var(--border-w)' as any }}
        >
          <div
            className="p-6 border-b border-[var(--c-border)]"
            style={{ borderBottomWidth: 'var(--border-w)' as any }}
          >
            <div className="font-extrabold text-xl flex items-center gap-2" style={{ color: 'var(--c-primary)' }}>
              <div
                className="w-8 h-8 rounded-lg flex items-center justify-center text-white"
                style={{ background: 'var(--c-primary)' }}
              >
                P
              </div>
              Portal
            </div>
          </div>
          <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
            {canShow('dashboard') && <NavBtn id="dashboard" label="Dashboard" icon={Layout} />}
            {canShow('appointments') && <NavBtn id="appointments" label="Appointments" icon={CalendarIcon} />}
            {canShow('invoices') && <NavBtn id="invoices" label="Invoices" icon={FileText} />}
            {canShow('profile') && <NavBtn id="profile" label="Profile" icon={User} />}
            {canShow('settings') && <NavBtn id="settings" label="Settings" icon={Settings} />}
          </nav>

          {config.showLogout && (
            <div
              className="p-4 border-t border-[var(--c-border)]"
              style={{ borderTopWidth: 'var(--border-w)' as any }}
            >
              <button
                className="w-full text-left px-4 py-3 rounded-[var(--radius)] text-sm font-semibold flex items-center gap-3 hover:bg-[var(--c-bg)] transition-colors"
                style={{ color: 'var(--c-danger)' }}
              >
                <LogOut size={18} /> Logout
              </button>
            </div>
          )}
        </div>
      )}

      <div className="flex-1 overflow-y-auto p-6 md:p-8">{content}</div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                           EMPLOYEE PORTAL (SIMPLIFIED)                      */
/* -------------------------------------------------------------------------- */

const EmployeePortalSimulation: React.FC<{
  theme: ThemeConfig;
  config: EmployeePortalConfig;
  t: (k: TextKey) => string;
}> = ({ theme, config, t }) => {
  const { customers, appointments } = useApp();
  const [activePage, setActivePage] = useState<EmployeePage>('dashboard');

  const canShow = (p: EmployeePage) =>
    !!config[`show${p.charAt(0).toUpperCase() + p.slice(1)}` as keyof EmployeePortalConfig];

  useEffect(() => {
    const order: EmployeePage[] = ['dashboard', 'schedule', 'clients', 'tracking'];
    if (!canShow(activePage)) {
      const next = order.find((p) => canShow(p));
      if (next) setActivePage(next);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [config]);

  const NavBtn = ({ id, label, icon: Icon }: { id: EmployeePage; label: string; icon: any }) => {
    const active = activePage === id;
    return (
      <button
        onClick={() => setActivePage(id)}
        className={`w-full text-left px-4 py-3 rounded-[var(--radius)] text-sm font-semibold flex items-center gap-3 transition-colors ${
          active ? 'bg-[var(--c-bg)]' : 'hover:bg-[var(--c-bg)]'
        }`}
        style={{ color: active ? 'var(--c-primary)' : 'var(--c-text-muted)' }}
      >
        <Icon size={18} /> {label}
      </button>
    );
  };

  const Dashboard = () => {
    const upcoming = appointments.slice(0, 4);
    return (
      <div className="space-y-4">
        <SimCard className="p-6 text-white">
          <div
            className="rounded-[var(--radius)] p-6"
            style={{
              background: `linear-gradient(135deg, var(--c-secondary), color-mix(in srgb, var(--c-secondary) 70%, black))`,
              boxShadow: 'var(--shadow)' as any,
            }}
          >
            <div className="flex justify-between items-start">
              <div>
                <h2 className="text-xl font-extrabold">Hello</h2>
                <p className="text-white/70 text-sm">Staff preview</p>
              </div>
              <div className="w-10 h-10 bg-white/15 rounded-full flex items-center justify-center font-extrabold border border-white/20">
                SJ
              </div>
            </div>
            <div className="flex gap-3 mt-6">
              <button
                className="flex-1 py-3 rounded-[var(--radius)] font-extrabold text-sm shadow-lg flex items-center justify-center gap-2 transition-all hover:brightness-110"
                style={{ background: 'var(--c-primary)' }}
              >
                <Timer size={18} /> Clock In
              </button>
              <button className="flex-1 bg-white/10 hover:bg-white/20 py-3 rounded-[var(--radius)] font-extrabold text-sm backdrop-blur transition-all border border-white/15">
                Break
              </button>
            </div>
          </div>
        </SimCard>

        <SimCard className="p-4">
          <h4 className="text-xs font-extrabold text-[var(--c-text-muted)] uppercase mb-3">Upcoming</h4>
          {upcoming.length === 0 ? (
            <div className="text-sm text-[var(--c-text-muted)] italic">{t('portal_no_data')}</div>
          ) : (
            <div className="space-y-2">
              {upcoming.map((apt) => (
                <div
                  key={apt.id}
                  className="flex items-center justify-between p-3 rounded-[var(--radius)] border bg-[var(--c-surface)]"
                  style={{ borderColor: 'var(--c-border)', borderWidth: 'var(--border-w)' as any }}
                >
                  <div className="min-w-0">
                    <div className="font-extrabold text-[var(--c-text)] truncate">{apt.serviceName}</div>
                    <div className="text-xs text-[var(--c-text-muted)] mt-1">
                      {apt.date} • {apt.startTime} • {apt.customerName}
                    </div>
                  </div>
                  <SimBadge color={apt.status === 'Confirmed' ? 'success' : 'secondary'}>{apt.status}</SimBadge>
                </div>
              ))}
            </div>
          )}
        </SimCard>
      </div>
    );
  };

  const Schedule = () => (
    <div className="space-y-3">
      <h2 className="text-xl font-extrabold text-[var(--c-text)]">Schedule</h2>
      <SimCard className="p-4">
        <div className="text-sm text-[var(--c-text-muted)]">Minimal schedule preview (extend as needed).</div>
      </SimCard>
    </div>
  );

  const Clients = () => (
    <div className="space-y-3">
      <h2 className="text-xl font-extrabold text-[var(--c-text)]">Clients</h2>
      {customers.length === 0 ? (
        <div className="text-[var(--c-text-muted)] italic">{t('portal_no_data')}</div>
      ) : (
        customers.slice(0, 10).map((c) => (
          <SimCard key={c.id} className="p-4 flex items-center justify-between">
            <div className="min-w-0">
              <div className="font-extrabold text-[var(--c-text)] truncate">
                {c.firstName} {c.lastName}
              </div>
              <div className="text-xs text-[var(--c-text-muted)] truncate">{c.email}</div>
            </div>
            <ChevronRight size={18} className="text-[var(--c-text-muted)]" />
          </SimCard>
        ))
      )}
    </div>
  );

  const Tracking = () => (
    <div className="space-y-3">
      <h2 className="text-xl font-extrabold text-[var(--c-text)]">Tracking</h2>
      <SimCard className="p-4 space-y-3">
        <div className="text-sm text-[var(--c-text-muted)]">Minimal time tracking preview (extend as needed).</div>
        <SimButton variant="primary" className="w-full">
          <Play size={16} /> Start
        </SimButton>
      </SimCard>
    </div>
  );

  const content =
    activePage === 'dashboard' ? (
      <Dashboard />
    ) : activePage === 'schedule' ? (
      <Schedule />
    ) : activePage === 'clients' ? (
      <Clients />
    ) : (
      <Tracking />
    );

  return (
    <div className="flex h-full bg-[var(--c-bg)]">
      {!!theme.layout.showSidebar && (
        <div
          className="hidden md:flex w-64 bg-[var(--c-surface)] border-r border-[var(--c-border)] flex-col"
          style={{ borderRightWidth: 'var(--border-w)' as any }}
        >
          <div
            className="p-6 border-b border-[var(--c-border)]"
            style={{ borderBottomWidth: 'var(--border-w)' as any }}
          >
            <div className="font-extrabold text-xl flex items-center gap-2" style={{ color: 'var(--c-primary)' }}>
              <div
                className="w-8 h-8 rounded-lg flex items-center justify-center text-white"
                style={{ background: 'var(--c-primary)' }}
              >
                E
              </div>
              Employee
            </div>
          </div>
          <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
            {canShow('dashboard') && <NavBtn id="dashboard" label="Dashboard" icon={Layout} />}
            {canShow('schedule') && <NavBtn id="schedule" label="Schedule" icon={CalendarDays} />}
            {canShow('clients') && <NavBtn id="clients" label="Clients" icon={Users} />}
            {canShow('tracking') && <NavBtn id="tracking" label="Tracking" icon={Clock} />}
          </nav>
        </div>
      )}

      <div className="flex-1 overflow-y-auto p-6 md:p-8">{content}</div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                     STUDIO UI WRAPPER + STUDIO THEME VARS                  */
/* -------------------------------------------------------------------------- */

type StudioThemeVars = {
  bg: string;
  surface: string;
  surface2: string;
  border: string;
  text: string;
  muted: string;
  ring: string;
};

const defaultStudioVars: StudioThemeVars = {
  bg: '#f1f5f9',
  surface: '#ffffff',
  surface2: '#f8fafc',
  border: '#e2e8f0',
  text: '#0f172a',
  muted: '#64748b',
  ring: '#0f172a',
};

function studioVarsToCss(vars: StudioThemeVars): React.CSSProperties {
  return {
    '--studio-bg': vars.bg,
    '--studio-surface': vars.surface,
    '--studio-surface-2': vars.surface2,
    '--studio-border': vars.border,
    '--studio-text': vars.text,
    '--studio-muted': vars.muted,
    '--studio-ring': vars.ring,
  } as React.CSSProperties;
}

const StudioUiWrapper: React.FC<{ children: React.ReactNode; vars?: StudioThemeVars }> = ({
  children,
  vars = defaultStudioVars,
}) => {
  return (
    <div
      className="w-full min-h-screen"
      style={{
        ...(studioVarsToCss(vars) as any),
        background: 'var(--studio-bg)',
        color: 'var(--studio-text)',
      }}
    >
      {children}
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                          PRESET STORAGE + MODALS                           */
/* -------------------------------------------------------------------------- */

type DesignPreset = {
  id: string;
  name: string;
  context: PresetContext;
  createdAt: string;
  theme: ThemeConfig;
  widgetBehavior: WidgetBehaviorConfig;
  customerConfig: CustomerPortalConfig;
  employeeConfig: EmployeePortalConfig;
  texts: TextOverrides;
};

const PRESET_STORAGE_KEY = 'portal_studio_presets_v2';

function migratePreset(raw: any): DesignPreset | null {
  if (!raw || typeof raw !== 'object') return null;

  const id = typeof raw.id === 'string' ? raw.id : uid('preset');
  const name = typeof raw.name === 'string' ? raw.name : 'Imported';
  const createdAt = typeof raw.createdAt === 'string' ? raw.createdAt : new Date().toISOString();

  const context: PresetContext =
    raw.context === 'widget' ||
    raw.context === 'offerForm' ||
    raw.context === 'customer' ||
    raw.context === 'employee'
      ? raw.context
      : 'all';

  return {
    id,
    name,
    context,
    createdAt,
    theme: raw.theme || defaultTheme,
    widgetBehavior: raw.widgetBehavior || defaultWidgetBehavior,
    customerConfig: raw.customerConfig || defaultCustomerPortalConfig,
    employeeConfig: raw.employeeConfig || defaultEmployeePortalConfig,
    texts: raw.texts || {},
  };
}

function loadPresets(): DesignPreset[] {
  const parsed = safeJsonParse<any[]>(
    typeof window !== 'undefined' ? window.localStorage.getItem(PRESET_STORAGE_KEY) : null,
  );

  if (Array.isArray(parsed)) {
    return parsed.map(migratePreset).filter(Boolean) as DesignPreset[];
  }

  const oldParsed = safeJsonParse<any[]>(
    typeof window !== 'undefined' ? window.localStorage.getItem('portal_studio_presets_v1') : null,
  );

  if (!Array.isArray(oldParsed)) return [];

  const migrated = oldParsed
    .map((x) => migratePreset({ ...x, context: 'all' }))
    .filter(Boolean) as DesignPreset[];

  try {
    window.localStorage.setItem(PRESET_STORAGE_KEY, JSON.stringify(migrated));
  } catch {}
  return migrated;
}

function savePresets(presets: DesignPreset[]) {
  try {
    window.localStorage.setItem(PRESET_STORAGE_KEY, JSON.stringify(presets));
  } catch {}
}

const ModalShell: React.FC<{ title: string; onClose: () => void; children: React.ReactNode }> = ({
  title,
  onClose,
  children,
}) => (
  <div className="absolute inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div
      className="rounded-2xl w-full max-w-3xl overflow-hidden shadow-2xl"
      style={{
        background: 'var(--studio-surface)',
        border: `1px solid var(--studio-border)`,
      }}
    >
      <div
        className="px-5 py-4 flex items-center justify-between"
        style={{ borderBottom: `1px solid var(--studio-border)` }}
      >
        <div className="font-extrabold" style={{ color: 'var(--studio-text)' }}>
          {title}
        </div>
        <button
          onClick={onClose}
          className="p-2 rounded-xl hover:bg-black/5"
          style={{ color: 'var(--studio-muted)' }}
        >
          <X size={18} />
        </button>
      </div>
      <div className="p-5">{children}</div>
    </div>
  </div>
);

const ImportExportModal: React.FC<{
  presets: DesignPreset[];
  onClose: () => void;
  onImport: (jsonStr: string) => void;
}> = ({ presets, onClose, onImport }) => {
  const [importText, setImportText] = useState('');

  const doExport = async () => {
    const json = JSON.stringify(presets, null, 2);
    const ok = await copyToClipboard(json);
    if (ok) window.alert('Export copied to clipboard.');
    else window.prompt('Copy JSON:', json);
  };

  return (
    <ModalShell title="Import / Export" onClose={onClose}>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
          <div className="text-xs font-bold uppercase mb-2" style={{ color: 'var(--studio-muted)' }}>
            Export
          </div>
          <div className="text-sm" style={{ color: 'var(--studio-muted)' }}>
            Exports all saved designs (all contexts) as JSON.
          </div>
          <div className="mt-3">
            <button
              onClick={doExport}
              className="px-3 py-2 rounded-xl text-sm font-extrabold"
              style={{
                border: `1px solid var(--studio-border)`,
                background: 'var(--studio-surface)',
                color: 'var(--studio-text)',
              }}
            >
              Export JSON
            </button>
          </div>
        </div>

        <div>
          <div className="text-xs font-bold uppercase mb-2" style={{ color: 'var(--studio-muted)' }}>
            Import
          </div>
          <textarea
            value={importText}
            onChange={(e) => setImportText(e.target.value)}
            className="w-full min-h-[220px] rounded-xl p-3 text-sm font-mono focus:outline-none"
            style={{
              border: `1px solid var(--studio-border)`,
              background: 'var(--studio-surface)',
              color: 'var(--studio-text)',
              boxShadow: 'none',
            }}
            placeholder="Paste exported JSON here..."
          />
          <div className="mt-3 flex gap-2">
            <button
              onClick={() => onImport(importText)}
              className="px-3 py-2 rounded-xl text-sm font-extrabold"
              style={{
                background: 'var(--studio-text)',
                color: 'white',
              }}
            >
              Import
            </button>
            <button
              onClick={() => setImportText('')}
              className="px-3 py-2 rounded-xl text-sm font-extrabold"
              style={{
                border: `1px solid var(--studio-border)`,
                background: 'var(--studio-surface)',
                color: 'var(--studio-text)',
              }}
            >
              Clear
            </button>
          </div>
          <div className="mt-3 text-xs" style={{ color: 'var(--studio-muted)' }}>
            Tip: import keeps theme, toggles, and text overrides together.
          </div>
        </div>
      </div>
    </ModalShell>
  );
};

/* -------------------------------------------------------------------------- */
/*                           STEP 1: CONTEXT PICKER                            */
/* -------------------------------------------------------------------------- */

const ContextSelector: React.FC<{ onSelect: (ctx: PortalTab) => void }> = ({ onSelect }) => {
  const options: { id: PortalTab; title: string; desc: string; icon: any }[] = [
    { id: 'widget', title: 'Booking Widget', desc: 'Embeddable widget for your website.', icon: MousePointer2 },
    { id: 'offerForm', title: 'Booking Form', desc: 'Standalone offer booking form.', icon: ClipboardList },
    { id: 'customer', title: 'Customer Portal', desc: 'Client login area for managing bookings.', icon: User },
    { id: 'employee', title: 'Employee Portal', desc: 'Staff dashboard for daily operations.', icon: Briefcase },
  ];

  return (
    <div className="flex flex-col items-center justify-center min-h-screen p-8" style={{ background: 'var(--studio-bg)' }}>
      <div className="text-center mb-10 max-w-2xl">
        <h1 className="text-3xl font-extrabold mb-3" style={{ color: 'var(--studio-text)' }}>
          Design Frontend
        </h1>
        <p className="text-lg" style={{ color: 'var(--studio-muted)' }}>
          Choose which part of your frontend you want to customize.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl w-full">
        {options.map((opt) => (
          <button
            key={opt.id}
            onClick={() => onSelect(opt.id)}
            className="flex flex-col items-start p-8 rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all text-left"
            style={{
              background: 'var(--studio-surface)',
              border: `1px solid var(--studio-border)`,
            }}
          >
            <div
              className="p-4 rounded-xl mb-6"
              style={{ background: 'var(--studio-surface-2)', color: 'var(--studio-text)' }}
            >
              <opt.icon size={32} />
            </div>
            <h3 className="text-xl font-extrabold mb-2" style={{ color: 'var(--studio-text)' }}>
              {opt.title}
            </h3>
            <p style={{ color: 'var(--studio-muted)' }}>{opt.desc}</p>
          </button>
        ))}
      </div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                     STEP 2: PRESET DASHBOARD PER CONTEXT                    */
/* -------------------------------------------------------------------------- */

const contextLabel: Record<PortalTab, string> = {
  widget: 'Booking Widget',
  offerForm: 'Booking Form',
  customer: 'Customer Portal',
  employee: 'Employee Portal',
};

const PresetDashboard: React.FC<{
  context: PortalTab;
  presets: DesignPreset[];
  onBack: () => void;
  onCreate: () => void;
  onEdit: (preset: DesignPreset) => void;
  onDuplicate: (presetId: string) => void;
  onDelete: (presetId: string) => void;
  onOpenImportExport: () => void;
}> = ({ context, presets, onBack, onCreate, onEdit, onDuplicate, onDelete, onOpenImportExport }) => {
  const contextPresets = presets.filter((p) => p.context === context || p.context === 'all');

  return (
    <div className="flex flex-col min-h-screen" style={{ background: 'var(--studio-bg)' }}>
      <div
        className="px-8 py-6 flex justify-between items-center shadow-sm"
        style={{ background: 'var(--studio-surface)', borderBottom: `1px solid var(--studio-border)` }}
      >
        <div className="flex items-center gap-4 min-w-0">
          <button
            onClick={onBack}
            className="p-2 rounded-full transition-colors"
            style={{ color: 'var(--studio-muted)' }}
          >
            <ChevronLeft size={22} />
          </button>
          <div className="min-w-0">
            <h2 className="text-2xl font-extrabold truncate" style={{ color: 'var(--studio-text)' }}>
              {contextLabel[context]} Designs
            </h2>
            <p style={{ color: 'var(--studio-muted)' }}>Saved views, code preview, and actions.</p>
          </div>
        </div>

        <div className="flex items-center gap-2 shrink-0">
          <button
            onClick={onOpenImportExport}
            className="px-4 py-2 rounded-xl text-sm font-extrabold flex items-center gap-2"
            style={{
              border: `1px solid var(--studio-border)`,
              background: 'var(--studio-surface)',
              color: 'var(--studio-text)',
            }}
            title="Import / Export"
          >
            <Code size={16} /> Import/Export
          </button>

          <button
            onClick={onCreate}
            className="px-5 py-2.5 rounded-xl font-extrabold shadow-lg transition-all flex items-center gap-2"
            style={{ background: 'var(--studio-text)', color: 'white' }}
          >
            <Plus size={18} /> Create New Design
          </button>
        </div>
      </div>

      <div className="flex-1 overflow-y-auto p-8">
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 max-w-7xl mx-auto">
          <button
            onClick={onCreate}
            className="rounded-2xl p-8 flex flex-col items-center justify-center transition-all min-h-[240px]"
            style={{
              border: `2px dashed var(--studio-border)`,
              color: 'var(--studio-muted)',
              background: 'transparent',
            }}
          >
            <div
              className="w-16 h-16 rounded-full flex items-center justify-center mb-4"
              style={{ background: 'var(--studio-surface-2)', color: 'var(--studio-text)' }}
            >
              <Plus size={32} />
            </div>
            <span className="font-extrabold text-lg" style={{ color: 'var(--studio-text)' }}>
              Create Blank Design
            </span>
          </button>

          {contextPresets.map((preset) => {
            const codeSnippet = JSON.stringify(
              {
                context: preset.context,
                colors: preset.theme.colors,
                typography: preset.theme.typography,
                shape: preset.theme.shape,
                layout: preset.theme.layout,
              },
              null,
              2,
            );

            return (
              <div
                key={preset.id}
                className="rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col"
                style={{ background: 'var(--studio-surface)', border: `1px solid var(--studio-border)` }}
              >
                <div className="h-28 relative p-4 overflow-hidden" style={{ background: 'var(--studio-surface-2)' }}>
                  <div className="absolute inset-0 opacity-10 bg-[radial-gradient(#64748b_1px,transparent_1px)] [background-size:16px_16px]" />
                  <div className="relative z-10 flex items-center justify-between">
                    <div className="flex gap-2">
                      <div className="w-3 h-3 rounded-full bg-rose-400" />
                      <div className="w-3 h-3 rounded-full bg-amber-400" />
                      <div className="w-3 h-3 rounded-full bg-emerald-400" />
                    </div>
                    {preset.context === 'all' && (
                      <span
                        className="text-[10px] font-extrabold px-2 py-1 rounded-full"
                        style={{ background: 'var(--studio-border)', color: 'var(--studio-text)' }}
                      >
                        LEGACY
                      </span>
                    )}
                  </div>
                </div>

                <div className="p-5 flex-1 flex flex-col">
                  <h3 className="font-extrabold text-lg mb-1" style={{ color: 'var(--studio-text)' }}>
                    {preset.name}
                  </h3>
                  <p className="text-xs mb-4" style={{ color: 'var(--studio-muted)' }}>
                    Last edited: {new Date(preset.createdAt).toLocaleDateString()}
                  </p>

                  <div
                    className="rounded-xl p-3 mb-4 font-mono text-[11px] overflow-hidden relative"
                    style={{ background: 'var(--studio-surface-2)', border: `1px solid var(--studio-border)`, color: 'var(--studio-text)' }}
                  >
                    <div className="absolute top-2 right-2 flex gap-2">
                      <button
                        onClick={async () => {
                          const ok = await copyToClipboard(codeSnippet);
                          if (ok) window.alert('Code copied to clipboard.');
                          else window.prompt('Copy code:', codeSnippet);
                        }}
                        className="p-1.5 rounded-lg"
                        style={{ border: `1px solid var(--studio-border)`, background: 'var(--studio-surface)' }}
                        title="Copy code"
                      >
                        <Copy size={14} />
                      </button>
                    </div>
                    <pre className="whitespace-pre-wrap line-clamp-6 opacity-90 pr-12">{codeSnippet}</pre>
                  </div>

                  <div className="mt-auto flex items-center justify-between pt-2">
                    <button
                      onClick={() => onEdit(preset)}
                      className="px-4 py-2 rounded-xl text-sm font-extrabold"
                      style={{ background: 'var(--studio-text)', color: 'white' }}
                    >
                      Edit
                    </button>

                    <div className="flex gap-1">
                      <button
                        onClick={() => onDuplicate(preset.id)}
                        className="p-2 rounded-xl"
                        style={{ color: 'var(--studio-muted)' }}
                        title="Duplicate"
                      >
                        <Copy size={18} />
                      </button>
                      <button
                        onClick={() => onDelete(preset.id)}
                        className="p-2 rounded-xl"
                        style={{ color: 'var(--studio-muted)' }}
                        title="Delete"
                      >
                        <Trash2 size={18} />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}

          {contextPresets.length === 0 && (
            <div className="col-span-full text-center italic py-10" style={{ color: 'var(--studio-muted)' }}>
              No designs saved for this area yet.
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                         STEP 3: EDITOR STAGE (REAL)                         */
/* -------------------------------------------------------------------------- */

const EditorStage: React.FC<{
  preset: DesignPreset;
  onSave: (updated: DesignPreset) => void;
  onBack: () => void;
}> = ({ preset, onSave, onBack }) => {
  const [activeTab, setActiveTab] = useState<PortalTab>(
    preset.context === 'all' ? 'widget' : (preset.context as PortalTab),
  );
  const [device, setDevice] = useState<Device>('desktop');
  const [orientation, setOrientation] = useState<Orientation>('portrait');

  const [theme, setTheme] = useState<ThemeConfig>(preset.theme);
  const [widgetBehavior, setWidgetBehavior] = useState<WidgetBehaviorConfig>(preset.widgetBehavior);
  const [customerConfig, setCustomerConfig] = useState<CustomerPortalConfig>(preset.customerConfig);
  const [employeeConfig, setEmployeeConfig] = useState<EmployeePortalConfig>(preset.employeeConfig);
  const [texts, setTexts] = useState<TextOverrides>(preset.texts ?? {});

  const t = useMemo(() => createTranslator(theme.locale.uiLanguage, texts), [theme.locale.uiLanguage, texts]);

  useEffect(() => {
    if (preset.context !== 'all') setActiveTab(preset.context as PortalTab);
  }, [preset.context]);

  const handleSave = () => {
    onSave({
      ...preset,
      theme,
      widgetBehavior,
      customerConfig,
      employeeConfig,
      texts,
      createdAt: new Date().toISOString(),
    });
  };

  return (
    <div className="flex min-h-screen overflow-hidden flex-col" style={{ background: 'var(--studio-bg)' }}>
      <div
        className="px-6 py-3 flex justify-between items-center shadow-sm z-40"
        style={{ background: 'var(--studio-surface)', borderBottom: `1px solid var(--studio-border)` }}
      >
        <div className="flex items-center gap-4 min-w-0">
          <button
            onClick={onBack}
            className="flex items-center gap-2 transition-colors text-sm font-extrabold"
            style={{ color: 'var(--studio-muted)' }}
          >
            <ChevronLeft size={18} /> Back to Designs
          </button>
          <div className="h-6 w-px" style={{ background: 'var(--studio-border)' }} />
          <div className="min-w-0">
            <div className="text-xs font-extrabold uppercase tracking-wider" style={{ color: 'var(--studio-muted)' }}>
              Editing
            </div>
            <div className="font-extrabold truncate" style={{ color: 'var(--studio-text)' }}>
              {preset.name}
            </div>
          </div>
        </div>

        <div className="flex items-center gap-3 shrink-0">
          <div className="hidden md:flex items-center gap-1 p-1.5 rounded-2xl" style={{ background: 'var(--studio-surface-2)' }}>
            <button
              onClick={() => setDevice('desktop')}
              className="p-2 rounded-xl transition-all"
              style={{
                background: device === 'desktop' ? 'var(--studio-surface)' : 'transparent',
                boxShadow: device === 'desktop' ? '0 1px 8px rgba(0,0,0,0.08)' : 'none',
                color: device === 'desktop' ? 'var(--studio-text)' : 'var(--studio-muted)',
              }}
              title="Desktop"
            >
              <Monitor size={18} />
            </button>
            <button
              onClick={() => setDevice('tablet')}
              className="p-2 rounded-xl transition-all"
              style={{
                background: device === 'tablet' ? 'var(--studio-surface)' : 'transparent',
                boxShadow: device === 'tablet' ? '0 1px 8px rgba(0,0,0,0.08)' : 'none',
                color: device === 'tablet' ? 'var(--studio-text)' : 'var(--studio-muted)',
              }}
              title="Tablet"
            >
              <Tablet size={18} />
            </button>
            <button
              onClick={() => setDevice('mobile')}
              className="p-2 rounded-xl transition-all"
              style={{
                background: device === 'mobile' ? 'var(--studio-surface)' : 'transparent',
                boxShadow: device === 'mobile' ? '0 1px 8px rgba(0,0,0,0.08)' : 'none',
                color: device === 'mobile' ? 'var(--studio-text)' : 'var(--studio-muted)',
              }}
              title="Mobile"
            >
              <Smartphone size={18} />
            </button>

            {device !== 'desktop' && <div className="w-px h-7 mx-1" style={{ background: 'var(--studio-border)' }} />}

            {device !== 'desktop' && (
              <button
                onClick={() => setOrientation((o) => (o === 'portrait' ? 'landscape' : 'portrait'))}
                className="p-2 rounded-xl transition-colors"
                style={{ color: 'var(--studio-muted)' }}
                title="Rotate"
              >
                <RotateCcw size={18} className={orientation === 'landscape' ? 'rotate-90' : ''} />
              </button>
            )}
          </div>

          <button
            onClick={handleSave}
            className="px-5 py-2 rounded-xl text-sm font-extrabold shadow-lg flex items-center gap-2"
            style={{ background: 'var(--studio-text)', color: 'white' }}
          >
            <Check size={16} /> Save
          </button>
        </div>
      </div>

      <div className="flex-1 flex overflow-hidden">
        {/* Sidebar bleibt bewusst im "Studio"-Look (nicht vom Widget-Theme beeinflusst) */}
        <EditorSidebar
          activeTab={activeTab}
          theme={theme}
          setTheme={setTheme}
          widgetBehavior={widgetBehavior}
          setWidgetBehavior={setWidgetBehavior}
          customerConfig={customerConfig}
          setCustomerConfig={setCustomerConfig}
          employeeConfig={employeeConfig}
          setEmployeeConfig={setEmployeeConfig}
          texts={texts}
          setTexts={setTexts}
          t={t}
        />

        <div className="flex-1 overflow-hidden relative" style={{ background: 'var(--studio-surface-2)' }}>
          <div
            className="absolute inset-0 pointer-events-none"
            style={{ backgroundImage: 'radial-gradient(#cbd5e1 1px, transparent 1px)', backgroundSize: '20px 20px' }}
          />
          <DeviceFrame device={device} orientation={orientation}>
            <SimulationWrapper theme={theme}>
              {activeTab === 'widget' && (
                <BookingWidgetSimulation device={device} theme={theme} behavior={widgetBehavior} t={t} />
              )}
              {activeTab === 'offerForm' && (
                <OfferBookingFormSimulation theme={theme} device={device} behavior={widgetBehavior} t={t} />
              )}
              {activeTab === 'customer' && <CustomerPortalSimulation theme={theme} config={customerConfig} t={t} />}
              {activeTab === 'employee' && <EmployeePortalSimulation theme={theme} config={employeeConfig} t={t} />}
            </SimulationWrapper>
          </DeviceFrame>
        </div>
      </div>
    </div>
  );
};

/* -------------------------------------------------------------------------- */
/*                                   ROOT                                     */
/* -------------------------------------------------------------------------- */

const PortalStudio: React.FC = () => {
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [selectedContext, setSelectedContext] = useState<PortalTab | null>(null);

  const [presets, setPresets] = useState<DesignPreset[]>(() => loadPresets());
  const [activePreset, setActivePreset] = useState<DesignPreset | null>(null);
  const [importExportOpen, setImportExportOpen] = useState(false);

  useEffect(() => {
    savePresets(presets);
  }, [presets]);

  const handleContextSelect = (ctx: PortalTab) => {
    setSelectedContext(ctx);
    setStep(2);
  };

  const createNew = () => {
    if (!selectedContext) return;
    const newPreset: DesignPreset = {
      id: uid('preset'),
      name: 'New Custom Design',
      context: selectedContext,
      createdAt: new Date().toISOString(),
      theme: defaultTheme,
      widgetBehavior: defaultWidgetBehavior,
      customerConfig: defaultCustomerPortalConfig,
      employeeConfig: defaultEmployeePortalConfig,
      texts: {},
    };
    setPresets((p) => [newPreset, ...p]);
    setActivePreset(newPreset);
    setStep(3);
  };

  const editPreset = (preset: DesignPreset) => {
    setActivePreset(preset);
    setStep(3);
  };

  const duplicatePreset = (id: string) => {
    const p = presets.find((x) => x.id === id);
    if (!p) return;
    const copy: DesignPreset = {
      ...p,
      id: uid('preset'),
      name: `${p.name} (Copy)`,
      createdAt: new Date().toISOString(),
      context: selectedContext ?? p.context,
    };
    setPresets((arr) => [copy, ...arr]);
  };

  const deletePreset = (id: string) => {
    const p = presets.find((x) => x.id === id);
    if (!p) return;
    const ok = window.confirm(`Delete "${p.name}"?`);
    if (!ok) return;
    setPresets((arr) => arr.filter((x) => x.id !== id));
    if (activePreset?.id === id) setActivePreset(null);
  };

  const savePreset = (updated: DesignPreset) => {
    setPresets((arr) => {
      const idx = arr.findIndex((p) => p.id === updated.id);
      if (idx >= 0) {
        const next = [...arr];
        next[idx] = updated;
        return next;
      }
      return [updated, ...arr];
    });
    setActivePreset(updated);
  };

  const importPresets = (jsonStr: string) => {
    const parsed = safeJsonParse<any>(jsonStr);
    if (!Array.isArray(parsed)) {
      window.alert('Invalid JSON.');
      return;
    }

    const cleaned: DesignPreset[] = parsed.map(migratePreset).filter(Boolean) as DesignPreset[];

    if (cleaned.length === 0) {
      window.alert('No presets found in JSON.');
      return;
    }
    setPresets((arr) => [...cleaned, ...arr]);
    window.alert(`Imported ${cleaned.length} design(s).`);
  };

  return (
    <StudioUiWrapper>
      {step === 1 && <ContextSelector onSelect={handleContextSelect} />}

      {step === 2 && selectedContext && (
        <div className="min-h-screen w-full relative">
          <PresetDashboard
            context={selectedContext}
            presets={presets}
            onBack={() => setStep(1)}
            onCreate={createNew}
            onEdit={editPreset}
            onDuplicate={duplicatePreset}
            onDelete={deletePreset}
            onOpenImportExport={() => setImportExportOpen(true)}
          />

          {importExportOpen && (
            <ImportExportModal
              presets={presets}
              onClose={() => setImportExportOpen(false)}
              onImport={(str) => {
                importPresets(str);
                setImportExportOpen(false);
              }}
            />
          )}
        </div>
      )}

      {step === 3 && activePreset && <EditorStage preset={activePreset} onSave={savePreset} onBack={() => setStep(2)} />}

      {!(step === 1 || (step === 2 && selectedContext) || (step === 3 && activePreset)) && (
        <div className="min-h-screen w-full flex items-center justify-center" style={{ color: 'var(--studio-muted)' }}>
          Error: Unknown State
        </div>
      )}
    </StudioUiWrapper>
  );
};

export default PortalStudio;

