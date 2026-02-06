/**
 * useCourses Hook
 *
 * Custom Hook für Course Management mit API-Integration
 */

import { useState, useEffect, useCallback } from 'react';
import courseService, { Course, CourseFilters, CreateCourseData } from '../services/course.service';

export function useCourses(filters?: CourseFilters) {
  const [courses, setCourses] = useState<Course[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Kurse laden
  const loadCourses = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await courseService.getCourses(filters);
      setCourses(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load courses');
      console.error('Error loading courses:', err);
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  // Initialer Load
  useEffect(() => {
    loadCourses();
  }, [loadCourses]);

  // Kurs erstellen
  const createCourse = async (data: CreateCourseData): Promise<Course> => {
    setError(null);
    try {
      const newCourse = await courseService.createCourse(data);
      setCourses((prev) => [...prev, newCourse]);
      return newCourse;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to create course';
      setError(message);
      throw new Error(message);
    }
  };

  // Kurs aktualisieren
  const updateCourse = async (id: string, data: Partial<CreateCourseData>): Promise<Course> => {
    setError(null);
    try {
      const updatedCourse = await courseService.updateCourse(id, data);
      setCourses((prev) => prev.map((c) => (c.id === id ? updatedCourse : c)));
      return updatedCourse;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to update course';
      setError(message);
      throw new Error(message);
    }
  };

  // Kurs löschen
  const deleteCourse = async (id: string): Promise<void> => {
    setError(null);
    try {
      await courseService.deleteCourse(id);
      setCourses((prev) => prev.filter((c) => c.id !== id));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to delete course';
      setError(message);
      throw new Error(message);
    }
  };

  // Kurs veröffentlichen
  const publishCourse = async (id: string): Promise<void> => {
    setError(null);
    try {
      const updatedCourse = await courseService.publishCourse(id);
      setCourses((prev) => prev.map((c) => (c.id === id ? updatedCourse : c)));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to publish course';
      setError(message);
      throw new Error(message);
    }
  };

  // Veröffentlichung rückgängig machen
  const unpublishCourse = async (id: string): Promise<void> => {
    setError(null);
    try {
      const updatedCourse = await courseService.unpublishCourse(id);
      setCourses((prev) => prev.map((c) => (c.id === id ? updatedCourse : c)));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to unpublish course';
      setError(message);
      throw new Error(message);
    }
  };

  return {
    courses,
    isLoading,
    error,
    loadCourses,
    createCourse,
    updateCourse,
    deleteCourse,
    publishCourse,
    unpublishCourse,
  };
}

export default useCourses;
