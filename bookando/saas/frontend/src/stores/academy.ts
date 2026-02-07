/**
 * Academy Store — Kurse, Module, Lektionen, Quizze & Badges
 *
 * Refactored gemaess MODUL_ANALYSE.md Abschnitt 2.7:
 * - AcademyCourse mit Kurs → Modul → Lektion Hierarchie
 * - AcademyQuiz mit wiederverwendbaren Fragen
 * - Badge-System fuer Zertifizierungen
 * - CourseEnrollment fuer Teilnehmer-Tracking
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';
import type {
  AcademyCourse,
  AcademyModule,
  AcademyLesson,
  AcademyQuiz,
  QuizQuestion,
  Badge,
  CourseEnrollment,
  QuizAttempt,
  AcademyCourseFormData,
  AcademyLessonFormData,
  AcademyQuizFormData,
  CourseType,
  CourseVisibility,
  CourseDifficulty,
  CourseStatus,
  LessonType,
  QuestionType,
} from '@/types/domain/academy';

// Re-export domain types
export type {
  AcademyCourse,
  AcademyModule,
  AcademyLesson,
  AcademyQuiz,
  QuizQuestion,
  Badge,
  CourseEnrollment,
  QuizAttempt,
  AcademyCourseFormData,
  AcademyLessonFormData,
  AcademyQuizFormData,
  CourseType,
  CourseVisibility,
  CourseDifficulty,
  CourseStatus,
  LessonType,
  QuestionType,
};

// ============================================================================
// CONSTANTS
// ============================================================================

export const COURSE_TYPE_LABELS: Record<CourseType, string> = {
  ONLINE: 'Online',
  IN_PERSON: 'Vor Ort',
  BLENDED: 'Blended',
};

export const COURSE_VISIBILITY_LABELS: Record<CourseVisibility, string> = {
  PRIVATE: 'Privat',
  INTERNAL: 'Intern',
  PUBLIC: 'Oeffentlich',
};

export const COURSE_DIFFICULTY_LABELS: Record<CourseDifficulty, string> = {
  BEGINNER: 'Einsteiger',
  INTERMEDIATE: 'Fortgeschritten',
  ADVANCED: 'Experte',
};

export const COURSE_STATUS_LABELS: Record<CourseStatus, string> = {
  DRAFT: 'Entwurf',
  PUBLISHED: 'Veroeffentlicht',
  ARCHIVED: 'Archiviert',
};

export const COURSE_STATUS_COLORS: Record<CourseStatus, string> = {
  DRAFT: 'warning',
  PUBLISHED: 'success',
  ARCHIVED: 'info',
};

export const LESSON_TYPE_LABELS: Record<LessonType, string> = {
  VIDEO: 'Video',
  TEXT: 'Text',
  INTERACTIVE: 'Interaktiv',
  LIVE_SESSION: 'Live-Session',
};

export const QUESTION_TYPE_LABELS: Record<QuestionType, string> = {
  SINGLE_CHOICE: 'Einzelauswahl',
  MULTIPLE_CHOICE: 'Mehrfachauswahl',
  TRUE_FALSE: 'Wahr/Falsch',
  FREE_TEXT: 'Freitext',
};

export const ACADEMY_CATEGORIES = [
  { value: 'cat-driving-theory', label: 'Fahrtheorie' },
  { value: 'cat-driving-practice', label: 'Fahrpraxis' },
  { value: 'cat-safety', label: 'Sicherheit' },
  { value: 'cat-first-aid', label: 'Erste Hilfe' },
  { value: 'cat-regulations', label: 'Vorschriften' },
  { value: 'cat-soft-skills', label: 'Soft Skills' },
  { value: 'cat-business', label: 'Business' },
] as const;

// ============================================================================
// STORE
// ============================================================================

export const useAcademyStore = defineStore('academy', () => {
  // ── State ──────────────────────────────────────────────────────────────
  const courses = ref<AcademyCourse[]>([]);
  const enrollments = ref<CourseEnrollment[]>([]);
  const badges = ref<Badge[]>([
    { id: 'badge-001', name: 'Theorie Grundlagen', icon: 'book', color: '#3b82f6', description: 'Abschluss des Theorie-Grundlagenkurses', courseCount: 1 },
    { id: 'badge-002', name: 'Sicherheitsexperte', icon: 'shield', color: '#10b981', description: 'Sicherheitskurs erfolgreich abgeschlossen', courseCount: 1 },
    { id: 'badge-003', name: 'Erste Hilfe', icon: 'heart', color: '#f43f5e', description: 'Erste-Hilfe-Kurs zertifiziert', courseCount: 1 },
    { id: 'badge-004', name: 'Fahrprofi', icon: 'star', color: '#f59e0b', description: 'Alle Pflichtmodule erfolgreich absolviert', courseCount: 0 },
    { id: 'badge-005', name: 'Academy Star', icon: 'trophy', color: '#8b5cf6', description: 'Alle Kurse mit Bestnote abgeschlossen', courseCount: 0 },
  ]);

  const loading = ref(false);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // ── Getters ────────────────────────────────────────────────────────────
  const courseCount = computed(() => courses.value.length);
  const badgeCount = computed(() => badges.value.length);

  const publishedCourses = computed(() =>
    courses.value.filter(c => c.status === 'PUBLISHED')
  );

  const draftCourses = computed(() =>
    courses.value.filter(c => c.status === 'DRAFT')
  );

  const archivedCourses = computed(() =>
    courses.value.filter(c => c.status === 'ARCHIVED')
  );

  const totalLessonCount = computed(() =>
    courses.value.reduce((sum, c) =>
      sum + c.modules.reduce((mSum, m) => mSum + m.lessons.length, 0), 0)
  );

  const totalQuizCount = computed(() =>
    courses.value.reduce((sum, c) => sum + c.quizzes.length, 0)
  );

  const categories = computed(() => ACADEMY_CATEGORIES.map(c => ({ value: c.value, label: c.label })));

  // Backward compat
  const lessonCount = computed(() => totalLessonCount.value);

  // ── Fetch Actions ──────────────────────────────────────────────────────
  async function fetchCourses(): Promise<void> {
    try {
      const response = await api.get<{ data: AcademyCourse[] }>('/v1/courses', { per_page: 100 });
      courses.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Kurse konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchEnrollments(courseId?: string): Promise<void> {
    try {
      const params = courseId ? { courseId } : {};
      const response = await api.get<{ data: CourseEnrollment[] }>('/v1/enrollments', params);
      enrollments.value = response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Einschreibungen konnten nicht geladen werden';
      error.value = message;
      throw err;
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    isLoading.value = true;
    error.value = null;
    try {
      await fetchCourses();
    } catch {
      // fetchCourses already sets error.value
    } finally {
      loading.value = false;
      isLoading.value = false;
    }
  }

  // ── Course CRUD ────────────────────────────────────────────────────────
  function getCourseById(id: string): AcademyCourse | undefined {
    return courses.value.find(c => c.id === id);
  }

  async function addCourse(data: Partial<AcademyCourse>): Promise<AcademyCourse> {
    try {
      const response = await api.post<{ data: AcademyCourse }>('/v1/courses', data);
      courses.value.push(response.data);
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Kurs konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateCourse(id: string, data: Partial<AcademyCourse>): Promise<void> {
    try {
      const response = await api.put<{ data: AcademyCourse }>(`/v1/courses/${id}`, data);
      const idx = courses.value.findIndex(c => c.id === id);
      if (idx !== -1) {
        courses.value[idx] = response.data;
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Kurs konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteCourse(id: string): Promise<void> {
    try {
      await api.delete(`/v1/courses/${id}`);
      courses.value = courses.value.filter(c => c.id !== id);
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Kurs konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // ── Module CRUD ────────────────────────────────────────────────────────
  async function addModule(courseId: string, data: Omit<AcademyModule, 'id' | 'courseId' | 'lessons'>): Promise<AcademyModule> {
    try {
      const response = await api.post<{ data: AcademyModule }>(`/v1/courses/${courseId}/modules`, data);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        course.modules.push(response.data);
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Modul konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateModule(courseId: string, moduleId: string, data: Partial<AcademyModule>): Promise<void> {
    try {
      const response = await api.put<{ data: AcademyModule }>(`/v1/courses/${courseId}/modules/${moduleId}`, data);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        const idx = course.modules.findIndex(m => m.id === moduleId);
        if (idx !== -1) {
          course.modules[idx] = response.data;
        }
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Modul konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteModule(courseId: string, moduleId: string): Promise<void> {
    try {
      await api.delete(`/v1/courses/${courseId}/modules/${moduleId}`);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        course.modules = course.modules.filter(m => m.id !== moduleId);
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Modul konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // ── Lesson CRUD ────────────────────────────────────────────────────────
  async function addLesson(courseId: string, moduleId: string, data: Omit<AcademyLesson, 'id' | 'moduleId' | 'attachments'>): Promise<AcademyLesson> {
    try {
      const response = await api.post<{ data: AcademyLesson }>(`/v1/courses/${courseId}/modules/${moduleId}/lessons`, data);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        const mod = course.modules.find(m => m.id === moduleId);
        if (mod) {
          mod.lessons.push(response.data);
        }
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Lektion konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteLesson(courseId: string, moduleId: string, lessonId: string): Promise<void> {
    try {
      await api.delete(`/v1/lessons/${lessonId}`);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        const mod = course.modules.find(m => m.id === moduleId);
        if (mod) {
          mod.lessons = mod.lessons.filter(l => l.id !== lessonId);
        }
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Lektion konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // ── Quiz CRUD ──────────────────────────────────────────────────────────
  async function addQuiz(courseId: string, data: Omit<AcademyQuiz, 'id' | 'courseId'>): Promise<AcademyQuiz> {
    try {
      const response = await api.post<{ data: AcademyQuiz }>(`/v1/courses/${courseId}/quizzes`, data);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        course.quizzes.push(response.data);
      }
      return response.data;
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Quiz konnte nicht erstellt werden';
      error.value = message;
      throw err;
    }
  }

  async function updateQuiz(courseId: string, quizId: string, data: Partial<AcademyQuiz>): Promise<void> {
    try {
      const response = await api.put<{ data: AcademyQuiz }>(`/v1/courses/${courseId}/quizzes/${quizId}`, data);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        const idx = course.quizzes.findIndex(q => q.id === quizId);
        if (idx !== -1) {
          course.quizzes[idx] = response.data;
        }
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Quiz konnte nicht aktualisiert werden';
      error.value = message;
      throw err;
    }
  }

  async function deleteQuiz(courseId: string, quizId: string): Promise<void> {
    try {
      await api.delete(`/v1/courses/${courseId}/quizzes/${quizId}`);
      const course = courses.value.find(c => c.id === courseId);
      if (course) {
        course.quizzes = course.quizzes.filter(q => q.id !== quizId);
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Quiz konnte nicht geloescht werden';
      error.value = message;
      throw err;
    }
  }

  // ── Badge CRUD ─────────────────────────────────────────────────────────
  function getBadgeById(id: string): Badge | undefined {
    return badges.value.find(b => b.id === id);
  }

  function addBadge(badge: Omit<Badge, 'id' | 'courseCount'>): Badge {
    const newBadge: Badge = {
      ...badge,
      id: `badge-${Date.now()}`,
      courseCount: 0,
    };
    badges.value.push(newBadge);
    return newBadge;
  }

  function updateBadge(id: string, data: Partial<Badge>) {
    const idx = badges.value.findIndex(b => b.id === id);
    if (idx !== -1) {
      badges.value[idx] = { ...badges.value[idx], ...data };
    }
  }

  function deleteBadge(id: string) {
    badges.value = badges.value.filter(b => b.id !== id);
    courses.value.forEach(c => {
      if (c.badgeId === id) {
        c.badgeId = undefined;
        c.badgeName = undefined;
      }
    });
  }

  // ── Enrollment helpers ─────────────────────────────────────────────────
  function getEnrollmentsForCourse(courseId: string): CourseEnrollment[] {
    return enrollments.value.filter(e => e.courseId === courseId);
  }

  function getEnrollmentsForCustomer(customerId: string): CourseEnrollment[] {
    return enrollments.value.filter(e => e.customerId === customerId);
  }

  return {
    // State
    courses,
    enrollments,
    badges,
    loading,
    isLoading,
    error,

    // Getters
    courseCount,
    badgeCount,
    publishedCourses,
    draftCourses,
    archivedCourses,
    totalLessonCount,
    totalQuizCount,
    lessonCount,
    categories,

    // Fetch
    fetchCourses,
    fetchEnrollments,
    fetchAll,

    // Course CRUD
    getCourseById,
    addCourse,
    updateCourse,
    deleteCourse,

    // Module CRUD
    addModule,
    updateModule,
    deleteModule,

    // Lesson CRUD
    addLesson,
    deleteLesson,

    // Quiz CRUD
    addQuiz,
    updateQuiz,
    deleteQuiz,

    // Badge CRUD
    getBadgeById,
    addBadge,
    updateBadge,
    deleteBadge,

    // Enrollment helpers
    getEnrollmentsForCourse,
    getEnrollmentsForCustomer,
  };
});
