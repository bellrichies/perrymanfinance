# PerrymanFinance Development Blueprint

## Purpose

This documentation pack is the implementation source of truth for the first production release of **PerrymanFinance**.

PerrymanFinance is a digital asset investment and wealth management website designed to:

- present the company and its investment philosophy;
- explain digital asset and wealth management services;
- present structured investment opportunities;
- publish financial and digital asset insights;
- educate prospective clients about process, risk, and services;
- capture qualified enquiries and consultation requests;
- provide strong legal, privacy, and risk-disclosure foundations;
- allow authorized administrators to manage public website content;
- preserve a clear architecture path toward a future secure client portal.

The MVP is intentionally a **corporate/investment information and lead-generation platform**, not a cryptocurrency exchange, brokerage, wallet, custody platform, or automated investment engine.

---

## Documentation Index

1. [Product & Architecture](./01-product-and-architecture.md)
2. [Build Blueprint](./02-build-blueprint.md)
3. [Backend Architecture & Workflow](./03-backend-architecture-and-workflow.md)
4. [Frontend Architecture & Workflow](./04-frontend-architecture-and-workflow.md)
5. [Data, API, Security & Compliance](./05-data-api-security-compliance.md)
6. [DevOps, Testing & Deployment](./06-devops-testing-deployment.md)
7. [Delivery Phases & Roadmap](./07-delivery-phases-roadmap.md)
8. [Copilot-Ready Implementation Prompts](./08-copilot-ready-prompts.md)

---

## Recommended Technology Stack

### Backend

- PHP 8.3+
- Custom OOP MVC framework
- RESTful JSON API
- Composer / PSR-4 autoloading
- PSR-12 coding standard
- MySQL 8+ with InnoDB and foreign keys
- PDO prepared statements
- `vlucas/phpdotenv`
- PHPMailer
- JWT for API authentication where required
- Service layer + Repository pattern
- Dependency Injection container
- Middleware pipeline
- Centralized validation, logging, exception handling, and API response formatting

### Frontend

- React.js
- Vite
- React Router
- Tailwind CSS
- Fetch/Axios-style API client abstraction
- React Query/TanStack Query or equivalent for server-state management
- Accessible reusable component system
- Mobile-first responsive layouts
- SEO-aware rendering strategy for public pages

### Infrastructure

- Shared Linux hosting with PHP 8.3+ and MySQL 8+/compatible MariaDB
- Apache, LiteSpeed, or Nginx as provided by the hosting platform
- Node.js tooling in development/CI; deploy prebuilt frontend assets
- GitHub Actions
- Staging + Production environments
- Automated testing and deployment gates
- Environment-based configuration
- Hosting control-panel cron for scheduled tasks where required
- CDN/caching where appropriate

---

## MVP Scope

### Public Website

- Home
- About
- Investment Solutions
- Digital Asset Management
- Wealth Management
- How It Works
- Investment Opportunities
- Insights / Blog
- Insight Details
- FAQ
- Contact
- Privacy Policy
- Terms of Service
- Risk Disclosure
- Cookie Policy

### Admin CMS

- Secure admin authentication
- Dashboard
- Pages/content management
- Investment opportunities management
- Insights/articles management
- Categories/tags
- FAQ management
- Contact enquiries
- SEO metadata
- Site settings
- Media/library uploads
- Audit log for sensitive admin actions

### Explicitly Out of MVP Scope

Do not implement these unless the project scope is formally changed:

- cryptocurrency wallet custody;
- wallet funding;
- deposits and withdrawals;
- trading execution;
- blockchain private-key management;
- automated ROI computation;
- guaranteed-return schemes;
- brokerage execution;
- investment subscription ledger;
- KYC transaction onboarding;
- fiat settlement;
- payout processing;
- portfolio accounting for real client money.

---

## Engineering Principles

1. Thin controllers.
2. Business logic in services.
3. Persistence through repositories.
4. Strict validation at trust boundaries.
5. Centralized authorization.
6. Database migrations under version control.
7. API-first architecture.
8. Security-by-default configuration.
9. Progressive enhancement and accessibility.
10. Automated tests for business-critical behavior.
11. Separate public, admin, and future client-portal concerns.
12. No financial claim or calculation should be hard-coded into UI content.
13. Legal and risk-disclosure content must be editable without code deployment.
14. Observability, deployment rollback, and backups are part of the definition of done.

---

## Suggested Repository Structure

```text
perrymanfinance/
├── backend/
├── web/
├── docs/
├── .github/
│   └── workflows/
├── .env.example
├── README.md
└── Makefile
```

The files in this pack should live under `docs/` in the project repository.
