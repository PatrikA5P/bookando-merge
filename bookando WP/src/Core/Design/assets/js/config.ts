// src/Core/Design/assets/js/config.ts
import http from '@assets/http';

// Wird z. B. in Portalen beim Start aufgerufen:
export function bootstrapFromGlobals() {
  if (typeof window === 'undefined') return;

  const { BOOKANDO_CLOUD_API, BOOKANDO_TENANT, BOOKANDO_TOKEN } = window;

  if (BOOKANDO_CLOUD_API) http.setApiBase(BOOKANDO_CLOUD_API);
  if (BOOKANDO_TENANT)   http.setTenant(BOOKANDO_TENANT);
  if (BOOKANDO_TOKEN)    http.setToken(BOOKANDO_TOKEN);
}
