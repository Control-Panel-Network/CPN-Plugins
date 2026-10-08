#!/usr/bin/env bash
# Deploy Mr Agent public UI into the site docroot as /mr-agent.
# Usage (as root): ./install.sh <domain>
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

TARGET="${DOCROOT}/mr-agent"
log "Linking ${PUBLIC_SRC} -> ${TARGET}"
rm -rf "$TARGET"
ln -sfn "$PUBLIC_SRC" "$TARGET"

mkdir -p "${PLUGIN_SRC}/data"
chmod 700 "${PLUGIN_SRC}/data"
printf '%s\n' "$DOMAIN" > "${PLUGIN_SRC}/data/domain.txt"
chmod 600 "${PLUGIN_SRC}/data/domain.txt"

SECRET_DIR="/var/lib/cpn/mr-agent/${DOMAIN}"
mkdir -p "$SECRET_DIR"
chmod 700 /var/lib/cpn/mr-agent 2>/dev/null || true
chmod 700 "$SECRET_DIR"

if [[ ! -f "${PLUGIN_SRC}/config.php" && -f "${PLUGIN_SRC}/config.php.example" ]]; then
  PASS="$(openssl rand -base64 24 2>/dev/null | tr -d '/+=' | head -c 24 || head -c 24 /dev/urandom | base64 | tr -d '/+=' | head -c 24)"
  cat > "${PLUGIN_SRC}/config.php" <<EOF
<?php
return [
    'access_password' => '${PASS}',
    'domain' => '${DOMAIN}',
    'openai_api_key' => '',
    'anthropic_api_key' => '',
    'custom_api_key' => '',
    'custom_base_url' => '',
    'local_base_url' => 'http://127.0.0.1:11434/v1',
    'local_api_key' => '',
    'local_model' => 'llama3.2:1b',
];
EOF
  chmod 600 "${PLUGIN_SRC}/config.php"
  printf '%s\n' "$PASS" > "${SECRET_DIR}/access.password"
  chmod 600 "${SECRET_DIR}/access.password"
  log "Created config.php and wrote access password to ${SECRET_DIR}/access.password"
fi

if [[ ! -f "${SECRET_DIR}/keys.json" ]]; then
  printf '%s\n' '{"host":{},"users":{}}' > "${SECRET_DIR}/keys.json"
  chmod 600 "${SECRET_DIR}/keys.json"
fi

# Prefer OLS/LSE: ensure .php is executed under the symlink target.
if command -v systemctl >/dev/null 2>&1; then
  if systemctl is-active --quiet lsws 2>/dev/null || systemctl is-active --quiet lshttpd 2>/dev/null; then
    log "Restarting LiteSpeed so /mr-agent is live..."
    systemctl restart lsws 2>/dev/null || systemctl restart lshttpd 2>/dev/null || true
  fi
fi

log "OK: open https://${DOMAIN}/mr-agent"
log "Sign in with username owner (or admin) and the access password from ${SECRET_DIR}/access.password"
log "CPN Plugin settings: visibility, package_ids, allow_user_keys, rate_limit_per_hour"
