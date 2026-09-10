import { useQuery } from '@tanstack/react-query';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useEffect } from 'react';
import { PublicLayout } from '../../components/marketing/PublicLayout';
import { Breadcrumb, CTASection, EmptyState, ErrorState, HeroSection, LoadingSkeleton, MarketsSection, PhilosophyStatsSection, ProcessSteps, RichText, SectionHeader, ServiceCard } from '../../components/marketing/Primitives';
import type { PageSection } from '../content/cmsService';
import { investmentService } from '../investments/investmentService';
import { insightService } from '../insights/insightService';
import { publicService, usePublicPage } from './publicService';
import { Metadata } from './Metadata';
import aboutImage from '../../assets/about1.png';
import bannerImage from '../../assets/banner-img.png';
import tradeImage from '../../assets/trade.png';

const string = (value: unknown) => typeof value === 'string' ? value : '';
const riskLabel = (risk: string) => risk.replace('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
function cards(value: unknown) {
  return Array.isArray(value) ? value.filter((item): item is Record<string, unknown> => !!item && typeof item === 'object').map((item) => ({ title: string(item.title ?? item.heading), body: string(item.body), href: string(item.href) })) : [];
}
function FeaturedOpportunities() {
  const query = useQuery({ queryKey: ['investments', 'home'], queryFn: ({ signal }) => investmentService.publicList({ featured: '1', per_page: 9 }, signal) });
  return query.isPending ? <LoadingSkeleton /> : query.isError ? <ErrorState retry={() => void query.refetch()} /> : query.data.data.length ? <div className="investment-carousel" aria-label="Featured investment opportunities"><div className="investment-carousel-track">{[...query.data.data, ...query.data.data].map((item, index) => <article className="investment-carousel-card" key={`${item.uuid}-${index}`}><div className="flex items-center justify-between gap-4 text-xs font-semibold uppercase text-slate-400"><span>{item.category_name}</span><span>{riskLabel(item.risk_classification)} risk</span></div><h3 className="mt-4 text-lg font-semibold leading-snug text-white">{item.title}</h3><p className="mt-3 line-clamp-2 text-sm text-slate-300">{item.short_description}</p><Link className="mt-5 inline-block text-sm font-semibold text-emerald-200" to={`/investments/${item.slug}`}>View details <span aria-hidden="true">-&gt;</span></Link></article>)}</div></div> : <EmptyState>No featured opportunities are currently available.</EmptyState>;
}

function FeaturedInsights() {
  const query = useQuery({ queryKey: ['insights', 'home'], queryFn: ({ signal }) => insightService.publicList({ featured: '1', per_page: 3, sort: '-published_at' }, signal) });
  return query.isPending ? <LoadingSkeleton /> : query.isError ? <ErrorState retry={() => void query.refetch()} /> : query.data.data.length ? <div className="grid gap-6 md:grid-cols-3">{query.data.data.map((item) => <article className="rounded-lg border border-white/10 bg-white/[0.04] p-7" key={item.uuid}><div className="mb-4 flex flex-wrap justify-between gap-3 text-xs font-semibold uppercase text-slate-400"><span>{item.category_name}</span>{item.published_at && <span>{new Date(item.published_at).toLocaleDateString()}</span>}</div><h3 className="text-xl font-semibold text-white"><Link to={`/insights/${item.slug}`}>{item.title}</Link></h3><p className="mt-3 text-sm leading-6 text-slate-300">{item.excerpt}</p><Link className="mt-5 inline-block font-semibold text-emerald-200" to={`/insights/${item.slug}`}>Read insight <span aria-hidden="true">-&gt;</span></Link></article>)}</div> : <EmptyState>No featured insights are currently available.</EmptyState>;
}

const homeImages: Record<string, string> = {
  positioning: aboutImage,
  philosophy: bannerImage,
  risk: tradeImage,
};

function VisualBand({ slot, title, body, eyebrow }: { slot: string; title: string; body: string; eyebrow?: string }) {
  const image = homeImages[slot];
  const textColumn = <div>{eyebrow && <p className="eyebrow">{eyebrow}</p>}<h2 className="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">{title}</h2><div className="mt-6 max-w-2xl text-lg text-slate-300"><RichText html={body} /></div></div>;
  const imageColumn = image && <div className="rounded-lg border border-white/10 bg-white/[0.04] p-6"><img src={image} alt="" aria-hidden="true" className="mx-auto max-h-80 object-contain" loading="lazy" decoding="async" /></div>;
  return <section className="section-wrap">
    <div className="grid items-center gap-10 lg:grid-cols-[1.05fr_.95fr]">
      {slot === 'philosophy' ? <>{imageColumn}{textColumn}</> : <>{textColumn}{imageColumn}</>}
    </div>
  </section>;
}

export function CmsSection({ section }: { section: PageSection }) {
  const content = section.content ?? {};
  const title = string(content.heading ?? content.title);
  const body = string(content.body);
  const slot = string(content.slot);
  if (section.type === 'hero') return <HeroSection title={title} body={body} eyebrow={string(content.eyebrow)} />;
  if (section.type === 'cta') return <div className="section-wrap"><CTASection title={title || 'Start a Consultation'} body={body} /></div>;
  if (section.type === 'markets') return <MarketsSection />;
  if (section.type === 'philosophy_stats') return <PhilosophyStatsSection />;
  if (['positioning', 'philosophy', 'risk'].includes(slot)) return <VisualBand slot={slot} title={title} body={body} eyebrow={string(content.eyebrow)} />;
  return <section className="section-wrap">{title && <SectionHeader title={title} eyebrow={string(content.eyebrow)} />}<div className="text-slate-300"><RichText html={body} /></div>
    {['service_grid', 'feature_grid', 'statistics', 'testimonials', 'image_text'].includes(section.type) && <div className="mt-7 grid gap-6 md:grid-cols-3">{cards(content.items).map((item, i) => <ServiceCard key={i} {...item} />)}</div>}
    {section.type === 'process_steps' && <ProcessSteps items={cards(content.items)} />}
    {section.type === 'investment_preview' && <FeaturedOpportunities />}
    {section.type === 'insights_preview' && <FeaturedInsights />}
    {section.type === 'faq_preview' && <Link className="button mt-6" to="/faq">Frequently asked questions</Link>}
  </section>;
}
type HomeSlot = [string, string, string];
const homeSlots: HomeSlot[] = [
  ['hero', 'hero', 'PerrymanFinance'], ['philosophy_stats', 'philosophy_stats', 'Investment philosophy'],
  ['markets', 'markets', 'Markets'], ['positioning', 'rich_text', 'Who we serve'], ['services', 'service_grid', 'Services'], ['philosophy', 'rich_text', 'Investment philosophy'],
  ['opportunities', 'investment_preview', 'Featured opportunities'], ['process', 'process_steps', 'How it works'], ['credibility', 'feature_grid', 'Why PerrymanFinance'], ['risk', 'rich_text', 'Risk management'], ['insights', 'insights_preview', 'Featured insights'], ['cta', 'cta', 'Start a Consultation'],
];

function fallbackHomeSection([slot, type, heading]: HomeSlot): PageSection {
  const defaults: Record<string, Record<string, unknown>> = {
    hero: { heading: 'Financial services for modern wealth and securities clients', eyebrow: 'PerrymanFinance', body: '<p>PerrymanFinance presents financial services, securities-related capabilities, investment products, wealth and portfolio management, digital asset strategy, account-service pathways, and market insight for clients who need a disciplined operating platform for financial decisions.</p>' },
    positioning: { heading: 'A financial services partner for informed clients', eyebrow: 'Who we serve', body: '<p>PerrymanFinance helps private investors, families, founders, and professional allocators review investment themes with clearer context across wealth management, portfolio considerations, digital asset exposure, and market insight.</p><p>The platform connects public content with consultation, onboarding intake, product review, client-service expectations, reporting concepts, and account-management pathways while avoiding unapproved custody, execution, payment, or guaranteed-return claims.</p>' },
    services: { heading: 'Services designed around advice, products, accounts, and discipline', eyebrow: 'Services', items: [
      { title: 'Investment Solutions', body: 'Investment-product and opportunity workflows for clients comparing objectives, time horizon, liquidity needs, securities exposure, risk classification, suitability inputs, and portfolio role.', href: '/investment-solutions' },
      { title: 'Digital Asset Management', body: 'Research-led digital asset management covering exposure design, market structure, custody-model review, operational controls, counterparty oversight, and governance requirements.', href: '/digital-assets' },
      { title: 'Wealth Management', body: 'Wealth and portfolio management support for diversification, liquidity management, family and business-owner priorities, account reporting, and long-term financial decisions.', href: '/wealth-management' },
    ] },
    philosophy: { heading: 'Built around judgement, governance, and restraint', eyebrow: 'Investment philosophy', body: '<p>The PerrymanFinance approach begins with objectives, constraints, suitability, liquidity, documentation, and risk. Opportunity entries are presented as controlled product records for review, suitability discussion, risk disclosure, and client-service follow-up, not as guarantees or pressure-based calls to act.</p>' },
    opportunities: { heading: 'Featured investment opportunities', eyebrow: 'Catalogue', body: '<p>Review published opportunity themes with stated objectives, horizons, risk classifications, and disclaimers.</p>' },
    process: { heading: 'How the service conversation works', eyebrow: 'Process', items: [
      { title: 'Explore services', body: 'Review investment, wealth-management, digital asset, market-insight, account-service, and reporting information.' },
      { title: 'Review risks', body: 'Consider whether a topic fits your objectives, liquidity needs, and tolerance for loss.' },
      { title: 'Request consultation', body: 'Submit an enquiry, consultation request, or onboarding-intake request so the team can route the next step through approved procedures.' },
      { title: 'Proceed through review', body: 'Account opening, suitability review, documentation, reporting setup, and any transaction-related process require approved controlled procedures beyond public content.' },
    ] },
    credibility: { heading: 'Why clients consider PerrymanFinance', eyebrow: 'Why PerrymanFinance', items: [
      { title: 'Integrated market perspective', body: 'Traditional financial markets and digital asset themes are discussed together for clearer allocation context.', href: '/insights' },
      { title: 'Risk-first communication', body: 'Service and opportunity content avoids guaranteed returns, fabricated performance, and artificial urgency.', href: '/risk-disclosure' },
      { title: 'Controlled client pathway', body: 'Public calls to action route users toward consultation, onboarding intake, product review, and client-service follow-up while preserving clear boundaries around custody, execution, payment, and accounting capabilities.', href: '/faq' },
    ] },
    risk: { heading: 'Risk management is part of every discussion', eyebrow: 'Risk management', body: '<p>Investments can lose value, and digital assets may experience significant volatility, liquidity constraints, technology failures, cyber incidents, regulatory change, tax complexity, and third-party risk.</p>' },
    insights: { heading: 'Financial insights for better decisions', eyebrow: 'Insights', body: '<p>Read educational commentary on global markets, digital assets, liquidity, portfolio construction, governance, and risk.</p>' },
    cta: { heading: 'Start a Consultation from PerrymanFinance', body: '<p>Start with a focused enquiry about investment services, securities products, wealth-management priorities, digital asset strategy, client account needs, reporting expectations, or a published opportunity.</p>' },
  };
  return { type, content: { slot, heading, ...(defaults[slot] ?? {}) } };
}
export function MarketingPage({ slug, title }: { slug: string; title: string }) {
  const query = usePublicPage(slug);
  const page = query.data?.data;
  const sections = page?.sections ?? [];
  const home = slug === 'home';
  return <PublicLayout><Metadata title={page?.title ?? title} seo={page?.seo} unavailable={!page} /><main id="content" tabIndex={-1}>
    {query.isPending ? <div className="section-wrap"><LoadingSkeleton /></div> : query.isError ? <div className="section-wrap"><h1 className="text-4xl font-semibold">{title}</h1>{query.error instanceof Error && 'status' in query.error && query.error.status === 404 ? <EmptyState>This page has not been published yet.</EmptyState> : <ErrorState retry={() => void query.refetch()} />}</div> : home ? homeSlots.map(([slot, type, heading], i) => {
      const section = sections.find((item) => item.content?.slot === slot) ?? (type === 'hero' ? sections.find((item) => item.type === 'hero') : sections[i]);
      return <CmsSection key={slot} section={section?.type === type ? section : fallbackHomeSection([slot, type, heading])} />;
    }) : <><div className="section-wrap pb-0"><Breadcrumb title={page?.title ?? title} />{!sections.some((section) => section.type === 'hero') && <><h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">{page?.title ?? title}</h1><p className="mt-5 max-w-3xl text-lg text-slate-600">{page?.excerpt}</p></>}</div>{sections.length ? sections.map((section, i) => <CmsSection section={section} key={i} />) : <div className="section-wrap"><EmptyState /></div>}</>}
  </main></PublicLayout>;
}
export function LegalPage({ slug, title }: { slug: string; title: string }) {
  const query = useQuery({ queryKey: ['legal', slug], queryFn: ({ signal }) => publicService.legal(slug, signal) });
  const document = query.data?.data;
  return <PublicLayout><Metadata title={document?.title ?? title} seo={document?.seo} unavailable={!document} /><main id="content" tabIndex={-1} className="section-wrap max-w-4xl"><Breadcrumb title={title} /><h1 className="text-4xl font-semibold">{document?.title ?? title}</h1>{query.isPending ? <LoadingSkeleton /> : query.isError ? <ErrorState retry={() => void query.refetch()} /> : document ? <><p className="my-6 text-sm text-slate-600">Version {document.version}{document.effective_at ? ` · Effective ${document.effective_at.slice(0, 10)}` : ''}</p><RichText html={document.content} /></> : <EmptyState />}</main></PublicLayout>;
}
export function NotFoundPage() {
  const location = useLocation();
  const navigate = useNavigate();
  const redirect = useQuery({ queryKey: ['redirect', location.pathname], queryFn: ({ signal }) => publicService.redirect(location.pathname, signal), retry: false });
  useEffect(() => {
    if (redirect.data?.data.destination_path) {
      navigate(redirect.data.data.destination_path, { replace: true });
    }
  }, [navigate, redirect.data]);

  return <PublicLayout><Metadata title="Page not found" unavailable /><main id="content" tabIndex={-1} className="section-wrap py-28"><p className="eyebrow">404</p><h1 className="mt-4 text-4xl font-semibold">Page not found</h1><p className="mt-5 text-slate-600">The page may have moved or is no longer available.</p><Link className="button mt-8" to="/">Return home</Link></main></PublicLayout>;
}
