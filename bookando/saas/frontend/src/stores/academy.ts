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
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

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
  const courses = ref<Course[]>([
    {
      id: 'course-001',
      title: 'Grundlagen der Haarpflege',
      description: 'Einsteigerkurs fuer professionelle Haarpflege-Techniken. Lernen Sie die Basics von Waschen, Schneiden und Styling.',
      type: 'ONLINE',
      visibility: 'PUBLIC',
      difficulty: 'BEGINNER',
      categoryId: 'cat-hair',
      certificateEnabled: true,
      badgeId: 'badge-001',
      curriculum: [
        { id: 'ci-001', type: 'LESSON', title: 'Einleitung & Werkzeuge', duration: 30, order: 1 },
        { id: 'ci-002', type: 'LESSON', title: 'Haartypologie', duration: 45, order: 2 },
        { id: 'ci-003', type: 'QUIZ', title: 'Zwischenpruefung Basics', duration: 15, order: 3 },
        { id: 'ci-004', type: 'LESSON', title: 'Schnitttechniken Grundlagen', duration: 60, order: 4 },
        { id: 'ci-005', type: 'ASSIGNMENT', title: 'Praxisaufgabe: Basisschnitt', duration: 90, order: 5 },
      ],
      participantCount: 45,
      status: 'PUBLISHED',
    },
    {
      id: 'course-002',
      title: 'Farbtheorie & Koloristik',
      description: 'Fortgeschrittener Kurs zu Farbtechniken, Balayage, Highlights und Farbkorrekturen.',
      type: 'BLENDED',
      visibility: 'INTERNAL',
      difficulty: 'INTERMEDIATE',
      categoryId: 'cat-color',
      certificateEnabled: true,
      badgeId: 'badge-002',
      curriculum: [
        { id: 'ci-006', type: 'LESSON', title: 'Farbrad & Grundlagen', duration: 40, order: 1 },
        { id: 'ci-007', type: 'LESSON', title: 'Oxidationsfarben', duration: 50, order: 2 },
        { id: 'ci-008', type: 'QUIZ', title: 'Farbtheorie Test', duration: 20, order: 3 },
      ],
      participantCount: 28,
      status: 'PUBLISHED',
    },
    {
      id: 'course-003',
      title: 'Kundenberatung & Kommunikation',
      description: 'Professionelle Kundenberatung, Bedarfsanalyse und Kommunikation im Salon.',
      type: 'IN_PERSON',
      visibility: 'INTERNAL',
      difficulty: 'BEGINNER',
      categoryId: 'cat-soft',
      certificateEnabled: false,
      curriculum: [
        { id: 'ci-009', type: 'LESSON', title: 'Erstgespraech fuehren', duration: 45, order: 1 },
        { id: 'ci-010', type: 'LESSON', title: 'Bedarfsanalyse', duration: 30, order: 2 },
        { id: 'ci-011', type: 'ASSIGNMENT', title: 'Rollenspiel: Beratung', duration: 60, order: 3 },
      ],
      participantCount: 12,
      status: 'PUBLISHED',
    },
    {
      id: 'course-004',
      title: 'Hochsteckfrisuren Meisterklasse',
      description: 'Brautfrisuren, Abendfrisuren und kreative Hochstecktechniken fuer besondere Anlaesse.',
      type: 'IN_PERSON',
      visibility: 'PUBLIC',
      difficulty: 'ADVANCED',
      categoryId: 'cat-styling',
      certificateEnabled: true,
      badgeId: 'badge-003',
      curriculum: [
        { id: 'ci-012', type: 'LESSON', title: 'Grundtechniken Hochstecken', duration: 60, order: 1 },
        { id: 'ci-013', type: 'LESSON', title: 'Brautfrisuren Klassiker', duration: 90, order: 2 },
        { id: 'ci-014', type: 'QUIZ', title: 'Techniken Quiz', duration: 15, order: 3 },
        { id: 'ci-015', type: 'ASSIGNMENT', title: 'Portfolio: 3 Looks', duration: 180, order: 4 },
      ],
      participantCount: 8,
      status: 'DRAFT',
    },
    {
      id: 'course-005',
      title: 'Arbeitssicherheit im Salon',
      description: 'Pflichtschulung zu Hygiene, Chemikaliensicherheit und Ergonomie am Arbeitsplatz.',
      type: 'ONLINE',
      visibility: 'INTERNAL',
      difficulty: 'BEGINNER',
      categoryId: 'cat-safety',
      certificateEnabled: true,
      badgeId: 'badge-004',
      curriculum: [
        { id: 'ci-016', type: 'LESSON', title: 'Hygienevorschriften', duration: 30, order: 1 },
        { id: 'ci-017', type: 'LESSON', title: 'Chemikaliensicherheit', duration: 40, order: 2 },
        { id: 'ci-018', type: 'QUIZ', title: 'Sicherheitstest', duration: 20, order: 3 },
      ],
      participantCount: 52,
      status: 'PUBLISHED',
    },
    {
      id: 'course-006',
      title: 'Bartpflege & Rasurtechniken',
      description: 'Von der klassischen Nassrasur bis zum modernen Bartstyling.',
      type: 'BLENDED',
      visibility: 'PUBLIC',
      difficulty: 'INTERMEDIATE',
      categoryId: 'cat-barber',
      certificateEnabled: false,
      curriculum: [
        { id: 'ci-019', type: 'LESSON', title: 'Bartformen & Trends', duration: 25, order: 1 },
        { id: 'ci-020', type: 'LESSON', title: 'Nassrasur-Technik', duration: 45, order: 2 },
      ],
      participantCount: 19,
      status: 'ARCHIVED',
    },
  ]);

  const lessonGroups = ref<LessonGroup[]>([
    { id: 'group-basics', name: 'Grundlagen' },
    { id: 'group-technique', name: 'Techniken' },
    { id: 'group-safety', name: 'Sicherheit & Hygiene' },
  ]);

  const lessons = ref<Lesson[]>([
    { id: 'lesson-001', title: 'Werkzeugkunde: Scheren & Kaemme', content: 'Ueberblick ueber professionelle Werkzeuge im Salon. Pflege und richtige Handhabung.', type: 'TEXT', groupId: 'group-basics', groupName: 'Grundlagen', mediaUrls: [], duration: 25 },
    { id: 'lesson-002', title: 'Haartypologie verstehen', content: 'Die verschiedenen Haartypen und ihre Eigenschaften erkennen und behandeln.', type: 'TEXT', groupId: 'group-basics', groupName: 'Grundlagen', mediaUrls: [], duration: 30 },
    { id: 'lesson-003', title: 'Waschtechniken Praxis', content: 'Professionelle Haarwaesche: Kopfhautmassage, Temperatur, Produktwahl.', type: 'VIDEO', groupId: 'group-basics', groupName: 'Grundlagen', mediaUrls: ['https://videos.example.com/wash-techniques.mp4'], duration: 20 },
    { id: 'lesson-004', title: 'Schnitttechniken: Graduierung', content: 'Schrittweise Anleitung zur Graduierungstechnik mit Praxisbeispielen.', type: 'VIDEO', groupId: 'group-technique', groupName: 'Techniken', mediaUrls: ['https://videos.example.com/graduation-cut.mp4'], duration: 45 },
    { id: 'lesson-005', title: 'Folientechnik Highlights', content: 'Interaktives Modul: Folientechnik Schritt fuer Schritt mit Uebungen.', type: 'INTERACTIVE', groupId: 'group-technique', groupName: 'Techniken', mediaUrls: ['https://interactive.example.com/foil-technique'], duration: 60 },
    { id: 'lesson-006', title: 'Balayage Grundlagen', content: 'Freihandtechnik fuer natuerliche Farbverlaeufe. Theorie und Demonstration.', type: 'VIDEO', groupId: 'group-technique', groupName: 'Techniken', mediaUrls: ['https://videos.example.com/balayage-basics.mp4'], duration: 50 },
    { id: 'lesson-007', title: 'Hygienevorschriften CH', content: 'Schweizerische Hygienevorschriften fuer Coiffeursalons. Gesetzliche Grundlagen.', type: 'TEXT', groupId: 'group-safety', groupName: 'Sicherheit & Hygiene', mediaUrls: [], duration: 30 },
    { id: 'lesson-008', title: 'Umgang mit Chemikalien', content: 'Sicherheitsdatenblaetter, Schutzausruestung und Notfallmassnahmen.', type: 'TEXT', groupId: 'group-safety', groupName: 'Sicherheit & Hygiene', mediaUrls: [], duration: 35 },
    { id: 'lesson-009', title: 'Ergonomie am Arbeitsplatz', content: 'Interaktive Uebungen zur korrekten Koerperhaltung bei der Arbeit.', type: 'INTERACTIVE', groupId: 'group-safety', groupName: 'Sicherheit & Hygiene', mediaUrls: ['https://interactive.example.com/ergonomics'], duration: 25 },
    { id: 'lesson-010', title: 'Kundenberatung: Erstgespraech', content: 'Leitfaden fuer das professionelle Erstgespraech mit Neukunden.', type: 'TEXT', groupId: undefined, groupName: undefined, mediaUrls: [], duration: 40 },
  ]);

  const badges = ref<Badge[]>([
    { id: 'badge-001', name: 'Haarpflege Grundlagen', icon: 'scissors', color: '#f43f5e', description: 'Abschluss des Grundlagenkurses Haarpflege', courseCount: 1 },
    { id: 'badge-002', name: 'Koloristik Profi', icon: 'palette', color: '#8b5cf6', description: 'Zertifikat fuer Farbtheorie und Koloristik', courseCount: 1 },
    { id: 'badge-003', name: 'Hochsteck-Meister', icon: 'crown', color: '#f59e0b', description: 'Meisterklasse Hochsteckfrisuren absolviert', courseCount: 1 },
    { id: 'badge-004', name: 'Sicherheitsexperte', icon: 'shield', color: '#10b981', description: 'Arbeitssicherheit und Hygiene zertifiziert', courseCount: 1 },
    { id: 'badge-005', name: 'Academy Star', icon: 'star', color: '#3b82f6', description: 'Alle Pflichtschulungen erfolgreich abgeschlossen', courseCount: 0 },
  ]);

  const isLoading = ref(false);

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

  // ---- Actions ----

  function addCourse(course: Omit<Course, 'id' | 'participantCount'>): Course {
    const newCourse: Course = {
      ...course,
      id: `course-${Date.now()}`,
      participantCount: 0,
    };
    courses.value.push(newCourse);
    return newCourse;
  }

  function updateCourse(id: string, data: Partial<Course>) {
    const idx = courses.value.findIndex(c => c.id === id);
    if (idx !== -1) {
      courses.value[idx] = { ...courses.value[idx], ...data };
    }
  }

  function deleteCourse(id: string) {
    const idx = courses.value.findIndex(c => c.id === id);
    if (idx !== -1) {
      courses.value.splice(idx, 1);
    }
  }

  function getCourseById(id: string): Course | undefined {
    return courses.value.find(c => c.id === id);
  }

  function addLesson(lesson: Omit<Lesson, 'id'>): Lesson {
    const newLesson: Lesson = {
      ...lesson,
      id: `lesson-${Date.now()}`,
    };
    lessons.value.push(newLesson);
    return newLesson;
  }

  function updateLesson(id: string, data: Partial<Lesson>) {
    const idx = lessons.value.findIndex(l => l.id === id);
    if (idx !== -1) {
      lessons.value[idx] = { ...lessons.value[idx], ...data };
    }
  }

  function deleteLesson(id: string) {
    const idx = lessons.value.findIndex(l => l.id === id);
    if (idx !== -1) {
      lessons.value.splice(idx, 1);
    }
  }

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

    // Getters
    courseCount,
    lessonCount,
    badgeCount,
    publishedCourses,
    categories,

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
