#!/usr/bin/env bash
# Clear CPN Backup Destinations host feature flag.
# Host plugin files under /var/lib/cpn/host-plugins/backupDestinations/ are removed via the Plugin Store.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

FEATURE_FILE="${CPN_DATA_DIR:-/var/lib/cpn}/features/backup-destinations.enabled"
if [[ -f "$FEATURE_FILE" ]]; then
  rm -f "$FEATURE_FILE"
  log "Removed $FEATURE_FILE"
else
  log "No host feature flag present."
fi
log "OK: Backup Destinations host flag cleared. Uninstall the Host package from Plugins → Installed if needed."
log "Create, Restore, Schedule, and local Destinations remain available in core."
