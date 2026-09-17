import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ClientAuthProvider } from './ClientAuthProvider';
import { AdminClientsPage } from './AdminClientPages';
import { ClientDashboardPage, ClientPlansPage, ClientRegisterPage } from './ClientPages';

afterEach(() => vi.unstubAllGlobals());

function envelope(data: unknown, status = 200) {
  return new Response(JSON.stringify({ success: status < 400, data, meta: {}, message: null }), { status });
}

function bodyFor(fetchMock: ReturnType<typeof vi.fn>, path: string) {
  const call = fetchMock.mock.calls.find(args => String(args[0]).includes(path));
  const init = call?.[1] as RequestInit | undefined;
  expect(init?.body).toBeTypeOf('string');
  return JSON.parse(String(init?.body));
}

describe('client account pages', () => {
  it('registers a client with required risk and terms acknowledgement', async () => {
    const fetchMock = vi.fn().mockResolvedValue(envelope(null, 201));
    vi.stubGlobal('fetch', fetchMock);
    render(<MemoryRouter><ClientRegisterPage /></MemoryRouter>);

    fireEvent.change(screen.getByLabelText('First name'), { target: { value: 'Ada' } });
    fireEvent.change(screen.getByLabelText('Last name'), { target: { value: 'Client' } });
    fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'ada@example.test' } });
    fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'Secure-client-123' } });
    fireEvent.change(screen.getByLabelText('Confirm password'), { target: { value: 'Secure-client-123' } });
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getByRole('button', { name: 'Create Account' }));

    await screen.findByText(/check your email/i);
    expect(bodyFor(fetchMock, '/client/auth/register')).toMatchObject({ consent_terms: true });
  });

  it('submits a plan request only after acknowledgement', async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL) => {
      const url = String(input);
      if (url.endsWith('/client/auth/refresh')) return envelope({ user: null, access_token: '' }, 401);
      if (url.endsWith('/client/plans')) return envelope([{ uuid: 'plan-1', title: 'Managed Income', short_description: 'A reviewed plan.', risk_classification: 'moderate', minimum_investment_display: '$1,000', currency_display: 'USD', disclaimer: 'Capital is at risk.' }]);
      return envelope({ uuid: 'request-1', status: 'pending' }, 201);
    });
    vi.stubGlobal('fetch', fetchMock);
    render(<MemoryRouter><ClientAuthProvider><ClientPlansPage /></ClientAuthProvider></MemoryRouter>);

    fireEvent.click(await screen.findByRole('button', { name: /managed income/i }));
    fireEvent.change(screen.getByLabelText('Requested amount'), { target: { value: '1000' } });
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getByRole('button', { name: 'Submit for Review' }));

    await screen.findByText(/submitted for review/i);
    const body = bodyFor(fetchMock, '/client/plan-requests');
    expect(body).toMatchObject({ investment_opportunity_uuid: 'plan-1', risk_acknowledged: true, requested_amount: '1000' });
  });

  it('shows client dashboard request status and approved plan reporting language', async () => {
    vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL) => {
      if (String(input).endsWith('/client/auth/refresh')) return envelope({ user: { uuid: 'client-1', email: 'ada@example.test', status: 'active', email_verified_at: '2026-09-13', profile: { first_name: 'Ada', last_name: 'Client' } }, access_token: 'client-token' });
      return envelope({ client: { uuid: 'client-1', email: 'ada@example.test', status: 'active', email_verified_at: '2026-09-13', profile: { first_name: 'Ada', last_name: 'Client' } }, summary: { active_investments: 1, pending_requests: 0, reported_balance: '1125.50', currency: 'USD', latest_snapshot_date: '2026-09-13' }, disclaimer: 'Balances are reporting records only, not wallets.', plan_requests: [{ uuid: 'request-1', status: 'approved', requested_amount: '1000.00', currency: 'USD', created_at: '2026-09-13', reviewed_at: '2026-09-13', plan_title: 'Managed Income', risk_classification: 'moderate' }], investment_accounts: [{ uuid: 'account-1', status: 'active', approved_amount: '1000.00', current_balance: '1125.50', currency: 'USD', approved_at: '2026-09-13', last_snapshot_at: '2026-09-13', plan_title: 'Managed Income', risk_classification: 'moderate' }], latest_snapshots: [{ account_uuid: 'account-1', plan_title: 'Managed Income', snapshots: [{ uuid: 'snapshot-1', snapshot_date: '2026-09-13', principal_amount: '1000.00', reported_value: '1125.50', growth_amount: '125.50', growth_percent: '12.5500', currency: 'USD', methodology_note: 'Approved snapshot.', approved_at: '2026-09-13' }] }] });
    }));
    render(<MemoryRouter><ClientAuthProvider><ClientDashboardPage /></ClientAuthProvider></MemoryRouter>);

    expect(await screen.findByRole('heading', { name: /welcome, ada client/i })).toBeInTheDocument();
    expect(screen.queryByText(/not wallets/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/not a guarantee of future returns/i)).not.toBeInTheDocument();
    expect(screen.getAllByText(/managed income/i).length).toBeGreaterThan(0);
    expect(screen.getAllByText(/1,125.50 USD/i).length).toBeGreaterThan(0);
    expect(screen.getByText(/12.5500%/i)).toBeInTheDocument();
  });

  it('supports admin pending request approval with a required reason field', async () => {
    const fetchMock = vi.fn(async (input: RequestInfo | URL, init?: RequestInit) => {
      const url = String(input);
      if (url.endsWith('/admin/clients')) return envelope([{ uuid: 'client-1', email: 'ada@example.test', status: 'active', first_name: 'Ada', last_name: 'Client', created_at: '2026-09-13' }]);
      if (url.includes('/admin/client-plan-requests?')) return envelope([{ uuid: 'request-1', client_uuid: 'client-1', email: 'ada@example.test', first_name: 'Ada', last_name: 'Client', plan_title: 'Managed Income', requested_amount: '1000.00', currency: 'USD', status: 'pending', created_at: '2026-09-13', reviewed_at: null, risk_classification: 'moderate' }]);
      if (init?.method === 'POST') return envelope({ uuid: 'request-1', status: 'approved' });
      return envelope([]);
    });
    vi.stubGlobal('fetch', fetchMock);
    render(<MemoryRouter><AdminClientsPage /></MemoryRouter>);

    await screen.findByText('Managed Income');
    fireEvent.change(screen.getByLabelText('Review reason'), { target: { value: 'Reviewed client request.' } });
    fireEvent.click(screen.getByRole('button', { name: 'Approve' }));

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith(expect.stringContaining('/admin/client-plan-requests/request-1/approve'), expect.objectContaining({ method: 'POST' })));
    expect(bodyFor(fetchMock, '/approve')).toMatchObject({ reason: 'Reviewed client request.' });
  });
});
