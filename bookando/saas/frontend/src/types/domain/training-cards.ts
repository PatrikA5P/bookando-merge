/**
 * Training Card (Ausbildungskarte) Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md Abschnitt 2.8
 *
 * TrainingCardTemplate → Chapter → Item (mit Medien)
 * TrainingCardAssignment → ItemProgress + Notes (personalisiert)
 *
 * Use-Case: Schweizer Fahrschule — Fahrlehrer bewertet Schueler pro Lektion,
 * fuegt Notizen/Skizzen hinzu, Schueler sieht Fortschritt im Portal.
 */

// ============================================================================
// ENUMS
// ============================================================================

export type GradingType = 'SLIDER' | 'BUTTONS' | 'STARS';

export type ItemProgressStatus = 'NOT_STARTED' | 'IN_PROGRESS' | 'COMPLETED' | 'SKIPPED';

export type AssignmentSource = 'AUTOMATION' | 'MANUAL';

export type AssignmentStatus = 'ACTIVE' | 'COMPLETED' | 'CANCELLED';

export type NoteType = 'TEXT' | 'IMAGE' | 'SKETCH' | 'VIDEO' | 'DOCUMENT';

export type MediaType = 'IMAGE' | 'VIDEO';

export type TemplateStatus = 'ACTIVE' | 'ARCHIVED';

// ============================================================================
// TRAINING CARD TEMPLATE (vom Admin erstellt)
// ============================================================================

export interface TrainingCardTemplate {
  id: string;
  organizationId: string;

  title: string;
  description?: string;

  /** Bewertungskonfiguration */
  gradingType: GradingType;
  gradingMin: number;
  gradingMax: number;
  gradingLabelMin?: string; // z.B. "Anfaenger"
  gradingLabelMax?: string; // z.B. "Experte"

  status: TemplateStatus;

  chapters: TrainingCardChapter[];

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// CHAPTER (Kapitel innerhalb einer Vorlage)
// ============================================================================

export interface TrainingCardChapter {
  id: string;
  templateId: string;
  title: string;
  sortOrder: number;
  items: TrainingCardItem[];
}

// ============================================================================
// ITEM (Skill/Lektion innerhalb eines Kapitels)
// ============================================================================

export interface TrainingCardItem {
  id: string;
  chapterId: string;

  title: string;
  description?: string;

  /** Verknuepfung mit Academy-Lektion (optional) */
  linkedLessonId?: string;
  linkedLessonTitle?: string;

  /** Medien auf Template-Ebene (fuer alle Schueler gleich) */
  media: TrainingCardItemMedia[];

  sortOrder: number;
}

export interface TrainingCardItemMedia {
  id: string;
  itemId: string;
  mediaType: MediaType;
  url: string;
  label?: string;
  sortOrder: number;
}

// ============================================================================
// ASSIGNMENT (Zuweisung an einen Kunden/Schueler)
// ============================================================================

export interface TrainingCardAssignment {
  id: string;
  templateId: string;
  templateTitle?: string;
  customerId: string;
  customerName?: string;

  /** Quelle der Zuweisung */
  assignedBy: AssignmentSource;
  assignedByEmployeeId?: string;
  assignedByEmployeeName?: string;
  triggerBookingId?: string;

  /** Status */
  status: AssignmentStatus;
  completedAt?: string;

  /** Berechneter Fortschritt */
  progressPercent: number;
  completedItemCount: number;
  totalItemCount: number;

  /** Detail-Fortschritt pro Item */
  itemProgress: TrainingCardItemProgress[];

  /** Persoenliche Notizen */
  notes: TrainingCardNote[];

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// ITEM PROGRESS (Bewertung pro Item pro Assignment)
// ============================================================================

export interface TrainingCardItemProgress {
  id: string;
  assignmentId: string;
  itemId: string;
  itemTitle?: string;
  chapterTitle?: string;

  /** Bewertung durch Mitarbeiter */
  grade?: number;
  evaluatedById?: string;
  evaluatedByName?: string;
  evaluatedAt?: string;

  /** Status */
  status: ItemProgressStatus;
}

// ============================================================================
// NOTE (Persoenliche Notizen/Medien pro Assignment)
// ============================================================================

export interface TrainingCardNote {
  id: string;
  assignmentId: string;
  itemId?: string; // null = Notiz zum ganzen Assignment

  noteType: NoteType;
  content: string; // Text oder URL (fuer Medien)

  createdById: string;
  createdByName?: string;

  /** Sichtbarkeit fuer Schueler */
  visibleToCustomer: boolean;

  createdAt: string;
}

// ============================================================================
// FORM DATA
// ============================================================================

export type TrainingCardTemplateFormData = Omit<TrainingCardTemplate, 'id' | 'organizationId' | 'createdAt' | 'updatedAt'> & {
  chapters: (Omit<TrainingCardChapter, 'id' | 'templateId' | 'items'> & {
    items: (Omit<TrainingCardItem, 'id' | 'chapterId' | 'media' | 'linkedLessonTitle'> & {
      media: Omit<TrainingCardItemMedia, 'id' | 'itemId'>[];
    })[];
  })[];
};

export interface TrainingCardNoteFormData {
  assignmentId: string;
  itemId?: string;
  noteType: NoteType;
  content: string;
  visibleToCustomer: boolean;
}

export interface ItemProgressUpdateData {
  assignmentId: string;
  itemId: string;
  grade?: number;
  status: ItemProgressStatus;
}
