# PerrymanFinance Deployment Runbook

## Scope

This runbook describes staging and production deployment to shared Linux hosting with PHP 8.2+, MySQL 8+/compatible MariaDB, HTTPS, provider-supported URL rewriting, and no required production Node.js runtime.

## Required GitHub Configuration

Use GitHub protected environments named `staging` and `production`. Store secrets only in GitHub environment secrets or the hosting provider secret manager.

Required environment variables:

- `STAGING_BASE_URL`
- `PRODUCTION_BASE_URL`
- `PRERENDER_API_URL`
- `VITE_SITE_URL`

Required secrets per environment:

- `STAGING_SSH_HOST` / `PRODUCTION_SSH_HOST`
- `STAGING_SSH_USER` / `PRODUCTION_SSH_USER`
- `STAGING_SSH_PORT` / `PRODUCTION_SSH_PORT`
- `STAGING_SSH_PRIVATE_KEY` / `PRODUCTION_SSH_PRIVATE_KEY`
- `STAGING_RELEASE_ROOT` / `PRODUCTION_RELEASE_ROOT`

Do not put database credentials, SMTP credentials, JWT secrets, or production `.env` values in workflow files.

## CI Gates

The `CI` workflow runs:

- Composer validation and install;
- PHP syntax lint;
- PHPCS style checks;
- PHPStan static analysis;
- backend PHPUnit tests;
- Composer audit;
- frontend `npm ci`;
- ESLint;
- TypeScript type-check;
- Vitest;
- prerendered production build;
- Playwright smoke coverage;
- frontend dependency audit;
- release artifact dry run.

## Release Artifact

Run locally or in CI:

```bash
./scripts/package-release.sh v1.0.0
```

The artifact is written to `build/perrymanfinance-v1.0.0.zip` and contains:

- `private/backend`: PHP application source, migrations, and production Composer dependencies;
- `public`: prebuilt frontend assets;
- `ops`: runbooks and provider rewrite/security reference files.

Production does not require Node.js. Build the frontend in CI and upload only compiled assets.

Release generation requires `PRERENDER_API_URL` and `VITE_SITE_URL` so canonical URLs, sitemap, robots, and prerendered public pages are generated from approved HTTPS origins. CI artifact dry runs may set `SKIP_PRERENDER=1`; staging and production deployments must not skip prerendering.

## Shared-Hosting Layout

Preferred layout:

```text
/home/account/perrymanfinance/releases/perrymanfinance-<version>/
  private/backend/
  public/
/home/account/perrymanfinance/current -> releases/perrymanfinance-<version>
```

Set the website document root to the active release `public` directory where the hosting panel permits it. Keep backend source, configuration, migrations, tests, logs, and uploads outside the public document root.

If the provider cannot point the document root to a release directory, upload to the provider-supported private application directory and copy only `public` assets into the public document root as a documented manual step.

## Staging Deployment

Staging deploys from `main` or by manually running the `Deploy` workflow with `target=staging`.

Workflow steps:

1. Build a versioned artifact.
2. Upload the artifact to the staging release directory.
3. Switch `current` to the uploaded release where symlinks are supported.
4. Run `php bin/migrate.php up`.
5. Run `/api/v1/health`.
6. Run the smoke test script against public routes.

## Production Deployment

Production deploys only from a published release, a `v*.*.*` tag, or a manually approved `production` environment workflow run.

Before approval:

1. Confirm CI is green.
2. Confirm staging smoke tests and UAT are accepted.
3. Confirm legal/content approvals.
4. Confirm provider backup or database dump is complete.
5. Confirm migration review and rollback notes.

After deployment:

1. Verify `/api/v1/health`.
2. Run smoke tests.
3. Confirm enquiry/contact email delivery.
4. Review application and provider error logs.
5. Record the active release version, migration status, and backup reference.

## Provider Configuration

Enable:

- HTTPS and redirect HTTP to HTTPS;
- Apache/LiteSpeed/Nginx URL rewriting;
- cache headers for hashed static assets;
- security headers equivalent to `backend/public/.htaccess`;
- OPcache where available;
- provider log rotation;
- scheduled backups;
- uptime monitoring of `/api/v1/health`.

Use hosting-panel cron for scheduled database-backed jobs. Do not assume Redis, root access, systemd, Supervisor, or continuously running queue workers.
