# Clean2 plugin install matrix (13/09/2026)

Lab: `CPN-AlmaLinux-9-clean2` · SSH `127.0.0.1:2226` · Panel `http://127.0.0.1:2090` · Site `test2.newstargeted.com` (already present) · CPN `0.2.6-alpha.36`

Catalog cache cleared: `sudo rm -f /var/lib/cpn/plugin-catalog-cache.json`

## Plugin Store / Installed UI

| Check | Result |
|-------|--------|
| `GET /plugins?view=store&domain=test2...&refresh=1` | PASS (200; after refresh, Billing/Hosting shows `ntHostingBilling`) |
| `GET /plugins?view=installed&domain=test2...` | PASS (lists `ntHostingBilling`) |
| `GET /plugins/settings?...&id=ntHostingBilling` | PASS |
| `GET /plugins/dashboard?...&id=ntHostingBilling` | PASS |
| Host packages tab | PASS (200; separate from catalog installs) |

## Catalog install (`cpn plugin install --domain test2.newstargeted.com --id …`)

| Plugin id | Result |
|-----------|--------|
| autoBanSecurityAlerts | PASS |
| clamav | PASS |
| contaboAutoSnapshot | PASS |
| cspManager | PASS |
| discordAuth | PASS |
| discordWebhooks | PASS |
| emailMarketing | PASS |
| examplePlugin | PASS |
| fail2ban | PASS |
| googleTagManager | PASS |
| limitedPhpmyAdmin | PASS |
| memcacheManager | PASS |
| ntMalwareApi | PASS |
| ntHostingBilling | PASS |
| panelAccess | PASS |
| paypalPremiumPlugin | PASS |
| pm2Manager | PASS |
| port_manager | PASS |
| postgresManager | PASS |
| premiumPlugin | PASS |
| redisManager | PASS |
| roundcubeWebmail | PASS |
| snappymailAdmin | PASS |
| snappymailWebmail | PASS |
| testPlugin | PASS |

**Summary: 25 PASS / 0 FAIL**

## ntHostingBilling post-install

| Step | Result |
|------|--------|
| `php -l` all plugin PHP files | PASS (no syntax errors) |
| `install-host.sh test2.newstargeted.com` | PASS (`public_html/nt-billing` symlink) |
| SQLite boot / migrate | PASS (`db_ok`) |

## API (api.newstargeted.com)

| Endpoint | Result |
|----------|--------|
| `GET /api/cpn-plugins/v1/catalog.php` | PASS (includes `ntHostingBilling`) |
| `GET /api/cpn-plugins/v1/checkout.php?plugin=ntHostingBilling` | PASS |
| `allowed_plugins.json` includes `ntHostingBilling` + `ntMalwareApi` | PASS |
| Existing grant/activation/entitlement endpoints | Unchanged; accept `plugin_name=ntHostingBilling` |

## Known notes (not install blockers)

- Store UI paginates (default page size); `ntHostingBilling` appears on later pages / Billing category / search `Hosting` after catalog refresh.
- Commerce features remain license-gated until Shop Grant / activation key / entitlement token is configured in `config.php`.
- Site `vhost_wired=false` on clean2: public `http://test2.../nt-billing` may need OLS vhost wiring for HTTP smoke; panel plugin install path is independent.
- WHMCS reference tree `whmcs_v7100_full` was not found under the GitHub workspace; product intent used NT paid-plugin + CPN patterns instead.

## Publish

- CPN-Plugins commit: `72c07ae` on `main` · https://github.com/Control-Panel-Network/CPN-Plugins/commit/72c07ae
- Repo: https://github.com/Control-Panel-Network/CPN-Plugins
