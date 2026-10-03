#!/usr/bin/env bash
# Remove the public /filegator symlink and optional app tree for one site.
# Does not delete /var/lib/cpn/filegator/<domain>/ credentials (operator may keep them).
# Usage: ./uninstall.sh <domain> [--purge-app]
set -euo pipefail

PLUGIN_SRC="$(cd "$(dirname "$0")" && pwd)"
DOMAIN="$(echo "${1:-}" | tr '[:upper:]' '[:lower:]' | xargs)"
PURGE_APP=0
[[ "${2:-}" == "--purge-app" ]] && PURGE_APP=1
[[ -n "$DOMAIN" ]] || { echo "Usage: $0 <domain> [--purge-app]" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  echo "ERROR: Run as root (sudo)." >&2
  exit 1
fi

DOCROOT=""
SITE_JSON="/var/lib/cpn/sites/${DOMAIN}.json"
if [[ -f "$SITE_JSON" ]]; then
  DOCROOT="$(python3 -c 'import json,sys; d=json.load(open(sys.argv[1],encoding="utf-8")); print((d.get("docroot") or d.get("document_root") or "").strip())' "$SITE_JSON" 2>/dev/null || true)"
fi
if [[ -z "$DOCROOT" ]]; then
  parent="${DOMAIN#*.}"
  for cand in "/home/${DOMAIN}/public_html" "/home/${parent}/${DOMAIN}/public_html"; do
    [[ -d "$cand" ]] && DOCROOT="$cand" && break
  done
fi

PUBLIC_SLUG="filegator"
if [[ -f "${PLUGIN_SRC}/.install-state.json" ]]; then
  PUBLIC_SLUG="$(python3 -c 'import json,sys; d=json.load(open(sys.argv[1],encoding="utf-8")); p=(d.get("public_path") or "/filegator").strip().strip("/"); print(p or "filegator")' "${PLUGIN_SRC}/.install-state.json" 2>/dev/null || echo filegator)"
fi

if [[ -n "$DOCROOT" ]]; then
  TARGET="${DOCROOT}/${PUBLIC_SLUG}"
  if [[ -L "$TARGET" ]]; then
    rm -f "$TARGET"
    echo "Removed symlink ${TARGET}"
  elif [[ -e "$TARGET" ]]; then
    echo "WARN: ${TARGET} exists and is not a symlink; left untouched" >&2
  fi
fi

if [[ "$PURGE_APP" -eq 1 && -d "${PLUGIN_SRC}/app" ]]; then
  rm -rf "${PLUGIN_SRC}/app"
  echo "Removed ${PLUGIN_SRC}/app"
fi

rm -f "${PLUGIN_SRC}/.install-state.json" 2>/dev/null || true
echo "OK: FileGator public link cleaned for ${DOMAIN}"
echo "Credentials (if any) remain under /var/lib/cpn/filegator/${DOMAIN}/"
echo "Remove the catalog plugin with: cpn plugin remove --domain ${DOMAIN} --id fileGator --yes"
