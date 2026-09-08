import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
  await page.route('**/api/v1/**', async route => {
    const path = new URL(route.request().url()).pathname;
    let data: unknown = [];
    if (path.endsWith('/site-settings/public')) data = { risk_statement: '<p>Test risk statement.</p>', enquiry_consent: 'I consent to a response to this enquiry.' };
    else if (path.includes('/pages/')) {
      const slug = path.split('/').at(-1)!;
      const names: Record<string, string> = { home: 'Considered perspectives', about: 'About', 'investment-solutions': 'Investment Solutions', 'digital-assets': 'Digital Assets', 'wealth-management': 'Wealth Management', 'how-it-works': 'How It Works', contact: 'Request information' };
      data = { title: names[slug] ?? slug, slug, sections: slug === 'home' ? [{ type: 'hero', content: { heading: 'Considered perspectives', body: 'Test content from the public API.' } }] : [{ type: 'rich_text', content: { heading: 'Our perspective', body: '<p>Published test content.</p>' } }], seo: { meta_title: names[slug] ?? slug, meta_description: 'Test description', robots: 'index,follow' } };
    } else if (path.includes('/legal/')) data = { title: 'Published legal document', version: '1.0', effective_at: '2026-09-01', content: '<p>Legal test fixture only.</p>' };
    else if (path.endsWith('/investments/test')) data = { uuid: 'test', title: 'Test opportunity', risk_classification: 'high', full_description: '<p>Test detail.</p>', disclaimer: '<p>Test risk notice.</p>' };
    else if (path.endsWith('/insights/test')) data = { uuid: 'test', title: 'Test insight', content: '<p>Test article.</p>', tags: [] };
    else if (path === '/api/v1/enquiries') {
      return route.fulfill({ status: 202, json: { success: true, data: null } });
    }
    await route.fulfill({ json: { success: true, data, meta: { total_pages: 1 } } });
  });
});

test('homepage order, API content, and mobile keyboard navigation', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Considered perspectives');
  await expect(page.locator('main > section h2, main > div > section h2')).toHaveText(['Our perspective', 'Investment solutions', 'Investment philosophy', 'Featured opportunities', 'How it works', 'Risk management', 'Featured insights', 'Request information']);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
  await page.getByRole('button', { name: 'Menu', exact: true }).click();
  await expect(page.getByRole('navigation', { name: 'Mobile navigation' })).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('button', { name: 'Menu', exact: true })).toBeFocused();
  await page.getByRole('button', { name: 'Menu', exact: true }).click();
  await page.getByRole('navigation', { name: 'Mobile navigation' }).getByRole('link', { name: 'About' }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('About');
});

test('all public routes render one main landmark and a heading', async ({ page }) => {
  for (const route of ['/about', '/investment-solutions', '/digital-assets', '/wealth-management', '/how-it-works', '/investments', '/investments/test', '/insights', '/insights/test', '/faq', '/contact', '/terms', '/privacy-policy', '/risk-disclosure', '/cookie-policy', '/missing']) {
    await page.goto(route);
    await expect(page.getByRole('main')).toHaveCount(1, { timeout: 15000 });
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.getByRole('contentinfo')).toBeVisible();
  }
  await expect(page.getByRole('heading', { name: 'Page not found' })).toBeVisible();
  await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex,nofollow');
});

test('enquiry submission uses consent and prevents duplicate sends', async ({ page }) => {
  await page.goto('/contact?investment=test-opportunity');
  await page.getByLabel('Name', { exact: true }).fill('Test Visitor');
  await page.getByLabel('Email address').fill('visitor@example.test');
  await page.getByLabel('Message', { exact: true }).fill('Please provide more information.');
  await page.getByRole('checkbox').check();
  const request = page.waitForRequest(request => request.url().endsWith('/enquiries'));
  await page.getByRole('button', { name: 'Send enquiry' }).click();
  expect((await request).postDataJSON()).toMatchObject({ consent: true, subject: 'Information about test-opportunity', source_page: '/contact?investment=test-opportunity' });
  await expect(page.getByRole('status')).toHaveText('Thank you. Your enquiry has been received.');
  await expect(page.getByRole('button', { name: 'Send enquiry' })).toHaveCount(0);
});

test('network errors offer retry and unpublished pages do not invent copy', async ({ page }) => {
  await page.route('**/api/v1/pages/about', route => route.fulfill({ status: 500, json: { success: false } }));
  await page.goto('/about');
  await expect(page.getByRole('button', { name: 'Try again' })).toBeVisible();
  await page.route('**/api/v1/pages/about', route => route.fulfill({ status: 404, json: { success: false } }));
  await page.getByRole('button', { name: 'Try again' }).click();
  await expect(page.getByText('This page has not been published yet.')).toBeVisible();
});

test('consultation validation focuses an accessible error summary before sending', async ({ page }) => {
  let submissions = 0;
  page.on('request', request => { if (request.method() === 'POST' && request.url().endsWith('/enquiries')) submissions++; });
  await page.goto('/contact?type=consultation');
  await expect(page.getByLabel('Reason for enquiry')).toHaveValue('consultation');
  await page.getByRole('button', { name: 'Send enquiry' }).click();
  await expect(page.getByText('Please correct the following fields:')).toBeVisible();
  await expect(page.locator('#name')).toHaveAttribute('aria-invalid', 'true');
  await expect(page.locator('div[tabindex="-1"]').filter({ hasText: 'Please correct the following fields:' })).toBeFocused();
  expect(submissions).toBe(0);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});
