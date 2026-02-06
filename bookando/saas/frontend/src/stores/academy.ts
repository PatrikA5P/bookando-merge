/**
 * Academy Store — Kurse, Lektionen, Quizze & Badges
 *
 * Pinia Store fuer das Academy-Modul.
 * Verwaltet Kurse mit Curriculum, Lektionen-Bibliothek
 * und Badge-System fuer Zertifizierungen.
 *
 * Verbesserung gegenueber Referenz:
 * - Pinia statt monolithischem Context
 * - Typisierte Enums fuer Type, Visibility, Difficulty
 * - Curriculum als geordnete Liste mit Drag-Support
 * - Badge-Verknuepfung mit Kursen
 * - Echte API-Anbindung statt Mock-Daten
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

// ============================================================================
// TYPES & ENUMS
// ============================================================================

export type CourseType = 'ONLINE' | 'IN_PERSON' | 'BLENDED';
export type CourseVisibility = 'PRIVATE' | 'INTERNAL' | 'PUBLIC';
export type CourseDifficulty = 'BEGINNER' | 'INTERMEDIATE' | 'ADVANCED';
export type CourseStatus = 'DRAFT' | 'PUBLISHED' | 'ARCHIVED';
export type CurriculumItemType = 'LESSON' | 'QUIZ' | 'ASSIGNMENT';
export type LessonType = 'TEXT' | 'VIDEO' | 'INTERACTIVE';

export interface CurriculumItem {
  id: string;
  type: CurriculumItemType;
  title: string;
  duration: number;
  order: number;
}

export interface Course {
  id: string;
  title: string;
  description: string;
  type: CourseType;
  visibility: CourseVisibility;
  difficulty: CourseDifficulty;
  categoryId: string;
  image?: string;
  certificateEnabled: boolean;
  badgeId?: string;
  curriculum: CurriculumItem[];
  participantCount: number;
  status: CourseStatus;
}

export interface Lesson {
  id: string;
  title: string;
  content: string;
  type: LessonType;
  groupId?: string;
  groupName?: string;
  mediaUrls: string[];
  duration: number;
}

export interface LessonGroup {
  id: string;
  name: string;
}

export interface Badge {
  id: string;
  name: string;
  icon: string;
  color: string;
  description: string;
  courseCount: number;
}

// ============================================================================
// STORE
// ============================================================================

export const useAcademyStore = defineStore('academy', () => {
  // ---- State ----
  const courses = ref<Course[]>([]);
  const lessons = ref<Lesson[]>([]);

  // Lesson groups: local state (no backend endpoint)
  const lessonGroups = ref<LessonGroup[]>([
    { id: 'group-basics', name: 'Grundlagen' },
    { id: 'group-technique', name: 'Techniken' },
    { id: 'group-safety', name: 'Sicherheit & Hygiene' },
  ]);

  // Badges: local state with mock data (no backend endpoint)
  const badges = ref<Badge[]>([
    { id: 'badge-001', name: 'Haarpflege Grundlagen', icon: 'scissors', color: '#f43f5e', description: 'Abschluss des Grundlagenkurses Haarpflege', courseCount: 1 },
    { id: 'badge-002', name: 'Koloristik Profi', icon: 'palette', color: '#8b5cf6', description: 'Zertifikat fuer Farbtheorie und Koloristik', courseCount: 1 },
    { id: 'badge-003', name: 'Hochsteck-Meister', icon: 'crown', color: '#f59e0b', description: 'Meisterklasse Hochsteckfrisuren absolviert', courseCount: 1 },
    { id: 'badge-004', name: 'Sicherheitsexperte', icon: 'shield', color: '#10b981', description: 'Arbeitssicherheit und Hygiene zertifiziert', courseCount: 1 },
    { id: 'badge-005', name: 'Academy Star', icon: 'star', color: '#3b82f6', description: 'Alle Pflichtschulungen erfolgreich abgeschlossen', courseCount: 0 },
  ]);

  const isLoading = ref(false);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // ---- Getters ----
  const courseCount = computed(() => courses.value.length);
  const lessonCount = computed(() => lessons.value.length);
  const badgeCount = computed(() => badges.value.length);

  const publishedCourses = computed(() =>
    courses.value.filter(c => c.status === 'PUBLISHED')
  );

  const categories = computed(() => {
    const map: Record<string, string> = {
      'cat-hair': 'Haarpflege',
      'cat-color': 'Koloristik',
      'cat-soft': 'Soft Skills',
      'cat-styling': 'Styling',
      'cat-safety': 'Sicherheit',
      'cat-barber': 'Barber',
    };
    return Object.entries(map).map(([value, label]) => ({ value, label }));
  });

  // ---- Fetch Actions ----

  async function fetchCourses(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get<{ data: Course[] }>('/v1/courses', { per_page: 100 });
      courses.value = response.data;
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to fetch courses';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function fetchLessons(courseId: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.get<{ data: Lesson[] }>(
        `/v1/courses/${courseId}/lessons`,
        { per_page: 100 },
      );
      const fetched = response.data;
      // Merge: replace existing lessons by id, add new ones
      const fetchedIds = new Set(fetched.map(l => l.id));
      lessons.value = [
        ...lessons.value.filter(l => !fetchedIds.has(l.id)),
        ...fetched,
      ];
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to fetch lessons';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    isLoading.value = true;
    error.value = null;
    try {
      // Fetch all courses
      const coursesResponse = await api.get<{ data: Course[] }>('/v1/courses', { per_page: 100 });
      courses.value = coursesResponse.data;

      // Fetch lessons for each course in parallel
      if (courses.value.length > 0) {
        const lessonResponses = await Promise.all(
          courses.value.map(course =>
            api.get<{ data: Lesson[] }>(
              `/v1/courses/${course.id}/lessons`,
              { per_page: 100 },
            ),
          ),
        );
        lessons.value = lessonResponses.flatMap(r => r.data);
      }
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to fetch academy data';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
      isLoading.value = false;
    }
  }

  // ---- Course Actions ----

  async function addCourse(course: Omit<Course, 'id' | 'participantCount'>): Promise<Course> {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.post<{ data: Course }>('/v1/courses', course);
      const newCourse = response.data;
      courses.value.push(newCourse);
      return newCourse;
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to create course';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updateCourse(id: string, data: Partial<Course>): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.put<{ data: Course }>(`/v1/courses/${id}`, data);
      const idx = courses.value.findIndex(c => c.id === id);
      if (idx !== -1) {
        courses.value[idx] = response.data;
      }
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to update course';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteCourse(id: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await api.delete(`/v1/courses/${id}`);
      const idx = courses.value.findIndex(c => c.id === id);
      if (idx !== -1) {
        courses.value.splice(idx, 1);
      }
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to delete course';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  function getCourseById(id: string): Course | undefined {
    return courses.value.find(c => c.id === id);
  }

  // ---- Lesson Actions ----

  async function addLesson(lesson: Omit<Lesson, 'id'>, courseId?: string): Promise<Lesson> {
    loading.value = true;
    error.value = null;
    try {
      if (courseId) {
        const response = await api.post<{ data: Lesson }>(
          `/v1/courses/${courseId}/lessons`,
          lesson,
        );
        const newLesson = response.data;
        lessons.value.push(newLesson);
        return newLesson;
      }
      // Local-only lesson (library item, not yet attached to a course)
      const newLesson: Lesson = {
        ...lesson,
        id: `lesson-${Date.now()}`,
      };
      lessons.value.push(newLesson);
      return newLesson;
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to create lesson';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function updateLesson(id: string, data: Partial<Lesson>): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      const response = await api.put<{ data: Lesson }>(`/v1/lessons/${id}`, data);
      const idx = lessons.value.findIndex(l => l.id === id);
      if (idx !== -1) {
        lessons.value[idx] = response.data;
      }
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to update lesson';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function deleteLesson(id: string): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await api.delete(`/v1/lessons/${id}`);
      const idx = lessons.value.findIndex(l => l.id === id);
      if (idx !== -1) {
        lessons.value.splice(idx, 1);
      }
    } catch (e: unknown) {
      const message = e instanceof Error ? e.message : 'Failed to delete lesson';
      error.value = message;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  // ---- Lesson Group Actions (local state only) ----

  function addLessonGroup(name: string): LessonGroup {
    const group: LessonGroup = {
      id: `group-${Date.now()}`,
      name,
    };
    lessonGroups.value.push(group);
    return group;
  }

  function renameLessonGroup(id: string, name: string) {
    const group = lessonGroups.value.find(g => g.id === id);
    if (group) {
      group.name = name;
      // Update groupName in all lessons of this group
      lessons.value.forEach(l => {
        if (l.groupId === id) {
          l.groupName = name;
        }
      });
    }
  }

  // ---- Badge Actions (local state only) ----

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
    const idx = badges.value.findIndex(b => b.id === id);
    if (idx !== -1) {
      badges.value.splice(idx, 1);
    }
    // Remove badge from courses
    courses.value.forEach(c => {
      if (c.badgeId === id) {
        c.badgeId = undefined;
      }
    });
  }

  function getBadgeById(id: string): Badge | undefined {
    return badges.value.find(b => b.id === id);
  }

  return {
    // State
    courses,
    lessons,
    lessonGroups,
    badges,
    isLoading,
    loading,
    error,

    // Getters
    courseCount,
    lessonCount,
    badgeCount,
    publishedCourses,
    categories,

    // Actions — Fetch
    fetchCourses,
    fetchLessons,
    fetchAll,

    // Actions — Courses
    addCourse,
    updateCourse,
    deleteCourse,
    getCourseById,

    // Actions — Lessons
    addLesson,
    updateLesson,
    deleteLesson,
    addLessonGroup,
    renameLessonGroup,

    // Actions — Badges
    addBadge,
    updateBadge,
    deleteBadge,
    getBadgeById,
  };
});
