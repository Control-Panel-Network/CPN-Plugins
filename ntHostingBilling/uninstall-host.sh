#!/usr/bin/env bash
# Remove /nt-billing symlink from site docroot.
set -euo pipefail
DOMAIN="${1:-${DOMAIN:-}}"
DOMAIN="$(echo "$DOMAIN" | tr '[:upper:]' '[:lower:]' | xargs)"
[[ -n "$DOMAIN" ]] || { echo "Usage: $0 <domain>" >&2; exit 1; }

DOCROOT=""
SITE_JSON="/var/lib/cpn/sites/${DOMAIN}.json"
if [[ -f "$SITE_JSON" ]]; then
  DOCROOT="$(python3 -c 'import json,sys;print(json.load(open(sys.argv[1])).get("docroot") or "")' "$SITE_JSON" 2>/dev/null || true)"
fi
if [[ -z "$DOCROOT" ]]; then
  parent="${DOMAIN#*.}"
  for cand in \
    "/home/${DOMAIN}/public_html" \
    "/home/newstargeted.com/${DOMAIN}/public_html" \
    "/home/${parent}/${DOMAIN}/public_html"
  do
    if [[ -d "$cand" ]]; then DOCROOT="$cand"; break; fi
  done
fi
[[ -n "$DOCROOT" ]] || { echo "Docroot not found" >&2; exit 1; }
TARGET="${DOCROOT}/nt-billing"
if [[ -L "$TARGET" || -d "$TARGET" ]]; then
  rm -rf "$TARGET"
  echo "Removed ${TARGET}"
else
  echo "Nothing to remove at ${TARGET}"
fi
