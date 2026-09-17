import { createContext, useContext } from 'react';

export type ClientUser = {
  uuid: string;
  email: string;
  status: string;
  email_verified_at: string | null;
  profile: { first_name: string | null; last_name: string | null };
};

export type ClientAuthContextValue = {
  user: ClientUser | null;
  status: 'loading' | 'anonymous' | 'authenticated';
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
};

export const ClientAuthContext = createContext<ClientAuthContextValue | null>(null);

export function useClientAuth() {
  const value = useContext(ClientAuthContext);
  if (!value) throw new Error('useClientAuth must be used inside ClientAuthProvider');
  return value;
}

export function useOptionalClientAuth() {
  return useContext(ClientAuthContext);
}
