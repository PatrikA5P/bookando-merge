// src/Core/Design/assets/js/auth.ts
import http from '@assets/http';

export async function login(email: string, password: string) {
  const { data } = await http.post('auth/login', { email, password }, { absolute: false });
  if (data?.token)  http.setToken(data.token);
  if (data?.tenant) http.setTenant(data.tenant);
  return data;
}
export function logout() { http.clearToken(); }
