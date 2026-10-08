#!/usr/bin/env bash
# Remove Mr Agent site publish. Restores previous docroot after vhost mode.
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

PLUGIN_SRC="$(cd "$(dirname "$0")" && pwd)"
MARKER_DIR="${PLUGIN_SRC}/data"
SITE_JSON="/var/lib/cpn/sites/${DOMAIN}.json"
MODE="folder"
if [[ -f "${MARKER_DIR}/install_mode.txt" ]]; then
  MODE="$(tr '[:upper:]' '[:lower:]' < "${MARKER_DIR}/install_mode.txt" | xargs)"
fi

DOCROOT=""
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

if [[ "$MODE" == "vhost" && -f "${MARKER_DIR}/vhost_previous_docroot.txt" && -f "$SITE_JSON" ]]; then
  PREV="$(tr -d '\r\n' < "${MARKER_DIR}/vhost_previous_docroot.txt")"
  if [[ -n "$PREV" ]]; then
    log "Restoring previous docroot for ${DOMAIN}: ${PREV}"
    python3 - "$SITE_JSON" "$PREV" <<'PY'
import json, sys
path, prev = sys.argv[1], sys.argv[2]
with open(path, encoding="utf-8") as fh:
    data = json.load(fh)
data["docroot"] = prev
data["document_root"] = prev
with open(path, "w", encoding="utf-8") as fh:
    json.dump(data, fh, indent=2, ensure_ascii=False)
    fh.write("\n")
PY
    chmod 600 "$SITE_JSON" 2>/dev/null || true
    DOCROOT="$PREV"
  fi
fi

FOLDER_ROOT="$DOCROOT"
if [[ -f "${MARKER_DIR}/folder_docroot.txt" ]]; then
  FOLDER_ROOT="$(tr -d '\r\n' < "${MARKER_DIR}/folder_docroot.txt")"
fi
if [[ -n "$FOLDER_ROOT" ]]; then
  TARGET="${FOLDER_ROOT}/mr-agent"
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

if command -v systemctl >/dev/null 2>&1; then
  if systemctl is-active --quiet lsws 2>/dev/null || systemctl is-active --quiet lshttpd 2>/dev/null; then
    systemctl restart lsws 2>/dev/null || systemctl restart lshttpd 2>/dev/null || true
  fi
fi

log "OK: Mr Agent public publish removed. Remove the catalog plugin with: cpn plugin remove --domain ${DOMAIN} --id mrAgent --yes"
