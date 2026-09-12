#!/usr/bin/env bash
# Wire ntHostingBilling public UI into the site docroot as /nt-billing.
# Usage (as root): ./install-host.sh test2.newstargeted.com
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

DOMAIN="${1:-${DOMAIN:-}}"
DOMAIN="$(echo "$DOMAIN" | tr '[:upper:]' '[:lower:]' | xargs)"
[[ -n "$DOMAIN" ]] || die "Usage: $0 <domain>"

PLUGIN_SRC="$(cd "$(dirname "$0")" && pwd)"
PUBLIC_SRC="${PLUGIN_SRC}/public"
[[ -d "$PUBLIC_SRC" ]] || die "Missing public/ in plugin tree"

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

[[ -n "$DOCROOT" && -d "$DOCROOT" ]] || die "Could not resolve docroot for ${DOMAIN}"

TARGET="${DOCROOT}/nt-billing"
log "Linking ${PUBLIC_SRC} -> ${TARGET}"
rm -rf "$TARGET"
ln -sfn "$PUBLIC_SRC" "$TARGET"

if [[ ! -f "${PLUGIN_SRC}/config.php" && -f "${PLUGIN_SRC}/config.php.example" ]]; then
  cp "${PLUGIN_SRC}/config.php.example" "${PLUGIN_SRC}/config.php"
  chmod 600 "${PLUGIN_SRC}/config.php"
  log "Created ${PLUGIN_SRC}/config.php from example (set admin_password and license)."
fi

mkdir -p "${PLUGIN_SRC}/data"
chmod 700 "${PLUGIN_SRC}/data"
install -d -m 700 /var/lib/cpn/nt-hosting-billing || true

log "OK: open http(s)://${DOMAIN}/nt-billing after PHP is available for the vhost."
log "CPN Plugins Settings for ntHostingBilling; License tab inside the commerce UI."
