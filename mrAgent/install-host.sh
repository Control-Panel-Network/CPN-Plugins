#!/usr/bin/env bash
# Host install for Mr Agent (no site docroot / vhost takeover).
# Usage (as root): ./install-host.sh
#   HOST_PLUGIN_ROOT=/var/lib/cpn/host-plugins/mrAgent ./install-host.sh
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

PLUGIN_SRC="$(cd "$(dirname "$0")" && pwd)"
HOST_ROOT="${HOST_PLUGIN_ROOT:-/var/lib/cpn/host-plugins/mrAgent}"
SECRET_DIR="/var/lib/cpn/mr-agent/_host"

mkdir -p "$(dirname "$HOST_ROOT")"
if [[ "$PLUGIN_SRC" != "$HOST_ROOT" ]]; then
  if [[ ! -d "$HOST_ROOT" ]]; then
    log "Copying ${PLUGIN_SRC} -> ${HOST_ROOT}"
    mkdir -p "$HOST_ROOT"
    if command -v rsync >/dev/null 2>&1; then
      rsync -a --delete \
        --exclude 'data/domain.txt' \
        --exclude 'config.php' \
        "${PLUGIN_SRC}/" "${HOST_ROOT}/"
    else
      tar -C "$PLUGIN_SRC" \
        --exclude='data/domain.txt' \
        --exclude='config.php' \
        -cf - . | tar -C "$HOST_ROOT" -xf -
    fi
  else
    log "Host plugin tree already present at ${HOST_ROOT} (panel catalog install). Finalizing secrets only."
  fi
fi

mkdir -p "${HOST_ROOT}/data"
chmod 700 "${HOST_ROOT}/data"
printf '%s\n' "_host" > "${HOST_ROOT}/data/domain.txt"
chmod 600 "${HOST_ROOT}/data/domain.txt"
printf '%s\n' "host" > "${HOST_ROOT}/data/install_mode.txt"
chmod 600 "${HOST_ROOT}/data/install_mode.txt"

mkdir -p "$SECRET_DIR"
chmod 700 /var/lib/cpn/mr-agent 2>/dev/null || true
chmod 700 "$SECRET_DIR"

if [[ ! -f "${HOST_ROOT}/config.php" && -f "${HOST_ROOT}/config.php.example" ]]; then
  PASS="$(openssl rand -base64 24 2>/dev/null | tr -d '/+=' | head -c 24 || head -c 24 /dev/urandom | base64 | tr -d '/+=' | head -c 24)"
  cat > "${HOST_ROOT}/config.php" <<EOF
<?php
return [
    'access_password' => '${PASS}',
    'domain' => '_host',
    'openai_api_key' => '',
    'anthropic_api_key' => '',
    'custom_api_key' => '',
    'custom_base_url' => '',
    'local_base_url' => 'http://127.0.0.1:11434/v1',
    'local_api_key' => '',
    'local_model' => 'llama3.2:1b',
];
EOF
  chmod 600 "${HOST_ROOT}/config.php"
  printf '%s\n' "$PASS" > "${SECRET_DIR}/access.password"
  chmod 600 "${SECRET_DIR}/access.password"
  log "Created host config.php and access password at ${SECRET_DIR}/access.password"
fi

if [[ ! -f "${SECRET_DIR}/keys.json" ]]; then
  printf '%s\n' '{"host":{},"users":{}}' > "${SECRET_DIR}/keys.json"
  chmod 600 "${SECRET_DIR}/keys.json"
fi

mkdir -p "${SECRET_DIR}/chats" "${SECRET_DIR}/locks"
chmod 700 "${SECRET_DIR}/chats" "${SECRET_DIR}/locks"

if [[ ! -f "${HOST_ROOT}/settings.json" ]]; then
  cat > "${HOST_ROOT}/settings.json" <<'EOF'
{
  "show_in_sidebar": true,
  "fields": {
    "install_mode": "folder",
    "public_path": "/mr-agent",
    "enabled": "1",
    "show_floating_bubble": "1",
    "visibility": "admins_only",
    "expand_via": "panel"
  }
}
EOF
  chmod 600 "${HOST_ROOT}/settings.json"
fi

log "OK: Host Mr Agent ready at ${HOST_ROOT}"
log "Chat via CPN Panel: /plugins/mr-agent (no site docroot takeover)"
log "Site users may still Install on a site (folder default) if Host is not used."
