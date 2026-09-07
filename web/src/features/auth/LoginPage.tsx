import { useState, type FormEvent } from 'react';
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { ApiError } from '../../services/apiClient';
import { useAuth } from './authContext';

export function LoginPage() {
  const auth = useAuth(); const navigate = useNavigate(); const location = useLocation();
  const [email, setEmail] = useState(''); const [password, setPassword] = useState(''); const [error, setError] = useState(''); const [submitting, setSubmitting] = useState(false);
  if (auth.status === 'authenticated') return <Navigate to="/admin" replace />;
  async function submit(event: FormEvent) {
    event.preventDefault(); setError(''); setSubmitting(true);
    try { await auth.login(email, password); navigate((location.state as { from?: { pathname?: string } } | null)?.from?.pathname ?? '/admin', { replace: true }); }
    catch (reason) { setError(reason instanceof ApiError ? reason.message : 'Sign in is unavailable. Please try again.'); }
    finally { setSubmitting(false); }
  }
  return <main className="grid min-h-screen place-items-center px-5 py-12"><section className="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-950 p-7 shadow-2xl">
    <p className="text-sm font-semibold uppercase tracking-[.2em] text-emerald-300">PerrymanFinance</p><h1 className="mt-3 text-3xl font-semibold text-white">Admin sign in</h1><p className="mt-2 text-slate-400">Use your authorized staff account.</p>
    {auth.sessionExpired && <p className="mt-5 rounded-lg bg-amber-950 p-3 text-amber-200" role="status">Your session expired. Please sign in again.</p>}{error && <p className="mt-5 rounded-lg bg-rose-950 p-3 text-rose-200" role="alert">{error}</p>}
    <form className="mt-7 space-y-5" onSubmit={submit}><label className="block text-sm font-medium text-slate-200">Email address<input className="mt-2 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2.5 text-white" type="email" autoComplete="username" required value={email} onChange={(event) => setEmail(event.target.value)} /></label><label className="block text-sm font-medium text-slate-200">Password<input className="mt-2 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2.5 text-white" type="password" autoComplete="current-password" required value={password} onChange={(event) => setPassword(event.target.value)} /></label><button className="w-full rounded-lg bg-emerald-300 px-4 py-3 font-semibold text-slate-950 disabled:opacity-60" disabled={submitting} type="submit">{submitting ? 'Signing in…' : 'Sign in'}</button></form><Link className="mt-5 inline-block text-emerald-300" to="/admin/forgot-password">Forgot password?</Link>
  </section></main>;
}
