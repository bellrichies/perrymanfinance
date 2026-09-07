# PerrymanFinance — Data, API, Security & Compliance

# 1. Database Standards

Use:
- MySQL InnoDB;
- utf8mb4;
- foreign keys;
- explicit indexes;
- migrations;
- timestamps in UTC;
- soft deletion only where business value exists.

Every table should have:
- primary key;
- sensible unique constraints;
- indexes supporting real queries;
- predictable created/updated timestamps.

---

# 2. Suggested Relationships

## Migration implementation

The initial schema is installed by ordered migrations under `backend/database/migrations`. Applied names, batches, and
UTC execution timestamps are recorded in `schema_migrations`. The runner wraps each migration in a transaction when
the PDO driver supports transactional DDL. MySQL DDL implicitly commits, so each statement remains atomic but an
operator must inspect and resolve a partially completed multi-table migration before retrying. The migration is not
recorded unless its complete `up()` method succeeds.

Rollback is explicit and reverses registered migration order. Production rollback requires `--force`; no automatic
reset/fresh command exists. Deployed migration files are immutable—schema corrections require a new migration.

The SEO schema uses explicit nullable foreign keys for pages, legal documents, investment opportunities, and articles,
with a database check requiring exactly one owner. This is the selected alternative to a polymorphic owner pair.

All application connections set the MySQL session time zone to UTC. Tables use `DATETIME(6)`, InnoDB,
`utf8mb4_unicode_ci`, foreign keys, access-path indexes, and explicit unique constraints.

```text
admin_users <-> roles
roles <-> permissions

admin_users -> pages
admin_users -> articles
admin_users -> investment_opportunities

investment_categories -> investment_opportunities

article_categories -> articles
articles <-> tags

media_assets -> articles
media_assets -> investment_opportunities

pages -> seo_metadata
articles -> seo_metadata
investment_opportunities -> seo_metadata
```

A polymorphic SEO association is possible, but explicit relationships are easier to reason about in a custom framework. Choose one approach and document it.

---

# 3. Pagination Contract

Request:

```text
?page=1&per_page=20&sort=-published_at&status=published
```

Response:

```json
{
  "success": true,
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 120,
    "total_pages": 6
  }
}
```

Cap `per_page` server-side.

---

# 4. Filtering and Sorting

Whitelist sortable/filterable fields.

Never interpolate arbitrary user-provided column names directly into SQL.

---

# 5. Validation

Validation must occur server-side.

Common rules:
- required;
- string;
- length;
- email;
- URL;
- slug;
- enum/status;
- UUID;
- integer;
- boolean;
- uploaded MIME;
- file size.

Frontend validation improves UX but does not replace backend validation.

---

# 6. Security Model

## Admin identity implementation

The admin API uses a short-lived HMAC-SHA-256 access token in the `Authorization: Bearer` header and a rotating,
opaque refresh token in a `Secure`, `HttpOnly`, `SameSite=Strict` cookie scoped to `/api/v1/admin/auth`. Access tokens
are held in frontend memory only. Refresh and password-reset tokens are stored only as keyed SHA-256 hashes.

Identity endpoints are:

```text
POST /api/v1/admin/auth/login
POST /api/v1/admin/auth/refresh
POST /api/v1/admin/auth/logout
POST /api/v1/admin/auth/forgot-password
POST /api/v1/admin/auth/reset-password
GET  /api/v1/admin/auth/me
```

Login and reset requests use database-backed counters so throttling works on shared hosting without Redis. Configure
`JWT_SECRET` with at least 32 random characters and keep `AUTH_COOKIE_SECURE=true` outside local HTTP development.
Password reset delivery uses the host PHP mail transport in this phase; configure `MAIL_FROM_ADDRESS` and validate the
provider's delivery setup and set `FRONTEND_URL` before production. Responses never contain reset tokens and unknown accounts receive the
same response as known accounts.

## Authentication
- secure password hashing;
- access token expiration;
- refresh rotation;
- logout revocation;
- reset-token expiration;
- login throttling.

## Authorization
- deny by default;
- RBAC permissions;
- backend enforcement;
- no trust in hidden frontend controls.

## SQL
- PDO prepared statements;
- no concatenated user input.

## XSS
- escape rendered content;
- sanitize admin rich-text input with allowlists;
- avoid raw HTML injection.

## CSRF
If authenticated cookies are used, protect state-changing requests with CSRF tokens and SameSite cookie settings.

## CORS
Whitelist required application origins.

## HTTP Headers
Set:
- Content-Security-Policy;
- X-Content-Type-Options;
- Referrer-Policy;
- Permissions-Policy;
- frame-ancestors via CSP;
- Strict-Transport-Security in HTTPS production.

---

# 7. File Upload Security

The local CMS media implementation accepts decoded JPEG, PNG, and WebP images only. It rejects SVG and all executable
formats, enforces `MEDIA_MAX_BYTES` and `MEDIA_MAX_DIMENSION`, generates random server filenames, stores files under
`MEDIA_STORAGE_PATH` (outside the public document root by default), and returns metadata rather than executable URLs.
Allowed structured page-section types are enforced by the backend. Legal and structured content is stripped to a
small HTML tag allowlist and all HTML attributes are removed, preventing scripts, event handlers, styled payloads,
embedded documents, and `javascript:` links.
The hosting environment therefore requires the PHP Fileinfo and GD extensions in addition to JSON/PDO support.

Requirements:
- max file size;
- MIME validation using server-side inspection;
- image decoding;
- randomized filenames;
- storage outside executable path where possible;
- no PHP/script execution;
- image dimension limits;
- metadata cleanup optional;
- SVG sanitization or prohibition.

---

# 8. Rate Limiting

Apply to:
- login;
- password reset;
- enquiry;
- newsletter;
- public search if abused;
- admin mutation endpoints if appropriate.

Return HTTP 429.

---

# 9. Secrets

Store in environment configuration:
- DB credentials;
- JWT signing secret/private key;
- SMTP credentials;
- API keys;
- object-storage credentials.

Commit:
- `.env.example`

Never commit:
- `.env`
- private keys
- production secrets.

---

# 10. API Versioning

Use:

```text
/api/v1
```

Breaking changes require a new version or carefully managed migration.

---

# 11. Idempotency

MVP should prevent duplicate public form submissions using:
- disabled UI during submit;
- server rate limiting;
- optional idempotency token.

For future financial write operations, idempotency becomes mandatory.

---

# 12. Audit Logging

Audit:
- login success/failure where appropriate;
- user creation/update;
- role changes;
- publication changes;
- legal-document changes;
- settings changes;
- deletes/archives.

Suggested fields:

```text
id
actor_user_id
action
entity_type
entity_id
old_values_json
new_values_json
ip_address
user_agent
request_id
created_at
```

Do not store secrets in snapshots.

---

# 13. Privacy

Collect only necessary personal data.

Enquiry form:
- identify data purpose;
- record consent if required;
- define retention policy;
- restrict admin access;
- support deletion/export obligations where applicable.

---

# 14. Financial Communications Guardrails

Because the product concerns investment and digital assets:

- do not display guaranteed returns unless legally substantiated and approved;
- do not fabricate performance;
- clearly distinguish examples from actual performance;
- expose risk disclosure prominently;
- legal/compliance personnel should approve regulated claims;
- do not imply regulatory licensing that the company does not hold;
- all investment descriptions should be administratively editable.

---

# 15. Terms, Privacy and Risk Content

Legal documents should not be copied blindly from another company.

They must reflect:
- PerrymanFinance's jurisdiction;
- real operating entity;
- actual services;
- actual data practices;
- actual fee structure;
- actual dispute mechanism;
- actual regulatory status.

Use counsel review before production.

---

# 16. Backup and Recovery

Minimum:
- automated DB backups;
- encrypted storage;
- retention policy;
- periodic restore testing;
- media backup if local/object storage;
- configuration recovery documentation.

---

# 17. Security Release Checklist

Before launch verify:

- production debug disabled;
- HTTPS enforced;
- secure cookies;
- CORS restricted;
- CSP enabled;
- secrets rotated from development;
- database user least privilege;
- admin default credentials removed;
- login throttling;
- uploads hardened;
- dependency scan clean;
- backup tested;
- error pages do not leak stack traces;
- audit logging operational.
