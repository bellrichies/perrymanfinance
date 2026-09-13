#!/usr/bin/env bash
set -euo pipefail

version="${1:?Usage: remote-migrate.sh <version>}"
ssh_port="${SSH_PORT:-22}"

: "${SSH_PRIVATE_KEY:?SSH_PRIVATE_KEY is required}"
: "${SSH_HOST:?SSH_HOST is required}"
: "${SSH_USER:?SSH_USER is required}"
: "${RELEASE_ROOT:?RELEASE_ROOT is required}"

key_file="$(mktemp)"
trap 'rm -f "$key_file"' EXIT
printf '%s\n' "$SSH_PRIVATE_KEY" > "$key_file"
chmod 600 "$key_file"

ssh -i "$key_file" -p "$ssh_port" -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST" \
  "cd '$RELEASE_ROOT/releases/perrymanfinance-$version/private/backend' && php bin/migrate.php up"
