import { FormEvent, useEffect, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { ApiError } from '../../services/apiClient';
import { clientService, type ClientDashboard, type ClientPlan, type ClientReportingSnapshot } from './clientService';
import { useClientAuth } from './clientAuthContext';

export function ClientLoginPage() {
  const auth = useClientAuth(); const navigate = useNavigate(); const [error, setError] = useState(''); const [busy, setBusy] = useState(false);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError('');
    const form = new FormData(event.currentTarget);
    try { await auth.login(String(form.get('email')), String(form.get('password'))); navigate('/client'); } catch (err) { setError(err instanceof ApiError ? err.message : 'Unable to sign in.'); } finally { setBusy(false); }
  }
  return <AuthShell title="Client Login"><form onSubmit={submit} className="grid gap-4"><Field name="email" label="Email" type="email" /><Field name="password" label="Password" type="password" />{error && <p className="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}<button disabled={busy} className="button bg-emerald-500 text-slate-950">{busy ? 'Signing in...' : 'Sign in'}</button><div className="flex justify-between text-sm"><Link to="/client/forgot-password">Forgot password?</Link><Link to="/client/register">Create Account</Link></div></form></AuthShell>;
}

export function ClientRegisterPage() {
  const [message, setMessage] = useState(''); const [error, setError] = useState(''); const [busy, setBusy] = useState(false);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError(''); setMessage('');
    const form = new FormData(event.currentTarget);
    try {
      await clientService.register({ ...Object.fromEntries(form.entries()), consent_terms: form.get('consent_terms') === 'on' });
      setMessage('Account created. Please check your email to verify your client account.');
    } catch (err) { setError(err instanceof ApiError ? err.message : 'Unable to create account.'); } finally { setBusy(false); }
  }
  return <AuthShell title="Create Account"><form onSubmit={submit} className="grid gap-4"><div className="grid gap-4 sm:grid-cols-2"><Field name="first_name" label="First name" /><Field name="last_name" label="Last name" /></div><Field name="email" label="Email" type="email" /><Field name="password" label="Password" type="password" /><Field name="password_confirmation" label="Confirm password" type="password" /><label className="flex gap-3 text-sm"><input name="consent_terms" type="checkbox" required /> <span>I acknowledge the client account terms, risk disclosure, and non-guarantee notice.</span></label>{message && <p className="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</p>}{error && <p className="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}<button disabled={busy} className="button bg-emerald-500 text-slate-950">{busy ? 'Creating...' : 'Create Account'}</button><Link className="text-sm" to="/client/login">Already have an account?</Link></form></AuthShell>;
}

export function ClientForgotPasswordPage() {
  const [message, setMessage] = useState(''); const [error, setError] = useState('');
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); const form = new FormData(event.currentTarget); setError(''); setMessage('');
    try { await clientService.forgotPassword(String(form.get('email'))); setMessage('If the account is eligible, password reset instructions will be sent.'); } catch (err) { setError(err instanceof ApiError ? err.message : 'Unable to request reset.'); }
  }
  return <AuthShell title="Reset Password"><form onSubmit={submit} className="grid gap-4"><Field name="email" label="Email" type="email" />{message && <p className="text-sm text-emerald-700">{message}</p>}{error && <p className="text-sm text-red-700">{error}</p>}<button className="button bg-emerald-500 text-slate-950">Send reset instructions</button><Link className="text-sm" to="/client/login">Back to sign in</Link></form></AuthShell>;
}

export function ClientResetPasswordPage() {
  const [params] = useSearchParams(); const [message, setMessage] = useState(''); const [error, setError] = useState('');
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); const form = new FormData(event.currentTarget); setError(''); setMessage('');
    try { await clientService.resetPassword(params.get('token') ?? '', String(form.get('password'))); setMessage('Password reset complete. Please sign in again.'); } catch (err) { setError(err instanceof ApiError ? err.message : 'Unable to reset password.'); }
  }
  return <AuthShell title="Choose New Password"><form onSubmit={submit} className="grid gap-4"><Field name="password" label="New password" type="password" />{message && <p className="text-sm text-emerald-700">{message}</p>}{error && <p className="text-sm text-red-700">{error}</p>}<button className="button bg-emerald-500 text-slate-950">Reset password</button></form></AuthShell>;
}

export function ClientVerifyEmailPage() {
  const [params] = useSearchParams(); const [message, setMessage] = useState('Verifying email...');
  useEffect(() => { clientService.verifyEmail(params.get('token') ?? '').then(() => setMessage('Email verified. You can now sign in.')).catch(() => setMessage('Verification link is invalid or expired.')); }, [params]);
  return <AuthShell title="Email Verification"><p>{message}</p><Link className="mt-5 inline-block text-sm" to="/client/login">Client Login</Link></AuthShell>;
}

export function ClientDashboardPage() {
  const [data, setData] = useState<ClientDashboard | null>(null); const [error, setError] = useState('');
  useEffect(() => { clientService.dashboard().then(setData).catch(() => setError('Dashboard could not be loaded.')); }, []);
  if (error) return <ClientLayout><Alert tone="error" title="Dashboard unavailable">{error}</Alert></ClientLayout>;
  if (!data) return <ClientLayout><DashboardSkeleton /></ClientLayout>;
  const name = [data.client.profile.first_name, data.client.profile.last_name].filter(Boolean).join(' ') || data.client.email;
  const snapshots = data.latest_snapshots?.flatMap(group => group.snapshots.map(snapshot => ({ ...snapshot, plan_title: group.plan_title }))) ?? [];
  const latestRequest = data.plan_requests[0];
  return <ClientLayout>
    <section className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
      <div className="grid gap-6 bg-slate-950 p-5 text-white sm:p-6 lg:grid-cols-[1fr_auto] lg:items-center">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-200">Client portal</p>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl">Welcome, {name}</h1>
          <div className="mt-3 flex flex-wrap gap-2 text-sm text-slate-300">
            <span>{data.client.email}</span>
            <StatusBadge value={clientStatus(data.client.status)} tone="success" />
          </div>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
          <Link to="/client/plans" className="button bg-emerald-400 text-slate-950">Select Plan</Link>
          <Link to="/client/support" className="button border border-white/20 bg-white/10 text-white">Contact Support</Link>
        </div>
      </div>
      <div className="grid gap-3 p-5 sm:p-6 md:grid-cols-2 xl:grid-cols-4">
        <MetricCard label="Reported balance" value={data.summary.reported_balance ? `${money(data.summary.reported_balance)} ${data.summary.currency ?? ''}` : `0.00 ${data.summary.currency ?? ''}`.trim()} helper="Approved reporting value" />
        <MetricCard label="Active investments" value={String(data.summary.active_investments)} helper="Admin-approved accounts" />
        <MetricCard label="Pending requests" value={String(data.summary.pending_requests)} helper="Awaiting review" />
        <MetricCard label="Latest snapshot" value={data.summary.latest_snapshot_date ? dateLabel(data.summary.latest_snapshot_date) : 'None'} helper="Most recent approved report" />
      </div>
    </section>

    <Alert tone="info" title="Reporting note">Reported balances and growth snapshots are approved records for client reporting. They are not cash balances, payment facilities, or forecasts.</Alert>

    <div className="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(20rem,.85fr)]">
      <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <SectionHeader kicker="Portfolio reporting" title="Approved Growth Snapshots" action={snapshots.length ? `${snapshots.length} record${snapshots.length === 1 ? '' : 's'}` : undefined} />
        {snapshots.length ? <SnapshotTable snapshots={snapshots} /> : <EmptyState title="No approved growth snapshots" text="No growth snapshot has been published for your account." />}
      </section>

      <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <SectionHeader kicker="Next action" title="Plan Requests" action={data.plan_requests.length ? `${data.plan_requests.length} submitted` : undefined} />
        <div className="mt-5 grid gap-3">
          {data.plan_requests.length ? data.plan_requests.map(request => <RequestCard key={request.uuid} request={request} />) : <EmptyState title="No plan requests" text="You have not submitted an investment plan request." action={<Link className="button bg-slate-950 text-white" to="/client/plans">Select a plan</Link>} />}
        </div>
      </section>
    </div>

    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <SectionHeader kicker="Account status" title="Approved Investments" action={data.investment_accounts.length ? `${data.investment_accounts.length} active` : undefined} />
      {data.investment_accounts.length ? <div className="mt-5 grid gap-4 lg:grid-cols-2">{data.investment_accounts.map(account => <InvestmentAccountCard key={account.uuid} account={account} />)}</div> : <EmptyState title={latestRequest?.status === 'pending' ? 'Request under review' : 'No approved investments'} text={latestRequest?.status === 'pending' ? 'Your submitted request is still pending staff review.' : 'You do not have an approved investment account yet.'} action={latestRequest ? undefined : <Link className="button bg-slate-950 text-white" to="/client/plans">Select a plan</Link>} />}
    </section>
  </ClientLayout>;
}

export function ClientPlansPage() {
  const [plans, setPlans] = useState<ClientPlan[] | null>(null); const [selected, setSelected] = useState<ClientPlan | null>(null); const [message, setMessage] = useState(''); const [error, setError] = useState('');
  useEffect(() => { clientService.plans().then(setPlans).catch(() => setError('Plans could not be loaded.')); }, []);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); if (!selected) return; const form = new FormData(event.currentTarget); setError(''); setMessage('');
    try { await clientService.submitPlanRequest({ investment_opportunity_uuid: selected.uuid, requested_amount: form.get('requested_amount'), currency: form.get('currency') || 'USD', client_note: form.get('client_note'), risk_acknowledged: form.get('risk_acknowledged') === 'on' }); setMessage('Plan request submitted for review.'); } catch (err) { setError(err instanceof ApiError ? err.message : 'Unable to submit request.'); }
  }
  return <ClientLayout>
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div className="max-w-3xl">
        <p className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Investment plans</p>
        <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Select Plan</h1>
        <p className="mt-3 text-sm leading-6 text-slate-600">Review available plans, choose one that fits your objectives, and submit a request for staff review.</p>
      </div>
    </section>
    {!plans && !error && <DashboardSkeleton compact />}
    {error && <Alert tone="error" title="Plans unavailable">{error}</Alert>}
    <div className="grid gap-4 lg:grid-cols-3">{plans?.map(plan => <button type="button" key={plan.uuid} onClick={() => setSelected(plan)} className={`group rounded-lg border bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-400 hover:shadow-md ${selected?.uuid === plan.uuid ? 'border-emerald-500 ring-2 ring-emerald-100' : 'border-slate-200'}`}><div className="flex items-start justify-between gap-3"><h2 className="text-lg font-semibold text-slate-950">{plan.title}</h2><StatusBadge value={riskLabel(plan.risk_classification)} /></div><p className="mt-3 text-sm leading-6 text-slate-600">{plan.short_description}</p><div className="mt-5 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">{plan.minimum_investment_display && <span className="rounded-full bg-slate-100 px-3 py-1">{plan.minimum_investment_display}</span>}{plan.currency_display && <span className="rounded-full bg-slate-100 px-3 py-1">{plan.currency_display}</span>}</div></button>)}</div>
    {selected && <form onSubmit={submit} className="grid gap-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><SectionHeader kicker="Request details" title={selected.title} /><p className="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">{selected.disclaimer}</p><div className="grid gap-4 sm:grid-cols-2"><Field name="requested_amount" label="Requested amount" type="number" /><Field name="currency" label="Currency" defaultValue="USD" /></div><label className="grid gap-2 text-sm font-medium text-slate-700"><span>Client note</span><textarea name="client_note" className="min-h-28 rounded-md border border-slate-300 bg-white p-3 text-slate-950 shadow-sm transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100" /></label><label className="flex gap-3 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700"><input name="risk_acknowledged" type="checkbox" required className="mt-1 h-5 w-5 rounded border-slate-300" /> <span>I acknowledge the investment risks and that no future return is guaranteed.</span></label>{message && <Alert tone="success" title="Request submitted">{message}</Alert>}<button className="button bg-emerald-500 text-slate-950 sm:w-fit">Submit for Review</button></form>}
  </ClientLayout>;
}

function ClientLayout({ children }: { children: React.ReactNode }) {
  const auth = useClientAuth();
  return <main className="min-h-screen bg-slate-100 text-slate-900"><nav className="sticky top-0 z-30 border-b border-white/10 bg-slate-950 px-4 py-3 text-white shadow-lg shadow-slate-950/10"><div className="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><Link to="/client" className="font-semibold tracking-tight">PerrymanFinance Client</Link><div className="flex items-center gap-1 overflow-x-auto text-sm"><Link to="/client" className="rounded-md px-3 py-2 text-slate-200 hover:bg-white/10 hover:text-white">Dashboard</Link><Link to="/client/plans" className="rounded-md px-3 py-2 text-slate-200 hover:bg-white/10 hover:text-white">Plans</Link><Link to="/client/profile" className="rounded-md px-3 py-2 text-slate-200 hover:bg-white/10 hover:text-white">Profile</Link><button onClick={() => void auth.logout()} className="rounded-md px-3 py-2 font-semibold text-emerald-200 hover:bg-white/10">Logout</button></div></div></nav><div className="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">{children}</div></main>;
}

function AuthShell({ title, children }: { title: string; children: React.ReactNode }) {
  return <main className="grid min-h-screen place-items-center bg-slate-950 px-6 text-slate-900"><section className="w-full max-w-md rounded-lg bg-white p-8 shadow-xl"><h1 className="mb-6 text-2xl font-semibold">{title}</h1>{children}</section></main>;
}

function Field({ name, label, type = 'text', defaultValue }: { name: string; label: string; type?: string; defaultValue?: string }) {
  return <label className="grid gap-2 text-sm font-medium text-slate-700"><span>{label}</span><input name={name} type={type} defaultValue={defaultValue} required={type !== 'number'} className="rounded-md border border-slate-300 bg-white px-3 py-3 text-slate-950 shadow-sm transition focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100" /></label>;
}

function label(status: string) { return status === 'pending' ? 'Pending Review' : status.charAt(0).toUpperCase() + status.slice(1); }

function MetricCard({ label, value, helper }: { label: string; value: string; helper?: string }) {
  return <section className="rounded-lg border border-slate-200 bg-slate-50 p-4"><h2 className="text-sm font-medium text-slate-500">{label}</h2><p className="mt-2 break-words text-2xl font-semibold tracking-tight text-slate-950">{value}</p>{helper && <p className="mt-1 text-xs text-slate-500">{helper}</p>}</section>;
}

function EmptyState({ title, text, action }: { title: string; text: string; action?: React.ReactNode }) {
  return <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5"><p className="font-semibold text-slate-900">{title}</p><p className="mt-1 text-sm leading-6 text-slate-600">{text}</p>{action && <div className="mt-4 text-sm">{action}</div>}</div>;
}

function SectionHeader({ kicker, title, action }: { kicker?: string; title: string; action?: string }) {
  return <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div>{kicker && <p className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">{kicker}</p>}<h2 className="mt-1 text-xl font-semibold tracking-tight text-slate-950">{title}</h2></div>{action && <span className="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{action}</span>}</div>;
}

function StatusBadge({ value, tone = 'neutral' }: { value: string; tone?: 'neutral' | 'success' | 'warning' }) {
  const classes = tone === 'success' ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : tone === 'warning' ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200';
  return <span className={`inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ${classes}`}>{value}</span>;
}

function Alert({ tone, title, children }: { tone: 'error' | 'success' | 'info'; title: string; children: React.ReactNode }) {
  const classes = tone === 'error' ? 'border-red-200 bg-red-50 text-red-900' : tone === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-blue-200 bg-blue-50 text-blue-950';
  return <section className={`rounded-lg border p-4 ${classes}`}><p className="font-semibold">{title}</p><div className="mt-1 text-sm leading-6">{children}</div></section>;
}

function RequestCard({ request }: { request: ClientDashboard['plan_requests'][number] }) {
  const isPending = request.status === 'pending';
  return <article className="rounded-lg border border-slate-200 bg-slate-50 p-4"><div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p className="font-semibold text-slate-950">{request.plan_title}</p><p className="mt-1 text-sm leading-6 text-slate-600">Requested: {request.requested_amount ? `${money(request.requested_amount)} ${request.currency}` : 'Amount not specified'} - {riskLabel(request.risk_classification)}</p></div><StatusBadge value={label(request.status)} tone={isPending ? 'warning' : 'success'} /></div><p className="mt-3 text-xs text-slate-500">Submitted {dateLabel(request.created_at)}{request.reviewed_at ? ` - Reviewed ${dateLabel(request.reviewed_at)}` : ''}</p></article>;
}

function InvestmentAccountCard({ account }: { account: ClientDashboard['investment_accounts'][number] }) {
  return <article className="rounded-lg border border-slate-200 bg-slate-50 p-4"><div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h3 className="font-semibold text-slate-950">{account.plan_title}</h3><p className="mt-1 text-sm text-slate-600">{riskLabel(account.risk_classification)} - {clientStatus(account.status)}</p></div><p className="text-sm font-semibold text-slate-950">{account.current_balance ? `${money(account.current_balance)} ${account.currency}` : 'No reported balance'}</p></div><dl className="mt-4 grid gap-3 text-sm sm:grid-cols-3"><div><dt className="text-slate-500">Approved amount</dt><dd className="mt-1 font-medium text-slate-900">{account.approved_amount ? `${money(account.approved_amount)} ${account.currency}` : 'Not recorded'}</dd></div><div><dt className="text-slate-500">Approved date</dt><dd className="mt-1 font-medium text-slate-900">{dateLabel(account.approved_at)}</dd></div><div><dt className="text-slate-500">Last snapshot</dt><dd className="mt-1 font-medium text-slate-900">{account.last_snapshot_at ? dateLabel(account.last_snapshot_at) : 'None'}</dd></div></dl></article>;
}

function SnapshotTable({ snapshots }: { snapshots: (ClientReportingSnapshot & { plan_title?: string })[] }) {
  return <div className="mt-5 overflow-hidden rounded-lg border border-slate-200"><div className="hidden overflow-x-auto md:block"><table className="min-w-full text-left text-sm"><thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Plan</th><th className="px-4 py-3">Date</th><th className="px-4 py-3">Principal</th><th className="px-4 py-3">Reported value</th><th className="px-4 py-3">Growth</th><th className="px-4 py-3">Growth %</th></tr></thead><tbody className="divide-y divide-slate-200 bg-white">{snapshots.map(snapshot => <tr key={snapshot.uuid}><td className="px-4 py-3 font-medium text-slate-950">{snapshot.plan_title ?? 'Investment account'}</td><td className="px-4 py-3 text-slate-600">{dateLabel(snapshot.snapshot_date)}</td><td className="px-4 py-3 text-slate-600">{money(snapshot.principal_amount)} {snapshot.currency}</td><td className="px-4 py-3 font-semibold text-slate-950">{money(snapshot.reported_value)} {snapshot.currency}</td><td className="px-4 py-3 text-slate-600">{money(snapshot.growth_amount)} {snapshot.currency}</td><td className="px-4 py-3 text-slate-600">{snapshot.growth_percent}%</td></tr>)}</tbody></table></div><div className="grid gap-3 bg-slate-50 p-3 md:hidden">{snapshots.map(snapshot => <article key={snapshot.uuid} className="rounded-md bg-white p-4 shadow-sm"><div className="flex items-start justify-between gap-3"><div><p className="font-semibold text-slate-950">{snapshot.plan_title ?? 'Investment account'}</p><p className="mt-1 text-xs text-slate-500">{dateLabel(snapshot.snapshot_date)}</p></div><StatusBadge value="Snapshot" /></div><dl className="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt className="text-slate-500">Principal</dt><dd className="font-medium text-slate-900">{money(snapshot.principal_amount)} {snapshot.currency}</dd></div><div><dt className="text-slate-500">Reported</dt><dd className="font-medium text-slate-900">{money(snapshot.reported_value)} {snapshot.currency}</dd></div><div><dt className="text-slate-500">Growth</dt><dd className="font-medium text-slate-900">{money(snapshot.growth_amount)} {snapshot.currency}</dd></div><div><dt className="text-slate-500">Growth %</dt><dd className="font-medium text-slate-900">{snapshot.growth_percent}<span aria-hidden="true"> pct</span></dd></div></dl></article>)}</div></div>;
}

function DashboardSkeleton({ compact = false }: { compact?: boolean }) {
  return <section aria-busy="true" className="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><span className="sr-only">Loading dashboard...</span><div className="h-7 w-48 animate-pulse rounded bg-slate-200" /><div className={`grid gap-3 ${compact ? 'sm:grid-cols-3' : 'sm:grid-cols-2 xl:grid-cols-4'}`}>{Array.from({ length: compact ? 3 : 4 }).map((_, index) => <div key={index} className="h-28 animate-pulse rounded-lg bg-slate-100" />)}</div></section>;
}

function money(value: string) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : value;
}

function dateLabel(value: string) {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString();
}

function riskLabel(value: string) {
  return value.replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function clientStatus(value: string) {
  return value.replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
}
