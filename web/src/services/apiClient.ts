const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api/v1';
type ErrorEnvelope = { error?: { message?: string; fields?: Record<string, string[]> } };
type AuthHooks = { getAccessToken: () => string | null; refresh: () => Promise<string | null>; expired: () => void };
let authHooks: AuthHooks | null = null;
let refreshPromise: Promise<string | null> | null = null;

export class ApiError extends Error {
  public constructor(public readonly status: number, message: string, public readonly fields: Record<string, string[]> = {}) {
    super(message); this.name = 'ApiError';
  }
}

export function configureApiAuth(hooks: AuthHooks | null) { authHooks = hooks; }

export async function requestJson<T>(path: string, init: RequestInit = {}, retryAuth = true): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set('Accept', 'application/json');
  if (init.body && !(init.body instanceof FormData)) headers.set('Content-Type', 'application/json');
  const token = authHooks?.getAccessToken();
  if (token) headers.set('Authorization', `Bearer ${token}`);
  const response = await fetch(`${API_BASE_URL}${path}`, { ...init, headers, credentials: 'include' });
  if (response.status === 401 && retryAuth && authHooks) {
    refreshPromise ??= authHooks.refresh().finally(() => { refreshPromise = null; });
    const refreshed = await refreshPromise;
    if (refreshed) return requestJson<T>(path, init, false);
    authHooks.expired();
  }
  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as ErrorEnvelope;
    throw new ApiError(response.status, body.error?.message ?? 'The service request failed.', body.error?.fields);
  }
  return response.json() as Promise<T>;
}

export function getJson<T>(path: string, signal?: AbortSignal): Promise<T> { return requestJson<T>(path, { signal }); }
