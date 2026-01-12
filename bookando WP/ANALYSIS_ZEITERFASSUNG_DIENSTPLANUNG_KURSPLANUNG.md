# Analyse & Verbesserungsvorschläge: Zeiterfassung, Dienstplanung & Kursplanung
## Bookando Plugin - Time Tracking, Shift Planning & Course Scheduling

**Datum:** 2025-11-19
**Analysiert von:** Claude (Sonnet 4.5)
**Basis:** Bestehende Bookando-Struktur v1.0.0

---

## Executive Summary

Das Bookando-Plugin verfügt bereits über eine **solide Basis** für Zeiterfassung, Verfügbarkeitsverwaltung und Kursverwaltung. Die vorgeschlagenen Funktionen aus dem anderen GPT-Vorschlag sind teilweise redundant, teilweise veraltet und passen nicht optimal zur bestehenden Architektur.

**Haupterkenntnisse:**
- ✅ **Zeiterfassung existiert bereits** (time_entries, active_timers)
- ✅ **Employee Portal existiert bereits** ([bookando_employee_portal])
- ⚠️ **Pausen-Tracking fehlt** (nur Gesamtsumme, keine einzelnen Pausen)
- ❌ **Schicht-Planung fehlt komplett** (nur Verfügbarkeit, keine konkreten Schichten)
- ⚠️ **Abwesenheitstypen fehlen** (nur generische days_off)
- ❌ **Automatische Schicht-Generierung fehlt**
- ❌ **Kursinstanz-Planung fehlt** (nur Kursdefinitionen, keine Terminplanung)
- ⚠️ **Reporting ist rudimentär**

---

## Detaillierte Analyse nach Modulen

### 🟢 MODUL 0: KALENDER

#### Vorgeschlagene Funktionen:
> Ziel: Kalenderübersicht aller Kurse und Termine der Mitarbeitenden (mit Filterfunktion auf Mitarbeiter)

#### Bestehende Implementierung:
- ✅ **Workday-Modul** hat bereits Kalender-Funktionalität
- ✅ **Appointments-Modul** hat Event/Termin-Verwaltung
- ✅ **event_periods** Tabelle mit period_start_utc/period_end_utc
- ✅ Kalender-Integration (Google, Outlook, Apple, CalDAV, ICS)

#### Bewertung: **80% VORHANDEN**

#### ⚡ Verbesserungsvorschläge:
1. **Vereinheitlichte Kalender-Ansicht erstellen**
   - Zentrale Vue-Komponente für alle zeitbasierten Entitäten
   - Filter: Mitarbeiter, Typ (Kurs, Termin, Schicht, Abwesenheit)
   - Mehrere Ansichten: Tag, Woche, Monat, Agenda

2. **Kalender-Datenmodell erweitern**
   - Nicht notwendig - bestehende Tabellen ausreichend
   - Nur View-Layer verbessern

#### ✨ Ergänzungsvorschläge:
1. **Unified Calendar Service erstellen**
   ```php
   CalendarService::getEvents($userId, $startDate, $endDate, $types = [])
   // Returns: Appointments, Event Periods, Shifts, Days Off, Time Entries
   ```

2. **iCal Feed für Mitarbeiter**
   ```php
   /wp-json/bookando/v1/employees/{id}/calendar.ics
   ```

3. **Drag & Drop Kalender-UI** (Vue3 + FullCalendar)
   - Schichten verschieben
   - Termine anpassen
   - Echtzeit-Konflikt-Prüfung

---

### 🟡 MODUL 1: ZEITERFASSUNG

#### Vorgeschlagene Funktionen vs. Realität:

| Funktion | GPT-Vorschlag | Bookando Realität | Status |
|----------|---------------|-------------------|--------|
| Clock In/Out | [my_sp_time_tracking] Shortcode | [bookando_employee_portal] existiert | ✅ VORHANDEN |
| Pausen-Tracking | Separate Break-Tabelle | Nur `break_minutes` Summe | ⚠️ FEHLT |
| Admin-Übersicht | Neue Seite | Tools > Zeiterfassung existiert | ✅ VORHANDEN |
| Manuelle Einträge | is_manual + manual_reason | source='manual' + notes | ✅ VORHANDEN |
| Arbeitszeitregeln | Plugin Settings | Fehlt | ❌ FEHLT |
| Abwesenheitsmanagement | Neue Tabelle | employees_days_off existiert | ⚠️ TEILWEISE |
| Abwesenheitstypen | vacation, sick, training | Kein type-Feld! | ❌ FEHLT |
| Genehmigungsworkflow | status-basiert | request_status existiert | ✅ VORHANDEN |
| Reporting | CSV Export | Rudimentär | ⚠️ FEHLT |

#### Bewertung: **60% VORHANDEN**

#### ⚡ Verbesserungsvorschläge:

##### 1. **Pausen-Tracking-Tabelle hinzufügen**
```sql
CREATE TABLE {prefix}time_entry_breaks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    time_entry_id BIGINT UNSIGNED NOT NULL,
    break_start_at DATETIME NOT NULL,
    break_end_at DATETIME NULL,
    break_minutes INT UNSIGNED NULL,
    break_type ENUM('paid','unpaid','meal','rest') DEFAULT 'unpaid',
    is_automatic TINYINT(1) DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_break_entry (time_entry_id),
    KEY idx_break_start (break_start_at)
) $col;
```

**Warum wichtig:**
- Gesetzliche Anforderungen (Arbeitszeitgesetz)
- Audit-Trail
- Bessere Reporting-Genauigkeit
- Automatische Pausenregeln möglich

##### 2. **Abwesenheitstypen zu employees_days_off hinzufügen**
```sql
ALTER TABLE {prefix}employees_days_off
ADD COLUMN absence_type ENUM(
    'vacation',      -- Urlaub
    'sick',          -- Krankheit
    'sick_child',    -- Kind krank
    'training',      -- Weiterbildung
    'unpaid',        -- Unbezahlter Urlaub
    'compensatory',  -- Zeitausgleich
    'parental',      -- Elternzeit
    'special',       -- Sonderurlaub (Hochzeit, Todesfall, etc.)
    'public_holiday' -- Feiertag
) NOT NULL DEFAULT 'vacation' AFTER end_date;

ALTER TABLE {prefix}employees_days_off
ADD COLUMN hours_per_day DECIMAL(4,2) NULL AFTER absence_type;
-- Für halbe Tage: 4.0, ganze Tage: 8.0, etc.

ALTER TABLE {prefix}employees_days_off
ADD COLUMN affects_vacation_balance TINYINT(1) DEFAULT 1 AFTER hours_per_day;
-- Ob dieser Eintrag vom Urlaubskontingent abgezogen wird

ALTER TABLE {prefix}employees_days_off
ADD COLUMN requires_certificate TINYINT(1) DEFAULT 0 AFTER affects_vacation_balance;
-- Ob Attest erforderlich (z.B. ab 3 Tagen Krankheit)

ALTER TABLE {prefix}employees_days_off
ADD COLUMN certificate_uploaded TINYINT(1) DEFAULT 0 AFTER requires_certificate;
```

##### 3. **Urlaubskontingent-Verwaltung**
```sql
CREATE TABLE {prefix}employee_vacation_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    year YEAR NOT NULL,
    entitled_days DECIMAL(5,2) NOT NULL DEFAULT 25.00,
    carried_over_days DECIMAL(5,2) DEFAULT 0.00,
    taken_days DECIMAL(5,2) DEFAULT 0.00,
    planned_days DECIMAL(5,2) DEFAULT 0.00,
    remaining_days DECIMAL(5,2) GENERATED ALWAYS AS
        (entitled_days + carried_over_days - taken_days - planned_days) STORED,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_year (user_id, year),
    KEY idx_year (year)
) $col;
```

##### 4. **Arbeitszeitregeln-Konfiguration**
Erweitern Sie die bestehende `bookando_settings` Tabelle:

```php
// Neue Setting-Keys:
'workforce_rules' => [
    'default_work_hours_per_day' => 8.0,
    'default_work_hours_per_week' => 40.0,
    'overtime_threshold_daily' => 10.0,
    'overtime_threshold_weekly' => 45.0,
    'automatic_breaks' => [
        'enabled' => true,
        'rules' => [
            ['min_work_minutes' => 360, 'break_minutes' => 30], // 6h → 30min
            ['min_work_minutes' => 540, 'break_minutes' => 45], // 9h → 45min
        ]
    ],
    'minimum_rest_hours' => 11.0, // Ruhezeit zwischen Schichten
    'max_consecutive_work_days' => 6,
    'rounding_rules' => [
        'enabled' => true,
        'interval_minutes' => 15,
        'method' => 'nearest' // nearest, up, down
    ]
]
```

##### 5. **Automatische Pausen-Logik**
```php
// In WorkforceTimeTrackingService
public function closeTimeEntry(int $entryId): void
{
    $entry = $this->getEntry($entryId);
    $settings = $this->getWorkforceRules();

    if ($settings['automatic_breaks']['enabled']) {
        $this->applyAutomaticBreaks($entry, $settings['automatic_breaks']['rules']);
    }

    $this->calculateTotals($entry);
    $this->checkForViolations($entry); // Überstunden, fehlende Pausen, etc.
}
```

#### ✨ Ergänzungsvorschläge:

##### 1. **GPS-basierte Zeit-Stempelung** (für mobile Außendienstmitarbeiter)
```sql
ALTER TABLE {prefix}time_entries
ADD COLUMN clock_in_lat DECIMAL(10,8) NULL AFTER clock_in_at,
ADD COLUMN clock_in_lng DECIMAL(11,8) NULL AFTER clock_in_lat,
ADD COLUMN clock_out_lat DECIMAL(10,8) NULL AFTER clock_out_at,
ADD COLUMN clock_out_lng DECIMAL(11,8) NULL AFTER clock_out_lat,
ADD COLUMN geofence_verified TINYINT(1) DEFAULT 0 AFTER clock_out_lng;
```

##### 2. **Mitarbeiter-Kommentarfunktion**
```sql
ALTER TABLE {prefix}time_entries
ADD COLUMN employee_comment TEXT NULL AFTER notes,
ADD COLUMN manager_comment TEXT NULL AFTER employee_comment;
```

##### 3. **Export-Formate erweitern**
- CSV (existiert)
- Excel (XLSX)
- PDF (Monatsbericht)
- DATEV (für Lohnbuchhaltung)

##### 4. **Überstunden-Management**
```sql
CREATE TABLE {prefix}overtime_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    overtime_minutes INT NOT NULL DEFAULT 0,
    compensated_minutes INT NOT NULL DEFAULT 0,
    balance_minutes INT GENERATED ALWAYS AS
        (overtime_minutes - compensated_minutes) STORED,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_period (user_id, period_start, period_end)
) $col;
```

##### 5. **Benachrichtigungen**
```php
// Neue Notification-Trigger:
- 'time_entry_pending_approval' → Manager
- 'absence_request_submitted' → Manager
- 'absence_request_approved' → Employee
- 'absence_request_denied' → Employee
- 'overtime_threshold_exceeded' → Manager + Employee
- 'missing_time_entry' → Employee (tägliche Erinnerung)
- 'vacation_balance_low' → Employee
```

---

### 🔴 MODUL 2: DIENSTPLANUNG (STAFF SCHEDULING)

#### Vorgeschlagene Funktionen vs. Realität:

| Funktion | GPT-Vorschlag | Bookando Realität | Status |
|----------|---------------|-------------------|--------|
| Schichten (Shifts) | Neue Tabelle my_sp_shifts | **FEHLT KOMPLETT** | ❌ FEHLT |
| Schicht-Templates | Neue Tabelle | **FEHLT KOMPLETT** | ❌ FEHLT |
| Automatische Schichterzeugung | Algorithmus | **FEHLT KOMPLETT** | ❌ FEHLT |
| Wochenbasierte Ansicht | Admin UI | Workday-Modul zeigt nur Verfügbarkeit | ⚠️ FEHLT |
| Validierung | Überschneidungen, Ruhezeiten | Nicht für Schichten | ❌ FEHLT |
| Mitarbeiter-Limits | Max Std/Tag, Woche | Nicht konfigurierbar | ❌ FEHLT |

#### Bewertung: **10% VORHANDEN** (nur Verfügbarkeit, keine Schichten)

#### 🚨 Kritische Erkenntnis:
**Das Plugin hat VERFÜGBARKEITS-Management (workday/specialday), aber KEIN SCHICHT-Management!**

**Unterschied:**
- **Verfügbarkeit:** "Ich KANN arbeiten Mo-Fr 9-17 Uhr"
- **Schicht:** "Ich MUSS arbeiten am 20.11.2025 von 10:00-14:00 Uhr"

#### ⚡ Verbesserungsvorschläge:

##### 1. **Schicht-Tabelle erstellen** (KRITISCH!)
```sql
CREATE TABLE {prefix}shifts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_minutes INT UNSIGNED DEFAULT 0,
    location_id BIGINT UNSIGNED NULL,
    service_id BIGINT UNSIGNED NULL,
    event_period_id BIGINT UNSIGNED NULL,
    -- Verknüpfung zu event_periods (wenn Schicht für Kurs/Event)

    shift_type ENUM('regular','on_call','training','event','standby') DEFAULT 'regular',
    status ENUM('draft','published','confirmed','cancelled','completed') DEFAULT 'draft',

    notes TEXT NULL,
    color VARCHAR(7) NULL,

    template_id BIGINT UNSIGNED NULL,
    generated_by VARCHAR(50) NULL,
    -- 'manual', 'auto_scheduler', 'template', 'recurring'

    recurring_rule JSON NULL,
    -- Für wiederkehrende Schichten: { freq: 'weekly', interval: 1, days: [1,3,5] }

    published_at DATETIME NULL,
    published_by BIGINT UNSIGNED NULL,

    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_shift_user (user_id),
    KEY idx_shift_date (shift_date),
    KEY idx_shift_user_date (user_id, shift_date),
    KEY idx_shift_datetime (shift_date, start_time, end_time),
    KEY idx_shift_status (status),
    KEY idx_shift_location (location_id),
    KEY idx_shift_service (service_id),
    KEY idx_shift_event (event_period_id),
    KEY idx_shift_tenant (tenant_id)
) $col;
```

**Design-Entscheidungen:**
- ✅ Nutzt `shift_date + start_time/end_time` (nicht UTC) für lokale Planung
- ✅ Verknüpfung zu bestehenden `event_periods` (Kurse werden zu Schichten)
- ✅ Status-Workflow: draft → published → confirmed → completed
- ✅ Unterscheidet zwischen geplant (draft) und veröffentlicht (published)

##### 2. **Schicht-Templates**
```sql
CREATE TABLE {prefix}shift_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_minutes INT UNSIGNED DEFAULT 0,

    default_location_id BIGINT UNSIGNED NULL,
    default_service_id BIGINT UNSIGNED NULL,
    shift_type ENUM('regular','on_call','training','event','standby') DEFAULT 'regular',

    color VARCHAR(7) NULL,

    required_role VARCHAR(64) NULL,
    -- z.B. 'driving_instructor', 'yoga_trainer'

    meta JSON NULL,
    -- Zusätzliche Metadaten

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_template_tenant (tenant_id)
) $col;

-- Beispiel-Templates:
-- "Frühschicht" (06:00-14:00, 30min Pause)
-- "Spätschicht" (14:00-22:00, 30min Pause)
-- "Nachtschicht" (22:00-06:00, 45min Pause)
-- "Fahrstunde" (individuell, 0min Pause)
```

##### 3. **Mitarbeiter-Schichtpräferenzen**
```sql
CREATE TABLE {prefix}employee_shift_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,

    max_hours_per_day DECIMAL(4,2) DEFAULT 8.00,
    max_hours_per_week DECIMAL(5,2) DEFAULT 40.00,
    preferred_work_days_per_week INT DEFAULT 5,

    preferred_shift_types JSON NULL,
    -- ['regular', 'event'] - keine On-Call-Schichten

    preferred_days JSON NULL,
    -- [1, 2, 3, 4, 5] - Mo-Fr bevorzugt

    blocked_days JSON NULL,
    -- [0, 6] - Keine Sonntags-/Samstagsschichten

    preferred_time_ranges JSON NULL,
    -- [{ start: '09:00', end: '17:00' }]

    max_consecutive_work_days INT DEFAULT 6,
    min_hours_between_shifts DECIMAL(4,2) DEFAULT 11.00,

    notes TEXT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_prefs (user_id)
) $col;
```

##### 4. **Schicht-Anforderungen** (für automatische Planung)
```sql
CREATE TABLE {prefix}shift_requirements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,

    day_of_week TINYINT UNSIGNED NULL,
    -- 1=Monday, 7=Sunday, NULL=jeden Tag

    start_date DATE NULL,
    end_date DATE NULL,
    -- Optional: nur für bestimmten Zeitraum

    start_time TIME NOT NULL,
    end_time TIME NOT NULL,

    required_employees INT DEFAULT 1,
    required_role VARCHAR(64) NULL,

    location_id BIGINT UNSIGNED NULL,
    service_id BIGINT UNSIGNED NULL,

    priority INT DEFAULT 0,
    -- Höhere Priorität = wird zuerst geplant

    is_active TINYINT(1) DEFAULT 1,

    notes TEXT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_req_tenant (tenant_id),
    KEY idx_req_dow (day_of_week),
    KEY idx_req_dates (start_date, end_date),
    KEY idx_req_time (start_time, end_time)
) $col;
```

##### 5. **Automatischer Schicht-Planer (MVP-Algorithmus)**

```php
namespace Bookando\Modules\workday\Services;

class ShiftScheduler
{
    /**
     * Generiert Schichten für einen Zeitraum basierend auf:
     * - Shift Requirements
     * - Employee Preferences
     * - Employee Availability (workday_sets, specialday_sets)
     * - Absences (days_off)
     * - Existing Shifts & Appointments
     */
    public function generateShifts(
        string $startDate,
        string $endDate,
        array $options = []
    ): array {
        $strategy = $options['strategy'] ?? 'balanced';
        // 'balanced', 'minimize_staff', 'maximize_coverage'

        $requirements = $this->getRequirements($startDate, $endDate);
        $employees = $this->getAvailableEmployees($startDate, $endDate);

        $shifts = [];
        $conflicts = [];

        foreach ($requirements as $req) {
            $dates = $this->getRelevantDates($req, $startDate, $endDate);

            foreach ($dates as $date) {
                for ($i = 0; $i < $req->required_employees; $i++) {
                    $candidates = $this->findCandidates(
                        $employees,
                        $date,
                        $req,
                        $shifts
                    );

                    if (empty($candidates)) {
                        $conflicts[] = [
                            'date' => $date,
                            'time' => $req->start_time . '-' . $req->end_time,
                            'reason' => 'No available employees'
                        ];
                        continue;
                    }

                    $bestEmployee = $this->selectBestCandidate(
                        $candidates,
                        $strategy,
                        $shifts
                    );

                    $shifts[] = $this->createShift([
                        'user_id' => $bestEmployee->id,
                        'shift_date' => $date,
                        'start_time' => $req->start_time,
                        'end_time' => $req->end_time,
                        'location_id' => $req->location_id,
                        'service_id' => $req->service_id,
                        'generated_by' => 'auto_scheduler'
                    ]);
                }
            }
        }

        return [
            'shifts' => $shifts,
            'conflicts' => $conflicts,
            'summary' => $this->generateSummary($shifts, $conflicts)
        ];
    }

    private function findCandidates(
        array $employees,
        string $date,
        object $req,
        array $existingShifts
    ): array {
        $candidates = [];

        foreach ($employees as $emp) {
            // 1. Hat Mitarbeiter erforderliche Rolle?
            if ($req->required_role && !$this->hasRole($emp, $req->required_role)) {
                continue;
            }

            // 2. Ist Mitarbeiter verfügbar (workday/specialday)?
            if (!$this->isAvailable($emp, $date, $req->start_time, $req->end_time)) {
                continue;
            }

            // 3. Ist Mitarbeiter abwesend (days_off)?
            if ($this->isAbsent($emp, $date)) {
                continue;
            }

            // 4. Hat Mitarbeiter bereits eine Schicht zu dieser Zeit?
            if ($this->hasConflictingShift($emp, $date, $req->start_time, $req->end_time, $existingShifts)) {
                continue;
            }

            // 5. Würde Schicht Limits überschreiten (max Std/Tag, Woche)?
            if ($this->wouldExceedLimits($emp, $date, $req, $existingShifts)) {
                continue;
            }

            // 6. Würde Ruhezeit unterschritten (11h zwischen Schichten)?
            if ($this->wouldViolateRestPeriod($emp, $date, $req, $existingShifts)) {
                continue;
            }

            $candidates[] = [
                'employee' => $emp,
                'score' => $this->calculateScore($emp, $date, $req, $existingShifts)
            ];
        }

        return $candidates;
    }

    private function calculateScore(
        object $emp,
        string $date,
        object $req,
        array $existingShifts
    ): float {
        $score = 100.0;

        // Bevorzuge Mitarbeiter mit weniger geplanten Stunden
        $currentHours = $this->getPlannedHours($emp, $existingShifts);
        $score -= ($currentHours * 0.5);

        // Bevorzuge Präferenzen
        $prefs = $this->getPreferences($emp);
        if ($prefs && $this->matchesPreferences($date, $req, $prefs)) {
            $score += 10;
        }

        // Bevorzuge Service-Qualifikationen
        if ($req->service_id && $this->hasServiceQualification($emp, $req->service_id)) {
            $score += 5;
        }

        // Bevorzuge Orts-Nähe/Erfahrung
        if ($req->location_id && $this->hasLocationExperience($emp, $req->location_id)) {
            $score += 3;
        }

        return $score;
    }

    private function selectBestCandidate(
        array $candidates,
        string $strategy,
        array $existingShifts
    ): object {
        usort($candidates, function($a, $b) use ($strategy) {
            if ($strategy === 'balanced') {
                // Höherer Score = besser
                return $b['score'] <=> $a['score'];
            } elseif ($strategy === 'minimize_staff') {
                // Bevorzuge Mitarbeiter, die bereits Schichten haben
                $aCount = $this->getShiftCount($a['employee'], $existingShifts);
                $bCount = $this->getShiftCount($b['employee'], $existingShifts);
                return $bCount <=> $aCount;
            } elseif ($strategy === 'maximize_coverage') {
                // Bevorzuge Mitarbeiter mit weniger Schichten
                $aCount = $this->getShiftCount($a['employee'], $existingShifts);
                $bCount = $this->getShiftCount($b['employee'], $existingShifts);
                return $aCount <=> $bCount;
            }
        });

        return $candidates[0]['employee'];
    }
}
```

##### 6. **Schicht-Publikation & Benachrichtigung**
```php
namespace Bookando\Modules\workday\Services;

class ShiftPublisher
{
    public function publishShifts(array $shiftIds, int $userId): array
    {
        $shifts = $this->getShiftsByIds($shiftIds);
        $published = [];

        foreach ($shifts as $shift) {
            // Status ändern
            $shift->status = 'published';
            $shift->published_at = current_time('mysql');
            $shift->published_by = $userId;
            $shift->save();

            // Benachrichtigung senden
            $this->notifyEmployee($shift);

            $published[] = $shift;
        }

        return $published;
    }

    private function notifyEmployee(object $shift): void
    {
        // E-Mail
        $employee = $this->getEmployee($shift->user_id);
        $this->sendEmail($employee, $shift);

        // Optional: Push-Notification (für zukünftige Mobile App)
        // $this->sendPushNotification($employee, $shift);

        // Optional: SMS
        // if ($this->isSmsEnabled()) {
        //     $this->sendSms($employee, $shift);
        // }
    }
}
```

#### ✨ Ergänzungsvorschläge:

##### 1. **Schicht-Tausch (Shift Swap)**
```sql
CREATE TABLE {prefix}shift_swap_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_id BIGINT UNSIGNED NOT NULL,
    from_user_id BIGINT UNSIGNED NOT NULL,
    to_user_id BIGINT UNSIGNED NULL,
    -- NULL = öffentliche Anfrage, beliebiger Mitarbeiter kann übernehmen

    status ENUM('pending','accepted','declined','cancelled','approved','completed') DEFAULT 'pending',

    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    approved_at DATETIME NULL,
    -- Manager muss Tausch genehmigen

    approved_by BIGINT UNSIGNED NULL,

    reason TEXT NULL,
    notes TEXT NULL,

    KEY idx_swap_shift (shift_id),
    KEY idx_swap_from (from_user_id),
    KEY idx_swap_to (to_user_id),
    KEY idx_swap_status (status)
) $col;
```

##### 2. **Schicht-Verfügbarkeitsprüfung** (Open Shifts)
```sql
ALTER TABLE {prefix}shifts
ADD COLUMN is_open TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN min_required INT DEFAULT 1 AFTER is_open,
ADD COLUMN max_allowed INT DEFAULT 1 AFTER min_required;

CREATE TABLE {prefix}shift_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('assigned','accepted','declined','completed') DEFAULT 'assigned',
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    KEY idx_assignment_shift (shift_id),
    KEY idx_assignment_user (user_id),
    UNIQUE KEY uq_shift_user (shift_id, user_id)
) $col;
```

##### 3. **Schicht-Kostenrechnung**
```sql
ALTER TABLE {prefix}shifts
ADD COLUMN hourly_rate DECIMAL(10,2) NULL AFTER break_minutes,
ADD COLUMN total_cost DECIMAL(10,2) GENERATED ALWAYS AS
    (TIMESTAMPDIFF(MINUTE,
        TIMESTAMP(shift_date, start_time),
        TIMESTAMP(shift_date, end_time)
    ) - break_minutes) * hourly_rate / 60 STORED;
```

##### 4. **Konflikt-Detektor**
```php
class ShiftConflictDetector
{
    public function detectConflicts(int $userId, string $date, string $startTime, string $endTime): array
    {
        $conflicts = [];

        // 1. Überschneidende Schichten
        $overlappingShifts = $this->getOverlappingShifts($userId, $date, $startTime, $endTime);
        if ($overlappingShifts) {
            $conflicts[] = ['type' => 'overlapping_shift', 'data' => $overlappingShifts];
        }

        // 2. Appointments
        $overlappingAppointments = $this->getOverlappingAppointments($userId, $date, $startTime, $endTime);
        if ($overlappingAppointments) {
            $conflicts[] = ['type' => 'overlapping_appointment', 'data' => $overlappingAppointments];
        }

        // 3. Abwesenheit
        if ($this->isAbsent($userId, $date)) {
            $conflicts[] = ['type' => 'absence', 'data' => $this->getAbsence($userId, $date)];
        }

        // 4. Ruhezeit-Verletzung
        $restViolation = $this->checkRestPeriod($userId, $date, $startTime);
        if ($restViolation) {
            $conflicts[] = ['type' => 'rest_period_violation', 'data' => $restViolation];
        }

        // 5. Arbeitszeit-Limit
        $limitViolation = $this->checkWorkingTimeLimits($userId, $date, $startTime, $endTime);
        if ($limitViolation) {
            $conflicts[] = ['type' => 'working_time_limit', 'data' => $limitViolation];
        }

        // 6. Availability
        if (!$this->isAvailable($userId, $date, $startTime, $endTime)) {
            $conflicts[] = ['type' => 'not_available', 'data' => null];
        }

        return $conflicts;
    }
}
```

---

### 🟡 MODUL 3: KURSPLANUNG (COURSE SCHEDULING)

#### Vorgeschlagene Funktionen vs. Realität:

| Funktion | GPT-Vorschlag | Bookando Realität | Status |
|----------|---------------|-------------------|--------|
| Kurstypen | course Post Type | Academy: courses Tabelle | ✅ VORHANDEN |
| Kursinstanzen | my_sp_course_instances | **FEHLT** | ❌ FEHLT |
| Automatische Planung | Algorithmus | **FEHLT** | ❌ FEHLT |
| Kursplanung-UI | Admin Seite | Tools > Kursplaner existiert (leer?) | ⚠️ TEILWEISE |
| Mitarbeiter-Qualifikationen | Zuordnung | employees_services existiert | ✅ VORHANDEN |
| Orte | location Tabelle | locations existiert | ✅ VORHANDEN |

#### Bewertung: **40% VORHANDEN** (Kursdefinitionen ja, Instanz-Planung nein)

#### 🚨 Kritische Erkenntnis:
**Das Plugin unterscheidet zwischen:**
1. **Academy-Kurse:** Training/Ausbildungskurse (academy_courses, lessons, topics)
2. **Event-Kurse:** Buchbare Gruppentermine (events, event_periods)

**Problem:** Keine automatische Planung von Event-Instanzen über längere Zeiträume!

#### ⚡ Verbesserungsvorschläge:

##### 1. **Kursinstanz-Planungstabelle NICHT notwendig!**
**Nutzen Sie stattdessen die bestehende Struktur:**
- `events` = Kursdefinition (z.B. "Yoga Anfänger")
- `event_periods` = Kursinstanzen (z.B. "Yoga Anfänger am 20.11.2025 18:00-19:30")

**Korrekte Architektur:**
```
┌─────────────────┐
│ events          │ ← Kursdefinition (wiederkehrend oder einmalig)
│ - type: 'course'│
│ - name          │
│ - max_capacity  │
└────────┬────────┘
         │
         │ 1:N
         │
┌────────▼────────────────┐
│ event_periods           │ ← Konkrete Termine (Instanzen)
│ - period_start_utc      │
│ - period_end_utc        │
└────────┬────────────────┘
         │
         ├─→ event_period_employees (Trainer zuordnen)
         ├─→ event_period_services (Services zuordnen)
         ├─→ event_period_locations (Orte zuordnen)
         └─→ event_period_resources (Ressourcen zuordnen)
```

##### 2. **Event-Generierungs-Logik erweitern**
```php
namespace Bookando\Modules\appointments\Services;

class EventPeriodGenerator
{
    /**
     * Generiert Event-Perioden für einen Event über einen Zeitraum
     *
     * @param int $eventId
     * @param string $startDate
     * @param string $endDate
     * @param array $options
     * @return array
     */
    public function generatePeriods(
        int $eventId,
        string $startDate,
        string $endDate,
        array $options = []
    ): array {
        $event = $this->getEvent($eventId);

        // Optionen auswerten
        $daysOfWeek = $options['days_of_week'] ?? [1, 2, 3, 4, 5]; // Mo-Fr
        $timeSlots = $options['time_slots'] ?? [
            ['start' => '09:00:00', 'end' => '10:30:00'],
            ['start' => '18:00:00', 'end' => '19:30:00']
        ];
        $maxInstances = $options['max_instances'] ?? null;
        $minInstancesPerWeek = $options['min_instances_per_week'] ?? 1;
        $maxInstancesPerWeek = $options['max_instances_per_week'] ?? 7;
        $preferredEmployees = $options['preferred_employees'] ?? [];
        $requiredLocation = $options['required_location'] ?? null;

        $periods = [];
        $conflicts = [];
        $instanceCount = 0;

        $currentDate = new \DateTime($startDate);
        $endDateTime = new \DateTime($endDate);

        while ($currentDate <= $endDateTime) {
            $dayOfWeek = (int)$currentDate->format('N'); // 1=Mo, 7=So

            if (!in_array($dayOfWeek, $daysOfWeek)) {
                $currentDate->modify('+1 day');
                continue;
            }

            foreach ($timeSlots as $slot) {
                if ($maxInstances && $instanceCount >= $maxInstances) {
                    break 2;
                }

                $periodStart = $currentDate->format('Y-m-d') . ' ' . $slot['start'];
                $periodEnd = $currentDate->format('Y-m-d') . ' ' . $slot['end'];

                // Mitarbeiter finden
                $employee = $this->findAvailableEmployee(
                    $event,
                    $periodStart,
                    $periodEnd,
                    $preferredEmployees
                );

                if (!$employee) {
                    $conflicts[] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'time' => $slot['start'] . '-' . $slot['end'],
                        'reason' => 'No available employee'
                    ];
                    continue;
                }

                // Ort prüfen
                $location = $this->findAvailableLocation(
                    $periodStart,
                    $periodEnd,
                    $requiredLocation
                );

                if (!$location) {
                    $conflicts[] = [
                        'date' => $currentDate->format('Y-m-d'),
                        'time' => $slot['start'] . '-' . $slot['end'],
                        'reason' => 'No available location'
                    ];
                    continue;
                }

                // Event Period erstellen
                $period = $this->createEventPeriod([
                    'event_id' => $eventId,
                    'period_start_utc' => $this->toUtc($periodStart),
                    'period_end_utc' => $this->toUtc($periodEnd),
                    'time_zone' => wp_timezone_string()
                ]);

                // Verknüpfungen erstellen
                $this->assignEmployee($period->id, $employee->id);
                $this->assignLocation($period->id, $location->id);

                // Optional: Schicht für Mitarbeiter erstellen
                if ($options['create_shifts'] ?? true) {
                    $this->createShiftForPeriod($period, $employee);
                }

                $periods[] = $period;
                $instanceCount++;
            }

            $currentDate->modify('+1 day');
        }

        return [
            'periods' => $periods,
            'conflicts' => $conflicts,
            'summary' => [
                'created' => count($periods),
                'requested' => $maxInstances ?? 'unlimited',
                'conflicts' => count($conflicts)
            ]
        ];
    }

    private function findAvailableEmployee(
        object $event,
        string $startTime,
        string $endTime,
        array $preferred = []
    ): ?object {
        // 1. Qualifizierte Mitarbeiter finden
        $qualified = $this->getQualifiedEmployees($event);

        // 2. Bevorzugte zuerst prüfen
        if (!empty($preferred)) {
            $qualified = array_merge(
                array_filter($qualified, fn($e) => in_array($e->id, $preferred)),
                array_filter($qualified, fn($e) => !in_array($e->id, $preferred))
            );
        }

        // 3. Verfügbarkeit prüfen
        foreach ($qualified as $emp) {
            if ($this->isEmployeeAvailable($emp, $startTime, $endTime)) {
                return $emp;
            }
        }

        return null;
    }

    private function isEmployeeAvailable(
        object $employee,
        string $startTime,
        string $endTime
    ): bool {
        // 1. Abwesenheit prüfen
        if ($this->isAbsent($employee->id, $startTime)) {
            return false;
        }

        // 2. Überschneidende Termine prüfen
        if ($this->hasOverlappingAppointment($employee->id, $startTime, $endTime)) {
            return false;
        }

        // 3. Überschneidende Schichten prüfen
        if ($this->hasOverlappingShift($employee->id, $startTime, $endTime)) {
            return false;
        }

        // 4. Überschneidende Event-Perioden prüfen
        if ($this->hasOverlappingEventPeriod($employee->id, $startTime, $endTime)) {
            return false;
        }

        // 5. Workday/Specialday-Verfügbarkeit prüfen
        if (!$this->isWithinWorkingHours($employee->id, $startTime, $endTime)) {
            return false;
        }

        // 6. Externe Kalender prüfen (busy-Zeit)
        if ($this->hasBusyTimeInCalendar($employee->id, $startTime, $endTime)) {
            return false;
        }

        return true;
    }

    private function createShiftForPeriod(object $period, object $employee): void
    {
        global $wpdb;

        $startLocal = $this->toLocal($period->period_start_utc);
        $endLocal = $this->toLocal($period->period_end_utc);

        $wpdb->insert(
            $wpdb->prefix . 'bookando_shifts',
            [
                'tenant_id' => $period->tenant_id,
                'user_id' => $employee->id,
                'shift_date' => date('Y-m-d', strtotime($startLocal)),
                'start_time' => date('H:i:s', strtotime($startLocal)),
                'end_time' => date('H:i:s', strtotime($endLocal)),
                'shift_type' => 'event',
                'event_period_id' => $period->id,
                'status' => 'published',
                'generated_by' => 'course_planner',
                'created_at' => current_time('mysql')
            ]
        );
    }
}
```

##### 3. **Kursplanungs-UI (Vue-Komponente)**
```vue
<!-- src/modules/tools/assets/vue/components/course-planner/CoursePlannerTab.vue -->
<template>
  <div class="course-planner">
    <AppCard title="Automatische Kursplanung">
      <div class="planner-form">
        <!-- Event/Kurs auswählen -->
        <AppSelect
          v-model="selectedEventId"
          label="Kurs auswählen"
          :options="events"
          option-label="name"
          option-value="id"
          required
        />

        <!-- Zeitraum -->
        <AppDateRangePicker
          v-model="dateRange"
          label="Planungszeitraum"
          required
        />

        <!-- Wochentage -->
        <AppCheckboxGroup
          v-model="daysOfWeek"
          label="Wochentage"
          :options="weekdayOptions"
        />

        <!-- Zeitfenster -->
        <div class="time-slots">
          <label>Zeitfenster</label>
          <div v-for="(slot, index) in timeSlots" :key="index" class="time-slot">
            <AppTimePicker v-model="slot.start" />
            <span>bis</span>
            <AppTimePicker v-model="slot.end" />
            <AppButton
              icon="trash"
              variant="danger"
              size="small"
              @click="removeTimeSlot(index)"
            />
          </div>
          <AppButton
            icon="plus"
            label="Zeitfenster hinzufügen"
            @click="addTimeSlot"
          />
        </div>

        <!-- Weitere Optionen -->
        <AppAccordion title="Erweiterte Optionen">
          <AppNumberInput
            v-model="maxInstances"
            label="Maximale Anzahl Instanzen"
            :min="1"
          />

          <AppNumberInput
            v-model="minInstancesPerWeek"
            label="Min. Instanzen pro Woche"
            :min="1"
          />

          <AppNumberInput
            v-model="maxInstancesPerWeek"
            label="Max. Instanzen pro Woche"
            :min="1"
          />

          <AppSelect
            v-model="preferredEmployees"
            label="Bevorzugte Mitarbeiter"
            :options="employees"
            option-label="full_name"
            option-value="id"
            multiple
          />

          <AppSelect
            v-model="requiredLocation"
            label="Ort (optional)"
            :options="locations"
            option-label="name"
            option-value="id"
          />

          <AppCheckbox
            v-model="createShifts"
            label="Automatisch Schichten für Mitarbeiter erstellen"
          />
        </AppAccordion>

        <!-- Buttons -->
        <div class="actions">
          <AppButton
            label="Vorschau generieren"
            icon="eye"
            @click="generatePreview"
            :loading="isGenerating"
          />

          <AppButton
            label="Kurse planen"
            icon="calendar-plus"
            variant="primary"
            @click="generatePeriods"
            :loading="isGenerating"
            :disabled="!canGenerate"
          />
        </div>
      </div>
    </AppCard>

    <!-- Vorschau/Ergebnisse -->
    <AppCard v-if="result" title="Planungsergebnis">
      <div class="result-summary">
        <div class="stat">
          <span class="stat-label">Erstellt:</span>
          <span class="stat-value">{{ result.summary.created }}</span>
        </div>
        <div class="stat">
          <span class="stat-label">Konflikte:</span>
          <span class="stat-value stat-value--warning">{{ result.summary.conflicts }}</span>
        </div>
      </div>

      <!-- Konflikte anzeigen -->
      <AppAlert v-if="result.conflicts.length > 0" type="warning">
        <p>{{ result.conflicts.length }} Termine konnten nicht geplant werden:</p>
        <ul>
          <li v-for="conflict in result.conflicts" :key="conflict.date + conflict.time">
            {{ formatDate(conflict.date) }} {{ conflict.time }}: {{ conflict.reason }}
          </li>
        </ul>
      </AppAlert>

      <!-- Erstellte Perioden anzeigen -->
      <AppTable
        :columns="periodColumns"
        :data="result.periods"
      />

      <div class="actions">
        <AppButton
          label="Alle löschen"
          icon="trash"
          variant="danger"
          @click="deleteAllPeriods"
        />
      </div>
    </AppCard>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useApiClient } from '@/composables/useApiClient'

const api = useApiClient()

const selectedEventId = ref<number | null>(null)
const dateRange = ref<[string, string]>(['', ''])
const daysOfWeek = ref<number[]>([1, 2, 3, 4, 5])
const timeSlots = ref<Array<{ start: string; end: string }>>([
  { start: '09:00', end: '10:30' }
])
const maxInstances = ref<number | null>(null)
const minInstancesPerWeek = ref(1)
const maxInstancesPerWeek = ref(7)
const preferredEmployees = ref<number[]>([])
const requiredLocation = ref<number | null>(null)
const createShifts = ref(true)

const isGenerating = ref(false)
const result = ref<any>(null)

const events = ref([])
const employees = ref([])
const locations = ref([])

const weekdayOptions = [
  { value: 1, label: 'Montag' },
  { value: 2, label: 'Dienstag' },
  { value: 3, label: 'Mittwoch' },
  { value: 4, label: 'Donnerstag' },
  { value: 5, label: 'Freitag' },
  { value: 6, label: 'Samstag' },
  { value: 7, label: 'Sonntag' }
]

const canGenerate = computed(() => {
  return selectedEventId.value &&
    dateRange.value[0] &&
    dateRange.value[1] &&
    daysOfWeek.value.length > 0 &&
    timeSlots.value.length > 0
})

async function generatePeriods() {
  isGenerating.value = true
  try {
    const response = await api.post('/appointments/event-periods/generate', {
      event_id: selectedEventId.value,
      start_date: dateRange.value[0],
      end_date: dateRange.value[1],
      options: {
        days_of_week: daysOfWeek.value,
        time_slots: timeSlots.value,
        max_instances: maxInstances.value,
        min_instances_per_week: minInstancesPerWeek.value,
        max_instances_per_week: maxInstancesPerWeek.value,
        preferred_employees: preferredEmployees.value,
        required_location: requiredLocation.value,
        create_shifts: createShifts.value
      }
    })

    result.value = response.data
  } catch (error) {
    console.error('Failed to generate periods:', error)
  } finally {
    isGenerating.value = false
  }
}
</script>
```

##### 4. **Intelligentere Planung mit historischen Daten**
```sql
-- Event-Performance-Tracking erweitern
ALTER TABLE {prefix}event_periods
ADD COLUMN actual_participants INT NULL AFTER period_end_utc,
ADD COLUMN revenue DECIMAL(10,2) NULL AFTER actual_participants,
ADD COLUMN rating DECIMAL(3,2) NULL AFTER revenue,
ADD COLUMN feedback_count INT DEFAULT 0 AFTER rating;

-- Für spätere Analytics
CREATE TABLE {prefix}event_period_analytics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    -- 'morning', 'afternoon', 'evening', 'night'

    avg_participants DECIMAL(5,2) DEFAULT 0.00,
    avg_capacity_utilization DECIMAL(5,2) DEFAULT 0.00,
    avg_revenue DECIMAL(10,2) DEFAULT 0.00,
    avg_rating DECIMAL(3,2) DEFAULT 0.00,

    total_instances INT DEFAULT 0,
    cancelled_instances INT DEFAULT 0,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_event_dow_slot (event_id, day_of_week, time_slot),
    KEY idx_analytics_event (event_id)
) $col;
```

```php
class SmartCoursePlanner extends EventPeriodGenerator
{
    /**
     * Nutzt historische Daten für bessere Planung
     */
    protected function selectOptimalTimeSlots(int $eventId): array
    {
        $analytics = $this->getEventAnalytics($eventId);

        if (empty($analytics)) {
            // Fallback auf Standard-Zeitfenster
            return $this->getDefaultTimeSlots();
        }

        // Sortiere nach Performance
        usort($analytics, function($a, $b) {
            $scoreA = ($a->avg_capacity_utilization * 0.4) +
                      ($a->avg_rating * 20) +
                      ((1 - $a->cancelled_instances / $a->total_instances) * 40);

            $scoreB = ($b->avg_capacity_utilization * 0.4) +
                      ($b->avg_rating * 20) +
                      ((1 - $b->cancelled_instances / $b->total_instances) * 40);

            return $scoreB <=> $scoreA;
        });

        // Top 3 Zeitfenster zurückgeben
        return array_slice($analytics, 0, 3);
    }
}
```

#### ✨ Ergänzungsvorschläge:

##### 1. **Wartelisten-Management** (existiert teilweise)
```sql
-- Tabelle existiert vermutlich bereits, hier zur Vollständigkeit
CREATE TABLE IF NOT EXISTS {prefix}event_waitlist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_period_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    position INT NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notified_at DATETIME NULL,
    converted_at DATETIME NULL,
    expired_at DATETIME NULL,
    KEY idx_waitlist_period (event_period_id),
    KEY idx_waitlist_customer (customer_id),
    KEY idx_waitlist_position (event_period_id, position)
) $col;
```

##### 2. **Serie von Kursen** (z.B. 10-Wochen-Kurs)
```sql
CREATE TABLE {prefix}event_series (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NULL,
    event_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    series_type ENUM('sequential','parallel','flexible') DEFAULT 'sequential',
    -- sequential: Termine müssen nacheinander besucht werden
    -- parallel: Termine können parallel von verschiedenen Teilnehmern besucht werden
    -- flexible: Teilnehmer können beliebige Termine auswählen

    total_sessions INT NOT NULL,
    attended_sessions_required INT NULL,
    -- Mindestanzahl zu besuchender Termine für Abschluss

    price DECIMAL(10,2) NULL,
    -- Preis für gesamte Serie (statt Einzelpreise)

    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    -- Rabatt gegenüber Einzelbuchungen

    enrollment_start_utc DATETIME NULL,
    enrollment_end_utc DATETIME NULL,

    series_start_date DATE NOT NULL,
    series_end_date DATE NOT NULL,

    max_participants INT NULL,

    status ENUM('draft','open','closed','completed','cancelled') DEFAULT 'draft',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_series_event (event_id),
    KEY idx_series_tenant (tenant_id),
    KEY idx_series_dates (series_start_date, series_end_date)
) $col;

CREATE TABLE {prefix}event_series_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    series_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    session_number INT NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    KEY idx_series_period (series_id, session_number),
    KEY idx_period_series (period_id)
) $col;

CREATE TABLE {prefix}event_series_enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    series_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','completed','cancelled','suspended') DEFAULT 'active',
    attended_sessions INT DEFAULT 0,
    missed_sessions INT DEFAULT 0,
    progress_percentage DECIMAL(5,2) GENERATED ALWAYS AS
        (attended_sessions * 100.0 / (SELECT total_sessions FROM {prefix}event_series WHERE id = series_id)) STORED,
    completed_at DATETIME NULL,
    KEY idx_enrollment_series (series_id),
    KEY idx_enrollment_customer (customer_id),
    UNIQUE KEY uq_series_customer (series_id, customer_id)
) $col;
```

##### 3. **Raum-/Ressourcen-Konflikt-Prüfung**
```php
class ResourceConflictChecker
{
    public function checkLocationAvailability(
        int $locationId,
        string $startTime,
        string $endTime
    ): bool {
        global $wpdb;

        // Prüfe überschneidende Event-Perioden
        $overlappingPeriods = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}bookando_event_periods ep
            INNER JOIN {$wpdb->prefix}bookando_event_period_locations epl
                ON ep.id = epl.period_id
            WHERE epl.location_id = %d
            AND (
                (ep.period_start_utc < %s AND ep.period_end_utc > %s)
                OR (ep.period_start_utc < %s AND ep.period_end_utc > %s)
                OR (ep.period_start_utc >= %s AND ep.period_end_utc <= %s)
            )
        ", $locationId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime));

        return $overlappingPeriods == 0;
    }

    public function checkResourceAvailability(
        int $resourceId,
        int $requiredQuantity,
        string $startTime,
        string $endTime
    ): bool {
        global $wpdb;

        $resource = $this->getResource($resourceId);

        // Berechne bereits belegte Menge
        $usedQuantity = $wpdb->get_var($wpdb->prepare("
            SELECT COALESCE(SUM(epr.required_quantity), 0)
            FROM {$wpdb->prefix}bookando_event_periods ep
            INNER JOIN {$wpdb->prefix}bookando_event_period_resources epr
                ON ep.id = epr.period_id
            WHERE epr.resource_id = %d
            AND (
                (ep.period_start_utc < %s AND ep.period_end_utc > %s)
                OR (ep.period_start_utc < %s AND ep.period_end_utc > %s)
                OR (ep.period_start_utc >= %s AND ep.period_end_utc <= %s)
            )
        ", $resourceId, $endTime, $startTime, $endTime, $startTime, $startTime, $endTime));

        $availableQuantity = $resource->quantity - $usedQuantity;

        return $availableQuantity >= $requiredQuantity;
    }
}
```

---

## 🎯 Implementierungspriorisierung

### Phase 1: KRITISCHE GRUNDLAGEN (Woche 1-2)

#### 1.1 Pausen-Tracking
- ✅ Tabelle `time_entry_breaks` erstellen
- ✅ Admin-UI erweitern
- ✅ Employee Portal anpassen
- ✅ Automatische Pausenregeln implementieren
- **Aufwand:** 3-4 Tage

#### 1.2 Abwesenheitstypen & Urlaubskontingent
- ✅ `employees_days_off` erweitern (absence_type, etc.)
- ✅ Tabelle `employee_vacation_balances` erstellen
- ✅ Admin-UI für Urlaubsverwaltung
- ✅ Berechnung von Urlaubstagen
- **Aufwand:** 2-3 Tage

#### 1.3 Schicht-Management (Basis)
- ✅ Tabelle `shifts` erstellen
- ✅ Tabelle `shift_templates` erstellen
- ✅ Einfache manuelle Schicht-UI (CRUD)
- ✅ Konflikt-Detektor
- **Aufwand:** 5-6 Tage

### Phase 2: AUTOMATISIERUNG (Woche 3-4)

#### 2.1 Automatische Schichtplanung
- ✅ `shift_requirements` Tabelle
- ✅ `employee_shift_preferences` Tabelle
- ✅ ShiftScheduler-Service implementieren
- ✅ Admin-UI für automatische Generierung
- ✅ Publikation & Benachrichtigung
- **Aufwand:** 7-8 Tage

#### 2.2 Automatische Kursplanung
- ✅ EventPeriodGenerator-Service erweitern
- ✅ Kursplanungs-UI (CoursePlannerTab)
- ✅ Integration mit Schicht-System
- ✅ Konflikt-Behandlung
- **Aufwand:** 5-6 Tage

### Phase 3: ERWEITERTE FEATURES (Woche 5-6)

#### 3.1 Reporting & Analytics
- ✅ Zeiterfassungs-Reports (Monat, Jahr)
- ✅ Überstunden-Tracking
- ✅ Export-Funktionen (CSV, XLSX, PDF)
- ✅ Dashboard mit Statistiken
- **Aufwand:** 4-5 Tage

#### 3.2 Unified Calendar
- ✅ CalendarService für alle Entitäten
- ✅ iCal-Feed für Mitarbeiter
- ✅ Drag & Drop Kalender-UI
- **Aufwand:** 4-5 Tage

#### 3.3 Schicht-Tausch & Open Shifts
- ✅ `shift_swap_requests` Tabelle
- ✅ `shift_assignments` Tabelle
- ✅ UI für Schicht-Tausch
- ✅ Benachrichtigungen
- **Aufwand:** 3-4 Tage

### Phase 4: OPTIMIERUNGEN (Woche 7-8)

#### 4.1 Smart Scheduling
- ✅ Event-Analytics-Tracking
- ✅ ML-basierte Optimierungen
- ✅ Historische Daten nutzen
- **Aufwand:** 5-6 Tage

#### 4.2 Mobile Optimierungen
- ✅ GPS-Stempelung
- ✅ Push-Benachrichtigungen vorbereiten
- ✅ Offline-Fähigkeit
- **Aufwand:** 4-5 Tage

---

## 📊 Zusammenfassung & Empfehlungen

### ✅ Was GUT ist am GPT-Vorschlag:
1. **Strukturierter Ansatz** zu Zeiterfassung, Schichtplanung und Kursplanung
2. **Genehmigungsworkflows** für Abwesenheiten
3. **Automatische Planung** als Ziel
4. **Reporting-Fokus**

### ❌ Was PROBLEMATISCH ist:
1. **Redundanz:** Viele vorgeschlagene Features existieren bereits
2. **Veraltete Architektur:** Shortcode-Namen passen nicht (my_sp_* statt bookando_*)
3. **Tabellennamen:** Vorgeschlagene Namen kollidieren mit Konvention
4. **Fehlende Integration:** Keine Berücksichtigung der bestehenden Multi-Tenant-Architektur
5. **Überkomplexität:** Zu viele separate Tabellen statt Nutzung bestehender Strukturen

### 🎯 Meine Empfehlungen:

#### 1. **NICHT implementieren:**
- ❌ Neue Zeiterfassungs-Tabelle (existiert bereits)
- ❌ Neue Abwesenheits-Tabelle (existiert bereits)
- ❌ Neue Kursinstanzen-Tabelle (nutze event_periods)
- ❌ Neue Shortcodes (nutze bestehende Portale)

#### 2. **ERWEITERN:**
- ✅ `time_entries` um Pausen-Tracking
- ✅ `employees_days_off` um Typen und Felder
- ✅ `events/event_periods` um automatische Generierung

#### 3. **NEU erstellen:**
- ✅ Schicht-Management (shifts, shift_templates)
- ✅ Urlaubskontingent (vacation_balances)
- ✅ Überstunden-Tracking (overtime_balances)
- ✅ Schicht-Präferenzen (employee_shift_preferences)
- ✅ Schicht-Anforderungen (shift_requirements)

#### 4. **Priorisierung:**
1. **Hoch:** Pausen-Tracking, Abwesenheitstypen, Schicht-Basis
2. **Mittel:** Automatische Schichtplanung, Kursplanung, Reporting
3. **Niedrig:** Schicht-Tausch, Smart Scheduling, Analytics

---

## 🚀 Nächste Schritte

Ich empfehle, mit **Phase 1** zu beginnen:

1. **Pausen-Tracking implementieren** (sofort umsetzbar, hoher Wert)
2. **Abwesenheitstypen hinzufügen** (kleine Änderung, große Wirkung)
3. **Schicht-Tabellen erstellen** (Grundlage für alles Weitere)

Möchten Sie, dass ich mit der Implementierung beginne? Ich würde vorschlagen:
- Migration für neue Tabellen schreiben
- Services implementieren
- Admin-UI anpassen
- Employee Portal erweitern

Soll ich starten?
