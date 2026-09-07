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
});
