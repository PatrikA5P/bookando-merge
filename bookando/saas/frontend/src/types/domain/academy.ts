/**
 * Academy Domain Types
 *
 * SOLL-Architektur gemaess MODUL_ANALYSE.md Abschnitt 2.7
 *
 * AcademyCourse → Module → Lesson (mit Attachments)
 * Quiz → Question (eigenstaendig, wiederverwendbar)
 * Badge
 */

// ============================================================================
// ENUMS
// ============================================================================

export type CourseType = 'ONLINE' | 'IN_PERSON' | 'BLENDED';
export type CourseVisibility = 'PRIVATE' | 'INTERNAL' | 'PUBLIC';
export type CourseDifficulty = 'BEGINNER' | 'INTERMEDIATE' | 'ADVANCED';
export type CourseStatus = 'DRAFT' | 'PUBLISHED' | 'ARCHIVED';

export type LessonType = 'VIDEO' | 'TEXT' | 'INTERACTIVE' | 'LIVE_SESSION';

export type QuestionType = 'SINGLE_CHOICE' | 'MULTIPLE_CHOICE' | 'TRUE_FALSE' | 'FREE_TEXT';

export type AttachmentType = 'IMAGE' | 'VIDEO' | 'DOCUMENT' | 'LINK';

// ============================================================================
// ACADEMY COURSE
// ============================================================================

export interface AcademyCourse {
  id: string;
  organizationId: string;

  title: string;
  description: string;
  coverImageUrl?: string;
  categoryId?: string;
  categoryName?: string;

  type: CourseType;
  visibility: CourseVisibility;
  difficulty: CourseDifficulty;
  status: CourseStatus;

  /** Zertifikat & Badge */
  certificateEnabled: boolean;
  badgeId?: string;
  badgeName?: string;

  /** Strukturiertes Curriculum */
  modules: AcademyModule[];

  /** Quizze */
  quizzes: AcademyQuiz[];

  /** Statistik */
  participantCount: number;
  completionRate?: number;

  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// ACADEMY MODULE (Gruppierung von Lessons = "Topics")
// ============================================================================

export interface AcademyModule {
  id: string;
  courseId: string;
  title: string;
  sortOrder: number;
  lessons: AcademyLesson[];
}

// ============================================================================
// ACADEMY LESSON
// ============================================================================

export interface AcademyLesson {
  id: string;
  moduleId: string;

  title: string;
  lessonType: LessonType;
  content?: string; // Markdown/HTML
  videoUrl?: string;
  durationMinutes?: number;
  sortOrder: number;

  /** Attachments */
  attachments: LessonAttachment[];
}

export interface LessonAttachment {
  id: string;
  lessonId: string;
  fileType: AttachmentType;
  url: string;
  name: string;
  description?: string;
  sortOrder: number;
}

// ============================================================================
// QUIZ & QUESTIONS
// ============================================================================

export interface AcademyQuiz {
  id: string;
  courseId: string;

  title: string;
  passingScore: number; // Prozent (z.B. 70)
  timeLimitMinutes?: number;
  maxAttempts: number;
  sortOrder: number;

  questions: QuizQuestion[];
}

export interface QuizQuestion {
  id: string;
  quizId: string;

  questionText: string;
  questionType: QuestionType;
  options?: QuizOption[];
  explanation?: string;
  points: number;
  sortOrder: number;
}

export interface QuizOption {
  text: string;
  isCorrect: boolean;
}

// ============================================================================
// BADGE
// ============================================================================

export interface Badge {
  id: string;
  name: string;
  icon: string;
  color: string;
  description: string;
  courseCount: number;
}

// ============================================================================
// ENROLLMENT (Kurs-Teilnahme)
// ============================================================================

export interface CourseEnrollment {
  id: string;
  courseId: string;
  customerId: string;
  customerName?: string;

  /** Fortschritt */
  completedLessonIds: string[];
  completedQuizIds: string[];
  progressPercent: number;

  /** Status */
  enrolledAt: string;
  completedAt?: string;
  expiresAt?: string;

  /** Ergebnis */
  certificateUrl?: string;
  badgeAwarded: boolean;
}

// ============================================================================
// QUIZ ATTEMPT
// ============================================================================

export interface QuizAttempt {
  id: string;
  quizId: string;
  customerId: string;
  answers: QuizAnswer[];
  score: number;
  passed: boolean;
  startedAt: string;
  completedAt?: string;
}

export interface QuizAnswer {
  questionId: string;
  selectedOptionIndices?: number[];
  freeTextAnswer?: string;
  isCorrect: boolean;
  pointsAwarded: number;
}

// ============================================================================
// FORM DATA
// ============================================================================

export type AcademyCourseFormData = Omit<AcademyCourse, 'id' | 'organizationId' | 'createdAt' | 'updatedAt' | 'participantCount' | 'completionRate' | 'categoryName' | 'badgeName'>;

export type AcademyLessonFormData = Omit<AcademyLesson, 'id' | 'attachments'> & {
  attachments?: Omit<LessonAttachment, 'id' | 'lessonId'>[];
};

export type AcademyQuizFormData = Omit<AcademyQuiz, 'id' | 'questions'> & {
  questions: Omit<QuizQuestion, 'id' | 'quizId'>[];
};
