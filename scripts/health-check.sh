#!/usr/bin/env bash
set -euo pipefail

base_url="${1:?Usage: health-check.sh <base-url>}"
base_url="${base_url%/}"

curl --fail --silent --show-error --max-time 20 "$base_url/api/v1/health" > /tmp/perryman-health.json
grep -q '"success"[[:space:]]*:[[:space:]]*true' /tmp/perryman-health.json
