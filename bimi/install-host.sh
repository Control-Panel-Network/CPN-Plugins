#!/usr/bin/env bash
# Enable CPN Email → BIMI host feature flag after catalog install.
# Catalog install alone also unlocks the UI when the panel detects plugin id bimi.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

FEATURE_DIR="${CPN_DATA_DIR:-/var/lib/cpn}/features"
mkdir -p "$FEATURE_DIR"
chmod 755 "$FEATURE_DIR"
printf 'enabled\n' >"$FEATURE_DIR/bimi.enabled"
chmod 644 "$FEATURE_DIR/bimi.enabled"
log "OK: BIMI feature flag written ($FEATURE_DIR/bimi.enabled)."
log "Reopen CPN Email → BIMI (or refresh the sidebar)."
