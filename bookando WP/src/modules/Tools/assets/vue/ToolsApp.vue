<template>
  <div class="bookando-tools">
    <div class="bookando-tools-header">
      <h1>{{ t('mod.tools.title') }}</h1>
      <p>{{ t('mod.tools.description') }}</p>
    </div>

    <div class="bookando-tools-content">
      <div class="bookando-card">
        <h2>{{ t('mod.tools.system_info') }}</h2>
        <div v-if="loading">
          {{ t('core.common.loading') }}...
        </div>
        <div v-else-if="systemInfo">
          <p><strong>WordPress:</strong> {{ systemInfo.wordpress_version }}</p>
          <p><strong>PHP:</strong> {{ systemInfo.php_version }}</p>
          <p><strong>Bookando:</strong> {{ systemInfo.bookando_version }}</p>
        </div>
      </div>

      <div class="bookando-card">
        <h2>{{ t('mod.tools.reports') }}</h2>
        <p>{{ t('mod.tools.reports_description') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

import { extractRestData } from './utils/api'

const { t } = useI18n()

const loading = ref(false)
const systemInfo = ref<any>(null)

onMounted(async () => {
  loading.value = true
  try {
    const response = await fetch('/wp-json/bookando/v1/tools/system-info', {
      headers: {
        'X-WP-Nonce': (window as any).BOOKANDO_VARS?.nonce || ''
      }
    })
    const result = await response.json()
    const payload = extractRestData(result)
    if (payload) {
      systemInfo.value = payload
    }
  } catch (error) {
    console.error('Failed to load system info:', error)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.bookando-tools {
  padding: var(--bookando-space-lg);
}

.bookando-tools-header {
  margin-bottom: var(--bookando-space-xl);
}

.bookando-tools-header h1 {
  font-size: var(--bookando-font-xxl);
  margin: 0 0 var(--bookando-space-sm);
}

.bookando-tools-header p {
  color: var(--bookando-text-muted);
  margin: 0;
}

.bookando-tools-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--bookando-space-lg);
}

.bookando-card {
  background: var(--bookando-surface);
  border: 1px solid var(--bookando-border);
  border-radius: var(--bookando-radius-lg);
  padding: var(--bookando-space-lg);
}

.bookando-card h2 {
  font-size: var(--bookando-font-lg);
  margin: 0 0 var(--bookando-space-md);
}

.bookando-card p {
  margin: var(--bookando-space-sm) 0;
}
</style>
