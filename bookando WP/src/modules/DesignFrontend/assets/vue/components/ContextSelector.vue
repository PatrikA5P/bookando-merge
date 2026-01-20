<template>
  <div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-6xl w-full">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-white mb-3">Design Studio</h1>
        <p class="text-slate-400 text-lg">Choose what you want to customize</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <button
          v-for="context in contexts"
          :key="context.id"
          @click="$emit('select', context.id)"
          class="group relative bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-brand-500 rounded-2xl p-8 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-brand-500/20"
        >
          <div class="flex flex-col items-center text-center space-y-4">
            <div class="w-16 h-16 bg-gradient-to-br from-brand-500 to-brand-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <component :is="context.icon" :size="32" class="text-white" />
            </div>
            <div>
              <h3 class="text-xl font-bold text-white mb-2">{{ context.title }}</h3>
              <p class="text-sm text-slate-400">{{ context.description }}</p>
            </div>
          </div>

          <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
            <ChevronRightIcon :size="20" class="text-brand-400" />
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Layout as LayoutIcon,
  FileText as FileTextIcon,
  User as UserIcon,
  Briefcase as BriefcaseIcon,
  ChevronRight as ChevronRightIcon
} from 'lucide-vue-next'

defineEmits<{
  select: [context: 'widget' | 'offerForm' | 'customer' | 'employee']
}>()

const contexts = [
  {
    id: 'widget' as const,
    title: 'Booking Widget',
    description: 'Customize the embedded booking widget for your website',
    icon: LayoutIcon
  },
  {
    id: 'offerForm' as const,
    title: 'Offer Form',
    description: 'Design custom booking forms for specific services',
    icon: FileTextIcon
  },
  {
    id: 'customer' as const,
    title: 'Customer Portal',
    description: 'Personalize the customer self-service portal',
    icon: UserIcon
  },
  {
    id: 'employee' as const,
    title: 'Employee Hub',
    description: 'Configure the employee workspace and tools',
    icon: BriefcaseIcon
  }
]
</script>
