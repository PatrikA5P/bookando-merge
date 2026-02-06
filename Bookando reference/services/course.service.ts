/**
 * Course Service
 *
 * API-Service für Course/Academy Management
 */

import apiClient from './api';

export interface CourseFilters {
  status?: 'DRAFT' | 'ACTIVE' | 'FULL' | 'CANCELLED' | 'COMPLETED' | 'ARCHIVED';
  categoryId?: string;
  type?: 'ONLINE' | 'IN_PERSON' | 'BLENDED';
  showOnWebsite?: boolean;
  published?: boolean;
  search?: string;
}

export interface Course {
  id: string;
  organizationId: string;
  title: string;
  description?: string;
  coverImage?: string;
  type: 'ONLINE' | 'IN_PERSON' | 'BLENDED';
  visibility: 'PUBLIC' | 'PRIVATE' | 'INTERNAL';
  status: 'DRAFT' | 'ACTIVE' | 'FULL' | 'CANCELLED' | 'COMPLETED' | 'ARCHIVED';
  categoryId?: string;
  category?: {
    id: string;
    name: string;
    color?: string;
  };
  tags: Array<{
    tag: {
      id: string;
      name: string;
      color?: string;
    };
  }>;
  price: number;
  currency: string;
  capacity?: number;
  duration?: number;
  difficulty?: string;
  published: boolean;
  showOnWebsite: boolean;
  organizerId?: string;
  organizer?: {
    id: string;
    user: {
      firstName: string;
      lastName: string;
    };
  };
  defaultLocation?: {
    id: string;
    name: string;
  };
  createdAt: string;
  updatedAt: string;
  _count?: {
    sessions: number;
    enrollments: number;
  };
}

export interface CreateCourseData {
  title: string;
  description?: string;
  coverImage?: string;
  type: 'ONLINE' | 'IN_PERSON' | 'BLENDED';
  visibility: 'PUBLIC' | 'PRIVATE' | 'INTERNAL';
  status?: 'DRAFT' | 'ACTIVE';
  categoryId?: string;
  tagIds?: string[];
  price: number;
  currency?: string;
  capacity?: number;
  duration?: number;
  difficulty?: string;
  organizerId?: string;
  defaultLocationId?: string;
  // Booking Window
  bookingStartsImmediately?: boolean;
  bookingStartDate?: string;
  bookingStartTime?: string;
  bookingEndDate?: string;
  bookingEndTime?: string;
  bookingClosesOnStart?: boolean;
  // Settings
  notifyParticipants?: boolean;
  isRecurring?: boolean;
  // Deposit
  depositEnabled?: boolean;
  depositAmount?: number;
  // Capacity Rules
  allowGroupBooking?: boolean;
  allowRepeatBooking?: boolean;
  closeOnMinimumEnabled?: boolean;
  closeOnMinimumValue?: number;
  limitExtraEnabled?: boolean;
  limitExtraValue?: number;
  // Advanced
  waitlistEnabled?: boolean;
  cancellationLeadTime?: number;
  paymentLinkEnabled?: boolean;
  paymentOnSite?: boolean;
  googleMeetEnabled?: boolean;
  showOnWebsite?: boolean;
}

export interface CourseSession {
  id: string;
  courseId: string;
  date: string;
  startTime: string;
  endTime: string;
  instructorId?: string;
  instructor?: {
    id: string;
    user: {
      firstName: string;
      lastName: string;
    };
  };
  locationId?: string;
  location?: {
    id: string;
    name: string;
  };
  roomId?: string;
  room?: {
    id: string;
    name: string;
  };
  maxParticipants: number;
  currentEnrollment: number;
  status: 'SCHEDULED' | 'FULL' | 'CANCELLED' | 'COMPLETED';
  createdAt: string;
  updatedAt: string;
}

export interface CourseAvailability {
  bookingWindowOpen: boolean;
  availableSpots: number;
  isFull: boolean;
  nextSession?: CourseSession;
}

export interface Tag {
  id: string;
  organizationId: string;
  name: string;
  color?: string;
  description?: string;
  createdAt: string;
  updatedAt: string;
}

class CourseService {
  /**
   * Liste aller Kurse mit optionalen Filtern
   */
  async getCourses(filters?: CourseFilters): Promise<Course[]> {
    return apiClient.get<Course[]>('/courses', { params: filters as any });
  }

  /**
   * Einzelner Kurs nach ID
   */
  async getCourse(id: string): Promise<Course> {
    return apiClient.get<Course>(`/courses/${id}`);
  }

  /**
   * Neuen Kurs erstellen
   */
  async createCourse(data: CreateCourseData): Promise<Course> {
    return apiClient.post<Course>('/courses', data);
  }

  /**
   * Kurs aktualisieren
   */
  async updateCourse(id: string, data: Partial<CreateCourseData>): Promise<Course> {
    return apiClient.put<Course>(`/courses/${id}`, data);
  }

  /**
   * Kurs löschen (archivieren)
   */
  async deleteCourse(id: string): Promise<void> {
    return apiClient.delete(`/courses/${id}`);
  }

  /**
   * Kurs veröffentlichen
   */
  async publishCourse(id: string): Promise<Course> {
    return apiClient.post<Course>(`/courses/${id}/publish`);
  }

  /**
   * Kurs-Veröffentlichung rückgängig machen
   */
  async unpublishCourse(id: string): Promise<Course> {
    return apiClient.post<Course>(`/courses/${id}/unpublish`);
  }

  /**
   * Verfügbarkeit eines Kurses prüfen
   */
  async getCourseAvailability(id: string): Promise<CourseAvailability> {
    return apiClient.get<CourseAvailability>(`/courses/${id}/availability`);
  }

  /**
   * Sessions für einen Kurs abrufen
   */
  async getCourseSessions(courseId: string): Promise<CourseSession[]> {
    return apiClient.get<CourseSession[]>(`/courses/${courseId}/sessions`);
  }

  /**
   * Neue Session erstellen
   */
  async createSession(courseId: string, data: {
    date: string;
    startTime: string;
    endTime: string;
    instructorId?: string;
    locationId?: string;
    roomId?: string;
    maxParticipants: number;
  }): Promise<CourseSession> {
    return apiClient.post<CourseSession>(`/courses/${courseId}/sessions`, data);
  }

  /**
   * Session aktualisieren
   */
  async updateSession(courseId: string, sessionId: string, data: Partial<{
    date: string;
    startTime: string;
    endTime: string;
    instructorId?: string;
    locationId?: string;
    roomId?: string;
    maxParticipants: number;
    status: string;
  }>): Promise<CourseSession> {
    return apiClient.put<CourseSession>(`/courses/${courseId}/sessions/${sessionId}`, data);
  }

  /**
   * Session löschen
   */
  async deleteSession(courseId: string, sessionId: string): Promise<void> {
    return apiClient.delete(`/courses/${courseId}/sessions/${sessionId}`);
  }

  /**
   * Teilnehmer in Kurs einschreiben
   */
  async enrollCustomer(courseId: string, customerId: string): Promise<void> {
    return apiClient.post(`/courses/${courseId}/enroll`, { customerId });
  }

  /**
   * Teilnehmer aus Kurs entfernen
   */
  async unenrollCustomer(courseId: string, customerId: string): Promise<void> {
    return apiClient.delete(`/courses/${courseId}/enroll/${customerId}`);
  }

  /**
   * Alle Tags abrufen
   */
  async getTags(): Promise<Tag[]> {
    return apiClient.get<Tag[]>('/tags');
  }

  /**
   * Neuen Tag erstellen
   */
  async createTag(data: {
    name: string;
    color?: string;
    description?: string;
  }): Promise<Tag> {
    return apiClient.post<Tag>('/tags', data);
  }

  /**
   * Tag aktualisieren
   */
  async updateTag(id: string, data: {
    name?: string;
    color?: string;
    description?: string;
  }): Promise<Tag> {
    return apiClient.put<Tag>(`/tags/${id}`, data);
  }

  /**
   * Tag löschen
   */
  async deleteTag(id: string): Promise<void> {
    return apiClient.delete(`/tags/${id}`);
  }
}

export const courseService = new CourseService();
export default courseService;
