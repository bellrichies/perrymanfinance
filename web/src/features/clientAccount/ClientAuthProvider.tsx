import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { configureApiAuth, requestJson } from '../../services/apiClient';
import { ClientAuthContext, type ClientAuthContextValue, type ClientUser } from './clientAuthContext';

type AuthResponse = { data: { user: ClientUser; access_token: string } };

export function ClientAuthProvider({ children }: { children: ReactNode }) {
  const token = useRef<string | null>(null);
  const [user, setUser] = useState<ClientUser | null>(null);
  const [status, setStatus] = useState<ClientAuthContextValue['status']>('loading');
  const expire = useCallback(() => { token.current = null; setUser(null); setStatus('anonymous'); }, []);
  const refresh = useCallback(async () => {
    try {
      const response = await requestJson<AuthResponse>('/client/auth/refresh', { method: 'POST' }, false);
      token.current = response.data.access_token; setUser(response.data.user); setStatus('authenticated'); return response.data.access_token;
    } catch { expire(); return null; }
  }, [expire]);
  useEffect(() => {
    configureApiAuth({ getAccessToken: () => token.current, refresh, expired: expire }, 'client');
    queueMicrotask(() => { void refresh(); });
    return () => configureApiAuth(null, 'client');
  }, [expire, refresh]);
  const login = useCallback(async (email: string, password: string) => {
    const response = await requestJson<AuthResponse>('/client/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) }, false);
    token.current = response.data.access_token; setUser(response.data.user); setStatus('authenticated');
  }, []);
  const logout = useCallback(async () => { try { await requestJson('/client/auth/logout', { method: 'POST' }, false); } finally { expire(); } }, [expire]);
  const value = useMemo(() => ({ user, status, login, logout }), [login, logout, status, user]);
  return <ClientAuthContext.Provider value={value}>{children}</ClientAuthContext.Provider>;
}
