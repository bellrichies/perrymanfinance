import { useEffect, useRef, useState, type CSSProperties, type ReactNode } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { useOptionalClientAuth } from '../../features/clientAccount/clientAuthContext';

const navigation: [string, string][] = [['Home', '/'], ['About', '/about'], ['Investments', '/investments'], ['Insights', '/insights'], ['Contact', '/contact']];
const services: [string, string][] = [['Digital Assets', '/digital-assets'], ['Wealth Management', '/wealth-management'], ['How It Works', '/how-it-works'], ['Investment Opportunities', '/investments']];
const legal: [string, string][] = [['Terms', '/terms'], ['Privacy', '/privacy-policy'], ['Risk Disclosure', '/risk-disclosure'], ['Cookie Policy', '/cookie-policy']];

function BrandLogo({ compact = false }: { compact?: boolean }) {
  return <span className="inline-flex items-center gap-3">
    <img src="/favicon.svg" alt="" aria-hidden="true" className={compact ? 'h-8 w-8 rounded-lg' : 'h-9 w-9 rounded-lg'} decoding="async" />
    <span>Perryman<span className="font-normal text-emerald-200">Finance</span></span>
  </span>;
}

export function MobileNavigation() {
  const [open, setOpen] = useState(false);
  const button = useRef<HTMLButtonElement>(null);
  const location = useLocation();
  const clientAuth = useOptionalClientAuth();
  const accountLink = clientAuth?.status === 'authenticated' ? '/client' : '/client/login';
  const accountLabel = clientAuth?.status === 'authenticated' ? 'Dashboard' : 'Client Login';
  function navigate(path: string) {
    setOpen(false);
    if (path === '/#markets' && location.pathname === '/') requestAnimationFrame(() => document.getElementById('markets')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
  }
  return <div className="md:hidden" onKeyDown={(event) => { if (event.key === 'Escape') { setOpen(false); button.current?.focus(); } }}>
    <button ref={button} className="rounded border border-white/25 px-4 py-2 text-sm" aria-expanded={open} aria-controls="mobile-navigation" onClick={() => setOpen(!open)}>{open ? 'Close menu' : 'Menu'}</button>
    {open && <nav id="mobile-navigation" aria-label="Mobile navigation" className="absolute inset-x-0 top-full z-30 border-t border-white/10 bg-[#030812] p-5 shadow-xl">{navigation.map(([label, path]) => <NavLink className="block rounded px-3 py-3" key={path} to={path} onClick={() => navigate(path)}>{label}</NavLink>)}<div className="mt-4 border-t border-white/10 pt-4"><Link className="button bg-white/10" to={accountLink} onClick={() => setOpen(false)}>{accountLabel}</Link>{clientAuth?.status !== 'authenticated' && <Link className="ml-3 text-sm text-emerald-200" to="/client/register" onClick={() => setOpen(false)}>Create Account</Link>}</div></nav>}
  </div>;
}

export function Header() {
  const { pathname, hash } = useLocation();
  const clientAuth = useOptionalClientAuth();
  const accountLink = clientAuth?.status === 'authenticated' ? '/client' : '/client/login';
  const accountLabel = clientAuth?.status === 'authenticated' ? 'Dashboard' : 'Client Login';
  useEffect(() => {
    if (hash === '#markets') requestAnimationFrame(() => document.getElementById('markets')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
  }, [hash, pathname]);
  return <header className="sticky top-0 z-20 border-b border-white/10 bg-[#030812]/90 text-white backdrop-blur">
    <div className="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-5">
      <Link to="/" aria-label="PerrymanFinance home" className="text-xl font-semibold tracking-tight"><BrandLogo /></Link>
      <nav aria-label="Primary navigation" className="hidden items-center gap-7 text-sm text-slate-300 md:flex">{navigation.map(([label, path]) => <NavLink className="py-2 hover:text-white" to={path} key={path}>{label}</NavLink>)}</nav>
      <div className="hidden items-center gap-3 md:flex"><Link className="px-4 py-2 text-sm font-semibold text-slate-200 hover:text-white" to={accountLink}>{accountLabel}</Link>{clientAuth?.status !== 'authenticated' && <Link className="button bg-white/10" to="/client/register">Create Account</Link>}</div>
      <MobileNavigation key={pathname} />
    </div>
  </header>;
}

export function Footer() {
  return <footer className="border-t border-white/10 bg-[#030812] text-slate-300">
    <div className="mx-auto max-w-7xl px-6 py-14">
      <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr_1.25fr]">
        <div><Link className="text-xl font-semibold text-white" to="/"><BrandLogo compact /></Link><p className="mt-4 text-sm text-slate-400">Financial services, securities products, wealth and portfolio management, digital asset research, client account pathways, and market insight for clients seeking a disciplined operating platform.</p></div>
        <nav aria-label="Services"><h2 className="mb-4 font-semibold text-white">Explore</h2>{services.map(([label, path]) => <Link className="mb-3 block text-sm" to={path} key={path}>{label}</Link>)}</nav>
        <nav aria-label="Resources"><h2 className="mb-4 font-semibold text-white">Resources</h2>{([['Insights', '/insights'], ['FAQ', '/faq'], ['Contact', '/contact']] as const).map(([label, path]) => <Link className="mb-3 block text-sm" to={path} key={path}>{label}</Link>)}</nav>
        <nav aria-label="Legal"><h2 className="mb-4 font-semibold text-white">Legal & risk</h2>{legal.map(([label, path]) => <Link className="mb-3 block text-sm" to={path} key={path}>{label}</Link>)}</nav>
        <section aria-labelledby="footer-contact"><h2 id="footer-contact" className="mb-4 font-semibold text-white">Contact information</h2><address className="text-sm not-italic leading-7 text-slate-400">12221 Merit Drive, Suite 1661<br />Dallas, TX 75251</address><a className="mt-3 block wrap-break-word text-sm text-emerald-200" href="mailto:support@perrymanfinance.com">support@perrymanfinance.com</a></section>
      </div>
      <div className="mt-10 flex flex-col gap-3 border-t border-white/10 pt-7 text-sm sm:flex-row sm:items-center sm:justify-between"><p className="text-xs">&copy; {new Date().getFullYear()} PerrymanFinance</p><Link className="underline" to="/risk-disclosure">Risk Disclosure</Link></div>
    </div>
  </footer>;
}

function BackToTopButton() {
  const [visible, setVisible] = useState(false);
  const [progress, setProgress] = useState(0);
  useEffect(() => {
    const update = () => {
      const scrollable = document.documentElement.scrollHeight - window.innerHeight;
      const nextProgress = scrollable > 0 ? Math.min(100, Math.max(0, (window.scrollY / scrollable) * 100)) : 0;
      setVisible(window.scrollY > 500);
      setProgress(nextProgress);
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    return () => {
      window.removeEventListener('scroll', update);
      window.removeEventListener('resize', update);
    };
  }, []);
  return <button type="button" className={`back-to-top ${visible ? 'is-visible' : ''}`} style={{ '--scroll-progress': `${progress}%` } as CSSProperties} aria-label="Go to top" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>
    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2.25" strokeLinecap="round" strokeLinejoin="round">
      <path d="m18 15-6-6-6 6" />
    </svg>
  </button>;
}

export function PublicLayout({ children }: { children: ReactNode }) {
  const { pathname, hash } = useLocation();
  const previousPath = useRef(pathname);
  useEffect(() => {
    if (previousPath.current !== pathname) {
      document.querySelector<HTMLElement>('main')?.focus();
      if (hash) requestAnimationFrame(() => document.getElementById(hash.slice(1))?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
      else window.scrollTo?.(0, 0);
      previousPath.current = pathname;
    }
  }, [hash, pathname]);
  return <div className="public-site min-h-screen bg-[#030812] text-slate-200"><a className="skip-link" href="#content">Skip to content</a><Header />{children}<Footer /><BackToTopButton /></div>;
}
