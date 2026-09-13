import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import heroImageWebp from '../../assets/a.webp';
import callImage from '../../assets/call-img.png';
import heroVideo from '../../assets/vid.mp4';
import chartIcon from '../../assets/animatedicons/chart.svg';
import philosophyImage from '../../assets/banner-img.png';

export function LoadingSkeleton() {
  return <div role="status" aria-live="polite" className="space-y-5 py-12"><span className="sr-only">Loading content</span><div className="skeleton h-10 w-2/3" /><div className="skeleton h-5 w-full" /><div className="skeleton h-5 w-4/5" /></div>;
}

export function ErrorState({ retry }: { retry?: () => void }) {
  return <div role="alert" className="my-8 rounded-lg border border-rose-200 bg-rose-50 p-6 text-slate-900"><h2 className="text-xl font-semibold">Unable to load this content</h2><p className="mt-2">Please try again in a moment.</p>{retry && <button className="button mt-4" onClick={retry}>Try again</button>}</div>;
}

export function EmptyState({ children = 'There is no published content available yet.' }: { children?: ReactNode }) {
  return <p className="my-8 rounded-lg border border-white/10 bg-white/3 p-6 text-slate-300">{children}</p>;
}

export function Breadcrumb({ title }: { title: string }) {
  return <nav aria-label="Breadcrumb" className="mb-8 text-sm text-slate-400"><ol className="flex flex-wrap gap-3"><li><Link to="/">Home</Link></li><li aria-hidden="true">/</li><li aria-current="page">{title}</li></ol></nav>;
}

export function RichText({ html }: { html?: string | null }) {
  // Only pass allowlist-sanitized content returned by the public CMS API.
  return html ? <div className="rich-text" dangerouslySetInnerHTML={{ __html: html }} /> : null;
}

export function SectionHeader({ title, eyebrow }: { title: string; eyebrow?: string }) {
  return <div className="mb-8 max-w-3xl">{eyebrow && <p className="eyebrow">{eyebrow}</p>}<h2 className="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">{title}</h2></div>;
}

export function HeroSection({ title, body, eyebrow }: { title: string; body?: string | null; eyebrow?: string }) {
  const [reducedMotion, setReducedMotion] = useState(false);
  const [videoStarted, setVideoStarted] = useState(false);
  const videoRef = useRef<HTMLVideoElement>(null);
  useEffect(() => {
    const query = window.matchMedia('(prefers-reduced-motion: reduce)');
    const update = () => setReducedMotion(query.matches);
    update();
    query.addEventListener('change', update);
    return () => query.removeEventListener('change', update);
  }, []);
  const playVideo = () => {
    setVideoStarted(true);
    void videoRef.current?.play();
  };
  return <section className="hero relative overflow-hidden bg-[#030812] text-white"><img src={heroImageWebp} alt="" aria-hidden="true" width={1600} height={900} className="absolute inset-0 h-full w-full object-cover opacity-70" loading="eager" decoding="async" fetchPriority="high" /><div className="absolute inset-0 bg-[radial-gradient(circle_at_78%_32%,rgba(20,184,166,.10),transparent_28%),linear-gradient(120deg,rgba(3,8,18,.82)_0%,rgba(7,25,47,.62)_54%,rgba(3,8,18,.34)_100%)]" /><div className="relative z-10 mx-auto grid min-h-160 max-w-7xl items-center gap-10 px-6 py-20 lg:grid-cols-[1fr_.9fr]">
    <div className="max-w-3xl">{eyebrow && <p className="eyebrow text-emerald-200">{eyebrow}</p>}<h1 className="mt-4 text-4xl font-semibold leading-tight tracking-tight sm:text-6xl">{title}</h1><div className="mt-7 max-w-2xl text-lg leading-relaxed text-slate-300"><RichText html={body} /></div><div className="mt-9 flex flex-wrap gap-4"><Link className="button button-light" to="/investments">Explore Investments</Link><Link className="button bg-white/10 text-white hover:bg-white/15" to="/contact?type=consultation">Request Consultation</Link></div></div>
    <div className="hero-video-panel" aria-label="Perryman Finance market perspective video"><div className="relative overflow-hidden rounded-lg"><video ref={videoRef} className="aspect-4/3 w-full bg-[#07192f] object-cover" controls={videoStarted} muted={reducedMotion} playsInline preload="metadata"><source src={heroVideo} type="video/mp4" /></video>{!videoStarted && <button type="button" className="video-play-overlay" aria-label="Play Perryman Finance market perspective video" onClick={playVideo}><span className="video-play-button" aria-hidden="true"><span /></span></button>}</div><div className="mt-4 grid grid-cols-3 gap-3 text-center text-xs text-slate-300"><span>Portfolio context</span><span>Risk review</span><span>Client enquiry</span></div></div>
  </div></section>;
}

export function PhilosophyStatsSection() {
  const stats: { value: string; label: string; body: string }[] = [
    { value: 'Research', label: 'Market perspective', body: 'Securities markets, liquidity conditions, and digital asset developments considered in relation to objectives and portfolio construction.' },
    { value: 'Governance', label: 'Decision discipline', body: 'A deliberate review considers risk, liquidity, diversification, documentation, and the operating requirements behind an investment.' },
  ];
  return <section className="section-wrap reveal-section pt-10" aria-labelledby="philosophy-stats-title"><div className="grid gap-10 lg:grid-cols-[.9fr_1.1fr] lg:items-center"><div className="overflow-hidden rounded-lg border border-white/10 bg-white/4"><img src={philosophyImage} alt="" aria-hidden="true" className="h-full min-h-90 w-full object-cover" loading="lazy" decoding="async" /></div><div><p className="eyebrow">Investment philosophy</p><h2 id="philosophy-stats-title" className="mt-3 text-3xl font-semibold text-white sm:text-4xl">A financial plan should reflect the whole picture</h2><p className="mt-5 text-lg text-slate-300">PerrymanFinance approaches investment and wealth decisions through the relationship between market opportunity and client context. That means looking beyond a headline to objectives, portfolio exposures, liquidity requirements, time horizon, governance, and the risks that can alter an outcome.</p><div className="mt-7 grid gap-4 sm:grid-cols-2">{stats.map((stat) => <article key={stat.label} className="stat-card compact-stat-card"><strong className="block text-3xl text-white">{stat.value}</strong><h3 className="mt-2 text-sm font-semibold uppercase text-emerald-100">{stat.label}</h3><p className="mt-2 text-sm text-slate-300">{stat.body}</p></article>)}</div></div></div></section>;
}

export function MarketsSection() {
  const markets: [string, string, string, string][] = [
    ['Currencies', 'Exchange rates, funding conditions, and cross-border purchasing power can shape portfolio risk and global allocation decisions.', 'Portfolio input', 'FX markets'],
    ['Digital Assets', 'Market structure, liquidity, technology, and regulation are examined alongside the volatility associated with emerging digital assets.', 'Higher volatility', 'Digital assets'],
    ['Public Equities', 'Earnings, valuations, sector leadership, and investor sentiment provide context for listed-equity exposure within diversified portfolios.', 'Market context', 'Listed securities'],
    ['Indices', 'Broad market benchmarks help frame regional participation, risk appetite, and changing macroeconomic conditions.', 'Benchmark context', 'Global indices'],
    ['Commodities', 'Real-asset prices can influence inflation expectations, industrial activity, currency dynamics, and diversification discussions.', 'Macro context', 'Commodities'],
    ['Energy', 'Oil, gas, and transition-related markets can affect inflation, currencies, corporate earnings, and wider risk assets.', 'Macro context', 'Energy markets'],
  ];
  return <section id="markets" className="section-wrap scroll-mt-28 reveal-section" aria-labelledby="markets-title"><div className="mb-8 grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end"><div><p className="eyebrow">Market perspective</p><h2 id="markets-title" className="mt-3 text-3xl font-semibold text-white sm:text-4xl">Market intelligence for better portfolio questions</h2><p className="mt-4 max-w-3xl text-slate-300">PerrymanFinance follows traditional and digital markets to connect changing conditions with the questions that matter to investors: What is driving the market? Where could liquidity change? How might an exposure interact with a wider portfolio? Market information is educational context, not a personalized recommendation or instruction to trade.</p></div><p className="rounded border border-emerald-300/20 bg-emerald-300/10 px-4 py-3 text-sm text-emerald-100">Context, not a trading signal.</p></div>
    <article className="market-chart-panel" aria-labelledby="eurusd-title"><div className="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h3 id="eurusd-title" className="text-xl font-semibold text-white">EUR/USD market context</h3><p className="text-sm text-slate-400">A third-party view of a major currency pair and its movement over time.</p></div><span className="rounded bg-white/10 px-3 py-1 text-xs text-slate-200">External data</span></div><iframe title="EUR/USD market chart" src="https://s.tradingview.com/widgetembed/?symbol=FX%3AEURUSD&interval=60&theme=dark&style=1&timezone=Etc%2FUTC&hide_top_toolbar=1&hide_side_toolbar=0&allow_symbol_change=0&save_image=0&studies=%5B%5D" loading="lazy" className="h-105 w-full rounded border-0 bg-[#07192f]" /><p className="mt-3 text-xs text-slate-400">Third-party data can be delayed, incomplete, or unavailable. Confirm current information independently before making a financial decision.</p></article>
    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{markets.map(([name, body, signal, scope]) => <article className="market-card" key={name} tabIndex={0}><div className="flex items-start justify-between gap-4"><div><h3 className="text-lg font-semibold text-white">{name}</h3><p className="mt-2 text-sm text-slate-300">{body}</p></div><img src={chartIcon} alt="" aria-hidden="true" className="h-9 w-9 shrink-0" loading="lazy" /></div><div className="mt-5 flex items-center justify-between border-t border-white/10 pt-4"><span className="text-xs uppercase text-slate-400">{scope}</span><span className="text-emerald-200">{signal}</span></div></article>)}</div></section>;
}

function safePublicPath(value: unknown): string | null {
  return typeof value === 'string' && /^\/(?!\/)[a-z0-9/?=&%#_-]*$/i.test(value) && !value.startsWith('/admin') ? value : null;
}

export function ServiceCard({ title, body, href }: { title: string; body?: string; href?: string }) {
  const path = safePublicPath(href);
  return <article className="rounded-lg border border-white/10 bg-white/4 p-7 shadow-[0_20px_70px_rgba(0,0,0,.22)]"><div className="mb-7 h-1 w-10 bg-emerald-300" /><h3 className="text-xl font-semibold text-white">{title}</h3><div className="mt-4 text-slate-300"><RichText html={body} /></div>{path && <Link className="mt-6 inline-block font-semibold text-emerald-200" to={path}>Learn More <span aria-hidden="true">-&gt;</span></Link>}</article>;
}

export function ProcessSteps({ items }: { items: { title: string; body?: string }[] }) {
  return <ol className="grid gap-8 md:grid-cols-4">{items.map((item, index) => <li key={index} className="border-t border-white/15 pt-6"><span className="font-mono text-sm text-emerald-300">{String(index + 1).padStart(2, '0')}</span><h3 className="mt-5 text-xl font-semibold text-white">{item.title}</h3><div className="mt-3 text-slate-300"><RichText html={item.body} /></div></li>)}</ol>;
}

export function CTASection({ title = 'Start a Consultation', body }: { title?: string; body?: string }) {
  return <section className="cta-panel overflow-hidden rounded-lg border border-emerald-300/20 bg-[#07192f] text-white sm:grid sm:grid-cols-[1fr_340px]"><div className="p-8 sm:p-12"><p className="eyebrow">Next step</p><h2 className="mt-3 text-3xl font-semibold">{title}</h2><div className="mt-4 max-w-2xl text-slate-300"><RichText html={body || 'Start a focused conversation about investment services, securities products, digital asset strategy, wealth planning, client account needs, reporting expectations, and the information you need before moving forward.'} /></div><Link className="button button-light mt-7" to="/contact?type=consultation">Request Consultation</Link></div><div className="hidden items-center justify-center bg-[#103d4a] p-5 sm:flex"><div className="rounded-lg bg-white/90 p-4"><img src={callImage} alt="" aria-hidden="true" className="max-h-52 object-contain" loading="lazy" decoding="async" /></div></div></section>;
}

export function RiskNotice({ children }: { children?: ReactNode }) {
  return <aside aria-label="Risk notice" className="my-8 border-l-2 border-emerald-300 bg-white/4 p-6 text-slate-200"><h2 className="font-semibold text-white">Risk information</h2>{children && <div className="mt-3 text-sm leading-relaxed">{children}</div>}<Link className="mt-3 inline-block text-sm font-semibold text-emerald-200 underline" to="/risk-disclosure">Read the Risk Disclosure</Link></aside>;
}

export function FAQAccordion({ question, answer }: { question: string; answer: string }) {
  return <details className="group border-b border-white/10 last:border-b-0"><summary className="flex cursor-pointer list-none items-center justify-between gap-5 py-5 text-left text-base font-semibold text-white marker:content-none focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-emerald-200 sm:text-lg"><span>{question}</span><span aria-hidden="true" className="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-white/15 text-xl font-normal text-emerald-100 transition-transform duration-200 group-open:rotate-45">+</span></summary><div className="pb-5 text-slate-300"><RichText html={answer} /></div></details>;
}
