# PerrymanFinance — DevOps, Testing & Deployment

# 1. Environment Strategy

Use:

- local;
- test/CI;
- staging;
- production.

Never share production secrets with staging.

---

# 2. Runtime and Hosting Model

Production uses shared Linux hosting. The host must provide:

```text
PHP 8.3+ and required PHP extensions
MySQL 8+ or compatible MariaDB
HTTPS and URL rewriting
cron scheduling
secure environment configuration
restricted writable storage
backup and restore facilities
SSH, SFTP, or another auditable deployment mechanism
```

The frontend is compiled in CI or on a trusted build machine and deployed as static assets. Production does not require Node.js. A local SMTP catcher may be used for development, but production uses an approved SMTP provider. Redis and continuously running queue workers must not be assumed because typical shared hosting cannot support them; use database-backed jobs triggered by cron only when required and supported.

---

# 3. CI Pipeline

On pull request:

```text
Checkout
-> Backend Composer Install
-> PHP Syntax / Style
-> Static Analysis
-> Backend Unit Tests
-> Backend Integration Tests
-> Frontend Install
-> Frontend Lint
-> Type Check
-> Frontend Tests
-> Production Build
-> Security/Dependency Checks
```

Block merge if required checks fail.

---

# 4. CD Pipeline

Staging:

```text
Merge to develop/main policy
-> Build artifact/image
-> Deploy staging
-> Run migrations
-> Health check
-> Smoke test
```

Production:

```text
Approved release/tag
-> Backup checkpoint
-> Build versioned release artifact
-> Upload to a new release directory or staging location
-> Run forward-compatible migration
-> Switch the active release when the host supports it
-> Health check
-> Smoke test
-> Monitor errors
```

Maintain a rollback procedure based on versioned release directories, atomic symlink switching where supported, or verified hosting snapshots/backups. Do not claim atomic or immutable deployment when the chosen host cannot provide it.

---

# 5. Migration Strategy

Rules:
- migrations are immutable after deployment;
- no destructive schema change in the same release as dependent application code if avoidable;
- deploy expand/migrate/contract for high-risk changes;
- back up before major changes.

---

# 6. Test Pyramid

## Backend
- many unit tests;
- repository/integration tests;
- API feature tests;
- small number of end-to-end system tests.

## Frontend
- component tests;
- feature integration tests;
- critical E2E tests.

---

# 7. Critical Test Cases

## Public
- all core routes return successfully;
- contact validation;
- contact submission;
- published content visible;
- drafts invisible;
- investment filters;
- article pagination;
- 404 page.

## Admin
- valid/invalid login;
- permission denial;
- create/update/publish article;
- create/update/publish investment;
- legal content update;
- media upload validation;
- enquiry status update;
- user/role controls.

## Security
- invalid token;
- expired token;
- revoked token;
- XSS-rich-text payload sanitization;
- malicious upload;
- brute-force throttling;
- unauthorized object access.

---

# 8. Quality Gates

Backend:
- PSR-12;
- static analysis threshold;
- tests green;
- no critical dependency vulnerability.

Frontend:
- lint clean;
- type-check clean;
- test suite green;
- production build successful.

Application:
- staging smoke test;
- responsive check;
- accessibility review;
- SEO metadata review;
- no broken links.

---

# 9. Observability

Provide:
- `/api/v1/health`;
- structured application logs;
- request IDs;
- error reporting;
- server metrics;
- uptime checks.

Track:
- HTTP error rates;
- response time;
- failed login spikes;
- mail delivery failures;
- DB availability.

---

# 10. Deployment Configuration

Production configuration should include:

- provider-supported Apache/LiteSpeed/Nginx rewrite rules;
- PHP runtime limits and OPcache settings available through the hosting control panel;
- OPcache;
- HTTP compression;
- static asset cache headers;
- HTTPS;
- database connection limits;
- scheduled backup jobs or provider-managed backups;
- log retention/rotation supported by the provider;
- separate public and private paths so source, secrets, logs, migrations, tests, and uploads are not executable or publicly readable.

---

# 11. SEO Deployment Tasks

Release must include:
- canonical base URL;
- robots.txt;
- sitemap.xml;
- Open Graph assets;
- favicon/app icons;
- organization structured data;
- analytics only after privacy review;
- redirects from renamed slugs.

---

# 12. Release Checklist

1. CI green.
2. Staging accepted.
3. Content approved.
4. Legal pages approved.
5. Environment variables verified.
6. DB backup complete.
7. Migrations reviewed.
8. Production build generated.
9. Deployment executed.
10. Health endpoint green.
11. Smoke tests green.
12. Contact email verified.
13. Monitoring checked.
14. Rollback reference recorded.

---

# 13. Post-Launch Operations

Weekly:
- review application errors;
- dependency alerts;
- failed enquiry notifications;
- admin activity anomalies.

Monthly:
- restore-test backup sample;
- performance review;
- SEO health;
- access review.

Quarterly:
- dependency upgrades;
- security review;
- content/legal review;
- disaster-recovery procedure review.
