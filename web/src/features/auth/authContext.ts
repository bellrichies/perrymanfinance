import { createContext, useContext } from 'react';
import type { AdminUser } from './authTypes';

export type AuthContextValue = { user: AdminUser | null; status: 'loading' | 'authenticated' | 'anonymous'; sessionExpired: boolean; login: (email: string, password: string) => Promise<void>; logout: () => Promise<void>; can: (permission: string) => boolean };
export const AuthContext = createContext<AuthContextValue | null>(null);
export function useAuth() { const context = useContext(AuthContext); if (!context) throw new Error('useAuth must be used inside AuthProvider.'); return context; }
