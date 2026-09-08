import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { fireEvent, render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, expect, it, vi } from 'vitest';
import { AuthContext, type AuthContextValue } from '../auth/authContext';
import { AdminEnquiriesPage } from './AdminEnquiriesPage';

afterEach(() => vi.unstubAllGlobals());
const enquiry = { uuid: 'test', name: 'Visitor', email: 'visitor@example.test', subject: 'Consultation', enquiry_type: 'consultation', message: '<script>untrusted</script>', status: 'new', consent_at: '2026-09-08', created_at: '2026-09-08' };
function renderPage(update = true) {
  const auth: AuthContextValue = { user: null, status: 'authenticated', sessionExpired: false, login: vi.fn(), logout: vi.fn(), can: permission => permission === 'enquiries.view' || (update && permission === 'enquiries.update') };
  const client = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });
  return render(<QueryClientProvider client={client}><AuthContext.Provider value={auth}><MemoryRouter><AdminEnquiriesPage /></MemoryRouter></AuthContext.Provider></QueryClientProvider>);
}

it('loads details, escapes messages and saves status through the API', async () => {
  let status = 'new';
  const fetchMock = vi.fn(async (url: string, init?: RequestInit) => {
    if (init?.method === 'PATCH') status = JSON.parse(String(init.body)).status;
    return new Response(JSON.stringify({ data: url.includes('/enquiries?') ? [{ ...enquiry, status }] : { ...enquiry, status }, meta: { page: 1, total: 1, total_pages: 1 } }));
  });
  vi.stubGlobal('fetch', fetchMock);
  renderPage();
  fireEvent.click(await screen.findByRole('button', { name: 'Consultation' }));
  expect(await screen.findByText('<script>untrusted</script>')).toBeInTheDocument();
  fireEvent.change(screen.getByLabelText('Update status'), { target: { value: 'in_progress' } });
  fireEvent.click(screen.getByRole('button', { name: 'Save status' }));
  expect(await screen.findByText('Current status: in progress')).toBeInTheDocument();
  expect(fetchMock.mock.calls.filter(([, init]) => init?.method === 'PATCH')).toHaveLength(1);
});

it('allows viewers to read without status controls and handles empty filters', async () => {
  vi.stubGlobal('fetch', vi.fn(async (url: string) => new Response(JSON.stringify({ data: url.includes('search=absent') ? [] : url.includes('/enquiries?') ? [enquiry] : enquiry, meta: { page: 1, total: 1, total_pages: 1 } }))));
  renderPage(false);
  fireEvent.click(await screen.findByRole('button', { name: 'Consultation' }));
  await screen.findByText('<script>untrusted</script>');
  expect(screen.queryByRole('button', { name: 'Save status' })).not.toBeInTheDocument();
  fireEvent.change(screen.getByLabelText('Search name, email or subject'), { target: { value: 'absent' } });
  fireEvent.click(screen.getByRole('button', { name: /^Search$/ }));
  expect(await screen.findByText('No enquiries match your filters.')).toBeInTheDocument();
});

