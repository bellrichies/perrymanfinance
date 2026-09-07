import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from './authContext';

const navigation = [['Pages', 'pages.view'], ['Investments', 'investments.view'], ['Articles', 'articles.view'], ['FAQs', 'faqs.manage'], ['Enquiries', 'enquiries.view'], ['Media', 'media.manage'], ['Settings', 'settings.manage'], ['Users', 'users.manage'], ['Audit logs', 'audit.view'], ['Legal', 'legal.manage']] as const;

export function AdminLayout() {
  const auth = useAuth();
  return <div className="min-h-screen bg-slate-950 text-slate-100"><header className="border-b border-slate-800 px-5 py-4"><div className="mx-auto flex max-w-7xl items-center justify-between"><span className="font-semibold">PerrymanFinance Admin</span><button className="rounded-lg border border-slate-600 px-3 py-2" onClick={() => void auth.logout()}>Sign out</button></div></header><div className="mx-auto grid max-w-7xl gap-8 px-5 py-8 md:grid-cols-[220px_1fr]"><nav aria-label="Admin navigation" className="space-y-1">{navigation.filter(([, permission]) => auth.can(permission)).map(([label]) => <NavLink className="block rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800" key={label} to={`/admin/${label.toLowerCase().replace(' ', '-')}`}>{label}</NavLink>)}{(auth.can('pages.update') || auth.can('legal.manage')) && <NavLink className="block rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800" to="/admin/seo">SEO</NavLink>}</nav><Outlet /></div></div>;
}

export function DashboardPage() {
  const { user } = useAuth();
  return <main><h1 className="text-3xl font-semibold">Dashboard</h1><p className="mt-3 text-slate-400">Signed in as {user?.display_name}.</p><p className="mt-8 rounded-xl border border-slate-800 p-5 text-slate-300">CMS modules will appear here as their vertical slices are delivered.</p></main>;
}
