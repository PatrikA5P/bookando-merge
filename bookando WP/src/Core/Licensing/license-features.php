<?php
/**
 * Bookando Lizenz-Feature-Mapping (Master-Architektur 2025, SaaS-Ready)
 * 
 * Jede Änderung hier steuert zentral die Pläne für Plugin & SaaS!
 *
 * - "modules": Echte Funktionseinheiten mit eigener Tabelle, API, UI.
 * - "features": Optionale Erweiterungen, Integrationen, Zusatzschalter (kein eigenes Modul!).
 * - Docstrings bei jedem Eintrag möglich (für generate-module.js und UI).
 * - Keine Oberkategorien mehr, alles granular und einzeln.
 */

return [
    'plans' => [
        'starter' => [
            // Doc: Entry für kleine Anbieter (CRM, Booking, Services, Events, Finanzen)
            'modules' => [
                'settings',         // Globale Einstellungen
                'customers',        // Customer CRM, User-Daten, Profile, Felder            
                'employees',        // Mitarbeitende, Trainer, Lehrer, Rollen, Abwesenheiten, KalenderSync
                'locations',        // Standorte, Räume, Adressen, Zuordnung zu Services, Events
                'services',         // Dienstleistungen, Typen, Preise, Dauer, Extras, Zuordnung zu Mitarbeitenden
                'resources',        // Räume, Geräte, Fahrzeuge, Inventar, Buchung/Reservierung
                'events',           // Kurse, Seminare, Events, Veranstaltungsdaten, Buchungen, Warteliste, Tickets
                'appointments',     // Einzelbuchungen, Terminverwaltung, CustomFields, Zuordnung zu Ressourcen/Services
                'packages',         // Servicepakete, Bundles, Abo-Modelle, Zuordnung zu Kunden, Services
                'payments',         // Zahlungen, Transaktionen, Refunds, Zahlungsarten, Gateways
                'invoices',         // Rechnungen, Gutschriften, Steuerdaten, PDF-Export, Status
                'discounts',        // Gutscheine, Rabattcodes, Coupons, Limitierungen, Anwendung auf Buchungen
                'notifications',    // E-Mail, SMS, WhatsApp, Vorlagen, Triggers, Logs, Reminders
                'custom_fields',    // Benutzerdefinierte Felder (Mapping zu Modulen)
                'analytics',        // Statistiken, Berichte, Auswertungen (DataView/BI)
                'reports',          // CSV, PDF, Exporte, Custom-Reports (Admin, Manager)
            ],
            'features' => [
                'waitlist',               // Warteliste für Events, Services, Kunden (Feature innerhalb von Modulen)
                'calendar_sync',          // Google/Microsoft/Exchange/iCloud/ICS Sync (rw für OAuth; ICS ro)
                'feedback',               // Bewertungen/Rezensionen für Events, Services, Kunden
                'mobile_app',             // PWA/Mobile-App-Modus, Branding, App-Push
                'webhooks',               // Automatisierte Integrationen (zentral konfiguriert)
                'export_csv',             // CSV-Export für alle Module
                'analytics_basic',        // Basis-Dashboards & Auswertungen
                'basic_payments',         // Basis-Zahlungsarten
                'online_payment',         // Stripe, Paypal, Mollie, weitere Gateways
                'integration_zoom',       // Zoom-Integration für Online-Events
                'integration_meet',       // Google Meet-Integration
                'integration_teams',      // MS Teams-Integration
                'rest_api_read',          // Lesender REST-API-Zugriff
                'notifications_whatsapp', // WhatsApp-Kommunikation (Triggers)
            ],
        ],

        'pro' => [
            // Doc: Teams, Studios, Fitness, größere Businesses – alles aus Starter + Profi-Funktionen
            'modules' => [
                '@starter', // Alle Starter-Module enthalten, keine neuen eigenen Module hier
            ],
            'features' => [
                '@starter',              // Alle Starter-Features inklusive
                'multi_tenant',          // Mandantenfähigkeit für SaaS/Cloud
                'white_label',           // Eigenes Branding, Logos, Farben
                'rest_api_write',        // Schreibender REST-API-Zugriff
                'cross_tenant_share',    // Mandantenübergreifende Freigaben (ACL + Token)
                'export_pdf',            // PDF-Export
                'multi_calendar',        // Mehrere Kalender & Kalenderansichten
                'refunds',               // Rückerstattungen für Zahlungen
                'user_roles',            // Benutzerdefinierte Rollen/Capabilities
                'custom_reports',        // Individuelle Reports/BI
                'priority_support',      // Priorisierter Support
                'analytics_advanced',    // Erweiterte Statistiken und Berichte
            ],
        ],

        'academy' => [
            // Doc: Schulen, Fitness, Coaching, Ausbildung, Lerninhalte – erweitert um Education-Module
            'modules' => [
                '@pro',
                'education_cards',      // Ausbildungskarte, Trainingsplan, Skillmatrix, Fortschritt
                'learning_materials',   // Materialien, Files, Videos, Anleitungen
                'tests',               // Quiz, Prüfungen, Ergebnis-Tracking
                'training_plans',      // Trainingspläne, Fortschrittstracking
                'document_upload',     // Dokumentenupload/-verwaltung, ggf. mit externer Cloud-Integration
            ],
            'features' => [
                '@pro',
                'student_offline',         // Offline-Sync, PWA-Cache für Ausbildungskarte
                'competence_matrix',       // Skillmatrix/Kompetenzprofile
                'grade_export',            // Noten-/Leistungs-Export
                'school_custom_features',  // Individuelle School-/Fitness-/Coaching-Addons
                'digital_report',          // Digitaler Lern-/Trainingsreport
                'progress_tracking',       // Fortschritts-/Trainingsdaten für Academy/Fitness
            ],
        ],

        'enterprise' => [
            // Doc: Franchise, Enterprise, Multi-Domain, Top-Support
            'modules' => [
                '@academy'
            ],
            'features' => [
                '@academy',
                'advanced_security',   // 2FA, Audit, Enterprise-Security
                'sso',                 // Single Sign-On (OIDC/SAML)
                'unlimited_domains',   // Keine Domain-Beschränkung
            ],
        ],
    ],
];
// Jede neue Funktion/Funktionseinheit bitte zuerst als Feature anlegen,
// echte, datengetriebene Einheiten als eigenes Modul.
// Docstrings werden vom Scaffold, von Doku und Lizenz-API genutzt.
