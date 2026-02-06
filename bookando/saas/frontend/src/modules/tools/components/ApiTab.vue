<script setup lang="ts">
/**
 * ApiTab -- API-Schluessel & Webhook-Verwaltung
 *
 * Features:
 * - API Keys Tabelle mit Maskierung und Show/Hide
 * - Neuen API-Schluessel erstellen (Modal)
 * - Webhook-Konfiguration: URL, Events, Test
 * - Dokumentationslinks (Swagger UI, Postman)
 * - System Health Dashboard: Latenz, Success Rate, Rate Limit
 */
import { ref, computed } from 'vue';
import BButton from '@/components/ui/BButton.vue';
import BInput from '@/components/ui/BInput.vue';
import BSelect from '@/components/ui/BSelect.vue';
import BTable from '@/components/ui/BTable.vue';
import BBadge from '@/components/ui/BBadge.vue';
import BModal from '@/components/ui/BModal.vue';
import { useBreakpoint } from '@/composables/useBreakpoint';
import { useToast } from '@/composables/useToast';
import { useI18n } from '@/composables/useI18n';
import { BUTTON_STYLES, CARD_STYLES, BADGE_STYLES, GRID_STYLES, INPUT_STYLES } from '@/design';

const { t } = useI18n();
const toast = useToast();
const { isMobile } = useBreakpoint();

// API Keys
interface ApiKey {
  id: string;
  name: string;
  key: string;
  createdAt: string;
  lastUsed: string | null;
  status: 'active' | 'revoked';
}

const apiKeys = ref<ApiKey[]>([
  { id: 'key-1', name: 'Production App', key: 'bk_live_abc123def456ghi789jkl012mno345', createdAt: '2025-03-15', lastUsed: '2025-06-02', status: 'active' },
  { id: 'key-2', name: 'Development', key: 'bk_test_xyz789uvw456rst123opq012nml345', createdAt: '2025-04-01', lastUsed: '2025-06-01', status: 'active' },
  { id: 'key-3', name: 'Legacy Integration', key: 'bk_live_old111aaa222bbb333ccc444ddd555', createdAt: '2024-11-10', lastUsed: '2025-02-15', status: 'revoked' },
]);

const visibleKeys = ref<Set<string>>(new Set());
const isCreateModalOpen = ref(false);
const newKeyName = ref('');

const apiKeyColumns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'key', label: t('tools.api.key') || 'Schluessel' },
  { key: 'createdAt', label: t('tools.api.created') || 'Erstellt', sortable: true },
  { key: 'lastUsed', label: t('tools.api.lastUsed') || 'Zuletzt verwendet' },
  { key: 'actions', label: '', width: '120px', align: 'right' as const },
];

function maskKey(key: string): string {
  return key.substring(0, 10) + '...' + key.substring(key.length - 4);
}

function toggleKeyVisibility(keyId: string) {
  if (visibleKeys.value.has(keyId)) {
    visibleKeys.value.delete(keyId);
  } else {
    visibleKeys.value.add(keyId);
  }
}

function createApiKey() {
  if (!newKeyName.value.trim()) return;
  const newKey: ApiKey = {
    id: `key-${Date.now()}`,
    name: newKeyName.value,
    key: `bk_live_${Math.random().toString(36).slice(2)}${Math.random().toString(36).slice(2)}`,
    createdAt: new Date().toISOString().split('T')[0],
    lastUsed: null,
    status: 'active',
  };
  apiKeys.value.unshift(newKey);
  isCreateModalOpen.value = false;
  newKeyName.value = '';
  toast.success(t('tools.api.keyCreated') || 'API-Schluessel erstellt');
}

function revokeKey(keyId: string) {
  const key = apiKeys.value.find(k => k.id === keyId);
  if (key) {
    key.status = 'revoked';
    toast.success(t('tools.api.keyRevoked') || 'API-Schluessel widerrufen');
  }
}

// Webhooks
interface Webhook {
  id: string;
  url: string;
  events: string[];
  status: 'active' | 'inactive';
}

const webhooks = ref<Webhook[]>([
  { id: 'wh-1', url: 'https://example.com/webhooks/bookando', events: ['appointment.created', 'appointment.updated'], status: 'active' },
]);

const newWebhookUrl = ref('');
const selectedWebhookEvents = ref<string[]>([]);

const webhookEventOptions = [
  { value: 'appointment.created', label: 'Appointment Created' },
  { value: 'appointment.updated', label: 'Appointment Updated' },
  { value: 'appointment.cancelled', label: 'Appointment Cancelled' },
  { value: 'customer.created', label: 'Customer Created' },
  { value: 'customer.updated', label: 'Customer Updated' },
  { value: 'payment.completed', label: 'Payment Completed' },
  { value: 'invoice.created', label: 'Invoice Created' },
];

function testWebhook(webhookId: string) {
  toast.info(t('tools.api.testingSent') || 'Test-Webhook gesendet...');
}

// Health metrics
const healthMetrics = ref({
  latency: 45,
  successRate: 99.7,
  rateLimit: 32,
  rateLimitMax: 100,
});
</script>

<template>
  <div class="space-y-6">
    <!-- API Keys Section -->
    <div :class="CARD_STYLES.base" class="overflow-hidden">
      <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">{{ t('tools.api.apiKeys') || 'API-Schluessel' }}</h3>
          <p class="text-xs text-slate-500 mt-0.5">{{ t('tools.api.apiKeysDesc') || 'Verwalten Sie Ihre API-Schluessel fuer den Zugriff auf die Bookando API' }}</p>
        </div>
        <BButton variant="primary" size="sm" @click="isCreateModalOpen = true">
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('tools.api.createKey') || 'Neuen API-Schluessel erstellen' }}
        </BButton>
      </div>

      <BTable
        :columns="apiKeyColumns"
        :data="apiKeys as unknown as Record<string, unknown>[]"
        :empty-title="t('tools.api.noKeys') || 'Keine API-Schluessel'"
        :empty-message="t('tools.api.noKeysDesc') || 'Erstellen Sie Ihren ersten API-Schluessel'"
      >
        <template #cell-name="{ row }">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-slate-900">{{ (row as any).name }}</span>
            <BBadge :variant="(row as any).status === 'active' ? 'success' : 'danger'" class="text-[10px]">
              {{ (row as any).status === 'active'
                ? (t('tools.api.active') || 'Aktiv')
                : (t('tools.api.revoked') || 'Widerrufen')
              }}
            </BBadge>
          </div>
        </template>

        <template #cell-key="{ row }">
          <div class="flex items-center gap-2">
            <code class="text-xs bg-slate-100 px-2 py-1 rounded font-mono text-slate-700">
              {{ visibleKeys.has((row as any).id) ? (row as any).key : maskKey((row as any).key) }}
            </code>
            <button
              :class="BUTTON_STYLES.icon"
              class="!p-1"
              :title="visibleKeys.has((row as any).id) ? (t('tools.api.hide') || 'Verbergen') : (t('tools.api.show') || 'Anzeigen')"
              @click.stop="toggleKeyVisibility((row as any).id)"
            >
              <svg v-if="visibleKeys.has((row as any).id)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </button>
          </div>
        </template>

        <template #cell-lastUsed="{ row }">
          <span class="text-sm text-slate-500">
            {{ (row as any).lastUsed || (t('tools.api.neverUsed') || 'Nie verwendet') }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <BButton
            v-if="(row as any).status === 'active'"
            variant="danger"
            size="xs"
            class="!text-xs"
            @click.stop="revokeKey((row as any).id)"
          >
            {{ t('tools.api.revoke') || 'Widerrufen' }}
          </BButton>
        </template>
      </BTable>
    </div>

    <!-- Webhook Configuration -->
    <div :class="CARD_STYLES.base" class="p-5">
      <h3 class="text-sm font-semibold text-slate-900 mb-1">{{ t('tools.api.webhooks') || 'Webhooks' }}</h3>
      <p class="text-xs text-slate-500 mb-4">{{ t('tools.api.webhooksDesc') || 'Empfangen Sie Echtzeit-Benachrichtigungen ueber Ereignisse in Ihrer Bookando-Instanz' }}</p>

      <!-- Existing Webhooks -->
      <div v-for="wh in webhooks" :key="wh.id" class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <code class="text-xs font-mono text-slate-700 truncate">{{ wh.url }}</code>
              <BBadge :variant="wh.status === 'active' ? 'success' : 'default'">
                {{ wh.status === 'active' ? (t('tools.api.active') || 'Aktiv') : (t('tools.api.inactive') || 'Inaktiv') }}
              </BBadge>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <BBadge v-for="event in wh.events" :key="event" variant="info" class="text-[10px]">
                {{ event }}
              </BBadge>
            </div>
          </div>
          <BButton variant="secondary" size="sm" class="!text-xs shrink-0" @click="testWebhook(wh.id)">
            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            {{ t('tools.api.test') || 'Testen' }}
          </BButton>
        </div>
      </div>

      <!-- Add Webhook -->
      <div class="flex flex-col sm:flex-row items-end gap-3">
        <div class="flex-1 w-full">
          <BInput
            v-model="newWebhookUrl"
            type="url"
            :label="t('tools.api.webhookUrl') || 'Webhook URL'"
            placeholder="https://example.com/webhook"
          />
        </div>
        <BButton variant="primary" size="sm" :disabled="!newWebhookUrl" class="shrink-0">
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ t('tools.api.addWebhook') || 'Webhook hinzufuegen' }}
        </BButton>
      </div>
    </div>

    <!-- Documentation Links -->
    <div :class="GRID_STYLES.cols2">
      <div :class="CARD_STYLES.interactive" class="p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-semibold text-slate-900">Swagger UI</h4>
            <p class="text-xs text-slate-500">{{ t('tools.api.swaggerDesc') || 'Interaktive API-Dokumentation mit Try-it-out' }}</p>
          </div>
        </div>
      </div>
      <div :class="CARD_STYLES.interactive" class="p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h4 class="text-sm font-semibold text-slate-900">Postman Collection</h4>
            <p class="text-xs text-slate-500">{{ t('tools.api.postmanDesc') || 'Fertige Collection mit allen Endpoints zum Importieren' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- System Health Dashboard -->
    <div :class="CARD_STYLES.base" class="p-5">
      <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ t('tools.api.systemHealth') || 'System Health' }}</h3>
      <div :class="GRID_STYLES.cols3">
        <!-- API Latency -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500">{{ t('tools.api.latency') || 'API Latenz' }}</span>
            <span class="text-sm font-semibold text-slate-900">{{ healthMetrics.latency }}ms</span>
          </div>
          <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="healthMetrics.latency < 100 ? 'bg-emerald-500' : healthMetrics.latency < 300 ? 'bg-amber-500' : 'bg-red-500'"
              :style="{ width: `${Math.min(healthMetrics.latency / 5, 100)}%` }"
            />
          </div>
          <p class="text-[10px] text-slate-400">{{ t('tools.api.latencyTarget') || 'Ziel: < 100ms' }}</p>
        </div>

        <!-- Success Rate -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500">{{ t('tools.api.successRate') || 'Erfolgsrate' }}</span>
            <span class="text-sm font-semibold text-slate-900">{{ healthMetrics.successRate }}%</span>
          </div>
          <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="healthMetrics.successRate > 99 ? 'bg-emerald-500' : healthMetrics.successRate > 95 ? 'bg-amber-500' : 'bg-red-500'"
              :style="{ width: `${healthMetrics.successRate}%` }"
            />
          </div>
          <p class="text-[10px] text-slate-400">{{ t('tools.api.successRateTarget') || 'Ziel: > 99.5%' }}</p>
        </div>

        <!-- Rate Limit -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500">{{ t('tools.api.rateLimit') || 'Rate Limit' }}</span>
            <span class="text-sm font-semibold text-slate-900">{{ healthMetrics.rateLimit }}/{{ healthMetrics.rateLimitMax }}</span>
          </div>
          <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="(healthMetrics.rateLimit / healthMetrics.rateLimitMax) < 0.6 ? 'bg-emerald-500' : (healthMetrics.rateLimit / healthMetrics.rateLimitMax) < 0.85 ? 'bg-amber-500' : 'bg-red-500'"
              :style="{ width: `${(healthMetrics.rateLimit / healthMetrics.rateLimitMax) * 100}%` }"
            />
          </div>
          <p class="text-[10px] text-slate-400">{{ t('tools.api.rateLimitInfo') || 'Anfragen pro Minute' }}</p>
        </div>
      </div>
    </div>

    <!-- Create API Key Modal -->
    <BModal
      v-model="isCreateModalOpen"
      :title="t('tools.api.createKeyTitle') || 'Neuen API-Schluessel erstellen'"
      size="sm"
    >
      <div class="space-y-4">
        <BInput
          v-model="newKeyName"
          :label="t('tools.api.keyName') || 'Name'"
          :placeholder="t('tools.api.keyNamePlaceholder') || 'z.B. Production App'"
          required
        />
        <p class="text-xs text-slate-500">
          {{ t('tools.api.keyWarning') || 'Der API-Schluessel wird nur einmal angezeigt. Speichern Sie ihn sicher.' }}
        </p>
      </div>
      <template #footer>
        <BButton variant="secondary" @click="isCreateModalOpen = false">
          {{ t('common.cancel') || 'Abbrechen' }}
        </BButton>
        <BButton variant="primary" :disabled="!newKeyName.trim()" @click="createApiKey">
          {{ t('tools.api.create') || 'Erstellen' }}
        </BButton>
      </template>
    </BModal>
  </div>
</template>
