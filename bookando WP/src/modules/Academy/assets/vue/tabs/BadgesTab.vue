<template>
  <div class="p-6 space-y-6 animate-fadeIn">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-bold text-slate-800">{{ $t('mod.academy.badges_education_cards') }}</h2>
      <button
        @click="isAdding = true"
        class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2"
      >
        <PlusIcon :size="18" /> {{ $t('mod.academy.actions.create_badge') }}
      </button>
    </div>

    <!-- Badge Creation Form -->
    <div v-if="isAdding" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-6">
      <h4 class="font-bold text-slate-800 mb-4">{{ $t('mod.academy.new_badge_details') }}</h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.badge_name') }}</label>
          <input
            v-model="newBadge.name"
            class="w-full border border-slate-300 rounded-lg px-3 py-2"
            :placeholder="$t('mod.academy.badge_name_placeholder')"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.style') }}</label>
          <select
            v-model="newBadge.color"
            class="w-full border border-slate-300 rounded-lg px-3 py-2"
          >
            <option value="bg-slate-100 text-slate-800">{{ $t('mod.academy.style_slate') }}</option>
            <option value="bg-brand-100 text-brand-800">{{ $t('mod.academy.style_brand') }}</option>
            <option value="bg-emerald-100 text-emerald-800">{{ $t('mod.academy.style_emerald') }}</option>
            <option value="bg-amber-100 text-amber-800">{{ $t('mod.academy.style_amber') }}</option>
            <option value="bg-rose-100 text-rose-800">{{ $t('mod.academy.style_rose') }}</option>
            <option value="bg-purple-100 text-purple-800">{{ $t('mod.academy.style_purple') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('mod.academy.icon') }}</label>
          <select
            v-model="newBadge.icon"
            class="w-full border border-slate-300 rounded-lg px-3 py-2"
          >
            <option value="Shield">Shield</option>
            <option value="Award">Award</option>
            <option value="Check">Check</option>
            <option value="Activity">Activity</option>
            <option value="Heart">Heart</option>
          </select>
        </div>
      </div>
      <div class="flex justify-end gap-2">
        <button
          @click="isAdding = false"
          class="px-4 py-2 text-slate-600 hover:bg-slate-50 rounded-lg"
        >
          {{ $t('common.cancel') }}
        </button>
        <button
          @click="handleSave"
          class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700"
        >
          {{ $t('mod.academy.save_badge') }}
        </button>
      </div>
    </div>

    <!-- Badge List -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div
        v-for="badge in badges"
        :key="badge.id"
        class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group"
      >
        <div class="flex items-center gap-3">
          <div :class="['p-2 rounded-full', badge.color]">
            <ShieldIcon v-if="badge.icon === 'Shield'" :size="20" />
            <AwardIcon v-else-if="badge.icon === 'Award'" :size="20" />
            <CheckIcon v-else-if="badge.icon === 'Check'" :size="20" />
            <ActivityIcon v-else-if="badge.icon === 'Activity'" :size="20" />
            <HeartIcon v-else-if="badge.icon === 'Heart'" :size="20" />
          </div>
          <span class="font-medium text-slate-800">{{ badge.name }}</span>
        </div>
        <button
          @click="handleDelete(badge.id)"
          class="text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <Trash2Icon :size="16" />
        </button>
      </div>
    </div>

    <!-- Education Cards Preview Section -->
    <div class="mt-8 pt-8 border-t border-slate-200">
      <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
        <CreditCardIcon :size="20" /> {{ $t('mod.academy.education_cards_preview') }}
      </h3>
      <div class="p-6 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card 1 -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
          <div class="h-2 bg-brand-500"></div>
          <div class="p-5">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h4 class="font-bold text-lg text-slate-800">{{ $t('mod.academy.certified_safety_instructor') }}</h4>
                <p class="text-sm text-slate-500">{{ $t('mod.academy.level_2_qualification') }}</p>
              </div>
              <div class="w-10 h-10 bg-brand-50 text-brand-600 rounded-full flex items-center justify-center">
                <AwardIcon :size="20" />
              </div>
            </div>
            <div class="space-y-3">
              <p class="text-xs font-bold text-slate-400 uppercase">{{ $t('mod.academy.required_badges') }}</p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="badge in badges.slice(0, 3)"
                  :key="badge.id"
                  :class="['text-xs px-2 py-1 rounded border bg-white', badge.color]"
                >
                  {{ badge.name }}
                </span>
              </div>
            </div>
          </div>
          <div class="bg-slate-50 p-3 border-t border-slate-100 text-xs text-center text-slate-500 font-mono">
            ID: CARD-SAF-02
          </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
          <div class="h-2 bg-emerald-500"></div>
          <div class="p-5">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h4 class="font-bold text-lg text-slate-800">{{ $t('mod.academy.master_yogi') }}</h4>
                <p class="text-sm text-slate-500">{{ $t('mod.academy.advanced_practitioner') }}</p>
              </div>
              <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center">
                <ActivityIcon :size="20" />
              </div>
            </div>
            <div class="space-y-3">
              <p class="text-xs font-bold text-slate-400 uppercase">{{ $t('mod.academy.required_badges') }}</p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="badge in badges.slice(1, 2)"
                  :key="badge.id"
                  :class="['text-xs px-2 py-1 rounded border bg-white', badge.color]"
                >
                  {{ badge.name }}
                </span>
              </div>
            </div>
          </div>
          <div class="bg-slate-50 p-3 border-t border-slate-100 text-xs text-center text-slate-500 font-mono">
            ID: CARD-YOG-09
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  Plus as PlusIcon,
  Trash2 as Trash2Icon,
  Award as AwardIcon,
  Shield as ShieldIcon,
  Check as CheckIcon,
  Activity as ActivityIcon,
  Heart as HeartIcon,
  CreditCard as CreditCardIcon
} from 'lucide-vue-next'

interface Badge {
  id: string
  name: string
  icon: string
  color: string
  description?: string
}

const { t: $t } = useI18n()

// State
const isAdding = ref(false)
const newBadge = ref<Partial<Badge>>({
  name: '',
  icon: 'Shield',
  color: 'bg-indigo-100 text-indigo-800'
})

// Mock badges data
const badges = ref<Badge[]>([
  { id: 'b1', name: 'Safety Certified', icon: 'Shield', color: 'bg-brand-100 text-brand-800' },
  { id: 'b2', name: 'Advanced Yoga', icon: 'Activity', color: 'bg-emerald-100 text-emerald-800' },
  { id: 'b3', name: 'First Aid', icon: 'Heart', color: 'bg-rose-100 text-rose-800' }
])

// Methods
const handleSave = () => {
  if (!newBadge.value.name) return

  badges.value.push({
    id: `b_${Date.now()}`,
    name: newBadge.value.name,
    icon: newBadge.value.icon || 'Shield',
    color: newBadge.value.color || 'bg-slate-100 text-slate-800',
    description: newBadge.value.description
  })

  newBadge.value = { name: '', icon: 'Shield', color: 'bg-indigo-100 text-indigo-800' }
  isAdding.value = false
}

const handleDelete = (id: string) => {
  if (confirm($t('mod.academy.confirm_delete_badge'))) {
    badges.value = badges.value.filter(b => b.id !== id)
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
</style>
