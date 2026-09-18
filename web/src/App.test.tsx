import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { App } from './App';

afterEach(() => { vi.unstubAllGlobals(); window.history.replaceState({}, '', '/'); });

describe('admin authentication', () => {
  it('renders login after session restoration fails and signs in', async () => {
    window.history.replaceState({}, '', '/admin/login');
    const user = { id: 'uuid', email: 'admin@example.test', display_name: 'Test Admin', roles: ['viewer'], permissions: ['pages.view'] };
    const fetchMock = vi.fn()
      .mockResolvedValueOnce(new Response(JSON.stringify({ success: false, error: { message: 'Expired', fields: {} } }), { status: 401 }))
      .mockResolvedValueOnce(new Response(JSON.stringify({ success: true, data: { user, access_token: 'access', token_type: 'Bearer', expires_in: 900 }, meta: {}, message: null }), { status: 200 }));
    vi.stubGlobal('fetch', fetchMock);
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    render(<QueryClientProvider client={queryClient}><App /></QueryClientProvider>);
    const email = await screen.findByLabelText(/email address/i);
    fireEvent.change(email, { target: { value: 'admin@example.test' } });
    fireEvent.change(screen.getByLabelText(/password/i), { target: { value: 'Correct-password-123' } });
    fireEvent.click(screen.getByRole('button', { name: /^sign in$/i }));
    expect(await screen.findByRole('heading', { name: /dashboard/i })).toBeInTheDocument();
    expect(screen.getByText(/signed in as test admin/i)).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Pages' })).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Users' })).not.toBeInTheDocument();
  });

  it('shows the page catalogue with loading and success states', async () => {
    window.history.replaceState({}, '', '/admin/pages');
    const user = { id: 'uuid', email: 'editor@example.test', display_name: 'Editor', roles: ['editor'], permissions: ['pages.view', 'pages.update'] };
    vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL) => {
      const url = String(input);
      if (url.endsWith('/admin/auth/refresh')) {
        return new Response(JSON.stringify({ success: true, data: { user, access_token: 'access', token_type: 'Bearer', expires_in: 900 }, meta: {}, message: null }), { status: 200 });
      }
      return new Response(JSON.stringify({ success: true, data: [{ id: 1, uuid: 'page-uuid', title: 'About', slug: 'about', page_type: 'marketing', status: 'draft', excerpt: null, updated_at: '2026-09-07' }], meta: {}, message: null }), { status: 200 });
    }));
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    render(<QueryClientProvider client={queryClient}><App /></QueryClientProvider>);
    expect(await screen.findByRole('heading', { name: 'Pages' })).toBeInTheDocument();
    expect(await screen.findByRole('button', { name: /about/i })).toHaveTextContent('draft');
  });
});

describe('client authentication', () => {
  it('restores a valid client session on direct protected route loads without changing the current URL', async () => {
    window.history.replaceState({}, '', '/client/plans');
    const user = { uuid: 'client-1', email: 'client@example.test', status: 'active', email_verified_at: '2026-09-13', profile: { first_name: 'Ada', last_name: 'Client' } };
    vi.stubGlobal('fetch', vi.fn(async (input: RequestInfo | URL) => {
      const url = String(input);
      if (url.endsWith('/client/auth/refresh')) {
        return new Response(JSON.stringify({ success: true, data: { user, access_token: 'client-access', token_type: 'Bearer', expires_in: 900 }, meta: {}, message: null }), { status: 200 });
      }
      if (url.endsWith('/client/plans')) {
        return new Response(JSON.stringify({ success: true, data: [], meta: {}, message: null }), { status: 200 });
      }
      return new Response(JSON.stringify({ success: false, error: { message: 'Unexpected request', fields: {} } }), { status: 404 });
    }));
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    render(<QueryClientProvider client={queryClient}><App /></QueryClientProvider>);

    expect(await screen.findByRole('heading', { name: 'Select Plan' })).toBeInTheDocument();
    expect(window.location.pathname).toBe('/client/plans');
  });
});
