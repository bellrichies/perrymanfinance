import { useEffect, useRef, useState, type ReactNode } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { usePublicSettings } from '../../features/public/publicService';
import { RichText } from './Primitives';

const navigation: [string, string][] = [['About', '/about'], ['Solutions', '/investment-solutions'], ['Investments', '/investments'], ['Insights', '/insights'], ['Contact', '/contact']];
const services: [string, string][] = [['Investment Solutions', '/investment-solutions'], ['Digital Assets', '/digital-assets'], ['Wealth Management', '/wealth-management'], ['How It Works', '/how-it-works']];
const legal: [string, string][] = [['Terms', '/terms'], ['Privacy', '/privacy-policy'], ['Risk Disclosure', '/risk-disclosure'], ['Cookie Policy', '/cookie-policy']];
export function MobileNavigation() {
  const [open, setOpen] = useState(false);
  const button = useRef<HTMLButtonElement>(null);
  return <div className="md:hidden" onKeyDown={(event) => { if (event.key === 'Escape') { setOpen(false); button.current?.focus(); } }}><button ref={button} className="rounded border border-slate-500 px-4 py-2" aria-expanded={open} aria-controls="mobile-navigation" onClick={() => setOpen(!open)}>{open ? 'Close menu' : 'Menu'}</button>{open && <nav id="mobile-navigation" aria-label="Mobile navigation" className="absolute inset-x-0 top-full z-30 border-t border-slate-700 bg-[#07192f] p-5 shadow-xl">{navigation.map(([label, path]) => <NavLink className="block rounded px-3 py-3" key={path} to={path} onClick={() => setOpen(false)}>{label}</NavLink>)}</nav>}</div>;
}
export function Header() {
  const { pathname } = useLocation();
  return <header className="relative z-20 bg-[#07192f] text-white"><div className="mx-auto flex max-w-6xl items-center justify-between gap-6 px-5 py-6"><Link to="/" aria-label="PerrymanFinance home" className="text-xl font-semibold tracking-tight">Perryman<span className="font-normal text-emerald-200">Finance</span></Link><nav aria-label="Primary navigation" className="hidden items-center gap-7 text-sm md:flex">{navigation.map(([label, path]) => <NavLink className="py-2" to={path} key={path}>{label}</NavLink>)}</nav><MobileNavigation key={pathname} /></div></header>;
}
export function Footer() {
  const settings = usePublicSettings();
  const risk = settings.data?.data.risk_statement;
  return <footer className="bg-[#07192f] text-slate-300"><div className="mx-auto max-w-6xl px-5 py-14"><div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4"><div><Link className="text-xl font-semibold text-white" to="/">PerrymanFinance</Link></div><nav aria-label="Services"><h2 className="mb-4 font-semibold text-white">Explore</h2>{services.map(([label, path]) => <Link className="mb-3 block text-sm" to={path} key={path}>{label}</Link>)}</nav><nav aria-label="Resources"><h2 className="mb-4 font-semibold text-white">Resources</h2>{([['Insights', '/insights'], ['FAQ', '/faq'], ['Contact', '/contact']] as const).map(([label, path]) => <Link className="mb-3 block text-sm" to={path} key={path}>{label}</Link>)}</nav><nav aria-label="Legal"><h2 className="mb-4 font-semibold text-white">Legal & risk</h2>{legal.map(([label, path]) => <Link className="mb-3 block text-sm" to={path} key={path}>{label}</Link>)}</nav></div><div className="mt-10 border-t border-slate-700 pt-7 text-sm leading-relaxed">{typeof risk === 'string' && <RichText html={risk} />}<Link className="mt-4 inline-block underline" to="/risk-disclosure">Risk Disclosure</Link><p className="mt-6 text-xs">© {new Date().getFullYear()} PerrymanFinance</p></div></div></footer>;
}
export function PublicLayout({ children }: { children: ReactNode }) {
  const { pathname } = useLocation();
  const previousPath = useRef(pathname);
  useEffect(() => {
    if (previousPath.current !== pathname) {
      document.querySelector<HTMLElement>('main')?.focus();
      window.scrollTo?.(0, 0);
      previousPath.current = pathname;
    }
  }, [pathname]);
  return <div className="public-site min-h-screen bg-[#f8fafb] text-slate-900"><a className="skip-link" href="#content">Skip to content</a><Header />{children}<Footer /></div>;
}
