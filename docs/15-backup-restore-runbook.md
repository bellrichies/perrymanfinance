# PerrymanFinance Backup and Restore Runbook

## Backup Scope

Back up:

- MySQL/MariaDB database;
- uploaded media and writable storage;
- production `.env` or provider-managed environment settings;
- active and previous release artifacts;
- web-server rewrite/security configuration where managed outside Git.

Do not store production secrets in Git.

## Schedule

- Daily provider-managed database backup where available.
- Daily uploaded-media backup.
- Before every production deployment, take or verify a backup checkpoint.
- Monthly restore test into a non-production environment.

## Manual Database Backup

Use the hosting panel backup tool when available. If SSH and `mysqldump` are supported:

```bash
mysqldump --single-transaction --routines --triggers --default-character-set=utf8mb4 "$DB_NAME" > "backup-$DB_NAME-$(date -u +%Y%m%dT%H%M%SZ).sql"
```

Store backups in provider backup storage or approved secure storage with restricted access.

## Restore Test

1. Provision a staging or temporary database.
2. Import the selected backup.
3. Configure staging `.env` to the restored database.
4. Run `php bin/migrate.php status`.
5. Run health and smoke tests.
6. Record restore duration and any errors.

## Production Restore

Only restore production after stakeholder approval.

1. Put the site in the provider-supported maintenance state if available.
2. Take a final backup of the current damaged state for investigation.
3. Restore the selected database and media backup.
4. Verify environment secrets and permissions.
5. Run health and smoke tests.
6. Remove maintenance state.
7. Monitor logs and enquiry delivery.

## Retention

Use the provider's retention controls where available. Keep enough restore points to cover accidental content deletion and deployment failures, balanced against privacy and storage policies.
