# PerrymanFinance — Frontend Architecture & Workflow

# 1. Frontend Objectives

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

# 2. Visual Direction

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

# 3. Frontend Structure

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
│   └── settings/
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

# 4. Route Architecture

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

Use route guards for admin routes.

---

# 5. Server State

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
```

---

# 6. Local/UI State

Use component state for:
- modal visibility;
- form controls where suitable;
- disclosure/accordion state;
- mobile-menu state.

Use a small global store only when state is truly cross-cutting.

Do not put server data into a global store unnecessarily.

---

# 7. API Client

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

# 8. Forms

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

# 9. Homepage Sections

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

# 10. Investment Catalogue UX

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
- Request Information

---

# 11. Investment Detail UX

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

[Request Information]
```

---

# 12. Insights UX

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

# 13. Admin UX

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

---

# 14. Responsive Rules

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

# 15. Accessibility

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

# 16. SEO Architecture

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

# 17. Performance

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

# 18. Frontend Testing

## Unit/Component
Test:
- button/input primitives;
- investment cards;
- validation;
- permission-based rendering;
- error states.

## Integration
Test:
- form submission;
- list filters;
- publication screens;
- auth behavior.

## E2E
Critical:
- public navigation;
- contact submission;
- admin login;
- create/publish investment;
- create/publish article;
- edit legal page.

---

# 19. Frontend Delivery Workflow

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
