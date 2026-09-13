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

function ClientJourney({ section }: { section: PageSection }) {
  const content = section.content ?? {};
  const title = string(content.heading ?? content.title) || 'A clear path from exploration to the right conversation';
  const body = string(content.body);
  const eyebrow = string(content.eyebrow) || 'Your client journey';
  const stages = cards(content.items);
  const checkpoints = cards(content.checkpoints);
  const preparation = cards(content.preparation);
  return <>
    <section className="journey-hero" aria-labelledby="journey-title">
      <div className="journey-hero__image"><img src={tradeImage} alt="" aria-hidden="true" loading="eager" decoding="async" /></div>
      <div className="journey-hero__overlay" />
      <div className="journey-hero__content">
        <Breadcrumb title="How It Works" />
        <p className="eyebrow">{eyebrow}</p>
        <h1 id="journey-title">{title}</h1>
        <div className="journey-hero__body"><RichText html={body} /></div>
        <div className="mt-8 flex flex-wrap gap-4"><a className="button button-light" href="#journey">See the journey</a><Link className="button bg-white/10 text-white hover:bg-white/15" to="/contact?type=consultation">Request consultation</Link></div>
      </div>
    </section>
    <nav className="journey-nav" aria-label="Journey stages"><div>{stages.map((stage, index) => <a href={`#stage-${index + 1}`} key={stage.title}><span>{String(index + 1).padStart(2, '0')}</span>{stage.title}</a>)}</div></nav>
    <section id="journey" className="section-wrap journey-section" aria-labelledby="journey-stages-title">
      <div className="journey-intro"><div><p className="eyebrow">What to expect</p><h2 id="journey-stages-title">An informed process, at your pace</h2></div><p>Each stage is designed to make the next conversation more useful. The appropriate path depends on the service, the information available, and any approved requirements that apply.</p></div>
      <ol className="journey-stages">{stages.map((stage, index) => <li id={`stage-${index + 1}`} key={stage.title}><div className="journey-stage__number" aria-hidden="true">{String(index + 1).padStart(2, '0')}</div><div className="journey-stage__content"><p className="journey-stage__label">Stage {index + 1}</p><h3>{stage.title}</h3><div><RichText html={stage.body} /></div>{stage.href && <Link to={stage.href} className="journey-stage__link">Explore related information <span aria-hidden="true">-&gt;</span></Link>}</div></li>)}</ol>
    </section>
    <section className="journey-checkpoints" aria-labelledby="journey-checkpoints-title"><div className="section-wrap"><div className="journey-checkpoints__header"><p className="eyebrow">Decision checkpoints</p><h2 id="journey-checkpoints-title">Questions worth resolving before a next step</h2></div><div className="journey-checkpoints__grid">{checkpoints.map((checkpoint) => <article key={checkpoint.title}><h3>{checkpoint.title}</h3><div><RichText html={checkpoint.body} /></div>{checkpoint.href && <Link to={checkpoint.href}>Learn more <span aria-hidden="true">-&gt;</span></Link>}</article>)}</div></div></section>
    <section className="section-wrap journey-preparation" aria-labelledby="journey-preparation-title"><div><p className="eyebrow">Before you enquire</p><h2 id="journey-preparation-title">A little context helps us start well</h2><p>Share only the information needed to describe your question. Please do not send account credentials, private keys, payment instructions, or other unnecessary sensitive information through the public form.</p></div><ul>{preparation.map((item) => <li key={item.title}><h3>{item.title}</h3><div><RichText html={item.body} /></div></li>)}</ul></section>
    <section className="section-wrap pt-0"><aside className="journey-boundary" aria-labelledby="journey-boundary-title"><div><p className="eyebrow">Important boundary</p><h2 id="journey-boundary-title">A website visit is the beginning of a conversation</h2></div><p>Submitting an enquiry does not open an account, create an investment, move funds or assets, provide custody, or execute a transaction. Where a process requires formal approval, documentation, suitability assessment, compliance controls, or other specialist review, it proceeds only through the applicable approved process.</p><Link to="/risk-disclosure">Read the Risk Disclosure <span aria-hidden="true">-&gt;</span></Link></aside></section>
  </>;
}

function ServiceExperience({ section }: { section: PageSection }) {
  const content = section.content ?? {};
  const title = string(content.heading ?? content.title);
  const body = string(content.body);
  const eyebrow = string(content.eyebrow);
  const variant = string(content.variant);
  const themes = cards(content.items);
  const principles = cards(content.principles);
  const questions = cards(content.questions);
  const image = variant === 'digital-assets' ? tradeImage : bannerImage;
  const titleId = `${variant || 'service'}-experience-title`;
  return <>
    <section className={`service-hero service-hero--${variant}`} aria-labelledby={titleId}>
      <div className="service-hero__media"><img src={image} alt="" aria-hidden="true" loading="eager" decoding="async" /></div><div className="service-hero__overlay" />
      <div className="service-hero__content"><Breadcrumb title={title || 'Service'} /><p className="eyebrow">{eyebrow}</p><h1 id={titleId}>{title}</h1><div className="service-hero__body"><RichText html={body} /></div><div className="mt-8 flex flex-wrap gap-4"><a className="button button-light" href="#service-focus">Explore the approach</a><Link className="button bg-white/10 text-white hover:bg-white/15" to="/contact?type=consultation">Request consultation</Link></div></div>
    </section>
    <section id="service-focus" className="section-wrap service-focus" aria-labelledby={`${titleId}-focus`}><div className="service-focus__intro"><p className="eyebrow">The conversation</p><h2 id={`${titleId}-focus`}>Start with the factors that shape the decision</h2></div><div className="service-focus__grid">{themes.map((theme, index) => <article key={theme.title}><span aria-hidden="true">{String(index + 1).padStart(2, '0')}</span><h3>{theme.title}</h3><div><RichText html={theme.body} /></div>{theme.href && <Link to={theme.href}>Explore related information <span aria-hidden="true">-&gt;</span></Link>}</article>)}</div></section>
    <section className="service-principles" aria-labelledby={`${titleId}-principles`}><div className="section-wrap"><div className="service-principles__intro"><p className="eyebrow">A disciplined perspective</p><h2 id={`${titleId}-principles`}>What informed review can involve</h2></div><div className="service-principles__grid">{principles.map((principle) => <article key={principle.title}><h3>{principle.title}</h3><div><RichText html={principle.body} /></div></article>)}</div></div></section>
    <section className="section-wrap service-questions" aria-labelledby={`${titleId}-questions`}><div><p className="eyebrow">Prepare for a consultation</p><h2 id={`${titleId}-questions`}>Useful questions to bring to the conversation</h2><p>These prompts can help you organise a discussion. They do not replace suitability, legal, tax, or other professional advice where it is required.</p></div><ol>{questions.map((question, index) => <li key={question.title}><span aria-hidden="true">{String(index + 1).padStart(2, '0')}</span><div><h3>{question.title}</h3><RichText html={question.body} /></div></li>)}</ol></section>
    <section className="section-wrap pt-0"><aside className="service-boundary" aria-label="Service scope"><div><p className="eyebrow">Service scope</p><h2>Explore first. Proceed through the appropriate process.</h2></div><p>These pages provide educational service information and a route to enquiry. They do not provide a personal recommendation, guarantee an outcome, open an account, receive funds, create custody arrangements, or execute transactions.</p><Link to="/risk-disclosure">Read the Risk Disclosure <span aria-hidden="true">-&gt;</span></Link></aside></section>
  </>;
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
  if (section.type === 'client_journey') return <ClientJourney section={section} />;
  if (section.type === 'service_experience') return <ServiceExperience section={section} />;
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
  ['opportunities', 'investment_preview', 'Featured opportunities'], ['risk', 'rich_text', 'Risk management'], ['cta', 'cta', 'Start a Consultation'],
];

function fallbackHomeSection([slot, type, heading]: HomeSlot): PageSection {
  const defaults: Record<string, Record<string, unknown>> = {
    hero: { heading: 'Clearer investment decisions begin with disciplined advice', eyebrow: 'PerrymanFinance', body: '<p>PerrymanFinance brings investment solutions, wealth and portfolio management, securities-market perspective, digital asset research, and client-service support into one considered financial-services experience. Explore the services, review opportunity and risk information, and request a consultation when you are ready to discuss your priorities.</p>' },
    positioning: { heading: 'A considered approach to modern wealth', eyebrow: 'Who we serve', body: '<p>PerrymanFinance supports private investors, families, founders, and professional allocators who need a clearer way to assess financial markets and investment themes. Our public platform explains how investment solutions, wealth planning, portfolio context, digital assets, and risk management can inform a more deliberate conversation.</p><p>Each route is designed to help clients understand the questions, information, and review process that may be relevant before engaging with a service or opportunity.</p>' },
    services: { heading: 'Financial services built around your objectives', eyebrow: 'Our services', items: [
      { title: 'Investment Solutions', body: 'Explore investment themes and published opportunities through objectives, time horizon, liquidity needs, risk classification, and their potential role within a broader portfolio.', href: '/investment-solutions' },
      { title: 'Digital Asset Management', body: 'Assess digital asset exposure with attention to market structure, volatility, operational resilience, governance, counterparties, and the place of emerging assets in a wider allocation.', href: '/digital-assets' },
      { title: 'Wealth Management', body: 'Frame long-term wealth priorities around diversification, liquidity, family or business interests, portfolio review, client reporting needs, and informed decision-making.', href: '/wealth-management' },
    ] },
    philosophy: { heading: 'Investment decisions deserve more than a market view', eyebrow: 'Our approach', body: '<p>Our approach starts with the client context: objectives, existing exposures, liquidity needs, time horizon, governance, and tolerance for loss. We then consider the market, operational, and documentation questions that can affect an investment decision.</p><p>Published opportunities are intended to support informed review. They describe a theme, its risk classification, and the information needed for a meaningful discussion; they are not a promise of outcome or a substitute for suitability assessment.</p>' },
    opportunities: { heading: 'Explore investment opportunities with context', eyebrow: 'Investment catalogue', body: '<p>Review published opportunities with their stated objective, time horizon, risk classification, and disclosure information before deciding whether to request a discussion.</p>' },
    risk: { heading: 'Risk management belongs at the start of the conversation', eyebrow: 'Risk management', body: '<p>Every investment involves risk, including the possible loss of capital. Securities and digital assets can be affected by market volatility, liquidity constraints, economic conditions, counterparty exposure, technology failures, cyber incidents, tax considerations, and regulatory change.</p><p>Risk classification and disclosure are starting points, not a complete assessment. Review the relevant information and seek appropriate professional advice before making a financial decision.</p>' },
    cta: { heading: 'Talk to PerrymanFinance about your priorities', body: '<p>Request a consultation about investment solutions, wealth-management priorities, a digital asset strategy, client-service needs, or a published opportunity. We will use your enquiry to direct you to the appropriate next conversation.</p>' },
  };
  return { type, content: { slot, heading, ...(defaults[slot] ?? {}) } };
}
export function MarketingPage({ slug, title }: { slug: string; title: string }) {
  const query = usePublicPage(slug);
  const page = query.data?.data;
  const sections = page?.sections ?? [];
  const home = slug === 'home';
  const hasJourney = sections.some((section) => section.type === 'client_journey');
  const hasServiceExperience = sections.some((section) => section.type === 'service_experience');
  return <PublicLayout><Metadata title={page?.title ?? title} seo={page?.seo} unavailable={!page} /><main id="content" tabIndex={-1}>
    {query.isPending ? <div className="section-wrap"><LoadingSkeleton /></div> : query.isError ? <div className="section-wrap"><h1 className="text-4xl font-semibold">{title}</h1>{query.error instanceof Error && 'status' in query.error && query.error.status === 404 ? <EmptyState>This page has not been published yet.</EmptyState> : <ErrorState retry={() => void query.refetch()} />}</div> : home ? homeSlots.map(([slot, type, heading], i) => {
      const section = sections.find((item) => item.content?.slot === slot) ?? (type === 'hero' ? sections.find((item) => item.type === 'hero') : sections[i]);
      return <CmsSection key={slot} section={section?.type === type ? section : fallbackHomeSection([slot, type, heading])} />;
    }) : <>{!hasJourney && !hasServiceExperience && <div className="section-wrap pb-0"><Breadcrumb title={page?.title ?? title} />{!sections.some((section) => section.type === 'hero') && <><h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">{page?.title ?? title}</h1><p className="mt-5 max-w-3xl text-lg text-slate-600">{page?.excerpt}</p></>}</div>}{sections.length ? sections.map((section, i) => <CmsSection section={section} key={i} />) : <div className="section-wrap"><EmptyState /></div>}</>}
  </main></PublicLayout>;
}
export function LegalPage({ slug, title }: { slug: string; title: string }) {
  const query = useQuery({ queryKey: ['legal', slug], queryFn: ({ signal }) => publicService.legal(slug, signal) });
  const document = query.data?.data;
  const legalLinks = [['terms', 'Terms'], ['privacy-policy', 'Privacy'], ['risk-disclosure', 'Risk disclosure'], ['cookie-policy', 'Cookies']] as const;
  return <PublicLayout><Metadata title={document?.title ?? title} seo={document?.seo} unavailable={!document} /><main id="content" tabIndex={-1}>
    <section className="legal-hero"><div className="legal-shell"><Breadcrumb title={document?.title ?? title} /><p className="eyebrow">Legal and trust</p><h1>{document?.title ?? title}</h1><p>This document is maintained through the PerrymanFinance CMS so its published version, effective date, and supporting metadata can be reviewed in one place.</p>{document && <div className="legal-hero__meta"><span>Version {document.version}</span>{document.effective_at && <span>Effective {document.effective_at.slice(0, 10)}</span>}<span>Published document</span></div>}</div></section>
    <nav className="legal-nav" aria-label="Legal documents"><div className="legal-shell">{legalLinks.map(([path, label]) => <Link key={path} to={`/${path}`} aria-current={path === slug ? 'page' : undefined}>{label}</Link>)}</div></nav>
    <section className="legal-shell legal-layout">{query.isPending ? <LoadingSkeleton /> : query.isError ? <ErrorState retry={() => void query.refetch()} /> : document ? <><article className="legal-document"><div className="legal-document__notice"><strong>Important</strong><p>Read this document with the related legal and risk information. It does not replace the formal documents or professional advice that may be required for a particular decision or service.</p></div><RichText html={document.content} /></article><aside className="legal-aside" aria-label="Related legal information"><p className="eyebrow">Related information</p><h2>Make decisions with the full context.</h2><p>Investment and digital asset topics can involve material risk. Public content supports research and enquiry; it does not create an account, custody arrangement, payment, or transaction.</p><Link to="/risk-disclosure">Read the Risk Disclosure <span aria-hidden="true">-&gt;</span></Link><Link to="/contact?type=consultation">Request consultation <span aria-hidden="true">-&gt;</span></Link></aside></> : <EmptyState />}</section>
  </main></PublicLayout>;
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
