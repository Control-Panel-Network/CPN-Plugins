#!/usr/bin/env bash
# Install all CPN-Plugins catalog ids onto a domain; write PASS/FAIL matrix.
set -u
DOMAIN="${1:-test2.newstargeted.com}"
OUT="/tmp/cpn-plugin-install-matrix.txt"
: > "$OUT"
sudo rm -f /var/lib/cpn/plugin-catalog-cache.json

IDS=(
  autoBanSecurityAlerts clamav contaboAutoSnapshot cspManager discordAuth discordWebhooks
  emailMarketing examplePlugin fail2ban googleTagManager limitedPhpmyAdmin memcacheManager
  ntMalwareApi ntHostingBilling panelAccess paypalPremiumPlugin pm2Manager port_manager
  postgresManager premiumPlugin redisManager roundcubeWebmail snappymailAdmin snappymailWebmail
  testPlugin
)

echo "domain=$DOMAIN" | tee -a "$OUT"
echo "started=$(date -u +%Y-%m-%dT%H:%M:%SZ)" | tee -a "$OUT"
PASS=0
FAIL=0
for id in "${IDS[@]}"; do
  echo "---- install $id ----" | tee -a "$OUT"
  if sudo cpn plugin install --domain "$DOMAIN" --id "$id" >>"$OUT" 2>&1; then
    echo "RESULT $id PASS" | tee -a "$OUT"
    PASS=$((PASS+1))
  else
    # already installed counts as PASS for matrix goals
    if sudo cpn plugin list --domain "$DOMAIN" 2>/dev/null | grep -qi "$id"; then
      echo "RESULT $id PASS (already installed)" | tee -a "$OUT"
      PASS=$((PASS+1))
    else
      echo "RESULT $id FAIL" | tee -a "$OUT"
      FAIL=$((FAIL+1))
    fi
  fi
done
echo "summary pass=$PASS fail=$FAIL" | tee -a "$OUT"
sudo cpn plugin list --domain "$DOMAIN" 2>&1 | tee -a "$OUT"
echo "matrix=$OUT"
