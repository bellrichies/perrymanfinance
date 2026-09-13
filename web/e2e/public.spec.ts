import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
  await page.route('**/api/v1/**', async route => {
    const path = new URL(route.request().url()).pathname;
    let data: unknown = [];
    if (path.endsWith('/site-settings/public')) data = { risk_statement: '<p>Test risk statement.</p>', enquiry_consent: 'I consent to a response to this enquiry.' };
    else if (path.includes('/pages/')) {
      const slug = path.split('/').at(-1)!;
      const names: Record<string, string> = { home: 'PerrymanFinance', about: 'About', 'digital-assets': 'Digital Assets', 'wealth-management': 'Wealth Management', 'how-it-works': 'How It Works', contact: 'Client Services' };
      const aboutSections = [
        { type: 'rich_text', content: { heading: 'Financial services for considered decisions', body: '<p>PerrymanFinance is a financial services platform focused on investment solutions, wealth-management context, securities-market perspective, and digital asset research.</p>' } },
        { type: 'feature_grid', content: { heading: 'Our service focus', items: [{ title: 'Investment solutions', body: 'Review investment themes through objectives, time horizon, liquidity, and risk.', href: '/investment-solutions' }, { title: 'Wealth management', body: 'Frame longer-term financial priorities with portfolio context.', href: '/wealth-management' }, { title: 'Digital asset management', body: 'Examine digital asset exposure with market and operational context.', href: '/digital-assets' }] } },
        { type: 'rich_text', content: { heading: 'Our mission and business objective', body: '<p>We support informed financial conversations through accessible market education and disciplined client engagement.</p>' } },
      ];
      const journeySection = { type: 'client_journey', content: { heading: 'Start with context. Move forward with clarity.', body: '<p>A structured route from research to the appropriate next conversation.</p>', items: [{ title: 'Explore the relevant information', body: '<p>Review services and risk information.</p>', href: '/investment-solutions' }, { title: 'Frame your objectives and constraints', body: '<p>Consider priorities and time horizon.</p>', href: '/wealth-management' }, { title: 'Review the relevant risks', body: '<p>Read risk information.</p>', href: '/risk-disclosure' }, { title: 'Request a consultation or support conversation', body: '<p>Tell us how we can help.</p>', href: '/contact?type=consultation' }, { title: 'Discuss the appropriate next steps', body: '<p>Route the conversation.</p>', href: '/faq' }, { title: 'Proceed only through approved processes', body: '<p>Formal processes require appropriate controls.</p>', href: '/terms' }], checkpoints: [{ title: 'Is the objective clear?', body: '<p>Start with your question.</p>', href: '/faq' }, { title: 'Is the risk understood?', body: '<p>Capital is at risk.</p>', href: '/risk-disclosure' }, { title: 'Is the information complete enough to discuss?', body: '<p>Share only what is needed.</p>', href: '/privacy-policy' }], preparation: [{ title: 'Your question', body: '<p>Tell us what you want to explore.</p>' }, { title: 'Your priorities', body: '<p>Include your key considerations.</p>' }, { title: 'Your preferred contact details', body: '<p>Tell us how to respond.</p>' }] } };
      const serviceSection = (variant: string, heading: string, item: string) => ({ type: 'service_experience', content: { variant, eyebrow: names[slug], heading, body: '<p>A detailed, risk-aware service fixture for informed client conversations.</p>', items: [{ title: item, body: '<p>Relevant decision factor.</p>', href: '/risk-disclosure' }, { title: 'Portfolio context', body: '<p>Consider the wider financial picture.</p>', href: '/wealth-management' }], principles: [{ title: 'Research before reaction', body: '<p>Information supports better questions.</p>' }, { title: 'Risk stays visible', body: '<p>Capital is at risk.</p>' }, { title: 'Clear review points', body: '<p>Revisit priorities over time.</p>' }], questions: [{ title: 'What is the objective?', body: '<p>Start with context.</p>' }, { title: 'What must remain flexible?', body: '<p>Consider liquidity.</p>' }, { title: 'Who should be part of the conversation?', body: '<p>Consider stakeholders.</p>' }] } });
      data = { title: names[slug] ?? slug, slug, sections: slug === 'home' ? [{ type: 'hero', content: { heading: 'Clearer investment decisions begin with disciplined advice', body: '<p>Investment solutions and wealth-management information for an informed client conversation.</p>' } }] : slug === 'about' ? aboutSections : slug === 'how-it-works' ? [journeySection, { type: 'cta', content: { heading: 'Ready to discuss your priorities?', body: '<p>Request a consultation.</p>' } }] : slug === 'digital-assets' ? [serviceSection('digital-assets', 'Digital asset exposure deserves a wider lens.', 'Market structure and liquidity')] : slug === 'wealth-management' ? [serviceSection('wealth-management', 'A financial plan should reflect the whole picture.', 'Objectives and time horizon')] : [{ type: 'rich_text', content: { heading: 'Our perspective', body: '<p>Published page fixture.</p>' } }], seo: { meta_title: names[slug] ?? slug, meta_description: 'Page fixture description', robots: 'index,follow' } };
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
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Clearer investment decisions begin with disciplined advice');
  await expect(page.locator('main > section h2, main > div > section h2')).toHaveText(['A financial plan should reflect the whole picture', 'Market intelligence for better portfolio questions', 'A considered approach to modern wealth', 'Financial services built around your objectives', 'Investment decisions deserve more than a market view', 'Explore investment opportunities with context', 'Risk management belongs at the start of the conversation', 'Talk to PerrymanFinance about your priorities']);
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
  test.setTimeout(60000);
  for (const route of ['/about', '/digital-assets', '/wealth-management', '/how-it-works', '/investments', '/investments/test', '/insights', '/insights/test', '/faq', '/contact', '/terms', '/privacy-policy', '/risk-disclosure', '/cookie-policy', '/missing']) {
    await page.goto(route);
    await expect(page.getByRole('main')).toHaveCount(1, { timeout: 15000 });
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.getByRole('contentinfo')).toBeVisible();
  }
  await expect(page.getByRole('heading', { name: 'Page not found' })).toBeVisible();
  await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', 'noindex,nofollow');
});

test('legal routes provide a readable CMS document shell and related legal navigation', async ({ page }) => {
  await page.goto('/risk-disclosure');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Published legal document');
  await expect(page.getByRole('navigation', { name: 'Legal documents' })).toBeVisible();
  await expect(page.getByRole('navigation', { name: 'Legal documents' }).getByRole('link', { name: 'Risk disclosure' })).toHaveAttribute('aria-current', 'page');
  await expect(page.getByText('Version 1.0')).toBeVisible();
  await expect(page.getByText('Legal test fixture only.')).toBeVisible();
  await expect(page.getByRole('link', { name: /request consultation/i })).toHaveAttribute('href', '/contact?type=consultation');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});

test('about page presents company identity, service focus, and mission without promotional claims', async ({ page }) => {
  await page.goto('/about');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('About');
  await expect(page.getByRole('heading', { name: 'Financial services for considered decisions' })).toBeVisible();
  await expect(page.getByText(/financial services platform focused on investment solutions/i)).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Our service focus' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Investment solutions' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Our mission and business objective' })).toBeVisible();
  await expect(page.getByText(/disciplined client engagement/i)).toBeVisible();
});

test('how it works presents a clear, non-transactional client journey', async ({ page }) => {
  await page.goto('/how-it-works');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Start with context. Move forward with clarity.');
  await expect(page.getByRole('navigation', { name: 'Journey stages' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'An informed process, at your pace' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Explore the relevant information' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Questions worth resolving before a next step' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'A website visit is the beginning of a conversation' })).toBeVisible();
  await expect(page.getByText(/does not open an account, create an investment, move funds or assets/i)).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});

test('wealth and digital asset pages present distinct, risk-aware service experiences', async ({ page }) => {
  await page.goto('/wealth-management');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('A financial plan should reflect the whole picture.');
  await expect(page.getByRole('heading', { name: 'Objectives and time horizon' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Explore first. Proceed through the appropriate process.' })).toBeVisible();
  await page.goto('/digital-assets');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Digital asset exposure deserves a wider lens.');
  await expect(page.getByRole('heading', { name: 'Market structure and liquidity' })).toBeVisible();
  await expect(page.getByText(/do not provide a personal recommendation, guarantee an outcome/i)).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
});

test('FAQ page groups client questions and exposes answers through keyboard-accessible disclosures', async ({ page }) => {
  await page.route('**/api/v1/faq', route => route.fulfill({ json: { success: true, data: [
    { id: 1, question: 'What does PerrymanFinance do?', answer: '<p>Client-facing service information.</p>', category: 'general' },
    { id: 2, question: 'Are investment returns guaranteed?', answer: '<p>No. Capital is at risk.</p>', category: 'investment-opportunities' },
  ] } }));
  await page.goto('/faq');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Frequently asked questions');
  await expect(page.getByRole('heading', { name: 'General' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Investment Opportunities' })).toBeVisible();
  const question = page.locator('summary').filter({ hasText: 'What does PerrymanFinance do?' });
  await expect(question).toHaveCount(1);
  await question.press('Enter');
  await expect(page.getByText('Client-facing service information.')).toBeVisible();
  await expect(page.getByRole('link', { name: 'Contact client services' })).toHaveAttribute('href', '/contact');
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

test('investment list and detail expose catalogue data and risk notices', async ({ page }) => {
  await page.unroute('**/api/v1/**');
  await page.route('**/api/v1/investments?**', route => route.fulfill({ json: { success: true, data: [
    { uuid: 'inv-1', title: 'Core income strategy', slug: 'core-income', short_description: 'A reviewed opportunity summary.', full_description: '<p>Reviewed detail.</p>', risk_classification: 'moderate', category_name: 'Diversified', disclaimer: '<p>Capital is at risk.</p>' },
  ], meta: { total_pages: 1 } } }));
  await page.route('**/api/v1/investments/core-income', route => route.fulfill({ json: { success: true, data: { uuid: 'inv-1', title: 'Core income strategy', slug: 'core-income', short_description: 'A reviewed opportunity summary.', full_description: '<p>Reviewed detail.</p>', risk_classification: 'moderate', category_name: 'Diversified', disclaimer: '<p>Capital is at risk.</p>' } } }));
  await page.route('**/api/v1/investment-categories', route => route.fulfill({ json: { success: true, data: [{ id: 1, name: 'Diversified', slug: 'diversified' }] } }));
  await page.route('**/api/v1/site-settings/public', route => route.fulfill({ json: { success: true, data: { risk_statement: '<p>Test risk statement.</p>' } } }));

  await page.goto('/investments');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Investment opportunities');
  await page.getByRole('link', { name: /core income strategy/i }).click();
  await expect(page).toHaveURL(/\/investments\/core-income$/);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Core income strategy');
  await expect(page.getByText('Capital is at risk.')).toBeVisible();
});

test('insights list and detail expose article content', async ({ page }) => {
  await page.unroute('**/api/v1/**');
  await page.route('**/api/v1/insights?**', route => route.fulfill({ json: { success: true, data: [
    { uuid: 'art-1', title: 'Market structure note', slug: 'market-structure-note', excerpt: 'Research summary.', content: '<p>Reviewed article body.</p>', category_name: 'Research', tags: [], author: 'Admin' },
  ], meta: { total_pages: 1 } } }));
  await page.route('**/api/v1/insights/market-structure-note', route => route.fulfill({ json: { success: true, data: { uuid: 'art-1', title: 'Market structure note', slug: 'market-structure-note', excerpt: 'Research summary.', content: '<p>Reviewed article body.</p>', category_name: 'Research', tags: [], related: [], author: 'Admin' } } }));
  await page.route('**/api/v1/insights?featured=1**', route => route.fulfill({ json: { success: true, data: [], meta: { total_pages: 1 } } }));
  await page.route('**/api/v1/insight-categories', route => route.fulfill({ json: { success: true, data: [{ id: 1, name: 'Research', slug: 'research' }] } }));
  await page.route('**/api/v1/tags', route => route.fulfill({ json: { success: true, data: [] } }));
  await page.route('**/api/v1/site-settings/public', route => route.fulfill({ json: { success: true, data: { risk_statement: '<p>Test risk statement.</p>' } } }));

  await page.goto('/insights');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Insights');
  await page.getByRole('link', { name: /market structure note/i }).click();
  await expect(page).toHaveURL(/\/insights\/market-structure-note$/);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Market structure note');
  await expect(page.getByText('Reviewed article body.')).toBeVisible();
});

test('admin login and create-edit-publish content workflows submit expected payloads', async ({ page }) => {
  await page.unroute('**/api/v1/**');
  const requests: Array<{ path: string; method: string; body: unknown }> = [];
  const pageRecord = { uuid: 'page-1', title: 'Staging page', slug: 'staging-page', page_type: 'marketing', status: 'draft', excerpt: 'Draft page.', sections: [{ type: 'hero', content: { heading: 'Staging page' } }], updated_at: '2026-09-12T00:00:00Z' };
  const articleRecord = { uuid: 'article-1', title: 'Staging article', slug: 'staging-article', category_id: 1, category_name: 'Research', category_slug: 'research', excerpt: 'Draft article.', content: '<p>Draft.</p>', author: 'Admin', tags: [], tag_ids: [], featured: false, status: 'draft', published_at: null };
  const investmentRecord = { uuid: 'investment-1', title: 'Staging opportunity', slug: 'staging-opportunity', category_id: 1, category_name: 'Diversified', category_slug: 'diversified', short_description: 'Draft opportunity.', full_description: '<p>Draft.</p>', strategy_summary: '', investment_objective: '', investment_horizon: '', risk_classification: 'moderate', minimum_investment_display: '', currency_display: '', featured: false, status: 'draft', disclaimer: '<p>Capital is at risk.</p>', published_at: null };

  await page.route('**/api/v1/**', async route => {
    const request = route.request();
    const path = new URL(request.url()).pathname;
    if (request.method() !== 'GET') requests.push({ path, method: request.method(), body: request.postDataJSON() ?? null });
    if (path.endsWith('/admin/auth/refresh')) return route.fulfill({ status: 401, json: { success: false } });
    if (path.endsWith('/admin/auth/login')) return route.fulfill({ json: { success: true, data: { access_token: 'access-token', user: { id: 1, email: 'admin@example.test', display_name: 'Admin User', roles: ['administrator'], permissions: ['pages.*', 'pages.view', 'articles.*', 'articles.view', 'investments.*', 'investments.view', 'legal.manage'] } } } });
    if (path.endsWith('/admin/pages')) return route.fulfill({ json: { success: true, data: request.method() === 'GET' ? [] : { ...pageRecord, ...(request.postDataJSON() as object), uuid: 'page-1' } } });
    if (path.endsWith('/admin/pages/page-1')) return route.fulfill({ json: { success: true, data: { ...pageRecord, ...(request.postDataJSON() as object) } } });
    if (path.endsWith('/insight-categories')) return route.fulfill({ json: { success: true, data: [{ id: 1, name: 'Research', slug: 'research' }] } });
    if (path.endsWith('/tags')) return route.fulfill({ json: { success: true, data: [] } });
    if (path.endsWith('/admin/articles')) return route.fulfill({ json: { success: true, data: request.method() === 'GET' ? [] : { ...articleRecord, ...(request.postDataJSON() as object), uuid: 'article-1', tags: [] } } });
    if (path.endsWith('/investment-categories')) return route.fulfill({ json: { success: true, data: [{ id: 1, name: 'Diversified', slug: 'diversified', position: 1 }] } });
    if (path.endsWith('/admin/investments')) return route.fulfill({ json: { success: true, data: request.method() === 'GET' ? [] : { ...investmentRecord, ...(request.postDataJSON() as object), uuid: 'investment-1' } } });
    return route.fulfill({ json: { success: true, data: [], meta: { total_pages: 1 } } });
  });

  await page.goto('/admin/login');
  await page.getByLabel('Email address').fill('admin@example.test');
  await page.getByLabel('Password').fill('Correct-password-123');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page).toHaveURL(/\/admin$/);

  await page.getByRole('link', { name: 'Pages' }).click();
  await page.getByRole('button', { name: 'New page' }).click();
  await page.getByLabel('Title', { exact: true }).fill('Staging page');
  await page.getByLabel('Slug').fill('staging-page');
  await page.getByLabel('Excerpt').fill('Draft page.');
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByRole('status')).toHaveText('Page saved.');
  await page.getByRole('button', { name: 'Submit for review' }).click();
  await page.getByRole('button', { name: 'Publish' }).click();
  await page.getByRole('button', { name: 'Save' }).click();

  await page.getByRole('link', { name: 'Articles' }).click();
  await page.getByRole('button', { name: 'New article' }).click();
  await page.getByLabel('Title', { exact: true }).fill('Staging article');
  await page.getByLabel('Slug').fill('staging-article');
  await page.getByLabel('Category').selectOption('1');
  await page.getByLabel('Excerpt').fill('Draft article.');
  await page.getByLabel('Reviewed content (HTML)').fill('<p>Reviewed article.</p>');
  await page.getByLabel('Meta title').fill('Staging article');
  await page.getByLabel('Meta description').fill('Reviewed article metadata.');
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByRole('status')).toHaveText('Article saved.');

  await page.getByRole('link', { name: 'Investments' }).click();
  await page.getByRole('button', { name: 'New opportunity' }).click();
  await page.getByLabel('Title', { exact: true }).fill('Staging opportunity');
  await page.getByLabel('Slug').fill('staging-opportunity');
  await page.getByLabel('Category').selectOption('1');
  await page.getByLabel('Short description').fill('Draft opportunity.');
  await page.getByLabel('Reviewed full description (HTML)').fill('<p>Reviewed opportunity.</p>');
  await page.getByLabel('Required disclaimer').fill('<p>Capital is at risk.</p>');
  await page.getByLabel('Meta title').fill('Staging opportunity');
  await page.getByLabel('Meta description').fill('Reviewed opportunity metadata.');
  await page.getByRole('button', { name: 'Save' }).click();
  await expect(page.getByRole('status')).toHaveText('Investment opportunity saved.');

  expect(requests.some(request => request.path.endsWith('/admin/auth/login') && request.method === 'POST')).toBe(true);
  expect(requests.some(request => request.path.endsWith('/admin/pages/page-1') && request.method === 'PATCH' && (request.body as { status?: string })?.status === 'published')).toBe(true);
  expect(requests.some(request => request.path.endsWith('/admin/articles') && request.method === 'POST')).toBe(true);
  expect(requests.some(request => request.path.endsWith('/admin/investments') && request.method === 'POST')).toBe(true);
});
