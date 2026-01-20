<template>
  <ModuleLayout
    :hero-title="$t('mod.resources.title')"
    :hero-description="$t('mod.resources.description')"
    :hero-icon="BoxIcon"
    hero-gradient="bg-gradient-to-br from-cyan-700 to-teal-900"
    :tabs="tabs"
    :active-tab="activeTab"
    @update:active-tab="handleTabChange"
    :show-search="false"
  >
    <!-- Locations Tab -->
    <div v-if="activeTab === 'locations'" class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          v-for="location in store.resources.locations"
          :key="location.id"
          class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col"
        >
          <div class="flex justify-between items-start mb-4">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
              <MapIcon :size="24" />
            </div>
            <span
              :class="[
                'px-2 py-1 rounded-full text-xs font-medium',
                location.status === 'Open'
                  ? 'bg-emerald-100 text-emerald-800'
                  : 'bg-rose-100 text-rose-800'
              ]"
            >
              {{ location.status }}
            </span>
          </div>
          <h3 class="text-lg font-bold text-slate-900 mb-1">{{ location.name }}</h3>
          <p class="text-slate-500 text-sm mb-4 flex items-center gap-1">
            <MapPinIcon :size="14" /> {{ location.address }}
          </p>
          <div class="mt-auto pt-4 border-t border-slate-100 flex justify-between items-center">
            <span class="text-sm font-medium text-slate-600">
              {{ location.rooms }} {{ $t('mod.resources.rooms_configured') }}
            </span>
            <button class="text-brand-600 text-sm font-medium hover:underline">
              {{ $t('mod.resources.manage_details') }}
            </button>
          </div>
        </div>

        <!-- Add New Location -->
        <button
          @click="handleAddLocation"
          class="border-2 border-dashed border-slate-300 rounded-xl p-6 flex flex-col items-center justify-center text-slate-400 hover:border-brand-400 hover:text-brand-600 hover:bg-slate-50 transition-all min-h-[200px]"
        >
          <PlusIcon :size="32" class="mb-2" />
          <span class="font-medium">{{ $t('mod.resources.add_location') }}</span>
        </button>
      </div>
    </div>

    <!-- Rooms Tab -->
    <div v-if="activeTab === 'rooms'" class="p-6">
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div
          v-for="room in store.resources.rooms"
          :key="room.id"
          class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow"
        >
          <div
            :class="[
              'h-2 w-full',
              room.status === 'Available'
                ? 'bg-emerald-500'
                : room.status === 'In Use'
                ? 'bg-amber-500'
                : 'bg-rose-500'
            ]"
          ></div>
          <div class="p-5">
            <div class="flex justify-between items-center mb-2">
              <h3 class="font-semibold text-slate-800 text-lg">{{ room.name }}</h3>
              <MoreHorizontalIcon
                :size="18"
                class="text-slate-400 cursor-pointer hover:text-slate-600"
              />
            </div>
            <p class="text-xs text-slate-500 mb-4">{{ room.location }}</p>

            <div class="space-y-2 mb-4">
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">{{ $t('mod.resources.capacity') }}</span>
                <span class="font-medium text-slate-800">{{ room.capacity }} {{ $t('mod.resources.people') }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-slate-500">{{ $t('common.status') }}</span>
                <span
                  :class="[
                    'font-medium',
                    room.status === 'Available'
                      ? 'text-emerald-600'
                      : room.status === 'In Use'
                      ? 'text-amber-600'
                      : 'text-rose-600'
                  ]"
                >
                  {{ room.status }}
                </span>
              </div>
            </div>

            <div class="flex gap-2 flex-wrap">
              <span
                v-for="(feature, i) in room.features"
                :key="i"
                class="text-[10px] bg-slate-100 text-slate-600 px-2 py-1 rounded border border-slate-200"
              >
                {{ feature }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Equipment/Materials Tab -->
    <div v-if="activeTab === 'equipment'" class="p-6">
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold">
              <tr>
                <th class="p-4">{{ $t('mod.resources.item_name') }}</th>
                <th class="p-4">{{ $t('mod.resources.category') }}</th>
                <th class="p-4">{{ $t('mod.resources.availability') }}</th>
                <th class="p-4">{{ $t('mod.resources.condition') }}</th>
                <th class="p-4 text-right">{{ $t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in store.resources.materials"
                :key="item.id"
                class="hover:bg-slate-50 group"
              >
                <td class="p-4 font-medium text-slate-800 flex items-center gap-3">
                  <div class="bg-slate-100 p-2 rounded text-slate-500">
                    <BoxIcon :size="16" />
                  </div>
                  {{ item.name }}
                </td>
                <td class="p-4 text-sm text-slate-600">{{ item.category }}</td>
                <td class="p-4">
                  <div class="w-32">
                    <div class="flex justify-between text-xs mb-1">
                      <span class="font-medium text-slate-700">{{ item.available }}/{{ item.total }}</span>
                      <span class="text-slate-400">{{ $t('mod.resources.available') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                      <div
                        :class="[
                          'h-1.5 rounded-full',
                          item.available === 0 ? 'bg-rose-400' : 'bg-brand-500'
                        ]"
                        :style="{ width: `${(item.available / item.total) * 100}%` }"
                      ></div>
                    </div>
                  </div>
                </td>
                <td class="p-4">
                  <span
                    :class="[
                      'text-xs font-medium px-2 py-1 rounded-full border',
                      item.condition === 'Good'
                        ? 'bg-emerald-50 border-emerald-100 text-emerald-700'
                        : item.condition === 'Fair'
                        ? 'bg-amber-50 border-amber-100 text-amber-700'
                        : 'bg-rose-50 border-rose-100 text-rose-700'
                    ]"
                  >
                    {{ item.condition }}
                  </span>
                </td>
                <td class="p-4 text-right">
                  <button
                    class="text-slate-400 hover:text-brand-600 opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <SettingsIcon :size="18" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </ModuleLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useResourcesStore } from '../store/resourcesStore'
import ModuleLayout from '@core/Design/components/ModuleLayout.vue'
import {
  Box as BoxIcon,
  MapPin as MapPinIcon,
  Map as MapIcon,
  Plus as PlusIcon,
  MoreHorizontal as MoreHorizontalIcon,
  Settings as SettingsIcon,
  LayoutGrid as LayoutGridIcon
} from 'lucide-vue-next'

const { t: $t } = useI18n()
const store = useResourcesStore()

// State
const activeTab = ref('locations')

// Tabs definition
const tabs = ref([
  { id: 'locations', icon: MapPinIcon, label: $t('mod.resources.tabs.locations') },
  { id: 'rooms', icon: LayoutGridIcon, label: $t('mod.resources.tabs.rooms') },
  { id: 'equipment', icon: BoxIcon, label: $t('mod.resources.tabs.equipment') }
])

// Methods
const handleTabChange = (tabId: string) => {
  activeTab.value = tabId
}

const handleAddLocation = () => {
  // TODO: Implement add location dialog
  alert('Add Location functionality will be implemented')
}

// Lifecycle
onMounted(async () => {
  await store.loadResources()
})
</script>
