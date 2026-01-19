<template>
  <div class="p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div
        v-for="course in courses"
        :key="course.id"
        @click="$emit('edit-course', course.id)"
        class="group bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-lg hover:border-brand-200 transition-all cursor-pointer overflow-hidden flex flex-col"
      >
        <!-- Cover Image -->
        <div class="h-40 bg-slate-100 relative overflow-hidden">
          <img
            v-if="course.coverImage"
            :src="course.coverImage"
            :alt="course.title"
            class="w-full h-full object-cover"
          />
          <div
            v-else
            class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-300"
          >
            <ImageIcon :size="48" />
          </div>

          <!-- Status Badge -->
          <div class="absolute top-3 right-3">
            <span
              v-if="course.published"
              class="bg-emerald-500/90 text-white text-xs font-bold px-2 py-1 rounded backdrop-blur-sm"
            >
              {{ $t('mod.academy.status.published') }}
            </span>
            <span
              v-else
              class="bg-slate-500/90 text-white text-xs font-bold px-2 py-1 rounded backdrop-blur-sm"
            >
              {{ $t('mod.academy.status.draft') }}
            </span>
          </div>
        </div>

        <!-- Card Content -->
        <div class="p-5 flex-1 flex flex-col">
          <div class="text-xs font-semibold text-brand-600 mb-1 uppercase tracking-wide">
            {{ course.category?.name || $t('mod.academy.uncategorized') }}
          </div>
          <h3 class="text-lg font-bold text-slate-800 mb-2 leading-tight group-hover:text-brand-600 transition-colors">
            {{ course.title }}
          </h3>
          <p class="text-sm text-slate-500 line-clamp-2 mb-4 flex-1">
            {{ course.description || $t('mod.academy.no_description') }}
          </p>

          <!-- Footer Stats -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-100 text-xs text-slate-500">
            <span class="flex items-center gap-1">
              <UsersIcon :size="14" /> {{ course.studentsCount }} {{ $t('mod.academy.students') }}
            </span>
            <span class="flex items-center gap-1">
              <ListIcon :size="14" /> {{ course.curriculum?.length || 0 }} {{ $t('mod.academy.modules') }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Users as UsersIcon, List as ListIcon, Image as ImageIcon } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'

interface Course {
  id: string
  title: string
  description?: string
  coverImage?: string
  published: boolean
  category?: { id: string; name: string }
  studentsCount: number
  curriculum?: any[]
}

defineProps<{
  courses: Course[]
}>()

defineEmits<{
  'edit-course': [courseId: string]
}>()

const { t: $t } = useI18n()
</script>
