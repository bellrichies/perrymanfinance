# Public website and enquiry delivery

The public experience uses the existing PHP repository/service/controller APIs and React Query services. The shared public layout provides navigation, mobile menu, footer, skip link and risk-disclosure access. All specified public routes are registered, including a public 404; admin routes remain separately authenticated and lazy loaded. Financial copy is loaded from published CMS records. No financial execution features are introduced.

## Content contract

Publish pages with slugs `home`, `about`, `investment-solutions`, `digital-assets`, `wealth-management`, `how-it-works`, and `contact`. Publish metadata pages for `investments`, `insights`, and `faq` as well. Legal routes read published effective documents with matching slugs: `terms`, `privacy-policy`, `risk-disclosure`, `cookie-policy`.

Sections use the existing `{type, content}` format. Content fields are `heading` (or `title`), `body` (sanitized rich text), `eyebrow`, and `items` containing `title`, `body`, and optional internal `href`. Links allow internal public paths only. The supported CMS types use shared hero, section heading, card, process, preview and CTA components. Investment and insight previews query featured published records. No example investment performance or production legal wording is seeded.

Home uses these ordered slots: `hero`, `positioning`, `services`, `philosophy`, `opportunities`, `process`, `risk`, `insights`, `cta`. Set `content.slot` explicitly or store sections in this order. Their types are respectively `hero`, `rich_text`, `service_grid`, `rich_text`, `investment_preview`, `process_steps`, `rich_text`, `insights_preview`, `cta`. Missing sections provide headings and empty API-backed lists without invented financial descriptions.

Public site settings: `risk_statement`, `contact_details`, and `enquiry_consent` are CMS-sanitized strings. Business and counsel must approve their content before publication. Contact submission is unavailable until public consent wording and an effective published privacy policy exist. Images remain dependent on the existing secure media-delivery work; private storage paths are not exposed as public image URLs.

## Enquiry API

`POST /api/v1/enquiries` accepts `name` (160), `email` (254), optional `phone` (40), `enquiry_type` (`general`, `consultation`, `investment`), `subject` (255), `message` (5000), optional `source_page` (500), boolean `consent`, and honeypot `website`.

The service validates and checks consent/privacy availability, applies a five-request/15-minute IP limit, and writes the existing `enquiries` table with status `new` and UTC consent timestamp. No migration is needed. Public success is HTTP 202 with null data and a generic acknowledgement; identifiers are not returned. Invalid requests return the standard 422 field envelope; throttling returns 429. Honeypot submissions receive generic acknowledgement without persistence. Personal content is stored as text and must be escaped by downstream admin displays.

Set `ENQUIRY_NOTIFICATION_EMAIL` and `MAIL_FROM_ADDRESS` for the existing hosting-native PHP mail transport. Notifications contain no enquiry personal data. Persistence precedes notification, and mail errors are logged without losing submissions. Notification retry and the admin enquiry management screen remain Phase 8 operational work; staff must have an authorized retrieval procedure before enabling the form in production. Retention/export policy remains a business decision.

## Rendering decision and release process

Public pages use API-backed build-time prerendering, suitable for shared Linux hosting without a Node runtime. The interactive React application uses the same public API after loading. `npm run build` is the development/CI asset build; it is not by itself a crawlable release artifact.

For a content-complete release:

1. Install dependencies and Chromium (`npm ci`, `npx playwright install --no-shell chromium`).
2. Set `VITE_SITE_URL` to the approved absolute HTTPS production origin, `VITE_API_BASE_URL=/api/v1`, and `PRERENDER_API_URL` to the trusted environment API ending in `/api/v1`.
3. Run `npm run build:release`. Missing required published pages, legal documents, or SEO metadata fail generation. Dynamic slugs come only from published catalogue/editorial APIs.
4. Deploy the entire generated `web/dist` artifact. Configure the host to serve route `index.html` files, send `/api/v1` to PHP, and serve `404.html` with HTTP 404 for unknown public URLs. Admin deep links need the application shell. Do not rewrite unknown public routes to HTTP 200.
5. Rebuild and replace the complete versioned artifact after publishing, unpublishing, archiving, legal-effective-date changes or slug changes. Replacing the complete artifact removes old snapshots; retain the prior artifact for rollback. Invalidate CDN caches. CMS publishing does not automatically trigger deployment yet.

Generated HTML contains visible public content and metadata, with canonical URLs, robots, Open Graph, Twitter fields and JSON-LD. Organization structured data is emitted on the home route, Article structured data on insight details, BreadcrumbList on public routes, and FAQPage structured data from published FAQ records. Sitemap generation excludes noindex routes, admin paths, drafts, archived records, future publications and the 404 page. Configure approved social images through SEO `open_graph.image`. No production origin, organizational legal identity, licensing status or investment performance claim is invented.

The backend serves `/sitemap.xml`, `/robots.txt`, `/api/v1/sitemap.xml`, `/api/v1/robots.txt`, `/api/v1/seo/sitemap`, and `/api/v1/seo/redirect`. Production sitemap and canonical generation require an absolute HTTPS public origin through `FRONTEND_URL`. Renaming an already published page, legal document, investment opportunity or insight article while keeping it published creates an internal 301 redirect from the old public path to the new one. Host-level redirects should still be exported/applied for production SEO; the React app also checks the public redirect endpoint on 404 to help users following stale in-app links.

## Verification

`npm run lint`, `npm run typecheck`, `npm test -- --run`, `npm run build`, `npm run test:e2e`, and `npm run test:prerender` cover the frontend. Browser tests use clearly marked fixtures and exercise route landmarks, mobile overflow/navigation, unavailable content and contact submission. Backend enquiry tests verify persistence through a mail outage, input validation, consent, privacy availability, honeypot handling and throttling. Existing editorial and catalogue suites remain in place.

This work does not establish production content approval, hosting configuration, email deliverability, automated publication rebuilds, or complete release readiness.

The prerender smoke test writes synthetic HTML into ignored `web/dist`; never deploy those fixture artifacts. Run a fresh `build:release` against approved content for deployment.
