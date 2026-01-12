<!-- DesignTypeCard.vue -->
<template>
  <AppCard
    :hide-header="true"
    :disabled="disabled"
    :hoverable="true"
    :clickable="!disabled"
    :expandable="false"
    rounded="sm"
    shadow="1"
    padding="0"
    body-padding="md"
    :cols="'1fr'"
    :rows="'auto auto 1fr auto'"
    place-items="start"
    @click="$emit('click')"
  >
    <!-- Row 1: Static Image -->
    <div class="design-type-card__image">
      <slot name="image">
        <!-- Placeholder for static image -->
      </slot>
    </div>

    <!-- Row 2: Icon + Title on same line -->
    <div class="design-type-card__header">
      <AppIcon
        v-if="icon"
        :name="icon"
        class="bookando-icon--lg"
      />
      <h3>
        {{ title }}
        <span
          v-if="badge"
          class="design-type-card__badge"
        >{{ badge }}</span>
      </h3>
    </div>

    <!-- Row 3: Description (takes remaining space) -->
    <div class="design-type-card__description">
      <p>{{ description }}</p>
    </div>

    <!-- Row 4: Button -->
    <div class="design-type-card__action">
      <AppButton
        variant="primary"
        size="md"
        :disabled="disabled"
      >
        {{ buttonText || 'Weiter' }}
      </AppButton>
    </div>
  </AppCard>
</template>

<script setup lang="ts">
import AppCard from '@core/Design/components/AppCard.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppButton from '@core/Design/components/AppButton.vue'

defineProps<{
  icon?: string
  title: string
  description: string
  badge?: string
  buttonText?: string
  disabled?: boolean
}>()

defineEmits<{ (event: 'click'): void }>()
</script>

<style scoped lang="scss">
@use '@core/Design/assets/scss/variables' as *;

.design-type-card__image {
  width: 100%;
  height: 160px;
  background: linear-gradient(135deg, $bookando-primary-light 0%, $bookando-bg-soft 100%);
  border-radius: $bookando-radius-sm;
  margin-bottom: $bookando-spacing-sm;
  overflow: hidden;
}

.design-type-card__header {
  width: 100%;
  display: flex;
  align-items: center;
  gap: $bookando-spacing-sm;
  margin-bottom: $bookando-spacing-sm;

  h3 {
    margin: 0;
    font-size: $bookando-font-size-lg;
    font-weight: $bookando-font-weight-semi-bold;
    color: $bookando-text-dark;
    display: flex;
    align-items: center;
    gap: $bookando-spacing-xs;
  }
}

.design-type-card__badge {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  background: $bookando-primary;
  color: $bookando-white;
  font-size: $bookando-font-size-xs;
  font-weight: $bookando-font-weight-semi-bold;
  border-radius: $bookando-radius-pill;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.design-type-card__description {
  width: 100%;
  flex: 1;
  margin-bottom: $bookando-spacing-md;

  p {
    margin: 0;
    color: $bookando-text-muted;
    font-size: $bookando-font-size-sm;
    line-height: 1.5;
  }
}

.design-type-card__action {
  width: 100%;
}
</style>
