<template>
  <div
    class="p-8 transition-all duration-300"
    :style="{
      background: theme.colors.background,
      fontFamily: theme.typography.fontFamily,
      fontSize: (theme.typography.scale / 100) + 'rem'
    }"
  >
    <!-- Booking Widget Preview -->
    <div v-if="context === 'widget'" class="space-y-6">
      <div class="text-center mb-6">
        <h1
          class="text-3xl font-bold mb-2"
          :style="{ color: theme.colors.text }"
        >
          Book Your Service
        </h1>
        <p :style="{ color: theme.colors.textMuted }">
          Choose from our available services
        </p>
      </div>

      <!-- Service Cards -->
      <div class="grid grid-cols-2 gap-4">
        <div
          v-for="i in 4"
          :key="i"
          class="p-4 transition-all hover:scale-105 cursor-pointer"
          :style="{
            background: theme.colors.surface,
            borderRadius: theme.shape.radius + 'px',
            border: theme.shape.borderWidth + 'px solid ' + theme.colors.border,
            boxShadow: getShadow(theme.shape.shadow)
          }"
        >
          <div
            class="w-full h-24 mb-3"
            :style="{
              background: theme.colors.primary,
              borderRadius: theme.shape.radius / 2 + 'px'
            }"
          ></div>
          <h3
            class="font-bold mb-1"
            :style="{ color: theme.colors.text }"
          >
            Service {{ i }}
          </h3>
          <p
            class="text-sm mb-2"
            :style="{ color: theme.colors.textMuted }"
          >
            60 min
          </p>
          <div
            class="inline-block px-3 py-1 text-sm font-bold"
            :style="{
              background: theme.colors.primary,
              color: '#ffffff',
              borderRadius: theme.shape.radius / 2 + 'px'
            }"
          >
            CHF 120
          </div>
        </div>
      </div>

      <!-- CTA Button -->
      <button
        class="w-full py-4 font-bold text-white transition-opacity hover:opacity-90"
        :style="{
          background: theme.colors.primary,
          borderRadius: theme.shape.radius + 'px',
          boxShadow: getShadow(theme.shape.shadow)
        }"
      >
        Continue to Booking
      </button>
    </div>

    <!-- Customer Portal Preview -->
    <div v-else-if="context === 'customer'" class="space-y-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-white"
            :style="{ background: theme.colors.primary }"
          >
            JD
          </div>
          <div>
            <h2
              class="font-bold"
              :style="{ color: theme.colors.text }"
            >
              John Doe
            </h2>
            <p
              class="text-sm"
              :style="{ color: theme.colors.textMuted }"
            >
              Premium Member
            </p>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-4">
        <div
          v-for="stat in stats"
          :key="stat.label"
          class="p-4 text-center"
          :style="{
            background: theme.colors.surface,
            borderRadius: theme.shape.radius + 'px',
            border: theme.shape.borderWidth + 'px solid ' + theme.colors.border
          }"
        >
          <div
            class="text-2xl font-bold mb-1"
            :style="{ color: theme.colors.primary }"
          >
            {{ stat.value }}
          </div>
          <div
            class="text-sm"
            :style="{ color: theme.colors.textMuted }"
          >
            {{ stat.label }}
          </div>
        </div>
      </div>

      <!-- Appointments List -->
      <div
        class="p-6"
        :style="{
          background: theme.colors.surface,
          borderRadius: theme.shape.radius + 'px',
          border: theme.shape.borderWidth + 'px solid ' + theme.colors.border
        }"
      >
        <h3
          class="font-bold mb-4"
          :style="{ color: theme.colors.text }"
        >
          Upcoming Appointments
        </h3>
        <div class="space-y-3">
          <div
            v-for="i in 3"
            :key="i"
            class="flex items-center justify-between p-3"
            :style="{
              background: theme.colors.background,
              borderRadius: theme.shape.radius / 2 + 'px'
            }"
          >
            <div>
              <div
                class="font-medium mb-1"
                :style="{ color: theme.colors.text }"
              >
                Deep Tissue Massage
              </div>
              <div
                class="text-sm"
                :style="{ color: theme.colors.textMuted }"
              >
                Tomorrow at 14:00
              </div>
            </div>
            <div
              class="px-3 py-1 text-xs font-bold"
              :style="{
                background: theme.colors.success,
                color: '#ffffff',
                borderRadius: theme.shape.radius / 3 + 'px'
              }"
            >
              Confirmed
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Employee Hub Preview -->
    <div v-else-if="context === 'employee'" class="space-y-6">
      <h1
        class="text-2xl font-bold mb-6"
        :style="{ color: theme.colors.text }"
      >
        Employee Dashboard
      </h1>

      <!-- Today's Schedule -->
      <div
        class="p-6"
        :style="{
          background: theme.colors.surface,
          borderRadius: theme.shape.radius + 'px',
          border: theme.shape.borderWidth + 'px solid ' + theme.colors.border
        }"
      >
        <h3
          class="font-bold mb-4"
          :style="{ color: theme.colors.text }"
        >
          Today's Schedule
        </h3>
        <div class="space-y-2">
          <div
            v-for="time in ['09:00', '10:30', '14:00', '15:30']"
            :key="time"
            class="flex items-center gap-3 p-3"
            :style="{
              background: theme.colors.background,
              borderRadius: theme.shape.radius / 2 + 'px',
              borderLeft: '4px solid ' + theme.colors.primary
            }"
          >
            <div
              class="font-bold"
              :style="{ color: theme.colors.primary }"
            >
              {{ time }}
            </div>
            <div :style="{ color: theme.colors.text }">
              Client Appointment
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Offer Form Preview -->
    <div v-else class="space-y-6">
      <h2
        class="text-2xl font-bold"
        :style="{ color: theme.colors.text }"
      >
        Service Booking
      </h2>

      <div class="space-y-4">
        <div>
          <label
            class="block text-sm font-bold mb-2"
            :style="{ color: theme.colors.text }"
          >
            Full Name
          </label>
          <input
            type="text"
            class="w-full px-4 py-3 outline-none"
            :style="{
              background: theme.colors.surface,
              borderRadius: theme.shape.radius + 'px',
              border: theme.shape.borderWidth + 'px solid ' + theme.colors.border,
              color: theme.colors.text
            }"
            placeholder="John Doe"
          />
        </div>

        <div>
          <label
            class="block text-sm font-bold mb-2"
            :style="{ color: theme.colors.text }"
          >
            Email
          </label>
          <input
            type="email"
            class="w-full px-4 py-3 outline-none"
            :style="{
              background: theme.colors.surface,
              borderRadius: theme.shape.radius + 'px',
              border: theme.shape.borderWidth + 'px solid ' + theme.colors.border,
              color: theme.colors.text
            }"
            placeholder="john@example.com"
          />
        </div>

        <button
          class="w-full py-4 font-bold text-white"
          :style="{
            background: theme.colors.primary,
            borderRadius: theme.shape.radius + 'px'
          }"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  theme: any
  context: 'widget' | 'offerForm' | 'customer' | 'employee'
}

defineProps<Props>()

const stats = [
  { label: 'Appointments', value: '12' },
  { label: 'Hours', value: '24' },
  { label: 'Points', value: '450' }
]

const getShadow = (shadow: string) => {
  const shadows = {
    none: 'none',
    sm: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
    md: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
    lg: '0 10px 15px -3px rgb(0 0 0 / 0.1)',
    xl: '0 20px 25px -5px rgb(0 0 0 / 0.1)'
  }
  return shadows[shadow] || shadows.sm
}
</script>
