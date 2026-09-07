# PerrymanFinance Agent Guide

## Purpose

This repository implements PerrymanFinance, an institutional-style digital asset investment and wealth-management information website. The production MVP consists of:

- a public marketing and educational website;
- an investment-opportunity catalogue that is informational only;
- insights, FAQs, legal content, SEO, and site settings;
- contact and consultation lead capture;
- a secure administration CMS.

The MVP is not an exchange, brokerage, wallet, custody product, trading platform, investment ledger, or automated investment engine.

## Source of Truth

Before changing code, read `docs/00-README.md` and the documents relevant to the task:

- `docs/01-product-and-architecture.md`: product scope, users, routes, architecture, and exit criteria;
- `docs/02-build-blueprint.md`: repository structure, entities, API contract, and definition of done;
- `docs/03-backend-architecture-and-workflow.md`: backend boundaries and workflows;
- `docs/04-frontend-architecture-and-workflow.md`: frontend structure, UX, accessibility, and state management;
- `docs/05-data-api-security-compliance.md`: data, API, security, privacy, and financial communications controls;
- `docs/06-devops-testing-deployment.md`: environments, CI/CD, testing, deployment, and operations;
- `docs/07-delivery-phases-roadmap.md`: canonical delivery sequence and phase exit criteria;
- `docs/08-copilot-ready-prompts.md`: detailed implementation checklists for each workstream.

Use this precedence when guidance conflicts:

1. explicit current user requirements;
2. this `AGENTS.md`;
3. product scope and architecture in `docs/00` through `docs/07`;
4. task recipes in `docs/08`;
5. established implementation conventions in the repository.

The roadmap in `docs/07-delivery-phases-roadmap.md` is authoritative for phase order. The more granular prompt numbering in `docs/08-copilot-ready-prompts.md` is descriptive only.

Do not silently resolve a material product, legal, security, or architectural contradiction. Record the issue and request a decision when it would change scope or behavior.

## Non-Negotiable Scope Boundaries

Do not implement or imply any of the following unless the project is formally rescoped after a separate product, legal/compliance, architecture, and threat-model review:

- wallet creation, custody, private-key handling, or blockchain signing;
- deposits, withdrawals, fiat settlement, payouts, or payment processing;
- trade or brokerage execution;
- investment subscriptions or client-money accounting;
- portfolio accounting, transaction ledgers, or automated ROI calculations;
- KYC transaction onboarding;
- guaranteed returns, fabricated performance, or unsupported financial claims;
- unverified licensing, regulatory status, company details, fees, or legal terms.

Use informational CTAs such as “View Details,” “Learn More,” and “Request Information.” Do not introduce “Invest Now,” artificial urgency, fake balances, or speculative crypto-casino patterns.

All financial descriptions, risk notices, and legal content must be CMS-editable. Never invent production legal copy; flag placeholders for review by qualified counsel and the business.

## Architecture

Build a modular monolith with two application surfaces:

- `backend/`: PHP 8.3+ custom OOP MVC REST API;
- `web/`: React, TypeScript, Vite, and Tailwind CSS application.

Supporting areas are `.github/workflows/` and `docs/`. Use MySQL 8+ with InnoDB and `utf8mb4`. Development uses locally installed PHP 8.3+, Composer, MySQL, and Node tooling. Production targets shared Linux hosting and deploys prebuilt frontend assets with production Composer dependencies. Do not assume access to long-running workers, Redis, root privileges, or web-server configuration beyond the selected host's capabilities.

Keep public, admin, and future client-portal concerns isolated. Do not retrofit future financial functions into CMS modules.

Implement features as vertical slices when their prerequisites exist:

```text
Migration/schema -> repository -> service/policy -> controller/route
-> API test -> frontend type/service/query hook -> UI -> UI test
```

Do not create speculative abstractions or unrelated features. Prefer the simplest design that respects the documented boundaries and leaves an obvious extension point.

## Backend Rules

- Use `declare(strict_types=1)`, PSR-4, PSR-12, typed properties, parameter types, and return types.
- Follow SOLID principles and constructor dependency injection.
- Controllers only translate HTTP input/output, invoke validation, and call application services.
- Services own business rules, workflows, transactions, policies, repository coordination, and integrations.
- Repositories own persistence, safe query construction, and row/domain mapping.
- Keep SQL, filesystem work, email transport, and complex authorization out of controllers.
- Keep HTTP and UI concerns out of repositories.
- Avoid God services, oversized base controllers, service locators, global mutable state, and business logic in route files.
- Bind configuration, database connections, and loggers as singletons where appropriate; use factories for integrations.
- Use PDO prepared statements. Whitelist filter and sort columns; never interpolate untrusted identifiers or values.
- Use database transactions for multi-record consistency, including token rotation, role changes, publishing, and article/tag updates.
- Use standardized JSON responses under `/api/v1`.

Success responses follow:

```json
{"success":true,"data":{},"meta":{},"message":null}
```

Errors follow:

```json
{"success":false,"error":{"code":"STABLE_CODE","message":"Safe message","fields":{}}}
```

Use correct HTTP status codes. Never expose stack traces, SQL details, secrets, or internal diagnostics in production responses.

## Domain and Workflow Rules

Keep the documented modules explicit: Identity, Content, Investment, Insights, Enquiries, and SEO.

For pages, articles, investment opportunities, and legal documents, use the workflow:

```text
draft -> review -> published -> archived
```

Public endpoints must expose published content only. Publishing records actor and timestamp and invalidates relevant caches. Legal documents must retain meaningful version and effective-date information.

Investment opportunities are catalogue entries only. Require risk classification and appropriate disclaimers before publication. Do not calculate returns.

Enquiries use `new`, `in_progress`, `resolved`, `spam`, or `closed`. Validate and persist an enquiry before attempting notification. An email-provider failure must not lose the submission. Return a generic public success response where appropriate and avoid exposing internal IDs.

Validate allowed structured CMS section types server-side. Sanitize rich content with an allowlist and prohibit arbitrary scripts.

## Data and Migrations

- Manage all schema changes through version-controlled migrations.
- Use foreign keys, explicit indexes for real access paths, appropriate unique constraints, UTC timestamps, and predictable naming.
- Cap pagination server-side and follow the documented `page`, `per_page`, `total`, and `total_pages` contract.
- Use soft deletion only where it has defined business value.
- Once deployed, migrations are immutable. Add a new migration instead of editing history.
- Prefer expand/migrate/contract for risky production changes and keep migrations forward-compatible during deployment.
- Seed development/test data only. Never seed real credentials, secrets, claims, or production personal data.
- Do not add automatic destructive production reset commands.
- If choosing between explicit and polymorphic SEO relationships, document the decision and use it consistently.

## Authentication, Authorization, and Security

- Admin access is deny-by-default and enforced by backend RBAC, regardless of frontend visibility.
- Use short-lived access tokens and hashed, persisted, rotating refresh tokens. Revoke sessions on logout and password reset.
- Hash passwords with PHP password APIs. Rate-limit login, password reset, enquiries, and abuse-prone endpoints.
- Prevent user enumeration in password-reset responses. Never log passwords, reset tokens, JWTs, refresh tokens, private keys, or secrets.
- If browser authentication uses cookies, use Secure, HttpOnly, suitable SameSite settings, and CSRF protection for state changes.
- Restrict CORS to configured origins and apply CSP, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, frame restrictions, and production HSTS.
- Validate all input at trust boundaries. Escape output and sanitize permitted rich text.
- For uploads, enforce authentication/authorization, maximum byte and dimension limits, server-side MIME inspection, actual image decoding, randomized names, and non-executable storage. Sanitize SVG safely or reject it.
- Store secrets only in environment/secret management. Commit `.env.example`, never `.env`, credentials, signing keys, or production secrets.
- Collect only necessary personal data, restrict enquiry access, record consent where required, and preserve a path for retention, deletion, and export obligations.
- Audit sensitive actions: authentication events as appropriate, users/roles, publishing, legal changes, settings, and deletion/archive actions. Exclude secrets from audit snapshots.

## Frontend Rules

- Use React functional components and TypeScript.
- Organize shared primitives under components and domain behavior under feature modules.
- Use one API client for base URL, headers, auth/refresh, timeout/cancellation, request IDs, and normalized errors.
- Keep API calls in service modules and wrap server state in TanStack Query or an equivalent query/cache layer. Do not scatter `fetch()` calls through components or duplicate server data in a global store.
- Invalidate relevant query keys after mutations.
- Every data-driven screen must deliberately handle loading, empty, error, and success states.
- Every form needs labels, accessible validation feedback, server error mapping, submit progress, duplicate-submit prevention, and success confirmation.
- Protect admin routes, render permission-aware navigation, and treat backend authorization as authoritative.
- Use mobile-first responsive layouts, semantic HTML, landmarks, a skip link, logical headings, keyboard support, visible focus, sufficient contrast, descriptive alt text, and reduced-motion preferences. Use ARIA only when native semantics are insufficient.
- Use route-level code splitting, responsive optimized images, lazy loading below the fold, optimized fonts, and cacheable hashed assets. Justify large dependencies.
- Admin destructive actions require clear confirmation and server-side permission enforcement.

The visual language must be calm and institutional: deep navy, restrained blue/emerald accents, strong typography, generous whitespace, and disciplined motion. Avoid neon-heavy crypto visuals, animated coin clutter, fake dashboards, profit claims, and countdowns.

## SEO and Public Content

Every public route must support title, meta description, absolute production canonical URL, robots directive, Open Graph data, social image, and relevant JSON-LD. Provide sitemap, robots, redirects for renamed published slugs, and structured data where applicable for Organization, Article, BreadcrumbList, and FAQPage.

Exclude admin routes, drafts, previews, and noindex content from the sitemap and public APIs. Do not hard-code claims or important content that administrators must be able to update.

Target WCAG 2.1 AA practices and fast meaningful rendering. An SEO-friendly rendering approach must be selected and documented before public-page architecture is locked; a client-only SPA must not be assumed to satisfy the documented SSR/ISR and crawlability expectations without evidence.

## Tests and Verification

Every meaningful change includes proportionate automated coverage. Do not weaken assertions or delete tests merely to make CI pass.

Backend coverage should include:

- unit tests for validators, services, policies, permissions, transitions, and slug logic;
- integration tests for repositories, migrations, constraints, and transactions;
- feature/API tests for authentication, authorization, CRUD, publication visibility, validation, pagination, enquiries, uploads, and error mapping;
- security cases for injection payloads, malicious uploads, throttling, invalid/expired/revoked tokens, XSS sanitization, and unauthorized object access.

Frontend coverage should include component tests, feature integration tests, and critical E2E flows: navigation, enquiry submission, admin login, and create/edit/publish workflows for core content.

Before reporting completion, run the narrowest relevant checks and then the broader available quality gates. Expected gates include PHP syntax/style, static analysis, backend tests, frontend lint, TypeScript checks, frontend tests, production build, and dependency/security checks. If a check cannot run, report the exact command and reason; do not claim it passed.

## Documentation and Delivery

Update documentation alongside behavior, API contracts, configuration, migrations, deployment steps, and architectural decisions. Keep `.env.example` synchronized with required variables, using safe placeholders.

The definition of done includes:

- acceptance criteria satisfied and scope respected;
- validation, authorization, error handling, and audit/security impact addressed;
- schema and API changes documented;
- relevant automated tests passing;
- responsive and accessibility behavior checked;
- no secrets or unsupported financial/legal claims introduced;
- CI/build checks passing;
- operational impact considered, including cache invalidation, migrations, backup/restore, logs, health checks, and rollback.

Deployment must use versioned release artifacts where the shared host permits them, environment-specific secrets, reviewed migrations, health and smoke checks, observability, backups, and a documented provider-compatible rollback path. Production debug output must be disabled. Source, secrets, logs, tests, migrations, and writable uploads must not be publicly executable or readable.

## Agent Working Method

For each implementation request:

1. Read the relevant documentation and inspect the current code, tests, configuration, and local conventions.
2. Confirm the requested work is inside the MVP boundary and identify dependencies or unresolved legal/product inputs.
3. State the intended files, schema/API impact, security considerations, and test approach when the change is non-trivial.
4. Implement only the requested vertical slice and preserve unrelated user changes.
5. Add or update tests and documentation in the same change.
6. Run relevant verification and review the diff for scope, security, accessibility, and accidental claims.
7. Report the outcome, files changed, checks run, and any remaining risks or decisions.

Do not mark placeholders, unapproved copy, skipped verification, or unimplemented operational work as production-ready.
