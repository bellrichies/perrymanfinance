# Pre-Release Hardening Report

## Scope

This pass reviewed performance, accessibility and security items that can be hardened in source before a shared-hosting release. It also adds safe local/development seed data for the public CMS pages required by the frontend.

## Implemented Remediation

### Performance

- Added Apache/LiteSpeed cache and rewrite guidance in `web/public/.htaccess` for immutable hashed assets, short-lived HTML/XML/TXT caching, public route fallbacks, and API exclusion.
- Added backend public `.htaccess` routing and header protections.
- Added public API cache headers for non-admin GET responses while preserving explicit auth `no-store` headers.
- Added a forward migration for public listing, sitemap, FAQ and redirect indexes.
- Added a WebP hero image variant and switched the hero to `<picture>` with explicit dimensions and async decoding.
- Confirmed route code splitting already exists through `React.lazy` in `web/src/App.tsx`.
- Reviewed dependencies; no unnecessary runtime dependencies were identified in the current package list.
- Added `docs/deployment/php-user.ini.example` with OPcache and production PHP runtime guidance for hosts that support `.user.ini` or equivalent control-panel settings.

### Accessibility

- Confirmed public layout includes a skip link, header, nav, main and footer landmarks.
- Confirmed public route transitions focus the main landmark and reset scroll.
- Confirmed forms use labels, disabled submit states and accessible error/status messaging.
- Confirmed reduced-motion CSS exists for animations and transitions.
- Kept hero image decorative with empty alt text and `aria-hidden` because it is not content-bearing.

### Security

- Expanded CSP from API-only defaults to a restrictive same-origin policy compatible with the static frontend and API.
- Confirmed HSTS is emitted in production HTTPS contexts and added shared-hosting header templates.
- Confirmed CORS only echoes configured allowlisted origins.
- Confirmed refresh cookies are `HttpOnly`, `SameSite=Strict`, path-scoped, and secure by default outside local development.
- Confirmed login, password reset and enquiry rate limits are database-backed.
- Confirmed rich text and structured CMS content use allowlist sanitization.
- Confirmed uploads decode JPEG/PNG/WebP server-side, reject SVG/executable formats, randomize names and store outside public paths by default.
- Confirmed token service enforces a minimum signing secret length, short access-token expiry, refresh-token rotation and revocation on reset/logout.
- Confirmed production error responses mask unexpected exception details.
- Added public-content seeding through an inactive seed actor and safe placeholders; no real credentials or production legal claims are seeded.

## Remaining Operational Release Gates

These items require environment or business decisions and cannot be completed purely in source:

- Configure real `FRONTEND_URL`, `APP_URL`, `CORS_ALLOWED_ORIGINS`, `JWT_SECRET`, database credentials and SMTP settings in the hosting environment.
- Replace seeded legal placeholders and risk/contact copy with business/counsel-approved content before production indexing.
- Verify the selected host honors `.htaccess`, `.user.ini` or equivalent control-panel OPcache/cache/header settings.
- Run migrations against staging with a backup checkpoint and confirm the new indexes build successfully on the provider database.
- Run the full CI matrix with PHP 8.3+; local backend PHPUnit is blocked if the CLI is older.
- Verify production email deliverability, backups, restore procedure, monitoring and rollback.
