import { useState, type FormEvent, type ReactNode } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ApiError, requestJson } from '../../services/apiClient';

function AuthCard({ children }: { children: ReactNode }) {
  return <main className="grid min-h-screen place-items-center px-5 py-12"><section className="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-950 p-7 shadow-2xl">{children}</section></main>;
}

export function ForgotPasswordPage() {
  const [email, setEmail] = useState(''); const [message, setMessage] = useState(''); const [busy, setBusy] = useState(false);
  async function submit(event: FormEvent) { event.preventDefault(); setBusy(true); try { await requestJson('/admin/auth/forgot-password', { method: 'POST', body: JSON.stringify({ email }) }, false); setMessage('If the account is eligible, reset instructions will be sent.'); } catch (error) { setMessage(error instanceof ApiError ? error.message : 'The request is unavailable.'); } finally { setBusy(false); } }
  return <AuthCard><h1 className="text-3xl font-semibold text-white">Reset password</h1><p className="mt-2 text-slate-400">Enter your staff email address.</p>{message && <p className="mt-5 text-emerald-200" role="status">{message}</p>}<form className="mt-7 space-y-5" onSubmit={submit}><label className="block text-sm font-medium">Email address<input className="mt-2 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2.5" type="email" required value={email} onChange={(event) => setEmail(event.target.value)} /></label><button className="w-full rounded-lg bg-emerald-300 px-4 py-3 font-semibold text-slate-950" disabled={busy}>{busy ? 'Sending…' : 'Send reset instructions'}</button></form><Link className="mt-5 inline-block text-emerald-300" to="/admin/login">Back to sign in</Link></AuthCard>;
}

export function ResetPasswordPage() {
  const [params] = useSearchParams(); const [password, setPassword] = useState(''); const [message, setMessage] = useState(''); const [busy, setBusy] = useState(false);
  async function submit(event: FormEvent) { event.preventDefault(); setBusy(true); try { await requestJson('/admin/auth/reset-password', { method: 'POST', body: JSON.stringify({ token: params.get('token') ?? '', password }) }, false); setMessage('Password reset complete. You can now sign in.'); } catch (error) { setMessage(error instanceof ApiError ? error.message : 'The reset request is unavailable.'); } finally { setBusy(false); } }
  return <AuthCard><h1 className="text-3xl font-semibold text-white">Choose a new password</h1>{message && <p className="mt-5 text-emerald-200" role="status">{message}</p>}<form className="mt-7 space-y-5" onSubmit={submit}><label className="block text-sm font-medium">New password<input className="mt-2 w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2.5" type="password" minLength={12} autoComplete="new-password" required value={password} onChange={(event) => setPassword(event.target.value)} /></label><button className="w-full rounded-lg bg-emerald-300 px-4 py-3 font-semibold text-slate-950" disabled={busy}>{busy ? 'Resetting…' : 'Reset password'}</button></form><Link className="mt-5 inline-block text-emerald-300" to="/admin/login">Back to sign in</Link></AuthCard>;
}
