# PerrymanFinance — Frontend Architecture & Workflow

## 1. Frontend Objectives

The PerrymanFinance frontend must be:

- institutional and trustworthy;
- mobile-first;
- fast;
- accessible;
- SEO-friendly;
- simple to navigate;
- API-driven;
- reusable across public and admin experiences.

---

## 2. Visual Direction

Avoid:

- crypto-casino aesthetics;
- excessive neon;
- fake dashboards;
- unrealistic profits;
- countdown urgency;
- animated coin clutter.

Prefer:

- deep navy;
- restrained blue/emerald accents;
- generous whitespace;
- high-quality typography;
- subtle charts/data motifs;
- disciplined motion;
- risk and legal messaging where appropriate.

---

## 3. Frontend Structure

```text
src/
├── app/
│   ├── App.tsx
│   ├── providers.tsx
│   └── queryClient.ts
├── components/
│   ├── ui/
│   ├── marketing/
│   └── admin/
├── features/
│   ├── auth/
│   ├── content/
│   ├── investments/
│   ├── insights/
│   ├── enquiries/
│   ├── settings/
│   └── clientAccount/
├── layouts/
│   ├── PublicLayout.tsx
│   └── AdminLayout.tsx
├── pages/
├── routes/
├── services/
├── hooks/
├── types/
├── utils/
└── styles/
```

---

## 4. Route Architecture

Public:

```text
/
 /about
 /investment-solutions
 /digital-assets
 /wealth-management
 /how-it-works
 /investments
 /investments/:slug
 /insights
 /insights/:slug
 /faq
 /contact
 /privacy-policy
 /terms
 /risk-disclosure
 /cookie-policy
```

Admin:

```text
/admin/login
/admin
/admin/pages
/admin/investments
/admin/insights
/admin/faqs
/admin/enquiries
/admin/media
/admin/settings
/admin/users
/admin/audit-logs
```

Client:

```text
/client/register
/client/login
/client
/client/plans
/client/investments
/client/documents
/client/profile
/client/support
```

Admin client operations:

```text
/admin/clients
/admin/clients/:uuid
/admin/client-plan-requests
/admin/client-investments/:uuid
```

Use route guards for admin routes.

Public navigation rule:

- the public header and mobile drawer must show client access only;
- replace any generic `Login` button in public navigation with `Client Login` linking to `/client/login`;
- show `Dashboard` instead of `Client Login` when a client session is active;
- do not show `Admin Login`, `/admin/login`, CMS links, staff links, or admin-only labels anywhere in public navigation,
  public footer links, marketing pages, sitemap, or discoverable frontend menus;
- `/admin/login` remains a direct staff URL and must be protected by backend authentication, throttling, and monitoring.

---

## 5. Server State

Use a query/cache layer such as TanStack Query.

Rules:

- service layer owns API calls;
- query hooks wrap service calls;
- components consume query hooks;
- invalidate relevant keys after mutations.

Example conceptual keys:

```text
['page', slug]
['investments', filters]
['investment', slug]
['insights', filters]
['insight', slug]
['admin', 'enquiries', filters]
['client', 'dashboard']
['client', 'plans']
['client', 'planRequests']
['client', 'investments']
['client', 'investmentSnapshots', uuid]
['admin', 'clients', filters]
['admin', 'clientPlanRequests', filters]
```

---

## 6. Local/UI State

Use component state for:

- modal visibility;
- form controls where suitable;
- disclosure/accordion state;
- mobile-menu state.

Use a small global store only when state is truly cross-cutting.

Do not put server data into a global store unnecessarily.

---

## 7. API Client

Create one API client wrapper that handles:

- base URL;
- JSON headers;
- auth header;
- token refresh strategy;
- error normalization;
- request ID if provided;
- timeout;
- abort/cancellation where appropriate.

No random `fetch()` calls spread across components.

---

## 8. Forms

All forms need:

- labels;
- inline validation;
- server validation messages;
- loading state;
- disabled duplicate-submit state;
- success confirmation;
- accessible errors;
- anti-spam behavior for public forms.

Contact form example:

```text
Name
Email
Phone (optional)
Reason for enquiry
Message
Consent
Submit
```

---

## 9. Homepage Sections

Recommended sequence:

1. Hero
2. Trust/positioning statement
3. Services
4. Investment philosophy
5. Featured investment opportunities
6. How it works
7. Risk-management philosophy
8. Featured insights
9. Final CTA
10. Legal/risk footer statement

---

## 10. Investment Catalogue UX

Filters can include:

- category;
- risk classification;
- strategy;
- status.

Cards should emphasize:

- strategy;
- objective;
- horizon;
- risk classification;
- minimum-investment display if legitimately applicable;
- detail CTA.

Do not use aggressive "Invest Now" UX in MVP.

Prefer:

- View Details
- Learn More
- Request Consultation

For authenticated clients, use calm account CTAs:

- Select Plan
- Submit for Review
- View Investment
- View Report

Do not label these actions as purchase, deposit, instant investment, withdrawal, cash-out, trade, or guaranteed return.

---

## 11. Investment Detail UX

Suggested structure:

```text
Breadcrumb
Title
Category / Risk badge

Overview
Investment Objective
Strategy
Investment Horizon
Risk Classification
Minimum Investment (if approved)
Key Considerations

Risk Notice

[Request Consultation]
```

---

## 12. Insights UX

Index:

- featured article;
- latest articles;
- category filters;
- pagination;
- search optional.

Detail:

- title;
- category;
- date;
- author;
- cover image;
- article content;
- risk/disclaimer note where relevant;
- related articles;
- CTA.

---

## 13. Admin UX

Admin screens should prioritize productivity.

Every index:

- title;
- primary action;
- search;
- filter;
- table/list;
- pagination;
- status;
- actions.

Every editor:

- main content;
- publish state;
- preview;
- SEO panel;
- save status;
- validation summary.

Dangerous actions:

- confirmation dialog;
- clear consequence text;
- permission check;
- server-side enforcement.

## 14. Client Account UX

The client portal should be deliberately simple:

1. Register with name, email, password, consent, and email verification.
2. Log in with clear error handling and session timeout behavior.
3. Land on a dashboard with current approved balance, active plan, pending requests, recent reporting snapshots, and
   document/support shortcuts.
4. Choose one investment plan from published eligible opportunities.
5. Submit the plan request after acknowledging risk and non-guarantee language.
6. View request status as pending, approved, rejected, or cancelled.
7. View investment growth through approved historical snapshots only.

Dashboard rules:

- show loading, empty, error, and success states;
- label `current_balance` as "Reported balance" or equivalent, not withdrawable balance;
- show snapshot date and stale-data notice when data is not current;
- include a compact chart/table for principal, reported value, growth amount, and growth percent;
- avoid fake portfolio tiles, fabricated sample values, animated profit emphasis, countdowns, or aggressive crypto visuals.

Admin client-operation screens should use a productivity layout:

- client search and filters;
- pending plan-request queue;
- approval/rejection modal with required reason;
- balance-adjustment form with amount, type, source reference, effective date, and reason;
- reporting-snapshot form with methodology note and approval confirmation;
- audit timeline on the client detail view.

Client frontend flow:

```text
Public Header Client Login
  -> /client/login
  -> /client/register when account creation is needed
  -> Email verification
  -> Protected /client dashboard
  -> /client/plans
  -> Plan request confirmation
  -> Dashboard status and notifications
```

Client dashboard design:

- topbar with client name/status, support link, and logout;
- summary band with reported balance, active plan, and latest snapshot date;
- main panel with growth chart plus accessible table values;
- secondary panel with pending requests, documents, and notifications;
- persistent risk/non-guarantee note near reported balance and growth data;
- clear empty state for new clients with a single `Select Plan` action.

Client auth design:

- `/client/register` uses name, email, password, password confirmation, consent, and submit;
- `/client/login` uses email, password, forgot-password link, and create-account link;
- both forms need labels, inline errors, submit loading state, duplicate-submit prevention, success state, and safe generic
  authentication errors;
- client auth state must be separate from admin auth state.

---

## 15. Responsive Rules

Mobile-first breakpoints.

Navigation:

- desktop mega/simple dropdown where needed;
- mobile drawer;
- keyboard accessible.

Cards:

- one column mobile;
- two columns tablet;
- three/four as appropriate desktop.

Admin:

- collapsible sidebar;
- horizontally scrollable tables only as last resort;
- stacked mobile record views where useful.

---

## 16. Accessibility

Implement:

- semantic headings;
- proper landmarks;
- skip navigation;
- accessible form labels;
- visible focus;
- keyboard navigation;
- ARIA only where semantic HTML is insufficient;
- reduced-motion respect;
- adequate contrast;
- descriptive alt text.

---

## 17. SEO Architecture

Every public route should support:

- title;
- meta description;
- canonical;
- robots directive;
- Open Graph title;
- Open Graph description;
- Open Graph image;
- Twitter card fields;
- JSON-LD where relevant.

Generate:

- sitemap.xml;
- robots.txt.

Use structured data for:

- Organization;
- Article;
- BreadcrumbList;
- FAQPage where appropriate.

---

## 18. Performance

Implement:

- responsive images;
- WebP/AVIF where supported by pipeline;
- lazy loading;
- route/code splitting;
- prefetch critical routes cautiously;
- optimized fonts;
- cache immutable assets;
- no oversized frontend dependencies without justification.

---

## 19. Frontend Testing

### Unit/Component

Test:

- button/input primitives;
- investment cards;
- validation;
- permission-based rendering;
- error states.

### Integration

Test:

- form submission;
- list filters;
- publication screens;
- auth behavior.

### E2E

Critical:

- public navigation;
- contact submission;
- admin login;
- create/publish investment;
- create/publish article;
- edit legal page.
- client registration and login;
- client plan request submission;
- admin plan approval;
- admin balance/reporting snapshot update;
- client dashboard growth display.

---

## 20. Frontend Delivery Workflow

For each feature:

```text
Read API Contract
-> Create Types
-> Add Service Method
-> Add Query/Mutation Hook
-> Build UI
-> Add Loading State
-> Add Empty State
-> Add Error State
-> Add Responsive Behavior
-> Add Accessibility
-> Add Tests
-> Review Against Acceptance Criteria
```
