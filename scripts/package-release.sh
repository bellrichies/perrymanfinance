#!/usr/bin/env bash
set -euo pipefail

version="${1:-$(git rev-parse --short=12 HEAD)}"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
build_dir="$root/build"
stage_dir="$build_dir/perrymanfinance-$version"
artifact="$build_dir/perrymanfinance-$version.zip"

rm -rf "$stage_dir" "$artifact"
mkdir -p "$stage_dir/private/backend" "$stage_dir/public" "$stage_dir/ops"

cd "$root/backend"
composer validate --strict
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

cd "$root/web"
npm ci
if [[ "${SKIP_PRERENDER:-0}" == "1" ]]; then
  npm run build
else
  npm run build:release
fi

cd "$root"
rsync -a \
  --exclude='.env' \
  --exclude='.phpunit.cache' \
  --exclude='storage/logs/*.log' \
  --exclude='storage/uploads/*' \
  --exclude='tests' \
  backend/app backend/bootstrap backend/bin backend/database backend/public backend/routes \
  backend/composer.json backend/composer.lock backend/vendor \
  "$stage_dir/private/backend/"

rsync -a web/dist/ "$stage_dir/public/"
cp backend/public/.htaccess "$stage_dir/ops/backend-public.htaccess"
cp docs/13-deployment-runbook.md docs/14-rollback-runbook.md docs/15-backup-restore-runbook.md docs/16-production-checklist.md "$stage_dir/ops/"

cat > "$stage_dir/RELEASE.txt" <<EOF
PerrymanFinance release: $version
Commit: $(git rev-parse HEAD)
Built at: $(date -u +"%Y-%m-%dT%H:%M:%SZ")

Upload layout:
- private/backend: PHP source, migrations, Composer production dependencies.
- public: prebuilt frontend assets for the public document root.
- ops: runbooks and provider rewrite/security reference files.

Secrets are intentionally excluded. Configure environment values through the hosting panel or protected deployment secrets.
EOF

cd "$build_dir"
zip -qr "$artifact" "perrymanfinance-$version"
echo "$artifact"
