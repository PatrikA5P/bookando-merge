
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
  CheckSquare,
  LayoutTemplate,
  Globe,
  ArrowLeft,
  Save
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

// --- DESIGN TOOL ---

type DesignStep = 'selection' | 'templates' | 'editor';
type DesignContext = 'booking' | 'customer' | 'employee';
type DeviceType = 'desktop' | 'tablet' | 'mobile';

// Complex Configuration Types
interface GlobalDesignConfig {
    fontFamily: string;
    fontScale: number; // Percentage
    colors: {
        primary: string;
        success: string;
        warning: string;
        error: string;
        sidebarBg: string;
        sidebarText: string;
        contentBg: string;
        contentHeading: string;
        contentText: string;
        formCheckbox: string;
        formBg: string;
        formBorder: string;
        formText: string;
        btnPrimaryBg: string;
        btnPrimaryText: string;
        btnSecondaryBg: string;
        btnSecondaryText: string;
    };
    borders: {
        width: number;
        radius: number;
        btnGlobalBorder: boolean; // Primary
        btnSecondaryBorder: boolean;
    };
}

interface CategoriesConfig {
    showPageColor: boolean;
    showAccentColor: boolean;
    showCount: boolean;
    labels: {
        primaryBtn: string;
        secondaryBtn: string;
    };
}

interface ServicesConfig {
    layout: {
        showBackground: boolean;
        showSidebar: boolean;
        showCardColors: boolean;
    };
    filters: {
        showSearch: boolean;
        showEmployeeFilter: boolean;
        showLocationFilter: boolean;
    };
    info: {
        showVat: boolean;
        showBadge: boolean;
        showPrice: boolean;
        showActionButton: boolean;
    };
    labels: {
        primaryBtn: string;
        secondaryBtn: string;
        service: string;
        bookNow: string;
        free: string;
        multipleLocations: string;
        viewPhotos: string;
        about: string;
        employees: string;
        vat: string;
        inclVat: string;
    };
}

interface BookingWidgetConfig {
    global: GlobalDesignConfig;
    categories: CategoriesConfig;
    services: ServicesConfig;
    packages: { enabled: boolean }; // Placeholder
    events: { enabled: boolean }; // Placeholder
    form: { enabled: boolean }; // Placeholder
}

const DesignFrontendModule = () => {
    const [step, setStep] = useState<DesignStep>('selection');
    const [context, setContext] = useState<DesignContext | null>(null);
    const [device, setDevice] = useState<DeviceType>('desktop');
    const [activeAccordion, setActiveAccordion] = useState<string | null>('global');
    const [isSaving, setIsSaving] = useState(false);
    const [saveStatus, setSaveStatus] = useState<'idle' | 'success' | 'error'>('idle');
    const [saveMessage, setSaveMessage] = useState<string | null>(null);

    // --- STATE INIT ---
    const [bookingConfig, setBookingConfig] = useState<BookingWidgetConfig>({
        global: {
            fontFamily: 'Inter',
            fontScale: 100,
            colors: {
                primary: '#0ea5e9', success: '#10b981', warning: '#f59e0b', error: '#ef4444',
                sidebarBg: '#f8fafc', sidebarText: '#475569',
                contentBg: '#ffffff', contentHeading: '#1e293b', contentText: '#64748b',
                formCheckbox: '#0ea5e9', formBg: '#ffffff', formBorder: '#e2e8f0', formText: '#1e293b',
                btnPrimaryBg: '#0ea5e9', btnPrimaryText: '#ffffff',
                btnSecondaryBg: '#f1f5f9', btnSecondaryText: '#475569'
            },
            borders: { width: 1, radius: 8, btnGlobalBorder: false, btnSecondaryBorder: true }
        },
        categories: {
            showPageColor: true, showAccentColor: true, showCount: true,
            labels: { primaryBtn: 'Select', secondaryBtn: 'Back' }
        },
        services: {
            layout: { showBackground: true, showSidebar: true, showCardColors: false },
            filters: { showSearch: true, showEmployeeFilter: true, showLocationFilter: true },
            info: { showVat: true, showBadge: true, showPrice: true, showActionButton: true },
            labels: {
                primaryBtn: 'Next', secondaryBtn: 'Back', service: 'Service', bookNow: 'Book Now',
                free: 'Free', multipleLocations: 'Multiple Locations', viewPhotos: 'View All Photos',
                about: 'About the Service', employees: 'Employees', vat: 'VAT', inclVat: 'Incl. VAT'
            }
        },
        packages: { enabled: true },
        events: { enabled: true },
        form: { enabled: true }
    });

    const [customerConfig, setCustomerConfig] = useState({ dashboard: true, profile: true, appointments: true, courses: true, quizzes: true, settings: true, logout: true });
    const [employeeConfig, setEmployeeConfig] = useState({ dashboard: true, schedule: true, timeTracking: true, absences: true, payroll: true });

    // --- HELPER COMPONENTS ---

    const ConfigSection = ({ id, title, icon: Icon, children }: any) => (
        <div className="border-b border-slate-100 last:border-0">
            <button 
                onClick={() => setActiveAccordion(activeAccordion === id ? null : id)}
                className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition-colors"
            >
                <div className="flex items-center gap-2 font-bold text-slate-700 text-sm">
                    <Icon size={16} className="text-slate-400" /> {title}
                </div>
                {activeAccordion === id ? <ChevronDown size={16} className="text-slate-400" /> : <ChevronRight size={16} className="text-slate-400" />}
            </button>
            {activeAccordion === id && (
                <div className="p-4 bg-slate-50/50 space-y-4 animate-slideDown">
                    {children}
                </div>
            )}
        </div>
    );

    const Toggle = ({ label, checked, onChange }: { label: string, checked: boolean, onChange: (v: boolean) => void }) => (
        <label className="flex items-center justify-between cursor-pointer group">
            <span className="text-sm text-slate-600 group-hover:text-slate-900">{label}</span>
            <button onClick={() => onChange(!checked)} className={`transition-colors ${checked ? 'text-brand-600' : 'text-slate-300'}`}>
                {checked ? <CheckSquare size={20} /> : <Square size={20} />}
            </button>
        </label>
    );

    const ColorInput = ({ label, value, onChange }: { label: string, value: string, onChange: (v: string) => void }) => (
        <div className="flex items-center justify-between">
            <span className="text-xs text-slate-500">{label}</span>
            <div className="flex items-center gap-2">
                <input type="text" value={value} onChange={e => onChange(e.target.value)} className="w-20 text-xs border border-slate-200 rounded px-1 py-0.5 font-mono bg-white" />
                <input type="color" value={value} onChange={e => onChange(e.target.value)} className="w-6 h-6 rounded cursor-pointer border-0 p-0 bg-transparent" />
            </div>
        </div>
    );

    const LabelInput = ({ label, value, onChange }: { label: string, value: string, onChange: (v: string) => void }) => (
        <div>
            <div className="flex justify-between mb-1">
                <span className="text-xs text-slate-500">{label}</span>
                <Globe size={10} className="text-slate-400" />
            </div>
            <input 
                type="text" 
                value={value} 
                onChange={e => onChange(e.target.value)}
                className="w-full border border-slate-200 rounded px-2 py-1 text-sm focus:ring-1 focus:ring-brand-500 outline-none"
            />
        </div>
    );

    const Slider = ({ label, value, onChange, min, max, unit }: any) => (
        <div>
            <div className="flex justify-between mb-1">
                <span className="text-xs text-slate-500">{label}</span>
                <span className="text-xs font-bold text-slate-700">{value}{unit}</span>
            </div>
            <input 
                type="range" min={min} max={max} value={value} 
                onChange={e => onChange(parseInt(e.target.value))}
                className="w-full accent-brand-600 h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer"
            />
        </div>
    );

    const persistDesignConfig = async (serializedConfig: string) => {
        // Replace with API call or context action to persist the editor configuration
        console.log('Persisting design configuration', serializedConfig);
        return new Promise<void>((resolve) => setTimeout(resolve, 800));
    };

    const handleSave = async () => {
        if (!context) {
            setSaveStatus('error');
            setSaveMessage('Please select a context before saving.');
            return;
        }

        setIsSaving(true);
        setSaveMessage(null);
        setSaveStatus('idle');

        const payload = {
            context,
            device,
            configs: {
                booking: bookingConfig,
                customer: customerConfig,
                employee: employeeConfig,
            },
            updatedAt: new Date().toISOString(),
        };

        try {
            const serialized = JSON.stringify(payload);
            await persistDesignConfig(serialized);
            setSaveStatus('success');
            setSaveMessage('Design settings saved successfully.');
        } catch (error) {
            console.error('Error saving design settings', error);
            setSaveStatus('error');
            setSaveMessage(error instanceof Error ? error.message : 'Saving failed. Please try again.');
        } finally {
            setIsSaving(false);
        }
    };

    const getPreviewWidth = () => {
        if (device === 'mobile') return 'max-w-[375px]';
        if (device === 'tablet') return 'max-w-[768px]';
        return 'max-w-full';
    };

    const previewStyle = context === 'booking' ? {
        '--font-family': bookingConfig.global.fontFamily,
        '--font-scale': `${bookingConfig.global.fontScale}%`,
        '--col-primary': bookingConfig.global.colors.primary,
        '--col-bg-sidebar': bookingConfig.global.colors.sidebarBg,
        '--col-text-sidebar': bookingConfig.global.colors.sidebarText,
        '--col-bg-content': bookingConfig.global.colors.contentBg,
        '--col-head-content': bookingConfig.global.colors.contentHeading,
        '--col-text-content': bookingConfig.global.colors.contentText,
        '--btn-prim-bg': bookingConfig.global.colors.btnPrimaryBg,
        '--btn-prim-text': bookingConfig.global.colors.btnPrimaryText,
        '--btn-sec-bg': bookingConfig.global.colors.btnSecondaryBg,
        '--btn-sec-text': bookingConfig.global.colors.btnSecondaryText,
        '--border-w': `${bookingConfig.global.borders.width}px`,
        '--border-r': `${bookingConfig.global.borders.radius}px`,
        fontFamily: 'var(--font-family)',
        fontSize: 'var(--font-scale)',
    } as React.CSSProperties : {};

    if (step === 'selection') {
        return (
            <div className="flex-1 flex flex-col p-8 overflow-y-auto">
                <h2 className="text-2xl font-bold text-slate-800 mb-2">What would you like to customize?</h2>
                <p className="text-slate-500 mb-8">Select a portal or widget to configure its design and functionality.</p>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <button onClick={() => { setContext('booking'); setStep('editor'); }} className="bg-white p-8 rounded-xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-brand-500 transition-all text-left group">
                        <div className="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform"><LayoutTemplate size={32} /></div>
                        <h3 className="text-xl font-bold text-slate-800 mb-2">Booking Widget</h3>
                        <p className="text-sm text-slate-500">Embeddable widget for your website to accept bookings.</p>
                    </button>
                    <button onClick={() => { setContext('customer'); setStep('editor'); }} className="bg-white p-8 rounded-xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-brand-500 transition-all text-left group">
                        <div className="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform"><Users size={32} /></div>
                        <h3 className="text-xl font-bold text-slate-800 mb-2">Customer Portal</h3>
                        <p className="text-sm text-slate-500">The login area for your clients to manage their profile.</p>
                    </button>
                    <button onClick={() => { setContext('employee'); setStep('editor'); }} className="bg-white p-8 rounded-xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-brand-500 transition-all text-left group">
                        <div className="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform"><Briefcase size={32} /></div>
                        <h3 className="text-xl font-bold text-slate-800 mb-2">Employee Portal</h3>
                        <p className="text-sm text-slate-500">The dashboard for your staff to manage schedule and work.</p>
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="flex-1 flex flex-col h-full">
            {/* Editor Header */}
            <div className="p-4 border-b border-slate-200 flex justify-between items-center bg-white shadow-sm z-10">
                <div className="flex items-center gap-4">
                    <button onClick={() => setStep('selection')} className="text-slate-500 hover:text-slate-700 flex items-center gap-1 text-sm font-medium">
                        <ArrowLeft size={16} /> Back
                    </button>
                    <div className="h-6 w-px bg-slate-200"></div>
                    <div className="flex bg-slate-100 p-1 rounded-lg">
                        <button onClick={() => setDevice('desktop')} className={`p-2 rounded ${device === 'desktop' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}><Monitor size={18} /></button>
                        <button onClick={() => setDevice('tablet')} className={`p-2 rounded ${device === 'tablet' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}><Tablet size={18} /></button>
                        <button onClick={() => setDevice('mobile')} className={`p-2 rounded ${device === 'mobile' ? 'bg-white shadow text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}><Smartphone size={18} /></button>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <span className="text-xs text-slate-400 uppercase font-bold tracking-wider">{context} editor</span>
                    {saveMessage && (
                        <span
                            className={`text-xs font-medium ${saveStatus === 'success' ? 'text-emerald-600' : 'text-rose-600'}`}
                            role="status"
                        >
                            {saveMessage}
                        </span>
                    )}
                    <button
                        onClick={handleSave}
                        disabled={isSaving}
                        className={`bg-brand-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm transition-colors ${isSaving ? 'opacity-70 cursor-not-allowed' : 'hover:bg-brand-700'}`}
                    >
                        {isSaving ? <span className="animate-pulse h-4 w-4 rounded-full bg-white/70" /> : <Save size={16} />}
                        {isSaving ? 'Saving...' : 'Save'}
                    </button>
                </div>
            </div>

            <div className="flex-1 flex overflow-hidden">
                {/* CONFIGURATION SIDEBAR */}
                <div className="w-80 border-r border-slate-200 bg-white flex flex-col overflow-y-auto z-10 scrollbar-thin">
                    <div className="p-4 border-b border-slate-100">
                        <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Settings</h4>
                    </div>

                    {/* --- BOOKING WIDGET CONFIG --- */}
                    {context === 'booking' && (
                        <>
                            {/* GLOBAL */}
                            <ConfigSection id="global" title="Global Settings" icon={Globe}>
                                <div className="space-y-4">
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Typography</span>
                                        <select 
                                            className="w-full border border-slate-200 rounded text-sm p-1 mb-2" 
                                            value={bookingConfig.global.fontFamily}
                                            onChange={(e) => setBookingConfig(prev => ({...prev, global: {...prev.global, fontFamily: e.target.value}}))}
                                        >
                                            <option value="Inter">Inter (Default)</option>
                                            <option value="Roboto">Roboto</option>
                                            <option value="Open Sans">Open Sans</option>
                                            <option value="Lato">Lato</option>
                                        </select>
                                        <Slider label="Font Scale" unit="%" min={80} max={120} value={bookingConfig.global.fontScale} onChange={(v: number) => setBookingConfig(prev => ({...prev, global: {...prev.global, fontScale: v}}))} />
                                    </div>
                                    
                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Colors</span>
                                        <ColorInput label="Primary" value={bookingConfig.global.colors.primary} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, primary: v}}}))} />
                                        <ColorInput label="Content Bg" value={bookingConfig.global.colors.contentBg} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, contentBg: v}}}))} />
                                        <ColorInput label="Headings" value={bookingConfig.global.colors.contentHeading} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, contentHeading: v}}}))} />
                                        <ColorInput label="Sidebar Bg" value={bookingConfig.global.colors.sidebarBg} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, sidebarBg: v}}}))} />
                                        <ColorInput label="Sidebar Text" value={bookingConfig.global.colors.sidebarText} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, sidebarText: v}}}))} />
                                    </div>

                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Buttons</span>
                                        <ColorInput label="Primary Bg" value={bookingConfig.global.colors.btnPrimaryBg} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, btnPrimaryBg: v}}}))} />
                                        <ColorInput label="Primary Text" value={bookingConfig.global.colors.btnPrimaryText} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, colors: {...prev.global.colors, btnPrimaryText: v}}}))} />
                                        <Toggle label="Primary Border" checked={bookingConfig.global.borders.btnGlobalBorder} onChange={(v) => setBookingConfig(prev => ({...prev, global: {...prev.global, borders: {...prev.global.borders, btnGlobalBorder: v}}}))} />
                                    </div>

                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Borders</span>
                                        <Slider label="Thickness" unit="px" min={0} max={5} value={bookingConfig.global.borders.width} onChange={(v: number) => setBookingConfig(prev => ({...prev, global: {...prev.global, borders: {...prev.global.borders, width: v}}}))} />
                                        <div className="h-2"></div>
                                        <Slider label="Radius" unit="px" min={0} max={20} value={bookingConfig.global.borders.radius} onChange={(v: number) => setBookingConfig(prev => ({...prev, global: {...prev.global, borders: {...prev.global.borders, radius: v}}}))} />
                                    </div>
                                </div>
                            </ConfigSection>

                            {/* CATEGORIES */}
                            <ConfigSection id="categories" title="Categories" icon={LayoutTemplate}>
                                <div className="space-y-4">
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Layout</span>
                                        <Toggle label="Show Page Color" checked={bookingConfig.categories.showPageColor} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, showPageColor: v}}))} />
                                        <Toggle label="Show Accent Color" checked={bookingConfig.categories.showAccentColor} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, showAccentColor: v}}))} />
                                        <Toggle label="Show Services Count" checked={bookingConfig.categories.showCount} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, showCount: v}}))} />
                                    </div>
                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Labels & Translation</span>
                                        <LabelInput label="Primary Button" value={bookingConfig.categories.labels.primaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, labels: {...prev.categories.labels, primaryBtn: v}}}))} />
                                        <LabelInput label="Secondary Button" value={bookingConfig.categories.labels.secondaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, categories: {...prev.categories, labels: {...prev.categories.labels, secondaryBtn: v}}}))} />
                                    </div>
                                </div>
                            </ConfigSection>

                            {/* SERVICES */}
                            <ConfigSection id="services" title="Service List" icon={Briefcase}>
                                <div className="space-y-4">
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Layout</span>
                                        <Toggle label="Show Background" checked={bookingConfig.services.layout.showBackground} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, layout: {...prev.services.layout, showBackground: v}}}))} />
                                        <Toggle label="Show Sidebar" checked={bookingConfig.services.layout.showSidebar} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, layout: {...prev.services.layout, showSidebar: v}}}))} />
                                        <Toggle label="Use Card Colors" checked={bookingConfig.services.layout.showCardColors} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, layout: {...prev.services.layout, showCardColors: v}}}))} />
                                    </div>
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Filters</span>
                                        <Toggle label="Show Search" checked={bookingConfig.services.filters.showSearch} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, filters: {...prev.services.filters, showSearch: v}}}))} />
                                        <Toggle label="Employee Filter" checked={bookingConfig.services.filters.showEmployeeFilter} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, filters: {...prev.services.filters, showEmployeeFilter: v}}}))} />
                                        <Toggle label="Location Filter" checked={bookingConfig.services.filters.showLocationFilter} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, filters: {...prev.services.filters, showLocationFilter: v}}}))} />
                                    </div>
                                    <div>
                                        <span className="text-xs font-bold text-slate-500 block mb-2">Information</span>
                                        <Toggle label="Show VAT" checked={bookingConfig.services.info.showVat} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, info: {...prev.services.info, showVat: v}}}))} />
                                        <Toggle label="Show Price" checked={bookingConfig.services.info.showPrice} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, info: {...prev.services.info, showPrice: v}}}))} />
                                        <Toggle label="Action Button" checked={bookingConfig.services.info.showActionButton} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, info: {...prev.services.info, showActionButton: v}}}))} />
                                    </div>
                                    <div className="space-y-2">
                                        <span className="text-xs font-bold text-slate-500 block">Labels & Translation</span>
                                        <LabelInput label="Primary Button" value={bookingConfig.services.labels.primaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, primaryBtn: v}}}))} />
                                        <LabelInput label="Secondary Button" value={bookingConfig.services.labels.secondaryBtn} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, secondaryBtn: v}}}))} />
                                        <LabelInput label="Book Now" value={bookingConfig.services.labels.bookNow} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, bookNow: v}}}))} />
                                        <LabelInput label="Free" value={bookingConfig.services.labels.free} onChange={(v) => setBookingConfig(prev => ({...prev, services: {...prev.services, labels: {...prev.services.labels, free: v}}}))} />
                                    </div>
                                </div>
                            </ConfigSection>
                        </>
                    )}

                    {/* --- CUSTOMER PORTAL CONFIG (Simplified) --- */}
                    {context === 'customer' && (
                        <div className="p-4 space-y-1">
                            <Toggle label="Dashboard" checked={customerConfig.dashboard} onChange={() => setCustomerConfig(p => ({...p, dashboard: !p.dashboard}))} />
                            <Toggle label="My Profile" checked={customerConfig.profile} onChange={() => setCustomerConfig(p => ({...p, profile: !p.profile}))} />
                            <Toggle label="Appointments" checked={customerConfig.appointments} onChange={() => setCustomerConfig(p => ({...p, appointments: !p.appointments}))} />
                            <Toggle label="Courses" checked={customerConfig.courses} onChange={() => setCustomerConfig(p => ({...p, courses: !p.courses}))} />
                            <Toggle label="Quiz Attempts" checked={customerConfig.quizzes} onChange={() => setCustomerConfig(p => ({...p, quizzes: !p.quizzes}))} />
                            <div className="h-px bg-slate-100 my-2"></div>
                            <Toggle label="Settings (Notifications)" checked={customerConfig.settings} onChange={() => setCustomerConfig(p => ({...p, settings: !p.settings}))} />
                            <Toggle label="Logout Button" checked={customerConfig.logout} onChange={() => setCustomerConfig(p => ({...p, logout: !p.logout}))} />
                        </div>
                    )}

                    {/* --- EMPLOYEE PORTAL CONFIG (Simplified) --- */}
                    {context === 'employee' && (
                        <div className="p-4 space-y-1">
                            <Toggle label="Dashboard" checked={employeeConfig.dashboard} onChange={() => setEmployeeConfig(p => ({...p, dashboard: !p.dashboard}))} />
                            <Toggle label="My Schedule" checked={employeeConfig.schedule} onChange={() => setEmployeeConfig(p => ({...p, schedule: !p.schedule}))} />
                            <Toggle label="Time Tracking" checked={employeeConfig.timeTracking} onChange={() => setEmployeeConfig(p => ({...p, timeTracking: !p.timeTracking}))} />
                            <Toggle label="Absences" checked={employeeConfig.absences} onChange={() => setEmployeeConfig(p => ({...p, absences: !p.absences}))} />
                            <Toggle label="Payroll / Payslips" checked={employeeConfig.payroll} onChange={() => setEmployeeConfig(p => ({...p, payroll: !p.payroll}))} />
                        </div>
                    )}
                </div>

                {/* LIVE PREVIEW AREA */}
                <div className="flex-1 bg-slate-100 flex justify-center overflow-y-auto p-8">
                    <div 
                        style={previewStyle}
                        className={`
                            shadow-2xl transition-all duration-500 ease-in-out flex flex-col 
                            ${getPreviewWidth()} 
                            ${device === 'mobile' ? 'rounded-[3rem] border-[8px] border-slate-800' : 'rounded-lg border border-slate-300'} 
                            min-h-[600px] h-fit w-full bg-white overflow-hidden
                        `}
                    >
                        
                        {/* Browser Header */}
                        {device !== 'mobile' && (
                            <div className="h-8 bg-slate-100 border-b border-slate-200 flex items-center px-4 gap-2 shrink-0">
                                <div className="flex gap-1.5">
                                    <div className="w-3 h-3 rounded-full bg-rose-400"></div>
                                    <div className="w-3 h-3 rounded-full bg-amber-400"></div>
                                    <div className="w-3 h-3 rounded-full bg-emerald-400"></div>
                                </div>
                                <div className="flex-1 text-center text-[10px] text-slate-400 bg-white mx-8 rounded py-0.5 border border-slate-200">
                                    portal.bookando.com
                                </div>
                            </div>
                        )}

                        {/* Notch */}
                        {device === 'mobile' && (
                            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-800 rounded-b-xl z-20"></div>
                        )}

                        {/* --- PREVIEW CONTENT RENDER --- */}
                        <div className={`flex-1 overflow-y-auto relative ${device === 'mobile' ? 'rounded-[2.5rem] pt-8' : ''}`} style={{ backgroundColor: 'var(--col-bg-content)' }}>
                            
                            {/* BOOKING WIDGET PREVIEW */}
                            {context === 'booking' && (
                                <div className="h-full flex flex-col">
                                    {/* Header / Sidebar Concept */}
                                    <div className="flex flex-1">
                                        {/* Optional Sidebar */}
                                        {bookingConfig.services.layout.showSidebar && (
                                            <div className="w-1/4 p-4 border-r hidden md:block" style={{ backgroundColor: 'var(--col-bg-sidebar)', color: 'var(--col-text-sidebar)', borderColor: 'var(--form-border)' }}>
                                                <div className="h-10 w-full bg-slate-200 rounded mb-6 opacity-50"></div>
                                                {bookingConfig.services.filters.showLocationFilter && <div className="h-4 w-3/4 bg-slate-200 rounded mb-2 opacity-50"></div>}
                                                {bookingConfig.services.filters.showEmployeeFilter && <div className="h-4 w-1/2 bg-slate-200 rounded mb-2 opacity-50"></div>}
                                            </div>
                                        )}

                                        <div className="flex-1 p-6 space-y-6">
                                            {/* Categories */}
                                            <h2 className="text-xl font-bold mb-4" style={{ color: 'var(--col-head-content)' }}>Categories</h2>
                                            <div className="grid grid-cols-3 gap-4 mb-8">
                                                {['Wellness', 'Fitness', 'Health'].map((cat, i) => (
                                                    <div 
                                                        key={i} 
                                                        className="rounded-lg p-4 flex flex-col items-center justify-center text-center border transition-all cursor-pointer"
                                                        style={{ 
                                                            backgroundColor: bookingConfig.categories.showPageColor ? (i===0 ? 'var(--col-primary)' : '#f8fafc') : '#fff', 
                                                            color: bookingConfig.categories.showPageColor && i===0 ? '#fff' : 'var(--col-text-content)',
                                                            borderColor: 'var(--form-border)',
                                                            borderWidth: 'var(--border-w)',
                                                            borderRadius: 'var(--border-r)'
                                                        }}
                                                    >
                                                        <span className="font-bold">{cat}</span>
                                                        {bookingConfig.categories.showCount && <span className="text-xs opacity-70">4 services</span>}
                                                    </div>
                                                ))}
                                            </div>

                                            {/* Service List */}
                                            <h2 className="text-xl font-bold mb-4" style={{ color: 'var(--col-head-content)' }}>{bookingConfig.services.labels.service}s</h2>
                                            
                                            {bookingConfig.services.filters.showSearch && (
                                                <div className="mb-4">
                                                    <div className="w-full h-10 border rounded px-3 flex items-center text-sm text-slate-400" style={{ borderColor: 'var(--form-border)', borderRadius: 'var(--border-r)', backgroundColor: 'var(--form-bg)' }}>Search...</div>
                                                </div>
                                            )}

                                            <div className="space-y-4">
                                                {[1, 2].map((item) => (
                                                    <div 
                                                        key={item} 
                                                        className="border p-4 flex gap-4 items-start transition-all"
                                                        style={{ 
                                                            backgroundColor: bookingConfig.services.layout.showBackground ? '#fff' : 'transparent',
                                                            borderColor: 'var(--form-border)',
                                                            borderWidth: 'var(--border-w)',
                                                            borderRadius: 'var(--border-r)'
                                                        }}
                                                    >
                                                        <div className="w-16 h-16 bg-slate-100 rounded-lg shrink-0" style={{ borderRadius: 'var(--border-r)' }}></div>
                                                        <div className="flex-1">
                                                            <div className="flex justify-between items-start">
                                                                <h3 className="font-bold" style={{ color: 'var(--col-head-content)' }}>Deep Tissue Massage</h3>
                                                                {bookingConfig.services.info.showPrice && <span className="font-bold" style={{ color: 'var(--col-primary)' }}>$120</span>}
                                                            </div>
                                                            <p className="text-sm mt-1 mb-2" style={{ color: 'var(--col-text-content)' }}>Relieve tension with this intensive treatment.</p>
                                                            
                                                            <div className="flex items-center gap-4 text-xs" style={{ color: 'var(--col-text-content)' }}>
                                                                <span>60 min</span>
                                                                {bookingConfig.services.info.showVat && <span>{bookingConfig.services.labels.inclVat}</span>}
                                                            </div>

                                                            <div className="flex justify-end mt-3 gap-2">
                                                                {bookingConfig.services.info.showActionButton && (
                                                                    <button 
                                                                        className="px-4 py-2 rounded text-sm font-medium"
                                                                        style={{ 
                                                                            backgroundColor: 'var(--btn-prim-bg)', 
                                                                            color: 'var(--btn-prim-text)',
                                                                            borderRadius: 'var(--border-r)',
                                                                            border: bookingConfig.global.borders.btnGlobalBorder ? `1px solid var(--form-border)` : 'none'
                                                                        }}
                                                                    >
                                                                        {bookingConfig.services.labels.bookNow}
                                                                    </button>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* CUSTOMER PORTAL PREVIEW */}
                            {context === 'customer' && (
                                <div className="flex h-full flex-col font-sans text-slate-600">
                                    <div className="h-14 border-b border-slate-100 flex items-center justify-between px-4 shrink-0 bg-white">
                                        <div className="font-bold text-brand-600">Logo</div>
                                        <div className="w-8 h-8 bg-slate-200 rounded-full"></div>
                                    </div>
                                    <div className="flex flex-1 bg-slate-50">
                                        {device !== 'mobile' && (
                                            <div className="w-48 border-r border-slate-100 bg-white p-2 space-y-1">
                                                {customerConfig.dashboard && <div className="p-2 bg-brand-50 text-brand-700 text-xs font-bold rounded">Dashboard</div>}
                                                {customerConfig.profile && <div className="p-2 hover:bg-slate-50 text-slate-600 text-xs rounded">My Profile</div>}
                                                {customerConfig.appointments && <div className="p-2 hover:bg-slate-50 text-slate-600 text-xs rounded">Appointments</div>}
                                                <div className="mt-auto pt-4">
                                                    {customerConfig.logout && <div className="p-2 text-rose-600 text-xs rounded font-medium">Logout</div>}
                                                </div>
                                            </div>
                                        )}
                                        <div className="flex-1 p-6 space-y-4">
                                            <div className="h-24 bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
                                                <div className="h-4 w-32 bg-slate-100 rounded mb-2"></div>
                                                <div className="h-8 w-16 bg-slate-100 rounded"></div>
                                            </div>
                                        </div>
                                    </div>
                                    {device === 'mobile' && (
                                        <div className="h-14 border-t border-slate-100 flex justify-around items-center px-2 bg-white">
                                            {customerConfig.dashboard && <div className="w-6 h-6 bg-brand-100 rounded-full"></div>}
                                            {customerConfig.appointments && <div className="w-6 h-6 bg-slate-200 rounded-full"></div>}
                                            {customerConfig.settings && <div className="w-6 h-6 bg-slate-200 rounded-full"></div>}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* EMPLOYEE PORTAL PREVIEW */}
                            {context === 'employee' && (
                                <div className="flex h-full flex-col bg-slate-50 font-sans">
                                    <div className="bg-slate-800 text-white p-4">
                                        <div className="font-bold">Employee Hub</div>
                                    </div>
                                    <div className="p-4 space-y-4">
                                        {employeeConfig.dashboard && (
                                            <div className="bg-white p-4 rounded shadow-sm">
                                                <h5 className="text-xs font-bold text-slate-400 uppercase mb-2">Today</h5>
                                                <div className="flex gap-2">
                                                    <div className="h-12 w-12 bg-brand-100 rounded-full"></div>
                                                    <div className="space-y-1">
                                                        <div className="h-3 w-32 bg-slate-200 rounded"></div>
                                                        <div className="h-3 w-20 bg-slate-200 rounded"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default DesignFrontendModule;