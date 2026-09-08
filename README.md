# PerrymanFinance

PerrymanFinance is an institutional-style digital asset investment and wealth-management information website. The MVP is a corporate website, informational opportunity catalogue, publishing platform, enquiry workflow, and administration CMS. It is not a wallet, exchange, custody, trading, payment, investment-ledger, or automated-return system.

## Runtime and development requirements

- PHP 8.3+ with PDO MySQL and the extensions required by Composer
- Composer 2
- MySQL 8+ or a compatible MariaDB version provided by the host
- Node.js 22+ and npm for local development and CI builds
- An independently installed local SMTP catcher (for example Mailpit) for enquiry email testing
- GNU Make is optional

Node.js is a build-time dependency. Production shared hosting serves the compiled files from `web/dist`; it does not need to run Vite or install frontend development dependencies.

## Local setup

Create local environment files and install locked dependencies:

```powershell
Copy-Item backend/.env.example backend/.env
Copy-Item web/.env.example web/.env
composer --working-dir=backend install
npm --prefix web ci
```

Confirm `php -v` reports 8.3 or newer (an older XAMPP PHP on PATH must be replaced in that terminal's PATH).
Enable PDO MySQL, OpenSSL, Mbstring, Fileinfo and GD; tests also need PDO SQLite and DOM/XML/XMLWriter.
Start your installed MySQL service. Create the local database and user using an administrative MySQL session:

```sql
CREATE DATABASE perrymanfinance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'perryman'@'127.0.0.1' IDENTIFIED BY 'choose-a-local-password';
GRANT ALL PRIVILEGES ON perrymanfinance.* TO 'perryman'@'127.0.0.1';
```

Set `DB_USERNAME=perryman` and your chosen `DB_PASSWORD` in `backend/.env`. Generate a local signing secret with
`php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` and put its output in `JWT_SECRET`. Never use the example placeholder.
Then run:

```powershell
php backend/bin/migrate.php up
php backend/bin/seed.php
node scripts/dev.mjs up
```

The supervisor runs in the foreground. Stop it with Ctrl+C or `node scripts/dev.mjs down` in another terminal.
It stops only the PHP/Vite processes it started. MySQL and SMTP are independently managed local services.
GNU Make users can use `make up` and `make down`.
If those ports are occupied, set `DEV_API_PORT` and `DEV_WEB_PORT` before `up`; the supervisor configures Vite's API
proxy to match. Add a changed frontend origin to `CORS_ALLOWED_ORIGINS` when using direct cross-origin API requests.

Start the API and frontend in separate terminals:

```powershell
php -S 127.0.0.1:8090 -t backend/public
npm --prefix web run dev
```

Open:

- Frontend development server: <http://localhost:5173>
- API health endpoint: <http://localhost:8090/api/v1/health>

For an independently installed Mailpit executable, run `mailpit --smtp 127.0.0.1:1025 --listen 127.0.0.1:8025` in another
terminal. Set `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`, blank `MAIL_ENCRYPTION`, `MAIL_USERNAME`, and `MAIL_PASSWORD`,
and `ENQUIRY_NOTIFICATION_EMAIL=enquiries@example.test` in `backend/.env`. Read captured messages at
`http://127.0.0.1:8025`. An alternative catcher can use the same SMTP settings. In production use an approved SMTP
provider with `MAIL_ENCRYPTION=tls` (normally port 587) or `ssl` (normally 465), real sender/recipient, and private credentials.
The committed environment files contain placeholders only. Real secrets belong in ignored environment files or hosting configuration.

## Commands

```bash
make install   # install locked backend and frontend dependencies
make up        # run local API and Vite under one foreground supervisor
make down      # stop only supervisor-managed API/Vite processes
make migrate   # run registered migrations
make seed      # run development seeders; never run production seed data blindly
make test      # run backend and frontend tests
make lint      # run PHP style/static analysis and frontend lint/type checks
make build     # create web/dist for deployment
make api       # start the local PHP API at 127.0.0.1:8090
make web-dev   # start the Vite development server
```

Database commands can also be run directly:

```bash
php backend/bin/migrate.php up
php backend/bin/migrate.php status
php backend/bin/migrate.php down 1
php backend/bin/seed.php
```

Migrations run in deterministic filename order and are recorded in `schema_migrations`. Rollback is always explicit;
production rollback additionally requires `--force`. There is intentionally no reset or fresh command. Seeders are
restricted to `local`, `development`, and `testing` environments and contain reference roles and permissions only.
Configure a least-privilege database user and take a verified backup before production migrations.

The equivalent Composer/npm/PHP commands are defined directly in the [Makefile](./Makefile).

## Shared Linux hosting deployment

Before selecting or configuring a host, confirm it supports PHP 8.3+, the required PHP extensions, MySQL/MariaDB, HTTPS, URL rewriting, cron jobs, SSH or a secure deployment method, environment configuration outside the public directory, and writable application storage.

Recommended layout when the hosting account permits separate document roots:

```text
account-home/
├── app/                 # backend source, vendor, configuration, migrations
├── storage/             # logs/uploads; writable and not executable
└── public_html/         # web/dist assets and public entry points only
    └── api/             # routes API requests to backend/public/index.php
```

The exact symlink/front-controller arrangement depends on the hosting provider. The following rules are mandatory:

- Do not expose `.env`, Composer files, source code, migrations, logs, tests, or private uploads through the public document root.
- Point API requests at `backend/public/index.php` and route `/api/v1/*` to that front controller.
- Serve the compiled `web/dist` application and configure history fallback without rewriting `/api/*` requests to `index.html`.
- Build and test in CI or a trusted build machine. Deploy `composer install --no-dev --classmap-authoritative` output and the prebuilt frontend; do not depend on production npm tooling.
- Set `APP_ENV=production` and `APP_DEBUG=false`, configure the production base URL, restrict CORS, and enable HTTPS/HSTS and the documented security headers.
- Make only required storage paths writable. Disable script execution in upload directories.
- Take a database and media backup before migrations, run reviewed forward-compatible migrations explicitly, and perform health and smoke checks after deployment.
- Use versioned release directories or a verified hosting backup/snapshot for rollback. Database rollback must follow the migration runbook rather than blindly running destructive down migrations.
- Configure scheduled backups and future queue/scheduled tasks through the hosting control panel's cron facility.

A provider-specific deployment and rewrite configuration must be documented after the shared host and its Apache/LiteSpeed/Nginx control model are known.

## Structure

- `backend/`: PHP 8.3 REST API and backend tests
- `web/`: React, TypeScript, Vite, Tailwind CSS and frontend tests
- `docs/`: product, architecture, security, delivery, and implementation guidance
- `.github/workflows/`: build and quality verification

The repository contains the engineering foundation, CMS/public website and admin identity prerequisites, plus enquiry
and consultation submission and administration. See [enquiry API, status workflow and operations](docs/13-enquiries.md).
Publish approved privacy and consent content before enabling the public form. Production hosting, legal approval and
SMTP delivery must be verified separately; a passing local build is not production sign-off.
