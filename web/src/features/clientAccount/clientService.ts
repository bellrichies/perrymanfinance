import { getJson, requestJson } from '../../services/apiClient';

export type ClientPlan = { uuid: string; title: string; short_description: string; risk_classification: string; minimum_investment_display: string | null; currency_display: string | null; disclaimer: string };
export type ClientPlanRequest = { uuid: string; requested_amount: string | null; currency: string; status: string; created_at: string; reviewed_at: string | null; plan_title: string; risk_classification: string };
export type ClientInvestmentAccount = { uuid: string; status: string; approved_amount: string | null; current_balance: string | null; currency: string; approved_at: string; last_snapshot_at: string | null; plan_title: string; risk_classification: string };
export type ClientReportingSnapshot = { uuid: string; snapshot_date: string; principal_amount: string; reported_value: string; growth_amount: string; growth_percent: string; currency: string; methodology_note: string; approved_at: string };
export type ClientProfile = { uuid: string; email: string; status: string; email_verified_at: string | null; profile: { first_name: string | null; last_name: string | null } };
export type ClientDashboardSummary = { active_investments: number; pending_requests: number; reported_balance: string | null; currency: string | null; latest_snapshot_date: string | null };
export type ClientDashboard = { client: ClientProfile; summary: ClientDashboardSummary; plan_requests: ClientPlanRequest[]; investment_accounts: ClientInvestmentAccount[]; latest_snapshots?: { account_uuid: string; plan_title?: string; snapshots: ClientReportingSnapshot[] }[]; disclaimer: string };

type Envelope<T> = { data: T; message?: string };

export const clientService = {
  register: (body: Record<string, unknown>) => requestJson<Envelope<null>>('/client/auth/register', { method: 'POST', body: JSON.stringify(body) }),
  forgotPassword: (email: string) => requestJson<Envelope<null>>('/client/auth/forgot-password', { method: 'POST', body: JSON.stringify({ email }) }),
  resetPassword: (token: string, password: string) => requestJson<Envelope<null>>('/client/auth/reset-password', { method: 'POST', body: JSON.stringify({ token, password }) }),
  verifyEmail: (token: string) => requestJson<Envelope<null>>('/client/auth/verify-email', { method: 'POST', body: JSON.stringify({ token }) }),
  dashboard: () => getJson<Envelope<ClientDashboard>>('/client/dashboard').then(r => r.data),
  plans: () => getJson<Envelope<ClientPlan[]>>('/client/plans').then(r => r.data),
  submitPlanRequest: (body: Record<string, unknown>) => requestJson<Envelope<{ uuid: string; status: string }>>('/client/plan-requests', { method: 'POST', body: JSON.stringify(body) }),
  snapshots: (uuid: string) => getJson<Envelope<ClientReportingSnapshot[]>>(`/client/investments/${uuid}/snapshots`).then(r => r.data),
};

export type AdminClient = { uuid: string; email: string; status: string; first_name: string | null; last_name: string | null; created_at: string };
export type AdminPlanRequest = ClientPlanRequest & { client_uuid: string; email: string; first_name: string | null; last_name: string | null };
export type AdminInvestmentAccount = ClientInvestmentAccount & { id?: number; client_user_id?: number };
export type AdminClientDetail = { client: AdminClient & { phone?: string | null; country?: string | null }; plan_requests: ClientPlanRequest[]; investment_accounts: AdminInvestmentAccount[]; audit_events: { actor_type: string; action: string; entity_type: string | null; entity_id: string | null; created_at: string }[] };
export type AdminInvestmentDetail = { account: AdminInvestmentAccount; adjustments: { uuid: string; adjustment_type: string; amount: string; currency: string; effective_at: string; source_reference: string; reason: string; created_at: string }[]; snapshots: (ClientReportingSnapshot & { source_reference: string })[] };

export const adminClientService = {
  clients: () => getJson<Envelope<AdminClient[]>>('/admin/clients').then(r => r.data),
  client: (uuid: string) => getJson<Envelope<AdminClientDetail>>(`/admin/clients/${uuid}`).then(r => r.data),
  planRequests: () => getJson<Envelope<AdminPlanRequest[]>>('/admin/client-plan-requests?status=pending').then(r => r.data),
  investment: (uuid: string) => getJson<Envelope<AdminInvestmentDetail>>(`/admin/client-investments/${uuid}`).then(r => r.data),
  approve: (uuid: string, reason: string) => requestJson<Envelope<{ uuid: string; status: string }>>(`/admin/client-plan-requests/${uuid}/approve`, { method: 'POST', body: JSON.stringify({ reason }) }),
  reject: (uuid: string, reason: string) => requestJson<Envelope<{ uuid: string; status: string }>>(`/admin/client-plan-requests/${uuid}/reject`, { method: 'POST', body: JSON.stringify({ reason }) }),
  balanceAdjustment: (uuid: string, body: Record<string, unknown>) => requestJson<Envelope<{ uuid: string; status: string }>>(`/admin/client-investments/${uuid}/balance-adjustments`, { method: 'POST', body: JSON.stringify(body) }),
  reportingSnapshot: (uuid: string, body: Record<string, unknown>) => requestJson<Envelope<{ uuid: string; status: string }>>(`/admin/client-investments/${uuid}/reporting-snapshots`, { method: 'POST', body: JSON.stringify(body) }),
};
