#!/usr/bin/env bash
# Remove ClamAV host packages installed for CPN (optional).
# Does not remove /var/lib/cpn or site plugin files.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

systemctl disable --now clamd@scan 2>/dev/null || true
systemctl disable --now clamav-daemon 2>/dev/null || true
systemctl disable --now clamd 2>/dev/null || true

if command -v dnf >/dev/null 2>&1; then
  dnf remove -y clamav clamav-update clamd 2>/dev/null || dnf remove -y clamav clamav-update || true
elif command -v yum >/dev/null 2>&1; then
  yum remove -y clamav clamav-update clamd 2>/dev/null || yum remove -y clamav || true
elif command -v apt-get >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get remove -y clamav clamav-daemon clamav-freshclam || true
else
  die "No supported package manager."
fi

log "ClamAV host packages removed (best effort). Site plugin folder is unchanged."
