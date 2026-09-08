import { useRef, useState, type FormEvent } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router-dom';
import { PublicLayout } from '../../components/marketing/PublicLayout';
import { Breadcrumb, LoadingSkeleton, RichText } from '../../components/marketing/Primitives';
import { ApiError } from '../../services/apiClient';
import { publicService, usePublicPage, usePublicSettings } from './publicService';
import { Metadata } from './Metadata';

export function ContactPage() {
  const [params] = useSearchParams();
  const page = usePublicPage('contact');
  const settings = usePublicSettings();
  const privacy = useQuery({ queryKey: ['legal', 'privacy-policy'], queryFn: ({ signal }) => publicService.legal('privacy-policy', signal) });
  const consent = settings.data?.data.enquiry_consent;
  const enabled = typeof consent === 'string' && consent.trim() !== '' && privacy.isSuccess;
  const mutation = useMutation({ mutationFn: publicService.enquire });
  const submitting = useRef(false);
  const feedback = useRef<HTMLDivElement>(null);
  const [localErrors, setLocalErrors] = useState<Record<string, string[]>>({});
  const fields = { ...(mutation.error instanceof ApiError ? mutation.error.fields : {}), ...localErrors };
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (submitting.current || !enabled) return;
    const errors: Record<string, string[]> = {};
    for (const element of Array.from(event.currentTarget.elements)) {
      if ((element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement) && element.willValidate) {
        if (!element.validity.valid) errors[element.name] = [element.validationMessage];
        else if (element.required && element.type !== 'checkbox' && !element.value.trim()) errors[element.name] = ['This field is required.'];
      }
    }
    setLocalErrors(errors);
    if (Object.keys(errors).length) { requestAnimationFrame(() => feedback.current?.focus()); return; }
    submitting.current = true;
    const data = Object.fromEntries(new FormData(event.currentTarget));
    try { await mutation.mutateAsync({ ...data, consent: data.consent === 'on', source_page: `/contact${params.size ? `?${params}` : ''}` }); } catch { /* Mutation state presents safe API feedback. */ }
    finally { submitting.current = false; requestAnimationFrame(() => feedback.current?.focus()); }
  }
  return <PublicLayout><Metadata title="Contact" seo={page.data?.data.seo} /><main id="content" tabIndex={-1} className="section-wrap"><Breadcrumb title="Contact" /><div className="grid gap-12 lg:grid-cols-2"><section><p className="eyebrow">Contact</p><h1 className="mt-4 text-4xl font-semibold sm:text-5xl">{page.data?.data.title ?? 'Request information'}</h1><div className="mt-6 text-lg text-slate-600"><RichText html={page.data?.data.excerpt} /></div>{typeof settings.data?.data.contact_details === 'string' && <div className="mt-10"><RichText html={settings.data.data.contact_details} /></div>}</section><section className="rounded-xl border border-slate-200 bg-white p-6 sm:p-8" aria-label="Contact form">
    <div ref={feedback} tabIndex={-1}>{Object.keys(fields).length > 0 && <div role="alert" className="mb-5 text-rose-800"><p>Please correct the following fields:</p><ul>{Object.entries(fields).map(([name, errors]) => <li key={name}><a href={`#${name}`}>{name.replaceAll('_', ' ')}: {errors.join(' ')}</a></li>)}</ul></div>}{mutation.isSuccess && <p role="status" className="rounded bg-emerald-50 p-5 text-emerald-900">Thank you. Your enquiry has been received.</p>}{mutation.isError && <p role="alert" className="mb-5 text-rose-800">{mutation.error instanceof ApiError && mutation.error.status === 429 ? 'Too many requests. Please try again later.' : 'Your enquiry could not be submitted. Review the form and try again.'}</p>}</div>
    {!mutation.isSuccess && <form noValidate onSubmit={(event) => void submit(event)} className="space-y-5" aria-busy={mutation.isPending}>
      {([['name', 'Name', 'text', 160], ['email', 'Email address', 'email', 254], ['phone', 'Phone (optional)', 'tel', 40], ['subject', 'Subject', 'text', 255]] as const).map(([name, label, type, max]) => <label className="block" key={name}>{label}<input className="form-control" id={name} name={name} type={type} required={name !== 'phone'} maxLength={max} autoComplete={name === 'subject' ? 'off' : name === 'phone' ? 'tel' : name} defaultValue={name === 'subject' && params.get('investment') ? `Information about ${params.get('investment')}` : undefined} aria-invalid={Boolean(fields[name])} aria-describedby={fields[name] ? `${name}-error` : undefined} />{fields[name] && <span id={`${name}-error`} className="text-sm text-rose-800">{fields[name].join(' ')}</span>}</label>)}
      <label className="block">Reason for enquiry<select className="form-control" defaultValue={params.get("type") === "consultation" ? "consultation" : params.has("investment") ? "investment" : "general"} id="enquiry_type" name="enquiry_type" aria-invalid={Boolean(fields.enquiry_type)} aria-describedby={fields.enquiry_type ? "enquiry_type-error" : undefined}><option value="general">General enquiry</option><option value="consultation">Consultation request</option><option value="investment">Investment information</option></select>{fields.enquiry_type && <span id="enquiry_type-error" className="text-sm text-rose-800">{fields.enquiry_type.join(" ")}</span>}</label>
      <label className="block">Message<textarea className="form-control min-h-36" id="message" name="message" required maxLength={5000} aria-invalid={Boolean(fields.message)} aria-describedby={fields.message ? 'message-error' : undefined} />{fields.message && <span id="message-error" className="text-sm text-rose-800">{fields.message.join(' ')}</span>}</label>
      <div hidden aria-hidden="true"><label>Website<input name="website" tabIndex={-1} autoComplete="off" /></label></div>
      {settings.isPending || privacy.isPending ? <LoadingSkeleton /> : enabled ? <div><label className="flex items-start gap-3"><input className="mt-1" type="checkbox" id="consent" name="consent" required aria-invalid={Boolean(fields.consent)} aria-describedby={fields.consent ? "privacy-link consent-error" : "privacy-link"} /><span><RichText html={consent} /></span></label><Link id="privacy-link" className="mt-3 inline-block text-sm underline" to="/privacy-policy">Read the Privacy Policy</Link></div> : <p role="status">The enquiry form is temporarily unavailable. Please try again later.</p>}
      {fields.consent && <p id="consent-error" role="alert" className="text-sm text-rose-800">{fields.consent.join(' ')}</p>}
      <button className="button" disabled={mutation.isPending || !enabled}>{mutation.isPending ? 'Sending…' : 'Send enquiry'}</button>
    </form>}
  </section></div></main></PublicLayout>;
}
