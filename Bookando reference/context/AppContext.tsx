
import React, { createContext, useContext, useState, ReactNode, useEffect } from 'react';
import { ModuleName, Role, ModulePermission, Badge, SystemAlert, Course, Lesson, CourseType, CourseVisibility, DifficultyLevel, OfferTag, OfferExtra, OfferCategory, Location, Room, Equipment, DynamicPricingRule, ServiceItem, BundleItem, VoucherItem, Customer, CustomerStatus, Appointment, AppointmentStatus, Invoice, InvoiceStatus, DunningLevel, VatRate, InvoiceTemplate, CompanySettings, FieldGroup, FormTemplate } from '../types';

type Language = 'en' | 'de' | 'fr' | 'it';

const translations: Record<Language, Record<string, string>> = {
  en: {
    [ModuleName.DASHBOARD]: 'Dashboard',
    [ModuleName.CUSTOMERS]: 'Customers',
    [ModuleName.EMPLOYEES]: 'Employees',
    [ModuleName.APPOINTMENTS]: 'Appointments',
    [ModuleName.FINANCE]: 'Finance',
    [ModuleName.OFFERS]: 'Offers',
    [ModuleName.ACADEMY]: 'Academy',
    [ModuleName.RESOURCES]: 'Resources',
    [ModuleName.WORKDAY]: 'Workday',
    [ModuleName.PARTNER_HUB]: 'Partner Hub',
    [ModuleName.TOOLS]: 'Tools',
    [ModuleName.DESIGN_SYSTEM]: 'Design System',
    [ModuleName.DESIGN_FRONTEND]: 'Design Frontend',
    [ModuleName.SETTINGS]: 'Settings',
    'welcome': 'Welcome back',
    'search': 'Search...',
    'save_changes': 'Save Changes',
    'cancel': 'Cancel',
    'edit': 'Edit',
    'delete': 'Delete',
    'add': 'Add',
    'status': 'Status',
    'actions': 'Actions',
    'employees_title': 'Employees',
    'employees_subtitle': 'Manage staff profiles, access, and assignments.',
    'add_employee': 'Add Employee',
    'search_employees': 'Search employees...',
    'export': 'Export',
    'joined': 'Joined',
    'personal_info': 'Personal Information',
    'first_name': 'First Name',
    'last_name': 'Last Name',
    'email': 'Email',
    'phone': 'Phone',
    'gender': 'Gender',
    'birthday': 'Birthday',
    'address': 'Address',
    'employment': 'Employment',
    'position': 'Position',
    'department': 'Department',
    'role': 'System Role',
    'hire_date': 'Hire Date',
    'hr_access': 'HR & Access',
    'tools_title': 'Toolbox',
    'tools_subtitle': 'Utilities & Management',
    'reports_analytics': 'Reports & Analytics',
    'course_planner': 'Course Planner',
    'booking_forms': 'Booking Forms',
    'notifications': 'Notifications',
    'system_tools': 'System Tools',
    'reports_center': 'Reports Center',
    'configure': 'Configure',
    'new_report': 'New Report',
    'settings_title': 'Settings',
    'settings_subtitle': 'System Configuration',
    'general': 'General',
    'company_details': 'Company Details',
    'integrations': 'Integrations',
    'license_plan': 'License & Plan',
    'modules': 'Modules',
    'roles_permissions': 'Roles & Permissions',
    'system_language': 'System Language',
    'timezone': 'Timezone',
    'date_format': 'Date Format',
    'currency': 'Primary Currency',
    'create_role': 'Create Role',
    'role_name': 'Role Name',
    'active_users': 'active users',
    'edit_permissions': 'Edit Permissions',
    'module': 'Module',
    'view': 'View',
    'create_edit': 'Create/Edit',
    'delete_perm': 'Delete',
    'save_permissions': 'Save Permissions',
    'module_config': 'Module Configuration',
    'module_desc': 'Enable or disable features to customize your workspace.',
    // Dashboard
    'overview': 'Overview',
    'overview_subtitle': 'Your customizable command center.',
    'customize': 'Customize',
    'done': 'Done',
    'manage_widgets': 'Manage Widgets',
    'reorder_hint': 'Use the arrows to reorder visible widgets.',
    'no_widgets': 'No widgets selected. Click "Customize" to add some.',
    'revenue_analytics': 'Revenue Analytics',
    'weekly_appointments': 'Weekly Appointments',
    'recent_activity': 'Recent Activity',
    'next_up': 'Next Up',
    'total_revenue': 'Total Revenue',
    'active_customers': 'Active Customers',
    'appointments': 'Appointments',
    'avg_session_time': 'Avg. Session Time',
    'vs_last_month': 'vs last month',
    'infocenter': 'Infocenter',
    'all_clear': 'All Clear',
    'no_alerts': 'No system alerts or warnings.',
    'new': 'New',
    'action_booked': 'booked an appointment',
    'action_paid': 'paid invoice',
    'action_connection': 'requested connection',
    'action_backup': 'Weekly backup completed',
  },
  de: {
    [ModuleName.DASHBOARD]: 'Übersicht',
    [ModuleName.CUSTOMERS]: 'Kunden',
    [ModuleName.EMPLOYEES]: 'Mitarbeiter',
    [ModuleName.APPOINTMENTS]: 'Termine',
    [ModuleName.FINANCE]: 'Finanzen',
    [ModuleName.OFFERS]: 'Angebote',
    [ModuleName.ACADEMY]: 'Akademie',
    [ModuleName.RESOURCES]: 'Ressourcen',
    [ModuleName.WORKDAY]: 'Arbeitszeit',
    [ModuleName.PARTNER_HUB]: 'Partner Hub',
    [ModuleName.TOOLS]: 'Werkzeuge',
    [ModuleName.DESIGN_SYSTEM]: 'Design System',
    [ModuleName.DESIGN_FRONTEND]: 'Design Frontend',
    [ModuleName.SETTINGS]: 'Einstellungen',
    'welcome': 'Willkommen zurück',
    'search': 'Suchen...',
    'save_changes': 'Speichern',
    'cancel': 'Abbrechen',
    'edit': 'Bearbeiten',
    'delete': 'Löschen',
    'add': 'Hinzufügen',
    'status': 'Status',
    'actions': 'Aktionen',
    'employees_title': 'Mitarbeiter',
    'employees_subtitle': 'Verwalten Sie Personalprofile, Zugriffe und Aufgaben.',
    'add_employee': 'Mitarbeiter anlegen',
    'search_employees': 'Mitarbeiter suchen...',
    'export': 'Exportieren',
    'joined': 'Dabei seit',
    'personal_info': 'Persönliche Daten',
    'first_name': 'Vorname',
    'last_name': 'Nachname',
    'email': 'E-Mail',
    'phone': 'Telefon',
    'gender': 'Geschlecht',
    'birthday': 'Geburtstag',
    'address': 'Adresse',
    'employment': 'Beschäftigung',
    'position': 'Position',
    'department': 'Abteilung',
    'role': 'System-Rolle',
    'hire_date': 'Eintrittsdatum',
    'hr_access': 'HR & Zugriff',
    'tools_title': 'Werkzeugkasten',
    'tools_subtitle': 'Dienstprogramme & Verwaltung',
    'reports_analytics': 'Berichte & Analysen',
    'course_planner': 'Kursplaner',
    'booking_forms': 'Buchungsformulare',
    'notifications': 'Benachrichtigungen',
    'system_tools': 'System-Tools',
    'reports_center': 'Berichtszentrale',
    'configure': 'Konfigurieren',
    'new_report': 'Neuer Bericht',
    'settings_title': 'Einstellungen',
    'settings_subtitle': 'Systemkonfiguration',
    'general': 'Allgemein',
    'company_details': 'Firmendaten',
    'integrations': 'Integrationen',
    'license_plan': 'Lizenz & Plan',
    'modules': 'Module',
    'roles_permissions': 'Rollen & Rechte',
    'system_language': 'Systemsprache',
    'timezone': 'Zeitzone',
    'date_format': 'Datumsformat',
    'currency': 'Hauptwährung',
    'create_role': 'Rolle erstellen',
    'role_name': 'Rollenname',
    'active_users': 'aktive Nutzer',
    'edit_permissions': 'Rechte bearbeiten',
    'module': 'Modul',
    'view': 'Ansehen',
    'create_edit': 'Erstellen/Bearb.',
    'delete_perm': 'Löschen',
    'save_permissions': 'Rechte speichern',
    'module_config': 'Modul-Konfiguration',
    'module_desc': 'Aktivieren oder deaktivieren Sie Funktionen für Ihren Arbeitsbereich.',
    // Dashboard
    'overview': 'Übersicht',
    'overview_subtitle': 'Ihre anpassbare Kommandozentrale.',
    'customize': 'Anpassen',
    'done': 'Fertig',
    'manage_widgets': 'Widgets verwalten',
    'reorder_hint': 'Nutzen Sie die Pfeile, um die Widgets neu anzuordnen.',
    'no_widgets': 'Keine Widgets ausgewählt. Klicken Sie auf "Anpassen", um welche hinzuzufügen.',
    'revenue_analytics': 'Umsatzanalyse',
    'weekly_appointments': 'Wöchentliche Termine',
    'recent_activity': 'Letzte Aktivitäten',
    'next_up': 'Nächste Termine',
    'total_revenue': 'Gesamtumsatz',
    'active_customers': 'Aktive Kunden',
    'appointments': 'Termine',
    'avg_session_time': 'Ø Sitzungsdauer',
    'vs_last_month': 'vs. Vormonat',
    'infocenter': 'Infocenter',
    'all_clear': 'Alles Klar',
    'no_alerts': 'Keine Systemwarnungen oder Hinweise.',
    'new': 'Neu',
    'action_booked': 'hat einen Termin gebucht',
    'action_paid': 'hat Rechnung bezahlt',
    'action_connection': 'hat Verbindung angefragt',
    'action_backup': 'Wöchentliches Backup abgeschlossen',
  },
  fr: {
    [ModuleName.DASHBOARD]: 'Tableau de bord',
    [ModuleName.CUSTOMERS]: 'Clients',
    [ModuleName.EMPLOYEES]: 'Employés',
    [ModuleName.APPOINTMENTS]: 'Rendez-vous',
    [ModuleName.FINANCE]: 'Finance',
    [ModuleName.OFFERS]: 'Offres',
    [ModuleName.ACADEMY]: 'Académie',
    [ModuleName.RESOURCES]: 'Ressources',
    [ModuleName.WORKDAY]: 'Journée',
    [ModuleName.PARTNER_HUB]: 'Partenaires',
    [ModuleName.TOOLS]: 'Outils',
    [ModuleName.DESIGN_SYSTEM]: 'Système de Design',
    [ModuleName.DESIGN_FRONTEND]: 'Design Frontend',
    [ModuleName.SETTINGS]: 'Paramètres',
    'welcome': 'Bon retour',
    'search': 'Rechercher...',
    'save_changes': 'Enregistrer',
    'cancel': 'Annuler',
    'edit': 'Modifier',
    'delete': 'Supprimer',
    'add': 'Ajouter',
    'status': 'Statut',
    'actions': 'Actions',
    'employees_title': 'Employés',
    'employees_subtitle': 'Gérer les profils, les accès et les affectations.',
    'add_employee': 'Ajouter un employé',
    'search_employees': 'Rechercher...',
    'export': 'Exporter',
    'joined': 'Rejoint le',
    'personal_info': 'Informations personnelles',
    'first_name': 'Prénom',
    'last_name': 'Nom',
    'email': 'E-mail',
    'phone': 'Téléphone',
    'gender': 'Genre',
    'birthday': 'Anniversaire',
    'address': 'Adresse',
    'employment': 'Emploi',
    'position': 'Poste',
    'department': 'Département',
    'role': 'Rôle système',
    'hire_date': 'Date d\'embauche',
    'hr_access': 'RH et Accès',
    'tools_title': 'Boîte à outils',
    'tools_subtitle': 'Utilitaires & Gestion',
    'reports_analytics': 'Rapports & Analyses',
    'course_planner': 'Planificateur',
    'booking_forms': 'Formulaires',
    'notifications': 'Notifications',
    'system_tools': 'Outils système',
    'reports_center': 'Centre de rapports',
    'configure': 'Configurer',
    'new_report': 'Nouveau rapport',
    'settings_title': 'Paramètres',
    'settings_subtitle': 'Configuration du système',
    'general': 'Général',
    'company_details': 'Détails entreprise',
    'integrations': 'Intégrations',
    'license_plan': 'Licence & Plan',
    'modules': 'Modules',
    'roles_permissions': 'Rôles & Permissions',
    'system_language': 'Langue système',
    'timezone': 'Fuseau horaire',
    'date_format': 'Format de date',
    'currency': 'Devise principale',
    'create_role': 'Créer un rôle',
    'role_name': 'Nom du rôle',
    'active_users': 'utilisateurs actifs',
    'edit_permissions': 'Modifier permissions',
    'module': 'Module',
    'view': 'Voir',
    'create_edit': 'Créer/Modifier',
    'delete_perm': 'Supprimer',
    'save_permissions': 'Enregistrer',
    'module_config': 'Configuration des modules',
    'module_desc': 'Activez ou désactivez des fonctionnalités.',
    // Dashboard
    'overview': 'Vue d\'ensemble',
    'overview_subtitle': 'Votre centre de commande personnalisable.',
    'customize': 'Personnaliser',
    'done': 'Terminé',
    'manage_widgets': 'Gérer les widgets',
    'reorder_hint': 'Utilisez les flèches pour réorganiser les widgets.',
    'no_widgets': 'Aucun widget sélectionné. Cliquez sur "Personnaliser" pour en ajouter.',
    'revenue_analytics': 'Analyse des revenus',
    'weekly_appointments': 'Rendez-vous hebdomadaires',
    'recent_activity': 'Activité récente',
    'next_up': 'À venir',
    'total_revenue': 'Revenu total',
    'active_customers': 'Clients actifs',
    'appointments': 'Rendez-vous',
    'avg_session_time': 'Durée moy. session',
    'vs_last_month': 'vs mois dernier',
    'infocenter': 'Infocentre',
    'all_clear': 'Tout est clair',
    'no_alerts': 'Aucune alerte système.',
    'new': 'Nouveau',
    'action_booked': 'a pris rendez-vous',
    'action_paid': 'a payé la facture',
    'action_connection': 'a demandé une connexion',
    'action_backup': 'Sauvegarde hebdomadaire terminée',
  },
  it: {
    [ModuleName.DASHBOARD]: 'Cruscotto',
    [ModuleName.CUSTOMERS]: 'Clienti',
    [ModuleName.EMPLOYEES]: 'Dipendenti',
    [ModuleName.APPOINTMENTS]: 'Appuntamenti',
    [ModuleName.FINANCE]: 'Finanza',
    [ModuleName.OFFERS]: 'Offerte',
    [ModuleName.ACADEMY]: 'Accademia',
    [ModuleName.RESOURCES]: 'Risorse',
    [ModuleName.WORKDAY]: 'Giorno',
    [ModuleName.PARTNER_HUB]: 'Partner',
    [ModuleName.TOOLS]: 'Strumenti',
    [ModuleName.DESIGN_SYSTEM]: 'Sistema di Design',
    [ModuleName.DESIGN_FRONTEND]: 'Design Frontend',
    [ModuleName.SETTINGS]: 'Impostazioni',
    'welcome': 'Bentornato',
    'search': 'Cerca...',
    'save_changes': 'Salva modifiche',
    'cancel': 'Annulla',
    'edit': 'Modifica',
    'delete': 'Elimina',
    'add': 'Aggiungi',
    'status': 'Stato',
    'actions': 'Azioni',
    'employees_title': 'Dipendenti',
    'employees_subtitle': 'Gestisci profili, accessi e assegnazioni.',
    'add_employee': 'Aggiungi dipendente',
    'search_employees': 'Cerca dipendenti...',
    'export': 'Esporta',
    'joined': 'Iscritto dal',
    'personal_info': 'Informazioni personali',
    'first_name': 'Nome',
    'last_name': 'Cognome',
    'email': 'Email',
    'phone': 'Telefono',
    'gender': 'Genere',
    'birthday': 'Compleanno',
    'address': 'Indirizzo',
    'employment': 'Impiego',
    'position': 'Posizione',
    'department': 'Dipartimento',
    'role': 'Ruolo di sistema',
    'hire_date': 'Data di assunzione',
    'hr_access': 'HR e Accesso',
    'tools_title': 'Cassetta attrezzi',
    'tools_subtitle': 'Utilità e Gestione',
    'reports_analytics': 'Rapporti & Analisi',
    'course_planner': 'Pianificatore',
    'booking_forms': 'Moduli di prenotazione',
    'notifications': 'Notifiche',
    'system_tools': 'Strumenti di sistema',
    'reports_center': 'Centro rapporti',
    'configure': 'Configura',
    'new_report': 'Nuovo rapporto',
    'settings_title': 'Impostazioni',
    'settings_subtitle': 'Configurazione del sistema',
    'general': 'Generale',
    'company_details': 'Dettagli azienda',
    'integrations': 'Integrazioni',
    'license_plan': 'Licenza & Piano',
    'modules': 'Moduli',
    'roles_permissions': 'Ruoli & Permessi',
    'system_language': 'Lingua del sistema',
    'timezone': 'Fuso orario',
    'date_format': 'Formato data',
    'currency': 'Valuta principale',
    'create_role': 'Crea ruolo',
    'role_name': 'Nome del ruolo',
    'active_users': 'utenti attivi',
    'edit_permissions': 'Modifica permessi',
    'module': 'Modulo',
    'view': 'Visualizza',
    'create_edit': 'Crea/Modifica',
    'delete_perm': 'Elimina',
    'save_permissions': 'Salva permessi',
    'module_config': 'Configurazione moduli',
    'module_desc': 'Abilita o disabilita le funzionalità.',
    // Dashboard
    'overview': 'Panoramica',
    'overview_subtitle': 'Il tuo centro di comando personalizzabile.',
    'customize': 'Personalizza',
    'done': 'Fatto',
    'manage_widgets': 'Gestisci Widget',
    'reorder_hint': 'Usa le frecce per riordinare i widget.',
    'no_widgets': 'Nessun widget selezionato. Clicca "Personalizza" per aggiungerne.',
    'revenue_analytics': 'Analisi Ricavi',
    'weekly_appointments': 'Appuntamenti Settimanali',
    'recent_activity': 'Attività Recenti',
    'next_up': 'Prossimi',
    'total_revenue': 'Entrate Totali',
    'active_customers': 'Clienti Attivi',
    'appointments': 'Appuntamenti',
    'avg_session_time': 'Durata media sessione',
    'vs_last_month': 'vs mese scorso',
    'infocenter': 'Infocenter',
    'all_clear': 'Tutto tranquillo',
    'no_alerts': 'Nessun avviso di sistema.',
    'new': 'Nuovo',
    'action_booked': 'ha prenotato un appuntamento',
    'action_paid': 'ha pagato la fattura',
    'action_connection': 'ha richiesto connessione',
    'action_backup': 'Backup settimanale completato',
  }
};

const initialRoles: Role[] = [
  { 
    id: 'admin', 
    name: 'Administrator', 
    permissions: Object.values(ModuleName).reduce((acc, mod) => ({...acc, [mod]: {read: true, write: true, delete: true}}), {})
  },
  { 
    id: 'manager', 
    name: 'Manager', 
    permissions: Object.values(ModuleName).reduce((acc, mod) => ({...acc, [mod]: {read: true, write: true, delete: false}}), {})
  },
  { 
    id: 'employee', 
    name: 'Employee', 
    permissions: {
      [ModuleName.DASHBOARD]: {read: true, write: false, delete: false},
      [ModuleName.WORKDAY]: {read: true, write: true, delete: false},
      [ModuleName.APPOINTMENTS]: {read: true, write: false, delete: false},
    }
  },
  { 
    id: 'accountant', 
    name: 'Accountant', 
    permissions: {
       [ModuleName.FINANCE]: {read: true, write: true, delete: true},
       [ModuleName.DASHBOARD]: {read: true, write: false, delete: false},
    }
  }
];

const initialBadges: Badge[] = [
  { id: 'b1', name: 'Safety Level 1', color: 'bg-emerald-100 text-emerald-800', icon: 'Shield' },
  { id: 'b2', name: 'Advanced Yoga', color: 'bg-purple-100 text-purple-800', icon: 'Activity' },
  { id: 'b3', name: 'First Aid', color: 'bg-rose-100 text-rose-800', icon: 'Heart' },
  { id: 'b4', name: 'Intro Completed', color: 'bg-blue-100 text-blue-800', icon: 'Check' },
];

const initialAlerts: SystemAlert[] = [
  { 
    id: 'a1', 
    type: 'warning', 
    title: 'Booking Chain Broken', 
    message: 'Customer Alice Freeman had a booking for "Advanced Yoga" cancelled, but it was a prerequisite for "Yoga Masterclass" next week.', 
    timestamp: '2023-10-24 10:30', 
    acknowledged: false,
    relatedCustomerId: 'c1' 
  }
];

const initialOfferCategories: OfferCategory[] = [
    { id: '1', name: 'Wellness', color: '#ec4899', image: 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=80&w=200' },
    { id: '2', name: 'Fitness', color: '#3b82f6', image: 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&q=80&w=200' },
    { id: '3', name: 'Online Courses', color: '#8b5cf6', image: 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?auto=format&fit=crop&q=80&w=200' },
    { id: '4', name: 'Health', color: '#10b981', image: 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&q=80&w=200' }
];

const initialCourses: Course[] = [
  { 
    id: '1', 
    title: 'Advanced Sales Techniques', 
    description: 'Master the art of closing deals with modern strategies.', 
    type: CourseType.ONLINE, 
    author: 'Sarah Jenkins',
    visibility: CourseVisibility.INTERNAL,
    category: 'Sales',
    tags: ['Sales', 'Communication', 'B2B'],
    difficulty: DifficultyLevel.ADVANCED,
    coverImage: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&q=80&w=800',
    studentsCount: 124,
    published: true,
    certificate: { enabled: true, templateId: 'default', showScore: true, signatureText: 'CEO John Doe' },
    curriculum: [
      {
        id: 't1', title: 'Introduction to Sales', summary: 'Basics of the modern sales funnel.', items: [
          { id: 'l1', type: 'lesson', title: 'The New Customer Journey', content: 'Content goes here...', mediaUrls: [], fileAttachments: [] },
          { id: 'q1', type: 'quiz', title: 'Module 1 Checkpoint', summary: 'Test your knowledge', questions: [], settings: { allowedAttempts: 3, passingScore: 80, shuffleQuestions: true, layout: 'Single Page', feedbackMode: 'Immediate' } }
        ]
      }
    ]
  },
  { 
    id: '2', 
    title: 'Workplace Safety 101', 
    description: 'Essential safety protocols for all employees.', 
    type: CourseType.ONLINE, 
    author: 'Compliance Dept',
    visibility: CourseVisibility.INTERNAL,
    category: 'Compliance',
    tags: ['Safety', 'Mandatory'],
    difficulty: DifficultyLevel.BEGINNER,
    coverImage: 'https://images.unsplash.com/photo-1581092334651-ddf26d9a09d0?auto=format&fit=crop&q=80&w=800',
    studentsCount: 450,
    published: true,
    certificate: { enabled: true, templateId: 'safety_cert', showScore: false, signatureText: 'Safety Officer' },
    curriculum: []
  }
];

const initialLessons: Lesson[] = [
    { id: 'l1', type: 'lesson', title: 'The New Customer Journey', content: 'Lesson Content...', mediaUrls: [], fileAttachments: [] },
    { id: 'l2', type: 'lesson', title: 'Safety First: Protocols', content: 'Lesson Content...', mediaUrls: [], fileAttachments: [] },
    { id: 'l3', type: 'lesson', title: 'Emergency Exits', content: 'Lesson Content...', mediaUrls: [], fileAttachments: [] },
];

const initialOfferTags: OfferTag[] = [
    { id: 't1', name: 'Popular', color: 'bg-rose-100 text-rose-700' },
    { id: 't2', name: 'New', color: 'bg-emerald-100 text-emerald-700' },
    { id: 't3', name: 'Intensive', color: 'bg-purple-100 text-purple-700' },
    { id: 't4', name: 'Relaxing', color: 'bg-blue-100 text-blue-700' },
];

const initialOfferExtras: OfferExtra[] = [
    { id: 'ex1', name: 'Towel Rental', description: 'Fresh premium towel', price: 5, priceType: 'Fixed', currency: 'CHF' },
    { id: 'ex2', name: 'Mat Rental', description: 'Pro Yoga Mat', price: 3, priceType: 'Fixed', currency: 'CHF' },
    { id: 'ex3', name: 'Cancellation Insurance', description: 'Full refund if cancelled', price: 10, priceType: 'Percentage', currency: 'CHF' },
];

const initialPricingRules: DynamicPricingRule[] = [
    { 
      id: 'pr1', name: 'Early Bird Special', type: 'EarlyBird', active: true, 
      roundingValue: 0.05, roundingMethod: 'Nearest', priceEnding: 'None', 
      maxIncreasePercent: 50, maxDecreasePercent: 30,
      tiers: [
        { id: 't1', conditionValue: 60, conditionUnit: 'Days', adjustmentValue: -20, adjustmentType: 'Percentage', limitValue: 30 },
        { id: 't2', conditionValue: 30, conditionUnit: 'Days', adjustmentValue: -10, adjustmentType: 'Percentage', limitValue: 50 }
      ]
    },
    { 
      id: 'pr2', name: 'Last Minute Surge', type: 'LastMinute', active: true, 
      roundingValue: 1.0, roundingMethod: 'Up', priceEnding: '.99',
      tiers: [
        { id: 't1', conditionValue: 24, conditionUnit: 'Hours', adjustmentValue: 15, adjustmentType: 'Percentage', limitValue: 80 },
        { id: 't2', conditionValue: 2, conditionUnit: 'Hours', adjustmentValue: 25, adjustmentType: 'Percentage', limitValue: 0 }
      ]
    },
    { 
      id: 'pr3', name: 'Peak Season Summer', type: 'Season', active: false, 
      roundingValue: 0.05, roundingMethod: 'Nearest',
      seasonalRules: [
          { 
            id: 's1', 
            type: 'Range', 
            startDate: '2024-06-01', 
            endDate: '2024-08-31', 
            name: 'Summer Weekends',
            dayConfigs: {
                'Sat': { active: true, slots: [{ startTime: '09:00', endTime: '18:00', adjustmentValue: 20, adjustmentType: 'Percentage' }] },
                'Sun': { active: true, slots: [{ startTime: '09:00', endTime: '18:00', adjustmentValue: 20, adjustmentType: 'Percentage' }] }
            }
          },
          { id: 's2', type: 'SpecificDate', specificDate: '2024-08-01', adjustmentValue: 50, adjustmentType: 'Percentage', name: 'National Holiday' }
      ]
    },
];

const initialServices: ServiceItem[] = [
  { 
    id: '1', 
    title: 'Deep Tissue Massage', 
    description: 'Intensive massage therapy targeting deep muscle layers.',
    category: 'Wellness', 
    categories: ['Wellness'],
    tags: ['Massage', 'Relaxation'],
    duration: 60, 
    price: 120,
    currency: 'CHF', 
    productCode: 'SVC-001',
    vatEnabled: true,
    vatRateSales: 8.1,
    salePrice: 0,
    image: 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&q=80&w=800',
    type: 'Service', 
    active: true,
    capacity: 1,
    dynamicPricing: 'Manual',
    pricingRuleId: 'pr1',
    gallery: [],
    requiredRooms: ['Room A'],
    defaultStatus: 'Confirmed',
    paymentOptions: ['Credit Card', 'On Site'],
    waitlistEnabled: true
  },
  { 
    id: '2', 
    title: 'Yoga for Beginners', 
    description: 'Learn the basics of Hatha Yoga in a supportive group setting.',
    category: 'Fitness', 
    categories: ['Fitness'],
    tags: ['Yoga', 'Group'],
    duration: 60, 
    price: 25,
    currency: 'CHF', 
    productCode: 'EVT-YOGA-01',
    vatEnabled: true,
    vatRateSales: 2.6,
    image: 'https://images.unsplash.com/photo-1599447421405-0e5a10c54688?auto=format&fit=crop&q=80&w=800',
    type: 'Event', 
    active: true,
    capacity: 15,
    eventStructure: 'Series_DropIn',
    sessions: [
        { id: 's1', date: '2023-11-01', startTime: '18:00', endTime: '19:00', instructorId: 'emp2', locationId: 'loc1', title: '', description: '', awardedBadges: [], requiredBadges: [] }
    ],
    organizerId: 'emp2',
    paymentOptions: ['Credit Card'],
    allowGroupBooking: true,
    maxGroupSize: 5
  },
  { 
    id: '3', 
    title: 'Nutrition Masterclass', 
    description: 'Complete guide to healthy eating available 24/7.',
    category: 'Health', 
    categories: ['Health', 'Online Courses'],
    tags: ['Nutrition', 'Online'],
    lessons: 12,
    price: 49, 
    currency: 'CHF', 
    productCode: 'DIGI-NUTRI',
    vatEnabled: false,
    image: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&q=80&w=800',
    type: 'Online Course', 
    active: true,
    integration: 'None',
    duration: 0 // Duration in minutes, 0 for self-paced
  }
];

const initialBundles: BundleItem[] = [
  { 
    id: 'b1', 
    title: 'Wellness Starter Pack', 
    items: ['Yoga for Beginners', 'Nutrition Masterclass'], 
    price: 149, 
    originalPrice: 174, 
    savings: 15,
    image: 'https://images.unsplash.com/photo-1545205597-3d9d02c29597?auto=format&fit=crop&q=80&w=800',
    active: true
  },
];

const initialVouchers: VoucherItem[] = [
  { 
      id: 'v1', title: 'Summer Sale 2023', category: 'Promotion', 
      code: 'SUMMER23', discountType: 'Percentage', discountValue: 15, 
      uses: 45, maxUses: 100, maxUsesPerCustomer: 1, expiry: '2023-12-31', status: 'Active' 
  },
  { 
      id: 'v2', title: 'New Customer Welcome', category: 'Promotion',
      code: 'WELCOME10', discountType: 'Fixed', discountValue: 10, 
      uses: 12, maxUses: null, maxUsesPerCustomer: 1, expiry: '2024-01-01', status: 'Active' 
  },
  {
      id: 'v3', title: 'General Gift Card', category: 'GiftCard',
      allowCustomAmount: true, minCustomAmount: 10, maxCustomAmount: 500,
      status: 'Active', image: 'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?auto=format&fit=crop&q=80&w=800'
  }
];

const initialLocations: Location[] = [
  { id: '1', name: 'Downtown Studio', address: '123 Main St, Metropolis', rooms: 4, status: 'Open' },
  { id: '2', name: 'Westside Branch', address: '456 West Ave, Gotham', rooms: 2, status: 'Open' },
];

const initialRooms: Room[] = [
  { id: 'r1', name: 'Yoga Hall A', location: 'Downtown Studio', capacity: 25, features: ['Mirrors', 'Sound System'], status: 'Available' },
  { id: 'r2', name: 'Consultation Room 1', location: 'Downtown Studio', capacity: 4, features: ['Whiteboard', 'Table'], status: 'In Use' },
  { id: 'r3', name: 'Spin Class Room', location: 'Westside Branch', capacity: 15, features: ['15 Bikes', 'AC'], status: 'Maintenance' },
];

const initialEquipment: Equipment[] = [
  { id: 'e1', name: 'Yoga Mat (Pro)', category: 'Fitness', total: 50, available: 42, condition: 'Good' },
  { id: 'e2', name: 'Projector 4K', category: 'Electronics', total: 2, available: 1, condition: 'Fair' },
  { id: 'e3', name: 'Massage Table', category: 'Furniture', total: 5, available: 5, condition: 'Good' },
];

const initialCustomers: Customer[] = [
  { 
    id: '1', firstName: 'Alice', lastName: 'Freeman', email: 'alice.f@example.com', phone: '+1 555-0101', 
    status: CustomerStatus.ACTIVE, street: '123 Maple St', zip: '10001', city: 'New York', country: 'USA', gender: 'Female', notes: 'VIP Client', 
    customFields: [{key: 'Allergy', value: 'Peanuts'}], birthday: '1985-04-12'
  },
  { 
    id: '2', firstName: 'Bob', lastName: 'Smith', email: 'bob.smith@example.com', phone: '+1 555-0102', 
    status: CustomerStatus.ACTIVE, street: '456 Oak Ave', zip: '8001', city: 'Zurich', country: 'Switzerland', gender: 'Male', customFields: [], birthday: '1990-08-23'
  },
  { 
    id: '3', firstName: 'Charlie', lastName: 'Brown', email: 'charlie@example.com', phone: '+1 555-0103', 
    status: CustomerStatus.BLOCKED, street: '789 Pine Ln', zip: '10002', city: 'New York', country: 'USA', gender: 'Male', notes: 'Missed 3 appointments', customFields: []
  },
  { 
    id: '4', firstName: 'Diana', lastName: 'Prince', email: 'diana@example.com', phone: '+1 555-0104', 
    status: CustomerStatus.ACTIVE, street: 'Palace Way 1', zip: '9999', city: 'Themyscira', country: 'Greece', gender: 'Female', customFields: [], birthday: '1992-01-15'
  },
];

const initialAppointments: Appointment[] = [
  { 
    id: '1', title: 'Deep Tissue Massage', customerId: '1', customerName: 'Alice Freeman', 
    employeeId: 'emp1', employeeName: 'Sarah Jenkins', serviceId: '1', serviceName: 'Deep Tissue Massage',
    category: 'Wellness', date: '2023-10-24', startTime: '09:00', endTime: '10:00', duration: 60,
    type: 'Service', location: 'Room A', status: 'Confirmed', price: 120 
  },
  { 
    id: '2', title: 'Physiotherapy Init', customerId: '2', customerName: 'Bob Smith', 
    employeeId: 'emp1', employeeName: 'Sarah Jenkins', serviceId: 's2', serviceName: 'Physiotherapy',
    category: 'Health', date: '2023-10-24', startTime: '10:30', endTime: '11:30', duration: 60,
    type: 'Service', location: 'Room B', status: 'Pending', price: 90 
  },
  { 
    id: '3', title: 'Yoga Group Class', customerId: '3', customerName: 'Charlie Brown', 
    employeeId: 'emp2', employeeName: 'Mike Ross', serviceId: '2', serviceName: 'Yoga Basics',
    category: 'Fitness', date: '2023-10-24', startTime: '18:00', endTime: '19:00', duration: 60,
    type: 'Course', location: 'Main Hall', status: 'Confirmed', price: 25 
  },
  { 
    id: '4', title: 'Follow Up', customerId: '4', customerName: 'Diana Prince', 
    employeeId: 'emp3', employeeName: 'Jessica Pearson', serviceId: 's4', serviceName: 'Consultation',
    category: 'Wellness', date: '2023-10-25', startTime: '14:00', endTime: '14:30', duration: 30,
    type: 'Service', location: 'Video Call', status: 'Confirmed', price: 60 
  },
  { 
    id: '5', title: 'Nutrition Plan', customerId: '1', customerName: 'Alice Freeman', 
    employeeId: 'emp3', employeeName: 'Jessica Pearson', serviceId: '3', serviceName: 'Nutrition',
    category: 'Health', date: '2023-10-25', startTime: '09:00', endTime: '10:00', duration: 60,
    type: 'Service', location: 'Room C', status: 'Completed', price: 150 
  },
];

// FINANCE DATA
const initialInvoices: Invoice[] = [
  { id: 'INV-2023-101', client: 'Acme Corp', category: 'Customer', amount: 1250.00, date: '2023-10-01', dueDate: '2023-10-31', status: InvoiceStatus.PAID, type: 'Invoice', dunningLevel: DunningLevel.NONE },
  { id: 'INV-2023-102', client: 'John Doe', category: 'Customer', amount: 150.00, date: '2023-10-05', dueDate: '2023-10-15', status: InvoiceStatus.OVERDUE, type: 'Invoice', dunningLevel: DunningLevel.REMINDER_2 },
  { id: 'BILL-9912', client: 'Office Supplies Co', category: 'Supplier', amount: -230.00, date: '2023-10-12', dueDate: '2023-11-12', status: InvoiceStatus.SENT, type: 'Expense', dunningLevel: DunningLevel.NONE },
  { id: 'BILL-9913', client: 'Cleaning Services', category: 'Supplier', amount: -450.00, date: '2023-10-14', dueDate: '2023-10-28', status: InvoiceStatus.PAID, type: 'Expense', dunningLevel: DunningLevel.NONE },
];

const initialVatRates: VatRate[] = [
  { id: 'BZB77', code: 'BZB77', description: 'Bezugsteuer Inv/BA', rate: 7.7, type: 'Bezugsteuer MWST Investitionen, uebriger Betriebsaufwand', formCode: '382', linkedAccountId: '1171', validFrom: '2017-07-01', validTo: '2023-06-30', active: false },
  { id: 'BZB81', code: 'BZB81', description: 'Bezugsteuer Inv/BA', rate: 8.1, type: 'Bezugsteuer MWST Investitionen, uebriger Betriebsaufwand', formCode: '383', linkedAccountId: '1171', validFrom: '2023-07-01', active: true },
  { id: 'BZM81', code: 'BZM81', description: 'Bezugsteuer Mat/DL', rate: 8.1, type: 'Bezugsteuer MWST Material, Waren, Dienstleistungen, Energie', formCode: '383', linkedAccountId: '1170', validFrom: '2023-07-01', active: true },
  { id: 'UN81', code: 'UN81', description: 'Umsatz (NS)', rate: 8.1, type: 'Geschuldete MWST (Umsatzsteuer)', formCode: '303', linkedAccountId: '2200', validFrom: '2023-07-01', active: true },
  { id: 'UR26', code: 'UR26', description: 'Umsatz (RS)', rate: 2.6, type: 'Geschuldete MWST (Umsatzsteuer)', formCode: '313', linkedAccountId: '2200', validFrom: '2023-07-01', active: true },
  { id: 'US38', code: 'US38', description: 'Umsatz (SS)', rate: 3.8, type: 'Geschuldete MWST (Umsatzsteuer)', formCode: '343', linkedAccountId: '2200', validFrom: '2023-07-01', active: true },
  { id: 'U00', code: 'U00', description: 'Ohne MWST', rate: 0.0, type: 'Geschuldete MWST (Umsatzsteuer)', formCode: '000', linkedAccountId: '2200', validFrom: '2000-01-01', active: true },
];

const initialInvoiceTemplates: InvoiceTemplate[] = [
    {
        id: 'tpl_default', name: 'Standard Invoice', isDefault: true,
        accentColor: '#0ea5e9', fontFamily: 'Inter',
        addressWindowPosition: 'Left',
        senderLine: 'Bookando Inc. • Bahnhofstrasse 10 • 8001 Zurich',
        senderBlock: '',
        introText: 'Thank you for your business. Please find the invoice details below.',
        outroText: 'Payment is due within 30 days. \n\nKind regards,\nBookando Team',
        footerColumn1: 'Bookando Inc.\nBahnhofstrasse 10\n8001 Zurich\nSwitzerland',
        footerColumn2: 'Bank: UBS Switzerland\nIBAN: CH10 0023 0000 1234 5678 9\nBIC: UBSWCHZH',
        footerColumn3: 'VAT: CHE-123.456.789 MWST\nCommercial Reg: CH-020.3.033.123-4\nContact: finance@bookando.com'
    }
];

const initialCompanySettings: CompanySettings = {
  name: 'Bookando Inc.',
  email: 'contact@bookando.com',
  phone: '+41 44 123 45 67',
  address: 'Bahnhofstrasse 10',
  zip: '8001',
  city: 'Zurich',
  country: 'Switzerland',
  vatId: 'CHE-123.456.789 MWST',
  bankName: 'UBS Switzerland',
  iban: 'CH10 0023 0000 1234 5678 9',
  qrIban: '', 
  besrId: '',
  qrReferenceType: 'QR'
};

// Initial Dynamic Fields (Example: Driving School)
const initialCustomerFieldDefinitions: FieldGroup[] = [
  {
    id: 'driving_license',
    title: 'Driving License Details',
    fields: [
      { id: 'license_no', label: 'License Number', type: 'text', required: true },
      { id: 'category', label: 'Category', type: 'select', options: ['A', 'A1', 'B', 'BE', 'C', 'D'], required: true },
      { id: 'expiry', label: 'Expiry Date', type: 'date', required: true }
    ]
  }
];

const initialFormTemplates: FormTemplate[] = [
    {
        id: 'ft_default',
        name: 'Standard Booking Form',
        active: true,
        elements: [
            { id: 'e1', label: 'License Number', type: 'text', source: 'linked', linkedFieldId: 'license_no', linkedGroupId: 'driving_license', required: true, width: 'full' },
            { id: 'e2', label: 'Any special requests?', type: 'textarea', source: 'custom', required: false, width: 'full' }
        ]
    }
];

interface AppContextType {
  language: Language;
  setLanguage: (lang: Language) => void;
  enabledModules: ModuleName[];
  toggleModule: (module: ModuleName) => void;
  t: (key: string) => string;
  formatPrice: (amount: number) => string;
  setSystemCurrency: (currency: string) => void;
  systemCurrency: string;
  roles: Role[];
  addRole: (role: Role) => void;
  updateRole: (role: Role) => void;
  deleteRole: (id: string) => void;
  badges: Badge[];
  addBadge: (badge: Badge) => void;
  deleteBadge: (id: string) => void;
  alerts: SystemAlert[];
  acknowledgeAlert: (id: string) => void;
  courses: Course[];
  setCourses: (courses: Course[]) => void;
  lessons: Lesson[];
  setLessons: (lessons: Lesson[]) => void;
  offerTags: OfferTag[];
  addOfferTag: (tag: OfferTag) => void;
  deleteOfferTag: (id: string) => void;
  offerCategories: OfferCategory[];
  addOfferCategory: (category: OfferCategory) => void;
  updateOfferCategory: (category: OfferCategory) => void;
  deleteOfferCategory: (id: string) => void;
  offerExtras: OfferExtra[];
  addOfferExtra: (extra: OfferExtra) => void;
  deleteOfferExtra: (id: string) => void;
  pricingRules: DynamicPricingRule[];
  setPricingRules: (rules: DynamicPricingRule[]) => void;
  locations: Location[];
  rooms: Room[];
  equipment: Equipment[];
  services: ServiceItem[];
  setServices: (services: ServiceItem[]) => void;
  bundles: BundleItem[];
  setBundles: (bundles: BundleItem[]) => void;
  vouchers: VoucherItem[];
  setVouchers: (vouchers: VoucherItem[]) => void;
  
  // Centralized Data
  customers: Customer[];
  addCustomer: (customer: Customer) => void;
  updateCustomer: (customer: Customer) => void;
  deleteCustomer: (id: string) => void;
  
  // Dynamic Customer Fields & Forms
  customerFieldDefinitions: FieldGroup[];
  setCustomerFieldDefinitions: (defs: FieldGroup[]) => void;
  bookingFormTemplates: FormTemplate[];
  addFormTemplate: (tpl: FormTemplate) => void;
  updateFormTemplate: (tpl: FormTemplate) => void;
  deleteFormTemplate: (id: string) => void;

  appointments: Appointment[];
  addAppointment: (apt: Appointment) => void;
  updateAppointment: (apt: Appointment) => void;
  deleteAppointment: (id: string) => void;

  // Finance
  invoices: Invoice[];
  addInvoice: (inv: Invoice) => void;
  updateInvoice: (inv: Invoice) => void;
  deleteInvoice: (id: string) => void;
  vatRates: VatRate[];
  setVatRates: (rates: VatRate[]) => void;
  generateInvoiceFromAppointment: (apt: Appointment) => void;
  
  // Templates & Settings
  invoiceTemplates: InvoiceTemplate[];
  addInvoiceTemplate: (tpl: InvoiceTemplate) => void;
  updateInvoiceTemplate: (tpl: InvoiceTemplate) => void;
  deleteInvoiceTemplate: (id: string) => void;
  companySettings: CompanySettings;
  updateCompanySettings: (settings: CompanySettings) => void;
}

const AppContext = createContext<AppContextType | undefined>(undefined);

export const AppProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [language, setLanguage] = useState<Language>('en');
  const [roles, setRoles] = useState<Role[]>(initialRoles);
  const [badges, setBadges] = useState<Badge[]>(initialBadges);
  const [alerts, setAlerts] = useState<SystemAlert[]>(initialAlerts);
  const [courses, setCourses] = useState<Course[]>(initialCourses);
  const [lessons, setLessons] = useState<Lesson[]>(initialLessons);
  const [offerTags, setOfferTags] = useState<OfferTag[]>(initialOfferTags);
  const [offerCategories, setOfferCategories] = useState<OfferCategory[]>(initialOfferCategories);
  const [offerExtras, setOfferExtras] = useState<OfferExtra[]>(initialOfferExtras);
  const [pricingRules, setPricingRules] = useState<DynamicPricingRule[]>(initialPricingRules);
  const [systemCurrency, setSystemCurrency] = useState('CHF'); 
  
  const [locations] = useState<Location[]>(initialLocations);
  const [rooms] = useState<Room[]>(initialRooms);
  const [equipment] = useState<Equipment[]>(initialEquipment);
  
  const [services, setServices] = useState<ServiceItem[]>(initialServices);
  const [bundles, setBundles] = useState<BundleItem[]>(initialBundles);
  const [vouchers, setVouchers] = useState<VoucherItem[]>(initialVouchers);
  
  const [customers, setCustomers] = useState<Customer[]>(initialCustomers);
  const [customerFieldDefinitions, setCustomerFieldDefinitions] = useState<FieldGroup[]>(initialCustomerFieldDefinitions);
  const [bookingFormTemplates, setBookingFormTemplates] = useState<FormTemplate[]>(initialFormTemplates);

  const [appointments, setAppointments] = useState<Appointment[]>(initialAppointments);
  
  const [invoices, setInvoices] = useState<Invoice[]>(initialInvoices);
  const [vatRates, setVatRates] = useState<VatRate[]>(initialVatRates);
  const [invoiceTemplates, setInvoiceTemplates] = useState<InvoiceTemplate[]>(initialInvoiceTemplates);
  const [companySettings, setCompanySettings] = useState<CompanySettings>(initialCompanySettings);

  const [enabledModules, setEnabledModules] = useState<ModuleName[]>([
    ModuleName.DASHBOARD,
    ModuleName.CUSTOMERS,
    ModuleName.EMPLOYEES,
    ModuleName.WORKDAY,
    ModuleName.FINANCE,
    ModuleName.OFFERS,
    ModuleName.ACADEMY,
    ModuleName.RESOURCES,
    ModuleName.PARTNER_HUB,
    ModuleName.TOOLS,
    ModuleName.DESIGN_SYSTEM,
    ModuleName.DESIGN_FRONTEND,
    ModuleName.SETTINGS
  ]);

  const toggleModule = (module: ModuleName) => {
    if (module === ModuleName.SETTINGS) return;
    setEnabledModules(prev => {
      if (prev.includes(module)) {
        return prev.filter(m => m !== module);
      } else {
        return [...prev, module];
      }
    });
  };

  const t = (key: string) => {
    return translations[language][key] || key;
  };

  const formatPrice = (amount: number) => {
    return new Intl.NumberFormat(language === 'de' ? 'de-CH' : language === 'fr' ? 'fr-CH' : language === 'it' ? 'it-CH' : 'en-US', {
      style: 'currency',
      currency: systemCurrency,
    }).format(amount);
  };

  const addRole = (role: Role) => setRoles([...roles, role]);
  const updateRole = (role: Role) => setRoles(roles.map(r => r.id === role.id ? role : r));
  const deleteRole = (id: string) => setRoles(roles.filter(r => r.id !== id));

  const addBadge = (badge: Badge) => setBadges([...badges, badge]);
  const deleteBadge = (id: string) => setBadges(badges.filter(b => b.id !== id));

  const acknowledgeAlert = (id: string) => setAlerts(alerts.map(a => a.id === id ? { ...a, acknowledged: true } : a));

  const addOfferTag = (tag: OfferTag) => setOfferTags([...offerTags, tag]);
  const deleteOfferTag = (id: string) => setOfferTags(offerTags.filter(t => t.id !== id));
  
  const addOfferCategory = (category: OfferCategory) => setOfferCategories([...offerCategories, category]);
  
  const updateOfferCategory = (category: OfferCategory) => {
      const oldCategory = offerCategories.find(c => c.id === category.id);
      setOfferCategories(offerCategories.map(c => c.id === category.id ? category : c));
      
      // If name changed, update all associated services
      if (oldCategory && oldCategory.name !== category.name) {
          setServices(services.map(s => {
              // Update primary category
              let updatedService = { ...s };
              if (updatedService.category === oldCategory.name) {
                  updatedService.category = category.name;
              }
              // Update categories array
              if (updatedService.categories && updatedService.categories.includes(oldCategory.name)) {
                  updatedService.categories = updatedService.categories.map(catName => catName === oldCategory.name ? category.name : catName);
              }
              return updatedService;
          }));
      }
  };
  
  const deleteOfferCategory = (id: string) => {
      const category = offerCategories.find(c => c.id === id);
      setOfferCategories(offerCategories.filter(c => c.id !== id));
      // Services referencing this category will effectively have a "dead" category reference or need reassignment via UI before deletion
  };

  const addOfferExtra = (extra: OfferExtra) => setOfferExtras([...offerExtras, extra]);
  const deleteOfferExtra = (id: string) => setOfferExtras(offerExtras.filter(e => e.id !== id));

  // Customer Actions
  const addCustomer = (customer: Customer) => setCustomers([...customers, customer]);
  const updateCustomer = (customer: Customer) => setCustomers(customers.map(c => c.id === customer.id ? customer : c));
  const deleteCustomer = (id: string) => setCustomers(customers.filter(c => c.id !== id));

  // Form Template Actions
  const addFormTemplate = (tpl: FormTemplate) => setBookingFormTemplates([...bookingFormTemplates, tpl]);
  const updateFormTemplate = (tpl: FormTemplate) => setBookingFormTemplates(bookingFormTemplates.map(t => t.id === tpl.id ? tpl : t));
  const deleteFormTemplate = (id: string) => setBookingFormTemplates(bookingFormTemplates.filter(t => t.id !== id));

  // Finance Actions
  const addInvoice = (inv: Invoice) => setInvoices([inv, ...invoices]);
  const updateInvoice = (inv: Invoice) => setInvoices(invoices.map(i => i.id === inv.id ? inv : i));
  const deleteInvoice = (id: string) => setInvoices(invoices.filter(i => i.id !== id));

  const generateInvoiceFromAppointment = (apt: Appointment) => {
      // Check if invoice exists for this appointment (simple check based on ID pattern or future metadata)
      // For this demo, we simply create a new one
      const newInvoice: Invoice = {
          id: `INV-${new Date().getFullYear()}-${Math.floor(Math.random() * 10000)}`,
          client: apt.customerName,
          category: 'Customer',
          amount: apt.price,
          date: new Date().toISOString().split('T')[0],
          dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // +30 days
          status: InvoiceStatus.DRAFT,
          type: 'Invoice',
          dunningLevel: DunningLevel.NONE,
          items: [{ desc: apt.serviceName, qty: 1, price: apt.price }]
      };
      addInvoice(newInvoice);
      console.log("Generated Invoice:", newInvoice);
  };

  // Appointment Actions
  const addAppointment = (apt: Appointment) => setAppointments([...appointments, apt]);
  const updateAppointment = (apt: Appointment) => {
      setAppointments(appointments.map(a => a.id === apt.id ? apt : a));
      
      // Logic: If status changed to Completed, generate Invoice
      const oldApt = appointments.find(a => a.id === apt.id);
      if (oldApt && oldApt.status !== 'Completed' && apt.status === 'Completed') {
          generateInvoiceFromAppointment(apt);
      }
  };
  const deleteAppointment = (id: string) => setAppointments(appointments.filter(a => a.id !== id));

  // Template & Settings Actions
  const addInvoiceTemplate = (tpl: InvoiceTemplate) => setInvoiceTemplates([...invoiceTemplates, tpl]);
  const updateInvoiceTemplate = (tpl: InvoiceTemplate) => setInvoiceTemplates(invoiceTemplates.map(t => t.id === tpl.id ? tpl : t));
  const deleteInvoiceTemplate = (id: string) => setInvoiceTemplates(invoiceTemplates.filter(t => t.id !== id));
  
  const updateCompanySettings = (settings: CompanySettings) => setCompanySettings(settings);

  return (
    <AppContext.Provider value={{ 
      language, setLanguage, enabledModules, toggleModule, t, formatPrice,
      setSystemCurrency, systemCurrency,
      roles, addRole, updateRole, deleteRole,
      badges, addBadge, deleteBadge,
      alerts, acknowledgeAlert,
      courses, setCourses, lessons, setLessons,
      offerTags, addOfferTag, deleteOfferTag,
      offerCategories, addOfferCategory, updateOfferCategory, deleteOfferCategory,
      offerExtras, addOfferExtra, deleteOfferExtra,
      pricingRules, setPricingRules,
      locations, rooms, equipment,
      services, setServices,
      bundles, setBundles,
      vouchers, setVouchers,
      customers, addCustomer, updateCustomer, deleteCustomer,
      customerFieldDefinitions, setCustomerFieldDefinitions,
      bookingFormTemplates, addFormTemplate, updateFormTemplate, deleteFormTemplate,
      appointments, addAppointment, updateAppointment, deleteAppointment,
      invoices, addInvoice, updateInvoice, deleteInvoice, vatRates, setVatRates, generateInvoiceFromAppointment,
      invoiceTemplates, addInvoiceTemplate, updateInvoiceTemplate, deleteInvoiceTemplate,
      companySettings, updateCompanySettings
    }}>
      {children}
    </AppContext.Provider>
  );
};

export const useApp = () => {
  const context = useContext(AppContext);
  if (context === undefined) {
    throw new Error('useApp must be used within an AppProvider');
  }
  return context;
};
