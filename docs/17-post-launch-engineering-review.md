# PerrymanFinance Post-Launch Engineering Review

## Scope

This review covers the first post-launch operational engineering pass for PerrymanFinance:

- production errors;
- slow endpoints;
- failed enquiries;
- email delivery;
- security alerts;
- page performance;
- accessibility regressions;
- SEO crawl/indexing;
- admin usability;
- backup health.

No live production telemetry, hosting panel, SMTP provider dashboard, uptime monitor, security scanner, analytics, Search Console, or backup console output was available in this workspace. Items below therefore distinguish between source/repository follow-up and production evidence that must be gathered from the deployed environment.

## Review Inputs

- `docs/06-devops-testing-deployment.md`
- `docs/10-staging-smoke-test-checklist.md`
- `docs/11-uat-checklist.md`
- `docs/14-pre-release-hardening-report.md`
- `docs/15-backup-restore-runbook.md`
- `docs/16-production-checklist.md`
- repository source structure for backend API, enquiries, admin CMS, public frontend, scripts, and tests.

## Priority Definitions

- **P0 critical**: user-impacting outage, data loss, security exposure, enquiry loss, broken production deployment, broken backups, or compliance-risking public behavior.
- **P1 high**: major conversion, admin, performance, observability, deliverability, or SEO issue that materially affects operations but has a workaround.
- **P2 medium**: important hardening, usability, regression prevention, or operational maturity work.
- **P3 improvement**: polish, optimization, reporting, experimentation, or future capability that is useful but not urgent.

## Production Evidence Required

Collect and attach these before closing review items:

- application logs and provider PHP/error logs for the launch window and latest 7 days;
- `/api/v1/health` uptime history and incident timeline;
- top 20 slow API paths with p50, p95, p99, and error rate;
- failed enquiry rows, validation failures, and notification failure counts;
- SMTP provider bounce, reject, deferral, complaint, and delivery stats;
- dependency, hosting, WAF, malware, TLS, and account security alerts;
- Lighthouse or WebPageTest results for public core routes on mobile and desktop;
- axe/Pa11y/manual keyboard results for public and admin critical workflows;
- Search Console/Bing Webmaster crawl, indexing, sitemap, robots, canonical, and structured-data reports;
- admin user feedback for login, publishing, enquiry triage, media upload, and legal content workflows;
- backup job status, latest successful database/media backup, retention policy, and latest restore-test result.

## Prioritized Backlog

### P0 Critical

| Area | Category | Backlog Item | Acceptance Criteria |
| --- | --- | --- | --- |
| Production errors | Bug fixes | Investigate any 5xx spikes, fatal PHP errors, uncaught exceptions, or blank public/admin pages from launch logs. | No unresolved P0 exceptions in latest 24-hour production window; recurring root causes have tests or monitoring alerts. |
| Failed enquiries | Bug fixes | Reconcile submitted public enquiries against persisted database rows and admin-visible records. | Every valid submitted enquiry is persisted once, visible in admin, and has a traceable status even if email notification fails. |
| Email delivery | Bug fixes | Verify production SMTP authentication, sender domain alignment, recipient routing, and bounce handling. | Contact/consultation/onboarding test submissions persist and deliver to approved inboxes; failures are logged without exposing details to users. |
| Security alerts | Bug fixes | Triage critical dependency, exposed secret, malware, web shell, TLS, WAF, suspicious admin login, and publicly accessible private-file alerts. | No unresolved critical alert; secrets rotated where exposure is suspected; source, logs, migrations, `.env`, tests, and private uploads are not web-accessible. |
| Backup health | Bug fixes | Prove database, media, environment, and active release backups are current and restorable. | Latest production backup is successful, restore-tested in non-production, and documented with duration, operator, and rollback notes. |
| Compliance boundary | Bug fixes | Remove or block any production UI/API path implying wallet funding, custody, deposits, withdrawals, trading execution, client-money ledgers, guaranteed returns, or fabricated performance. | No public/admin route presents these as available MVP features; any request is moved to the gated future-review list below. |

### P1 High

| Area | Category | Backlog Item | Acceptance Criteria |
| --- | --- | --- | --- |
| Slow endpoints | Performance work | Add or configure production request timing capture by route, status, request ID, and authenticated/public context. | Weekly report identifies p95 latency and error rate by endpoint without logging secrets or personal data. |
| Slow endpoints | Performance work | Optimize the top slow public GET endpoints after telemetry confirms the offenders. | Top public endpoints meet agreed p95 target under production traffic; cache headers remain correct for published-only content. |
| Page performance | Performance work | Run mobile and desktop performance audits for `/`, service pages, `/investments`, `/insights`, `/faq`, `/contact`, and legal pages. | Core pages have recorded LCP, CLS, INP/TBT, transfer size, and prioritized fixes. |
| Page performance | Performance work | Audit large media assets and below-fold loading behavior. | Oversized images/video are replaced, compressed, lazy-loaded, or deferred; hero assets keep stable dimensions. |
| SEO crawl/indexing | Bug fixes | Verify production `robots.txt`, `sitemap.xml`, canonical base URL, noindex rules, redirects, and published-only sitemap contents. | Search tools show valid sitemap processing; admin, draft, archived, preview, private, and noindex URLs are excluded. |
| Accessibility regressions | Bug fixes | Run automated accessibility checks plus manual keyboard review for public navigation, contact form, admin login, publishing, enquiries, and media upload. | WCAG 2.1 AA blocking regressions are fixed; each critical workflow is keyboard usable with visible focus and accessible errors. |
| Admin usability | Bug fixes | Review admin enquiry triage, publishing workflows, validation errors, loading/error states, and permission-denied flows with real admins. | Admins can complete critical workflows without unclear errors or data loss; usability blockers are fixed. |
| Security alerts | Bug fixes | Confirm login, password reset, enquiry, and upload rate limits are active in production and observable. | Abuse-prone routes throttle correctly; false positives are manageable; events are reviewable. |
| Production errors | Bug fixes | Add an operational error review cadence and incident template. | Weekly review records open incidents, owner, severity, root cause, remediation, and follow-up tests. |

### P2 Medium

| Area | Category | Backlog Item | Acceptance Criteria |
| --- | --- | --- | --- |
| Failed enquiries | Bug fixes | Add an admin filter or saved view for enquiries with notification failure or stale `new` status. | Operations can find failed/stale enquiries without database access. |
| Email delivery | Performance work | Add a resend or re-notify workflow for persisted enquiries after transient SMTP failure. | Authorized admins can retry notification safely; retry actions are audited and rate-limited. |
| Page performance | Performance work | Add recurring Lighthouse CI or scheduled page-performance checks for core routes. | Performance regressions are visible in CI or scheduled reports before they become launch surprises. |
| Accessibility regressions | Bug fixes | Add axe/component checks around shared form and navigation primitives. | Shared UI regressions fail automated checks during development. |
| SEO crawl/indexing | Content requests | Review page titles, meta descriptions, OG text/images, FAQ structured data, and article metadata with marketing/compliance. | Published metadata is complete, non-duplicative, compliant, and CMS-editable where required. |
| Admin usability | Bug fixes | Improve table empty states, pagination clarity, status chips, save feedback, and validation summaries where admin feedback identifies friction. | Admin workflows communicate state clearly during loading, failure, save, publish, and archive actions. |
| Backup health | Bug fixes | Schedule and record a monthly restore test. | Restore-test history exists with backup ID, environment, migration status, smoke-test result, duration, and issues. |
| Security alerts | Bug fixes | Add a monthly access review for admin users, roles, inactive accounts, and audit-log anomalies. | Access review is recorded; unnecessary admin access is removed. |
| Production errors | Performance work | Add correlation IDs to support handoff between frontend reports, backend logs, and provider logs if not already visible in production. | User-reported incidents can be traced by timestamp and request ID without exposing sensitive data. |

### P3 Improvement

| Area | Category | Backlog Item | Acceptance Criteria |
| --- | --- | --- | --- |
| Conversion | Conversion optimization | Review CTA placement and wording across public pages for consultative language. | CTAs remain compliant, avoid urgency/performance claims, and guide users toward consultation or onboarding intake. |
| Conversion | Conversion optimization | Add privacy-reviewed analytics events for enquiry starts, validation failures, successful submissions, investment-detail views, and article engagement. | Events avoid sensitive financial/personal content and support funnel reporting. |
| Content | Content requests | Refresh insights, FAQ, legal effective dates, risk disclosures, and service-page content through business/compliance review. | Content owners approve updates; no unverified licensing, custody, return, AUM, or regulatory claims are introduced. |
| Content | Content requests | Add editorial checklist for post-launch content updates. | New content has owner, review status, SEO metadata, risk/legal review where required, and publication date. |
| Admin usability | Bug fixes | Add admin onboarding notes or inline field help for risk classification, disclaimers, SEO fields, and legal versioning. | Help text reduces publishing mistakes without replacing required policy/legal review. |
| Page performance | Performance work | Evaluate CDN/static-cache improvements after baseline production metrics are known. | Any CDN/cache change preserves API behavior, sitemap/robots freshness, and admin no-store responses. |

## Category-Separated Backlog

### Bug Fixes

- Resolve any production 5xx, fatal PHP, uncaught exception, blank-page, or broken-route issue.
- Reconcile enquiry submissions against persisted rows and admin visibility.
- Verify and fix SMTP failures, bounces, rejects, and inbox routing.
- Triage security alerts and remove any public exposure of private files, secrets, uploads, logs, migrations, source, or tests.
- Prove backup health and restore capability.
- Fix SEO robots/sitemap/canonical/noindex defects.
- Fix WCAG 2.1 AA blocking accessibility regressions.
- Fix admin publishing, enquiry triage, login/session, upload, and permission UX blockers.
- Remove any MVP-unsafe wallet, trading, client-money, guaranteed-return, or fabricated-performance language or feature path.

### Performance Work

- Add production endpoint timing and route-level latency reporting.
- Optimize confirmed slow public GET endpoints and database access paths.
- Audit page performance on mobile and desktop for public core routes.
- Compress, replace, defer, or lazy-load oversized media.
- Add recurring Lighthouse or equivalent page-performance checks.
- Improve operational traceability with request/correlation IDs in support workflows.
- Evaluate CDN/static caching after production baselines are known.

### Content Requests

- Review titles, meta descriptions, OG metadata, social images, FAQ schema, article metadata, and structured data.
- Refresh FAQ, insights, service descriptions, risk notices, legal effective dates, and operational support copy through business/compliance approval.
- Maintain an editorial checklist for ownership, review status, SEO, legal/risk approval, and publication date.

### Conversion Optimization

- Review CTA wording and placement while preserving controlled language such as "Learn More," "View Details," "Request Consultation," and "Start Onboarding."
- Add privacy-reviewed funnel analytics for enquiry starts, successful submissions, investment-detail views, and article engagement.
- Review contact/onboarding form completion friction after validation-error and drop-off data is available.

### Client-Account and Reporting Features

These are not ordinary post-launch bug or growth backlog items. Treat them as a separate program with product, legal/compliance, architecture, privacy, data, authorization, audit, and threat-model review before implementation:

- authenticated client portal;
- portfolio or holding summaries;
- performance reports;
- downloadable client reports;
- investment document access;
- account support workflows;
- suitability/profile data;
- client notifications.

The first approved slice should be read-only reporting with strong identity, RBAC/ABAC, audit logging, privacy controls, data-source validation, and explicit compliance sign-off. It must not be added casually to the CMS/public-site backlog.

## Excluded From Ordinary Backlog

Do not begin any of the following as routine bug fixes, optimizations, conversion tasks, or client-account features:

- wallet creation or wallet connection;
- custody or private-key handling;
- deposits, withdrawals, payments, settlement, or payouts;
- brokerage, exchange, or trade execution;
- investment subscriptions or client-money accounting;
- transaction ledgers for real client funds;
- automated ROI, guaranteed returns, fake balances, or fabricated performance;
- KYC transaction onboarding.

Any request in this area must be flagged for separate architecture, threat-model, compliance, legal, security, data, operations, and product review before scoping.

## Immediate Next Steps

1. Gather production evidence listed above for the latest 7-day period.
2. Open P0 items for any confirmed outage, enquiry loss, security exposure, delivery failure, backup failure, or compliance-boundary breach.
3. Assign P1 owners for telemetry, page-performance, accessibility, SEO, and admin workflow verification.
4. Schedule the first monthly restore test and admin access review.
5. Keep client-account/reporting requests in a separate governed roadmap until approved.
