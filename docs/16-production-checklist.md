# PerrymanFinance Production Checklist

## Infrastructure

- PHP 8.2+ enabled with required extensions.
- MySQL 8+ or compatible MariaDB provisioned with least-privilege credentials.
- HTTPS enabled and HTTP redirects to HTTPS.
- Provider-supported URL rewriting configured.
- Backend source, migrations, logs, tests, and secrets kept outside public document root where supported.
- Prebuilt frontend assets deployed; no production Node.js dependency.
- Hosting-panel cron documented for scheduled/database-backed jobs.
- Provider backups enabled and restore-tested.
- Log rotation enabled.
- Uptime monitoring configured for `/api/v1/health`.

## Secrets and Environment

- Production `.env` or hosting secrets configured outside Git.
- JWT/session secrets generated and unique to production.
- Database and SMTP credentials are not present in workflow source.
- Staging and production secrets are separate.
- Debug mode disabled.

## Release Readiness

- CI green.
- Staging deployment complete.
- Staging health check green.
- Staging smoke test green.
- UAT checklist accepted.
- Legal, privacy, risk, and financial communications reviewed.
- Migration status reviewed.
- Backup checkpoint complete.
- Rollback release identified.

## Runtime Verification

- Home, about, services, opportunities, insights, FAQ, contact, and legal routes load.
- `/api/v1/health` returns success.
- Enquiry submission persists data and handles notification failure safely.
- Admin login and protected routes work.
- Public APIs expose published content only.
- Cache headers apply to hashed assets.
- Security headers are present.
- Sitemap and robots output are correct for the environment.

## Post-Deployment

- Record release version, commit, deployment time, operator, backup reference, and migration result.
- Review application logs and provider error logs.
- Confirm monitoring is green.
- Confirm contact email delivery.
- Keep the previous release available until the new release is stable.
