/**
 * @bookando/api-client - Vue 3 Composables
 * Vue-specific utilities for using the API client
 */

import { inject, provide, type InjectionKey } from 'vue';
import { createBookandoClient, createWordPressClient, type ApiClientConfig } from './index';

const API_CLIENT_KEY: InjectionKey<ReturnType<typeof createBookandoClient>> = Symbol('bookando-api-client');

/**
 * Provide API client to child components
 */
export function provideApiClient(config: ApiClientConfig) {
  const client = createBookandoClient(config);
  provide(API_CLIENT_KEY, client);
  return client;
}

/**
 * Provide WordPress API client to child components
 * Automatically uses BOOKANDO_VARS
 */
export function provideWordPressClient() {
  const client = createWordPressClient();
  provide(API_CLIENT_KEY, client);
  return client;
}

/**
 * Inject API client in child components
 */
export function useApiClient() {
  const client = inject(API_CLIENT_KEY);
  if (!client) {
    throw new Error('API client not provided. Use provideApiClient() or provideWordPressClient() in a parent component.');
  }
  return client;
}

/**
 * Create a standalone API client (no injection)
 */
export function useStandaloneClient(config: ApiClientConfig) {
  return createBookandoClient(config);
}
