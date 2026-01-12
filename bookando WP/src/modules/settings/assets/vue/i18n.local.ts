export const messages = {
  de: {
    mod: {
      settings: {
        title: 'Einstellungen',
        messages: {
          save_error: 'Speichern fehlgeschlagen.',
        },
        cards: {
          general: {
            title: 'Allgemein',
            desc: 'Allgemeine Voreinstellungen verwalten.',
            link: 'Öffnen',
          },
          company: {
            title: 'Unternehmen',
            desc: 'Unternehmensprofil und Kontaktangaben pflegen.',
            link: 'Öffnen',
          },
          notifications: {
            title: 'Benachrichtigungen',
            desc: 'E-Mail- und SMS-Benachrichtigungen konfigurieren.',
            link: 'Demnächst',
          },
          working_hours: {
            title: 'Arbeitszeiten',
            desc: 'Standardarbeits- und Öffnungszeiten definieren.',
            link: 'Demnächst',
          },
          payments: {
            title: 'Zahlungen',
            desc: 'Zahlungsanbieter und Tarife verwalten.',
            link: 'Demnächst',
          },
          integrations: {
            title: 'Integrationen',
            desc: 'Anbindungen an externe Systeme steuern.',
            link: 'Demnächst',
          },
          appointments_events: {
            title: 'Termine & Events',
            desc: 'Termin- und Eventeinstellungen anpassen.',
            link: 'Demnächst',
          },
          labels: {
            title: 'Labels',
            desc: 'Kennzeichnungen und Tags organisieren.',
            link: 'Demnächst',
          },
          roles: {
            title: 'Rollen & Berechtigungen',
            desc: 'Rechte für Team und Kund:innen festlegen.',
            link: 'Öffnen',
          },
          activation: {
            title: 'Aktivierung',
            desc: 'Buchungsportal aktivieren oder pausieren.',
            link: 'Demnächst',
          },
          api_keys: {
            title: 'API-Schlüssel',
            desc: 'Zugänge für Schnittstellen verwalten.',
            link: 'Demnächst',
          },
        },
        general_form: {
          title: 'Allgemein',
          system_language_named: 'Systemsprache ({lang})',
          limits: {
            max_appointments: 'Max. aktive Termine',
            max_packages: 'Max. aktive Pakete',
            max_events: 'Max. aktive Events',
            zero_hint: '0 = unbegrenzt',
          },
        },
        company_form: {
          choose_logo: 'Logo auswählen',
          use_logo: 'Dieses Logo verwenden',
          logo: 'Logo',
          company_name: 'Unternehmensname',
        },
        roles_form: {
          title: 'Rollen & Berechtigungen',
          tabs: {
            employee: 'Mitarbeitende',
            customer: 'Kund:innen',
            admin: 'Administrator:innen',
          },
          fields: {
            employee: {
              can_configure_services: 'Darf Leistungen konfigurieren',
              can_edit_schedule: 'Darf Arbeitspläne anpassen',
              can_edit_days_off: 'Darf Absenzen erfassen',
              can_edit_special_days: 'Darf Spezialtage verwalten',
              can_manage_appointments: 'Darf Termine verwalten',
              can_manage_events: 'Darf Events verwalten',
              employee_area_enabled: 'Mitarbeiterbereich aktivieren',
              employee_area_url: 'URL zum Mitarbeiterbereich',
              can_manage_badges: 'Darf Badges verwalten',
              max_appointments: 'Max. parallele Termine',
            },
            customer: {
              check_existing_contact: 'Bestehende Kontakte prüfen',
              auto_create_account: 'Automatisch Konto erstellen',
              can_reschedule_appointments: 'Termine selbst verschieben',
              customer_area_enabled: 'Kundenbereich aktivieren',
              customer_area_url: 'URL zum Kundenbereich',
              require_password: 'Passwort zwingend erforderlich',
              can_self_delete: 'Konto darf selbst gelöscht werden',
              can_cancel_packages: 'Darf Pakete stornieren',
              enable_no_show_tag: '"No-Show"-Tag erlauben',
              max_appointments: 'Max. aktive Termine',
              max_packages: 'Max. aktive Pakete',
              max_events: 'Max. aktive Events',
            },
            admin: {
              can_always_book: 'Kann immer buchen',
              book_window_depends_on_service: 'Buchungsfenster richtet sich nach Leistung',
            },
          },
        },
      },
    },
  },
  en: {
    mod: {
      settings: {
        title: 'Settings',
        messages: {
          save_error: 'Saving failed.',
        },
        cards: {
          general: {
            title: 'General',
            desc: 'Manage global defaults and behaviour.',
            link: 'Open',
          },
          company: {
            title: 'Company',
            desc: 'Maintain company profile and contact data.',
            link: 'Open',
          },
          notifications: {
            title: 'Notifications',
            desc: 'Configure email and SMS notifications.',
            link: 'Coming soon',
          },
          working_hours: {
            title: 'Working hours',
            desc: 'Define default working and opening hours.',
            link: 'Coming soon',
          },
          payments: {
            title: 'Payments',
            desc: 'Manage payment providers and rates.',
            link: 'Coming soon',
          },
          integrations: {
            title: 'Integrations',
            desc: 'Control connections to external systems.',
            link: 'Coming soon',
          },
          appointments_events: {
            title: 'Appointments & events',
            desc: 'Adjust appointment and event settings.',
            link: 'Coming soon',
          },
          labels: {
            title: 'Labels',
            desc: 'Organise labels and tags.',
            link: 'Coming soon',
          },
          roles: {
            title: 'Roles & permissions',
            desc: 'Define privileges for staff and customers.',
            link: 'Open',
          },
          activation: {
            title: 'Activation',
            desc: 'Activate or pause the booking portal.',
            link: 'Coming soon',
          },
          api_keys: {
            title: 'API keys',
            desc: 'Manage access for integrations.',
            link: 'Coming soon',
          },
        },
        general_form: {
          title: 'General',
          system_language_named: 'System language ({lang})',
          limits: {
            max_appointments: 'Max active appointments',
            max_packages: 'Max active packages',
            max_events: 'Max active events',
            zero_hint: '0 = unlimited',
          },
        },
        company_form: {
          choose_logo: 'Choose logo',
          use_logo: 'Use this logo',
          logo: 'Logo',
          company_name: 'Company name',
        },
        roles_form: {
          title: 'Roles & permissions',
          tabs: {
            employee: 'Employees',
            customer: 'Customers',
            admin: 'Administrators',
          },
          fields: {
            employee: {
              can_configure_services: 'May configure services',
              can_edit_schedule: 'May edit schedules',
              can_edit_days_off: 'May manage days off',
              can_edit_special_days: 'May manage special days',
              can_manage_appointments: 'May manage appointments',
              can_manage_events: 'May manage events',
              employee_area_enabled: 'Enable employee area',
              employee_area_url: 'Employee area URL',
              can_manage_badges: 'May manage badges',
              max_appointments: 'Max parallel appointments',
            },
            customer: {
              check_existing_contact: 'Check for existing contact',
              auto_create_account: 'Create account automatically',
              can_reschedule_appointments: 'Customers may reschedule appointments',
              customer_area_enabled: 'Enable customer area',
              customer_area_url: 'Customer area URL',
              require_password: 'Password required',
              can_self_delete: 'Customers may delete their account',
              can_cancel_packages: 'Customers may cancel packages',
              enable_no_show_tag: 'Allow “no-show” tag',
              max_appointments: 'Max active appointments',
              max_packages: 'Max active packages',
              max_events: 'Max active events',
            },
            admin: {
              can_always_book: 'May always book',
              book_window_depends_on_service: 'Booking window depends on service',
            },
          },
        },
      },
    },
  },
  fr: {
    mod: {
      settings: {
        title: 'Paramètres',
        messages: {
          save_error: 'Échec de l’enregistrement.',
        },
        cards: {
          general: {
            title: 'Général',
            desc: 'Gérer les paramètres généraux par défaut.',
            link: 'Ouvrir',
          },
          company: {
            title: 'Entreprise',
            desc: 'Gérer le profil de l’entreprise et les coordonnées.',
            link: 'Ouvrir',
          },
          notifications: {
            title: 'Notifications',
            desc: 'Configurer les notifications e-mail et SMS.',
            link: 'À venir',
          },
          working_hours: {
            title: 'Horaires de travail',
            desc: 'Définir les horaires standard de travail et d’ouverture.',
            link: 'À venir',
          },
          payments: {
            title: 'Paiements',
            desc: 'Gérer les prestataires de paiement et les tarifs.',
            link: 'À venir',
          },
          integrations: {
            title: 'Intégrations',
            desc: 'Contrôler les connexions aux systèmes externes.',
            link: 'À venir',
          },
          appointments_events: {
            title: 'Rendez-vous & évènements',
            desc: 'Adapter les paramètres de rendez-vous et d’évènements.',
            link: 'À venir',
          },
          labels: {
            title: 'Labels',
            desc: 'Organiser les libellés et les tags.',
            link: 'À venir',
          },
          roles: {
            title: 'Rôles & autorisations',
            desc: 'Définir les droits pour l’équipe et les client·e·s.',
            link: 'Ouvrir',
          },
          activation: {
            title: 'Activation',
            desc: 'Activer ou mettre en pause le portail de réservation.',
            link: 'À venir',
          },
          api_keys: {
            title: 'Clés API',
            desc: 'Gérer les accès pour les intégrations.',
            link: 'À venir',
          },
        },
        general_form: {
          title: 'Général',
          system_language_named: 'Langue du système ({lang})',
          limits: {
            max_appointments: 'Rendez-vous actifs max.',
            max_packages: 'Forfaits actifs max.',
            max_events: 'Évènements actifs max.',
            zero_hint: '0 = illimité',
          },
        },
        company_form: {
          choose_logo: 'Choisir un logo',
          use_logo: 'Utiliser ce logo',
          logo: 'Logo',
          company_name: 'Nom de l’entreprise',
        },
        roles_form: {
          title: 'Rôles & autorisations',
          tabs: {
            employee: 'Employé·e·s',
            customer: 'Client·e·s',
            admin: 'Administrateur·rice·s',
          },
          fields: {
            employee: {
              can_configure_services: 'Peut configurer les prestations',
              can_edit_schedule: 'Peut modifier les plannings',
              can_edit_days_off: 'Peut gérer les absences',
              can_edit_special_days: 'Peut gérer les jours spéciaux',
              can_manage_appointments: 'Peut gérer les rendez-vous',
              can_manage_events: 'Peut gérer les évènements',
              employee_area_enabled: 'Activer l’espace employé',
              employee_area_url: 'URL de l’espace employé',
              can_manage_badges: 'Peut gérer les badges',
              max_appointments: 'Rendez-vous simultanés max.',
            },
            customer: {
              check_existing_contact: 'Vérifier les contacts existants',
              auto_create_account: 'Créer automatiquement un compte',
              can_reschedule_appointments: 'Les client·e·s peuvent replanifier les rendez-vous',
              customer_area_enabled: 'Activer l’espace client',
              customer_area_url: 'URL de l’espace client',
              require_password: 'Mot de passe obligatoire',
              can_self_delete: 'Les client·e·s peuvent supprimer leur compte',
              can_cancel_packages: 'Les client·e·s peuvent annuler les forfaits',
              enable_no_show_tag: 'Autoriser l’étiquette « no-show »',
              max_appointments: 'Rendez-vous actifs max.',
              max_packages: 'Forfaits actifs max.',
              max_events: 'Évènements actifs max.',
            },
            admin: {
              can_always_book: 'Peut toujours réserver',
              book_window_depends_on_service: 'Fenêtre de réservation selon la prestation',
            },
          },
        },
      },
    },
  },
  it: {
    mod: {
      settings: {
        title: 'Impostazioni',
        messages: {
          save_error: 'Salvataggio non riuscito.',
        },
        cards: {
          general: {
            title: 'Generale',
            desc: 'Gestisci le impostazioni predefinite generali.',
            link: 'Apri',
          },
          company: {
            title: 'Azienda',
            desc: 'Gestisci profilo aziendale e dati di contatto.',
            link: 'Apri',
          },
          notifications: {
            title: 'Notifiche',
            desc: 'Configura notifiche e-mail e SMS.',
            link: 'In arrivo',
          },
          working_hours: {
            title: 'Orari di lavoro',
            desc: 'Definisci orari standard di lavoro e apertura.',
            link: 'In arrivo',
          },
          payments: {
            title: 'Pagamenti',
            desc: 'Gestisci provider di pagamento e tariffe.',
            link: 'In arrivo',
          },
          integrations: {
            title: 'Integrazioni',
            desc: 'Gestisci le connessioni con sistemi esterni.',
            link: 'In arrivo',
          },
          appointments_events: {
            title: 'Appuntamenti & eventi',
            desc: 'Adatta le impostazioni di appuntamenti ed eventi.',
            link: 'In arrivo',
          },
          labels: {
            title: 'Etichette',
            desc: 'Organizza etichette e tag.',
            link: 'In arrivo',
          },
          roles: {
            title: 'Ruoli & permessi',
            desc: 'Definisci i diritti per team e clienti.',
            link: 'Apri',
          },
          activation: {
            title: 'Attivazione',
            desc: 'Attiva o sospendi il portale di prenotazione.',
            link: 'In arrivo',
          },
          api_keys: {
            title: 'Chiavi API',
            desc: 'Gestisci gli accessi per le integrazioni.',
            link: 'In arrivo',
          },
        },
        general_form: {
          title: 'Generale',
          system_language_named: 'Lingua di sistema ({lang})',
          limits: {
            max_appointments: 'Appuntamenti attivi max.',
            max_packages: 'Pacchetti attivi max.',
            max_events: 'Eventi attivi max.',
            zero_hint: '0 = illimitato',
          },
        },
        company_form: {
          choose_logo: 'Seleziona logo',
          use_logo: 'Usa questo logo',
          logo: 'Logo',
          company_name: 'Nome azienda',
        },
        roles_form: {
          title: 'Ruoli & permessi',
          tabs: {
            employee: 'Collaboratori',
            customer: 'Clienti',
            admin: 'Amministratori',
          },
          fields: {
            employee: {
              can_configure_services: 'Può configurare i servizi',
              can_edit_schedule: 'Può modificare i turni',
              can_edit_days_off: 'Può gestire le assenze',
              can_edit_special_days: 'Può gestire i giorni speciali',
              can_manage_appointments: 'Può gestire gli appuntamenti',
              can_manage_events: 'Può gestire gli eventi',
              employee_area_enabled: 'Abilita area collaboratori',
              employee_area_url: 'URL area collaboratori',
              can_manage_badges: 'Può gestire i badge',
              max_appointments: 'Appuntamenti simultanei max.',
            },
            customer: {
              check_existing_contact: 'Verifica contatti esistenti',
              auto_create_account: 'Crea automaticamente l’account',
              can_reschedule_appointments: 'I clienti possono riprogrammare gli appuntamenti',
              customer_area_enabled: 'Abilita area clienti',
              customer_area_url: 'URL area clienti',
              require_password: 'Password obbligatoria',
              can_self_delete: 'I clienti possono eliminare il proprio account',
              can_cancel_packages: 'I clienti possono annullare i pacchetti',
              enable_no_show_tag: 'Consenti tag «no-show»',
              max_appointments: 'Appuntamenti attivi max.',
              max_packages: 'Pacchetti attivi max.',
              max_events: 'Eventi attivi max.',
            },
            admin: {
              can_always_book: 'Può sempre prenotare',
              book_window_depends_on_service: 'La finestra di prenotazione dipende dal servizio',
            },
          },
        },
      },
    },
  },
  es: {
    mod: {
      settings: {
        title: 'Configuración',
        messages: {
          save_error: 'Error al guardar.',
        },
        cards: {
          general: {
            title: 'General',
            desc: 'Gestiona la configuración predeterminada general.',
            link: 'Abrir',
          },
          company: {
            title: 'Empresa',
            desc: 'Mantén el perfil de la empresa y los datos de contacto.',
            link: 'Abrir',
          },
          notifications: {
            title: 'Notificaciones',
            desc: 'Configura notificaciones por correo y SMS.',
            link: 'Próximamente',
          },
          working_hours: {
            title: 'Horarios laborales',
            desc: 'Define horarios estándar de trabajo y apertura.',
            link: 'Próximamente',
          },
          payments: {
            title: 'Pagos',
            desc: 'Gestiona proveedores de pago y tarifas.',
            link: 'Próximamente',
          },
          integrations: {
            title: 'Integraciones',
            desc: 'Controla las conexiones con sistemas externos.',
            link: 'Próximamente',
          },
          appointments_events: {
            title: 'Citas y eventos',
            desc: 'Ajusta la configuración de citas y eventos.',
            link: 'Próximamente',
          },
          labels: {
            title: 'Etiquetas',
            desc: 'Organiza etiquetas y tags.',
            link: 'Próximamente',
          },
          roles: {
            title: 'Roles y permisos',
            desc: 'Define permisos para el equipo y clientes.',
            link: 'Abrir',
          },
          activation: {
            title: 'Activación',
            desc: 'Activa o pausa el portal de reservas.',
            link: 'Próximamente',
          },
          api_keys: {
            title: 'Claves API',
            desc: 'Gestiona accesos para integraciones.',
            link: 'Próximamente',
          },
        },
        general_form: {
          title: 'General',
          system_language_named: 'Idioma del sistema ({lang})',
          limits: {
            max_appointments: 'Citas activas máx.',
            max_packages: 'Paquetes activos máx.',
            max_events: 'Eventos activos máx.',
            zero_hint: '0 = ilimitado',
          },
        },
        company_form: {
          choose_logo: 'Seleccionar logotipo',
          use_logo: 'Usar este logotipo',
          logo: 'Logotipo',
          company_name: 'Nombre de la empresa',
        },
        roles_form: {
          title: 'Roles y permisos',
          tabs: {
            employee: 'Empleados',
            customer: 'Clientes',
            admin: 'Administradores',
          },
          fields: {
            employee: {
              can_configure_services: 'Puede configurar servicios',
              can_edit_schedule: 'Puede modificar horarios',
              can_edit_days_off: 'Puede gestionar ausencias',
              can_edit_special_days: 'Puede gestionar días especiales',
              can_manage_appointments: 'Puede gestionar citas',
              can_manage_events: 'Puede gestionar eventos',
              employee_area_enabled: 'Activar área de empleados',
              employee_area_url: 'URL del área de empleados',
              can_manage_badges: 'Puede gestionar insignias',
              max_appointments: 'Citas simultáneas máx.',
            },
            customer: {
              check_existing_contact: 'Comprobar contactos existentes',
              auto_create_account: 'Crear cuenta automáticamente',
              can_reschedule_appointments: 'Los clientes pueden reprogramar citas',
              customer_area_enabled: 'Activar área de clientes',
              customer_area_url: 'URL del área de clientes',
              require_password: 'Contraseña obligatoria',
              can_self_delete: 'Los clientes pueden eliminar su cuenta',
              can_cancel_packages: 'Los clientes pueden cancelar paquetes',
              enable_no_show_tag: 'Permitir etiqueta «no-show»',
              max_appointments: 'Citas activas máx.',
              max_packages: 'Paquetes activos máx.',
              max_events: 'Eventos activos máx.',
            },
            admin: {
              can_always_book: 'Puede reservar siempre',
              book_window_depends_on_service: 'La ventana de reserva depende del servicio',
            },
          },
        },
      },
    },
  },
}
