# MTA-STS (Email TLS policy)

Free CPN Panel catalog plugin. Installing it unlocks **Email → MTA-STS** (sidebar and hub tile). Policy storage, recommended DNS, and optional Cloudflare push stay in the panel; this package is the enablement gate.

## Install

```bash
# Plugin Store, or:
sudo cpn plugin install --domain example.com --id mtaSts

# Optional host flag (also written automatically by newer panel installs):
sudo /home/example.com/plugins/mtaSts/install-host.sh
```

## Verify

- Sidebar: Email → MTA-STS appears
- Open `https://<panel>/email/mta-sts` and save policy / DNS for a domain

## Uninstall

```bash
sudo /home/example.com/plugins/mtaSts/uninstall-host.sh
sudo cpn plugin remove --domain example.com --id mtaSts --yes
```
