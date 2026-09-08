# Enquiries and consultations

Both workflows use `POST /api/v1/enquiries`. Set `enquiry_type` to `general`, `consultation`, or `investment`.
The public form is at `/contact`; `/contact?type=consultation` preselects consultation.
Fields are name (160), email (254), optional phone (40), subject (255), message (5000), optional source_page (500),
and boolean consent, which must be true. Strings are trimmed. Lengths are server-enforced.

Publish a Privacy Policy and configure the public `enquiry_consent` CMS setting with business/counsel-approved copy
before accepting submissions. No legal copy is seeded. Consent time is stored in UTC.

The hidden `website` honeypot silently discards bot submissions. All attempts, including honeypot submissions,
use the shared database-backed limit of five requests per IP per 15 minutes. Do not trust arbitrary forwarded IP headers.
Successful submissions and honeypot responses both return HTTP 202 with null data and a generic acknowledgement.
Invalid fields return 422; throttling returns 429. Public responses never expose enquiry identifiers.

The enquiry is stored before synchronous PHPMailer notification through the injectable `EnquiryNotifier` abstraction.
SMTP has a 10-second connection timeout. Production requires TLS/SSL with certificate validation enabled.
Mail contains no submitted personal data. Delivery failure logs a generic warning and leaves the enquiry available
to administrators. This version has no automatic delivery retry: review the admin inbox and warning logs regularly.
No persistent worker or Redis service is needed. Password-reset delivery remains the existing separate transport.

## Admin API

All endpoints require a valid admin access token. Existing seeded permissions are reused.

| Method and path | Permission | Behavior |
| --- | --- | --- |
| GET `/api/v1/admin/enquiries` | `enquiries.view` | Search name/email/subject, filter status, paginate |
| GET `/api/v1/admin/enquiries/{uuid}` | `enquiries.view` | Full enquiry details |
| PATCH `/api/v1/admin/enquiries/{uuid}` | `enquiries.update` | Accepts only `{"status":"in_progress"}` |

List parameters: `search` (max 255), `status`, `page` (default 1), `per_page` (default 20, capped at 50).
Returns the standard `data` list and `meta.page`, `per_page`, `total`, `total_pages` envelope, newest first.
Deleted records are excluded. Admin UI: `/admin/enquiries`, with permission-aware status controls.

| Current status | Allowed next statuses |
| --- | --- |
| new | in_progress, spam, closed |
| in_progress | resolved, spam, closed |
| resolved | in_progress, closed |
| spam | in_progress, closed |
| closed | in_progress |

Reopening uses `in_progress`. Invalid/same-state transitions and unsupported fields return 422.
Updates compare the previous status to prevent overwriting concurrent changes. Status and audit event commit together;
audit failure rolls back the update. Audit records contain actor, enquiry UUID, previous/new status and request ID,
without message, name, email, or phone. `resolved_at` is set when resolving and cleared when leaving resolved.

Existing enquiry tables and RBAC seed data suffice; this change adds no migration. Assignment and internal notes are
not exposed. Privacy retention/deletion/export procedures and approved content remain required before production release.

## Hosting and operations

Serve only `backend/public` for the API and prebuilt `web/dist` for the frontend. The repository root must never be a
document root. Apache/LiteSpeed can use `backend/public/.htaccess` with rewrite support and permitted overrides;
an Nginx host must configure the equivalent front-controller routing. Route API paths before frontend history fallback.
Keep `.env`, vendor, source, migrations, tests, logs and uploads outside public roots. Restrict writable storage to the
application account; use provider-supported non-executable private storage and log rotation.

PHP extensions: PDO/PDO MySQL, JSON, OpenSSL, Mbstring, Fileinfo, GD; development tests also need PDO SQLite,
DOM/XML/XMLWriter, with cURL and ZIP recommended for Composer. Configure matching PHP versions for HTTP and cron.
Cron is needed for provider-approved backup/retention tasks, not enquiry notification delivery in this version.
Verify HTTPS, SMTP delivery, logs, database backups and restore procedures on staging before release.
Build the release with `npm --prefix web run build:release` after configuring the published-content API and production
origin as described in `12-public-website.md`. A plain Vite build is a foundation check, not the SEO release artifact.

The health endpoints `/api/v1/health` and frontend `/health` test application availability, not database or SMTP readiness.
