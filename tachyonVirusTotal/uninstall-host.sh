#!/usr/bin/env bash
# Remove the Tachyon VirusTotal extension from CPN webmail data.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

TACHYON_DATA="${TACHYON_DATA_DIR:-/var/lib/cpn-webmail/tachyon/_data_/_default_}"
DEST="${TACHYON_DATA}/plugins/virustotal"
INI="${TACHYON_DATA}/configs/application.ini"
PLUGIN_ID="virustotal"

if [[ -d "${DEST}" ]]; then
  rm -rf "${DEST}"
  log "Removed ${DEST}"
else
  log "Plugin directory already absent: ${DEST}"
fi

if [[ -f "${INI}" ]]; then
  python3 - "${INI}" "${PLUGIN_ID}" <<'PY'
import re, sys
path, plugin_id = sys.argv[1], sys.argv[2]
text = open(path, "r", encoding="utf-8", errors="replace").read()
m = re.search(r"(?im)^(enabled_list\s*=\s*)(.*)$", text)
if m:
    raw = m.group(2).strip().strip('"').strip("'")
    parts = [p.strip() for p in raw.split(",") if p.strip() and p.strip() != plugin_id]
    new_line = m.group(1) + '"' + ",".join(parts) + '"'
    text = text[: m.start()] + new_line + text[m.end() :]
    open(path, "w", encoding="utf-8", newline="\n").write(text)
    print("updated", path)
PY
fi

log "OK: Tachyon VirusTotal extension disabled/removed."
exit 0
