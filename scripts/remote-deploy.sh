#!/usr/bin/env bash
set -euo pipefail

artifact="${1:?Usage: remote-deploy.sh <artifact.zip> <version>}"
version="${2:?Usage: remote-deploy.sh <artifact.zip> <version>}"
ssh_port="${SSH_PORT:-22}"

: "${SSH_PRIVATE_KEY:?SSH_PRIVATE_KEY is required}"
: "${SSH_HOST:?SSH_HOST is required}"
: "${SSH_USER:?SSH_USER is required}"
: "${RELEASE_ROOT:?RELEASE_ROOT is required}"

key_file="$(mktemp)"
trap 'rm -f "$key_file"' EXIT
printf '%s\n' "$SSH_PRIVATE_KEY" > "$key_file"
chmod 600 "$key_file"

ssh_cmd=(ssh -i "$key_file" -p "$ssh_port" -o StrictHostKeyChecking=accept-new "$SSH_USER@$SSH_HOST")
scp_cmd=(scp -i "$key_file" -P "$ssh_port" -o StrictHostKeyChecking=accept-new)

"${ssh_cmd[@]}" "mkdir -p '$RELEASE_ROOT/releases' '$RELEASE_ROOT/incoming'"
"${scp_cmd[@]}" "$artifact" "$SSH_USER@$SSH_HOST:$RELEASE_ROOT/incoming/perrymanfinance-$version.zip"
"${ssh_cmd[@]}" "cd '$RELEASE_ROOT/releases' && rm -rf 'perrymanfinance-$version' && unzip -q '../incoming/perrymanfinance-$version.zip' && ln -sfn '$RELEASE_ROOT/releases/perrymanfinance-$version' '$RELEASE_ROOT/current'"
