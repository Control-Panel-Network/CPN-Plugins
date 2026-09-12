#!/usr/bin/env bash
# Install ClamAV host packages for CPN Panel (free malware path).
# Run as root after the catalog plugin is copied under /home/<domain>/plugins/clamav/.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

if command -v clamscan >/dev/null 2>&1 || command -v clamdscan >/dev/null 2>&1 || command -v clamd >/dev/null 2>&1; then
  log "ClamAV tools already present."
  clamscan --version 2>/dev/null || clamdscan --version 2>/dev/null || true
  exit 0
fi

if command -v dnf >/dev/null 2>&1; then
  log "Installing ClamAV via dnf..."
  dnf install -y clamav clamav-update clamd || dnf install -y clamav clamav-update || die "dnf install failed"
elif command -v yum >/dev/null 2>&1; then
  log "Installing ClamAV via yum..."
  yum install -y clamav clamav-update clamd || yum install -y clamav clamav-update || die "yum install failed"
elif command -v apt-get >/dev/null 2>&1; then
  log "Installing ClamAV via apt..."
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get install -y clamav clamav-daemon clamav-freshclam || die "apt install failed"
else
  die "No supported package manager (dnf/yum/apt-get)."
fi

if command -v freshclam >/dev/null 2>&1; then
  log "Updating virus definitions (freshclam)..."
  freshclam || log "WARN: freshclam failed; retry later."
fi

systemctl enable --now clamd@scan 2>/dev/null \
  || systemctl enable --now clamav-daemon 2>/dev/null \
  || systemctl enable --now clamd 2>/dev/null \
  || log "WARN: could not enable clamd unit; clamscan CLI may still work."

if command -v clamscan >/dev/null 2>&1 || command -v clamdscan >/dev/null 2>&1 || command -v clamd >/dev/null 2>&1; then
  log "OK: ClamAV installed. Reopen CPN Security → Malware scan."
  clamscan --version 2>/dev/null || true
  exit 0
fi

die "Packages ran but clamscan/clamd still missing."
