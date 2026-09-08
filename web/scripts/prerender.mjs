import { chromium } from '@playwright/test';
import { preview } from 'vite';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const api = process.env.PRERENDER_API_URL;
const site = process.env.VITE_SITE_URL;
if (!api || !site || new URL(site).protocol !== 'https:' || new URL(site).origin !== site.replace(/\/$/, '')) {
  throw new Error('Set PRERENDER_API_URL and VITE_SITE_URL (approved HTTPS origin) for release generation.');
}
async function get(endpoint) {
  const response = await fetch(`${api.replace(/\/$/, '')}${endpoint}`, { signal: AbortSignal.timeout(15000) });
  if (!response.ok) throw new Error(`Public content unavailable: ${endpoint} (${response.status})`);
  const body = await response.json();
  if (!body.success) throw new Error(`Invalid public response: ${endpoint}`);
  return body;
}
const routes = new Map();
for (const slug of ['home', 'about', 'investment-solutions', 'digital-assets', 'wealth-management', 'how-it-works', 'contact']) {
  const { data } = await get(`/pages/${slug}`);
  routes.set(slug === 'home' ? '/' : `/${slug}`, data.seo);
}
for (const slug of ['terms', 'privacy-policy', 'risk-disclosure', 'cookie-policy']) {
  const { data } = await get(`/legal/${slug}`);
  routes.set(`/${slug}`, data.seo);
}
for (const collection of ['investments', 'insights']) {
  routes.set(`/${collection}`, (await get(`/pages/${collection}`)).data.seo);
  let page = 1, total = 1;
  do {
    const response = await get(`/${collection}?page=${page}&per_page=100`);
    total = response.meta.total_pages;
    for (const item of response.data) {
      if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(item.slug)) throw new Error('Invalid published slug.');
      const detail = await get(`/${collection}/${item.slug}`);
      routes.set(`/${collection}/${item.slug}`, detail.data.seo);
    }
    page++;
  } while (page <= total);
}
routes.set('/faq', (await get('/pages/faq')).data.seo);
await get('/site-settings/public');
const server = await preview({ preview: { host: '127.0.0.1', port: 4178, strictPort: true } });
const browser = await chromium.launch({ channel: 'chromium' });
const template = await readFile('dist/index.html', 'utf8');
const sitemap = [];
try {
  for (const [route, seo] of [...routes, ['/404', null]]) {
    if (route !== '/404' && (!seo?.meta_title || !seo?.meta_description)) throw new Error(`Missing approved SEO metadata: ${route}`);
    const page = await browser.newPage();
    await page.route('**/api/v1/**', async (request) => {
      const suffix = new URL(request.request().url()).pathname.replace('/api/v1', '') + new URL(request.request().url()).search;
      if (request.request().method() !== 'GET' || suffix.startsWith('/admin')) return request.abort();
      const response = await get(suffix);
      return request.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(response) });
    });
    // Always use the original application shell, including when regenerating an existing route.
    await page.route(`http://127.0.0.1:4178${route}`, request => request.fulfill({ contentType: 'text/html', body: template }));
    await page.goto(`http://127.0.0.1:4178${route}`, { waitUntil: 'networkidle' });
    await page.locator('main h1').waitFor();
    if (await page.getByRole('alert').count() || await page.getByText('Loading content', { exact: true }).count()) throw new Error(`Incomplete rendered page: ${route}`);
    const canonical = seo?.canonical_url || `${site.replace(/\/$/, '')}${route}`;
    const robots = route === '/404' ? 'noindex,nofollow' : seo.robots;
    await page.evaluate(({ canonical, robots }) => {
      let link = document.querySelector('link[rel="canonical"]');
      if (!link) { link = document.createElement('link'); link.setAttribute('rel', 'canonical'); document.head.append(link); }
      link.setAttribute('href', canonical);
      document.querySelector('meta[name="robots"]')?.setAttribute('content', robots);
      document.querySelectorAll('head title, head meta:not([charset]):not([name="viewport"]), head link[rel="canonical"]').forEach(node => node.setAttribute('data-prerender', 'true'));
    }, { canonical, robots });
    const html = '<!doctype html>\n' + await page.locator('html').evaluate(node => node.outerHTML);
    const file = route === '/404' ? 'dist/404.html' : path.join('dist', route.slice(1), 'index.html');
    await mkdir(path.dirname(file), { recursive: true });
    await writeFile(file, html);
    if (!robots?.includes('noindex') && route !== '/404') sitemap.push(canonical);
    await page.close();
  }
  await writeFile('dist/sitemap.xml', `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${sitemap.map(url => `<url><loc>${url}</loc></url>`).join('')}</urlset>`);
  await writeFile('dist/robots.txt', `User-agent: *\nDisallow: /admin\nSitemap: ${site.replace(/\/$/, '')}/sitemap.xml\n`);
  console.log(`Prerendered ${routes.size + 1} public routes.`);
} finally {
  await browser.close();
  await new Promise(resolve => server.httpServer.close(resolve));
}
