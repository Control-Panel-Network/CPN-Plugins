#!/usr/bin/env bash
# Install the Tachyon VirusTotal extension into CPN webmail data.
# Run as root after the catalog plugin is under /home/<domain>/plugins/tachyonVirusTotal/
# or from this repo checkout.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="${SCRIPT_DIR}/plugin/virustotal"
TACHYON_DATA="${TACHYON_DATA_DIR:-/var/lib/cpn-webmail/tachyon/_data_/_default_}"
PLUGINS_DIR="${TACHYON_DATA}/plugins"
INI="${TACHYON_DATA}/configs/application.ini"
DEST="${PLUGINS_DIR}/virustotal"
PLUGIN_ID="virustotal"

[[ -f "${SRC}/index.php" ]] || die "Missing bundled plugin at ${SRC}/index.php"
[[ -d "${TACHYON_DATA}" ]] || die "Tachyon data not found at ${TACHYON_DATA}. Install Tachyon webmail first."
[[ -f "${INI}" ]] || die "Missing Tachyon config: ${INI}"

mkdir -p "${PLUGINS_DIR}"
rm -rf "${DEST}"
mkdir -p "${DEST}"
cp -a "${SRC}/." "${DEST}/"

# Enable extensions + add virustotal to enabled_list (idempotent).
python3 - "${INI}" "${PLUGIN_ID}" <<'PY'
import re, sys
path, plugin_id = sys.argv[1], sys.argv[2]
text = open(path, "r", encoding="utf-8", errors="replace").read()
if not re.search(r"(?im)^\[plugins\]", text):
    text += "\n[plugins]\nenable = On\nenabled_list = \"\"\n"

def ensure_plugins_enable(s: str) -> str:
    # Only flip enable under [plugins], not other sections.
    parts = re.split(r"(?im)(?=^\[)", s)
    out = []
    for part in parts:
        if re.match(r"(?im)^\[plugins\]", part):
            if re.search(r"(?im)^enable\s*=", part):
                part = re.sub(r"(?im)^(enable\s*=\s*).*$", r"\1On", part, count=1)
            else:
                part = part.rstrip() + "\nenable = On\n"
            if not re.search(r"(?im)^enabled_list\s*=", part):
                part = part.rstrip() + "\nenabled_list = \"\"\n"
        out.append(part)
    return "".join(out)

text = ensure_plugins_enable(text)

m = re.search(r"(?im)^(enabled_list\s*=\s*)(.*)$", text)
if m:
    raw = m.group(2).strip().strip('"').strip("'")
    parts = [p.strip() for p in raw.split(",") if p.strip()]
    if plugin_id not in parts:
        parts.append(plugin_id)
    new_line = m.group(1) + '"' + ",".join(parts) + '"'
    text = text[: m.start()] + new_line + text[m.end() :]

open(path, "w", encoding="utf-8", newline="\n").write(text)
print("updated", path)
PY

OWNER="cpn-webmail"
if id -u "${OWNER}" >/dev/null 2>&1; then
  chown -R "${OWNER}:${OWNER}" "${DEST}" "${PLUGINS_DIR}" 2>/dev/null || true
  chown "${OWNER}:${OWNER}" "${INI}" 2>/dev/null || true
fi
chmod -R u=rwX,g=rX,o= "${DEST}" 2>/dev/null || true

log "OK: Tachyon VirusTotal plugin installed at ${DEST}"
log "Optional API key: Tachyon Admin → Extensions → VirusTotal"
log "  Create account: https://www.virustotal.com/gui/join-us"
log "  Get API key:    https://www.virustotal.com/gui/my-apikey"
log "  API overview:   https://docs.virustotal.com/docs/api-overview"
log "Reload Tachyon / PHP-FPM if the extension list does not refresh."
exit 0
