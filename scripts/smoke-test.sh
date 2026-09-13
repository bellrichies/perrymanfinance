#!/usr/bin/env bash
set -euo pipefail

base_url="${1:?Usage: smoke-test.sh <base-url>}"
base_url="${base_url%/}"

paths=(
  "/"
  "/about"
  "/investment-opportunities"
  "/insights"
  "/faq"
  "/api/v1/health"
)

for path in "${paths[@]}"; do
  curl --fail --silent --show-error --location --max-time 20 "$base_url$path" > /dev/null
done
