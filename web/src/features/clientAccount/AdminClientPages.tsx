import { FormEvent, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { adminClientService, type AdminClient, type AdminClientDetail, type AdminInvestmentAccount, type AdminInvestmentDetail, type AdminPlanRequest } from './clientService';
import { ApiError } from '../../services/apiClient';

export function AdminClientsPage() {
  const [clients, setClients] = useState<AdminClient[] | null>(null);
  const [requests, setRequests] = useState<AdminPlanRequest[] | null>(null);
  const [reasonByRequest, setReasonByRequest] = useState<Record<string, string>>({});
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const pendingCount = requests?.length ?? 0;
  const activeClients = clients?.filter(client => client.status === 'active').length ?? 0;

  const load = () => {
    void adminClientService.clients().then(setClients).catch(() => setError('Client accounts could not be loaded.'));
    void adminClientService.planRequests().then(setRequests).catch(() => setError('Plan requests could not be loaded.'));
  };

  useEffect(load, []);

  async function review(uuid: string, decision: 'approve' | 'reject') {
    setError(''); setMessage('');
    const reason = reasonByRequest[uuid] ?? '';
    try {
      if (decision === 'approve') await adminClientService.approve(uuid, reason);
      else await adminClientService.reject(uuid, reason);
      setReasonByRequest(previous => ({ ...previous, [uuid]: '' }));
      setMessage(`Request ${decision === 'approve' ? 'approved' : 'rejected'}.`);
      load();
    } catch (err) { setMessage(''); setError(err instanceof ApiError ? err.message : 'Review failed.'); }
  }

  return <main className="space-y-8">
    <section className="overflow-hidden rounded-lg bg-slate-950 text-white shadow-sm">
      <div className="grid gap-6 p-6 lg:grid-cols-[1fr_360px] lg:p-8">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-200">Client operations</p>
          <h1 className="mt-3 text-3xl font-semibold tracking-tight">Client Accounts</h1>
          <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-300">Review plan requests and open client records for admin-approved investment reporting. Reported values are operational records only, not wallets, deposits, payouts, or withdrawable cash.</p>
        </div>
        <dl className="grid grid-cols-2 gap-3">
          <SummaryTile label="Pending requests" value={String(pendingCount)} tone="emerald" />
          <SummaryTile label="Active clients" value={String(activeClients)} />
        </dl>
      </div>
    </section>
    {error && <p className="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
    {message && <p className="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</p>}
    <section className="rounded-lg border border-slate-200 bg-white p-5 text-slate-900 shadow-sm">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 className="text-lg font-semibold">Pending Plan Requests</h2>
          <p className="mt-1 text-sm text-slate-600">Approve or reject requests after recording a staff review reason.</p>
        </div>
        <span className="w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{pendingCount} open</span>
      </div>
      {!requests && <p className="mt-4" aria-busy="true">Loading requests...</p>}
      {requests?.length === 0 && <EmptyPanel title="No pending requests" text="New client plan requests will appear here for review." />}
      <div className="mt-4 space-y-4">
        {requests?.map(request => <article key={request.uuid} className="grid gap-4 rounded-lg border border-slate-200 bg-slate-50 p-4 lg:grid-cols-[1fr_360px]">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <p className="font-semibold">{request.plan_title}</p>
              <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">{riskLabel(request.risk_classification)}</span>
            </div>
            <p className="mt-2 text-sm text-slate-600">{request.first_name} {request.last_name} - {request.email}</p>
            <p className="mt-1 text-sm text-slate-600">Requested: {request.requested_amount ?? 'Not specified'} {request.currency}</p>
            <Link to={`/admin/clients/${request.client_uuid}`} className="mt-3 inline-flex text-sm font-semibold text-emerald-700">Open investment operations</Link>
          </div>
          <div className="grid gap-3">
            <label className="grid gap-1 text-sm"><span>Review reason</span><textarea value={reasonByRequest[request.uuid] ?? ''} onChange={(event) => setReasonByRequest(previous => ({ ...previous, [request.uuid]: event.target.value }))} required className="min-h-24 rounded border border-slate-300 p-2" /></label>
            <div className="flex flex-wrap gap-2"><button type="button" onClick={() => void review(request.uuid, 'approve')} className="button bg-emerald-600 text-white">Approve</button><button type="button" onClick={() => void review(request.uuid, 'reject')} className="button bg-slate-800 text-white">Reject</button></div>
          </div>
        </article>)}
      </div>
    </section>
    <section className="rounded-lg border border-slate-200 bg-white p-5 text-slate-900 shadow-sm">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h2 className="text-lg font-semibold">Client Directory</h2>
          <p className="mt-1 text-sm text-slate-600">Open a client to manage approved investment reporting on a dedicated page.</p>
        </div>
        <span className="text-sm font-medium text-slate-500">{clients?.length ?? 0} total</span>
      </div>
      {!clients && <p className="mt-4" aria-busy="true">Loading clients...</p>}
      {clients?.length === 0 && <EmptyPanel title="No clients yet" text="Client accounts will appear here once registration and verification records exist." />}
      <div className="mt-4 overflow-x-auto">
        <table className="min-w-full text-left text-sm">
          <thead><tr className="border-b text-xs uppercase tracking-wide text-slate-500"><th className="py-3">Name</th><th>Email</th><th>Status</th><th>Created</th><th></th></tr></thead>
          <tbody>{clients?.map(client => <tr key={client.uuid} className="border-b last:border-0"><td className="py-3 font-medium">{client.first_name} {client.last_name}</td><td className="text-slate-600">{client.email}</td><td><StatusBadge value={client.status} /></td><td className="text-slate-600">{new Date(client.created_at).toLocaleDateString()}</td><td className="text-right"><Link to={`/admin/clients/${client.uuid}`} className="font-semibold text-emerald-700">Manage</Link></td></tr>)}</tbody>
        </table>
      </div>
    </section>
  </main>;
}

export function AdminClientInvestmentOperationsPage() {
  const { uuid } = useParams();
  const [selectedClient, setSelectedClient] = useState<AdminClientDetail | null>(null);
  const [selectedInvestment, setSelectedInvestment] = useState<AdminInvestmentDetail | null>(null);
  const [modalAction, setModalAction] = useState<'adjustment' | 'snapshot' | null>(null);
  const [activeAction, setActiveAction] = useState<string | null>(null);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const selectedAccount = selectedInvestment?.account;

  useEffect(() => {
    if (!uuid) return;
    setError(''); setMessage(''); setSelectedInvestment(null);
    void adminClientService.client(uuid).then(setSelectedClient).catch((err) => setError(err instanceof ApiError ? err.message : 'Client detail could not be loaded.'));
  }, [uuid]);

  async function openInvestment(accountUuid: string) {
    setError(''); setMessage('');
    try { setSelectedInvestment(await adminClientService.investment(accountUuid)); } catch (err) { setError(err instanceof ApiError ? err.message : 'Investment detail could not be loaded.'); }
  }

  async function submitAdjustment(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); if (!selectedAccount) return;
    const form = new FormData(event.currentTarget); setError(''); setMessage('');
    setActiveAction(`adjustment:${selectedAccount.uuid}`);
    try {
      await adminClientService.balanceAdjustment(selectedAccount.uuid, {
        adjustment_type: form.get('adjustment_type'),
        amount: form.get('amount'),
        currency: selectedAccount.currency,
        effective_at: form.get('effective_at'),
        source_reference: generatedSourceReference('ADJ', selectedAccount.uuid),
        reason: form.get('reason'),
        idempotency_key: crypto.randomUUID(),
      });
      setMessage('Reported balance adjustment recorded.');
      event.currentTarget.reset();
      await openInvestment(selectedAccount.uuid);
      setModalAction(null);
    } catch (err) { setMessage(''); setError(err instanceof ApiError ? err.message : 'Balance adjustment failed.'); }
    finally { setActiveAction(null); }
  }

  async function submitSnapshot(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); if (!selectedAccount) return;
    const form = new FormData(event.currentTarget); setError(''); setMessage('');
    setActiveAction(`snapshot:${selectedAccount.uuid}`);
    try {
      await adminClientService.reportingSnapshot(selectedAccount.uuid, {
        snapshot_date: form.get('snapshot_date'),
        growth_amount: form.get('growth_amount'),
        currency: selectedAccount.currency,
        methodology_note: form.get('methodology_note'),
        source_reference: generatedSourceReference('SNAP', selectedAccount.uuid),
        idempotency_key: crypto.randomUUID(),
      });
      setMessage('Growth reporting snapshot published.');
      event.currentTarget.reset();
      await openInvestment(selectedAccount.uuid);
      setModalAction(null);
    } catch (err) { setMessage(''); setError(err instanceof ApiError ? err.message : 'Reporting snapshot failed.'); }
    finally { setActiveAction(null); }
  }

  return <main className="space-y-8">
    <Link to="/admin/clients" className="text-sm font-semibold text-emerald-300">Back to clients</Link>
    {error && <p className="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
    {message && <p className="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</p>}
    {!selectedClient && !error && <p aria-busy="true">Loading client operations...</p>}
    {selectedClient && <><section className="rounded-lg bg-white p-6 text-slate-900 shadow-sm">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <p className="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Client Investment Operations</p>
          <h1 className="mt-2 text-3xl font-semibold tracking-tight">{selectedClient.client.first_name} {selectedClient.client.last_name}</h1>
          <p className="mt-2 text-sm text-slate-600">{selectedClient.client.email} - {clientStatus(selectedClient.client.status)}</p>
        </div>
        <div className="grid grid-cols-2 gap-3 sm:min-w-80">
          <SummaryTile label="Approved accounts" value={String(selectedClient.investment_accounts.length)} />
          <SummaryTile label="Plan requests" value={String(selectedClient.plan_requests.length)} tone="emerald" />
        </div>
      </div>
    </section>
    <div className="grid gap-8 xl:grid-cols-[380px_minmax(0,1fr)]">
      <aside>
        <section className="rounded-lg border border-slate-200 bg-white p-5 text-slate-900 shadow-sm">
          <h2 className="font-semibold">Approved Investments</h2>
          {selectedClient.investment_accounts.length === 0 && <EmptyPanel title="No approved investments" text="Approve a plan request before reporting operations are available." />}
          {selectedClient.investment_accounts.map(account => <InvestmentButton key={account.uuid} account={account} selected={selectedAccount?.uuid === account.uuid} onOpen={openInvestment} />)}
        </section>
      </aside>
      <section className="rounded-lg border border-slate-200 bg-white p-5 text-slate-900 shadow-sm">
        {!selectedInvestment && <EmptyPanel title="Select an investment account" text="Choose an approved account to record reporting adjustments or publish a reviewed growth snapshot." />}
        {selectedInvestment && <InvestmentOperationsPanel investment={selectedInvestment} onChooseAction={setModalAction} />}
      </section>
    </div></>}
    {selectedInvestment && modalAction === 'adjustment' && <Modal title="Record Balance or Dividend Adjustment" onClose={() => setModalAction(null)}><AdjustmentForm investment={selectedInvestment} activeAction={activeAction} onAdjustment={submitAdjustment} /></Modal>}
    {selectedInvestment && modalAction === 'snapshot' && <Modal title="Publish Growth Snapshot" onClose={() => setModalAction(null)}><SnapshotForm account={selectedInvestment.account} disabled={activeAction !== null} busy={activeAction === `snapshot:${selectedInvestment.account.uuid}`} onSubmit={submitSnapshot} /></Modal>}
  </main>;
}

function InvestmentButton({ account, selected, onOpen }: { account: AdminInvestmentAccount; selected: boolean; onOpen: (uuid: string) => Promise<void> }) {
  return <button type="button" onClick={() => void onOpen(account.uuid)} className={`mt-3 w-full rounded border p-4 text-left transition ${selected ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-100' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}`}><span className="block font-medium">{account.plan_title}</span><span className="mt-1 block text-sm text-slate-600">Reported balance: {account.current_balance ?? account.approved_amount ?? 'Pending'} {account.currency}</span><span className="mt-2 inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">{riskLabel(account.risk_classification)}</span></button>;
}

function InvestmentOperationsPanel({ investment, onChooseAction }: { investment: AdminInvestmentDetail; onChooseAction: (action: 'adjustment' | 'snapshot') => void }) {
  const account = investment.account;
  return <div className="space-y-6"><div className="rounded-lg bg-slate-50 p-4"><h3 className="font-semibold">{account.plan_title}</h3><dl className="mt-3 grid gap-3 text-sm sm:grid-cols-3"><div><dt className="text-slate-500">Approved amount</dt><dd className="font-semibold">{account.approved_amount ?? 'Not specified'} {account.currency}</dd></div><div><dt className="text-slate-500">Reported balance</dt><dd className="font-semibold">{account.current_balance ?? 'Pending report'} {account.currency}</dd></div><div><dt className="text-slate-500">Status</dt><dd className="font-semibold">{clientStatus(account.status)}</dd></div></dl></div><section className="grid gap-4 sm:grid-cols-2"><button type="button" onClick={() => onChooseAction('adjustment')} className="rounded-lg border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-emerald-400 hover:bg-emerald-50"><span className="block font-semibold text-slate-950">Record Adjustment</span><span className="mt-2 block text-sm leading-6 text-slate-600">Record a reviewed balance, dividend, distribution, valuation, or correction entry.</span></button><button type="button" onClick={() => onChooseAction('snapshot')} className="rounded-lg border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-emerald-400 hover:bg-emerald-50"><span className="block font-semibold text-slate-950">Publish Snapshot</span><span className="mt-2 block text-sm leading-6 text-slate-600">Publish an admin-approved growth snapshot using the recorded principal amount.</span></button></section><div className="grid gap-5 lg:grid-cols-2"><section className="rounded-lg border border-slate-200 p-4"><h4 className="font-semibold">Recent Adjustments</h4><History rows={investment.adjustments.map(row => ({ id: row.uuid, text: `${row.adjustment_type}: ${row.amount} ${row.currency} - ${row.source_reference}` }))} /></section><section className="rounded-lg border border-slate-200 p-4"><h4 className="font-semibold">Published Snapshots</h4><History rows={investment.snapshots.map(row => ({ id: row.uuid, text: `${row.snapshot_date}: ${row.reported_value} ${row.currency} (${row.growth_percent}%)` }))} /></section></div></div>;
}

function AdjustmentForm({ investment, activeAction, onAdjustment }: { investment: AdminInvestmentDetail; activeAction: string | null; onAdjustment: (event: FormEvent<HTMLFormElement>) => Promise<void> }) {
  const account = investment.account;
  const adjustmentBusy = activeAction === `adjustment:${account.uuid}`;
  return <form onSubmit={onAdjustment} className="grid gap-3"><p className="rounded bg-slate-50 p-3 text-sm text-slate-600">{account.plan_title} - {account.currency}</p><label className="grid gap-1 text-sm"><span>Adjustment type</span><select name="adjustment_type" className="rounded border border-slate-300 px-3 py-2" required><option value="increase">Reported dividend / distribution increase</option><option value="decrease">Decrease</option><option value="valuation_update">Valuation update</option><option value="correction">Correction</option><option value="initial_allocation">Initial allocation</option></select></label><label className="grid gap-1 text-sm"><span>Amount</span><input name="amount" type="number" step="0.01" min="0.01" required className="rounded border border-slate-300 px-3 py-2" /></label><label className="grid gap-1 text-sm"><span>Effective date</span><input name="effective_at" type="date" required className="rounded border border-slate-300 px-3 py-2" /></label><label className="grid gap-1 text-sm"><span>Reason</span><textarea name="reason" required className="min-h-28 rounded border border-slate-300 p-2" /></label><button disabled={adjustmentBusy} className="button bg-slate-900 text-white">{adjustmentBusy ? 'Recording...' : 'Record Adjustment'}</button><p className="text-xs text-slate-500">Dividend/distribution entries update reporting only. A source reference is generated automatically for the audit record.</p></form>;
}

function SnapshotForm({ account, disabled, busy, onSubmit }: { account: AdminInvestmentAccount; disabled: boolean; busy: boolean; onSubmit: (event: FormEvent<HTMLFormElement>) => Promise<void> }) {
  const [growthAmount, setGrowthAmount] = useState('');
  const principal = parseMoney(account.approved_amount);
  const growth = parseMoney(growthAmount);
  const canPreview = principal !== null && growth !== null;
  const reportedValue = canPreview ? principal + growth : null;
  const growthPercent = canPreview ? (growth / principal) * 100 : null;

  return <form onSubmit={onSubmit} onReset={() => setGrowthAmount('')} className="grid gap-3 rounded border border-slate-200 p-4"><h4 className="font-semibold">Publish Growth Snapshot</h4><dl className="grid gap-3 rounded bg-slate-50 p-3 text-sm sm:grid-cols-3"><div><dt className="text-slate-500">Principal amount</dt><dd className="font-semibold text-slate-900">{principal !== null ? `${money(principal)} ${account.currency}` : 'Not recorded'}</dd></div><div><dt className="text-slate-500">Reported value</dt><dd className="font-semibold text-slate-900">{reportedValue !== null ? `${money(reportedValue)} ${account.currency}` : 'Calculated after growth entry'}</dd></div><div><dt className="text-slate-500">Growth percent</dt><dd className="font-semibold text-slate-900">{growthPercent !== null ? `${growthPercent.toFixed(4)}%` : 'Calculated after growth entry'}</dd></div></dl><div className="grid gap-3 sm:grid-cols-2"><label className="grid gap-1 text-sm"><span>Snapshot date</span><input name="snapshot_date" type="date" required className="rounded border border-slate-300 px-3 py-2" /></label><label className="grid gap-1 text-sm"><span>Growth amount</span><input name="growth_amount" type="number" step="0.01" required value={growthAmount} onChange={(event) => setGrowthAmount(event.target.value)} className="rounded border border-slate-300 px-3 py-2" /></label></div><label className="grid gap-1 text-sm"><span>Methodology note</span><textarea name="methodology_note" required className="rounded border border-slate-300 p-2" /></label><button disabled={disabled || principal === null} className="button bg-emerald-600 text-white">{busy ? 'Publishing...' : 'Publish Snapshot'}</button><p className="text-xs text-slate-500">A source reference is generated automatically when the snapshot is published.</p></form>;
}

function Modal({ title, children, onClose }: { title: string; children: React.ReactNode; onClose: () => void }) {
  return <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="client-operation-modal-title">
    <section className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-5 text-slate-900 shadow-2xl">
      <div className="mb-4 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
        <h2 id="client-operation-modal-title" className="text-lg font-semibold">{title}</h2>
        <button type="button" onClick={onClose} className="rounded px-3 py-1 text-sm font-semibold text-slate-600 hover:bg-slate-100">Close</button>
      </div>
      {children}
    </section>
  </div>;
}

function History({ rows }: { rows: { id: string; text: string }[] }) {
  return rows.length ? <ul className="mt-3 space-y-2 text-sm text-slate-600">{rows.map(row => <li key={row.id} className="rounded bg-slate-50 px-3 py-2">{row.text}</li>)}</ul> : <p className="mt-3 text-sm text-slate-600">No records yet.</p>;
}

function SummaryTile({ label, value, tone = 'slate' }: { label: string; value: string; tone?: 'slate' | 'emerald' }) {
  const classes = tone === 'emerald' ? 'bg-emerald-400 text-slate-950' : 'bg-slate-900 text-white';
  return <div className={`rounded-lg p-4 ${classes}`}><dt className="text-xs font-semibold uppercase tracking-wide opacity-75">{label}</dt><dd className="mt-2 text-2xl font-semibold">{value}</dd></div>;
}

function EmptyPanel({ title, text }: { title: string; text: string }) {
  return <div className="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4"><p className="font-semibold text-slate-900">{title}</p><p className="mt-1 text-sm text-slate-600">{text}</p></div>;
}

function StatusBadge({ value }: { value: string }) {
  const active = value === 'active';
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>{clientStatus(value)}</span>;
}

function parseMoney(value: string | number | null | undefined): number | null {
  if (value === null || value === undefined || value === '') return null;
  const parsed = Number(value);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

function money(value: number) {
  return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function generatedSourceReference(prefix: 'ADJ' | 'SNAP', accountUuid: string) {
  const compactTimestamp = new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14);
  const accountFragment = accountUuid.replaceAll('-', '').slice(0, 8).toUpperCase();
  const randomFragment = crypto.randomUUID().replaceAll('-', '').slice(0, 6).toUpperCase();
  return `OPS-${prefix}-${compactTimestamp}-${accountFragment}-${randomFragment}`;
}

function riskLabel(value: string) {
  return value.replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
}

function clientStatus(value: string) {
  return value.replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase());
}
