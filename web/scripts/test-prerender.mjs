import { createServer } from 'node:http';
import { spawn } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

// Explicitly synthetic public content; never used as production seed data.
const seo = { meta_title: 'Test publication', meta_description: 'Fixture for crawlability verification.', robots: 'index,follow' };
const server = createServer((request, response) => {
  const url = new URL(request.url, 'http://localhost');
  const slug = url.pathname.split('/').at(-1);
  let data = [];
  if (url.pathname.includes('/pages/')) data = { title: 'Test publication', slug, sections: [{ type: 'rich_text', content: { heading: 'Test section', body: '<p>Published fixture body.</p>' } }], seo };
  else if (url.pathname.includes('/legal/')) data = { title: 'Test legal publication', content: '<p>Test legal content only.</p>', version: '1', seo };
  else if (url.pathname.endsWith('/site-settings/public')) data = { risk_statement: '<p>Fixture risk statement.</p>', enquiry_consent: 'Fixture consent.' };
  else if (url.pathname.endsWith('/investments')) data = [{ uuid: 'test', slug: 'test-opportunity', title: 'Test opportunity', category_name: 'Research', risk_classification: 'high', short_description: 'Fixture catalogue information.' }];
  else if (slug === 'test-opportunity') data = { uuid: 'test', slug, title: 'Test opportunity', category_name: 'Research', risk_classification: 'high', disclaimer: '<p>Test risk content.</p>', full_description: '<p>Test opportunity detail.</p>', seo };
  else if (url.pathname.endsWith('/insights')) data = [{ uuid: 'article', slug: 'test-insight', title: 'Test insight', tags: [] }];
  else if (slug === 'test-insight') data = { uuid: 'article', slug, title: 'Test insight', author: 'Test author', content: '<p>Test insight detail.</p>', tags: [], seo: { ...seo, robots: 'noindex,follow' } };
  response.writeHead(200, { 'Content-Type': 'application/json' });
  response.end(JSON.stringify({ success: true, data, meta: { total_pages: 1 } }));
});
await new Promise(resolve => server.listen(4180, '127.0.0.1', resolve));
try {
  const child = spawn(process.execPath, ['scripts/prerender.mjs'], { stdio: 'inherit', env: { ...process.env, PRERENDER_API_URL: 'http://127.0.0.1:4180/api/v1', VITE_SITE_URL: 'https://example.test' } });
  const code = await new Promise(resolve => child.on('exit', resolve));
  assert.equal(code, 0);
  const html = await readFile('dist/about/index.html', 'utf8');
  assert.match(html, /Published fixture body/);
  assert.match(html, /rel="canonical" href="https:\/\/example.test\/about"/);
  const detail = await readFile('dist/investments/test-opportunity/index.html', 'utf8');
  assert.match(detail, /Test opportunity detail/);
  const sitemap = await readFile('dist/sitemap.xml', 'utf8');
  assert.match(sitemap, /investments\/test-opportunity/);
  assert.doesNotMatch(sitemap, /test-insight|\/admin|\/404/);
  console.log('Prerender crawlability and sitemap assertions passed. Fixture artifacts must not be deployed.');
} finally { server.close(); }
