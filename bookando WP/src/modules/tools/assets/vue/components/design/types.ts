// types.ts - TypeScript Interfaces für Design Customization System

/**
 * Multilingual Label (Standard, German, English)
 */
export interface MultilingualLabel {
  default: string
  de?: string
  en?: string
}

/**
 * Template Categories
 */
export type TemplateCategory =
  | 'service_catalog'
  | 'event_list'
  | 'step_by_step'
  | 'customer_portal'
  | 'employee_portal'

/**
 * Color Group (Background, Border, Text)
 */
export interface ColorGroup {
  background: string
  text: string
  border?: string
}

/**
 * Button Color Configuration
 */
export interface ButtonColors {
  background: string
  text: string
  padding?: string
  borderColor?: string
  borderWidth?: number
  borderRadius?: number
  useGlobalBorder?: boolean // Falls true, werden globale Border-Settings verwendet
}

/**
 * Typography Settings
 */
export interface TypographySettings {
  fontFamily: string
  fontSize: number
}

/**
 * Border Settings
 */
export interface BorderSettings {
  width: number
  radius: number
}

/**
 * Form Elements Colors (unified or individual)
 */
export interface FormElementsColors {
  unified: boolean // Wenn true, verwenden alle die gleichen Farben
  input?: {
    background: string
    border: string
    text: string
    placeholder: string
  }
  dropdown?: {
    background: string
    border: string
    text: string
  }
  cards?: {
    background: string
    border: string
    text: string
  }
}

/**
 * Global Settings (Typography, Colors, Borders)
 */
export interface GlobalSettings {
  typography: TypographySettings
  colors: {
    // Primary & State Colors
    primary: string
    success: string
    warning: string
    error: string

    // Sidebar Colors
    sidebar: {
      background: string
      text: string
    }

    // Content Colors
    content: {
      background: string
      heading: string
      text: string
    }

    // Form Elements (unified or individual)
    formElements: FormElementsColors

    // Buttons
    buttons: {
      primary: ButtonColors
      secondary: ButtonColors
    }
  }
  borders: BorderSettings
}

/**
 * Categories Section Options
 */
export interface CategoriesOptions {
  showCardSideColor: boolean
  showAccentColor: boolean
  showTotalServicesCount: boolean
}

/**
 * Categories Section Labels
 */
export interface CategoriesLabels {
  primaryButton: MultilingualLabel
}

/**
 * Categories Section
 */
export interface CategoriesSection {
  options: CategoriesOptions
  labels: CategoriesLabels
}

/**
 * Services Overview Layout Options
 */
export interface ServicesLayoutOptions {
  showBackground: boolean
  showSidebar: boolean
  showCardColors: boolean
}

/**
 * Services Overview Filter Options
 */
export interface ServicesFilterOptions {
  showSearch: boolean
  showEmployeeFilter: boolean
  showLocationFilter: boolean
}

/**
 * Services Overview Info Options
 */
export interface ServicesInfoOptions {
  showVatVisibility: boolean
  showServiceBadge: boolean
  showServicePrice: boolean
  showActionButton: boolean
  showServiceCategory: boolean
  showServiceDuration: boolean
  showServiceCapacity: boolean
  showServiceLocation: boolean
  showEmployeesButton: boolean
}

/**
 * Services Overview Options
 */
export interface ServicesOverviewOptions {
  layout: ServicesLayoutOptions
  filters: ServicesFilterOptions
  serviceInfo: ServicesInfoOptions
}

/**
 * Services Overview Labels
 */
export interface ServicesOverviewLabels {
  backButton: MultilingualLabel
  service: MultilingualLabel
  bookNow: MultilingualLabel
  free: MultilingualLabel
  multipleLocations: MultilingualLabel
  viewAllPhotos: MultilingualLabel
  aboutService: MultilingualLabel
  employees: MultilingualLabel
  vat: MultilingualLabel
  inclVat: MultilingualLabel
}

/**
 * Services Overview Section
 */
export interface ServicesOverviewSection {
  options: ServicesOverviewOptions
  labels: ServicesOverviewLabels
}

/**
 * Service Details Section (Platzhalter - kann erweitert werden)
 */
export interface ServiceDetailsSection {
  options: Record<string, boolean>
  labels: Record<string, MultilingualLabel>
}

/**
 * Package Details Section (Platzhalter - kann erweitert werden)
 */
export interface PackageDetailsSection {
  options: Record<string, boolean>
  labels: Record<string, MultilingualLabel>
}

/**
 * Template Sections
 */
export interface TemplateSections {
  categories?: CategoriesSection
  servicesOverview?: ServicesOverviewSection
  serviceDetails?: ServiceDetailsSection
  packageDetails?: PackageDetailsSection
}

/**
 * Complete Design Template
 */
export interface DesignTemplate {
  id: number | null
  name: string
  category: TemplateCategory
  globalSettings: GlobalSettings
  sections: TemplateSections
  createdAt?: string
  updatedAt?: string
}

/**
 * Category Card for Level 1 Grid
 */
export interface CategoryCard {
  value: TemplateCategory
  label: string
  description: string
  icon: string
  badge?: string
}

/**
 * Panel State für iOS-style Navigation
 */
export type PanelLevel = 'main' | 'detail' | 'nested'

export interface PanelState {
  level: PanelLevel
  title: string
  component?: string
  data?: any
}

/**
 * Default Settings Factory
 */
export const getDefaultGlobalSettings = (): GlobalSettings => ({
  typography: {
    fontFamily: 'Inter',
    fontSize: 16,
  },
  colors: {
    primary: '#1A84EE',
    success: '#10B981',
    warning: '#F59E0B',
    error: '#EF4444',
    sidebar: {
      background: '#1F2937',
      text: '#F9FAFB',
    },
    content: {
      background: '#FFFFFF',
      heading: '#354052',
      text: '#7F8FA4',
    },
    formElements: {
      unified: true,
      input: {
        background: '#FFFFFF',
        border: '#E2E6EC',
        text: '#354052',
        placeholder: 'rgba(127, 143, 164, 0.5)',
      },
    },
    buttons: {
      primary: {
        background: '#1A84EE',
        text: '#FFFFFF',
        useGlobalBorder: true,
      },
      secondary: {
        background: '#F3F4F6',
        text: '#354052',
        useGlobalBorder: true,
      },
    },
  },
  borders: {
    width: 1,
    radius: 6,
  },
})

export const getDefaultCategoriesSection = (): CategoriesSection => ({
  options: {
    showCardSideColor: true,
    showAccentColor: true,
    showTotalServicesCount: true,
  },
  labels: {
    primaryButton: {
      default: 'Select',
      de: 'Auswählen',
      en: 'Select',
    },
  },
})

export const getDefaultServicesOverviewSection = (): ServicesOverviewSection => ({
  options: {
    layout: {
      showBackground: true,
      showSidebar: true,
      showCardColors: true,
    },
    filters: {
      showSearch: true,
      showEmployeeFilter: true,
      showLocationFilter: true,
    },
    serviceInfo: {
      showVatVisibility: true,
      showServiceBadge: true,
      showServicePrice: true,
      showActionButton: true,
      showServiceCategory: true,
      showServiceDuration: true,
      showServiceCapacity: true,
      showServiceLocation: true,
      showEmployeesButton: false,
    },
  },
  labels: {
    backButton: { default: 'Back', de: 'Zurück', en: 'Back' },
    service: { default: 'Service', de: 'Dienstleistung', en: 'Service' },
    bookNow: { default: 'Book Now', de: 'Jetzt buchen', en: 'Book Now' },
    free: { default: 'Free', de: 'Gratis', en: 'Free' },
    multipleLocations: { default: 'Multiple Locations', de: 'Mehrere Standorte', en: 'Multiple Locations' },
    viewAllPhotos: { default: 'View All Photos', de: 'Alle Fotos ansehen', en: 'View All Photos' },
    aboutService: { default: 'About the Service', de: 'Über die Dienstleistung', en: 'About the Service' },
    employees: { default: 'Employees', de: 'Mitarbeiter', en: 'Employees' },
    vat: { default: 'VAT', de: 'Mwst.', en: 'VAT' },
    inclVat: { default: 'Incl. VAT', de: 'Inkl. Mwst.', en: 'Incl. VAT' },
  },
})
