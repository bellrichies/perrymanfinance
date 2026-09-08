import { useQuery } from '@tanstack/react-query';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useEffect } from 'react';
import { PublicLayout } from '../../components/marketing/PublicLayout';
import { Breadcrumb, CTASection, EmptyState, ErrorState, HeroSection, LoadingSkeleton, ProcessSteps, RichText, SectionHeader, ServiceCard } from '../../components/marketing/Primitives';
import type { PageSection } from '../content/cmsService';
import { investmentService } from '../investments/investmentService';
import { InvestmentCard } from '../investments/InvestmentPages';
import { insightService } from '../insights/insightService';
import { ArticleCard as InsightCard } from '../insights/InsightPages';
import { publicService, usePublicPage } from './publicService';
import { Metadata } from './Metadata';

const string = (value: unknown) => typeof value === 'string' ? value : '';
function cards(value: unknown) {
  return Array.isArray(value) ? value.filter((item): item is Record<string, unknown> => !!item && typeof item === 'object').map((item) => ({ title: string(item.title ?? item.heading), body: string(item.body), href: string(item.href) })) : [];
}
function FeaturedOpportunities() {
  const query = useQuery({ queryKey: ['investments', 'home'], queryFn: ({ signal }) => investmentService.publicList({ featured: '1', per_page: 3 }, signal) });
  return query.isPending ? <LoadingSkeleton /> : query.isError ? <ErrorState retry={() => void query.refetch()} /> : query.data.data.length ? <div className="grid gap-6 md:grid-cols-3">{query.data.data.map((item) => <InvestmentCard item={item} key={item.uuid} />)}</div> : <EmptyState>No featured opportunities are currently available.</EmptyState>;
}
function FeaturedInsights() {
  const query = useQuery({ queryKey: ['insights', 'home'], queryFn: ({ signal }) => insightService.publicList({ featured: '1', per_page: 3 }, signal) });
  return query.isPending ? <LoadingSkeleton /> : query.isError ? <ErrorState retry={() => void query.refetch()} /> : query.data.data.length ? <div className="grid gap-6 md:grid-cols-3">{query.data.data.map((item) => <InsightCard item={item} key={item.uuid} />)}</div> : <EmptyState>No featured insights are currently available.</EmptyState>;
}
export function CmsSection({ section }: { section: PageSection }) {
  const content = section.content ?? {};
  const title = string(content.heading ?? content.title);
  const body = string(content.body);
  if (section.type === 'hero') return <HeroSection title={title} body={body} eyebrow={string(content.eyebrow)} />;
  if (section.type === 'cta') return <div className="section-wrap"><CTASection title={title || undefined} body={body} /></div>;
  return <section className="section-wrap">{title && <SectionHeader title={title} eyebrow={string(content.eyebrow)} />}<RichText html={body} />
    {['service_grid', 'feature_grid', 'statistics', 'testimonials', 'image_text'].includes(section.type) && <div className="mt-7 grid gap-6 md:grid-cols-3">{cards(content.items).map((item, i) => <ServiceCard key={i} {...item} />)}</div>}
    {section.type === 'process_steps' && <ProcessSteps items={cards(content.items)} />}
    {section.type === 'investment_preview' && <FeaturedOpportunities />}
    {section.type === 'insights_preview' && <FeaturedInsights />}
    {section.type === 'faq_preview' && <Link className="button mt-6" to="/faq">Frequently asked questions</Link>}
  </section>;
}
const homeSlots: [string, string, string][] = [
  ['hero', 'hero', 'PerrymanFinance'], ['positioning', 'rich_text', 'Our perspective'],
  ['services', 'service_grid', 'Investment solutions'], ['philosophy', 'rich_text', 'Investment philosophy'],
  ['opportunities', 'investment_preview', 'Featured opportunities'], ['process', 'process_steps', 'How it works'],
  ['risk', 'rich_text', 'Risk management'], ['insights', 'insights_preview', 'Featured insights'], ['cta', 'cta', 'Request information'],
];
export function MarketingPage({ slug, title }: { slug: string; title: string }) {
  const query = usePublicPage(slug);
  const page = query.data?.data;
  const sections = page?.sections ?? [];
  const home = slug === 'home';
  return <PublicLayout><Metadata title={page?.title ?? title} seo={page?.seo} unavailable={!page} /><main id="content" tabIndex={-1}>
    {query.isPending ? <div className="section-wrap"><LoadingSkeleton /></div> : query.isError ? <div className="section-wrap"><h1 className="text-4xl font-semibold">{title}</h1>{query.error instanceof Error && 'status' in query.error && query.error.status === 404 ? <EmptyState>This page has not been published yet.</EmptyState> : <ErrorState retry={() => void query.refetch()} />}</div> : home ? homeSlots.map(([slot, type, heading], i) => {
      const section = sections.find((item) => item.content?.slot === slot) ?? sections[i];
      return <CmsSection key={slot} section={section?.type === type ? section : { type, content: { heading } }} />;
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
