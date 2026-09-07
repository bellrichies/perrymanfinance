# PerrymanFinance Phase 0 Implementation Checklist

## Status and Scope

This document validates the requirements and architecture described in `docs/00-README.md` through `docs/08-copilot-ready-prompts.md`. At the time of review, the repository contains documentation and `AGENTS.md` only; no product code, tests, runtime configuration, or infrastructure has been implemented.

The MVP is limited to a public corporate and educational website, an informational investment-opportunity catalogue, insights and FAQs, editable legal content, technical SEO, enquiry capture, and a secure administration CMS. It does not move money or assets and does not provide client portfolios, custody, wallets, trading, brokerage execution, subscriptions, KYC transaction onboarding, or return calculations.

## 1. Assumptions

### Confirmed architectural assumptions

- The system is a modular monolith with two application surfaces: a PHP 8.3+ REST API under `backend/` and a React/TypeScript web application under `web/`.
- MySQL 8+ with InnoDB, `utf8mb4`, foreign keys, versioned migrations, and UTC timestamps is the system of record.
- Public and admin experiences share the web application but use separate layouts, route trees, API policies, and authorization boundaries.
- All API routes use `/api/v1`; successful and failed responses use the documented JSON envelopes.
- Public content is read from the API. Important marketing, investment, risk, and legal content is CMS-editable and is not embedded as production claims in frontend code.
- Pages, articles, investment opportunities, and legal documents use `draft -> review -> published -> archived`. Public APIs return published records only.
- Admin authorization is deny-by-default and enforced by the backend. Frontend permission-aware controls are usability aids, not security controls.
- A single API client and a TanStack Query-style server-state layer are used by the frontend.
- Local development uses installed PHP 8.3+, Composer, MySQL, and Node tooling. An independent SMTP catcher or test account is configured when email work begins. Redis and continuously running workers are not assumed on shared hosting.
- SMTP through a PHPMailer abstraction is the initial email integration. Email failure cannot roll back or lose a valid persisted enquiry.
- Media is initially handled through an abstracted storage interface so local non-executable storage can later be replaced by object storage without changing domain workflows.
- Public CTAs use terms such as “View Details,” “Learn More,” and “Request Information.”

### Decisions required before the affected work is finalized

- **Business and legal identity:** operating entity, jurisdiction, addresses, contact details, regulatory status, services, fees, dispute process, and approved disclaimers must come from the business and qualified counsel.
- **Content approval:** final brand assets, page copy, team information, risk language, legal documents, and any investment minimum/currency display require approval.
- **SEO rendering:** choose and document SSR, prerendering/static generation, or another verified crawlable approach before Phase 7 public architecture is locked. A client-only SPA is not assumed sufficient.
- **Browser authentication transport:** choose either secure HttpOnly cookies with CSRF protection or another documented access/refresh-token strategy. Tokens must not be placed in insecure persistent browser storage by default.
- **SEO association:** choose explicit per-entity relationships (preferred for this custom framework) or a polymorphic relation and apply the decision consistently.
- **Privacy:** approve enquiry purpose text, consent wording, retention periods, deletion/export process, analytics policy, and cookie behavior.
- **Hosting and operations:** select a shared Linux hosting provider/plan, DNS/CDN, object storage if needed, SMTP provider, monitoring/error tracking, backup destination, retention, recovery objectives, deployment access, cron capabilities, and approvers.
- **Optional features:** newsletter subscription, market data, team section, article search, enquiry assignment/notes, Redis, queues, analytics, and client-login affordances remain excluded unless separately approved.

## 2. Resolved Architecture

### Runtime and repository topology

```text
Browser
  -> Shared-hosting web server / HTTPS
     -> React public/admin application
     -> PHP front controller at /api/v1
        -> router and middleware pipeline
        -> thin controller and validated request DTO
        -> application service / policy / transaction
        -> repository or external integration
        -> MySQL, media storage, SMTP
```

The repository contains `backend/`, `web/`, `.github/workflows/`, root environment examples, a Makefile, and project setup documentation. Production deployment uses shared Linux hosting and prebuilt frontend assets.

### Backend boundaries

| Layer | Responsibility | Must not contain |
| --- | --- | --- |
| HTTP/controller | Translate request and response, invoke validation and services | SQL, filesystem operations, email transport, complex policy or business rules |
| Application service/policy | Workflows, transitions, authorization policies, transactions, repository coordination, integrations | Presentation behavior |
| Repository | Prepared SQL, whitelisted query construction, persistence, row/domain mapping | HTTP, UI decisions, authorization, email |
| Integration | SMTP, media storage, optional observability/jobs behind interfaces | Domain policy |
| Middleware | Request ID, JSON parsing, CORS, headers, rate limiting, authentication, permission and audit context | Feature-specific business workflows |

Core configuration, PDO connection, and logger bindings are singletons where appropriate. Integration clients use factories. Controllers remain thin, and neither a service locator nor a God base class is introduced.

### Domain modules

| Module | Responsibilities | Principal entities/tables |
| --- | --- | --- |
| Identity | Admin authentication, RBAC, reset and refresh flows, audit | `admin_users`, `roles`, `permissions`, `role_permissions`, `user_roles`, `password_reset_tokens`, `refresh_tokens`, `audit_logs` |
| Content | Marketing pages and sections, legal documents, media, settings | `pages`, `page_sections`, `legal_documents`, `media_assets`, `site_settings` |
| Investment | Informational opportunity catalogue and risk classification | `investment_categories`, `investment_opportunities` |
| Insights | Articles, taxonomies, featured/related content and FAQs | `article_categories`, `articles`, `tags`, `article_tags`, `faqs` |
| Enquiries | Contact/consultation capture and admin workflow | `enquiries` |
| SEO | Metadata, canonical/index controls, redirects and sitemap inputs | `seo_metadata`, `redirects` |

Optional `jobs`, `failed_jobs`, and application-managed rate-limit tables are added only if the selected implementation needs them.

### Public route map

| Web route | Data/API dependency | Required behavior |
| --- | --- | --- |
| `/` | pages, public settings, featured investments/articles | Approved homepage sequence, risk statement, responsive and crawlable output |
| `/about` | `GET /pages/{slug}` | Company, philosophy, approved team/details |
| `/investment-solutions` | `GET /pages/{slug}` | Informational services only |
| `/digital-assets` | `GET /pages/{slug}` | Education and approved risk-aware service content |
| `/wealth-management` | `GET /pages/{slug}` | Approved service content without unsupported claims |
| `/how-it-works` | `GET /pages/{slug}` | Informational process ending in enquiry, not transaction |
| `/investments` | `GET /investments`, `GET /investment-categories` | Published-only catalogue, safe filters and capped pagination |
| `/investments/:slug` | `GET /investments/{slug}` | Risk classification, disclaimer, risk notice, request-information CTA |
| `/insights` | `GET /insights`, categories/tags | Published-only list, filters and pagination |
| `/insights/category/:slug` | `GET /insights` with category filter | Category archive; required by product IA though omitted from the frontend route list |
| `/insights/:slug` | `GET /insights/{slug}` | Article metadata, related content and applicable disclaimer |
| `/faq` | `GET /faq` | Ordered published FAQs and FAQ structured data when eligible |
| `/contact` | `POST /enquiries`, public settings | Accessible validation, consent, anti-spam, duplicate prevention, generic success |
| `/privacy-policy` | `GET /legal/{slug}` | Current approved published version |
| `/terms` | `GET /legal/{slug}` | Current approved published version |
| `/risk-disclosure` | `GET /legal/{slug}` | Prominent approved published version |
| `/cookie-policy` | `GET /legal/{slug}` | Must match actual cookie/analytics use |
| fallback 404 | none or settings | Accessible branded not-found page |

Every public route requires a title, description, absolute production canonical URL, robots directive, Open Graph data, social image, and applicable JSON-LD. Only published, indexable canonical URLs enter the sitemap.

### Admin route map

| Web route | Capability/API area | Authorization |
| --- | --- | --- |
| `/admin/login` | login, forgot/reset flow | Anonymous-only where appropriate; throttled |
| `/admin` or `/admin/dashboard` | dashboard summaries | Authenticated admin |
| `/admin/pages` | page/section CRUD, preview, publish, SEO | granular `pages.*` |
| `/admin/investments` | opportunity/category CRUD and publishing | granular `investments.*` |
| `/admin/insights` | article CRUD, preview and publishing | granular `articles.*` |
| `/admin/categories` | article categories | corresponding article administration permission |
| `/admin/tags` | tag management | corresponding article administration permission |
| `/admin/faqs` | FAQ management | `faqs.manage` |
| `/admin/enquiries` | list, detail, filters, status | `enquiries.view` / `enquiries.update` |
| `/admin/media` | secure media upload/library/delete | `media.manage` |
| `/admin/seo` | metadata and redirects | explicit SEO permission must be added or incorporated into content permissions |
| `/admin/settings` | public settings | `settings.manage` |
| `/admin/users` | staff users, roles and permissions | `users.manage` |
| `/admin/audit-logs` | immutable audit review | `audit.view` |
| legal editor under pages or dedicated route | versioned legal workflow | `legal.manage` |

All admin routes use guards and deliberate loading/error states, but every API action independently authenticates and authorizes the principal.

### API and data conventions

- Success envelope: `{"success":true,"data":{},"meta":{},"message":null}`.
- Error envelope: `{"success":false,"error":{"code":"STABLE_CODE","message":"Safe message","fields":{}}}`.
- Pagination fields are `page`, `per_page`, `total`, and `total_pages`; `per_page`, filters, and sortable columns are server-controlled/whitelisted.
- Publishing records actor and time and invalidates related public caches. Multi-record publishing, token rotation, role changes, and article/tag changes are transactional.
- Slugs are unique in the relevant namespace. Renaming published slugs preserves redirects.
- Structured CMS section types are server-side allowlisted. Rich content is sanitized and rendered safely.
- Legal records retain document type, version, effective date, status, content, publication actor, and publication time.
- Enquiries are persisted before notification. Public responses reveal neither internal identifiers nor provider failures.

### External integration map

| Integration | MVP status | Failure/operational contract |
| --- | --- | --- |
| SMTP/email provider via PHPMailer | Required for notifications; persistence remains authoritative | Log safe failure with request ID; alert/retry if a queue is adopted; never lose enquiry |
| Local SMTP catcher/test account | Required only for development email testing | Independently selected; no production exposure |
| Media storage | Required; local non-executable storage acceptable initially | Interface-backed; media backup required; object storage/CDN optional |
| Error tracking/metrics/uptime | Required capability before production; vendor undecided | No secrets or personal message bodies; alert on API, DB, auth and mail symptoms |
| CDN | Optional | Introduce for measured asset/caching needs |
| Redis/queue worker | Optional | Add only with documented reliability/performance need |
| Market data provider | Excluded from MVP baseline | Requires approved use case, source/licensing and stale-data behavior |
| Analytics | Deferred pending privacy review | Consent/cookie behavior and data minimization must be approved |

## 3. Requirement-to-Module Matrix

| Requirement | Backend modules | Frontend features/routes | Data | Core verification |
| --- | --- | --- | --- | --- |
| PR-001 Responsive public site | Content, Investment, Insights, SEO | all public routes, responsive design system and layouts | published content/settings | viewport checks, navigation E2E, no broken routes |
| PR-002 Editable important content | Content, SEO, Identity | page/section, settings, media and SEO editors | pages, sections, media, settings, SEO | CRUD, authorization, validation, preview/publish visibility |
| PR-003 Opportunity catalogue | Investment, Content, SEO | public list/detail; admin category/editor/publish UI | categories, opportunities, media, SEO | risk/disclaimer gates, slug, filters, pagination, draft invisibility |
| PR-004 Insights publishing | Insights, Content, SEO | index/detail/category; article/taxonomy editors | articles, categories, tags, joins, media, SEO | relationships, filtering, related content, publication visibility |
| PR-005 Contact/consultation leads | Enquiries, Identity/audit, email integration | accessible contact form; admin list/detail/status | enquiries; optional jobs | validation, throttling, duplicate handling, email failure durability, RBAC |
| PR-006 Editable/versioned legal content | Content, SEO, Identity/audit | legal routes and editor/preview/publish UI | legal documents, SEO, audit | version/effective date, approval, public current version, audit |
| PR-007 SEO | SEO plus every publishable module | metadata manager, route head/JSON-LD, sitemap/robots | SEO metadata, redirects | canonical, noindex/draft exclusion, redirect and structured-data tests |
| PR-008 WCAG 2.1 AA practices | API errors support usable UI | semantics, skip link, keyboard/focus, contrast, reduced motion, accessible forms | alt text/media metadata where relevant | automated accessibility plus manual keyboard/screen/viewport review |
| PR-009 Secure administration | Identity and all protected modules | login/reset/session-expiry, guards, permission-aware navigation | identity, token, role/permission tables | invalid/expired/revoked tokens, CSRF if cookies, object access and permission denial |
| PR-010 Auditability | Identity/audit and sensitive services | audit viewer | audit logs | actor/action/entity/request context; secrets excluded; sensitive actions covered |

### Test requirement map

| Level | Required coverage |
| --- | --- |
| Backend unit | validators, slug rules, services, policies/permissions, state transitions, sanitization behavior |
| Backend integration | migrations, repositories, foreign/unique constraints, transactions, pagination queries and cache invalidation boundaries |
| Backend API/feature | response envelopes and status codes; auth lifecycle; RBAC; all CRUD/public endpoints; publication visibility; validation; pagination; enquiry durability; upload rejection; 404/405 |
| Security | injection payloads, stored/reflected XSS, malicious/oversized/dimension-invalid uploads, brute force and 429, CSRF when applicable, CORS, unauthorized object access, invalid/expired/revoked tokens, production error masking |
| Frontend component | primitives, cards, status/publish controls, forms, permission rendering, loading/empty/error/success states |
| Frontend integration | API error mapping, filters/pagination, auth refresh/expiry, content editors, contact submission and mutation invalidation |
| E2E | public navigation; investment and insight list/detail; contact submission; admin login; create/edit/review/publish core content; legal update; media rejection |
| Release validation | responsive and browser checks, accessibility review, SEO/canonical/structured-data checks, links, staging smoke tests, UAT, restore test and rollback rehearsal |

### Security control map

- **Identity:** PHP password APIs; inactive-account checks; short access-token lifetime; hashed persisted rotating refresh tokens; logout/password-reset revocation; expiring reset tokens; non-enumerating reset response.
- **Authorization:** deny by default; permission middleware and service/policy checks; object-level authorization; dangerous admin actions confirmed in UI and enforced on server.
- **Input/data:** boundary validation; PDO prepared statements; whitelisted identifiers; safe pagination caps; output escaping; allowlist rich-text/section sanitization.
- **Browser/API:** HTTPS, restricted CORS, CSP, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, frame restrictions, production HSTS, and CSRF/SameSite controls if cookies are used.
- **Abuse:** rate limits for login, resets, enquiries, and other measured abuse paths; generic public responses; optional idempotency key for forms.
- **Uploads:** auth/RBAC, byte and dimension limits, server MIME inspection, actual decoding, randomized names, non-executable storage, SVG rejection unless safely sanitized.
- **Privacy:** data minimization, approved consent/purpose, restricted enquiry access, retention/deletion/export path, no sensitive log payloads.
- **Operations:** secrets only in environment/secret management; least-privilege DB account; masked production exceptions; structured request IDs; dependency scanning; encrypted backups and tested restores.
- **Audit:** authentication events as appropriate, user/role changes, publishing, legal/settings changes, and delete/archive actions; no password/token/secret snapshots.

## 4. Dependency Order

The phase numbering in `docs/07-delivery-phases-roadmap.md` is canonical. The separate migration prompt in `docs/08` is treated as work within the repository/foundation phase, not as a new canonical phase.

1. **Phase 0 — scope freeze:** approve business/legal facts, information architecture, content inventory, rendering decision, privacy rules, optional-feature exclusions, and this checklist.
2. **Phase 1 — foundation:** repository layout, local PHP/Composer/MySQL/Node requirements, shared-hosting compatibility, PHP and React bootstraps, CI baseline, test tools, migration runner and initial schema, health endpoint, environment examples, setup guide.
3. **Phase 2 — backend core:** router, request/response, middleware, DI, PDO/transactions, validation, logging, error mapping, response envelopes and framework tests.
4. **Phase 3 — identity/security:** admin users, RBAC, authentication lifecycle, reset, rate limiting, security middleware, audit foundations and admin auth UI.
5. **Phase 4 — CMS/legal:** pages/sections, legal versions, settings, media, SEO base model, workflows, admin editors, public content endpoints and cache invalidation.
6. **Phase 5 — investments:** category/opportunity vertical slice, publication risk gates, admin and public UI, SEO and tests.
7. **Phase 6 — insights/FAQ:** editorial taxonomy, articles/tags/FAQ, related content, admin/public UI, SEO and tests.
8. **Phase 7 — public marketing:** approved rendering strategy, design system, layouts and remaining marketing routes composed from existing APIs.
9. **Phase 8 — enquiries:** persistence-first submission, consent/anti-spam/rate limit, email abstraction, admin workflow and tests.
10. **Phase 9 — SEO/performance/accessibility:** sitemap/robots/redirect completion, JSON-LD, caching/image/font optimization and accessibility remediation.
11. **Phase 10 — QA/security/UAT:** full regression/E2E/security suite, dependency scan, permission/upload review, staging content and stakeholder acceptance.
12. **Phase 11 — production:** versioned release artifacts, reviewed forward-compatible migrations, secrets, HTTPS, backups, shared-host deployment, health/smoke checks, monitoring and rollback.
13. **Phase 12 — post-launch:** error/mail/lead/SEO/performance/access/backup review and a prioritized backlog within MVP boundaries.

Within Phases 4–8, each feature follows: migration/schema -> repository -> service/policy -> controller/route -> API tests -> frontend types/service/query hook -> UI states/accessibility -> UI tests -> documentation.

## 5. Contradictions, Gaps, Scope Expansion and Risks

### Reconciled documentation differences

| Finding | Resolution |
| --- | --- |
| `docs/07` Phase 1 includes migrations, while `docs/08` gives migrations a separate Phase 3 and shifts later numbers | Follow `docs/07` phase order. Use `docs/08` prompts as detailed work packages, not authoritative phase numbering. |
| `/admin` and `/admin/dashboard` both describe the dashboard | Make `/admin` the canonical route and redirect `/admin/dashboard`, or choose the reverse before frontend routing. Avoid two indexable/canonical admin URLs. |
| Product IA includes `/insights/category/{slug}`, but frontend route documentation omits it | Include the category route because the product IA has precedence. |
| Product IA has dedicated `/admin/categories`, `/admin/tags`, and `/admin/seo`; frontend route documentation omits them | Include them or document their deliberate nesting under insights/content before implementation. Capabilities must remain available. |
| `Portfolio Management` appears as a service label while portfolio accounting is prohibited | Treat it only as approved advisory/marketing content. Do not store client holdings, calculate performance, or imply an operational portfolio platform. |
| JWT is suggested while browser token transport is undecided | JWT alone is not the decision. Record an authentication ADR covering storage, cookie/CSRF behavior, rotation and revocation before Phase 3. |
| SEO relations may be polymorphic or explicit | Prefer explicit relations for clarity unless an ADR demonstrates a better alternative. |
| The docs mention retries/queues but make workers optional | Persist first; introduce a job table/worker only when email delivery requirements justify operational complexity. |

### Missing or unresolved requirements

- No approved business identity, jurisdiction, regulatory wording, financial claims, fees, disclaimers, legal copy, privacy notices, or retention schedule exists.
- No approved sitemap/page content inventory identifies which marketing sections are structured CMS blocks and which fields are required.
- No formal state-transition matrix defines who may move each publishable entity between statuses, whether published content can return to draft, or how scheduled/future effective legal versions behave.
- No permission names are defined for SEO, investment categories, article categories/tags, or dashboard access; the final permission catalogue and role matrix need approval.
- No exact API payload schemas, stable error-code catalogue, filter vocabulary, maximum page size, maximum field lengths, or concurrency strategy is defined.
- No choice exists for admin authentication transport, JWT algorithm/key rotation, token lifetime, session/device management, or CSRF implementation.
- No upload allowlist, byte/dimension limits, derivative-generation policy, media deletion/reference behavior, storage provider, or backup method is fixed.
- No cache implementation, TTLs, invalidation event list, HTTP caching policy, or public preview security design is fixed.
- No enquiry transition rules, recipient routing, retry/alert policy, spam mechanism, retention, export/deletion procedure, or administrator data-access policy is approved.
- No SEO rendering architecture, canonical production hostname, locale strategy, preview/noindex behavior, redirect conflict policy, or sitemap scale/update strategy is approved.
- No supported browser/device matrix, measurable performance budgets, accessibility tooling/acceptance protocol, or analytics requirements are defined.
- No CI vendors/runners, static-analysis level, coverage expectations, dependency-policy thresholds, branching/release policy, hosting topology, recovery objectives, or rollback ownership is defined.
- No health/readiness contract defines whether database and external dependencies are reported separately.

These gaps do not justify inventing behavior. Resolve each before its dependent phase exits; safe defaults may be proposed in an ADR during that phase.

### Accidental scope-expansion watchlist

- Newsletter functionality is conditional and is not part of the baseline MVP.
- Market feeds, live prices, charts, trading signals, and market-data subscriptions are excluded unless explicitly approved.
- Existing-client login links must not become a client account or portal in MVP.
- “Portfolio Management” and “Structured Investment Opportunities” are informational content, not ledgers, subscriptions, suitability decisions, execution, or reporting systems.
- Investment minimum and currency are display-only, optional, and require verified approved source data.
- Enquiry assignment and internal notes are optional; do not build a general CRM.
- Jobs, Redis, object storage, CDN, analytics, search, and image pipelines are implementation options, not automatic scope.
- Do not add KYC, identity verification, document signing, payments, wallets, deposits, withdrawals, payouts, custody, transactions, returns, balances, or performance dashboards.

### Principal implementation risks

| Risk | Impact | Mitigation/gate |
| --- | --- | --- |
| Unapproved legal or financial copy | Regulatory, reputational and launch risk | Use clearly marked non-production placeholders only; counsel/business approval blocks production |
| Client-only rendering chosen too late | SEO rework and poor crawlability | Approve and spike rendering strategy before Phase 7 architecture locks |
| Custom MVC security defects | Authentication, routing or error leakage | Build narrow core, test middleware ordering and negative cases, perform security review before release |
| Token storage/rotation ambiguity | Session theft or unreliable logout | Authentication ADR and threat review before Phase 3 implementation |
| Flexible CMS content enables XSS/layout breakage | Public compromise and unstable pages | Section schema allowlists, rich-text sanitization, safe renderer and malicious-payload tests |
| Upload handling exposes executable or oversized files | Remote execution, storage abuse | Decode/MIME/dimension checks, randomized names, non-executable storage, quotas and adversarial tests |
| Authorization only represented in UI | Unauthorized admin access | Deny-by-default middleware/policies, object checks and permission test matrix |
| Enquiry email coupled to persistence | Lost leads | Commit valid enquiry before notification; observable retry/recovery path |
| Personal data retained indefinitely | Privacy/compliance risk | Approve retention/export/deletion controls and access restrictions before launch |
| Cache serves drafts or stale legal/risk content | Disclosure and trust failure | Publication-aware keys, synchronous invalidation, short safe TTLs and visibility tests |
| Schema deployed incompatibly | Outage/data loss | Immutable migrations, expand/migrate/contract, backup checkpoint and rollback runbook |
| Optional infrastructure added prematurely | Delivery and operational drag | Require a measured or contractual justification for Redis, workers, CDN and object storage |
| Documentation encoding is visibly corrupted in some terminal output | Copy/paste and readability defects | Normalize/verify UTF-8 when files are next edited; avoid mechanical rewrite without diff review |

## 6. Final MVP Checklist

### Phase 0 — approval gates

- [ ] MVP and explicit out-of-scope list signed off.
- [ ] Public and admin route maps approved, including the category and dashboard canonical routes.
- [ ] Business identity, jurisdiction, services, claims and contact details supplied and verified.
- [ ] Content inventory, ownership and approval workflow completed.
- [ ] Legal/privacy/risk dependencies and counsel approver identified.
- [ ] SEO rendering ADR approved.
- [ ] Authentication/token transport ADR approved before identity implementation.
- [ ] Privacy, consent, retention, deletion/export, cookies and analytics decisions recorded.
- [ ] Optional features explicitly accepted or deferred.

### Engineering foundation and architecture

- [ ] Planned monorepo directories, root tooling and setup documentation exist.
- [ ] Local PHP 8.3+, Composer, MySQL, Node/npm, and optional SMTP test tooling are documented and usable.
- [ ] Shared-hosting requirements, document roots, rewrite behavior, cron support, writable paths, and deployment access are verified.
- [ ] Safe `.env.example` files exist; production debug defaults off; no secrets are committed.
- [ ] PHP 8.3+, Composer PSR-4/PSR-12, strict types, static analysis and PHPUnit are configured.
- [ ] React, TypeScript, Vite, Tailwind, lint/typecheck/component/E2E tools and query client are configured.
- [ ] CI runs syntax/style/static analysis, tests, production build and dependency/security checks.
- [ ] Migration/seed commands are deterministic, safe and documented; no destructive production reset exists.
- [ ] `/api/v1/health`, request IDs, structured logs and safe exception responses work.

### Data and API

- [ ] All required identity, content, investment, insight, enquiry and SEO migrations use InnoDB, `utf8mb4`, UTC, foreign keys, constraints and query-driven indexes.
- [ ] SEO relationship ADR and schema are consistent.
- [ ] Repositories use PDO prepared statements and whitelisted filter/sort identifiers.
- [ ] API envelopes, status codes, stable errors, pagination caps and payload schemas are documented and tested.
- [ ] Multi-record changes use transactions; migrations remain immutable after deployment.
- [ ] Public endpoints expose published content only; previews are authenticated and noindex.
- [ ] Publication records actor/time and invalidates all relevant caches.
- [ ] Published slug changes create safe redirects without loops/conflicts.

### Identity, authorization and audit

- [ ] Admin login, current-user, refresh rotation, logout/revocation and forgot/reset flows work.
- [ ] Passwords use PHP password APIs; refresh/reset tokens are hashed at rest and expire.
- [ ] Login/reset rate limits and non-enumerating reset behavior are verified.
- [ ] Permission catalogue and role matrix cover every admin capability, including SEO/taxonomies.
- [ ] Backend authentication, permission and object-level checks are deny-by-default.
- [ ] Cookie security/CSRF or approved alternative token protections are implemented.
- [ ] Sensitive changes generate useful audit records without secrets.
- [ ] Admin route guards, session expiry and permission-aware navigation handle loading/error states.

### CMS, legal, settings and media

- [ ] Marketing pages and allowlisted sections can be drafted, reviewed, previewed, published and archived.
- [ ] Legal documents retain version, effective date, publication state and actor history.
- [ ] Site settings distinguish public-safe fields from confidential configuration.
- [ ] SEO metadata is editable for every public content type.
- [ ] Rich content and structured sections reject unsafe scripts/markup.
- [ ] Media uploads enforce auth, permissions, byte/dimension/MIME/decode checks and randomized safe names.
- [ ] Upload storage cannot execute content; SVG is rejected unless an approved sanitizer exists.
- [ ] Media reference/deletion rules and backup coverage are tested.
- [ ] Drafts, archived records and previews never leak through public APIs, sitemap or caches.

### Investment catalogue

- [ ] Categories and opportunities have admin CRUD, preview and controlled publication workflow.
- [ ] Publication requires approved risk classification and disclaimer.
- [ ] Public list/detail implement safe filters, capped pagination and published-only visibility.
- [ ] Detail UX prominently presents risk and uses request-information CTAs.
- [ ] Optional minimum/currency values appear only when verified and approved.
- [ ] No transaction, funding, subscription, wallet, return, balance or urgency behavior exists.
- [ ] Slug, validation, permissions, publication, pagination and malicious-input tests pass.

### Insights and FAQ

- [ ] Articles, categories, tags and relationships have complete admin workflows.
- [ ] Article content, cover media, author, dates, featured state and SEO are managed.
- [ ] Public article list/detail/category filtering, pagination and related content work.
- [ ] FAQs are ordered, publishable and safely rendered; category is added only if approved.
- [ ] Relevant content carries approved disclaimer/risk treatment.
- [ ] Publication, taxonomy, ordering, filtering, pagination, sanitization and visibility tests pass.

### Public experience and accessibility

- [ ] All listed public routes and accessible 404 render using the approved crawlable strategy.
- [ ] Header, mobile navigation, footer, breadcrumbs, cards, process, CTA, risk, FAQ and state components are reusable.
- [ ] Every data screen intentionally handles loading, empty, error and success states.
- [ ] Every form has labels, inline/server errors, error summary where needed, progress, duplicate prevention and confirmation.
- [ ] Mobile, tablet, laptop and desktop layouts pass the approved browser/device matrix.
- [ ] Semantic landmarks/headings, skip link, keyboard behavior, visible focus, contrast, alt text and reduced motion meet WCAG 2.1 AA practices.
- [ ] Route splitting, responsive/lazy images, fonts and hashed asset caching meet approved performance budgets.
- [ ] No fake dashboards, unverified claims, guaranteed returns, aggressive investment CTA, countdown or crypto-casino styling exists.

### Enquiries

- [ ] Required and optional fields, enquiry types, consent copy and validation limits are approved.
- [ ] Server validation, sanitization, rate limits and anti-spam controls work.
- [ ] Valid enquiry is committed before email is attempted; generic public response exposes no internal ID/provider state.
- [ ] Duplicate submission handling is implemented at UI and server levels as approved.
- [ ] SMTP configuration, recipient routing, delivery alerts and recovery/retry procedure are verified.
- [ ] Admin list/detail/search/filter and allowed status transitions enforce RBAC.
- [ ] Enquiry access, audit, retention, deletion and export obligations are operational.
- [ ] Validation, throttling, provider-failure durability, unauthorized access and transition tests pass.

### SEO, security, operations and launch

- [ ] Every public route has approved title, description, absolute canonical, robots, Open Graph/social image and applicable JSON-LD.
- [ ] Sitemap/robots exclude admin, drafts, previews, archived and noindex content.
- [ ] Organization, Article, BreadcrumbList and eligible FAQPage structured data validate.
- [ ] HTTPS, production HSTS, CSP, CORS allowlist, content-type, referrer, permissions and frame controls are verified.
- [ ] Production errors mask stack traces/SQL/secrets; logs omit passwords, tokens, secrets and unnecessary enquiry content.
- [ ] Dependency scan, injection/XSS/upload/token/throttle/CSRF/object-access tests have no release blocker.
- [ ] Versioned shared-hosting release artifact, environment secrets, reviewed migrations and health/readiness checks are in place.
- [ ] Automated encrypted DB/media backups, retention and a successful restore test are recorded.
- [ ] Monitoring covers availability, error/latency, DB health, failed-login spikes and mail failures.
- [ ] Deployment, rollback and backup/restore runbooks identify owners and exact procedures.
- [ ] CI is green, staging smoke checks pass, UAT is signed off, links and forms work, and contact email is verified.
- [ ] Legal, privacy, risk, company and investment content has documented business/counsel approval.
- [ ] Production is stable and recoverable before Phase 11 is closed.

## Phase 0 Exit Decision

Architecture and MVP boundaries are sufficiently coherent to begin foundation planning, but Phase 0 is **not yet approved for exit**. The repository needs decisions and sign-off for the business/legal facts, content inventory, SEO rendering approach, browser authentication transport, privacy/retention rules, permission matrix, optional integrations, and production operating model listed above. Product implementation should not silently decide those matters.
