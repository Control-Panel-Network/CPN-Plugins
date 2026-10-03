#!/usr/bin/env bash
# Deploy FileGator for one CPN site (site-jailed file manager).
# Usage (as root):
#   ./install.sh <domain>
#   ./install.sh <domain> --heal
#   ./install.sh <domain> --reset-password
# Env:
#   REPO_SCOPE=home|docroot   (default: home)
#   PUBLIC_PATH=/filegator    (default: /filegator)
set -euo pipefail

PLUGIN_SRC="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_ID="fileGator"

# Pinned FileGator precompiled build (matches APP_VERSION inside dist/index.php).
FILEGATOR_VERSION="7.16.5"
FILEGATOR_ZIP_URL="https://raw.githubusercontent.com/filegator/static/master/builds/filegator_latest.zip"
# SHA256 of the zip when it shipped FileGator 7.16.5 (recompute when bumping FILEGATOR_VERSION).
FILEGATOR_ZIP_SHA256="6236363556F78ACE18E3405156ED297130081AD5C69F1F3FD65BAC9CA4701421"

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

DOMAIN=""
HEAL=0
RESET_PASSWORD=0
for arg in "$@"; do
  case "$arg" in
    --heal) HEAL=1 ;;
    --reset-password) RESET_PASSWORD=1 ;;
    -h|--help)
      cat <<EOF
Usage: $0 <domain> [--heal] [--reset-password]

Installs FileGator ${FILEGATOR_VERSION} under this plugin folder, jails the
repository to the site home (or docroot), and publishes PUBLIC_PATH under the
site document root.

Credentials are written to /var/lib/cpn/filegator/<domain>/admin.credentials (mode 600).
EOF
      exit 0
      ;;
    *)
      if [[ -z "$DOMAIN" && "$arg" != -* ]]; then
        DOMAIN="$arg"
      else
        die "Unknown argument: $arg"
      fi
      ;;
  esac
done

DOMAIN="$(echo "${DOMAIN:-}" | tr '[:upper:]' '[:lower:]' | xargs)"
[[ -n "$DOMAIN" ]] || die "Usage: $0 <domain> [--heal] [--reset-password]"

REPO_SCOPE="${REPO_SCOPE:-home}"
PUBLIC_PATH="${PUBLIC_PATH:-/filegator}"
PUBLIC_PATH="/${PUBLIC_PATH#/}"
PUBLIC_PATH="${PUBLIC_PATH%/}"
[[ -n "$PUBLIC_PATH" ]] || PUBLIC_PATH="filegator"
PUBLIC_SLUG="${PUBLIC_PATH#/}"

resolve_site() {
  local site_json="/var/lib/cpn/sites/${DOMAIN}.json"
  DOCROOT=""
  SITE_HOME=""
  if [[ -f "$site_json" ]]; then
    # shellcheck disable=SC2016
    eval "$(python3 - "$site_json" <<'PY'
import json, shlex, sys
path = sys.argv[1]
with open(path, encoding="utf-8") as fh:
    data = json.load(fh)
docroot = (data.get("docroot") or data.get("document_root") or "").strip()
home = (data.get("home") or data.get("site_home") or data.get("path") or "").strip()
if not home and docroot:
    # Typical CPN layout: /home/<domain>/public_html or /home/<parent>/<fqdn>/public_html
    import os
    home = os.path.dirname(docroot.rstrip("/"))
print("DOCROOT=" + shlex.quote(docroot))
print("SITE_HOME=" + shlex.quote(home))
PY
)"
  fi

  if [[ -z "$DOCROOT" ]]; then
    local parent="${DOMAIN#*.}"
    local cand
    for cand in \
      "/home/${DOMAIN}/public_html" \
      "/home/${parent}/${DOMAIN}/public_html"
    do
      if [[ -d "$cand" ]]; then
        DOCROOT="$cand"
        SITE_HOME="$(dirname "$cand")"
        break
      fi
    done
  fi

  [[ -n "$DOCROOT" && -d "$DOCROOT" ]] || die "Could not resolve docroot for ${DOMAIN}"
  [[ -n "$SITE_HOME" && -d "$SITE_HOME" ]] || SITE_HOME="$(dirname "$DOCROOT")"
}

resolve_site

case "$REPO_SCOPE" in
  home|site|site_home) REPO_ROOT="$SITE_HOME" ;;
  docroot|public_html|web) REPO_ROOT="$DOCROOT" ;;
  *) die "REPO_SCOPE must be home or docroot (got: $REPO_SCOPE)" ;;
esac

APP_DIR="${PLUGIN_SRC}/app"
CRED_DIR="/var/lib/cpn/filegator/${DOMAIN}"
CRED_FILE="${CRED_DIR}/admin.credentials"
STATE_FILE="${PLUGIN_SRC}/.install-state.json"
TMP_ZIP="$(mktemp /tmp/filegator-XXXXXX.zip)"
TMP_DIR="$(mktemp -d /tmp/filegator-extract-XXXXXX)"
cleanup() {
  rm -f "$TMP_ZIP" 2>/dev/null || true
  rm -rf "$TMP_DIR" 2>/dev/null || true
}
trap cleanup EXIT

need_download=1
if [[ -f "${APP_DIR}/dist/index.php" ]]; then
  if grep -q "define('APP_VERSION', '${FILEGATOR_VERSION}')" "${APP_DIR}/dist/index.php" 2>/dev/null \
    || grep -q "define(\"APP_VERSION\", \"${FILEGATOR_VERSION}\")" "${APP_DIR}/dist/index.php" 2>/dev/null; then
    if [[ "$HEAL" -eq 1 ]]; then
      need_download=0
      log "Heal: keeping existing FileGator ${FILEGATOR_VERSION} under app/"
    fi
  fi
fi

if [[ "$need_download" -eq 1 ]]; then
  log "Downloading FileGator ${FILEGATOR_VERSION} (pinned SHA256)..."
  if command -v curl >/dev/null 2>&1; then
    curl -fsSL --connect-timeout 30 --max-time 300 -o "$TMP_ZIP" "$FILEGATOR_ZIP_URL" \
      || die "Download failed: $FILEGATOR_ZIP_URL"
  elif command -v wget >/dev/null 2>&1; then
    wget -q -O "$TMP_ZIP" "$FILEGATOR_ZIP_URL" || die "Download failed: $FILEGATOR_ZIP_URL"
  else
    die "Need curl or wget to download FileGator"
  fi

  got_sha="$(sha256sum "$TMP_ZIP" | awk '{print toupper($1)}')"
  want_sha="$(echo "$FILEGATOR_ZIP_SHA256" | tr '[:lower:]' '[:upper:]')"
  [[ "$got_sha" == "$want_sha" ]] || die "SHA256 mismatch for FileGator zip (got ${got_sha}, want ${want_sha}). Bump FILEGATOR_ZIP_SHA256 when updating the pin."

  unzip -q -o "$TMP_ZIP" -d "$TMP_DIR" || die "unzip failed"
  SRC_APP=""
  if [[ -d "${TMP_DIR}/filegator" ]]; then
    SRC_APP="${TMP_DIR}/filegator"
  else
    SRC_APP="$(find "$TMP_DIR" -maxdepth 2 -type d -name dist -printf '%h\n' | head -n1 || true)"
  fi
  [[ -n "$SRC_APP" && -d "${SRC_APP}/dist" ]] || die "Unexpected zip layout (dist/ missing)"

  if ! grep -q "${FILEGATOR_VERSION}" "${SRC_APP}/dist/index.php" 2>/dev/null; then
    die "Downloaded build is not FileGator ${FILEGATOR_VERSION}; update pin URL/SHA256"
  fi

  mkdir -p "$APP_DIR"
  # Preserve private/ and configuration across upgrades.
  if [[ -d "${APP_DIR}/private" ]]; then
    rm -rf "${TMP_DIR}/_preserve_private"
    cp -a "${APP_DIR}/private" "${TMP_DIR}/_preserve_private"
  fi
  KEEP_CONFIG=""
  if [[ -f "${APP_DIR}/configuration.php" && "$HEAL" -eq 1 ]]; then
    KEEP_CONFIG="${TMP_DIR}/_preserve_configuration.php"
    cp -a "${APP_DIR}/configuration.php" "$KEEP_CONFIG"
  fi

  # Replace app tree but restore private data.
  find "$APP_DIR" -mindepth 1 -maxdepth 1 ! -name private -exec rm -rf {} + 2>/dev/null || true
  mkdir -p "$APP_DIR"
  cp -a "${SRC_APP}/." "$APP_DIR/"

  if [[ -d "${TMP_DIR}/_preserve_private" ]]; then
    rm -rf "${APP_DIR}/private"
    mv "${TMP_DIR}/_preserve_private" "${APP_DIR}/private"
  fi
  if [[ -n "$KEEP_CONFIG" && -f "$KEEP_CONFIG" ]]; then
    cp -a "$KEEP_CONFIG" "${APP_DIR}/configuration.php"
  fi
fi

[[ -d "${APP_DIR}/dist" ]] || die "FileGator app/dist missing after install"

mkdir -p "${APP_DIR}/private/logs" "${APP_DIR}/private/tmp" "${APP_DIR}/private/sessions" "${APP_DIR}/repository"
chmod 750 "${APP_DIR}/private" "${APP_DIR}/private/logs" "${APP_DIR}/private/tmp" "${APP_DIR}/private/sessions" || true

# Public path for subdirectory install.
if grep -q "define('APP_PUBLIC_PATH'" "${APP_DIR}/dist/index.php"; then
  sed -i "s|define('APP_PUBLIC_PATH', '[^']*');|define('APP_PUBLIC_PATH', '/${PUBLIC_SLUG}');|" "${APP_DIR}/dist/index.php"
elif grep -q 'define("APP_PUBLIC_PATH"' "${APP_DIR}/dist/index.php"; then
  sed -i "s|define(\"APP_PUBLIC_PATH\", \"[^\"]*\");|define(\"APP_PUBLIC_PATH\", \"/${PUBLIC_SLUG}\");|" "${APP_DIR}/dist/index.php"
fi

# CSRF + repository + sessions under private/
CSRF_KEY="$(openssl rand -hex 24 2>/dev/null || head -c 48 /dev/urandom | xxd -p -c 48)"
if [[ -f "${APP_DIR}/configuration.php" ]] && grep -q "CPN_FILEGATOR_MANAGED" "${APP_DIR}/configuration.php" 2>/dev/null && [[ "$HEAL" -eq 1 ]]; then
  # Refresh only the repository adapter path and public branding path markers.
  python3 - "$APP_DIR/configuration.php" "$REPO_ROOT" <<'PY'
import pathlib, re, sys
path = pathlib.Path(sys.argv[1])
repo = sys.argv[2].replace("\\", "\\\\").replace("'", "\\'")
text = path.read_text(encoding="utf-8")
# Replace Local adapter root string literal assigned to CPN marker block.
pat = r"(// CPN_REPO_ROOT_START\n)(.*?)(// CPN_REPO_ROOT_END)"
repl = r"\1            return new \\League\\Flysystem\\Adapter\\Local(\n                '" + repo + r"'\n            );\n            \3"
new, n = re.subn(pat, repl, text, count=1, flags=re.S)
if n:
    path.write_text(new, encoding="utf-8")
PY
else
  cat > "${APP_DIR}/configuration.php" <<EOF
<?php
// CPN_FILEGATOR_MANAGED=1
// Generated by CPN plugin ${PLUGIN_ID} for ${DOMAIN}. Do not commit secrets.

return [
    'public_path' => APP_PUBLIC_PATH,
    'public_dir' => APP_PUBLIC_DIR,
    'overwrite_on_upload' => false,
    'timezone' => 'UTC',
    'download_inline' => ['pdf'],
    'lockout_attempts' => 5,
    'lockout_timeout' => 15,

    'frontend_config' => [
        'app_name' => 'FileGator',
        'app_version' => APP_VERSION,
        'language' => 'english',
        'logo' => 'https://filegator.io/filegator_logo.svg',
        'upload_max_size' => 100 * 1024 * 1024,
        'upload_chunk_size' => 1 * 1024 * 1024,
        'upload_simultaneous' => 3,
        'default_archive_name' => 'archive.zip',
        'editable' => ['.txt', '.css', '.js', '.ts', '.html', '.json', '.md', '.php'],
        'date_format' => 'DD/MM/YYYY HH:mm:ss',
        'guest_redirection' => '',
        'search_simultaneous' => 5,
        'filter_entries' => [],
        'pagination' => ['', 5, 10, 15],
    ],

    'services' => [
        'Filegator\\Services\\Logger\\LoggerInterface' => [
            'handler' => '\\Filegator\\Services\\Logger\\Adapters\\MonoLogger',
            'config' => [
                'monolog_handlers' => [
                    function () {
                        return new \\Monolog\\Handler\\StreamHandler(
                            __DIR__ . '/private/logs/app.log',
                            \\Monolog\\Logger::DEBUG
                        );
                    },
                ],
            ],
        ],
        'Filegator\\Services\\Session\\SessionStorageInterface' => [
            'handler' => '\\Filegator\\Services\\Session\\Adapters\\SessionStorage',
            'config' => [
                'handler' => function () {
                    \$save_path = __DIR__ . '/private/sessions';
                    \$handler = new \\Symfony\\Component\\HttpFoundation\\Session\\Storage\\Handler\\NativeFileSessionHandler(\$save_path);
                    return new \\Symfony\\Component\\HttpFoundation\\Session\\Storage\\NativeSessionStorage([
                        'cookie_samesite' => 'Lax',
                        'cookie_secure' => null,
                        'cookie_httponly' => true,
                    ], \$handler);
                },
            ],
        ],
        'Filegator\\Services\\Cors\\Cors' => [
            'handler' => '\\Filegator\\Services\\Cors\\Cors',
            'config' => [
                'enabled' => false,
            ],
        ],
        'Filegator\\Services\\Tmpfs\\TmpfsInterface' => [
            'handler' => '\\Filegator\\Services\\Tmpfs\\Adapters\\Tmpfs',
            'config' => [
                'path' => __DIR__ . '/private/tmp/',
                'gc_probability_perc' => 10,
                'gc_older_than' => 60 * 60 * 24 * 2,
            ],
        ],
        'Filegator\\Services\\Security\\Security' => [
            'handler' => '\\Filegator\\Services\\Security\\Security',
            'config' => [
                'csrf_protection' => true,
                'csrf_key' => '${CSRF_KEY}',
                'ip_allowlist' => [],
                'ip_denylist' => [],
                'allow_insecure_overlays' => false,
            ],
        ],
        'Filegator\\Services\\View\\ViewInterface' => [
            'handler' => '\\Filegator\\Services\\View\\Adapters\\Vuejs',
            'config' => [
                'add_to_head' => '',
                'add_to_body' => '',
            ],
        ],
        'Filegator\\Services\\Storage\\Filesystem' => [
            'handler' => '\\Filegator\\Services\\Storage\\Filesystem',
            'config' => [
                'separator' => '/',
                'config' => [],
                'adapter' => function () {
                    // CPN_REPO_ROOT_START
                    return new \\League\\Flysystem\\Adapter\\Local(
                        '${REPO_ROOT}'
                    );
                    // CPN_REPO_ROOT_END
                },
            ],
        ],
        'Filegator\\Services\\Archiver\\ArchiverInterface' => [
            'handler' => '\\Filegator\\Services\\Archiver\\Adapters\\ZipArchiver',
            'config' => [],
        ],
        'Filegator\\Services\\Auth\\AuthInterface' => [
            'handler' => '\\Filegator\\Services\\Auth\\Adapters\\JsonFile',
            'config' => [
                'file' => __DIR__ . '/private/users.json',
            ],
        ],
        'Filegator\\Services\\Router\\Router' => [
            'handler' => '\\Filegator\\Services\\Router\\Router',
            'config' => [
                'query_param' => 'r',
                'routes_file' => __DIR__ . '/backend/Controllers/routes.php',
            ],
        ],
    ],
];
EOF
  chmod 600 "${APP_DIR}/configuration.php"
fi

# First-boot admin password (never commit). Preserve unless --reset-password.
mkdir -p "$CRED_DIR"
chmod 700 /var/lib/cpn/filegator "$CRED_DIR" 2>/dev/null || true

ADMIN_USER="admin"
ADMIN_PASS=""
if [[ -f "$CRED_FILE" && "$RESET_PASSWORD" -eq 0 ]]; then
  ADMIN_PASS="$(awk -F= '/^password=/{print substr($0,index($0,"=")+1); exit}' "$CRED_FILE" || true)"
fi
if [[ -z "$ADMIN_PASS" ]]; then
  ADMIN_PASS="$(openssl rand -base64 24 2>/dev/null | tr -d '/+=' | head -c 20)"
  [[ -n "$ADMIN_PASS" ]] || ADMIN_PASS="$(head -c 16 /dev/urandom | xxd -p)"
  umask 077
  cat > "$CRED_FILE" <<EOF
# CPN FileGator admin credentials for ${DOMAIN}
# Generated $(date -u +%Y-%m-%dT%H:%M:%SZ). Change password in FileGator after first login.
domain=${DOMAIN}
username=${ADMIN_USER}
password=${ADMIN_PASS}
public_url=https://${DOMAIN}/${PUBLIC_SLUG}
repository=${REPO_ROOT}
filegator_version=${FILEGATOR_VERSION}
EOF
  chmod 600 "$CRED_FILE"
  log "Wrote admin credentials to ${CRED_FILE} (mode 600)"
fi

command -v php >/dev/null 2>&1 || die "php-cli is required to hash the admin password"
export FILEGATOR_ADMIN_PASS="$ADMIN_PASS"
HASH="$(php -r 'echo password_hash(getenv("FILEGATOR_ADMIN_PASS"), PASSWORD_BCRYPT);')" \
  || die "php password_hash failed"
unset FILEGATOR_ADMIN_PASS
[[ -n "$HASH" ]] || die "Empty password hash from php"

python3 - "$APP_DIR/private/users.json" "$ADMIN_USER" "$HASH" <<'PY'
import json, pathlib, sys
path = pathlib.Path(sys.argv[1])
user = sys.argv[2]
pw_hash = sys.argv[3]
data = {}
if path.is_file():
    try:
        data = json.loads(path.read_text(encoding="utf-8") or "{}")
    except Exception:
        data = {}
# Keep guest entry; replace/create admin id "1"
admin = {
    "username": user,
    "name": "Admin",
    "role": "admin",
    "homedir": "/",
    "permissions": "read|write|upload|download|batchdownload|zip|chmod",
    "password": pw_hash,
}
guest = data.get("2") or {
    "username": "guest",
    "name": "Guest",
    "role": "guest",
    "homedir": "/",
    "permissions": "",
    "password": "",
}
out = {"1": admin, "2": guest}
path.parent.mkdir(parents=True, exist_ok=True)
path.write_text(json.dumps(out, separators=(",", ":")), encoding="utf-8")
path.chmod(0o600)
PY

# Harden: deny HTTP to plugin tree secrets; only dist is linked into docroot.
cat > "${PLUGIN_SRC}/.htaccess" <<'EOF'
# Deny direct web access to the plugin package (FileGator is exposed via docroot symlink only).
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order Allow,Deny
  Deny from all
</IfModule>
EOF

cat > "${APP_DIR}/dist/.htaccess" <<'EOF'
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
</IfModule>
DirectoryIndex index.php
Options -Indexes
EOF

# Publish under site docroot (symlink to dist only; private/ stays outside web root).
TARGET="${DOCROOT}/${PUBLIC_SLUG}"
if [[ -e "$TARGET" && ! -L "$TARGET" ]]; then
  die "Refusing to replace non-symlink path: ${TARGET}"
fi
ln -sfn "${APP_DIR}/dist" "$TARGET"
log "Linked ${TARGET} -> ${APP_DIR}/dist"

# Ownership: match docroot owner when possible.
OWNER="$(stat -c '%U:%G' "$DOCROOT" 2>/dev/null || true)"
if [[ -n "$OWNER" && "$OWNER" != "UNKNOWN:UNKNOWN" ]]; then
  chown -R "$OWNER" "$APP_DIR" 2>/dev/null || true
  # Keep credentials root-only.
  chown root:root "$CRED_FILE" 2>/dev/null || true
  chmod 600 "$CRED_FILE" 2>/dev/null || true
fi
chmod -R u+rwX,g+rX,o-rwx "$APP_DIR" 2>/dev/null || true
chmod 600 "${APP_DIR}/configuration.php" "${APP_DIR}/private/users.json" 2>/dev/null || true

python3 - "$STATE_FILE" "$DOMAIN" "$REPO_ROOT" "$DOCROOT" "$PUBLIC_SLUG" "$FILEGATOR_VERSION" <<'PY'
import json, pathlib, sys, time
path = pathlib.Path(sys.argv[1])
state = {
    "schema_version": 1,
    "plugin_id": "fileGator",
    "domain": sys.argv[2],
    "repository": sys.argv[3],
    "docroot": sys.argv[4],
    "public_path": "/" + sys.argv[5],
    "filegator_version": sys.argv[6],
    "updated_unix": int(time.time()),
}
path.write_text(json.dumps(state, indent=2) + "\n", encoding="utf-8")
path.chmod(0o600)
PY

# Optional OLS/LSE: restart is often needed after new .htaccess under docroot.
if command -v systemctl >/dev/null 2>&1 && systemctl is-active --quiet lsws 2>/dev/null; then
  systemctl restart lsws || log "WARN: lsws restart failed; retry manually if /${PUBLIC_SLUG} 404s"
fi

log "OK: FileGator ${FILEGATOR_VERSION} ready for ${DOMAIN}"
log "Open: https://${DOMAIN}/${PUBLIC_SLUG}  (or http:// in lab)"
log "Login: ${ADMIN_USER}  (password in ${CRED_FILE})"
log "Repository jailed to: ${REPO_ROOT}"
log "Change the admin password after first login."
