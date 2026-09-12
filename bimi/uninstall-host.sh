#!/usr/bin/env bash
# Clear CPN Email → BIMI host feature flag.
# Site plugin folders under /home/<domain>/plugins/bimi/ are removed separately via the Plugin Store.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

FEATURE_FILE="${CPN_DATA_DIR:-/var/lib/cpn}/features/bimi.enabled"
if [[ -f "$FEATURE_FILE" ]]; then
  rm -f "$FEATURE_FILE"
  log "Removed $FEATURE_FILE"
else
  log "No host feature flag present."
fi
log "OK: BIMI host flag cleared. Uninstall site copies from Plugins → Installed if needed."
