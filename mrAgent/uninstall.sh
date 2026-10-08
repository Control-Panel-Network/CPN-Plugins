#!/usr/bin/env bash
# Remove Mr Agent public symlink. Optional: --purge-secrets
# Usage: ./uninstall.sh <domain> [--purge-secrets]
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

DOMAIN="${1:-}"
DOMAIN="$(echo "$DOMAIN" | tr '[:upper:]' '[:lower:]' | xargs)"
[[ -n "$DOMAIN" ]] || die "Usage: $0 <domain> [--purge-secrets]"
PURGE=0
if [[ "${2:-}" == "--purge-secrets" ]]; then
  PURGE=1
fi

DOCROOT=""
SITE_JSON="/var/lib/cpn/sites/${DOMAIN}.json"
if [[ -f "$SITE_JSON" ]]; then
  DOCROOT="$(python3 -c 'import json,sys; d=json.load(open(sys.argv[1])); print(d.get("docroot") or d.get("document_root") or "")' "$SITE_JSON" 2>/dev/null || true)"
fi
if [[ -z "$DOCROOT" ]]; then
  parent="${DOMAIN#*.}"
  for cand in \
    "/home/${DOMAIN}/public_html" \
    "/home/newstargeted.com/${DOMAIN}/public_html" \
    "/home/${parent}/${DOMAIN}/public_html"
  do
    if [[ -d "$cand" ]]; then
      DOCROOT="$cand"
      break
    fi
  done
fi

if [[ -n "$DOCROOT" ]]; then
  TARGET="${DOCROOT}/mr-agent"
  if [[ -e "$TARGET" || -L "$TARGET" ]]; then
    rm -rf "$TARGET"
    log "Removed ${TARGET}"
  fi
fi

if [[ "$PURGE" -eq 1 ]]; then
  SECRET_DIR="/var/lib/cpn/mr-agent/${DOMAIN}"
  if [[ -d "$SECRET_DIR" ]]; then
    rm -rf "$SECRET_DIR"
    log "Purged ${SECRET_DIR}"
  fi
else
  log "Secrets kept under /var/lib/cpn/mr-agent/${DOMAIN}/ (pass --purge-secrets to remove)"
fi

log "OK: Mr Agent public URL removed. Remove the catalog plugin with: cpn plugin remove --domain ${DOMAIN} --id mrAgent --yes"
