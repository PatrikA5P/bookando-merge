export const messages = {
  de: {
    mod: {
      appointments: {
        title: 'Termine',
        actions: {
          new: 'Termin anlegen',
          assign: 'Zu Veranstaltung zuordnen',
          refresh: 'Aktualisieren'
        },
        timeline: {
          empty: 'Keine Termine im ausgewählten Zeitraum.',
          appointment_label: 'Termin',
          event_label: 'Veranstaltung',
          participants: '{count} Teilnehmer|{count} Teilnehmer',
          capacity: 'Kapazität: {used}/{max}',
          no_capacity: 'Keine Kapazitätsbegrenzung'
        },
        status: {
          pending: 'Ausstehend',
          approved: 'Genehmigt',
          confirmed: 'Bestätigt',
          cancelled: 'Storniert',
          noshow: 'Nicht erschienen'
        },
        labels: {
          unknown_service: 'Unbekannte Dienstleistung',
          unknown_customer: 'Unbekannter Kunde',
          event_type: 'Typ: {type}'
        },
        forms: {
          appointment: {
            title: 'Neuen Termin erstellen',
            customer: 'Kunde',
            service: 'Dienstleistung',
            starts_at: 'Beginn',
            ends_at: 'Ende',
            status: 'Status',
            persons: 'Personen',
            persons_hint: 'Anzahl der teilnehmenden Personen',
            note: 'Notiz',
            submit: 'Termin speichern',
            success: 'Der Termin wurde erfolgreich erstellt.',
            error: 'Der Termin konnte nicht gespeichert werden.'
          },
          assign: {
            title: 'Teilnehmer zuordnen',
            event: 'Veranstaltung/Kurs',
            period: 'Termin (optional)',
            customer: 'Kunde',
            service: 'Dienstleistung (optional)',
            starts_at: 'Beginn (falls kein Termin ausgewählt)',
            ends_at: 'Ende (optional)',
            status: 'Status',
            submit: 'Zu Veranstaltung hinzufügen',
            success: 'Der Kunde wurde der Veranstaltung hinzugefügt.',
            error: 'Die Zuordnung konnte nicht gespeichert werden.'
          }
        }
      }
    }
  },
  en: {
    mod: {
      appointments: {
        title: 'Appointments',
        actions: {
          new: 'Create appointment',
          assign: 'Assign to event/course',
          refresh: 'Refresh'
        },
        timeline: {
          empty: 'No appointments in the selected range.',
          appointment_label: 'Appointment',
          event_label: 'Event',
          participants: '{count} participant|{count} participants',
          capacity: 'Capacity: {used}/{max}',
          no_capacity: 'No capacity limit'
        },
        status: {
          pending: 'Pending',
          approved: 'Approved',
          confirmed: 'Confirmed',
          cancelled: 'Cancelled',
          noshow: 'No-show'
        },
        labels: {
          unknown_service: 'Unknown service',
          unknown_customer: 'Unknown customer',
          event_type: 'Type: {type}'
        },
        forms: {
          appointment: {
            title: 'Create new appointment',
            customer: 'Customer',
            service: 'Service',
            starts_at: 'Start',
            ends_at: 'End',
            status: 'Status',
            persons: 'Persons',
            persons_hint: 'Number of attendees',
            note: 'Note',
            submit: 'Save appointment',
            success: 'Appointment created successfully.',
            error: 'Failed to save the appointment.'
          },
          assign: {
            title: 'Assign attendee',
            event: 'Event/course',
            period: 'Scheduled session (optional)',
            customer: 'Customer',
            service: 'Service (optional)',
            starts_at: 'Start (if no session selected)',
            ends_at: 'End (optional)',
            status: 'Status',
            submit: 'Add to event',
            success: 'Customer was assigned to the event.',
            error: 'Assignment could not be saved.'
          }
        }
      }
    }
  },
  fr: {
    mod: {
      appointments: {
        title: 'Rendez-vous',
        actions: {
          new: 'Créer un rendez-vous',
          assign: 'Affecter à un évènement/cours',
          refresh: 'Actualiser'
        },
        timeline: {
          empty: 'Aucun rendez-vous sur la période sélectionnée.',
          appointment_label: 'Rendez-vous',
          event_label: 'Évènement',
          participants: '{count} participant·e|{count} participant·e·s',
          capacity: 'Capacité : {used}/{max}',
          no_capacity: 'Aucune limite de capacité'
        },
        status: {
          pending: 'En attente',
          approved: 'Approuvé',
          confirmed: 'Confirmé',
          cancelled: 'Annulé',
          noshow: 'Absent·e'
        },
        labels: {
          unknown_service: 'Prestation inconnue',
          unknown_customer: 'Client·e inconnu·e',
          event_type: 'Type : {type}'
        },
        forms: {
          appointment: {
            title: 'Créer un rendez-vous',
            customer: 'Client·e',
            service: 'Prestation',
            starts_at: 'Début',
            ends_at: 'Fin',
            status: 'Statut',
            persons: 'Personnes',
            persons_hint: 'Nombre de participant·e·s',
            note: 'Note',
            submit: 'Enregistrer le rendez-vous',
            success: 'Le rendez-vous a été créé avec succès.',
            error: 'Le rendez-vous n’a pas pu être enregistré.'
          },
          assign: {
            title: 'Affecter un·e participant·e',
            event: 'Évènement/cours',
            period: 'Séance (facultatif)',
            customer: 'Client·e',
            service: 'Prestation (facultatif)',
            starts_at: 'Début (si aucune séance n’est sélectionnée)',
            ends_at: 'Fin (facultatif)',
            status: 'Statut',
            submit: 'Ajouter à l’évènement',
            success: 'Le/la client·e a été ajouté·e à l’évènement.',
            error: 'L’affectation n’a pas pu être enregistrée.'
          }
        }
      }
    }
  },
  it: {
    mod: {
      appointments: {
        title: 'Appuntamenti',
        actions: {
          new: 'Crea appuntamento',
          assign: 'Assegna a evento/corso',
          refresh: 'Aggiorna'
        },
        timeline: {
          empty: 'Nessun appuntamento nell’intervallo selezionato.',
          appointment_label: 'Appuntamento',
          event_label: 'Evento',
          participants: '{count} partecipante|{count} partecipanti',
          capacity: 'Capienza: {used}/{max}',
          no_capacity: 'Nessun limite di capienza'
        },
        status: {
          pending: 'In attesa',
          approved: 'Approvato',
          confirmed: 'Confermato',
          cancelled: 'Annullato',
          noshow: 'Assente'
        },
        labels: {
          unknown_service: 'Servizio sconosciuto',
          unknown_customer: 'Cliente sconosciuto',
          event_type: 'Tipo: {type}'
        },
        forms: {
          appointment: {
            title: 'Crea nuovo appuntamento',
            customer: 'Cliente',
            service: 'Servizio',
            starts_at: 'Inizio',
            ends_at: 'Fine',
            status: 'Stato',
            persons: 'Persone',
            persons_hint: 'Numero di partecipanti',
            note: 'Nota',
            submit: 'Salva appuntamento',
            success: 'Appuntamento creato con successo.',
            error: 'Impossibile salvare l’appuntamento.'
          },
          assign: {
            title: 'Assegna partecipante',
            event: 'Evento/corso',
            period: 'Sessione pianificata (facoltativa)',
            customer: 'Cliente',
            service: 'Servizio (facoltativo)',
            starts_at: 'Inizio (se nessuna sessione selezionata)',
            ends_at: 'Fine (facoltativa)',
            status: 'Stato',
            submit: 'Aggiungi all’evento',
            success: 'Il cliente è stato aggiunto all’evento.',
            error: 'Impossibile salvare l’assegnazione.'
          }
        }
      }
    }
  },
  es: {
    mod: {
      appointments: {
        title: 'Citas',
        actions: {
          new: 'Crear cita',
          assign: 'Asignar a evento/curso',
          refresh: 'Actualizar'
        },
        timeline: {
          empty: 'No hay citas en el periodo seleccionado.',
          appointment_label: 'Cita',
          event_label: 'Evento',
          participants: '{count} participante|{count} participantes',
          capacity: 'Capacidad: {used}/{max}',
          no_capacity: 'Sin límite de capacidad'
        },
        status: {
          pending: 'Pendiente',
          approved: 'Aprobada',
          confirmed: 'Confirmada',
          cancelled: 'Cancelada',
          noshow: 'Ausente'
        },
        labels: {
          unknown_service: 'Servicio desconocido',
          unknown_customer: 'Cliente desconocido',
          event_type: 'Tipo: {type}'
        },
        forms: {
          appointment: {
            title: 'Crear nueva cita',
            customer: 'Cliente',
            service: 'Servicio',
            starts_at: 'Inicio',
            ends_at: 'Fin',
            status: 'Estado',
            persons: 'Personas',
            persons_hint: 'Número de asistentes',
            note: 'Nota',
            submit: 'Guardar cita',
            success: 'Cita creada correctamente.',
            error: 'No se pudo guardar la cita.'
          },
          assign: {
            title: 'Asignar asistente',
            event: 'Evento/curso',
            period: 'Sesión programada (opcional)',
            customer: 'Cliente',
            service: 'Servicio (opcional)',
            starts_at: 'Inicio (si no se selecciona sesión)',
            ends_at: 'Fin (opcional)',
            status: 'Estado',
            submit: 'Añadir al evento',
            success: 'El cliente se añadió al evento.',
            error: 'No se pudo guardar la asignación.'
          }
        }
      }
    }
  }
}
