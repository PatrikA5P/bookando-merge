<!-- AppForm.vue -->
<template>
  <div
    class="bookando-side-dialog"
    role="document"
  >
    <!-- Fix: Header -->
    <header class="dialog-title-row">
      <slot name="header" />
    </header>

    <!-- Fix: Tabs außerhalb des Scroll-Containers -->
    <div v-if="$slots.tabs">
      <slot name="tabs" />
    </div>


    <!-- Scrollbarer Body -->
    <div class="bookando-side-dialog__body">
      <slot />
    </div>

    <!-- Fix: Sticky Footer -->
    <div class="sticky-footer">
      <slot name="footer" />
    </div>
  </div>
</template>
