/**
 * @bookando/api-client
 * Unified API client for Bookando platform
 */

export * from './client';
export * from './endpoints';

import { BookandoApiClient, type ApiClientConfig } from './client';
import { createCustomersEndpoints } from './endpoints/customers';
import { createEmployeesEndpoints } from './endpoints/employees';
import { createAppointmentsEndpoints } from './endpoints/appointments';

/**
 * Factory function to create a fully-featured Bookando API client
 * with all endpoint groups
 */
export function createBookandoClient(config: ApiClientConfig) {
  const client = new BookandoApiClient(config);

  return {
    // Raw client for custom requests
    client,

    // Type-safe endpoint groups
    customers: createCustomersEndpoints(client),
    employees: createEmployeesEndpoints(client),
    appointments: createAppointmentsEndpoints(client),

    // Utility methods
    setTenantId: (tenantId: number) => client.setTenantId(tenantId),
    setAuth: (auth: ApiClientConfig['auth']) => client.setAuth(auth),
  };
}

/**
 * WordPress-specific factory function
 * Automatically uses BOOKANDO_VARS from window
 */
export function createWordPressClient() {
  if (typeof window === 'undefined' || !window.BOOKANDO_VARS) {
    throw new Error('BOOKANDO_VARS not available. Make sure you are in a WordPress environment.');
  }

  const vars = window.BOOKANDO_VARS;

  return createBookandoClient({
    baseUrl: vars.rest_url || '/wp-json/bookando/v1',
    auth: {
      nonce: vars.nonce,
    },
    tenantId: vars.tenant_id,
  });
}

// Type augmentation for global window
declare global {
  interface Window {
    BOOKANDO_VARS?: {
      rest_url?: string;
      nonce?: string;
      tenant_id?: number;
      [key: string]: unknown;
    };
  }
}
