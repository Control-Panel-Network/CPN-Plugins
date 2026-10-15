#!/usr/bin/env bash
# Enable CPN Backup Destinations host feature flag after catalog install.
# Catalog Host install also unlocks the UI when the panel detects plugin id backupDestinations.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

FEATURE_DIR="${CPN_DATA_DIR:-/var/lib/cpn}/features"
mkdir -p "$FEATURE_DIR"
chmod 755 "$FEATURE_DIR"
printf 'enabled\n' >"$FEATURE_DIR/backup-destinations.enabled"
chmod 644 "$FEATURE_DIR/backup-destinations.enabled"
log "OK: Backup Destinations feature flag written ($FEATURE_DIR/backup-destinations.enabled)."

# Soft hints only: missing tools must not fail Host install.
if command -v restic >/dev/null 2>&1; then
  log "Hint: restic found on PATH (Incremental jobs can use it)."
else
  log "Hint: restic not on PATH yet. Install restic later for Incremental jobs (install still OK)."
fi
if command -v aws >/dev/null 2>&1; then
  log "Hint: aws CLI found on PATH (optional for S3 tooling)."
else
  log "Hint: aws CLI not on PATH (optional). S3 destination UI still works with panel-stored credentials."
fi

log "Reopen CPN Backups → Destinations / Google Drive / Remote / S3 / Incremental (or refresh the sidebar)."
