#!/bin/bash
set -euo pipefail

systemctl stop cpn-port-manager-mcp 2>/dev/null || true
systemctl disable cpn-port-manager-mcp 2>/dev/null || true
rm -f /etc/systemd/system/cpn-port-manager-mcp.service
systemctl daemon-reload 2>/dev/null || true

rm -rf /usr/local/cpn/port_manager
rm -rf /home/cpn/plugins/port_manager

if systemctl is-active --quiet lscpd 2>/dev/null; then
  systemctl reload lscpd || systemctl restart lscpd
fi

echo "[Port Manager] Uninstalled."
