/**
 * @bookando/types - Academy Models
 * Types for courses, lessons, quizzes, and certifications
 */

import type { BaseEntity } from './base';
import type { CourseType, CourseVisibility, DifficultyLevel, QuestionType } from './enums';

export interface Question {
  id: string;
  text: string;
  type: QuestionType;
  points: number;
  options?: string[];
  correctAnswer?: string | string[] | number;
  mediaUrl?: string;
}

export interface QuizSettings {
  allowedAttempts: number;
  passingScore: number;
  questionsToShow?: number;
  shuffleQuestions: boolean;
  layout: 'Single Page' | 'One per page';
  feedbackMode: 'Immediate' | 'End of Quiz';
}

export interface Quiz {
  id: string;
  type: 'quiz';
  title: string;
  summary: string;
  questions: Question[];
  settings: QuizSettings;
}

export interface Lesson {
  id: string;
  type: 'lesson';
  title: string;
  content: string;
  mediaUrls: string[];
  fileAttachments: string[];
}

export interface Topic {
  id: string;
  title: string;
  summary: string;
  items: (Lesson | Quiz)[];
}

export interface CertificateSettings {
  enabled: boolean;
  templateId: string;
  validityMonths?: number;
  showScore: boolean;
  signatureText: string;
}

export interface Badge extends BaseEntity {
  name: string;
  color: string;
  icon: string;
  description?: string;
}

export interface Course extends BaseEntity {
  title: string;
  description: string;
  type: CourseType;
  author: string;
  maxParticipants?: number;
  visibility: CourseVisibility;
  startDate?: string;
  endDate?: string;
  category: string;
  tags: string[];
  difficulty: DifficultyLevel;
  coverImage: string;
  introVideoUrl?: string;
  curriculum: Topic[];
  certificate: CertificateSettings;
  awardedBadgeId?: string;
  studentsCount: number;
  published: boolean;
}
