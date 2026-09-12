#!/usr/bin/env bash
# Install fail2ban host package for CPN Panel feature gate (Security → Fail2ban).
# Catalog copy may also include a legacy management UI tree; CPN gates on fail2ban-client.
set -euo pipefail

log() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  die "Run as root (sudo)."
fi

if command -v fail2ban-client >/dev/null 2>&1 || command -v fail2ban-server >/dev/null 2>&1; then
  log "fail2ban already present."
  fail2ban-client --version 2>/dev/null || true
  systemctl enable --now fail2ban 2>/dev/null || true
  exit 0
fi

if command -v dnf >/dev/null 2>&1; then
  dnf install -y fail2ban fail2ban-firewalld || dnf install -y fail2ban || die "dnf install failed"
elif command -v yum >/dev/null 2>&1; then
  yum install -y fail2ban || die "yum install failed"
elif command -v apt-get >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get install -y fail2ban || die "apt install failed"
else
  die "No supported package manager (dnf/yum/apt-get)."
fi

systemctl enable --now fail2ban || die "Could not enable fail2ban service"

if command -v fail2ban-client >/dev/null 2>&1; then
  log "OK: fail2ban installed. Reopen CPN Security → Fail2ban."
  fail2ban-client status || true
  exit 0
fi

die "Package install finished but fail2ban-client is missing."
