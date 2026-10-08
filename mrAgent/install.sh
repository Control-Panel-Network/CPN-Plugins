#!/usr/bin/env bash
# Publish Mr Agent for a site.
#
# Modes (INSTALL_MODE):
#   folder (default): symlink public/ under the site docroot as /mr-agent/
#                     without replacing the site index or docroot.
#   vhost:            point the site document root at this plugin's public/
#                     (full sub-domain / site takeover). Requires CONFIRM=yes.
#
# Usage (as root):
#   ./install.sh <domain>
#   INSTALL_MODE=folder ./install.sh <domain>
#   INSTALL_MODE=vhost CONFIRM=yes ./install.sh <domain>
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

DOMAIN="${1:-${DOMAIN:-}}"
DOMAIN="$(echo "$DOMAIN" | tr '[:upper:]' '[:lower:]' | xargs)"
[[ -n "$DOMAIN" ]] || die "Usage: INSTALL_MODE=folder|vhost $0 <domain>  (vhost also needs CONFIRM=yes)"

INSTALL_MODE="$(echo "${INSTALL_MODE:-folder}" | tr '[:upper:]' '[:lower:]' | xargs)"
CONFIRM="$(echo "${CONFIRM:-${CONFIRM_VHOST:-}}" | tr '[:upper:]' '[:lower:]' | xargs)"

case "$INSTALL_MODE" in
  folder|vhost) ;;
  *) die "INSTALL_MODE must be folder or vhost (got: ${INSTALL_MODE})" ;;
esac

PLUGIN_SRC="$(cd "$(dirname "$0")" && pwd)"
PUBLIC_SRC="${PLUGIN_SRC}/public"
[[ -d "$PUBLIC_SRC" ]] || die "Missing public/ in plugin tree"

resolve_docroot() {
  local domain="$1" docroot="" parent
  local site_json="/var/lib/cpn/sites/${domain}.json"
  if [[ -f "$site_json" ]]; then
    docroot="$(python3 -c 'import json,sys; d=json.load(open(sys.argv[1])); print(d.get("docroot") or d.get("document_root") or "")' "$site_json" 2>/dev/null || true)"
  fi
  if [[ -z "$docroot" ]]; then
    parent="${domain#*.}"
    for cand in \
      "/home/${domain}/public_html" \
      "/home/newstargeted.com/${domain}/public_html" \
      "/home/${parent}/${domain}/public_html"
    do
      if [[ -d "$cand" ]]; then
        docroot="$cand"
        break
      fi
    done
  fi
  printf '%s' "$docroot"
}

DOCROOT="$(resolve_docroot "$DOMAIN")"
[[ -n "$DOCROOT" && -d "$DOCROOT" ]] || die "Could not resolve docroot for ${DOMAIN}"

SITE_JSON="/var/lib/cpn/sites/${DOMAIN}.json"
MARKER_DIR="${PLUGIN_SRC}/data"
mkdir -p "$MARKER_DIR"
chmod 700 "$MARKER_DIR"
printf '%s\n' "$DOMAIN" > "${MARKER_DIR}/domain.txt"
chmod 600 "${MARKER_DIR}/domain.txt"
printf '%s\n' "$INSTALL_MODE" > "${MARKER_DIR}/install_mode.txt"
chmod 600 "${MARKER_DIR}/install_mode.txt"

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
  # 640 + nobody group so OLS/PHP can require config.php via the /mr-agent symlink.
  chgrp nobody "${PLUGIN_SRC}/config.php" 2>/dev/null || true
  chmod 640 "${PLUGIN_SRC}/config.php"
  printf '%s\n' "$PASS" > "${SECRET_DIR}/access.password"
  chmod 600 "${SECRET_DIR}/access.password"
  log "Created config.php and wrote access password to ${SECRET_DIR}/access.password"
elif [[ -f "${PLUGIN_SRC}/config.php" ]]; then
  chgrp nobody "${PLUGIN_SRC}/config.php" 2>/dev/null || true
  chmod 640 "${PLUGIN_SRC}/config.php" 2>/dev/null || true
fi

if [[ ! -f "${SECRET_DIR}/keys.json" ]]; then
  printf '%s\n' '{"host":{},"users":{}}' > "${SECRET_DIR}/keys.json"
  chmod 600 "${SECRET_DIR}/keys.json"
fi

mkdir -p "${SECRET_DIR}/chats" "${SECRET_DIR}/locks"
chmod 700 "${SECRET_DIR}/chats" "${SECRET_DIR}/locks"

if command -v php >/dev/null 2>&1 && [[ -f "${PLUGIN_SRC}/modules/cli_prune.php" ]]; then
  log "Pruning Mr Agent chat logs for ${DOMAIN}..."
  php "${PLUGIN_SRC}/modules/cli_prune.php" "${DOMAIN}" >/dev/null 2>&1 || true
fi

publish_folder() {
  local target="${DOCROOT}/mr-agent"
  if [[ -e "$target" || -L "$target" ]]; then
    if [[ -L "$target" ]]; then
      rm -f "$target"
    elif [[ -d "$target" ]]; then
      if [[ -f "${target}/index.php" ]]; then
        rm -rf "$target"
      else
        die "Refusing to replace non-empty ${target} (not a Mr Agent publish). Move it aside first."
      fi
    else
      die "Refusing to replace unexpected file at ${target}"
    fi
  fi
  log "Folder mode: linking ${PUBLIC_SRC} -> ${target}"
  ln -sfn "$PUBLIC_SRC" "$target"
  printf '%s\n' "$DOCROOT" > "${MARKER_DIR}/folder_docroot.txt"
  chmod 600 "${MARKER_DIR}/folder_docroot.txt"
  log "OK: site folder URL https://${DOMAIN}/mr-agent (site index unchanged)"
}

publish_vhost() {
  if [[ "$CONFIRM" != "yes" && "$CONFIRM" != "1" && "$CONFIRM" != "true" ]]; then
    cat >&2 <<EOF
ERROR: INSTALL_MODE=vhost refuses to run without confirmation.

This replaces the site document root for ${DOMAIN} with Mr Agent public/.
Impact:
  - Docroot becomes: ${PUBLIC_SRC}
  - Existing site files under ${DOCROOT} are NOT deleted, but the live vhost/docroot no longer serves them
  - Visitors to https://${DOMAIN}/ see Mr Agent instead of the previous site

To proceed explicitly:
  INSTALL_MODE=vhost CONFIRM=yes sudo bash $0 ${DOMAIN}

Prefer the safer default instead:
  INSTALL_MODE=folder sudo bash $0 ${DOMAIN}
EOF
    exit 2
  fi

  if [[ ! -f "$SITE_JSON" ]]; then
    die "vhost mode requires ${SITE_JSON} so the previous docroot can be restored later"
  fi

  if [[ ! -f "${MARKER_DIR}/vhost_previous_docroot.txt" ]]; then
    printf '%s\n' "$DOCROOT" > "${MARKER_DIR}/vhost_previous_docroot.txt"
    chmod 600 "${MARKER_DIR}/vhost_previous_docroot.txt"
  fi

  log "Vhost mode (confirmed): setting docroot for ${DOMAIN} to ${PUBLIC_SRC}"
  python3 - "$SITE_JSON" "$PUBLIC_SRC" <<'PY'
import json, sys
path, new_root = sys.argv[1], sys.argv[2]
with open(path, encoding="utf-8") as fh:
    data = json.load(fh)
data["docroot"] = new_root
data["document_root"] = new_root
with open(path, "w", encoding="utf-8") as fh:
    json.dump(data, fh, indent=2, ensure_ascii=False)
    fh.write("\n")
PY
  chmod 600 "$SITE_JSON" 2>/dev/null || true
  log "OK: https://${DOMAIN}/ now serves Mr Agent (previous docroot saved under data/vhost_previous_docroot.txt)"
  log "Restore with: sudo bash ${PLUGIN_SRC}/uninstall.sh ${DOMAIN}"
}

case "$INSTALL_MODE" in
  folder) publish_folder ;;
  vhost) publish_vhost ;;
esac

if command -v systemctl >/dev/null 2>&1; then
  if systemctl is-active --quiet lsws 2>/dev/null || systemctl is-active --quiet lshttpd 2>/dev/null; then
    log "Restarting LiteSpeed so publish is live..."
    systemctl restart lsws 2>/dev/null || systemctl restart lshttpd 2>/dev/null || true
  fi
fi

log "Panel Expand / full chat (preferred): open CPN /plugins/mr-agent?domain=${DOMAIN}"
log "Sign in with username owner (or admin) and the access password from ${SECRET_DIR}/access.password"
log "CPN Plugin settings: install_mode, visibility, package_ids, retention/disk caps"
log "Manual prune: php ${PLUGIN_SRC}/modules/cli_prune.php ${DOMAIN}"
