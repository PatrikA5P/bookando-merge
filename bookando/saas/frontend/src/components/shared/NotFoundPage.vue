<script setup lang="ts">
import { useRouter } from 'vue-router';
import { BUTTON_STYLES } from '@/design';

const router = useRouter();
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
    <div class="text-center">
      <p class="text-6xl font-extrabold text-brand-600">404</p>
      <h1 class="mt-4 text-2xl font-bold text-slate-900">Seite nicht gefunden</h1>
      <p class="mt-2 text-slate-500">Die angeforderte Seite existiert nicht oder wurde verschoben.</p>
      <button
        :class="[BUTTON_STYLES.primary, 'mt-6']"
        @click="router.push('/dashboard')"
      >
        Zum Dashboard
      </button>
    </div>
  </div>
</template>
