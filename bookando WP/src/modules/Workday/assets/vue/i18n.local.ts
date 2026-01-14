export const messages = {
  de: {
    mod: {
      workday: {
        title: 'Arbeitstag',

        // Tabs
        tabs: {
          calendar: 'Kalender',
          appointments: 'Termine',
          timeTracking: 'Zeiterfassung',
          dutyScheduling: 'Dienstplanung'
        },

        // Calendar Tab
        calendar: {
          title: 'Kalender',
          subtitle: 'Übersicht aller Kurse, Termine, Arbeitszeiten und Urlaube',
          comingSoon: 'Kalender wird bald verfügbar sein',
          description: 'Hier werden alle Aktivitäten Ihrer Mitarbeiter in einer übersichtlichen Kalenderansicht dargestellt.',
          courses: 'Kurse - Geplante Kursangebote',
          appointments: 'Termine - Gebuchte Termine',
          workingHours: 'Arbeitszeiten - Zeiterfassung',
          vacations: 'Urlaube - Freistellungen'
        },

        // Appointments Tab
        appointments: {
          title: 'Termine',
          subtitle: 'Verwaltung aller Termine und Buchungen',
          comingSoon: 'Terminverwaltung wird bald verfügbar sein',
          description: 'Termine können bald hier verwaltet werden.',
          migrationNote: 'Das bisherige Appointments-Modul wird hier integriert.'
        },

        // Time Tracking Tab (reuse workforce translations from tools)
        timeTracking: {
          title: 'Zeiterfassung',
          subtitle: 'Zeiterfassung und Urlaubsverwaltung für Mitarbeiter',
          selectEmployee: 'Mitarbeiter auswählen',
          employee: 'Mitarbeiter',
          statusFilter: 'Status-Filter',
          statusActive: 'Nur Aktive',
          statusAll: 'Alle',
          clockIn: 'Einstempeln',
          clockOut: 'Ausstempeln',
          clockInTime: 'Einstempelzeit',
          clockOutTime: 'Ausstempelzeit',
          totalHours: 'Gesamtstunden',
          thisWeek: 'Diese Woche',
          thisMonth: 'Dieser Monat',
          activeTimers: 'Aktive Timer',
          recentEntries: 'Letzte Einträge',
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
          requestSubmitted: 'Antrag wurde eingereicht',
          requestApproved: 'Antrag wurde genehmigt',
          requestRejected: 'Antrag wurde abgelehnt'
        },

        // Duty Scheduling Tab (reuse from tools.dutyScheduling)
        dutyScheduling: {
          title: 'Dienstplanung',
          subtitle: 'Schichtvorlagen, Verfügbarkeiten und automatische Einsatzpläne',
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

        // Common
        save: 'Speichern',
        cancel: 'Abbrechen',
        choose: 'Auswählen',
        notes: 'Notizen',
        loading: 'Laden...'
      }
    }
  },
  en: {
    mod: {
      workday: {
        title: 'Workday',

        // Tabs
        tabs: {
          calendar: 'Calendar',
          appointments: 'Appointments',
          timeTracking: 'Time Tracking',
          dutyScheduling: 'Duty Scheduling'
        },

        // Calendar Tab
        calendar: {
          title: 'Calendar',
          subtitle: 'Overview of all courses, appointments, working hours and vacations',
          comingSoon: 'Calendar coming soon',
          description: 'All employee activities will be displayed here in a clear calendar view.',
          courses: 'Courses - Planned course offerings',
          appointments: 'Appointments - Booked appointments',
          workingHours: 'Working Hours - Time tracking',
          vacations: 'Vacations - Time off'
        },

        // Appointments Tab
        appointments: {
          title: 'Appointments',
          subtitle: 'Management of all appointments and bookings',
          comingSoon: 'Appointment management coming soon',
          description: 'Appointments can be managed here soon.',
          migrationNote: 'The previous Appointments module will be integrated here.'
        },

        // Time Tracking Tab
        timeTracking: {
          title: 'Time Tracking',
          subtitle: 'Time tracking and vacation management for employees',
          selectEmployee: 'Select Employee',
          employee: 'Employee',
          statusFilter: 'Status Filter',
          statusActive: 'Active Only',
          statusAll: 'All',
          clockIn: 'Clock In',
          clockOut: 'Clock Out',
          clockInTime: 'Clock In Time',
          clockOutTime: 'Clock Out Time',
          totalHours: 'Total Hours',
          thisWeek: 'This Week',
          thisMonth: 'This Month',
          activeTimers: 'Active Timers',
          recentEntries: 'Recent Entries',
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
          requestSubmitted: 'Request submitted',
          requestApproved: 'Request approved',
          requestRejected: 'Request rejected'
        },

        // Duty Scheduling Tab
        dutyScheduling: {
          title: 'Duty Scheduling',
          subtitle: 'Manage shift templates, availability and rosters',
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
          shift: 'Shift',
          role: 'Role',
          employee: 'Employee',
          status: 'Status',
          openSlot: 'Open slot',
          assigned: 'Assigned',
          open: 'Open',
          noRoster: 'No roster generated yet.'
        },

        // Common
        save: 'Save',
        cancel: 'Cancel',
        choose: 'Choose',
        notes: 'Notes',
        loading: 'Loading...'
      }
    }
  }
}
