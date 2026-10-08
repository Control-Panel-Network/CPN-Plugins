#!/usr/bin/env bash
# Enable CPN Email → Proton Mail host feature flag after catalog install.
# Catalog Host install also unlocks the UI when the panel detects plugin id protonMail.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

FEATURE_DIR="${CPN_DATA_DIR:-/var/lib/cpn}/features"
mkdir -p "$FEATURE_DIR"
chmod 755 "$FEATURE_DIR"
printf 'enabled\n' >"$FEATURE_DIR/proton-mail.enabled"
chmod 644 "$FEATURE_DIR/proton-mail.enabled"
log "OK: Proton Mail feature flag written ($FEATURE_DIR/proton-mail.enabled)."
log "Reopen CPN Email → Proton Mail (or refresh the sidebar)."
log "Note: CPN does not host Proton encryption. Use https://mail.proton.me or Proton Mail Bridge on a workstation."
