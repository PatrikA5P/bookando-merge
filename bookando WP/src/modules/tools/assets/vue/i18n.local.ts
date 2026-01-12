export const messages = {
  de: {
    mod: {
      tools: {
        title: 'Tools',

        // Tabs
        tabs: {
          workforce: 'Workforce Management',
          reports: 'Reports',
          coursePlanner: 'Kursplaner',
          customFields: 'Benutzerdefinierte Felder',
          formTemplates: 'Formularvorlagen',
          notifications: 'Benachrichtigungen',
          design: 'Design'
        },

        workforce: {
          title: 'Workforce Management',
          subtitle: 'Zeiterfassung und Urlaubsverwaltung für Mitarbeiter',
          selectEmployee: 'Mitarbeiter auswählen',
          employee: 'Mitarbeiter',
          statusFilter: 'Status-Filter',
          statusActive: 'Nur Aktive',
          statusAll: 'Alle',
          timeTracking: 'Zeiterfassung',
          clockIn: 'Einstempeln',
          clockOut: 'Ausstempeln',
          clockInTime: 'Einstempelzeit',
          clockOutTime: 'Ausstempelzeit',
          totalHours: 'Gesamtstunden',
          thisWeek: 'Diese Woche',
          thisMonth: 'Dieser Monat',
          activeTimers: 'Aktive Timer',
          recentEntries: 'Letzte Einträge',
          clockInSuccess: 'Erfolgreich eingestempelt',
          clockOutSuccess: 'Erfolgreich ausgestempelt',
          vacationRequests: 'Urlaubsanträge',
          newRequest: 'Neuer Antrag',
          startDate: 'Startdatum',
          endDate: 'Enddatum',
          reason: 'Grund',
          submitRequest: 'Antrag stellen',
          pendingRequests: 'Offene Anträge',
          days: 'Tage',
          approve: 'Genehmigen',
          reject: 'Ablehnen',
          rejectReason: 'Grund für Ablehnung',
          requestSubmitted: 'Antrag wurde eingereicht',
          requestApproved: 'Antrag wurde genehmigt',
          requestRejected: 'Antrag wurde abgelehnt'
        },

        coursePlanner: {
          title: 'Intelligenter Kursplaner',
          subtitle: 'Plane Präsenzkurse und Events aus dem Modul Offers mit wenigen Klicks.',
          periodStart: 'Planungsbeginn',
          periodEnd: 'Planungsende',
          generatePlan: 'Plan generieren',
          analytics: 'Auswertung',
          totalSessions: 'Erfasste Kurse',
          avgAttendance: 'Ø Teilnehmer',
          cancellationRate: 'Stornoquote',
          popularSlots: 'Beliebte Slots',
          successScore: 'Erfolgswert',
          importTitle: 'Historie importieren',
          offer: 'Offer',
          titleLabel: 'Titel',
          type: 'Kursart',
          location: 'Ort',
          date: 'Datum',
          startTime: 'Start',
          endTime: 'Ende',
          status: 'Status',
          statuses: {
            held: 'Durchgeführt',
            waitlist: 'Warteliste',
            cancelled: 'Abgesagt'
          },
          capacity: 'Kapazität',
          attendance: 'Teilnehmende',
          importAction: 'Importieren',
          preferences: 'Planungsregeln',
          savePreferences: 'Regeln speichern',
          allowedDays: 'Erlaubte Tage',
          timeWindow: 'Zeitfenster',
          requireDaylight: 'Nur bei Tageslicht planen',
          typeTargets: 'Ziel pro Kursart',
          simultaneous: 'Parallele Kurse',
          simultaneousPlaceholder: 'z.B. yoga,pilates',
          planPreview: 'Planvorschlag',
          generatedAt: 'Generiert am',
          score: 'Score',
          noPlan: 'Noch kein Plan vorhanden',
          history: 'Historie',
          entries: '{count} Einträge'
        },

        timeTracking: {
          title: 'Zeiterfassung & Dienste',
          subtitle: 'Live-Stempeln, manuelle Einträge und Regeln für Mitarbeitende.',
          employeeId: 'Mitarbeiter-ID',
          employeeName: 'Name',
          role: 'Rolle',
          startTimer: 'Timer starten',
          stopTimer: 'Stoppen',
          hoursWeek: 'Stunden (Woche)',
          overtime: 'Überstunden',
          activeTimers: 'Aktive Timer',
          activeTimersTitle: 'Laufende Timer',
          noTimers: 'Aktuell keine aktiven Timer.',
          manualEntry: 'Manuelle Erfassung',
          date: 'Datum',
          start: 'Start',
          end: 'Ende',
          rulesTitle: 'Regeln',
          rounding: 'Rundung (Minuten)',
          overtimeThreshold: 'Überstundengrenze (h)',
          allowManual: 'Manuelle Einträge erlauben',
          entriesTitle: 'Letzte Einträge',
          duration: 'Dauer'
        },

        dutyScheduling: {
          title: 'Dienstplanung',
          subtitle: 'Schichtvorlagen, Verfügbarkeiten und automatische Einsatzpläne.',
          periodStart: 'Von',
          periodEnd: 'Bis',
          generate: 'Dienstplan erzeugen',
          templates: 'Schichtvorlagen',
          templateName: 'Vorlagenname',
          start: 'Start',
          end: 'Ende',
          days: 'Tage',
          addRole: 'Rolle hinzufügen',
          existingTemplates: 'Bestehende Vorlagen',
          availability: 'Verfügbarkeiten',
          employeeId: 'Mitarbeiter-ID',
          employeeName: 'Name',
          roles: 'Rollen',
          weeklyCapacity: 'Wochenstunden',
          unavailableDays: 'Nicht verfügbar',
          preferredShifts: 'Bevorzugte Schichten',
          currentAvailability: 'Hinterlegte Verfügbarkeiten',
          constraints: 'Rahmenbedingungen',
          maxHours: 'Max. Stunden/Woche',
          minRest: 'Min. Ruhezeit (h)',
          allowOvertime: 'Überstunden erlauben',
          rosterTitle: 'Dienstplan',
          generatedAt: 'Erstellt am',
          shift: 'Schicht',
          role: 'Rolle',
          employee: 'Mitarbeiter:in',
          status: 'Status',
          openSlot: 'Offen',
          assigned: 'Besetzt',
          open: 'Offen',
          noRoster: 'Noch kein Dienstplan vorhanden.'
        },

        // Reports
        reports: {
          title: 'Reports & Statistiken',
          overview: 'Übersicht',
          dateRange: 'Zeitraum',
          exportReport: 'Report exportieren',
          totalBookings: 'Gesamtbuchungen',
          totalRevenue: 'Gesamtumsatz',
          totalCustomers: 'Gesamtkunden',
          conversionRate: 'Conversion Rate',
          bookingsOverTime: 'Buchungen im Zeitverlauf',
          revenueByService: 'Umsatz nach Dienstleistung',
          topServices: 'Top Dienstleistungen',
          customerGrowth: 'Kundenwachstum',
          exportFormat: 'Export-Format',
          exportPDF: 'Als PDF exportieren',
          exportCSV: 'Als CSV exportieren',
          exportExcel: 'Als Excel exportieren'
        },

        // Custom Fields
        customFields: {
          title: 'Benutzerdefinierte Felder',
          addField: 'Feld hinzufügen',
          editField: 'Feld bearbeiten',
          fieldName: 'Feldname',
          fieldLabel: 'Beschriftung',
          fieldType: 'Feldtyp',
          entityType: 'Entitätstyp',
          required: 'Pflichtfeld',
          active: 'Aktiv',
          position: 'Position',
          options: 'Optionen',
          validationRules: 'Validierungsregeln',

          types: {
            text: 'Text',
            textarea: 'Textbereich',
            number: 'Nummer',
            email: 'E-Mail',
            phone: 'Telefon',
            date: 'Datum',
            time: 'Uhrzeit',
            select: 'Auswahlliste',
            checkbox: 'Checkbox',
            radio: 'Radio-Buttons',
            file: 'Datei-Upload'
          },

          entities: {
            customer: 'Kunde',
            booking: 'Buchung',
            service: 'Dienstleistung',
            employee: 'Mitarbeiter'
          }
        },

        // Form Templates
        formTemplates: {
          title: 'Formularvorlagen',
          addTemplate: 'Vorlage hinzufügen',
          editTemplate: 'Vorlage bearbeiten',
          templateName: 'Vorlagenname',
          description: 'Beschreibung',
          templateType: 'Vorlagentyp',
          fields: 'Felder',
          isDefault: 'Als Standard',
          preview: 'Vorschau',

          types: {
            booking: 'Buchungsformular',
            registration: 'Registrierungsformular',
            contact: 'Kontaktformular',
            feedback: 'Feedback-Formular'
          }
        },

        // Notifications
        notifications: {
          title: 'Benachrichtigungen',
          addNotification: 'Benachrichtigung hinzufügen',
          editNotification: 'Benachrichtigung bearbeiten',
          name: 'Name',
          eventTrigger: 'Auslöser',
          channel: 'Kanal',
          recipientType: 'Empfängertyp',
          subject: 'Betreff',
          message: 'Nachricht',
          active: 'Aktiv',
          sendDelay: 'Verzögerung (Minuten)',
          testNotification: 'Test senden',
          viewLogs: 'Versandlog anzeigen',
          templateVariables: 'Verfügbare Variablen',

          channels: {
            email: 'E-Mail',
            sms: 'SMS',
            whatsapp: 'WhatsApp',
            push: 'Push-Benachrichtigung'
          },

          triggers: {
            bookingCreated: 'Buchung erstellt',
            bookingConfirmed: 'Buchung bestätigt',
            bookingCancelled: 'Buchung storniert',
            bookingReminder: 'Buchungserinnerung',
            customerRegistered: 'Kunde registriert',
            paymentReceived: 'Zahlung erhalten'
          },

          recipients: {
            customer: 'Kunde',
            employee: 'Mitarbeiter',
            admin: 'Administrator',
            custom: 'Benutzerdefiniert'
          },

          logs: {
            title: 'Versandlog',
            recipient: 'Empfänger',
            status: 'Status',
            sentAt: 'Gesendet am',
            error: 'Fehler',

            statuses: {
              sent: 'Gesendet',
              failed: 'Fehlgeschlagen',
              pending: 'Ausstehend',
              delivered: 'Zugestellt'
            }
          }
        },

        // Design
        design: {
          title: 'Design-Anpassungen',
          serviceList: 'Dienstleistungsübersicht',
          serviceDetail: 'Dienstleistungsdetails',
          bookingForm: 'Buchungsformular',
          customerPortal: 'Kundenportal',
          employeePortal: 'Mitarbeiterportal',

          colors: 'Farben',
          primaryColor: 'Primärfarbe',
          secondaryColor: 'Sekundärfarbe',
          accentColor: 'Akzentfarbe',
          textColor: 'Textfarbe',
          backgroundColor: 'Hintergrundfarbe',

          typography: 'Typografie',
          fontFamily: 'Schriftart',
          fontSize: 'Schriftgröße',

          layout: 'Layout',
          gridColumns: 'Spalten',
          cardStyle: 'Kartenstil',
          borderRadius: 'Randradius',
          spacing: 'Abstände',

          buttons: 'Buttons',
          buttonStyle: 'Button-Stil',
          buttonSize: 'Button-Größe',

          preview: 'Vorschau',
          resetToDefault: 'Zurücksetzen',
          saveChanges: 'Änderungen speichern'
        },

        // Common
        save: 'Speichern',
        cancel: 'Abbrechen',
        delete: 'Löschen',
        edit: 'Bearbeiten',
        create: 'Erstellen',
        search: 'Suchen',
        filter: 'Filtern',
        actions: 'Aktionen',
        noData: 'Keine Daten verfügbar',
        loading: 'Laden...',
        success: 'Erfolgreich',
        error: 'Fehler',
        add: 'Hinzufügen',
        choose: 'Auswählen',
        notes: 'Notizen',

        // Weekdays
        weekdays: {
          mon: 'Montag',
          tue: 'Dienstag',
          wed: 'Mittwoch',
          thu: 'Donnerstag',
          fri: 'Freitag',
          sat: 'Samstag',
          sun: 'Sonntag'
        }
      }
    }
  },
  en: {
    mod: {
      tools: {
        title: 'Tools',

        // Tabs
        tabs: {
          workforce: 'Workforce Management',
          reports: 'Reports',
          coursePlanner: 'Course Planner',
          customFields: 'Custom Fields',
          formTemplates: 'Form Templates',
          notifications: 'Notifications',
          design: 'Design'
        },

        workforce: {
          title: 'Workforce Management',
          subtitle: 'Time tracking and vacation management for employees',
          selectEmployee: 'Select Employee',
          employee: 'Employee',
          statusFilter: 'Status Filter',
          statusActive: 'Active Only',
          statusAll: 'All',
          timeTracking: 'Time Tracking',
          clockIn: 'Clock In',
          clockOut: 'Clock Out',
          clockInTime: 'Clock In Time',
          clockOutTime: 'Clock Out Time',
          totalHours: 'Total Hours',
          thisWeek: 'This Week',
          thisMonth: 'This Month',
          activeTimers: 'Active Timers',
          recentEntries: 'Recent Entries',
          clockInSuccess: 'Clocked in successfully',
          clockOutSuccess: 'Clocked out successfully',
          vacationRequests: 'Vacation Requests',
          newRequest: 'New Request',
          startDate: 'Start Date',
          endDate: 'End Date',
          reason: 'Reason',
          submitRequest: 'Submit Request',
          pendingRequests: 'Pending Requests',
          days: 'Days',
          approve: 'Approve',
          reject: 'Reject',
          rejectReason: 'Reason for rejection',
          requestSubmitted: 'Request submitted',
          requestApproved: 'Request approved',
          requestRejected: 'Request rejected'
        },

        coursePlanner: {
          title: 'Intelligent course planner',
          subtitle: 'Plan physical courses and events coming from the Offers module.',
          periodStart: 'Planning start',
          periodEnd: 'Planning end',
          generatePlan: 'Generate plan',
          analytics: 'Analytics',
          totalSessions: 'Tracked sessions',
          avgAttendance: 'Avg. attendees',
          cancellationRate: 'Cancellation rate',
          popularSlots: 'Top slots',
          successScore: 'Success score',
          importTitle: 'Import history',
          offer: 'Offer',
          titleLabel: 'Title',
          type: 'Type',
          location: 'Location',
          date: 'Date',
          startTime: 'Start time',
          endTime: 'End time',
          status: 'Status',
          statuses: {
            held: 'Held',
            waitlist: 'Waitlist',
            cancelled: 'Cancelled'
          },
          capacity: 'Capacity',
          attendance: 'Attendance',
          importAction: 'Import entry',
          preferences: 'Planning preferences',
          savePreferences: 'Save preferences',
          allowedDays: 'Allowed days',
          timeWindow: 'Time window',
          requireDaylight: 'Require daylight',
          typeTargets: 'Targets per type',
          simultaneous: 'Simultaneous courses',
          simultaneousPlaceholder: 'e.g. yoga,pilates',
          planPreview: 'Plan preview',
          generatedAt: 'Generated at',
          score: 'Score',
          noPlan: 'No plan yet',
          history: 'History',
          entries: '{count} entries'
        },

        timeTracking: {
          title: 'Time tracking',
          subtitle: 'Track work hours, run timers and store manual entries.',
          employeeId: 'Employee ID',
          employeeName: 'Name',
          role: 'Role',
          startTimer: 'Start timer',
          stopTimer: 'Stop',
          hoursWeek: 'Hours (week)',
          overtime: 'Overtime',
          activeTimers: 'Active timers',
          activeTimersTitle: 'Running timers',
          noTimers: 'No timers are running.',
          manualEntry: 'Manual entry',
          date: 'Date',
          start: 'Start',
          end: 'End',
          rulesTitle: 'Rules',
          rounding: 'Rounding (minutes)',
          overtimeThreshold: 'Overtime threshold (h)',
          allowManual: 'Allow manual edits',
          entriesTitle: 'Latest entries',
          duration: 'Duration'
        },

        dutyScheduling: {
          title: 'Duty scheduling',
          subtitle: 'Manage shift templates, availability and rosters.',
          periodStart: 'From',
          periodEnd: 'To',
          generate: 'Generate roster',
          templates: 'Shift templates',
          templateName: 'Template name',
          start: 'Start',
          end: 'End',
          days: 'Days',
          addRole: 'Add role',
          existingTemplates: 'Existing templates',
          availability: 'Availability',
          employeeId: 'Employee ID',
          employeeName: 'Name',
          roles: 'Roles',
          weeklyCapacity: 'Weekly capacity',
          unavailableDays: 'Unavailable',
          preferredShifts: 'Preferred shifts',
          currentAvailability: 'Current availability',
          constraints: 'Constraints',
          maxHours: 'Max hours/week',
          minRest: 'Min. rest (h)',
          allowOvertime: 'Allow overtime',
          rosterTitle: 'Roster',
          generatedAt: 'Generated at',
          date: 'Date',
          shift: 'Shift',
          role: 'Role',
          employee: 'Employee',
          status: 'Status',
          openSlot: 'Open slot',
          assigned: 'Assigned',
          open: 'Open',
          noRoster: 'No roster generated yet.'
        },

        // Reports
        reports: {
          title: 'Reports & Statistics',
          overview: 'Overview',
          dateRange: 'Date Range',
          exportReport: 'Export Report',
          totalBookings: 'Total Bookings',
          totalRevenue: 'Total Revenue',
          totalCustomers: 'Total Customers',
          conversionRate: 'Conversion Rate',
          bookingsOverTime: 'Bookings Over Time',
          revenueByService: 'Revenue by Service',
          topServices: 'Top Services',
          customerGrowth: 'Customer Growth',
          exportFormat: 'Export Format',
          exportPDF: 'Export as PDF',
          exportCSV: 'Export as CSV',
          exportExcel: 'Export as Excel'
        },

        // Custom Fields
        customFields: {
          title: 'Custom Fields',
          addField: 'Add Field',
          editField: 'Edit Field',
          fieldName: 'Field Name',
          fieldLabel: 'Label',
          fieldType: 'Field Type',
          entityType: 'Entity Type',
          required: 'Required',
          active: 'Active',
          position: 'Position',
          options: 'Options',
          validationRules: 'Validation Rules',

          types: {
            text: 'Text',
            textarea: 'Text Area',
            number: 'Number',
            email: 'Email',
            phone: 'Phone',
            date: 'Date',
            time: 'Time',
            select: 'Select',
            checkbox: 'Checkbox',
            radio: 'Radio Buttons',
            file: 'File Upload'
          },

          entities: {
            customer: 'Customer',
            booking: 'Booking',
            service: 'Service',
            employee: 'Employee'
          }
        },

        // Form Templates
        formTemplates: {
          title: 'Form Templates',
          addTemplate: 'Add Template',
          editTemplate: 'Edit Template',
          templateName: 'Template Name',
          description: 'Description',
          templateType: 'Template Type',
          fields: 'Fields',
          isDefault: 'Set as Default',
          preview: 'Preview',

          types: {
            booking: 'Booking Form',
            registration: 'Registration Form',
            contact: 'Contact Form',
            feedback: 'Feedback Form'
          }
        },

        // Notifications
        notifications: {
          title: 'Notifications',
          addNotification: 'Add Notification',
          editNotification: 'Edit Notification',
          name: 'Name',
          eventTrigger: 'Event Trigger',
          channel: 'Channel',
          recipientType: 'Recipient Type',
          subject: 'Subject',
          message: 'Message',
          active: 'Active',
          sendDelay: 'Delay (Minutes)',
          testNotification: 'Send Test',
          viewLogs: 'View Send Log',
          templateVariables: 'Available Variables',

          channels: {
            email: 'Email',
            sms: 'SMS',
            whatsapp: 'WhatsApp',
            push: 'Push Notification'
          },

          triggers: {
            bookingCreated: 'Booking Created',
            bookingConfirmed: 'Booking Confirmed',
            bookingCancelled: 'Booking Cancelled',
            bookingReminder: 'Booking Reminder',
            customerRegistered: 'Customer Registered',
            paymentReceived: 'Payment Received'
          },

          recipients: {
            customer: 'Customer',
            employee: 'Employee',
            admin: 'Administrator',
            custom: 'Custom'
          },

          logs: {
            title: 'Send Log',
            recipient: 'Recipient',
            status: 'Status',
            sentAt: 'Sent At',
            error: 'Error',

            statuses: {
              sent: 'Sent',
              failed: 'Failed',
              pending: 'Pending',
              delivered: 'Delivered'
            }
          }
        },

        // Design
        design: {
          title: 'Design Customizations',
          serviceList: 'Service List',
          serviceDetail: 'Service Details',
          bookingForm: 'Booking Form',
          customerPortal: 'Customer Portal',
          employeePortal: 'Employee Portal',

          colors: 'Colors',
          primaryColor: 'Primary Color',
          secondaryColor: 'Secondary Color',
          accentColor: 'Accent Color',
          textColor: 'Text Color',
          backgroundColor: 'Background Color',

          typography: 'Typography',
          fontFamily: 'Font Family',
          fontSize: 'Font Size',

          layout: 'Layout',
          gridColumns: 'Grid Columns',
          cardStyle: 'Card Style',
          borderRadius: 'Border Radius',
          spacing: 'Spacing',

          buttons: 'Buttons',
          buttonStyle: 'Button Style',
          buttonSize: 'Button Size',

          preview: 'Preview',
          resetToDefault: 'Reset to Default',
          saveChanges: 'Save Changes'
        },

        // Common
        save: 'Save',
        cancel: 'Cancel',
        delete: 'Delete',
        edit: 'Edit',
        create: 'Create',
        search: 'Search',
        filter: 'Filter',
        actions: 'Actions',
        noData: 'No data available',
        loading: 'Loading...',
        success: 'Success',
        error: 'Error',
        add: 'Add',
        choose: 'Choose',
        notes: 'Notes',

        // Weekdays
        weekdays: {
          mon: 'Monday',
          tue: 'Tuesday',
          wed: 'Wednesday',
          thu: 'Thursday',
          fri: 'Friday',
          sat: 'Saturday',
          sun: 'Sunday'
        }
      }
    }
  }
}
