# Bookando - Vollständiger Umsetzungsplan & Handlungsempfehlungen

**Datum:** 2026-01-11
**Version:** 1.0
**Analyse-Umfang:** Gesamtes Repository (14 Module, 756 Typen, 1110 Zeilen Context)

---

## Executive Summary

Bookando ist eine **hochmoderne Frontend-Anwendung** für Buchungssysteme mit Schwerpunkt auf Fahrschulen, Wellness-Centern und Bildungseinrichtungen. Die aktuelle Implementation bietet:

- ✅ **14 vollständig gestaltete Module** mit einheitlichem Design-System
- ✅ **Umfassende Type-Safety** mit 756 Zeilen TypeScript-Definitionen
- ✅ **4-Sprachen-Support** (DE, EN, FR, IT) mit vollständiger i18n
- ✅ **Sophisticated UI/UX** mit Tailwind CSS und Responsive Design
- ⚠️ **Keine Backend-Integration** - alle Daten sind in-memory
- ⚠️ **Keine Modul-Verknüpfungen** - Module arbeiten isoliert
- ⚠️ **Keine Authentifizierung/Authorization** - Rollen sind UI-only

**Geschätzter Fertigstellungsgrad:** 35% (Frontend: 85%, Backend: 0%, Integration: 10%)

---

## 1. Aktuelle Situation & Kritische Analyse

### 1.1 Was Funktioniert

#### ✅ Vollständig Implementiert (UI-Ebene)

1. **Design System**
   - ModuleLayout-Komponente für einheitliche UX
   - 12 Modul-spezifische Farbschemata und Gradients
   - Responsive Grid-Layouts mit Tailwind CSS
   - Zentrale Design Tokens (`designTokens.ts`)

2. **Internationalisierung**
   - 4 Sprachen vollständig übersetzt
   - Locale-aware Währungsformatierung
   - Einfache Erweiterung für weitere Sprachen

3. **Module mit vollständiger UI**
   - **Academy:** Kurs-Editor, Lesson-Manager, Education Cards, Badge-System
   - **Offers:** Service-Katalog, Bundles, Vouchers, Dynamic Pricing
   - **Finance:** Rechnungsverwaltung, Swiss QR-Bill, Mahnwesen
   - **Customers:** Vollständiges CRM mit Custom Fields
   - **Employees:** Mitarbeiterverwaltung mit Grid-Ansicht
   - **Workday:** Appointments, Time Tracking, Shift Planner, Absence Manager
   - **Resources:** Standorte, Räume, Equipment
   - **Settings:** Rollen, Sprachen, Integrationen

4. **Datenmodelle**
   - Comprehensive Type Definitions in `types.ts`
   - 40+ komplexe Interfaces und Enums
   - Gut durchdachte Beziehungen zwischen Entitäten

### 1.2 Was Fehlt (Kritische Lücken)

#### ❌ Backend & Persistence

1. **Keine Datenbank**
   - Alle Daten leben nur in React State
   - Bei Page Refresh gehen alle Daten verloren
   - Keine localStorage/sessionStorage Implementation

2. **Keine API-Layer**
   - Keine REST API Endpoints
   - Keine GraphQL Implementation
   - Keine WebSocket-Verbindungen

3. **Keine Authentifizierung**
   - Keine User Login/Logout
   - Keine Session Management
   - Keine JWT/OAuth Implementation
   - Rollen/Permissions existieren nur als UI

#### ❌ Modul-Verknüpfungen

1. **Academy ↔ Offers:**
   - Kurse und Services sind getrennte Entitäten
   - Keine direkte Verknüpfung zwischen Course und ServiceItem
   - Keine automatische Synchronisation

2. **Offers ↔ Bookings:**
   - Keine zentrale Booking-Entität
   - Appointments und Services nicht verknüpft
   - Kein Buchungs-Workflow

3. **Bookings ↔ Finance:**
   - Nur rudimentäre Auto-Invoice bei Appointment Completion
   - Keine Zahlungs-Tracking
   - Keine Rechnung-zu-Buchung Verknüpfung

4. **Offers ↔ Employees:**
   - Mitarbeiter-Zuweisung nur in Appointments
   - Keine Verfügbarkeitsprüfung
   - Keine Qualifikations-Matching

5. **Offers ↔ Resources:**
   - Keine Raum-Buchung bei Services
   - Keine Equipment-Zuweisung
   - Keine Konflikt-Erkennung

#### ❌ Business Logic

1. **Buchungs-Workflow**
   - Keine Validierung (Kapazität, Minimum Notice, etc.)
   - Keine Bestätigungs-Emails
   - Keine Reminder
   - Keine Wartelisten

2. **Payment Processing**
   - Payment Options definiert aber nicht implementiert
   - Keine Payment Gateway Integration (Stripe, PayPal)
   - Keine Zahlungsstatus-Verfolgung

3. **Dynamic Pricing**
   - Pricing Rules definiert aber Berechnung fehlt
   - Keine Echtzeit-Preis-Updates
   - Keine A/B-Testing-Funktionalität

4. **Rollen & Berechtigungen**
   - Permissions existieren in UI aber werden nicht enforced
   - Keine Route Guards
   - Keine API-Level Authorization

5. **Kursverwaltung**
   - Keine Student Enrollment
   - Keine Progress Tracking
   - Keine Certificate Issuance
   - Badges manuell, keine Automation

---

## 2. Spezifische Anforderungen & Lösungen

### 2.1 Academy-Modul: Online + Physische Kurse

#### Anforderung
- Online-Kurse mit digitalen Elementen (bereits vorhanden)
- Physische Kurse mit festen Terminen (Grundkurse, 2-Phasen, VKU, Nothelfer)
- Beide Kursarten sollen Academy-Inhalte nutzen können

#### Aktuelle Situation
- `CourseType` Enum existiert: `ONLINE`, `IN_PERSON`, `BLENDED`
- Aber keine Termin-Verwaltung für IN_PERSON Kurse
- EventSession Typ existiert in `ServiceItem`, nicht in `Course`

#### Lösung: Course-Event-Hybrid-System

```typescript
// Erweitere Course Type
interface Course {
  // ... existing fields
  type: CourseType;

  // NEU: Für physische Kurse
  scheduledSessions?: CourseSession[];
  location?: string;
  maxParticipants?: number;
  minParticipants?: number;
  instructorIds?: string[];
  registrationDeadline?: string;

  // NEU: Für Online-Kurse
  platform?: 'Zoom' | 'Google Meet' | 'Microsoft Teams';
  meetingLink?: string;
  accessDuration?: number; // Tage nach Kauf
}

interface CourseSession {
  id: string;
  date: string;
  startTime: string;
  endTime: string;
  instructorId: string;
  locationId: string; // Verknüpfung zu Resources
  roomId?: string;
  currentEnrollment: number;
  status: 'Scheduled' | 'Full' | 'Cancelled' | 'Completed';
}
```

**Implementation Steps:**
1. Erweitere `Course` Interface in `types.ts`
2. Erstelle `CourseSessionManager` Komponente in Academy
3. Füge Kalender-Ansicht für physische Kurse hinzu
4. Verknüpfe mit Resources-Modul für Raumbuchung
5. Verknüpfe mit Employees für Instructor-Zuweisung

### 2.2 Offers-Modul: Zentrale Angebotsübersicht

#### Anforderung
- Alle Website-Angebote (Einzeltermine, Kurse, Online-Kurse) in Offers
- Verknüpfung zu Academy (Kurse, Education Cards)
- Zentrale Buchungsübersicht
- Datums-basiertes Sortieren/Filtern/Planen

#### Aktuelle Situation
- `ServiceItem` hat `type: OfferType` ('Service' | 'Online Course' | 'Event')
- Aber keine Verknüpfung zu `Course` Objekten
- Keine zentrale Booking-Entität

#### Lösung: Unified Offer System

```typescript
// Erweitere ServiceItem
interface ServiceItem {
  // ... existing fields

  // NEU: Academy-Verknüpfung
  linkedCourseId?: string; // Verknüpfung zu Course
  linkedEducationCardId?: string; // Verknüpfung zu Education Card
  includesCourseMaterials: boolean;

  // NEU: Für bessere Kategorisierung
  offerCategory: 'Driving_Lesson' | 'Course' | 'Online_Course' |
                 'Service' | 'Event' | 'Bundle' | 'Voucher';

  // NEU: Für Fahrschul-spezifisch
  licenseCategory?: 'B' | 'A' | 'C' | 'D' | 'BE'; // Führerschein-Kategorien
  requiresLicense?: boolean;
}

// Neue zentrale Booking-Entität
interface Booking {
  id: string;
  bookingNumber: string; // z.B. "BK-2026-001234"
  customerId: string;
  serviceId: string;

  // Datum & Zeit
  bookingDate: string;
  scheduledDate?: string;
  scheduledTime?: string;

  // Session-basiert (für Kurse)
  courseId?: string;
  sessionIds?: string[]; // Für mehrtägige Kurse

  // Status & Workflow
  status: 'Pending' | 'Confirmed' | 'Paid' | 'Completed' | 'Cancelled' | 'No-Show';
  paymentStatus: 'Unpaid' | 'Partial' | 'Paid' | 'Refunded';

  // Preise
  basePrice: number;
  appliedPricing?: DynamicPricingResult;
  extras: BookingExtra[];
  totalPrice: number;

  // Verknüpfungen
  invoiceId?: string;
  assignedEmployeeId?: string;
  formResponses: FormResponse[];

  // Tracking
  createdAt: string;
  confirmedAt?: string;
  paidAt?: string;
  completedAt?: string;
}

interface DynamicPricingResult {
  ruleId: string;
  ruleName: string;
  originalPrice: number;
  adjustedPrice: number;
  adjustmentPercent: number;
  reason: string; // "Early Bird Discount (-20%)"
}

interface BookingExtra {
  extraId: string;
  name: string;
  price: number;
  quantity: number;
}

interface FormResponse {
  fieldId: string;
  fieldLabel: string;
  value: any;
}
```

**Neue Buchungsübersicht (Neues Tab in Offers oder eigenes Modul):**

```typescript
// Neues Modul: Bookings
interface BookingsModule {
  views: {
    calendar: 'Kalender-Ansicht aller Buchungen',
    list: 'Tabellen-Ansicht mit Filtern',
    timeline: 'Gantt-Chart für Kurs-Planung',
  },
  filters: {
    dateRange: 'Von/Bis Datum',
    status: 'Status-Filter',
    service: 'Nach Service-Typ',
    employee: 'Nach Mitarbeiter',
    customer: 'Nach Kunde',
  },
  actions: {
    confirm: 'Buchung bestätigen',
    cancel: 'Buchung stornieren',
    reschedule: 'Umbuchen',
    sendReminder: 'Reminder senden',
    generateInvoice: 'Rechnung erstellen',
  }
}
```

**Implementation Steps:**
1. Erstelle `Booking` Interface in `types.ts`
2. Füge `bookings` State zu AppContext hinzu
3. Erstelle neues `Bookings` Modul oder erweitere Offers
4. Baue Kalender-Komponente mit react-big-calendar
5. Implementiere Filter & Sortierung
6. Verknüpfe mit Finance für Auto-Invoice

### 2.3 Durchgängige Modul-Verknüpfung

#### Anforderung
Kompletter Workflow: Website-Buchung → Buchung → Kunde → Rechnung → Mitarbeiter

#### Lösung: Event-Driven Architecture

```typescript
// Zentrale Event-System
interface SystemEvent {
  id: string;
  type: EventType;
  timestamp: string;
  payload: any;
  source: ModuleName;
  processedBy: ModuleName[];
}

type EventType =
  | 'BOOKING_CREATED'
  | 'BOOKING_CONFIRMED'
  | 'BOOKING_PAID'
  | 'BOOKING_COMPLETED'
  | 'BOOKING_CANCELLED'
  | 'CUSTOMER_CREATED'
  | 'INVOICE_CREATED'
  | 'INVOICE_PAID'
  | 'COURSE_SESSION_FULL'
  | 'EMPLOYEE_ASSIGNED';

// Event Handlers
const eventHandlers = {
  BOOKING_CREATED: [
    createCustomerIfNotExists,
    checkAvailability,
    assignEmployee,
    generateInvoice,
    sendConfirmationEmail,
  ],

  BOOKING_CONFIRMED: [
    updateResourceAllocation,
    sendReminderSchedule,
    triggerEducationCardIfConfigured,
  ],

  BOOKING_PAID: [
    markInvoiceAsPaid,
    sendReceiptEmail,
    grantCourseAccess, // Für Online-Kurse
  ],

  BOOKING_COMPLETED: [
    markSessionComplete,
    triggerCertificateIfEligible,
    requestReview,
  ],

  BOOKING_CANCELLED: [
    releaseResources,
    processRefund,
    sendCancellationEmail,
  ],
};
```

**Workflow-Diagramm:**

```
[Website Booking Form]
        ↓
[Create Booking] → Event: BOOKING_CREATED
        ↓
        ├─→ [Check Customer] → Create if new
        ├─→ [Check Availability] → Validate capacity/time
        ├─→ [Calculate Price] → Apply dynamic pricing
        ├─→ [Assign Employee] → Match skills & availability
        ├─→ [Book Resources] → Reserve room/equipment
        └─→ [Generate Invoice] → Create invoice draft
        ↓
[Booking Status: Pending] → Email: "Awaiting Confirmation"
        ↓
[Admin Confirms] → Event: BOOKING_CONFIRMED
        ↓
        ├─→ [Send Confirmation] → Email with details
        ├─→ [Schedule Reminders] → 1 day before, 1 hour before
        └─→ [Trigger Education Card] → If configured in Service
        ↓
[Payment Received] → Event: BOOKING_PAID
        ↓
        ├─→ [Mark Invoice Paid]
        ├─→ [Send Receipt]
        └─→ [Grant Course Access] → If online course, send login
        ↓
[Session Date] → Event: BOOKING_COMPLETED
        ↓
        ├─→ [Mark Session Complete]
        ├─→ [Check Certificate Eligibility] → All sessions done?
        └─→ [Request Review] → Ask for feedback
```

### 2.4 Rollen & Berechtigungen Enforcement

#### Anforderung
Berechtigungen müssen tatsächlich wirksam sein

#### Lösung: Permission Guards

```typescript
// Permission Checker Hook
function usePermission(module: ModuleName, action: 'read' | 'write' | 'delete') {
  const { currentUser, roles } = useApp();

  return useMemo(() => {
    if (!currentUser) return false;
    const userRole = roles.find(r => r.id === currentUser.roleId);
    if (!userRole) return false;

    return userRole.permissions[module]?.[action] ?? false;
  }, [currentUser, roles, module, action]);
}

// Route Guard Component
function ProtectedRoute({
  module,
  requiredPermission = 'read',
  children
}: {
  module: ModuleName;
  requiredPermission?: 'read' | 'write' | 'delete';
  children: React.ReactNode;
}) {
  const hasPermission = usePermission(module, requiredPermission);

  if (!hasPermission) {
    return <div className="p-8">
      <AlertCircle className="text-red-500 mb-4" size={48} />
      <h2 className="text-xl font-bold">Zugriff verweigert</h2>
      <p>Sie haben keine Berechtigung für dieses Modul.</p>
    </div>;
  }

  return <>{children}</>;
}

// Usage in App.tsx
<ProtectedRoute module="finance" requiredPermission="read">
  <Finance />
</ProtectedRoute>

// Button-Level Guards
function DeleteButton({ onClick }: { onClick: () => void }) {
  const canDelete = usePermission('customers', 'delete');

  if (!canDelete) return null;

  return <button onClick={onClick}>Delete</button>;
}
```

**Implementation Steps:**
1. Füge `currentUser` zu AppContext hinzu
2. Erstelle `usePermission` Hook
3. Erstelle `ProtectedRoute` Komponente
4. Wrap alle Module in `ProtectedRoute`
5. Füge Permission-Checks zu Actions hinzu (Buttons, Forms)
6. Erstelle Login-Seite mit Role-Auswahl (dev mode)

### 2.5 Sprach-Sensitivität

#### Anforderung
Alle Module sollen Sprache aus Settings respektieren

#### Aktuelle Situation
- ✅ Translations vorhanden (4 Sprachen)
- ✅ `t()` Hook verfügbar
- ⚠️ Aber viele hardcoded Strings in Komponenten

#### Lösung: Translation Audit + Enforcement

**Phase 1: Audit**
```bash
# Finde alle hardcoded Strings
grep -r "\"[A-Z][a-z]" modules/ | grep -v "className\|style\|import"
```

**Phase 2: Ersetze mit t() Keys**
```typescript
// Vorher
<h1>Customers</h1>

// Nachher
<h1>{t('customers')}</h1>

// Vorher (komplexer)
<p>You have {count} new bookings today</p>

// Nachher
<p>{t('bookings_today', { count })}</p>

// Update translations.ts
bookings_today: {
  en: 'You have {{count}} new bookings today',
  de: 'Sie haben {{count}} neue Buchungen heute',
  fr: 'Vous avez {{count}} nouvelles réservations aujourd\'hui',
  it: 'Hai {{count}} nuove prenotazioni oggi',
}
```

**Phase 3: CI/CD Check**
```typescript
// Add ESLint rule: no-hardcoded-strings
// Add pre-commit hook to check for untranslated strings
```

---

## 3. Lizenzierungssystem

### 3.1 Konzept: Modul-basierte Lizenzierung

```typescript
interface License {
  id: string;
  organizationId: string;

  // Lizenz-Typ
  tier: 'starter' | 'professional' | 'enterprise';

  // Modul-Aktivierung
  enabledModules: {
    [key in ModuleName]?: ModuleCapabilities;
  };

  // Limits
  limits: {
    maxUsers: number;
    maxCustomers: number;
    maxBookingsPerMonth: number;
    maxStorageGB: number;
    maxAPICallsPerDay: number;
  };

  // Features
  features: {
    whiteLabel: boolean;
    customDomain: boolean;
    advancedReporting: boolean;
    apiAccess: boolean;
    webhooks: boolean;
    sso: boolean;
    multiLanguage: boolean;
  };

  // Billing
  validFrom: string;
  validUntil: string;
  billingCycle: 'monthly' | 'yearly';
  price: number;
  currency: string;

  // Status
  status: 'active' | 'trial' | 'suspended' | 'cancelled';
  trialEndsAt?: string;
}

interface ModuleCapabilities {
  enabled: boolean;
  maxRecords?: number; // z.B. max 100 Kurse in Academy
  features: string[]; // z.B. ['export', 'import', 'advanced_filters']
}
```

### 3.2 Lizenz-Tiers

#### 🥉 Starter (49 CHF/Monat)
**Zielgruppe:** Einzelunternehmer, kleine Studios

**Inkludierte Module:**
- ✅ Dashboard (read-only)
- ✅ Customers (max 100)
- ✅ Appointments (max 50/Monat)
- ✅ Offers (max 10 Services)
- ❌ Academy
- ❌ Finance (nur Basis-Rechnungen)
- ❌ Employees (nur 1 User)
- ❌ Workday (nur Appointments)
- ❌ Resources
- ❌ Tools (nur Basic Reports)
- ❌ Settings (eingeschränkt)

**Limits:**
- 1 User
- 100 Kunden
- 50 Buchungen/Monat
- 1 GB Storage
- Kein API-Zugriff
- Email-Support

---

#### 🥈 Professional (149 CHF/Monat)
**Zielgruppe:** Fahrschulen, Wellness-Center, Yoga-Studios

**Inkludierte Module:**
- ✅ Dashboard (volle Funktionalität)
- ✅ Customers (max 1000)
- ✅ Appointments (unlimited)
- ✅ Offers (unlimited Services + Bundles + Vouchers)
- ✅ Academy (max 20 Kurse)
  - Online-Kurse
  - Education Cards
  - ❌ Zertifikate (nur Enterprise)
- ✅ Finance (volle Funktionalität)
  - Rechnungen
  - Mahnwesen
  - QR-Bill
  - VAT-Reports
- ✅ Employees (max 10)
- ✅ Workday (volle Funktionalität)
- ✅ Resources (max 5 Locations)
- ✅ Tools (Advanced Reports)
- ✅ Settings (volle Funktionalität)

**Limits:**
- 10 Users
- 1000 Kunden
- Unlimited Buchungen
- 10 GB Storage
- 10'000 API Calls/Tag
- Email + Chat Support

**Features:**
- ✅ Multi-Language
- ✅ Webhooks
- ❌ White-Label
- ❌ Custom Domain
- ❌ SSO

---

#### 🥇 Enterprise (499 CHF/Monat + Custom)
**Zielgruppe:** Große Fahrschul-Ketten, Franchises

**Inkludierte Module:**
- ✅ Alle Module ohne Einschränkungen
- ✅ Academy (unlimited Kurse)
  - Online + Physische Kurse
  - Education Cards
  - Zertifikate
  - Badges
  - LMS-Integration
- ✅ PartnerHub (API-Management)

**Limits:**
- Unlimited Users
- Unlimited Kunden
- Unlimited Buchungen
- 100 GB Storage (erweiterbar)
- Unlimited API Calls
- 24/7 Phone + Priority Support

**Features:**
- ✅ White-Label
- ✅ Custom Domain
- ✅ SSO (SAML, OAuth)
- ✅ Dedicated Account Manager
- ✅ Custom Integrations
- ✅ SLA Garantie (99.9% Uptime)
- ✅ On-Premise Option

---

### 3.3 Add-Ons (für Professional & Enterprise)

| Add-On | Beschreibung | Preis |
|--------|-------------|-------|
| **Extra Users** | +10 User Lizenzen | +29 CHF/Monat |
| **Extra Storage** | +50 GB Storage | +19 CHF/Monat |
| **Advanced Academy** | Unlimited Kurse, Zertifikate | +79 CHF/Monat |
| **SMS Notifications** | SMS-Reminder an Kunden | +0.10 CHF/SMS |
| **Payment Gateway** | Stripe/PayPal Integration | +2% pro Transaktion |
| **Custom Branding** | Logo, Farben, Domain | +99 CHF/Monat |
| **API Access** | REST API + Webhooks | +49 CHF/Monat |

### 3.4 Implementation: License Check System

```typescript
// LicenseService.ts
class LicenseService {
  private license: License | null = null;

  async loadLicense(organizationId: string): Promise<License> {
    // API Call to backend
    const response = await fetch(`/api/licenses/${organizationId}`);
    this.license = await response.json();
    return this.license;
  }

  canAccessModule(module: ModuleName): boolean {
    if (!this.license) return false;
    if (this.license.status !== 'active') return false;

    return this.license.enabledModules[module]?.enabled ?? false;
  }

  canUseFeature(feature: keyof License['features']): boolean {
    if (!this.license) return false;
    return this.license.features[feature] ?? false;
  }

  getRemainingCapacity(resource: keyof License['limits']): number {
    if (!this.license) return 0;

    const limit = this.license.limits[resource];
    const current = this.getCurrentUsage(resource);

    return Math.max(0, limit - current);
  }

  async checkLimit(resource: keyof License['limits']): Promise<boolean> {
    const remaining = this.getRemainingCapacity(resource);

    if (remaining <= 0) {
      this.showUpgradePrompt(resource);
      return false;
    }

    return true;
  }

  private showUpgradePrompt(resource: string): void {
    // Show modal: "You've reached your limit. Upgrade to Professional?"
  }

  private getCurrentUsage(resource: keyof License['limits']): number {
    // Query current usage from backend/state
    // e.g., count customers, bookings this month, etc.
    return 0; // Placeholder
  }
}

// Usage in Components
function CustomerModule() {
  const license = useLicense();

  const handleCreateCustomer = async () => {
    const canCreate = await license.checkLimit('maxCustomers');
    if (!canCreate) return;

    // Proceed with creation
  };

  return (
    <div>
      {!license.canAccessModule('customers') && (
        <UpgradePrompt module="customers" />
      )}

      {license.canAccessModule('customers') && (
        <>
          <button onClick={handleCreateCustomer}>
            Add Customer
          </button>

          <p>
            {license.getRemainingCapacity('maxCustomers')} customers remaining
          </p>
        </>
      )}
    </div>
  );
}

// License Context
interface LicenseContextType {
  license: License | null;
  loading: boolean;
  canAccessModule: (module: ModuleName) => boolean;
  canUseFeature: (feature: keyof License['features']) => boolean;
  getRemainingCapacity: (resource: keyof License['limits']) => number;
  checkLimit: (resource: keyof License['limits']) => Promise<boolean>;
}

const LicenseContext = createContext<LicenseContextType | null>(null);

export function LicenseProvider({ children }: { children: React.ReactNode }) {
  const [license, setLicense] = useState<License | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadLicense().then(setLicense).finally(() => setLoading(false));
  }, []);

  const value: LicenseContextType = {
    license,
    loading,
    canAccessModule: (module) => license?.enabledModules[module]?.enabled ?? false,
    canUseFeature: (feature) => license?.features[feature] ?? false,
    getRemainingCapacity: (resource) => {
      if (!license) return 0;
      const limit = license.limits[resource];
      const current = getCurrentUsage(resource);
      return Math.max(0, limit - current);
    },
    checkLimit: async (resource) => {
      const remaining = value.getRemainingCapacity(resource);
      if (remaining <= 0) {
        showUpgradeModal(resource);
        return false;
      }
      return true;
    },
  };

  return (
    <LicenseContext.Provider value={value}>
      {children}
    </LicenseContext.Provider>
  );
}

export function useLicense() {
  const context = useContext(LicenseContext);
  if (!context) throw new Error('useLicense must be used within LicenseProvider');
  return context;
}
```

### 3.5 Upgrade Flow

```typescript
// UpgradeModal.tsx
function UpgradeModal({
  currentTier,
  limitedResource
}: {
  currentTier: 'starter' | 'professional';
  limitedResource: string;
}) {
  const messages = {
    maxCustomers: 'Sie haben das Kunden-Limit erreicht.',
    maxBookingsPerMonth: 'Sie haben das Buchungs-Limit für diesen Monat erreicht.',
    maxAPICallsPerDay: 'Sie haben das API-Limit erreicht.',
  };

  return (
    <Modal>
      <div className="p-6">
        <AlertCircle className="text-amber-500 mb-4" size={48} />
        <h2 className="text-2xl font-bold mb-2">Upgrade Erforderlich</h2>
        <p className="text-slate-600 mb-6">
          {messages[limitedResource as keyof typeof messages]}
        </p>

        {currentTier === 'starter' && (
          <div className="bg-gradient-to-br from-brand-50 to-brand-100 p-6 rounded-xl mb-4">
            <h3 className="font-bold text-lg mb-2">Professional Plan</h3>
            <ul className="space-y-2 mb-4">
              <li>✅ 1000 Kunden (statt 100)</li>
              <li>✅ Unlimited Buchungen</li>
              <li>✅ Academy Modul</li>
              <li>✅ Volle Finance-Funktionalität</li>
            </ul>
            <p className="text-2xl font-bold">149 CHF/Monat</p>
          </div>
        )}

        <div className="flex gap-3">
          <button className="btn-secondary">Später</button>
          <button className="btn-primary">Jetzt upgraden</button>
        </div>
      </div>
    </Modal>
  );
}
```

---

## 4. Detaillierter Umsetzungsplan

### Phase 1: Fundament (4-6 Wochen)

#### Sprint 1: Backend Setup (2 Wochen)
**Ziel:** Grundlegende Backend-Infrastruktur

**Tasks:**
1. **Backend-Technologie wählen**
   - **Option A:** Node.js + Express + PostgreSQL + Prisma
   - **Option B:** Python + FastAPI + PostgreSQL + SQLAlchemy
   - **Option C:** .NET Core + Entity Framework + PostgreSQL
   - **Empfehlung:** Node.js (TypeScript-Sharing mit Frontend)

2. **Database Schema**
   ```sql
   -- Core Tables
   CREATE TABLE organizations (
     id UUID PRIMARY KEY,
     name VARCHAR(255),
     created_at TIMESTAMP
   );

   CREATE TABLE users (
     id UUID PRIMARY KEY,
     organization_id UUID REFERENCES organizations(id),
     email VARCHAR(255) UNIQUE,
     password_hash VARCHAR(255),
     role_id UUID REFERENCES roles(id),
     created_at TIMESTAMP
   );

   CREATE TABLE customers (
     id UUID PRIMARY KEY,
     organization_id UUID REFERENCES organizations(id),
     first_name VARCHAR(100),
     last_name VARCHAR(100),
     email VARCHAR(255),
     phone VARCHAR(50),
     created_at TIMESTAMP,
     updated_at TIMESTAMP
   );

   CREATE TABLE courses (
     id UUID PRIMARY KEY,
     organization_id UUID REFERENCES organizations(id),
     title VARCHAR(255),
     type VARCHAR(50), -- 'Online' | 'InPerson' | 'Blended'
     curriculum JSONB,
     created_at TIMESTAMP
   );

   CREATE TABLE course_sessions (
     id UUID PRIMARY KEY,
     course_id UUID REFERENCES courses(id),
     date DATE,
     start_time TIME,
     end_time TIME,
     instructor_id UUID REFERENCES users(id),
     location_id UUID REFERENCES locations(id),
     max_participants INT,
     current_enrollment INT DEFAULT 0,
     status VARCHAR(50)
   );

   CREATE TABLE services (
     id UUID PRIMARY KEY,
     organization_id UUID REFERENCES organizations(id),
     title VARCHAR(255),
     type VARCHAR(50),
     price DECIMAL(10,2),
     duration_minutes INT,
     linked_course_id UUID REFERENCES courses(id),
     form_template_id UUID REFERENCES form_templates(id)
   );

   CREATE TABLE bookings (
     id UUID PRIMARY KEY,
     booking_number VARCHAR(50) UNIQUE,
     organization_id UUID REFERENCES organizations(id),
     customer_id UUID REFERENCES customers(id),
     service_id UUID REFERENCES services(id),
     course_session_id UUID REFERENCES course_sessions(id),
     scheduled_date DATE,
     scheduled_time TIME,
     status VARCHAR(50),
     payment_status VARCHAR(50),
     total_price DECIMAL(10,2),
     form_responses JSONB,
     created_at TIMESTAMP
   );

   CREATE TABLE invoices (
     id UUID PRIMARY KEY,
     organization_id UUID REFERENCES organizations(id),
     booking_id UUID REFERENCES bookings(id),
     customer_id UUID REFERENCES customers(id),
     invoice_number VARCHAR(50) UNIQUE,
     amount DECIMAL(10,2),
     status VARCHAR(50),
     due_date DATE,
     created_at TIMESTAMP
   );

   CREATE TABLE licenses (
     id UUID PRIMARY KEY,
     organization_id UUID REFERENCES organizations(id),
     tier VARCHAR(50),
     enabled_modules JSONB,
     limits JSONB,
     features JSONB,
     valid_until DATE,
     status VARCHAR(50)
   );
   ```

3. **API Endpoints (REST)**
   ```
   Authentication:
   POST   /api/auth/login
   POST   /api/auth/logout
   POST   /api/auth/refresh
   GET    /api/auth/me

   Customers:
   GET    /api/customers
   POST   /api/customers
   GET    /api/customers/:id
   PUT    /api/customers/:id
   DELETE /api/customers/:id

   Courses:
   GET    /api/courses
   POST   /api/courses
   GET    /api/courses/:id
   PUT    /api/courses/:id
   DELETE /api/courses/:id
   GET    /api/courses/:id/sessions
   POST   /api/courses/:id/sessions

   Services:
   GET    /api/services
   POST   /api/services
   GET    /api/services/:id
   PUT    /api/services/:id
   DELETE /api/services/:id

   Bookings:
   GET    /api/bookings
   POST   /api/bookings
   GET    /api/bookings/:id
   PUT    /api/bookings/:id
   DELETE /api/bookings/:id
   POST   /api/bookings/:id/confirm
   POST   /api/bookings/:id/cancel

   Invoices:
   GET    /api/invoices
   POST   /api/invoices
   GET    /api/invoices/:id
   PUT    /api/invoices/:id
   POST   /api/invoices/:id/send
   GET    /api/invoices/:id/pdf

   Licenses:
   GET    /api/licenses/:organizationId
   PUT    /api/licenses/:organizationId
   ```

4. **Authentication & Authorization**
   - JWT-basierte Authentifizierung
   - Refresh Token Rotation
   - Role-Based Access Control (RBAC)
   - Permission Middleware für alle API Routes

**Deliverables:**
- ✅ Backend Server läuft
- ✅ Database Schema deployed
- ✅ API Endpoints implementiert
- ✅ Swagger/OpenAPI Dokumentation
- ✅ Unit Tests für Core Services

#### Sprint 2: Frontend-Backend Integration (2 Wochen)

**Tasks:**
1. **API Client Setup**
   ```typescript
   // api/client.ts
   import axios from 'axios';

   const apiClient = axios.create({
     baseURL: import.meta.env.VITE_API_URL,
     timeout: 10000,
   });

   // Request Interceptor (Auth Token)
   apiClient.interceptors.request.use((config) => {
     const token = localStorage.getItem('auth_token');
     if (token) {
       config.headers.Authorization = `Bearer ${token}`;
     }
     return config;
   });

   // Response Interceptor (Token Refresh)
   apiClient.interceptors.response.use(
     (response) => response,
     async (error) => {
       if (error.response?.status === 401) {
         // Token expired, try refresh
         const refreshToken = localStorage.getItem('refresh_token');
         if (refreshToken) {
           const { data } = await axios.post('/api/auth/refresh', { refreshToken });
           localStorage.setItem('auth_token', data.token);
           error.config.headers.Authorization = `Bearer ${data.token}`;
           return axios.request(error.config);
         }
       }
       return Promise.reject(error);
     }
   );

   export default apiClient;
   ```

2. **Service Layer**
   ```typescript
   // services/CustomerService.ts
   import apiClient from '../api/client';
   import { Customer } from '../types';

   export const CustomerService = {
     async getAll(): Promise<Customer[]> {
       const { data } = await apiClient.get('/customers');
       return data;
     },

     async getById(id: string): Promise<Customer> {
       const { data } = await apiClient.get(`/customers/${id}`);
       return data;
     },

     async create(customer: Partial<Customer>): Promise<Customer> {
       const { data } = await apiClient.post('/customers', customer);
       return data;
     },

     async update(id: string, customer: Partial<Customer>): Promise<Customer> {
       const { data } = await apiClient.put(`/customers/${id}`, customer);
       return data;
     },

     async delete(id: string): Promise<void> {
       await apiClient.delete(`/customers/${id}`);
     },
   };
   ```

3. **Update AppContext to use API**
   ```typescript
   // context/AppContext.tsx
   export function AppProvider({ children }: { children: React.ReactNode }) {
     const [customers, setCustomers] = useState<Customer[]>([]);
     const [loading, setLoading] = useState(true);

     // Load data from API on mount
     useEffect(() => {
       loadInitialData();
     }, []);

     const loadInitialData = async () => {
       try {
         const [customersData, coursesData, servicesData] = await Promise.all([
           CustomerService.getAll(),
           CourseService.getAll(),
           ServiceService.getAll(),
         ]);

         setCustomers(customersData);
         setCourses(coursesData);
         setServices(servicesData);
       } catch (error) {
         console.error('Failed to load data:', error);
       } finally {
         setLoading(false);
       }
     };

     const addCustomer = async (customer: Partial<Customer>) => {
       const newCustomer = await CustomerService.create(customer);
       setCustomers([...customers, newCustomer]);
       return newCustomer;
     };

     // ... rest of context
   }
   ```

4. **Login Page**
   ```typescript
   // pages/Login.tsx
   function Login() {
     const [email, setEmail] = useState('');
     const [password, setPassword] = useState('');
     const navigate = useNavigate();

     const handleLogin = async (e: React.FormEvent) => {
       e.preventDefault();

       try {
         const { data } = await apiClient.post('/auth/login', { email, password });
         localStorage.setItem('auth_token', data.token);
         localStorage.setItem('refresh_token', data.refreshToken);
         navigate('/');
       } catch (error) {
         alert('Login failed');
       }
     };

     return (
       <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-brand-600 to-brand-800">
         <form onSubmit={handleLogin} className="bg-white p-8 rounded-xl shadow-xl w-96">
           <h1 className="text-2xl font-bold mb-6">Bookando Login</h1>
           <input type="email" value={email} onChange={e => setEmail(e.target.value)} />
           <input type="password" value={password} onChange={e => setPassword(e.target.value)} />
           <button type="submit">Login</button>
         </form>
       </div>
     );
   }
   ```

**Deliverables:**
- ✅ API Client konfiguriert
- ✅ Alle Service Layer implementiert
- ✅ AppContext nutzt API statt Mock-Daten
- ✅ Login/Logout funktioniert
- ✅ Protected Routes implementiert

---

### Phase 2: Kernfunktionalität (6-8 Wochen)

#### Sprint 3: Booking System (2 Wochen)

**Tasks:**
1. **Booking-Entität erweitern**
   - Erstelle `Booking` Interface wie oben beschrieben
   - Füge `bookings` State zu AppContext hinzu
   - Implementiere BookingService

2. **Booking-Modul erstellen**
   - Neues Modul: `/modules/Bookings.tsx`
   - Kalender-Ansicht mit react-big-calendar
   - Listen-Ansicht mit Filtern
   - Detail-Ansicht mit Timeline

3. **Booking-Workflow**
   - Status-Maschine implementieren
   - Auto-Invoice bei Payment
   - Email-Notifications (Template-System)

4. **Public Booking Portal**
   - Öffentliche Seite für Kunden
   - Service-Auswahl
   - Datum/Zeit-Picker
   - Form Rendering (dynamisch)
   - Payment Integration (Stripe Checkout)

**Deliverables:**
- ✅ Booking CRUD funktioniert
- ✅ Kalender zeigt alle Buchungen
- ✅ Status-Workflow implementiert
- ✅ Public Booking Form funktioniert

#### Sprint 4: Course-Service Integration (2 Woeken)

**Tasks:**
1. **Course-Session System**
   - `CourseSession` Table erstellen
   - Session CRUD in Academy
   - Kalender-Ansicht für Sessions

2. **Service ↔ Course Linking**
   - `linkedCourseId` in ServiceItem
   - Beim Service-Kauf: Auto-Enroll in Course
   - Course Access Management

3. **Enrollment Tracking**
   - Student-Course Relationship
   - Progress Tracking (welche Lessons completed)
   - Certificate Generation bei Completion

4. **Education Card Automation**
   - Trigger Education Card bei Service-Buchung
   - Auto-Assignment basierend auf Offer Category

**Deliverables:**
- ✅ Kurse haben Sessions
- ✅ Services können Kurse verknüpfen
- ✅ Enrollment funktioniert
- ✅ Education Cards werden automatisch zugewiesen

#### Sprint 5: Finance Integration (2 Wochen)

**Tasks:**
1. **Invoice Auto-Generation**
   - Event Listener: BOOKING_CONFIRMED → Create Invoice
   - Template Rendering mit HTML
   - PDF Generation (puppeteer oder pdfmake)

2. **Payment Processing**
   - Stripe Integration
   - PayPal Integration
   - Webhook Handler für Payment Success
   - Event: PAYMENT_RECEIVED → Mark Invoice Paid

3. **Dunning Process**
   - Cron Job: Check Overdue Invoices
   - Auto-send Reminder Emails (Level 1, 2, 3)
   - Escalation to Collection

4. **Financial Reports**
   - Revenue Report (Daily/Monthly/Yearly)
   - Outstanding Invoices Report
   - VAT Report (Swiss Tax Format)

**Deliverables:**
- ✅ Invoices werden automatisch erstellt
- ✅ Zahlungen werden verarbeitet
- ✅ Mahnwesen funktioniert
- ✅ Reports generierbar

#### Sprint 6: Employee & Resource Management (2 Wochen)

**Tasks:**
1. **Employee Availability**
   - Working Hours Definition
   - Absence Calendar
   - Booking Conflict Detection

2. **Auto-Assignment**
   - Skills/Qualifications Matching
   - Load Balancing (equal distribution)
   - Preferred Employee Selection

3. **Resource Booking**
   - Room Availability Check
   - Equipment Allocation
   - Conflict Resolution

4. **Capacity Management**
   - Service Capacity Enforcement
   - Waitlist System
   - Overbooking Alerts

**Deliverables:**
- ✅ Mitarbeiter-Verfügbarkeit berücksichtigt
- ✅ Auto-Assignment funktioniert
- ✅ Räume werden automatisch gebucht
- ✅ Kapazitäten werden eingehalten

---

### Phase 3: Advanced Features (4-6 Wochen)

#### Sprint 7: Dynamic Pricing Engine (2 Wochen)

**Tasks:**
1. **Pricing Calculation Service**
   ```typescript
   class PricingEngine {
     calculate(
       service: ServiceItem,
       bookingDate: Date,
       bookingTime: string,
       currentDemand: number
     ): PricingResult {
       let price = service.price;
       const appliedRules: AppliedRule[] = [];

       if (service.pricingRuleId) {
         const rule = getPricingRule(service.pricingRuleId);

         switch (rule.type) {
           case 'EarlyBird':
             price = this.applyEarlyBird(price, rule, bookingDate);
             break;
           case 'LastMinute':
             price = this.applyLastMinute(price, rule, bookingDate);
             break;
           case 'Season':
             price = this.applySeasonal(price, rule, bookingDate);
             break;
           case 'Demand':
             price = this.applyDemand(price, rule, currentDemand);
             break;
         }
       }

       return { originalPrice: service.price, finalPrice: price, appliedRules };
     }
   }
   ```

2. **Real-time Price Updates**
   - WebSocket für Live-Preis-Updates
   - Demand Tracking (Bookings pro Stunde)
   - Cache-System für Performance

3. **A/B Testing**
   - Pricing Experiment Framework
   - Conversion Tracking
   - Analytics Dashboard

**Deliverables:**
- ✅ Dynamic Pricing funktioniert
- ✅ Preise werden live berechnet
- ✅ A/B Tests durchführbar

#### Sprint 8: Notifications & Communications (2 Woeken)

**Tasks:**
1. **Email System**
   - Email Templates (Handlebars/Mustache)
   - SMTP Configuration
   - Email Queue (Bull/BullMQ)
   - Templates:
     - Booking Confirmation
     - Payment Receipt
     - Reminder (24h, 1h before)
     - Cancellation
     - Invoice
     - Course Access

2. **SMS System**
   - Twilio Integration
   - SMS Templates
   - Opt-in/Opt-out Management

3. **In-App Notifications**
   - Notification Center
   - Real-time via WebSocket
   - Push Notifications (PWA)

4. **Automated Workflows**
   - Reminder Scheduler
   - Follow-up Emails
   - Review Requests

**Deliverables:**
- ✅ Email-System funktioniert
- ✅ SMS werden versendet
- ✅ In-App Notifications
- ✅ Workflows automatisiert

#### Sprint 9: Reporting & Analytics (1 Woche)

**Tasks:**
1. **Dashboard Charts**
   - Revenue Chart (real data)
   - Bookings Chart
   - Customer Growth
   - Service Popularity

2. **Advanced Reports**
   - Custom Report Builder
   - Export to Excel/PDF
   - Scheduled Reports (Email)

3. **Business Intelligence**
   - Customer Lifetime Value
   - Churn Prediction
   - Service Performance

**Deliverables:**
- ✅ Dashboard zeigt echte Daten
- ✅ Reports exportierbar
- ✅ BI-Metriken verfügbar

#### Sprint 10: Multi-Tenant & License System (1 Woche)

**Tasks:**
1. **Multi-Tenancy**
   - Organization Isolation
   - Subdomain Routing
   - Database Row-Level Security

2. **License Management**
   - License Service implementieren
   - Frontend Guards
   - Upgrade Flow
   - Trial Management

3. **Billing System**
   - Stripe Subscriptions
   - Invoice Generation for Bookando
   - Usage Tracking

**Deliverables:**
- ✅ Multi-Tenant funktioniert
- ✅ Lizenzen werden geprüft
- ✅ Upgrades möglich
- ✅ Billing automatisiert

---

### Phase 4: Polish & Launch (2-3 Wochen)

#### Sprint 11: Testing & Bug Fixing (1 Woche)

**Tasks:**
- E2E Tests (Playwright/Cypress)
- Load Testing (k6)
- Security Audit
- Bug Fixing

#### Sprint 12: Documentation & Launch (1 Woche)

**Tasks:**
- User Documentation
- API Documentation
- Admin Guide
- Video Tutorials
- Soft Launch (Beta)

#### Sprint 13: Monitoring & Optimization (1 Woche)

**Tasks:**
- Error Tracking (Sentry)
- Performance Monitoring (New Relic)
- User Analytics (PostHog)
- Optimize Queries
- CDN Setup

---

## 5. Technologie-Stack Empfehlungen

### 5.1 Backend

**Primary Stack:**
```yaml
Runtime: Node.js 20 LTS
Language: TypeScript
Framework: Express.js oder Fastify
ORM: Prisma
Database: PostgreSQL 15
Cache: Redis 7
Queue: BullMQ
Email: Nodemailer + SendGrid
SMS: Twilio
Payments: Stripe + PayPal SDK
File Storage: AWS S3 oder Cloudflare R2
```

**Alternative Stack (Enterprise):**
```yaml
Runtime: .NET 8
Language: C#
Framework: ASP.NET Core
ORM: Entity Framework Core
Database: PostgreSQL 15
Cache: Redis
Queue: Hangfire
Email: SendGrid
Payments: Stripe
Storage: Azure Blob Storage
```

### 5.2 Frontend (bereits vorhanden)

```yaml
Framework: React 19.2
Language: TypeScript
Styling: Tailwind CSS
Icons: Lucide React
Charts: Recharts
State: React Context (später Redux Toolkit)
Forms: React Hook Form + Zod
Calendar: react-big-calendar
Date: date-fns
HTTP: Axios
```

### 5.3 DevOps

```yaml
Hosting: Vercel (Frontend) + Railway (Backend)
CI/CD: GitHub Actions
Monitoring: Sentry + PostHog
Logging: Winston + Logtail
Backup: Automated PostgreSQL Backups
CDN: Cloudflare
```

### 5.4 Development Tools

```yaml
Package Manager: pnpm
Linting: ESLint + Prettier
Testing: Vitest + Playwright
API Testing: Postman/Insomnia
Database Tool: TablePlus
Design: Figma
```

---

## 6. Kostenabschätzung

### 6.1 Entwicklungskosten

| Phase | Dauer | Entwickler | Kosten (100 CHF/h) |
|-------|-------|------------|---------------------|
| Phase 1: Fundament | 6 Wochen | 1 Senior Dev | 24'000 CHF |
| Phase 2: Kern | 8 Wochen | 1 Senior + 1 Mid | 64'000 CHF |
| Phase 3: Advanced | 6 Wochen | 1 Senior + 1 Mid | 48'000 CHF |
| Phase 4: Polish | 3 Wochen | 1 Senior | 12'000 CHF |
| **Total** | **23 Wochen** | | **148'000 CHF** |

### 6.2 Laufende Kosten (Monatlich)

| Service | Kosten |
|---------|--------|
| Vercel (Frontend) | 20 CHF |
| Railway/Render (Backend) | 100 CHF |
| PostgreSQL (Hosted) | 50 CHF |
| Redis (Hosted) | 30 CHF |
| SendGrid (Email) | 50 CHF |
| Twilio (SMS, 1000 SMS) | 100 CHF |
| Stripe (Gebühren) | 2.9% + 0.30 CHF |
| Sentry (Monitoring) | 30 CHF |
| Cloudflare (CDN) | 0 CHF (Free) |
| **Total** | **~380 CHF/Monat** |

### 6.3 Revenue Projections

**Conservative (Jahr 1):**
- 50 Starter Kunden × 49 CHF = 2'450 CHF/Monat
- 20 Professional Kunden × 149 CHF = 2'980 CHF/Monat
- 2 Enterprise Kunden × 499 CHF = 998 CHF/Monat
- **Total: 6'428 CHF/Monat × 12 = 77'136 CHF/Jahr**

**Optimistic (Jahr 2):**
- 100 Starter × 49 = 4'900 CHF
- 50 Professional × 149 = 7'450 CHF
- 5 Enterprise × 499 = 2'495 CHF
- **Total: 14'845 CHF/Monat × 12 = 178'140 CHF/Jahr**

**Break-Even:** Nach ca. 6 Monaten mit 40-50 Kunden

---

## 7. Risiken & Mitigation

### 7.1 Technische Risiken

| Risiko | Wahrscheinlichkeit | Impact | Mitigation |
|--------|-------------------|--------|------------|
| Skalierungsprobleme | Mittel | Hoch | Load Testing, Caching, CDN |
| Datenverlust | Niedrig | Kritisch | Automated Backups, Replication |
| Security Breach | Mittel | Kritisch | Security Audits, Penetration Testing |
| API Downtime | Mittel | Hoch | Multi-Region Deployment, Failover |
| Payment Failures | Niedrig | Mittel | Webhook Retries, Manual Reconciliation |

### 7.2 Business Risiken

| Risiko | Wahrscheinlichkeit | Impact | Mitigation |
|--------|-------------------|--------|------------|
| Langsame Adoption | Hoch | Hoch | Free Trial, Aggressive Marketing |
| Konkurrenz | Mittel | Mittel | Differenzierung, Fahrschul-Fokus |
| Churn Rate | Mittel | Hoch | Onboarding, Support, Feature Requests |
| Compliance (GDPR) | Niedrig | Kritisch | Legal Review, Data Privacy by Design |

---

## 8. Nächste Schritte

### Sofort (Diese Woche)

1. ✅ **Entscheidung treffen:**
   - Backend-Technologie wählen (Node.js empfohlen)
   - Hosting-Provider wählen (Railway/Render empfohlen)
   - License-Tiers finalisieren

2. ✅ **Repository Setup:**
   ```bash
   # Erstelle Backend-Ordner
   mkdir backend
   cd backend
   npm init -y
   npm install express prisma @prisma/client typescript
   npx prisma init
   ```

3. ✅ **Database Schema schreiben:**
   - Nutze oben genanntes Schema
   - Führe `prisma migrate dev` aus

### Woche 2-3

1. **API Endpoints implementieren:**
   - Starte mit Customers, Courses, Services
   - Teste mit Postman

2. **Frontend Integration:**
   - Erstelle API Client
   - Update AppContext
   - Teste CRUD Operations

### Woche 4-6

1. **Authentication:**
   - Login/Logout
   - JWT Tokens
   - Protected Routes

2. **Booking System:**
   - Erstelle Booking-Modul
   - Implementiere Workflow
   - Public Booking Form

### Danach

- Folge dem 23-Wochen-Plan oben

---

## 9. Zusammenfassung

**Was Sie haben:**
- ✅ Exzellentes Frontend (85% fertig)
- ✅ Durchdachte Datenmodelle
- ✅ Klare Vision für Fahrschul-Business

**Was fehlt:**
- ❌ Backend & API (0% fertig)
- ❌ Database Persistence
- ❌ Authentication & Authorization
- ❌ Modul-Integrationen
- ❌ Payment Processing
- ❌ Email/SMS System

**Was zu tun ist:**
1. Backend aufbauen (Phase 1)
2. Module verknüpfen (Phase 2)
3. Advanced Features (Phase 3)
4. Launch (Phase 4)

**Timeline:** 23 Wochen (ca. 6 Monate)
**Budget:** 148'000 CHF Entwicklung + 380 CHF/Monat Betrieb
**ROI:** Break-Even nach 6-12 Monaten

---

**Kontakt für Fragen:**
- GitHub: `/issues`
- Email: [email protected]
- Slack: #bookando-dev

**Version:** 1.0
**Letzte Aktualisierung:** 2026-01-11
