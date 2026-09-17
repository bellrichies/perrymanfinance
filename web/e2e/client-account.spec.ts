import { expect, test } from '@playwright/test';

test('client registration, plan request, admin approval, and client status display', async ({ page }) => {
  test.setTimeout(60000);
  let requestStatus = 'pending';
  let clientLoggedIn = false;
  await page.route('**/api/v1/**', async route => {
    const request = route.request();
    const path = new URL(request.url()).pathname;
    if (path.endsWith('/client/auth/refresh')) return clientLoggedIn
      ? route.fulfill({ json: { success: true, data: { access_token: 'client-token', user: { uuid: 'client-1', email: 'ada@example.test', status: 'active', email_verified_at: '2026-09-13', profile: { first_name: 'Ada', last_name: 'Client' } } } } })
      : route.fulfill({ status: 401, json: { success: false, error: { message: 'Expired' } } });
    if (path.endsWith('/client/auth/register')) return route.fulfill({ status: 201, json: { success: true, data: null, message: 'Account created.' } });
    if (path.endsWith('/client/auth/login')) {
      clientLoggedIn = true;
      return route.fulfill({ json: { success: true, data: { access_token: 'client-token', user: { uuid: 'client-1', email: 'ada@example.test', status: 'active', email_verified_at: '2026-09-13', profile: { first_name: 'Ada', last_name: 'Client' } } } } });
    }
    if (path.endsWith('/client/plans')) return route.fulfill({ json: { success: true, data: [{ uuid: 'plan-1', title: 'Managed Income', short_description: 'A reviewed plan.', risk_classification: 'moderate', minimum_investment_display: '$1,000', currency_display: 'USD', disclaimer: 'Capital is at risk.' }] } });
    if (path.endsWith('/client/plan-requests') && request.method() === 'POST') return route.fulfill({ status: 201, json: { success: true, data: { uuid: 'request-1', status: requestStatus } } });
    if (path.endsWith('/client/dashboard')) return route.fulfill({ json: { success: true, data: { client: {}, disclaimer: 'Balances are reporting records only. They are not wallets or guarantees of future returns.', plan_requests: [{ uuid: 'request-1', requested_amount: '1000.00', currency: 'USD', status: requestStatus, created_at: '2026-09-13', reviewed_at: requestStatus === 'approved' ? '2026-09-13' : null, plan_title: 'Managed Income', risk_classification: 'moderate' }], investment_accounts: requestStatus === 'approved' ? [{ uuid: 'account-1', status: 'active', approved_amount: '1000.00', currency: 'USD', approved_at: '2026-09-13', plan_title: 'Managed Income', risk_classification: 'moderate' }] : [] } } });
    if (path.endsWith('/admin/auth/refresh')) return route.fulfill({ status: 401, json: { success: false } });
    if (path.endsWith('/admin/auth/login')) return route.fulfill({ json: { success: true, data: { access_token: 'admin-token', user: { id: 1, email: 'admin@example.test', display_name: 'Admin User', roles: ['ops'], permissions: ['clients.view', 'client_plans.review'] } } } });
    if (path.endsWith('/admin/clients')) return route.fulfill({ json: { success: true, data: [{ uuid: 'client-1', email: 'ada@example.test', status: 'active', first_name: 'Ada', last_name: 'Client', created_at: '2026-09-13' }] } });
    if (path.endsWith('/admin/client-plan-requests')) return route.fulfill({ json: { success: true, data: requestStatus === 'pending' ? [{ uuid: 'request-1', client_uuid: 'client-1', email: 'ada@example.test', first_name: 'Ada', last_name: 'Client', plan_title: 'Managed Income', requested_amount: '1000.00', currency: 'USD', status: 'pending', created_at: '2026-09-13', reviewed_at: null, risk_classification: 'moderate' }] : [] } });
    if (path.endsWith('/admin/client-plan-requests/request-1/approve')) {
      requestStatus = 'approved';
      return route.fulfill({ json: { success: true, data: { uuid: 'request-1', status: 'approved' } } });
    }
    return route.fulfill({ json: { success: true, data: [] } });
  });

  await page.goto('/client/register', { waitUntil: 'domcontentloaded' });
  await page.getByLabel('First name').fill('Ada');
  await page.getByLabel('Last name').fill('Client');
  await page.getByLabel('Email').fill('ada@example.test');
  await page.getByLabel('Password', { exact: true }).fill('Secure-client-123');
  await page.getByLabel('Confirm password').fill('Secure-client-123');
  await page.getByRole('checkbox').check();
  await page.getByRole('button', { name: 'Create Account' }).click();
  await expect(page.getByText(/check your email/i)).toBeVisible();

  await page.goto('/client/login', { waitUntil: 'domcontentloaded' });
  await page.getByLabel('Email').fill('ada@example.test');
  await page.getByLabel('Password').fill('Secure-client-123');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page).toHaveURL(/\/client$/);
  await page.getByRole('link', { name: 'Plans' }).click();
  await page.getByRole('button', { name: /managed income/i }).click();
  await page.getByLabel('Requested amount').fill('1000');
  await page.getByRole('checkbox').check();
  await page.getByRole('button', { name: 'Submit for Review' }).click();
  await expect(page.getByText(/submitted for review/i)).toBeVisible();
  await page.goto('/client', { waitUntil: 'domcontentloaded' });
  await expect(page.getByText('Pending Review')).toBeVisible();

  await page.goto('/admin/login', { waitUntil: 'domcontentloaded' });
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password').fill('Correct-password-123');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await page.getByRole('link', { name: 'Client Plan Requests' }).click();
  await page.getByLabel('Review reason').fill('Reviewed client request and risk acknowledgement.');
  await page.getByRole('button', { name: 'Approve' }).click();
  await expect(page.getByText('Request approved.')).toBeVisible();

  await page.goto('/client', { waitUntil: 'domcontentloaded' });
  await expect(page.getByText(/Approved on/)).toBeVisible();
  await expect(page.getByText(/not wallets or guarantees/i)).toBeVisible();
});
