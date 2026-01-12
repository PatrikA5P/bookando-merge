import http from '@assets/http'

// Legacy type for backward compatibility
export type AcademyCourseModule = {
  title: string
  goal: string
}

// New comprehensive course structure
export type CourseType = 'online' | 'physical'
export type CourseVisibility = 'public' | 'logged_in' | 'private'
export type CourseLevel = 'beginner' | 'intermediate' | 'advanced'

export type QuestionType =
  | 'quiz_single'
  | 'quiz_multiple'
  | 'true_false'
  | 'slider'
  | 'pin_answer'
  | 'essay'
  | 'fill_blank'
  | 'short_answer'
  | 'matching'
  | 'image_answer'
  | 'sorting'
  | 'puzzle'

export type QuizOption = {
  text: string
  correct: boolean
}

export type PinLocation = {
  x: number
  y: number
  tolerance: number
}

export type MatchingPair = {
  left: string
  right: string
}

export type PuzzleMatch = {
  left_index: number
  right_index: number
}

export type QuestionData = {
  // Quiz (single/multiple)
  options?: QuizOption[]

  // True/False
  correct_answer?: boolean

  // Slider
  min?: number
  max?: number
  step?: number
  correct_value?: number | null
  correct_range_min?: number | null
  correct_range_max?: number | null

  // Pin answer
  image_url?: string
  pins?: PinLocation[]

  // Essay / Short answer
  sample_answer?: string

  // Fill blank
  text_with_blanks?: string
  answers?: string[]

  // Matching
  pairs?: MatchingPair[]

  // Sorting
  items?: string[]

  // Puzzle
  left_items?: string[]
  right_items?: string[]
  correct_matches?: PuzzleMatch[]
}

export type Question = {
  id?: string
  type: QuestionType
  question_text: string
  time_limit: number | null
  points: number
  data: QuestionData
}

export type QuizSettings = {
  attempts_allowed: number | null // null = unlimited
  pass_percentage: number
  questions_to_show: number | null // null = all
  randomize_answers: boolean
  question_layout: 'single' | 'all'
  show_feedback: 'immediate' | 'end'
}

export type Quiz = {
  id?: string
  title: string
  summary: string
  questions: Question[]
  settings: QuizSettings
}

export type Lesson = {
  id?: string
  title: string
  content: string
  images: string[]
  videos: string[]
  files: string[]
}

export type Topic = {
  id?: string
  title: string
  summary: string
  lessons: Lesson[]
  quizzes: Quiz[]
}

export type AcademyCourse = {
  id?: string

  // Tab 1: Kursdefinition - Grundlegende Informationen
  title: string
  description: string
  course_type: CourseType
  author: string

  // Tab 1: Teilnahme & Sichtbarkeit
  max_participants: number | null
  visibility: CourseVisibility
  display_from: string | null
  display_until: string | null

  // Tab 1: Kategorisierung
  level: CourseLevel
  category: string
  tags: string[]

  // Tab 1: Medien
  featured_image: string | null
  intro_video: string | null

  // Tab 2: Kursplanung
  sequential_topics: boolean
  topics: Topic[]

  // Legacy fields (for compatibility)
  duration_minutes?: number
  modules?: AcademyCourseModule[]

  // Meta
  created_at?: string
  updated_at?: string
}

export type TrainingMilestone = {
  title: string
  completed: boolean
  completed_at: string | null
}

// Erweiterte Ausbildungskarten-Strukturen
export type TrainingLessonResource = {
  type: 'image' | 'video' | 'course_link' | 'lesson_link'
  url?: string // Für Bild/Video URLs
  course_id?: string // Für Kursverknüpfung
  topic_id?: string // Für Themenverknüpfung
  lesson_id?: string // Für Lektionsverknüpfung
  title: string
  description?: string
}

export type TrainingLesson = {
  id: string
  title: string
  completed: boolean
  completed_at: string | null
  notes?: string
  resources: TrainingLessonResource[]
}

export type TrainingMainTopic = {
  id: string
  title: string
  order: number // Für Drag & Drop Sortierung
  lessons: TrainingLesson[]
}

export type TrainingCard = {
  id?: string
  student: string
  instructor: string
  program: string // z.B. "Kategorie B" oder "Kategorie A"
  category?: 'A' | 'B' // Neue Kategorisierung
  progress: number
  notes: string
  milestones: TrainingMilestone[]
  main_topics: TrainingMainTopic[] // Neue erweiterte Struktur
  created_at?: string
  updated_at?: string
}

export type AcademyState = {
  courses: AcademyCourse[]
  training_cards: TrainingCard[]
}

const BASE_URL = (window as any).BOOKANDO_VARS?.rest_url || '/wp-json/bookando/v1/academy'

export async function fetchState(): Promise<AcademyState> {
  const { data } = await http.get<AcademyState>(`${BASE_URL}/state`)
  return data
}

export async function saveCourse(course: Partial<AcademyCourse>): Promise<AcademyCourse> {
  const { data } = await http.post<AcademyCourse>(`${BASE_URL}/courses`, course)
  return data
}

export async function deleteCourse(id: string): Promise<boolean> {
  console.log('[AcademyApi] deleteCourse called with ID:', id, 'Type:', typeof id)
  console.log('[AcademyApi] Request URL:', `${BASE_URL}/courses/${id}`)

  try {
    const { data } = await http.delete<{ deleted: boolean }>(`${BASE_URL}/courses/${id}`)
    console.log('[AcademyApi] Delete response:', data)
    return !!data?.deleted
  } catch (error) {
    console.error('[AcademyApi] Delete request failed:', error)
    throw error
  }
}

export async function saveTrainingCard(card: Partial<TrainingCard>): Promise<TrainingCard> {
  const { data } = await http.post<TrainingCard>(`${BASE_URL}/training_cards`, card)
  return data
}

export async function deleteTrainingCard(id: string): Promise<boolean> {
  const { data } = await http.delete<{ deleted: boolean }>(`${BASE_URL}/training_cards/${id}`)
  return !!data?.deleted
}

export async function updateTrainingProgress(id: string, payload: { progress: number; milestones?: TrainingMilestone[] }): Promise<TrainingCard> {
  const { data } = await http.post<TrainingCard>(`${BASE_URL}/training_cards_progress`, { id, ...payload })
  return data
}

// ===== HELPER FUNCTIONS: Default Templates =====

/**
 * Erstellt eine Standard-Ausbildungskarte für Kategorie B
 */
export function getDefaultTrainingCardKategorieB(): Omit<TrainingCard, 'id' | 'created_at' | 'updated_at'> {
  return {
    student: '',
    instructor: '',
    program: 'Kategorie B',
    category: 'B',
    progress: 0,
    notes: '',
    milestones: [],
    main_topics: [
      {
        id: 'vorschulung',
        title: 'Vorschulung',
        order: 1,
        lessons: [
          { id: 'v1', title: 'Rundumkontrolle Vorbereitung im Stand', completed: false, completed_at: null, resources: [] },
          { id: 'v2', title: 'Anfahren und Anhalten in der Ebene (manuelle Getriebe)', completed: false, completed_at: null, resources: [] },
          { id: 'v3', title: 'Blicktechnik und Lenken', completed: false, completed_at: null, resources: [] },
          { id: 'v4', title: 'Schalten aller Gänge (manuelle Getriebe)', completed: false, completed_at: null, resources: [] },
          { id: 'v5', title: 'Bremsen', completed: false, completed_at: null, resources: [] },
          { id: 'v6', title: 'Abbiegen rechts und links / zeitliche Faktoren', completed: false, completed_at: null, resources: [] },
          { id: 'v7', title: 'Fahrzeugbedienung beim Rückwärtsfahren', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'grundschulung',
        title: 'Grundschulung',
        order: 2,
        lessons: [
          { id: 'g1', title: 'Grundlagen Blickführung', completed: false, completed_at: null, resources: [] },
          { id: 'g2', title: 'Abbiegen / Blicktechnik an Verzweigungen', completed: false, completed_at: null, resources: [] },
          { id: 'g3', title: 'Bremsbereitschaft / Sichtpunktfahren', completed: false, completed_at: null, resources: [] },
          { id: 'g4', title: 'Fahrbahnbenützung', completed: false, completed_at: null, resources: [] },
          { id: 'g5', title: 'Kreisverkehrsplatz', completed: false, completed_at: null, resources: [] },
          { id: 'g6', title: 'Benützung von Fahrstreifen, Einspurstrecken, Radstreifen', completed: false, completed_at: null, resources: [] },
          { id: 'g7', title: 'Verhalten bei Lichtsignalen', completed: false, completed_at: null, resources: [] },
          { id: 'g8', title: 'Einfügen im Verkehr', completed: false, completed_at: null, resources: [] },
          { id: 'g9', title: 'Verkehre in Steigungen und Gefällen', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'hauptschulung',
        title: 'Hauptschulung',
        order: 3,
        lessons: [
          { id: 'h1', title: 'Vortritt', completed: false, completed_at: null, resources: [] },
          { id: 'h2', title: 'Verkehrspartner / 3-A-Training', completed: false, completed_at: null, resources: [] },
          { id: 'h3', title: 'Fahrstreifen und Spurwechsel', completed: false, completed_at: null, resources: [] },
          { id: 'h4', title: 'Überholen und Vorbeifahren', completed: false, completed_at: null, resources: [] },
          { id: 'h5', title: 'Verhalten gegenüber ÖV und Bahnübergänge', completed: false, completed_at: null, resources: [] },
          { id: 'h6', title: 'Mithalten / Abstände / Kolonnenfahren', completed: false, completed_at: null, resources: [] },
          { id: 'h7', title: 'Fahren auf besonderen Strassen', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'perfektionsschulung',
        title: 'Perfektionsschulung',
        order: 4,
        lessons: [
          { id: 'p1', title: 'Fahren nach Wegweisern', completed: false, completed_at: null, resources: [] },
          { id: 'p2', title: 'Fahren auf Autobahn und Autostrassen', completed: false, completed_at: null, resources: [] },
          { id: 'p3', title: 'Fahren bei Nacht und schlechter Sicht', completed: false, completed_at: null, resources: [] },
          { id: 'p4', title: 'Schwierige Verkehrspartner / -situationen', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'fahrmanoever',
        title: 'Fahrmanöver',
        order: 5,
        lessons: [
          { id: 'f1', title: 'Sichern des Fahrzeuges in Steigung und Gefälle', completed: false, completed_at: null, resources: [] },
          { id: 'f2', title: 'Rückwärtsfahren', completed: false, completed_at: null, resources: [] },
          { id: 'f3', title: 'Wenden', completed: false, completed_at: null, resources: [] },
          { id: 'f4', title: 'Parkieren rechtwinklig vorwärts', completed: false, completed_at: null, resources: [] },
          { id: 'f5', title: 'Parkieren rechtwinklig rückwärts', completed: false, completed_at: null, resources: [] },
          { id: 'f6', title: 'Parkieren seitwärts', completed: false, completed_at: null, resources: [] },
          { id: 'f7', title: 'Schnelle sichere Bremsung / Notbremsung', completed: false, completed_at: null, resources: [] },
        ]
      },
    ]
  }
}

/**
 * Erstellt eine Standard-Ausbildungskarte für Kategorie A (Motorrad)
 */
export function getDefaultTrainingCardKategorieA(): Omit<TrainingCard, 'id' | 'created_at' | 'updated_at'> {
  return {
    student: '',
    instructor: '',
    program: 'Kategorie A',
    category: 'A',
    progress: 0,
    notes: '',
    milestones: [],
    main_topics: [
      {
        id: 'vorschulung_a',
        title: 'Vorschulung',
        order: 1,
        lessons: [
          { id: 'va1', title: 'Motorrad aufstellen und sichern', completed: false, completed_at: null, resources: [] },
          { id: 'va2', title: 'Auf- und Absteigen, Fahrzeugkontrolle', completed: false, completed_at: null, resources: [] },
          { id: 'va3', title: 'Anfahren und Anhalten', completed: false, completed_at: null, resources: [] },
          { id: 'va4', title: 'Blicktechnik und Lenken', completed: false, completed_at: null, resources: [] },
          { id: 'va5', title: 'Schalten aller Gänge', completed: false, completed_at: null, resources: [] },
          { id: 'va6', title: 'Bremsen (Vorder- und Hinterradbremse)', completed: false, completed_at: null, resources: [] },
          { id: 'va7', title: 'Gleichgewicht und Fahrzeugbeherrschung', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'grundschulung_a',
        title: 'Grundschulung',
        order: 2,
        lessons: [
          { id: 'ga1', title: 'Grundlagen Blickführung', completed: false, completed_at: null, resources: [] },
          { id: 'ga2', title: 'Kurventechnik und Schräglage', completed: false, completed_at: null, resources: [] },
          { id: 'ga3', title: 'Abbiegen / Blicktechnik an Verzweigungen', completed: false, completed_at: null, resources: [] },
          { id: 'ga4', title: 'Bremsbereitschaft / Sichtpunktfahren', completed: false, completed_at: null, resources: [] },
          { id: 'ga5', title: 'Fahrbahnbenützung / Spurwahl', completed: false, completed_at: null, resources: [] },
          { id: 'ga6', title: 'Kreisverkehrsplatz', completed: false, completed_at: null, resources: [] },
          { id: 'ga7', title: 'Verhalten bei Lichtsignalen', completed: false, completed_at: null, resources: [] },
          { id: 'ga8', title: 'Einfügen im Verkehr', completed: false, completed_at: null, resources: [] },
          { id: 'ga9', title: 'Verkehre in Steigungen und Gefällen', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'hauptschulung_a',
        title: 'Hauptschulung',
        order: 3,
        lessons: [
          { id: 'ha1', title: 'Vortritt', completed: false, completed_at: null, resources: [] },
          { id: 'ha2', title: 'Verkehrspartner / 3-A-Training', completed: false, completed_at: null, resources: [] },
          { id: 'ha3', title: 'Fahrstreifen und Spurwechsel', completed: false, completed_at: null, resources: [] },
          { id: 'ha4', title: 'Überholen und Vorbeifahren', completed: false, completed_at: null, resources: [] },
          { id: 'ha5', title: 'Verhalten gegenüber ÖV und Bahnübergänge', completed: false, completed_at: null, resources: [] },
          { id: 'ha6', title: 'Mithalten / Abstände / Kolonnenfahren', completed: false, completed_at: null, resources: [] },
          { id: 'ha7', title: 'Fahren auf besonderen Strassen', completed: false, completed_at: null, resources: [] },
          { id: 'ha8', title: 'Fahren bei unterschiedlichen Witterungsverhältnissen', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'perfektionsschulung_a',
        title: 'Perfektionsschulung',
        order: 4,
        lessons: [
          { id: 'pa1', title: 'Fahren nach Wegweisern', completed: false, completed_at: null, resources: [] },
          { id: 'pa2', title: 'Fahren auf Autobahn und Autostrassen', completed: false, completed_at: null, resources: [] },
          { id: 'pa3', title: 'Fahren bei Nacht und schlechter Sicht', completed: false, completed_at: null, resources: [] },
          { id: 'pa4', title: 'Schwierige Verkehrspartner / -situationen', completed: false, completed_at: null, resources: [] },
          { id: 'pa5', title: 'Defensive und vorausschauende Fahrweise', completed: false, completed_at: null, resources: [] },
        ]
      },
      {
        id: 'fahrmanoever_a',
        title: 'Fahrmanöver',
        order: 5,
        lessons: [
          { id: 'fa1', title: 'Sichern des Motorrades in Steigung und Gefälle', completed: false, completed_at: null, resources: [] },
          { id: 'fa2', title: 'Slalom und Gleichgewichtsübungen', completed: false, completed_at: null, resources: [] },
          { id: 'fa3', title: 'Wenden auf engem Raum', completed: false, completed_at: null, resources: [] },
          { id: 'fa4', title: 'Rangiermanöver / Rückwärtsfahren mit Begleitung', completed: false, completed_at: null, resources: [] },
          { id: 'fa5', title: 'Vollbremsung / Gefahrenbremsung', completed: false, completed_at: null, resources: [] },
          { id: 'fa6', title: 'Ausweichmanöver', completed: false, completed_at: null, resources: [] },
        ]
      },
    ]
  }
}

/**
 * Vollständiger Kurs für Kategorie B (PKW) im Kurse-Tab
 */
export function getDefaultCourseKategorieB(): Omit<AcademyCourse, 'id' | 'created_at' | 'updated_at'> {
  return {
    title: 'Fahrausbildung Kategorie B (PKW)',
    description: 'Vollständige praktische Fahrausbildung für die Führerscheinkategorie B - vom ersten Kennenlernen des Fahrzeugs bis zur Prüfungsreife.',
    course_type: 'physical',
    author: 'Fahrschule',
    max_participants: 1,
    visibility: 'private',
    display_from: null,
    display_until: null,
    level: 'beginner',
    category: 'Kategorie B',
    tags: ['Fahrschule', 'PKW', 'Praktische Ausbildung'],
    featured_image: null,
    intro_video: null,
    sequential_topics: true,
    duration_minutes: 0, // Varies per student
    topics: [
      {
        id: 'vorschulung_b_course',
        title: 'Vorschulung',
        summary: 'Erste Schritte - Vorbereitung und grundlegende Fahrzeugkenntnisse',
        lessons: [
          { id: 'v1_course', title: 'Rundumkontrolle Vorbereitung im Stand', content: 'Überprüfung des Fahrzeugs vor Fahrtantritt: Reifen, Lichter, Spiegel, Sicherheitsgurte.', images: [], videos: [], files: [] },
          { id: 'v2_course', title: 'Sitzhaltung & Spiegeleinstellung', content: 'Korrekte Sitzposition einstellen, alle Spiegel optimal anpassen für beste Rundumsicht.', images: [], videos: [], files: [] },
          { id: 'v3_course', title: 'Bedienelemente kennenlernen', content: 'Lenkrad, Pedalen, Schalthebel, Blinker, Licht, Scheibenwischer und weitere Bedienelemente.', images: [], videos: [], files: [] },
          { id: 'v4_course', title: 'Anfahren & Lenken', content: 'Sanftes Anfahren und erste Lenkbewegungen auf verkehrsarmem Platz.', images: [], videos: [], files: [] },
          { id: 'v5_course', title: 'Anhalten & Bremsen', content: 'Kontrolliertes Bremsen und Anhalten an definierter Stelle.', images: [], videos: [], files: [] },
          { id: 'v6_course', title: 'Gangwechsel', content: 'Schalten zwischen den Gängen, Kupplung richtig nutzen.', images: [], videos: [], files: [] },
          { id: 'v7_course', title: 'Beobachtung des Umfeldes', content: 'Blicktechnik, Schulterblick, Spiegel regelmäßig kontrollieren.', images: [], videos: [], files: [] },
          { id: 'v8_course', title: 'Fahren auf gerader Strecke', content: 'Geradeausfahrt mit konstanter Geschwindigkeit, Spurhaltung üben.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'grundschulung_b_course',
        title: 'Grundschulung',
        summary: 'Verkehrsteilnahme - erste Erfahrungen im Straßenverkehr',
        lessons: [
          { id: 'g1_course', title: 'Einordnen und Spurwechsel', content: 'Sicheres Wechseln der Fahrspur, Blinken, Schulterblick, Einfädeln.', images: [], videos: [], files: [] },
          { id: 'g2_course', title: 'Vortritt beachten', content: 'Rechts-vor-Links, Hauptstraßen, Stoppschilder, Lichtsignalanlagen.', images: [], videos: [], files: [] },
          { id: 'g3_course', title: 'Geschwindigkeit anpassen', content: 'Tempolimits einhalten, Geschwindigkeit der Situation anpassen.', images: [], videos: [], files: [] },
          { id: 'g4_course', title: 'Fahren in Kurven', content: 'Kurventechnik: Bremsen vor der Kurve, in der Kurve lenken und beschleunigen.', images: [], videos: [], files: [] },
          { id: 'g5_course', title: 'Kreuzungen und Verzweigungen', content: 'Sicheres Überqueren von Kreuzungen, richtige Einordnung.', images: [], videos: [], files: [] },
          { id: 'g6_course', title: 'Kreisverkehr', content: 'Einfahren, Verhalten im Kreisel, korrekt Ausfahren.', images: [], videos: [], files: [] },
          { id: 'g7_course', title: 'Verkehrszeichen und Markierungen', content: 'Bedeutung und Beachtung aller wichtigen Verkehrszeichen.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'hauptschulung_b_course',
        title: 'Hauptschulung',
        summary: 'Routinebildung - sicheres Fahren in verschiedenen Situationen',
        lessons: [
          { id: 'h1_course', title: 'Abbiegen links und rechts', content: 'Abbiegen mit korrekter Einordnung, Blinken, Rücksicht auf Fussgänger.', images: [], videos: [], files: [] },
          { id: 'h2_course', title: 'Überholen', content: 'Wann und wie überholen? Sicherheitsabstand, Blinken, Spurwechsel.', images: [], videos: [], files: [] },
          { id: 'h3_course', title: 'Fahren in verkehrsreichen Situationen', content: 'Stadtverkehr, Stau, dichter Verkehr - Ruhe bewahren.', images: [], videos: [], files: [] },
          { id: 'h4_course', title: 'Parken längs und quer', content: 'Einparken in Längs- und Querparklücken, Rückwärtsfahren.', images: [], videos: [], files: [] },
          { id: 'h5_course', title: 'Anfahren in Steigung', content: 'Bergauf anfahren ohne zurückzurollen, Handbremse nutzen.', images: [], videos: [], files: [] },
          { id: 'h6_course', title: 'Verkehrssituationen mit Fussgängern', content: 'Zebrastreifen, Schulwege, besondere Vorsicht.', images: [], videos: [], files: [] },
          { id: 'h7_course', title: 'Verkehrssituationen mit Radfahrern', content: 'Abstand zu Radfahrern, Vorsicht beim Abbiegen.', images: [], videos: [], files: [] },
          { id: 'h8_course', title: 'Verhalten auf Nebenstrassen', content: 'Begegnungsverkehr auf engen Strassen, Vortrittsregeln.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'perfektionsschulung_b_course',
        title: 'Perfektionsschulung',
        summary: 'Prüfungsvorbereitung - anspruchsvolle Situationen meistern',
        lessons: [
          { id: 'p1_course', title: 'Fahren nach Wegweisern', content: 'Navigation, Orientierung, vorausschauend richtige Spur wählen.', images: [], videos: [], files: [] },
          { id: 'p2_course', title: 'Fahren auf Autobahn und Autostrassen', content: 'Auffahren, hohe Geschwindigkeiten, Spurwechsel, Abfahren.', images: [], videos: [], files: [] },
          { id: 'p3_course', title: 'Fahren bei Nacht und schlechter Sicht', content: 'Lichtführung, reduzierte Sicht, Nebel, Regen.', images: [], videos: [], files: [] },
          { id: 'p4_course', title: 'Schwierige Verkehrspartner / -situationen', content: 'Umgang mit aggressiven Fahrern, unübersichtlichen Kreuzungen.', images: [], videos: [], files: [] },
          { id: 'p5_course', title: 'Defensive und vorausschauende Fahrweise', content: 'Gefahren frühzeitig erkennen, Sicherheitsabstand, Bremsbereitschaft.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'fahrmanoever_b_course',
        title: 'Fahrmanöver',
        summary: 'Prüfungsrelevante Manöver perfekt beherrschen',
        lessons: [
          { id: 'f1_course', title: 'Wenden auf der Strasse', content: 'Drehen auf engem Raum, Sicherheit, Beobachtung.', images: [], videos: [], files: [] },
          { id: 'f2_course', title: 'Rückwärtsfahren (gerade & Kurve)', content: 'Rückwärts geradeaus und in Kurven, Orientierung.', images: [], videos: [], files: [] },
          { id: 'f3_course', title: 'Notbremsung / Vollbremsung', content: 'Schnellstmöglich zum Stehen kommen, ABS nutzen.', images: [], videos: [], files: [] },
          { id: 'f4_course', title: 'Ausweichmanöver', content: 'Hindernis umfahren ohne zu bremsen, Stabilität halten.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
    ]
  }
}

/**
 * Vollständiger Kurs für Kategorie A (Motorrad) im Kurse-Tab
 */
export function getDefaultCourseKategorieA(): Omit<AcademyCourse, 'id' | 'created_at' | 'updated_at'> {
  return {
    title: 'Fahrausbildung Kategorie A (Motorrad)',
    description: 'Vollständige praktische Motorradausbildung für die Führerscheinkategorie A - vom Aufsteigen bis zur perfekten Kurvenlage.',
    course_type: 'physical',
    author: 'Fahrschule',
    max_participants: 1,
    visibility: 'private',
    display_from: null,
    display_until: null,
    level: 'beginner',
    category: 'Kategorie A',
    tags: ['Fahrschule', 'Motorrad', 'Praktische Ausbildung'],
    featured_image: null,
    intro_video: null,
    sequential_topics: true,
    duration_minutes: 0,
    topics: [
      {
        id: 'vorschulung_a_course',
        title: 'Vorschulung',
        summary: 'Erste Schritte - Motorrad kennenlernen und Grundlagen',
        lessons: [
          { id: 'v1a_course', title: 'Rundumkontrolle Vorbereitung im Stand', content: 'Überprüfung des Motorrads: Reifen, Bremsen, Kette, Beleuchtung, Ölstand.', images: [], videos: [], files: [] },
          { id: 'v2a_course', title: 'Aufsteigen & Ausbalancieren', content: 'Sicheres Aufsteigen, Motorrad im Stand ausbalancieren.', images: [], videos: [], files: [] },
          { id: 'v3a_course', title: 'Bedienelemente kennenlernen', content: 'Kupplung, Gas, Bremsen (vorne/hinten), Schalthebel, Blinker.', images: [], videos: [], files: [] },
          { id: 'v4a_course', title: 'Anfahren & Lenken', content: 'Erstes Anfahren, Gleichgewicht halten, sanfte Lenkbewegungen.', images: [], videos: [], files: [] },
          { id: 'v5a_course', title: 'Anhalten & Bremsen', content: 'Beide Bremsen richtig einsetzen, dosiert und sicher anhalten.', images: [], videos: [], files: [] },
          { id: 'v6a_course', title: 'Gangwechsel', content: 'Schalten hoch und runter, Kupplung fein dosieren.', images: [], videos: [], files: [] },
          { id: 'v7a_course', title: 'Beobachtung des Umfeldes', content: 'Blicktechnik auf dem Motorrad, Schulterblick, Spiegel.', images: [], videos: [], files: [] },
          { id: 'v8a_course', title: 'Geradeausfahrt und erste Kurven', content: 'Spur halten, sanfte Kurven fahren, Blick in Fahrtrichtung.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'grundschulung_a_course',
        title: 'Grundschulung',
        summary: 'Verkehrsteilnahme - sicheres Fahren im Straßenverkehr',
        lessons: [
          { id: 'g1a_course', title: 'Einordnen und Spurwechsel', content: 'Sichere Spurwechsel, Blinken, Schulterblick beim Motorradfahren.', images: [], videos: [], files: [] },
          { id: 'g2a_course', title: 'Vortritt beachten', content: 'Vortrittsregeln einhalten, defensiv fahren.', images: [], videos: [], files: [] },
          { id: 'g3a_course', title: 'Geschwindigkeit anpassen', content: 'Tempolimits, Geschwindigkeit der Verkehrssituation anpassen.', images: [], videos: [], files: [] },
          { id: 'g4a_course', title: 'Kurventechnik Grundlagen', content: 'In Kurven: Bremsen davor, Blick durch die Kurve, Gas dosieren.', images: [], videos: [], files: [] },
          { id: 'g5a_course', title: 'Kreuzungen und Verzweigungen', content: 'Sicheres Überqueren, korrekte Einordnung beim Abbiegen.', images: [], videos: [], files: [] },
          { id: 'g6a_course', title: 'Kreisverkehr', content: 'Einfahren, Verhalten, Ausfahren mit dem Motorrad.', images: [], videos: [], files: [] },
          { id: 'g7a_course', title: 'Verkehrszeichen und Markierungen', content: 'Alle wichtigen Verkehrszeichen kennen und beachten.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'hauptschulung_a_course',
        title: 'Hauptschulung',
        summary: 'Routinebildung - fortgeschrittene Fahrtechnik',
        lessons: [
          { id: 'h1a_course', title: 'Abbiegen links und rechts', content: 'Abbiegevorgänge mit Motorrad, Stabilität in Schräglagen.', images: [], videos: [], files: [] },
          { id: 'h2a_course', title: 'Überholen', content: 'Sicheres Überholen, Beschleunigung, Spurwechsel.', images: [], videos: [], files: [] },
          { id: 'h3a_course', title: 'Fahren in verkehrsreichen Situationen', content: 'Stadtverkehr, Kolonnenfahrt, aufmerksam bleiben.', images: [], videos: [], files: [] },
          { id: 'h4a_course', title: 'Enge Kurven & Serpentinen', content: 'Enge Radien meistern, Schräglage kontrolliert aufbauen.', images: [], videos: [], files: [] },
          { id: 'h5a_course', title: 'Anfahren in Steigung', content: 'Bergauf starten ohne zurückzurollen, Kupplung und Gas.', images: [], videos: [], files: [] },
          { id: 'h6a_course', title: 'Verkehrssituationen mit Fussgängern', content: 'Fussgänger wahrnehmen, Zebrastreifen, langsam fahren.', images: [], videos: [], files: [] },
          { id: 'h7a_course', title: 'Verkehrssituationen mit anderen Fahrzeugen', content: 'Toter Winkel, Sichtbarkeit erhöhen, defensiv fahren.', images: [], videos: [], files: [] },
          { id: 'h8a_course', title: 'Fahren auf Nebenstrassen', content: 'Begegnungsverkehr, enge Strassen, Vorsicht.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'perfektionsschulung_a_course',
        title: 'Perfektionsschulung',
        summary: 'Prüfungsvorbereitung - anspruchsvolle Situationen meistern',
        lessons: [
          { id: 'p1a_course', title: 'Fahren nach Wegweisern', content: 'Navigation, vorausschauend richtige Spur wählen.', images: [], videos: [], files: [] },
          { id: 'p2a_course', title: 'Fahren auf Autobahn und Autostrassen', content: 'Hohe Geschwindigkeiten, Windempfindlichkeit, sichere Spurwechsel.', images: [], videos: [], files: [] },
          { id: 'p3a_course', title: 'Fahren bei Nacht und schlechter Sicht', content: 'Lichtführung, eingeschränkte Sicht, Vorsicht.', images: [], videos: [], files: [] },
          { id: 'p4a_course', title: 'Schwierige Verkehrspartner / -situationen', content: 'Defensive Fahrweise, Gefahren antizipieren.', images: [], videos: [], files: [] },
          { id: 'p5a_course', title: 'Defensive und vorausschauende Fahrweise', content: 'Sicherheitsabstand, Bremsbereitschaft, Risikominimierung.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
      {
        id: 'fahrmanoever_a_course',
        title: 'Fahrmanöver',
        summary: 'Prüfungsrelevante Manöver perfekt beherrschen',
        lessons: [
          { id: 'f1a_course', title: 'Sichern des Motorrades in Steigung und Gefälle', content: 'Motorrad abstellen, sichern, Standsicherheit prüfen.', images: [], videos: [], files: [] },
          { id: 'f2a_course', title: 'Slalom und Gleichgewichtsübungen', content: 'Langsam fahren, Balance halten, Slalom zwischen Hütchen.', images: [], videos: [], files: [] },
          { id: 'f3a_course', title: 'Wenden auf engem Raum', content: 'Motorrad auf engstem Raum drehen, Füße als Stütze.', images: [], videos: [], files: [] },
          { id: 'f4a_course', title: 'Rangiermanöver / Rückwärtsfahren mit Begleitung', content: 'Motorrad schiebend rückwärts bewegen, lenken.', images: [], videos: [], files: [] },
          { id: 'f5a_course', title: 'Vollbremsung / Gefahrenbremsung', content: 'Beide Bremsen voll nutzen, ABS, schnellstmöglich stoppen.', images: [], videos: [], files: [] },
          { id: 'f6a_course', title: 'Ausweichmanöver', content: 'Hindernissen ausweichen, Stabilität bewahren, Gas halten.', images: [], videos: [], files: [] },
        ],
        quizzes: []
      },
    ]
  }
}
