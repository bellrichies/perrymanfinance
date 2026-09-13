# PerrymanFinance Rollback Runbook

## Decision Criteria

Rollback when a release causes failed health checks, broken critical public routes, failed enquiry submission, severe admin lockout, unexpected database errors, or a security-impacting misconfiguration.

## Application Rollback

Preferred shared-hosting rollback:

1. Identify the previous known-good release directory under `releases/`.
2. Switch `current` back to that release using the provider-supported symlink or file-manager equivalent.
3. Clear provider cache/CDN cache if enabled.
4. Run `./scripts/health-check.sh https://example.com`.
5. Run `./scripts/smoke-test.sh https://example.com`.
6. Record the rollback time, release version, operator, and reason.

If symlinks are unsupported, restore the previous release files from the provider backup or upload the previous artifact and point/copy public assets according to the provider's documented workflow.

## Database Rollback

Prefer forward fixes. Use `php bin/migrate.php down <steps> --force` in production only when:

- the migration is confirmed reversible;
- the rollback impact is understood;
- a backup exists and has a restore path;
- stakeholders approve the data impact.

For risky schema changes, use expand/migrate/contract releases so application rollback does not require destructive database rollback.

## Post-Rollback Checks

- `/api/v1/health` returns success.
- Public home, insights, opportunities, FAQ, and legal routes load.
- Admin login works.
- Enquiry submission persists and notification failure does not lose data.
- Logs show no continuing elevated error rate.

## Follow-Up

Open an incident note with:

- failed release version;
- restored release version;
- backup reference;
- migration status;
- customer impact;
- root cause;
- corrective action before the next deployment.
