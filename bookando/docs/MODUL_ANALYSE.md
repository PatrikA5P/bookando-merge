# Bookando Modulanalyse — IST → SOLL → Umsetzungsplan

> Erstellt: 2026-02-07 | Basis: `Bookando reference/` Codebase
> Zweck: Spezifikationsdokument für den Umbau der Module Offers, Ressourcen, Academy, Customers, Panels

---

## Inhaltsverzeichnis

1. [IST-Analyse](#1-ist-analyse)
2. [SOLL-Architektur (Gold Standard)](#2-soll-architektur-gold-standard)
3. [Workflows pro Persona](#3-workflows-pro-persona)
4. [Ressourcen- und Verfügbarkeitslogik](#4-ressourcen--und-verfügbarkeitslogik)
5. [Integrationspunkte Offers ↔ Academy](#5-integrationspunkte-offers--academy)
6. [Konkreter Umsetzungsplan](#6-konkreter-umsetzungsplan)

---

## 1. IST-Analyse

### 1.1 Offers-Modul

#### Datenmodell

**Frontend** (`types.ts:651-725`): Ein einziges `ServiceItem`-Interface mit ~70 optionalen Feldern für alle drei Typen. Typ-Unterscheidung über `type: OfferType` ('Service' | 'Online Course' | 'Event').

**Backend** (`schema.prisma:578-643`): `Service`-Model mit `ServiceType`-Enum (SERVICE, ONLINE_COURSE, EVENT), aber das Feld `type` existiert nicht direkt im Schema — stattdessen `eventType` (String, Default "ONE_ON_ONE") und `isOnline` (Boolean). Die Typ-Zuordnung ist dadurch uneindeutig.

**Inkonsistenzen Frontend ↔ Backend:**

| Aspekt | Frontend (types.ts) | Backend (schema.prisma) | Problem |
|--------|-------------------|----------------------|---------|
| Typ-Feld | `type: OfferType` | `eventType` + `isOnline` | Kein 1:1-Mapping |
| Name | `title` | `name` | Verschiedene Feldnamen |
| Kapazität | `capacity` | `maxParticipants` | Verschiedene Feldnamen |
| Kategorien | `category` + `categories[]` | `category` (Enum) + `categoryId` (FK) | Doppelmodellierung |
| Sessions | `sessions: EventSession[]` (inline) | `CourseSession` (eigenes Model) | Unterschiedliche Struktur |
| Ressourcen | `requiredLocations[]`, `requiredRooms[]`, `requiredEquipment[]` | Keine Felder vorhanden | Frontend-only, kein Backend-Support |
| Puffer | `bufferBefore`, `bufferAfter` | Nicht vorhanden | Kein Backend-Support |
| Extras | `allowedExtras[]`, `customerSelectableExtras[]` | `ServiceExtra` (1:n Relation) | Verschiedene Modelle |

**Kritische Befunde:**

1. **Mega-Interface-Anti-Pattern:** `ServiceItem` ist ein "God Object" — 70+ Felder, davon ~50 optional. Typ-spezifische Felder (z.B. `eventStructure`, `sessions` nur für Events; `isRecurring` nur für Services; `lessons` nur für Online Courses) sind nicht getrennt. Das führt zu unklaren Validierungsregeln und UI-Komplexität.

2. **Kein Slot-Engine:** Es gibt kein Backend-System, das aus Offer-Regeln (Dauer, Puffer, Öffnungszeiten) + Mitarbeiter-Verfügbarkeit + Ressourcen-Verfügbarkeit konkrete buchbare Slots berechnet. Die Frontend-Scheduling-UI (`OfferModal.tsx:480-669`) konfiguriert Regeln, aber es fehlt die Engine, die daraus Verfügbarkeit ableitet.

3. **Event-Sessions ohne Lifecycle:** `EventSession` (Frontend) hat `date`, `startTime`, `endTime`, `instructorId`, `locationId` — aber keinen Status (geplant/durchgeführt/abgesagt) und keine Teilnehmerliste. Backend `CourseSession` hat `status` und `currentEnrollment`, aber die Frontend-Daten fliessen nicht 1:1 durch.

4. **Float für Geld:** `price: Float` im Prisma-Schema (`schema.prisma:594`). Widerspricht der Entscheidung "Money as integer minor units (Rappen/Cents)". Rundungsfehler bei Berechnungen sind vorprogrammiert.

#### Offer-Modal UI (`OfferModal.tsx`, 1012 Zeilen)

- 5 Tabs: General, Pricing, Scheduling, Rules & Limits, Process & Media
- **Typ-Selektion** (Zeile 204-239): Drei Karten, aber nach Auswahl wird alles im selben Formular dargestellt — kein typ-spezifisches Formular
- **Scheduling-Tab** mischt Service-Felder (Duration, Buffer, Recurring) mit Event-Feldern (Sessions, Event Structure) — Conditional Rendering statt sauberer Trennung
- **Kein Validierungsframework:** Felder werden ohne Schema-Validierung gespeichert

#### Booking-Modell (`schema.prisma:733-795`)

```
Booking → Service (1:1)
Booking → CourseSession (optional, 1:1)
Booking → Employee (optional, 1:1)
Booking → resourceAllocation (JSON blob)
```

**Probleme:**
- `resourceAllocation` ist ein **untypisierter JSON-Blob** — keine referenzielle Integrität, keine FK-Constraints, keine Abfrage-Performance
- Kein `Participant`-Model — bei Gruppen-Buchungen wird `participants: Int` gespeichert, aber die einzelnen Teilnehmer sind nicht verknüpft
- Keine Statusmaschine: `BookingStatus` ist ein Enum, aber erlaubte Übergänge (PENDING→CONFIRMED→PAID→COMPLETED, PENDING→CANCELLED) sind nirgends definiert
- `scheduledDate` und `scheduledTime` als **Strings** statt DateTime — Zeitzonen-Probleme, keine DB-Indexierung für Range-Queries

#### Assignment-Strategien (`BookingService.ts:7-690`)

5 Strategien implementiert (ROUND_ROBIN, AVAILABILITY, PRIORITY, SAME_EMPLOYEE, WORKLOAD_BALANCE). **Gut konzipiert**, aber:
- Strategie wird pro Booking gewählt, nicht pro Service konfiguriert (im Schema steht `assignmentStrategy` am Service, aber der BookingService nutzt es nicht konsistent)
- Kein Fallback-Chain (wenn SAME_EMPLOYEE fehlschlägt → nur Fallback auf Workload, nicht konfigurierbar)

---

### 1.2 Ressourcen-Modul

#### Datenmodell

**Drei Ressourcen-Typen (Frontend `types.ts:602-626`):**

```typescript
Location { id, name, address, rooms: number, status: 'Open'|'Closed' }
Room { id, name, location, capacity, features[], status: 'Available'|'In Use'|'Maintenance' }
Equipment { id, name, category, total: number, available: number, condition: 'Good'|'Fair'|'Poor' }
```

**Backend (`schema.prisma:912-942`):**
- `Location`: name, address, city, zip, country — Relationen zu Room[], CourseSession[], Course[]
- `Room`: name, capacity, equipment[] (String-Array) — Relation zu CourseSession[]
- **Equipment: existiert NICHT im Prisma-Schema** — nur als Frontend-Type

**Kritische Befunde:**

1. **Keine Verfügbarkeitsmodellierung:** Weder Location noch Room noch Equipment haben ein Zeitmodell. `Room.status` ist ein statischer Wert ('Available'|'In Use'|'Maintenance'), kein Kalender. Es gibt keine `ResourceAvailability`- oder `ResourceReservation`-Tabelle.

2. **Equipment nicht persistent:** Equipment existiert nur im Frontend-Typ und in Mock-Daten. Kein Prisma-Model, keine API-Routes, kein Backend-Service.

3. **Keine Reservierungslogik:** Wenn eine Buchung einen Raum braucht, wird das in `Booking.resourceAllocation` (JSON) gespeichert. Es gibt:
   - Keinen Conflict-Check (kann derselbe Raum zur selben Zeit doppelt vergeben werden? Ja.)
   - Keine Sperrung bei Planung
   - Keine Freigabe bei Storno

4. **Keine Kundenseitige Buchbarkeit:** `ServiceItem.customerSelectableResources` existiert als Frontend-Feld, aber es gibt keinen Backend-Endpunkt, der Ressourcen nach Verfügbarkeit filtert oder dem Kunden anzeigt.

5. **Keine Verknüpfung Service↔Resource:** Im Backend hat `Service` keine Relation zu `Location`, `Room` oder `Equipment`. Die Frontend-Felder `requiredLocations[]`, `requiredRooms[]`, `requiredEquipment[]` sind reine UI-Daten ohne Backend-Persistenz.

#### UI (`modules/Resources.tsx`)

- 3 Tabs: Locations, Rooms & Spaces, Equipment
- Reine CRUD-Ansicht: Karten/Tabelle, Status-Badge, Add/Edit/Delete
- **Kein Kalender, keine Zeitansicht, keine Belegungsübersicht**
- Kein Zusammenhang mit Buchungen oder Kursen sichtbar

---

### 1.3 Academy-Modul

#### Datenmodell

**Frontend (`types.ts:302-500, 758-823`):**

```
Course { id, title, description, instructor, category, visibility, tags[],
         curriculum: Topic[], quizzes: Quiz[], badges: Badge[],
         enrollmentCount, maxStudents, completionRate,
         bookingWindow: { start, end, startImmediately, closeOnStart } }

Topic { id, title, lessons: Lesson[] }

Lesson { id, title, type: 'Video'|'Text'|'Interactive'|'Live Session',
         content, duration, order }

Quiz { id, title, questions: Question[], passingScore, timeLimit, maxAttempts }

EducationCardTemplate { id, title, description, chapters: CardChapter[],
                        grading: GradingConfig, automation: AutomationRule, active }

CardChapter { id, title, items: EducationItem[] }

EducationItem { id, title, description?, media: EducationMedia[], originalLessonId? }
```

**Backend (`schema.prisma:432-572`):**

```
Course → Lesson[] (1:n), Quiz[] (1:n), Tag[] (m:n via CourseTag),
         CourseSession[] (1:n), Enrollment[] (1:n)

Lesson: title, content, type (VIDEO/TEXT/INTERACTIVE/LIVE), duration, order

Quiz: title, questions (JSON), passingScore, timeLimit, maxAttempts

EducationCard: title, description, content (JSON), triggerService,
               triggerCategory, triggerType → CustomerEducationCard[] (1:n)

CustomerEducationCard: customerId, cardId, progress (JSON), completed, assignedAt, completedAt
```

**Kritische Befunde:**

1. **EducationCard-Content ist JSON-Blob:** `content: Json` im Backend vs. strukturierte `chapters: CardChapter[]` im Frontend. Keine Abfragen auf Kapitel-/Item-Ebene möglich, keine referenzielle Integrität.

2. **Kein personalisierter Fortschritt pro Item:** `CustomerEducationCard.progress` ist ein JSON-Blob. Es gibt kein Model für:
   - Bewertung pro Lektion/Skill durch den Mitarbeiter (Fahrlehrer)
   - Notizen/Skizzen/Medien pro Schüler pro Lektion
   - Fortschrittsstatus pro einzelnem Item (nicht nur Gesamtfortschritt)

3. **Automation ohne Engine:** `AutomationRule` definiert Trigger (`triggerType: 'Service'|'Category'`, `triggerId`), aber:
   - Kein Event-Listener, der bei Booking-Erstellung die Zuweisung auslöst
   - Kein Zeitpunkt-Trigger ("bei erster Fahrstunde" nicht möglich, nur "bei Buchung")
   - `@@unique([customerId, cardId])` verhindert Mehrfachzuweisung — widerspricht `allowMultiple: boolean`

4. **Keine Verknüpfung Session↔Lesson:** `EventSession.linkedLessonId` existiert nur im Frontend-Typ, nicht im Backend `CourseSession`. Blended Learning (physische Session → Online-Lektion) ist nicht durchgängig.

5. **Curriculum nur auf Course-Ebene:** Die Topic/Lesson-Hierarchie ist nur im Frontend als verschachtelte Struktur. Im Backend sind Lessons flach an den Course gehängt (`courseId`), ohne Topic-Gruppierung.

6. **Quiz-Questions als JSON:** `Quiz.questions: Json` — keine eigenständigen Question-Records, keine Wiederverwendung von Fragen über Quizzes hinweg, keine Statistik pro Frage.

---

### 1.4 Customers-Modul

#### Datenmodell

**Frontend (`types.ts:122-141`):**
```typescript
Customer { id, firstName, lastName, email, phone, status,
           street?, zip?, city?, country?, birthday?, gender?,
           notes?, customFields[], earnedBadges[], bookings? (deprecated) }
```

**Backend (`schema.prisma:131-188`):**
```
Customer: firstName, lastName, email (unique per org), phone, dateOfBirth,
          gender, nationality, language, status, notes, customData (JSON),
          → Bookings[], Enrollments[], CustomerEducationCards[], Invoices[]
```

**Befunde:**

1. **Kein Kundenportal:** Es gibt kein `CustomerPanel`-Modul. Kein Self-Service für Buchungsverlauf, Ausbildungskarten-Einsicht, Onlinekurs-Zugang.

2. **Keine Kommunikationshistorie:** Kein Model für gesendete E-Mails, SMS, Benachrichtigungen. Kein Notification-Preferences-System.

3. **Custom Fields doppelt modelliert:** Frontend nutzt `customFields: CustomField[]` (key/value-Array), Backend nutzt `customData: Json`. Kein Schema für dynamische Felder, keine Abfragbarkeit.

4. **Badge-System oberflächlich:** `earnedBadges: string[]` im Frontend, `Badge`-Model im Backend — aber keine Logik, die Badges automatisch vergibt (z.B. bei Quiz-Bestehen oder Kursabschluss).

5. **Fehlende Felder für Schweizer Kontext:** Kein AHV-Nummer-Feld, keine Lernfahrausweis-Nummer, kein Führerausweis-Kategorie-Tracking (relevant für Fahrschulen).

---

### 1.5 Online-Mitarbeiterpanel

**Status: NICHT VORHANDEN**

Backend-Modelle existieren (`TimeEntry`, `Shift`, `Absence`, `Employee` mit `workloadPercentage`, `hourlyRate`), aber:
- Kein dediziertes Panel-Modul
- Kein Self-Service für Mitarbeiter (Verfügbarkeit pflegen, Schichten einsehen, Abwesenheit beantragen)
- Kein mobiler Zugang für Fahrlehrer (Ausbildungskarte im Unterricht nutzen)
- Kalender-Integration existiert (`CalendarIntegrationService.ts`) aber nur als Admin-Funktion

Der `Employees.tsx`-Modul ist eine reine Admin-Verwaltung (Liste, CRUD, Status-Änderung).

---

### 1.6 Online-Kundenpanel

**Status: NICHT VORHANDEN**

Kein Kundenportal. Kein Self-Service-Booking. Kein Onlinekurs-Player. Keine Ausbildungskarten-Ansicht. Keine Buchungshistorie.

Die gesamte Interaktion läuft über das Admin-Backend.

---

### 1.7 Zusammenfassung der Hauptprobleme

| # | Problem | Schwere | Betroffene Module |
|---|---------|---------|-------------------|
| 1 | Float für Geldbeträge statt Integer (Rappen) | Kritisch | Offers, Booking, Finance |
| 2 | Keine Slot-/Verfügbarkeits-Engine | Kritisch | Offers, Resources, Booking |
| 3 | resourceAllocation als JSON-Blob | Hoch | Booking, Resources |
| 4 | Kein Kundenportal | Hoch | Customers |
| 5 | Kein Mitarbeiterpanel | Hoch | Employees |
| 6 | Frontend/Backend-Typ-Divergenz | Hoch | Alle Module |
| 7 | Mega-Interface ServiceItem | Mittel | Offers |
| 8 | Education Card Content als JSON | Mittel | Academy |
| 9 | Keine Automation-Engine für Zuweisungen | Mittel | Academy, Offers |
| 10 | Keine Statusmaschinen | Mittel | Booking, Offers |
| 11 | Datum/Zeit als Strings | Mittel | Booking, Sessions |
| 12 | Equipment nicht persistent | Niedrig | Resources |

---

## 2. SOLL-Architektur (Gold Standard)

### 2.1 Domänenmodell-Übersicht

```
┌─────────────────────────────────────────────────────┐
│                    OFFERS DOMAIN                     │
│                                                      │
│  Offer (Basis)                                       │
│    ├── ServiceOffer (Einzeltermine)                  │
│    ├── EventOffer (fixe Termine, Gruppen)            │
│    └── OnlineCourseOffer (24/7, self-paced)          │
│                                                      │
│  OfferCategory, OfferTag, OfferExtra                 │
│  OfferResourceRequirement (n:m → Resource)           │
│  PricingRule, Bundle, Voucher                        │
└───────────┬─────────────────────────┬───────────────┘
            │                         │
            ▼                         ▼
┌───────────────────┐   ┌─────────────────────────────┐
│  SCHEDULING       │   │  ACADEMY DOMAIN              │
│                   │   │                              │
│  Schedule         │   │  AcademyCourse               │
│  Occurrence       │   │    └── Module/Topic          │
│  Session          │   │         └── Lesson           │
│  SlotRule         │   │              └── Attachment   │
│  CalendarBlock    │   │  Quiz                        │
│                   │   │    └── Question               │
└───────┬───────────┘   │  Badge                       │
        │               │                              │
        ▼               │  TrainingCardTemplate        │
┌───────────────────┐   │    └── Chapter               │
│  RESOURCE DOMAIN  │   │         └── Skill/Item       │
│                   │   │  TrainingCardAssignment       │
│  Resource (Basis) │   │    └── ItemProgress          │
│    ├── Location   │   │    └── Evaluation            │
│    ├── Room       │   │    └── PersonalNote          │
│    ├── Vehicle    │   │    └── MediaAttachment       │
│    └── Equipment  │   └──────────────┬──────────────┘
│                   │                  │
│  ResourceSchedule │                  │
│  ResourceReserv.  │                  │
│  AvailabilityRule │                  │
└───────┬───────────┘                  │
        │                              │
        ▼                              ▼
┌─────────────────────────────────────────────────────┐
│                  BOOKING DOMAIN                      │
│                                                      │
│  Booking (Statusmaschine)                            │
│    └── BookingParticipant                            │
│    └── BookingResourceReservation                    │
│    └── BookingExtra                                  │
│    └── BookingFormResponse                           │
│                                                      │
│  AutomationRule (Trigger → Action)                   │
│    "Bei Buchung von X → weise TrainingCard Y zu"     │
│    "Bei erster Session → aktiviere Onlinekurs Z"     │
└─────────────────────────────────────────────────────┘
```

### 2.2 Offer (Basis) + Subtypen

**Empfehlung: Single-Table mit `offerType`-Discriminator + typ-spezifische JSON-Config**

```sql
-- offers (Basis-Tabelle)
CREATE TABLE offers (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),

  -- Identifikation
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255),
  description     TEXT,
  offer_type      offer_type_enum NOT NULL, -- 'SERVICE', 'EVENT', 'ONLINE_COURSE'

  -- Kategorisierung
  category_id     UUID REFERENCES offer_categories(id),

  -- Pricing (Integer Minor Units!)
  price_cents     INTEGER NOT NULL,         -- z.B. 8500 = CHF 85.00
  currency        VARCHAR(3) DEFAULT 'CHF',
  sale_price_cents INTEGER,
  vat_rate        DECIMAL(5,2),             -- z.B. 8.10 für 8.1% MwSt
  pricing_rule_id UUID REFERENCES pricing_rules(id),

  -- Medien
  cover_image_url TEXT,
  gallery_urls    TEXT[],

  -- Status
  status          offer_status_enum DEFAULT 'DRAFT', -- DRAFT, ACTIVE, PAUSED, ARCHIVED
  visibility      visibility_enum DEFAULT 'PUBLIC',   -- PUBLIC, UNLISTED, PRIVATE

  -- Booking-Defaults
  default_booking_status  booking_status_enum DEFAULT 'CONFIRMED',
  form_template_id        UUID REFERENCES form_templates(id),

  -- Timestamps
  created_at      TIMESTAMPTZ DEFAULT NOW(),
  updated_at      TIMESTAMPTZ DEFAULT NOW(),
  published_at    TIMESTAMPTZ
);

CREATE TYPE offer_type_enum AS ENUM ('SERVICE', 'EVENT', 'ONLINE_COURSE');
CREATE TYPE offer_status_enum AS ENUM ('DRAFT', 'ACTIVE', 'PAUSED', 'ARCHIVED');
```

**Service-spezifische Config:**
```sql
-- offer_service_config (1:1 zu offers WHERE offer_type = 'SERVICE')
CREATE TABLE offer_service_configs (
  offer_id          UUID PRIMARY KEY REFERENCES offers(id),

  duration_minutes  INTEGER NOT NULL,
  buffer_before_min INTEGER DEFAULT 0,
  buffer_after_min  INTEGER DEFAULT 0,

  -- Slot-Regeln
  slot_interval_min INTEGER DEFAULT 30,       -- Alle 30 Min ein Slot
  booking_window_days_ahead INTEGER DEFAULT 30,
  min_notice_hours  INTEGER DEFAULT 24,
  cancel_notice_hours INTEGER DEFAULT 24,
  reschedule_notice_hours INTEGER DEFAULT 24,

  -- Kapazität
  max_participants  INTEGER DEFAULT 1,
  allow_group_booking BOOLEAN DEFAULT FALSE,
  max_group_size    INTEGER,

  -- Zuweisung
  assignment_strategy assignment_strategy_enum DEFAULT 'WORKLOAD_BALANCE',

  -- Wiederholung
  is_recurring      BOOLEAN DEFAULT FALSE,
  recurrence_rule   JSONB   -- iCal RRULE format
);
```

**Event-spezifische Config:**
```sql
-- offer_event_config (1:1 zu offers WHERE offer_type = 'EVENT')
CREATE TABLE offer_event_configs (
  offer_id            UUID PRIMARY KEY REFERENCES offers(id),

  event_structure     event_structure_enum NOT NULL, -- SINGLE, SERIES_ALL, SERIES_DROP_IN
  max_participants    INTEGER,
  min_participants    INTEGER,

  -- Booking-Fenster
  booking_opens_at    TIMESTAMPTZ,
  booking_closes_at   TIMESTAMPTZ,
  booking_opens_immediately BOOLEAN DEFAULT TRUE,
  booking_closes_on_start   BOOLEAN DEFAULT FALSE,

  -- Auto-Cancel
  auto_cancel_below_min BOOLEAN DEFAULT FALSE,
  auto_cancel_hours_before INTEGER DEFAULT 48,

  -- Waitlist
  waitlist_enabled    BOOLEAN DEFAULT FALSE,
  waitlist_capacity   INTEGER
);
```

**OnlineCourse-spezifische Config:**
```sql
-- offer_online_course_config (1:1 zu offers WHERE offer_type = 'ONLINE_COURSE')
CREATE TABLE offer_online_course_configs (
  offer_id              UUID PRIMARY KEY REFERENCES offers(id),

  academy_course_id     UUID REFERENCES academy_courses(id), -- Verknüpfung zu Lerninhalten
  max_participants      INTEGER,  -- NULL = unbegrenzt
  access_duration_days  INTEGER,  -- NULL = unbegrenzt

  -- Video-Integration
  integration_type      VARCHAR(50), -- 'ZOOM', 'GOOGLE_MEET', 'MS_TEAMS', NULL
  integration_config    JSONB
);
```

**Source of Truth:**
- `offers` + Config-Tabelle = **was** angeboten wird
- `sessions` = **wann** es stattfindet (→ Scheduling-Domain)
- `bookings` = **wer** gebucht hat (→ Booking-Domain)

### 2.3 Schedule / Occurrence / Session

```sql
-- sessions (Einzelne Durchführungen von Offers)
CREATE TABLE sessions (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  offer_id          UUID NOT NULL REFERENCES offers(id),

  -- Zeitpunkt
  starts_at         TIMESTAMPTZ NOT NULL,
  ends_at           TIMESTAMPTZ NOT NULL,

  -- Zuweisung
  instructor_id     UUID REFERENCES employees(id),

  -- Kapazität
  max_participants  INTEGER,
  current_enrollment INTEGER DEFAULT 0,

  -- Status
  status            session_status_enum DEFAULT 'SCHEDULED',
  -- SCHEDULED, CONFIRMED, IN_PROGRESS, COMPLETED, CANCELLED

  -- Verknüpfung Academy
  linked_lesson_id  UUID REFERENCES academy_lessons(id),

  -- Metadaten
  title             VARCHAR(255),  -- Override für Serientitel
  notes             TEXT,

  created_at        TIMESTAMPTZ DEFAULT NOW(),
  updated_at        TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX idx_sessions_offer_starts ON sessions(offer_id, starts_at);
CREATE INDEX idx_sessions_instructor_starts ON sessions(instructor_id, starts_at);
CREATE INDEX idx_sessions_status ON sessions(status) WHERE status != 'CANCELLED';
```

**Für Services:** Sessions werden on-demand generiert (aus SlotRules + Verfügbarkeit). Wenn ein Kunde bucht, wird eine Session erstellt.

**Für Events:** Sessions werden vom Admin/Planer manuell oder per Serie erstellt. Kunden buchen bestehende Sessions.

**Für Online-Kurse:** Keine Sessions nötig (self-paced). Optional: Live-Sessions für Webinare.

### 2.4 Resource + ResourceReservation

```sql
-- resources (Generische Ressourcen-Tabelle)
CREATE TABLE resources (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id   UUID NOT NULL REFERENCES organizations(id),

  resource_type     resource_type_enum NOT NULL, -- LOCATION, ROOM, VEHICLE, EQUIPMENT

  name              VARCHAR(255) NOT NULL,
  description       TEXT,

  -- Kapazität (für Räume: Sitzplätze, für Equipment: Stückzahl)
  capacity          INTEGER DEFAULT 1,

  -- Hierarchie (Room → Location)
  parent_id         UUID REFERENCES resources(id),

  -- Eigenschaften
  properties        JSONB DEFAULT '{}',  -- { features: [], condition: 'Good', address: '...' }

  -- Sichtbarkeit/Buchbarkeit
  visibility        resource_visibility_enum DEFAULT 'ADMIN_ONLY',
  -- ADMIN_ONLY, EMPLOYEE, CUSTOMER_VISIBLE, CUSTOMER_BOOKABLE

  status            resource_status_enum DEFAULT 'ACTIVE',
  -- ACTIVE, MAINTENANCE, RETIRED

  created_at        TIMESTAMPTZ DEFAULT NOW(),
  updated_at        TIMESTAMPTZ DEFAULT NOW()
);

CREATE TYPE resource_type_enum AS ENUM ('LOCATION', 'ROOM', 'VEHICLE', 'EQUIPMENT');
CREATE TYPE resource_visibility_enum AS ENUM ('ADMIN_ONLY', 'EMPLOYEE', 'CUSTOMER_VISIBLE', 'CUSTOMER_BOOKABLE');

-- offer_resource_requirements (n:m: Welche Ressourcen braucht ein Offer?)
CREATE TABLE offer_resource_requirements (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  offer_id      UUID NOT NULL REFERENCES offers(id),
  resource_id   UUID REFERENCES resources(id),       -- Spezifische Ressource ODER
  resource_type resource_type_enum,                   -- "irgendein Raum"

  is_required   BOOLEAN DEFAULT TRUE,                 -- Pflicht oder optional
  is_customer_selectable BOOLEAN DEFAULT FALSE,       -- Kunde darf wählen
  quantity      INTEGER DEFAULT 1,

  UNIQUE(offer_id, resource_id)
);

-- resource_reservations (Konkrete Sperren)
CREATE TABLE resource_reservations (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  resource_id   UUID NOT NULL REFERENCES resources(id),

  -- Zeitfenster
  starts_at     TIMESTAMPTZ NOT NULL,
  ends_at       TIMESTAMPTZ NOT NULL,

  -- Quelle der Reservierung
  booking_id    UUID REFERENCES bookings(id),
  session_id    UUID REFERENCES sessions(id),
  manual_reason TEXT,  -- Für manuelle Sperren

  -- Status
  status        reservation_status_enum DEFAULT 'CONFIRMED',
  -- TENTATIVE (Warenkorb/TTL), CONFIRMED, RELEASED

  -- TTL für tentative Reservierungen
  expires_at    TIMESTAMPTZ,

  created_at    TIMESTAMPTZ DEFAULT NOW(),

  -- Constraint: Keine Überlappungen für dieselbe Ressource
  EXCLUDE USING gist (
    resource_id WITH =,
    tstzrange(starts_at, ends_at) WITH &&
  ) WHERE (status != 'RELEASED')
);

CREATE INDEX idx_reservations_resource_time ON resource_reservations(resource_id, starts_at, ends_at);
CREATE INDEX idx_reservations_booking ON resource_reservations(booking_id);
```

**Erklärung `EXCLUDE USING gist`:** PostgreSQL Exclusion Constraint — verhindert auf DB-Ebene, dass zwei aktive Reservierungen derselben Ressource sich zeitlich überlappen. Das ist die sicherste Art, Doppelbuchungen zu verhindern (kein Race Condition möglich).

### 2.5 Employee Availability

```sql
-- employee_availability_rules (Regelmässige Verfügbarkeit)
CREATE TABLE employee_availability_rules (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  employee_id   UUID NOT NULL REFERENCES employees(id),

  -- Wochentag-basiert
  day_of_week   INTEGER NOT NULL CHECK (day_of_week BETWEEN 0 AND 6), -- 0=Mo
  start_time    TIME NOT NULL,
  end_time      TIME NOT NULL,

  -- Gültigkeitszeitraum
  valid_from    DATE NOT NULL DEFAULT CURRENT_DATE,
  valid_until   DATE,

  UNIQUE(employee_id, day_of_week, valid_from)
);

-- employee_calendar_blocks (Einmalige Sperren/Abwesenheiten)
CREATE TABLE employee_calendar_blocks (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  employee_id   UUID NOT NULL REFERENCES employees(id),

  starts_at     TIMESTAMPTZ NOT NULL,
  ends_at       TIMESTAMPTZ NOT NULL,

  block_type    calendar_block_type_enum NOT NULL,
  -- ABSENCE, VACATION, SICK, PERSONAL, EXTERNAL_BUSY, MANUAL_BLOCK

  reason        TEXT,
  status        absence_status_enum DEFAULT 'APPROVED',
  -- PENDING, APPROVED, REJECTED

  -- Sync mit externem Kalender
  external_calendar_id UUID REFERENCES calendar_connections(id),
  external_event_id    VARCHAR(255),

  created_at    TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.6 Booking (Statusmaschine)

```sql
-- bookings
CREATE TABLE bookings (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_number    VARCHAR(20) NOT NULL UNIQUE,
  organization_id   UUID NOT NULL REFERENCES organizations(id),

  -- Verknüpfungen
  offer_id          UUID NOT NULL REFERENCES offers(id),
  session_id        UUID REFERENCES sessions(id),
  customer_id       UUID NOT NULL REFERENCES customers(id),
  employee_id       UUID REFERENCES employees(id),

  -- Zeitpunkt (redundant zu Session, für direkte Abfragen)
  scheduled_at      TIMESTAMPTZ NOT NULL,
  duration_minutes  INTEGER NOT NULL,

  -- Teilnehmer
  participant_count INTEGER DEFAULT 1,

  -- Pricing (Integer Minor Units!)
  base_price_cents  INTEGER NOT NULL,
  extras_total_cents INTEGER DEFAULT 0,
  discount_cents    INTEGER DEFAULT 0,
  total_price_cents INTEGER NOT NULL,
  currency          VARCHAR(3) DEFAULT 'CHF',

  -- Status
  status            booking_status_enum NOT NULL DEFAULT 'PENDING',
  payment_status    payment_status_enum NOT NULL DEFAULT 'PENDING',

  -- Storno
  cancelled_at      TIMESTAMPTZ,
  cancel_reason     TEXT,

  -- Formular-Antworten
  form_responses    JSONB DEFAULT '{}',

  -- Timestamps
  created_at        TIMESTAMPTZ DEFAULT NOW(),
  confirmed_at      TIMESTAMPTZ,
  paid_at           TIMESTAMPTZ,
  completed_at      TIMESTAMPTZ,
  updated_at        TIMESTAMPTZ DEFAULT NOW()
);
```

**Statusmaschine — erlaubte Übergänge:**

```
                    ┌──────────┐
           ┌───────│ PENDING  │───────┐
           │       └──────────┘       │
           ▼                          ▼
    ┌────────────┐             ┌────────────┐
    │ CONFIRMED  │             │ CANCELLED  │
    └──────┬─────┘             └────────────┘
           │                          ▲
           ▼                          │
    ┌────────────┐                    │
    │    PAID    │────────────────────┘
    └──────┬─────┘
           │
           ▼
    ┌────────────┐     ┌────────────┐
    │ COMPLETED  │     │  NO_SHOW   │
    └────────────┘     └────────────┘
```

Erlaubte Übergänge (als Code-Konstante):
```typescript
const ALLOWED_TRANSITIONS: Record<BookingStatus, BookingStatus[]> = {
  PENDING:   ['CONFIRMED', 'CANCELLED'],
  CONFIRMED: ['PAID', 'CANCELLED'],
  PAID:      ['COMPLETED', 'NO_SHOW', 'CANCELLED'],
  COMPLETED: [],  // Final
  NO_SHOW:   [],  // Final
  CANCELLED: [],  // Final
};
```

### 2.7 Academy-Domäne

```sql
-- academy_courses (Lerninhalt, NICHT das buchbare Angebot)
CREATE TABLE academy_courses (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id   UUID NOT NULL REFERENCES organizations(id),

  title             VARCHAR(255) NOT NULL,
  description       TEXT,
  cover_image_url   TEXT,

  status            course_status_enum DEFAULT 'DRAFT', -- DRAFT, PUBLISHED, ARCHIVED

  created_at        TIMESTAMPTZ DEFAULT NOW(),
  updated_at        TIMESTAMPTZ DEFAULT NOW()
);

-- academy_modules (Gruppierung von Lessons = "Topics")
CREATE TABLE academy_modules (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  course_id       UUID NOT NULL REFERENCES academy_courses(id) ON DELETE CASCADE,
  title           VARCHAR(255) NOT NULL,
  sort_order      INTEGER NOT NULL DEFAULT 0
);

-- academy_lessons
CREATE TABLE academy_lessons (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  module_id       UUID NOT NULL REFERENCES academy_modules(id) ON DELETE CASCADE,

  title           VARCHAR(255) NOT NULL,
  lesson_type     lesson_type_enum NOT NULL, -- VIDEO, TEXT, INTERACTIVE, LIVE_SESSION
  content         TEXT,           -- Markdown/HTML Content
  video_url       TEXT,
  duration_minutes INTEGER,
  sort_order      INTEGER NOT NULL DEFAULT 0
);

-- academy_lesson_attachments
CREATE TABLE academy_lesson_attachments (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  lesson_id     UUID NOT NULL REFERENCES academy_lessons(id) ON DELETE CASCADE,

  file_type     attachment_type_enum NOT NULL, -- IMAGE, VIDEO, DOCUMENT, LINK
  url           TEXT NOT NULL,
  name          VARCHAR(255),
  description   TEXT,
  sort_order    INTEGER DEFAULT 0
);

-- academy_quizzes
CREATE TABLE academy_quizzes (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  course_id       UUID NOT NULL REFERENCES academy_courses(id) ON DELETE CASCADE,

  title           VARCHAR(255) NOT NULL,
  passing_score   INTEGER NOT NULL DEFAULT 70,  -- Prozent
  time_limit_min  INTEGER,
  max_attempts    INTEGER DEFAULT 3,
  sort_order      INTEGER DEFAULT 0
);

-- academy_quiz_questions (eigenständig, wiederverwendbar)
CREATE TABLE academy_quiz_questions (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  quiz_id       UUID NOT NULL REFERENCES academy_quizzes(id) ON DELETE CASCADE,

  question_text TEXT NOT NULL,
  question_type question_type_enum NOT NULL, -- SINGLE_CHOICE, MULTIPLE_CHOICE, TRUE_FALSE, FREE_TEXT
  options       JSONB,          -- [{ text, isCorrect }]
  explanation   TEXT,           -- Erklärung nach Antwort
  points        INTEGER DEFAULT 1,
  sort_order    INTEGER DEFAULT 0
);
```

### 2.8 Training Card (Ausbildungskarte)

```sql
-- training_card_templates (vom Admin erstellt)
CREATE TABLE training_card_templates (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),

  title           VARCHAR(255) NOT NULL,
  description     TEXT,

  -- Bewertungskonfiguration
  grading_type    grading_type_enum DEFAULT 'SLIDER', -- SLIDER, BUTTONS, STARS
  grading_min     INTEGER DEFAULT 1,
  grading_max     INTEGER DEFAULT 5,
  grading_label_min VARCHAR(50),  -- z.B. "Anfänger"
  grading_label_max VARCHAR(50),  -- z.B. "Experte"

  status          template_status_enum DEFAULT 'ACTIVE', -- ACTIVE, ARCHIVED

  created_at      TIMESTAMPTZ DEFAULT NOW(),
  updated_at      TIMESTAMPTZ DEFAULT NOW()
);

-- training_card_chapters
CREATE TABLE training_card_chapters (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  template_id     UUID NOT NULL REFERENCES training_card_templates(id) ON DELETE CASCADE,
  title           VARCHAR(255) NOT NULL,
  sort_order      INTEGER NOT NULL DEFAULT 0
);

-- training_card_items (Skills/Lektionen innerhalb eines Kapitels)
CREATE TABLE training_card_items (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  chapter_id      UUID NOT NULL REFERENCES training_card_chapters(id) ON DELETE CASCADE,

  title           VARCHAR(255) NOT NULL,
  description     TEXT,

  -- Verknüpfung mit Academy-Lektion (optional)
  linked_lesson_id UUID REFERENCES academy_lessons(id),

  sort_order      INTEGER NOT NULL DEFAULT 0
);

-- training_card_item_media (Medien auf Template-Ebene)
CREATE TABLE training_card_item_media (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  item_id     UUID NOT NULL REFERENCES training_card_items(id) ON DELETE CASCADE,

  media_type  media_type_enum NOT NULL, -- IMAGE, VIDEO
  url         TEXT NOT NULL,
  label       VARCHAR(255),
  sort_order  INTEGER DEFAULT 0
);

-- training_card_assignments (Zuweisung an Kunden)
CREATE TABLE training_card_assignments (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  template_id     UUID NOT NULL REFERENCES training_card_templates(id),
  customer_id     UUID NOT NULL REFERENCES customers(id),

  -- Quelle der Zuweisung
  assigned_by     assignment_source_enum NOT NULL, -- AUTOMATION, MANUAL
  assigned_by_employee_id UUID REFERENCES employees(id),
  trigger_booking_id      UUID REFERENCES bookings(id),

  -- Status
  status          assignment_status_enum DEFAULT 'ACTIVE', -- ACTIVE, COMPLETED, CANCELLED
  completed_at    TIMESTAMPTZ,

  created_at      TIMESTAMPTZ DEFAULT NOW(),
  updated_at      TIMESTAMPTZ DEFAULT NOW()

  -- KEIN UNIQUE(customer_id, template_id) — Mehrfachzuweisung erlaubt!
);

-- training_card_item_progress (Bewertung pro Item pro Assignment)
CREATE TABLE training_card_item_progress (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  assignment_id   UUID NOT NULL REFERENCES training_card_assignments(id) ON DELETE CASCADE,
  item_id         UUID NOT NULL REFERENCES training_card_items(id),

  -- Bewertung durch Mitarbeiter
  grade           INTEGER,          -- Wert gemäss GradingConfig (z.B. 1-5)
  evaluated_by    UUID REFERENCES employees(id),
  evaluated_at    TIMESTAMPTZ,

  -- Status
  status          item_progress_enum DEFAULT 'NOT_STARTED',
  -- NOT_STARTED, IN_PROGRESS, COMPLETED, SKIPPED

  UNIQUE(assignment_id, item_id)
);

-- training_card_notes (Persönliche Notizen/Medien pro Assignment pro Item)
CREATE TABLE training_card_notes (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  assignment_id   UUID NOT NULL REFERENCES training_card_assignments(id) ON DELETE CASCADE,
  item_id         UUID REFERENCES training_card_items(id), -- NULL = Note zum ganzen Assignment

  -- Inhalt
  note_type       note_type_enum NOT NULL, -- TEXT, IMAGE, SKETCH, VIDEO, DOCUMENT
  content         TEXT,           -- Text oder URL

  -- Autor
  created_by      UUID NOT NULL REFERENCES employees(id),

  -- Sichtbarkeit
  visible_to_customer BOOLEAN DEFAULT TRUE,

  created_at      TIMESTAMPTZ DEFAULT NOW()
);
```

### 2.9 Automation Rules

```sql
-- automation_rules (Trigger → Action)
CREATE TABLE automation_rules (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  organization_id UUID NOT NULL REFERENCES organizations(id),

  name            VARCHAR(255) NOT NULL,
  description     TEXT,
  active          BOOLEAN DEFAULT TRUE,

  -- Trigger
  trigger_event   trigger_event_enum NOT NULL,
  -- BOOKING_CREATED, BOOKING_CONFIRMED, BOOKING_PAID,
  -- FIRST_SESSION_OF_TYPE, SESSION_COMPLETED, COURSE_COMPLETED

  -- Trigger-Bedingung
  trigger_offer_id    UUID REFERENCES offers(id),       -- Spezifisches Angebot
  trigger_category_id UUID REFERENCES offer_categories(id), -- Oder Kategorie
  trigger_condition   JSONB,  -- Erweiterte Bedingungen, z.B. { "sessionNumber": 1 }

  -- Action
  action_type     action_type_enum NOT NULL,
  -- ASSIGN_TRAINING_CARD, ASSIGN_ONLINE_COURSE, GRANT_BADGE,
  -- SEND_NOTIFICATION, ENROLL_IN_COURSE

  action_config   JSONB NOT NULL,
  -- z.B. { "trainingCardTemplateId": "..." }
  -- z.B. { "onlineCourseOfferId": "...", "accessDurationDays": 90 }

  -- Optionen
  allow_duplicate BOOLEAN DEFAULT FALSE,  -- Mehrfach-Ausführung pro Kunde

  priority        INTEGER DEFAULT 0,

  created_at      TIMESTAMPTZ DEFAULT NOW(),
  updated_at      TIMESTAMPTZ DEFAULT NOW()
);
```

**Beispiel Use-Case Motorradfahrschüler:**

| Regel | Trigger | Bedingung | Action |
|-------|---------|-----------|--------|
| VKU → Onlinekurs | BOOKING_CONFIRMED | offer = "VKU Motorrad" | ASSIGN_ONLINE_COURSE (Theorie Online) |
| Grundkurs → Onlinekurs | BOOKING_CONFIRMED | category = "Grundkurs" | ASSIGN_ONLINE_COURSE (Grundkurs Online) |
| Erste Fahrstunde → Karte | FIRST_SESSION_OF_TYPE | category = "Fahrstunde" | ASSIGN_TRAINING_CARD (Motorrad-Ausbildungskarte) |

---

## 3. Workflows pro Persona

### 3.1 Admin: Angebot erstellen

**Happy Path:**

```
1. Admin → Offers → "Neues Angebot"
2. Typ wählen: [Dienstleistung] [Kurs/Event] [Onlinekurs]
   → Typ-spezifisches Formular öffnet sich (NICHT ein Mega-Formular)
3. Basis-Daten: Titel, Beschreibung, Kategorie, Tags, Bild
4. Pricing: Preis (CHF, Rappen-genau), MwSt, optionaler Aktionspreis, Dynamic Pricing
5. Typ-spezifisch:
   - Dienstleistung: Dauer, Puffer, Slot-Intervall, Buchungsfenster, Zuweisung
   - Kurs/Event: Struktur (Einzel/Serie), Sessions anlegen, Booking-Window
   - Onlinekurs: Academy-Kurs verknüpfen, Zugangsdauer, Kapazität
6. Ressourcen: Pflicht-Ressourcen zuweisen, optionale markieren,
   "Kundenseitig buchbar" togglen
7. Extras: Zubuchbare Optionen mit Preis
8. Buchungsformular: Template zuweisen
9. Automationen: "Bei Buchung dieses Angebots → weise X zu"
   → Bestehende Automation-Rules verknüpfen oder neue erstellen
10. Vorschau → Speichern als Entwurf → Aktivieren
```

**Zentrale UI-Komponenten:**
- `OfferTypeSelector` — 3 Karten, nach Auswahl typ-spezifischer Wizard
- `OfferFormService` / `OfferFormEvent` / `OfferFormOnlineCourse` — getrennte Formulare
- `ResourceRequirementEditor` — Ressourcen hinzufügen mit required/optional/customer-selectable
- `AutomationRuleLinker` — bestehende Regeln verknüpfen, Quick-Create
- `OfferPreview` — So sieht es für den Kunden aus

### 3.2 Mitarbeiter/Kursplaner: Kurs planen

**Happy Path:**

```
1. Planer → Offers → Event auswählen → "Sessions verwalten"
2. Kalenderansicht mit bestehenden Sessions
3. "Neue Session" → Datum/Zeit wählen
4. System prüft automatisch:
   - Instruktor-Verfügbarkeit (Kalender, Abwesenheiten, andere Sessions)
   - Raum-Verfügbarkeit (bestehende Reservierungen)
   → Konflikte werden SOFORT angezeigt (rote Markierung + Alternative)
5. Instruktor zuweisen (Dropdown zeigt nur verfügbare)
6. Raum zuweisen (Dropdown zeigt nur verfügbare, mit Kapazität)
7. Optional: Academy-Lektion verknüpfen
8. Speichern → Ressourcen-Reservierungen werden automatisch erstellt
9. Bei Änderung: System prüft, ob neue Zeit/Raum verfügbar,
   warnt bei bestehenden Buchungen → Bestätigung erforderlich
```

**Zentrale UI-Komponenten:**
- `SessionCalendar` — Kalenderansicht aller Sessions eines Events
- `SessionEditor` — Formular mit Live-Verfügbarkeitsprüfung
- `ConflictResolver` — Zeigt Konflikte und Alternativen
- `ResourcePicker` — Gefiltert nach Verfügbarkeit im gewählten Zeitfenster

### 3.3 Mitarbeiter/Fahrlehrer: Ausbildungskarte nutzen

**Happy Path:**

```
1. Fahrlehrer öffnet Mitarbeiterpanel (mobil-optimiert)
2. Sieht heutige Termine → wählt nächsten Schüler
3. Ausbildungskarte des Schülers öffnet sich
4. Kapitel/Items sind sichtbar mit aktuellem Fortschritt
5. Während/nach der Lektion:
   a. Item auswählen → Bewertung setzen (Slider 1-5)
   b. Notiz hinzufügen (Text, Foto, Skizze)
   c. "Für Schüler sichtbar" toggle
6. Speichern → Schüler sieht Update sofort im Kundenportal
7. Optional: Medien aus Academy-Lektion zeigen (verknüpfte Inhalte)
```

**Zentrale UI-Komponenten:**
- `EmployeeDashboard` — Tagesansicht mit Terminen
- `TrainingCardView` — Kapitel-Akkordeon mit Items
- `ItemEvaluator` — Slider/Buttons für Bewertung
- `NoteEditor` — Text + Foto + Skizze (Canvas) + Sichtbarkeits-Toggle
- `MediaViewer` — Verknüpfte Academy-Inhalte inline anzeigen

### 3.4 Kunde: Buchen + Ausbildungskarte verfolgen

**Happy Path Buchung:**

```
1. Kunde → Website/Kundenportal → Angebote durchsuchen
2. Filter: Kategorie, Datum, Ressource ("Motorrad Honda CB500")
3. Angebotskarte → Details ansehen
4. Dienstleistung: Verfügbare Slots sehen (Kalender) → Slot wählen
   Kurs/Event: Verfügbare Sessions sehen → Session wählen
   Onlinekurs: Direkt buchen
5. Optional: Ressource wählen (wenn customer_bookable)
6. Optional: Extras hinzufügen
7. Buchungsformular ausfüllen
8. Zusammenfassung → Bezahlen → Bestätigung
9. Automatische Zuweisungen laufen (Onlinekurs, Ausbildungskarte)
```

**Happy Path Ausbildungskarte:**

```
1. Kunde → Kundenportal → "Meine Ausbildung"
2. Aktive Ausbildungskarten sehen (z.B. "Motorrad-Ausbildung")
3. Karte öffnen → Kapitel mit Fortschrittsbalken
4. Item aufklappen:
   - Bewertung des Fahrlehrers sehen (z.B. 3/5 Sterne)
   - Persönliche Notizen/Tipps des Fahrlehrers lesen
   - Medien ansehen (Fotos, Skizzen aus dem Unterricht)
   - Verknüpfte Academy-Lektion: "Jetzt online vertiefen" → Link
5. Gesamtfortschritt sichtbar (X von Y Items abgeschlossen)
```

**Zentrale UI-Komponenten:**
- `OfferBrowser` — Filterbarer Katalog
- `SlotPicker` — Kalender mit verfügbaren Slots
- `SessionPicker` — Liste/Kalender mit verfügbaren Event-Sessions
- `ResourceSelector` — Customer-selectable Ressourcen
- `BookingCheckout` — Zusammenfassung, Extras, Formular, Zahlung
- `CustomerTrainingCard` — Read-Only-Ansicht der Ausbildungskarte
- `LessonLink` — Deep-Link zu verknüpfter Academy-Lektion

---

## 4. Ressourcen- und Verfügbarkeitslogik

### 4.1 Slot-Berechnung für Dienstleistungen

**Algorithmus `getAvailableSlots(offerId, date)`:**

```
Input: offerId, date
Output: AvailableSlot[] { startTime, endTime, employeeId, resources[] }

1. Lade Offer + ServiceConfig (duration, buffer_before, buffer_after, slot_interval)
2. Lade OfferResourceRequirements (required resources)
3. Lade alle zugewiesenen Mitarbeiter für dieses Offer

4. Für jeden Mitarbeiter:
   a. Lade AvailabilityRules für diesen Wochentag
      → Ergibt Zeitfenster, z.B. [08:00-12:00, 13:00-17:00]
   b. Lade CalendarBlocks für dieses Datum (Abwesenheit, externe Kalender)
      → Subtrahiere von verfügbaren Fenstern
   c. Lade bestehende Bookings für dieses Datum
      → Subtrahiere (inkl. Puffer!) von verfügbaren Fenstern
   d. Generiere Slots im Intervall (z.B. alle 30 Min)
      → Nur wo duration + buffer_before + buffer_after reinpasst

5. Für jeden generierten Slot:
   a. Prüfe required Resources:
      → Lade ResourceReservations für Zeitfenster
      → Ist Resource frei? Wenn ja, markiere als verfügbar
   b. Wenn alle required Resources frei → Slot ist buchbar

6. Return: Alle buchbaren Slots mit zugeordnetem Mitarbeiter + Ressourcen
```

**Performance-Optimierung:**
- Alle Queries für einen Tag in einem Batch laden (nicht pro Slot)
- Index auf `resource_reservations(resource_id, starts_at, ends_at)`
- Index auf `bookings(employee_id, scheduled_at)`
- Ergebnis kann für 5 Minuten gecacht werden (Cache invalidieren bei neuer Buchung)

### 4.2 Verfügbarkeitsprüfung bei Events

```
Input: sessionId
Output: { available: boolean, spotsLeft: number, waitlistAvailable: boolean }

1. Lade Session (max_participants, current_enrollment, status)
2. Lade aktive Bookings für diese Session (status IN [PENDING, CONFIRMED, PAID])
3. spotsLeft = max_participants - COUNT(aktive Bookings)
4. available = spotsLeft > 0 AND session.status = 'SCHEDULED'
5. waitlistAvailable = !available AND offer.waitlist_enabled AND waitlistCount < waitlist_capacity
```

### 4.3 Transaktionssicherheit

**Problem:** Zwei Kunden buchen gleichzeitig den letzten Slot.

**Lösung: Optimistic Locking + DB-Constraints**

```
1. TENTATIVE Reservation (TTL 10 Minuten):
   - Beim Start des Buchungsprozesses:
     INSERT resource_reservations (status='TENTATIVE', expires_at=NOW()+10min)
   - EXCLUDE-Constraint verhindert Doppelbuchung auf DB-Ebene
   - Wenn INSERT fehlschlägt → Slot bereits vergeben → User informieren

2. CONFIRM bei Buchungsabschluss:
   BEGIN TRANSACTION ISOLATION LEVEL SERIALIZABLE;
     UPDATE resource_reservations SET status='CONFIRMED', expires_at=NULL
       WHERE booking_id = $1 AND status = 'TENTATIVE';
     UPDATE sessions SET current_enrollment = current_enrollment + 1
       WHERE id = $1 AND current_enrollment < max_participants;
     -- Wenn UPDATE 0 Rows → Rollback → "Platz vergeben"
   COMMIT;

3. Cleanup-Job (alle 5 Minuten):
   DELETE FROM resource_reservations
     WHERE status = 'TENTATIVE' AND expires_at < NOW();
```

**Für Umbuchungen:**
```
BEGIN TRANSACTION;
  -- Neue Reservierung erstellen (TENTATIVE)
  -- Alte Reservierung freigeben (RELEASED)
  -- Neue bestätigen (CONFIRMED)
COMMIT;
-- Bei Fehler: Alte bleibt bestehen, neue wird nicht erstellt
```

### 4.4 Filter in der Kundensuche

**"Zeige nur Angebote, bei denen Ressource X verfügbar ist"**

```sql
-- Beispiel: Alle Fahrstunden-Slots am 2026-03-15,
-- bei denen "Honda CB500" (resource_id = '...') frei ist

SELECT s.starts_at, s.ends_at, s.instructor_id
FROM generate_slots('offer_id', '2026-03-15') s  -- Funktion oder Materialized View
WHERE NOT EXISTS (
  SELECT 1 FROM resource_reservations rr
  WHERE rr.resource_id = 'honda-cb500-id'
    AND rr.status != 'RELEASED'
    AND tstzrange(rr.starts_at, rr.ends_at) && tstzrange(s.starts_at, s.ends_at)
);
```

**Index-Strategie:**
```sql
CREATE INDEX idx_res_reserv_lookup
  ON resource_reservations (resource_id, starts_at, ends_at)
  WHERE status != 'RELEASED';

CREATE INDEX idx_bookings_employee_date
  ON bookings (employee_id, scheduled_at)
  WHERE status NOT IN ('CANCELLED');

CREATE INDEX idx_sessions_offer_date
  ON sessions (offer_id, starts_at)
  WHERE status != 'CANCELLED';
```

---

## 5. Integrationspunkte Offers ↔ Academy

### 5.1 Verknüpfungsmodell

```
Offer (Onlinekurs) ──1:1──→ AcademyCourse
    "Dieses Angebot verkauft Zugang zu diesem Kursinhalt"

Offer (Event) ──Session──→ AcademyLesson (optional, pro Session)
    "Diese Session behandelt diese Lektion"

Offer (beliebig) ──AutomationRule──→ TrainingCardTemplate
    "Bei Buchung/Session dieses Angebots → Karte zuweisen"

Offer (beliebig) ──AutomationRule──→ Offer (Onlinekurs)
    "Bei Buchung dieses Angebots → Zugang zu Onlinekurs freischalten"

Offer (Bundle) ──BundleItem──→ Offer[] (mehrere)
    "Dieses Paket enthält diese Angebote"
```

### 5.2 Automations-Engine

**Event-basiert (Domain Events):**

```typescript
// Events, die das Booking-Modul emittiert:
BookingCreated    { bookingId, offerId, customerId, sessionId? }
BookingConfirmed  { bookingId, offerId, customerId }
BookingPaid       { bookingId, offerId, customerId }
SessionCompleted  { sessionId, offerId, customerId, employeeId, sessionNumber }
BookingCancelled  { bookingId, offerId, customerId }

// Automation-Listener:
onDomainEvent(event) {
  1. Lade alle aktiven AutomationRules für diese Organization
  2. Filtere: trigger_event matches AND (trigger_offer_id OR trigger_category_id matches)
  3. Prüfe Bedingungen (z.B. sessionNumber = 1 für "erste Fahrstunde")
  4. Prüfe allow_duplicate (hat Kunde schon eine aktive Zuweisung?)
  5. Führe Action aus:
     - ASSIGN_TRAINING_CARD → INSERT training_card_assignments
     - ASSIGN_ONLINE_COURSE → INSERT customer_course_enrollments
     - GRANT_BADGE → INSERT customer_badges
     - SEND_NOTIFICATION → Queue notification
}
```

### 5.3 Trigger-Typen im Detail

| Trigger | Wann | Use-Case |
|---------|------|----------|
| `BOOKING_CREATED` | Sofort bei Buchung | Onlinekurs-Zugang sofort freischalten |
| `BOOKING_CONFIRMED` | Nach Admin-Bestätigung | Onlinekurs erst nach Bestätigung |
| `BOOKING_PAID` | Nach Zahlung | Zugang erst nach Bezahlung |
| `FIRST_SESSION_OF_TYPE` | Erste durchgeführte Session einer Kategorie | Ausbildungskarte bei erster Fahrstunde |
| `SESSION_COMPLETED` | Jede abgeschlossene Session | Badge nach jeder Lektion |
| `COURSE_COMPLETED` | Alle Sessions + Quiz bestanden | Zertifikat, Abschluss-Badge |

### 5.4 Status-Konsistenz

**Regel:** Jede Zuweisung (Onlinekurs, Ausbildungskarte) referenziert die auslösende Buchung. Wird die Buchung storniert:

```
BOOKING_CANCELLED →
  Prüfe: Gibt es Zuweisungen mit trigger_booking_id = bookingId?
  → Onlinekurs: Zugang entziehen (status = 'REVOKED')
  → Ausbildungskarte: Status = 'CANCELLED' (wenn noch keine Bewertungen)
  → Ausbildungskarte: Warnung an Admin (wenn bereits Bewertungen existieren)
  → Badge: Bleibt bestehen (einmal verdient = behalten)
```

**Statusanzeige pro Persona:**

| Entity | Admin sieht | Mitarbeiter sieht | Kunde sieht |
|--------|------------|-------------------|-------------|
| Booking | Alle Status + History | Eigene Termine + Status | Eigene Buchungen + Status |
| Onlinekurs | Alle Enrollments + Fortschritt | Eigene Schüler | Eigener Fortschritt + Inhalte |
| Ausbildungskarte | Alle Assignments + Bewertungen | Eigene Schüler, Bewertung editierbar | Eigener Fortschritt + Notizen (read-only) |
| Training Card Notes | Alle | Eigene erstellen/bearbeiten | visible_to_customer = true |

---

## 6. Konkreter Umsetzungsplan

### 6.1 Quick Wins (1–2 Tage je)

| # | Aufgabe | Akzeptanzkriterien | Dateien |
|---|---------|-------------------|---------|
| Q1 | **Geldbeträge auf Integer umstellen** | Alle `Float`-Felder für Preise → `Int` (Rappen/Cents). price_cents statt price. Migration: `UPDATE SET price_cents = ROUND(price * 100)` | `schema.prisma`, alle Services, Frontend-Typen |
| Q2 | **Booking-Statusmaschine** | `ALLOWED_TRANSITIONS`-Map implementieren. Jeder Statuswechsel wird validiert. Ungültige Übergänge → Error. | `BookingService.ts`, neuer `BookingStateMachine.ts` |
| Q3 | **Frontend/Backend Typen synchronisieren** | Ein einziges shared Types-Package. `title`→`title` überall, `capacity`→`maxParticipants` überall. | `types.ts`, `types-api.ts`, alle Komponenten |
| Q4 | **DateTime statt String für Termine** | `scheduledDate` + `scheduledTime` → `scheduledAt: DateTime`. Zeitzonen-korrekt. | `schema.prisma`, `BookingService.ts` |

### 6.2 Mittelfristig (1–2 Wochen je)

| # | Aufgabe | Akzeptanzkriterien |
|---|---------|-------------------|
| M1 | **Offer-Subtyp-Trennung** | `offers`-Tabelle + `offer_service_configs` / `offer_event_configs` / `offer_online_course_configs`. Typ-spezifische Formulare im Frontend. Mega-Interface aufgelöst. |
| M2 | **Resource-Domain aufbauen** | `resources`-Tabelle (generisch), `offer_resource_requirements`, `resource_reservations` mit EXCLUDE-Constraint. Equipment im Backend persistent. |
| M3 | **Session-Modell vereinheitlichen** | Ein `sessions`-Modell für alle Offer-Typen. Status-Lifecycle. Verknüpfung mit Instructor + Resources + Academy Lesson. |
| M4 | **Verfügbarkeits-Engine** | `getAvailableSlots(offerId, date)` Algorithmus implementiert. Berücksichtigt: Employee Rules, CalendarBlocks, bestehende Bookings, Resource Reservations, Puffer. |
| M5 | **Training Card Backend** | Alle Tabellen gem. 2.8. CRUD-Endpoints. Progress-Tracking pro Item. Notes mit Medien-Upload. |
| M6 | **Automation-Engine** | Domain Events emittieren. AutomationRule-Tabelle. Event-Listener mit Regelauswertung. Actions: ASSIGN_TRAINING_CARD, ASSIGN_ONLINE_COURSE, GRANT_BADGE. |

### 6.3 Langfristig (sauberer Umbau)

| # | Aufgabe | Akzeptanzkriterien |
|---|---------|-------------------|
| L1 | **Kundenportal** | Eigene Vue-App. Angebots-Browser mit Filtern. Slot-/Session-Picker. Buchungsflow mit Checkout. Meine Buchungen, Meine Kurse, Meine Ausbildungskarten. Responsive/Mobile-first. |
| L2 | **Mitarbeiterpanel** | Eigene Vue-App. Tages-/Wochenansicht. Ausbildungskarte live bearbeiten (Bewertung, Notizen, Skizzen). Verfügbarkeit self-service pflegen. Mobile-optimiert für Unterricht. |
| L3 | **Slot-Picker mit Ressourcen-Filter** | Kunde kann Ressource wählen (z.B. Motorrad), System zeigt nur passende Slots. Live-Verfügbarkeitsprüfung. Tentative Reservation bei Slot-Auswahl. |
| L4 | **Tentative Reservations + TTL** | Optimistic Locking im Buchungsprozess. 10-Min-TTL. Cleanup-Job. Conflict-Resolution bei Ablauf. |
| L5 | **Kalender-Integration erweitern** | CalendarBlocks aus externen Kalendern automatisch synchronisieren (Cron). Bidirektionale Sync: Bookando-Sessions → Google/Outlook. |
| L6 | **Quiz-Engine** | Eigenständige Questions (wiederverwendbar). Attempt-Tracking. Automatische Auswertung. Bestanden/Nicht-bestanden → Badge-Vergabe. Statistik pro Frage. |

### 6.4 Priorisierter Backlog

```
Priorität 1 (Fundament — ohne diese funktioniert nichts sauber):
  [Q1] Geldbeträge Integer
  [Q2] Booking-Statusmaschine
  [Q4] DateTime statt String
  [M1] Offer-Subtyp-Trennung
  [M3] Session-Modell

Priorität 2 (Kernfunktionalität):
  [M2] Resource-Domain
  [M4] Verfügbarkeits-Engine
  [M5] Training Card Backend
  [Q3] Typen synchronisieren

Priorität 3 (Integration + Automation):
  [M6] Automation-Engine
  [L4] Tentative Reservations
  [L5] Kalender-Integration

Priorität 4 (Kundenerlebnis):
  [L1] Kundenportal
  [L3] Slot-Picker mit Ressourcen-Filter
  [L6] Quiz-Engine

Priorität 5 (Mitarbeiter-Erlebnis):
  [L2] Mitarbeiterpanel
```

### 6.5 Migrations-Strategie

**Grundsatz:** Keine aktiven Kunden → kein Live-Migrationsproblem. Trotzdem sauber vorgehen:

1. **Neue Tabellen neben alten erstellen** (kein DROP TABLE)
2. **Migrationsskript** das alte Daten transformiert (Float→Int, String→DateTime, JSON→Relations)
3. **Feature Flags** für neue vs. alte Codepfade während der Übergangsphase
4. **Seed-Daten** für jeden Meilenstein aktualisieren (Fahrschul-Testdaten)
5. **Tests pro Meilenstein:** Jeder Umbau hat Unit-Tests für die Statusmaschine, Verfügbarkeitslogik, Automation-Rules

---

## Anhang: Referenzierte Dateien

| Datei | Inhalt | Zeilen |
|-------|--------|--------|
| `Bookando reference/types.ts` | Frontend-Typdefinitionen | 823 |
| `Bookando reference/types-api.ts` | API-Typdefinitionen | 532 |
| `Bookando reference/backend/prisma/schema.prisma` | Datenbankschema | ~999 |
| `Bookando reference/modules/Offers.tsx` | Offers-Modul UI | 419 |
| `Bookando reference/modules/Offers/components/OfferModal.tsx` | Offer-Formular | 1012 |
| `Bookando reference/modules/Offers/tabs/CatalogTab.tsx` | Offer-Katalog | 101 |
| `Bookando reference/modules/Resources.tsx` | Ressourcen-Modul UI | ~170 |
| `Bookando reference/modules/Appointments.tsx` | Kalender/Termine UI | ~1300 |
| `Bookando reference/modules/Customers.tsx` | Kunden-Modul UI | ~300 |
| `Bookando reference/modules/Employees.tsx` | Mitarbeiter-Modul UI | ~150 |
| `Bookando reference/backend/src/services/BookingService.ts` | Buchungslogik | 693 |
| `Bookando reference/backend/src/services/ServiceService.ts` | Service-CRUD | 548 |
| `Bookando reference/backend/src/services/CourseService.ts` | Kurs-Logik | 583 |
| `Bookando reference/backend/src/services/EmployeeService.ts` | Mitarbeiter + Verfügbarkeit | ~490 |
| `Bookando reference/backend/src/services/CalendarIntegrationService.ts` | Kalender-Sync | ~940 |
