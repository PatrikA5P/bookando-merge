<script setup lang="ts">
/**
 * BFormSection — Strukturierter Formularabschnitt
 *
 * Wird innerhalb von BFormPanel verwendet, um Formulare
 * in logische Abschnitte mit optionalem Titel und Beschreibung zu gliedern.
 *
 * Usage:
 *   <BFormSection title="Preise" description="Alle Preise in CHF">
 *     <BInput label="Preis" ... />
 *     <BInput label="Aktionspreis" ... />
 *   </BFormSection>
 */
defineProps<{
  title?: string;
  description?: string;
  /** Kompakter Abstand (fuer verschachtelte Sektionen) */
  compact?: boolean;
  /** Optionaler Rand zwischen Sektionen */
  divided?: boolean;
  /** Grid: wie viele Spalten fuer die Felder */
  columns?: 1 | 2 | 3;
}>();

const gridClass: Record<number, string> = {
  1: 'grid grid-cols-1 gap-4',
  2: 'grid grid-cols-1 md:grid-cols-2 gap-4',
  3: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4',
};
</script>

<template>
  <div
    :class="[
      divided ? 'border-b border-slate-100 pb-6 mb-6 last:border-0 last:pb-0 last:mb-0' : '',
      compact ? 'mb-4' : 'mb-6',
    ]"
  >
    <!-- Section Header -->
    <div v-if="title || description" :class="compact ? 'mb-3' : 'mb-4'">
      <h3 v-if="title" class="text-sm font-semibold text-slate-800 uppercase tracking-wide">
        {{ title }}
      </h3>
      <p v-if="description" class="text-xs text-slate-500 mt-0.5">
        {{ description }}
      </p>
    </div>

    <!-- Fields -->
    <div :class="columns ? gridClass[columns] : ''">
      <slot />
    </div>
  </div>
</template>
