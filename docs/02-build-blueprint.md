# PerrymanFinance — Build Blueprint

# 1. Development Strategy

Build PerrymanFinance as a modular monolith with two application surfaces:

1. **PHP REST API**
2. **React web application**

The implementation should proceed vertically rather than building all database code first and all UI last.

A vertical slice should include:

```text
Database
-> Repository
-> Service
-> Controller
-> API Route
-> API Test
-> Frontend API Client
-> Page/Component
-> UI Test
```

This reduces integration surprises.

---

# 2. Repository Layout

```text
perrymanfinance/
├── backend/
│   ├── app/
│   │   ├── Config/
│   │   ├── Core/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   ├── Requests/
│   │   │   └── Responses/
│   │   ├── Domain/
│   │   │   ├── Identity/
│   │   │   ├── Content/
│   │   │   ├── Investment/
│   │   │   ├── Insights/
│   │   │   ├── Enquiries/
│   │   │   └── Seo/
│   │   ├── Services/
│   │   ├── Repositories/
│   │   ├── Integrations/
│   │   └── Support/
│   ├── bootstrap/
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── fixtures/
│   ├── public/
│   ├── routes/
│   ├── storage/
│   │   ├── logs/
│   │   └── uploads/
│   ├── tests/
│   │   ├── Unit/
│   │   ├── Integration/
│   │   └── Feature/
│   ├── composer.json
│   └── phpunit.xml
│
├── web/
│   ├── src/
│   │   ├── app/
│   │   ├── assets/
│   │   ├── components/
│   │   ├── features/
│   │   ├── hooks/
│   │   ├── layouts/
│   │   ├── lib/
│   │   ├── pages/
│   │   ├── routes/
│   │   ├── services/
│   │   ├── styles/
│   │   ├── types/
│   │   └── utils/
│   ├── public/
│   ├── tests/
│   └── package.json
│
├── docs/
├── .github/
│   └── workflows/
├── .env.example
├── Makefile
└── README.md
```

---

# 3. Backend Bootstrap

Build these primitives before domain features:

- environment loader;
- configuration repository;
- DI container;
- router;
- request abstraction;
- response abstraction;
- middleware dispatcher;
- PDO connection manager;
- base repository;
- validator;
- logger;
- exception handler;
- JSON response helper;
- migration runner;
- route grouping;
- health-check controller.

Avoid an oversized "BaseController" or "God Service".

---

# 4. Initial Database Tables

## Identity
- admin_users
- roles
- permissions
- role_permissions
- user_roles
- password_reset_tokens
- refresh_tokens
- audit_logs

## Content
- pages
- page_sections
- legal_documents
- media_assets
- site_settings

## Investments
- investment_categories
- investment_opportunities

## Insights
- article_categories
- articles
- tags
- article_tags

## Enquiries
- enquiries

## SEO
- seo_metadata
- redirects

Optional operational:
- jobs
- failed_jobs
- rate_limit_counters if application-managed

---

# 5. Content Model

## Page

Suggested fields:

```text
id
uuid
title
slug
page_type
status
excerpt
content_json
published_at
created_by
updated_by
created_at
updated_at
deleted_at
```

`content_json` can store structured section configuration for editable marketing pages while important domain content remains normalized.

## LegalDocument

```text
id
document_type
title
slug
version
content
effective_at
status
published_at
created_by
updated_by
timestamps
```

## InvestmentOpportunity

```text
id
uuid
category_id
title
slug
short_description
full_description
strategy_summary
investment_objective
investment_horizon
risk_classification
minimum_investment_display
currency_display
status
featured
cover_media_id
disclaimer
published_at
timestamps
```

Do not calculate or promise future returns in this table for MVP.

## Article

```text
id
uuid
category_id
title
slug
excerpt
content
cover_media_id
author_id
status
featured
published_at
timestamps
```

---

# 6. API Blueprint

Base prefix:

```text
/api/v1
```

## Public

```text
GET  /health

GET  /pages/{slug}
GET  /legal/{slug}

GET  /investments
GET  /investments/{slug}
GET  /investment-categories

GET  /insights
GET  /insights/{slug}
GET  /insight-categories
GET  /tags

GET  /faq
POST /enquiries
GET  /site-settings/public
```

## Admin Authentication

```text
POST /admin/auth/login
POST /admin/auth/refresh
POST /admin/auth/logout
POST /admin/auth/forgot-password
POST /admin/auth/reset-password
GET  /admin/auth/me
```

## Admin

```text
GET/POST/PATCH/DELETE /admin/pages
GET/POST/PATCH/DELETE /admin/legal-documents
GET/POST/PATCH/DELETE /admin/investments
GET/POST/PATCH/DELETE /admin/investment-categories
GET/POST/PATCH/DELETE /admin/articles
GET/POST/PATCH/DELETE /admin/article-categories
GET/POST/PATCH/DELETE /admin/tags
GET/POST/PATCH/DELETE /admin/faqs
GET/PATCH             /admin/enquiries
GET/POST/DELETE        /admin/media
GET/PATCH              /admin/settings
GET/POST/PATCH/DELETE  /admin/users
GET                     /admin/audit-logs
```

---

# 7. API Response Standard

Success:

```json
{
  "success": true,
  "data": {},
  "meta": {},
  "message": null
}
```

Validation error:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The submitted data is invalid.",
    "fields": {
      "email": ["A valid email address is required."]
    }
  }
}
```

Server errors should not expose stack traces in production.

---

# 8. Frontend Blueprint

## Public Layout

```text
App
├── PublicLayout
│   ├── Header
│   ├── Main
│   └── Footer
└── AdminLayout
    ├── Sidebar
    ├── Topbar
    ├── Main
    └── Notifications
```

## Public Pages

- HomePage
- AboutPage
- InvestmentSolutionsPage
- DigitalAssetsPage
- WealthManagementPage
- HowItWorksPage
- InvestmentsPage
- InvestmentDetailPage
- InsightsPage
- InsightDetailPage
- FAQPage
- ContactPage
- LegalPage
- NotFoundPage

## Admin Pages

- LoginPage
- DashboardPage
- PagesIndex/Edit
- InvestmentsIndex/Create/Edit
- ArticlesIndex/Create/Edit
- FAQsIndex
- EnquiriesIndex/Detail
- MediaLibrary
- Settings
- Users
- AuditLogs

---

# 9. Component System

Core primitives:

- Button
- Input
- Textarea
- Select
- Checkbox
- Radio
- Modal
- Drawer
- Alert
- Toast
- Badge
- Card
- Table
- Pagination
- Breadcrumb
- Skeleton
- Spinner
- EmptyState
- ErrorState
- ConfirmDialog

Marketing components:

- HeroSection
- SectionHeader
- ServiceCard
- InvestmentCard
- InsightCard
- StatisticsBlock
- ProcessSteps
- CTASection
- RiskNotice
- NewsletterBlock
- FAQAccordion

Admin:

- DataTable
- StatusBadge
- PublishControls
- RichTextEditor
- SeoEditor
- MediaPicker
- AuditTimeline

---

# 10. Development and Hosting Environment

Required local tools/services:

```text
PHP 8.3+ CLI with required extensions
Composer 2
MySQL 8+ or compatible MariaDB
Node.js/npm for frontend development and builds
local SMTP catcher or test SMTP account when email is implemented
```

Production is deployed to shared Linux hosting. The selected plan must provide PHP 8.3+, required extensions, MySQL/MariaDB, HTTPS, URL rewriting, cron, secure environment configuration, writable non-executable storage, backups, and a supported deployment mechanism. Frontend assets are built in CI and uploaded; Node.js is not required at runtime.

Optional local tools:

```text
PHP built-in server
provider-compatible Apache/LiteSpeed rewrite testing
```

Commands should be standardized through Makefile:

```bash
make install
make migrate
make seed
make test
make lint
make build
make api
make web-dev
```

---

# 11. Coding Standards

## PHP
- strict types;
- PSR-4;
- PSR-12;
- constructor injection;
- typed properties;
- return types;
- no raw SQL in controllers;
- no global mutable state;
- no business rules in route definitions.

## React
- functional components;
- TypeScript preferred;
- feature-oriented modules;
- no direct fetch calls inside arbitrary components;
- reusable API-client layer;
- proper loading/error/empty states;
- semantic HTML;
- accessible labels and focus behavior.

---

# 12. Definition of Done for Every Feature

A feature is complete only when:

- requirements are met;
- migration exists if persistence changed;
- validation exists;
- authorization is enforced;
- API response follows standard;
- error handling exists;
- automated tests exist;
- UI has loading, empty, error, success states;
- mobile layout works;
- accessibility is checked;
- relevant documentation is updated;
- no secrets are committed;
- CI passes.
