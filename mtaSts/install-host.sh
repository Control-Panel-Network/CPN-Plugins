#!/usr/bin/env bash
# Enable CPN Email → MTA-STS host feature flag after catalog install.
# Catalog install alone also unlocks the UI when the panel detects plugin id mtaSts.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

FEATURE_DIR="${CPN_DATA_DIR:-/var/lib/cpn}/features"
mkdir -p "$FEATURE_DIR"
chmod 755 "$FEATURE_DIR"
printf 'enabled\n' >"$FEATURE_DIR/mta-sts.enabled"
chmod 644 "$FEATURE_DIR/mta-sts.enabled"
log "OK: MTA-STS feature flag written ($FEATURE_DIR/mta-sts.enabled)."
log "Reopen CPN Email → MTA-STS (or refresh the sidebar)."
