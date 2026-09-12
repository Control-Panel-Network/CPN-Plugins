# CPN-Plugins

Community plugin catalog for **CPN Panel** (Control Panel Network).

Plugins install under `/home/<domain>/plugins/<plugin-id>/` (subdomains nest under the parent domain home).

## Catalog

CPN Panel fetches the `main` branch tarball and discovers plugin folders that contain `meta.xml` (and optional `cpn-plugin.json`). Index: `catalog.json`.

Refresh on a panel host (cache TTL is about one hour, or delete the cache file):

```bash
sudo rm -f /var/lib/cpn/plugin-catalog-cache.json
# Reopen Plugins → Plugin Store, or:
sudo cpn plugin list --catalog
```

## Security host plugins (epic)

| Id | Pricing | Role |
|----|---------|------|
| `fail2ban` | free | Host fail2ban; see `fail2ban/CPN.md` and `install-host.sh` |
| `clamav` | free | Host ClamAV for Security → Malware scan |
| `ntMalwareApi` | paid | Documents `api.newstargeted.com` entitlement; secrets only in `/var/lib/cpn/malware.json` |
| `ntHostingBilling` | paid | Hosting commerce (clients, invoices, subscriptions, ownership); license via `api.newstargeted.com` |

## Email enablement plugins

| Id | Pricing | Role |
|----|---------|------|
| `mtaSts` | free | Unlocks Email → MTA-STS in CPN Panel |
| `bimi` | free | Unlocks Email → BIMI in CPN Panel |

Host packages such as MariaDB, OpenLiteSpeed, and phpMyAdmin stay on the panel **Plugins → Host packages** tab (`cpn app`), not as separate catalog folders unless a site-scoped drop-in exists.

## License

See `LICENSE`. Individual plugins may carry their own notices.
