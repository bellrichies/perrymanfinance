import { lazy, Suspense, type ReactNode } from 'react';
import { LoadingSkeleton } from './components/marketing/Primitives';
import { BrowserRouter, Route, Routes, useLocation } from 'react-router-dom';
import { AuthProvider } from './features/auth/AuthProvider';
import { ProtectedRoute } from './features/auth/ProtectedRoute';
import { ClientAuthProvider } from './features/clientAccount/ClientAuthProvider';
import { ClientProtectedRoute } from './features/clientAccount/ClientProtectedRoute';

export function App() {
  return <BrowserRouter><AuthenticationBoundary><Suspense fallback={<div className="mx-auto max-w-6xl px-5"><LoadingSkeleton /></div>}><Routes>
    <Route path="/admin/login" element={<LoginPage />} />
    <Route path="/admin/forgot-password" element={<ForgotPasswordPage />} />
    <Route path="/admin/reset-password" element={<ResetPasswordPage />} />
    <Route path="/client/login" element={<ClientBoundary><ClientLoginPage /></ClientBoundary>} />
    <Route path="/client/register" element={<ClientBoundary><ClientRegisterPage /></ClientBoundary>} />
    <Route path="/client/verify-email" element={<ClientBoundary><ClientVerifyEmailPage /></ClientBoundary>} />
    <Route path="/client/forgot-password" element={<ClientBoundary><ClientForgotPasswordPage /></ClientBoundary>} />
    <Route path="/client/reset-password" element={<ClientBoundary><ClientResetPasswordPage /></ClientBoundary>} />
    <Route element={<ClientBoundary><ClientProtectedRoute /></ClientBoundary>}><Route path="/client" element={<ClientDashboardPage />} /><Route path="/client/plans" element={<ClientPlansPage />} /><Route path="/client/profile" element={<ClientDashboardPage />} /><Route path="/client/investments" element={<ClientDashboardPage />} /></Route>
    <Route path="/investments" element={<InvestmentsPage />} />
    <Route path="/investments/:slug" element={<InvestmentDetailPage />} />
    <Route path="/insights" element={<InsightsPage />} />
    <Route path="/insights/:slug" element={<InsightDetailPage />} />
    <Route path="/faq" element={<FAQPage />} />
    <Route path="/health" element={<HealthPage />} />
    <Route element={<ProtectedRoute />}><Route element={<AdminLayout />}><Route path="/admin/enquiries" element={<AdminEnquiriesPage />} /></Route></Route>
    <Route element={<ProtectedRoute />}><Route path="/admin" element={<AdminLayout />}><Route index element={<DashboardPage />} /><Route path="pages" element={<PagesScreen />} /><Route path="investments" element={<AdminInvestmentsPage />} /><Route path="articles" element={<AdminEditorialPage />} /><Route path="faqs" element={<AdminEditorialPage initialTab="faqs" />} /><Route path="legal" element={<LegalScreen />} /><Route path="settings" element={<SettingsScreen />} /><Route path="media" element={<MediaScreen />} /><Route path="seo" element={<SeoScreen />} /><Route path="clients" element={<AdminClientsPage />} /><Route path="clients/:uuid" element={<AdminClientInvestmentOperationsPage />} /><Route path="client-plan-requests" element={<AdminClientsPage />} /><Route path="users" element={<AdminUsersPage />} /><Route path="audit-logs" element={<AdminAuditLogsPage />} /></Route></Route>
    <Route path="/" element={<MarketingPage slug="home" title="PerrymanFinance" />} />
    {Object.entries({ about: 'About', 'digital-assets': 'Digital Assets', 'wealth-management': 'Wealth Management', 'how-it-works': 'How It Works' }).map(([slug, title]) => <Route key={slug} path={`/${slug}`} element={<MarketingPage slug={slug} title={title} />} />)}
    {Object.entries({ terms: 'Terms', 'privacy-policy': 'Privacy Policy', 'risk-disclosure': 'Risk Disclosure', 'cookie-policy': 'Cookie Policy' }).map(([slug, title]) => <Route key={slug} path={`/${slug}`} element={<LegalPage slug={slug} title={title} />} />)}
    <Route path="/contact" element={<ContactPage />} />
    <Route path="*" element={<NotFoundPage />} />
  </Routes></Suspense></AuthenticationBoundary></BrowserRouter>;
}

function ClientBoundary({ children }: { children: ReactNode }) {
  return <>{children}</>;
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
  return pathname.startsWith('/admin') ? <AuthProvider>{children}</AuthProvider> : <ClientAuthProvider>{children}</ClientAuthProvider>;
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
const AdminEnquiriesPage = lazy(() => import('./features/enquiries/AdminEnquiriesPage').then(m => ({ default: m.AdminEnquiriesPage })));
const HealthPage = lazy(() => import('./features/health/HealthPage').then(m => ({ default: m.HealthPage })));
const ClientLoginPage = lazy(() => import('./features/clientAccount/ClientPages').then(m => ({ default: m.ClientLoginPage })));
const ClientRegisterPage = lazy(() => import('./features/clientAccount/ClientPages').then(m => ({ default: m.ClientRegisterPage })));
const ClientVerifyEmailPage = lazy(() => import('./features/clientAccount/ClientPages').then(m => ({ default: m.ClientVerifyEmailPage })));
const ClientForgotPasswordPage = lazy(() => import('./features/clientAccount/ClientPages').then(m => ({ default: m.ClientForgotPasswordPage })));
const ClientResetPasswordPage = lazy(() => import('./features/clientAccount/ClientPages').then(m => ({ default: m.ClientResetPasswordPage })));
const ClientDashboardPage = lazy(() => import('./features/clientAccount/ClientPages').then(m => ({ default: m.ClientDashboardPage })));
const ClientPlansPage = lazy(() => import('./features/clientAccount/ClientPages').then(m => ({ default: m.ClientPlansPage })));
const AdminClientsPage = lazy(() => import('./features/clientAccount/AdminClientPages').then(m => ({ default: m.AdminClientsPage })));
const AdminClientInvestmentOperationsPage = lazy(() => import('./features/clientAccount/AdminClientPages').then(m => ({ default: m.AdminClientInvestmentOperationsPage })));
const AdminUsersPage = lazy(() => import('./features/auth/AdminOperationsPages').then(m => ({ default: m.AdminUsersPage })));
const AdminAuditLogsPage = lazy(() => import('./features/auth/AdminOperationsPages').then(m => ({ default: m.AdminAuditLogsPage })));
