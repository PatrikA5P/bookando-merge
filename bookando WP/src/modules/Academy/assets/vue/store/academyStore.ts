import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Course {
  id: string
  title: string
  description: string
  type: string
  author: string
  visibility: string
  category: { id: string; name: string }
  tags: string[]
  difficulty: string
  coverImage: string
  studentsCount: number
  published: boolean
  certificate: {
    enabled: boolean
    templateId: string
    showScore: boolean
    signatureText: string
  }
  curriculum: any[]
}

export const useAcademyStore = defineStore('academy', () => {
  // State
  const courses = ref<Course[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Mock data
  const mockCourses: Course[] = [
    {
      id: 'c1',
      title: 'Introduction to Yoga',
      description: 'Learn the basics of yoga practice and philosophy.',
      type: 'online',
      author: 'Instructor Name',
      visibility: 'public',
      category: { id: 'yoga', name: 'Yoga' },
      tags: ['beginner', 'wellness'],
      difficulty: 'beginner',
      coverImage: '',
      studentsCount: 45,
      published: true,
      certificate: { enabled: true, templateId: 'default', showScore: true, signatureText: '' },
      curriculum: [
        { id: 'm1', title: 'Module 1: Foundations' },
        { id: 'm2', title: 'Module 2: Basic Poses' }
      ]
    },
    {
      id: 'c2',
      title: 'Advanced Massage Techniques',
      description: 'Master advanced massage therapy techniques.',
      type: 'hybrid',
      author: 'Expert Therapist',
      visibility: 'private',
      category: { id: 'massage', name: 'Massage' },
      tags: ['advanced', 'certification'],
      difficulty: 'advanced',
      coverImage: '',
      studentsCount: 12,
      published: false,
      certificate: { enabled: true, templateId: 'premium', showScore: true, signatureText: 'Certified by...' },
      curriculum: [
        { id: 'm1', title: 'Module 1: Deep Tissue' },
        { id: 'm2', title: 'Module 2: Sports Massage' },
        { id: 'm3', title: 'Module 3: Hot Stone' }
      ]
    }
  ]

  // Actions
  const loadCourses = async () => {
    loading.value = true
    error.value = null

    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 300))
      courses.value = mockCourses
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to load courses'
    } finally {
      loading.value = false
    }
  }

  const addCourse = (course: Course) => {
    courses.value.push(course)
  }

  const updateCourse = (updatedCourse: Course) => {
    const index = courses.value.findIndex(c => c.id === updatedCourse.id)
    if (index !== -1) {
      courses.value[index] = updatedCourse
    }
  }

  const deleteCourse = (courseId: string) => {
    courses.value = courses.value.filter(c => c.id !== courseId)
  }

  return {
    // State
    courses,
    loading,
    error,

    // Actions
    loadCourses,
    addCourse,
    updateCourse,
    deleteCourse
  }
})
