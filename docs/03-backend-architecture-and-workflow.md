# PerrymanFinance — Backend Architecture & Workflow

# 1. Backend Goals

The backend must provide a secure, maintainable REST API for:

- public website content;
- investment opportunities;
- insights;
- enquiries;
- administration;
- media;
- SEO configuration;
- legal content;
- future client-portal expansion.

Use a **modular monolith** with strict internal boundaries.

---

# 2. Request Lifecycle

```text
HTTP Request
   |
   v
Shared-hosting web server (Apache/LiteSpeed/Nginx)
   |
   v
public/index.php
   |
   v
Application Bootstrap
   |
   v
Router
   |
   v
Global Middleware
   |
   v
Route Middleware
   |
   v
Controller
   |
   v
Request DTO / Validation
   |
   v
Application Service
   |
   +--> Repository
   +--> Domain Policy
   +--> External Integration
   |
   v
Response DTO / Resource
   |
   v
JSON Response
```

---

# 3. Core Framework Components

## Router
Responsibilities:
- method/path matching;
- parameters;
- route groups;
- middleware assignment;
- named routes where useful.

## DI Container
Responsibilities:
- constructor dependency resolution;
- interface bindings;
- singleton lifecycle for DB/config/logger;
- factory bindings for integrations.

## Request
Expose:
- method;
- URI;
- headers;
- query;
- route params;
- JSON body;
- uploaded files;
- authenticated principal.

## Response
Support:
- JSON;
- status;
- headers;
- file streaming where necessary.

## Middleware
Initial middleware:
- RequestIdMiddleware
- JsonBodyMiddleware
- CorsMiddleware
- SecurityHeadersMiddleware
- RateLimitMiddleware
- AuthMiddleware
- PermissionMiddleware
- AuditContextMiddleware

### Phase 2 implementation notes

The core implementation lives under `backend/app` and is composed in `backend/bootstrap/app.php`. Configuration,
database connections, the middleware dispatcher, response factory, and logger are container-managed singletons where
shared lifecycle is appropriate. Routes are declared separately in `backend/routes/api.php`.

Global middleware executes in this order: request ID, security headers, CORS, then JSON body parsing. Route middleware
is appended by the router after a route matches. CORS origins come from the comma-separated
`CORS_ALLOWED_ORIGINS` environment value; wildcard origins are not enabled. HSTS is emitted only in production when
the request is reported as HTTPS. The deployment proxy must therefore provide a trusted HTTPS indication.

The router treats a path that exists under another method as `405 METHOD_NOT_ALLOWED` and includes an `Allow` header;
unknown paths map to `404 NOT_FOUND`. All uncaught exceptions pass through the centralized exception handler, which
logs structured context and masks internal messages unless `APP_DEBUG=true`.

Repositories extend only the narrow `AbstractRepository` prepared-statement and identifier-allowlist utilities.
Transactions are owned by `TransactionManager`; domain query and mapping behavior remains in concrete repositories.
Authentication, authorization, rate limiting, and audit context are intentionally deferred to Phase 3.

---

# 4. Layering Rules

## Controller
Allowed:
- read request;
- invoke validator/request class;
- call service;
- convert result to response.

Forbidden:
- SQL;
- complex business logic;
- filesystem manipulation;
- direct email transport;
- authorization decisions beyond middleware/policy invocation.

## Service
Allowed:
- transactions;
- business rules;
- orchestration;
- repository usage;
- event dispatching;
- integration calls.

## Repository
Allowed:
- CRUD persistence;
- query construction;
- mapping rows to domain/data objects.

Forbidden:
- HTTP concerns;
- authorization;
- email;
- application UI decisions.

---

# 5. Authentication Design

For admin API:

1. Admin posts credentials.
2. AuthService resolves admin user.
3. Password hash is verified.
4. Account status is checked.
5. Access token is issued.
6. Refresh token is persisted hashed.
7. Audit entry records successful login.
8. Failed attempts are rate-limited.

Use:
- `password_hash()` / `password_verify()`;
- short-lived access tokens;
- rotated refresh tokens;
- secure refresh-token storage strategy;
- revocation on logout/password reset.

If using secure HttpOnly cookies instead of browser storage, implement CSRF protections appropriately.

---

# 6. RBAC

Suggested roles:

- `super_admin`
- `content_admin`
- `editor`
- `viewer`

Suggested permissions:

```text
pages.view
pages.create
pages.update
pages.publish
pages.delete

investments.view
investments.create
investments.update
investments.publish
investments.delete

articles.view
articles.create
articles.update
articles.publish
articles.delete

faqs.manage
enquiries.view
enquiries.update
media.manage
settings.manage
users.manage
audit.view
legal.manage
```

Authorization must be checked server-side.

---

# 7. Publishing Workflow

Use statuses:

```text
draft
review
published
archived
```

Applicable to:
- pages;
- articles;
- investment opportunities;
- legal documents.

Typical flow:

```text
Draft
  -> Review
  -> Published
  -> Archived

Published
  -> Draft (only if business rules allow)
```

Record:
- published_at;
- published_by;
- updated_by.

---

# 8. Investment Opportunity Workflow

```text
Admin Creates Draft
   |
   v
Adds Category + Description + Risk Information
   |
   v
Adds Required Disclaimer
   |
   v
Preview
   |
   v
Publish
   |
   v
Public Catalogue
   |
   v
User Opens Details
   |
   v
User Reads Risk Disclosure
   |
   v
Enquiry / Request Information
```

No investment transaction occurs in MVP.

---

# 9. Enquiry Workflow

```text
Visitor submits form
   |
   v
Validate + sanitize
   |
   v
Rate-limit / anti-spam checks
   |
   v
Persist enquiry
   |
   +--> Queue/send notification email
   |
   v
Return generic success response
```

Admin:

```text
new
-> in_progress
-> resolved

optional:
-> spam
-> closed
```

Fields:
- name;
- email;
- phone optional;
- enquiry type;
- subject;
- message;
- source page;
- consent;
- status;
- assigned user optional;
- timestamps.

Do not expose internal IDs publicly if avoidable.

---

# 10. Content Workflow

Page content should support:
- structured page sections;
- status;
- preview;
- version-safe legal content;
- SEO metadata.

Recommended page-section structure:

```json
{
  "sections": [
    {
      "type": "hero",
      "heading": "...",
      "body": "...",
      "primaryCta": {}
    },
    {
      "type": "service_grid",
      "items": []
    }
  ]
}
```

Validate allowed section types server-side.

---

# 11. Media Workflow

Upload:

```text
Multipart Request
-> Authentication
-> Authorization
-> Size Check
-> MIME Check
-> Decode/Validate Image
-> Generate Safe Filename
-> Store
-> Create Media Record
-> Return Metadata
```

Never trust extension alone.

Recommended:
- JPEG;
- PNG;
- WebP;
- SVG only if sanitized or disallowed.

Prevent executable uploads.

---

# 12. Transactions

Use database transactions for operations that change multiple related records, such as:

- create article + tags;
- update roles + permissions;
- refresh-token rotation;
- publish operations involving multiple tables.

Pattern:

```php
beginTransaction();

try {
    // operations
    commit();
} catch (Throwable $e) {
    rollBack();
    throw $e;
}
```

---

# 13. Caching

Cache candidates:
- site settings;
- public pages;
- investment catalogue;
- investment details;
- insight lists;
- legal documents;
- FAQ.

Cache invalidation should happen when admin publishes or updates relevant content.

Do not introduce Redis until justified; filesystem or application cache may be sufficient initially.

---

# 14. Logging

Use structured logs.

Recommended context:
- request_id;
- authenticated_user_id;
- route;
- method;
- status_code;
- duration_ms;
- exception_class;
- environment;
- app_version.

Never log:
- passwords;
- access tokens;
- refresh tokens;
- secrets;
- full payment/account details if introduced later.

---

# 15. Background Jobs

MVP can process some operations synchronously, but create an abstraction for jobs.

Candidates:
- email notifications;
- image optimization;
- sitemap generation;
- cache warmup.

A database-backed queue is sufficient if required initially.

---

# 16. Backend Testing Strategy

## Unit
Test:
- validators;
- policies;
- services;
- state transitions;
- slug generation;
- permission logic.

## Integration
Test:
- repositories;
- migrations;
- DB constraints;
- transactions.

## Feature/API
Test:
- login;
- permissions;
- CRUD;
- publishing;
- enquiries;
- validation failures;
- pagination;
- not-found behavior.

## Security Tests
Include:
- unauthenticated admin access;
- permission denial;
- SQL injection payload behavior;
- upload rejection;
- brute-force throttling;
- invalid token handling.

---

# 17. Future Client Portal Boundary

When future financial functionality is approved, add modules such as:

```text
ClientIdentity
KYC
Portfolio
InvestmentAccount
Transactions
Statements
Notifications
```

Do not retrofit client-money logic into the CMS modules.

Before adding real investment execution, conduct a new architecture/security/compliance review.
