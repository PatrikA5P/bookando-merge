import React, { useState, useRef, useEffect } from 'react';
import { 
  Course, CourseType, CourseVisibility, DifficultyLevel, Topic, Lesson, Quiz, 
  Question, QuestionType, QuizSettings, CertificateSettings, Badge
} from '../types';
import { 
  BookOpen, Users, Award, PlayCircle, MoreVertical, Search, Plus, 
  ArrowLeft, Save, Layout, List, Settings, Image as ImageIcon, Video, 
  FileText, Trash2, Edit2, GripVertical, CheckSquare, CheckCircle, 
  HelpCircle, ChevronDown, ChevronRight, Move, Clock, Eye, MonitorPlay,
  Sliders, AlignLeft, Type, Puzzle, X, Shield, Activity, Heart, Check, CreditCard,
  GraduationCap, LayoutGrid, Book, Zap, Star, Circle, Folder, FolderPlus, Paperclip,
  Bold, Italic, List as ListIcon, Link as LinkIcon, File, ArrowUp, ArrowDown, Filter, Code,
  Upload
} from 'lucide-react';
import { useApp } from '../context/AppContext';
import ModuleLayout from '../components/ModuleLayout';
import { getModuleDesign } from '../utils/designTokens';
import CoursesTab from './Academy/tabs/CoursesTab';
import LessonsTab from './Academy/tabs/LessonsTab';
import BadgesTab from './Academy/tabs/BadgesTab';
import CardsTab from './Academy/tabs/CardsTab';
import CourseEditor from './Academy/editors/CourseEditor';
import EducationCardEditor from './Academy/editors/EducationCardEditor';

// --- INTERNAL TYPES FOR EDUCATION CARDS ---
interface EducationMedia {
    type: 'image' | 'video';
    url: string;
    label: string;
}

interface EducationItem {
    id: string;
    title: string;
    description?: string;
    media: EducationMedia[];
    // Linked lesson ID if imported
    originalLessonId?: string; 
}

interface EducationChapter {
    id: string;
    title: string;
    items: EducationItem[];
}

interface GradingConfig {
    type: 'slider' | 'buttons' | 'stars';
    min: number;
    max: number;
    labels?: { min: string, max: string };
}

interface AutomationRule {
    enabled: boolean;
    triggerType: 'Service' | 'Category';
    triggerId: string; // ID of service or category name
    allowMultiple: boolean; // Can be assigned multiple times to same customer
}

interface EducationCardTemplate {
    id: string;
    title: string;
    description: string;
    chapters: EducationChapter[];
    grading: GradingConfig;
    automation: AutomationRule;
    active: boolean;
}

// --- INTERNAL TYPES FOR LESSONS ---
interface LessonGroup {
    id: string;
    title: string;
}

interface StructuredAttachment {
    id: string;
    type: 'image' | 'video' | 'document';
    url: string; // or base64
    name: string;
    description: string;
}

// Extended Lesson type to handle local state before saving to the simpler global type
interface ExtendedLesson extends Lesson {
    groupId?: string;
    structuredAttachments?: StructuredAttachment[];
}

// --- MOCK DATA FOR CARDS ---
const initialEducationCards: EducationCardTemplate[] = [
    {
        id: 'ec_1',
        title: 'Fahrschul-Ausbildungskarte Klasse B',
        description: 'Standardisierter Ausbildungsplan für PKW.',
        chapters: [
            {
                id: 'c1', title: 'Grundstufe', items: [
                    { id: 'i1', title: 'Einsteigen & Sitzeinstellung', media: [] },
                    { id: 'i2', title: 'Anfahren & Schalten', media: [] }
                ]
            }
        ],
        grading: { type: 'buttons', min: 1, max: 5, labels: { min: 'Ungenügend', max: 'Sehr Gut' } },
        automation: { enabled: false, triggerType: 'Category', triggerId: '', allowMultiple: false },
        active: true
    }
];

// Mock Groups for Lessons
const initialLessonGroups: LessonGroup[] = [
    { id: 'lg_1', title: 'General Basics' },
    { id: 'lg_2', title: 'Advanced Techniques' },
    { id: 'lg_3', title: 'Safety Protocols' },
];

// --- Component ---

const AcademyModule: React.FC = () => {
  const [view, setView] = useState<'list' | 'editor'>('list');
  const [activeTab, setActiveTab] = useState<'courses' | 'lessons' | 'badges' | 'cards'>('courses');
  const [selectedCourseId, setSelectedCourseId] = useState<string | null>(null);
  const { courses, setCourses } = useApp();

  // Local state for Education Cards (Simulating separate store)
  const [eduCards, setEduCards] = useState<EducationCardTemplate[]>(initialEducationCards);
  const [selectedCardId, setSelectedCardId] = useState<string | null>(null);

  const handleEditCourse = (courseId: string) => {
    setSelectedCourseId(courseId);
    setView('editor');
  };

  const handleCreateCourse = () => {
    const newCourse: Course = {
      id: `new_${Date.now()}`,
      title: 'New Untitled Course',
      description: '',
      type: CourseType.ONLINE,
      author: 'Current User',
      visibility: CourseVisibility.PRIVATE,
      category: 'Uncategorized',
      tags: [],
      difficulty: DifficultyLevel.BEGINNER,
      coverImage: '',
      studentsCount: 0,
      published: false,
      certificate: { enabled: false, templateId: 'default', showScore: true, signatureText: '' },
      curriculum: []
    };
    setCourses([...courses, newCourse]);
    setSelectedCourseId(newCourse.id);
    setView('editor');
  };

  const handleSaveCourse = (updatedCourse: Course) => {
    setCourses(courses.map(c => c.id === updatedCourse.id ? updatedCourse : c));
  };

  // Card Handlers
  const handleCreateCard = () => {
      const newCard: EducationCardTemplate = {
          id: `ec_${Date.now()}`,
          title: 'New Education Plan',
          description: '',
          chapters: [],
          grading: { type: 'buttons', min: 1, max: 4 },
          automation: { enabled: false, triggerType: 'Service', triggerId: '', allowMultiple: false },
          active: false
      };
      setEduCards([...eduCards, newCard]);
      setSelectedCardId(newCard.id);
      setView('editor');
  };

  const handleEditCard = (id: string) => {
      setSelectedCardId(id);
      setView('editor');
  };

  const handleSaveCard = (updatedCard: EducationCardTemplate) => {
      setEduCards(eduCards.map(c => c.id === updatedCard.id ? updatedCard : c));
  };

  const handleDeleteCard = (id: string) => {
      if(confirm('Delete this template?')) {
          setEduCards(eduCards.filter(c => c.id !== id));
      }
  }

  const handleBack = () => {
    setView('list');
    setSelectedCourseId(null);
    setSelectedCardId(null);
  };

  // Editor View takes over full screen content area
  if (view === 'editor') {
      if (selectedCourseId) {
        const course = courses.find(c => c.id === selectedCourseId);
        if (course) {
            return <CourseEditor course={course} onSave={handleSaveCourse} onBack={handleBack} />;
        }
      }
      if (selectedCardId) {
          const card = eduCards.find(c => c.id === selectedCardId);
          if (card) {
              return <EducationCardEditor template={card} onSave={handleSaveCard} onBack={handleBack} />;
          }
      }
  }

  const moduleDesign = getModuleDesign('academy');

  const tabs = [
    { id: 'courses', icon: LayoutGrid, label: 'Courses' },
    { id: 'lessons', icon: Book, label: 'Lessons' },
    { id: 'cards', icon: CreditCard, label: 'Education Cards' },
    { id: 'badges', icon: Award, label: 'Badges & Certs' },
  ];

  return (
    <div className="flex flex-col min-h-full">
      <ModuleLayout
        variant="mixed"
        moduleName="Academy"
        hero={{
          icon: GraduationCap,
          title: 'Academy',
          description: 'Learning Management System',
          gradient: moduleDesign.gradient
        }}
        tabs={tabs}
        activeTab={activeTab}
        onTabChange={(tabId) => setActiveTab(tabId as any)}
        primaryAction={
          activeTab === 'courses' ? {
            label: 'Create Course',
            icon: Plus,
            onClick: handleCreateCourse
          } : activeTab === 'cards' ? {
            label: 'Create Template',
            icon: Plus,
            onClick: handleCreateCard
          } : undefined
        }
      >
        {/* Courses Tab */}
        {activeTab === 'courses' && <CoursesTab courses={courses} onEditCourse={handleEditCourse} />}

        {/* Education Cards Tab */}
        {activeTab === 'cards' && (
          <CardsTab
            cards={eduCards}
            onCreateCard={handleCreateCard}
            onEditCard={handleEditCard}
            onDeleteCard={handleDeleteCard}
          />
        )}

        {/* Lessons Tab */}
        {activeTab === 'lessons' && <LessonsTab />}

        {/* Badges Tab */}
        {activeTab === 'badges' && <BadgesTab />}
      </ModuleLayout>
    </div>
  );
};

export default AcademyModule;