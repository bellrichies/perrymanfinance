import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { configureApiAuth, requestJson } from '../../services/apiClient';
import type { AdminUser, AuthResponse } from './authTypes';
import { AuthContext, type AuthContextValue } from './authContext';

export function AuthProvider({ children }: { children: ReactNode }) {
  const token = useRef<string | null>(null);
  const [user, setUser] = useState<AdminUser | null>(null);
  const [status, setStatus] = useState<AuthContextValue['status']>('loading');
  const [sessionExpired, setSessionExpired] = useState(false);
  const expire = useCallback(() => { token.current = null; setUser(null); setSessionExpired(true); setStatus('anonymous'); }, []);
  const refresh = useCallback(async () => {
    try {
      const response = await requestJson<AuthResponse>('/admin/auth/refresh', { method: 'POST' }, false);
      token.current = response.data.access_token; setUser(response.data.user); setStatus('authenticated'); return response.data.access_token;
    } catch { token.current = null; setUser(null); setStatus('anonymous'); return null; }
  }, []);
  useEffect(() => {
    configureApiAuth({ getAccessToken: () => token.current, refresh, expired: expire });
    queueMicrotask(() => { void refresh(); });
    return () => configureApiAuth(null);
  }, [expire, refresh]);
  const login = useCallback(async (email: string, password: string) => {
    const response = await requestJson<AuthResponse>('/admin/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) }, false);
    token.current = response.data.access_token; setUser(response.data.user); setSessionExpired(false); setStatus('authenticated');
  }, []);
  const logout = useCallback(async () => { try { await requestJson('/admin/auth/logout', { method: 'POST' }, false); } finally { expire(); setSessionExpired(false); } }, [expire]);
  const value = useMemo<AuthContextValue>(() => ({ user, status, sessionExpired, login, logout, can: (permission) => user?.permissions.includes(permission) === true || user?.permissions.includes(`${permission.split('.')[0]}.*`) === true }), [login, logout, sessionExpired, status, user]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
