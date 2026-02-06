<script setup lang="ts">
import { ref } from 'vue';
import BBadge from '@/components/ui/BBadge.vue';
import BButton from '@/components/ui/BButton.vue';
import { useI18n } from '@/composables/useI18n';
import { CARD_STYLES } from '@/design';

const { t } = useI18n();

interface Integration {
  id: string;
  name: string;
  description: string;
  icon: string;
  connected: boolean;
  category: string;
}

const integrations = ref<Integration[]>([
  { id: 'google-cal', name: 'Google Calendar', description: 'Sync appointments with Google Calendar', icon: '\uD83D\uDCC5', connected: true, category: 'calendar' },
  { id: 'outlook', name: 'Microsoft Outlook', description: 'Sync with Outlook Calendar & Contacts', icon: '\uD83D\uDCE7', connected: false, category: 'calendar' },
  { id: 'stripe', name: 'Stripe', description: 'Online payments via Stripe', icon: '\uD83D\uDCB3', connected: true, category: 'payment' },
  { id: 'twint', name: 'TWINT', description: 'Swiss mobile payment', icon: '\uD83C\uDDE8\uD83C\uDDED', connected: false, category: 'payment' },
  { id: 'mailchimp', name: 'Mailchimp', description: 'Email marketing automation', icon: '\u2709\uFE0F', connected: false, category: 'marketing' },
  { id: 'slack', name: 'Slack', description: 'Team notifications', icon: '\uD83D\uDCAC', connected: false, category: 'communication' },
]);
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
    <div
      v-for="integration in integrations"
      :key="integration.id"
      :class="CARD_STYLES.base"
      class="hover:shadow-md transition-shadow"
    >
      <div class="p-4">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <span class="text-2xl">{{ integration.icon }}</span>
            <div>
              <h3 class="text-sm font-semibold text-slate-900">{{ integration.name }}</h3>
              <p class="text-xs text-slate-500 mt-0.5">{{ integration.description }}</p>
            </div>
          </div>
        </div>
        <div class="mt-4 flex items-center justify-between">
          <BBadge :status="integration.connected ? 'ACTIVE' : 'INACTIVE'" dot>
            {{ integration.connected ? t('settings.connected') : t('settings.disconnected') }}
          </BBadge>
          <BButton :variant="integration.connected ? 'secondary' : 'primary'" size="sm">
            {{ integration.connected ? t('settings.configure') : 'Connect' }}
          </BButton>
        </div>
      </div>
    </div>
  </div>
</template>
