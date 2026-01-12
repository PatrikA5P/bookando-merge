<!-- AppShell.vue -->
<template>
  <div class="bookando-admin-app">
    <!-- Page/View steckt im default-slot -->
    <slot />

    <!-- globale Singletons -->
    <AppInfoModal />

    <!-- optionale Teleport-Senken (falls Komponenten NICHT nach <body> teleporten) -->
    <div id="bookando-modals-root" />
    <div id="bookando-toasts-root" />
    <div id="bookando-popovers-root" />
  </div>
</template>

<script setup lang="ts">
import AppInfoModal from './AppInfoModal.vue'
</script>
