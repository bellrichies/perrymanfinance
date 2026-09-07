# PerrymanFinance — Comprehensive Copilot-Ready Implementation Prompts

Use these prompts sequentially. Every prompt assumes the repository documentation is the source of truth.

---

## Master Instruction for Every Coding Session

```text
You are working on PerrymanFinance.

Before writing or modifying code, read the relevant files under /docs and inspect the existing implementation. Treat the documentation and existing architectural conventions as the source of truth.

Project stack:
- PHP 8.3+ custom OOP MVC backend
- RESTful JSON API
- PSR-4 / PSR-12
- SOLID
- dependency injection
- repository pattern
- service-based business logic
- middleware
- MySQL/InnoDB
- React.js + TypeScript + Vite
- Tailwind CSS
- API-driven frontend
- Shared Linux hosting deployment
- GitHub Actions

Architecture rules:
1. Keep controllers thin.
2. Put business logic in services.
3. Use repositories for persistence.
4. Enforce validation server-side.
5. Enforce authorization server-side.
6. Use prepared statements.
7. Do not introduce framework dependencies that conflict with the custom MVC architecture unless explicitly approved.
8. Do not implement wallet custody, deposits, withdrawals, trading, blockchain private-key handling, automated ROI, or client-money accounting. Those are outside MVP scope.
9. Do not invent financial claims, guaranteed returns, licensing, or regulatory status.
10. Update tests and documentation with each meaningful change.

Before implementation:
- summarize the relevant existing architecture;
- identify files to create/change;
- identify schema/API impact;
- identify security considerations.

Then implement the requested scope only.
```

---

## Phase 0 Prompt — Requirements and Architecture Validation

```text
Review all PerrymanFinance documentation under /docs.

Create a project implementation checklist that maps:
- product requirements;
- public routes;
- admin routes;
- backend modules;
- frontend features;
- database entities;
- external integrations;
- test requirements;
- security controls;
- launch requirements.

Identify contradictions, missing requirements, or accidental scope expansion.

Do not write product code yet.

Output:
1. assumptions;
2. resolved architecture;
3. requirement-to-module matrix;
4. dependency order;
5. implementation risks;
6. final MVP checklist.

Keep the MVP limited to the corporate investment website, admin CMS, investment-opportunity catalogue, insights, legal content, SEO, and enquiries.
```

---

## Phase 1 Prompt — Repository and Shared-Hosting Foundation

```text
Implement PerrymanFinance Phase 1: repository and engineering foundation.

Requirements:
- create /backend, /web, /docs, /.github/workflows;
- configure local development for installed PHP 8.3+, Composer, MySQL, Node/npm, and an independently selected local SMTP catcher when email is implemented;
- establish shared Linux hosting requirements for PHP extensions, document roots, URL rewriting, environment configuration, cron, writable storage, and prebuilt frontend assets;
- create .env.example files;
- create Makefile commands for up, down, install, migrate, seed, test, lint, build;
- initialize Composer with PSR-4 autoloading;
- initialize React + TypeScript + Vite + Tailwind;
- configure PHPUnit;
- configure PHP code style/static-analysis tooling;
- configure frontend lint/typecheck/test commands;
- add basic CI workflow;
- add backend /api/v1/health endpoint;
- add simple frontend health/home screen consuming or displaying API availability.

Security:
- no secrets in repository;
- safe default environment configuration;
- production debug disabled by environment.
- do not expose source, secrets, logs, migrations, tests, or writable uploads through the public document root.

Testing:
- test the health endpoint;
- verify production frontend build.

Update README with exact local setup commands.

Do not create domain features yet.
```

---

## Phase 2 Prompt — Custom PHP MVC Core

```text
Implement the backend core framework for PerrymanFinance.

Required components:
- Application bootstrap;
- configuration loader using vlucas/phpdotenv;
- DI container with interface bindings and singleton lifecycle support;
- HTTP Request abstraction;
- JSON Response abstraction;
- Router with route groups, params, and middleware;
- middleware dispatcher;
- PDO connection manager;
- transaction helper;
- base repository utilities without building a God class;
- validator with reusable rules;
- centralized exception handler;
- structured logger;
- standardized API success/error response factory;
- request/correlation ID middleware;
- CORS middleware;
- security headers middleware;
- JSON body middleware;
- 404 and 405 handling.

Use strict types and typed classes.

Write unit and feature tests for:
- route matching;
- request params;
- validation;
- API response shape;
- exception mapping;
- DB connection abstraction.

Do not implement authentication yet.
```

---

## Phase 3 Prompt — Database Migration System

```text
Implement a database migration and seeding system for the PerrymanFinance custom PHP MVC backend.

Requirements:
- migration registry/table;
- deterministic migration ordering;
- up/down support;
- CLI commands;
- transactional migration execution where supported;
- migration status output;
- seed runner;
- safe failure handling;
- no automatic destructive production reset command.

Create initial migrations for:
admin_users
roles
permissions
role_permissions
user_roles
password_reset_tokens
refresh_tokens
audit_logs
pages
page_sections
legal_documents
media_assets
site_settings
investment_categories
investment_opportunities
article_categories
articles
tags
article_tags
faqs
enquiries
seo_metadata
redirects

Use:
- foreign keys;
- indexes;
- unique constraints;
- UTC timestamps;
- InnoDB;
- utf8mb4.

Create only safe development seed data.
Never seed real passwords or production credentials.
```

---

## Phase 4 Prompt — Admin Authentication and RBAC

```text
Implement secure admin identity and RBAC for PerrymanFinance.

Backend:
- AdminUser repository/service;
- roles and permissions;
- login;
- current-user endpoint;
- short-lived access token;
- refresh-token rotation;
- logout/revocation;
- forgot/reset password workflow;
- password hash/verify;
- account active status;
- rate limiting;
- AuthMiddleware;
- PermissionMiddleware;
- audit logging.

Permissions should include:
pages.*
investments.*
articles.*
faqs.manage
enquiries.view
enquiries.update
media.manage
settings.manage
users.manage
audit.view
legal.manage

Frontend:
- /admin/login;
- auth context/provider or suitable equivalent;
- API-client auth handling;
- protected routes;
- permission-aware navigation;
- session-expiry behavior;
- loading/error states.

Security:
- never log tokens/passwords;
- prevent user enumeration in reset responses;
- expire reset tokens;
- invalidate refresh tokens after reset;
- backend is authoritative for permissions.

Tests:
- valid/invalid login;
- inactive admin;
- refresh;
- logout;
- expired/revoked token;
- permission denial;
- rate limiting;
- password reset.

Do not implement public client accounts.
```

---

## Phase 5 Prompt — CMS Pages, Legal Content, Settings and Media

```text
Implement PerrymanFinance CMS functionality.

Backend modules:
1. Pages
2. PageSections
3. LegalDocuments
4. SiteSettings
5. MediaAssets
6. SEO metadata

Page workflow:
draft -> review -> published -> archived

Legal documents:
- document type;
- title;
- slug;
- version;
- effective date;
- status;
- published content.

Media:
- authenticated upload;
- file-size limits;
- server-side MIME inspection;
- image decoding;
- randomized filenames;
- safe storage;
- no executable files;
- image metadata response.

Public endpoints:
GET /api/v1/pages/{slug}
GET /api/v1/legal/{slug}
GET /api/v1/site-settings/public

Admin CRUD endpoints for all modules.

Frontend admin:
- page list/editor;
- publish controls;
- preview;
- legal editor;
- settings;
- media library;
- SEO editor.

Tests:
- draft not publicly visible;
- published visible;
- legal version update;
- invalid upload rejected;
- unauthorized mutation denied;
- SEO persistence.

Do not allow unsafe arbitrary scripts in CMS content.
```

---

## Phase 6 Prompt — Investment Opportunities

```text
Implement the PerrymanFinance investment-opportunity catalogue.

This is informational only. Do not implement investing, funding, payments, subscriptions, wallets, deposits, withdrawals, or guaranteed return calculations.

Entities:
InvestmentCategory
InvestmentOpportunity

Fields should support:
- title;
- slug;
- short description;
- full description;
- strategy summary;
- investment objective;
- investment horizon;
- risk classification;
- optional minimum-investment display;
- optional currency display;
- status;
- featured;
- cover media;
- disclaimer;
- published_at;
- SEO.

Backend:
- repositories;
- services;
- validation;
- publish workflow;
- pagination;
- safe filtering;
- admin CRUD;
- public list/detail.

Frontend public:
- investments index;
- filters;
- investment cards;
- investment detail;
- prominent risk notice;
- Request Information CTA.

Frontend admin:
- list;
- create/edit;
- preview;
- publish/archive;
- SEO.

Tests:
- published filtering;
- slug uniqueness;
- risk field validation;
- permissions;
- pagination;
- public detail;
- draft invisibility.
```

---

## Phase 7 Prompt — Insights, Categories, Tags and FAQ

```text
Implement PerrymanFinance editorial publishing.

Entities:
- ArticleCategory
- Article
- Tag
- ArticleTag
- FAQ

Article fields:
title
slug
excerpt
content
cover_media
author
category
tags
featured
status
published_at
SEO metadata

Backend:
- CRUD;
- publish workflow;
- pagination;
- category/tag filtering;
- related content logic;
- public list/detail endpoints.

FAQ:
- question;
- answer;
- display order;
- status/category if useful.

Frontend public:
- insights landing page;
- article card;
- featured article;
- category filtering;
- pagination;
- article detail;
- related articles;
- FAQ accordion.

Frontend admin:
- article editor;
- categories;
- tags;
- FAQ manager.

Security:
- sanitize rich content;
- no arbitrary script execution.

Tests:
- publication visibility;
- pagination;
- filters;
- article/tag relationships;
- FAQ ordering.
```

---

## Phase 8 Prompt — Public Marketing Website

```text
Build the complete PerrymanFinance public website using the API and the approved design direction.

Style:
- institutional digital wealth management;
- deep navy base;
- restrained blue/emerald accents;
- strong typography;
- generous whitespace;
- no crypto-casino visuals;
- no fake profits or urgency mechanics.

Pages:
Home
About
Investment Solutions
Digital Assets
Wealth Management
How It Works
Investments
Investment Detail
Insights
Insight Detail
FAQ
Contact
Terms
Privacy
Risk Disclosure
Cookie Policy
404

Reusable components:
Header
MobileNavigation
Footer
HeroSection
SectionHeader
ServiceCard
InvestmentCard
InsightCard
ProcessSteps
CTASection
RiskNotice
FAQAccordion
Breadcrumb
LoadingSkeleton
ErrorState
EmptyState

Requirements:
- responsive;
- semantic HTML;
- visible focus;
- keyboard navigation;
- accessible forms;
- loading/error states;
- route-level code splitting;
- clean API-service abstraction.

Homepage order:
Hero
Positioning
Services
Investment Philosophy
Featured Opportunities
How It Works
Risk Management
Featured Insights
CTA
Footer/Risk Statement

Do not hard-code financial claims.
```

---

## Phase 9 Prompt — Enquiries and Email Workflow

```text
Implement PerrymanFinance enquiry and consultation workflows.

Public form fields:
- name;
- email;
- phone optional;
- enquiry type;
- subject;
- message;
- source page;
- consent.

Backend:
- validation;
- anti-spam controls;
- rate limiting;
- persistence;
- status workflow;
- notification email through PHPMailer abstraction;
- generic success response;
- audit/admin handling.

Statuses:
new
in_progress
resolved
spam
closed

Frontend public:
- inline validation;
- submit loading state;
- duplicate-submit prevention;
- accessible error summary;
- success confirmation.

Admin:
- enquiries table;
- search/filter;
- detail view;
- status updates;
- assignment optional;
- notes only if properly scoped.

Tests:
- validation;
- throttling;
- email failure does not lose stored enquiry;
- unauthorized access;
- status transition.
```

---

## Phase 10 Prompt — SEO and Structured Data

```text
Implement technical SEO for PerrymanFinance.

Requirements:
- dynamic page title;
- meta description;
- canonical URL;
- robots directive;
- Open Graph;
- social image;
- Twitter card;
- sitemap.xml;
- robots.txt;
- slug redirects;
- JSON-LD.

Structured data where relevant:
- Organization;
- Article;
- BreadcrumbList;
- FAQPage.

Implement server/API support where metadata is managed by CMS.

Ensure:
- drafts/noindex admin routes never enter sitemap;
- canonical URLs are absolute in production;
- renamed published slugs can create redirects;
- investment content does not make unapproved financial claims.

Add tests for metadata and sitemap behavior.
```

---

## Phase 11 Prompt — Performance, Accessibility and Security Hardening

```text
Perform PerrymanFinance pre-release hardening.

Performance:
- optimize images;
- lazy-load below-fold media;
- code-split routes;
- configure caching;
- remove unnecessary dependencies;
- enable backend OPcache guidance;
- improve slow SQL queries;
- add appropriate indexes.

Accessibility:
- heading hierarchy;
- landmarks;
- focus states;
- keyboard navigation;
- labels;
- error messaging;
- contrast;
- reduced motion;
- alt text.

Security:
- CSP;
- HSTS production;
- CORS allowlist;
- secure cookies;
- rate limits;
- XSS sanitization;
- upload hardening;
- auth token review;
- permission tests;
- production error masking;
- secret review.

Produce a remediation report and implement all release-blocking items.
```

---

## Phase 12 Prompt — Test Suite and Staging UAT

```text
Prepare PerrymanFinance for staging acceptance.

Expand automated coverage for critical paths.

Backend E2E/feature cases:
- login;
- refresh/logout;
- permissions;
- publish page;
- publish legal document;
- publish investment;
- publish article;
- enquiry submission;
- media rejection.

Frontend E2E:
- public navigation;
- investment list/detail;
- insights list/detail;
- contact form;
- admin login;
- create/edit/publish content.

Create staging smoke-test checklist.

Create a UAT checklist grouped by:
- public content;
- navigation;
- admin;
- investments;
- insights;
- enquiries;
- legal;
- SEO;
- responsive;
- accessibility;
- security.

Fix release-blocking defects.
Do not weaken tests simply to get green CI.
```

---

## Phase 13 Prompt — CI/CD and Production Deployment

```text
Implement production-grade CI/CD for PerrymanFinance.

CI:
- Composer install;
- PHP lint/style;
- static analysis;
- backend tests;
- frontend install;
- frontend lint;
- type-check;
- frontend tests;
- production build;
- dependency/security checks.

CD:
- versioned build artifact suitable for upload to shared hosting;
- staging deploy;
- production deploy on approved release/tag;
- migration step;
- health check;
- smoke test;
- rollback procedure.

Infrastructure:
- shared Linux hosting with PHP 8.3+ and MySQL 8+/compatible MariaDB;
- provider-supported Apache/LiteSpeed/Nginx URL rewriting and security configuration;
- prebuilt frontend assets without a production Node.js dependency;
- HTTPS;
- backups;
- log rotation;
- environment secrets;
- cache headers;
- monitoring.

Shared-hosting constraints:
- do not assume root access, Redis, system services, or a continuously running queue worker;
- use hosting-panel cron for scheduled/database-backed jobs where required;
- keep backend source and configuration outside the public document root where supported;
- document a provider-compatible release, migration, health-check, and rollback workflow.

Create:
- deployment runbook;
- rollback runbook;
- backup/restore runbook;
- production checklist.

Never place production credentials in GitHub workflow source.
Use protected secrets/environment configuration.
```

---

## Phase 14 Prompt — Post-Launch Review

```text
Perform a PerrymanFinance post-launch engineering review.

Review:
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

Create a prioritized backlog:
P0 critical
P1 high
P2 medium
P3 improvement

Separate:
- bug fixes;
- performance work;
- content requests;
- conversion optimization;
- future client-portal features.

Do not begin wallet/trading/client-money features as ordinary backlog tasks. Flag them for separate architecture, threat-model, compliance, and product review.
```

---

## Future Client Portal Discovery Prompt

```text
Do not write code.

We are considering a future PerrymanFinance authenticated client portal.

Conduct architecture discovery for:
- client identity;
- MFA;
- KYC;
- portfolio reporting;
- statements;
- investment subscriptions;
- transactions;
- notifications;
- document access;
- audit/compliance controls.

Explicitly identify:
- legal/regulatory dependencies;
- money/asset movement implications;
- custody implications;
- data-security classification;
- threat model;
- idempotency requirements;
- reconciliation requirements;
- audit requirements;
- segregation from the existing CMS.

Output a separate architecture proposal and migration strategy.
Do not assume the MVP CMS is sufficient for financial transaction processing.
```
