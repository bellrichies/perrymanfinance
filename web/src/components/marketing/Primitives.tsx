import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import heroImage from '../../assets/a.jpg';

export function LoadingSkeleton() {
  return <div role="status" aria-live="polite" className="space-y-5 py-12"><span className="sr-only">Loading content</span><div className="skeleton h-10 w-2/3" /><div className="skeleton h-5 w-full" /><div className="skeleton h-5 w-4/5" /></div>;
}

export function ErrorState({ retry }: { retry?: () => void }) {
  return <div role="alert" className="my-8 rounded-lg border border-rose-200 bg-rose-50 p-6 text-slate-900"><h2 className="text-xl font-semibold">Unable to load this content</h2><p className="mt-2">Please try again in a moment.</p>{retry && <button className="button mt-4" onClick={retry}>Try again</button>}</div>;
}

export function EmptyState({ children = 'There is no published content available yet.' }: { children?: ReactNode }) {
  return <p className="my-8 rounded-lg border border-slate-200 p-6 text-slate-600">{children}</p>;
}

export function Breadcrumb({ title }: { title: string }) {
  return <nav aria-label="Breadcrumb" className="mb-8 text-sm text-slate-600"><ol className="flex flex-wrap gap-3"><li><Link to="/">Home</Link></li><li aria-hidden="true">/</li><li aria-current="page">{title}</li></ol></nav>;
}

export function RichText({ html }: { html?: string | null }) {
  // Only pass allowlist-sanitized content returned by the public CMS API.
  return html ? <div className="rich-text" dangerouslySetInnerHTML={{ __html: html }} /> : null;
}

export function SectionHeader({ title, eyebrow }: { title: string; eyebrow?: string }) {
  return <div className="mb-8 max-w-3xl">{eyebrow && <p className="eyebrow">{eyebrow}</p>}<h2 className="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">{title}</h2></div>;
}

export function HeroSection({ title, body, eyebrow }: { title: string; body?: string | null; eyebrow?: string }) {
  return <section className="hero relative overflow-hidden bg-[#07192f] text-white"><img src={heroImage} alt="" aria-hidden="true" className="absolute inset-0 h-full w-full object-cover opacity-30" loading="eager" /><div className="absolute inset-0 bg-[#07192f]/80" /><div className="relative z-10 mx-auto max-w-6xl px-5 py-20 sm:py-28"><div className="max-w-3xl">{eyebrow && <p className="eyebrow text-emerald-200">{eyebrow}</p>}<h1 className="mt-4 text-4xl font-semibold leading-tight tracking-tight sm:text-6xl">{title}</h1><div className="mt-7 max-w-2xl text-lg leading-relaxed text-slate-300"><RichText html={body} /></div><div className="mt-9 flex flex-wrap gap-4"><Link className="button button-light" to="/contact">Request Information</Link><Link className="inline-flex items-center px-3 py-3 font-medium" to="/investment-solutions">Explore solutions <span aria-hidden="true" className="ml-3">-&gt;</span></Link></div></div></div></section>;
}

function safePublicPath(value: unknown): string | null {
  return typeof value === 'string' && /^\/(?!\/)[a-z0-9/?=&%#_-]*$/i.test(value) && !value.startsWith('/admin') ? value : null;
}

export function ServiceCard({ title, body, href }: { title: string; body?: string; href?: string }) {
  const path = safePublicPath(href);
  return <article className="rounded-xl border border-slate-200 bg-white p-7"><div className="mb-7 h-1 w-10 bg-emerald-600" /><h3 className="text-xl font-semibold">{title}</h3><div className="mt-4 text-slate-600"><RichText html={body} /></div>{path && <Link className="mt-6 inline-block font-semibold text-blue-800" to={path}>Learn More <span aria-hidden="true">-&gt;</span></Link>}</article>;
}

export function ProcessSteps({ items }: { items: { title: string; body?: string }[] }) {
  return <ol className="grid gap-8 md:grid-cols-3">{items.map((item, index) => <li key={index} className="border-t border-slate-300 pt-6"><span className="font-mono text-sm text-emerald-700">{String(index + 1).padStart(2, '0')}</span><h3 className="mt-5 text-xl font-semibold">{item.title}</h3><div className="mt-3 text-slate-600"><RichText html={item.body} /></div></li>)}</ol>;
}

export function CTASection({ title = 'Request information', body }: { title?: string; body?: string }) {
  return <section className="rounded-2xl bg-[#07192f] p-8 text-white sm:p-12"><h2 className="text-3xl font-semibold">{title}</h2><div className="mt-4 max-w-2xl text-slate-300"><RichText html={body} /></div><Link className="button button-light mt-7" to="/contact">Request Information</Link></section>;
}

export function RiskNotice({ children }: { children?: ReactNode }) {
  return <aside aria-label="Risk notice" className="my-8 border-l-2 border-emerald-600 bg-slate-100 p-6"><h2 className="font-semibold">Risk information</h2>{children && <div className="mt-3 text-sm leading-relaxed">{children}</div>}<Link className="mt-3 inline-block text-sm font-semibold underline" to="/risk-disclosure">Read the Risk Disclosure</Link></aside>;
}

export function FAQAccordion({ question, answer }: { question: string; answer: string }) {
  return <details className="border-b border-slate-200 py-5"><summary className="cursor-pointer text-lg font-medium">{question}</summary><div className="mt-4 text-slate-600"><RichText html={answer} /></div></details>;
}
