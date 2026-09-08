import { lazy, Suspense, type ReactNode } from 'react';
import { LoadingSkeleton } from './components/marketing/Primitives';
import { BrowserRouter, Route, Routes, useLocation } from 'react-router-dom';
import { AuthProvider } from './features/auth/AuthProvider';
import { ProtectedRoute } from './features/auth/ProtectedRoute';

export function App() {
  return <BrowserRouter><AuthenticationBoundary><Suspense fallback={<div className="mx-auto max-w-6xl px-5"><LoadingSkeleton /></div>}><Routes>
    <Route path="/admin/login" element={<LoginPage />} />
    <Route path="/admin/forgot-password" element={<ForgotPasswordPage />} />
    <Route path="/admin/reset-password" element={<ResetPasswordPage />} />
    <Route path="/investments" element={<InvestmentsPage />} />
    <Route path="/investments/:slug" element={<InvestmentDetailPage />} />
    <Route path="/insights" element={<InsightsPage />} />
    <Route path="/insights/:slug" element={<InsightDetailPage />} />
    <Route path="/faq" element={<FAQPage />} />
    <Route element={<ProtectedRoute />}><Route path="/admin" element={<AdminLayout />}><Route index element={<DashboardPage />} /><Route path="pages" element={<PagesScreen />} /><Route path="investments" element={<AdminInvestmentsPage />} /><Route path="articles" element={<AdminEditorialPage />} /><Route path="faqs" element={<AdminEditorialPage initialTab="faqs" />} /><Route path="legal" element={<LegalScreen />} /><Route path="settings" element={<SettingsScreen />} /><Route path="media" element={<MediaScreen />} /><Route path="seo" element={<SeoScreen />} /></Route></Route>
    <Route path="/" element={<MarketingPage slug="home" title="PerrymanFinance" />} />
    {Object.entries({ about: 'About', 'investment-solutions': 'Investment Solutions', 'digital-assets': 'Digital Assets', 'wealth-management': 'Wealth Management', 'how-it-works': 'How It Works' }).map(([slug, title]) => <Route key={slug} path={`/${slug}`} element={<MarketingPage slug={slug} title={title} />} />)}
    {Object.entries({ terms: 'Terms', 'privacy-policy': 'Privacy Policy', 'risk-disclosure': 'Risk Disclosure', 'cookie-policy': 'Cookie Policy' }).map(([slug, title]) => <Route key={slug} path={`/${slug}`} element={<LegalPage slug={slug} title={title} />} />)}
    <Route path="/contact" element={<ContactPage />} />
    <Route path="*" element={<NotFoundPage />} />
  </Routes></Suspense></AuthenticationBoundary></BrowserRouter>;
}

const InvestmentDetailPage = lazy(() => import('./features/investments/InvestmentPages').then(m => ({ default: m.InvestmentDetailPage })));

const InvestmentsPage = lazy(() => import('./features/investments/InvestmentPages').then(m => ({ default: m.InvestmentsPage })));

const FAQPage = lazy(() => import('./features/insights/InsightPages').then(m => ({ default: m.FAQPage })));

const InsightDetailPage = lazy(() => import('./features/insights/InsightPages').then(m => ({ default: m.InsightDetailPage })));

const InsightsPage = lazy(() => import('./features/insights/InsightPages').then(m => ({ default: m.InsightsPage })));

const MarketingPage = lazy(() => import('./features/public/MarketingPages').then(m => ({ default: m.MarketingPage })));

const LegalPage = lazy(() => import('./features/public/MarketingPages').then(m => ({ default: m.LegalPage })));

const NotFoundPage = lazy(() => import('./features/public/MarketingPages').then(m => ({ default: m.NotFoundPage })));

const ContactPage = lazy(() => import('./features/public/ContactPage').then(m => ({ default: m.ContactPage })));

function AuthenticationBoundary({ children }: { children: ReactNode }) {
  const { pathname } = useLocation();
  return pathname.startsWith('/admin') ? <AuthProvider>{children}</AuthProvider> : children;
}

const AdminLayout = lazy(() => import('./features/auth/AdminLayout').then(m => ({ default: m.AdminLayout })));

const DashboardPage = lazy(() => import('./features/auth/AdminLayout').then(m => ({ default: m.DashboardPage })));

const LoginPage = lazy(() => import('./features/auth/LoginPage').then(m => ({ default: m.LoginPage })));

const ForgotPasswordPage = lazy(() => import('./features/auth/PasswordResetPages').then(m => ({ default: m.ForgotPasswordPage })));

const ResetPasswordPage = lazy(() => import('./features/auth/PasswordResetPages').then(m => ({ default: m.ResetPasswordPage })));

const LegalScreen = lazy(() => import('./features/content/CmsScreens').then(m => ({ default: m.LegalScreen })));

const MediaScreen = lazy(() => import('./features/content/CmsScreens').then(m => ({ default: m.MediaScreen })));

const PagesScreen = lazy(() => import('./features/content/CmsScreens').then(m => ({ default: m.PagesScreen })));

const SeoScreen = lazy(() => import('./features/content/CmsScreens').then(m => ({ default: m.SeoScreen })));

const SettingsScreen = lazy(() => import('./features/content/CmsScreens').then(m => ({ default: m.SettingsScreen })));

const AdminInvestmentsPage = lazy(() => import('./features/investments/AdminInvestmentsPage').then(m => ({ default: m.AdminInvestmentsPage })));

const AdminEditorialPage = lazy(() => import('./features/insights/AdminEditorialPage').then(m => ({ default: m.AdminEditorialPage })));
