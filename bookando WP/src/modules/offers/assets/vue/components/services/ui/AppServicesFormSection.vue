<template>
  <section
    class="svc-form-section"
    :class="{ 'svc-form-section--compact': compact }"
  >
    <header
      v-if="hasHeader"
      class="svc-form-section__header"
    >
      <div class="svc-form-section__title">
        <AppIcon
          v-if="icon"
          :name="icon"
          class="svc-form-section__icon"
          aria-hidden="true"
        />
        <div>
          <h3
            v-if="title"
            class="svc-form-section__heading"
          >
            {{ title }}
          </h3>
          <p
            v-if="description"
            class="svc-form-section__description"
          >
            {{ description }}
          </p>
        </div>
      </div>
      <div
        v-if="$slots.actions"
        class="svc-form-section__actions"
      >
        <slot name="actions" />
      </div>
    </header>

    <div
      :class="bodyClasses"
      :data-stacked="layout === 'stack' || undefined"
    >
      <slot />
    </div>

    <footer
      v-if="$slots.footer"
      class="svc-form-section__footer"
    >
      <slot name="footer" />
    </footer>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AppIcon from '@core/Design/components/AppIcon.vue'

type Layout = 'grid' | 'stack'

type Columns = 1 | 2 | 3

const props = withDefaults(defineProps<{
  title?: string
  description?: string
  icon?: string
  layout?: Layout
  columns?: Columns
  dense?: boolean
  compact?: boolean
}>(), {
  layout: 'grid',
  columns: 1,
  dense: false,
  compact: false,
})

const hasHeader = computed(() => !!(props.title || props.description || props.icon))

const bodyClasses = computed(() => {
  const classes = ['svc-form-section__body'] as string[]
  if (props.layout === 'stack') {
    classes.push('svc-form-section__body--stack')
  } else {
    classes.push('svc-form-section__body--grid')
    classes.push(`svc-form-section__body--cols-${props.columns}`)
  }
  if (props.dense) classes.push('is-dense')
  return classes
})
</script>

<style scoped>
.svc-form-section {
  --svc-form-border: color-mix(in srgb, var(--bookando-border, #d5d9e3) 70%, transparent);
  --svc-form-radius: var(--bookando-radius, 16px);
  --svc-form-padding: clamp(1rem, 0.75rem + 0.5vw, 1.75rem);
  --svc-form-gap: clamp(0.9rem, 0.6rem + 0.35vw, 1.4rem);
  background: var(--bookando-card-bg, #fff);
  border: 1px solid var(--svc-form-border);
  border-radius: var(--svc-form-radius);
  padding: var(--svc-form-padding);
  box-shadow: var(--bookando-card-shadow, 0 16px 32px -24px rgba(15, 23, 42, 0.45));
  display: flex;
  flex-direction: column;
  gap: var(--svc-form-gap);
}

.svc-form-section--compact {
  --svc-form-padding: clamp(0.75rem, 0.6rem + 0.35vw, 1.2rem);
  --svc-form-gap: clamp(0.7rem, 0.5rem + 0.3vw, 1rem);
}

.svc-form-section__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--bookando-space-sm, 0.75rem);
}

.svc-form-section__title {
  display: inline-flex;
  align-items: flex-start;
  gap: clamp(0.6rem, 0.35rem + 0.4vw, 1rem);
}

.svc-form-section__icon {
  font-size: clamp(1.35rem, 1rem + 0.4vw, 1.75rem);
  color: var(--bookando-primary, #2563eb);
  flex-shrink: 0;
}

.svc-form-section__heading {
  font-size: clamp(1.05rem, 0.95rem + 0.25vw, 1.35rem);
  font-weight: 600;
  margin: 0;
  color: var(--bookando-text-strong, #0f172a);
}

.svc-form-section__description {
  margin: 0.2rem 0 0;
  color: var(--bookando-text-muted, #5f6b7a);
  font-size: 0.925rem;
  line-height: 1.45;
}

.svc-form-section__actions {
  display: inline-flex;
  align-items: center;
  gap: var(--bookando-space-xs, 0.5rem);
}

.svc-form-section__body {
  display: grid;
  gap: clamp(0.75rem, 0.55rem + 0.25vw, 1.1rem);
}

.svc-form-section__body.is-dense {
  gap: clamp(0.5rem, 0.4rem + 0.2vw, 0.75rem);
}

.svc-form-section__body--grid {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.svc-form-section__body--cols-2 {
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.svc-form-section__body--cols-3 {
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.svc-form-section__body--stack {
  display: flex;
  flex-direction: column;
}

.svc-form-section__footer {
  border-top: 1px solid color-mix(in srgb, var(--svc-form-border) 65%, transparent);
  padding-top: clamp(0.75rem, 0.55rem + 0.25vw, 1.1rem);
  margin-top: clamp(0.5rem, 0.4rem + 0.2vw, 0.75rem);
}

@media (max-width: 768px) {
  .svc-form-section__header {
    flex-direction: column;
    align-items: flex-start;
  }
  .svc-form-section__actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
