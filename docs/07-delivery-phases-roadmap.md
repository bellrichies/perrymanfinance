# PerrymanFinance — Delivery Phases & Roadmap

# Phase 0 — Discovery and Scope Freeze

## Goals
- confirm MVP;
- confirm company/legal details;
- confirm brand direction;
- inventory required content;
- finalize page map.

## Deliverables
- signed-off requirements;
- out-of-scope list;
- information architecture;
- content checklist;
- legal-review dependencies;
- technical architecture approval.

## Exit Criteria
No major unresolved scope ambiguity.

---

# Phase 1 — Repository and Engineering Foundation

## Build
- monorepo/project structure;
- local PHP/Composer/Node development environment;
- shared-hosting compatibility baseline;
- PHP bootstrap;
- React/Vite bootstrap;
- MySQL;
- migrations;
- CI baseline;
- `.env.example`;
- coding standards;
- test frameworks.

## Exit Criteria
- project boots locally;
- CI runs;
- health endpoint works;
- frontend renders;
- DB migration command works.

---

# Phase 2 — Backend Core Framework

## Build
- config;
- DI container;
- router;
- request/response;
- middleware pipeline;
- DB manager;
- validator;
- exception handler;
- logger;
- standardized API responses.

## Exit Criteria
Framework components tested and documented.

---

# Phase 3 — Admin Identity and Security

## Build
- admin users;
- roles;
- permissions;
- login;
- refresh/logout;
- password reset;
- auth middleware;
- permission middleware;
- audit logs;
- throttling.

## Exit Criteria
Admin access is secure and permission-controlled.

---

# Phase 4 — CMS and Legal Content

## Build
- pages;
- page sections;
- legal documents;
- SEO metadata;
- site settings;
- media;
- admin CRUD;
- publish workflow.

## Exit Criteria
Core public website content can be managed without code changes.

---

# Phase 5 — Investment Opportunities

## Build
- categories;
- opportunity records;
- risk classification;
- draft/publish/archive;
- public list/detail;
- admin management;
- SEO.

## Exit Criteria
Investment catalogue works end-to-end without enabling financial transactions.

---

# Phase 6 — Insights and FAQ

## Build
- articles;
- categories;
- tags;
- featured status;
- public insights;
- article details;
- FAQ management;
- related content.

## Exit Criteria
Content publishing workflow works from admin to public pages.

---

# Phase 7 — Public Marketing Frontend

## Build
- design system;
- header/footer;
- home;
- about;
- service pages;
- how it works;
- investments;
- insight pages;
- FAQ;
- legal pages;
- responsive design.

## Exit Criteria
All public routes match approved UX and responsive standards.

---

# Phase 8 — Enquiries and Conversion

## Build
- contact/consultation forms;
- validation;
- anti-spam/rate limiting;
- database persistence;
- email notifications;
- admin enquiry workflow;
- consent.

## Exit Criteria
A real prospect can submit an enquiry and administrators can manage it.

---

# Phase 9 — SEO, Performance and Accessibility

## Build
- metadata;
- canonical URLs;
- sitemap;
- robots;
- structured data;
- redirects;
- image optimization;
- caching;
- accessibility fixes.

## Exit Criteria
Technical SEO and accessibility release checks pass.

---

# Phase 10 — QA, Security and Staging UAT

## Build/Review
- complete test suite;
- E2E critical paths;
- dependency scan;
- security headers;
- upload tests;
- permission tests;
- staging content;
- stakeholder UAT.

## Exit Criteria
No release-blocking defects.

---

# Phase 11 — Production Deployment

## Build
- production infra;
- shared-hosting account and document-root/rewrite configuration;
- DB;
- secrets;
- HTTPS;
- backups;
- deployment;
- migrations;
- smoke tests;
- observability.

## Exit Criteria
Production is stable and recoverable.

---

# Phase 12 — Post-Launch Optimization

## Activities
- monitor errors;
- review leads;
- improve conversion;
- optimize content;
- publish insights;
- performance tuning;
- backlog prioritization.

---

# Future Phase — Client Portal

This placeholder is superseded by Phase 13, Phase 14, and the Future Regulated Transaction Platform section below.
Only begin after separate requirements/compliance review.

Potential scope:
- client authentication;
- KYC;
- portfolio reporting;
- statements;
- investment subscriptions;
- transactions;
- notifications.

If real funds or assets are involved, perform a new threat model and regulatory review before implementation.

---

# Phase 13 - Approved Client Account Foundation

Only begin after separate requirements/compliance review.

## Build
- client authentication;
- email verification and password reset;
- optional MFA foundation;
- client profile;
- published plan list for authenticated clients;
- plan request submission with risk acknowledgement;
- admin client list and detail;
- admin plan-request approval/rejection with required reason;
- client dashboard with pending/approved/rejected request status.

## Exit Criteria
- client identity is separate from admin identity;
- client ownership checks and admin client-operation permissions are enforced;
- all sensitive actions are audited;
- no wallet, deposit, withdrawal, custody, trading, or payment functionality is introduced;
- public and client-facing copy includes approved risk and non-guarantee language.

---

# Phase 14 - Client Investment Reporting and Manual Balance Operations

Only begin after Phase 13 and confirmation of the reporting source of truth.

## Build
- client investment accounts created through admin approval;
- manual balance adjustments with reason, source reference, effective date, and audit;
- reporting snapshots with principal, reported value, growth amount, growth percent, methodology note, and approval;
- client dashboard growth chart/table based only on approved snapshots;
- admin reporting and adjustment screens;
- stale-data notices and support flow.

## Exit Criteria
- adjustments and snapshots are append-only or correction-based;
- state-changing operations are idempotent;
- clients see only their own records;
- administrators can access client financial/reporting data only with explicit permissions;
- all balance/growth displays are clearly marked as reporting figures, not withdrawable balances or guaranteed returns;
- tests cover client login, plan request, approval, adjustment, snapshot publication, IDOR prevention, and audit.

---

# Future Phase - Regulated Transaction Platform

Potential scope:
- KYC;
- statements;
- investment subscriptions;
- transactions;
- deposits and withdrawals;
- payment settlement;
- custody or custodian integrations;
- brokerage or trade execution.

If real funds or assets are involved, perform a new threat model and regulatory review before implementation.

---

# Suggested Milestone Grouping

## Milestone A — Foundation
Phases 0–3

## Milestone B — CMS/API
Phases 4–6

## Milestone C — Public Experience
Phases 7–9

## Milestone D — Release
Phases 10–12

---

# Dependency Map

```text
Discovery
   |
Foundation
   |
Backend Core
   |
Identity/Security
   |
CMS ------------------+
   |                  |
Investments        Insights
   |                  |
   +---------+--------+
             |
        Public Frontend
             |
          Enquiries
             |
       SEO/Performance
             |
          QA/UAT
             |
        Production
```

---

# Phase Definition of Done Template

Every phase must confirm:

- acceptance criteria completed;
- code reviewed;
- automated tests added;
- migrations reviewed;
- security impact reviewed;
- API docs updated;
- UI responsive;
- accessibility considered;
- no secrets committed;
- CI green;
- documentation current.
