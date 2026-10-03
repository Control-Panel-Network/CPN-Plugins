#!/usr/bin/env bash
# Re-apply FileGator wiring for a site without wiping private data / credentials.
# Usage: ./heal.sh <domain>
set -euo pipefail
PLUGIN_SRC="$(cd "$(dirname "$0")" && pwd)"
DOMAIN="${1:-}"
[[ -n "$DOMAIN" ]] || { echo "Usage: $0 <domain>" >&2; exit 1; }
exec bash "${PLUGIN_SRC}/install.sh" "$DOMAIN" --heal
