# PerrymanFinance — Product & Architecture

## 1. Product Definition

**PerrymanFinance** is a financial services, securities, digital asset investment, and wealth-management platform that helps prospective and existing clients understand the company's operations, investment philosophy, service model, products, structured opportunities, portfolio capabilities, risk controls, reporting expectations, and financial insights.

The MVP is designed to achieve five business outcomes:

1. Establish trust and institutional credibility.
2. Explain investment and wealth-management services clearly.
3. Present investment products, securities-related services, client-account processes, and reporting capabilities without fabricating regulatory or performance claims.
4. Capture qualified enquiries, consultation requests, and onboarding-intake requests.
5. Build a maintainable foundation for authenticated investment, portfolio, reporting, and client-service features.

---

## 2. Product Vision

Create a calm, credible, transparent, research-oriented financial brand that connects traditional wealth-management principles with the digital asset economy.

The website should feel closer to:

- a private wealth manager;
- an investment advisory firm;
- an asset-management company;
- a modern institutional fintech company;

and not like:

- a speculative token portal;
- a cryptocurrency casino;
- a high-yield investment program;
- a trading-signal site;
- a generic crypto exchange.

---

## 3. Primary Users

### 3.1 Prospective Investor

Goals:

- understand what PerrymanFinance does;
- understand the available service categories;
- review investment opportunities;
- evaluate the company's philosophy and credibility;
- understand risk;
- contact the company.

### 3.2 Existing Client

MVP goals:

- read company updates and insights;
- find support/contact information;
- access legal documents;
- navigate toward client-account access when that capability is configured.

Authenticated account goals:

- view portfolio;
- review performance;
- access investment documents;
- review transactions;
- download reports.

### 3.3 Content Administrator

Goals:

- publish and edit site content;
- manage investment opportunities;
- publish insights;
- manage FAQs;
- update SEO metadata;
- review enquiries;
- maintain legal-page content.

### 3.4 Super Administrator

Goals:

- administer staff access;
- manage platform configuration;
- review audit history;
- control high-risk administrative actions.

---

## 4. Product Scope

### 4.1 Public Content Modules

#### Company

- Home
- About
- Mission
- Vision
- Investment philosophy
- Why PerrymanFinance
- Team section if required
- Contact details

#### Investment Services

- Investment Solutions
- Digital Asset Management
- Portfolio Management
- Wealth Strategy
- Structured Investment Opportunities

#### Education

- Insights
- Categories
- Featured articles
- Market commentary
- Educational content
- FAQ

#### Legal and Trust

- Terms of Service
- Privacy Policy
- Risk Disclosure
- Cookie Policy
- Disclaimers
- Company/regulatory information where applicable

#### Client Engagement

- Contact
- Consultation request
- Onboarding intake
- Client-account access entry point when available
- Newsletter subscription if approved
- CTA components

---

## 5. Product Requirements

### PR-001 — Responsive Public Website

All public pages must function on mobile, tablet, laptop, and desktop.

### PR-002 — Content Management

Administrators must update important site content without source-code modification.

### PR-003 — Investment Opportunity Catalogue

Administrators must be able to create, edit, publish, unpublish, and archive opportunities.

### PR-004 — Insights Publishing

Administrators must manage articles, categories, tags, excerpts, cover images, SEO information, and publication status.

### PR-005 — Contact/Consultation Leads

Public users must be able to submit enquiries, consultation requests, and onboarding-intake requests. Entries must be stored and optionally emailed to configured recipients.

### PR-006 — Legal Content

Legal pages must be editable and versioned sufficiently to show current published content.

### PR-007 — SEO

Each public page should support

- title;
- meta description;
- canonical URL;
- Open Graph fields;
- social image;
- slug;
- index/noindex configuration where appropriate.

### PR-008 — Accessibility

Target WCAG 2.1 AA-level implementation practices.

### PR-009 — Security

Administrative routes require authenticated and authorized access.

### PR-010 — Auditability

Sensitive administrative changes should create audit records.

---

## 6. Key User Journeys

### Journey A — Understand the Company

```text
Landing Page
  -> About
  -> Investment Philosophy
  -> Services
  -> Contact / Consultation / Onboarding Intake
```

### Journey B — Explore an Investment Opportunity

```text
Home
  -> Investment Opportunities
  -> Opportunity Details
  -> Risk Disclosure
  -> Consultation / Onboarding Intake
```

### Journey C — Learn Before Enquiring

```text
Search / Social / Direct Visit
  -> Insight Article
  -> Related Content
  -> Service Page
  -> Contact / Client Services
```

### Journey D — Administrator Publishes an Insight

```text
Admin Login
  -> Insights
  -> Create Draft
  -> Add SEO
  -> Preview
  -> Publish
  -> Public Insight Detail
```

---

## 7. Information Architecture

```text
/
├── /about
├── /investment-solutions
├── /digital-assets
├── /wealth-management
├── /how-it-works
├── /investments
│   └── /investments/{slug}
├── /insights
│   ├── /insights/category/{slug}
│   └── /insights/{slug}
├── /faq
├── /contact
├── /privacy-policy
├── /terms
├── /risk-disclosure
└── /cookie-policy
```

Admin:

```text
/admin
├── /login
├── /dashboard
├── /pages
├── /investments
├── /insights
├── /categories
├── /tags
├── /faq
├── /enquiries
├── /media
├── /seo
├── /settings
├── /users
└── /audit-logs
```

---

## 8. Architecture Context

```text
Browser
   |
   v
React Web Application
   |
   | HTTPS / JSON
   v
PHP REST API
   |
   +--> Authentication / Authorization
   +--> Content Services
   +--> Investment Services
   +--> Insight Services
   +--> Enquiry Services
   +--> Media Services
   +--> Settings Services
   |
   v
Repository Layer
   |
   v
MySQL
```

External integrations:

```text
PHP API
├── Email Provider / SMTP
├── Market Data Provider (optional)
├── Object Storage / CDN (optional)
└── Observability / Error Tracking
```

---

## 9. Architectural Decisions

### ADR-001 — API-Driven Frontend

The web frontend consumes backend services through REST JSON APIs.

Benefits:

- clean separation of concerns;
- future mobile compatibility;
- client-account reuse;
- testable contracts;
- independent frontend/backend deployment if required.

### ADR-002 — Modular Monolith First

Use a modular monolith rather than microservices.

Why:

- MVP complexity is low;
- transactions remain straightforward;
- less operational overhead;
- faster delivery;
- domain modules can be extracted later if needed.

### ADR-003 — Repository + Service Pattern

Controllers:

- accept HTTP input;
- invoke use cases;
- return responses.

Services:

- enforce business rules;
- orchestrate repositories and integrations.

Repositories:

- execute persistence concerns.

### ADR-004 — CMS as Domain Functionality

Do not use uncontrolled free-form database editing.

Model important content explicitly:

- pages;
- investments;
- articles;
- FAQs;
- legal pages;
- SEO;
- settings.

### ADR-005 — Future Client Portal Isolation

Authenticated financial functionality should live in a separate bounded module with stronger security, authorization, audit, privacy, suitability, reporting, and compliance requirements than public CMS content.

---

## 10. Domain Modules

### Identity

- AdminUser
- Role
- Permission
- RefreshToken/Session
- PasswordReset
- AuditLog

### Content

- Page
- PageSection
- MediaAsset
- SiteSetting
- LegalDocument

### Investment

- InvestmentOpportunity
- InvestmentCategory
- RiskClassification
- InvestmentStatus

### Insights

- Article
- Category
- Tag
- ArticleTag

### Enquiries

- ContactEnquiry
- ConsultationRequest
- EnquiryStatus

### SEO

- SeoMetadata
- Redirect
- Sitemap

---

## 11. Non-Functional Requirements

### Performance

- target fast first meaningful render;
- optimize images;
- cache public GET endpoints;
- lazy-load below-fold assets;
- CDN for static files where practical.

### Reliability

- structured exception handling;
- retries only for safe external operations;
- backup and restore procedure;
- health endpoints;
- graceful external-provider failure.

### Security

- HTTPS;
- prepared SQL;
- CSRF protection where cookie/session authentication is used;
- secure password hashing;
- rate limiting;
- upload validation;
- least-privilege RBAC;
- secure headers;
- audit logging.

### Maintainability

- PSR-12;
- static analysis;
- unit tests;
- integration tests;
- API contract tests;
- frontend component tests;
- conventional migrations and fixtures.

### Observability

- request ID/correlation ID;
- structured logs;
- deployment version;
- application health;
- error tracking;
- admin action logging.

---

## 12. Success Metrics

Potential product KPIs:

- qualified contact enquiries;
- consultation conversion rate;
- investment detail-page engagement;
- insight readership;
- organic-search impressions;
- returning visitor percentage;
- page-performance scores;
- enquiry-response time.

Do not treat speculative financial returns as a product KPI.

---

## 13. MVP Exit Criteria

The MVP is ready for production when:

- all public pages are responsive;
- all core content is editable;
- admin authentication and RBAC are operational;
- contact enquiries are persisted;
- legal pages are published;
- SEO metadata is complete;
- sitemap and robots configuration exist;
- tests pass in CI;
- production environment is reproducible;
- backups are configured;
- error logging is operational;
- no wallet/deposit/withdrawal/trading functionality exists unless formally approved.
