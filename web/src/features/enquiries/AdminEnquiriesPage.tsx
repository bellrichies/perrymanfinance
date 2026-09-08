import { useRef, useState, type FormEvent } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../auth/authContext';
import { enquiryService, transitions, type Enquiry } from './enquiryService';

const label = (value: string) => value.replaceAll('_', ' ');
const control = 'block w-full rounded border border-slate-500 bg-slate-900 p-2';

export function AdminEnquiriesPage() {
  const { can } = useAuth();
  const [params, setParams] = useSearchParams();
  const search = params.get('search') ?? '';
  const status = params.get('status') ?? '';
  const page = Math.max(1, Number(params.get('page')) || 1);
  const uuid = params.get('enquiry');
  const list = useQuery({ queryKey: ['enquiries', search, status, page], queryFn: ({ signal }) => enquiryService.list(search, status, page, signal), enabled: can('enquiries.view') });
  const detail = useQuery({ queryKey: ['enquiry', uuid], queryFn: ({ signal }) => enquiryService.detail(uuid!, signal), enabled: !!uuid && can('enquiries.view') });
  function filter(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setParams({ search: String(data.get('search') ?? ''), status: String(data.get('status') ?? '') });
  }
  if (!can('enquiries.view')) return <main><h1>Enquiries</h1><p role="alert">You do not have permission to view enquiries.</p></main>;
  return <main className="min-w-0 space-y-6"><nav aria-label="Breadcrumb"><Link to="/admin">Dashboard</Link> / Enquiries</nav><h1 className="text-3xl font-semibold">Enquiries</h1>
    <form onSubmit={filter} className="flex flex-wrap items-end gap-3"><label>Search name, email or subject<input name="search" className={control} defaultValue={search} maxLength={255} /></label><label>Status<select name="status" className={control} defaultValue={status}><option value="">All statuses</option>{Object.keys(transitions).map(s => <option key={s} value={s}>{label(s)}</option>)}</select></label><button className="rounded border p-2">Search</button></form>
    {list.isPending && <p role="status">Loading enquiries…</p>}{list.isError && <p role="alert">Unable to load enquiries. <button onClick={() => void list.refetch()}>Retry</button></p>}
    {list.isSuccess && <>{list.data.data.length === 0 ? <p>No enquiries match your filters.</p> : <div className="overflow-x-auto"><table className="w-full text-left"><caption className="sr-only">Enquiries matching the current filters</caption><thead><tr>{['Name', 'Subject', 'Type', 'Status', 'Received'].map(h => <th scope="col" className="p-3" key={h}>{h}</th>)}</tr></thead><tbody>{list.data.data.map(item => <tr className="border-t border-slate-700" key={item.uuid}><td className="p-3">{item.name}</td><td className="p-3"><button className="underline" onClick={() => { const next = new URLSearchParams(params); next.set('enquiry', item.uuid); setParams(next); }}>{item.subject}</button></td><td className="p-3">{item.enquiry_type}</td><td className="p-3">{label(item.status)}</td><td className="p-3">{item.created_at} UTC</td></tr>)}</tbody></table></div>}<nav aria-label="Enquiry pagination" className="flex gap-4"><button disabled={page <= 1} onClick={() => { const next = new URLSearchParams(params); next.set('page', String(page - 1)); setParams(next); }}>Previous</button><span>Page {page} of {Math.max(1, list.data.meta.total_pages)}</span><button disabled={page >= list.data.meta.total_pages} onClick={() => { const next = new URLSearchParams(params); next.set('page', String(page + 1)); setParams(next); }}>Next</button></nav></>}
    {uuid && <section aria-label="Enquiry details" className="rounded border border-slate-600 p-5">{detail.isPending && <p role="status">Loading enquiry…</p>}{detail.isError && <p role="alert">Unable to load this enquiry. <button onClick={() => void detail.refetch()}>Retry</button></p>}{detail.data && <EnquiryDetail key={`${uuid}-${detail.data.data.status}`} enquiry={detail.data.data} editable={can('enquiries.update')} />}</section>}
  </main>;
}

function EnquiryDetail({ enquiry, editable }: { enquiry: Enquiry; editable: boolean }) {
  const client = useQueryClient();
  const [status, setStatus] = useState('');
  const busy = useRef(false);
  const mutation = useMutation({ mutationFn: () => enquiryService.update(enquiry.uuid, status), onSuccess: async () => { await client.invalidateQueries({ queryKey: ['enquiries'] }); await client.invalidateQueries({ queryKey: ['enquiry', enquiry.uuid] }); } });
  async function submit(event: FormEvent) { event.preventDefault(); if (busy.current || !status) return; busy.current = true; try { await mutation.mutateAsync(); } catch { /* Display mutation error below. */ } finally { busy.current = false; } }
  return <><h2 className="text-xl font-semibold">{enquiry.subject}</h2><dl className="my-4 grid gap-2">{Object.entries({ Name: enquiry.name, Email: enquiry.email, Phone: enquiry.phone || 'Not provided', Type: enquiry.enquiry_type, Status: label(enquiry.status), Source: enquiry.source_page || 'Not provided', 'Consent recorded (UTC)': enquiry.consent_at }).map(([key, value]) => <div key={key}><dt className="font-semibold">{key}</dt><dd className="break-words">{value}</dd></div>)}</dl><h3 className="font-semibold">Message</h3><p className="my-3 whitespace-pre-wrap break-words">{enquiry.message}</p>{editable && <form onSubmit={event => void submit(event)} className="space-y-3" aria-busy={mutation.isPending}><label>Update status<select className={control} value={status} onChange={event => setStatus(event.target.value)} required><option value="">Choose a status</option>{transitions[enquiry.status].map(s => <option value={s} key={s}>{label(s)}</option>)}</select></label><button className="rounded border p-2" disabled={mutation.isPending || !status}>{mutation.isPending ? 'Saving…' : 'Save status'}</button>{mutation.isError && <p role="alert">Status could not be saved. Reload the enquiry and try again.</p>}</form>}<p role="status">Current status: {label(enquiry.status)}</p></>;
}
